<?php
$page_title = 'Register - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light">
            <div class="w-100" style="max-width: 450px;">
                <div class="text-center mb-4">
                    <i class="fas fa-coins fa-3x text-primary mb-3"></i>
                    <h2 class="fw-bold">Join <?= Config::get('app.name') ?></h2>
                    <p class="text-muted">Create your account and start earning crypto today!</p>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form id="registerForm">
                            <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                            
                            <h5 class="mb-3">Account Information</h5>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <small class="text-muted">3-50 characters, letters and numbers only</small>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye" id="passwordToggle"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="confirmPasswordToggle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mb-3 mt-4">Referral (Optional)</h5>
                            
                            <div class="mb-3">
                                <label for="referral_code" class="form-label">Referral Code</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-gift"></i></span>
                                    <input type="text" class="form-control" id="referral_code" name="referral_code" placeholder="Enter referral code if you have one">
                                </div>
                                <small class="text-muted">Get bonus earnings from referrals</small>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="<?= Config::get('app.base_path') ?>/terms" target="_blank">Terms and Conditions</a> and <a href="<?= Config::get('app.base_path') ?>/privacy" target="_blank">Privacy Policy</a>
                                </label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="registerBtn">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <p class="mb-0">Already have an account? 
                                    <a href="<?= Config::get('app.base_path') ?>/login" class="text-decoration-none fw-bold">
                                        Login here
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary text-white">
            <div class="text-center px-4">
                <h1 class="display-4 fw-bold mb-4">Why Join <?= Config::get('app.name') ?>?</h1>
                
                <div class="benefits-list mb-4">
                    <div class="benefit-item mb-4">
                        <i class="fas fa-coins fa-2x mb-3"></i>
                        <h4>Multiple Earning Methods</h4>
                        <p>View ads, complete tasks, participate in surveys, and more</p>
                    </div>
                    
                    <div class="benefit-item mb-4">
                        <i class="fas fa-shield-alt fa-2x mb-3"></i>
                        <h4>Secure & Reliable</h4>
                        <p>Your earnings and data are protected with industry-standard security</p>
                    </div>
                    
                    <div class="benefit-item mb-4">
                        <i class="fas fa-users fa-2x mb-3"></i>
                        <h4>Generous Referral Program</h4>
                        <p>Earn up to 10% commission from your referrals' earnings</p>
                    </div>
                    
                    <div class="benefit-item mb-4">
                        <i class="fas fa-bolt fa-2x mb-3"></i>
                        <h4>Instant Withdrawals</h4>
                        <p>Get your earnings quickly with our fast withdrawal system</p>
                    </div>
                    
                    <div class="benefit-item mb-4">
                        <i class="fas fa-globe fa-2x mb-3"></i>
                        <h4>Global Community</h4>
                        <p>Join thousands of users worldwide earning crypto every day</p>
                    </div>
                </div>

                <div class="testimonials">
                    <div class="testimonial-item">
                        <i class="fas fa-quote-left fa-2x mb-3"></i>
                        <p>"<?= Config::get('app.name') ?> has changed my life! I earn crypto every day from the comfort of my home."</p>
                        <small>- John D., Active Member</small>
                    </div>
                </div>
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
.benefit-item {
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    transition: transform 0.3s ease;
}

.benefit-item:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.15);
}

.testimonial-item {
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

.password-strength {
    height: 5px;
    border-radius: 3px;
    margin-top: 5px;
    transition: all 0.3s ease;
}

.strength-weak { background: #dc3545; width: 33%; }
.strength-medium { background: #ffc107; width: 66%; }
.strength-strong { background: #28a745; width: 100%; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const passwordInput = document.getElementById('password');
    const basePath = window.VSItoA_BASE_PATH || '';
    
    // Password strength checker
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = checkPasswordStrength(password);
        updatePasswordStrength(strength);
    });
    
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate passwords match
        if (passwordInput.value !== document.getElementById('confirm_password').value) {
            showAlert('Error', 'Passwords do not match', 'danger');
            return;
        }
        
        const formData = new FormData(registerForm);
        const data = Object.fromEntries(formData);
        
        registerBtn.disabled = true;
        registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
        
        // এখানে সঠিক এপিআই রুট '/api/auth/register' করে দেওয়া হলো
        // Vercel রাউটিং এর জন্য রুট সোজা এবং পরিষ্কার করে দেওয়া হলো
fetch('/register', {
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
                showAlert('Success', data.message, 'success');
                
                // Store token if provided
                if (data.token) {
                    localStorage.setItem('jwt_token', data.token);
                }
                
                // Redirect after delay
                setTimeout(() => {
                    if (data.requires_verification) {
                        window.location.href = basePath + '/login?message=Please check your email to verify your account';
                    } else {
                        window.location.href = basePath + '/dashboard';
                    }
                }, 2000);
            } else {
                if (data.errors) {
                    let errorMessage = '';
                    for (const [field, messages] of Object.entries(data.errors)) {
                        errorMessage += `${field}: ${messages.join(', ')}\n`;
                    }
                    showAlert('Validation Error', errorMessage, 'danger');
                } else {
                    showAlert('Error', data.message, 'danger');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error', 'An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            registerBtn.disabled = false;
            registerBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
        });
    });
    
    // Auto-fill referral code from URL
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');
    if (refCode) {
        document.getElementById('referral_code').value = refCode;
    }
});

function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(fieldId + 'Toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    return strength;
}

function updatePasswordStrength(strength) {
    const passwordInput = document.getElementById('password');
    let strengthDiv = passwordInput.parentNode.parentNode.querySelector('.password-strength');
    
    if (!strengthDiv) {
        strengthDiv = document.createElement('div');
        strengthDiv.className = 'password-strength';
        passwordInput.parentNode.parentNode.appendChild(strengthDiv);
    }
    
    strengthDiv.className = 'password-strength';
    
    if (strength <= 2) {
        strengthDiv.classList.add('strength-weak');
    } else if (strength <= 3) {
        strengthDiv.classList.add('strength-medium');
    } else {
        strengthDiv.classList.add('strength-strong');
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

// Auto-focus username field
document.getElementById('username').focus();
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>