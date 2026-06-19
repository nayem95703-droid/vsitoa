-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'superadmin',
    permissions JSON DEFAULT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_status (status)
);

-- Insert default admin account (password: admin123)
-- The password hash is for 'admin123' using bcrypt ($2y$10$...)
INSERT IGNORE INTO admins (username, email, password, role, status) 
VALUES ('admin', 'admin@vsitoa.com', '$2y$10$YIQfkVDr2F0xIGmxvZ0CeOYf0EgLGZfZkQx8PZlKc8JZT4WzCpT2K', 'superadmin', 'active');
