<?php
$page_title = 'Earn - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$todayViews = $todayViews ?? 0;
$dailyLimit = $dailyLimit ?? Config::get('max_daily_ads', 100);
$ads = $ads ?? [];

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Earn Crypto</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshAds()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary">Today's Views</h5>
                    <h2 class="text-primary"><?= $todayViews ?></h2>
                    <small class="text-muted">of <?= $dailyLimit ?> daily limit</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">Available Ads</h5>
                    <h2 class="text-success"><?= count($ads) ?></h2>
                    <small class="text-muted">ready to view</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info">Avg. Reward</h5>
                    <h2 class="text-info">
                        <?= count($ads) > 0 ? number_format(array_sum(array_column($ads, 'cost_per_view')) / count($ads), 8) : '0.00000000' ?>
                    </h2>
                    <small class="text-muted">USDT per view</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning">Potential Earnings</h5>
                    <h2 class="text-warning">
                        <?= number_format(array_sum(array_column($ads, 'cost_per_view')) * min($dailyLimit - $todayViews, count($ads)), 8) ?>
                    </h2>
                    <small class="text-muted">USDT available</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4" id="adTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                <i class="fas fa-list me-2"></i>All Ads (<?= count($ads) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="surf-tab" data-bs-toggle="tab" data-bs-target="#surf" type="button" role="tab">
                <i class="fas fa-eye me-2"></i>Surf Ads
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="window-tab" data-bs-toggle="tab" data-bs-target="#window" type="button" role="tab">
                <i class="fas fa-window-maximize me-2"></i>Window Ads
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="video-tab" data-bs-toggle="tab" data-bs-target="#video" type="button" role="tab">
                <i class="fas fa-video me-2"></i>Video Ads
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="article-tab" data-bs-toggle="tab" data-bs-target="#article" type="button" role="tab">
                <i class="fas fa-newspaper me-2"></i>Article Ads
            </button>
        </li>
    </ul>

    <!-- Ads Content -->
    <div class="tab-content" id="adTabsContent">
        <!-- All Ads Tab -->
        <div class="tab-pane fade show active" id="all" role="tabpanel">
            <div class="row" id="all-ads-container">
                <?php if (empty($ads)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No ads available</h4>
                            <p class="text-muted">Check back later for new earning opportunities</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($ads as $ad): ?>
                        <div class="col-lg-6 mb-4">
                            <?= renderAdCard($ad) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Surf Ads Tab -->
        <div class="tab-pane fade" id="surf" role="tabpanel">
            <div class="row" id="surf-ads-container">
                <?php
                $surfAds = array_filter($ads, fn($ad) => $ad['ad_type'] === 'surf');
                foreach ($surfAds as $ad): ?>
                    <div class="col-lg-6 mb-4">
                        <?= renderAdCard($ad) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Window Ads Tab -->
        <div class="tab-pane fade" id="window" role="tabpanel">
            <div class="row" id="window-ads-container">
                <?php
                $windowAds = array_filter($ads, fn($ad) => $ad['ad_type'] === 'window');
                foreach ($windowAds as $ad): ?>
                    <div class="col-lg-6 mb-4">
                        <?= renderAdCard($ad) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Video Ads Tab -->
        <div class="tab-pane fade" id="video" role="tabpanel">
            <div class="row" id="video-ads-container">
                <?php
                $videoAds = array_filter($ads, fn($ad) => $ad['ad_type'] === 'video');
                foreach ($videoAds as $ad): ?>
                    <div class="col-lg-6 mb-4">
                        <?= renderAdCard($ad) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Article Ads Tab -->
        <div class="tab-pane fade" id="article" role="tabpanel">
            <div class="row" id="article-ads-container">
                <?php
                $articleAds = array_filter($ads, fn($ad) => $ad['ad_type'] === 'article');
                foreach ($articleAds as $ad): ?>
                    <div class="col-lg-6 mb-4">
                        <?= renderAdCard($ad) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Report Ad Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fas fa-flag me-2"></i>Report Ad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportModalBody">
                <!-- Report form will be loaded here -->
            </div>
            <input type="hidden" id="reportModalAdId" value="">
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="submitReportBtn" onclick="submitReport()">
                    <i class="fas fa-paper-plane me-1"></i>Submit Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Ad View Modal -->
<div class="modal fade" id="adViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Advertisement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ad-view-content">
                    <!-- Ad content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <span class="badge bg-primary" id="ad-timer">--</span>
                        <span class="text-muted ms-2">seconds remaining</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="confirm-view-btn" disabled>
                            <i class="fas fa-check me-2"></i>Confirm View
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ad View Overlay -->
<div id="ad-view-overlay" class="ad-view-overlay" style="display: none;">
    <div class="ad-timer-container">
        <h4>Viewing Advertisement</h4>
        <div class="timer mb-3" id="overlay-timer">--</div>
        <p class="text-muted">Keep this tab open and focused</p>
        <div class="progress mb-3">
            <div class="progress-bar" id="timer-progress" role="progressbar" style="width: 0%"></div>
        </div>
        <button type="button" class="btn btn-danger" onclick="closeAdView()">
            <i class="fas fa-times me-2"></i>Cancel
        </button>
    </div>
</div>

<?php
function renderAdCard($ad) {
    $typeIcons = [
        'surf' => 'fa-eye',
        'window' => 'fa-window-maximize',
        'video' => 'fa-video',
        'article' => 'fa-newspaper'
    ];
    
    $typeColors = [
        'surf' => 'primary',
        'window' => 'info',
        'video' => 'success',
        'article' => 'warning'
    ];
    
    $icon = $typeIcons[$ad['ad_type']] ?? 'fa-ad';
    $color = $typeColors[$ad['ad_type']] ?? 'secondary';
    
    return "
    <div class='ad-card' data-ad-id='{$ad['ad_id']}'>
        <div class='card-body'>
            <div class='d-flex justify-content-between align-items-start mb-3'>
                <div>
                    <span class='badge bg-{$color} mb-2'>
                        <i class='fas {$icon} me-1'></i>
                        " . ucfirst($ad['ad_type']) . "
                    </span>
                    <h6 class='card-title mb-1'>" . htmlspecialchars($ad['ad_title']) . "</h6>
                    <p class='text-muted small mb-2'>" . htmlspecialchars(substr($ad['description'] ?? '', 0, 100)) . "</p>
                </div>
                <div class='text-end'>
                    <div class='text-success fw-bold'>" . number_format($ad['cost_per_view'], 8) . " USDT</div>
                    <small class='text-muted'>per view</small>
                </div>
            </div>
            
            <div class='ad-preview mb-3'>
                " . ($ad['preview_image'] ? "<img src='{$ad['preview_image']}' alt='Ad preview' class='img-fluid rounded'>" : "
                    <div class='ad-preview-placeholder'>
                        <i class='fas {$icon} fa-2x text-muted'></i>
                        <p class='text-muted mb-0'>Ad Preview</p>
                    </div>
                ") . "
            </div>
            
            <div class='d-flex justify-content-between align-items-center'>
                <div>
                    <small class='text-muted'>
                        <i class='fas fa-clock me-1'></i>
                        {$ad['view_time']} seconds
                    </small>
                    " . ($ad['auto_redirect'] ? "
                        <small class='text-muted ms-3'>
                            <i class='fas fa-external-link-alt me-1'></i>
                            Auto redirect
                        </small>
                    " : '') . "
                </div>
                <div class='d-flex gap-2'>
                    <button type='button' class='btn btn-outline-danger btn-sm' onclick='reportAd({$ad['ad_id']})' title='Report inappropriate content'>
                        <i class='fas fa-flag'></i>
                    </button>
                    <button type='button' class='btn btn-primary btn-sm' onclick='startAdView({$ad['ad_id']})'>
                        <i class='fas fa-play me-1'></i>View Ad
                    </button>
                </div>
            </div>
        </div>
    </div>";
}
?>

<style>
.ad-card {
    transition: transform 0.2s ease;
}

.ad-card:hover {
    transform: translateY(-2px);
}

.ad-preview {
    height: 150px;
    overflow: hidden;
    border-radius: 8px;
    background: #f8f9fc;
}

.ad-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ad-preview-placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.ad-view-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
}

.ad-timer-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    text-align: center;
    min-width: 300px;
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
}

