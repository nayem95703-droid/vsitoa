<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Privacy Policy - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">Privacy Policy</h1>
            <p class="text-muted">Last updated: <?= date('F j, Y') ?></p>

            <h3 class="mt-4">1. Information We Collect</h3>
            <p>We collect information you provide directly, including:</p>
            <ul>
                <li>Account registration details (username, email)</li>
                <li>Payment information (wallet addresses)</li>
                <li>Task completion data</li>
                <li>IP addresses and device information</li>
            </ul>

            <h3 class="mt-4">2. How We Use Your Information</h3>
            <p>We use collected information to:</p>
            <ul>
                <li>Provide and maintain our services</li>
                <li>Process transactions and payments</li>
                <li>Prevent fraud and abuse</li>
                <li>Improve user experience</li>
            </ul>

            <h3 class="mt-4">3. Data Security</h3>
            <p>We implement industry-standard security measures to protect your personal information. However, no method of transmission over the internet is 100% secure.</p>

            <h3 class="mt-4">4. Third-Party Services</h3>
            <p>We may use third-party services that collect information used to identify you. Please refer to their privacy policies for more details.</p>

            <h3 class="mt-4">5. Your Rights</h3>
            <p>You have the right to access, update, or delete your personal information at any time through your account settings.</p>

            <h3 class="mt-4">6. Contact Us</h3>
            <p>If you have questions about this Privacy Policy, please contact us through our support system.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
