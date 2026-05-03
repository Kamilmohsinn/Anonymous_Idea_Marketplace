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

$requestId = (int) ($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if ($requestId <= 0 || !in_array($status, ['Accepted', 'Declined'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid request id and status are required']);
    exit();
}

$ownerStmt = $pdo->prepare('SELECT id, idea_id, sender_user_id, receiver_user_id FROM collaboration_requests WHERE id = ? AND receiver_user_id = ? AND status = "Pending" LIMIT 1');
$ownerStmt->execute([$requestId, $userId]);
$request = $ownerStmt->fetch();
if (!$request) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only the receiver can respond to pending requests']);
    exit();
}

$updateStmt = $pdo->prepare('UPDATE collaboration_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
$updateStmt->execute([$status, $requestId]);

$ideaId = (int) $request['idea_id'];
$senderId = (int) $request['sender_user_id'];
$receiverId = (int) $request['receiver_user_id'];

if ($status === 'Accepted') {
    $roomStmt = $pdo->prepare('INSERT INTO chat_rooms (collaboration_request_id, user_one_id, user_two_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_active = 1');
    $roomStmt->execute([$requestId, $senderId, $receiverId]);

    addReputation($pdo, $senderId, 15, 'collaboration_accepted');
    addReputation($pdo, $receiverId, 15, 'collaboration_accepted');
}

recalculateIdeaMetrics($pdo, $ideaId);
createNotification($pdo, $senderId, 'Collaboration request updated', 'Your collaboration request was ' . strtolower($status) . '.', 'collaboration', $requestId);

echo json_encode([
    'success' => true,
    'message' => 'Request updated successfully'
]);
