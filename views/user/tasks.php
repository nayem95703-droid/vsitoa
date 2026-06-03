<?php
$page_title = 'Tasks & Offers - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

if (!isset($stats) || !is_array($stats)) {
    $stats = [
        'today_completed' => 0,
        'total_completed' => 0,
        'today_earned' => 0,
    ];
}

if (!isset($pendingTasks) || !is_array($pendingTasks)) {
    $pendingTasks = [];
}

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Tasks & Offers</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-outline-secondary" onclick="refreshTasks()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">Today's Completed</h5>
                    <h2 class="text-success"><?= $stats['today_completed'] ?></h2>
                    <small class="text-muted">tasks</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary">Total Completed</h5>
                    <h2 class="text-primary"><?= $stats['total_completed'] ?></h2>
                    <small class="text-muted">tasks</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info">Today's Earnings</h5>
                    <h2 class="text-info"><?= number_format($stats['today_earned'], 8) ?> USDT</h2>
                    <small class="text-muted">from tasks</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning">Pending Tasks</h5>
                    <h2 class="text-warning"><?= count($pendingTasks) ?></h2>
                    <small class="text-muted">awaiting review</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Categories -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Task Categories</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="task-category-card" onclick="filterTasks('survey')">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="fas fa-clipboard-list fa-3x text-primary mb-3"></i>
                                        <h6 class="card-title">Surveys</h6>
                                        <p class="card-text">Share your opinions and get paid</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="task-category-card" onclick="filterTasks('app_install')">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="fas fa-mobile-alt fa-3x text-success mb-3"></i>
                                        <h6 class="card-title">App Install</h6>
                                        <p class="card-text">Install apps and earn rewards</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="task-category-card" onclick="filterTasks('game')">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="fas fa-gamepad fa-3x text-info mb-3"></i>
                                        <h6 class="card-title">Games</h6>
                                        <p class="card-text">Play games and complete levels</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="task-category-card" onclick="filterTasks('video')">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="fas fa-video fa-3x text-warning mb-3"></i>
                                        <h6 class="card-title">Videos</h6>
                                        <p class="card-text">Watch videos and earn</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="task-category-card" onclick="filterTasks('offer')">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="fas fa-gift fa-3x text-danger mb-3"></i>
                                        <h6 class="card-title">Offers</h6>
                                        <p class="card-text">Third-party offers</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="task-category-card" onclick="filterTasks('all')">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="fas fa-tasks fa-3x text-secondary mb-3"></i>
                                        <h6 class="card-title">All Tasks</h6>
                                        <p class="card-text">View all available tasks</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Third-party Offers Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-warning">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-gift me-2"></i>Third-Party Offers
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">Complete offers from our trusted partners to earn extra cryptocurrency:</p>
                    <div class="row" id="offer-providers">
                        <!-- Offers will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No tasks available</h4>
                        <p class="text-muted">Check back later for new earning opportunities</p>
                    </div>
                    <?php else: ?>
                    <div class="row" id="tasks-container">
                        <?php foreach ($tasks as $task): ?>
                            <div class="col-lg-6 mb-4">
                                <?= renderTaskCard($task) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="task-details">
                <!-- Task details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="complete-task-btn">Complete Task</button>
            </div>
        </div>
    </div>
</div>

<!-- Offer Modal -->
<div class="modal fade" id="offerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="offer-details">
                <!-- Offer details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="complete-offer-btn">Complete Offer</button>
            </div>
        </div>
    </div>
</div>

<style>
.task-category-card {
    cursor: pointer;
    transition: transform 0.2s ease;
    border: 2px solid transparent;
}

.task-category-card:hover {
    transform: translateY(-5px);
    border-color: #007bff;
}

.task-card {
    transition: transform 0.2s ease;
    border-left: 4px solid #e3e6f0;
}

.task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}

.task-card.completed {
    border-left-color: #28a745;
}

.task-card.pending {
    border-left-color: #ffc107;
}

.task-card.rejected {
    border-left-color: #dc3545;
}

.task-type-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    margin-bottom: 15px;
}

.task-type-icon.survey {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
}

