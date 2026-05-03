<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../admin_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

requireAdmin();
$pdo = getDbConnection();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid category id is required']);
    exit();
}

$stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
$stmt->execute([$id]);

echo json_encode([
    'success' => true,
    'message' => 'Category deleted successfully'
]);
