<?php
$page_title = 'Dashboard - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

// Get user statistics
$user = \Core\Auth::user() ?? [];
$userId = \Core\Auth::id();
$basePath = (string) Config::get('app.base_path', '');

if (!$userId) {
    (new \Core\Response())->redirect('/login');
    exit;
}

// Include verified badge component
include_once ROOT_PATH . '/views/partials/verified_badge.php';

// Include blueprint tick component
include_once ROOT_PATH . '/views/partials/blueprint_badge.php';

// Dashboard statistics with error handling
$stats = [
    'balance' => 0,
    'total_earned' => 0,
    'total_withdrawn' => 0,
    'active_ads' => 0,
    'today_views' => 0,
    'today_earnings' => 0,
    'total_referrals' => 0,
    'today_referrals' => 0,
    'today_referral_earnings' => 0,
];
$recentActivities = [];
$availableAds = 0;
$availableTasks = 0;
$isVerified = false;
$blueprintApproved = false;

try {
    $userStatus = \Core\Database::fetchColumn("SELECT status FROM users WHERE user_id = ?", [$userId]);
    $blueprintApproved = (($userStatus ?? '') === 'active');
} catch (\Throwable $e) {
    \Core\Logger::warning('Dashboard: failed to load user status - ' . $e->getMessage());
}

try {
    $isVerified = (bool) \Core\Database::fetchColumn("SELECT COALESCE(is_verified, 0) FROM users WHERE user_id = ?", [$userId]);
} catch (\Throwable $e) {
    \Core\Logger::warning('Dashboard: failed to load verification status - ' . $e->getMessage());
}

try {
    $result = \Core\Database::fetch("
        SELECT 
            u.earning_balance,
            u.advisor_balance,
            u.total_earned,
            u.total_withdrawn,
            (SELECT COUNT(*) FROM ads WHERE user_id = ? AND status = 'active') as active_ads,
            (SELECT COUNT(*) FROM ad_views WHERE viewer_user_id = ? AND DATE(created_at) = CURDATE() AND is_valid = TRUE) as today_views,
            (SELECT COALESCE(SUM(earned_amount), 0) FROM ad_views WHERE viewer_user_id = ? AND DATE(created_at) = CURDATE() AND is_valid = TRUE) as today_earnings
        FROM users u WHERE u.user_id = ?
    ", [$userId, $userId, $userId, $userId]);
    if ($result) {
        $stats = array_merge($stats, $result);
        $stats['balance'] = (float) ($stats['earning_balance'] ?? 0);
    }
} catch (\Throwable $e) {
    \Core\Logger::warning('Dashboard: failed to load stats - ' . $e->getMessage());
}

try {
    if (\Core\Database::tableExists('referrals')) {
        $stats['total_referrals'] = (int) \Core\Database::fetchColumn(
            "SELECT COUNT(*) FROM referrals WHERE referrer_id = ?", [$userId]
        );
        $stats['today_referrals'] = (int) \Core\Database::fetchColumn(
            "SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND DATE(created_at) = CURDATE()", [$userId]
        );
    }
} catch (\Throwable $e) {
    \Core\Logger::warning('Dashboard: failed to load referral stats - ' . $e->getMessage());
}

try {
    if (\Core\Database::tableExists('referral_earnings')) {
        $stats['today_referral_earnings'] = (float) \Core\Database::fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM referral_earnings WHERE referrer_id = ? AND DATE(created_at) = CURDATE()", [$userId]
        );
    }
} catch (\Throwable $e) {
    \Core\Logger::warning('Dashboard: failed to load referral earnings - ' . $e->getMessage());
}

