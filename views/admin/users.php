<?php
$page_title = 'Admin Users - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
$users = \Core\Database::fetchAll("SELECT user_id, username, email, status, created_at FROM users ORDER BY created_at DESC LIMIT 200");
include_once ROOT_PATH . '/views/partials/blueprint_badge.php';
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Users</h1>
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
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">User Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span><?= htmlspecialchars($user['username']) ?></span>
                                            <?php if (($user['status'] ?? '') === 'active'): ?>
                                                <?php renderBlueprintTick('small', true, 'Blueprint Approved'); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($user['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td class="text-end">
                                        <?php if ($user['status'] !== 'active'): ?>
                                            <form method="POST" action="<?= Config::get('app.base_path') ?>/admin/users/approve" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-check me-1"></i>Approve Blueprint
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?= Config::get('app.base_path') ?>/admin/users/reject" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                                <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-times me-1"></i>Reject Blueprint
                                                </button>
                                            </form>
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
