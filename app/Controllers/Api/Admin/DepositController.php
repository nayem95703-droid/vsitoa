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

            $amount = (float) ($deposit['amount'] ?? 0);
            $userId = (int) $deposit['user_id'];

            Database::query(
                "UPDATE users SET earning_balance = earning_balance + ?, advisor_balance = advisor_balance + ? WHERE user_id = ?",
                [$amount, $amount, $userId]
            );

            $depositUpdate = ['status' => 'approved'];
            if ($adminNotes !== null && $adminNotes !== '') {
                $depositUpdate['admin_notes'] = $adminNotes;
            }
            Database::update('deposits', $depositUpdate, 'deposit_id = ?', [$depositId]);

            Database::query(
                "INSERT INTO wallet_transactions (user_id, type, amount, description, reference_id, reference_type) VALUES (?, 'deposit', ?, ?, ?, 'deposit')",
                [$userId, $amount, 'Deposit approved: ' . $amount . ' ' . (string) ($deposit['currency'] ?? ''), $depositId]
            );

            Database::commit();

            Database::insert('notifications', [
                'user_id' => $userId,
                'title' => 'Deposit Approved',
                'message' => 'Your deposit of ' . number_format($amount, 8) . ' ' . (string) ($deposit['currency'] ?? '') . ' has been approved.',
                'type' => 'success',
                'reference_type' => 'deposit',
                'reference_id' => $depositId
            ]);
            Database::insert('admin_notifications', [
                'user_id' => $userId,
                'message' => 'Deposit #' . $depositId . ' approved: ' . number_format($amount, 8) . ' ' . (string) ($deposit['currency'] ?? ''),
                'type' => 'deposit'
            ]);

            $response->json(['success' => true, 'message' => 'Deposit approved. Balance updated by ' . number_format($amount, 8)]);
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
