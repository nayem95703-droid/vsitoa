<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Validator;
use Core\Database;
use Core\Mailer;
use Core\Logger;
use Core\Config;

class AuthController
{
    /**
     * Show change password form (logged in users)
     */
    public function showChangePassword(Request $request, Response $response): void
    {
        Auth::requireAuth();

        include ROOT_PATH . '/views/user/change_password.php';
    }

    /**
     * Handle change password (logged in users)
     */
    public function changePassword(Request $request, Response $response): void
    {
        Auth::requireAuth();

        $data = $request->all();

        $validator = Validator::make($data, [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password'
        ]);

        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please fill all fields correctly.';
            $response->redirect('/change-password');
            return;
        }

        $user = Auth::user();
        if (!$user || !password_verify($data['current_password'], $user['password'])) {
            $_SESSION['flash_error'] = 'Current password is incorrect.';
            $response->redirect('/change-password');
            return;
        }

        try {
            $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);

            Database::update(
                'users',
                ['password' => $hashedPassword],
                'user_id = ?',
                [$user['user_id']]
            );

            $_SESSION['flash_success'] = 'Password updated successfully.';
            $response->redirect('/change-password');
        } catch (\Exception $e) {
            Logger::error("Change password failed: " . $e->getMessage());
            $_SESSION['flash_error'] = 'An error occurred. Please try again.';
            $response->redirect('/change-password');
        }
    }
    /**
     * Show registration form
     */
    public function showRegister(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->redirect('/dashboard');
            return;
        }

        include ROOT_PATH . '/views/auth/register.php';
    }

    /**
     * Handle user registration
     */
    public function register(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->json(['success' => false, 'message' => 'Already logged in']);
            return;
        }

        $data = $request->all();

        // Validate input
        $validator = Validator::make($data, [
            'username' => 'required|min:3|max:50|alpha_num|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'wallet_address' => 'nullable|string|max:255',
            'referral_code' => 'nullable|alpha_num|max:20',
            'terms' => 'required'
        ], [
            'terms.required' => 'You must agree to the terms and conditions',
            'username.unique' => 'Username already exists',
            'email.unique' => 'Email already exists'
        ]);

        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }

        try {
            $user = Auth::register($data);
            
            Logger::info("New user registered: {$user['email']}", [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'referred_by' => $data['referral_code'] ?? null
            ]);

            $response->json([
                'success' => true,
                'message' => 'Registration successful! Please check your email to verify your account.',
                'user' => $user,
                'requires_verification' => Config::get('security.require_email_verification')
            ]);

        } catch (\Exception $e) {
            Logger::error("Registration failed: " . $e->getMessage());
            $response->error($e->getMessage());
        }
    }

    /**
     * Show login form
     */
    public function showLogin(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->redirect('/dashboard');
            return;
        }

        $redirect = $request->get('redirect', '/dashboard');
        include ROOT_PATH . '/views/auth/login.php';
    }

    /**
     * Handle user login
     */
    public function login(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->json(['success' => false, 'message' => 'Already logged in']);
            return;
        }

        $data = $request->all();

        // Validate input
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'nullable'
        ]);

        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }

        try {
            $result = Auth::login([
                'email' => $data['email'],
                'password' => $data['password']
            ], !empty($data['remember']));

            Logger::info("User logged in: {$result['user']['email']}", [
                'user_id' => $result['user']['user_id'],
                'ip' => $request->ip()
            ]);

            $response->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $result['user'],
                'token' => $result['token'],
                'redirect' => $request->get('redirect', '/dashboard')
            ]);

        } catch (\Exception $e) {
            Logger::warning("Login failed for {$data['email']}: " . $e->getMessage());
            $response->error($e->getMessage());
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            Auth::logout();
            
            Logger::info("User logged out", [
                'user_id' => $user['user_id'],
                'username' => $user['username']
            ]);
        }

        $response->redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->redirect('/dashboard');
            return;
        }

        include ROOT_PATH . '/views/auth/forgot_password.php';
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->json(['success' => false, 'message' => 'Already logged in']);
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|email'
        ]);

        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }

        try {
            $user = Database::fetch(
                "SELECT * FROM users WHERE email = ? AND status = 'active'",
                [$data['email']]
            );

            if (!$user) {
                // Don't reveal if email exists or not
                $response->json([
                    'success' => true,
                    'message' => 'If an account exists with this email, you will receive password reset instructions.'
                ]);
                return;
            }

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Store reset token
            Database::insert('password_resets', [
                'user_id' => $user['user_id'],
                'token' => $token,
                'expires_at' => $expiresAt
            ]);

            // Send reset email
            $sent = Mailer::sendPasswordResetEmail($user, $token);

            if ($sent) {
                Logger::info("Password reset email sent to: {$user['email']}");
                $response->json([
                    'success' => true,
                    'message' => 'Password reset instructions have been sent to your email.'
                ]);
            } else {
                $response->error('Failed to send reset email. Please try again.');
            }

        } catch (\Exception $e) {
            Logger::error("Password reset failed: " . $e->getMessage());
            $response->error('An error occurred. Please try again.');
        }
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->redirect('/dashboard');
            return;
        }

        $token = $request->get('token');
        
        if (!$token) {
            $response->redirect('/login');
            return;
        }

        // Validate token
        $reset = Database::fetch(
            "SELECT pr.*, u.email FROM password_resets pr
             JOIN users u ON pr.user_id = u.user_id
             WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = FALSE",
            [$token]
        );

        if (!$reset) {
            include ROOT_PATH . '/views/auth/reset_password_invalid.php';
            return;
        }

        include ROOT_PATH . '/views/auth/reset_password.php';
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->json(['success' => false, 'message' => 'Already logged in']);
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'token' => 'required',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password'
        ]);

        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }

        try {
            // Validate token
            $reset = Database::fetch(
                "SELECT pr.*, u.email FROM password_resets pr
                 JOIN users u ON pr.user_id = u.user_id
                 WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = FALSE",
                [$data['token']]
            );

            if (!$reset) {
                $response->error('Invalid or expired reset token');
                return;
            }

            // Update password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            Database::update(
                'users',
                ['password' => $hashedPassword],
                'user_id = ?',
                [$reset['user_id']]
            );

            // Mark token as used
            Database::update(
                'password_resets',
                ['used' => TRUE, 'used_at' => date('Y-m-d H:i:s')],
                'token = ?',
                [$data['token']]
            );

            Logger::info("Password reset completed for: {$reset['email']}", [
                'user_id' => $reset['user_id']
            ]);

            $response->json([
                'success' => true,
                'message' => 'Password reset successful. You can now login with your new password.'
            ]);

        } catch (\Exception $e) {
            Logger::error("Password reset failed: " . $e->getMessage());
            $response->error('An error occurred. Please try again.');
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request, Response $response): void
    {
        $token = $request->get('token');

        if (!$token) {
            $response->redirect('/login');
            return;
        }

        try {
            // Validate token
            $verification = Database::fetch(
                "SELECT ev.*, u.email, u.username FROM email_verifications ev
                 JOIN users u ON ev.user_id = u.user_id
                 WHERE ev.token = ? AND ev.expires_at > NOW() AND ev.used = FALSE",
                [$token]
            );

            if (!$verification) {
                include ROOT_PATH . '/views/auth/verify_invalid.php';
                return;
            }

            // Activate user account
            Database::update(
                'users',
                ['status' => 'active', 'email_verified' => TRUE],
                'user_id = ?',
                [$verification['user_id']]
            );

            // Mark token as used
            Database::update(
                'email_verifications',
                ['used' => TRUE, 'used_at' => date('Y-m-d H:i:s')],
                'token = ?',
                [$token]
            );

            Logger::info("Email verified for: {$verification['email']}", [
                'user_id' => $verification['user_id']
            ]);

            include ROOT_PATH . '/views/auth/verify_success.php';

        } catch (\Exception $e) {
            Logger::error("Email verification failed: " . $e->getMessage());
            include ROOT_PATH . '/views/auth/verify_error.php';
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerification(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->json(['success' => false, 'message' => 'Already logged in']);
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|email'
        ]);

        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }

        try {
            $user = Database::fetch(
                "SELECT * FROM users WHERE email = ? AND status = 'unverified'",
                [$data['email']]
            );

            if (!$user) {
                $response->json([
                    'success' => true,
                    'message' => 'If an unverified account exists with this email, verification instructions have been sent.'
                ]);
                return;
            }

            // Generate new token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            // Store verification token
            Database::insert('email_verifications', [
                'user_id' => $user['user_id'],
                'token' => $token,
                'expires_at' => $expiresAt
            ]);

            // Send verification email
            $sent = Mailer::sendVerificationEmail($user, $token);

            if ($sent) {
                Logger::info("Verification email resent to: {$user['email']}");
                $response->json([
                    'success' => true,
                    'message' => 'Verification email has been sent to your email address.'
                ]);
            } else {
                $response->error('Failed to send verification email. Please try again.');
            }

        } catch (\Exception $e) {
            Logger::error("Resend verification failed: " . $e->getMessage());
            $response->error('An error occurred. Please try again.');
        }
    }

    /**
     * Show admin login form
     */
    public function showAdminLogin(Request $request, Response $response): void
    {
        if (Auth::adminCheck()) {
            $response->redirect('/admin');
            return;
        }

        include ROOT_PATH . '/views/auth/admin_login.php';
    }

    /**
     * Handle admin login
     */
    public function adminLogin(Request $request, Response $response): void
    {
        if (Auth::adminCheck()) {
            $response->json(['success' => false, 'message' => 'Already logged in']);
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'password' => 'required'
        ]);

        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }

        try {
            $result = Auth::adminLogin($data);

            Logger::info("Admin logged in: {$result['admin']['username']}", [
                'admin_id' => $result['admin']['admin_id'],
                'role' => $result['admin']['role'],
                'ip' => $request->ip()
            ]);

            if ($request->isAjax() || $request->isJson()) {
                $response->json([
                    'success' => true,
                    'message' => 'Admin login successful',
                    'redirect' => '/admin'
                ]);
                return;
            }

            $response->redirect('/admin');
            return;

        } catch (\Exception $e) {
            Logger::warning("Admin login failed: " . $e->getMessage(), [
                'ip' => $request->ip()
            ]);

            if ($request->isAjax() || $request->isJson()) {
                $response->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 401);
                return;
            }

            $response->error($e->getMessage());
        }
    }

    /**
     * Handle admin logout
     */
    public function adminLogout(Request $request, Response $response): void
    {
        if (Auth::adminCheck()) {
            $admin = Auth::admin();
            Auth::adminLogout();
            
            Logger::info("Admin logged out", [
                'admin_id' => $admin['admin_id'],
                'username' => $admin['username']
            ]);
        }

        $response->redirect('/admin/login');
    }
}
