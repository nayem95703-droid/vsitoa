<?php $basePath = Config::get('app.base_path'); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid px-3">
        <?php if (isset($_SESSION['user_id'])): ?>
        <button class="btn btn-sm btn-outline-light d-lg-none me-2" onclick="toggleSidebar()" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <?php endif; ?>
        <a class="navbar-brand" href="<?= $basePath ?>/">
            <i class="fas fa-coins me-2"></i>
            <?= Config::get('app.name') ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                
                <?php if (\Core\Auth::check()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="earnDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-coins me-1"></i> Earn
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $basePath ?>/earn"><i class="fas fa-eye me-2"></i>View Ads</a></li>
                            <li><a class="dropdown-item" href="<?= $basePath ?>/tasks"><i class="fas fa-tasks me-2"></i>Tasks</a></li>
                            <li><a class="dropdown-item" href="<?= $basePath ?>/advisor"><i class="fas fa-ad me-2"></i>Advisor</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/wallet">
                            <i class="fas fa-wallet me-1"></i> Wallet
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/referral">
                            <i class="fas fa-users me-1"></i> Referral
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (\Core\Auth::check()): ?>
                    <!-- User Balances -->
                    <?php
                    $navAdvisorBalance = 0;
                    $navEarningBalance = 0;
                    try {
                        $navUser = \Core\Database::fetch("SELECT advisor_balance, earning_balance FROM users WHERE user_id = ?", [\Core\Auth::id()]);
                        $navAdvisorBalance = (float) ($navUser['advisor_balance'] ?? 0);
                        $navEarningBalance = (float) ($navUser['earning_balance'] ?? 0);
                    } catch (\Exception $e) { /* fallback to 0 */ }
                    ?>
                    <li class="nav-item d-none d-lg-block">
                        <span class="navbar-text me-3 d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark">USDT</span>
                            <span class="small text-white-50">Advisor:</span>
                            <span id="advisor-balance" class="fw-semibold">
                                <?= number_format($navAdvisorBalance, 2) ?>
                            </span>
                            <span class="small text-white-50">Earning:</span>
                            <span id="earning-balance" class="fw-semibold">
                                <?= number_format($navEarningBalance, 2) ?>
                            </span>
                        </span>
                    </li>
                    
                    <!-- Notifications (commented out - user requested removal)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" id="notification-list">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="<?= $basePath ?>/notifications">View all notifications</a></li>
                        </ul>
                    </li>
                    -->
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <?php 
                            $user = \Core\Auth::user();
                            $isVerified = $user['is_verified'] ?? false;
                            $blueprintApproved = false;
                            if (!empty($user['user_id'])) {
                                $freshUser = \Core\Database::fetch("SELECT status FROM users WHERE user_id = ?", [$user['user_id']]);
                                $blueprintApproved = (($freshUser['status'] ?? '') === 'active');
                            } else {
                                $blueprintApproved = (($user['status'] ?? '') === 'active');
                            }
                            if ($blueprintApproved):
                                include_once ROOT_PATH . '/views/partials/blueprint_badge.php';
                                renderBlueprintTick('small', true, 'Blueprint Approved');
                                echo ' ';
                            endif;
                            if ($isVerified):
                                include_once ROOT_PATH . '/views/partials/verified_badge.php';
                                renderVerifiedBadge('small', false, 'Verified User');
                                echo ' ';
                            endif;
                            ?>
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars(\Core\Auth::user()['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= $basePath ?>/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <?php if ($isVerified): ?>
                                <li><a class="dropdown-item" href="<?= $basePath ?>/stickers"><i class="fas fa-star me-2"></i>My Stickers</a></li>
                                <li><a class="dropdown-item" href="<?= $basePath ?>/support/tickets"><i class="fas fa-headset me-2"></i>Priority Support</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= $basePath ?>/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="<?= $basePath ?>/support"><i class="fas fa-life-ring me-2"></i>Support</a></li>
                            <?php if (!$isVerified): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-primary" href="<?= $basePath ?>/verify"><i class="fas fa-check-circle me-2"></i>Get Verified</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/login">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="<?= $basePath ?>/register">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('<?= $basePath ?>/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '<?= $basePath ?>/login';
            }
        });
    }
}

// Load notifications
function loadNotifications() {
    const token = localStorage.getItem('jwt_token');
    if (!token) return;
    fetch('<?= $basePath ?>/api/user/notifications', {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notifications = data.data.filter(n => !n.is_read);
            const badge = document.querySelector('.notification-count');
            if (badge) {
                badge.textContent = notifications.length;
                badge.style.display = notifications.length > 0 ? '' : 'none';
            }
            
            const notificationList = document.getElementById('notification-list');
            if (notificationList && notifications.length > 0) {
                const html = notifications.map(notif => `
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); markNotificationRead(${notif.id})">
                            <div class="small fw-bold">${notif.title || 'Notification'}</div>
                            <div class="text-muted small">${notif.message}</div>
                            <div class="text-muted small">${new Date(notif.created_at).toLocaleString()}</div>
                        </a>
                    </li>
                `).join('');
                notificationList.innerHTML = '<li><h6 class="dropdown-header">Notifications</h6></li><li><hr class="dropdown-divider"></li>' + html + '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-center" href="<?= $basePath ?>/notifications">View all notifications</a></li>';
            }
        }
    })
    .catch(() => {});
}

// Auto-refresh balance
function updateBalance() {
    fetch('<?= $basePath ?>/api/wallet/balance', {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const advisorBalance = document.getElementById('advisor-balance');
            const earningBalance = document.getElementById('earning-balance');
            if (advisorBalance) {
                advisorBalance.textContent = parseFloat(data.data.advisor_balance || 0).toFixed(2);
            }
            if (earningBalance) {
                earningBalance.textContent = parseFloat(data.data.earning_balance || 0).toFixed(2);
            }
        }
    });
}

// Initialize
if (document.querySelector('.notification-count')) {
    loadNotifications();
    setInterval(loadNotifications, 60000); // Refresh every minute
}

if (document.getElementById('earning-balance')) {
    setInterval(updateBalance, 30000); // Refresh every 30 seconds
}

function markNotificationRead(notificationId) {
    fetch(`<?= $basePath ?>/api/user/notifications/${notificationId}/read`, {
        method: 'PUT',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}
</script>
