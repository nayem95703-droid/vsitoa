-- VSITOA Database Schema

CREATE DATABASE IF NOT EXISTS vsitoa_db;
USE vsitoa_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE DEFAULT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    user_type ENUM('worker', 'advertiser', 'admin') DEFAULT 'worker',
    company_name VARCHAR(200),
    website_url VARCHAR(200),
    profile_image VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 0,
    total_earnings DECIMAL(10,2) DEFAULT 0,
    total_tasks_completed INT DEFAULT 0,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    wallet_address VARCHAR(255) DEFAULT NULL,
    referral_code VARCHAR(20) DEFAULT NULL,
    referred_by INT DEFAULT NULL,
    status ENUM('active', 'unverified', 'suspended') DEFAULT 'active',
    email_verified TINYINT(1) DEFAULT 0,
    earning_balance DECIMAL(18,8) DEFAULT 0,
    advisor_balance DECIMAL(18,8) DEFAULT 0,
    total_withdrawn DECIMAL(18,8) DEFAULT 0,
    total_earned DECIMAL(18,8) DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_type (user_type),
    INDEX idx_email (email),
    INDEX idx_referral_code (referral_code)
);

-- Measurement Ads / Tasks Table
CREATE TABLE IF NOT EXISTS tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    advertiser_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description LONGTEXT NOT NULL,
    ad_type ENUM('website_visit', 'measurement_ads', 'url_submission') DEFAULT 'measurement_ads',
    category VARCHAR(100),
    verification_method VARCHAR(100) DEFAULT 'manual_verification',
    proof_type VARCHAR(100) DEFAULT 'website_url',
    target_website_url VARCHAR(500) NOT NULL,
    payment_per_execution DECIMAL(10,2) NOT NULL,
    total_budget DECIMAL(10,2),
    max_executions INT,
    current_executions INT DEFAULT 0,
    execution_type ENUM('one_user_once', 'unlimited') DEFAULT 'one_user_once',
    max_completion_time INT DEFAULT 30,
    ip_restriction ENUM('any', 'selected') DEFAULT 'any',
    cookie_clearing BOOLEAN DEFAULT FALSE,
    min_rating INT DEFAULT 0,
    allowed_countries VARCHAR(500) DEFAULT 'all',
    target_gender ENUM('all', 'male', 'female') DEFAULT 'all',
    target_age_min INT DEFAULT 0,
    target_age_max INT DEFAULT 120,
    status ENUM('draft', 'active', 'paused', 'completed', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (advertiser_id) REFERENCES users(id),
    INDEX idx_advertiser_id (advertiser_id),
    INDEX idx_status (status),
    INDEX idx_ad_type (ad_type)
);

-- Task Submissions Table
CREATE TABLE IF NOT EXISTS submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    worker_id INT NOT NULL,
    submitted_url VARCHAR(500),
    submission_text LONGTEXT,
    screenshot_path VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason VARCHAR(500),
    verified_by INT,
    verified_at TIMESTAMP NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (worker_id) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_task_id (task_id),
    INDEX idx_worker_id (worker_id),
    INDEX idx_status (status)
);

-- Payment / Reward Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    worker_id INT NOT NULL,
    advertiser_id INT NOT NULL,
    task_id INT NOT NULL,
    submission_id INT,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USDT',
    transaction_type ENUM('reward', 'refund', 'penalty') DEFAULT 'reward',
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (worker_id) REFERENCES users(id),
    FOREIGN KEY (advertiser_id) REFERENCES users(id),
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (submission_id) REFERENCES submissions(id),
    INDEX idx_worker_id (worker_id),
    INDEX idx_status (status)
);

-- Task Analytics Table
CREATE TABLE IF NOT EXISTS task_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    views INT DEFAULT 0,
    clicks INT DEFAULT 0,
    submissions INT DEFAULT 0,
    approved_submissions INT DEFAULT 0,
    rejected_submissions INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    INDEX idx_task_id (task_id)
);

-- Create Sample Admin User
INSERT INTO users (username, email, password, first_name, user_type, is_verified, is_active)
VALUES ('admin', 'admin@vsitoa.com', '$2y$10$YIjlrBBdsd9HA7xQG8nwWOTGfPmjd1GsOUjGH9ZNqPtQ5VhVIg5l2', 'Administrator', 'admin', TRUE, TRUE);

-- Create Sample Advertiser
INSERT INTO users (username, email, password, first_name, company_name, website_url, user_type, is_verified, is_active)
VALUES ('advertiser1', 'advertiser@vsitoa.com', '$2y$10$YIjlrBBdsd9HA7xQG8nwWOTGfPmjd1GsOUjGH9ZNqPtQ5VhVIg5l2', 'John', 'Tech Company', 'https://example.com', 'advertiser', TRUE, TRUE);

