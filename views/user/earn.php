<?php
$page_title = 'Earn - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$todayViews = $todayViews ?? 0;
$dailyLimit = $dailyLimit ?? Config::get('max_daily_ads', 100);
$ads = $ads ?? [];

$typeIcons = [
    'surf' => 'fa-eye',
    'window' => 'fa-window-maximize',
    'video' => 'fa-video',
    'article' => 'fa-newspaper'
];
$typeColors = [
    'surf' => '#2563eb',
    'window' => '#0891b2',
    'video' => '#16a34a',
    'article' => '#d97706'
];
$typeLabels = [
    'surf' => 'Surf',
    'window' => 'Window',
    'video' => 'Video',
    'article' => 'Article'
];

$avgReward = count($ads) > 0 ? array_sum(array_column($ads, 'cost_per_view')) / count($ads) : 0;
$potential = array_sum(array_column($ads, 'cost_per_view')) * min($dailyLimit - $todayViews, count($ads));

ob_start();
?>

<style>
.earn-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}
.earn-topbar h1 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}
.stat-row {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    overflow-x: auto;
    padding-bottom: 4px;
}
.stat-box {
    flex: 1;
    min-width: 140px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 14px 16px;
    text-align: center;
}
.stat-box .stat-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94a3b8;
    margin-bottom: 4px;
}
.stat-box .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    font-family: 'Courier New', monospace;
}
.stat-box .stat-sub {
    font-size: 0.7rem;
    color: #64748b;
    margin-top: 2px;
}
.filter-pills {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
    overflow-x: auto;
    padding-bottom: 4px;
    -webkit-overflow-scrolling: touch;
}
.filter-pills::-webkit-scrollbar { height: 3px; }
.filter-pills::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
.filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.04);
    color: #94a3b8;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    user-select: none;
}
.filter-pill:hover {
    background: rgba(255,255,255,0.08);
    color: #e2e8f0;
    border-color: rgba(255,255,255,0.2);
}
.filter-pill.active {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}
.filter-pill .pill-count {
    background: rgba(0,0,0,0.2);
    padding: 1px 7px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}
