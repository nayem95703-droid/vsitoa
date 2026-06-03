<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Config;

class TaskController
{
    /**
     * Show tasks page
     */
    public function showTasks(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $userId = Auth::id();
        
        // Get user stats
        $stats = [
            'today_completed' => 0,
            'total_completed' => 0,
            'today_earned' => 0,
        ];
        
        // Today's completed tasks and earnings
        $todayStats = Database::fetch("
            SELECT COUNT(*) as completed, COALESCE(SUM(t.reward_amount), 0) as earned
            FROM user_tasks ut
            JOIN tasks t ON ut.task_id = t.task_id
            WHERE ut.user_id = ? AND ut.status = 'approved' AND DATE(ut.completed_at) = CURDATE()
        ", [$userId]);
        
        if ($todayStats) {
            $stats['today_completed'] = (int) $todayStats['completed'];
            $stats['today_earned'] = (float) $todayStats['earned'];
        }
        
        // Total completed tasks
        $totalCompleted = Database::fetchColumn("
            SELECT COUNT(*)
            FROM user_tasks
            WHERE user_id = ? AND status = 'approved'
        ", [$userId]);
        
        $stats['total_completed'] = (int) $totalCompleted;
        
        // Get pending tasks
        $pendingTasks = Database::fetchAll("
            SELECT 
                ut.user_task_id,
                ut.task_id,
                ut.status,
                ut.created_at,
                t.task_title,
                t.task_type,
                t.reward_amount,
                t.expires_at
            FROM user_tasks ut
            JOIN tasks t ON ut.task_id = t.task_id
            WHERE ut.user_id = ? AND ut.status = 'pending'
            ORDER BY ut.created_at DESC
        ", [$userId]);
        
        // Get available tasks
        $tasks = Database::fetchAll("
            SELECT 
                t.task_id,
                t.task_title,
                t.task_type,
                t.instructions,
                t.reward_amount,
                t.daily_limit,
                t.total_limit,
                t.expires_at,
                ut.status as user_status,
                ut.completed_at as last_completed
            FROM tasks t
            LEFT JOIN user_tasks ut ON t.task_id = ut.task_id AND ut.user_id = ? 
                AND DATE(ut.created_at) = CURDATE()
            WHERE t.status = 'active' 
                AND (t.expires_at IS NULL OR t.expires_at > NOW())
            ORDER BY t.created_at DESC
        ", [$userId]);
        
        include ROOT_PATH . '/views/user/tasks.php';
    }
}
