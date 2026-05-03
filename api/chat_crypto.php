<?php

function getChatKey(): string {
    $envKey = getenv('CHAT_SECRET_KEY');
    $base = $envKey && strlen($envKey) >= 16 ? $envKey : 'phase1-chat-secret-change-in-production';
    return hash('sha256', $base, true);
}

function encryptChatMessage(string $plainText): string {
    $cipher = 'AES-256-CBC';
    $key = getChatKey();
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = random_bytes($ivLength);

    $encrypted = openssl_encrypt($plainText, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) {
        return $plainText;
    }

    return base64_encode($iv . $encrypted);
}

function decryptChatMessage(string $encoded): string {
    $cipher = 'AES-256-CBC';
    $key = getChatKey();
    $raw = base64_decode($encoded, true);

    if ($raw === false) {
        return $encoded;
    }

    $ivLength = openssl_cipher_iv_length($cipher);
    if (strlen($raw) <= $ivLength) {
        return $encoded;
    }

    $iv = substr($raw, 0, $ivLength);
    $cipherText = substr($raw, $ivLength);

    $plain = openssl_decrypt($cipherText, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? $encoded : $plain;
}
