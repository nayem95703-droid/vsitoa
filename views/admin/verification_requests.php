<?php
$page_title = 'Admin Verification Requests - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$status = $status ?? ($_GET['status'] ?? 'pending');
$requests = $requests ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Verification Requests</h1>
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

    <div class="card admin-card admin-card-hover">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="text-white fw-semibold">Requests</div>
                <div class="btn-group">
                    <a class="btn btn-sm btn-outline-light <?= $status === 'pending' ? 'active' : '' ?>" href="<?= Config::get('app.base_path') ?>/admin/verification-requests?status=pending">Pending</a>
                    <a class="btn btn-sm btn-outline-light <?= $status === 'approved' ? 'active' : '' ?>" href="<?= Config::get('app.base_path') ?>/admin/verification-requests?status=approved">Approved</a>
                    <a class="btn btn-sm btn-outline-light <?= $status === 'rejected' ? 'active' : '' ?>" href="<?= Config::get('app.base_path') ?>/admin/verification-requests?status=rejected">Rejected</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-white-50 py-4">No requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><?= (int)($req['id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($req['username'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($req['user_email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($req['user_type'] ?? '') ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($req['status'] ?? '') ?></span></td>
                                    <td><?= htmlspecialchars((string)($req['created_at'] ?? '')) ?></td>
                                    <td class="text-end">
                                        <?php if (($req['status'] ?? '') === 'pending'): ?>
                                            <form method="POST" action="<?= Config::get('app.base_path') ?>/admin/verification/process" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                <input type="hidden" name="request_id" value="<?= (int)($req['id'] ?? 0) ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                            <form method="POST" action="<?= Config::get('app.base_path') ?>/admin/verification/process" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                <input type="hidden" name="request_id" value="<?= (int)($req['id'] ?? 0) ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-white-50">—</span>
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
