<?php
$page_title = 'Admin Ads - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$basePath = (string) Config::get('app.base_path', '');
$status = $status ?? 'pending';
$counts = $counts ?? ['pending' => 0, 'active' => 0, 'paused' => 0, 'completed' => 0, 'all' => 0];
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
        <div class="col-md-2">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-list me-1"></i>All</div>
                <h4 class="text-info mb-0"><?= number_format((float) ($counts['all'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-clock me-1"></i>Pending</div>
                <h4 class="text-warning mb-0"><?= number_format((float) ($counts['pending'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-play me-1"></i>Active</div>
                <h4 class="text-success mb-0"><?= number_format((float) ($counts['active'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-pause me-1"></i>Paused</div>
                <h4 class="text-danger mb-0"><?= number_format((float) ($counts['paused'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-archive me-1"></i>Rejected</div>
                <h4 class="text-secondary mb-0"><?= number_format((float) ($counts['completed'] ?? 0)) ?></h4>
            </div>
        </div>
    </div>

    <div class="btn-group mb-3">
        <a class="btn btn-outline-light <?= $status === 'all' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=all">All (<?= (int) ($counts['all'] ?? 0) ?>)</a>
        <a class="btn btn-outline-light <?= $status === 'pending' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=pending">Pending (<?= (int) ($counts['pending'] ?? 0) ?>)</a>
        <a class="btn btn-outline-light <?= $status === 'active' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=active">Active (<?= (int) ($counts['active'] ?? 0) ?>)</a>
        <a class="btn btn-outline-light <?= $status === 'paused' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=paused">Paused (<?= (int) ($counts['paused'] ?? 0) ?>)</a>
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
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ads)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No ads found.</td>
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
                                        <?php if (!empty($ad['description'])): ?>
                                            <small class="text-muted" title="<?= htmlspecialchars((string) $ad['description']) ?>"><?= htmlspecialchars(mb_substr((string) $ad['description'], 0, 40)) ?>...</small>
                                        <?php endif; ?>
                                        <br><small><a href="<?= htmlspecialchars((string) ($ad['target_url'] ?? '')) ?>" target="_blank" class="text-info"><?= htmlspecialchars(mb_substr((string) ($ad['target_url'] ?? ''), 0, 35)) ?>...</a></small>
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
                                        if (($ad['status'] ?? '') === 'paused') $badge = 'danger';
                                        if (($ad['status'] ?? '') === 'completed') $badge = 'primary';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst(htmlspecialchars((string) ($ad['status'] ?? ''))) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if (($ad['status'] ?? '') === 'pending'): ?>
                                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                                <form method="POST" action="<?= $basePath ?>/admin/ads/approve" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="ad_id" value="<?= (int) ($ad['ad_id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve & Activate">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= $basePath ?>/admin/ads/reject" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="ad_id" value="<?= (int) ($ad['ad_id'] ?? 0) ?>">
                                                    <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Reason (optional)" style="max-width: 140px;">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject Ad">
                                                        <i class="fas fa-times me-1"></i>Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif (($ad['status'] ?? '') === 'active'): ?>
                                            <div class="d-flex justify-content-end gap-1">
                                                <form method="POST" action="<?= $basePath ?>/admin/ads/pause" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="ad_id" value="<?= (int) ($ad['ad_id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Pause - Inappropriate Content" onclick="return confirm('Pause this ad? (e.g. sexual/inappropriate content)')">
                                                        <i class="fas fa-ban me-1"></i>Pause
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif (($ad['status'] ?? '') === 'paused'): ?>
                                            <div class="d-flex justify-content-end gap-1">
                                                <form method="POST" action="<?= $basePath ?>/admin/ads/resume" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="ad_id" value="<?= (int) ($ad['ad_id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Resume Ad">
                                                        <i class="fas fa-play me-1"></i>Resume
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