.timer {
    font-family: 'Courier New', monospace;
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
}

.timer.warning {
    color: #ffc107;
}

.timer.danger {
    color: #dc3545;
}

.progress {
    height: 10px;
}

.nav-tabs .nav-link {
    font-weight: 600;
}

.nav-tabs .nav-link.active {
    font-weight: 700;
}
</style>

<script>
let currentAdView = null;
let viewTimer = null;
let viewStartTime = null;
let validationData = {
    pageFocusLost: false,
    multipleTabs: false,
    botDetected: false
};

// Track page visibility
document.addEventListener('visibilitychange', function() {
    if (document.hidden && currentAdView) {
        validationData.pageFocusLost = true;
    }
});

// Track multiple tabs
window.addEventListener('beforeunload', function() {
    if (currentAdView) {
        validationData.multipleTabs = true;
    }
});

// Simple bot detection
let mouseMovements = 0;
document.addEventListener('mousemove', function() {
    mouseMovements++;
});

function reportAd(adId) {
    const reasons = {
        'sexual': 'Sexual/Explicit Content',
        'violence': 'Violence/Gore',
        'spam': 'Spam/Misleading',
        'scam': 'Scam/Fraud',
        'other': 'Other'
    };

    let reasonHtml = '<div class="mb-3"><label class="form-label fw-bold">Select Reason:</label><select id="reportReason" class="form-select">';
    for (const [key, label] of Object.entries(reasons)) {
        reasonHtml += `<option value="${key}">${label}</option>`;
    }
    reasonHtml += '</select></div>';
    reasonHtml += '<div class="mb-3"><label class="form-label fw-bold">Details (optional):</label><textarea id="reportDetails" class="form-control" rows="2" placeholder="Describe the issue..."></textarea></div>';

    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    document.getElementById('reportModalBody').innerHTML = reasonHtml;
    document.getElementById('reportModalAdId').value = adId;
    modal.show();
}

