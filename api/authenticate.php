<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/jwtHelper.php';
require_once __DIR__ . '/envFormatHelper.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$debugMode = false;

function sendJson($payload, $statusCode = 200)
{
    if (ob_get_length()) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

register_shutdown_function(function () use (&$debugMode) {
    $error = error_get_last();

    if ($error !== null && !headers_sent()) {
        sendJson([
            "status" => "error",
            "message" => "Fatal error occurred.",
            "details" => $debugMode ? $error['message'] : null
        ], 500);
    }
});

function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        sendJson(["status" => "error", "message" => "Environment file not found."], 500);
    }

    $contents = file_get_contents($filePath);

    if ($contents === false) {
        sendJson(["status" => "error", "message" => "Failed to read environment file."], 500);
    }

    $_ENV = [];

    if (preg_match('/^DEVICE_KEYS=(\{.*\})\s*$/ms', $contents, $matches)) {
        $_ENV['DEVICE_KEYS'] = trim($matches[1]);
    }

    foreach (preg_split("/\r\n|\n|\r/", $contents) as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);

        if ($key === 'DEVICE_KEYS') {
            continue;
        }

        $_ENV[$key] = trim($value, "\"'");
    }
}

function readJsonFileUnlocked($filePath)
{
    if (!file_exists($filePath)) {
        if (file_put_contents($filePath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
            return [];
        }
    }

    $contents = file_get_contents($filePath);

    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $data = json_decode($contents, true);

    return is_array($data) ? $data : [];
}

function readJsonFromLockedFile($handle)
{
    rewind($handle);
    $contents = stream_get_contents($handle);

    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $data = json_decode($contents, true);

    if (!is_array($data)) {
        return [];
    }

    return $data;
}

function writeJsonToLockedFile($handle, $data)
{
    rewind($handle);
    ftruncate($handle, 0);

    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        return false;
    }

    if (fwrite($handle, $encoded) === false) {
        return false;
    }

    fflush($handle);
    return true;
}

function getStoredDeviceKeys()
{
    $deviceKeysText = $_ENV['DEVICE_KEYS'] ?? '{}';

    $deviceKeys = json_decode($deviceKeysText, true);

    if (is_array($deviceKeys)) {
        return $deviceKeys;
    }

    if (function_exists('parseDeviceKeysForEnvCleanup')) {
        $deviceKeys = parseDeviceKeysForEnvCleanup($deviceKeysText);

        if (is_array($deviceKeys)) {
            return $deviceKeys;
        }
    }

    return [];
}

function getStoredChallenges()
{
    return readJsonFileUnlocked(__DIR__ . '/../json/challenges.json');
}

function normalizeStoredPem($pem)
{
    $pem = trim($pem);
    $pem = str_replace("\\r\\n", "\n", $pem);
    $pem = str_replace("\\n", "\n", $pem);
    $pem = str_replace("\r\n", "\n", $pem);
    $pem = str_replace("\r", "\n", $pem);
    $pem = preg_replace("/\n{3,}/", "\n\n", $pem);

    return trim($pem);
}

function freeOpenSslKeyIfNeeded($key)
{
    if (is_resource($key)) {
        openssl_free_key($key);
    }
}

function getPublicKeyResourceFromStoredKey($storedKey)
{
    $storedKey = normalizeStoredPem($storedKey);

    $publicKey = @openssl_pkey_get_public($storedKey);

    if ($publicKey !== false) {
        return $publicKey;
    }

    $privateKey = @openssl_pkey_get_private($storedKey);

    if ($privateKey !== false) {
        $keyDetails = openssl_pkey_get_details($privateKey);

        if (is_array($keyDetails) && !empty($keyDetails['key'])) {
            $derivedPublicKey = normalizeStoredPem($keyDetails['key']);
            $publicKey = @openssl_pkey_get_public($derivedPublicKey);

            freeOpenSslKeyIfNeeded($privateKey);

            if ($publicKey !== false) {
                return $publicKey;
            }
        }

        freeOpenSslKeyIfNeeded($privateKey);
    }

    return false;
}

function verifyDeviceSignature($deviceName, $challenge, $signatureBase64)
{
    $deviceKeys = getStoredDeviceKeys();
    $storedChallenges = getStoredChallenges();

    if (!isset($deviceKeys[$deviceName])) {
        return [false, "No approved key found for this device."];
    }

    if (!isset($storedChallenges[$deviceName])) {
        return [false, "No stored challenge found for this device."];
    }

    $storedChallenge = $storedChallenges[$deviceName]['challenge'] ?? '';
    $challengeExpires = $storedChallenges[$deviceName]['expires'] ?? 0;

    if ($storedChallenge !== $challenge) {
        return [false, "Challenge mismatch."];
    }

    if ((int) $challengeExpires < time()) {
        return [false, "Challenge expired."];
    }

    $publicKey = getPublicKeyResourceFromStoredKey($deviceKeys[$deviceName]);

    if ($publicKey === false) {
        return [false, "Stored key is invalid."];
    }

    $signature = base64_decode($signatureBase64, true);

    if ($signature === false) {
        freeOpenSslKeyIfNeeded($publicKey);
        return [false, "Signature format is invalid."];
    }

    $verified = openssl_verify($challenge, $signature, $publicKey, OPENSSL_ALGO_SHA256);

    freeOpenSslKeyIfNeeded($publicKey);

    if ($verified === 1) {
        return [true, "Signature verified."];
    }

    if ($verified === 0) {
        return [false, "Signature verification failed."];
    }

    return [false, "OpenSSL verification error."];
}

