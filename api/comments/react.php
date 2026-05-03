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

$commentId = (int) ($_POST['comment_id'] ?? 0);
$reaction = trim($_POST['reaction'] ?? '');

if ($commentId <= 0 || !in_array($reaction, ['helpful', 'unhelpful'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid comment_id and reaction are required']);
    exit();
}

$commentStmt = $pdo->prepare('SELECT id, user_id, idea_id FROM comments WHERE id = ? LIMIT 1');
$commentStmt->execute([$commentId]);
$comment = $commentStmt->fetch();
if (!$comment) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Comment not found']);
    exit();
}

$saveStmt = $pdo->prepare('INSERT INTO comment_reactions (comment_id, user_id, reaction) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), updated_at = CURRENT_TIMESTAMP');
$saveStmt->execute([$commentId, $userId, $reaction]);

$scoreStmt = $pdo->prepare('SELECT COALESCE(SUM(CASE WHEN reaction = "helpful" THEN 1 ELSE -1 END), 0) AS score FROM comment_reactions WHERE comment_id = ?');
$scoreStmt->execute([$commentId]);
$score = (int) $scoreStmt->fetch()['score'];

$updateStmt = $pdo->prepare('UPDATE comments SET helpful_score = ? WHERE id = ?');
$updateStmt->execute([$score, $commentId]);

$commentOwnerId = (int) $comment['user_id'];
if ($reaction === 'helpful' && $commentOwnerId !== $userId) {
    addReputation($pdo, $commentOwnerId, 3, 'comment_marked_helpful');
    createNotification($pdo, $commentOwnerId, 'Comment marked helpful', 'A community member marked your comment as helpful.', 'comment', $commentId);
}

echo json_encode([
    'success' => true,
    'message' => 'Reaction saved',
    'helpful_score' => $score
]);
