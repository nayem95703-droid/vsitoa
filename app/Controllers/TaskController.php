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
        
        $stats = [
            'today_completed' => 0,
            'total_completed' => 0,
            'today_earned' => 0,
            'pending_count' => 0,
        ];
        
        $todayStats = Database::fetch("
            SELECT COUNT(*) as completed, COALESCE(SUM(t.payment_per_execution), 0) as earned
            FROM user_tasks ut
            JOIN tasks t ON ut.task_id = t.id
            WHERE ut.user_id = ? AND ut.status = 'approved' AND DATE(ut.completed_at) = CURDATE()
        ", [$userId]);
        
        if ($todayStats) {
            $stats['today_completed'] = (int) $todayStats['completed'];
            $stats['today_earned'] = (float) $todayStats['earned'];
        }
        
        $totalCompleted = Database::fetchColumn("
            SELECT COUNT(*)
            FROM user_tasks
            WHERE user_id = ? AND status = 'approved'
        ", [$userId]);
        
        $stats['total_completed'] = (int) $totalCompleted;

        $stats['pending_count'] = (int) Database::fetchColumn("
            SELECT COUNT(*) FROM user_tasks
            WHERE user_id = ? AND status IN ('started','submitted')
        ", [$userId]);
        
        $pendingTasks = Database::fetchAll("
            SELECT 
                ut.id as user_task_id,
                ut.task_id,
                ut.status,
                ut.started_at,
                ut.expires_at,
                t.title as task_title,
                t.ad_type as task_type,
                t.payment_per_execution as reward_amount,
                t.description,
                t.target_website_url
            FROM user_tasks ut
            JOIN tasks t ON ut.task_id = t.id
            WHERE ut.user_id = ? AND ut.status IN ('started','submitted')
            ORDER BY ut.started_at DESC
        ", [$userId]);
        
        $tasks = Database::fetchAll("
            SELECT 
                t.id as task_id,
                t.title as task_title,
                t.ad_type as task_type,
                t.description,
                t.payment_per_execution as reward_amount,
                t.max_executions,
                t.current_executions,
                t.max_completion_time,
                t.target_website_url,
                t.category,
                ut.status as user_status,
                ut.started_at as last_started
            FROM tasks t
            LEFT JOIN user_tasks ut ON t.id = ut.task_id AND ut.user_id = ? 
                AND DATE(ut.started_at) = CURDATE()
                AND ut.status IN ('started','submitted','approved')
            WHERE t.status = 'active'
            ORDER BY t.created_at DESC
        ", [$userId]);
        
        include ROOT_PATH . '/views/user/tasks.php';
    }

    /**
     * Show task execution page
     */
    public function executeTask(Request $request, Response $response): void
    {
        Auth::requireAuth();

        $userId = Auth::id();
        $taskId = (int) $request->param('id');

        $task = Database::fetch("SELECT * FROM tasks WHERE id = ? AND status = 'active'", [$taskId]);
        if (!$task) {
            $_SESSION['flash_error'] = 'Task not found.';
            $response->redirect('/tasks');
            return;
        }

        $userTask = Database::fetch("
            SELECT * FROM user_tasks 
            WHERE user_id = ? AND task_id = ? AND DATE(started_at) = CURDATE()
            ORDER BY id DESC LIMIT 1
        ", [$userId, $taskId]);

        if ($userTask && $userTask['status'] === 'approved') {
            $_SESSION['flash_error'] = 'You already completed this task today.';
            $response->redirect('/tasks');
            return;
        }

        if ($userTask && $userTask['status'] === 'submitted') {
            $_SESSION['flash_error'] = 'You already submitted proof for this task. Wait for review.';
            $response->redirect('/tasks');
            return;
        }

        if ($userTask && $userTask['status'] === 'started') {
            $expiresAt = strtotime($userTask['expires_at']);
            if (time() > $expiresAt) {
                Database::update('user_tasks', ['status' => 'expired'], 'id = ?', [$userTask['id']]);
                $userTask = null;
            }
        }

        if (!$userTask || $userTask['status'] === 'expired' || $userTask['status'] === 'refused') {
            $maxTime = (int) ($task['max_completion_time'] ?? 30);
            $expiresAt = date('Y-m-d H:i:s', time() + ($maxTime * 60));

            Database::insert('user_tasks', [
                'user_id' => $userId,
                'task_id' => $taskId,
                'status' => 'started',
                'expires_at' => $expiresAt
            ]);

            $userTask = Database::fetch("
                SELECT * FROM user_tasks 
                WHERE user_id = ? AND task_id = ? AND status = 'started'
                ORDER BY id DESC LIMIT 1
            ", [$userId, $taskId]);
        }

        $existingSubmission = null;
        if ($userTask) {
            $existingSubmission = Database::fetch("
                SELECT * FROM submissions 
                WHERE task_id = ? AND worker_id = ? 
                ORDER BY id DESC LIMIT 1
            ", [$taskId, $userId]);
        }

        include ROOT_PATH . '/views/user/execute_task.php';
    }
}
