<?php

namespace App\Controllers\Api\Admin;

use Core\Auth;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Logger;

class NotificationController
{
    public function getNotifications(Request $request, Response $response): void
    {
        Auth::requireAdmin();

        $page = max(1, (int) $request->get('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $notifications = Database::fetchAll(
            "SELECT id, user_id, message, type, is_read, created_at 
             FROM admin_notifications 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );

        $unreadCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");

        $response->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    public function createNotification(Request $request, Response $response): void
    {
        Auth::requireAdmin();

        $data = $request->all();
        $message = $data['message'] ?? '';
        $type = $data['type'] ?? 'info';
        $userId = $data['user_id'] ?? null;

        if (empty($message)) {
            $response->json(['success' => false, 'message' => 'Message is required'], 422);
            return;
        }

        try {
            $id = Database::insert('admin_notifications', [
                'user_id' => $userId ? (int) $userId : null,
                'message' => $message,
                'type' => $type
            ]);
            $response->json(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            Logger::error('createNotification error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to create notification'], 500);
        }
    }

    public function deleteNotification(Request $request, Response $response, string $id): void
    {
        Auth::requireAdmin();

        try {
            Database::delete('admin_notifications', 'id = ?', [(int) $id]);
            $response->json(['success' => true, 'message' => 'Notification deleted']);
        } catch (\Exception $e) {
            Logger::error('deleteNotification error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to delete notification'], 500);
        }
    }

    public function markAllRead(Request $request, Response $response): void
    {
        Auth::requireAdmin();

        try {
            Database::query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
            $response->json(['success' => true, 'message' => 'All notifications marked as read']);
        } catch (\Exception $e) {
            Logger::error('markAllRead error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to mark notifications'], 500);
        }
    }

    public function clearNotifications(Request $request, Response $response): void
    {
        Auth::requireAdmin();

        try {
            Database::query("DELETE FROM admin_notifications WHERE is_read = 1");
            $response->json(['success' => true, 'message' => 'Read notifications cleared']);
        } catch (\Exception $e) {
            Logger::error('clearNotifications error: ' . $e->getMessage());
            $response->json(['success' => false, 'message' => 'Failed to clear notifications'], 500);
        }
    }
}
