<?php
$page_title = 'Login - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <!-- Left Side - Login Form -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light">
            <div class="w-100" style="max-width: 400px;">
                <div class="text-center mb-4">
                    <i class="fas fa-coins fa-3x text-primary mb-3"></i>
                    <h2 class="fw-bold"><?= Config::get('app.name') ?></h2>
                    <p class="text-muted">Welcome back! Please login to your account.</p>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form id="loginForm">
                            <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

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

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="loginBtn">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <a href="<?= Config::get('app.base_path') ?>/forgot-password" class="text-decoration-none">
                                    <i class="fas fa-question-circle me-1"></i>Forgot your password?
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="mb-0">Don't have an account? 
                        <a href="<?= Config::get('app.base_path') ?>/register" class="text-decoration-none fw-bold">
                            Sign up here
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Simple Call to Action -->
        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary text-white">
            <div class="text-center px-4">
                <h1 class="display-4 fw-bold mb-4">Join <?= Config::get('app.name') ?></h1>
                <p class="lead mb-4">Start earning cryptocurrency today!</p>
                <div class="d-grid gap-2 col-6 mx-auto">
                    <a href="<?= Config::get('app.base_path') ?>/register" class="btn btn-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Sign Up
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Modal -->
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
.feature-item {
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    transition: transform 0.3s ease;
}

.feature-item:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.15);
}

.stats-container {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 30px;
    margin-top: 30px;
}

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
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const basePath = window.VSItoA_BASE_PATH || '';
    
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(loginForm);
        const data = Object.fromEntries(formData);
        
        // Add redirect parameter if exists
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('redirect')) {
            data.redirect = urlParams.get('redirect');
        }
        
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
        
        fetch(basePath + '/login', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': data._token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store token if provided
                if (data.token) {
                    localStorage.setItem('jwt_token', data.token);
                }
                
                showAlert('Success', data.message, 'success');
                
                // Redirect after delay
                setTimeout(() => {
                    window.location.href = data.redirect || (basePath + '/dashboard');
                }, 1500);
            } else {
                showAlert('Error', data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error', 'An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            loginBtn.disabled = false;
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Login';
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

// Auto-focus email field
document.getElementById('email').focus();
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
