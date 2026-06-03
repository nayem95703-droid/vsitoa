-- VSITOA Database Schema

CREATE DATABASE IF NOT EXISTS vsitoa_db;
USE vsitoa_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
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
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_type (user_type),
    INDEX idx_email (email)
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
