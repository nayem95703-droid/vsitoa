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

<?php if (false): ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= Config::get('app.base_path') ?>/assets/css/admin.css" rel="stylesheet">
    <style>
        body {
            background-color: transparent;
            color: #e5e7eb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-sidebar {
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .stat-card {
            border-left: 4px solid #4e73df;
            background: rgba(255,255,255,0.04);
            border-top: 1px solid rgba(255,255,255,0.08);
            border-right: 1px solid rgba(255,255,255,0.08);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 1.5rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 0.875rem;
            color: rgba(229,231,235,0.72);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .alert-container {
            margin-bottom: 20px;
        }
        .activity-item {
            border-left: 3px solid #e3e6f0;
            padding: 10px 15px;
            margin-bottom: 10px;
            background: rgba(255,255,255,0.04);
            border-radius: 0.35rem;
            transition: all 0.2s ease;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .activity-item:hover {
            border-left-color: #4e73df;
            background: rgba(255,255,255,0.06);
        }
        .activity-time {
            font-size: 0.75rem;
            color: rgba(229,231,235,0.65);
        }
        .activity-user {
            font-weight: 600;
            color: #e5e7eb;
        }
        .activity-action {
            font-size: 0.875rem;
            color: rgba(229,231,235,0.72);
        }
        .quick-action {
            text-decoration: none;
            color: #5a5c69;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .quick-action:hover {
            color: #4e73df;
        }
        .chart-container {
            background: rgba(255,255,255,0.04);
            border-radius: 0.35rem;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255,255,255,0.08);
        }
    </style>
</head>
<body class="admin-body">
    <?php include ROOT_PATH . '/views/partials/admin_navbar.php'; ?>
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <div class="d-flex flex-column flex-shrink-0 p-3 text-white">
            <h4 class="text-center mb-3">
                <i class="fas fa-shield-alt fa-2x mb-2"></i>
                <?= Config::get('app.name') ?>
            </h4>
            <hr class="border-light">
            
            <!-- Admin Menu -->
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin" class="nav-link text-white">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/users" class="nav-link text-white">
                        <i class="fas fa-users me-2"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/deposits" class="nav-link text-white">
                        <i class="fas fa-dollar-sign me-2"></i>
                        Deposits
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/withdrawals" class="nav-link text-white">
                        <i class="fas fa-credit-card me-2"></i>
                        Withdrawals
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/ads" class="nav-link text-white">
                        <i class="fas fa-ad me-2"></i>
                        Ads
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/tasks" class="nav-link text-white">
                        <i class="fas fa-tasks me-2"></i>
                        Tasks
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/referrals" class="nav-link text-white">
                        <i class="fas fa-users me-2"></i>
                        Referrals
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/reports" class="nav-link text-white">
                        <i class="fas fa-chart-bar me-2"></i>
                        Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/notifications" class="nav-link text-white">
                        <i class="fas fa-bell me-2"></i>
                        Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/settings" class="nav-link text-white">
                        <i class="fas fa-cog me-2"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/security" class="nav-link text-white">
                        <i class="fas fa-shield-alt me-2"></i>
                        Security
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/verification-requests" class="nav-link text-white">
                        <i class="fas fa-check-circle me-2"></i>
                        Verification
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/support/tickets" class="nav-link text-white">
                        <i class="fas fa-headset me-2"></i>
                        Support
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= Config::get('app.base_path') ?>/admin/stickers" class="nav-link text-white">
                        <i class="fas fa-icons me-2"></i>
                        Stickers
                    </a>
                </li>
            </ul>
            
            <hr class="border-light">
            
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header with Platform Title and Logout -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">BG2R Crypto Earning Platform</h1>
            </div>
            <div class="card border-danger">
                <div class="card-body py-2 px-3">
                    <button type="button" class="btn btn-danger btn-sm" onclick="adminLogout()">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        </div>

        <!-- System Alerts -->
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <!-- Users Statistics -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-number"><?= number_format($stats['users']['total']) ?></div>
                                <div class="stat-label">Total Users</div>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <div class="text-success">Active: <?= number_format($stats['users']['active']) ?></div>
                                    <div class="text-info">New Today: <?= number_format($stats['users']['new_today']) ?></div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ads Statistics -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-icon">
                                    <i class="fas fa-ad"></i>
                                </div>
                                <div class="stat-number"><?= number_format($stats['ads']['total']) ?></div>
                                <div class="stat-label">Total Ads</div>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <div class="text-success">Active: <?= number_format($stats['ads']['active']) ?></div>
                                    <div class="text-warning">Pending: <?= number_format($stats['ads']['pending']) ?></div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Statistics -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-icon">
                                    <i class="fas fa-bitcoin"></i>
                                </div>
                                <div class="stat-number"><?= number_format($stats['financial']['total_balance'], 8) ?></div>
                                <div class="stat-label">Total Balance</div>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <div class="text-success">Total Earned: <?= number_format($stats['financial']['total_earned'], 8) ?> BTC</div>
                                    <div class="text-danger">Total Withdrawn: <?= number_format($stats['financial']['total_withdrawn'], 8) ?> BTC</div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Statistics -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="stat-number"><?= number_format($stats['tasks']['total']) ?></div>
                                <div class="stat-label">Total Tasks</div>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <div class="text-success">Completed: <?= number_format($stats['tasks']['completed']) ?></div>
                                    <div class="text-info">Active: <?= number_format($stats['tasks']['active']) ?></div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Earnings -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-number"><?= number_format($stats['financial']['pending_deposits']) ?></div>
                                <div class="stat-label">Pending Deposits</div>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <div class="text-warning"><?= number_format($stats['financial']['pending_withdrawals']) ?></div>
                                    <div class="text-info">Pending Withdrawals</div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5 class="mb-3">Earnings Overview (Last 7 Days)</h5>
                    <canvas id="earningsChart" width="400" height="200"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5 class="user-select">Task Completion Rate (Last 7 Days)</h5>
                    <canvas id="tasksChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshActivities()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="recent-activities">
                            <?php if (empty($recentActivities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent activities</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-time">
                                        <?= date('H:i', strtotime($activity['created_at'])) ?>
                                    </div>
                                    <div class="activity-user">
                                        <?= $activity['admin_name'] ?>
                                    </div>
                                    <div class="activity-action">
                                        <?= $activity['action'] ?>
                                    </div>
                                    <div class="activity-details">
                                        <?= $activity['details'] ?? '' ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dashboard Statistics
        const stats = <?= json_encode($stats) ?>;
        
        // Earnings Chart
        const earningsCtx = document.getElementById('earningsChart').getContext('2d');
        const earningsChart = new Chart(earningsCtx, {
            type: 'line',
            data: {
                labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'day 5', 'Day 6', 'Day 7'],
                datasets: [{
                    label: 'Earnings',
                    data: [65, 59, 80, 81, 56, 72, 90],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            ]
        });

        // Tasks Chart
        const tasksCtx = document.getElementById('tasksChart').getContext('2d');
        const tasksChart = new Chart(tasksCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Rejected'],
                datasets: [{
                    data: [stats['tasks']['completed'], stats['tasks']['pending'], 0],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ]
                }]
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(() => {
            refreshDashboard();
        }, 30000);
    </script>
    <script>
        function adminLogout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('<?= Config::get('app.base_path') ?>/admin/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?= $_SESSION['_token'] ?? '' ?>'
                    }
                })
                .then(() => {
                    window.location.href = '<?= Config::get('app.base_path') ?>/admin/login';
                })
                .catch(() => {
                    window.location.href = '<?= Config::get('app.base_path') ?>/admin/login';
                });
            }
        }
    </script>
</body>
</html>

<?php endif; ?>

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
