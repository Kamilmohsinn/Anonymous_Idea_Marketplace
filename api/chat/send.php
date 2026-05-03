<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';
require_once __DIR__ . '/../chat_crypto.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$userId = requireAuth();
$pdo = getDbConnection();

$roomId = (int) ($_POST['room_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$attachmentPath = null;

if ($roomId <= 0 || ($message === '' && !isset($_FILES['attachment']))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid room_id and a message or file attachment are required']);
    exit();
}

$roomStmt = $pdo->prepare('SELECT id, user_one_id, user_two_id, is_active FROM chat_rooms WHERE id = ? LIMIT 1');
$roomStmt->execute([$roomId]);
$room = $roomStmt->fetch();
if (!$room || (int) $room['is_active'] !== 1) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Active chat room not found']);
    exit();
}

if ((int) $room['user_one_id'] !== $userId && (int) $room['user_two_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_FILES['attachment']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
    $uploadsDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'chat';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($_FILES['attachment']['name']));
    $targetName = uniqid('chat_', true) . '_' . $safeName;
    $targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $targetName;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
        $attachmentPath = 'uploads/chat/' . $targetName;
    }
}

$plainMessage = $message === '' ? '[file]' : $message;
$encryptedMessage = encryptChatMessage($plainMessage);

$insertStmt = $pdo->prepare('INSERT INTO chat_messages (room_id, sender_user_id, message, attachment_path) VALUES (?, ?, ?, ?)');
$insertStmt->execute([$roomId, $userId, $encryptedMessage, $attachmentPath]);

echo json_encode(['success' => true, 'message' => 'Message sent']);
