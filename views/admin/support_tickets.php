<?php
$page_title = 'Admin Support Tickets - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

\Core\Auth::requireAdmin();

$tickets = $tickets ?? [];

ob_start();
?>

<div class="container-fluid py-4 admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 text-white mb-0">Support Tickets</h1>
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
                <div class="text-white fw-semibold">All Tickets</div>
                <a class="btn btn-sm btn-outline-light" href="<?= Config::get('app.base_path') ?>/admin/support/tickets">Refresh</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Responses</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-white-50 py-4">No tickets found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td><?= (int)($t['id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($t['username'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($t['subject'] ?? '') ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($t['status'] ?? '') ?></span></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($t['priority'] ?? '') ?></span></td>
                                    <td><?= (int)($t['response_count'] ?? 0) ?></td>
                                    <td class="text-end">
                                        <form method="POST" action="<?= Config::get('app.base_path') ?>/admin/support/update-status" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                            <input type="hidden" name="ticket_id" value="<?= (int)($t['id'] ?? 0) ?>">
                                            <input type="hidden" name="status" value="in_progress">
                                            <button type="submit" class="btn btn-sm btn-outline-light">In Progress</button>
                                        </form>
                                        <form method="POST" action="<?= Config::get('app.base_path') ?>/admin/support/update-status" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                                            <input type="hidden" name="ticket_id" value="<?= (int)($t['id'] ?? 0) ?>">
                                            <input type="hidden" name="status" value="resolved">
                                            <button type="submit" class="btn btn-sm btn-success">Resolve</button>
                                        </form>
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
