<?php
$page_title = 'Notifications - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAuth();
$basePath = (string) Config::get('app.base_path', '');
$userId = \Core\Auth::id();

$notifications = [];
$unreadCount = 0;
if ($userId) {
    $notifications = \Core\Database::fetchAll(
        "SELECT id, title, message, type, is_read, reference_type, reference_id, created_at 
         FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100",
        [$userId]
    );
    $unreadCount = (int) \Core\Database::fetchColumn(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
        [$userId]
    );
}

ob_start();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h2">
            Notifications
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
            <?php endif; ?>
        </h1>
        <div class="d-flex gap-2">
            <?php if ($unreadCount > 0): ?>
                <form method="POST" action="<?= $basePath ?>/notifications/mark-all-read" class="d-inline">
                    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-check-double me-1"></i>Mark all read
                    </button>
                </form>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= $basePath ?>/dashboard">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-bell-slash fa-3x mb-3"></i>
                    <p>No notifications yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $note): ?>
                        <?php
                            $borderColor = '#0d6efd';
                            if (($note['type'] ?? '') === 'broadcast') $borderColor = '#ffc107';
                            elseif (($note['type'] ?? '') === 'deposit') $borderColor = '#198754';
                            elseif (($note['type'] ?? '') === 'withdrawal') $borderColor = '#dc3545';
                        ?>
                        <div class="list-group-item <?= ($note['is_read'] ?? 0) ? 'bg-light text-muted' : '' ?>" style="border-left: 4px solid <?= $borderColor ?>;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <?php if (!empty($note['title'])): ?>
                                        <strong><?= htmlspecialchars($note['title']) ?></strong><br>
                                    <?php endif; ?>
                                    <p class="mb-1"><?= htmlspecialchars($note['message']) ?></p>
                                    <small class="text-muted">
                                        <?php if (($note['type'] ?? '') === 'broadcast'): ?>
                                            <i class="fas fa-bullhorn me-1 text-warning"></i>Broadcast
                                        <?php endif; ?>
                                        <i class="fas fa-clock ms-2 me-1"></i>
                                        <?= !empty($note['created_at']) ? date('M j, Y g:i A', strtotime($note['created_at'])) : '' ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <?php if (($note['is_read'] ?? 0) == 0): ?>
                                        <span class="badge bg-primary">New</span>
                                        <form method="POST" action="<?= $basePath ?>/notifications/mark-read" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                            <input type="hidden" name="id" value="<?= (int) $note['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Read</button>
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
