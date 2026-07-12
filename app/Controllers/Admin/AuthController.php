<?php

namespace App\Controllers\Admin;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Validator;
use Core\Logger;

class AuthController
{
    /**
     * Show admin login form
     */
    public function showLogin(Request $request, Response $response): void
    {
        if (Auth::adminCheck()) {
            $response->redirect('/admin');
            return;
        }

        include ROOT_PATH . '/views/auth/admin_login.php';
    }

    /**
     * Handle admin login
     */
    public function login(Request $request, Response $response): void
    {
        if (Auth::adminCheck()) {
            $response->json(['success' => false, 'message' => 'Already logged in']);
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'username' => 'required',
            'password' => 'required'
        ]);

        if (!$validator->validate()) {
            $response->validationError($validator->errors());
            return;
        }

        try {
            $result = Auth::adminLogin($data);

            Logger::logAdminActivity('admin_login', [
                'username' => $data['username'],
                'ip' => $request->ip()
            ]);

            $response->json([
                'success' => true,
                'message' => 'Admin login successful',
                'admin' => $result['admin'],
                'token' => $result['token']
            ]);

        } catch (\Exception $e) {
            Logger::error("Admin login failed: " . $e->getMessage());
            $response->error($e->getMessage());
        }
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request, Response $response): void
    {
        if (Auth::adminCheck()) {
            $admin = Auth::admin();
            Auth::adminLogout();

            Logger::logAdminActivity('admin_logout', [
                'admin_id' => $admin['admin_id'],
                'username' => $admin['username']
            ]);
        }

        $response->redirect('/admin/login');
    }
}
