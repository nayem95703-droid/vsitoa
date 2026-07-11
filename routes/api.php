<?php

/**
 * API Routes
 * Handles all API requests
 */

// API prefix
$router->setBasePath(($basePath ?? '') . '/api');

// Authentication API
$router->post('/auth/register', ['App\Controllers\Api\AuthController', 'register']);
$router->post('/auth/login', ['App\Controllers\Api\AuthController', 'login']);
$router->post('/auth/logout', ['App\Controllers\Api\AuthController', 'logout']);
$router->post('/auth/forgot-password', ['App\Controllers\Api\AuthController', 'forgotPassword']);
$router->post('/auth/reset-password', ['App\Controllers\Api\AuthController', 'resetPassword']);
$router->post('/auth/verify-email', ['App\Controllers\Api\AuthController', 'verifyEmail']);
$router->post('/auth/resend-verification', ['App\Controllers\Api\AuthController', 'resendVerification']);

// Admin Authentication API
$router->post('/admin/auth/login', ['App\Controllers\Api\AdminAuthController', 'login']);
$router->post('/admin/auth/logout', ['App\Controllers\Api\AdminAuthController', 'logout']);

// User API (protected)
$router->get('/user/profile', ['App\Controllers\Api\UserController', 'profile']);
$router->put('/user/profile', ['App\Controllers\Api\UserController', 'updateProfile']);
$router->put('/user/password', ['App\Controllers\Api\UserController', 'updatePassword']);
$router->get('/user/stats', ['App\Controllers\Api\UserController', 'getStats']);
$router->get('/user/earnings', ['App\Controllers\Api\UserController', 'getEarnings']);
$router->get('/user/earnings-sources', ['App\Controllers\Api\UserController', 'getEarningsSources']);
$router->get('/user/notifications', ['App\Controllers\Api\UserController', 'getNotifications']);
$router->put('/user/notifications/{id}/read', ['App\Controllers\Api\UserController', 'markNotificationRead']);

// Ads API
$router->get('/ads', ['App\Controllers\Api\AdController', 'getAds']);
$router->post('/ads/{id}/view', ['App\Controllers\Api\AdController', 'viewAd']);
$router->post('/ads/complete-view', ['App\Controllers\Api\AdController', 'completeView']);
$router->get('/ads/{id}', ['App\Controllers\Api\AdController', 'getAd']);

// Tasks API
$router->get('/tasks', ['App\Controllers\Api\TaskController', 'getTasks']);
$router->post('/tasks/{id}/complete', ['App\Controllers\Api\TaskController', 'completeTask']);
$router->get('/tasks/{id}', ['App\Controllers\Api\TaskController', 'getTask']);
$router->get('/tasks/providers', ['App\Controllers\Api\TaskController', 'getProviders']);
$router->get('/tasks/providers/{providerId}/offers', ['App\Controllers\Api\TaskController', 'getProviderOffers']);
$router->post('/tasks/complete-offer', ['App\Controllers\Api\TaskController', 'completeOffer']);

// Wallet API
$router->get('/wallet/balance', ['App\Controllers\Api\WalletController', 'getBalance']);
$router->get('/wallet/transactions', ['App\Controllers\Api\WalletController', 'getTransactions']);
$router->post('/wallet/deposit', ['App\Controllers\Api\WalletController', 'createDeposit']);
$router->post('/wallet/withdraw', ['App\Controllers\Api\WalletController', 'createWithdraw']);

// Advisor API
$router->get('/advisor/ads', ['App\Controllers\Api\AdvisorController', 'getMyAds']);
$router->post('/advisor/ads', ['App\Controllers\Api\AdvisorController', 'createAd']);
$router->put('/advisor/ads/{id}', ['App\Controllers\Api\AdvisorController', 'updateAd']);
$router->delete('/advisor/ads/{id}', ['App\Controllers\Api\AdvisorController', 'deleteAd']);
$router->post('/advisor/ads/{id}/pause', ['App\Controllers\Api\AdvisorController', 'pauseAd']);
$router->post('/advisor/ads/{id}/resume', ['App\Controllers\Api\AdvisorController', 'resumeAd']);
$router->get('/advisor/ads/{id}/stats', ['App\Controllers\Api\AdvisorController', 'getAdStats']);

