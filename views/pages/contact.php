<?php
$basePath = Config::get('app.base_path') ?: '';
$page_title = 'Contact Us - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = false;
$show_footer = true;

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">Contact Us</h1>
            <p class="text-muted mb-5">Have a question or need help? Reach out to us through any of the following methods.</p>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                            <h5>Support Ticket</h5>
                            <p class="text-muted">Create a support ticket from your dashboard for the fastest response.</p>
                            <a href="<?= $basePath ?>/login" class="btn btn-primary">
                                <i class="fas fa-ticket-alt me-2"></i>Open Ticket
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-envelope fa-3x text-success mb-3"></i>
                            <h5>Email</h5>
                            <p class="text-muted">For general inquiries and business partnerships.</p>
                            <a href="mailto:support@vsitoa.com" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i>support@vsitoa.com
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5><i class="fas fa-info-circle me-2 text-info"></i>Frequently Asked Questions</h5>
                            <p class="text-muted mb-3">Before contacting us, please check our FAQ page for quick answers to common questions.</p>
                            <a href="<?= $basePath ?>/faq" class="btn btn-outline-primary">
                                <i class="fas fa-question-circle me-2"></i>View FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
