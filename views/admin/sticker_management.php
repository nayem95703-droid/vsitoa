<?php
$page_title = 'Admin Stickers - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$allStickers = $allStickers ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Sticker Management</h1>
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
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-white fw-semibold">Latest Stickers</div>
                <form method="POST" action="<?= Config::get('app.base_path') ?>/admin/stickers/create" class="d-flex gap-2">
                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                    <input class="form-control form-control-sm" name="sticker_name" placeholder="Sticker name" required>
                    <input class="form-control form-control-sm" name="sticker_icon" placeholder="FontAwesome class" required>
                    <input class="form-control form-control-sm" name="sticker_color" placeholder="#hex" required>
                    <button class="btn btn-sm admin-btn-accent" type="submit">Create</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Name</th>
                            <th>Icon</th>
                            <th>Color</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allStickers)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-white-50 py-4">No stickers found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allStickers as $s): ?>
                                <tr>
                                    <td><?= (int)($s['id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($s['username'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($s['sticker_name'] ?? '') ?></td>
                                    <td><i class="<?= htmlspecialchars($s['sticker_icon'] ?? '') ?>"></i></td>
                                    <td><span class="badge" style="background: <?= htmlspecialchars($s['sticker_color'] ?? '#666') ?>;">&nbsp;</span> <?= htmlspecialchars($s['sticker_color'] ?? '') ?></td>
                                    <td><?= htmlspecialchars((string)($s['created_at'] ?? '')) ?></td>
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
