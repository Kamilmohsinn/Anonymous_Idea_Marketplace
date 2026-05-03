<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email']);
    exit();
}

$pdo = getDbConnection();
$userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$userStmt->execute([$email]);
$user = $userStmt->fetch();

if (!$user) {
    echo json_encode([
        'success' => true,
        'message' => 'If this email exists, a password reset link has been issued.'
    ]);
    exit();
}

$tokenPlain = bin2hex(random_bytes(16));
$tokenHash = hash('sha256', $tokenPlain);

$expireStmt = $pdo->prepare('UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND used_at IS NULL');
$expireStmt->execute([(int) $user['id']]);

$insertStmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
$insertStmt->execute([(int) $user['id'], $tokenHash]);

// For Phase 1 local testing, token is returned in response. In production, send via email.
echo json_encode([
    'success' => true,
    'message' => 'Reset token issued. Use it to reset your password.',
    'reset_token' => $tokenPlain
]);
