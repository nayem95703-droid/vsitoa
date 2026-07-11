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
    ?>

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
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
