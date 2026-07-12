<?php
$page_title = 'Admin Referrals - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$basePath = (string) Config::get('app.base_path', '');
$totalReferrals = $totalReferrals ?? 0;
$totalCommission = $totalCommission ?? 0;
$topReferrers = $topReferrers ?? [];
$recentReferrals = $recentReferrals ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Referrals</h1>
        <span class="badge admin-badge">Admin Panel</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-users me-1"></i>Total Referrals</div>
                <h4 class="text-info mb-0"><?= number_format((float) $totalReferrals) ?></h4>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card admin-card text-center p-3">
                <div class="text-muted mb-1"><i class="fas fa-coins me-1"></i>Total Commission Paid</div>
                <h4 class="text-success mb-0"><?= number_format((float) $totalCommission, 2) ?> USDT</h4>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Top Referrers</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($topReferrers)): ?>
                        <div class="text-center text-muted py-4">No referral data yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th class="text-end">Referrals</th>
                                        <th class="text-end">Earned</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topReferrers as $r): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars((string) ($r['username'] ?? '')) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars((string) ($r['email'] ?? '')) ?></small>
                                            </td>
                                            <td class="text-end"><?= (int) ($r['referral_count'] ?? 0) ?></td>
                                            <td class="text-end"><?= number_format((float) ($r['total_earned'] ?? 0), 2) ?> USDT</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card admin-card admin-card-hover">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Recent Referrals</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recentReferrals)): ?>
                        <div class="text-center text-muted py-4">No recent referrals.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>Referrer</th>
                                        <th>Referred User</th>
                                        <th>Rate</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentReferrals as $r): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars((string) ($r['referrer'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($r['referred'] ?? '')) ?></td>
                                            <td><?= (float) ($r['commission_rate'] ?? 0) ?>%</td>
                                            <td><?= !empty($r['created_at']) ? date('M j, Y', strtotime((string) $r['created_at'])) : '' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
