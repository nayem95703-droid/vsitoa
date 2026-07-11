<?php
$basePath = Config::get('app.base_path');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if ($basePath && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath));
}
if ($currentPath === '') {
    $currentPath = '/';
}
$user = \Core\Auth::user() ?? [];
$freshStatus = $user['status'] ?? '';
if (!empty($user['user_id'])) {
    $freshUser = \Core\Database::fetch("SELECT status FROM users WHERE user_id = ?", [$user['user_id']]);
    $freshStatus = $freshUser['status'] ?? $freshStatus;
}
$blueprintApproved = $freshStatus === 'active';
include_once ROOT_PATH . '/views/partials/blueprint_badge.php';
?>
<div class="d-flex flex-column flex-shrink-0 p-3 bg-light" id="sidebar">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="<?= $basePath ?>/dashboard" class="d-flex align-items-center text-dark text-decoration-none">
            <i class="fas fa-tachometer-alt me-2"></i>
            <span class="fs-6 fw-bold">Dashboard</span>
        </a>
        <button class="btn btn-sm d-lg-none" onclick="toggleSidebar()" id="closeSidebarBtn">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <hr>
    
    <ul class="nav nav-pills flex-column">
        <li class="nav-item">
            <a href="<?= $basePath ?>/dashboard" class="nav-link text-dark <?= $currentPath === '/dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home me-2"></i>
                Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= $basePath ?>/advisor" class="nav-link text-dark <?= $currentPath === '/advisor' ? 'active' : '' ?>">
                <i class="fas fa-bullhorn me-2"></i>
                Offer
            </a>
        </li>
        
        <li class="nav-item">
            <a href="<?= $basePath ?>/earn" class="nav-link text-dark <?= strpos($currentPath, '/earn') === 0 ? 'active' : '' ?>">
                <i class="fas fa-coins me-2"></i>
                Earn / Tasks
            </a>
        </li>
        
        <li class="nav-item">
            <a href="<?= $basePath ?>/wallet" class="nav-link text-dark <?= strpos($currentPath, '/wallet') === 0 ? 'active' : '' ?>">
                <i class="fas fa-wallet me-2"></i>
                Wallet
            </a>
        </li>
        
        <li class="nav-item">
            <a href="<?= $basePath ?>/deposit" class="nav-link text-dark <?= $currentPath === '/deposit' ? 'active' : '' ?>">
                <i class="fas fa-plus-circle me-2"></i>
                Deposit
            </a>
        </li>
        
        <li class="nav-item">
            <a href="<?= $basePath ?>/withdraw" class="nav-link text-dark <?= $currentPath === '/withdraw' ? 'active' : '' ?>">
                <i class="fas fa-minus-circle me-2"></i>
                Withdraw
            </a>
        </li>
        
        <li class="nav-item">
            <a href="<?= $basePath ?>/referral" class="nav-link text-dark <?= $currentPath === '/referral' ? 'active' : '' ?>">
                <i class="fas fa-users me-2"></i>
                Referral
            </a>
        </li>
        
        <li class="nav-item">
            <a href="<?= $basePath ?>/notifications" class="nav-link text-dark <?= $currentPath === '/notifications' ? 'active' : '' ?>">
                <i class="fas fa-bell me-2"></i>
                Notifications
            </a>
        </li>
        
        <li class="nav-item">
            <a href="#advisor-submenu" data-bs-toggle="collapse" class="nav-link text-dark <?= strpos($currentPath, '/advisor') === 0 ? 'active' : '' ?>">
                <i class="fas fa-ad me-2"></i>
                Advisor
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <ul class="collapse nav nav-pills flex-column ms-3" id="advisor-submenu">
                <li class="nav-item">
                    <a href="<?= $basePath ?>/advisor/create-ad" class="nav-link text-dark small <?= $currentPath === '/advisor/create-ad' ? 'active' : '' ?>">
                        <i class="fas fa-plus me-2"></i>
                        Create Ad
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $basePath ?>/advisor/manage-ads" class="nav-link text-dark small <?= $currentPath === '/advisor/manage-ads' ? 'active' : '' ?>">
                        <i class="fas fa-list me-2"></i>
                        Manage Ads
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item">
            <a href="<?= $basePath ?>/support" class="nav-link text-dark <?= $currentPath === '/support' ? 'active' : '' ?>">
                <i class="fas fa-headset me-2"></i>
                Support
            </a>
        </li>
        
        <li class="nav-item">
            <a href="<?= $basePath ?>/settings" class="nav-link text-dark <?= $currentPath === '/settings' ? 'active' : '' ?>">
                <i class="fas fa-cog me-2"></i>
                Settings
            </a>
        </li>
    </ul>
    
    <hr>
    
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://picsum.photos/seed/user<?= \Core\Auth::id() ?>/32/32.jpg" alt="Avatar" width="32" height="32" class="rounded-circle me-2">
            <strong class="me-2"><?= htmlspecialchars($user['username'] ?? 'User') ?></strong>
            <?php if ($blueprintApproved): ?>
                <?php renderBlueprintTick('small', true, 'Blueprint Approved'); ?>
            <?php endif; ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="<?= $basePath ?>/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="<?= $basePath ?>/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-2"></i>Sign out</a></li>
        </ul>
    </div>
</div>

<style>
.sidebar {
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}

@media (min-width: 992px) {
    .sidebar {
        height: calc(100vh - 56px);
        position: sticky;
        top: 56px;
        overflow-y: auto;
    }
}

.sidebar .nav-link {
    border-radius: 0.375rem;
    margin: 0.125rem 0;
    transition: all 0.2s ease;
}

.sidebar .nav-link:hover {
    background-color: rgba(0,0,0,0.05);
    transform: translateX(2px);
}

.sidebar .nav-link.active {
    background-color: #0d6efd;
    color: white !important;
}

.sidebar .nav-link.active:hover {
    background-color: #0b5ed7;
}

.sidebar .collapse .nav-link {
    font-size: 0.875rem;
    padding-left: 2rem;
}

.sidebar .dropdown-toggle::after {
    display: none;
}
</style>
