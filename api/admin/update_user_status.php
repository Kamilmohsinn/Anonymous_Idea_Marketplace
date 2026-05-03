<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../admin_utils.php';
require_once __DIR__ . '/../platform_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

requireAdmin();
$pdo = getDbConnection();

$userId = (int) ($_POST['user_id'] ?? 0);
$status = trim($_POST['status'] ?? 'active');

if ($userId <= 0 || !in_array($status, ['active', 'warned', 'suspended'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid user_id and status are required']);
    exit();
}

$updateStmt = $pdo->prepare('UPDATE users SET account_status = ? WHERE id = ?');
$updateStmt->execute([$status, $userId]);

createNotification($pdo, $userId, 'Account status updated', 'Your account status is now: ' . $status, 'user', $userId);

echo json_encode([
    'success' => true,
    'message' => 'User status updated'
]);
