<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$pdo = getDbConnection();
$stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC');
$categories = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'categories' => $categories
]);
