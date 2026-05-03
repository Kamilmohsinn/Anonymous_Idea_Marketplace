<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';
require_once __DIR__ . '/../chat_crypto.php';

$userId = requireAuth();
$pdo = getDbConnection();

$requestId = (int) ($_GET['request_id'] ?? 0);
if ($requestId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid request_id is required']);
    exit();
}

$requestStmt = $pdo->prepare('SELECT id, sender_user_id, receiver_user_id, status FROM collaboration_requests WHERE id = ? LIMIT 1');
$requestStmt->execute([$requestId]);
$request = $requestStmt->fetch();
if (!$request || $request['status'] !== 'Accepted') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Chat is only available for accepted collaborations']);
    exit();
}

if ((int) $request['sender_user_id'] !== $userId && (int) $request['receiver_user_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized room access']);
    exit();
}

$roomStmt = $pdo->prepare('SELECT id, user_one_id, user_two_id FROM chat_rooms WHERE collaboration_request_id = ? LIMIT 1');
$roomStmt->execute([$requestId]);
$room = $roomStmt->fetch();
if (!$room) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Chat room not found']);
    exit();
}

$messagesStmt = $pdo->prepare('SELECT id, room_id, sender_user_id, message, attachment_path, created_at FROM chat_messages WHERE room_id = ? ORDER BY created_at ASC LIMIT 300');
$messagesStmt->execute([(int) $room['id']]);
$messages = $messagesStmt->fetchAll();

foreach ($messages as &$message) {
    $message['message'] = decryptChatMessage($message['message']);
}
unset($message);

$partnerId = (int) $room['user_one_id'] === $userId ? (int) $room['user_two_id'] : (int) $room['user_one_id'];
$partnerStmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
$partnerStmt->execute([$partnerId]);
$partner = $partnerStmt->fetch();

echo json_encode([
    'success' => true,
    'room' => $room,
    'messages' => $messages,
    'current_user_id' => $userId,
    'partner_email' => $partner ? $partner['email'] : 'Unknown Partner'
]);
