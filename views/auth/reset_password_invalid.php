<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Invalid Reset Link - ' . Config::get('app.name');
$show_navbar = false;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="text-center" style="max-width: 450px;">
        <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
        <h2 class="fw-bold text-dark mb-3">Invalid Reset Link</h2>
        <p class="text-muted mb-4">This password reset link is invalid or has expired. Please request a new one.</p>
        <a href="<?= $basePath ?>/forgot-password" class="btn btn-primary me-2">
            <i class="fas fa-redo me-2"></i>Request New Link
        </a>
        <a href="<?= $basePath ?>/login" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Login
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
