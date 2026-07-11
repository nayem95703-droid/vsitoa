<?php
$page_title = 'Manage Advertisements - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;
$stats = $stats ?? [];
$total = $total ?? 0;
$limit = $limit ?? 10;
$page = $page ?? 1;
$status = $status ?? 'all';
$ads = $ads ?? [];

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Advertisements</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="/advisor/create-ad" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create New Ad
                </a>
                <button type="button" class="btn btn-outline-secondary" onclick="refreshAds()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Ads
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['total_ads'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ad fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['active_ads'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-play-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['pending_ads'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Paused
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['paused_ads'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-pause-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Completed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['completed_ads'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Spent
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_spent'] ?? 0, 8) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bitcoin fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="adTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $status === 'all' ? 'active' : '' ?>" onclick="filterAds('all')">
                        All (<?= $stats['total_ads'] ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $status === 'pending' ? 'active' : '' ?>" onclick="filterAds('pending')">
                        Pending (<?= $stats['pending_ads'] ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $status === 'active' ? 'active' : '' ?>" onclick="filterAds('active')">
                        Active (<?= $stats['active_ads'] ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $status === 'paused' ? 'active' : '' ?>" onclick="filterAds('paused')">
                        Paused (<?= $stats['paused_ads'] ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $status === 'completed' ? 'active' : '' ?>" onclick="filterAds('completed')">
                        Completed (<?= $stats['completed_ads'] ?>)
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Ads List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <?php if (empty($ads)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ad fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No advertisements found</h4>
                        <p class="text-muted">Create your first advertisement to start promoting your business!</p>
                        <a href="/advisor/create-ad" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Advertisement
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ad Details</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Views</th>
                                    <th>Budget</th>
                                    <th>Spent</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ads as $ad): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-start">
                                            <?php if (!empty($ad['preview_image'])): ?>
                                                <img src="<?= htmlspecialchars($ad['preview_image']) ?>" alt="Preview" class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                <div class="ad-placeholder me-3" style="width: 50px; height: 50px; background: #f8f9fc; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                                    <i class="fas fa-ad text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($ad['ad_title'] ?? '') ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars(substr($ad['target_url'] ?? '', 0, 50)) ?>...</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= ucfirst($ad['ad_type'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'active' => 'success',
                                            'paused' => 'info',
                                            'completed' => 'secondary'
                                        ];
                                        $color = $statusColors[$ad['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= ucfirst($ad['status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <strong><?= number_format(($ad['total_views'] ?? 0) - ($ad['remaining_views'] ?? 0)) ?></strong> / <?= number_format($ad['total_views'] ?? 0) ?>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <?php
                                            $tv = $ad['total_views'] ?? 0;
                                            $rv = $ad['remaining_views'] ?? 0;
                                            $progress = ($tv > 0) ? (($tv - $rv) / $tv) * 100 : 0;
                                            ?>
                                            <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?= number_format($ad['total_budget'] ?? 0, 8) ?> USDT</small>
                                    </td>
                                    <td>
                                        <small class="<?= ($ad['spent_amount'] ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
                                            <?= number_format($ad['spent_amount'] ?? 0, 8) ?> USDT
                                        </small>
                                    </td>
                                    <td>
                                        <small><?= date('M j, Y', strtotime($ad['created_at'] ?? 'now')) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info" onclick="viewAdStats(<?= $ad['ad_id'] ?>)" title="View Statistics">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            
                                            <?php if ($ad['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-outline-warning" onclick="editAd(<?= $ad['ad_id'] ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteAd(<?= $ad['ad_id'] ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php elseif ($ad['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-outline-warning" onclick="pauseAd(<?= $ad['ad_id'] ?>)" title="Pause">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info" onclick="editAd(<?= $ad['ad_id'] ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php elseif ($ad['status'] === 'paused'): ?>
                                                <button type="button" class="btn btn-outline-success" onclick="resumeAd(<?= $ad['ad_id'] ?>)" title="Resume">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteAd(<?= $ad['ad_id'] ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php elseif ($ad['status'] === 'completed'): ?>
                                                <button type="button" class="btn btn-outline-info" onclick="viewAdStats(<?= $ad['ad_id'] ?>)" title="View Statistics">
                                                    <i class="fas fa-chart-bar"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php
                    $totalPages = ceil($total / $limit);
                    if ($totalPages > 1): ?>
                    <nav aria-label="Ads pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ad Statistics Modal -->
<div class="modal fade" id="adStatsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Advertisement Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ad-stats-content">
                <!-- Stats will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.ad-placeholder {
    background: linear-gradient(45deg, #f8f9fc 25%, transparent 25%, transparent 75%, #f8f9fc 75%, #f8f9fc);
    background-size: 10px 10px;
    background-position: 0 0, 5px 5px;
}

.progress {
    background-color: #e9ecef;
}

.progress-bar {
    background-color: #007bff;
}
</style>

<script>
function filterAds(status) {
    window.location.href = '?status=' + status;
}

function viewAdStats(adId) {
    const modal = new bootstrap.Modal(document.getElementById('adStatsModal'));
    const content = document.getElementById('ad-stats-content');
    
    content.innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <p>Loading statistics...</p>
        </div>
    `;
    
    modal.show();
    
    fetch(`/api/advisor/ads/${adId}/stats`, {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAdStats(data.data);
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load statistics: ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="alert alert-danger">
                Failed to load statistics. Please try again.
            </div>
        `;
    });
}

function displayAdStats(data) {
    const content = document.getElementById('ad-stats-content');
    const stats = data.stats;
    
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Performance Overview</h6>
                <div class="mb-3">
                    <strong>Total Views:</strong> ${stats.total_views || 0}
                </div>
                <div class="mb-3">
                    <strong>Valid Views:</strong> <span class="text-success">${stats.valid_views || 0}</span>
                </div>
                <div class="mb-3">
                    <strong>Invalid Views:</strong> <span class="text-danger">${stats.invalid_views || 0}</span>
                </div>
                <div class="mb-3">
                    <strong>Unique Viewers:</strong> ${stats.unique_viewers || 0}
                </div>
                <div class="mb-3">
                    <strong>Avg View Time:</strong> ${stats.avg_view_time ? stats.avg_view_time.toFixed(1) + 's' : 'N/A'}
                </div>
            </div>
            <div class="col-md-6">
                <h6>Financial Summary</h6>
                <div class="mb-3">
                    <strong>Total Spent:</strong> <span class="text-danger">${stats.total_spent ? parseFloat(stats.total_spent).toFixed(8) + ' USDT' : '0.00000000 USDT'}</span>
                </div>
                <div class="mb-3">
                    <strong>Cost Per View:</strong> ${stats.cost_per_view ? parseFloat(stats.cost_per_view).toFixed(8) + ' USDT' : 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Remaining Views:</strong> ${stats.remaining_views || 0}
                </div>
                <div class="mb-3">
                    <strong>Status:</strong> <span class="badge bg-primary">${stats.status}</span>
                </div>
            </div>
        </div>
        
        ${data.daily_stats && data.daily_stats.length > 0 ? `
            <h6 class="mt-4">Daily Performance (Last 30 Days)</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Views</th>
                            <th>Valid Views</th>
                            <th>Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.daily_stats.map(day => `
                            <tr>
                                <td>${new Date(day.date).toLocaleDateString()}</td>
                                <td>${day.views}</td>
                                <td>${day.valid_views}</td>
                                <td>${day.spent ? parseFloat(day.spent).toFixed(8) + ' USDT' : '0.00000000 USDT'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        ` : ''}
    `;
}

function pauseAd(adId) {
    if (!confirm('Are you sure you want to pause this advertisement?')) {
        return;
    }
    
    fetch(`/api/advisor/ads/${adId}/pause`, {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', data.message, 'success');
            location.reload();
        } else {
            showAlert('Error', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', 'Failed to pause advertisement', 'danger');
    });
}

function resumeAd(adId) {
    if (!confirm('Are you sure you want to resume this advertisement?')) {
        return;
    }
    
    fetch(`/api/advisor/ads/${adId}/resume`, {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', data.message, 'success');
            location.reload();
        } else {
            showAlert('Error', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', 'Failed to resume advertisement', 'danger');
    });
}

function editAd(adId) {
    window.location.href = `/advisor/edit-ad/${adId}`;
}

function deleteAd(adId) {
    if (!confirm('Are you sure you want to delete this advertisement? You may receive a partial refund of your remaining budget.')) {
        return;
    }
    
    fetch(`/api/advisor/ads/${adId}`, {
        method: 'DELETE',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', data.message, 'success');
            location.reload();
        } else {
            showAlert('Error', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', 'Failed to delete advertisement', 'danger');
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
