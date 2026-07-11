<?php
$page_title = 'Admin Reports - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$basePath = (string) Config::get('app.base_path', '');

$stats = $stats ?? [];
$recentDeposits = $recentDeposits ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Reports</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-dollar-sign me-1"></i>Total Deposited</div>
                <h4 class="text-success mb-0"><?= number_format((float) ($stats['total_deposited'] ?? 0), 2) ?> USDT</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-clock me-1"></i>Pending Deposits</div>
                <h4 class="text-warning mb-0"><?= number_format((float) ($stats['pending_amount'] ?? 0), 2) ?> USDT</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-arrow-up me-1"></i>Today's Deposits</div>
                <h4 class="text-info mb-0"><?= number_format((float) ($stats['today_deposited'] ?? 0), 2) ?> USDT</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-users me-1"></i>Unique Depositors</div>
                <h4 class="text-primary mb-0"><?= (int) ($stats['unique_depositors'] ?? 0) ?></h4>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card admin-card p-3">
                <div class="text-muted mb-2"><i class="fas fa-calendar-day me-1"></i>Last 7 Days</div>
                <h5 class="text-white mb-0"><?= number_format((float) ($stats['week_deposited'] ?? 0), 2) ?> USDT</h5>
                <small class="text-muted"><?= (int) ($stats['week_count'] ?? 0) ?> deposits</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card p-3">
                <div class="text-muted mb-2"><i class="fas fa-calendar me-1"></i>Last 30 Days</div>
                <h5 class="text-white mb-0"><?= number_format((float) ($stats['month_deposited'] ?? 0), 2) ?> USDT</h5>
                <small class="text-muted"><?= (int) ($stats['month_count'] ?? 0) ?> deposits</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card p-3">
                <div class="text-muted mb-2"><i class="fas fa-calendar-check me-1"></i>This Month (Approved)</div>
                <h5 class="text-white mb-0"><?= number_format((float) ($stats['month_approved'] ?? 0), 2) ?> USDT</h5>
                <small class="text-muted"><?= (int) ($stats['month_approved_count'] ?? 0) ?> deposits</small>
            </div>
        </div>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">Recent Deposits</h6>
        </div>
        <div class="card-body">
            <?php if (empty($recentDeposits)): ?>
                <div class="text-center text-muted py-4">No deposits yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Currency</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentDeposits as $d): ?>
                                <tr>
                                    <td><?= !empty($d['created_at']) ? date('M j, Y H:i', strtotime((string) $d['created_at'])) : '' ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($d['username'] ?? '')) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($d['email'] ?? '')) ?></small>
                                    </td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars((string) ($d['currency'] ?? '')) ?></span></td>
                                    <td class="text-end"><?= number_format((float) ($d['amount'] ?? 0), 8) ?></td>
                                    <td>
                                        <?php
                                        $badge = 'secondary';
                                        if (($d['status'] ?? '') === 'pending') $badge = 'warning';
                                        if (($d['status'] ?? '') === 'approved') $badge = 'success';
                                        if (($d['status'] ?? '') === 'rejected') $badge = 'danger';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst(htmlspecialchars((string) ($d['status'] ?? ''))) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