function deleteUsedChallenge($deviceName)
{
    $challengeFile = __DIR__ . '/../json/challenges.json';
    $handle = fopen($challengeFile, 'c+');

    if ($handle === false) {
        sendJson(["status" => "error", "message" => "Failed to open challenge store."], 500);
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        sendJson(["status" => "error", "message" => "Failed to lock challenge store."], 500);
    }

    $storedChallenges = readJsonFromLockedFile($handle);
    unset($storedChallenges[$deviceName]);

    if (!writeJsonToLockedFile($handle, $storedChallenges)) {
        flock($handle, LOCK_UN);
        fclose($handle);

        sendJson(["status" => "error", "message" => "Failed to update challenge store."], 500);
    }

    flock($handle, LOCK_UN);
    fclose($handle);
}

function storeRefreshToken($deviceName, $refreshToken)
{
    $refreshTokenFile = __DIR__ . '/../json/refreshTokens.json';
    $handle = fopen($refreshTokenFile, 'c+');

    if ($handle === false) {
        sendJson(["status" => "error", "message" => "Failed to open refresh token store."], 500);
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        sendJson(["status" => "error", "message" => "Failed to lock refresh token store."], 500);
    }

    $storedTokens = readJsonFromLockedFile($handle);
    $storedTokens[$deviceName] = $refreshToken;

    if (!writeJsonToLockedFile($handle, $storedTokens)) {
        flock($handle, LOCK_UN);
        fclose($handle);

        sendJson(["status" => "error", "message" => "Failed to store refresh token."], 500);
    }

    flock($handle, LOCK_UN);
    fclose($handle);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(["status" => "error", "message" => "POST required."], 405);
}

$envFile = __DIR__ . '/../fileSystemAccess.env';
$envCleaned = normalizeAuthEnvFile($envFile);
loadEnv($envFile);

$debugMode = isset($_ENV['AUTH_DEBUG']) && $_ENV['AUTH_DEBUG'] === 'true';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    sendJson(["status" => "error", "message" => "Invalid JSON format."], 400);
}

$submittedPassword = $input['password'] ?? null;
$deviceName = isset($input['device']) ? trim($input['device']) : '';
$signature = $input['signature'] ?? null;
$challenge = $input['challenge'] ?? null;

if (!$submittedPassword || $deviceName === '' || !$signature || !$challenge) {
    sendJson(["status" => "error", "message" => "Missing authentication parameters."], 400);
}

$role = null;

if ($submittedPassword === ($_ENV['ACCESS_PASSWORD'] ?? '')) {
    $role = 'admin';
} elseif ($submittedPassword === ($_ENV['EDITOR_PASSWORD'] ?? '')) {
    $role = 'editor';
} elseif ($submittedPassword === ($_ENV['VIEWER_PASSWORD'] ?? '')) {
    $role = 'viewer';
}

if (!$role) {
    sendJson(["status" => "error", "message" => "Invalid password."], 401);
}

[$sigOk, $sigMessage] = verifyDeviceSignature($deviceName, $challenge, $signature);

if (!$sigOk) {
    sendJson(["status" => "error", "message" => $sigMessage], 401);
}

if (empty($_ENV['SECRET_KEY'])) {
    sendJson(["status" => "error", "message" => "Server misconfiguration: SECRET_KEY missing."], 500);
}

$secretKey = $_ENV['SECRET_KEY'];
$issuedAt = time();

$accessPayload = [
    "type" => "access",
    "role" => $role,
    "device" => $deviceName,
    "exp" => $issuedAt + 900,
    "iat" => $issuedAt,
];

$refreshPayload = [
    "type" => "refresh",
    "role" => $role,
    "device" => $deviceName,
    "exp" => $issuedAt + (60 * 60 * 24 * 7),
    "iat" => $issuedAt,
];

try {
    $accessToken = bwJwtEncode($accessPayload, $secretKey);
    $refreshToken = bwJwtEncode($refreshPayload, $secretKey);
} catch (Exception $e) {
    sendJson([
        "status" => "error",
        "message" => "Token generation failed.",
        "details" => $debugMode ? $e->getMessage() : null
    ], 500);
}

deleteUsedChallenge($deviceName);
storeRefreshToken($deviceName, $refreshToken);

sendJson([
    "status" => "success",
    "message" => "Authentication successful.",
    "role" => $role,
    "device" => $deviceName,
    "accessToken" => $accessToken,
    "refreshToken" => $refreshToken
]);
?>