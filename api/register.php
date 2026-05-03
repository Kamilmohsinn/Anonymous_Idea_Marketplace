<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/platform_utils.php';
require_once __DIR__ . '/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$pdo = getDbConnection();
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? 'user');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit();
}

if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit();
}

$allowedRoles = ['user', 'investor'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'user';
}

$existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$existsStmt->execute([$email]);
if ($existsStmt->fetch()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email is already registered']);
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$insertStmt = $pdo->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, ?)');
$insertStmt->execute([$email, $hashedPassword, $role]);
$userId = (int) $pdo->lastInsertId();

ensureUserProfile($pdo, $userId);
addReputation($pdo, $userId, 5, 'registration');

$_SESSION['user_id'] = $userId;
$_SESSION['email'] = $email;
$_SESSION['role'] = $role;
$_SESSION['jwt'] = createJwtToken([
    'user_id' => $userId,
    'email' => $email,
    'role' => $role
]);

echo json_encode([
    'success' => true,
    'message' => 'Registration successful',
    'role' => $role,
    'token' => $_SESSION['jwt']
]);
