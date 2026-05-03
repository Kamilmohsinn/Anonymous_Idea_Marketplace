<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

try {
    $userId = requireAuth();
    $pdo = getDbConnection();

    // Admin check
    $userStmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $userStmt->execute([$userId]);
    if ($userStmt->fetch()['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit();
    }

    $reportId = (int) ($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? ''; // 'Dismissed', 'Actioned'

    if ($reportId <= 0 || !in_array($action, ['Dismissed', 'Actioned'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid report_id and action required']);
        exit();
    }

    $pdo->beginTransaction();

    // Get report details
    $reportStmt = $pdo->prepare('SELECT * FROM reports WHERE id = ? LIMIT 1');
    $reportStmt->execute([$reportId]);
    $report = $reportStmt->fetch();

    if (!$report) throw new Exception("Report not found");

    if ($action === 'Actioned') {
        if ($report['target_type'] === 'idea') {
            $pdo->prepare('DELETE FROM ideas WHERE id = ?')->execute([$report['target_id']]);
        } else {
            $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$report['target_id']]);
        }
        $pdo->prepare('UPDATE reports SET status = "Actioned", reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$userId, $reportId]);
    } else {
        $pdo->prepare('UPDATE reports SET status = "Dismissed", reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$userId, $reportId]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Action taken successfully']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
