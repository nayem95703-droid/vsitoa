<?php

namespace App\Controllers\Api;

use Core\Auth;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Validator;
use Core\Logger;

class UserController
{
    public function profile(Request $request, Response $response): void
    {
        Auth::requireAuth();

        $userId = Auth::id();
        if (!$userId) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $user = Database::fetch('SELECT * FROM users WHERE user_id = ? LIMIT 1', [$userId]);
        if (!$user) {
            $response->json(['success' => false, 'message' => 'User not found'], 404);
            return;
        }

        $data = [
            'user_id' => $user['user_id'] ?? $userId,
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
            'status' => $user['status'] ?? null,
            'earning_balance' => (float) ($user['earning_balance'] ?? 0),
            'advisor_balance' => (float) ($user['advisor_balance'] ?? 0),
            'balance' => (float) ($user['balance'] ?? 0),
            'total_earned' => (float) ($user['total_earned'] ?? 0),
            'total_withdrawn' => (float) ($user['total_withdrawn'] ?? 0),
            'is_verified' => (bool) ($user['is_verified'] ?? false),
            'verified_at' => $user['verified_at'] ?? null,
        ];

        $response->json(['success' => true, 'data' => $data]);
    }

    public function updateProfile(Request $request, Response $response): void
    {
        Auth::requireAuth();

        $userId = Auth::id();
        if (!$userId) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'username' => 'nullable|min:3|max:50',
            'email' => 'nullable|email|max:255'
        ]);

        if (!$validator->validate()) {
            $response->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            return;
        }

        $update = [];
        if (isset($data['username']) && $data['username'] !== '') {
            $update['username'] = $data['username'];
        }
        if (isset($data['email']) && $data['email'] !== '') {
            $update['email'] = $data['email'];
        }

        if (empty($update)) {
            $response->json(['success' => true, 'message' => 'Nothing to update']);
            return;
        }

        try {
            Database::update('users', $update, 'user_id = ?', [$userId]);
            $response->json(['success' => true, 'message' => 'Profile updated']);
        } catch (\Exception $e) {
            Logger::error('API updateProfile error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to update profile'], 500);
        }
    }

    public function updatePassword(Request $request, Response $response): void
    {
        Auth::requireAuth();

        $userId = Auth::id();
        if (!$userId) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $data = $request->all();
        $validator = Validator::make($data, [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password'
        ]);

        if (!$validator->validate()) {
            $response->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            return;
        }

        $user = Database::fetch('SELECT password FROM users WHERE user_id = ? LIMIT 1', [$userId]);
        if (!$user || !password_verify((string) $data['current_password'], (string) ($user['password'] ?? ''))) {
            $response->json(['success' => false, 'message' => 'Current password is incorrect'], 400);
            return;
        }

        try {
            Database::update('users', ['password' => password_hash((string) $data['new_password'], PASSWORD_DEFAULT)], 'user_id = ?', [$userId]);
            $response->json(['success' => true, 'message' => 'Password updated']);
        } catch (\Exception $e) {
            Logger::error('API updatePassword error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to update password'], 500);
        }
    }

    public function getStats(Request $request, Response $response): void
    {
        Auth::requireAuth();
        $response->json(['success' => true, 'data' => []]);
    }

    public function getEarnings(Request $request, Response $response): void
    {
        Auth::requireAuth();
        $response->json(['success' => true, 'data' => []]);
    }

    public function getEarningsSources(Request $request, Response $response): void
    {
        Auth::requireAuth();
        $response->json(['success' => true, 'data' => []]);
    }

    public function getNotifications(Request $request, Response $response): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        try {
            $notifications = Database::fetchAll(
                "SELECT id, title, message, type, is_read, reference_type, reference_id, created_at 
                 FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
                [$userId]
            );
            $response->json(['success' => true, 'data' => $notifications]);
        } catch (\Exception $e) {
            Logger::error('getNotifications error: ' . $e->getMessage());
            $response->json(['success' => true, 'data' => []]);
        }
    }

    public function markNotificationRead(Request $request, Response $response, string $id): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        try {
            Database::update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [(int) $id, $userId]);
            $response->json(['success' => true, 'message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            Logger::error('markNotificationRead error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to mark notification'], 500);
        }
    }
}
