<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_utils.php';

$userId = requireAuth();
$pdo = getDbConnection();

try {
    // Fetch projects where user is creator, investor, or collaborator
    $stmt = $pdo->prepare("
        SELECT p.*, i.title as idea_title, 
               (SELECT COUNT(*) FROM project_milestones WHERE project_id = p.id) as total_milestones,
               (SELECT COUNT(*) FROM project_milestones WHERE project_id = p.id AND status = 'Completed') as completed_milestones
        FROM projects p
        JOIN ideas i ON p.idea_id = i.id
        LEFT JOIN project_collaborators pc ON p.id = pc.project_id
        WHERE p.creator_id = ? OR p.investor_id = ? OR pc.user_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $projects = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'projects' => $projects
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
