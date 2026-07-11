<?php
$page_title = 'Admin Deposits - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Deposits</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php
    $basePath = (string) Config::get('app.base_path', '');
    $status = (string) ($status ?? 'pending');
    $counts = $counts ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $deposits = $deposits ?? [];
    $search = $search ?? '';
    $currency = $currency ?? '';
    $summary = $summary ?? [];
    $withdrawals = $withdrawals ?? [];
    $withdrawalCounts = $withdrawalCounts ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-arrow-down me-1"></i>Total Deposited</div>
                <h4 class="text-success mb-0"><?= number_format((float) ($summary['total_deposited'] ?? 0), 2) ?></h4>
                <small class="text-muted"><?= (int) ($summary['total_approved'] ?? 0) ?> approved</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-clock me-1"></i>Total Pending</div>
                <h4 class="text-warning mb-0"><?= number_format((float) ($summary['total_pending'] ?? 0), 2) ?></h4>
                <small class="text-muted"><?= (int) ($summary['pending_count'] ?? 0) ?> requests</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-arrow-up me-1"></i>Total Withdrawn</div>
                <h4 class="text-danger mb-0"><?= number_format((float) ($summary['total_withdrawn'] ?? 0), 2) ?></h4>
                <small class="text-muted"><?= (int) ($summary['total_withdraw_count'] ?? 0) ?> requests</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-balance-scale me-1"></i>Net Balance</div>
                <h4 class="text-info mb-0"><?= number_format((float) ($summary['net_balance'] ?? 0), 2) ?></h4>
                <small class="text-muted">deposited - withdrawn</small>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab ?? 'deposits') === 'deposits' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/deposits?tab=deposits&status=<?= htmlspecialchars($status) ?>">
                <i class="fas fa-arrow-down me-1"></i>Deposits
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab ?? '') === 'withdrawals' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/deposits?tab=withdrawals&wstatus=pending">
                <i class="fas fa-arrow-up me-1"></i>Withdrawals
                <?php if (($withdrawalCounts['pending'] ?? 0) > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $withdrawalCounts['pending'] ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <?php if (($activeTab ?? 'deposits') === 'deposits'): ?>
    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-white">Deposit Requests</h6>
            <div class="btn-group btn-group-sm">
                <a class="btn btn-outline-light <?= $status === 'pending' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/deposits?status=pending">
                    Pending (<?= (int) ($counts['pending'] ?? 0) ?>)
                </a>
                <a class="btn btn-outline-light <?= $status === 'approved' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/deposits?status=approved">
                    Approved (<?= (int) ($counts['approved'] ?? 0) ?>)
                </a>
                <a class="btn btn-outline-light <?= $status === 'rejected' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/deposits?status=rejected">
                    Rejected (<?= (int) ($counts['rejected'] ?? 0) ?>)
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/admin/deposits" class="row g-2 mb-3">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Search by username, email, TXID, or ID..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="currency" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="">All Currencies</option>
                        <?php foreach (['BTC', 'TRX', 'ETH', 'USDT'] as $cur): ?>
                            <option value="<?= $cur ?>" <?= $currency === $cur ? 'selected' : '' ?>><?= $cur ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-light w-100"><i class="fas fa-search me-1"></i>Search</button>
                </div>
                <div class="col-md-2">
                    <a href="<?= $basePath ?>/admin/deposits?status=<?= htmlspecialchars($status) ?>" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Clear</a>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-dark table-hover admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Currency</th>
                            <th class="text-end">Amount</th>
                            <th>TXID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deposits)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No deposits found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deposits as $d): ?>
                                <tr>
                                    <td>#<?= (int) ($d['deposit_id'] ?? 0) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($d['username'] ?? '')) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($d['email'] ?? '')) ?></small>
                                    </td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars((string) ($d['currency'] ?? '')) ?></span></td>
                                    <td class="text-end"><?= number_format((float) ($d['amount'] ?? 0), 8) ?></td>
                                    <td>
                                        <?php if (!empty($d['txid'])): ?>
                                            <code class="small"><?= htmlspecialchars(substr((string) $d['txid'], 0, 14)) ?>...</code>
                                        <?php else: ?>
                                            <span class="text-muted">Not provided</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($d['created_at']) ? date('M j, Y H:i', strtotime((string) $d['created_at'])) : '' ?></td>
                                    <td>
                                        <?php
                                        $badge = 'secondary';
                                        if (($d['status'] ?? '') === 'pending') $badge = 'warning';
                                        if (($d['status'] ?? '') === 'approved') $badge = 'success';
                                        if (($d['status'] ?? '') === 'rejected') $badge = 'danger';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars((string) ($d['status'] ?? '')) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if (($d['status'] ?? '') === 'pending'): ?>
                                            <div class="d-flex justify-content-end gap-2">
                                                <form method="POST" action="<?= $basePath ?>/admin/deposits/approve" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="deposit_id" value="<?= (int) ($d['deposit_id'] ?? 0) ?>">
                                                    <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Admin note (optional)" style="max-width: 220px;">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= $basePath ?>/admin/deposits/reject" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="deposit_id" value="<?= (int) ($d['deposit_id'] ?? 0) ?>">
                                                    <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Reject reason (optional)" style="max-width: 220px;">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times me-1"></i>Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (($activeTab ?? '') === 'withdrawals'): ?>
    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-white">Withdrawal Requests</h6>
            <div class="btn-group btn-group-sm">
                <a class="btn btn-outline-light <?= ($wstatus ?? 'pending') === 'pending' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/deposits?tab=withdrawals&wstatus=pending">
                    Pending (<?= (int) ($withdrawalCounts['pending'] ?? 0) ?>)
                </a>
                <a class="btn btn-outline-light <?= ($wstatus ?? '') === 'paid' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/deposits?tab=withdrawals&wstatus=paid">
                    Paid (<?= (int) ($withdrawalCounts['approved'] ?? 0) ?>)
                </a>
                <a class="btn btn-outline-light <?= ($wstatus ?? '') === 'rejected' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/deposits?tab=withdrawals&wstatus=rejected">
                    Rejected (<?= (int) ($withdrawalCounts['rejected'] ?? 0) ?>)
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Currency</th>
                            <th class="text-end">Amount</th>
                            <th>Wallet Address</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($withdrawals)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No withdrawals found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($withdrawals as $w): ?>
                                <tr>
                                    <td>#<?= (int) ($w['withdrawal_id'] ?? 0) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($w['username'] ?? '')) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($w['email'] ?? '')) ?></small>
                                    </td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars((string) ($w['currency'] ?? '')) ?></span></td>
                                    <td class="text-end"><?= number_format((float) ($w['amount'] ?? 0), 2) ?></td>
                                    <td><code class="small"><?= htmlspecialchars(substr((string) ($w['wallet_address'] ?? ''), 0, 16)) ?>...</code></td>
                                    <td><?= !empty($w['created_at']) ? date('M j, Y H:i', strtotime((string) $w['created_at'])) : '' ?></td>
                                    <td>
                                        <?php
                                        $wBadge = 'secondary';
                                        if (($w['status'] ?? '') === 'pending') $wBadge = 'warning';
                                        if (($w['status'] ?? '') === 'paid') $wBadge = 'success';
                                        if (($w['status'] ?? '') === 'rejected') $wBadge = 'danger';
                                        ?>
                                        <span class="badge bg-<?= $wBadge ?>"><?= ucfirst(htmlspecialchars((string) ($w['status'] ?? ''))) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if (($w['status'] ?? '') === 'pending'): ?>
                                            <div class="d-flex justify-content-end gap-2">
                                                <form method="POST" action="<?= $basePath ?>/admin/withdrawals/approve" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="withdrawal_id" value="<?= (int) ($w['withdrawal_id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= $basePath ?>/admin/withdrawals/reject" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="withdrawal_id" value="<?= (int) ($w['withdrawal_id'] ?? 0) ?>">
                                                    <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Reason (optional)" style="max-width: 180px;">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times me-1"></i>Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
