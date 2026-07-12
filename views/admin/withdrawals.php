<?php
$page_title = 'Admin Withdrawals - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$basePath = (string) Config::get('app.base_path', '');
$status = $status ?? 'pending';
$counts = $counts ?? ['pending' => 0, 'paid' => 0, 'rejected' => 0];
$withdrawals = $withdrawals ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Withdrawals</h1>
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

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-clock me-1"></i>Pending</div>
                <h4 class="text-warning mb-0"><?= number_format((float) ($counts['pending'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-check me-1"></i>Paid</div>
                <h4 class="text-success mb-0"><?= number_format((float) ($counts['paid'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-times me-1"></i>Rejected</div>
                <h4 class="text-danger mb-0"><?= number_format((float) ($counts['rejected'] ?? 0)) ?></h4>
            </div>
        </div>
    </div>

    <div class="btn-group mb-3">
        <a class="btn btn-outline-light <?= $status === 'pending' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/withdrawals?status=pending">
            Pending (<?= (int) ($counts['pending'] ?? 0) ?>)
        </a>
        <a class="btn btn-outline-light <?= $status === 'paid' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/withdrawals?status=paid">
            Paid (<?= (int) ($counts['paid'] ?? 0) ?>)
        </a>
        <a class="btn btn-outline-light <?= $status === 'rejected' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/withdrawals?status=rejected">
            Rejected (<?= (int) ($counts['rejected'] ?? 0) ?>)
        </a>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">Withdrawal Requests</h6>
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
                                        $badge = 'secondary';
                                        if (($w['status'] ?? '') === 'pending') $badge = 'warning';
                                        if (($w['status'] ?? '') === 'paid') $badge = 'success';
                                        if (($w['status'] ?? '') === 'rejected') $badge = 'danger';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst(htmlspecialchars((string) ($w['status'] ?? ''))) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if (($w['status'] ?? '') === 'pending'): ?>
                                            <div class="d-flex justify-content-end gap-2">
                                                <form method="POST" action="<?= $basePath ?>/admin/withdrawals/approve" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="withdrawal_id" value="<?= (int) ($w['withdrawal_id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= $basePath ?>/admin/withdrawals/reject" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="withdrawal_id" value="<?= (int) ($w['withdrawal_id'] ?? 0) ?>">
                                                    <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Reason (optional)" style="max-width: 160px;">
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
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
