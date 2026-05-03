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

$adminId = requireAdmin();
$pdo = getDbConnection();

$reportId = (int) ($_POST['report_id'] ?? 0);
$action = trim($_POST['action'] ?? 'review');

if ($reportId <= 0 || !in_array($action, ['review', 'dismiss', 'remove_target'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid report_id and action are required']);
    exit();
}

$reportStmt = $pdo->prepare('SELECT id, reporter_user_id, target_type, target_id FROM reports WHERE id = ? LIMIT 1');
$reportStmt->execute([$reportId]);
$report = $reportStmt->fetch();
if (!$report) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit();
}

$status = 'Reviewed';
if ($action === 'dismiss') {
    $status = 'Dismissed';
}
if ($action === 'remove_target') {
    $status = 'Actioned';

    if ($report['target_type'] === 'idea') {
        $deleteTargetStmt = $pdo->prepare('DELETE FROM ideas WHERE id = ?');
        $deleteTargetStmt->execute([(int) $report['target_id']]);
    } else {
        $deleteTargetStmt = $pdo->prepare('DELETE FROM comments WHERE id = ?');
        $deleteTargetStmt->execute([(int) $report['target_id']]);
    }
}

$updateStmt = $pdo->prepare('UPDATE reports SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?');
$updateStmt->execute([$status, $adminId, $reportId]);

createNotification($pdo, (int) $report['reporter_user_id'], 'Report updated', 'Your report has been ' . strtolower($status) . '.', 'report', $reportId);

echo json_encode([
    'success' => true,
    'message' => 'Report action applied successfully'
]);
