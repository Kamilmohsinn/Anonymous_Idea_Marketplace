<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth_utils.php';

$userId = requireAuth();
$pdo = getDbConnection();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$campaignId = (int)($data['campaign_id'] ?? 0);
$rewardId = isset($data['reward_id']) ? (int)$data['reward_id'] : null;
$amount = (float)($data['amount'] ?? 0);
$message = $data['message'] ?? '';

if ($campaignId <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid campaign or amount']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Check if campaign is active
    $checkStmt = $pdo->prepare("SELECT status, current_amount, goal_amount FROM crowdfunding_campaigns WHERE id = ? FOR UPDATE");
    $checkStmt->execute([$campaignId]);
    $campaign = $checkStmt->fetch();

    if (!$campaign || $campaign['status'] !== 'Active') {
        echo json_encode(['success' => false, 'message' => 'Campaign is not accepting donations']);
        $pdo->rollBack();
        exit();
    }

    // Insert donation
    $stmt = $pdo->prepare("INSERT INTO campaign_donations (campaign_id, user_id, reward_id, amount, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$campaignId, $userId, $rewardId, $amount, $message]);

    // Update campaign current amount
    $newAmount = $campaign['current_amount'] + $amount;
    $updateStmt = $pdo->prepare("UPDATE crowdfunding_campaigns SET current_amount = ? WHERE id = ?");
    $updateStmt->execute([$newAmount, $campaignId]);

    // Check if goal reached
    if ($newAmount >= $campaign['goal_amount']) {
        $pdo->prepare("UPDATE crowdfunding_campaigns SET status = 'Completed' WHERE id = ?")->execute([$campaignId]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Donation successful', 'new_amount' => $newAmount]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to process donation: ' . $e->getMessage()]);
}