.task-type-icon.app_install {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
}

.task-type-icon.game {
    background: linear-gradient(45deg, #17a2b8, #138496);
    color: white;
}

.task-type-icon.video {
    background: linear-gradient(45deg, #ffc107, #e0a800);
    color: white;
}

.task-type-icon.offer {
    background: linear-gradient(45deg, #dc3545, #c82333);
    color: white;
}

.task-type-icon.manual {
    background: linear-gradient(45deg, #6c757d, #5a626d);
    color: white;
}

.reward-badge {
    font-size: 0.8rem;
    font-weight: bold;
}

.requirements-list {
    font-size: 0.85rem;
}

.requirements-list li {
    margin-bottom: 0.5rem;
}

.offer-provider {
    border: 1px solid #e3e6f0;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    transition: transform 0.2s ease;
}

.offer-provider:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,.1);
}

.offer-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
    border-radius: 5px;
}

.offer-title {
    font-weight: 600;
    color: #333;
}

.offer-reward {
    color: #28a745;
    font-weight: bold;
}

.offer-description {
    color: #666;
    font-size: 0.9rem;
}

.offer-actions {
    margin-top: 10px;
}
</style>

<?php
function renderTaskCard($task) {
    $typeIcons = [
        'survey' => 'fa-clipboard-list',
        'app_install' => 'fa-mobile-alt',
        'game' => 'fa-gamepad',
        'video' => 'fa-video',
        'article' => 'fa-newspaper',
        'website_signup' => 'fa-globe',
        'manual' => 'fa-tasks',
        'offer' => 'fa-gift'
    ];
    
    $typeColors = [
        'survey' => 'primary',
        'app_install' => 'success',
        'game' => 'info',
        'video' => 'warning',
        'article' => 'secondary',
        'website_signup' => 'dark',
        'manual' => 'secondary',
        'offer' => 'danger'
    ];
    
    $icon = $typeIcons[$task['task_type']] ?? 'fa-tasks';
    $color = $typeColors[$task['task_type']] ?? 'secondary';
    
    $statusClass = '';
    if ($task['user_status'] === 'approved') {
        $statusClass = 'completed';
    } elseif ($task['user_status'] === 'pending') {
        $statusClass = 'pending';
    } elseif ($task['user_status'] === 'rejected') {
        $statusClass = 'rejected';
    }
    
    $statusBadge = '';
    if ($task['user_status']) {
        $statusColors = [
            'approved' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger'
        ];
        $statusColor = $statusColors[$task['user_status']] ?? 'secondary';
        $statusBadge = '<span class="badge bg-' . $statusColor . 'ms-2">' . ucfirst($task['user_status']) . '</span>';
    }
    
    $expiresText = '';
    if ($task['expires_at']) {
        $expiresAt = new DateTime($task['expires_at']);
        $now = new DateTime();
        if ($expiresAt < $now) {
            $expiresText = '<span class="text-danger">Expired</span>';
        } else {
            $daysLeft = $now->diff($expiresAt)->days;
            $expiresText = '<span class="text-info">' . $daysLeft . ' days left</span>';
        }
    }
    
    return "
    <div class='task-card {$statusClass}'>
        <div class='card-body'>
            <div class='d-flex align-items-start mb-3'>
                <div class='task-type-icon {$color} me-3'>
                    <i class='fas {$icon}'></i>
                </div>
                <div class='flex-grow-1'>
                    <h6 class='card-title mb-1'>" . htmlspecialchars($task['task_title']) . "</h6>
                    <div class='d-flex align-items-center mb-2'>
                        <span class='badge bg-{$color} me-2'>" . ucfirst($task['task_type']) . "</span>
                        {$statusBadge}
                        {$expiresText}
                    </div>
                    <p class='text-muted small mb-2'>" . htmlspecialchars(substr($task['description'] ?? '', 0, 100)) . "</p>
                </div>
            </div>
            
            <div class='d-flex justify-content-between align-items-center mb-3'>
                <div>
                    <div class='reward-badge text-success'>
                        <i class='fas fa-bitcoin me-1'></i>
                        " . number_format($task['reward_amount'], 8) . " USDT
                    </div>
                    <small class='text-muted'>Reward</small>
                </div>
                <div>
                    <button type='button' class='btn btn-primary btn-sm' onclick='showTaskDetails(" . $task['task_id'] . ")'>
                        <i class='fas fa-info-circle me-1'></i>Details
                    </button>
                    " . ($task['user_status'] !== 'approved'
                        ? "
                        <button type='button' class='btn btn-success btn-sm' onclick='completeTask(" . $task['task_id'] . ")'>
                            <i class='fas fa-check me-1'></i>Complete
                        </button>
                    "
                        : "
                        <button type='button' class='btn btn-outline-secondary btn-sm' disabled>
                            <i class='fas fa-check me-1'></i>Completed
                        </button>
                    "
                    ) . "
                </div>
            </div>
            
            " . ($task['daily_limit'] > 0 ? "
                <div class='text-muted small'>
                    <i class='fas fa-clock me-1'></i>
                    Limit: " . $task['daily_limit'] . " per day
                </div>
            " : '') . "
            
            " . ($task['total_limit'] > 0 ? "
                <div class='text-muted small'>
                    <i class='fas fa-chart-line me-1'></i>
                    Total: " . $task['total_limit'] . " completions
                </div>
            " : '') . "
            
            " . ($task['last_completed'] ? "
                <div class='text-muted small'>
                    <i class='fas fa-check-circle me-1'></i>
                    Last completed: " . date('M j, Y H:i', strtotime($task['last_completed'])) . "
                </div>
            " : '') . "
        </div>
    </div>
    ";
}
?>

