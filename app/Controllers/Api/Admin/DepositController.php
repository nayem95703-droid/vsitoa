<?php

namespace App\Controllers\Api\Admin;

use Core\Auth;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Logger;

class DepositController
{
    public function getDeposits(Request $request, Response $response): void
    {
        Auth::requireAdmin();

        $status = $request->get('status', 'pending');
        $page = max(1, (int) $request->get('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $deposits = Database::fetchAll(
            "SELECT d.deposit_id, d.user_id, u.username, u.email, d.currency, d.amount, d.txid, d.status, d.admin_notes, d.created_at 
             FROM deposits d 
             JOIN users u ON d.user_id = u.user_id 
             WHERE d.status = ? 
             ORDER BY d.created_at DESC 
             LIMIT ? OFFSET ?",
            [$status, $limit, $offset]
        );

        $counts = [
            'pending' => (int) Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'pending'"),
            'approved' => (int) Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'approved'"),
            'rejected' => (int) Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'rejected'"),
        ];

        $response->json(['success' => true, 'data' => $deposits, 'counts' => $counts]);
    }

    public function approveDeposit(Request $request, Response $response, string $id): void
    {
        Auth::requireAdmin();
        $depositId = (int) $id;
        $data = $request->all();
        $adminNotes = $data['admin_notes'] ?? null;

        try {
            Database::beginTransaction();

            $deposit = Database::fetch(
                "SELECT deposit_id, user_id, currency, amount, status FROM deposits WHERE deposit_id = ? FOR UPDATE",
                [$depositId]
            );

            if (!$deposit) {
                Database::rollback();
                $response->json(['success' => false, 'message' => 'Deposit not found'], 404);
                return;
            }

            if (($deposit['status'] ?? '') !== 'pending') {
                Database::rollback();
                $response->json(['success' => false, 'message' => 'This deposit has already been processed'], 400);
                return;
            }

            $user = Database::fetch(
                "SELECT balance, advisor_balance FROM users WHERE user_id = ? FOR UPDATE",
                [(int) $deposit['user_id']]
            );

            if (!$user) {
                Database::rollback();
                $response->json(['success' => false, 'message' => 'User not found'], 404);
                return;
            }

            $balanceBefore = (float) ($user['balance'] ?? 0);
            $advisorBalanceBefore = (float) ($user['advisor_balance'] ?? 0);
            $amount = (float) ($deposit['amount'] ?? 0);
            $balanceAfter = $balanceBefore + $amount;
            $advisorBalanceAfter = $advisorBalanceBefore + $amount;

            Database::update(
                'users',
                [
                    'balance' => $balanceAfter,
                    'advisor_balance' => $advisorBalanceAfter,
                ],
                'user_id = ?',
                [(int) $deposit['user_id']]
            );

            $depositUpdate = ['status' => 'approved'];
            if ($adminNotes !== null && $adminNotes !== '') {
                $depositUpdate['admin_notes'] = $adminNotes;
            }

            Database::update('deposits', $depositUpdate, 'deposit_id = ?', [$depositId]);

            Database::insert('wallet_transactions', [
                'user_id' => (int) $deposit['user_id'],
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'Deposit approved: ' . $amount . ' ' . (string) ($deposit['currency'] ?? ''),
                'reference_id' => $depositId,
                'reference_type' => 'deposit'
            ]);

            Database::insert('notifications', [
                'user_id' => (int) $deposit['user_id'],
                'title' => 'Deposit Approved',
                'message' => 'Your deposit of ' . number_format($amount, 8) . ' ' . (string) ($deposit['currency'] ?? '') . ' has been approved and added to your balance.',
                'type' => 'success',
                'reference_type' => 'deposit',
                'reference_id' => $depositId
            ]);

            Database::insert('admin_notifications', [
                'user_id' => (int) $deposit['user_id'],
                'message' => 'Deposit #' . $depositId . ' approved: ' . number_format($amount, 8) . ' ' . (string) ($deposit['currency'] ?? '') . ' added to user balance.',
                'type' => 'deposit'
            ]);

            Database::commit();
            $response->json(['success' => true, 'message' => 'Deposit approved successfully']);
        } catch (\Exception $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            Logger::error('API approveDeposit error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to approve deposit'], 500);
        }
    }

    public function rejectDeposit(Request $request, Response $response, string $id): void
    {
        Auth::requireAdmin();
        $depositId = (int) $id;
        $data = $request->all();
        $adminNotes = $data['admin_notes'] ?? null;

        try {
            Database::beginTransaction();

            $deposit = Database::fetch(
                "SELECT deposit_id, user_id, currency, amount, status FROM deposits WHERE deposit_id = ? FOR UPDATE",
                [$depositId]
            );

            if (!$deposit) {
                Database::rollback();
                $response->json(['success' => false, 'message' => 'Deposit not found'], 404);
                return;
            }

            if (($deposit['status'] ?? '') !== 'pending') {
                Database::rollback();
                $response->json(['success' => false, 'message' => 'This deposit has already been processed'], 400);
                return;
            }

            $depositUpdate = ['status' => 'rejected'];
            if ($adminNotes !== null && $adminNotes !== '') {
                $depositUpdate['admin_notes'] = $adminNotes;
            }

            Database::update('deposits', $depositUpdate, 'deposit_id = ?', [$depositId]);

            Database::insert('notifications', [
                'user_id' => (int) $deposit['user_id'],
                'title' => 'Deposit Rejected',
                'message' => 'Your deposit of ' . number_format((float) ($deposit['amount'] ?? 0), 8) . ' ' . (string) ($deposit['currency'] ?? '') . ' has been rejected.' . ($adminNotes ? ' Reason: ' . $adminNotes : ''),
                'type' => 'danger',
                'reference_type' => 'deposit',
                'reference_id' => $depositId
            ]);

            Database::insert('admin_notifications', [
                'user_id' => (int) $deposit['user_id'],
                'message' => 'Deposit #' . $depositId . ' rejected: ' . number_format((float) ($deposit['amount'] ?? 0), 8) . ' ' . (string) ($deposit['currency'] ?? '') . '.' . ($adminNotes ? ' Reason: ' . $adminNotes : ''),
                'type' => 'warning'
            ]);

            Database::commit();
            $response->json(['success' => true, 'message' => 'Deposit rejected successfully']);
        } catch (\Exception $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            Logger::error('API rejectDeposit error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to reject deposit'], 500);
        }
    }
}
