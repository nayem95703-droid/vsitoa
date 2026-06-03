<?php
$page_title = 'Profile - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
$user = \Core\Auth::user() ?? [];
$username = $user['username'] ?? '';
$email = $user['email'] ?? '';
$status = $user['status'] ?? 'unverified';
if (!empty($user['user_id'])) {
    if (\Core\Database::columnExists('users', 'is_verified')) {
        $freshUser = \Core\Database::fetch("SELECT status, is_verified FROM users WHERE user_id = ?", [$user['user_id']]);
        $status = $freshUser['status'] ?? $status;
        $isVerified = (bool) ($freshUser['is_verified'] ?? false);
    } else {
        $freshUser = \Core\Database::fetch("SELECT status FROM users WHERE user_id = ?", [$user['user_id']]);
        $status = $freshUser['status'] ?? $status;
        $isVerified = false;
    }
} else {
    $isVerified = $user['is_verified'] ?? false;
}
$emailVerified = !empty($user['email_verified']);
$avatar = $user['avatar'] ?? '';
$blueprintApproved = $status === 'active';
$basePath = Config::get('app.base_path');

// Include verified badge component
include_once ROOT_PATH . '/views/partials/verified_badge.php';

// Include blueprint tick component
include_once ROOT_PATH . '/views/partials/blueprint_badge.php';

// Get user stickers if verified
$userStickers = [];
if ($isVerified) {
    $stickerController = new \App\Controllers\StickerController();
    $userStickers = $stickerController->getUserStickers($user['id'] ?? 0);
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Profile</h1>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="/profile" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                        <div class="row g-3">
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <img src="<?= $avatar ?: 'https://picsum.photos/seed/user' . (\Core\Auth::id() ?? 0) . '/120/120.jpg' ?>" alt="Avatar" class="rounded-circle shadow" width="120" height="120">
                                </div>
                                <label for="avatar" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
                                        <?php if ($blueprintApproved): ?>
                                            <?php renderBlueprintTick('small', true, 'Blueprint Approved'); ?>
                                        <?php endif; ?>
                                        <?php if ($isVerified): ?>
                                            <?php renderVerifiedBadge('small', true, 'Verified User'); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Blueprint Status</h6>
                </div>
                <div class="card-body">
                    <?php if ($blueprintApproved): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <?php renderBlueprintTick('small', true, 'Blueprint Approved'); ?>
                            <span class="badge bg-success">Blueprint Approved</span>
                            <?php if ($isVerified): ?>
                                <?php renderVerifiedBadge('small', true, 'Verified Blueprint Holder'); ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted mb-0">Your blueprint badge is active and visible next to your name.</p>
                    <?php else: ?>
                        <span class="badge bg-warning mb-2">Pending Approval</span>
                        <p class="text-muted mb-0">Blueprint will appear after admin approval.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Account Verification</h6>
                </div>
                <div class="card-body">
                    <?php if ($isVerified): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <?php renderVerifiedBadge('normal', true, 'Verified Account'); ?>
                            <span class="badge bg-success">Verified</span>
                        </div>
                        <p class="text-muted mb-2">Your account is verified with enhanced protection and features.</p>
                        <a href="<?= $basePath ?>/verify" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-shield-alt me-2"></i>View Verification Benefits
                        </a>
                    <?php else: ?>
                        <span class="badge bg-warning mb-2">Unverified</span>
                        <p class="text-muted mb-2">Get verified to unlock enhanced features and protection.</p>
                        <a href="<?= $basePath ?>/verify" class="btn btn-primary btn-sm">
                            <i class="fas fa-check-circle me-2"></i>Apply for Verification
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Security</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Update your password regularly to keep your account secure.</p>
                    <a href="<?= $basePath ?>/change-password" class="btn btn-outline-secondary">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($userStickers)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-star me-2"></i>Your Stickers & Achievements
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($userStickers as $sticker): ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="mb-2">
                                        <?php echo \App\Controllers\StickerController::renderSticker($sticker, 'large'); ?>
                                    </div>
                                    <small class="text-muted d-block"><?= htmlspecialchars($sticker['sticker_name']) ?></small>
                                    <?php if (!empty($sticker['description'])): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($sticker['description']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?= $basePath ?>/stickers" class="btn btn-outline-primary">
                            <i class="fas fa-th me-2"></i>View All Stickers
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
