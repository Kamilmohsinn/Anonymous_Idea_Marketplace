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

$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$goalAmount = (float)($data['goal_amount'] ?? 0);
$endDate = $data['end_date'] ?? '';
$ideaId = isset($data['idea_id']) ? (int)$data['idea_id'] : null;
$rewards = $data['rewards'] ?? [];

if (empty($title) || empty($description) || $goalAmount <= 0 || empty($endDate)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO crowdfunding_campaigns (user_id, idea_id, title, description, goal_amount, end_date, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([$userId, $ideaId, $title, $description, $goalAmount, $endDate]);
    $campaignId = $pdo->lastInsertId();

    if (!empty($rewards)) {
        $rewardStmt = $pdo->prepare("INSERT INTO campaign_rewards (campaign_id, title, description, min_amount) VALUES (?, ?, ?, ?)");
        foreach ($rewards as $reward) {
            $rTitle = $reward['title'] ?? '';
            $rDesc = $reward['description'] ?? '';
            $rMin = (float)($reward['min_amount'] ?? 0);
            if (!empty($rTitle) && $rMin > 0) {
                $rewardStmt->execute([$campaignId, $rTitle, $rDesc, $rMin]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Campaign created successfully', 'campaign_id' => $campaignId]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to create campaign: ' . $e->getMessage()]);
}
