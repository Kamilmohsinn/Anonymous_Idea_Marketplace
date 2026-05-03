<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../admin_utils.php';

requireAdmin();
$pdo = getDbConnection();

$totalUsers = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$totalIdeas = (int) $pdo->query('SELECT COUNT(*) AS c FROM ideas')->fetch()['c'];
$ideasThisWeek = (int) $pdo->query('SELECT COUNT(*) AS c FROM ideas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetch()['c'];
$openReports = (int) $pdo->query('SELECT COUNT(*) AS c FROM reports WHERE status = "Open"')->fetch()['c'];

$topIdeasStmt = $pdo->query('SELECT id, title, trending_score, vote_score FROM ideas WHERE status = "Published" ORDER BY trending_score DESC LIMIT 5');
$topIdeas = $topIdeasStmt->fetchAll();

echo json_encode([
    'success' => true,
    'stats' => [
        'total_users' => $totalUsers,
        'total_ideas' => $totalIdeas,
        'ideas_this_week' => $ideasThisWeek,
        'open_reports' => $openReports
    ],
    'top_ideas' => $topIdeas
]);
