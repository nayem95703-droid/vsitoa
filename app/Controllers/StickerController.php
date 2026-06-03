<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Validator;
use Core\Logger;

class StickerController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Show user stickers collection
     */
    public function showStickers(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        
        // Get user stickers
        $stmt = $this->db->prepare("
            SELECT * FROM user_stickers 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY sticker_type, earned_at DESC
        ");
        $stmt->execute([$user['id']]);
        $userStickers = $stmt->fetchAll();
        
        // Get available stickers to earn
        $availableStickers = $this->getAvailableStickers($user);
        
        // Check if user has exclusive stickers feature
        $hasExclusiveStickers = VerifiedController::hasFeature($user['id'], 'exclusive_stickers');
        
        include ROOT_PATH . '/views/user/stickers.php';
    }
    
    /**
     * Award sticker to user
     */
    public function awardSticker(int $userId, string $stickerType, string $stickerName, string $icon, string $color = '#1e40af', string $description = ''): bool
    {
        try {
            // Check if user already has this sticker
            $stmt = $this->db->prepare("
                SELECT id FROM user_stickers 
                WHERE user_id = ? AND sticker_name = ? AND is_active = 1
            ");
            $stmt->execute([$userId, $stickerName]);
            
            if ($stmt->fetch()) {
                return true; // Already has this sticker
            }
            
            // Award sticker
            $stmt = $this->db->prepare("
                INSERT INTO user_stickers (
                    user_id, sticker_type, sticker_name, sticker_icon, 
                    sticker_color, description, earned_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$userId, $stickerType, $stickerName, $icon, $color, $description]);
            
            // Log sticker award
            Logger::info('Sticker awarded', [
                'user_id' => $userId,
                'sticker_name' => $stickerName,
                'sticker_type' => $stickerType
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Logger::error('Failed to award sticker', [
                'user_id' => $userId,
                'sticker_name' => $stickerName,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get available stickers user can earn
     */
    private function getAvailableStickers($user): array
    {
        $stickers = [];
        
        // Base stickers available to all users
        $stickers[] = [
            'name' => 'First Task',
            'icon' => '🎯',
            'color' => '#10b981',
            'description' => 'Complete your first task',
            'requirement' => 'Complete 1 task',
            'earned' => $this->hasSticker($user['id'], 'First Task')
        ];
        
        $stickers[] = [
            'name' => 'Task Master',
            'icon' => '⭐',
            'color' => '#f59e0b',
            'description' => 'Complete 10 tasks',
            'requirement' => 'Complete 10 tasks',
            'earned' => $this->hasSticker($user['id'], 'Task Master')
        ];
        
        $stickers[] = [
            'name' => 'Top Earner',
            'icon' => '💰',
            'color' => '#8b5cf6',
            'description' => 'Earn $100+ in total',
            'requirement' => 'Earn $100+',
            'earned' => $this->hasSticker($user['id'], 'Top Earner')
        ];
        
        // Verified user exclusive stickers
        if ($user['is_verified']) {
            $stickers[] = [
                'name' => 'Verified Pro',
                'icon' => '✓',
                'color' => '#1e40af',
                'description' => 'Verified user with enhanced features',
                'requirement' => 'Get verified',
                'earned' => $this->hasSticker($user['id'], 'Verified Pro')
            ];
            
            $stickers[] = [
                'name' => 'Security Expert',
                'icon' => '🛡️',
                'color' => '#059669',
                'description' => 'Master of account security',
                'requirement' => 'Enable 2FA and trusted IPs',
                'earned' => $this->hasSticker($user['id'], 'Security Expert')
            ];
        }
        
        // Exclusive stickers for verified users with premium features
        if (VerifiedController::hasFeature($user['id'], 'exclusive_stickers')) {
            $stickers[] = [
                'name' => 'Elite Member',
                'icon' => '👑',
                'color' => '#dc2626',
                'description' => 'Elite verified member',
                'requirement' => 'Verified + 6 months active',
                'earned' => $this->hasSticker($user['id'], 'Elite Member')
            ];
            
            $stickers[] = [
                'name' => 'Community Leader',
                'icon' => '🏆',
                'color' => '#7c3aed',
                'description' => 'Recognized community leader',
                'requirement' => 'Help 10+ users',
                'earned' => $this->hasSticker($user['id'], 'Community Leader')
            ];
        }
        
        return $stickers;
    }
    
    /**
     * Check if user has specific sticker
     */
    private function hasSticker(int $userId, string $stickerName): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM user_stickers 
            WHERE user_id = ? AND sticker_name = ? AND is_active = 1
        ");
        $stmt->execute([$userId, $stickerName]);
        return (int) $stmt->fetch()['count'] > 0;
    }
    
    /**
     * Check and award achievement stickers
     */
    public function checkAchievements(int $userId): void
    {
        try {
            // Get user stats
            $stmt = $this->db->prepare("
                SELECT 
                    u.is_verified,
                    u.total_tasks_completed,
                    u.total_earnings,
                    u.registration_date,
                    COUNT(DISTINCT t.id) as tasks_created
                FROM users u
                LEFT JOIN tasks t ON u.id = t.advertiser_id
                WHERE u.id = ?
                GROUP BY u.id
            ");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch();
            
            if (!$stats) return;
            
            // First Task sticker
            if ($stats['total_tasks_completed'] >= 1) {
                $this->awardSticker($userId, 'achievement', 'First Task', '🎯', '#10b981', 'Complete your first task');
            }
            
            // Task Master sticker
            if ($stats['total_tasks_completed'] >= 10) {
                $this->awardSticker($userId, 'achievement', 'Task Master', '⭐', '#f59e0b', 'Complete 10 tasks');
            }
            
            // Top Earner sticker
            if ($stats['total_earnings'] >= 100) {
                $this->awardSticker($userId, 'achievement', 'Top Earner', '💰', '#8b5cf6', 'Earn $100+ in total');
            }
            
            // Verified Pro sticker
            if ($stats['is_verified']) {
                $this->awardSticker($userId, 'verified', 'Verified Pro', '✓', '#1e40af', 'Verified user with enhanced features');
            }
            
            // Task Creator sticker
            if ($stats['tasks_created'] >= 5) {
                $this->awardSticker($userId, 'milestone', 'Task Creator', '📝', '#06b6d4', 'Create 5+ tasks');
            }
            
            // Long-term member sticker
            $registrationDate = new \DateTime($stats['registration_date']);
            $currentDate = new \DateTime();
            $monthsActive = $registrationDate->diff($currentDate)->m + ($registrationDate->diff($currentDate)->y * 12);
            
            if ($monthsActive >= 6) {
                $this->awardSticker($userId, 'milestone', 'Loyal Member', '💎', '#ec4899', 'Active for 6+ months');
            }
            
            // Elite Member sticker (verified + 6 months)
            if ($stats['is_verified'] && $monthsActive >= 6) {
                $this->awardSticker($userId, 'exclusive', 'Elite Member', '👑', '#dc2626', 'Elite verified member');
            }
            
        } catch (\Exception $e) {
            Logger::error('Failed to check achievements', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Create custom sticker (admin only)
     */
    public function createCustomSticker(Request $request, Response $response): void
    {
        Auth::requireAdmin();
        
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'user_id' => 'required|integer',
            'sticker_name' => 'required|min:2|max:100',
            'sticker_icon' => 'required|max:50',
            'sticker_color' => 'required|max:20',
            'description' => 'max:200'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Invalid sticker data.';
            $response->redirect('/admin?page=stickers');
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_stickers (
                    user_id, sticker_type, sticker_name, sticker_icon, 
                    sticker_color, description, earned_at
                ) VALUES (?, 'custom', ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['user_id'],
                $data['sticker_name'],
                $data['sticker_icon'],
                $data['sticker_color'],
                $data['description'] ?? ''
            ]);
            
            $_SESSION['flash_success'] = 'Custom sticker created successfully.';
            
        } catch (\Exception $e) {
            Logger::error('Failed to create custom sticker', [
                'user_id' => $data['user_id'],
                'sticker_name' => $data['sticker_name'],
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Failed to create custom sticker.';
        }
        
        $response->redirect('/admin?page=stickers');
    }
    
    /**
     * Remove sticker from user
     */
    public function removeSticker(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $stickerId = $request->get('id');
        
        if (!$stickerId) {
            $_SESSION['flash_error'] = 'Invalid request.';
            $response->redirect('/stickers');
            return;
        }
        
        try {
            // Check sticker ownership
            $stmt = $this->db->prepare("
                SELECT sticker_type FROM user_stickers 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$stickerId, $user['id']]);
            $sticker = $stmt->fetch();
            
            if (!$sticker) {
                $_SESSION['flash_error'] = 'Sticker not found.';
                $response->redirect('/stickers');
                return;
            }
            
            // Don't allow removing verified or achievement stickers
            if (in_array($sticker['sticker_type'], ['verified', 'achievement'])) {
                $_SESSION['flash_error'] = 'Cannot remove verified or achievement stickers.';
                $response->redirect('/stickers');
                return;
            }
            
            // Deactivate sticker
            $stmt = $this->db->prepare("
                UPDATE user_stickers 
                SET is_active = 0 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$stickerId, $user['id']]);
            
            $_SESSION['flash_success'] = 'Sticker removed successfully.';
            
        } catch (\Exception $e) {
            Logger::error('Failed to remove sticker', [
                'user_id' => $user['id'],
                'sticker_id' => $stickerId,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Failed to remove sticker.';
        }
        
        $response->redirect('/stickers');
    }
    
    /**
     * Get user stickers for display
     */
    public function getUserStickers(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_stickers 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY 
                CASE sticker_type 
                    WHEN 'verified' THEN 1
                    WHEN 'exclusive' THEN 2
                    WHEN 'achievement' THEN 3
                    WHEN 'milestone' THEN 4
                    ELSE 5
                END,
                earned_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Render sticker HTML
     */
    public static function renderSticker(array $sticker, string $size = 'normal'): string
    {
        $sizeClass = '';
        $iconSize = '16px';
        
        switch ($size) {
            case 'small':
                $sizeClass = 'verified-badge-small';
                $iconSize = '12px';
                break;
            case 'large':
                $sizeClass = 'verified-badge-large';
                $iconSize = '20px';
                break;
        }
        
        return sprintf(
            '<span class="verified-sticker %s" style="background: %s;" title="%s">' .
            '<span style="font-size: %s;">%s</span> %s' .
            '</span>',
            $sizeClass,
            htmlspecialchars($sticker['sticker_color']),
            htmlspecialchars($sticker['description'] ?? $sticker['sticker_name']),
            $iconSize,
            htmlspecialchars($sticker['sticker_icon']),
            htmlspecialchars($sticker['sticker_name'])
        );
    }
    
    /**
     * Admin: Show all stickers management
     */
    public function showStickerManagement(Request $request, Response $response): void
    {
        Auth::requireAdmin();
        
        $stmt = $this->db->prepare("
            SELECT us.*, u.username, u.email
            FROM user_stickers us
            JOIN users u ON us.user_id = u.id
            ORDER BY us.created_at DESC
            LIMIT 100
        ");
        $stmt->execute();
        $allStickers = $stmt->fetchAll();
        
        include ROOT_PATH . '/views/admin/sticker_management.php';
    }
}
