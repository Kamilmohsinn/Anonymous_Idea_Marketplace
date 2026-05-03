-- Database schema for Anonymous Idea Marketplace
-- Create database and use it
CREATE DATABASE IF NOT EXISTS anonymous_idea_marketplace;
USE anonymous_idea_marketplace;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Insert sample user for testing
-- Password: password123 (hashed)
INSERT INTO users (email, password) VALUES
('test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password123

-- Ideas table for Idea Posting & Management module (full CRUD)
CREATE TABLE IF NOT EXISTS ideas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pseudonym VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    problem_statement TEXT NOT NULL,
    proposed_solution TEXT NOT NULL,
    tags VARCHAR(255) DEFAULT '',
    stage ENUM('Concept', 'Prototype', 'Beta', 'Startup') DEFAULT 'Concept',
    vote_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ideas_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Voting table (one vote per user per idea)
CREATE TABLE IF NOT EXISTS idea_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type TINYINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_votes_idea FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT uq_vote UNIQUE (idea_id, user_id),
    CONSTRAINT chk_vote_type CHECK (vote_type IN (-1, 1))
);

-- Comments table for threaded discussion foundation
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL,
    user_id INT NOT NULL,
    pseudonym VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_idea FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Collaboration request lifecycle table
CREATE TABLE IF NOT EXISTS collaboration_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    receiver_user_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending', 'Accepted', 'Declined') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_collab_idea FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    CONSTRAINT fk_collab_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_collab_receiver FOREIGN KEY (receiver_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Optional demo ideas (safe to run after users exist)
INSERT INTO ideas (user_id, pseudonym, title, category, problem_statement, proposed_solution, tags, stage, vote_score)
SELECT 1, 'Innovator_432', 'AI-Powered Recipe Generation from Fridge Photos', 'AI & Technology',
       'People waste food because they do not know what meals can be made from available ingredients.',
       'Use computer vision to identify ingredients from a fridge photo and generate recipes in seconds.',
       'ai,food,computer-vision', 'Prototype', 12
WHERE NOT EXISTS (SELECT 1 FROM ideas WHERE title = 'AI-Powered Recipe Generation from Fridge Photos');

-- =========================
-- Phase 1 extended modules
-- =========================

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin', 'moderator', 'investor') DEFAULT 'user',
    ADD COLUMN IF NOT EXISTS account_status ENUM('active', 'warned', 'suspended') DEFAULT 'active';

ALTER TABLE ideas
    ADD COLUMN IF NOT EXISTS status ENUM('Draft', 'Published') DEFAULT 'Published',
    ADD COLUMN IF NOT EXISTS trending_score DECIMAL(12,4) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS attachment_path VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS similarity_score DECIMAL(5,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS similar_idea_id INT NULL,
    ADD COLUMN IF NOT EXISTS comments_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS collaboration_count INT DEFAULT 0,
    ADD CONSTRAINT fk_similar_idea FOREIGN KEY (similar_idea_id) REFERENCES ideas(id) ON DELETE SET NULL;

ALTER TABLE comments
    ADD COLUMN IF NOT EXISTS parent_comment_id INT NULL,
    ADD COLUMN IF NOT EXISTS helpful_score INT DEFAULT 0,
    ADD CONSTRAINT fk_comment_parent FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS comment_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction ENUM('helpful', 'unhelpful') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_react_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_react_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT uq_comment_react UNIQUE (comment_id, user_id)
);

CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    reputation_points INT DEFAULT 0,
    badge VARCHAR(100) DEFAULT 'New Contributor',
    bio VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reputation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    reason VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message VARCHAR(255) NOT NULL,
    reference_type VARCHAR(50) DEFAULT '',
    reference_id INT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_user_id INT NOT NULL,
    target_type ENUM('idea', 'comment') NOT NULL,
    target_id INT NOT NULL,
    reason ENUM('spam', 'inappropriate', 'plagiarism', 'other') NOT NULL,
    details VARCHAR(255) DEFAULT '',
    status ENUM('Open', 'Reviewed', 'Actioned', 'Dismissed') DEFAULT 'Open',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reporter_user FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS chat_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collaboration_request_id INT NOT NULL UNIQUE,
    user_one_id INT NOT NULL,
    user_two_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_room_request FOREIGN KEY (collaboration_request_id) REFERENCES collaboration_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_room_user_one FOREIGN KEY (user_one_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_room_user_two FOREIGN KEY (user_two_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    message TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chat_room FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS anonymous_identity_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    pseudonym VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_identity_idea FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    CONSTRAINT fk_identity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    email_notifications_enabled TINYINT(1) DEFAULT 0,
    public_profile_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    CONSTRAINT fk_email_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (email, password, role)
SELECT 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com');

INSERT INTO categories (name)
SELECT 'AI & Technology' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'AI & Technology');
INSERT INTO categories (name)
SELECT 'Healthcare' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Healthcare');
INSERT INTO categories (name)
SELECT 'FinTech' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'FinTech');
INSERT INTO categories (name)
SELECT 'Education' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Education');

INSERT INTO user_profiles (user_id)
SELECT id FROM users
WHERE id NOT IN (SELECT user_id FROM user_profiles);

INSERT INTO user_settings (user_id)
SELECT id FROM users
WHERE id NOT IN (SELECT user_id FROM user_settings);

INSERT INTO anonymous_identity_map (idea_id, user_id, pseudonym)
SELECT i.id, i.user_id, i.pseudonym
FROM ideas i
LEFT JOIN anonymous_identity_map m ON m.idea_id = i.id
WHERE m.id IS NULL;

-- Crowdfunding Module Tables
CREATE TABLE IF NOT EXISTS crowdfunding_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    goal_amount DECIMAL(15, 2) NOT NULL,
    current_amount DECIMAL(15, 2) DEFAULT 0,
    end_date DATE NOT NULL,
    status ENUM('Pending', 'Active', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_idea FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE SET NULL,
    CONSTRAINT fk_campaign_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS campaign_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    min_amount DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reward_campaign FOREIGN KEY (campaign_id) REFERENCES crowdfunding_campaigns(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS campaign_donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    reward_id INT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_donation_campaign FOREIGN KEY (campaign_id) REFERENCES crowdfunding_campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_donation_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_donation_reward FOREIGN KEY (reward_id) REFERENCES campaign_rewards(id) ON DELETE SET NULL
);