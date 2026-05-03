<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/jwt_utils.php';

if (!isset($_SESSION['user_id'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (stripos($authHeader, 'Bearer ') === 0) {
        $token = trim(substr($authHeader, 7));
        $payload = verifyJwtToken($token);

        if ($payload) {
            echo json_encode([
                'authenticated' => true,
                'user_id' => $payload['user_id'],
                'email' => $payload['email'],
                'role' => $payload['role']
            ]);
            exit();
        }
    }

    http_response_code(401);
    echo json_encode(['authenticated' => false, 'message' => 'Not authenticated']);
    exit();
}

echo json_encode([
    'authenticated' => true,
    'user_id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'role' => $_SESSION['role'] ?? 'user'
]);
?>