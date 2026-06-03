<?php

namespace Core;

use Core\Database;
use Core\Logger;
use Core\Mailer;

class Security
{
    private static $db;
    
    public static function init()
    {
        self::$db = Database::getInstance();
    }
    
    /**
     * Enhanced security for verified users
     */
    public static function enforceVerifiedSecurity(int $userId): void
    {
        if (!self::$db) {
            self::init();
        }
        
        // Check if user is verified
        $stmt = self::$db->prepare("SELECT is_verified FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['is_verified']) {
            return;
        }
        
        // Get user security features
        $features = self::getUserSecurityFeatures($userId);
        
        // Enforce two-factor authentication if enabled
        if ($features['two_factor_auth'] ?? false) {
            self::enforceTwoFactorAuth($userId);
        }
        
        // Set session timeout
        if ($features['session_timeout'] ?? false) {
            self::setSessionTimeout($features['session_timeout']);
        }
        
        // Enable login alerts
        if ($features['login_alerts'] ?? false) {
            self::monitorLoginActivity($userId);
        }
        
        // Enhanced IP monitoring
        self::monitorIPChanges($userId);
    }
    
    /**
     * Get user security features
     */
    private static function getUserSecurityFeatures(int $userId): array
    {
        $stmt = self::$db->prepare("
            SELECT features FROM user_features WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result ? json_decode($result['features'], true) : [];
    }
    
    /**
     * Enforce two-factor authentication
     */
    private static function enforceTwoFactorAuth(int $userId): void
    {
        if (!isset($_SESSION['2fa_verified']) || !$_SESSION['2fa_verified']) {
            // Redirect to 2FA verification
            if (!str_contains($_SERVER['REQUEST_URI'], '/2fa')) {
                header('Location: /2fa-verify');
                exit;
            }
        }
    }
    
    /**
     * Set session timeout for verified users
     */
    private static function setSessionTimeout(int $timeout): void
    {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive >= $timeout) {
                session_destroy();
                header('Location: /login?timeout=1');
                exit;
            }
        }
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Monitor login activity and send alerts
     */
    private static function monitorLoginActivity(int $userId): void
    {
        $currentIP = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        // Get last login info
        $stmt = self::$db->prepare("
            SELECT ip_address, user_agent, login_time 
            FROM user_login_log 
            WHERE user_id = ? 
            ORDER BY login_time DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $lastLogin = $stmt->fetch();
        
        // Check for suspicious activity
        $suspicious = false;
        $reasons = [];
        
        if ($lastLogin) {
            if ($lastLogin['ip_address'] !== $currentIP) {
                $suspicious = true;
                $reasons[] = "IP address changed from {$lastLogin['ip_address']} to $currentIP";
            }
            
            if (self::getUserAgentChanged($lastLogin['user_agent'], $userAgent)) {
                $suspicious = true;
                $reasons[] = "Browser/device changed";
            }
        }
        
        // Log current login
        self::logUserLogin($userId, $currentIP, $userAgent);
        
        // Send alert if suspicious
        if ($suspicious) {
            self::sendSecurityAlert($userId, $reasons);
        }
    }
    
    /**
     * Monitor IP changes for verified users
     */
    private static function monitorIPChanges(int $userId): void
    {
        $currentIP = $_SERVER['REMOTE_ADDR'];
        
        // Get user's trusted IPs
        $stmt = self::$db->prepare("
            SELECT ip_address FROM user_trusted_ips 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $trustedIPs = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // If current IP is not trusted and user has trusted IPs
        if (!empty($trustedIPs) && !in_array($currentIP, $trustedIPs)) {
            // Log suspicious IP access
            Logger::warning('Untrusted IP access for verified user', [
                'user_id' => $userId,
                'ip' => $currentIP,
                'trusted_ips' => $trustedIPs
            ]);
            
            // Send security alert
            self::sendSecurityAlert($userId, ["Access from untrusted IP: $currentIP"]);
        }
    }
    
    /**
     * Check if user agent has significantly changed
     */
    private static function getUserAgentChanged(string $oldAgent, string $newAgent): bool
    {
        $oldBrowser = self::extractBrowser($oldAgent);
        $newBrowser = self::extractBrowser($newAgent);
        
        return $oldBrowser !== $newBrowser;
    }
    
    /**
     * Extract browser name from user agent
     */
    private static function extractBrowser(string $userAgent): string
    {
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        return 'Unknown';
    }
    
    /**
     * Log user login
     */
    private static function logUserLogin(int $userId, string $ip, string $userAgent): void
    {
        $stmt = self::$db->prepare("
            INSERT INTO user_login_log (user_id, ip_address, user_agent, login_time)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $ip, $userAgent]);
    }
    
    /**
     * Send security alert to user
     */
    private static function sendSecurityAlert(int $userId, array $reasons): void
    {
        // Get user email
        $stmt = self::$db->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) return;
        
        $subject = '🚨 Security Alert - VSITOA';
        $reasonsList = implode('<br>- ', $reasons);
        
        $message = "
        <h2>🚨 Security Alert</h2>
        <p>Dear {$user['username']},</p>
        <p>We detected suspicious activity on your verified account:</p>
        <div style='background: #fef2f2; padding: 15px; border-radius: 8px; margin: 15px 0;'>
            <strong>Security Concerns:</strong><br>
            - $reasonsList
        </div>
        
        <h3>Login Details:</h3>
        <ul>
            <li><strong>IP Address:</strong> {$_SERVER['REMOTE_ADDR']}</li>
            <li><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</li>
            <li><strong>Device:</strong> " . self::extractBrowser($_SERVER['HTTP_USER_AGENT']) . "</li>
        </ul>
        
        <h3>Recommended Actions:</h3>
        <ol>
            <li>Review your account activity immediately</li>
            <li>Change your password if you don't recognize this activity</li>
            <li>Enable two-factor authentication</li>
            <li>Contact support if you suspect unauthorized access</li>
        </ol>
        
        <p>If this was you, you can safely ignore this alert.</p>
        
        <p>Stay safe,<br>VSITOA Security Team</p>
        ";
        
        try {
            Mailer::send($user['email'], $subject, $message);
            
            // Log alert sent
            Logger::info('Security alert sent', [
                'user_id' => $userId,
                'reasons' => $reasons
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to send security alert', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generate 2FA secret for user
     */
    public static function generate2FASecret(int $userId): string
    {
        $secret = bin2hex(random_bytes(16));
        
        $stmt = self::$db->prepare("
            INSERT INTO user_2fa (user_id, secret, created_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE secret = VALUES(secret), created_at = NOW()
        ");
        $stmt->execute([$userId, $secret]);
        
        return $secret;
    }
    
    /**
     * Verify 2FA code
     */
    public static function verify2FACode(int $userId, string $code): bool
    {
        $stmt = self::$db->prepare("
            SELECT secret FROM user_2fa WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // In a real implementation, you would use a library like OTPHP
        // For demo purposes, we'll use a simple verification
        return hash('sha256', $result['secret'] . date('Y-m-d-H')) === $code;
    }
    
    /**
     * Add trusted IP for user
     */
    public static function addTrustedIP(int $userId, string $ip): void
    {
        $stmt = self::$db->prepare("
            INSERT IGNORE INTO user_trusted_ips (user_id, ip_address, added_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$userId, $ip]);
    }
    
    /**
     * Remove trusted IP for user
     */
    public static function removeTrustedIP(int $userId, string $ip): void
    {
        $stmt = self::$db->prepare("
            DELETE FROM user_trusted_ips 
            WHERE user_id = ? AND ip_address = ?
        ");
        $stmt->execute([$userId, $ip]);
    }
    
    /**
     * Get user's trusted IPs
     */
    public static function getTrustedIPs(int $userId): array
    {
        $stmt = self::$db->prepare("
            SELECT ip_address, added_at 
            FROM user_trusted_ips 
            WHERE user_id = ?
            ORDER BY added_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if account should be locked due to suspicious activity
     */
    public static function checkAccountLock(int $userId): bool
    {
        $stmt = self::$db->prepare("
            SELECT COUNT(*) as failed_attempts
            FROM user_login_log 
            WHERE user_id = ? 
            AND login_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND login_status = 'failed'
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        // Lock account after 5 failed attempts in 1 hour
        if ($result['failed_attempts'] >= 5) {
            self::lockAccount($userId);
            return true;
        }
        
        return false;
    }
    
    /**
     * Lock user account
     */
    private static function lockAccount(int $userId): void
    {
        $stmt = self::$db->prepare("
            UPDATE users 
            SET is_locked = 1, locked_at = NOW(), lock_reason = 'Multiple failed login attempts'
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        
        Logger::warning('Account locked due to suspicious activity', ['user_id' => $userId]);
    }
    
    /**
     * Unlock user account
     */
    public static function unlockAccount(int $userId): void
    {
        $stmt = self::$db->prepare("
            UPDATE users 
            SET is_locked = 0, locked_at = NULL, lock_reason = NULL
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        
        Logger::info('Account unlocked', ['user_id' => $userId]);
    }
}
