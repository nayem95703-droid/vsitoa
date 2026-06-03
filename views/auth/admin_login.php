<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Admin Login - ' . Config::get('app.name');
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
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h2 class="fw-bold"><?= Config::get('app.name') ?></h2>
                    <p class="text-muted">Admin Panel Login</p>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form id="adminLoginForm">
                            <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="passwordToggle"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="adminLoginBtn">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="<?= $basePath ?>/" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Back to site
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary text-white">
            <div class="text-center px-4">
                <h1 class="display-5 fw-bold mb-4">Admin Dashboard</h1>
                <p class="lead mb-0">Manage users, payments, ads, and platform settings.</p>
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

<style>
.input-group-text {
    background: transparent;
    border-right: none;
}

.form-control {
    border-left: none;
}

.form-control:focus {
    border-left: none;
    box-shadow: none;
}

.input-group .form-control:focus + .input-group-text {
    border-color: #86b7fe;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('adminLoginForm');
    const btn = document.getElementById('adminLoginBtn');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';

        fetch((window.VSItoA_BASE_PATH || '') + '/admin/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': data._token
            },
            body: JSON.stringify({
    password: data.password
})
        })
        .then(r => r.json())
        .then(res => {
            console.log('Admin login response:', res);
            if (res.success) {
                console.log('Login successful, preparing redirect...');
                showAlert('Success', res.message, 'success');
                const redirectPath = res.redirect || '/admin';
                const fullRedirect = (window.VSItoA_BASE_PATH || '') + redirectPath;
                console.log('About to redirect to', fullRedirect);
                window.location.replace(fullRedirect);
                console.log('Redirect command executed');
            } else {
                console.log('Login failed:', res.message);
                showAlert('Error', res.message || 'Login failed', 'danger');
            }
        })
        .catch(() => {
            showAlert('Error', 'An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Login';
        });
    });
});

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggle.classList.remove('fa-eye');
        passwordToggle.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordToggle.classList.remove('fa-eye-slash');
        passwordToggle.classList.add('fa-eye');
    }
}

function showAlert(title, message, type) {
    const modal = new bootstrap.Modal(document.getElementById('alertModal'));
    document.getElementById('alertModalTitle').textContent = title;
    document.getElementById('alertModalBody').innerHTML = `
        <div class="alert alert-${type}" role="alert">
            ${message}
        </div>
    `;
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