-- Create Sample Worker
INSERT INTO users (username, email, password, first_name, user_type, rating, is_verified, is_active)
VALUES ('worker1', 'worker@vsitoa.com', '$2y$10$YIjlrBBdsd9HA7xQG8nwWOTGfPmjd1GsOUjGH9ZNqPtQ5VhVIg5l2', 'Alice', 'worker', 4.5, TRUE, TRUE);

-- Ads Table
CREATE TABLE IF NOT EXISTS ads (
    ad_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    ad_title VARCHAR(255) NOT NULL,
    ad_category VARCHAR(50) DEFAULT 'website',
    ad_type VARCHAR(20) NOT NULL,
    target_url VARCHAR(500) NOT NULL,
    description TEXT,
    preview_image VARCHAR(255),
    view_time INT DEFAULT 30,
    auto_redirect TINYINT(1) DEFAULT 0,
    timer_type VARCHAR(20) DEFAULT 'countdown',
    cost_per_view DECIMAL(18,8) NOT NULL,
    total_views INT NOT NULL,
    remaining_views INT NOT NULL,
    spent_amount DECIMAL(18,8) DEFAULT 0,
    total_budget DECIMAL(18,8) NOT NULL,
    platform_fee_percent DECIMAL(5,2) DEFAULT 20,
    target_countries TEXT,
    device_type VARCHAR(20) DEFAULT 'all',
    browser VARCHAR(20) DEFAULT 'all',
    user_level VARCHAR(20) DEFAULT 'all',
    status ENUM('pending','active','paused','completed','archived') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_ad_type (ad_type)
);

-- Ad Views Table
CREATE TABLE IF NOT EXISTS ad_views (
    view_id INT PRIMARY KEY AUTO_INCREMENT,
    ad_id INT NOT NULL,
    viewer_user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    view_time INT NOT NULL,
    actual_view_time INT DEFAULT 0,
    is_valid TINYINT(1) DEFAULT 0,
    earned_amount DECIMAL(18,8) DEFAULT 0,
    fraud_score INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ad_id) REFERENCES ads(ad_id),
    FOREIGN KEY (viewer_user_id) REFERENCES users(id),
    INDEX idx_ad_id (ad_id),
    INDEX idx_viewer (viewer_user_id),
    INDEX idx_valid (is_valid)
);

-- Deposits Table
CREATE TABLE IF NOT EXISTS deposits (
    deposit_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    currency VARCHAR(10) NOT NULL,
    amount DECIMAL(18,8) NOT NULL,
    wallet_address VARCHAR(255),
    txid VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Withdrawals Table
CREATE TABLE IF NOT EXISTS withdrawals (
    withdrawal_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    currency VARCHAR(10) NOT NULL,
    amount DECIMAL(18,8) NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    status ENUM('pending','processing','paid','rejected') DEFAULT 'pending',
    txid VARCHAR(255),
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Wallet Transactions Table
CREATE TABLE IF NOT EXISTS wallet_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(18,8) NOT NULL,
    balance_before DECIMAL(18,8),
    balance_after DECIMAL(18,8),
    description TEXT,
    reference_id INT,
    reference_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_type (type)
);

-- Earnings Table
CREATE TABLE IF NOT EXISTS earnings (
    earning_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    source VARCHAR(50),
    source_id INT,
    amount DECIMAL(18,8) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id)
);

-- Referrals Table
CREATE TABLE IF NOT EXISTS referrals (
    referral_id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 10,
    level INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id),
    FOREIGN KEY (referred_user_id) REFERENCES users(id),
    INDEX idx_referrer (referrer_id)
);

-- Referral Earnings Table
CREATE TABLE IF NOT EXISTS referral_earnings (
    earning_id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL,
    source_user_id INT NOT NULL,
    amount DECIMAL(18,8) NOT NULL,
    commission_rate DECIMAL(5,2),
    source_type VARCHAR(50),
    source_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id),
    FOREIGN KEY (source_user_id) REFERENCES users(id),
    INDEX idx_referrer (referrer_id)
);

-- Referral Clicks Table
CREATE TABLE IF NOT EXISTS referral_clicks (
    click_id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL,
    referral_code VARCHAR(20),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    landing_page VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id),
    INDEX idx_referrer (referrer_id),
    INDEX idx_code (referral_code)
);

-- User Login Log
CREATE TABLE IF NOT EXISTS user_login_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    success TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
);

-- Support Tickets
CREATE TABLE IF NOT EXISTS support_tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Support Responses
CREATE TABLE IF NOT EXISTS support_responses (
    response_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_ticket (ticket_id)
);

-- User Stickers
CREATE TABLE IF NOT EXISTS user_stickers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    sticker_type VARCHAR(100) NOT NULL,
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id)
);

-- User Features
CREATE TABLE IF NOT EXISTS user_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id)
);

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'superadmin',
    permissions JSON DEFAULT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_status (status)
);

-- Verification Requests
CREATE TABLE IF NOT EXISTS verification_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type VARCHAR(50),
    document_path VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