// Referral API
$router->get('/referral/info', ['App\Controllers\Api\ReferralController', 'getReferralInfo']);
$router->get('/referral/list', ['App\Controllers\Api\ReferralController', 'getReferrals']);
$router->get('/referral/earnings', ['App\Controllers\Api\ReferralController', 'getReferralEarnings']);

// Admin API (protected)
$router->get('/admin/dashboard/stats', ['App\Controllers\Api\Admin\DashboardController', 'getStats']);
$router->get('/admin/users', ['App\Controllers\Api\Admin\UserController', 'getUsers']);
$router->get('/admin/users/{id}', ['App\Controllers\Api\Admin\UserController', 'getUser']);
$router->put('/admin/users/{id}', ['App\Controllers\Api\Admin\UserController', 'updateUser']);
$router->post('/admin/users/{id}/ban', ['App\Controllers\Api\Admin\UserController', 'banUser']);
$router->post('/admin/users/{id}/unban', ['App\Controllers\Api\Admin\UserController', 'unbanUser']);

$router->get('/admin/deposits', ['App\Controllers\Api\Admin\DepositController', 'getDeposits']);
$router->put('/admin/deposits/{id}/approve', ['App\Controllers\Api\Admin\DepositController', 'approveDeposit']);
$router->put('/admin/deposits/{id}/reject', ['App\Controllers\Api\Admin\DepositController', 'rejectDeposit']);

$router->get('/admin/withdrawals', ['App\Controllers\Api\Admin\WithdrawalController', 'getWithdrawals']);
$router->put('/admin/withdrawals/{id}/approve', ['App\Controllers\Api\Admin\WithdrawalController', 'approveWithdrawal']);
$router->put('/admin/withdrawals/{id}/reject', ['App\Controllers\Api\Admin\WithdrawalController', 'rejectWithdrawal']);

$router->get('/admin/ads', ['App\Controllers\Api\Admin\AdController', 'getAds']);
$router->get('/admin/ads/{id}', ['App\Controllers\Api\Admin\AdController', 'getAd']);
$router->put('/admin/ads/{id}/approve', ['App\Controllers\Api\Admin\AdController', 'approveAd']);
$router->put('/admin/ads/{id}/reject', ['App\Controllers\Api\Admin\AdController', 'rejectAd']);
$router->put('/admin/ads/{id}/pause', ['App\Controllers\Api\Admin\AdController', 'pauseAd']);
$router->put('/admin/ads/{id}/resume', ['App\Controllers\Api\Admin\AdController', 'resumeAd']);

$router->get('/admin/tasks', ['App\Controllers\Api\Admin\TaskController', 'getTasks']);
$router->post('/admin/tasks', ['App\Controllers\Api\Admin\TaskController', 'createTask']);
$router->put('/admin/tasks/{id}', ['App\Controllers\Api\Admin\TaskController', 'updateTask']);
$router->delete('/admin/tasks/{id}', ['App\Controllers\Api\Admin\TaskController', 'deleteTask']);
$router->get('/admin/tasks/{id}/submissions', ['App\Controllers\Api\Admin\TaskController', 'getTaskSubmissions']);
$router->put('/admin/tasks/{id}/submissions/{submissionId}/approve', ['App\Controllers\Api\Admin\TaskController', 'approveSubmission']);
$router->put('/admin/tasks/{id}/submissions/{submissionId}/reject', ['App\Controllers\Api\Admin\TaskController', 'rejectSubmission']);

$router->get('/admin/settings', ['App\Controllers\Api\Admin\SettingController', 'getSettings']);
$router->put('/admin/settings', ['App\Controllers\Api\Admin\SettingController', 'updateSettings']);

$router->get('/admin/notifications', ['App\Controllers\Api\Admin\NotificationController', 'getNotifications']);
$router->post('/admin/notifications', ['App\Controllers\Api\Admin\NotificationController', 'createNotification']);
$router->delete('/admin/notifications/{id}', ['App\Controllers\Api\Admin\NotificationController', 'deleteNotification']);
$router->post('/admin/notifications/mark-all-read', ['App\Controllers\Api\Admin\NotificationController', 'markAllRead']);
$router->post('/admin/notifications/clear', ['App\Controllers\Api\Admin\NotificationController', 'clearNotifications']);

