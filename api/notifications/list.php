<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

$userId = requireAuth();
$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT id, title, message, reference_type, reference_id, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);
