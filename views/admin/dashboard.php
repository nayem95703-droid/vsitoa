<?php
// Ensure stats is always defined to prevent undefined variable errors
$stats = $stats ?? [
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
$alerts = $alerts ?? [];
$recentActivities = $recentActivities ?? [];

\Core\Auth::requireAdmin();

$page_title = 'Admin Dashboard - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Dashboard</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <?php if (!empty($alerts)): ?>
        <div class="alert-container">
            <?php foreach ($alerts as $alert): ?>
                <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
                    <i class="fas <?= $alert['icon'] ?> me-2"></i>
                    <?= $alert['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-body">
                    <div class="text-white-50 small">Total Users</div>
                    <div class="h3 text-white mb-0"><?= number_format((float)($stats['users']['total'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-body">
                    <div class="text-white-50 small">Active Users</div>
                    <div class="h3 text-white mb-0"><?= number_format((float)($stats['users']['active'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-body">
                    <div class="text-white-50 small">Total Ads</div>
                    <div class="h3 text-white mb-0"><?= number_format((float)($stats['ads']['total'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-body">
                    <div class="text-white-50 small">Pending Deposits</div>
                    <div class="h3 text-white mb-0"><?= number_format((float)($stats['financial']['pending_deposits'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
