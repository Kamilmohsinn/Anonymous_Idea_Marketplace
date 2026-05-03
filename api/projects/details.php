<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

$userId = requireAuth();
$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit();
}

$pdo = getDbConnection();

// Fetch project info
$stmt = $pdo->prepare("SELECT p.*, i.title as idea_title, u.email as creator_email 
                       FROM projects p 
                       JOIN ideas i ON p.idea_id = i.id 
                       JOIN users u ON p.creator_id = u.id 
                       WHERE p.id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit();
}

// Security: Check if user is creator, investor, or collaborator
$isMember = ($project['creator_id'] == $userId || $project['investor_id'] == $userId);

if (!$isMember) {
    $collabStmt = $pdo->prepare("SELECT 1 FROM project_collaborators WHERE project_id = ? AND user_id = ?");
    $collabStmt->execute([$projectId, $userId]);
    $isMember = $collabStmt->fetch();
}

if (!$isMember) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access to project']);
    exit();
}

// Fetch Milestones
$milestoneStmt = $pdo->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY due_date ASC");
$milestoneStmt->execute([$projectId]);
$milestones = $milestoneStmt->fetchAll();

// Fetch Collaborators
$collabStmt = $pdo->prepare("SELECT c.*, u.email, u.id as user_id 
                             FROM project_collaborators c 
                             JOIN users u ON c.user_id = u.id 
                             WHERE c.project_id = ?");
$collabStmt->execute([$projectId]);
$collaborators = $collabStmt->fetchAll();

// Fetch Investor
$investor = null;
if ($project['investor_id']) {
    $invStmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
    $invStmt->execute([$project['investor_id']]);
    $investor = $invStmt->fetch();
}

echo json_encode([
    'success' => true,
    'project' => $project,
    'milestones' => $milestones,
    'collaborators' => $collaborators,
    'investor' => $investor
]);
