<?php

/**
 * Web Routes
 * Handles all web page requests
 */

// Home page
$router->get('/', function($request, $response) {
    include ROOT_PATH . '/views/home.php';
});

// Authentication Routes
$router->get('/register', ['App\Controllers\AuthController', 'showRegister']);
$router->post('/register', ['App\Controllers\AuthController', 'register']);
$router->get('/login', ['App\Controllers\AuthController', 'showLogin']);
$router->post('/login', ['App\Controllers\AuthController', 'login']);
$router->post('/logout', ['App\Controllers\AuthController', 'logout']);
$router->get('/forgot-password', ['App\Controllers\AuthController', 'showForgotPassword']);
$router->post('/forgot-password', ['App\Controllers\AuthController', 'forgotPassword']);
$router->get('/reset-password', ['App\Controllers\AuthController', 'showResetPassword']);
$router->post('/reset-password', ['App\Controllers\AuthController', 'resetPassword']);
$router->get('/verify-email', ['App\Controllers\AuthController', 'verifyEmail']);
$router->post('/resend-verification', ['App\Controllers\AuthController', 'resendVerification']);

// Legacy .php entrypoints (compatibility)
$router->get('/register.php', function($request, $response) {
    $response->redirect('/register');
});
$router->get('/login.php', function($request, $response) {
    $response->redirect('/login');
});

// Admin Authentication Routes
$router->get('/admin/login', ['App\Controllers\AuthController', 'showAdminLogin']);
$router->post('/admin/login', ['App\Controllers\AuthController', 'adminLogin']);
$router->post('/admin/logout', ['App\Controllers\AuthController', 'adminLogout']);
$router->get('/admin/logout', ['App\Controllers\AuthController', 'adminLogout']);

// Protected User Routes (require authentication)
$router->get('/dashboard', function($request, $response) {
    try {
        \Core\Auth::requireAuth();
    } catch (\Throwable $e) {
        $response->redirect('/login');
    }

    if (!\Core\Auth::check()) {
        $response->redirect('/login');
    }

    try {
        include ROOT_PATH . '/views/user/dashboard.php';
    } catch (\Throwable $e) {
        if (class_exists(\Core\Logger::class)) {
            \Core\Logger::error('Dashboard render failed: ' . $e->getMessage());
        }
        $response->redirect('/login');
    }
});

$router->get('/earn', ['App\Controllers\AdController', 'showEarnPage']);

$router->post('/earn/report-ad', function($request, $response) {
    \Core\Auth::requireAuth();
    $userId = \Core\Auth::id();
    $data = $request->all();
    $adId = (int) ($data['ad_id'] ?? 0);
    $reason = trim($data['reason'] ?? 'inappropriate');
    $details = trim($data['details'] ?? '');

    if ($adId <= 0) {
        $response->json(['success' => false, 'message' => 'Invalid ad.']);
        return;
    }

    try {
        $ad = \Core\Database::fetch("SELECT ad_id FROM ads WHERE ad_id = ?", [$adId]);
        if (!$ad) {
            $response->json(['success' => false, 'message' => 'Ad not found.']);
            return;
        }

        $existing = \Core\Database::fetch(
            "SELECT id FROM ad_reports WHERE ad_id = ? AND reported_by = ? AND status = 'pending'",
            [$adId, $userId]
        );
        if ($existing) {
            $response->json(['success' => false, 'message' => 'You already reported this ad.']);
            return;
        }

        \Core\Database::insert('ad_reports', [
            'ad_id' => $adId,
            'reported_by' => $userId,
            'reason' => $reason,
            'details' => $details !== '' ? $details : null
        ]);

        $reportCount = (int) \Core\Database::fetchColumn(
            "SELECT COUNT(*) FROM ad_reports WHERE ad_id = ? AND status = 'pending'",
            [$adId]
        );

        if ($reportCount >= 3) {
            \Core\Database::update('ads', ['status' => 'blocked'], 'ad_id = ?', [$adId]);
        }

        \Core\Database::insert('admin_notifications', [
            'user_id' => null,
            'message' => 'Ad #' . $adId . ' reported for: ' . $reason . ($details ? ' - ' . $details : ''),
            'type' => 'warning'
        ]);

        $response->json(['success' => true, 'message' => 'Report submitted. Thank you for keeping our platform safe.']);
    } catch (\Throwable $e) {
        $response->json(['success' => false, 'message' => 'Failed to submit report.']);
    }
});

$router->get('/tasks', ['App\Controllers\TaskController', 'showTasks']);
$router->get('/tasks/{id}/execute', ['App\Controllers\TaskController', 'executeTask']);

