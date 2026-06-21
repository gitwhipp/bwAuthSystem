<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/jwtHelper.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

function sendJson($payload, $statusCode = 200)
{
    if (ob_get_length())
        ob_end_clean();
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(["status" => "error", "message" => "Invalid request method."], 405);
}

$request_body = file_get_contents("php://input");
$data = json_decode($request_body, true);

if (!is_array($data)) {
    sendJson(["status" => "error", "message" => "Invalid JSON input."], 400);
}

if (!isset($data['keyname']) || !isset($data['publickey']) || !isset($data['token'])) {
    sendJson(["status" => "error", "message" => "Invalid request. Missing key name, public key, or access token."], 400);
}

$key_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $data['keyname']);
$public_key = trim($data['publickey']);
$token = trim($data['token']);

if ($key_name === '' || $public_key === '' || $token === '') {
    sendJson(["status" => "error", "message" => "Missing key name, public key, or access token."], 400);
}

$env_file = __DIR__ . '/../fileSystemAccess.env';

if (!file_exists($env_file)) {
    sendJson(["status" => "error", "message" => "Error: .env file not found."], 500);
}

$env_content = file_get_contents($env_file);

if ($env_content === false) {
    sendJson(["status" => "error", "message" => "Failed to read .env file."], 500);
}

preg_match('/^SECRET_KEY=(.*)$/m', $env_content, $secretMatches);
$secretKey = isset($secretMatches[1]) ? trim($secretMatches[1], "\"'") : '';

if ($secretKey === '') {
    sendJson(["status" => "error", "message" => "SECRET_KEY missing."], 500);
}

[$decodeOk, $decoded, $decodeMessage] = bwJwtDecode($token, $secretKey);

if (!$decodeOk || !is_array($decoded)) {
    sendJson(["status" => "error", "message" => "Invalid access token."], 403);
}

if (($decoded['type'] ?? '') !== 'access') {
    sendJson(["status" => "error", "message" => "Access token required."], 403);
}

if (($decoded['role'] ?? '') !== 'admin') {
    sendJson(["status" => "error", "message" => "Admin role required."], 403);
}

preg_match('/DEVICE_KEYS=(\{.*?\})/s', $env_content, $matches);
$device_keys_json = isset($matches[1]) ? $matches[1] : '{}';

$device_keys = json_decode($device_keys_json, true);
if (!is_array($device_keys)) {
    $device_keys = [];
}

$formatted_public_key = str_replace("\n", "\\n", $public_key);

$device_keys[$key_name] = $formatted_public_key;

$updated_device_keys_json = json_encode($device_keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

$updated_env_content = preg_replace(
    '/DEVICE_KEYS=\{.*?\}/s',
    "DEVICE_KEYS=" . $updated_device_keys_json,
    $env_content
);

if (file_put_contents($env_file, $updated_env_content, LOCK_EX) === false) {
    sendJson(["status" => "error", "message" => "Failed to write to .env file."], 500);
}

sendJson([
    "status" => "success",
    "message" => "Public key for '{$key_name}' successfully updated."
]);
?>