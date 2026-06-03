<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Validator;
use Core\Logger;

class ProfileController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Show user profile with enhanced features for verified users
     */
    public function showProfile(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $username = $request->get('username', $user['username']);
        
        // Get profile user data
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   us.search_score,
                   us.profile_views,
                   GROUP_CONCAT(DISTINCT usl.url) as profile_links
            FROM users u
            LEFT JOIN user_search_data us ON u.id = us.user_id
            LEFT JOIN user_profile_links usl ON u.id = usl.user_id AND usl.is_public = 1
            WHERE u.username = ? AND u.is_active = 1
        ");
        
        $stmt->execute([$username]);
        $profileUser = $stmt->fetch();
        
        if (!$profileUser) {
            $_SESSION['flash_error'] = 'User not found.';
            $response->redirect('/dashboard');
            return;
        }
        
        // Increment profile views
        if ($profileUser['id'] !== $user['id']) {
            $stmt = $this->db->prepare("
                UPDATE user_search_data 
                SET profile_views = profile_views + 1 
                WHERE user_id = ?
            ");
            $stmt->execute([$profileUser['id']]);
        }
        
        // Get user stickers
        $stmt = $this->db->prepare("
            SELECT * FROM user_stickers 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY earned_at DESC
        ");
        $stmt->execute([$profileUser['id']]);
        $stickers = $stmt->fetchAll();
        
        // Get user profile links
        $stmt = $this->db->prepare("
            SELECT * FROM user_profile_links 
            WHERE user_id = ? AND is_public = 1 
            ORDER BY display_order ASC, created_at DESC
        ");
        $stmt->execute([$profileUser['id']]);
        $profileLinks = $stmt->fetchAll();
        
        // Check if current user can edit this profile
        $canEdit = $profileUser['id'] === $user['id'];
        $hasCustomLinks = VerifiedController::hasFeature($user['id'], 'custom_links');
        
        include ROOT_PATH . '/views/user/profile.php';
    }
    
    /**
     * Show profile edit form
     */
    public function showEditProfile(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        
        // Get user profile links
        $stmt = $this->db->prepare("
            SELECT * FROM user_profile_links 
            WHERE user_id = ? 
            ORDER BY display_order ASC, created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $profileLinks = $stmt->fetchAll();
        
        // Check if user has custom links feature
        $hasCustomLinks = VerifiedController::hasFeature($user['id'], 'custom_links');
        
        include ROOT_PATH . '/views/user/edit_profile.php';
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'first_name' => 'max:50',
            'last_name' => 'max:50',
            'company_name' => 'max:200',
            'website_url' => 'url|max:255',
            'bio' => 'max:1000'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please check your input and try again.';
            $_SESSION['old_input'] = $data;
            $response->redirect('/profile/edit');
            return;
        }
        
        try {
            // Update user profile
            $stmt = $this->db->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, company_name = ?, 
                    website_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['first_name'] ?? null,
                $data['last_name'] ?? null,
                $data['company_name'] ?? null,
                $data['website_url'] ?? null,
                $user['id']
            ]);
            
            // Update search data
            $this->updateSearchData($user['id'], $data);
            
            $_SESSION['flash_success'] = 'Profile updated successfully.';
            
        } catch (\Exception $e) {
            Logger::error('Failed to update profile', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Failed to update profile. Please try again.';
        }
        
        $response->redirect('/profile/edit');
    }
    
    /**
     * Add profile link (verified users only)
     */
    public function addProfileLink(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        
        // Check if user has custom links feature
        if (!VerifiedController::hasFeature($user['id'], 'custom_links')) {
            $_SESSION['flash_error'] = 'This feature is available for verified users only.';
            $response->redirect('/profile/edit');
            return;
        }
        
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'link_type' => 'required|in:website,social,portfolio,business,other',
            'title' => 'required|min:2|max:100',
            'url' => 'required|url|max:500',
            'description' => 'max:200'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please fill all required fields correctly.';
            $_SESSION['old_input'] = $data;
            $response->redirect('/profile/edit');
            return;
        }
        
        try {
            // Get next display order
            $stmt = $this->db->prepare("
                SELECT COALESCE(MAX(display_order), 0) + 1 as next_order
                FROM user_profile_links 
                WHERE user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $nextOrder = $stmt->fetch()['next_order'];
            
            // Add profile link
            $stmt = $this->db->prepare("
                INSERT INTO user_profile_links (
                    user_id, link_type, title, url, description, 
                    display_order, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user['id'],
                $data['link_type'],
                $data['title'],
                $data['url'],
                $data['description'] ?? null,
                $nextOrder
            ]);
            
            $_SESSION['flash_success'] = 'Profile link added successfully.';
            
        } catch (\Exception $e) {
            Logger::error('Failed to add profile link', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Failed to add profile link. Please try again.';
        }
        
        $response->redirect('/profile/edit');
    }
    
    /**
     * Update profile link
     */
    public function updateProfileLink(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'link_id' => 'required|integer',
            'title' => 'required|min:2|max:100',
            'url' => 'required|url|max:500',
            'description' => 'max:200',
            'is_public' => 'boolean'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Invalid request parameters.';
            $response->redirect('/profile/edit');
            return;
        }
        
        try {
            // Verify link ownership
            $stmt = $this->db->prepare("
                SELECT id FROM user_profile_links 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$data['link_id'], $user['id']]);
            
            if (!$stmt->fetch()) {
                $_SESSION['flash_error'] = 'Link not found.';
                $response->redirect('/profile/edit');
                return;
            }
            
            // Update link
            $stmt = $this->db->prepare("
                UPDATE user_profile_links 
                SET title = ?, url = ?, description = ?, 
                    is_public = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['title'],
                $data['url'],
                $data['description'] ?? null,
                $data['is_public'] ?? true,
                $data['link_id'],
                $user['id']
            ]);
            
            $_SESSION['flash_success'] = 'Profile link updated successfully.';
            
        } catch (\Exception $e) {
            Logger::error('Failed to update profile link', [
                'user_id' => $user['id'],
                'link_id' => $data['link_id'],
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Failed to update profile link. Please try again.';
        }
        
        $response->redirect('/profile/edit');
    }
    
    /**
     * Delete profile link
     */
    public function deleteProfileLink(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $linkId = $request->get('id');
        
        if (!$linkId) {
            $_SESSION['flash_error'] = 'Invalid request.';
            $response->redirect('/profile/edit');
            return;
        }
        
        try {
            // Delete link
            $stmt = $this->db->prepare("
                DELETE FROM user_profile_links 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$linkId, $user['id']]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['flash_success'] = 'Profile link deleted successfully.';
            } else {
                $_SESSION['flash_error'] = 'Link not found.';
            }
            
        } catch (\Exception $e) {
            Logger::error('Failed to delete profile link', [
                'user_id' => $user['id'],
                'link_id' => $linkId,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Failed to delete profile link. Please try again.';
        }
        
        $response->redirect('/profile/edit');
    }
    
    /**
     * Reorder profile links
     */
    public function reorderProfileLinks(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $linkIds = $request->get('link_ids', []);
        
        if (empty($linkIds) || !is_array($linkIds)) {
            $response->json(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            foreach ($linkIds as $order => $linkId) {
                $stmt = $this->db->prepare("
                    UPDATE user_profile_links 
                    SET display_order = ? 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$order, $linkId, $user['id']]);
            }
            
            $this->db->commit();
            
            $response->json(['success' => true, 'message' => 'Links reordered successfully']);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            
            Logger::error('Failed to reorder profile links', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $response->json(['success' => false, 'message' => 'Failed to reorder links']);
        }
    }
    
    /**
     * Get custom profile URL for verified users
     */
    public function getCustomProfileUrl(int $userId): string
    {
        if (!VerifiedController::hasFeature($userId, 'custom_links')) {
            return "/profile?username=" . $this->getUsername($userId);
        }
        
        $username = $this->getUsername($userId);
        return "/u/{$username}";
    }
    
    /**
     * Update search data for user
     */
    private function updateSearchData(int $userId, array $data): void
    {
        // Get current user data
        $stmt = $this->db->prepare("
            SELECT username, email, user_type, is_verified, rating
            FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        
        if (!$userData) return;
        
        // Build search keywords
        $keywords = [
            'username' => $userData['username'],
            'email' => $userData['email'],
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'company_name' => $data['company_name'] ?? '',
            'user_type' => $userData['user_type'],
            'bio' => $data['bio'] ?? ''
        ];
        
        // Calculate search score
        $searchScore = 50.0; // Base score
        
        if ($userData['is_verified']) {
            $searchScore += 30.0; // Verified bonus
        }
        
        if ($userData['rating'] >= 4.5) {
            $searchScore += 15.0; // High rating bonus
        } elseif ($userData['rating'] >= 3.5) {
            $searchScore += 10.0;
        }
        
        if (!empty($data['company_name'])) {
            $searchScore += 5.0; // Company bonus
        }
        
        // Update search data
        $stmt = $this->db->prepare("
            INSERT INTO user_search_data (user_id, search_keywords, search_score, last_search_update)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                search_keywords = VALUES(search_keywords),
                search_score = VALUES(search_score),
                last_search_update = NOW()
        ");
        
        $stmt->execute([$userId, json_encode($keywords), $searchScore]);
    }
    
    /**
     * Get username by user ID
     */
    private function getUsername(int $userId): string
    {
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result ? $result['username'] : 'user';
    }
    
    /**
     * Search users with enhanced optimization for verified users
     */
    public function searchUsers(Request $request, Response $response): void
    {
        $query = $request->get('q', '');
        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        if (empty($query)) {
            $response->json(['success' => false, 'message' => 'Search query is required']);
            return;
        }
        
        try {
            // Enhanced search that prioritizes verified users
            $stmt = $this->db->prepare("
                SELECT u.*, us.search_score, us.profile_views,
                       MATCH(us.search_keywords) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
                FROM users u
                JOIN user_search_data us ON u.id = us.user_id
                WHERE u.is_active = 1
                AND MATCH(us.search_keywords) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY 
                    u.is_verified DESC,
                    us.search_score DESC,
                    relevance_score DESC,
                    u.username ASC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$query, $query, $limit, $offset]);
            $users = $stmt->fetchAll();
            
            // Get total count
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM users u
                JOIN user_search_data us ON u.id = us.user_id
                WHERE u.is_active = 1
                AND MATCH(us.search_keywords) AGAINST(? IN NATURAL LANGUAGE MODE)
            ");
            
            $stmt->execute([$query]);
            $total = $stmt->fetch()['total'];
            
            $response->json([
                'success' => true,
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('User search error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            $response->json(['success' => false, 'message' => 'Search failed']);
        }
    }
}
