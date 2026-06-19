<?php

namespace Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    private static ?array $user = null;
    private static ?array $admin = null;

    private static function requestExpectsJson(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

        if ($path !== '' && str_starts_with($path, '/api/')) {
            return true;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if ($accept !== '' && str_contains(strtolower($accept), 'application/json')) {
            return true;
        }

        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if ($requestedWith !== '' && strtolower($requestedWith) === 'xmlhttprequest') {
            return true;
        }

        return false;
    }

    /**
     * Initialize authentication
     */
    public static function initialize(): void
    {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                self::validateToken($matches[1]);
                return;
            }
        }

        if (isset($_SESSION['admin_jwt_token'])) {
            self::validateToken((string) $_SESSION['admin_jwt_token']);
        }
        if (isset($_SESSION['jwt_token'])) {
            self::validateToken((string) $_SESSION['jwt_token']);
        }
        if (isset($_COOKIE['admin_jwt_token'])) {
            self::validateToken((string) $_COOKIE['admin_jwt_token']);
        }
        if (isset($_COOKIE['jwt_token'])) {
            self::validateToken((string) $_COOKIE['jwt_token']);
        }
    }

    /**
     * Get token from request
     */
    private static function getTokenFromRequest(): ?string
    {
        // Check Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        // Check session
        if (isset($_SESSION['jwt_token'])) {
            return $_SESSION['jwt_token'];
        }

        if (isset($_SESSION['admin_jwt_token'])) {
            return $_SESSION['admin_jwt_token'];
        }

        // Check cookie
        if (isset($_COOKIE['jwt_token'])) {
            return $_COOKIE['jwt_token'];
        }

        if (isset($_COOKIE['admin_jwt_token'])) {
            return $_COOKIE['admin_jwt_token'];
        }

        return null;
    }

    /**
     * Validate JWT token
     */
    private static function validateToken(string $token): bool
    {
        try {
            $payload = JWT::decode($token, new Key(Config::get('security.jwt_secret'), 'HS256'));
            $payload = json_decode(json_encode($payload), true);

            if ($payload['type'] === 'user') {
                self::$user = $payload;
            } elseif ($payload['type'] === 'admin') {
                self::$admin = $payload;
            }

            return true;
        } catch (\Exception $e) {
            Logger::error("JWT validation failed: " . $e->getMessage());
            if (isset($_SESSION['jwt_token']) && $_SESSION['jwt_token'] === $token) {
                unset($_SESSION['jwt_token']);
            }
            if (isset($_SESSION['admin_jwt_token']) && $_SESSION['admin_jwt_token'] === $token) {
                unset($_SESSION['admin_jwt_token']);
                unset($_SESSION['admin_logged_in']);
                unset($_SESSION['admin_id']);
                unset($_SESSION['admin_username']);
                self::$admin = null;
            }
            if (isset($_COOKIE['jwt_token']) && $_COOKIE['jwt_token'] === $token) {
                setcookie('jwt_token', '', time() - 3600, '/', '', false, true);
            }
            if (isset($_COOKIE['admin_jwt_token']) && $_COOKIE['admin_jwt_token'] === $token) {
                setcookie('admin_jwt_token', '', time() - 3600, '/', '', false, true);
            }
            return false;
        }
    }

    /**
     * Login user
     */
    public static function login(array $credentials, bool $remember = false): array
    {
        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';

        // Validate input
        if (empty($email) || empty($password)) {
            throw new \Exception('Email and password are required');
        }

        // Get user from database
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND status IN ('active', 'unverified')",
            [$email]
        );

        if (!$user) {
            throw new \Exception('Invalid credentials');
        }

        // Check password
        if (!password_verify($password, $user['password'])) {
            // Log failed login attempt
            self::logLoginAttempt($user['user_id'], false);
            throw new \Exception('Invalid credentials');
        }

        // Check if account is locked due to too many attempts
        if (self::isAccountLocked($user['user_id'])) {
            throw new \Exception('Account temporarily locked due to too many failed attempts');
        }

        // Generate JWT token
        $token = self::generateUserToken($user);

        // Store token
        if ($remember) {
            setcookie('jwt_token', $token, time() + (86400 * 30), '/', '', false, true);
        } else {
            $_SESSION['jwt_token'] = $token;
        }

        // Update last login
        Database::update(
            'users',
            ['last_login_at' => date('Y-m-d H:i:s')],
            'user_id = ?',
            [$user['user_id']]
        );

        // Log successful login
        self::logLoginAttempt($user['user_id'], true);

        // Clear failed login attempts
        self::clearFailedAttempts($user['user_id']);

        self::$user = $user;

        return [
            'user' => self::sanitizeUser($user),
            'token' => $token
        ];
    }

    /**
     * Login admin
     */
    public static function adminLogin(array $credentials): array
    {
        $password = $credentials['password'] ?? '';

        if (empty($password)) {
            throw new \Exception('Password is required');
        }

        // Always use 'admin' as username for password-only login
        $username = 'admin';
        $admin = Database::fetch(
            "SELECT * FROM admins WHERE username = ? AND status = 'active'",
            [$username]
        );

        if (!$admin) {
            throw new \Exception('Invalid credentials');
        }

        if (!password_verify($password, $admin['password'])) {
            throw new \Exception('Invalid credentials');
        }

        $token = self::generateAdminToken($admin);
        $_SESSION['admin_jwt_token'] = $token;
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];

        // Update last login
        Database::update(
            'admins',
            ['last_login_at' => date('Y-m-d H:i:s')],
            'admin_id = ?',
            [$admin['admin_id']]
        );

        self::$admin = $admin;

        return [
            'admin' => self::sanitizeAdmin($admin),
            'token' => $token
        ];
    }

    /**
     * Register new user
     */
    public static function register(array $data): array
    {
        // Validate required fields
        $required = ['username', 'email', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '$field' is required");
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        // Validate password match
        if ($data['password'] !== $data['confirm_password']) {
            throw new \Exception('Passwords do not match');
        }

        // Validate password strength
        if (strlen($data['password']) < Config::get('security.password_min_length')) {
            throw new \Exception('Password must be at least ' . Config::get('security.password_min_length') . ' characters');
        }

        // Check if username exists
        if (Database::fetch("SELECT user_id FROM users WHERE username = ?", [$data['username']])) {
            throw new \Exception('Username already exists');
        }

        // Check if email exists
        if (Database::fetch("SELECT user_id FROM users WHERE email = ?", [$data['email']])) {
            throw new \Exception('Email already exists');
        }

        // Generate referral code
        $referralCode = self::generateReferralCode();

        // Handle referral
        $referredBy = null;
        if (!empty($data['referral_code'])) {
            $referrer = Database::fetch(
                "SELECT user_id FROM users WHERE referral_code = ?",
                [$data['referral_code']]
            );
            if ($referrer) {
                $referredBy = $referrer['user_id'];
            }
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $requireVerification = (bool) Config::get('security.require_email_verification');

        // Insert user
        $userId = Database::insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'wallet_address' => $data['wallet_address'] ?? null,
            'referral_code' => $referralCode,
            'referred_by' => $referredBy,
            'status' => $requireVerification ? 'unverified' : 'active',
            'email_verified' => $requireVerification ? 0 : 1
        ]);

        // Get created user
        $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);

        if (!$user) {
            throw new \Exception('Failed to load the newly created account.');
        }

        // Create referral record if applicable
        if ($referredBy) {
            if (Database::tableExists('referrals')) {
                Database::insert('referrals', [
                    'referrer_id' => $referredBy,
                    'referred_user_id' => $userId,
                    'commission_rate' => Config::get('rates.referral_commission')
                ]);
            } else {
                Logger::warning('Referral table missing; skipping referral record', [
                    'referrer_id' => $referredBy,
                    'referred_user_id' => $userId
                ]);
            }
        }

        // Send verification email if required. A mail/SMTP failure must not
        // abort an otherwise successful registration.
        if ($requireVerification) {
            try {
                self::sendVerificationEmail($user);
            } catch (\Throwable $e) {
                Logger::error('Verification email could not be sent', [
                    'user_id' => $user['user_id'] ?? null,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // Auto login if no verification required
            $token = self::generateUserToken($user);
            $_SESSION['jwt_token'] = $token;
            self::$user = $user;
        }

        return self::sanitizeUser($user);
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        self::clearToken();
        self::$user = null;
    }

    /**
     * Logout admin
     */
    public static function adminLogout(): void
    {
        unset($_SESSION['admin_jwt_token']);
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        self::$admin = null;
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool
    {
        return self::$user !== null;
    }

    /**
     * Check if admin is logged in
     */
    public static function adminCheck(): bool
    {
        return self::$admin !== null;
    }

    /**
     * Get current user
     */
    public static function user(): ?array
    {
        return self::$user;
    }

    /**
     * Get current admin
     */
    public static function admin(): ?array
    {
        return self::$admin;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        if (!is_array(self::$user)) {
            return null;
        }

        return self::$user['user_id'] ?? null;
    }

    /**
     * Get current admin ID
     */
    public static function adminId(): ?int
    {
        if (!is_array(self::$admin)) {
            return null;
        }

        return self::$admin['admin_id'] ?? null;
    }

    /**
     * Generate user JWT token
     */
    private static function generateUserToken(array $user): string
    {
        $payload = [
            'type' => 'user',
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + Config::get('security.session_lifetime')
        ];

        return JWT::encode($payload, Config::get('security.jwt_secret'), 'HS256');
    }

    /**
     * Generate admin JWT token
     */
    private static function generateAdminToken(array $admin): string
    {
        $payload = [
            'type' => 'admin',
            'admin_id' => $admin['admin_id'],
            'username' => $admin['username'],
            'role' => $admin['role'],
            'permissions' => json_decode($admin['permissions'] ?? '[]', true),
            'iat' => time(),
            'exp' => time() + Config::get('security.session_lifetime')
        ];

        return JWT::encode($payload, Config::get('security.jwt_secret'), 'HS256');
    }

    /**
     * Clear token
     */
    private static function clearToken(): void
    {
        unset($_SESSION['jwt_token']);
        unset($_SESSION['admin_jwt_token']);
        setcookie('jwt_token', '', time() - 3600, '/', '', false, true);
        setcookie('admin_jwt_token', '', time() - 3600, '/', '', false, true);
    }

    /**
     * Generate unique referral code
     */
    private static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (Database::fetch("SELECT user_id FROM users WHERE referral_code = ?", [$code]));

        return $code;
    }

    /**
     * Send verification email
     */
    private static function sendVerificationEmail(array $user): void
    {
        $token = bin2hex(random_bytes(32));
 
        if (!Database::tableExists('email_verifications')) {
            Database::exec(
                "CREATE TABLE IF NOT EXISTS email_verifications (" .
                "verification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY," .
                "user_id BIGINT UNSIGNED NOT NULL," .
                "token VARCHAR(64) NOT NULL," .
                "expires_at TIMESTAMP NOT NULL," .
                "used BOOLEAN DEFAULT FALSE," .
                "used_at TIMESTAMP NULL," .
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP," .
                "INDEX idx_user_id (user_id)," .
                "INDEX idx_token (token)," .
                "INDEX idx_expires_at (expires_at)," .
                "UNIQUE KEY unique_token (token)," .
                "FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE" .
                ") ENGINE=InnoDB"
            );
        }

        Database::insert('email_verifications', [
            'user_id' => $user['user_id'],
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + 3600) // 1 hour
        ]);

        $verificationUrl = Config::get('app.url') . Config::get('app.base_path') . "/verify-email?token=$token";
        
        $subject = 'Verify your email address';
        $message = "Hello {$user['username']},\n\n";
        $message .= "Please click the following link to verify your email address:\n";
        $message .= "$verificationUrl\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you did not create an account, please ignore this email.";

        Mailer::send($user['email'], $subject, $message);
    }

    /**
     * Log login attempt
     */
    private static function logLoginAttempt(int $userId, bool $success): void
    {
        $request = new Request();
        
        Database::insert('user_logins', [
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => $success
        ]);
    }

    /**
     * Check if account is locked
     */
    private static function isAccountLocked(int $userId): bool
    {
        $maxAttempts = Config::get('security.max_login_attempts');
        $lockoutTime = max(1, (int) Config::get('security.login_lockout_time'));

        $recentFailures = Database::fetchColumn(
            "SELECT COUNT(*) FROM user_logins 
             WHERE user_id = ? AND success = FALSE 
             AND login_time > DATE_SUB(NOW(), INTERVAL {$lockoutTime} SECOND)",
            [$userId]
        );

        return $recentFailures >= $maxAttempts;
    }

    /**
     * Clear failed login attempts
     */
    private static function clearFailedAttempts(int $userId): void
    {
        Database::delete(
            'user_logins',
            'user_id = ? AND success = FALSE',
            [$userId]
        );
    }

    /**
     * Sanitize user data for output
     */
    private static function sanitizeUser(array $user): array
    {
        unset($user['password']);
        return $user;
    }

    /**
     * Sanitize admin data for output
     */
    private static function sanitizeAdmin(array $admin): array
    {
        unset($admin['password']);
        return $admin;
    }

    /**
     * Check if user has permission
     */
    public static function hasPermission(string $permission): bool
    {
        if (!self::adminCheck()) {
            return false;
        }

        $permissions = self::$admin['permissions'] ?? [];
        
        // Super admin has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Require authentication
     */
    public static function requireAuth(): void
    {
        // If the user is authenticated, allow access to user area even if an admin session exists.
        // This prevents stale admin cookies/sessions from forcing redirects to /admin.
        if (self::check()) {
            return;
        }

        // If no user session exists but an admin session is active, keep them inside /admin only
        if (self::adminCheck() || (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id']))) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
            $basePath = (string) Config::get('app.base_path', '');
            $normalized = $path;
            if ($basePath !== '' && $basePath !== '/' && str_starts_with($normalized, $basePath)) {
                $normalized = substr($normalized, strlen($basePath));
            }
            $normalized = $normalized ?: '/';

            if (!str_starts_with($normalized, '/admin')) {
                $response = new Response();
                if (self::requestExpectsJson()) {
                    $response->forbidden('Admin session active. Use admin panel.');
                    exit;
                }
                $response->redirect('/admin');
            }
        }

        if (!self::check()) {
            $response = new Response();
            if (self::requestExpectsJson()) {
                $response->unauthorized('Authentication required');
                exit;
            }

            $response->redirect('/login');
        }
    }

    /**
     * Require admin authentication
     */
    public static function requireAdmin(): void
    {
        if (!self::adminCheck() && !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id'])) {
            $admin = Database::fetch(
                "SELECT * FROM admins WHERE admin_id = ? AND status = 'active'",
                [$_SESSION['admin_id']]
            );
            if ($admin) {
                self::$admin = $admin;
            }
        }

        $isAdmin = self::adminCheck();
        if (class_exists(\Core\Logger::class)) {
            \Core\Logger::info('requireAdmin() called', [
                'adminCheck' => $isAdmin,
                'has_admin_session' => isset($_SESSION['admin_jwt_token']),
                'has_admin_cookie' => isset($_COOKIE['admin_jwt_token']),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
        }

        if (!$isAdmin) {
            $response = new Response();
            if (self::requestExpectsJson()) {
                $response->unauthorized('Admin authentication required');
                exit;
            }

            $response->redirect('/admin/login');
        }
    }

    /**
     * Require specific permission
     */
    public static function requirePermission(string $permission): void
    {
        if (!self::hasPermission($permission)) {
            $response = new Response();
            $response->forbidden('Insufficient permissions');
        }
    }
}
