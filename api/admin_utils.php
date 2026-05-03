<?php

require_once __DIR__ . '/auth_utils.php';
require_once __DIR__ . '/db.php';

function requireAdmin(): int {
    $userId = requireAuth();
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !in_array($user['role'], ['admin', 'moderator'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin or moderator access required']);
        exit();
    }

    return $userId;
}