.filter-pill.active .pill-count {
    background: rgba(255,255,255,0.25);
}
.task-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.task-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    transition: background 0.15s;
    gap: 12px;
}
.task-card:first-child {
    border-radius: 10px 10px 0 0;
}
.task-card:last-child {
    border-radius: 0 0 10px 10px;
    border-bottom: none;
}
.task-card:only-child {
    border-radius: 10px;
}
.task-card:hover {
    background: rgba(255,255,255,0.06);
}
.task-left {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}
.task-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #fff;
    flex-shrink: 0;
}
.task-info {
    min-width: 0;
    flex: 1;
}
.task-title {
    font-size: 0.92rem;
    font-weight: 600;
    color: #e2e8f0;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.task-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.task-meta span {
    font-size: 0.73rem;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.task-type-badge {
    display: inline-block;
    padding: 1px 7px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #fff;
}
.task-right {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
}
.task-price {
    text-align: right;
}
.task-price .amount {
    font-weight: 700;
    font-size: 1.05rem;
    color: #f59e0b;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
}
.task-price .label {
    font-size: 0.65rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.task-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}
.btn-watch {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 16px;
    border-radius: 8px;
    border: none;
    background: #2563eb;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.btn-watch:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}
.btn-hide {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
    background: transparent;
    color: #64748b;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-hide:hover {
    background: rgba(255,255,255,0.06);
    color: #94a3b8;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: 0.3;
}
.empty-state h4 {
    font-weight: 600;
    margin-bottom: 6px;
    color: #94a3b8;
}

@media (max-width: 640px) {
    .task-card {
        flex-wrap: wrap;
        padding: 12px 14px;
    }
    .task-right {
        width: 100%;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px solid rgba(255,255,255,0.06);
        margin-top: 4px;
    }
    .task-icon {
        width: 36px;
        height: 36px;
        font-size: 0.85rem;
    }
    .task-title {
        font-size: 0.85rem;
    }
    .stat-row {
        gap: 8px;
    }
    .stat-box {
        min-width: 120px;
        padding: 10px 12px;
    }
    .stat-box .stat-value {
        font-size: 1.05rem;
    }
    .filter-pills {
        gap: 6px;
    }
    .filter-pill {
        padding: 6px 12px;
        font-size: 0.75rem;
    }
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
    color: #2563eb;
}
.timer.warning { color: #f59e0b; }
.timer.danger { color: #ef4444; }
.progress { height: 10px; }
</style>

<div class="container-fluid py-4">
    <!-- Top Bar -->
    <div class="earn-topbar">
        <h1><i class="fas fa-coins me-2" style="color:#f59e0b"></i>Earn Crypto</h1>
        <button type="button" class="btn btn-sm btn-outline-light" onclick="refreshAds()" style="border-radius:8px;">
            <i class="fas fa-sync-alt me-1"></i>Refresh
        </button>
    </div>

    <!-- Stats -->
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Today's Views</div>
            <div class="stat-value" style="color:#60a5fa"><?= $todayViews ?></div>
            <div class="stat-sub">of <?= $dailyLimit ?> limit</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Available Ads</div>
            <div class="stat-value" style="color:#34d399"><?= count($ads) ?></div>
            <div class="stat-sub">ready to view</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Avg. Reward</div>
            <div class="stat-value" style="color:#fbbf24"><?= number_format($avgReward, 8) ?></div>
            <div class="stat-sub">USDT / view</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Potential</div>
            <div class="stat-value" style="color:#a78bfa"><?= number_format($potential, 8) ?></div>
            <div class="stat-sub">USDT available</div>
        </div>
    </div>

    <!-- Category Filter Pills -->
    <div class="filter-pills" id="filterPills">
        <div class="filter-pill active" data-filter="all" onclick="filterAds('all', this)">
            <i class="fas fa-th-large"></i> All <span class="pill-count"><?= count($ads) ?></span>
        </div>
        <?php
        $typeCounts = [];
        foreach ($ads as $ad) {
            $t = $ad['ad_type'];
            $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
        }
        foreach ($typeCounts as $type => $cnt): ?>
            <div class="filter-pill" data-filter="<?= $type ?>" onclick="filterAds('<?= $type ?>', this)">
                <i class="fas <?= $typeIcons[$type] ?? 'fa-ad' ?>"></i>
                <?= $typeLabels[$type] ?? ucfirst($type) ?>
                <span class="pill-count"><?= $cnt ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Task List -->
    <div class="task-list" id="taskList">
        <?php if (empty($ads)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox d-block"></i>
                <h4>No ads available</h4>
                <p>Check back later for new earning opportunities</p>
            </div>
        <?php else: ?>
            <?php foreach ($ads as $ad): ?>
                <?php
                $type = $ad['ad_type'];
                $color = $typeColors[$type] ?? '#64748b';
                $icon = $typeIcons[$type] ?? 'fa-ad';
                $label = $typeLabels[$type] ?? ucfirst($type);
                ?>
                <div class="task-card" data-ad-id="<?= (int) $ad['ad_id'] ?>" data-type="<?= $type ?>">
                    <div class="task-left">
                        <div class="task-icon" style="background:<?= $color ?>20; color:<?= $color ?>">
                            <i class="fas <?= $icon ?>"></i>
                        </div>
                        <div class="task-info">
                            <div class="task-title"><?= htmlspecialchars($ad['ad_title']) ?></div>
                            <div class="task-meta">
                                <span class="task-type-badge" style="background:<?= $color ?>"><?= $label ?></span>
                                <span><i class="fas fa-clock"></i> <?= (int) $ad['view_time'] ?>s</span>
                                <span><i class="fas fa-link"></i> <?= htmlspecialchars(mb_substr($ad['target_url'] ?? '', 0, 30)) ?></span>
                                <?php if (!empty($ad['description'])): ?>
                                    <span title="<?= htmlspecialchars($ad['description']) ?>"><i class="fas fa-info-circle"></i> <?= htmlspecialchars(mb_substr($ad['description'], 0, 25)) ?>...</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="task-right">
                        <div class="task-price">
                            <div class="amount"><?= number_format($ad['cost_per_view'], 8) ?></div>
                            <div class="label">USDT / view</div>
                        </div>
                        <div class="task-actions">
                            <button type="button" class="btn-hide" onclick="hideTask(<?= (int) $ad['ad_id'] ?>)" title="Hide this ad">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                            <button type="button" class="btn-hide" onclick="reportAd(<?= (int) $ad['ad_id'] ?>)" title="Report" style="color:#ef4444;">
                                <i class="fas fa-flag"></i>
                            </button>
                            <button type="button" class="btn-watch" onclick="startAdView(<?= (int) $ad['ad_id'] ?>)">
                                <i class="fas fa-play"></i> Watch
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
            <div class="modal-body" id="reportModalBody"></div>
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
                <div id="ad-view-content"></div>
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

<script>
let currentAdView = null;
let viewTimer = null;
let viewStartTime = null;
let validationData = {
    pageFocusLost: false,
    multipleTabs: false,
    botDetected: false
};

document.addEventListener('visibilitychange', function() {
    if (document.hidden && currentAdView) {
        validationData.pageFocusLost = true;
    }
});

window.addEventListener('beforeunload', function() {
    if (currentAdView) {
        validationData.multipleTabs = true;
    }
});

let mouseMovements = 0;
document.addEventListener('mousemove', function() {
    mouseMovements++;
});

function filterAds(type, el) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');

    document.querySelectorAll('.task-card').forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function hideTask(adId) {
    const card = document.querySelector(`.task-card[data-ad-id="${adId}"]`);
    if (card) {
        card.style.transition = 'opacity 0.2s, max-height 0.3s';
        card.style.opacity = '0';
        card.style.maxHeight = '0';
        card.style.overflow = 'hidden';
        card.style.padding = '0';
        card.style.borderBottom = 'none';
        setTimeout(() => card.remove(), 300);
    }
}

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

let adViewProcessing = false;

function startAdView(adId) {
    if (currentAdView) {
        showAlert('Warning', 'Please complete the current ad view first', 'warning');
        return;
    }
    if (adViewProcessing) return;
    adViewProcessing = true;

    const btn = document.querySelector(`.task-card[data-ad-id="${adId}"] .btn-watch`);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
    }

    validationData = {
        pageFocusLost: false,
        multipleTabs: false,
        botDetected: mouseMovements < 5
    };

    fetch((window.VSItoA_BASE_PATH || '') + `/api/ads/${adId}/view`, {
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
    })
    .finally(() => {
        adViewProcessing = false;
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-play me-1"></i>Watch';
        }
    });
}

function showAdViewInterface(adData) {
    const ad = adData.ad;
    if (ad.ad_type === 'window' || ad.ad_type === 'video') {
        showAdOverlay(ad);
    } else {
        showAdModal(ad);
    }
}

function showAdOverlay(ad) {
    const overlay = document.getElementById('ad-view-overlay');
    const timer = document.getElementById('overlay-timer');
    const progress = document.getElementById('timer-progress');

    overlay.style.display = 'block';
    viewStartTime = Date.now();

    const adWindow = window.open(ad.target_url, '_blank', 'width=800,height=600');

    let timeLeft = ad.view_time;
    timer.textContent = timeLeft;

    viewTimer = setInterval(() => {
        timeLeft--;
        timer.textContent = timeLeft;

        const progressPercent = ((ad.view_time - timeLeft) / ad.view_time) * 100;
        progress.style.width = progressPercent + '%';

        if (timeLeft <= 5) {
            timer.classList.add('danger');
        } else if (timeLeft <= 10) {
            timer.classList.add('warning');
        }

        if (timeLeft <= 0) {
            completeAdView();
        }
    }, 1000);

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

    content.innerHTML = `
        <div class='text-center'>
            <h5>${ad.ad_title}</h5>
            <iframe src='${ad.target_url}' width='100%' height='400' frameborder='0'></iframe>
        </div>
    `;

    modal.show();
    viewStartTime = Date.now();

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

    fetch((window.VSItoA_BASE_PATH || '') + '/api/ads/complete-view', {
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
    if (viewTimer) {
        clearInterval(viewTimer);
        viewTimer = null;
    }

    document.getElementById('ad-view-overlay').style.display = 'none';

    const modal = bootstrap.Modal.getInstance(document.getElementById('adViewModal'));
    if (modal) {
        modal.hide();
    }

    currentAdView = null;
    viewStartTime = null;

    document.getElementById('overlay-timer').textContent = '--';
    document.getElementById('ad-timer').textContent = '--';
    document.getElementById('timer-progress').style.width = '0%';
    document.getElementById('overlay-timer').className = 'timer';
    document.getElementById('ad-timer').className = 'timer';
}

function removeAdCard(adId) {
    const card = document.querySelector(`.task-card[data-ad-id="${adId}"]`);
    if (card) {
        card.style.transition = 'opacity 0.3s ease';
        card.style.opacity = '0';
        setTimeout(() => card.remove(), 300);
    }
}

function updateBalance() {
    fetch((window.VSItoA_BASE_PATH || '') + '/api/wallet/balance', {
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
