<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';
require_once __DIR__ . '/../platform_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$userId = requireAuth();
$pdo = getDbConnection();

$commentId = (int) ($_POST['id'] ?? 0);
if ($commentId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid comment id is required']);
    exit();
}

$ownerStmt = $pdo->prepare('SELECT id, idea_id FROM comments WHERE id = ? AND user_id = ? LIMIT 1');
$ownerStmt->execute([$commentId, $userId]);
$ownedComment = $ownerStmt->fetch();
if (!$ownedComment) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only delete your own comments']);
    exit();
}

$deleteStmt = $pdo->prepare('DELETE FROM comments WHERE id = ?');
$deleteStmt->execute([$commentId]);

recalculateIdeaMetrics($pdo, (int) $ownedComment['idea_id']);

echo json_encode([
    'success' => true,
    'message' => 'Comment deleted successfully'
]);
