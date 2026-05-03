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

requireAuth();
$pdo = getDbConnection();

$title = trim($_POST['title'] ?? '');
$problem = trim($_POST['problem_statement'] ?? '');
$solution = trim($_POST['proposed_solution'] ?? '');
$excludeId = (int) ($_POST['exclude_id'] ?? 0);

if ($title === '' && $problem === '' && $solution === '') {
    echo json_encode(['success' => true, 'similarity_score' => 0, 'similar_idea_id' => null]);
    exit();
}

$similarity = findClosestIdea($pdo, $title, $problem, $solution, $excludeId > 0 ? $excludeId : null);

echo json_encode([
    'success' => true,
    'similarity_score' => (float) ($similarity['score'] ?? 0),
    'similar_idea_id' => $similarity['idea_id'] ?? null
]);
