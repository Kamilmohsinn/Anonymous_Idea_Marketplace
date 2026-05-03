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

$ideaId = (int) ($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$problemStatement = trim($_POST['problem_statement'] ?? '');
$proposedSolution = trim($_POST['proposed_solution'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$stage = trim($_POST['stage'] ?? 'Concept');
$status = trim($_POST['status'] ?? 'Published');

$allowedStages = ['Concept', 'Prototype', 'Beta', 'Startup'];
if (!in_array($stage, $allowedStages, true)) {
    $stage = 'Concept';
}

$allowedStatus = ['Draft', 'Published'];
if (!in_array($status, $allowedStatus, true)) {
    $status = 'Published';
}

if ($ideaId <= 0 || $title === '' || $category === '' || $problemStatement === '' || $proposedSolution === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid id, title, category, problem statement, and proposed solution are required']);
    exit();
}

$ownerStmt = $pdo->prepare('SELECT id FROM ideas WHERE id = ? AND user_id = ? LIMIT 1');
$ownerStmt->execute([$ideaId, $userId]);
if (!$ownerStmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only update your own ideas']);
    exit();
}

$existingStmt = $pdo->prepare('SELECT attachment_path FROM ideas WHERE id = ? LIMIT 1');
$existingStmt->execute([$ideaId]);
$existing = $existingStmt->fetch();
$attachmentPath = $existing['attachment_path'] ?? null;

if (isset($_FILES['attachment']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
    $uploadsDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ideas';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($_FILES['attachment']['name']));
    $targetName = uniqid('idea_', true) . '_' . $safeName;
    $targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $targetName;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
        $attachmentPath = 'uploads/ideas/' . $targetName;
    }
}

$similarity = findClosestIdea($pdo, $title, $problemStatement, $proposedSolution, $ideaId);
$similarityScore = (float) ($similarity['score'] ?? 0);
$similarIdeaId = isset($similarity['idea_id']) ? (int) $similarity['idea_id'] : null;

$updateStmt = $pdo->prepare('UPDATE ideas SET title = ?, category = ?, problem_statement = ?, proposed_solution = ?, tags = ?, stage = ?, status = ?, attachment_path = ?, similarity_score = ?, similar_idea_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
$updateStmt->execute([$title, $category, $problemStatement, $proposedSolution, $tags, $stage, $status, $attachmentPath, $similarityScore, $similarIdeaId, $ideaId]);
recalculateIdeaMetrics($pdo, $ideaId);

echo json_encode([
    'success' => true,
    'message' => 'Idea updated successfully'
]);
