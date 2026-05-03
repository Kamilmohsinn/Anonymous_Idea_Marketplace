<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';
require_once __DIR__ . '/../platform_utils.php';

$userId = requireAuth();
$pdo = getDbConnection();

// Check if requester is admin
$userStmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$ideaId = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'approve' or 'reject'

if ($ideaId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid idea id and action (approve/reject) are required']);
    exit();
}

$status = ($action === 'approve') ? 'Published' : 'Rejected';

$updateStmt = $pdo->prepare('UPDATE ideas SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = "Pending"');
$updateStmt->execute([$status, $ideaId]);

if ($updateStmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Idea not found or not in Pending status']);
    exit();
}

// Fetch owner to notify
$ownerStmt = $pdo->prepare('SELECT user_id, title FROM ideas WHERE id = ?');
$ownerStmt->execute([$ideaId]);
$idea = $ownerStmt->fetch();

if ($idea) {
    $ownerId = (int) $idea['user_id'];
    $title = $idea['title'];
    $msg = ($action === 'approve') ? "Your idea '$title' has been approved and is now live!" : "Your idea '$title' was reviewed and rejected.";
    createNotification($pdo, $ownerId, 'Idea Review Update', $msg, 'idea', $ideaId);
    
    if ($action === 'approve') {
        addReputation($pdo, $ownerId, 10, 'idea_approved');
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Idea has been ' . ($action === 'approve' ? 'approved' : 'rejected')
]);
