<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/platform_utils.php';

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    // 1. Get or Create Kamil
    $checkKamil = $pdo->prepare("SELECT id FROM users WHERE email = 'kamil@example.com'");
    $checkKamil->execute();
    $kamil = $checkKamil->fetch();
    if (!$kamil) {
        $pdo->prepare("INSERT INTO users (email, password, role) VALUES ('kamil@example.com', ?, 'user')")
            ->execute([password_hash('pass123', PASSWORD_DEFAULT)]);
        $kamilId = $pdo->lastInsertId();
    } else {
        $kamilId = $kamil['id'];
    }
    ensureUserProfile($pdo, $kamilId);

    // 2. Get or Create Partner
    $checkPartner = $pdo->prepare("SELECT id FROM users WHERE email = 'partner@example.com'");
    $checkPartner->execute();
    $partner = $checkPartner->fetch();
    if (!$partner) {
        $pdo->prepare("INSERT INTO users (email, password, role) VALUES ('partner@example.com', ?, 'user')")
            ->execute([password_hash('pass123', PASSWORD_DEFAULT)]);
        $partnerId = $pdo->lastInsertId();
    } else {
        $partnerId = $partner['id'];
    }
    ensureUserProfile($pdo, $partnerId);

    // 3. Post Kamil's Idea
    $ideaStmt = $pdo->prepare("INSERT INTO ideas (user_id, pseudonym, title, category, problem_statement, proposed_solution, tags, stage, status) VALUES (?, 'Kamil', 'Global AI Ethics Protocol', 'AI & Technology', 'AI systems are being deployed without ethical guardrails.', 'A decentralized protocol for verifying AI safety and ethics before deployment.', 'ai,ethics,kamil', 'Concept', 'Published')");
    $ideaStmt->execute([$kamilId]);
    $ideaId = $pdo->lastInsertId();

    $pdo->prepare("INSERT IGNORE INTO anonymous_identity_map (idea_id, user_id, pseudonym) VALUES (?, ?, 'Kamil')")
        ->execute([$ideaId, $kamilId]);

    // 4. Collaboration
    $pdo->prepare("INSERT INTO collaboration_requests (idea_id, sender_user_id, receiver_user_id, message, status) VALUES (?, ?, ?, 'I am an ethics researcher. I can contribute to the framework.', 'Accepted')")
        ->execute([$ideaId, $partnerId, $kamilId]);
    $requestId = $pdo->lastInsertId();

    // 5. Chat Room
    $pdo->prepare("INSERT INTO chat_rooms (collaboration_request_id, user_one_id, user_two_id) VALUES (?, ?, ?)")
        ->execute([$requestId, $partnerId, $kamilId]);
    $roomId = $pdo->lastInsertId();

    // 6. Messages
    $msgStmt = $pdo->prepare("INSERT INTO chat_messages (room_id, sender_user_id, message) VALUES (?, ?, ?)");
    $messages = [
        [$partnerId, "Hi Kamil, thank you for the invite. This ethics protocol is exactly what the industry needs."],
        [$kamilId, "Glad to have you! I saw your work on algorithmic bias. It's very relevant."],
        [$partnerId, "Exactly. We can integrate the bias-detection modules into the core protocol."],
        [$kamilId, "Perfect. Let's start by drafting the whitepaper section on transparency."],
        [$partnerId, "On it. I'll share my initial notes by tomorrow."],
        [$kamilId, "Great, looking forward to it."]
    ];
    foreach ($messages as $m) {
        $msgStmt->execute([$roomId, $m[0], $m[1]]);
    }

    recalculateIdeaMetrics($pdo, $ideaId);
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Kamil's project and chat are ready."]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
