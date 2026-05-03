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

$ideaId = (int) ($_POST['idea_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$parentCommentId = (int) ($_POST['parent_comment_id'] ?? 0);

if ($ideaId <= 0 || $content === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid idea_id and content are required']);
    exit();
}

$pseudonymStmt = $pdo->prepare('SELECT user_id, title FROM ideas WHERE id = ? LIMIT 1');
$pseudonymStmt->execute([$ideaId]);
$idea = $pseudonymStmt->fetch();
if (!$idea) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Idea not found']);
    exit();
}

$commentAlias = 'Reviewer_' . random_int(100, 999);
$parentForInsert = $parentCommentId > 0 ? $parentCommentId : null;
$insertStmt = $pdo->prepare('INSERT INTO comments (idea_id, user_id, pseudonym, content, parent_comment_id) VALUES (?, ?, ?, ?, ?)');
$insertStmt->execute([$ideaId, $userId, $commentAlias, $content, $parentForInsert]);

recalculateIdeaMetrics($pdo, $ideaId);

$ideaOwnerId = (int) $idea['user_id'];
if ($ideaOwnerId !== $userId) {
    createNotification($pdo, $ideaOwnerId, 'New comment', 'Your idea received a new comment: ' . $idea['title'], 'idea', $ideaId);
}

addReputation($pdo, $userId, 2, 'comment_posted');

echo json_encode([
    'success' => true,
    'message' => 'Comment posted successfully'
]);
