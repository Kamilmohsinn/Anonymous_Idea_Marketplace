<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../admin_utils.php';

requireAdmin();
$pdo = getDbConnection();

$stmt = $pdo->query('SELECT r.id, r.reporter_user_id, r.target_type, r.target_id, r.reason, r.details, r.status, r.created_at, u.email AS reporter_email FROM reports r INNER JOIN users u ON u.id = r.reporter_user_id ORDER BY r.created_at DESC LIMIT 200');
$reports = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'reports' => $reports
]);
