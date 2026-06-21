<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

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

    foreach (preg_split("/\r\n|\n|\r/", $contents) as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $_ENV[trim($key)] = trim($value, "\"'");
    }
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(["status" => "error", "message" => "POST required."], 405);
}

loadEnv(__DIR__ . '/../fileSystemAccess.env');

$debugMode = isset($_ENV['AUTH_DEBUG']) && $_ENV['AUTH_DEBUG'] === 'true';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    sendJson(["status" => "error", "message" => "Invalid JSON input."], 400);
}

$deviceName = isset($input['device']) ? trim($input['device']) : '';

if ($deviceName === '') {
    sendJson(["status" => "error", "message" => "Missing device name."], 400);
}

$challenge = bin2hex(random_bytes(32));
$expiresAt = time() + 300;

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

$now = time();

foreach ($storedChallenges as $storedDevice => $storedChallengeData) {
    if (!is_array($storedChallengeData) || empty($storedChallengeData['expires']) || (int) $storedChallengeData['expires'] < $now) {
        unset($storedChallenges[$storedDevice]);
    }
}

$storedChallenges[$deviceName] = [
    "challenge" => $challenge,
    "expires" => $expiresAt
];

if (!writeJsonToLockedFile($handle, $storedChallenges)) {
    flock($handle, LOCK_UN);
    fclose($handle);

    sendJson(["status" => "error", "message" => "Failed to store challenge."], 500);
}

flock($handle, LOCK_UN);
fclose($handle);

sendJson([
    "status" => "success",
    "device" => $deviceName,
    "challenge" => $challenge,
    "expires" => $expiresAt
]);
?>