try {
    $recentActivities = \Core\Database::fetchAll("
        SELECT type, amount, description, created_at
        FROM wallet_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC LIMIT 10
    ", [$userId]);
} catch (\Throwable $e) {
    \Core\Logger::warning('Dashboard: failed to load activities - ' . $e->getMessage());
}

try {
    $availableAds = (int) \Core\Database::fetchColumn("
        SELECT COUNT(*) FROM ads 
        WHERE status = 'active' AND views_received < total_views AND user_id != ?
    ", [$userId]);
} catch (\Throwable $e) {
    \Core\Logger::warning('Dashboard: failed to load ads count - ' . $e->getMessage());
}

try {
    $availableTasks = (int) \Core\Database::fetchColumn("
        SELECT COUNT(*) FROM tasks 
        WHERE status = 'active' AND (expires_at IS NULL OR expires_at > NOW())
    ", []);
} catch (\Throwable $e) {
    \Core\Logger::warning('Dashboard: failed to load tasks count - ' . $e->getMessage());
}

$broadcastNotifications = [];
if ($userId) {
    try {
        $broadcastNotifications = \Core\Database::fetchAll(
            "SELECT id, title, message, created_at FROM notifications WHERE user_id = ? AND type = 'broadcast' AND is_read = 0 ORDER BY created_at DESC LIMIT 5",
            [$userId]
        );
    } catch (\Throwable $e) {}
}

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div class="d-flex align-items-center gap-3">
            <h1 class="h2">Dashboard</h1>
            <?php if ($blueprintApproved): ?>
                <?php renderBlueprintTick('small', true, 'Blueprint Approved'); ?>
            <?php endif; ?>
            <?php if ($isVerified): ?>
                <?php renderVerifiedBadge('normal', true, 'Verified User'); ?>
            <?php endif; ?>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($broadcastNotifications)): ?>
        <?php foreach ($broadcastNotifications as $bnote): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fas fa-bullhorn me-2 mt-1"></i>
                    <div class="flex-grow-1">
                        <strong><?= htmlspecialchars($bnote['title']) ?>:</strong> <?= htmlspecialchars($bnote['message']) ?>
                        <br><small class="text-muted"><?= date('M j, Y g:i A', strtotime($bnote['created_at'])) ?></small>
                    </div>
                    <a href="<?= $basePath ?>/notifications/mark-read?id=<?= (int) $bnote['id'] ?>" class="btn-close ms-2" aria-label="Dismiss"></a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($isVerified): ?>
    <!-- Verification Benefits Alert -->
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-shield-alt me-2"></i>
            <div class="flex-grow-1">
                <strong>Verified Account Benefits Active!</strong> You have enhanced security, priority support, and exclusive features.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Balance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['balance'], 8) ?> USDT
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today's Earnings
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['today_earnings'], 8) ?> USDT
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Offer
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?= $availableAds + $availableTasks ?>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 100%" aria-valuenow="<?= $availableAds + $availableTasks ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Referrals
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['total_referrals'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Earnings Overview</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="earningsDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="loadEarningsChart('7d')">Last 7 days</a>
                            <a class="dropdown-item" href="#" onclick="loadEarningsChart('30d')">Last 30 days</a>
                            <a class="dropdown-item" href="#" onclick="loadEarningsChart('90d')">Last 90 days</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="earningsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Earnings Sources</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="earningsSourcesChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Ad Views
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Tasks
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Referrals
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivities)): ?>
                        <p class="text-muted">No recent activities</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-<?= $activity['type'] === 'earn' ? 'success' : ($activity['type'] === 'withdraw' ? 'danger' : 'info') ?>"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">
                                            <?= ucfirst($activity['type']) ?>
                                            <span class="float-end text-<?= $activity['type'] === 'earn' ? 'success' : ($activity['type'] === 'withdraw' ? 'danger' : 'info') ?>">
                                                <?= $activity['type'] === 'earn' ? '+' : '-' ?><?= number_format($activity['amount'], 8) ?> USDT
                                            </span>
                                        </h6>
                                        <p class="timeline-text"><?= htmlspecialchars($activity['description']) ?></p>
                                        <small class="text-muted"><?= date('M j, Y H:i', strtotime($activity['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <a href="/earn" class="btn btn-primary btn-block">
                                <i class="fas fa-eye me-2"></i>View Ads
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="/tasks" class="btn btn-success btn-block">
                                <i class="fas fa-tasks me-2"></i>Complete Tasks
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="/deposit" class="btn btn-info btn-block">
                                <i class="fas fa-plus me-2"></i>Deposit Funds
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="/referral" class="btn btn-warning btn-block">
                                <i class="fas fa-users me-2"></i>Refer Friends
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="/advisor/create-ad" class="btn btn-secondary btn-block">
                                <i class="fas fa-ad me-2"></i>Create Ad
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="/withdraw" class="btn btn-danger btn-block">
                                <i class="fas fa-minus me-2"></i>Withdraw
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.timeline {
    position: relative;
    padding: 0;
    list-style: none;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e3e6f0;
}

.timeline-content {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #e3e6f0;
}

.timeline-title {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
}

.timeline-text {
    margin: 0 0 5px 0;
    font-size: 13px;
    color: #5a5c69;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let earningsChart, earningsSourcesChart;

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeEarningsChart();
    initializeEarningsSourcesChart();
});

function initializeEarningsChart() {
    const ctx = document.getElementById('earningsChart').getContext('2d');
    earningsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Daily Earnings',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(8) + ' USDT';
                        }
                    }
                }
            }
        }
    });
    
    loadEarningsChart('7d');
}

function initializeEarningsSourcesChart() {
    const ctx = document.getElementById('earningsSourcesChart').getContext('2d');
    earningsSourcesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Ad Views', 'Tasks', 'Referrals'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    loadEarningsSources();
}

function loadEarningsChart(period) {
    fetch(`/api/user/earnings?period=${period}`, {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            earningsChart.data.labels = data.data.labels;
            earningsChart.data.datasets[0].data = data.data.earnings;
            earningsChart.update();
        }
    });
}

function loadEarningsSources() {
    fetch('/api/user/earnings-sources', {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            earningsSourcesChart.data.datasets[0].data = data.data;
            earningsSourcesChart.update();
        }
    });
}

function refreshDashboard() {
    location.reload();
}
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
