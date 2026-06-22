<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Validator;
use Core\Logger;
use Core\Config;

class ReferralController
{
    /**
     * Show referral page
     */
    public function showReferral(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get user's referral code
        $user = Database::fetch("SELECT referral_code, username FROM users WHERE user_id = ?", [$userId]);
        
        // Get referral statistics
        $stats = Database::fetch("
            SELECT 
                COUNT(*) as total_referrals,
                COUNT(CASE WHEN DATE(u.created_at) = CURDATE() THEN 1 END) as today_referrals,
                COUNT(CASE WHEN u.status = 'active' THEN 1 END) as active_referrals,
                COALESCE(SUM(re.amount), 0) as total_commission,
                COALESCE(SUM(CASE WHEN DATE(re.created_at) = CURDATE() THEN re.amount END), 0) as today_commission
            FROM users u
            LEFT JOIN referrals r ON u.user_id = r.referred_user_id
            LEFT JOIN referral_earnings re ON r.referrer_id = re.referrer_id AND re.source_user_id = u.user_id
            WHERE r.referrer_id = ?
        ", [$userId]);
        
        // Get recent referrals
        $recentReferrals = Database::fetchAll("
            SELECT 
                u.username,
                u.email,
                u.created_at,
                u.status,
                COALESCE(SUM(re.amount), 0) as total_earned_from_user
            FROM users u
            JOIN referrals r ON u.user_id = r.referred_user_id
            LEFT JOIN referral_earnings re ON r.referrer_id = re.referrer_id AND re.source_user_id = u.user_id
            WHERE r.referrer_id = ?
            GROUP BY u.user_id
            ORDER BY u.created_at DESC
            LIMIT 10
        ", [$userId]);
        
        // Get referral earnings history
        $earnings = Database::fetchAll("
            SELECT 
                re.amount,
                re.commission_rate,
                re.source_type,
                re.source_id,
                u.username as source_username,
                re.created_at
            FROM referral_earnings re
            JOIN users u ON re.source_user_id = u.user_id
            WHERE re.referrer_id = ?
            ORDER BY re.created_at DESC
            LIMIT 20
        ", [$userId]);
        
        // Get commission rate
        $commissionRate = Config::get('rates.referral_commission');
        
        include ROOT_PATH . '/views/user/referral.php';
    }

    /**
     * Get referral information via API
     */
    public function getReferralInfo(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get user info
        $user = Database::fetch("SELECT referral_code, username FROM users WHERE user_id = ?", [$userId]);
        
        // Get referral link
        $referralLink = Config::get('app.url') . '/register?ref=' . $user['referral_code'];
        
        // Get statistics
        $stats = Database::fetch("
            SELECT 
                COUNT(*) as total_referrals,
                COUNT(CASE WHEN DATE(u.created_at) = CURDATE() THEN 1 END) as today_referrals,
                COUNT(CASE WHEN u.status = 'active' THEN 1 END) as active_referrals,
                COALESCE(SUM(re.amount), 0) as total_commission,
                COALESCE(SUM(CASE WHEN DATE(re.created_at) = CURDATE() THEN re.amount END), 0) as today_commission
            FROM users u
            LEFT JOIN referrals r ON u.user_id = r.referred_user_id
            LEFT JOIN referral_earnings re ON r.referrer_id = re.referrer_id AND re.source_user_id = u.user_id
            WHERE r.referrer_id = ?
        ", [$userId]);
        
        $response->json([
            'success' => true,
            'data' => [
                'referral_code' => $user['referral_code'],
                'referral_link' => $referralLink,
                'stats' => $stats,
                'commission_rate' => Config::get('rates.referral_commission')
            ]
        ]);
    }

    /**
     * Get referrals list via API
     */
    public function getReferrals(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, (int) $request->get('limit', 20));
        $status = $request->get('status', 'all');
        $offset = ($page - 1) * $limit;
        
        // Build query
        $whereClause = "WHERE r.referrer_id = ?";
        $params = [$userId];
        
        if ($status !== 'all') {
            $whereClause .= " AND u.status = ?";
            $params[] = $status;
        }
        
        // Get referrals
        $referrals = Database::fetchAll("
            SELECT 
                u.user_id,
                u.username,
                u.email,
                u.created_at,
                u.status,
                u.last_login AS last_login_at,
                r.level,
                r.commission_rate,
                COALESCE(SUM(re.amount), 0) as total_earned_from_user,
                COUNT(DISTINCT re.source_id) as activities_count
            FROM users u
            JOIN referrals r ON u.user_id = r.referred_user_id
            LEFT JOIN referral_earnings re ON r.referrer_id = re.referrer_id AND re.source_user_id = u.user_id
            $whereClause
            GROUP BY u.user_id
            ORDER BY u.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ", $params);
        
        // Get total count
        $total = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM users u
            JOIN referrals r ON u.user_id = r.referred_user_id
            $whereClause
        ", $params);
        
        $response->json([
            'success' => true,
            'data' => $referrals,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Get referral earnings via API
     */
    public function getReferralEarnings(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, (int) $request->get('limit', 20));
        $sourceType = $request->get('source_type', 'all');
        $offset = ($page - 1) * $limit;
        
        // Build query
        $whereClause = "WHERE re.referrer_id = ?";
        $params = [$userId];
        
        if ($sourceType !== 'all') {
            $whereClause .= " AND re.source_type = ?";
            $params[] = $sourceType;
        }
        
        // Get earnings
        $earnings = Database::fetchAll("
            SELECT 
                re.amount,
                re.commission_rate,
                re.source_type,
                re.source_id,
                u.username as source_username,
                re.created_at
            FROM referral_earnings re
            JOIN users u ON re.source_user_id = u.user_id
            $whereClause
            ORDER BY re.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ", $params);
        
        // Get total count
        $total = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM referral_earnings re
            JOIN users u ON re.source_user_id = u.user_id
            $whereClause
        ", $params);
        
        // Get summary statistics
        $summary = Database::fetch("
            SELECT 
                COUNT(*) as total_earnings,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN source_type = 'ad_view' THEN amount END), 0) as from_ad_views,
                COALESCE(SUM(CASE WHEN source_type = 'task' THEN amount END), 0) as from_tasks,
                COALESCE(SUM(CASE WHEN source_type = 'deposit' THEN amount END), 0) as from_deposits,
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN amount END), 0) as today_earnings
            FROM referral_earnings 
            WHERE referrer_id = ?
        ", [$userId]);
        
        $response->json([
            'success' => true,
            'data' => $earnings,
            'summary' => $summary,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Generate referral banners
     */
    public function generateBanners(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $user = Database::fetch("SELECT referral_code, username FROM users WHERE user_id = ?", [$userId]);
        
        $referralLink = Config::get('app.url') . '/register?ref=' . $user['referral_code'];
        
        // Generate different banner types
        $banners = [
            'leaderboard' => [
                'width' => 728,
                'height' => 90,
                'text' => 'Join ' . Config::get('app.name') . ' and Earn Crypto!',
                'subtext' => 'Sign up with my referral link and start earning today!'
            ],
            'medium_rectangle' => [
                'width' => 300,
                'height' => 250,
                'text' => 'Earn Free Crypto',
                'subtext' => 'Join ' . Config::get('app.name') . ' today!'
            ],
            'skyscraper' => [
                'width' => 160,
                'height' => 600,
                'text' => 'Earn Crypto',
                'subtext' => 'Free Sign Up'
            ],
            'square' => [
                'width' => 250,
                'height' => 250,
                'text' => 'Join Now',
                'subtext' => 'Earn Free Crypto'
            ]
        ];
        
        $response->json([
            'success' => true,
            'data' => [
                'referral_link' => $referralLink,
                'banners' => $banners
            ]
        ]);
    }

    /**
     * Track referral click
     */
    public function trackClick(Request $request, Response $response): void
    {
        $referralCode = $request->get('ref');
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        if (empty($referralCode)) {
            $response->redirect('/register');
            return;
        }
        
        // Validate referral code
        $referrer = Database::fetch("
            SELECT user_id, username 
            FROM users 
            WHERE referral_code = ? AND status = 'active'
        ", [$referralCode]);
        
        if (!$referrer) {
            $response->redirect('/register');
            return;
        }
        
        // Log referral click (for analytics)
        Database::insert('referral_clicks', [
            'referrer_id' => $referrer['user_id'],
            'referral_code' => $referralCode,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'landing_page' => '/register',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Store referral in session for registration
        $_SESSION['referral_code'] = $referralCode;
        
        $response->redirect('/register?ref=' . $referralCode);
    }

    /**
     * Get referral statistics dashboard
     */
    public function getReferralStats(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $period = max(1, (int) $request->get('period', '30')); // days
        
        // Get daily referral stats
        $dailyStats = Database::fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as referrals,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_referrals
            FROM users u
            JOIN referrals r ON u.user_id = r.referred_user_id
            WHERE r.referrer_id = ?
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ", [$userId]);
        
        // Get earnings by source type
        $earningsBySource = Database::fetchAll("
            SELECT 
                source_type,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total_amount
            FROM referral_earnings
            WHERE referrer_id = ?
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)
            GROUP BY source_type
        ", [$userId]);
        
        // Get top performing referrals
        $topReferrals = Database::fetchAll("
            SELECT 
                u.username,
                u.created_at,
                COALESCE(SUM(re.amount), 0) as total_earned
            FROM users u
            JOIN referrals r ON u.user_id = r.referred_user_id
            LEFT JOIN referral_earnings re ON r.referrer_id = re.referrer_id AND re.source_user_id = u.user_id
            WHERE r.referrer_id = ?
            GROUP BY u.user_id
            ORDER BY total_earned DESC
            LIMIT 10
        ", [$userId]);
        
        // Get conversion funnel
        $funnel = Database::fetch("
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT ip_address) as unique_clicks,
                (SELECT COUNT(*) FROM users u JOIN referrals r ON u.user_id = r.referred_user_id WHERE r.referrer_id = ?) as total_signups,
                (SELECT COUNT(*) FROM users u JOIN referrals r ON u.user_id = r.referred_user_id WHERE r.referrer_id = ? AND u.status = 'active') as active_users
            FROM referral_clicks
            WHERE referrer_id = ?
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)
        ", [$userId, $userId, $userId]);
        
        $response->json([
            'success' => true,
            'data' => [
                'daily_stats' => $dailyStats,
                'earnings_by_source' => $earningsBySource,
                'top_referrals' => $topReferrals,
                'funnel' => $funnel
            ]
        ]);
    }

    /**
     * Export referral data
     */
    public function exportReferralData(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $format = $request->get('format', 'csv');
        $type = $request->get('type', 'referrals');
        
        if ($type === 'referrals') {
            $data = Database::fetchAll("
                SELECT 
                    u.username,
                    u.email,
                    u.created_at,
                    u.status,
                    u.last_login AS last_login_at,
                    r.level,
                    r.commission_rate,
                    COALESCE(SUM(re.amount), 0) as total_earned_from_user
                FROM users u
                JOIN referrals r ON u.user_id = r.referred_user_id
                LEFT JOIN referral_earnings re ON r.referrer_id = re.referrer_id AND re.source_user_id = u.user_id
                WHERE r.referrer_id = ?
                GROUP BY u.user_id
                ORDER BY u.created_at DESC
            ", [$userId]);
        } else {
            $data = Database::fetchAll("
                SELECT 
                    re.amount,
                    re.commission_rate,
                    re.source_type,
                    re.source_id,
                    u.username as source_username,
                    re.created_at
                FROM referral_earnings re
                JOIN users u ON re.source_user_id = u.user_id
                WHERE re.referrer_id = ?
                ORDER BY re.created_at DESC
            ", [$userId]);
        }
        
        if ($format === 'csv') {
            $this->exportCSV($data, $type);
        } else {
            $this->exportJSON($data, $type);
        }
    }

    /**
     * Export data as CSV
     */
    private function exportCSV(array $data, string $type): void
    {
        $filename = "referral_{$type}_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            // Header row
            fputcsv($output, array_keys($data[0]));
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
    }

    /**
     * Export data as JSON
     */
    private function exportJSON(array $data, string $type): void
    {
        $filename = "referral_{$type}_" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
