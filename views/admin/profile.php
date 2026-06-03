<?php
$page_title = 'Admin Profile - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$admin = \Core\Auth::admin() ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Profile</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-white fw-semibold">Admin Details</div>
                <a class="btn btn-sm admin-btn-accent" href="<?= Config::get('app.base_path') ?>/admin">Back to Dashboard</a>
            </div>
        </div>
        <div class="card-body text-white">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="admin-kpi">
                        <div class="d-flex align-items-center gap-3">
                            <div class="admin-kpi-icon"><i class="fas fa-user"></i></div>
                            <div>
                                <div class="admin-kpi-number"><?= htmlspecialchars($admin['username'] ?? 'Admin') ?></div>
                                <div class="text-white-50">Username</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="admin-kpi">
                        <div class="d-flex align-items-center gap-3">
                            <div class="admin-kpi-icon"><i class="fas fa-id-card"></i></div>
                            <div>
                                <div class="admin-kpi-number"><?= htmlspecialchars((string)($admin['role'] ?? 'admin')) ?></div>
                                <div class="text-white-50">Role</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-light opacity-25">

            <a class="btn btn-sm btn-outline-light" href="<?= Config::get('app.base_path') ?>/admin/logout">
                <i class="fas fa-sign-out-alt me-2"></i>
                Logout
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
