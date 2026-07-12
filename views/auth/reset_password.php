<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Reset Password - ' . Config::get('app.name');
$show_navbar = false;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light">
            <div class="w-100" style="max-width: 400px;">
                <div class="text-center mb-4">
                    <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                    <h2 class="fw-bold">Reset Password</h2>
                    <p class="text-muted">Enter your new password below</p>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form id="resetPasswordForm">
                            <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                </div>
                                <div class="form-text">Minimum 8 characters</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Reset Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="<?= $basePath ?>/login" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Back to Login
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary text-white">
            <div class="text-center px-4">
                <h1 class="display-5 fw-bold mb-4">New Password</h1>
                <p class="lead mb-0">Create a strong password to secure your account.</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="alertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertModalTitle">Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="alertModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    const btn = document.getElementById('submitBtn');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        if (data.password !== data.confirm_password) {
            showAlert('Error', 'Passwords do not match.', 'danger');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Resetting...';

        fetch((window.VSItoA_BASE_PATH || '') + '/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': data._token
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showAlert('Success', res.message, 'success');
                setTimeout(() => {
                    window.location.replace((window.VSItoA_BASE_PATH || '') + '/login');
                }, 2000);
            } else {
                showAlert('Error', res.message || 'Reset failed.', 'danger');
            }
        })
        .catch(() => {
            showAlert('Error', 'An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Reset Password';
        });
    });
});

function showAlert(title, message, type) {
    const modal = new bootstrap.Modal(document.getElementById('alertModal'));
    document.getElementById('alertModalTitle').textContent = title;
    document.getElementById('alertModalBody').innerHTML = '<div class="alert alert-' + type + '">' + message + '</div>';
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
