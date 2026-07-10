<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Account Verification - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Apply for Account Verification
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>Why Get Verified?</h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="verified-feature-icon me-2">🛡️</span>
                                    <strong>Increased Account Protection</strong>
                                </div>
                                <small class="text-muted">Advanced security features and monitoring</small>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="verified-feature-icon me-2">💬</span>
                                    <strong>Enhanced Support</strong>
                                </div>
                                <small class="text-muted">Priority customer support</small>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="verified-feature-icon me-2">🔗</span>
                                    <strong>Upgraded Profile Links</strong>
                                </div>
                                <small class="text-muted">Custom profile URLs</small>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="verified-feature-icon me-2">🔍</span>
                                    <strong>Search Optimization</strong>
                                </div>
                                <small class="text-muted">Higher search rankings</small>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="verified-feature-icon me-2">⭐</span>
                                    <strong>Exclusive Stickers</strong>
                                </div>
                                <small class="text-muted">Special verified badges</small>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['flash_error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['flash_info'])): ?>
                        <div class="alert alert-info">
                            <?php echo $_SESSION['flash_info']; unset($_SESSION['flash_info']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= $basePath ?>/verify/submit" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($_SESSION['old_input']['full_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_SESSION['old_input']['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_SESSION['old_input']['phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_document" class="form-label">ID Document *</label>
                                    <input type="file" class="form-control" id="id_document" name="id_document" 
                                           accept="image/*,.pdf" required>
                                    <small class="text-muted">JPG, PNG, GIF, or PDF (Max 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?php echo htmlspecialchars($_SESSION['old_input']['company_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="website_url" class="form-label">Website URL</label>
                                    <input type="url" class="form-control" id="website_url" name="website_url" 
                                           value="<?php echo htmlspecialchars($_SESSION['old_input']['website_url'] ?? ''); ?>"
                                           placeholder="https://example.com">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($_SESSION['old_input']['description'] ?? ''); ?></textarea>
                            <small class="text-muted">Please describe why you want to verify your account and how you plan to use our platform (minimum 50 characters)</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms_accepted" name="terms_accepted" required>
                                <label class="form-check-label" for="terms_accepted">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Verification Terms</a> and confirm that all information provided is accurate *
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?= $basePath ?>/dashboard" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Verification Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Verification Terms & Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Eligibility</h6>
                <p>You must be at least 18 years old and provide accurate, verifiable information.</p>
                
                <h6>2. Documentation</h6>
                <p>All submitted documents must be valid, unexpired, and clearly visible. Falsified documents will result in immediate account suspension.</p>
                
                <h6>3. Verification Process</h6>
                <p>Verification typically takes 3-5 business days. We reserve the right to request additional information.</p>
                
                <h6>4. Verified Status</h6>
                <p>Verified status is subject to our Terms of Service and can be revoked if violated.</p>
                
                <h6>5. Privacy</h6>
                <p>Your personal information is protected according to our Privacy Policy and will only be used for verification purposes.</p>
                
                <h6>6. Benefits</h6>
                <p>Verified users receive enhanced security, priority support, and exclusive features as outlined in our verification benefits.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
unset($_SESSION['old_input']);
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
