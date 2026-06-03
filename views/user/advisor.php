<?php
$page_title = 'Advisor - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Offer / Advisor</h1>
        <span class="badge bg-primary">Sponsored Ads</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Create Sponsored Offer</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Use your advisor balance to run sponsored offers on other marketplaces.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= Config::get('app.base_path') ?>/advisor/create-ad" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Offer
                        </a>
                        <a href="<?= Config::get('app.base_path') ?>/advisor/manage-ads" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Manage Offers
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Offer Networks (Temporary)</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="mb-2">MaxBounty</h6>
                                <p class="text-muted small mb-3">Postback/offer links will be added here later.</p>
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>Link Pending</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="mb-2">CPAlead</h6>
                                <p class="text-muted small mb-3">Postback/offer links will be added here later.</p>
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>Link Pending</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="mb-2">AdCombo</h6>
                                <p class="text-muted small mb-3">Postback/offer links will be added here later.</p>
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>Link Pending</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Advisor Balance</h6>
                </div>
                <div class="card-body">
                    <div class="h4 mb-0 text-success">
                        <?= number_format((\Core\Auth::user()['advisor_balance'] ?? 0), 2) ?> USDT
                    </div>
                    <small class="text-muted">Use this balance for sponsored ads.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
