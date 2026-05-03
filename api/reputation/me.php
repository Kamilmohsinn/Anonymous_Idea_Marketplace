<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';
require_once __DIR__ . '/../platform_utils.php';

$userId = requireAuth();
$pdo = getDbConnection();
ensureUserProfile($pdo, $userId);

$profileStmt = $pdo->prepare('SELECT reputation_points, badge FROM user_profiles WHERE user_id = ? LIMIT 1');
$profileStmt->execute([$userId]);
$profile = $profileStmt->fetch();

$logsStmt = $pdo->prepare('SELECT points, reason, created_at FROM reputation_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
$logsStmt->execute([$userId]);
$logs = $logsStmt->fetchAll();

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'logs' => $logs
]);
