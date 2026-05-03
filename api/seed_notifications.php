<?php
/**
 * Seeder for notifications.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/platform_utils.php';

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    // Get some users
    $users = $pdo->query("SELECT id FROM users LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
    $kamilId = $pdo->query("SELECT id FROM users WHERE email = 'kamil@example.com'")->fetchColumn();

    $notifTypes = [
        ['title' => 'New Upvote', 'message' => 'Someone liked your idea!'],
        ['title' => 'Collaboration Request', 'message' => 'You have a new interest in your project.'],
        ['title' => 'New Comment', 'message' => 'Someone shared feedback on your concept.'],
        ['title' => 'Reputation Milestone', 'message' => 'Congratulations! You earned 10 reputation points.'],
        ['title' => 'Idea Trending', 'message' => 'Your idea is gaining traction in the community.']
    ];

    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, reference_type, reference_id, is_read) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($users as $userId) {
        // Add 3 random notifications for each user
        for ($i = 0; $i < 3; $i++) {
            $type = $notifTypes[array_rand($notifTypes)];
            $isRead = rand(0, 1);
            $stmt->execute([$userId, $type['title'], $type['message'], 'test', rand(1, 100), $isRead]);
        }
    }

    // Special notifications for Kamil
    if ($kamilId) {
        $stmt->execute([$kamilId, 'Welcome to VTR', 'Start by exploring trending concepts.', 'system', null, 0]);
        $stmt->execute([$kamilId, 'Profile Verified', 'Your account has been fully verified.', 'system', null, 1]);
        $stmt->execute([$kamilId, 'Idea Reached Top 5', 'Your project is now in the top 5 trending list!', 'idea', null, 0]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Notifications seeded successfully.']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
