<?php

require_once __DIR__ . '/db.php';

function ensureUserProfile(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('INSERT IGNORE INTO user_profiles (user_id) VALUES (?)');
    $stmt->execute([$userId]);
}

function addReputation(PDO $pdo, int $userId, int $points, string $reason): void {
    ensureUserProfile($pdo, $userId);

    $logStmt = $pdo->prepare('INSERT INTO reputation_logs (user_id, points, reason) VALUES (?, ?, ?)');
    $logStmt->execute([$userId, $points, $reason]);

    $updateStmt = $pdo->prepare('UPDATE user_profiles SET reputation_points = reputation_points + ? WHERE user_id = ?');
    $updateStmt->execute([$points, $userId]);

    $badgeStmt = $pdo->prepare('UPDATE user_profiles SET badge = CASE WHEN reputation_points >= 300 THEN "Visionary" WHEN reputation_points >= 150 THEN "Top Builder" WHEN reputation_points >= 50 THEN "Active Innovator" ELSE "New Contributor" END WHERE user_id = ?');
    $badgeStmt->execute([$userId]);
}

function createNotification(PDO $pdo, int $userId, string $title, string $message, string $referenceType = '', ?int $referenceId = null): void {
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, reference_type, reference_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $referenceType, $referenceId]);

    $settingStmt = $pdo->prepare('SELECT email_notifications_enabled FROM user_settings WHERE user_id = ? LIMIT 1');
    $settingStmt->execute([$userId]);
    $settings = $settingStmt->fetch();

    if ($settings && (int) $settings['email_notifications_enabled'] === 1) {
        $emailStmt = $pdo->prepare('INSERT INTO email_queue (user_id, subject, body) VALUES (?, ?, ?)');
        $emailStmt->execute([$userId, $title, $message]);
    }
}

function tokenize(string $text): array {
    $lower = strtolower($text);
    $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $lower);
    $parts = preg_split('/\s+/', trim($normalized));
    $filtered = array_filter($parts, static function ($word) {
        return strlen($word) >= 3;
    });

    return array_values(array_unique($filtered));
}

function similarityPercent(string $a, string $b): float {
    $aTokens = tokenize($a);
    $bTokens = tokenize($b);

    if (count($aTokens) === 0 || count($bTokens) === 0) {
        return 0;
    }

    $intersection = array_intersect($aTokens, $bTokens);
    $union = array_unique(array_merge($aTokens, $bTokens));

    if (count($union) === 0) {
        return 0;
    }

    return round((count($intersection) / count($union)) * 100, 2);
}

function findClosestIdea(PDO $pdo, string $title, string $problem, string $solution, ?int $excludeIdeaId = null): array {
    $sql = 'SELECT id, title, problem_statement, proposed_solution FROM ideas WHERE status = "Published"';
    $params = [];

    if ($excludeIdeaId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excludeIdeaId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ideas = $stmt->fetchAll();

    $base = $title . ' ' . $problem . ' ' . $solution;
    $bestScore = 0;
    $bestId = null;

    foreach ($ideas as $idea) {
        $candidate = $idea['title'] . ' ' . $idea['problem_statement'] . ' ' . $idea['proposed_solution'];
        $score = similarityPercent($base, $candidate);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestId = (int) $idea['id'];
        }
    }

    return ['score' => $bestScore, 'idea_id' => $bestId];
}

function recalculateIdeaMetrics(PDO $pdo, int $ideaId): void {
    $voteStmt = $pdo->prepare('SELECT COALESCE(SUM(vote_type), 0) AS vote_score FROM idea_votes WHERE idea_id = ?');
    $voteStmt->execute([$ideaId]);
    $voteScore = (int) $voteStmt->fetch()['vote_score'];

    $commentStmt = $pdo->prepare('SELECT COUNT(*) AS total_comments FROM comments WHERE idea_id = ?');
    $commentStmt->execute([$ideaId]);
    $comments = (int) $commentStmt->fetch()['total_comments'];

    $collabStmt = $pdo->prepare('SELECT COUNT(*) AS total_requests FROM collaboration_requests WHERE idea_id = ?');
    $collabStmt->execute([$ideaId]);
    $collabCount = (int) $collabStmt->fetch()['total_requests'];

    $ageStmt = $pdo->prepare('SELECT TIMESTAMPDIFF(HOUR, created_at, NOW()) AS age_hours FROM ideas WHERE id = ?');
    $ageStmt->execute([$ideaId]);
    $ageHours = (int) $ageStmt->fetch()['age_hours'];

    $decay = max(1, $ageHours / 24);
    $trendingScore = (($voteScore * 2) + ($comments * 1.2) + ($collabCount * 2.5)) / $decay;

    $updateStmt = $pdo->prepare('UPDATE ideas SET vote_score = ?, comments_count = ?, collaboration_count = ?, trending_score = ? WHERE id = ?');
    $updateStmt->execute([$voteScore, $comments, $collabCount, round($trendingScore, 4), $ideaId]);
}

function recalculateAllTrending(PDO $pdo): void {
    $stmt = $pdo->query('SELECT id FROM ideas WHERE status = "Published"');
    $ideas = $stmt->fetchAll();

    foreach ($ideas as $idea) {
        recalculateIdeaMetrics($pdo, (int) $idea['id']);
    }
}
