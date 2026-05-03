<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$ideaId = (int) ($_GET['idea_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = max(1, min(50, (int) ($_GET['per_page'] ?? 20)));

if ($ideaId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid idea_id is required']);
    exit();
}

$pdo = getDbConnection();
$countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM comments WHERE idea_id = ?');
$countStmt->execute([$ideaId]);
$total = (int) $countStmt->fetch()['total'];

$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare('SELECT id, idea_id, user_id, pseudonym, content, parent_comment_id, helpful_score, created_at, updated_at FROM comments WHERE idea_id = ? ORDER BY created_at ASC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $ideaId, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'comments' => $comments,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int) ceil($total / $perPage)
    ]
]);
