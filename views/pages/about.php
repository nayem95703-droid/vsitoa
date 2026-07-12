<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'About Us - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">About <?= htmlspecialchars(Config::get('app.name') ?: 'VSItoA') ?></h1>
            <p class="lead text-muted">VSItoA is a crypto earning platform where users can earn cryptocurrency by completing tasks, viewing ads, and referring friends.</p>

            <h3 class="mt-5 mb-3">Our Mission</h3>
            <p>We aim to provide a reliable and transparent platform for earning cryptocurrency. Our goal is to connect advertisers with a global audience while rewarding users for their time and attention.</p>

            <h3 class="mt-5 mb-3">How It Works</h3>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                            <h5>Complete Tasks</h5>
                            <p class="text-muted">Browse available tasks and earn rewards for each completion.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-ad fa-3x text-success mb-3"></i>
                            <h5>View Ads</h5>
                            <p class="text-muted">Watch advertisements and earn crypto for your attention.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-warning mb-3"></i>
                            <h5>Refer Friends</h5>
                            <p class="text-muted">Invite friends and earn commission on their activity.</p>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="mt-5 mb-3">For Advertisers</h3>
            <p>Advertise your products and services to a global audience. Set your budget, target specific regions, and track real-time performance analytics.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
