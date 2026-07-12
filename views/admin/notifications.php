<?php
$page_title = 'Admin Notifications - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$basePath = (string) Config::get('app.base_path', '');
$notifications = $notifications ?? [];
$unreadCount = $unreadCount ?? 0;

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Notifications
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
            <?php endif; ?>
        </h1>
        <div class="d-flex gap-2">
            <?php if ($unreadCount > 0): ?>
                <form method="POST" action="<?= $basePath ?>/admin/notifications/mark-all-read" class="d-inline">
                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-check-double me-1"></i>Mark all read
                    </button>
                </form>
            <?php endif; ?>
            <a class="btn btn-sm admin-btn-accent" href="<?= $basePath ?>/admin">Back to Dashboard</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Broadcast Message Card -->
    <div class="card admin-card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-bullhorn me-2"></i>Broadcast Message to All Users</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= $basePath ?>/admin/broadcast">
                <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                <div class="mb-3">
                    <label for="broadcast_title" class="form-label text-white">Title</label>
                    <input type="text" class="form-control" id="broadcast_title" name="title" placeholder="e.g. Maintenance Notice" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label for="broadcast_message" class="form-label text-white">Message</label>
                    <textarea class="form-control" id="broadcast_message" name="message" rows="3" placeholder="Type your announcement here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-warning" onclick="return confirm('Send this message to ALL users?')">
                    <i class="fas fa-paper-plane me-1"></i>Broadcast to All Users
                </button>
            </form>
        </div>
    </div>

    <div class="card admin-card admin-card-hover">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">System Notifications</h6>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-bell-slash fa-3x mb-3"></i>
                    <p>No notifications yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($notifications as $note): ?>
                        <div class="list-group-item <?= ($note['is_read'] ?? 0) ? 'bg-dark text-muted' : 'bg-dark text-white border-secondary' ?>" style="border-left: 4px solid <?= ($note['type'] ?? '') === 'deposit' ? '#198754' : (($note['type'] ?? '') === 'withdrawal' ? '#ffc107' : (($note['type'] ?? '') === 'warning' ? '#dc3545' : '#0d6efd')) ?>;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="mb-1"><?= htmlspecialchars((string) ($note['message'] ?? '')) ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= !empty($note['created_at']) ? date('M j, Y g:i A', strtotime((string) $note['created_at'])) : '' ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <?php if (($note['is_read'] ?? 0) == 0): ?>
                                        <span class="badge bg-primary">New</span>
                                        <form method="POST" action="<?= $basePath ?>/admin/notifications/mark-read" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                            <input type="hidden" name="id" value="<?= (int) $note['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-light">Read</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Read</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
