<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Email Verified - ' . Config::get('app.name');
$show_navbar = false;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="text-center" style="max-width: 450px;">
        <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
        <h2 class="fw-bold text-dark mb-3">Email Verified!</h2>
        <p class="text-muted mb-4">Your email has been verified successfully. You can now log in to your account.</p>
        <a href="<?= $basePath ?>/login" class="btn btn-primary">
            <i class="fas fa-sign-in-alt me-2"></i>Login Now
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
