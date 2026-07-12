<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Terms of Service - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">Terms of Service</h1>
            <p class="text-muted">Last updated: <?= date('F j, Y') ?></p>

            <h3 class="mt-4">1. Acceptance of Terms</h3>
            <p>By accessing and using <?= htmlspecialchars(Config::get('app.name') ?: 'VSItoA') ?>, you agree to be bound by these Terms of Service.</p>

            <h3 class="mt-4">2. Account Registration</h3>
            <p>You must provide accurate information when creating an account. You are responsible for maintaining the security of your account credentials.</p>

            <h3 class="mt-4">3. Earning &amp; Withdrawals</h3>
            <p>Earnings are credited upon task completion and approval. Withdrawals are subject to minimum amount requirements and may take up to 48 hours to process.</p>

            <h3 class="mt-4">4. Prohibited Activities</h3>
            <ul>
                <li>Using bots or automated tools to complete tasks</li>
                <li>Creating multiple accounts</li>
                <li>Submitting false or misleading information</li>
                <li>Attempting to exploit the platform</li>
            </ul>

            <h3 class="mt-4">5. Account Suspension</h3>
            <p>We reserve the right to suspend or terminate accounts that violate these terms without prior notice.</p>

            <h3 class="mt-4">6. Limitation of Liability</h3>
            <p><?= htmlspecialchars(Config::get('app.name') ?: 'VSItoA') ?> is not liable for any indirect, incidental, or consequential damages arising from your use of the platform.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
