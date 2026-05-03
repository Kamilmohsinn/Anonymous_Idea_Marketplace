<?php
/**
 * Master Demo Seeder for Anonymous Idea Marketplace
 * Populates all modules: Users, Ideas, Votes, Comments, Collaboration, Chat, Crowdfunding, Projects, Milestones, and Reports.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/platform_utils.php';

try {
    $pdo = getDbConnection();
    // $pdo->beginTransaction();

    echo "Cleaning up previous demo data...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = [
        'users', 'user_profiles', 'user_settings', 'ideas', 'idea_votes', 'comments', 
        'comment_reactions', 'collaboration_requests', 'chat_rooms', 'chat_messages', 
        'notifications', 'reports', 'crowdfunding_campaigns', 'campaign_rewards', 
        'campaign_donations', 'projects', 'project_milestones', 'project_collaborators',
        'anonymous_identity_map'
    ];
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 1. Create Users (Admin, Investor, Users)
    echo "Creating users and roles...\n";
    $usersData = [
        ['email' => 'admin@example.com', 'password' => 'password123', 'role' => 'admin'],
        ['email' => 'test@example.com', 'password' => 'password123', 'role' => 'user'],
        ['email' => 'investor@example.com', 'password' => 'password123', 'role' => 'investor'],
        ['email' => 'tech_lead@example.com', 'password' => 'password123', 'role' => 'user'],
        ['email' => 'ui_designer@example.com', 'password' => 'password123', 'role' => 'user'],
        ['email' => 'venture_cap@example.com', 'password' => 'password123', 'role' => 'investor']
    ];

    $uIds = [];
    foreach ($usersData as $u) {
        $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)")
            ->execute([$u['email'], password_hash($u['password'], PASSWORD_DEFAULT), $u['role']]);
        $id = $pdo->lastInsertId();
        $uIds[$u['email']] = $id;
        ensureUserProfile($pdo, $id);
        $pdo->prepare("INSERT INTO user_settings (user_id) VALUES (?)")->execute([$id]);
    }

    // 2. Create Categories
    $categories = ['AI & Technology', 'Healthcare', 'FinTech', 'Education', 'Environment'];
    foreach ($categories as $cat) {
        $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$cat]);
    }

    // 3. Create Ideas
    echo "Creating demo ideas...\n";
    $ideas = [
        [
            'email' => 'test@example.com',
            'title' => 'Smart Urban Farming Kits',
            'pseudonym' => 'GreenFinger',
            'category' => 'Environment',
            'problem' => 'Urban dwellers lack space to grow organic food.',
            'solution' => 'Vertical hydroponic kits that fit on balconies and use AI for nutrient monitoring.',
            'status' => 'Published',
            'stage' => 'Prototype'
        ],
        [
            'email' => 'tech_lead@example.com',
            'title' => 'Blockchain Patient Records',
            'pseudonym' => 'MedChain',
            'category' => 'Healthcare',
            'problem' => 'Medical history is fragmented across different hospitals.',
            'solution' => 'A secure, decentralized ledger for patient data that gives control back to the patient.',
            'status' => 'Published',
            'stage' => 'Concept'
        ],
        [
            'email' => 'ui_designer@example.com',
            'title' => 'AR Furniture Preview',
            'pseudonym' => 'DesignGenie',
            'category' => 'AI & Technology',
            'problem' => 'People struggle to visualize furniture in their homes before buying.',
            'solution' => 'An AR app that renders hyper-realistic 3D furniture in real-time using mobile cameras.',
            'status' => 'Published',
            'stage' => 'Beta'
        ],
        [
            'email' => 'test@example.com',
            'title' => 'Micro-Investment for Kids',
            'pseudonym' => 'FutureFin',
            'category' => 'FinTech',
            'problem' => 'Financial literacy is missing in early education.',
            'solution' => 'A gamified investment app for parents and kids to learn about stocks and savings.',
            'status' => 'Pending',
            'stage' => 'Concept'
        ]
    ];

    $ideaIds = [];
    foreach ($ideas as $i) {
        $pdo->prepare("INSERT INTO ideas (user_id, pseudonym, title, category, problem_statement, proposed_solution, status, stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$uIds[$i['email']], $i['pseudonym'], $i['title'], $i['category'], $i['problem'], $i['solution'], $i['status'], $i['stage']]);
        $id = $pdo->lastInsertId();
        $ideaIds[$i['title']] = $id;
        $pdo->prepare("INSERT INTO anonymous_identity_map (idea_id, user_id, pseudonym) VALUES (?, ?, ?)")
            ->execute([$id, $uIds[$i['email']], $i['pseudonym']]);
    }

    // 4. Votes & Trending
    echo "Simulating community interaction...\n";
    $allUserIds = array_values($uIds);
    $allIdeaIds = array_values($ideaIds);
    foreach ($allIdeaIds as $id) {
        foreach ($allUserIds as $uId) {
            if (rand(0, 1) === 1) {
                $pdo->prepare("INSERT INTO idea_votes (idea_id, user_id, vote_type) VALUES (?, ?, ?)")
                    ->execute([$id, $uId, 1]);
            }
        }
    }

    // 5. Comments & Reactions
    $commentStmt = $pdo->prepare("INSERT INTO comments (idea_id, user_id, pseudonym, content) VALUES (?, ?, ?, ?)");
    $commentStmt->execute([$ideaIds['Smart Urban Farming Kits'], $uIds['tech_lead@example.com'], 'TechGuru', 'This could really disrupt urban agriculture. Have you thought about water recycling?']);
    $c1 = $pdo->lastInsertId();
    $commentStmt->execute([$ideaIds['Smart Urban Farming Kits'], $uIds['test@example.com'], 'GreenFinger', 'Great point! We plan to use a closed-loop system to reduce water waste.']);
    
    // Reaction
    $pdo->prepare("INSERT INTO comment_reactions (comment_id, user_id, reaction) VALUES (?, ?, 'helpful')")
        ->execute([$c1, $uIds['investor@example.com']]);

    // 6. Collaboration & Chat (Identity Reveal Demo)
    echo "Setting up collaboration and chat...\n";
    $pdo->prepare("INSERT INTO collaboration_requests (idea_id, sender_user_id, receiver_user_id, message, status) VALUES (?, ?, ?, ?, 'Accepted')")
        ->execute([$ideaIds['Blockchain Patient Records'], $uIds['investor@example.com'], $uIds['tech_lead@example.com'], 'I love the healthcare potential here. Let\'s discuss investment.']);
    $requestId = $pdo->lastInsertId();
    
    $pdo->prepare("INSERT INTO chat_rooms (collaboration_request_id, user_one_id, user_two_id) VALUES (?, ?, ?)")
        ->execute([$requestId, $uIds['investor@example.com'], $uIds['tech_lead@example.com']]);
    $roomId = $pdo->lastInsertId();
    
    $pdo->prepare("INSERT INTO chat_messages (room_id, sender_user_id, message) VALUES (?, ?, ?)")
        ->execute([$roomId, $uIds['investor@example.com'], 'Hello, thank you for accepting my request. Looking forward to your identity reveal!']);

    // 7. Crowdfunding Demo
    echo "Seeding Crowdfunding...\n";
    $pdo->prepare("INSERT INTO crowdfunding_campaigns (idea_id, user_id, title, description, goal_amount, current_amount, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$ideaIds['Smart Urban Farming Kits'], $uIds['test@example.com'], 'Scale Urban Farming 2026', 'Help us bring vertical farming to 10,000 households.', 50000.00, 12500.00, '2026-12-31', 'Active']);
    $campId = $pdo->lastInsertId();
    
    $pdo->prepare("INSERT INTO campaign_rewards (campaign_id, title, description, min_amount) VALUES (?, ?, ?, ?)")
        ->execute([$campId, 'Early Bird Kit', 'Get a mini-starter kit for your balcony.', 100.00]);
    $pdo->prepare("INSERT INTO campaign_rewards (campaign_id, title, description, min_amount) VALUES (?, ?, ?, ?)")
        ->execute([$campId, 'Professional Setup', 'A full vertical garden installed at your home.', 1000.00]);

    $pdo->prepare("INSERT INTO campaign_donations (campaign_id, user_id, amount, message) VALUES (?, ?, ?, ?)")
        ->execute([$campId, $uIds['venture_cap@example.com'], 12500.00, 'Believing in a greener future!']);


    // 8. Projects & Milestone Tracking
    echo "Seeding Projects and Milestones...\n";
    $pdo->prepare("INSERT INTO projects (idea_id, creator_id, investor_id, title, status) VALUES (?, ?, ?, ?, ?)")
        ->execute([$ideaIds['AR Furniture Preview'], $uIds['ui_designer@example.com'], $uIds['venture_cap@example.com'], 'AR Furniture Pro Launch', 'Active']);

    $projId = $pdo->lastInsertId();
    
    $milestones = [
        ['title' => 'Phase 1: 3D Asset Library', 'desc' => 'Complete modeling of top 100 furniture items.', 'status' => 'Completed', 'date' => '2026-05-01'],
        ['title' => 'Phase 2: Beta App Release', 'desc' => 'Release beta version to first 500 testers.', 'status' => 'In Progress', 'date' => '2026-07-15'],
        ['title' => 'Phase 3: Global Launch', 'desc' => 'Full production launch on iOS and Android.', 'status' => 'Pending', 'date' => '2026-10-01']
    ];
    foreach ($milestones as $m) {
        $pdo->prepare("INSERT INTO project_milestones (project_id, title, description, status, due_date) VALUES (?, ?, ?, ?, ?)")
            ->execute([$projId, $m['title'], $m['desc'], $m['status'], $m['date']]);
    }

    // 9. Reporting (Moderation Demo)
    $pdo->prepare("INSERT INTO reports (reporter_user_id, target_type, target_id, reason, details, status) VALUES (?, 'comment', ?, 'inappropriate', 'Harsh language in comment.', 'Open')")
        ->execute([$uIds['test@example.com'], $c1]);

    // 10. Notifications
    $pdo->prepare("INSERT INTO notifications (user_id, title, message, reference_type, reference_id) VALUES (?, ?, ?, ?, ?)")
        ->execute([$uIds['test@example.com'], 'New Donation!', 'Someone donated $12,500 to your campaign!', 'crowdfunding', $campId]);

    recalculateAllTrending($pdo);
    // $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Full Demo Data Seeded! All modules are now populated.']);

} catch (Exception $e) {
    // if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Seeding failed: ' . $e->getMessage()]);
}