<script>
let currentTask = null;
let currentOffer = null;

document.addEventListener('DOMContentLoaded', function() {
    loadOfferProviders();
});

function filterTasks(type) {
    const url = new URL(window.location);
    url.searchParams.set('type', type);
    window.location.href = url.toString();
}

function loadOfferProviders() {
    fetch('/api/tasks/providers', {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayOfferProviders(data.data);
        }
    })
    .catch(error => {
        console.error('Error loading offer providers:', error);
    });
}

function displayOfferProviders(providers) {
    const container = document.getElementById('offer-providers');
    container.innerHTML = '';
    
    providers.forEach(provider => {
        const providerDiv = document.createElement('div');
        providerDiv.className = 'offer-provider';
        providerDiv.innerHTML = `
            <div class="row align-items-center">
                <div class="col-md-2">
                    <img src="${provider.logo}" alt="${provider.name}" class="offer-logo">
                </div>
                <div class="col-md-6">
                    <div class="offer-title">${provider.name}</div>
                    <div class="offer-description">${provider.description}</div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="offer-actions">
                        <button type="button" class="btn btn-primary btn-sm" onclick="showProviderOffers('${provider.id}')">
                            View Offers (${provider.offers.length})
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(providerDiv);
    });
}

function showProviderOffers(providerId) {
    const providers = {
        'adgem': 'AdGem',
        'offertoro': 'OfferToro',
        'cpalead' => 'CPALead'
    };
    
    // Get offers for this provider (in real implementation, this would call the provider's API)
    fetch(`/api/tasks/providers/${providerId}/offers`, {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayOffersModal(providers[providerId], data.data);
        }
    })
    .catch(error => {
        console.error('Error loading offers:', error);
    });
}

function displayOffersModal(providerName, offers) {
    const modal = new bootstrap.Modal(document.getElementById('offerModal'));
    const details = document.getElementById('offer-details');
    
    let offersHtml = '<h5>' + providerName + ' Offers</h5>';
    offersHtml += '<div class="list-group">';
    
    offers.forEach(offer => {
        offersHtml += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${offer.title}</h6>
                        <p class="text-muted small mb-2">${offer.description}</p>
                        <div class="requirements-list">
                            <strong>Requirements:</strong>
                            <ul>
                                ${offer.requirements.map(req => `<li>${req}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                    <div>
                        <div class="offer-reward">
                            <i class="fas fa-bitcoin"></i>
                            ${number_format(offer.reward, 8)} USDT
                        </div>
                        <button type="button" class="btn btn-success btn-sm" onclick="completeOffer('${offer.id}', '${offer.provider_id}')">
                            Complete
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    offersHtml += '</div>';
    details.innerHTML = offersHtml;
    modal.show();
}

function showTaskDetails(taskId) {
    fetch(`/api/tasks/${taskId}`, {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayTaskDetails(data.data);
        }
    })
    .catch(error => {
        console.error('Error loading task details:', error);
    });
}

function displayTaskDetails(task) {
    const modal = new bootstrap.Modal(document.getElementById('taskModal'));
    const details = document.getElementById('task-details');
    const completeBtn = document.getElementById('complete-task-btn');
    
    currentTask = task.task;
    
    let detailsHtml = `
        <div class="row">
            <div class="col-md-8">
                <h5>${task.task_title}</h5>
                <p><strong>Type:</strong> ${task.task_type}</p>
                <p><strong>Reward:</strong> <span class="text-success">${number_format(task.reward_amount, 8)} USDT</span></p>
                <p><strong>Description:</strong> ${task.description}</p>
                ${task.instructions ? `
                    <p><strong>Instructions:</strong></p>
                    <div class="requirements-list">
                        ${JSON.parse(task.instructions).map(inst => `<li>${inst}</li>`).join('')}
                    </div>
                ` : ''}
            </div>
            <div class="col-md-4">
                <h6>Task Information</h6>
                <ul class="list-unstyled">
                    <li><strong>Daily Limit:</strong> ${task.daily_limit || 'Unlimited'}</li>
                    <li><strong>Total Limit:</strong> ${task.total_limit || 'Unlimited'}</li>
                    <li><strong>Expires:</strong> ${task.expires_at ? date('M j, Y H:i', strtotime($task.expires_at)) : 'Never'}</li>
                </ul>
            </div>
        </div>
        ${task.completion_history && task.completion_history.length > 0 ? `
            <div class="mt-4">
                <h6>Completion History</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Admin Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${task.completion_history.map(history => `
                                <tr>
                                    <td>
                                        <span class="badge bg-${history.status === 'approved' ? 'success' : (history.status === 'rejected' ? 'danger' : 'warning')}">
                                            ${history.status}
                                        </span>
                                    </td>
                                    <td>${date('M j, Y H:i', strtotime($history.created_at))}</td>
                                    <td>${history.admin_notes || 'None'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        ` : ''}
    `;
    
    details.innerHTML = detailsHtml;
    
    if (task.user_status === 'approved') {
        completeBtn.style.display = 'none';
    } else {
        completeBtn.style.display = 'block';
        completeBtn.onclick = () => completeTask(task.task_id);
    }
    
    modal.show();
}

function completeTask(taskId) {
    if (!confirm('Are you sure you want to complete this task?')) {
        return;
    }
    
    const btn = document.getElementById('complete-task-btn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
    
    fetch(`/api/tasks/${taskId}/complete`, {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            if (data.errors) {
                showValidationErrors(data.errors);
            } else {
                showAlert('Error', data.message, 'danger');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', 'Failed to complete task', 'danger');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function completeOffer(offerId, providerId) {
    if (!confirm('Are you sure you want to complete this offer?')) {
        return;
    }
    
    const btn = document.getElementById('complete-offer-btn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
    
    fetch('/api/tasks/complete-offer', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            provider_id: providerId,
            offer_id: offerId,
            proof_data: {
                timestamp: Date.now(),
                user_agent: navigator.userAgent,
                screen_resolution: `${screen.width}x${screen.height}`
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('offerModal'));
            modal.hide();
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            if (data.errors) {
                showValidationErrors(data.errors);
            } else {
                showAlert('Error', data.message, 'danger');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', 'Failed to complete offer', 'danger');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function refreshTasks() {
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

function showValidationErrors(errors) {
    let errorMessage = '';
    for (const [field, messages] of Object.entries(errors)) {
        errorMessage += `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}\n`;
    }
    showAlert('Validation Error', errorMessage, 'danger');
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
