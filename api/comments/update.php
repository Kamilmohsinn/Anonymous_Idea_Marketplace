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
$content = trim($_POST['content'] ?? '');

if ($commentId <= 0 || $content === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid comment id and content are required']);
    exit();
}

$ownerStmt = $pdo->prepare('SELECT id FROM comments WHERE id = ? AND user_id = ? LIMIT 1');
$ownerStmt->execute([$commentId, $userId]);
if (!$ownerStmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only update your own comments']);
    exit();
}

$updateStmt = $pdo->prepare('UPDATE comments SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
$updateStmt->execute([$content, $commentId]);

$ideaStmt = $pdo->prepare('SELECT idea_id FROM comments WHERE id = ? LIMIT 1');
$ideaStmt->execute([$commentId]);
$idea = $ideaStmt->fetch();
if ($idea) {
    recalculateIdeaMetrics($pdo, (int) $idea['idea_id']);
}

echo json_encode([
    'success' => true,
    'message' => 'Comment updated successfully'
]);
