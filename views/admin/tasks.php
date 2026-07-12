<?php
$page_title = 'Admin Tasks - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$basePath = (string) Config::get('app.base_path', '');
$status = $status ?? 'active';
$counts = $counts ?? ['draft' => 0, 'active' => 0, 'paused' => 0, 'completed' => 0];
$tasks = $tasks ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Tasks</h1>
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
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-edit me-1"></i>Draft</div>
                <h4 class="text-secondary mb-0"><?= number_format((float) ($counts['draft'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-play me-1"></i>Active</div>
                <h4 class="text-success mb-0"><?= number_format((float) ($counts['active'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-pause me-1"></i>Paused</div>
                <h4 class="text-warning mb-0"><?= number_format((float) ($counts['paused'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-check-circle me-1"></i>Completed</div>
                <h4 class="text-info mb-0"><?= number_format((float) ($counts['completed'] ?? 0)) ?></h4>
            </div>
        </div>
    </div>

    <div class="btn-group mb-3">
        <a class="btn btn-outline-light <?= $status === 'draft' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/tasks?status=draft">Draft</a>
        <a class="btn btn-outline-light <?= $status === 'active' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/tasks?status=active">Active</a>
        <a class="btn btn-outline-light <?= $status === 'paused' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/tasks?status=paused">Paused</a>
        <a class="btn btn-outline-light <?= $status === 'completed' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/tasks?status=completed">Completed</a>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">Task Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Advertiser</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Payout</th>
                            <th>Budget</th>
                            <th>Executions</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No tasks found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $t): ?>
                                <tr>
                                    <td>#<?= (int) ($t['id'] ?? 0) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($t['username'] ?? '')) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($t['email'] ?? '')) ?></small>
                                    </td>
                                    <td class="fw-semibold"><?= htmlspecialchars((string) ($t['title'] ?? '')) ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars((string) ($t['ad_type'] ?? '')) ?></span></td>
                                    <td><?= number_format((float) ($t['payment_per_execution'] ?? 0), 2) ?> USDT</td>
                                    <td><?= number_format((float) ($t['total_budget'] ?? 0), 2) ?></td>
                                    <td><?= (int) ($t['current_executions'] ?? 0) ?> / <?= (int) ($t['max_executions'] ?? 0) ?></td>
                                    <td><?= !empty($t['created_at']) ? date('M j, Y', strtotime((string) $t['created_at'])) : '' ?></td>
                                    <td>
                                        <?php
                                        $badge = 'secondary';
                                        if (($t['status'] ?? '') === 'draft') $badge = 'secondary';
                                        if (($t['status'] ?? '') === 'active') $badge = 'success';
                                        if (($t['status'] ?? '') === 'paused') $badge = 'warning';
                                        if (($t['status'] ?? '') === 'completed') $badge = 'primary';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst(htmlspecialchars((string) ($t['status'] ?? ''))) ?></span>
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
