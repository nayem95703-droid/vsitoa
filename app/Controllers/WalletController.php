<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Config;
use Core\Database;
use Core\Validator;
use Core\Mailer;
use Core\Logger;

class WalletController
{
    /**
     * Show wallet page
     */
    public function showWallet(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get wallet balance
        $user = Database::fetch("SELECT earning_balance, advisor_balance, total_earned, total_withdrawn FROM users WHERE user_id = ?", [$userId]);
        
        // Get recent transactions
        $transactions = Database::fetchAll("
            SELECT 
                type,
                amount,
                description,
                created_at
            FROM wallet_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ", [$userId]);
        
        // Get pending deposits
        $pendingDeposits = Database::fetchAll("
            SELECT 
                deposit_id,
                currency,
                amount,
                status,
                created_at
            FROM deposits 
            WHERE user_id = ? AND status = 'pending'
            ORDER BY created_at DESC
        ", [$userId]);
        
        // Get pending withdrawals
        $pendingWithdrawals = Database::fetchAll("
            SELECT 
                withdrawal_id,
                currency,
                amount,
                status,
                created_at
            FROM withdrawals 
            WHERE user_id = ? AND status IN ('pending', 'processing')
            ORDER BY created_at DESC
        ", [$userId]);
        
        include ROOT_PATH . '/views/user/wallet.php';
    }

    /**
     * Get wallet balance via API
     */
    public function getBalance(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        $user = Database::fetch("
            SELECT earning_balance, advisor_balance, total_earned, total_withdrawn 
            FROM users 
            WHERE user_id = ?
        ", [$userId]);
        
        $response->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Get wallet transactions via API
     */
    public function getTransactions(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, (int) $request->get('limit', 20));
        $type = $request->get('type', 'all');
        $offset = ($page - 1) * $limit;
        
        // Build query
        $whereClause = "WHERE user_id = ?";
        $params = [$userId];
        
        if ($type !== 'all') {
            $whereClause .= " AND type = ?";
            $params[] = $type;
        }
        
        // Get transactions
        $transactions = Database::fetchAll("
            SELECT 
                transaction_id,
                type,
                amount,
                description,
                reference_id,
                reference_type,
                created_at
            FROM wallet_transactions 
            $whereClause
            ORDER BY created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ", $params);
        
        // Get total count
        $total = Database::fetchColumn("
            SELECT COUNT(*) 
            FROM wallet_transactions 
            $whereClause
        ", $params);
        
        $response->json([
            'success' => true,
            'data' => $transactions,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Show deposit page
     */
    public function showDeposit(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        // Get supported currencies and wallet addresses
        $currencies = [
            'BTC' => Config::get('crypto.btc_wallet'),
            'TRX' => Config::get('crypto.trx_wallet'),
            'ETH' => Config::get('crypto.eth_wallet'),
            'USDT' => Config::get('crypto.usdt_wallet')
        ];
        
        // Get deposit history
        $userId = Auth::id();
        $deposits = Database::fetchAll("
            SELECT 
                deposit_id,
                currency,
                amount,
                txid,
                status,
                admin_notes,
                created_at,
                updated_at
            FROM deposits 
            WHERE user_id = ? 
            ORDER BY created_at DESC
            LIMIT 20
        ", [$userId]);
        
        include ROOT_PATH . '/views/user/deposit.php';
    }

    /**
     * Create deposit request
     */
    public function createDeposit(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $data = $request->all();
        
        // Validate input
        $validator = Validator::make($data, [
            'currency' => 'required|in:BTC,TRX,ETH,USDT',
            'amount' => 'required|numeric|min:0.00000001',
            'txid' => 'nullable|string|max:255'
        ], [
            'currency.in' => 'Invalid currency selected',
            'amount.min' => 'Amount must be greater than 0'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please fill all deposit fields correctly.';
            $response->redirect('/deposit');
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Get wallet address for selected currency
            $walletAddresses = [
                'BTC' => Config::get('crypto.btc_wallet'),
                'TRX' => Config::get('crypto.trx_wallet'),
                'ETH' => Config::get('crypto.eth_wallet'),
                'USDT' => Config::get('crypto.usdt_wallet')
            ];
            
            $walletAddress = $walletAddresses[$data['currency']] ?? '';
            
            if (empty($walletAddress)) {
                Database::rollback();
                $_SESSION['flash_error'] = 'Selected currency is not available.';
                $response->redirect('/deposit');
                return;
            }
            
            // Create deposit record
            $depositId = Database::insert('deposits', [
                'user_id' => $userId,
                'currency' => $data['currency'],
                'amount' => $data['amount'],
                'wallet_address' => $walletAddress,
                'txid' => $data['txid'] ?? null,
                'status' => 'pending'
            ]);
            
            Database::commit();
            
            Logger::logUserActivity('deposit_created', [
                'deposit_id' => $depositId,
                'currency' => $data['currency'],
                'amount' => $data['amount']
            ]);
            
            $_SESSION['flash_success'] = 'Deposit request created successfully.';
            $response->redirect('/deposit');
            
        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Create deposit error: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Failed to create deposit request.';
            $response->redirect('/deposit');
        }
    }

    /**
     * Transfer earning balance to advisor balance
     */
    public function transferToAdvisor(Request $request, Response $response): void
    {
        Auth::requireAuth();

        $userId = Auth::id();
        $data = $request->all();

        $validator = Validator::make($data, [
            'amount' => 'required|numeric|min:0.01'
        ]);

        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please enter a valid amount.';
            $response->redirect('/wallet');
            return;
        }

        try {
            Database::beginTransaction();

            $user = Database::fetch("SELECT earning_balance, advisor_balance FROM users WHERE user_id = ? FOR UPDATE", [$userId]);

            if ($user['earning_balance'] < $data['amount']) {
                Database::rollback();
                $_SESSION['flash_error'] = 'Insufficient earning balance.';
                $response->redirect('/wallet');
                return;
            }

            $newEarning = $user['earning_balance'] - $data['amount'];
            $newAdvisor = $user['advisor_balance'] + $data['amount'];

            Database::update(
                'users',
                ['earning_balance' => $newEarning, 'advisor_balance' => $newAdvisor],
                'user_id = ?',
                [$userId]
            );

            Database::insert('wallet_transactions', [
                'user_id' => $userId,
                'type' => 'transfer_to_advisor',
                'amount' => -$data['amount'],
                'balance_before' => $user['earning_balance'],
                'balance_after' => $newEarning,
                'description' => "Transfer to advisor balance: {$data['amount']} USDT",
                'reference_id' => null,
                'reference_type' => 'transfer'
            ]);

            Database::commit();

            $_SESSION['flash_success'] = 'Transfer completed successfully.';
            $response->redirect('/wallet');
        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Transfer to advisor error: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Transfer failed. Please try again.';
            $response->redirect('/wallet');
        }
    }

    /**
     * Show manage ads page
     */
    public function showManageAds(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get user balance
        $user = Database::fetch("SELECT earning_balance, advisor_balance FROM users WHERE user_id = ?", [$userId]);
        
        // Get ad statistics
        $stats = Database::fetch("
            SELECT 
                COUNT(*) as total_ads,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_ads,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_ads,
                COUNT(CASE WHEN status = 'paused' THEN 1 END) as paused_ads,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_ads,
                COALESCE(SUM(spent_amount), 0) as total_spent
            FROM ads 
            WHERE user_id = ?
        ", [$userId]);
        
        // Get user's advertisements
        $ads = Database::fetchAll("
            SELECT 
                ad_id,
                ad_title as title,
                ad_category as category,
                ad_type,
                target_url,
                cost_per_view,
                total_views,
                (total_views - remaining_views) as completed_views,
                status,
                total_budget as total_cost,
                created_at,
                completed_at as expires_at
            FROM ads 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ", [$userId]);
        
        include ROOT_PATH . '/views/user/manage_ads.php';
    }

    /**
     * Show create ad page
     */
    public function showCreateAd(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get user balance
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
     * Show withdrawal page
     */
    public function showWithdraw(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get user balance
        $user = Database::fetch("SELECT earning_balance, advisor_balance, total_withdrawn FROM users WHERE user_id = ?", [$userId]);
        
        // Get minimum withdrawal amount
        $minimumWithdrawal = Config::get('rates.minimum_withdrawal');
        
        // Get withdrawal history
        $withdrawals = Database::fetchAll("
            SELECT 
                withdrawal_id,
                currency,
                amount,
                wallet_address,
                status,
                txid,
                admin_notes,
                created_at,
                processed_at
            FROM withdrawals 
            WHERE user_id = ? 
            ORDER BY created_at DESC
            LIMIT 20
        ", [$userId]);
        
        include ROOT_PATH . '/views/user/withdraw.php';
    }

    /**
     * Create withdrawal request
     */
    public function createWithdrawal(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        $data = $request->all();
        
        // Validate input
        $validator = Validator::make($data, [
            'currency' => 'required|in:BTC,TRX,ETH,USDT',
            'amount' => 'required|numeric|min:' . Config::get('rates.minimum_withdrawal'),
            'wallet_address' => 'required|string|max:255'
        ], [
            'currency.in' => 'Invalid currency selected',
            'amount.min' => 'Minimum withdrawal amount is ' . Config::get('rates.minimum_withdrawal'),
            'wallet_address.required' => 'Wallet address is required'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please fill all withdrawal fields correctly.';
            $response->redirect('/withdraw');
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Get user's current balance
            $user = Database::fetch("SELECT earning_balance FROM users WHERE user_id = ? FOR UPDATE", [$userId]);
            
            // Check if user has sufficient balance
            if ($user['earning_balance'] < $data['amount']) {
                Database::rollback();
                $_SESSION['flash_error'] = 'Insufficient balance.';
                $response->redirect('/withdraw');
                return;
            }
            
            // Check for pending withdrawals
            $pendingWithdrawals = Database::fetchColumn("
                SELECT COUNT(*) 
                FROM withdrawals 
                WHERE user_id = ? AND status IN ('pending', 'processing')
            ", [$userId]);
            
            if ($pendingWithdrawals >= 3) {
                Database::rollback();
                $_SESSION['flash_error'] = 'You have too many pending withdrawal requests.';
                $response->redirect('/withdraw');
                return;
            }

            // Deduct amount from user earning balance
            $newBalance = $user['earning_balance'] - $data['amount'];
            Database::update(
                'users',
                ['earning_balance' => $newBalance],
                'user_id = ?',
                [$userId]
            );

            // Create withdrawal record
            $withdrawalId = Database::insert('withdrawals', [
                'user_id' => $userId,
                'currency' => $data['currency'],
                'amount' => $data['amount'],
                'wallet_address' => $data['wallet_address'],
                'status' => 'pending'
            ]);

            // Create wallet transaction
            Database::insert('wallet_transactions', [
                'user_id' => $userId,
                'type' => 'withdraw',
                'amount' => -$data['amount'],
                'balance_before' => $user['earning_balance'],
                'balance_after' => $newBalance,
                'description' => "Withdrawal request: {$data['amount']} {$data['currency']}",
                'reference_id' => $withdrawalId,
                'reference_type' => 'withdrawal'
            ]);
            
            Database::commit();
            
            Logger::logUserActivity('withdrawal_created', [
                'withdrawal_id' => $withdrawalId,
                'currency' => $data['currency'],
                'amount' => $data['amount'],
                'wallet_address' => $data['wallet_address']
            ]);
            
            $_SESSION['flash_success'] = 'Withdrawal request created successfully.';
            $response->redirect('/withdraw');
            
        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Create withdrawal error: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Failed to create withdrawal request.';
            $response->redirect('/withdraw');
        }
    }

    /**
     * Get wallet statistics
     */
    public function getWalletStats(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get overall stats
        $stats = Database::fetch("
            SELECT 
                u.balance,
                u.total_earned,
                u.total_withdrawn,
                (SELECT COUNT(*) FROM deposits WHERE user_id = ? AND status = 'approved') as total_deposits,
                (SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND status = 'paid') as total_withdrawals_completed,
                (SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE user_id = ? AND status = 'approved') as total_deposited,
                (SELECT COUNT(*) FROM deposits WHERE user_id = ? AND status = 'pending') as pending_deposits,
                (SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND status IN ('pending', 'processing')) as pending_withdrawals
            FROM users u
            WHERE u.user_id = ?
        ", [$userId, $userId, $userId, $userId, $userId, $userId]);
        
        // Get earnings by source
        $earningsBySource = Database::fetchAll("
            SELECT 
                source,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total
            FROM earnings 
            WHERE user_id = ?
            GROUP BY source
        ", [$userId]);
        
        // Get recent activity
        $recentActivity = Database::fetchAll("
            SELECT 
                type,
                amount,
                description,
                created_at
            FROM wallet_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ", [$userId]);
        
        $response->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'earnings_by_source' => $earningsBySource,
                'recent_activity' => $recentActivity
            ]
        ]);
    }

    /**
     * Validate wallet address
     */
    public function validateWalletAddress(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $currency = $request->post('currency');
        $address = $request->post('address');
        
        if (empty($currency) || empty($address)) {
            $response->error('Currency and address are required', 400);
            return;
        }
        
        $isValid = false;
        
        // Basic validation based on currency
        switch ($currency) {
            case 'BTC':
                $isValid = (preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$|^bc1[a-z0-9]{8,87}$/', $address) === 1);
                break;
            case 'TRX':
                $isValid = (preg_match('/^T[A-Za-z1-9]{33}$/', $address) === 1);
                break;
            case 'ETH':
                $isValid = (preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1);
                break;
            case 'USDT':
                // USDT can be on multiple networks, accept common formats
                $isValid = (
                    preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1 || // ERC20
                    preg_match('/^T[A-Za-z1-9]{33}$/', $address) === 1     // TRC20
                );
                break;
        }
        
        $response->json([
            'success' => true,
            'data' => [
                'valid' => $isValid,
                'message' => $isValid ? 'Valid wallet address' : 'Invalid wallet address format'
            ]
        ]);
    }
}
