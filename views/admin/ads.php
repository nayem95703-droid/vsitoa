<?php
$page_title = 'Admin Ads - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$basePath = (string) Config::get('app.base_path', '');
$status = $status ?? 'all';
$counts = $counts ?? ['active' => 0, 'blocked' => 0, 'paused' => 0, 'completed' => 0, 'all' => 0, 'reports' => 0];
$ads = $ads ?? [];
$reports = $reports ?? [];

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
                <div class="text-muted mb-1"><i class="fas fa-play me-1"></i>Active</div>
                <h4 class="text-success mb-0"><?= number_format((float) ($counts['active'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-ban me-1"></i>Blocked</div>
                <h4 class="text-danger mb-0"><?= number_format((float) ($counts['blocked'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-pause me-1"></i>Paused</div>
                <h4 class="text-warning mb-0"><?= number_format((float) ($counts['paused'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-archive me-1"></i>Completed</div>
                <h4 class="text-secondary mb-0"><?= number_format((float) ($counts['completed'] ?? 0)) ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card admin-card text-center p-3 border-<?= ($counts['reports'] ?? 0) > 0 ? 'danger' : '' ?>">
                <div class="text-muted mb-1"><i class="fas fa-flag me-1"></i>Reports</div>
                <h4 class="mb-0 <?= ($counts['reports'] ?? 0) > 0 ? 'text-danger' : 'text-secondary' ?>"><?= number_format((float) ($counts['reports'] ?? 0)) ?></h4>
            </div>
        </div>
    </div>

    <div class="btn-group mb-3 flex-wrap">
        <a class="btn btn-outline-light <?= $status === 'all' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=all">All</a>
        <a class="btn btn-outline-light <?= $status === 'active' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=active">Active</a>
        <a class="btn btn-outline-light <?= $status === 'blocked' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=blocked">Blocked</a>
        <a class="btn btn-outline-light <?= $status === 'paused' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=paused">Paused</a>
        <a class="btn btn-outline-light <?= $status === 'completed' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=completed">Completed</a>
        <?php if (($counts['reports'] ?? 0) > 0): ?>
            <a class="btn btn-danger <?= $status === 'reports' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=reports">
                <i class="fas fa-flag me-1"></i>Reports (<?= (int) ($counts['reports'] ?? 0) ?>)
            </a>
        <?php else: ?>
            <a class="btn btn-outline-light <?= $status === 'reports' ? 'active' : '' ?>" href="<?= $basePath ?>/admin/ads?status=reports">Reports</a>
        <?php endif; ?>
    </div>

    <?php if ($status === 'reports'): ?>
        <div class="card admin-card admin-card-hover">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-flag me-2"></i>Pending Reports</h6>
            </div>
            <div class="card-body">
                <?php if (empty($reports)): ?>
                    <p class="text-center text-muted py-4 mb-0">No pending reports.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover admin-table">
                            <thead>
                                <tr>
                                    <th>Report ID</th>
                                    <th>Ad</th>
                                    <th>Reported By</th>
                                    <th>Reason</th>
                                    <th>Details</th>
                                    <th>Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $r): ?>
                                    <tr>
                                        <td>#<?= (int) ($r['id'] ?? 0) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars((string) ($r['ad_title'] ?? '')) ?></div>
                                            <small class="text-muted">by <?= htmlspecialchars((string) ($r['ad_owner'] ?? '')) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($r['reporter_username'] ?? '')) ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= htmlspecialchars((string) ($r['reason'] ?? '')) ?></span></td>
                                        <td><small class="text-muted"><?= htmlspecialchars(mb_substr((string) ($r['details'] ?? ''), 0, 50)) ?></small></td>
                                        <td><?= !empty($r['created_at']) ? date('M j, Y', strtotime((string) $r['created_at'])) : '' ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                                <form method="POST" action="<?= $basePath ?>/admin/ads/block" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="ad_id" value="<?= (int) ($r['ad_id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Block Ad" onclick="return confirm('Block this ad permanently?')">
                                                        <i class="fas fa-ban me-1"></i>Block
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= $basePath ?>/admin/ads/dismiss-report" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="report_id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Dismiss Report">
                                                        <i class="fas fa-times me-1"></i>Dismiss
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
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
                                <th>Views (Received/Total)</th>
                                <th>Spent</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
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
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars((string) ($ad['ad_title'] ?? '')) ?>
                                                <?php if (($ad['report_count'] ?? 0) > 0): ?>
                                                    <span class="badge bg-danger ms-1" title="<?= (int) $ad['report_count'] ?> pending reports">
                                                        <i class="fas fa-flag"></i> <?= (int) $ad['report_count'] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($ad['description'])): ?>
                                                <small class="text-muted" title="<?= htmlspecialchars((string) $ad['description']) ?>"><?= htmlspecialchars(mb_substr((string) $ad['description'], 0, 40)) ?>...</small>
                                            <?php endif; ?>
                                            <br><small><a href="<?= htmlspecialchars((string) ($ad['target_url'] ?? '')) ?>" target="_blank" class="text-info"><?= htmlspecialchars(mb_substr((string) ($ad['target_url'] ?? ''), 0, 35)) ?>...</a></small>
                                        </td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars((string) ($ad['ad_type'] ?? '')) ?></span></td>
                                        <td><?= number_format((float) ($ad['cost_per_view'] ?? 0), 8) ?></td>
                                        <td>
                                            <?= number_format((int) ($ad['views_received'] ?? 0)) ?> / <?= number_format((int) ($ad['total_views'] ?? 0)) ?>
                                            <?php if (($ad['total_views'] ?? 0) > 0): ?>
                                                <br><small class="text-muted"><?= round(((int) ($ad['views_received'] ?? 0) / (int) $ad['total_views']) * 100) ?>% complete</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format((float) ($ad['spent_amount'] ?? 0), 2) ?></td>
                                        <td><?= !empty($ad['created_at']) ? date('M j, Y', strtotime((string) $ad['created_at'])) : '' ?></td>
                                        <td>
                                            <?php
                                            $badge = 'secondary';
                                            if (($ad['status'] ?? '') === 'active') $badge = 'success';
                                            if (($ad['status'] ?? '') === 'blocked') $badge = 'danger';
                                            if (($ad['status'] ?? '') === 'paused') $badge = 'warning';
                                            if (($ad['status'] ?? '') === 'completed') $badge = 'primary';
                                            ?>
                                            <span class="badge bg-<?= $badge ?>"><?= ucfirst(htmlspecialchars((string) ($ad['status'] ?? ''))) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                                <?php if (($ad['status'] ?? '') === 'active'): ?>
                                                    <form method="POST" action="<?= $basePath ?>/admin/ads/block" class="d-inline">
                                                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                        <input type="hidden" name="ad_id" value="<?= (int) ($ad['ad_id'] ?? 0) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Block Ad" onclick="return confirm('Block this ad? It will no longer appear to users.')">
                                                            <i class="fas fa-ban me-1"></i>Block
                                                        </button>
                                                    </form>
                                                <?php elseif (($ad['status'] ?? '') === 'blocked'): ?>
                                                    <form method="POST" action="<?= $basePath ?>/admin/ads/unblock" class="d-inline">
                                                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                        <input type="hidden" name="ad_id" value="<?= (int) ($ad['ad_id'] ?? 0) ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Unblock & Reactivate Ad">
                                                            <i class="fas fa-unlock me-1"></i>Unblock
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" action="<?= $basePath ?>/admin/ads/delete" class="d-inline">
                                                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                    <input type="hidden" name="ad_id" value="<?= (int) ($ad['ad_id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Ad Permanently" onclick="return confirm('Permanently delete this ad? This cannot be undone.')">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </div>
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
