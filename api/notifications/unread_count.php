<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

try {
    $userId = requireAuth();
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    $count = (int) $stmt->fetchColumn();

    echo json_encode(['success' => true, 'unread_count' => $count]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'unread_count' => 0]);
}
