<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Config;
use Core\Validator;
use Core\Logger;

class AdController
{
    /**
     * Show earn page with available ads
     */
    public function showEarnPage(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get available ads
        $ads = Database::fetchAll("
            SELECT 
                ad_id,
                ad_title,
                ad_type,
                ad_category,
                target_url,
                description,
                preview_image,
                view_time,
                cost_per_view,
                auto_redirect,
                timer_type
            FROM ads 
            WHERE status = 'active' 
            AND views_received < total_views
            AND user_id != ?
            ORDER BY created_at DESC
            LIMIT 50
        ", [$userId]);

        // Get user's today's ad views
        $todayViews = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM ad_views 
            WHERE viewer_user_id = ? 
            AND DATE(created_at) = CURDATE()
            AND is_valid = TRUE
        ", [$userId]);

        // Get daily limit
        $dailyLimit = Config::get('max_daily_ads', 100);

        include ROOT_PATH . '/views/user/earn.php';
    }

    /**
     * Get available ads via API
     */
    public function getAds(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, (int) $request->get('limit', 20));
        $type = $request->get('type', 'all');
        $offset = ($page - 1) * $limit;

        // Build query
        $whereClause = "WHERE status = 'active' AND views_received < total_views AND user_id != ?";
        $params = [$userId];

        if ($type !== 'all') {
            $whereClause .= " AND ad_type = ?";
            $params[] = $type;
        }

        // Get ads
        $ads = Database::fetchAll("
            SELECT 
                ad_id,
                ad_title,
                ad_type,
                ad_category,
                target_url,
                description,
                preview_image,
                view_time,
                cost_per_view,
                auto_redirect,
                timer_type,
                created_at
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
     * View an ad
     */
    public function viewAd(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $adId = $request->param('id');
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        try {
            Database::beginTransaction();

            // Get ad details
            $ad = Database::fetch("
                SELECT 
                    ad_id,
                    user_id as advertiser_id,
                    ad_title,
                    ad_type,
                    target_url,
                    view_time,
                    cost_per_view,
                    remaining_views,
                    views_received,
                    total_views,
                    platform_fee_percent
                FROM ads 
                WHERE ad_id = ? 
                AND status = 'active'
                AND views_received < total_views
                FOR UPDATE
            ", [$adId]);

            if (!$ad) {
                Database::rollback();
                $response->error('Ad not available', 404);
                return;
            }

            // Check if user is the advertiser
            if ($ad['advertiser_id'] == $userId) {
                Database::rollback();
                $response->error('You cannot view your own ad', 403);
                return;
            }

            // Check cooldown period
            $recentView = Database::fetch("
                SELECT created_at 
                FROM ad_views 
                WHERE viewer_user_id = ? 
                AND ad_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
            ", [$userId, $adId]);

            if ($recentView) {
                Database::rollback();
                $response->error('Please wait before viewing this ad again', 429);
                return;
            }

            // Check daily limit
            $todayViews = Database::fetchColumn("
                SELECT COUNT(*) 
                FROM ad_views 
                WHERE viewer_user_id = ? 
                AND DATE(created_at) = CURDATE()
            ", [$userId]);

            if ($todayViews >= Config::get('max_daily_ads', 100)) {
                Database::rollback();
                $response->error('Daily ad view limit reached', 429);
                return;
            }

            // Calculate earnings
            $platformFeePercent = $ad['platform_fee_percent'] ?? Config::get('rates.platform_fee');
            $platformFee = $ad['cost_per_view'] * ($platformFeePercent / 100);
            $userEarnings = $ad['cost_per_view'] - $platformFee;

            // Get user's current balance
            $user = Database::fetch("SELECT earning_balance FROM users WHERE user_id = ? FOR UPDATE", [$userId]);

            $advertiserBalance = (float) Database::fetchColumn(
                "SELECT advisor_balance FROM users WHERE user_id = ?",
                [$ad['advertiser_id']]
            );

            // Check if advertiser has sufficient balance (non-locking pre-check)
            if ($advertiserBalance < (float) $ad['cost_per_view']) {
                Database::rollback();
                $response->error('Ad no longer available', 404);
                return;
            }

            // Create ad view record (initially pending validation)
            $viewId = Database::insert('ad_views', [
                'ad_id' => $adId,
                'viewer_user_id' => $userId,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'view_time' => $ad['view_time'],
                'actual_view_time' => 0,
                'is_valid' => 0,
                'earned_amount' => $userEarnings
            ]);

            // Reserve a view slot (do not charge here; charge on successful validation)
            Database::update(
                'ads',
                ['remaining_views' => $ad['remaining_views'] - 1],
                'ad_id = ?',
                [$adId]
            );

            Database::commit();

            // Log ad view
            Logger::logUserActivity('ad_view_started', [
                'ad_id' => $adId,
                'ad_title' => $ad['ad_title'],
                'view_id' => $viewId
            ]);

            $response->json([
                'success' => true,
                'data' => [
                    'view_id' => $viewId,
                    'ad' => [
                        'ad_id' => $ad['ad_id'],
                        'ad_title' => $ad['ad_title'],
                        'ad_type' => $ad['ad_type'],
                        'target_url' => $ad['target_url'],
                        'view_time' => $ad['view_time'],
                        'cost_per_view' => $ad['cost_per_view'],
                        'user_earnings' => $userEarnings,
                        'auto_redirect' => Database::fetch("SELECT auto_redirect FROM ads WHERE ad_id = ?", [$adId])['auto_redirect']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Ad view error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'ad_id' => $adId ?? null,
                'user_id' => $userId ?? null
            ]);
            $response->error('Failed to start ad view', 500);
        }
    }

    /**
     * Complete ad view validation
     */
    public function completeAdView(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $viewId = $request->post('view_id');
        $actualViewTime = $request->post('actual_view_time', 0);
        $validationData = $request->post('validation_data', []);

        try {
            Database::beginTransaction();

            // Get ad view record
            $adView = Database::fetch("
                SELECT av.*, a.view_time as required_view_time, a.cost_per_view, a.platform_fee_percent
                FROM ad_views av
                JOIN ads a ON av.ad_id = a.ad_id
                WHERE av.view_id = ?
                AND av.viewer_user_id = ?
                AND av.is_valid = FALSE
                FOR UPDATE
            ", [$viewId, $userId]);

            if (!$adView) {
                Database::rollback();
                $response->error('Invalid view record', 404);
                return;
            }

            // Validate view time
            $isValid = $actualViewTime >= $adView['required_view_time'];

            // Additional fraud checks
            if ($isValid) {
                $isValid = $this->validateAdView($adView, $validationData);
            }

            if ($isValid) {
                // Calculate earnings
                $platformFeePercent = $adView['platform_fee_percent'] ?? Config::get('rates.platform_fee');
                $platformFee = $adView['cost_per_view'] * ($platformFeePercent / 100);
                $userEarnings = $adView['cost_per_view'] - $platformFee;

                // Lock ad + advertiser to charge pay-as-you-go
                $ad = Database::fetch(
                    "SELECT ad_id, user_id as advertiser_id, ad_title, status, remaining_views, views_received, total_views
                    FROM ads
                    WHERE ad_id = ?
                    FOR UPDATE",
                    [$adView['ad_id']]
                );

                if (!$ad) {
                    Database::update(
                        'ad_views',
                        ['is_valid' => 0, 'actual_view_time' => $actualViewTime],
                        'view_id = ?',
                        [$viewId]
                    );
                    Database::commit();
                    $response->error('Ad is no longer available', 404);
                    return;
                }

                $advertiser = Database::fetch(
                    "SELECT advisor_balance FROM users WHERE user_id = ? FOR UPDATE",
                    [(int) $ad['advertiser_id']]
                );

                if (!$advertiser || (float) ($advertiser['advisor_balance'] ?? 0) < (float) $adView['cost_per_view']) {
                    Database::update(
                        'ads',
                        ['status' => 'paused'],
                        'ad_id = ?',
                        [$adView['ad_id']]
                    );

                    // Restore reserved view slot
                    Database::query(
                        "UPDATE ads SET remaining_views = remaining_views + 1 WHERE ad_id = ?",
                        [$adView['ad_id']]
                    );

                    Database::update(
                        'ad_views',
                        [
                'is_valid' => 0,
                            'actual_view_time' => $actualViewTime
                        ],
                        'view_id = ?',
                        [$viewId]
                    );
                    Database::commit();
                    $response->error('Advertiser balance is insufficient. Ad paused.', 400);
                    return;
                }

                $costPerView = (float) $adView['cost_per_view'];
                $advertiserBalanceBefore = (float) $advertiser['advisor_balance'];
                $advertiserBalanceAfter = $advertiserBalanceBefore - $costPerView;

                // Deduct advertiser balance
                Database::update(
                    'users',
                    ['advisor_balance' => $advertiserBalanceAfter],
                    'user_id = ?',
                    [(int) $ad['advertiser_id']]
                );

                // Update ad counters
                Database::query(
                    "UPDATE ads
                    SET spent_amount = COALESCE(spent_amount, 0) + ?,
                        views_received = COALESCE(views_received, 0) + 1
                    WHERE ad_id = ?",
                    [$costPerView, $adView['ad_id']]
                );

                // Create wallet transaction for advertiser
                Database::insert('wallet_transactions', [
                    'user_id' => (int) $ad['advertiser_id'],
                    'type' => 'ad_spend',
                    'amount' => -$costPerView,
                    'balance_before' => $advertiserBalanceBefore,
                    'balance_after' => $advertiserBalanceAfter,
                    'description' => "Ad spend: {$ad['ad_title']}",
                    'reference_id' => $viewId,
                    'reference_type' => 'ad_view'
                ]);

                // Get user's current balance
                $user = Database::fetch("SELECT earning_balance FROM users WHERE user_id = ? FOR UPDATE", [$userId]);

                // Update user balance
                $newBalance = $user['earning_balance'] + $userEarnings;
                Database::query(
                    "UPDATE users SET earning_balance = ?, total_earned = total_earned + ? WHERE user_id = ?",
                    [$newBalance, $userEarnings, $userId]
                );

                // Update ad view as valid
                Database::update(
                    'ad_views',
                    [
                        'is_valid' => 1,
                        'actual_view_time' => $actualViewTime,
                        'earned_amount' => $userEarnings
                    ],
                    'view_id = ?',
                    [$viewId]
                );

                // Create wallet transaction
                Database::insert('wallet_transactions', [
                    'user_id' => $userId,
                    'type' => 'earn',
                    'amount' => $userEarnings,
                    'balance_before' => $user['earning_balance'],
                    'balance_after' => $newBalance,
                    'description' => "Ad view earnings: {$adView['ad_title']}",
                    'reference_id' => $viewId,
                    'reference_type' => 'ad_view'
                ]);

                // Create earning record
                Database::insert('earnings', [
                    'user_id' => $userId,
                    'source' => 'ad_view',
                    'source_id' => $adView['ad_id'],
                    'amount' => $userEarnings,
                    'description' => "Viewed ad: {$adView['ad_title']}"
                ]);

                // Process referral commissions
                $this->processReferralCommission($userId, $userEarnings, 'ad_view', $adView['ad_id']);

                Database::commit();

                Logger::logUserActivity('ad_view_completed', [
                    'view_id' => $viewId,
                    'ad_id' => $adView['ad_id'],
                    'earnings' => $userEarnings,
                    'view_time' => $actualViewTime
                ]);

                $response->json([
                    'success' => true,
                    'message' => 'Ad view completed successfully',
                    'data' => [
                        'earnings' => $userEarnings,
                        'new_balance' => $newBalance
                    ]
                ]);

            } else {
                // Mark as invalid
                // Restore reserved view slot
                Database::query(
                    "UPDATE ads SET remaining_views = remaining_views + 1 WHERE ad_id = ?",
                    [$adView['ad_id']]
                );

                Database::update(
                    'ad_views',
                    [
                        'is_valid' => 0,
                        'actual_view_time' => $actualViewTime,
                        'fraud_score' => $this->calculateFraudScore($adView, $validationData)
                    ],
                    'view_id = ?',
                    [$viewId]
                );

                Database::commit();

                Logger::logSecurity('invalid_ad_view', [
                    'view_id' => $viewId,
                    'user_id' => $userId,
                    'reason' => 'Validation failed'
                ]);

                $response->error('Ad view validation failed', 400);
            }

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Complete ad view error: " . $e->getMessage());
            $response->error('Failed to complete ad view', 500);
        }
    }

    /**
     * Validate ad view for fraud detection
     */
    private function validateAdView(array $adView, array $validationData): bool
    {
        $fraudScore = 0;

        // Check IP address
        $ipCount = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM ad_views 
            WHERE ip_address = ? 
            AND DATE(created_at) = CURDATE()
        ", [$adView['ip_address']]);

        if ($ipCount > 100) {
            $fraudScore += 30;
        }

        // Check user agent consistency
        $userAgentCount = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM user_login_log 
            WHERE user_id = ? 
            AND user_agent = ?
            AND login_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ", [$adView['viewer_user_id'], $adView['user_agent']]);

        if ($userAgentCount === 0) {
            $fraudScore += 20;
        }

        // Check view time consistency
        if ($validationData['page_focus_lost'] ?? false) {
            $fraudScore += 15;
        }

        if ($validationData['multiple_tabs'] ?? false) {
            $fraudScore += 25;
        }

        // Check rapid clicking
        $recentViews = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM ad_views 
            WHERE viewer_user_id = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ", [$adView['viewer_user_id']]);

        if ($recentViews > 5) {
            $fraudScore += 35;
        }

        return $fraudScore < 50; // Allow if fraud score is less than 50
    }

    /**
     * Calculate fraud score
     */
    private function calculateFraudScore(array $adView, array $validationData): int
    {
        $score = 0;

        if ($validationData['page_focus_lost'] ?? false) $score += 15;
        if ($validationData['multiple_tabs'] ?? false) $score += 25;
        if ($validationData['bot_detected'] ?? false) $score += 50;

        return $score;
    }

    /**
     * Process referral commission
     */
    private function processReferralCommission(int $userId, float $amount, string $sourceType, int $sourceId): void
    {
        // Get referrer
        $referral = Database::fetch("
            SELECT r.referrer_id, r.commission_rate
            FROM referrals r
            WHERE r.referred_user_id = ?
        ", [$userId]);

        if (!$referral) {
            return;
        }

        $commissionRate = $referral['commission_rate'] ?? Config::get('rates.referral_commission');
        $commissionAmount = $amount * ($commissionRate / 100);

        if ($commissionAmount <= 0) {
            return;
        }

        // Update referrer balance
        Database::update(
            'users',
            ['earning_balance' => "earning_balance + {$commissionAmount}"],
            'user_id = ?',
            [$referral['referrer_id']]
        );

        // Create wallet transaction for referrer
        Database::insert('wallet_transactions', [
            'user_id' => $referral['referrer_id'],
            'type' => 'referral',
            'amount' => $commissionAmount,
            'description' => "Referral commission from {$sourceType}",
            'reference_id' => $sourceId,
            'reference_type' => $sourceType
        ]);

        // Create referral earning record
        Database::insert('referral_earnings', [
            'referrer_id' => $referral['referrer_id'],
            'source_user_id' => $userId,
            'amount' => $commissionAmount,
            'commission_rate' => $commissionRate,
            'source_type' => $sourceType,
            'source_id' => $sourceId
        ]);
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
            SELECT ad_id, ad_title, user_id 
            FROM ads 
            WHERE ad_id = ? AND user_id = ?
        ", [$adId, $userId]);

        if (!$ad) {
            $response->error('Ad not found', 404);
            return;
        }

        // Get statistics
        $stats = Database::fetch("
            SELECT 
                COUNT(*) as total_views,
                COUNT(CASE WHEN is_valid = TRUE THEN 1 END) as valid_views,
                COUNT(CASE WHEN is_valid = FALSE THEN 1 END) as invalid_views,
                SUM(earned_amount) as total_spent,
                AVG(actual_view_time) as avg_view_time,
                COUNT(DISTINCT viewer_user_id) as unique_viewers
            FROM ad_views 
            WHERE ad_id = ?
        ", [$adId]);

        // Get daily stats for last 7 days
        $dailyStats = Database::fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as views,
                COUNT(CASE WHEN is_valid = TRUE THEN 1 END) as valid_views,
                SUM(earned_amount) as spent
            FROM ad_views 
            WHERE ad_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ", [$adId]);

        $response->json([
            'success' => true,
            'data' => [
                'ad' => $ad,
                'stats' => $stats,
                'daily_stats' => $dailyStats
            ]
        ]);
    }
}
