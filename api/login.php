<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt_utils.php';
$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit();
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, password, role, account_status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if (($user['account_status'] ?? 'active') === 'suspended') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Your account is suspended. Contact admin.']);
            exit();
        }

        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['jwt'] = createJwtToken([
            'user_id' => (int) $user['id'],
            'email' => $email,
            'role' => $_SESSION['role']
        ]);

        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'role' => $_SESSION['role'],
            'token' => $_SESSION['jwt']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>