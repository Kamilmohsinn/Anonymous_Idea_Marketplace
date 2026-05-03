<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$pdo = getDbConnection();
$stmt = $pdo->query('SELECT u.id, u.email, up.reputation_points, up.badge FROM users u INNER JOIN user_profiles up ON up.user_id = u.id ORDER BY up.reputation_points DESC LIMIT 20');
$leaders = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'leaders' => $leaders
]);
