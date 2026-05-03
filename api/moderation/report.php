<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

try {
    $userId = requireAuth();
    $pdo = getDbConnection();

    $contentId = (int) ($_POST['content_id'] ?? 0);
    $contentType = $_POST['content_type'] ?? 'idea'; // 'idea' or 'comment'
    $reason = trim($_POST['reason'] ?? '');

    if ($contentId <= 0 || !in_array($contentType, ['idea', 'comment']) || $reason === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid content_id, content_type, and reason are required']);
        exit();
    }

    $stmt = $pdo->prepare('INSERT INTO reports (reporter_user_id, content_type, content_id, reason, status) VALUES (?, ?, ?, ?, "Pending")');
    $stmt->execute([$userId, $contentType, $contentId, $reason]);

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully. A moderator will review it.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
