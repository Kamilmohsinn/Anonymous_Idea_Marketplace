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

$ideaId = (int) ($_POST['id'] ?? 0);
if ($ideaId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid idea id is required']);
    exit();
}

$ownerStmt = $pdo->prepare('SELECT id FROM ideas WHERE id = ? AND user_id = ? LIMIT 1');
$ownerStmt->execute([$ideaId, $userId]);
if (!$ownerStmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only delete your own ideas']);
    exit();
}

$deleteStmt = $pdo->prepare('DELETE FROM ideas WHERE id = ?');
$deleteStmt->execute([$ideaId]);

echo json_encode([
    'success' => true,
    'message' => 'Idea deleted successfully'
]);
