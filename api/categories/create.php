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

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit();
}

$stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
$stmt->execute([$name]);

echo json_encode([
    'success' => true,
    'message' => 'Category created successfully'
]);
