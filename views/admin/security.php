<?php
$page_title = 'Admin Security - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$basePath = (string) Config::get('app.base_path', '');
$securityInfo = $securityInfo ?? [
    'failed_logins_today' => 0,
    'total_admins' => 0,
    'active_sessions' => 0,
    'recent_logins' => [],
];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Security</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-exclamation-triangle me-1"></i>Failed Logins Today</div>
                <h4 class="text-danger mb-0"><?= number_format((float) ($securityInfo['failed_logins_today'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-user-shield me-1"></i>Active Admins</div>
                <h4 class="text-success mb-0"><?= number_format((float) ($securityInfo['total_admins'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-lock me-1"></i>CSRF Protection</div>
                <h4 class="text-info mb-0">Enabled</h4>
            </div>
        </div>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">Recent Login Activity</h6>
        </div>
        <div class="card-body">
            <?php if (empty($securityInfo['recent_logins'] ?? [])): ?>
                <div class="text-center text-muted py-4">No login activity recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($securityInfo['recent_logins'] as $login): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars((string) ($login['username'] ?? 'Unknown')) ?></td>
                                    <td><code><?= htmlspecialchars((string) ($login['ip_address'] ?? '')) ?></code></td>
                                    <td><small class="text-muted"><?= htmlspecialchars(substr((string) ($login['user_agent'] ?? ''), 0, 40)) ?>...</small></td>
                                    <td>
                                        <span class="badge bg-<?= ($login['login_status'] ?? '') === 'success' ? 'success' : 'danger' ?>">
                                            <?= ucfirst(htmlspecialchars((string) ($login['login_status'] ?? ''))) ?>
                                        </span>
                                    </td>
                                    <td><?= !empty($login['login_time']) ? date('M j, Y g:i A', strtotime((string) $login['login_time'])) : '' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
