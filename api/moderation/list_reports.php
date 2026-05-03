<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

try {
    $userId = requireAuth();
    $pdo = getDbConnection();

    // Check if user is admin
    $userStmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $userStmt->execute([$userId]);
    if ($userStmt->fetch()['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit();
    }

    $stmt = $pdo->query('SELECT r.*, u.email as reporter_email FROM reports r JOIN users u ON r.reporter_user_id = u.id WHERE r.status = "Open" ORDER BY r.created_at DESC');
    $reports = $stmt->fetchAll();

    echo json_encode(['success' => true, 'reports' => $reports]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
