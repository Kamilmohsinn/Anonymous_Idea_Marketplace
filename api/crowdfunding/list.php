<?php
header('Content-Type: application/json');
require_once '../db.php';

$pdo = getDbConnection();

$status = $_GET['status'] ?? 'Active';

try {
    $query = "SELECT c.*, u.email as user_email, i.pseudonym 
              FROM crowdfunding_campaigns c 
              JOIN users u ON c.user_id = u.id 
              LEFT JOIN ideas i ON c.idea_id = i.id";
    
    if ($status !== 'all') {
        $query .= " WHERE c.status = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->query($query);
    }

    $campaigns = $stmt->fetchAll();

    echo json_encode(['success' => true, 'campaigns' => $campaigns]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch campaigns: ' . $e->getMessage()]);
}
