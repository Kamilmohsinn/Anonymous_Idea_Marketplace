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

$senderId = requireAuth();
$pdo = getDbConnection();

$ideaId = (int) ($_POST['idea_id'] ?? 0);
$message = trim($_POST['message'] ?? 'I would like to collaborate on this idea.');

if ($ideaId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid idea_id is required']);
    exit();
}

$ownerStmt = $pdo->prepare('SELECT user_id, title FROM ideas WHERE id = ? LIMIT 1');
$ownerStmt->execute([$ideaId]);
$idea = $ownerStmt->fetch();

if (!$idea) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Idea not found']);
    exit();
}

$receiverId = (int) $idea['user_id'];
if ($receiverId === $senderId) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'You cannot send a request to your own idea']);
    exit();
}

$insertStmt = $pdo->prepare('INSERT INTO collaboration_requests (idea_id, sender_user_id, receiver_user_id, message, status) VALUES (?, ?, ?, ?, "Pending")');
$insertStmt->execute([$ideaId, $senderId, $receiverId, $message]);
$requestId = (int) $pdo->lastInsertId();

recalculateIdeaMetrics($pdo, $ideaId);
createNotification($pdo, $receiverId, 'New collaboration request', 'You received a collaboration request on: ' . $idea['title'], 'collaboration', $requestId);

echo json_encode([
    'success' => true,
    'message' => 'Collaboration request sent successfully'
]);
