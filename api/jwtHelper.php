<?php
function bwBase64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function bwBase64UrlDecode($data)
{
    return base64_decode(strtr($data, '-_', '+/'));
}

function bwJwtEncode($payload, $secretKey)
{
    $header = ["typ" => "JWT", "alg" => "HS256"];

    $segments = [
        bwBase64UrlEncode(json_encode($header)),
        bwBase64UrlEncode(json_encode($payload))
    ];

    $signatureInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signatureInput, $secretKey, true);

    $segments[] = bwBase64UrlEncode($signature);

    return implode('.', $segments);
}

function bwJwtDecode($token, $secretKey)
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return [false, null, "Invalid token format."];
    }

    [$header64, $payload64, $signature64] = $parts;

    $signatureInput = $header64 . '.' . $payload64;
    $expectedSignature = bwBase64UrlEncode(
        hash_hmac('sha256', $signatureInput, $secretKey, true)
    );

    if (!hash_equals($expectedSignature, $signature64)) {
        return [false, null, "Invalid token signature."];
    }

    $payload = json_decode(bwBase64UrlDecode($payload64), true);

    if (!is_array($payload)) {
        return [false, null, "Invalid token payload."];
    }

    if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
        return [false, null, "Token expired."];
    }

    return [true, $payload, "Token valid."];
}