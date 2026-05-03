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

$userId = requireAuth();
$pdo = getDbConnection();

$bio = trim($_POST['bio'] ?? '');
$emailNotifications = isset($_POST['email_notifications_enabled']) ? (int) $_POST['email_notifications_enabled'] : 0;
$publicProfile = isset($_POST['public_profile_enabled']) ? (int) $_POST['public_profile_enabled'] : 1;

ensureUserProfile($pdo, $userId);

$profileStmt = $pdo->prepare('UPDATE user_profiles SET bio = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
$profileStmt->execute([$bio, $userId]);

$settingStmt = $pdo->prepare('INSERT INTO user_settings (user_id, email_notifications_enabled, public_profile_enabled) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE email_notifications_enabled = VALUES(email_notifications_enabled), public_profile_enabled = VALUES(public_profile_enabled), updated_at = CURRENT_TIMESTAMP');
$settingStmt->execute([$userId, $emailNotifications ? 1 : 0, $publicProfile ? 1 : 0]);

echo json_encode([
    'success' => true,
    'message' => 'Profile settings updated successfully'
]);