$router->get('/admin/reports/earnings', ['App\Controllers\Api\Admin\ReportController', 'getEarningsReport']);
$router->get('/admin/reports/ads', ['App\Controllers\Api\Admin\ReportController', 'getAdsReport']);
$router->get('/admin/reports/users', ['App\Controllers\Api\Admin\ReportController', 'getUsersReport']);
$router->get('/admin/reports/referrals', ['App\Controllers\Api\Admin\ReportController', 'getReferralsReport']);

$router->get('/admin/security/logs', ['App\Controllers\Api\Admin\SecurityController', 'getSecurityLogs']);
$router->get('/admin/security/blocked-ips', ['App\Controllers\Api\Admin\SecurityController', 'getBlockedIps']);
$router->post('/admin/security/block-ip', ['App\Controllers\Api\Admin\SecurityController', 'blockIp']);
$router->post('/admin/security/unblock-ip', ['App\Controllers\Api\Admin\SecurityController', 'unblockIp']);

// Public API (no authentication required)
$router->get('/public/stats', ['App\Controllers\Api\PublicController', 'getPublicStats']);
$router->get('/public/settings', ['App\Controllers\Api\PublicController', 'getPublicSettings']);
$router->get('/public/rates', ['App\Controllers\Api\PublicController', 'getRates']);

// Webhook endpoints for external integrations
$router->post('/webhook/payment-callback', ['App\Controllers\Api\WebhookController', 'paymentCallback']);
$router->post('/webhook/task-completion', ['App\Controllers\Api\WebhookController', 'taskCompletion']);

// File upload API
$router->post('/upload/avatar', ['App\Controllers\Api\UploadController', 'uploadAvatar']);
$router->post('/upload/ad-image', ['App\Controllers\Api\UploadController', 'uploadAdImage']);
$router->post('/upload/task-proof', ['App\Controllers\Api\UploadController', 'uploadTaskProof']);

// Search API
$router->get('/search/users', ['App\Controllers\Api\SearchController', 'searchUsers']);
$router->get('/search/ads', ['App\Controllers\Api\SearchController', 'searchAds']);
$router->get('/search/tasks', ['App\Controllers\Api\SearchController', 'searchTasks']);

// Notification API
$router->post('/notifications/mark-all-read', ['App\Controllers\Api\NotificationController', 'markAllRead']);
$router->delete('/notifications/clear', ['App\Controllers\Api\NotificationController', 'clearNotifications']);

// Rate limiting middleware for sensitive endpoints
$rateLimitMiddleware = function($request, $response) {
    $ip = $request->ip();
    $key = "rate_limit:$ip";
    
    // Simple in-memory rate limiting (in production, use Redis)
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + 3600];
    }
    
    $_SESSION[$key]['count']++;
    
    if ($_SESSION[$key]['count'] > 100) { // 100 requests per hour
        $response->error('Rate limit exceeded', 429);
        return false;
    }
    
    if (time() > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = ['count' => 1, 'reset_time' => time() + 3600];
    }
    
    return true;
};

// Apply rate limiting to authentication endpoints
$router->post('/auth/login', ['App\Controllers\Api\AuthController', 'login'], [$rateLimitMiddleware]);
$router->post('/auth/register', ['App\Controllers\Api\AuthController', 'register'], [$rateLimitMiddleware]);
$router->post('/auth/forgot-password', ['App\Controllers\Api\AuthController', 'forgotPassword'], [$rateLimitMiddleware]);

// CORS middleware for API
$corsMiddleware = function($request, $response) {
    $allowedOrigins = \Core\Config::get('security.allowed_origins');
    $origin = $request->header('Origin');
    
    if (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins)) {
        $response->setHeader('Access-Control-Allow-Origin', $origin);
    }
    
    $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    $response->setHeader('Access-Control-Allow-Credentials', 'true');
    
    if ($request->getMethod() === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    return true;
};

// Apply CORS to all API routes
$router->addMiddleware($corsMiddleware);