function submitReport() {
    const adId = document.getElementById('reportModalAdId').value;
    const reason = document.getElementById('reportReason').value;
    const details = document.getElementById('reportDetails').value;
    const btn = document.getElementById('submitReportBtn');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';

    fetch((window.VSItoA_BASE_PATH || '') + '/earn/report-ad', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ ad_id: adId, reason: reason, details: details })
    })
    .then(r => r.json())
    .then(res => {
        bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
        showAlert(res.success ? 'Thank You' : 'Error', res.message, res.success ? 'success' : 'danger');
    })
    .catch(() => {
        bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
        showAlert('Error', 'Failed to submit report.', 'danger');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit Report';
    });
}

function startAdView(adId) {
    if (currentAdView) {
        showAlert('Warning', 'Please complete the current ad view first', 'warning');
        return;
    }
    
    // Reset validation data
    validationData = {
        pageFocusLost: false,
        multipleTabs: false,
        botDetected: mouseMovements < 5
    };
    
    fetch(`/api/ads/${adId}/view`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentAdView = data.data;
            showAdViewInterface(data.data);
        } else {
            showAlert('Error', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', 'Failed to start ad view', 'danger');
    });
}

function showAdViewInterface(adData) {
    const ad = adData.ad;
    
    if (ad.ad_type === 'window' || ad.ad_type === 'video') {
        // Use overlay for window/video ads
        showAdOverlay(ad);
    } else {
        // Use modal for surf/article ads
        showAdModal(ad);
    }
}

