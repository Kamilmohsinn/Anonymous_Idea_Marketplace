<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';
require_once __DIR__ . '/../platform_utils.php';

$userId = requireAuth();
$pdo = getDbConnection();

ensureUserProfile($pdo, $userId);

$profileStmt = $pdo->prepare('SELECT bio, reputation_points, badge FROM user_profiles WHERE user_id = ? LIMIT 1');
$profileStmt->execute([$userId]);
$profile = $profileStmt->fetch();

$settingsStmt = $pdo->prepare('SELECT email_notifications_enabled, public_profile_enabled FROM user_settings WHERE user_id = ? LIMIT 1');
$settingsStmt->execute([$userId]);
$settings = $settingsStmt->fetch();

if (!$settings) {
    $insertSettings = $pdo->prepare('INSERT INTO user_settings (user_id) VALUES (?)');
    $insertSettings->execute([$userId]);
    $settings = ['email_notifications_enabled' => 0, 'public_profile_enabled' => 1];
}

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'settings' => $settings
]);
