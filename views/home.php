<?php
$page_title = 'Home - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = false;
$show_footer = true;

// Get platform statistics with error handling
$stats = [
    'total_users' => 0,
    'total_paid' => 0,
    'active_ads' => 0,
    'total_tasks' => 0
];

try {
    $stats['total_users'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active'") ?? 0;
    $stats['total_paid'] = (int) \Core\Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'paid'") ?? 0;
    $stats['active_ads'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ads WHERE status = 'active'") ?? 0;
    $stats['total_tasks'] = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM tasks WHERE status = 'active'") ?? 0;
} catch (\Exception $e) {
    // Database connection failed, use defaults
    \Core\Logger::warning('Failed to load home page statistics: ' . $e->getMessage());
}

$basePath = Config::get('app.base_path');
ob_start();
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Ready to Start Earning?</h1>
                <p class="lead mb-4">
                    Join <?= Config::get('app.name') ?> and start earning Bitcoin, Ethereum, TRON, and USDT by viewing ads, completing tasks, and referring friends. It's simple, secure, and rewarding!
                </p>
                <div class="d-flex gap-3">
                    <?php if (\Core\Auth::check()): ?>
                        <a href="<?= $basePath ?>/dashboard" class="btn btn-light btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= $basePath ?>/register" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Start Earning Now
                        </a>
                        <a href="<?= $basePath ?>/login" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-3d-wrap">
                    <div class="hero-3d-scene">
                        <div class="hero-card hero-card-left">
                            <div class="hero-card-face hero-card-front">
                                <span>Withdraw</span>
                                <strong>$120</strong>
                            </div>
                            <div class="hero-card-face hero-card-back">Approved</div>
                        </div>
                        <div class="hero-card hero-card-center">
                            <div class="hero-card-face hero-card-front">
                                <span>Withdraw</span>
                                <strong>$340</strong>
                            </div>
                            <div class="hero-card-face hero-card-back">Paid</div>
                        </div>
                        <div class="hero-card hero-card-right">
                            <div class="hero-card-face hero-card-front">
                                <span>Withdraw</span>
                                <strong>$90</strong>
                            </div>
                            <div class="hero-card-face hero-card-back">Completed</div>
                        </div>
                        <div class="hero-people">
                            <div class="hero-person">
                                <div class="hero-avatar"></div>
                                <div class="hero-coin"></div>
                            </div>
                            <div class="hero-person">
                                <div class="hero-avatar"></div>
                                <div class="hero-coin"></div>
                            </div>
                            <div class="hero-person">
                                <div class="hero-avatar"></div>
                                <div class="hero-coin"></div>
                            </div>
                        </div>
                        <div class="hero-glow"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h2 class="fw-bold text-primary"><?= number_format($stats['total_users']) ?>+</h2>
                    <p class="text-muted">Active Users</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h2 class="fw-bold text-success">$<?= number_format($stats['total_paid'], 0) ?>+</h2>
                    <p class="text-muted">Total Paid</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h2 class="fw-bold text-info"><?= number_format($stats['active_ads']) ?>+</h2>
                    <p class="text-muted">Active Ads</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h2 class="fw-bold text-warning"><?= number_format($stats['total_tasks']) ?>+</h2>
                    <p class="text-muted">Daily Tasks</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">How It Works</h2>
            <p class="lead text-muted">Start earning cryptocurrency in 3 simple steps</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4>1. Create Account</h4>
                    <p>Sign up for free in less than 2 minutes. No credit card required.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h4>2. Complete Tasks</h4>
                    <p>View ads, complete surveys, install apps, and more to earn crypto.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h4>3. Get Paid</h4>
                    <p>Withdraw your earnings instantly to your crypto wallet.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Earning Methods Section -->
<section class="earning-methods-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Multiple Ways to Earn</h2>
            <p class="lead text-muted">Choose from various earning methods that suit your preferences</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="earning-method-card">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <i class="fas fa-eye earning-icon text-primary"></i>
                        </div>
                        <div class="col-md-9">
                            <h4>View Advertisements</h4>
                            <p>Earn crypto by watching advertisements from our advertisers. Choose from surf ads, window ads, video ads, and article ads.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Multiple ad types</li>
                                <li><i class="fas fa-check text-success me-2"></i>Instant earnings</li>
                                <li><i class="fas fa-check text-success me-2"></i>No investment required</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="earning-method-card">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <i class="fas fa-clipboard-list earning-icon text-success"></i>
                        </div>
                        <div class="col-md-9">
                            <h4>Complete Tasks & Offers</h4>
                            <p>Participate in surveys, install mobile apps, sign up for websites, and complete various offers to earn rewards.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>High-paying offers</li>
                                <li><i class="fas fa-check text-success me-2"></i>3rd party integrations</li>
                                <li><i class="fas fa-check text-success me-2"></i>Daily new tasks</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="earning-method-card">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <i class="fas fa-users earning-icon text-info"></i>
                        </div>
                        <div class="col-md-9">
                            <h4>Referral Program</h4>
                            <p>Invite friends and earn commissions from their earnings. Build a network and earn passive income.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Up to 10% commission</li>
                                <li><i class="fas fa-check text-success me-2"></i>Multi-level system</li>
                                <li><i class="fas fa-check text-success me-2"></i>Lifetime earnings</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="earning-method-card">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <i class="fas fa-ad earning-icon text-warning"></i>
                        </div>
                        <div class="col-md-9">
                            <h4>Create Advertisements</h4>
                            <p>Promote your own products, services, or websites. Get targeted traffic to your offers.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Targeted advertising</li>
                                <li><i class="fas fa-check text-success me-2"></i>Detailed analytics</li>
                                <li><i class="fas fa-check text-success me-2"></i>Flexible budget</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">What Our Users Say</h2>
            <p class="lead text-muted">Join thousands of satisfied users earning crypto every day</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left text-primary mb-3"></i>
                        <p>"<?= Config::get('app.name') ?> is the best platform I've found for earning crypto. The interface is clean and the payments are always on time."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://picsum.photos/seed/user1/50/50.jpg" alt="User" class="rounded-circle">
                        <div>
                            <h6 class="mb-0">Sarah Johnson</h6>
                            <small class="text-muted">Active Member</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left text-primary mb-3"></i>
                        <p>"I've tried many earning sites, but this one stands out. The referral program is amazing and I earn passive income every month."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://picsum.photos/seed/user2/50/50.jpg" alt="User" class="rounded-circle">
                        <div>
                            <h6 class="mb-0">Michael Chen</h6>
                            <small class="text-muted">Top Earner</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left text-primary mb-3"></i>
                        <p>"As an advertiser, I get great results. The traffic is high quality and the targeting options help me reach the right audience."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://picsum.photos/seed/user3/50/50.jpg" alt="User" class="rounded-circle">
                        <div>
                            <h6 class="mb-0">David Wilson</h6>
                            <small class="text-muted">Advertiser</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Ready to Start Earning?</h2>
        <p class="lead mb-4">Join <?= Config::get('app.name') ?> today and start your cryptocurrency earning journey!</p>
        <?php if (\Core\Auth::check()): ?>
            <a href="/dashboard" class="btn btn-light btn-lg">
                <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
            </a>
        <?php else: ?>
            <a href="/register" class="btn btn-light btn-lg me-3">
                <i class="fas fa-user-plus me-2"></i>Sign Up Free
            </a>
            <a href="/login" class="btn btn-outline-light btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
        <?php endif; ?>
    </div>
</section>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hero-3d-wrap {
    height: 360px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-3d-scene {
    position: relative;
    width: 320px;
    height: 320px;
    perspective: 900px;
    transform-style: preserve-3d;
    animation: scene-tilt 10s ease-in-out infinite;
}

.hero-card {
    position: absolute;
    width: 140px;
    height: 180px;
    transform-style: preserve-3d;
    animation: card-float 6s ease-in-out infinite;
}

.hero-card-left {
    left: 0;
    top: 40px;
    transform: translateZ(40px) rotateY(-18deg);
    animation-delay: 0s;
}

.hero-card-center {
    left: 90px;
    top: 10px;
    transform: translateZ(90px) rotateY(0deg);
    animation-delay: 0.6s;
}

.hero-card-right {
    right: 0;
    top: 50px;
    transform: translateZ(40px) rotateY(18deg);
    animation-delay: 1.2s;
}

.hero-card-face {
    position: absolute;
    inset: 0;
    border-radius: 18px;
    backface-visibility: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    box-shadow: 0 16px 35px rgba(0, 0, 0, 0.25);
}

.hero-card-front {
    background: linear-gradient(160deg, rgba(255, 255, 255, 0.92), rgba(226, 238, 255, 0.95));
    color: #1f2a5a;
}

.hero-card-front span {
    font-size: 0.7rem;
    opacity: 0.7;
}

.hero-card-front strong {
    font-size: 1.4rem;
    margin-top: 0.5rem;
}

.hero-card-back {
    background: linear-gradient(160deg, #14b8a6, #0ea5e9);
    color: #fff;
    transform: rotateY(180deg);
}

.hero-people {
    position: absolute;
    bottom: 20px;
    left: 30px;
    right: 30px;
    display: flex;
    justify-content: space-between;
    transform: translateZ(30px);
}

.hero-person {
    width: 70px;
    height: 90px;
    position: relative;
    animation: person-bounce 3.5s ease-in-out infinite;
}

.hero-person:nth-child(2) {
    animation-delay: 0.4s;
}

.hero-person:nth-child(3) {
    animation-delay: 0.8s;
}

.hero-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    margin: 0 auto;
    background: radial-gradient(circle at 30% 30%, #ffe8c7, #f7b070);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
}

.hero-coin {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    margin: -6px auto 0;
    background: linear-gradient(135deg, #f6d365, #fda085);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
    animation: coin-spin 2.5s linear infinite;
}

.hero-glow {
    position: absolute;
    inset: 60px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0));
    filter: blur(16px);
    transform: translateZ(-20px);
}

@keyframes scene-tilt {
    0%, 100% { transform: rotateX(10deg) rotateY(-8deg); }
    50% { transform: rotateX(-6deg) rotateY(12deg); }
}

@keyframes card-float {
    0%, 100% { transform: translateZ(40px) translateY(0) rotateY(-18deg); }
    50% { transform: translateZ(80px) translateY(-10px) rotateY(8deg); }
}

.hero-card-center {
    animation-name: card-float-center;
}

@keyframes card-float-center {
    0%, 100% { transform: translateZ(90px) translateY(0) rotateY(0deg); }
    50% { transform: translateZ(120px) translateY(-12px) rotateY(-6deg); }
}

.hero-card-right {
    animation-name: card-float-right;
}

@keyframes card-float-right {
    0%, 100% { transform: translateZ(40px) translateY(0) rotateY(18deg); }
    50% { transform: translateZ(70px) translateY(-8px) rotateY(-6deg); }
}

@keyframes person-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

@keyframes coin-spin {
    0% { transform: rotateY(0deg); }
    100% { transform: rotateY(360deg); }
}

.stat-item h2 {
    font-size: 2.5rem;
}

.feature-card {
    padding: 2rem;
    border-radius: 10px;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-10px);
}

.feature-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 2rem;
    color: white;
}

.earning-method-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    height: 100%;
}

.earning-method-card:hover {
    transform: translateY(-5px);
}

.earning-icon {
    font-size: 3rem;
}

.testimonial-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    height: 100%;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
}

.testimonial-author img {
    width: 50px;
    height: 50px;
}

.cta-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

@media (max-width: 575.98px) {
    .hero-3d-wrap {
        height: 260px;
    }
    .hero-3d-scene {
        transform: scale(0.7);
        transform-origin: center center;
    }
    .stat-item h2 {
        font-size: 1.8rem;
    }
    .display-5 {
        font-size: 1.6rem;
    }
}
</style>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
