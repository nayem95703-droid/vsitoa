<?php
$page_title = 'Admin Settings - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$basePath = (string) Config::get('app.base_path', '');
$settings = $settings ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Settings</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-cog me-2"></i>General Settings</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-white-50">App Name</label>
                        <div class="form-control bg-dark text-white border-secondary"><?= htmlspecialchars((string) ($settings['app_name'] ?? '')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">App URL</label>
                        <div class="form-control bg-dark text-white border-secondary"><?= htmlspecialchars((string) ($settings['app_url'] ?? '')) ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50">Environment</label>
                            <div class="form-control bg-dark text-white border-secondary">
                                <span class="badge bg-<?= ($settings['app_env'] ?? '') === 'production' ? 'danger' : 'warning' ?>">
                                    <?= htmlspecialchars(ucfirst((string) ($settings['app_env'] ?? ''))) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50">Debug Mode</label>
                            <div class="form-control bg-dark text-white border-secondary">
                                <span class="badge bg-<?= ($settings['app_debug'] ?? false) ? 'danger' : 'success' ?>">
                                    <?= ($settings['app_debug'] ?? false) ? 'Enabled' : 'Disabled' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-envelope me-2"></i>Mail Settings</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-white-50">SMTP Host</label>
                        <div class="form-control bg-dark text-white border-secondary"><?= htmlspecialchars((string) ($settings['mail_host'] ?? '')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">SMTP Port</label>
                        <div class="form-control bg-dark text-white border-secondary"><?= htmlspecialchars((string) ($settings['mail_port'] ?? '')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">SMTP Username</label>
                        <div class="form-control bg-dark text-white border-secondary"><?= htmlspecialchars((string) ($settings['mail_username'] ?? '')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-dollar-sign me-2"></i>Financial Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50">Deposits Enabled</label>
                            <div class="form-control bg-dark text-white border-secondary">
                                <span class="badge bg-<?= ($settings['deposit_enabled'] ?? true) ? 'success' : 'danger' ?>">
                                    <?= ($settings['deposit_enabled'] ?? true) ? 'Enabled' : 'Disabled' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50">Minimum Withdrawal</label>
                            <div class="form-control bg-dark text-white border-secondary"><?= number_format((float) ($settings['withdrawal_min'] ?? 10), 2) ?> USDT</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50">Withdrawal Fee</label>
                            <div class="form-control bg-dark text-white border-secondary"><?= (float) ($settings['withdrawal_fee'] ?? 2) ?>%</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50">Referral Commission</label>
                            <div class="form-control bg-dark text-white border-secondary"><?= (float) ($settings['referral_commission'] ?? 10) ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-tools me-2"></i>System Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-white-50">Maintenance Mode</label>
                        <div class="form-control bg-dark text-white border-secondary">
                            <span class="badge bg-<?= ($settings['maintenance_mode'] ?? false) ? 'danger' : 'success' ?>">
                                <?= ($settings['maintenance_mode'] ?? false) ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">PHP Version</label>
                        <div class="form-control bg-dark text-white border-secondary"><?= PHP_VERSION ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Server Software</label>
                        <div class="form-control bg-dark text-white border-secondary"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
