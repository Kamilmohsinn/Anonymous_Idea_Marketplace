<?php

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string {
    $padding = 4 - (strlen($data) % 4);
    if ($padding < 4) {
        $data .= str_repeat('=', $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwtSecret(): string {
    $env = getenv('JWT_SECRET');
    return $env && strlen($env) > 20 ? $env : 'phase1-jwt-secret-change-in-production';
}

function createJwtToken(array $payload, int $expiresInSeconds = 86400): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiresInSeconds;

    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, jwtSecret(), true);
    $signatureEncoded = base64UrlEncode($signature);

    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

function verifyJwtToken(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
    $expectedSignature = base64UrlEncode(hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, jwtSecret(), true));

    if (!hash_equals($expectedSignature, $signatureEncoded)) {
        return null;
    }

    $payloadJson = base64UrlDecode($payloadEncoded);
    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return null;
    }

    if (!isset($payload['exp']) || time() > (int) $payload['exp']) {
        return null;
    }

    return $payload;
}
