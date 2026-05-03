<?php
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/jwt_utils.php';

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($authHeader, 'Bearer ') === 0) {
            $token = trim(substr($authHeader, 7));
            $payload = verifyJwtToken($token);

            if ($payload) {
                $_SESSION['user_id'] = (int) $payload['user_id'];
                $_SESSION['email'] = $payload['email'] ?? null;
                $_SESSION['role'] = $payload['role'] ?? 'user';
            }
        }
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit();
    }

    return (int) $_SESSION['user_id'];
}

function getUserIdFromSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function getCurrentUserEmail() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return $_SESSION['email'] ?? null;
}
