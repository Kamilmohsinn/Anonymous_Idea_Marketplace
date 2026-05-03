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

$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$problemStatement = trim($_POST['problem_statement'] ?? '');
$proposedSolution = trim($_POST['proposed_solution'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$stage = trim($_POST['stage'] ?? 'Concept');
$status = 'Pending';

$allowedStages = ['Concept', 'Prototype', 'Beta', 'Startup'];
if (!in_array($stage, $allowedStages, true)) {
    $stage = 'Concept';
}

// Users can only save as Draft or submit for approval (Pending)
$inputStatus = trim($_POST['status'] ?? 'Pending');
if ($inputStatus === 'Draft') {
    $status = 'Draft';
} else {
    $status = 'Pending';
}


if ($title === '' || $category === '' || $problemStatement === '' || $proposedSolution === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Title, category, problem statement, and proposed solution are required']);
    exit();
}

$pseudonym = 'Innovator_' . random_int(100, 999);

$attachmentPath = null;
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

$similarity = findClosestIdea($pdo, $title, $problemStatement, $proposedSolution);
$similarityScore = (float) ($similarity['score'] ?? 0);
$similarIdeaId = isset($similarity['idea_id']) ? (int) $similarity['idea_id'] : null;

$stmt = $pdo->prepare('INSERT INTO ideas (user_id, pseudonym, title, category, problem_statement, proposed_solution, tags, stage, status, attachment_path, similarity_score, similar_idea_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$userId, $pseudonym, $title, $category, $problemStatement, $proposedSolution, $tags, $stage, $status, $attachmentPath, $similarityScore, $similarIdeaId]);

$ideaId = (int) $pdo->lastInsertId();

$identityStmt = $pdo->prepare('INSERT INTO anonymous_identity_map (idea_id, user_id, pseudonym) VALUES (?, ?, ?)');
$identityStmt->execute([$ideaId, $userId, $pseudonym]);

recalculateIdeaMetrics($pdo, $ideaId);

if ($status === 'Published') {
    addReputation($pdo, $userId, 5, 'idea_posted');
}

echo json_encode([
    'success' => true,
    'message' => 'Idea created successfully',
    'idea' => [
        'id' => $ideaId,
        'pseudonym' => $pseudonym,
        'title' => $title,
        'category' => $category,
        'problem_statement' => $problemStatement,
        'proposed_solution' => $proposedSolution,
        'tags' => $tags,
        'stage' => $stage,
        'status' => $status,
        'attachment_path' => $attachmentPath,
        'similarity_score' => $similarityScore,
        'similar_idea_id' => $similarIdeaId
    ]
]);
