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

$targetType = trim($_POST['target_type'] ?? '');
$targetId = (int) ($_POST['target_id'] ?? 0);
$reason = trim($_POST['reason'] ?? 'other');
$details = trim($_POST['details'] ?? '');

$allowedTarget = ['idea', 'comment'];
$allowedReason = ['spam', 'inappropriate', 'plagiarism', 'other'];

if (!in_array($targetType, $allowedTarget, true) || $targetId <= 0 || !in_array($reason, $allowedReason, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid target_type, target_id, and reason are required']);
    exit();
}

$insertStmt = $pdo->prepare('INSERT INTO reports (reporter_user_id, target_type, target_id, reason, details) VALUES (?, ?, ?, ?, ?)');
$insertStmt->execute([$userId, $targetType, $targetId, $reason, $details]);
$reportId = (int) $pdo->lastInsertId();

$adminsStmt = $pdo->query('SELECT id FROM users WHERE role IN ("admin", "moderator") AND account_status = "active"');
$admins = $adminsStmt->fetchAll();
foreach ($admins as $admin) {
    createNotification($pdo, (int) $admin['id'], 'New report submitted', 'A new ' . $targetType . ' report is pending review.', 'report', $reportId);
}

echo json_encode([
    'success' => true,
    'message' => 'Report submitted successfully'
]);