// Submit task report (text + image upload)
$router->post('/tasks/{id}/submit', function($request, $response) {
    \Core\Auth::requireAuth();
    $userId = \Core\Auth::id();
    $taskId = (int) $request->param('id');
    $data = $request->all();

    $task = \Core\Database::fetch("SELECT id, payment_per_execution FROM tasks WHERE id = ? AND status = 'active'", [$taskId]);
    if (!$task) {
        $response->json(['success' => false, 'message' => 'Task not found.'], 404);
        return;
    }

    $userTask = \Core\Database::fetch("
        SELECT * FROM user_tasks 
        WHERE user_id = ? AND task_id = ? AND status = 'started' AND DATE(started_at) = CURDATE()
        ORDER BY id DESC LIMIT 1
    ", [$userId, $taskId]);

    if (!$userTask) {
        $response->json(['success' => false, 'message' => 'No active task found. Please start the task first.'], 400);
        return;
    }

    if (strtotime($userTask['expires_at']) < time()) {
        \Core\Database::update('user_tasks', ['status' => 'expired'], 'id = ?', [$userTask['id']]);
        $response->json(['success' => false, 'message' => 'Task time has expired.'], 400);
        return;
    }

    $reportText = trim($data['report_text'] ?? '');
    $submittedUrl = trim($data['submitted_url'] ?? '');

    if (empty($reportText)) {
        $response->json(['success' => false, 'message' => 'Please write a report.'], 400);
        return;
    }

    $screenshotPath = null;
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['proof_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            $response->json(['success' => false, 'message' => 'Invalid image type. Allowed: JPG, PNG, GIF, WebP.'], 400);
            return;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            $response->json(['success' => false, 'message' => 'Image must be under 5MB.'], 400);
            return;
        }

        $uploadDir = ROOT_PATH . '/uploads/proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = 'proof_' . $userId . '_' . $taskId . '_' . time() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . $fileName);
        $screenshotPath = '/uploads/proofs/' . $fileName;
    }

    $submissionId = \Core\Database::insert('submissions', [
        'task_id' => $taskId,
        'worker_id' => $userId,
        'submitted_url' => $submittedUrl ?: null,
        'submission_text' => $reportText,
        'screenshot_path' => $screenshotPath,
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'status' => 'pending'
    ]);

    \Core\Database::update('user_tasks', [
        'status' => 'submitted',
        'submitted_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$userTask['id']]);

    \Core\Database::query(
        "UPDATE tasks SET current_executions = current_executions + 1 WHERE id = ?",
        [$taskId]
    );

    \Core\Database::insert('admin_notifications', [
        'user_id' => null,
        'message' => 'New task submission from #' . $userId . ' for task "' . htmlspecialchars_decode($task['title'] ?? 'Task #' . $taskId) . '"',
        'type' => 'warning'
    ]);

    $response->json([
        'success' => true,
        'message' => 'Report submitted successfully! Waiting for review.'
    ]);
});

// Refuse task
$router->post('/tasks/{id}/refuse', function($request, $response) {
    \Core\Auth::requireAuth();
    $userId = \Core\Auth::id();
    $taskId = (int) $request->param('id');

    $userTask = \Core\Database::fetch("
        SELECT id FROM user_tasks 
        WHERE user_id = ? AND task_id = ? AND status = 'started' AND DATE(started_at) = CURDATE()
        ORDER BY id DESC LIMIT 1
    ", [$userId, $taskId]);

    if ($userTask) {
        \Core\Database::update('user_tasks', ['status' => 'refused'], 'id = ?', [$userTask['id']]);
    }

    $response->json(['success' => true, 'message' => 'Task refused.']);
});

$router->get('/wallet', ['App\Controllers\WalletController', 'showWallet']);
$router->post('/wallet/transfer-advisor', ['App\Controllers\WalletController', 'transferToAdvisor']);

$router->get('/notifications', function($request, $response) {
    \Core\Auth::requireAuth();
    include ROOT_PATH . '/views/user/notifications.php';
});

$router->get('/notifications/mark-read', function($request, $response) {
    \Core\Auth::requireAuth();
    $id = (int) ($_GET['id'] ?? 0);
    $userId = \Core\Auth::id();
    if ($id > 0 && $userId) {
        \Core\Database::query(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
    $response->redirect($referer);
});

$router->post('/notifications/mark-read', function($request, $response) {
    \Core\Auth::requireAuth();
    $data = $request->all();
    $id = (int) ($data['id'] ?? 0);
    $userId = \Core\Auth::id();
    if ($id > 0 && $userId) {
        \Core\Database::query(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }
    $response->redirect('/notifications');
});

$router->post('/notifications/mark-all-read', function($request, $response) {
    \Core\Auth::requireAuth();
    $userId = \Core\Auth::id();
    if ($userId) {
        \Core\Database::query(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }
    $_SESSION['flash_success'] = 'All notifications marked as read.';
    $response->redirect('/notifications');
});

$router->get('/deposit', ['App\Controllers\WalletController', 'showDeposit']);
$router->post('/deposit', ['App\Controllers\WalletController', 'createDeposit']);

$router->get('/withdraw', ['App\Controllers\WalletController', 'showWithdraw']);
$router->post('/withdraw', ['App\Controllers\WalletController', 'createWithdrawal']);

$router->get('/referral', ['App\Controllers\ReferralController', 'showReferral']);

$router->get('/advisor', function($request, $response) {
    \Core\Auth::requireAuth();
    include ROOT_PATH . '/views/user/advisor.php';
});

$router->get('/advisor/create-ad', ['App\Controllers\WalletController', 'showCreateAd']);

$router->get('/advisor/manage-ads', ['App\Controllers\WalletController', 'showManageAds']);

$router->get('/profile', function($request, $response) {
    \Core\Auth::requireAuth();
    include ROOT_PATH . '/views/user/profile.php';
});

$router->get('/settings', function($request, $response) {
    \Core\Auth::requireAuth();
    include ROOT_PATH . '/views/user/settings.php';
});

$router->get('/change-password', ['App\Controllers\AuthController', 'showChangePassword']);
$router->post('/change-password', ['App\Controllers\AuthController', 'changePassword']);

$router->get('/support', function($request, $response) {
    \Core\Auth::requireAuth();
    include ROOT_PATH . '/views/user/support.php';
});

// Protected Admin Routes (require admin authentication)
$router->get('/admin', function($request, $response) {
    \Core\Auth::requireAdmin();

    $page = (string) ($request->get('page', '') ?? '');
    $page = trim($page);
    if ($page !== '' && $page !== 'dashboard') {
        $map = [
            'users' => '/admin/users',
            'deposits' => '/admin/deposits',
            'withdrawals' => '/admin/withdrawals',
            'ads' => '/admin/ads',
            'tasks' => '/admin/tasks',
            'reports' => '/admin/reports',
            'referrals' => '/admin/referrals',
            'notifications' => '/admin/notifications',
            'settings' => '/admin/settings',
            'security' => '/admin/security',
            'profile' => '/admin/profile',
            'verification-requests' => '/admin/verification-requests',
            'support-tickets' => '/admin/support/tickets',
            'stickers' => '/admin/stickers',
        ];

        if (isset($map[$page])) {
            $response->redirect($map[$page]);
            return;
        }
    }

    $stats = [
        'users' => ['total' => 0, 'active' => 0, 'new_today' => 0],
        'ads' => ['total' => 0, 'active' => 0, 'pending' => 0],
        'financial' => [
            'total_balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
            'pending_deposits' => 0,
            'pending_withdrawals' => 0
        ],
        'tasks' => ['total' => 0, 'completed' => 0, 'active' => 0, 'pending' => 0]
    ];

    try {
        $stats['users']['total'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM users");
        $stats['users']['active'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $stats['users']['new_today'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
    } catch (\Throwable $e) {}

    try {
        $stats['ads']['total'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads");
        $stats['ads']['active'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads WHERE status = 'active'");
        $stats['ads']['pending'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads WHERE status = 'pending'");
    } catch (\Throwable $e) {}

    try {
        $stats['financial']['total_balance'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(earning_balance + advisor_balance), 0) FROM users");
        $stats['financial']['total_earned'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(total_earned), 0) FROM users");
        $stats['financial']['total_withdrawn'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(total_withdrawn), 0) FROM users");
        $stats['financial']['pending_deposits'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'pending'");
        $stats['financial']['pending_withdrawals'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'");
    } catch (\Throwable $e) {}

    try {
        $stats['tasks']['total'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks");
        $stats['tasks']['completed'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
        $stats['tasks']['active'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks WHERE status = 'active'");
        $stats['tasks']['pending'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks WHERE status = 'pending'");
    } catch (\Throwable $e) {}

    $alerts = [];
    if ($stats['financial']['pending_deposits'] > 0) {
        $alerts[] = ['type' => 'warning', 'icon' => 'fa-exclamation-triangle', 'message' => "{$stats['financial']['pending_deposits']} pending deposit(s) need review."];
    }
    if ($stats['financial']['pending_withdrawals'] > 0) {
        $alerts[] = ['type' => 'info', 'icon' => 'fa-info-circle', 'message' => "{$stats['financial']['pending_withdrawals']} pending withdrawal(s) to process."];
    }

    $recentActivities = [];
    try {
        $recentActivities = \Core\Database::fetchAll(
            "SELECT 'System' as admin_name, 'Deposit' as action, CONCAT(u.username, ' deposited ', d.amount, ' USDT') as details, d.created_at 
             FROM deposits d JOIN users u ON d.user_id = u.user_id 
             ORDER BY d.created_at DESC LIMIT 10"
        );
    } catch (\Throwable $e) {}

    include ROOT_PATH . '/views/admin/dashboard.php';
});

$router->get('/admin/users', function($request, $response) {
    \Core\Auth::requireAdmin();
    include ROOT_PATH . '/views/admin/users.php';
});

$router->post('/admin/users/approve', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $userId = $data['user_id'] ?? null;
    if (!$userId) {
        $_SESSION['flash_error'] = 'Invalid user.';
        $response->redirect('/admin/users');
        return;
    }

    \Core\Database::update('users', ['status' => 'active'], 'user_id = ?', [$userId]);
    $_SESSION['flash_success'] = 'Blueprint approved successfully.';
    $response->redirect('/admin/users');
});

$router->post('/admin/users/reject', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $userId = $data['user_id'] ?? null;
    if (!$userId) {
        $_SESSION['flash_error'] = 'Invalid user.';
        $response->redirect('/admin/users');
        return;
    }

    \Core\Database::update('users', ['status' => 'unverified'], 'user_id = ?', [$userId]);
    $_SESSION['flash_success'] = 'Blueprint rejected successfully.';
    $response->redirect('/admin/users');
});

$router->get('/admin/deposits', function($request, $response) {
    \Core\Auth::requireAdmin();
    $status = (string) ($request->get('status', 'pending') ?? 'pending');
    $status = trim($status);
    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $status = 'pending';
    }

    $search = trim((string) $request->get('search', ''));
    $currency = trim((string) $request->get('currency', ''));
    $activeTab = trim((string) $request->get('tab', 'deposits'));
    if (!in_array($activeTab, ['deposits', 'withdrawals'], true)) {
        $activeTab = 'deposits';
    }

    $summary = [
        'total_deposited' => (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved'"),
        'total_approved' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'approved'"),
        'total_pending' => (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'pending'"),
        'pending_count' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'pending'"),
        'total_withdrawn' => (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'paid'"),
        'total_withdraw_count' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM withdrawals WHERE status IN ('paid','pending')"),
    ];
    $summary['net_balance'] = $summary['total_deposited'] - $summary['total_withdrawn'];

    $counts = [
        'pending' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'pending'"),
        'approved' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'approved'"),
        'rejected' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'rejected'")
    ];

    $sql = "SELECT 
            d.deposit_id,
            d.user_id,
            u.username,
            u.email,
            d.currency,
            d.amount,
            d.wallet_address,
            d.txid,
            d.status,
            d.admin_notes,
            d.created_at
        FROM deposits d
        JOIN users u ON u.user_id = d.user_id
        WHERE d.status = ?";
    $params = [$status];

    if ($search !== '') {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR d.txid LIKE ? OR d.deposit_id = ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = (int) $search;
    }

    if ($currency !== '' && in_array($currency, ['BTC', 'TRX', 'ETH', 'USDT'], true)) {
        $sql .= " AND d.currency = ?";
        $params[] = $currency;
    }

    $sql .= " ORDER BY d.created_at DESC LIMIT 200";
    $deposits = \Core\Database::fetchAll($sql, $params);

    $wstatus = trim((string) $request->get('wstatus', 'pending'));
    if (!in_array($wstatus, ['pending', 'paid', 'rejected'], true)) {
        $wstatus = 'pending';
    }

    $withdrawalCounts = [
        'pending' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'"),
        'approved' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM withdrawals WHERE status = 'paid'"),
        'rejected' => (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM withdrawals WHERE status = 'rejected'")
    ];

    $withdrawals = \Core\Database::fetchAll(
        "SELECT w.withdrawal_id, w.user_id, u.username, u.email, w.currency, w.amount, w.wallet_address, w.status, w.admin_notes, w.created_at
         FROM withdrawals w JOIN users u ON u.user_id = w.user_id
         WHERE w.status = ?
         ORDER BY w.created_at DESC LIMIT 200",
        [$wstatus]
    );

    include ROOT_PATH . '/views/admin/deposits.php';
});

$router->post('/admin/deposits/approve', function($request, $response) {
    \Core\Auth::requireAdmin();

    $data = $request->all();
    $depositId = (int) ($data['deposit_id'] ?? 0);
    $adminNotes = isset($data['admin_notes']) ? (string) $data['admin_notes'] : null;

    if ($depositId <= 0) {
        $_SESSION['flash_error'] = 'Invalid deposit.';
        $response->redirect('/admin/deposits');
        return;
    }

    try {
        \Core\Database::beginTransaction();

        $deposit = \Core\Database::fetch(
            "SELECT deposit_id, user_id, currency, amount, status FROM deposits WHERE deposit_id = ? FOR UPDATE",
            [$depositId]
        );

        if (!$deposit) {
            \Core\Database::rollback();
            $_SESSION['flash_error'] = 'Deposit not found.';
            $response->redirect('/admin/deposits');
            return;
        }

        if (($deposit['status'] ?? '') !== 'pending') {
            \Core\Database::rollback();
            $_SESSION['flash_error'] = 'This deposit has already been processed.';
            $response->redirect('/admin/deposits');
            return;
        }

        $amount = (float) ($deposit['amount'] ?? 0);
        $userId = (int) $deposit['user_id'];

        \Core\Database::query(
            "UPDATE users SET advisor_balance = advisor_balance + ? WHERE user_id = ?",
            [$amount, $userId]
        );

        $depositUpdate = ['status' => 'approved'];
        if ($adminNotes !== null && $adminNotes !== '') {
            $depositUpdate['admin_notes'] = $adminNotes;
        }
        \Core\Database::update('deposits', $depositUpdate, 'deposit_id = ?', [$depositId]);

        \Core\Database::query(
            "INSERT INTO wallet_transactions (user_id, type, amount, description, reference_id, reference_type) VALUES (?, 'deposit', ?, ?, ?, 'deposit')",
            [$userId, $amount, 'Deposit approved: ' . $amount . ' ' . (string) ($deposit['currency'] ?? ''), $depositId]
        );

        \Core\Database::commit();

        try {
            \Core\Database::insert('notifications', [
                'user_id' => $userId,
                'title' => 'Deposit Approved',
                'message' => 'Your deposit of ' . number_format($amount, 8) . ' ' . (string) ($deposit['currency'] ?? '') . ' has been approved and added to your balance.',
                'type' => 'success',
                'reference_type' => 'deposit',
                'reference_id' => $depositId
            ]);
            \Core\Database::insert('admin_notifications', [
                'user_id' => $userId,
                'message' => 'Deposit #' . $depositId . ' approved: ' . number_format($amount, 8) . ' ' . (string) ($deposit['currency'] ?? '') . ' added to user balance.',
                'type' => 'deposit'
            ]);
        } catch (\Exception $e) {
            if (class_exists(\Core\Logger::class)) {
                \Core\Logger::error('Deposit approve notification error: ' . $e->getMessage());
            }
        }

        $_SESSION['flash_success'] = 'Deposit approved. Earning + Advisor balance updated by ' . number_format($amount, 8) . '.';
        $response->redirect('/admin/deposits');
    } catch (\Exception $e) {
        if (\Core\Database::inTransaction()) {
            \Core\Database::rollback();
        }
        if (class_exists(\Core\Logger::class)) {
            \Core\Logger::error('Approve deposit error: ' . $e->getMessage());
        }
        $_SESSION['flash_error'] = 'Failed to approve deposit.';
        $response->redirect('/admin/deposits');
    }
});

$router->post('/admin/deposits/reject', function($request, $response) {
    \Core\Auth::requireAdmin();

    $data = $request->all();
    $depositId = (int) ($data['deposit_id'] ?? 0);
    $adminNotes = isset($data['admin_notes']) ? (string) $data['admin_notes'] : null;

    if ($depositId <= 0) {
        $_SESSION['flash_error'] = 'Invalid deposit.';
        $response->redirect('/admin/deposits');
        return;
    }

    try {
        \Core\Database::beginTransaction();

        $deposit = \Core\Database::fetch(
            "SELECT deposit_id, user_id, currency, amount, status FROM deposits WHERE deposit_id = ? FOR UPDATE",
            [$depositId]
        );

        if (!$deposit) {
            \Core\Database::rollback();
            $_SESSION['flash_error'] = 'Deposit not found.';
            $response->redirect('/admin/deposits');
            return;
        }

        if (($deposit['status'] ?? '') !== 'pending') {
            \Core\Database::rollback();
            $_SESSION['flash_error'] = 'This deposit has already been processed.';
            $response->redirect('/admin/deposits');
            return;
        }

        $depositUpdate = ['status' => 'rejected'];
        if ($adminNotes !== null && $adminNotes !== '') {
            $depositUpdate['admin_notes'] = $adminNotes;
        }

        \Core\Database::update(
            'deposits',
            $depositUpdate,
            'deposit_id = ?',
            [$depositId]
        );

        \Core\Database::commit();

        try {
            \Core\Database::insert('notifications', [
                'user_id' => (int) $deposit['user_id'],
                'title' => 'Deposit Rejected',
                'message' => 'Your deposit of ' . number_format((float) ($deposit['amount'] ?? 0), 8) . ' ' . (string) ($deposit['currency'] ?? '') . ' has been rejected.' . ($adminNotes ? ' Reason: ' . $adminNotes : ''),
                'type' => 'danger',
                'reference_type' => 'deposit',
                'reference_id' => $depositId
            ]);
        } catch (\Exception $e) {
            if (class_exists(\Core\Logger::class)) {
                \Core\Logger::error('Reject deposit notification error: ' . $e->getMessage());
            }
        }

        try {
            \Core\Database::insert('admin_notifications', [
                'user_id' => (int) $deposit['user_id'],
                'message' => 'Deposit #' . $depositId . ' rejected: ' . number_format((float) ($deposit['amount'] ?? 0), 8) . ' ' . (string) ($deposit['currency'] ?? '') . '.' . ($adminNotes ? ' Reason: ' . $adminNotes : ''),
                'type' => 'warning'
            ]);
        } catch (\Exception $e) {
            if (class_exists(\Core\Logger::class)) {
                \Core\Logger::error('Admin notification error: ' . $e->getMessage());
            }
        }

        $_SESSION['flash_success'] = 'Deposit rejected successfully.';
        $response->redirect('/admin/deposits');
    } catch (\Exception $e) {
        if (\Core\Database::inTransaction()) {
            \Core\Database::rollback();
        }
        if (class_exists(\Core\Logger::class)) {
            \Core\Logger::error('Reject deposit error: ' . $e->getMessage());
        }
        $_SESSION['flash_error'] = 'Failed to reject deposit.';
        $response->redirect('/admin/deposits');
    }
});

$router->get('/admin/withdrawals', function($request, $response) {
    \Core\Auth::requireAdmin();

    $basePath = (string) Config::get('app.base_path', '');
    $status = (string) ($request->get('status', 'pending') ?? 'pending');
    $counts = ['pending' => 0, 'paid' => 0, 'rejected' => 0];
    $withdrawals = [];

    try {
        $counts['pending'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'");
        $counts['paid'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM withdrawals WHERE status = 'paid'");
        $counts['rejected'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM withdrawals WHERE status = 'rejected'");
    } catch (\Throwable $e) {}

    try {
        $withdrawals = \Core\Database::fetchAll(
            "SELECT w.withdrawal_id, w.user_id, u.username, u.email, w.currency, w.amount, w.wallet_address, w.status, w.admin_notes, w.created_at, w.processed_at
             FROM withdrawals w JOIN users u ON w.user_id = u.user_id
             WHERE w.status = ?
             ORDER BY w.created_at DESC LIMIT 200",
            [$status]
        );
    } catch (\Throwable $e) {}

    include ROOT_PATH . '/views/admin/withdrawals.php';
});

$router->post('/admin/withdrawals/approve', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $withdrawalId = (int) ($data['withdrawal_id'] ?? 0);

    if ($withdrawalId <= 0) {
        $_SESSION['flash_error'] = 'Invalid withdrawal.';
        $response->redirect('/admin/deposits?tab=withdrawals');
        return;
    }

    try {
        \Core\Database::beginTransaction();

        $w = \Core\Database::fetch(
            "SELECT withdrawal_id, user_id, currency, amount, status FROM withdrawals WHERE withdrawal_id = ? FOR UPDATE",
            [$withdrawalId]
        );

        if (!$w) {
            \Core\Database::rollback();
            $_SESSION['flash_error'] = 'Withdrawal not found.';
            $response->redirect('/admin/deposits?tab=withdrawals');
            return;
        }

        if (($w['status'] ?? '') !== 'pending') {
            \Core\Database::rollback();
            $_SESSION['flash_error'] = 'This withdrawal has already been processed.';
            $response->redirect('/admin/deposits?tab=withdrawals');
            return;
        }

        \Core\Database::update('withdrawals', ['status' => 'paid', 'processed_at' => date('Y-m-d H:i:s')], 'withdrawal_id = ?', [$withdrawalId]);

        \Core\Database::insert('admin_notifications', [
            'user_id' => (int) $w['user_id'],
            'message' => 'Withdrawal #' . $withdrawalId . ' approved: ' . number_format((float) $w['amount'], 2) . ' ' . (string) ($w['currency'] ?? '') . ' paid.',
            'type' => 'withdrawal'
        ]);

        \Core\Database::commit();
        $_SESSION['flash_success'] = 'Withdrawal approved successfully.';
        $response->redirect('/admin/deposits?tab=withdrawals');
    } catch (\Exception $e) {
        if (\Core\Database::inTransaction()) { \Core\Database::rollback(); }
        $_SESSION['flash_error'] = 'Failed to approve withdrawal.';
        $response->redirect('/admin/deposits?tab=withdrawals');
    }
});

$router->post('/admin/withdrawals/reject', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $withdrawalId = (int) ($data['withdrawal_id'] ?? 0);
    $adminNotes = $data['admin_notes'] ?? null;

    if ($withdrawalId <= 0) {
        $_SESSION['flash_error'] = 'Invalid withdrawal.';
        $response->redirect('/admin/deposits?tab=withdrawals');
        return;
    }

    try {
        \Core\Database::beginTransaction();

        $w = \Core\Database::fetch(
            "SELECT withdrawal_id, user_id, currency, amount, status FROM withdrawals WHERE withdrawal_id = ? FOR UPDATE",
            [$withdrawalId]
        );

        if (!$w) {
            \Core\Database::rollback();
            $_SESSION['flash_error'] = 'Withdrawal not found.';
            $response->redirect('/admin/deposits?tab=withdrawals');
            return;
        }

        if (($w['status'] ?? '') !== 'pending') {
            \Core\Database::rollback();
            $_SESSION['flash_error'] = 'This withdrawal has already been processed.';
            $response->redirect('/admin/deposits?tab=withdrawals');
            return;
        }

        $wUpdate = ['status' => 'rejected'];
        if ($adminNotes !== null && $adminNotes !== '') {
            $wUpdate['admin_notes'] = $adminNotes;
        }
        \Core\Database::update('withdrawals', $wUpdate, 'withdrawal_id = ?', [$withdrawalId]);

        \Core\Database::insert('admin_notifications', [
            'user_id' => (int) $w['user_id'],
            'message' => 'Withdrawal #' . $withdrawalId . ' rejected: ' . number_format((float) $w['amount'], 2) . ' ' . (string) ($w['currency'] ?? '') . '.' . ($adminNotes ? ' Reason: ' . $adminNotes : ''),
            'type' => 'warning'
        ]);

        \Core\Database::commit();
        $_SESSION['flash_success'] = 'Withdrawal rejected successfully.';
        $response->redirect('/admin/deposits?tab=withdrawals');
    } catch (\Exception $e) {
        if (\Core\Database::inTransaction()) { \Core\Database::rollback(); }
        $_SESSION['flash_error'] = 'Failed to reject withdrawal.';
        $response->redirect('/admin/deposits?tab=withdrawals');
    }
});

$router->get('/admin/ads', function($request, $response) {
    \Core\Auth::requireAdmin();

    $basePath = (string) Config::get('app.base_path', '');
    $status = (string) ($request->get('status', 'all') ?? 'all');
    $counts = ['active' => 0, 'blocked' => 0, 'paused' => 0, 'completed' => 0, 'all' => 0, 'reports' => 0];
    $ads = [];
    $reports = [];

    try {
        $counts['active'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads WHERE status = 'active'");
        $counts['blocked'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads WHERE status = 'blocked'");
        $counts['paused'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads WHERE status = 'paused'");
        $counts['completed'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads WHERE status = 'completed'");
        $counts['all'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads");
        $counts['reports'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ad_reports WHERE status = 'pending'");
    } catch (\Throwable $e) {}

    try {
        if ($status === 'all') {
            $ads = \Core\Database::fetchAll(
                "SELECT a.ad_id, a.user_id, u.username, u.email, a.ad_title, a.ad_type, a.target_url, a.description, a.cost_per_view, a.total_views, a.views_received, a.remaining_views, a.spent_amount, a.status, a.created_at,
                 (SELECT COUNT(*) FROM ad_reports ar WHERE ar.ad_id = a.ad_id AND ar.status = 'pending') as report_count
                 FROM ads a JOIN users u ON a.user_id = u.user_id
                 ORDER BY a.created_at DESC LIMIT 200"
            );
        } else {
            $ads = \Core\Database::fetchAll(
                "SELECT a.ad_id, a.user_id, u.username, u.email, a.ad_title, a.ad_type, a.target_url, a.description, a.cost_per_view, a.total_views, a.views_received, a.remaining_views, a.spent_amount, a.status, a.created_at,
                 (SELECT COUNT(*) FROM ad_reports ar WHERE ar.ad_id = a.ad_id AND ar.status = 'pending') as report_count
                 FROM ads a JOIN users u ON a.user_id = u.user_id
                 WHERE a.status = ?
                 ORDER BY a.created_at DESC LIMIT 200",
                [$status]
            );
        }
    } catch (\Throwable $e) {}

    if ($status === 'reports') {
        try {
            $reports = \Core\Database::fetchAll(
                "SELECT ar.id, ar.ad_id, ar.reported_by, ar.reason, ar.details, ar.status, ar.created_at,
                 u.username as reporter_username, a.ad_title, au.username as ad_owner
                 FROM ad_reports ar
                 JOIN users u ON ar.reported_by = u.user_id
                 JOIN ads a ON ar.ad_id = a.ad_id
                 JOIN users au ON a.user_id = au.user_id
                 WHERE ar.status = 'pending'
                 ORDER BY ar.created_at DESC LIMIT 100"
            );
        } catch (\Throwable $e) {}
    }

    include ROOT_PATH . '/views/admin/ads.php';
});

// Admin ad block
$router->post('/admin/ads/block', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $adId = (int) ($data['ad_id'] ?? 0);

    if ($adId <= 0) {
        $_SESSION['flash_error'] = 'Invalid ad.';
        $response->redirect('/admin/ads');
        return;
    }

    try {
        $ad = \Core\Database::fetch("SELECT ad_id, user_id, ad_title FROM ads WHERE ad_id = ?", [$adId]);
        if (!$ad) {
            $_SESSION['flash_error'] = 'Ad not found.';
            $response->redirect('/admin/ads');
            return;
        }

        \Core\Database::update('ads', ['status' => 'blocked'], 'ad_id = ?', [$adId]);

        \Core\Database::update('ad_reports', ['status' => 'reviewed', 'reviewed_at' => date('Y-m-d H:i:s')], "ad_id = ? AND status = 'pending'", [$adId]);

        \Core\Database::insert('notifications', [
            'user_id' => (int) $ad['user_id'],
            'title' => 'Ad Blocked',
            'message' => 'Your ad "' . $ad['ad_title'] . '" has been blocked by admin for policy violation.',
            'type' => 'warning',
            'is_read' => 0
        ]);

        $_SESSION['flash_success'] = 'Ad blocked successfully.';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Failed to block ad.';
    }

    $response->redirect('/admin/ads');
});

// Admin ad unblock
$router->post('/admin/ads/unblock', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $adId = (int) ($data['ad_id'] ?? 0);

    if ($adId <= 0) {
        $_SESSION['flash_error'] = 'Invalid ad.';
        $response->redirect('/admin/ads');
        return;
    }

    try {
        $ad = \Core\Database::fetch("SELECT ad_id, user_id, ad_title FROM ads WHERE ad_id = ?", [$adId]);
        if (!$ad) {
            $_SESSION['flash_error'] = 'Ad not found.';
            $response->redirect('/admin/ads');
            return;
        }

        \Core\Database::update('ads', ['status' => 'active'], 'ad_id = ?', [$adId]);

        \Core\Database::insert('notifications', [
            'user_id' => (int) $ad['user_id'],
            'title' => 'Ad Unblocked',
            'message' => 'Your ad "' . $ad['ad_title'] . '" has been unblocked and is now live again.',
            'type' => 'ad',
            'is_read' => 0
        ]);

        $_SESSION['flash_success'] = 'Ad unblocked and reactivated.';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Failed to unblock ad.';
    }

    $response->redirect('/admin/ads');
});

// Admin ad delete
$router->post('/admin/ads/delete', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $adId = (int) ($data['ad_id'] ?? 0);

    if ($adId <= 0) {
        $_SESSION['flash_error'] = 'Invalid ad.';
        $response->redirect('/admin/ads');
        return;
    }

    try {
        $ad = \Core\Database::fetch("SELECT ad_id, user_id, ad_title FROM ads WHERE ad_id = ?", [$adId]);
        if (!$ad) {
            $_SESSION['flash_error'] = 'Ad not found.';
            $response->redirect('/admin/ads');
            return;
        }

        \Core\Database::query("DELETE FROM ad_reports WHERE ad_id = ?", [$adId]);
        \Core\Database::query("DELETE FROM ad_views WHERE ad_id = ?", [$adId]);
        \Core\Database::delete('ads', 'ad_id = ?', [$adId]);

        $_SESSION['flash_success'] = 'Ad deleted permanently.';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Failed to delete ad.';
    }

    $response->redirect('/admin/ads');
});

// Admin dismiss report
$router->post('/admin/ads/dismiss-report', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $reportId = (int) ($data['report_id'] ?? 0);

    if ($reportId <= 0) {
        $_SESSION['flash_error'] = 'Invalid report.';
        $response->redirect('/admin/ads?status=reports');
        return;
    }

    try {
        \Core\Database::update('ad_reports', ['status' => 'dismissed', 'reviewed_at' => date('Y-m-d H:i:s')], 'id = ?', [$reportId]);
        $_SESSION['flash_success'] = 'Report dismissed.';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Failed to dismiss report.';
    }

    $response->redirect('/admin/ads?status=reports');
});

$router->get('/admin/tasks', function($request, $response) {
    \Core\Auth::requireAdmin();

    $basePath = (string) Config::get('app.base_path', '');
    $status = (string) ($request->get('status', 'active') ?? 'active');
    $counts = ['draft' => 0, 'active' => 0, 'paused' => 0, 'completed' => 0];
    $tasks = [];

    try {
        $counts['draft'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks WHERE status = 'draft'");
        $counts['active'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks WHERE status = 'active'");
        $counts['paused'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks WHERE status = 'paused'");
        $counts['completed'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
    } catch (\Throwable $e) {}

    try {
        $tasks = \Core\Database::fetchAll(
            "SELECT t.id, t.advertiser_id, u.username, u.email, t.title, t.ad_type, t.payment_per_execution, t.total_budget, t.max_executions, t.current_executions, t.status, t.created_at
             FROM tasks t JOIN users u ON t.advertiser_id = u.user_id
             WHERE t.status = ?
             ORDER BY t.created_at DESC LIMIT 200",
            [$status]
        );
    } catch (\Throwable $e) {}

    include ROOT_PATH . '/views/admin/tasks.php';
});

$router->get('/admin/referrals', function($request, $response) {
    \Core\Auth::requireAdmin();

    $basePath = (string) Config::get('app.base_path', '');
    $totalReferrals = 0;
    $totalCommission = 0;
    $topReferrers = [];
    $recentReferrals = [];

    try {
        $totalReferrals = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM referrals");
        $totalCommission = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM referral_earnings");
    } catch (\Throwable $e) {}

    try {
        $topReferrers = \Core\Database::fetchAll(
            "SELECT u.username, u.email, COUNT(r.referral_id) as referral_count, COALESCE(SUM(re.amount), 0) as total_earned
             FROM referrals r
             JOIN users u ON r.referrer_id = u.user_id
             LEFT JOIN referral_earnings re ON re.referrer_id = r.referrer_id
             GROUP BY r.referrer_id
             ORDER BY referral_count DESC
             LIMIT 20"
        );
    } catch (\Throwable $e) {}

    try {
        $recentReferrals = \Core\Database::fetchAll(
            "SELECT r.referral_id, ref.username as referrer, ru.username as referred, r.commission_rate, r.level, r.created_at
             FROM referrals r
             JOIN users ref ON r.referrer_id = ref.user_id
             JOIN users ru ON r.referred_user_id = ru.user_id
             ORDER BY r.created_at DESC
             LIMIT 50"
        );
    } catch (\Throwable $e) {}

    include ROOT_PATH . '/views/admin/referrals.php';
});

$router->get('/admin/reports', function($request, $response) {
    \Core\Auth::requireAdmin();

    $stats = [];
    $stats['total_deposited'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved'");
    $stats['pending_amount'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'pending'");
    $stats['today_deposited'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved' AND DATE(created_at) = CURDATE()");
    $stats['unique_depositors'] = (int) \Core\Database::fetchColumn("SELECT COUNT(DISTINCT user_id) FROM deposits WHERE status = 'approved'");
    $stats['week_deposited'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stats['week_count'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'approved' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stats['month_deposited'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stats['month_count'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'approved' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stats['month_approved'] = (float) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
    $stats['month_approved_count'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM deposits WHERE status = 'approved' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");

    $recentDeposits = \Core\Database::fetchAll(
        "SELECT d.deposit_id, u.username, u.email, d.currency, d.amount, d.status, d.created_at
         FROM deposits d JOIN users u ON u.user_id = d.user_id
         ORDER BY d.created_at DESC LIMIT 20"
    );

    include ROOT_PATH . '/views/admin/reports.php';
});

$router->get('/admin/notifications', function($request, $response) {
    \Core\Auth::requireAdmin();
    $notifications = \Core\Database::fetchAll(
        "SELECT id, user_id, message, type, is_read, created_at FROM admin_notifications ORDER BY created_at DESC LIMIT 100"
    );
    $unreadCount = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");
    include ROOT_PATH . '/views/admin/notifications.php';
});

$router->post('/admin/notifications/mark-read', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $id = (int) ($data['id'] ?? 0);
    if ($id > 0) {
        \Core\Database::update('admin_notifications', ['is_read' => 1], 'id = ?', [$id]);
    }
    $response->redirect('/admin/notifications');
});

$router->post('/admin/notifications/mark-all-read', function($request, $response) {
    \Core\Auth::requireAdmin();
    \Core\Database::query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
    $_SESSION['flash_success'] = 'All notifications marked as read.';
    $response->redirect('/admin/notifications');
});

$router->post('/admin/broadcast', function($request, $response) {
    \Core\Auth::requireAdmin();
    $data = $request->all();
    $title = trim($data['title'] ?? '');
    $message = trim($data['message'] ?? '');

    if (empty($message)) {
        $_SESSION['flash_error'] = 'Message cannot be empty.';
        $response->redirect('/admin/notifications');
        return;
    }

    $insertTitle = $title ?: 'Announcement';
    \Core\Database::query(
        "INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
         SELECT user_id, ?, ?, 'broadcast', 0, NOW() FROM users WHERE user_id IS NOT NULL",
        [$insertTitle, $message]
    );

    $inserted = \Core\Database::query("SELECT ROW_COUNT()")->fetchColumn();
    $_SESSION['flash_success'] = "Broadcast sent to all users successfully!";
    $response->redirect('/admin/notifications');
});

$router->get('/admin/settings', function($request, $response) {
    \Core\Auth::requireAdmin();

    $basePath = (string) Config::get('app.base_path', '');
    $settings = [];

    try {
        $settings = [
            'app_name' => Config::get('app.name') ?? 'VSItoA',
            'app_url' => Config::get('app.url') ?? '',
            'app_env' => Config::get('app.env') ?? 'production',
            'app_debug' => Config::get('app.debug') ?? false,
            'mail_host' => Config::get('mail.host') ?? '',
            'mail_port' => Config::get('mail.port') ?? '',
            'mail_username' => Config::get('mail.username') ?? '',
            'deposit_enabled' => Config::get('deposits.enabled') ?? true,
            'withdrawal_min' => Config::get('withdrawals.min_amount') ?? 10,
            'withdrawal_fee' => Config::get('withdrawals.fee_percent') ?? 2,
            'referral_commission' => Config::get('referrals.commission_rate') ?? 10,
            'maintenance_mode' => Config::get('maintenance.enabled') ?? false,
        ];
    } catch (\Throwable $e) {}

    include ROOT_PATH . '/views/admin/settings.php';
});

$router->get('/admin/security', function($request, $response) {
    \Core\Auth::requireAdmin();

    $basePath = (string) Config::get('app.base_path', '');
    $securityInfo = [
        'failed_logins_today' => 0,
        'total_admins' => 0,
        'active_sessions' => 0,
        'recent_logins' => [],
    ];

    try {
        $securityInfo['failed_logins_today'] = (int) \Core\Database::fetchColumn(
            "SELECT COUNT(*) FROM user_login_log WHERE login_status = 'failed' AND DATE(login_time) = CURDATE()"
        );
        $securityInfo['total_admins'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM admins WHERE status = 'active'");
    } catch (\Throwable $e) {}

    try {
        $securityInfo['recent_logins'] = \Core\Database::fetchAll(
            "SELECT ull.ip_address, ull.user_agent, ull.login_status, ull.login_time, u.username
             FROM user_login_log ull
             LEFT JOIN users u ON ull.user_id = u.user_id
             ORDER BY ull.login_time DESC
             LIMIT 20"
        );
    } catch (\Throwable $e) {}

    include ROOT_PATH . '/views/admin/security.php';
});

$router->get('/admin/profile', function($request, $response) {
    \Core\Auth::requireAdmin();
    include ROOT_PATH . '/views/admin/profile.php';
});

// Legacy admin pages -> single admin panel
$router->get('/admin/_legacy/verification-requests', function($request, $response) {
    \Core\Auth::requireAdmin();
    http_response_code(404);
    include ROOT_PATH . '/views/errors/404.php';
});

$router->get('/admin/_legacy/support/tickets', function($request, $response) {
    \Core\Auth::requireAdmin();
    http_response_code(404);
    include ROOT_PATH . '/views/errors/404.php';
});

$router->get('/admin/_legacy/stickers', function($request, $response) {
    \Core\Auth::requireAdmin();
    http_response_code(404);
    include ROOT_PATH . '/views/errors/404.php';
});

// Static Pages
$router->get('/about', function($request, $response) {
    include ROOT_PATH . '/views/pages/about.php';
});

$router->get('/terms', function($request, $response) {
    include ROOT_PATH . '/views/pages/terms.php';
});

$router->get('/privacy', function($request, $response) {
    include ROOT_PATH . '/views/pages/privacy.php';
});

$router->get('/faq', function($request, $response) {
    include ROOT_PATH . '/views/pages/faq.php';
});

$router->get('/contact', function($request, $response) {
    include ROOT_PATH . '/views/pages/contact.php';
});

// Error pages
$router->get('/404', function($request, $response) {
    http_response_code(404);
    include ROOT_PATH . '/views/errors/404.php';
});

$router->get('/500', function($request, $response) {
    http_response_code(500);
    include ROOT_PATH . '/views/errors/500.php';
});

// Maintenance page (if enabled)
$router->get('/maintenance', function($request, $response) {
    http_response_code(503);
    include ROOT_PATH . '/views/errors/maintenance.php';
});

// Verification Routes
$router->get('/verify', ['App\Controllers\VerifiedController', 'showVerificationForm']);
$router->post('/verify/submit', ['App\Controllers\VerifiedController', 'applyVerification']);

// Profile Routes (user pages)
$router->get('/profile/edit', ['App\Controllers\ProfileController', 'showEditProfile']);
$router->post('/profile/update', ['App\Controllers\ProfileController', 'updateProfile']);
$router->post('/profile/links/add', ['App\Controllers\ProfileController', 'addProfileLink']);
$router->post('/profile/links/update', ['App\Controllers\ProfileController', 'updateProfileLink']);
$router->get('/profile/links/delete', ['App\Controllers\ProfileController', 'deleteProfileLink']);
$router->post('/profile/links/reorder', ['App\Controllers\ProfileController', 'reorderProfileLinks']);

// Support Routes (user pages)
$router->get('/support/tickets', ['App\Controllers\SupportController', 'showTickets']);
$router->get('/support/create', ['App\Controllers\SupportController', 'showCreateTicket']);
$router->post('/support/create', ['App\Controllers\SupportController', 'createTicket']);
$router->get('/support/ticket', ['App\Controllers\SupportController', 'showTicket']);
$router->post('/support/respond', ['App\Controllers\SupportController', 'addResponse']);

// Search Routes
$router->get('/search', ['App\Controllers\SearchController', 'search']);
$router->get('/search/autocomplete', ['App\Controllers\SearchController', 'autocomplete']);
$router->get('/search/trending', ['App\Controllers\SearchController', 'getTrendingSearches']);
$router->get('/search/suggestions', ['App\Controllers\SearchController', 'getSearchSuggestions']);

// Stickers Routes (user pages)
$router->get('/stickers', ['App\Controllers\StickerController', 'showStickers']);
$router->get('/stickers/remove', ['App\Controllers\StickerController', 'removeSticker']);

// Admin Verification Routes
$router->get('/admin/verification-requests', ['App\Controllers\VerifiedController', 'showVerificationRequests']);
$router->post('/admin/verification/process', ['App\Controllers\VerifiedController', 'processVerificationRequest']);

// Admin Support Routes - view all tickets
$router->get('/admin/support/tickets', ['App\Controllers\SupportController', 'showAllTickets']);
$router->post('/admin/support/update-status', ['App\Controllers\SupportController', 'updateTicketStatus']);

// Admin Stickers Routes - management
$router->get('/admin/stickers', ['App\Controllers\StickerController', 'showStickerManagement']);
$router->post('/admin/stickers/create', ['App\Controllers\StickerController', 'createCustomSticker']);

// Catch all route for 404
$router->get('/{path:.*}', function($request, $response) {
    http_response_code(404);
    include ROOT_PATH . '/views/errors/404.php';
});
