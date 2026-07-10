<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Validator;
use Core\Mailer;
use Core\Logger;

class VerifiedController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function resolveUsersPrimaryKeyColumn(): string
    {
        return 'user_id';
    }

    private function ensureVerificationRequestsTableExists(): void
    {
        if (Database::tableExists('verification_requests')) {
            return;
        }

        Database::exec(
            "CREATE TABLE IF NOT EXISTS verification_requests (" .
            "id INT AUTO_INCREMENT PRIMARY KEY," .
            "user_id BIGINT UNSIGNED NOT NULL," .
            "full_name VARCHAR(100) NOT NULL," .
            "email VARCHAR(255) NOT NULL," .
            "phone VARCHAR(20) NOT NULL," .
            "id_document_path VARCHAR(500) NOT NULL," .
            "company_name VARCHAR(200) NULL," .
            "website_url VARCHAR(255) NULL," .
            "description TEXT NOT NULL," .
            "status ENUM('pending','approved','rejected') DEFAULT 'pending'," .
            "processed_by BIGINT UNSIGNED NULL," .
            "processed_at TIMESTAMP NULL," .
            "notes TEXT NULL," .
            "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP," .
            "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP," .
            "INDEX idx_status (status)," .
            "INDEX idx_user_id (user_id)," .
            "INDEX idx_created_at (created_at)" .
            ") ENGINE=InnoDB"
        );
    }
    
    /**
     * Show verification application form
     */
    public function showVerificationForm(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        if (!empty($user['is_verified'])) {
            $_SESSION['flash_info'] = 'Your account is already verified.';
            $response->redirect('/dashboard');
            return;
        }
        
        include ROOT_PATH . '/views/user/verification_form.php';
    }
    
    /**
     * Process verification application
     */
    public function applyVerification(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        if (!empty($user['is_verified'])) {
            $_SESSION['flash_error'] = 'Your account is already verified.';
            $response->redirect('/dashboard');
            return;
        }
        
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'full_name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'phone' => 'required|min:10|max:20',
            'id_document' => 'required',
            'company_name' => 'max:200',
            'website_url' => 'url|max:255',
            'description' => 'required|min:50|max:1000',
            'terms_accepted' => 'required'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please fill all required fields correctly.';
            $_SESSION['old_input'] = $data;
            $response->redirect('/verify');
            return;
        }
        
        try {
            // Handle document upload
            $documentPath = $this->handleDocumentUpload($_FILES['id_document']);
            
            // Insert verification request
            $stmt = $this->db->prepare("
                INSERT INTO verification_requests (
                    user_id, full_name, email, phone, id_document_path, 
                    company_name, website_url, description, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $user['id'],
                $data['full_name'],
                $data['email'],
                $data['phone'],
                $documentPath,
                $data['company_name'] ?? null,
                $data['website_url'] ?? null,
                $data['description']
            ]);
            
            // Log verification request
            Logger::info('Verification request submitted', [
                'user_id' => $user['id'],
                'email' => $data['email']
            ]);
            
            // Send confirmation email
            $this->sendVerificationEmail($user, $data);
            
            $_SESSION['flash_success'] = 'Verification request submitted successfully. We will review your application within 3-5 business days.';
            $response->redirect('/dashboard');
            
        } catch (\Exception $e) {
            Logger::error('Verification application error', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'An error occurred while submitting your verification request. Please try again.';
            $_SESSION['old_input'] = $data;
            $response->redirect('/verify');
        }
    }
    
    /**
     * Handle document upload
     */
    private function handleDocumentUpload($file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload error');
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception('Invalid file type. Only JPG, PNG, GIF, and PDF files are allowed.');
        }
        
        if ($file['size'] > $maxSize) {
            throw new \Exception('File size too large. Maximum size is 5MB.');
        }
        
        $uploadDir = ROOT_PATH . '/assets/uploads/verification/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = uniqid('verify_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new \Exception('Failed to move uploaded file');
        }
        
        return '/assets/uploads/verification/' . $filename;
    }
    
    /**
     * Send verification confirmation email
     */
    private function sendVerificationEmail($user, $data): void
    {
        $subject = 'Verification Request Received - VSITOA';
        $message = "
        <h2>Verification Request Received</h2>
        <p>Dear {$user['username']},</p>
        <p>Thank you for applying for account verification. Your request has been received and is currently under review.</p>
        
        <h3>Application Details:</h3>
        <ul>
            <li><strong>Full Name:</strong> {$data['full_name']}</li>
            <li><strong>Email:</strong> {$data['email']}</li>
            <li><strong>Phone:</strong> {$data['phone']}</li>
            <li><strong>Company:</strong> " . ($data['company_name'] ?? 'N/A') . "</li>
            <li><strong>Website:</strong> " . ($data['website_url'] ?? 'N/A') . "</li>
        </ul>
        
        <h3>Next Steps:</h3>
        <ol>
            <li>Our team will review your application within 3-5 business days</li>
            <li>You will receive an email notification once a decision is made</li>
            <li>If approved, your account will receive verified status and all associated benefits</li>
        </ol>
        
        <h3>Verified Benefits:</h3>
        <ul>
            <li>🛡️ Increased Account Protection</li>
            <li>💬 Enhanced Support Priority</li>
            <li>🔗 Upgraded Profile Links</li>
            <li>🔍 Search Optimization</li>
            <li>⭐ Exclusive Verified Stickers</li>
        </ul>
        
        <p>If you have any questions, please contact our support team.</p>
        
        <p>Best regards,<br>VSITOA Team</p>
        ";
        
        try {
            Mailer::send($data['email'], $subject, $message);
        } catch (\Exception $e) {
            Logger::error('Failed to send verification email', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Admin: Show verification requests
     */
    public function showVerificationRequests(Request $request, Response $response): void
    {
        Auth::requireAdmin();

        $status = (string) $request->get('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $this->ensureVerificationRequestsTableExists();

        $userPk = $this->resolveUsersPrimaryKeyColumn();
        $userTypeSelect = Database::columnExists('users', 'user_type') ? 'u.user_type' : "'' AS user_type";
        $userEmailSelect = Database::columnExists('users', 'email') ? 'u.email' : "''";

        try {
            $stmt = $this->db->prepare(
                "SELECT vr.*, u.username, {$userEmailSelect} as user_email, {$userTypeSelect} " .
                "FROM verification_requests vr " .
                "JOIN users u ON vr.user_id = u.{$userPk} " .
                "WHERE vr.status = ? " .
                "ORDER BY vr.created_at DESC"
            );
            $stmt->execute([$status]);
            $requests = $stmt->fetchAll();
        } catch (\Throwable $e) {
            Logger::error('Failed to load verification requests', [
                'error' => $e->getMessage(),
                'status' => $status
            ]);
            $_SESSION['flash_error'] = 'Unable to load verification requests.';
            $requests = [];
        }

        include ROOT_PATH . '/views/admin/verification_requests.php';
    }

    /**
     * Admin: Process verification request
     */
    public function processVerificationRequest(Request $request, Response $response): void
    {
        Auth::requireAdmin();
        
        $data = $request->all();
        $requestId = $data['request_id'];
        $action = $data['action']; // approve or reject
        $reason = $data['reason'] ?? '';
        
        $validator = Validator::make($data, [
            'request_id' => 'required|integer',
            'action' => 'required|in:approve,reject'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Invalid request parameters.';
            $response->redirect('/admin/verification-requests');
            return;
        }
        
        try {
            $this->ensureVerificationRequestsTableExists();

            $userPk = $this->resolveUsersPrimaryKeyColumn();

            $this->db->beginTransaction();
            
            // Get verification request
            $stmt = $this->db->prepare("
                SELECT vr.*, u.username, u.email
                FROM verification_requests vr
                JOIN users u ON vr.user_id = u.{$userPk}
                WHERE vr.id = ?
            ");
            
            $stmt->execute([$requestId]);
            $verificationRequest = $stmt->fetch();
            
            if (!$verificationRequest) {
                throw new \Exception('Verification request not found');
            }
            
            // Update verification request status
            $stmt = $this->db->prepare("
                UPDATE verification_requests 
                SET status = ?, processed_by = ?, processed_at = NOW(), notes = ?
                WHERE id = ?
            ");
            
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt->execute([$status, Auth::adminId(), $reason, $requestId]);
            
            // If approved, update user verification status
            if ($action === 'approve') {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET is_verified = 1, verified_at = NOW()
                    WHERE {$userPk} = ?
                ");
                $stmt->execute([$verificationRequest['user_id']]);
                
                // Grant enhanced protection features
                $this->grantEnhancedProtection($verificationRequest['user_id']);
            }
            
            // Send notification email
            $this->sendVerificationDecisionEmail($verificationRequest, $action, $reason);
            
            $this->db->commit();
            
            $_SESSION['flash_success'] = "Verification request {$status} successfully.";
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            Logger::error('Verification processing error', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'An error occurred while processing the verification request.';
        }
        
        $response->redirect('/admin/verification-requests');
    }
    
    /**
     * Grant enhanced protection features to verified user
     */
    private function grantEnhancedProtection(int $userId): void
    {
        $features = [
            'two_factor_auth' => true,
            'login_alerts' => true,
            'session_timeout' => 3600, // 1 hour
            'api_access' => true,
            'priority_support' => true,
            'enhanced_search' => true,
            'custom_links' => true,
            'exclusive_stickers' => true
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO user_features (user_id, features, created_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE features = VALUES(features), updated_at = NOW()
        ");
        
        $stmt->execute([$userId, json_encode($features)]);
    }
    
    /**
     * Send verification decision email
     */
    private function sendVerificationDecisionEmail($request, $action, $reason): void
    {
        $subject = $action === 'approve' ? 
            'Congratulations! Your Account is Now Verified - VSITOA' : 
            'Verification Request Update - VSITOA';
            
        if ($action === 'approve') {
            $message = "
            <h2>🎉 Congratulations! Your Account is Verified</h2>
            <p>Dear {$request['username']},</p>
            <p>Your verification request has been <strong>approved</strong>! Your account now has verified status with all the premium benefits.</p>
            
            <h3>🚀 Your New Verified Benefits:</h3>
            <ul>
                <li><strong>🛡️ Increased Account Protection</strong><br>
                Advanced security features including 2FA, login alerts, and enhanced monitoring</li>
                
                <li><strong>💬 Enhanced Support</strong><br>
                Priority customer support with faster response times</li>
                
                <li><strong>🔗 Upgraded Profile Links</strong><br>
                Custom profile URLs and enhanced link management</li>
                
                <li><strong>🔍 Search Optimization</strong><br>
                Your profile appears higher in search results</li>
                
                <li><strong>⭐ Exclusive Stickers</strong><br>
                Special verified-only stickers and badges</li>
            </ul>
            
            <p>Your verified badge will now appear on your profile and throughout the platform.</p>
            
            <p>Thank you for being part of our trusted community!</p>
            
            <p>Best regards,<br>VSITOA Team</p>
            ";
        } else {
            $message = "
            <h2>Verification Request Update</h2>
            <p>Dear {$request['username']},</p>
            <p>Your verification request has been <strong>reviewed</strong> but unfortunately could not be approved at this time.</p>
            
            <h3>Reason:</h3>
            <p>" . htmlspecialchars($reason) . "</p>
            
            <h3>Next Steps:</h3>
            <p>You may submit a new verification request after addressing the issues mentioned above. Please ensure all documents are clear and valid.</p>
            
            <p>If you believe this is an error, please contact our support team.</p>
            
            <p>Best regards,<br>VSITOA Team</p>
            ";
        }
        
        try {
            Mailer::send($request['email'], $subject, $message);
        } catch (\Exception $e) {
            Logger::error('Failed to send verification decision email', [
                'user_id' => $request['user_id'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if user has specific verified feature
     */
    public static function hasFeature(int $userId, string $feature): bool
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT u.is_verified, uf.features
            FROM users u
            LEFT JOIN user_features uf ON u.id = uf.user_id
            WHERE u.id = ? AND u.is_verified = 1
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        $features = json_decode($result['features'] ?? '{}', true);
        return $features[$feature] ?? false;
    }
}
