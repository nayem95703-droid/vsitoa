<?php
$page_title = 'Admin Ads - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$basePath = (string) Config::get('app.base_path', '');
$status = $status ?? 'pending';
$counts = $counts ?? ['pending' => 0, 'active' => 0, 'paused' => 0, 'completed' => 0];
$ads = $ads ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Advertisements</h1>
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
                <div class="text-muted mb-1"><i class="fas fa-clock me-1"></i>Pending</div>
                <h4 class="text-warning mb-0"><?= number_format((float) ($counts['pending'] ?? 0)) ?></h4>
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
                <h4 class="text-info mb-0"><?= number_format((float) ($counts['paused'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-check-circle me-1"></i>Completed</div>
                <h4 class="text-secondary mb-0"><?= number_format((float) ($counts['completed'] ?? 0)) ?></h4>
            </div>
        </div>
    </div>

    <div class="btn-group mb-3">
        <a class="btn btn-outline-light <?= $status === 'pending' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=pending">Pending</a>
        <a class="btn btn-outline-light <?= $status === 'active' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=active">Active</a>
        <a class="btn btn-outline-light <?= $status === 'paused' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=paused">Paused</a>
        <a class="btn btn-outline-light <?= $status === 'completed' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=completed">Completed</a>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">Ad Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Cost/View</th>
                            <th>Views</th>
                            <th>Spent</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ads)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No ads found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ads as $ad): ?>
                                <tr>
                                    <td>#<?= (int) ($ad['ad_id'] ?? 0) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($ad['username'] ?? '')) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($ad['email'] ?? '')) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($ad['ad_title'] ?? '')) ?></div>
                                        <small class="text-muted"><a href="<?= htmlspecialchars((string) ($ad['target_url'] ?? '')) ?>" target="_blank" class="text-info"><?= htmlspecialchars(substr((string) ($ad['target_url'] ?? ''), 0, 30)) ?>...</a></small>
                                    </td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars((string) ($ad['ad_type'] ?? '')) ?></span></td>
                                    <td><?= number_format((float) ($ad['cost_per_view'] ?? 0), 8) ?></td>
                                    <td><?= number_format((int) ($ad['total_views'] ?? 0)) ?></td>
                                    <td><?= number_format((float) ($ad['spent_amount'] ?? 0), 2) ?></td>
                                    <td><?= !empty($ad['created_at']) ? date('M j, Y', strtotime((string) $ad['created_at'])) : '' ?></td>
                                    <td>
                                        <?php
                                        $badge = 'secondary';
                                        if (($ad['status'] ?? '') === 'pending') $badge = 'warning';
                                        if (($ad['status'] ?? '') === 'active') $badge = 'success';
                                        if (($ad['status'] ?? '') === 'paused') $badge = 'info';
                                        if (($ad['status'] ?? '') === 'completed') $badge = 'primary';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst(htmlspecialchars((string) ($ad['status'] ?? ''))) ?></span>
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
