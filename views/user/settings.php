<?php
$page_title = 'Settings - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
include_once ROOT_PATH . '/views/partials/blueprint_badge.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Settings</h1>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="/settings">
                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                        <div class="mb-4">
                            <label class="form-label d-block">Dark Mode</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode">
                                <label class="form-check-label" for="dark_mode">Enable dark mode</label>
                            </div>
                            <small class="text-muted">Turns the dashboard into a darker theme.</small>
                        </div>

                        <div class="mb-3">
                            <label for="language" class="form-label">Language</label>
                            <select class="form-select" id="language" name="language">
                                <option value="bn">বাংলা</option>
                                <option value="en">English</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="email_notifications" class="form-label">Email Notifications</label>
                            <select class="form-select" id="email_notifications" name="email_notifications">
                                <option value="enabled">Enabled</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Asia/Dhaka">Asia/Dhaka</option>
                                <option value="UTC">UTC</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">System Notes</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Some settings are managed by admin.</p>
                    <ul class="small text-muted mb-0">
                        <li class="d-flex align-items-center gap-2">
                            <?php renderBlueprintTick('small', true, 'Blueprint Approved'); ?>
                            <span>Blueprint badge appears after admin approval.</span>
                        </li>
                        <li>Account verification status is controlled by admin.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
