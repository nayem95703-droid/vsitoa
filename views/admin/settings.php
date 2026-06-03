<?php
$page_title = 'Admin Settings - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Settings</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-white fw-semibold">Platform Settings</div>
                <a class="btn btn-sm admin-btn-accent" href="<?= Config::get('app.base_path') ?>/admin">Back to Dashboard</a>
            </div>
        </div>
        <div class="card-body text-white">
            <div class="alert alert-info mb-0">
                This page is ready. Add configuration controls here when available.
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
