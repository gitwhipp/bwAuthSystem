<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/jwtHelper.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

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

function rejectToken($message, $reason = '', $details = null)
{
    $payload = [
        "status" => "rejected",
        "message" => $message
    ];

    if ($reason !== '') {
        $payload["reason"] = $reason;
    }

    if ($details !== null) {
        $payload["details"] = $details;
    }

    sendJson($payload, 200);
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(["status" => "error", "message" => "POST required."], 405);
}

function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        sendJson(["status" => "error", "message" => "Environment file not found."], 500);
    }

    $contents = file_get_contents($filePath);

    if ($contents === false) {
        sendJson(["status" => "error", "message" => "Failed to read environment file."], 500);
    }

    $envData = [];

    if (preg_match('/^DEVICE_KEYS=(\{.*\})\s*$/m', $contents, $matches)) {
        $envData['DEVICE_KEYS'] = trim($matches[1]);
    }

    foreach (preg_split("/\r\n|\n|\r/", $contents) as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);

        if ($key === 'DEVICE_KEYS' || $key === 'REFRESH_TOKENS' || $key === 'CHALLENGES') {
            continue;
        }

        $envData[$key] = trim($value, '"\'');
    }

    return $envData;
}

$envFile = __DIR__ . '/../fileSystemAccess.env';
$envVars = loadEnv($envFile);

$debugMode = isset($envVars['AUTH_DEBUG']) && $envVars['AUTH_DEBUG'] === 'true';

if (!isset($envVars['SECRET_KEY']) || empty($envVars['SECRET_KEY'])) {
    sendJson(["status" => "error", "message" => "SECRET_KEY is missing from .env"], 500);
}

$secretKey = $envVars['SECRET_KEY'];

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!is_array($input)) {
    sendJson(["status" => "error", "message" => "Invalid JSON format."], 400);
}

if (!isset($input['token']) || empty($input['token'])) {
    sendJson(["status" => "error", "message" => "No token provided."], 400);
}

$token = trim($input['token']);

[$decodeOk, $decoded, $decodeMessage] = bwJwtDecode($token, $secretKey);

if (!$decodeOk || !is_array($decoded)) {
    rejectToken(
        "Token rejected.",
        "invalid_or_expired_token",
        $debugMode ? $decodeMessage : null
    );
}

if (!isset($decoded['type']) || $decoded['type'] !== 'access') {
    rejectToken("Token rejected.", "invalid_token_type");
}

if (!isset($decoded['exp']) || time() >= (int) $decoded['exp']) {
    rejectToken("Token rejected.", "token_expired");
}

$userRole = $decoded['role'] ?? "unknown";
$deviceName = isset($decoded['device']) ? trim($decoded['device']) : '';

sendJson([
    "status" => "success",
    "message" => "Token is valid.",
    "role" => $userRole,
    "device" => $deviceName
]);