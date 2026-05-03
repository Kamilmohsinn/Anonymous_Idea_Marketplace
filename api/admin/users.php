<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../admin_utils.php';

requireAdmin();
$pdo = getDbConnection();

$stmt = $pdo->query('SELECT u.id, u.email, u.role, u.account_status, u.created_at, up.reputation_points, up.badge FROM users u LEFT JOIN user_profiles up ON up.user_id = u.id ORDER BY u.created_at DESC');
$users = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'users' => $users
]);
