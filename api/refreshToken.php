<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/jwtHelper.php';

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

function getJsonEnvArray($key)
{
    $data = json_decode($_ENV[$key] ?? '{}', true);
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

function readRefreshTokens()
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

    flock($handle, LOCK_UN);
    fclose($handle);

    return $storedTokens;
}

function storeRefreshTokens($storedTokens)
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

loadEnv(__DIR__ . '/../fileSystemAccess.env');

$debugMode = isset($_ENV['AUTH_DEBUG']) && $_ENV['AUTH_DEBUG'] === 'true';

$accessTokenHours = 1;
$refreshTokenDays = 15;

$accessTokenLifetime = 60 * 60 * $accessTokenHours;
$refreshTokenLifetime = 60 * 60 * 24 * $refreshTokenDays;

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    sendJson(["status" => "error", "message" => "Invalid JSON format."], 400);
}

$submittedRefreshToken = $input['refreshToken'] ?? '';
$submittedDevice = isset($input['device']) ? trim($input['device']) : '';

if (!$submittedRefreshToken) {
    sendJson(["status" => "error", "message" => "Missing refresh token."], 400);
}

if (!$submittedDevice) {
    sendJson(["status" => "error", "message" => "Missing device."], 400);
}

if (empty($_ENV['SECRET_KEY'])) {
    sendJson(["status" => "error", "message" => "Server misconfiguration: SECRET_KEY missing."], 500);
}

$secretKey = $_ENV['SECRET_KEY'];

[$decodeOk, $decoded, $decodeMessage] = bwJwtDecode($submittedRefreshToken, $secretKey);

if (!$decodeOk || !is_array($decoded)) {
    sendJson([
        "status" => "error",
        "message" => "Invalid refresh token.",
        "error" => $debugMode ? $decodeMessage : null
    ], 403);
}

if (($decoded['type'] ?? '') !== 'refresh') {
    sendJson(["status" => "error", "message" => "Invalid token type."], 403);
}

if (($decoded['exp'] ?? 0) < time()) {
    sendJson(["status" => "error", "message" => "Refresh token expired."], 403);
}

if (empty($decoded['device']) || trim($decoded['device']) !== $submittedDevice) {
    sendJson(["status" => "error", "message" => "Refresh token does not match device."], 403);
}

$deviceKeys = getJsonEnvArray('DEVICE_KEYS');
$storedRefreshTokens = readRefreshTokens();

if (!isset($deviceKeys[$submittedDevice])) {
    sendJson(["status" => "error", "message" => "Device is no longer approved."], 403);
}

if (!isset($storedRefreshTokens[$submittedDevice])) {
    sendJson(["status" => "error", "message" => "No approved refresh token found for this device."], 403);
}

if (!hash_equals($storedRefreshTokens[$submittedDevice], $submittedRefreshToken)) {
    sendJson(["status" => "error", "message" => "Refresh token has been revoked or replaced."], 403);
}

$issuedAt = time();

$accessPayload = [
    "type" => "access",
    "role" => $decoded['role'],
    "device" => $decoded['device'],
    "exp" => $issuedAt + $accessTokenLifetime,
    "iat" => $issuedAt,
];

$refreshPayload = [
    "type" => "refresh",
    "role" => $decoded['role'],
    "device" => $decoded['device'],
    "exp" => $issuedAt + $refreshTokenLifetime,
    "iat" => $issuedAt,
];

try {
    $newAccessToken = bwJwtEncode($accessPayload, $secretKey);
    $newRefreshToken = bwJwtEncode($refreshPayload, $secretKey);
} catch (Exception $e) {
    sendJson([
        "status" => "error",
        "message" => "Failed to generate new tokens.",
        "error" => $debugMode ? $e->getMessage() : null
    ], 500);
}

$storedRefreshTokens[$submittedDevice] = $newRefreshToken;
storeRefreshTokens($storedRefreshTokens);

sendJson([
    "status" => "success",
    "token" => $newAccessToken,
    "accessToken" => $newAccessToken,
    "refreshToken" => $newRefreshToken,
    "role" => $decoded['role'],
    "device" => $decoded['device']
]);
?>