<?php
header('Content-Type: application/json');
require_once '../db.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid campaign ID']);
    exit();
}

try {
    // Get campaign details
    $stmt = $pdo->prepare("SELECT c.*, u.email as user_email, i.pseudonym 
                           FROM crowdfunding_campaigns c 
                           JOIN users u ON c.user_id = u.id 
                           LEFT JOIN ideas i ON c.idea_id = i.id 
                           WHERE c.id = ?");
    $stmt->execute([$id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        echo json_encode(['success' => false, 'message' => 'Campaign not found']);
        exit();
    }

    // Get rewards
    $rewardStmt = $pdo->prepare("SELECT * FROM campaign_rewards WHERE campaign_id = ? ORDER BY min_amount ASC");
    $rewardStmt->execute([$id]);
    $rewards = $rewardStmt->fetchAll();

    // Get donations
    $donationStmt = $pdo->prepare("SELECT d.*, u.email as donor_email 
                                 FROM campaign_donations d 
                                 JOIN users u ON d.user_id = u.id 
                                 WHERE d.campaign_id = ? 
                                 ORDER BY d.created_at DESC");
    $donationStmt->execute([$id]);
    $donations = $donationStmt->fetchAll();

    echo json_encode([
        'success' => true, 
        'campaign' => $campaign, 
        'rewards' => $rewards, 
        'donations' => $donations
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch campaign details: ' . $e->getMessage()]);
}
