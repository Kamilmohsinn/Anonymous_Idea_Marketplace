<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$userId = requireAuth();
$pdo = getDbConnection();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
} else {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$userId]);
}

echo json_encode(['success' => true, 'message' => 'Notifications updated']);
