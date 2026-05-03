<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../platform_utils.php';

$pdo = getDbConnection();
$refreshTrending = isset($_GET['refresh_trending']) && $_GET['refresh_trending'] === '1';
$mine = isset($_GET['mine']) && $_GET['mine'] === '1';
$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$stage = trim($_GET['stage'] ?? '');
$sort = trim($_GET['sort'] ?? 'new');
$status = trim($_GET['status'] ?? 'Published');


if ($refreshTrending) {
    recalculateAllTrending($pdo);
}

$whereClauses = [];
$params = [];

$userId = null;
$isAdmin = false;
require_once __DIR__ . '/../auth_utils.php';
$userId = getUserIdFromSession(); 
if ($userId) {
    $userStmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    $isAdmin = ($user && $user['role'] === 'admin');
}

if ($mine) {
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required to view your ideas']);
        exit();
    }
    $whereClauses[] = 'user_id = ?';
    $params[] = $userId;
    
    if ($status !== 'all' && $status !== '') {
        $whereClauses[] = 'status = ?';
        $params[] = $status;
    }
} else {
    if ($isAdmin && $status === 'Pending') {
        $whereClauses[] = 'status = "Pending"';
    } else {
        $whereClauses[] = 'status = "Published"';
    }
}


if ($q !== '') {
    $whereClauses[] = '(title LIKE ? OR problem_statement LIKE ? OR proposed_solution LIKE ? OR tags LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($category !== '') {
    $whereClauses[] = 'category = ?';
    $params[] = $category;
}

if ($stage !== '') {
    $whereClauses[] = 'stage = ?';
    $params[] = $stage;
}

$orderBy = 'created_at DESC';
if ($sort === 'top') {
    $orderBy = 'vote_score DESC, created_at DESC';
} elseif ($sort === 'trending') {
    $orderBy = 'trending_score DESC, created_at DESC';
}

$sql = 'SELECT id, user_id, pseudonym, title, category, problem_statement, proposed_solution, tags, stage, status, vote_score, trending_score, comments_count, collaboration_count, similarity_score, similar_idea_id, attachment_path, created_at, updated_at FROM ideas';
if (count($whereClauses) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
}
$sql .= ' ORDER BY ' . $orderBy;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$ideas = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'ideas' => $ideas
]);
