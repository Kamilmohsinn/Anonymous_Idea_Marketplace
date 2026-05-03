<?php
/**
 * Seeder for specific chat conversations between 2-3 users.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    // Find specific users
    $u1 = $pdo->prepare("SELECT id FROM users WHERE email = 'innovator1@example.com'");
    $u1->execute();
    $innovatorId = $u1->fetch()['id'];

    $u2 = $pdo->prepare("SELECT id FROM users WHERE email = 'collaborator1@example.com'");
    $u2->execute();
    $collaboratorId = $u2->fetch()['id'];

    $u3 = $pdo->prepare("SELECT id FROM users WHERE email = 'tech_wiz@example.com'");
    $u3->execute();
    $techWizId = $u3->fetch()['id'];

    if (!$innovatorId || !$collaboratorId || !$techWizId) {
        throw new Exception("Required seed users not found. Run seed_data.php first.");
    }

    // 1. Chat between Innovator and Collaborator
    // Find or create a collab request first
    $ideaStmt = $pdo->prepare("SELECT id FROM ideas WHERE user_id = ? LIMIT 1");
    $ideaStmt->execute([$innovatorId]);
    $ideaId = $ideaStmt->fetch()['id'];

    if ($ideaId) {
        $collabStmt = $pdo->prepare("INSERT INTO collaboration_requests (idea_id, sender_user_id, receiver_user_id, message, status) VALUES (?, ?, ?, ?, 'Accepted')");
        $collabStmt->execute([$ideaId, $collaboratorId, $innovatorId, "I really like your AI project. Let's build it!"]);
        $requestId = $pdo->lastInsertId();

        $roomStmt = $pdo->prepare("INSERT INTO chat_rooms (collaboration_request_id, user_one_id, user_two_id) VALUES (?, ?, ?)");
        $roomStmt->execute([$requestId, $collaboratorId, $innovatorId]);
        $roomId = $pdo->lastInsertId();

        $msgStmt = $pdo->prepare("INSERT INTO chat_messages (room_id, sender_user_id, message) VALUES (?, ?, ?)");
        $messages = [
            [$collaboratorId, "Hey! Thanks for accepting my request."],
            [$innovatorId, "No problem! Your profile looks impressive. Do you have experience with Python?"],
            [$collaboratorId, "Yes, I've been working with PyTorch and FastAPI for about 3 years now."],
            [$innovatorId, "Perfect. That's exactly what we need for the backend."],
            [$collaboratorId, "Awesome. Should we set up a GitHub repo?"],
            [$innovatorId, "Definitely. I'll share the private link once I've pushed the initial boilerplate."],
            [$collaboratorId, "Sounds like a plan. Talk soon!"]
        ];
        foreach ($messages as $m) {
            $msgStmt->execute([$roomId, $m[0], $m[1]]);
        }
    }

    // 2. Chat between Innovator and Tech Wiz
    $ideaStmt->execute([$innovatorId]);
    $ideaId2 = $ideaStmt->fetch()['id']; // Could be same idea or different

    if ($ideaId2) {
        $collabStmt->execute([$ideaId2, $techWizId, $innovatorId, "I can help with the 3D printing aspect of your project."]);
        $requestId2 = $pdo->lastInsertId();

        $roomStmt->execute([$requestId2, $techWizId, $innovatorId]);
        $roomId2 = $pdo->lastInsertId();

        $messages2 = [
            [$techWizId, "Hi there! I saw your idea for 3D printed housing."],
            [$innovatorId, "Hey! Yes, it's a bit ambitious but I think it's doable."],
            [$techWizId, "I actually have access to a large-scale industrial printer. We could run some tests."],
            [$innovatorId, "Wait, really? That would be a game changer."],
            [$techWizId, "Yeah, I work at a research lab. We have some spare capacity."],
            [$innovatorId, "I'm speechless. When can I see the facility?"],
            [$techWizId, "How about next Tuesday? I'll send you the location."]
        ];
        foreach ($messages2 as $m) {
            $msgStmt->execute([$roomId2, $m[0], $m[1]]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Specific chats seeded successfully.']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Seeding failed: ' . $e->getMessage()]);
}
