<?php
$page_title = 'Admin Reports - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Reports</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">Reports & Logs</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                This page is ready. Connect it to reports and logs when available.
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
