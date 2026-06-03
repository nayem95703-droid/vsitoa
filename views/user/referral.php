<?php
$page_title = 'Referral Program - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Referral Program</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-outline-secondary" onclick="refreshReferralData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Referral Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Referrals
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['total_referrals'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Referrals
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['active_referrals'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Commission
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_commission'], 8) ?> USDT
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-gift fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Today's Results
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                +<?= $stats['today_referrals'] ?> / <?= number_format($stats['today_commission'], 8) ?> USDT
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Referral Link Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-share-alt me-2"></i>Your Referral Link
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <label class="form-label">Share this link with friends:</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="referral-link" 
                                       value="<?= Config::get('app.url') ?>/register?ref=<?= $user['referral_code'] ?>" readonly>
                                <button type="button" class="btn btn-outline-success" onclick="copyReferralLink()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <small class="text-muted">You'll earn <?= $commissionRate ?>% commission from your referrals' earnings</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="qr-code-container">
                                <div id="referral-qr"></div>
                                <small class="text-muted">Scan to share</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Share Buttons -->
                    <div class="mt-3">
                        <label class="form-label">Share on social media:</label>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary" onclick="shareOnFacebook()">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </button>
                            <button type="button" class="btn btn-info" onclick="shareOnTwitter()">
                                <i class="fab fa-twitter"></i> Twitter
                            </button>
                            <button type="button" class="btn btn-success" onclick="shareOnWhatsApp()">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </button>
                            <button type="button" class="btn btn-danger" onclick="shareOnReddit()">
                                <i class="fab fa-reddit"></i> Reddit
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="shareByEmail()">
                                <i class="fas fa-envelope"></i> Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Marketing Materials -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bullhorn me-2"></i>Marketing Materials
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="marketingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="banners-tab" data-bs-toggle="tab" data-bs-target="#banners" type="button" role="tab">
                                <i class="fas fa-image me-2"></i>Banners
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="text-ads-tab" data-bs-toggle="tab" data-bs-target="#text-ads" type="button" role="tab">
                                <i class="fas fa-font me-2"></i>Text Ads
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="emails-tab" data-bs-toggle="tab" data-bs-target="#emails" type="button" role="tab">
                                <i class="fas fa-envelope me-2"></i>Email Templates
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="marketingTabsContent">
                        <!-- Banners Tab -->
                        <div class="tab-pane fade show active" id="banners" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6>Leaderboard (728x90)</h6>
                                    <div class="banner-preview" style="width: 100%; max-width: 728px; height: 90px; background: linear-gradient(45deg, #4e73df, #224abe); color: white; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                        <div>
                                            <h5 class="mb-1">Join <?= Config::get('app.name') ?></h5>
                                            <p class="mb-0">Earn Free Crypto - Sign Up Now!</p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="getBannerCode('leaderboard')">
                                        <i class="fas fa-code"></i> Get Code
                                    </button>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6>Medium Rectangle (300x250)</h6>
                                    <div class="banner-preview" style="width: 300px; height: 250px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 5px;">
                                        <i class="fas fa-coins fa-3x mb-3"></i>
                                        <h5>Earn Crypto</h5>
                                        <p class="text-center">Join <?= Config::get('app.name') ?> today!</p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="getBannerCode('medium_rectangle')">
                                        <i class="fas fa-code"></i> Get Code
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Text Ads Tab -->
                        <div class="tab-pane fade" id="text-ads" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6>Short Text Ad</h6>
                                    <div class="border p-3 rounded">
                                        <p class="mb-2"><strong>Earn Free Bitcoin Daily!</strong></p>
                                        <p class="mb-2">Join <?= Config::get('app.name') ?> - View ads, complete tasks, and get paid in cryptocurrency. Free to join!</p>
                                        <p class="mb-0 text-primary"><?= Config::get('app.url') ?>/register?ref=<?= $user['referral_code'] ?></p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyTextAd('short')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6>Long Text Ad</h6>
                                    <div class="border p-3 rounded">
                                        <p class="mb-2"><strong>🚀 Start Earning Cryptocurrency Today! 🚀</strong></p>
                                        <p class="mb-2">Looking for a reliable way to earn Bitcoin and other cryptocurrencies? <?= Config::get('app.name') ?> offers multiple earning methods:</p>
                                        <ul class="mb-2">
                                            <li>✅ View advertisements</li>
                                            <li>✅ Complete surveys and tasks</li>
                                            <li>✅ Refer friends and earn commission</li>
                                            <li>✅ Instant withdrawals</li>
                                        </ul>
                                        <p class="mb-0 text-primary"><?= Config::get('app.url') ?>/register?ref=<?= $user['referral_code'] ?></p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyTextAd('long')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Templates Tab -->
                        <div class="tab-pane fade" id="emails" role="tabpanel">
                            <div class="mb-3">
                                <h6>Email Template</h6>
                                <div class="border p-3 rounded">
                                    <pre class="mb-0">Subject: Earn Free Crypto with <?= Config::get('app.name') ?>

Hi [Friend's Name],

I wanted to share this amazing platform where you can earn real cryptocurrency for free!

<?= Config::get('app.name') ?> lets you:
• View ads and get paid in Bitcoin
• Complete surveys and tasks
• Earn from referrals (10% commission!)
• Withdraw instantly to your wallet

It's completely free to join and I've already earned [Your Amount] USDT!

Sign up here: <?= Config::get('app.url') ?>/register?ref=<?= $user['referral_code'] ?>

Let me know if you have any questions!

Best regards,
<?= $user['username'] ?></pre>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyEmailTemplate()">
                                    <i class="fas fa-copy"></i> Copy Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Referrals -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Referrals</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadMoreReferrals()">
                        <i class="fas fa-plus"></i> Load More
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($recentReferrals)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No referrals yet</h5>
                        <p class="text-muted">Start sharing your referral link to earn commissions!</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Last Active</th>
                                    <th>Earnings From User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReferrals as $referral): ?>
                                <tr>
                                    <td><?= htmlspecialchars($referral['username']) ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'active' => 'success',
                                            'unverified' => 'warning',
                                            'suspended' => 'danger',
                                            'banned' => 'danger'
                                        ];
                                        $color = $statusColors[$referral['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= ucfirst($referral['status']) ?></span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($referral['created_at'])) ?></td>
                                    <td><?= $referral['last_login_at'] ? date('M j, Y', strtotime($referral['last_login_at'])) : 'Never' ?></td>
                                    <td class="text-success"><?= number_format($referral['total_earned_from_user'], 8) ?> USDT</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Commission History</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($earnings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-gift fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No commissions yet</h6>
                    </div>
                    <?php else: ?>
                    <div class="referral-earnings-list">
                        <?php foreach (array_slice($earnings, 0, 5) as $earning): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <small class="text-muted"><?= ucfirst($earning['source_type']) ?></small>
                                <div class="fw-bold"><?= number_format($earning['amount'], 8) ?> USDT</div>
                            </div>
                            <div class="text-end">
                                <small class="text-muted"><?= date('M j', strtotime($earning['created_at'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Banner Code Modal -->
<div class="modal fade" id="bannerCodeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Banner Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">HTML Code:</label>
                    <textarea class="form-control font-monospace" id="banner-code" rows="6" readonly></textarea>
                </div>
                <button type="button" class="btn btn-primary" onclick="copyBannerCode()">
                    <i class="fas fa-copy"></i> Copy Code
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.referral-link-container {
    position: relative;
}

.qr-code-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

#referral-qr {
    width: 150px;
    height: 150px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fc;
    margin-bottom: 10px;
}

.banner-preview {
    margin-bottom: 15px;
}

.referral-earnings-list {
    max-height: 300px;
    overflow-y: auto;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
let qrCodeInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    // Generate QR code for referral link
    generateReferralQR();
    
    // Auto-refresh referral stats
    setInterval(() => {
        refreshReferralStats();
    }, 60000); // Every minute
});

function generateReferralQR() {
    const referralLink = document.getElementById('referral-link').value;
    const qrContainer = document.getElementById('referral-qr');
    
    try {
        qrCodeInstance = new QRCode(qrContainer, {
            text: referralLink,
            width: 150,
            height: 150,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    } catch (error) {
        qrContainer.innerHTML = '<i class="fas fa-qrcode fa-4x text-muted"></i>';
    }
}

function copyReferralLink() {
    const referralLink = document.getElementById('referral-link').value;
    
    navigator.clipboard.writeText(referralLink).then(() => {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
        }, 2000);
    });
}

function shareOnFacebook() {
    const url = encodeURIComponent(document.getElementById('referral-link').value);
    const text = encodeURIComponent('Join ' + '<?= Config::get('app.name') ?>' + ' and earn free cryptocurrency!');
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank');
}

function shareOnTwitter() {
    const url = encodeURIComponent(document.getElementById('referral-link').value);
    const text = encodeURIComponent('Earn free cryptocurrency with ' + '<?= Config::get('app.name') ?>' + '! Join now: ');
    window.open(`https://twitter.com/intent/tweet?text=${text}${url}`, '_blank');
}

function shareOnWhatsApp() {
    const url = encodeURIComponent(document.getElementById('referral-link').value);
    const text = encodeURIComponent('Join ' + '<?= Config::get('app.name') ?>' + ' and earn free cryptocurrency! ' + document.getElementById('referral-link').value);
    window.open(`https://wa.me/?text=${text}`, '_blank');
}

function shareOnReddit() {
    const url = encodeURIComponent(document.getElementById('referral-link').value);
    const title = encodeURIComponent('Earn Free Cryptocurrency - ' + '<?= Config::get('app.name') ?>');
    window.open(`https://reddit.com/submit?url=${url}&title=${title}`, '_blank');
}

function shareByEmail() {
    const subject = encodeURIComponent('Earn Free Cryptocurrency with ' + '<?= Config::get('app.name') ?>');
    const body = encodeURIComponent(`Hi!\n\nI wanted to share this amazing platform where you can earn real cryptocurrency for free!\n\n` + 
        `<?= Config::get('app.name') ?> lets you:\n• View ads and get paid in Bitcoin\n• Complete surveys and tasks\n• Earn from referrals\n• Withdraw instantly\n\n` + 
        `Sign up here: ${document.getElementById('referral-link').value}\n\n` + 
        `Let me know if you have any questions!\n\nBest regards`);
    
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

function getBannerCode(type) {
    const referralLink = document.getElementById('referral-link').value;
    let htmlCode = '';
    
    switch(type) {
        case 'leaderboard':
            htmlCode = `<a href="${referralLink}" target="_blank">
    <img src="YOUR_BANNER_IMAGE_URL" alt="Join <?= Config::get('app.name') ?>" width="728" height="90" border="0">
</a>`;
            break;
        case 'medium_rectangle':
            htmlCode = `<a href="${referralLink}" target="_blank">
    <img src="YOUR_BANNER_IMAGE_URL" alt="Earn Crypto" width="300" height="250" border="0">
</a>`;
            break;
    }
    
    document.getElementById('banner-code').value = htmlCode;
    
    const modal = new bootstrap.Modal(document.getElementById('bannerCodeModal'));
    modal.show();
}

function copyBannerCode() {
    const code = document.getElementById('banner-code');
    
    code.select();
    document.execCommand('copy');
    
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
    }, 2000);
}

function copyTextAd(type) {
    let text = '';
    
    if (type === 'short') {
        text = `Earn Free Bitcoin Daily!\n\nJoin <?= Config::get('app.name') ?> - View ads, complete tasks, and get paid in cryptocurrency. Free to join!\n\n${document.getElementById('referral-link').value}`;
    } else {
        text = `🚀 Start Earning Cryptocurrency Today! 🚀\n\nLooking for a reliable way to earn Bitcoin and other cryptocurrencies? <?= Config::get('app.name') ?> offers multiple earning methods:\n✅ View advertisements\n✅ Complete surveys and tasks\n✅ Refer friends and earn commission\n✅ Instant withdrawals\n\nIt's completely free to join!\n\n${document.getElementById('referral-link').value}`;
    }
    
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Success', 'Text ad copied to clipboard!', 'success');
    });
}

function copyEmailTemplate() {
    const template = `Subject: Earn Free Crypto with <?= Config::get('app.name') ?>

Hi [Friend's Name],

I wanted to share this amazing platform where you can earn real cryptocurrency for free!

<?= Config::get('app.name') ?> lets you:
• View ads and get paid in Bitcoin
• Complete surveys and tasks
• Earn from referrals (10% commission!)
• Withdraw instantly to your wallet

It's completely free to join!

Sign up here: ${document.getElementById('referral-link').value}

Let me know if you have any questions!

Best regards,
<?= $user['username'] ?>`;
    
    navigator.clipboard.writeText(template).then(() => {
        showAlert('Success', 'Email template copied to clipboard!', 'success');
    });
}

function refreshReferralData() {
    location.reload();
}

function refreshReferralStats() {
    const basePath = <?= json_encode((string) Config::get('app.base_path', '')) ?>;
    const apiBase = (basePath ? basePath : '') + '/api';
    const token = localStorage.getItem('jwt_token');
    if (!token) {
        return;
    }
    fetch(`${apiBase}/referral/stats`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update stats on page
            // This would update the stat cards with new data
        }
    })
    .catch(error => {
        console.error('Error refreshing stats:', error);
    });
}

function loadMoreReferrals() {
    // In a real implementation, this would load more referrals via API
    showAlert('Info', 'Loading more referrals...', 'info');
}

function showAlert(title, message, type) {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show slide-in-up`;
    alertElement.innerHTML = `
        <strong>${title}:</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alertElement);
    
    setTimeout(() => {
        if (alertElement.parentNode) {
            alertElement.remove();
        }
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
