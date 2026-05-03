<?php
/**
 * Database Setup Script for Railway Deployment
 * This script reads database.sql and executes it against the Railway MySQL instance.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    $pdo = getDbConnection();
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/../database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("database.sql file not found at " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and split by semicolon
    // Note: This is a simple parser. For complex SQL with triggers/procedures, 
    // it might need more advanced handling, but for your schema it works fine.
    $queries = explode(';', $sql);
    
    $executed = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
            $executed++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Database setup successful! Executed $executed queries.",
        'details' => 'Your tables (users, ideas, crowdfunding, etc.) are now ready on Railway.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Setup failed: ' . $e->getMessage()
    ]);
}
