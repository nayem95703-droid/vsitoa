-- Verification System Database Schema

-- Verification requests table
CREATE TABLE IF NOT EXISTS verification_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    id_document_path VARCHAR(500) NOT NULL,
    company_name VARCHAR(200) NULL,
    website_url VARCHAR(255) NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- User features table for verified users
CREATE TABLE IF NOT EXISTS user_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    features JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id)
);

-- User login log for security monitoring
CREATE TABLE IF NOT EXISTS user_login_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    login_status ENUM('success', 'failed', 'suspicious') DEFAULT 'success',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_login_time (login_time),
    INDEX idx_status (login_status)
);

-- User trusted IPs for enhanced security
CREATE TABLE IF NOT EXISTS user_trusted_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_ip (user_id, ip_address),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address)
);

-- Two-factor authentication table
CREATE TABLE IF NOT EXISTS user_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    secret VARCHAR(64) NOT NULL,
    backup_codes JSON NULL,
    is_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id)
);

-- Enhanced support tickets for verified users
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    category ENUM('general', 'technical', 'billing', 'security', 'verification') DEFAULT 'general',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT NULL,
    response_count INT DEFAULT 0,
    last_response_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
);

-- Support ticket responses
CREATE TABLE IF NOT EXISTS support_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NULL, -- NULL for admin responses
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Custom profile links for verified users
CREATE TABLE IF NOT EXISTS user_profile_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    link_type ENUM('website', 'social', 'portfolio', 'business', 'other') DEFAULT 'website',
    title VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    description TEXT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_link_type (link_type),
    INDEX idx_display_order (display_order)
);

-- User stickers/badges system
CREATE TABLE IF NOT EXISTS user_stickers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sticker_type ENUM('verified', 'achievement', 'milestone', 'exclusive', 'custom') DEFAULT 'verified',
    sticker_name VARCHAR(100) NOT NULL,
    sticker_icon VARCHAR(50) NOT NULL,
    sticker_color VARCHAR(20) DEFAULT '#1e40af',
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_sticker_type (sticker_type),
    INDEX idx_is_active (is_active)
);

-- Search optimization data
CREATE TABLE IF NOT EXISTS user_search_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    search_keywords JSON NOT NULL,
    search_score DECIMAL(5,2) DEFAULT 0.00,
    profile_views INT DEFAULT 0,
    last_search_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_search_score (search_score),
    INDEX idx_profile_views (profile_views)
);

-- Add verification columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS locked_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS lock_reason TEXT NULL;

-- Add indexes for verification-related columns
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_is_verified (is_verified),
ADD INDEX IF NOT EXISTS idx_verified_at (verified_at),
ADD INDEX IF NOT EXISTS idx_is_locked (is_locked);

-- Create default verified stickers
INSERT IGNORE INTO user_stickers (user_id, sticker_type, sticker_name, sticker_icon, sticker_color, description) 
SELECT 
    id, 
    'verified', 
    'Verified User', 
    '✓', 
    '#1e40af', 
    'Official verified user with enhanced protection and features'
FROM users 
WHERE is_verified = TRUE;

-- Create default search data for all users
INSERT IGNORE INTO user_search_data (user_id, search_keywords, search_score)
SELECT 
    id,
    JSON_OBJECT(
        'username', username,
        'email', email,
        'first_name', COALESCE(first_name, ''),
        'last_name', COALESCE(last_name, ''),
        'company_name', COALESCE(company_name, ''),
        'user_type', user_type
    ),
    CASE 
        WHEN is_verified = TRUE THEN 95.0
        WHEN rating >= 4.5 THEN 85.0
        WHEN rating >= 3.5 THEN 70.0
        ELSE 50.0
    END
FROM users;