function showAdOverlay(ad) {
    const overlay = document.getElementById('ad-view-overlay');
    const timer = document.getElementById('overlay-timer');
    const progress = document.getElementById('timer-progress');
    
    overlay.style.display = 'block';
    viewStartTime = Date.now();
    
    // Open ad in new window
    const adWindow = window.open(ad.target_url, '_blank', 'width=800,height=600');
    
    // Start timer
    let timeLeft = ad.view_time;
    timer.textContent = timeLeft;
    
    viewTimer = setInterval(() => {
        timeLeft--;
        timer.textContent = timeLeft;
        
        // Update progress
        const progressPercent = ((ad.view_time - timeLeft) / ad.view_time) * 100;
        progress.style.width = progressPercent + '%';
        
        // Add warning classes
        if (timeLeft <= 5) {
            timer.classList.add('danger');
        } else if (timeLeft <= 10) {
            timer.classList.add('warning');
        }
        
        if (timeLeft <= 0) {
            completeAdView();
        }
    }, 1000);
    
    // Check if window was closed
    const checkWindow = setInterval(() => {
        if (adWindow.closed) {
            clearInterval(checkWindow);
            if (viewTimer) {
                clearInterval(viewTimer);
            }
            validationData.pageFocusLost = true;
            completeAdView();
        }
    }, 1000);
}

function showAdModal(ad) {
    const modal = new bootstrap.Modal(document.getElementById('adViewModal'));
    const content = document.getElementById('ad-view-content');
    const timer = document.getElementById('ad-timer');
    const confirmBtn = document.getElementById('confirm-view-btn');
    
    // Load ad content
    content.innerHTML = `
        <div class='text-center'>
            <h5>${ad.ad_title}</h5>
            <iframe src='${ad.target_url}' width='100%' height='400' frameborder='0'></iframe>
        </div>
    `;
    
    modal.show();
    viewStartTime = Date.now();
    
    // Start timer
    let timeLeft = ad.view_time;
    timer.textContent = timeLeft;
    confirmBtn.disabled = true;
    
    viewTimer = setInterval(() => {
        timeLeft--;
        timer.textContent = timeLeft;
        
        if (timeLeft <= 0) {
            clearInterval(viewTimer);
            confirmBtn.disabled = false;
            confirmBtn.onclick = completeAdView;
        }
    }, 1000);
}

function completeAdView() {
    if (!currentAdView) return;
    
    const actualViewTime = Math.floor((Date.now() - viewStartTime) / 1000);
    
    fetch('/api/ads/complete-view', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        },
        body: JSON.stringify({
            view_id: currentAdView.view_id,
            actual_view_time: actualViewTime,
            validation_data: validationData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', `Earned ${data.data.earnings} USDT!`, 'success');
            updateBalance();
            removeAdCard(currentAdView.ad.ad_id);
        } else {
            showAlert('Error', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', 'Failed to complete ad view', 'danger');
    })
    .finally(() => {
        closeAdView();
    });
}

function closeAdView() {
    // Clear timer
    if (viewTimer) {
        clearInterval(viewTimer);
        viewTimer = null;
    }
    
    // Hide overlay
    document.getElementById('ad-view-overlay').style.display = 'none';
    
    // Hide modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('adViewModal'));
    if (modal) {
        modal.hide();
    }
    
    // Reset
    currentAdView = null;
    viewStartTime = null;
    
    // Reset timer display
    document.getElementById('overlay-timer').textContent = '--';
    document.getElementById('ad-timer').textContent = '--';
    document.getElementById('timer-progress').style.width = '0%';
    document.getElementById('overlay-timer').className = 'timer';
    document.getElementById('ad-timer').className = 'timer';
}

function removeAdCard(adId) {
    const card = document.querySelector(`[data-ad-id="${adId}"]`);
    if (card) {
        card.style.transition = 'opacity 0.3s ease';
        card.style.opacity = '0';
        setTimeout(() => card.remove(), 300);
    }
}

function updateBalance() {
    fetch('/api/wallet/balance', {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const balanceElements = document.querySelectorAll('[data-user="balance"]');
            balanceElements.forEach(element => {
                element.textContent = parseFloat(data.data.balance).toFixed(8);
                element.classList.add('earnings-pulse');
                setTimeout(() => {
                    element.classList.remove('earnings-pulse');
                }, 2000);
            });
        }
    });
}

function refreshAds() {
    location.reload();
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
