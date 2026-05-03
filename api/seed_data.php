<?php
/**
 * Standalone Database Seeder for Anonymous Idea Marketplace
 * This script populates the database with users, ideas, votes, comments, and chats.
 */

header('Content-Type: application/json');

// Include DB connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/platform_utils.php';

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    echo "Cleaning up old demo data (optional)...\n";
    // We won't truncate everything to avoid destroying user's work, but we can clean up if we want.
    // $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE users; TRUNCATE ideas; TRUNCATE comments; ...; SET FOREIGN_KEY_CHECKS = 1;");

    // 1. Create Users
    echo "Creating users...\n";
    $usersData = [
        ['email' => 'innovator1@example.com', 'password' => 'pass123', 'role' => 'user'],
        ['email' => 'innovator2@example.com', 'password' => 'pass123', 'role' => 'user'],
        ['email' => 'collaborator1@example.com', 'password' => 'pass123', 'role' => 'user'],
        ['email' => 'collaborator2@example.com', 'password' => 'pass123', 'role' => 'user'],
        ['email' => 'investor1@example.com', 'password' => 'pass123', 'role' => 'investor'],
        ['email' => 'investor2@example.com', 'password' => 'pass123', 'role' => 'investor'],
        ['email' => 'tech_wiz@example.com', 'password' => 'pass123', 'role' => 'user'],
        ['email' => 'market_pro@example.com', 'password' => 'pass123', 'role' => 'user'],
        ['email' => 'alpha_tester@example.com', 'password' => 'pass123', 'role' => 'user'],
        ['email' => 'beta_tester@example.com', 'password' => 'pass123', 'role' => 'user'],
    ];

    $userIds = [];
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (email, password, role) VALUES (?, ?, ?)");
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");

    foreach ($usersData as $u) {
        $stmt->execute([$u['email'], password_hash($u['password'], PASSWORD_DEFAULT), $u['role']]);
        $checkStmt->execute([$u['email']]);
        $user = $checkStmt->fetch();
        $userIds[] = $user['id'];
        
        // Ensure profiles and settings exist
        ensureUserProfile($pdo, $user['id']);
        $pdo->prepare("INSERT IGNORE INTO user_settings (user_id) VALUES (?)")->execute([$user['id']]);
    }

    // 2. Create Categories if they don't exist
    $categories = ['AI & Technology', 'Healthcare', 'FinTech', 'Education', 'Environment', 'Social Media', 'E-commerce', 'Blockchain'];
    $catStmt = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
    foreach ($categories as $cat) {
        $catStmt->execute([$cat]);
    }

    // 3. Create 20 Ideas
    echo "Creating 20 ideas...\n";
    $ideasData = [
        [
            'title' => 'Decentralized Peer-to-Peer Energy Grid',
            'category' => 'Environment',
            'pseudonym' => 'EcoWarrior_99',
            'problem' => 'Centralized energy grids are inefficient and vulnerable to outages.',
            'solution' => 'A blockchain-based system allowing neighbors to sell excess solar energy directly to each other using smart meters.',
            'tags' => 'blockchain,energy,sustainability',
            'stage' => 'Concept'
        ],
        [
            'title' => 'AI-Driven Personal Legal Assistant',
            'category' => 'AI & Technology',
            'pseudonym' => 'JusticeMind',
            'problem' => 'Legal advice is prohibitively expensive for small businesses and individuals.',
            'solution' => 'An AI that parses contracts, identifies risks, and suggests legal clauses based on jurisdictional case law.',
            'tags' => 'ai,legaltech,automation',
            'stage' => 'Prototype'
        ],
        [
            'title' => 'Tele-Health for Remote Wildlife Conservation',
            'category' => 'Healthcare',
            'pseudonym' => 'NatureDoc',
            'problem' => 'Vets cannot easily reach endangered species in remote areas for routine checks.',
            'solution' => 'Deploying low-latency satellite links and IoT sensors on animals to allow remote diagnosis and robotic medical intervention.',
            'tags' => 'iot,health,wildlife',
            'stage' => 'Beta'
        ],
        [
            'title' => 'Fractional Real Estate Investing for Gen Z',
            'category' => 'FinTech',
            'pseudonym' => 'UrbanInvestor',
            'problem' => 'High property prices prevent young people from entering the real estate market.',
            'solution' => 'A platform that tokenizes real estate assets, allowing users to buy as little as $10 worth of property shares.',
            'tags' => 'fintech,realestate,tokens',
            'stage' => 'Startup'
        ],
        [
            'title' => 'Smart Grocery List with Dynamic Pricing',
            'category' => 'E-commerce',
            'pseudonym' => 'SmartShopper',
            'problem' => 'Grocery prices fluctuate wildly, making it hard to stick to a budget.',
            'solution' => 'An app that tracks local store prices in real-time and optimizes your shopping list for the lowest total cost.',
            'tags' => 'shopping,data,budget',
            'stage' => 'Concept'
        ],
        [
            'title' => 'Mental Health Support for Remote Workers',
            'category' => 'Healthcare',
            'pseudonym' => 'ZenBuilder',
            'problem' => 'Isolation and burnout are rising among remote developers and freelancers.',
            'solution' => 'A VR-based meditation and coworking space that mimics a physical office environment with social cues.',
            'tags' => 'vr,mentalhealth,remote',
            'stage' => 'Prototype'
        ],
        [
            'title' => 'Automated Micro-Farming Kits',
            'category' => 'Environment',
            'pseudonym' => 'GreenThumb_AI',
            'problem' => 'Urban dwellers want to grow food but lack space, time, and knowledge.',
            'solution' => 'Hydroponic kits that use AI to monitor nutrient levels and light, managed entirely through a mobile app.',
            'tags' => 'agritech,iot,urban',
            'stage' => 'Beta'
        ],
        [
            'title' => 'Decentralized Identity for Refugees',
            'category' => 'Blockchain',
            'pseudonym' => 'GlobalCitizen',
            'problem' => 'Refugees often lose physical documents, making it impossible to access services.',
            'solution' => 'A sovereign identity system on the blockchain that stores verified credentials securely and privately.',
            'tags' => 'blockchain,identity,humanitarian',
            'stage' => 'Concept'
        ],
        [
            'title' => 'AI Tutor for Dyslexic Learners',
            'category' => 'Education',
            'pseudonym' => 'EduVision',
            'problem' => 'Traditional educational materials are not optimized for students with dyslexia.',
            'solution' => 'An AI that transforms any text into dyslexia-friendly formats and provides personalized audio-visual learning paths.',
            'tags' => 'edutech,ai,accessibility',
            'stage' => 'Prototype'
        ],
        [
            'title' => 'Waste-to-Biofuel Micro-Plants',
            'category' => 'Environment',
            'pseudonym' => 'BioGen_X',
            'problem' => 'Organic waste in cities contributes significantly to methane emissions.',
            'solution' => 'Neighborhood-scale plants that convert food waste into clean-burning biofuel for local transport.',
            'tags' => 'energy,waste,green',
            'stage' => 'Concept'
        ],
        [
            'title' => 'Skill-Swap Marketplace (No Money)',
            'category' => 'Social Media',
            'pseudonym' => 'TalentBarter',
            'problem' => 'People have skills but no money to pay for other services they need.',
            'solution' => 'A platform where you trade "hours" of your skill for "hours" of someone else\'s. 1hr coding = 1hr yoga.',
            'tags' => 'community,barter,skills',
            'stage' => 'Beta'
        ],
        [
            'title' => 'Precision Water Management for Vineyards',
            'category' => 'Environment',
            'pseudonym' => 'VineMaster',
            'problem' => 'Water scarcity is threatening high-value crops like wine grapes.',
            'solution' => 'IoT soil sensors and satellite data combined to deliver precise amounts of water to individual vines.',
            'tags' => 'iot,agritech,water',
            'stage' => 'Startup'
        ],
        [
            'title' => 'Anonymous Whistleblower Platform for ESG',
            'category' => 'FinTech',
            'pseudonym' => 'TruthSeeker',
            'problem' => 'Employees fear retaliation when reporting environmental or social governance violations.',
            'solution' => 'An encrypted, anonymous submission portal that verifies data using zero-knowledge proofs.',
            'tags' => 'privacy,esg,transparency',
            'stage' => 'Concept'
        ],
        [
            'title' => '3D Printed Sustainable Housing',
            'category' => 'Environment',
            'pseudonym' => 'FutureFab',
            'problem' => 'Traditional construction is slow, expensive, and high in carbon footprint.',
            'solution' => 'Using massive 3D printers and carbon-sequestering "concrete" to print houses in under 24 hours.',
            'tags' => 'construction,3dprint,eco',
            'stage' => 'Prototype'
        ],
        [
            'title' => 'Interactive AR History Tours',
            'category' => 'Education',
            'pseudonym' => 'HistoryBuff_AR',
            'problem' => 'Static museum exhibits are failing to engage younger generations.',
            'solution' => 'AR glasses or smartphone apps that overlay historical events and figures onto current city streets.',
            'tags' => 'ar,education,travel',
            'stage' => 'Beta'
        ],
        [
            'title' => 'Carbon Footprint API for Banks',
            'category' => 'FinTech',
            'pseudonym' => 'CarbonTrack',
            'problem' => 'Consumers don\'t know the environmental impact of their spending.',
            'solution' => 'An API that plugs into banking apps to show the CO2 impact of every transaction in real-time.',
            'tags' => 'fintech,climate,data',
            'stage' => 'Startup'
        ],
        [
            'title' => 'Micro-Influencer Matchmaking for Local Biz',
            'category' => 'Social Media',
            'pseudonym' => 'LocalVibe',
            'problem' => 'Local shops can\'t afford big influencers and don\'t know how to find local ones.',
            'solution' => 'A marketplace that connects shops with "neighborhood heroes" who have 1k-5k hyper-local followers.',
            'tags' => 'marketing,local,social',
            'stage' => 'Prototype'
        ],
        [
            'title' => 'Elderly Care Monitoring via Smart Flooring',
            'category' => 'Healthcare',
            'pseudonym' => 'SilverGuardian',
            'problem' => 'Falls are the leading cause of injury for the elderly, but cameras are intrusive.',
            'solution' => 'Sensors under the floor that detect unusual pressure patterns (falls) without violating privacy.',
            'tags' => 'healthcare,iot,privacy',
            'stage' => 'Concept'
        ],
        [
            'title' => 'Open-Source Biotech for Rare Diseases',
            'category' => 'Healthcare',
            'pseudonym' => 'BioHacker_Alpha',
            'problem' => 'Pharma companies don\'t invest in diseases with small patient populations.',
            'solution' => 'A collaborative platform for scientists to share research and crowdsource funding for orphan drugs.',
            'tags' => 'biotech,opensource,health',
            'stage' => 'Beta'
        ],
        [
            'title' => 'Automated Inventory Drone for Warehouses',
            'category' => 'AI & Technology',
            'pseudonym' => 'LogiFly',
            'problem' => 'Manual inventory counting is slow, dangerous, and prone to error.',
            'solution' => 'Drones that fly autonomously through warehouses at night, scanning RFID and barcodes to update stock.',
            'tags' => 'drones,ai,logistics',
            'stage' => 'Startup'
        ],
    ];

    $ideaStmt = $pdo->prepare("INSERT IGNORE INTO ideas (user_id, pseudonym, title, category, problem_statement, proposed_solution, tags, stage, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Published')");
    $identityStmt = $pdo->prepare("INSERT IGNORE INTO anonymous_identity_map (idea_id, user_id, pseudonym) VALUES (?, ?, ?)");
    
    $createdIdeaIds = [];
    foreach ($ideasData as $index => $idea) {
        $ownerId = $userIds[$index % count($userIds)];
        $ideaStmt->execute([
            $ownerId,
            $idea['pseudonym'],
            $idea['title'],
            $idea['category'],
            $idea['problem'],
            $idea['solution'],
            $idea['tags'],
            $idea['stage']
        ]);
        
        $newId = $pdo->lastInsertId();
        if ($newId) {
            $createdIdeaIds[] = $newId;
            $identityStmt->execute([$newId, $ownerId, $idea['pseudonym']]);
        } else {
            // If already exists, find the ID
            $s = $pdo->prepare("SELECT id FROM ideas WHERE title = ?");
            $s->execute([$idea['title']]);
            $createdIdeaIds[] = $s->fetch()['id'];
        }
    }

    // 4. Create Votes
    echo "Creating 100+ votes...\n";
    $voteStmt = $pdo->prepare("INSERT IGNORE INTO idea_votes (idea_id, user_id, vote_type) VALUES (?, ?, ?)");
    for ($i = 0; $i < 150; $i++) {
        $ideaId = $createdIdeaIds[array_rand($createdIdeaIds)];
        $userId = $userIds[array_rand($userIds)];
        $voteType = (rand(0, 10) > 2) ? 1 : -1; // Mostly upvotes
        $voteStmt->execute([$ideaId, $userId, $voteType]);
    }

    // 5. Create Comments
    echo "Creating 40+ comments...\n";
    $commentStmt = $pdo->prepare("INSERT INTO comments (idea_id, user_id, pseudonym, content) VALUES (?, ?, ?, ?)");
    $commentTexts = [
        "This is a brilliant concept! Have you considered the regulatory hurdles?",
        "I'd love to collaborate on the tech side. I have experience with blockchain.",
        "How do you plan to scale this in urban environments?",
        "Interesting idea, but I think the market is already saturated.",
        "Could this be applied to other industries as well?",
        "The problem statement is spot on. I face this every day.",
        "I'm an investor and I'd like to see a more detailed pitch deck.",
        "Great work on the proposed solution. Very innovative.",
        "I've seen something similar in Europe, maybe check out their model.",
        "What's the estimated time to prototype?",
    ];

    $commentIds = [];
    for ($i = 0; $i < 50; $i++) {
        $ideaId = $createdIdeaIds[array_rand($createdIdeaIds)];
        $userId = $userIds[array_rand($userIds)];
        $text = $commentTexts[array_rand($commentTexts)];
        $pseudonym = "User_" . rand(100, 999);
        $commentStmt->execute([$ideaId, $userId, $pseudonym, $text]);
        $commentIds[] = $pdo->lastInsertId();
    }

    // 6. Create Collaboration Requests
    echo "Creating collaboration requests...\n";
    $collabStmt = $pdo->prepare("INSERT INTO collaboration_requests (idea_id, sender_user_id, receiver_user_id, message, status) VALUES (?, ?, ?, ?, ?)");
    for ($i = 0; $i < 10; $i++) {
        $ideaId = $createdIdeaIds[array_rand($createdIdeaIds)];
        
        // Find owner
        $s = $pdo->prepare("SELECT user_id FROM ideas WHERE id = ?");
        $s->execute([$ideaId]);
        $row = $s->fetch();
        if (!$row) continue;
        $receiverId = $row['user_id'];
        
        $senderId = $userIds[array_rand($userIds)];
        if ($senderId === $receiverId) continue;
        
        $status = (rand(0, 1) === 0) ? 'Pending' : 'Accepted';
        $collabStmt->execute([
            $ideaId,
            $senderId,
            $receiverId,
            "Hi, I'm really interested in your idea. I'd love to help with the development.",
            $status
        ]);
        
        $collabRequestId = $pdo->lastInsertId();
        
        if ($status === 'Accepted' && $collabRequestId) {
            // Create chat room
            $roomStmt = $pdo->prepare("INSERT IGNORE INTO chat_rooms (collaboration_request_id, user_one_id, user_two_id) VALUES (?, ?, ?)");
            $roomStmt->execute([$collabRequestId, $senderId, $receiverId]);
            $roomId = $pdo->lastInsertId();
            
            if ($roomId) {
                // Add some messages
                $msgStmt = $pdo->prepare("INSERT INTO chat_messages (room_id, sender_user_id, message) VALUES (?, ?, ?)");
                $msgStmt->execute([$roomId, $senderId, "Thanks for accepting! When can we talk?"]);
                $msgStmt->execute([$roomId, $receiverId, "Sure, how about tomorrow at 10 AM?"]);
            }
        }
    }

    // 7. Recalculate everything
    echo "Recalculating all metrics...\n";
    recalculateAllTrending($pdo);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Database seeded successfully with 20 ideas, users, and interaction data.']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Seeding failed: ' . $e->getMessage()]);
}
