<?php

namespace App\Controllers\Api;

use Core\Auth;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Logger;

class WalletController
{
    public function getBalance(Request $request, Response $response): void
    {
        Auth::requireAuth();

        $userId = Auth::id();
        if (!$userId) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $user = Database::fetch('SELECT * FROM users WHERE user_id = ? LIMIT 1', [$userId]) ?? [];

        $data = [
            'earning_balance' => (float) ($user['earning_balance'] ?? 0),
            'advisor_balance' => (float) ($user['advisor_balance'] ?? 0),
            'balance' => (float) ($user['balance'] ?? 0),
            'total_earned' => (float) ($user['total_earned'] ?? 0),
            'total_withdrawn' => (float) ($user['total_withdrawn'] ?? 0),
        ];

        $response->json(['success' => true, 'data' => $data]);
    }

    public function getTransactions(Request $request, Response $response): void
    {
        Auth::requireAuth();

        $userId = Auth::id();
        if (!$userId) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        if (!Database::tableExists('wallet_transactions')) {
            $response->json(['success' => true, 'data' => [], 'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1]]);
            return;
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, (int) $request->get('limit', 20));
        $offset = ($page - 1) * $limit;

        $transactions = Database::fetchAll(
            "SELECT transaction_id, type, amount, description, created_at FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
            [$userId]
        );

        $total = (int) Database::fetchColumn('SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ?', [$userId]);

        $response->json([
            'success' => true,
            'data' => $transactions,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => (int) ceil($total / $limit)
            ]
        ]);
    }

    public function createDeposit(Request $request, Response $response): void
    {
        Auth::requireAuth();
        $response->json(['success' => false, 'message' => 'Not implemented'], 501);
    }

    public function createWithdraw(Request $request, Response $response): void
    {
        Auth::requireAuth();
        $response->json(['success' => false, 'message' => 'Not implemented'], 501);
    }
}
