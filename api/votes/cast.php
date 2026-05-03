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
$voteType = (int) ($_POST['vote_type'] ?? 0);

if ($ideaId <= 0 || !in_array($voteType, [1, -1], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid idea_id and vote_type are required']);
    exit();
}

$ideaStmt = $pdo->prepare('SELECT id, user_id, title FROM ideas WHERE id = ? LIMIT 1');
$ideaStmt->execute([$ideaId]);
$idea = $ideaStmt->fetch();
if (!$idea) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Idea not found']);
    exit();
}

$voteStmt = $pdo->prepare('INSERT INTO idea_votes (idea_id, user_id, vote_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type), updated_at = CURRENT_TIMESTAMP');
$voteStmt->execute([$ideaId, $userId, $voteType]);

recalculateIdeaMetrics($pdo, $ideaId);

$freshScoreStmt = $pdo->prepare('SELECT vote_score FROM ideas WHERE id = ?');
$freshScoreStmt->execute([$ideaId]);
$score = (int) $freshScoreStmt->fetch()['vote_score'];

$ideaOwnerId = (int) $idea['user_id'];
if ($ideaOwnerId !== $userId && $voteType === 1) {
    createNotification($pdo, $ideaOwnerId, 'New upvote', 'Your idea received an upvote: ' . $idea['title'], 'idea', $ideaId);
}

if ($ideaOwnerId !== $userId && $score >= 10) {
    addReputation($pdo, $ideaOwnerId, 10, 'idea_reached_10_upvotes');
}

echo json_encode([
    'success' => true,
    'message' => 'Vote saved',
    'vote_score' => $score
]);
