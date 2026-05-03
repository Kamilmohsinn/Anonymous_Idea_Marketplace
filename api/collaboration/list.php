<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

$userId = requireAuth();
$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT cr.id, cr.idea_id, cr.sender_user_id, cr.receiver_user_id, cr.message, cr.status, cr.created_at, i.title AS idea_title FROM collaboration_requests cr INNER JOIN ideas i ON i.id = cr.idea_id WHERE cr.sender_user_id = ? OR cr.receiver_user_id = ? ORDER BY cr.created_at DESC');
$stmt->execute([$userId, $userId]);
$requests = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'requests' => $requests,
    'current_user_id' => $userId
]);
