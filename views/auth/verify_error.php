<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Verification Error - ' . Config::get('app.name');
$show_navbar = false;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="text-center" style="max-width: 450px;">
        <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
        <h2 class="fw-bold text-dark mb-3">Verification Error</h2>
        <p class="text-muted mb-4">Something went wrong during email verification. Please try again or contact support.</p>
        <a href="<?= $basePath ?>/login" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Login
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
