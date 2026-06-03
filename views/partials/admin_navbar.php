<?php $basePath = Config::get('app.base_path'); $admin = \Core\Auth::admin() ?? []; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark admin-navbar">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="<?= $basePath ?>/admin">
            <i class="fas fa-user-shield me-2"></i>
            Admin Panel
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>/admin"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>/admin/users"><i class="fas fa-users me-1"></i>Users</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>/admin/deposits"><i class="fas fa-dollar-sign me-1"></i>Deposits</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>/admin/withdrawals"><i class="fas fa-credit-card me-1"></i>Withdrawals</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>/admin/ads"><i class="fas fa-ad me-1"></i>Ads</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>/admin/tasks"><i class="fas fa-tasks me-1"></i>Tasks</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>/admin/reports"><i class="fas fa-chart-bar me-1"></i>Reports</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminMoreDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-layer-group me-1"></i>
                        More
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $basePath ?>/admin/referrals"><i class="fas fa-users me-2"></i>Referrals</a></li>
                        <li><a class="dropdown-item" href="<?= $basePath ?>/admin/notifications"><i class="fas fa-bell me-2"></i>Notifications</a></li>
                        <li><a class="dropdown-item" href="<?= $basePath ?>/admin/verification-requests"><i class="fas fa-check-circle me-2"></i>Verification</a></li>
                        <li><a class="dropdown-item" href="<?= $basePath ?>/admin/support/tickets"><i class="fas fa-headset me-2"></i>Support</a></li>
                        <li><a class="dropdown-item" href="<?= $basePath ?>/admin/stickers"><i class="fas fa-icons me-2"></i>Stickers</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $basePath ?>/admin/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><a class="dropdown-item" href="<?= $basePath ?>/admin/security"><i class="fas fa-shield-alt me-2"></i>Security</a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminUserDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-cog me-1"></i>
                        <?= htmlspecialchars($admin['username'] ?? 'Admin') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= $basePath ?>/admin/profile"><i class="fas fa-id-badge me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= $basePath ?>/admin/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
