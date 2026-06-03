<?php

namespace App\Controllers\Api;

use Core\Auth;
use Core\Database;
use Core\Request;
use Core\Response;

class TaskController
{
    public function getTasks(Request $request, Response $response): void
    {
        Auth::requireAuth();

        // If your DB has a tasks table, return a basic list; otherwise return empty.
        if (!Database::tableExists('tasks')) {
            $response->json(['success' => true, 'data' => []]);
            return;
        }

        $tasks = Database::fetchAll("SELECT * FROM tasks WHERE status = 'active' ORDER BY created_at DESC LIMIT 50");
        $response->json(['success' => true, 'data' => $tasks]);
    }

    public function getTask(Request $request, Response $response, string $id = null): void
    {
        Auth::requireAuth();

        if (!Database::tableExists('tasks')) {
            $response->json(['success' => false, 'message' => 'Tasks not available'], 404);
            return;
        }

        $taskId = $id ?? (string) $request->param('id');
        $task = Database::fetch('SELECT * FROM tasks WHERE id = ? LIMIT 1', [$taskId]);
        if (!$task) {
            $response->json(['success' => false, 'message' => 'Task not found'], 404);
            return;
        }

        $response->json(['success' => true, 'data' => $task]);
    }

    public function completeTask(Request $request, Response $response, string $id = null): void
    {
        Auth::requireAuth();
        $response->json(['success' => false, 'message' => 'Not implemented'], 501);
    }

    public function getProviders(Request $request, Response $response): void
    {
        Auth::requireAuth();
        $response->json(['success' => true, 'data' => []]);
    }

    public function getProviderOffers(Request $request, Response $response, string $providerId = null): void
    {
        Auth::requireAuth();
        $response->json(['success' => true, 'data' => []]);
    }

    public function completeOffer(Request $request, Response $response): void
    {
        Auth::requireAuth();
        $response->json(['success' => false, 'message' => 'Not implemented'], 501);
    }
}
