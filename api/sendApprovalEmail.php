<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

function sendJson($payload, $statusCode = 200)
{
    if (ob_get_length()) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        return [];
    }

    $contents = file_get_contents($filePath);
    if ($contents === false) {
        return [];
    }

    $env = [];

    foreach (preg_split("/\r\n|\n|\r/", $contents) as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $trimmed, 2);
        $env[trim($name)] = trim($value, "\"'");
    }

    return $env;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(["status" => "error", "message" => "POST required."], 405);
}

$envPath = __DIR__ . '/../fileSystemAccess.env';
$env = loadEnv($envPath);

$key_name = isset($_POST['key_name']) ? trim($_POST['key_name']) : '';
$public_key = isset($_POST['public_key']) ? trim($_POST['public_key']) : '';
$approval_email = isset($_POST['approval_email']) ? trim($_POST['approval_email']) : '';

if ($key_name === '' || $public_key === '') {
    sendJson(["status" => "error", "message" => "Missing key name or public key."], 400);
}

if (strlen($key_name) > 160) {
    sendJson(["status" => "error", "message" => "Key name is too long."], 400);
}

if (strpos($public_key, '-----BEGIN PUBLIC KEY-----') === false) {
    sendJson(["status" => "error", "message" => "Invalid public key format."], 400);
}

$smtpUsername = trim($_POST['smtp_username'] ?? '');
$smtpPassword = trim($_POST['smtp_password'] ?? '');
$smtpHost = 'smtp.gmail.com';
$smtpPort = 587;
$approvalEmailTo = $approval_email !== '' ? $approval_email : $smtpUsername;

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$authBaseUrl = $scheme . '://' . $host . $basePath;

if ($smtpUsername === '' || $smtpPassword === '' || $approvalEmailTo === '') {
    sendJson([
        "status" => "error",
        "message" => "Missing mail input.",
        "diagnostic" => "Need sender Gmail, sender app password, and recipient email."
    ], 400);
}

$approval_link = $authBaseUrl . "/approveKeyPage.php?"
    . "keyname=" . urlencode($key_name)
    . "&publickey=" . urlencode($public_key);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;

    $mail->setFrom($smtpUsername, 'bwAuthSystem');
    $mail->addAddress($approvalEmailTo);

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Approve Public Key Inclusion';

    $safeKeyName = htmlspecialchars($key_name, ENT_QUOTES, 'UTF-8');
    $safeApprovalLink = htmlspecialchars($approval_link, ENT_QUOTES, 'UTF-8');

    $mail->Body = "
    <html>
    <body>
        <h2>Approve Key Addition</h2>
        <p>Device/key name: <strong>{$safeKeyName}</strong></p>
        <p><a href=\"{$safeApprovalLink}\">Approve Key</a></p>
        <p>If approval fails, manually add the public key to DEVICE_KEYS in fileSystemAccess.env.</p>
    </body>
    </html>";

    $mail->send();

    sendJson([
        "status" => "success",
        "message" => "Approval email sent.",
        "approval_email" => $approvalEmailTo
    ]);
} catch (Exception $e) {
    sendJson([
        "status" => "error",
        "message" => "Error sending email.",
        "diagnostic" => $mail->ErrorInfo ?: $e->getMessage(),
        "approval_email" => $approvalEmailTo,
        "smtp_host" => $smtpHost,
        "smtp_port" => $smtpPort
    ], 500);
}