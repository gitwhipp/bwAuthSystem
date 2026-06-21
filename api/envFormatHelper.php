<?php

function normalizePublicKeyPemForEnv($pem)
{
    $pem = trim($pem, " \t\n\r\0\x0B\"'");

    $pem = str_replace("\\r\\n", "\n", $pem);
    $pem = str_replace("\\n", "\n", $pem);
    $pem = str_replace("\\r", "\n", $pem);
    $pem = str_replace("\r\n", "\n", $pem);
    $pem = str_replace("\r", "\n", $pem);

    if (
        strpos($pem, "-----BEGIN PUBLIC KEY-----") === false ||
        strpos($pem, "-----END PUBLIC KEY-----") === false
    ) {
        return $pem;
    }

    $body = str_replace("-----BEGIN PUBLIC KEY-----", "", $pem);
    $body = str_replace("-----END PUBLIC KEY-----", "", $body);
    $body = preg_replace('/\s+/', '', $body);

    return "-----BEGIN PUBLIC KEY-----\n"
        . chunk_split($body, 64, "\n")
        . "-----END PUBLIC KEY-----";
}

function parseDeviceKeysForEnvCleanup($deviceKeysText)
{
    $deviceKeysText = trim($deviceKeysText);
    $deviceKeys = [];

    $decoded = json_decode($deviceKeysText, true);

    if (is_array($decoded)) {
        foreach ($decoded as $deviceName => $publicKey) {
            $deviceKeys[trim($deviceName)] = normalizePublicKeyPemForEnv($publicKey);
        }

        return $deviceKeys;
    }

    preg_match_all(
        '/"([^"]+)"\s*:\s*"((?:.|\n|\r)*?-----END PUBLIC KEY-----)"/',
        $deviceKeysText,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {
        $deviceName = trim($match[1]);
        $publicKey = normalizePublicKeyPemForEnv($match[2]);

        if ($deviceName !== '' && strpos($publicKey, "-----BEGIN PUBLIC KEY-----") !== false) {
            $deviceKeys[$deviceName] = $publicKey;
        }
    }

    return $deviceKeys;
}

function normalizeAuthEnvFile($envFile)
{
    if (!file_exists($envFile) || !is_writable($envFile)) {
        return false;
    }

    $contents = file_get_contents($envFile);

    if ($contents === false) {
        return false;
    }

    if (!preg_match('/^DEVICE_KEYS=(\{.*?\})\s*(?=^[A-Z0-9_]+=|\z)/ms', $contents, $matches)) {
        return true;
    }

    $deviceKeys = parseDeviceKeysForEnvCleanup($matches[1]);

    if (empty($deviceKeys)) {
        return false;
    }

    $encodedDeviceKeys = json_encode($deviceKeys, JSON_UNESCAPED_SLASHES);

    if ($encodedDeviceKeys === false) {
        return false;
    }

    $replacement = "DEVICE_KEYS=" . $encodedDeviceKeys . "\n";

    $updatedContents = preg_replace(
        '/^DEVICE_KEYS=\{.*?\}\s*(?=^[A-Z0-9_]+=|\z)/ms',
        $replacement,
        $contents,
        1
    );

    if ($updatedContents === null || $updatedContents === $contents) {
        return true;
    }

    return file_put_contents($envFile, $updatedContents, LOCK_EX) !== false;
}