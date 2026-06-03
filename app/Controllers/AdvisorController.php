<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Validator;
use Core\Logger;

class AdvisorController
{
    /**
     * Show advisor dashboard
     */
    public function showAdvisor(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get user's ads statistics
        $stats = Database::fetch("
            SELECT 
                COUNT(*) as total_ads,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_ads,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_ads,
                COUNT(CASE WHEN status = 'paused' THEN 1 END) as paused_ads,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_ads,
                COALESCE(SUM(total_views), 0) as total_views,
                COALESCE(SUM(remaining_views), 0) as remaining_views,
                COALESCE(SUM(spent_amount), 0) as total_spent,
                COALESCE(SUM(total_budget), 0) as total_budget
            FROM ads 
            WHERE user_id = ?
        ", [$userId]);
        
        // Get recent ads
        $recentAds = Database::fetchAll("
            SELECT 
                ad_id,
                ad_title,
                ad_type,
                status,
                total_views,
                remaining_views,
                spent_amount,
                total_budget,
                created_at
            FROM ads 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ", [$userId]);
        
        // Get today's ad performance
        $todayStats = Database::fetch("
            SELECT 
                COUNT(*) as today_views,
                COUNT(CASE WHEN av.is_valid = TRUE THEN 1 END) as today_valid_views,
                COALESCE(SUM(av.earned_amount), 0) as today_spent
            FROM ads a
            LEFT JOIN ad_views av ON a.ad_id = av.ad_id AND DATE(av.created_at) = CURDATE()
            WHERE a.user_id = ?
        ", [$userId]);
        
        include ROOT_PATH . '/views/user/advisor.php';
    }

    /**
     * Show create ad form
     */
    public function showCreateAd(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get user's balance
        $user = Database::fetch("SELECT advisor_balance FROM users WHERE user_id = ?", [$userId]);
        
        // Get ad types and categories
        $adTypes = [
            'surf' => 'Surf Ad',
            'window' => 'Window Ad', 
            'video' => 'Video Ad',
            'article' => 'Article Ad'
        ];
        
        $adCategories = [
            'website' => 'Website',
            'app' => 'Mobile App',
            'video' => 'Video Content',
            'article' => 'Article/Blog'
        ];
        
        include ROOT_PATH . '/views/user/create_ad.php';
    }

    /**
     * Create new advertisement
     */
    public function createAd(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $data = $request->all();
        
        // Validate input
        $validator = Validator::make($data, [
            'ad_title' => 'required|string|max:255',
            'ad_category' => 'required|in:website,app,video,article',
            'ad_type' => 'required|in:surf,window,video,article',
            'target_url' => 'required|url',
            'description' => 'nullable|string|max:1000',
            'view_time' => 'required|integer|min:5|max:300',
            'auto_redirect' => 'boolean',
            'timer_type' => 'required|in:countdown,progress',
            'cost_per_view' => 'required|numeric|min:0.00000001',
            'total_views' => 'required|integer|min:100|max:1000000',
            'target_countries' => 'nullable|array',
            'device_type' => 'required|in:all,mobile,desktop',
            'browser' => 'required|in:all,chrome,firefox,safari,edge',
            'user_level' => 'required|in:all,normal,premium'
        ], [
            'ad_title.required' => 'Ad title is required',
            'target_url.url' => 'Please enter a valid URL',
            'cost_per_view.min' => 'Cost per view must be greater than 0',
            'total_views.min' => 'Total views must be at least 100'
        ]);
        
        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Get user's current balance
            $user = Database::fetch("SELECT advisor_balance FROM users WHERE user_id = ? FOR UPDATE", [$userId]);
            
            // Calculate total budget
            $platformFeePercent = Config::get('rates.platform_fee');
            $totalBudget = $data['cost_per_view'] * $data['total_views'];
            $platformFee = $totalBudget * ($platformFeePercent / 100);
            $finalPayableAmount = $totalBudget + $platformFee;
            
            // Check if user has sufficient balance to start at least one view
            if ($user['advisor_balance'] < (float) $data['cost_per_view']) {
                Database::rollback();
                $response->error('Insufficient balance to start this ad. Please deposit more funds.', 400);
                return;
            }
            
            // Handle image upload
            $previewImage = null;
            if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
                $previewImage = $this->handleImageUpload($_FILES['preview_image']);
            }
            
            // Create ad record
            $adId = Database::insert('ads', [
                'user_id' => $userId,
                'ad_title' => $data['ad_title'],
                'ad_category' => $data['ad_category'],
                'ad_type' => $data['ad_type'],
                'target_url' => $data['target_url'],
                'description' => $data['description'] ?? null,
                'preview_image' => $previewImage,
                'view_time' => $data['view_time'],
                'auto_redirect' => $data['auto_redirect'] ?? false,
                'timer_type' => $data['timer_type'],
                'cost_per_view' => $data['cost_per_view'],
                'total_views' => $data['total_views'],
                'remaining_views' => $data['total_views'],
                'total_budget' => $totalBudget,
                'platform_fee_percent' => $platformFeePercent,
                'target_countries' => !empty($data['target_countries']) ? json_encode($data['target_countries']) : null,
                'device_type' => $data['device_type'],
                'browser' => $data['browser'],
                'user_level' => $data['user_level'],
                'status' => 'pending'
            ]);
            
            Database::commit();
            
            Logger::logUserActivity('ad_created', [
                'ad_id' => $adId,
                'ad_title' => $data['ad_title'],
                'ad_type' => $data['ad_type'],
                'total_budget' => $totalBudget,
                'total_views' => $data['total_views']
            ]);
            
            $response->json([
                'success' => true,
                'message' => 'Advertisement created successfully! Your ad is now pending admin approval.',
                'data' => [
                    'ad_id' => $adId,
                    'new_balance' => $user['advisor_balance']
                ]
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Create ad error: " . $e->getMessage());
            $response->error('Failed to create advertisement', 500);
        }
    }

    /**
     * Show manage ads page
     */
    public function showManageAds(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $status = $request->get('status', 'all');
        $page = max(1, (int) $request->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Build query
        $whereClause = "WHERE user_id = ?";
        $params = [$userId];
        
        if ($status !== 'all') {
            $whereClause .= " AND status = ?";
            $params[] = $status;
        }
        
        // Get ads
        $ads = Database::fetchAll("
            SELECT 
                ad_id,
                ad_title,
                ad_category,
                ad_type,
                status,
                total_views,
                remaining_views,
                spent_amount,
                total_budget,
                cost_per_view,
                view_time,
                created_at,
                started_at,
                completed_at
            FROM ads 
            $whereClause
            ORDER BY created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ", $params);
        
        // Get total count
        $total = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM ads 
            $whereClause
        ", $params);
        
        // Get statistics
        $stats = Database::fetch("
            SELECT 
                COUNT(*) as total_ads,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_ads,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_ads,
                COUNT(CASE WHEN status = 'paused' THEN 1 END) as paused_ads,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_ads,
                COALESCE(SUM(spent_amount), 0) as total_spent,
                COALESCE(SUM(remaining_views), 0) as remaining_views
            FROM ads 
            WHERE user_id = ?
        ", [$userId]);
        
        include ROOT_PATH . '/views/user/manage_ads.php';
    }

    /**
     * Get user's ads via API
     */
    public function getMyAds(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $status = $request->get('status', 'all');
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, (int) $request->get('limit', 20));
        $offset = ($page - 1) * $limit;
        
        // Build query
        $whereClause = "WHERE user_id = ?";
        $params = [$userId];
        
        if ($status !== 'all') {
            $whereClause .= " AND status = ?";
            $params[] = $status;
        }
        
        // Get ads
        $ads = Database::fetchAll("
            SELECT 
                ad_id,
                ad_title,
                ad_category,
                ad_type,
                status,
                total_views,
                remaining_views,
                spent_amount,
                total_budget,
                cost_per_view,
                view_time,
                created_at,
                started_at,
                completed_at
            FROM ads 
            $whereClause
            ORDER BY created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ", $params);
        
        // Get total count
        $total = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM ads 
            $whereClause
        ", $params);
        
        $response->json([
            'success' => true,
            'data' => $ads,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Update advertisement
     */
    public function updateAd(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $adId = $request->param('id');
        $data = $request->all();
        
        // Check if user owns this ad
        $ad = Database::fetch("
            SELECT ad_id, user_id, status, remaining_views, total_budget, spent_amount
            FROM ads 
            WHERE ad_id = ? AND user_id = ?
        ", [$adId, $userId]);
        
        if (!$ad) {
            $response->error('Advertisement not found', 404);
            return;
        }
        
        // Only allow editing certain fields for active ads
        $allowedFields = ['ad_title', 'description', 'target_url'];
        if ($ad['status'] === 'active') {
            $data = array_intersect_key($data, array_flip($allowedFields));
        }
        
        // Validate input
        $validator = Validator::make($data, [
            'ad_title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'target_url' => 'nullable|url'
        ]);
        
        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Update ad
            Database::update(
                'ads',
                $data,
                'ad_id = ?',
                [$adId]
            );
            
            Database::commit();
            
            Logger::logUserActivity('ad_updated', [
                'ad_id' => $adId,
                'updated_fields' => array_keys($data)
            ]);
            
            $response->json([
                'success' => true,
                'message' => 'Advertisement updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Update ad error: " . $e->getMessage());
            $response->error('Failed to update advertisement', 500);
        }
    }

    /**
     * Delete advertisement
     */
    public function deleteAd(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $adId = $request->param('id');
        
        // Check if user owns this ad
        $ad = Database::fetch("
            SELECT ad_id, user_id, status, total_budget, spent_amount
            FROM ads 
            WHERE ad_id = ? AND user_id = ?
        ", [$adId, $userId]);
        
        if (!$ad) {
            $response->error('Advertisement not found', 404);
            return;
        }
        
        if ($ad['status'] === 'active') {
            $response->error('Cannot delete active advertisement. Please pause it first.', 400);
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Delete ad
            Database::delete('ads', 'ad_id = ?', [$adId]);
            
            Database::commit();
            
            Logger::logUserActivity('ad_deleted', [
                'ad_id' => $adId,
                'refund_amount' => 0
            ]);
            
            $response->json([
                'success' => true,
                'message' => 'Advertisement deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Delete ad error: " . $e->getMessage());
            $response->error('Failed to delete advertisement', 500);
        }
    }

    /**
     * Pause advertisement
     */
    public function pauseAd(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $adId = $request->param('id');
        
        // Check if user owns this ad
        $ad = Database::fetch("
            SELECT ad_id, user_id, status
            FROM ads 
            WHERE ad_id = ? AND user_id = ?
        ", [$adId, $userId]);
        
        if (!$ad) {
            $response->error('Advertisement not found', 404);
            return;
        }
        
        if ($ad['status'] !== 'active') {
            $response->error('Only active advertisements can be paused', 400);
            return;
        }
        
        try {
            Database::update(
                'ads',
                ['status' => 'paused'],
                'ad_id = ?',
                [$adId]
            );
            
            Logger::logUserActivity('ad_paused', ['ad_id' => $adId]);
            
            $response->json([
                'success' => true,
                'message' => 'Advertisement paused successfully'
            ]);
            
        } catch (\Exception $e) {
            Logger::error("Pause ad error: " . $e->getMessage());
            $response->error('Failed to pause advertisement', 500);
        }
    }

    /**
     * Resume advertisement
     */
    public function resumeAd(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $adId = $request->param('id');
        
        // Check if user owns this ad
        $ad = Database::fetch("
            SELECT ad_id, user_id, status, remaining_views
            FROM ads 
            WHERE ad_id = ? AND user_id = ?
        ", [$adId, $userId]);
        
        if (!$ad) {
            $response->error('Advertisement not found', 404);
            return;
        }
        
        if ($ad['status'] !== 'paused') {
            $response->error('Only paused advertisements can be resumed', 400);
            return;
        }
        
        if ($ad['remaining_views'] <= 0) {
            $response->error('Cannot resume advertisement with no remaining views', 400);
            return;
        }
        
        try {
            Database::update(
                'ads',
                ['status' => 'active'],
                'ad_id = ?',
                [$adId]
            );
            
            Logger::logUserActivity('ad_resumed', ['ad_id' => $adId]);
            
            $response->json([
                'success' => true,
                'message' => 'Advertisement resumed successfully'
            ]);
            
        } catch (\Exception $e) {
            Logger::error("Resume ad error: " . $e->getMessage());
            $response->error('Failed to resume advertisement', 500);
        }
    }

    /**
     * Get ad statistics
     */
    public function getAdStats(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $adId = $request->param('id');
        
        // Check if user owns this ad
        $ad = Database::fetch("
            SELECT ad_id, user_id 
            FROM ads 
            WHERE ad_id = ? AND user_id = ?
        ", [$adId, $userId]);
        
        if (!$ad) {
            $response->error('Advertisement not found', 404);
            return;
        }
        
        // Get detailed statistics
        $stats = Database::fetch("
            SELECT 
                a.*,
                COUNT(av.view_id) as total_views,
                COUNT(CASE WHEN av.is_valid = TRUE THEN 1 END) as valid_views,
                COUNT(CASE WHEN av.is_valid = FALSE THEN 1 END) as invalid_views,
                SUM(av.earned_amount) as total_spent,
                AVG(av.actual_view_time) as avg_view_time,
                COUNT(DISTINCT av.viewer_user_id) as unique_viewers,
                COUNT(CASE WHEN DATE(av.created_at) = CURDATE() THEN 1 END) as today_views
            FROM ads a
            LEFT JOIN ad_views av ON a.ad_id = av.ad_id
            WHERE a.ad_id = ?
            GROUP BY a.ad_id
        ", [$adId]);
        
        // Get daily stats for last 30 days
        $dailyStats = Database::fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as views,
                COUNT(CASE WHEN is_valid = TRUE THEN 1 END) as valid_views,
                SUM(earned_amount) as spent
            FROM ad_views 
            WHERE ad_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ", [$adId]);
        
        // Get top viewer countries
        $topCountries = Database::fetchAll("
            SELECT 
                COUNT(*) as views,
                ip_address
            FROM ad_views 
            WHERE ad_id = ? AND is_valid = TRUE
            GROUP BY ip_address
            ORDER BY views DESC
            LIMIT 10
        ", [$adId]);
        
        $response->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'daily_stats' => $dailyStats,
                'top_countries' => $topCountries
            ]
        ]);
    }

    /**
     * Handle image upload
     */
    private function handleImageUpload(array $file): string
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload error');
        }
        
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Invalid file type');
        }
        
        if ($file['size'] > $maxFileSize) {
            throw new \Exception('File too large');
        }
        
        // Generate unique filename
        $filename = uniqid('ad_', true) . '.' . $extension;
        $uploadPath = ROOT_PATH . '/uploads/ads/' . $filename;
        
        // Create directory if not exists
        $uploadDir = dirname($uploadPath);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new \Exception('Failed to move uploaded file');
        }
        
        return '/uploads/ads/' . $filename;
    }
}
