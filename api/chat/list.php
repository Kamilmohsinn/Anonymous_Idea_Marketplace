<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';
require_once __DIR__ . '/../chat_crypto.php';

$userId = requireAuth();
$pdo = getDbConnection();

$roomId = (int) ($_GET['room_id'] ?? 0);
if ($roomId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid room_id is required']);
    exit();
}

$roomStmt = $pdo->prepare('SELECT id, user_one_id, user_two_id FROM chat_rooms WHERE id = ? LIMIT 1');
$roomStmt->execute([$roomId]);
$room = $roomStmt->fetch();
if (!$room) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit();
}

if ((int) $room['user_one_id'] !== $userId && (int) $room['user_two_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$afterId = (int) ($_GET['after_id'] ?? 0);
if ($afterId > 0) {
    $msgStmt = $pdo->prepare('SELECT id, room_id, sender_user_id, message, attachment_path, created_at FROM chat_messages WHERE room_id = ? AND id > ? ORDER BY id ASC');
    $msgStmt->execute([$roomId, $afterId]);
} else {
    $msgStmt = $pdo->prepare('SELECT id, room_id, sender_user_id, message, attachment_path, created_at FROM chat_messages WHERE room_id = ? ORDER BY id ASC LIMIT 300');
    $msgStmt->execute([$roomId]);
}

$messages = $msgStmt->fetchAll();

foreach ($messages as &$message) {
    $message['message'] = decryptChatMessage($message['message']);
}
unset($message);

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'current_user_id' => $userId
]);
