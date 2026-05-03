<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$token = trim($_POST['token'] ?? '');
$newPassword = $_POST['new_password'] ?? '';

if ($token === '' || strlen($newPassword) < 8) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Token and new password (min 8 chars) are required']);
    exit();
}

$pdo = getDbConnection();
$tokenHash = hash('sha256', $token);

$resetStmt = $pdo->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
$resetStmt->execute([$tokenHash]);
$reset = $resetStmt->fetch();

if (!$reset) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
    exit();
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$updateUser = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
$updateUser->execute([$hashedPassword, (int) $reset['user_id']]);

$markUsed = $pdo->prepare('UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = ?');
$markUsed->execute([(int) $reset['id']]);

echo json_encode([
    'success' => true,
    'message' => 'Password reset successfully. You can now login.'
]);
