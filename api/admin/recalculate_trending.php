<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../admin_utils.php';
require_once __DIR__ . '/../platform_utils.php';

requireAdmin();
$pdo = getDbConnection();

recalculateAllTrending($pdo);

echo json_encode([
    'success' => true,
    'message' => 'Trending scores recalculated successfully'
]);
