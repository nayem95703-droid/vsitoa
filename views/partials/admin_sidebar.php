<?php
$basePath = Config::get('app.base_path');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if ($basePath && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath));
}
if ($currentPath === '') {
    $currentPath = '/';
}
$admin = \Core\Auth::admin() ?? [];
?>
<div class="d-flex flex-column flex-shrink-0 p-3 bg-dark text-white admin-sidebar-shell" id="sidebar">
    <div class="d-flex align-items-center justify-content-between mb-3 d-lg-none">
        <span class="fs-6 fw-bold">Admin Menu</span>
        <button class="btn btn-sm btn-outline-light" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin" class="nav-link text-white <?= $currentPath === '/admin' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/users" class="nav-link text-white <?= $currentPath === '/admin/users' ? 'active' : '' ?>">
                <i class="fas fa-users me-2"></i>
                Users
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/deposits" class="nav-link text-white <?= $currentPath === '/admin/deposits' ? 'active' : '' ?>">
                <i class="fas fa-dollar-sign me-2"></i>
                Deposits
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/withdrawals" class="nav-link text-white <?= $currentPath === '/admin/withdrawals' ? 'active' : '' ?>">
                <i class="fas fa-credit-card me-2"></i>
                Withdrawals
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/ads" class="nav-link text-white <?= $currentPath === '/admin/ads' ? 'active' : '' ?>">
                <i class="fas fa-ad me-2"></i>
                Ads
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/tasks" class="nav-link text-white <?= $currentPath === '/admin/tasks' ? 'active' : '' ?>">
                <i class="fas fa-tasks me-2"></i>
                Tasks
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/referrals" class="nav-link text-white <?= $currentPath === '/admin/referrals' ? 'active' : '' ?>">
                <i class="fas fa-users me-2"></i>
                Referrals
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/reports" class="nav-link text-white <?= $currentPath === '/admin/reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar me-2"></i>
                Reports
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/notifications" class="nav-link text-white <?= $currentPath === '/admin/notifications' ? 'active' : '' ?>">
                <i class="fas fa-bell me-2"></i>
                Notifications
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/verification-requests" class="nav-link text-white <?= $currentPath === '/admin/verification-requests' ? 'active' : '' ?>">
                <i class="fas fa-check-circle me-2"></i>
                Verification
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/support/tickets" class="nav-link text-white <?= $currentPath === '/admin/support/tickets' ? 'active' : '' ?>">
                <i class="fas fa-headset me-2"></i>
                Support
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/stickers" class="nav-link text-white <?= $currentPath === '/admin/stickers' ? 'active' : '' ?>">
                <i class="fas fa-icons me-2"></i>
                Stickers
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/settings" class="nav-link text-white <?= $currentPath === '/admin/settings' ? 'active' : '' ?>">
                <i class="fas fa-cog me-2"></i>
                Settings
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= $basePath ?>/admin/security" class="nav-link text-white <?= $currentPath === '/admin/security' ? 'active' : '' ?>">
                <i class="fas fa-shield-alt me-2"></i>
                Security
            </a>
        </li>
    </ul>
</div>

