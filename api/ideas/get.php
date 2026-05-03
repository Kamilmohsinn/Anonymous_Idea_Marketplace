<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$ideaId = (int) ($_GET['id'] ?? 0);
if ($ideaId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid idea id is required']);
    exit();
}

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT id, user_id, pseudonym, title, category, problem_statement, proposed_solution, tags, stage, status, vote_score, trending_score, comments_count, collaboration_count, similarity_score, similar_idea_id, attachment_path, created_at, updated_at FROM ideas WHERE id = ? LIMIT 1');
$stmt->execute([$ideaId]);
$idea = $stmt->fetch();

if (!$idea) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Idea not found']);
    exit();
}

require_once __DIR__ . '/../auth_utils.php';
$currentUserId = getUserIdFromSession(); 

$isAdmin = false;
if ($currentUserId) {
    $userStmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([$currentUserId]);
    $user = $userStmt->fetch();
    $isAdmin = ($user && $user['role'] === 'admin');
}

$isOwner = ($currentUserId && (int)$idea['user_id'] === $currentUserId);

if ($idea['status'] !== 'Published' && !$isOwner && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'This idea is pending review or private']);
    exit();
}


echo json_encode([
    'success' => true,
    'idea' => $idea,
    'current_user_id' => $currentUserId
]);
