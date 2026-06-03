<?php
$page_title = 'Create Advertisement - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$basePath = (string) Config::get('app.base_path', '');
$defaultCostPerView = (float) Config::get('rates.cost_per_view');

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Create Advertisement</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?= $basePath ?>/advisor/manage-ads" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to My Ads
                </a>
            </div>
        </div>
    </div>

    <!-- Current Balance Alert -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Advisor Balance:</strong> <?= number_format($user['advisor_balance'] ?? 0, 2) ?> USDT
                <br>
                <small class="text-muted">Your advisor balance will be deducted when creating the advertisement (including platform fees).</small>
            </div>
        </div>
    </div>

    <!-- Create Ad Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Advertisement Details</h6>
                </div>
                <div class="card-body">
                    <form id="createAdForm" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                        
                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h6 class="text-secondary mb-3">Basic Information</h6>
                            
                            <div class="mb-3">
                                <label for="ad_title" class="form-label">Ad Title *</label>
                                <input type="text" class="form-control" id="ad_title" name="ad_title" required maxlength="255">
                                <small class="text-muted">A catchy title for your advertisement</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ad_category" class="form-label">Category *</label>
                                    <select class="form-select" id="ad_category" name="ad_category" required>
                                        <option value="">Select category</option>
                                        <?php foreach ($adCategories as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ad_type" class="form-label">Ad Type *</label>
                                    <select class="form-select" id="ad_type" name="ad_type" required>
                                        <option value="">Select type</option>
                                        <?php foreach ($adTypes as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="target_url" class="form-label">Target URL *</label>
                                <input type="url" class="form-control" id="target_url" name="target_url" required placeholder="https://example.com">
                                <small class="text-muted">The URL users will be directed to</small>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000"></textarea>
                                <small class="text-muted">Brief description of your product/service (optional)</small>
                            </div>
                        </div>

                        <!-- View Settings -->
                        <div class="mb-4">
                            <h6 class="text-secondary mb-3">View Settings</h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="view_time" class="form-label">Required View Time *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="view_time" name="view_time" required min="5" max="300" value="10">
                                        <span class="input-group-text">seconds</span>
                                    </div>
                                    <small class="text-muted">How long users must view your ad</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="timer_type" class="form-label">Timer Type *</label>
                                    <select class="form-select" id="timer_type" name="timer_type" required>
                                        <option value="countdown">Countdown</option>
                                        <option value="progress">Progress Bar</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="watch_time_tariff" class="form-label">Watch Time</label>
                                    <select class="form-select" id="watch_time_tariff">
                                        <option value="">Custom</option>
                                    </select>
                                    <small class="text-muted">Preset watch time + cost per view</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="traffic_source" class="form-label">Traffic Source</label>
                                    <select class="form-select" id="traffic_source" name="traffic_source">
                                        <option value="direct">Direct navigation (address bar)</option>
                                        <option value="social">Social</option>
                                        <option value="search">Search</option>
                                        <option value="referral">Referral</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <small class="text-muted">Optional</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="execution_speed" class="form-label">Execution Speed</label>
                                    <select class="form-select" id="execution_speed">
                                        <option value="5000">Standard - up to 5000 views/day</option>
                                        <option value="6000">Medium (VIP) - up to 6000 views/day</option>
                                        <option value="8000">High (Premium) - up to 8000 views/day</option>
                                    </select>
                                    <small class="text-muted">Estimated delivery speed</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="auto_redirect" name="auto_redirect">
                                    <label class="form-check-label" for="auto_redirect">
                                        Auto-redirect after timer completes
                                    </label>
                                </div>
                                <small class="text-muted">Automatically redirect users to your site when timer ends</small>
                            </div>
                        </div>

                        <!-- Budget Settings -->
                        <div class="mb-4">
                            <h6 class="text-secondary mb-3">Budget Settings</h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cost_per_view" class="form-label">Cost Per View *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="cost_per_view" name="cost_per_view" required min="<?= htmlspecialchars((string) $defaultCostPerView) ?>" step="<?= htmlspecialchars((string) $defaultCostPerView) ?>" value="<?= htmlspecialchars((string) $defaultCostPerView) ?>">
                                        <span class="input-group-text">USDT</span>
                                    </div>
                                    <small class="text-muted">Amount you'll pay for each valid view</small>
                                </div>
                                <input type="hidden" id="total_views" name="total_views" value="0">
                            </div>
                        </div>

                        <!-- Targeting Options -->
                        <div class="mb-4">
                            <h6 class="text-secondary mb-3">Targeting Options</h6>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="device_type" class="form-label">Device Type</label>
                                    <select class="form-select" id="device_type" name="device_type" required>
                                        <option value="all">All Devices</option>
                                        <option value="mobile">Mobile Only</option>
                                        <option value="desktop">Desktop Only</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="browser" class="form-label">Browser</label>
                                    <select class="form-select" id="browser" name="browser" required>
                                        <option value="all">All Browsers</option>
                                        <option value="chrome">Chrome</option>
                                        <option value="firefox">Firefox</option>
                                        <option value="safari">Safari</option>
                                        <option value="edge">Edge</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="user_level" class="form-label">User Level</label>
                                    <select class="form-select" id="user_level" name="user_level" required>
                                        <option value="all">All Users</option>
                                        <option value="normal">Normal Users</option>
                                        <option value="premium">Premium Users</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target Countries (Optional)</label>
                                <small class="text-muted">Leave empty to target all countries</small>
                                <div id="country-select">
                                    <select class="form-select" id="target_countries" name="target_countries[]" multiple>
                                        <!-- Countries will be populated by JavaScript -->
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple countries</small>
                                </div>
                            </div>
                        </div>

                        <!-- Ad Preview -->
                        <div class="mb-4">
                            <h6 class="text-secondary mb-3">Ad Preview</h6>
                            
                            <div class="mb-3">
                                <label for="preview_image" class="form-label">Preview Image</label>
                                <input type="file" class="form-control" id="preview_image" name="preview_image" accept="image/*">
                                <small class="text-muted">Optional: Upload an image to preview your ad (JPG, PNG, GIF, max 5MB)</small>
                            </div>

                            <div id="image-preview" class="text-center" style="display: none;">
                                <img id="preview-img" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                        </div>

                        <!-- Terms and Submit -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="<?= $basePath ?>/terms" target="_blank">Terms and Conditions</a> and understand that my ad will be reviewed by admin before going live.
                                </label>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="createAdBtn">
                                <i class="fas fa-plus-circle me-2"></i>Create Advertisement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Guidelines Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle me-2"></i>Ad Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="text-success">✅ What's Allowed</h6>
                    <ul class="list-unstyled small mb-3">
                        <li><i class="fas fa-check text-success me-2"></i>Legitimate businesses</li>
                        <li><i class="fas fa-check text-success me-2"></i>E-commerce websites</li>
                        <li><i class="fas fa-check text-success me-2"></i>Mobile apps and games</li>
                        <li><i class="fas fa-check text-success me-2"></i>Blogs and content sites</li>
                        <li><i class="fas fa-check text-success me-2"></i>Crypto and finance sites</li>
                    </ul>

                    <h6 class="text-danger">❌ What's Not Allowed</h6>
                    <ul class="list-unstyled small mb-3">
                        <li><i class="fas fa-times text-danger me-2"></i>Illegal activities</li>
                        <li><i class="fas fa-times text-danger me-2"></i>Scams or fraud</li>
                        <li><i class="fas fa-times text-danger me-2"></i>Pornography</li>
                        <li><i class="fas fa-times text-danger me-2"></i>Hate speech</li>
                        <li><i class="fas fa-times text-danger me-2"></i>Malware/viruses</li>
                    </ul>

                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> All ads are manually reviewed. Violating ads will be rejected and may result in account suspension.
                    </div>
                </div>
            </div>

            <!-- Pricing Info -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calculator me-2"></i>Pricing Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Current Rates</h6>
                        <ul class="list-unstyled">
                            <li><strong>Minimum Cost Per View:</strong> <?= htmlspecialchars((string) $defaultCostPerView) ?> USDT</li>
                            <li><strong>Platform Fee:</strong> 20% of total budget</li>
                            <li><strong>Minimum Views:</strong> 100</li>
                            <li><strong>Maximum Views:</strong> 1,000,000</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <h6>Example Calculation</h6>
                        <div class="border p-3 rounded">
                            <small>
                                <?php
                                $exampleViews = 1000;
                                $exampleCostPerView = $defaultCostPerView;
                                $exampleAdCost = $exampleViews * $exampleCostPerView;
                                $examplePlatformFee = $exampleAdCost * 0.2;
                                $exampleTotal = $exampleAdCost + $examplePlatformFee;
                                ?>
                                <?= number_format($exampleViews) ?> views @ <?= htmlspecialchars((string) $exampleCostPerView) ?> USDT each<br>
                                Ad Cost: <?= number_format($exampleAdCost, 8) ?> USDT<br>
                                Platform Fee (20%): <?= number_format($examplePlatformFee, 8) ?> USDT<br>
                                <strong>Total: <?= number_format($exampleTotal, 8) ?> USDT</strong>
                            </small>
                        </div>
                    </div>

                    <div class="text-muted small">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Tip:</strong> Higher cost per view may attract more viewers and get your ad seen faster!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#ad-cost, #platform-fee, #total-budget, #remaining-balance {
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

#image-preview {
    margin-top: 15px;
}

#preview-img {
    border: 2px dashed #ddd;
    padding: 10px;
}
</style>

<script>
// Load countries for selection
const countries = [
    'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Italy', 'Spain',
    'Netherlands', 'Sweden', 'Norway', 'Denmark', 'Finland', 'Poland', 'Russia', 'China', 'Japan',
    'South Korea', 'India', 'Brazil', 'Argentina', 'Mexico', 'South Africa', 'Egypt', 'Nigeria',
    'Kenya', 'Turkey', 'Saudi Arabia', 'UAE', 'Israel', 'Thailand', 'Vietnam', 'Philippines',
    'Malaysia', 'Singapore', 'Indonesia', 'New Zealand', 'Ireland', 'Belgium', 'Switzerland',
    'Austria', 'Portugal', 'Greece', 'Czech Republic', 'Hungary', 'Romania', 'Bulgaria',
    'Croatia', 'Slovakia', 'Lithuania', 'Latvia', 'Estonia', 'Ukraine', 'Belarus', 'Moldova',
    'Serbia', 'Montenegro', 'Albania', 'Macedonia', 'Bosnia', 'Slovenia', 'Cyprus', 'Malta',
    'Luxembourg', 'Monaco', 'Liechtenstein', 'Iceland', 'Greenland', 'Faroe Islands'
];

document.addEventListener('DOMContentLoaded', function() {
    // Category -> Ad Type mapping
    const categorySelect = document.getElementById('ad_category');
    const adTypeSelect = document.getElementById('ad_type');

    const allAdTypeOptions = Array.from(adTypeSelect.querySelectorAll('option'))
        .filter(opt => opt.value !== '')
        .map(opt => ({ value: opt.value, label: opt.textContent }));

    const allowedTypesByCategory = {
        website: ['surf', 'window'],
        app: ['window', 'video'],
        video: ['video'],
        article: ['article']
    };

    function syncAdTypes() {
        const category = categorySelect ? categorySelect.value : '';
        const allowed = allowedTypesByCategory[category] || allAdTypeOptions.map(o => o.value);
        const currentValue = adTypeSelect.value;

        adTypeSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select type';
        adTypeSelect.appendChild(placeholder);

        allAdTypeOptions
            .filter(o => allowed.includes(o.value))
            .forEach(o => {
                const option = document.createElement('option');
                option.value = o.value;
                option.textContent = o.label;
                adTypeSelect.appendChild(option);
            });

        if (allowed.includes(currentValue)) {
            adTypeSelect.value = currentValue;
        }
    }

    if (categorySelect && adTypeSelect) {
        categorySelect.addEventListener('change', syncAdTypes);
        syncAdTypes();
    }

    // Populate country select
    const countrySelect = document.getElementById('target_countries');
    countries.forEach(country => {
        const option = document.createElement('option');
        option.value = country;
        option.textContent = country;
        countrySelect.appendChild(option);
    });

    // Budget calculation
    const costPerView = document.getElementById('cost_per_view');
    const totalViews = document.getElementById('total_views');
    const userBalance = <?= $user['advisor_balance'] ?? 0 ?>;
    const viewTime = document.getElementById('view_time');
    const watchTimeTariff = document.getElementById('watch_time_tariff');
    const executionSpeed = document.getElementById('execution_speed');

    const defaultCostPerView = <?= json_encode((string) $defaultCostPerView) ?>;

    const watchTimeOptions = [
        { seconds: 10, cost: null },
        { seconds: 20, cost: null },
        { seconds: 25, cost: null },
        { seconds: 30, cost: null },
        { seconds: 35, cost: null },
        { seconds: 40, cost: null },
        { seconds: 45, cost: null },
        { seconds: 50, cost: null },
        { seconds: 55, cost: null },
        { seconds: 60, cost: null }
    ];

    function toNumber(value, fallback = 0) {
        const n = parseFloat(value);
        return Number.isFinite(n) ? n : fallback;
    }

    function buildWatchTimeTariffOptions() {
        const base = toNumber(defaultCostPerView, 0.001);
        const multipliers = {
            10: 1,
            20: 1.2,
            25: 1.3,
            30: 1.5,
            35: 1.7,
            40: 2,
            45: 2.2,
            50: 2.4,
            55: 2.7,
            60: 3
        };

        watchTimeTariff.innerHTML = '<option value="">Custom</option>';
        watchTimeOptions.forEach(opt => {
            const multiplier = multipliers[opt.seconds] ?? 1;
            const cost = base * multiplier;
            const option = document.createElement('option');
            option.value = `${opt.seconds}|${cost}`;
            option.textContent = `${opt.seconds}s ${cost.toFixed(6)} USDT per 1 view`;
            watchTimeTariff.appendChild(option);
        });
    }

    function updateScheduleSummary(totalBudget) {
        const cpv = toNumber(costPerView.value, 0);
        const views = parseInt(totalViews.value, 10) || 0;
        const speed = parseInt(executionSpeed.value, 10) || 0;

        const dailyLimitEl = document.getElementById('daily-limit');
        const daysEl = document.getElementById('estimated-days');
        const spendPerDayEl = document.getElementById('spend-per-day');

        if (!dailyLimitEl || !daysEl || !spendPerDayEl) {
            return;
        }

        if (!speed || views <= 0 || cpv <= 0) {
            dailyLimitEl.textContent = '—';
            daysEl.textContent = '—';
            spendPerDayEl.textContent = '—';
            return;
        }

        const days = Math.max(1, Math.ceil(views / speed));
        const viewsPerDay = Math.min(speed, views);
        const platformFeePercent = 20;
        const spendPerDay = (cpv * viewsPerDay) * (1 + platformFeePercent / 100);

        dailyLimitEl.textContent = `${speed.toLocaleString()} views/day`;
        daysEl.textContent = `${days} day${days > 1 ? 's' : ''}`;
        spendPerDayEl.textContent = formatUSDT(spendPerDay);
    }

    function calculateBudget() {
        const cpv = parseFloat(costPerView.value) || 0;
        const views = parseInt(totalViews.value) || 0;
        const platformFeePercent = 20;
        
        const adCost = cpv * views;
        const platformFee = adCost * (platformFeePercent / 100);
        const totalBudget = adCost + platformFee;
        const remainingBalance = userBalance - totalBudget;

        const adCostEl = document.getElementById('ad-cost');
        const platformFeeEl = document.getElementById('platform-fee');
        const totalBudgetEl = document.getElementById('total-budget');
        const remainingEl = document.getElementById('remaining-balance');

        if (adCostEl) adCostEl.textContent = formatUSDT(adCost);
        if (platformFeeEl) platformFeeEl.textContent = formatUSDT(platformFee);
        if (totalBudgetEl) totalBudgetEl.textContent = formatUSDT(totalBudget);
        if (remainingEl) {
            remainingEl.textContent = formatUSDT(remainingBalance);
            if (remainingBalance < 0) {
                remainingEl.className = 'text-danger';
            } else if (remainingBalance < totalBudget * 0.1) {
                remainingEl.className = 'text-warning';
            } else {
                remainingEl.className = 'text-info';
            }
        }

        updateScheduleSummary(totalBudget);
    }

    function updateAutoTotalViews() {
        const cpv = parseFloat(costPerView.value) || 0;
        if (cpv <= 0) {
            totalViews.value = '0';
            return;
        }

        const maxViews = Math.floor(userBalance / cpv);
        totalViews.value = String(Math.max(0, Math.min(1000000, maxViews)));
    }

    function formatUSDT(amount) {
        return amount.toFixed(2) + ' USDT';
    }

    costPerView.addEventListener('input', function() {
        updateAutoTotalViews();
        calculateBudget();
    });

    buildWatchTimeTariffOptions();
    watchTimeTariff.addEventListener('change', function() {
        const val = this.value;
        if (!val) {
            return;
        }
        const [secondsStr, costStr] = val.split('|');
        const seconds = parseInt(secondsStr, 10);
        const cost = parseFloat(costStr);

        if (Number.isFinite(seconds)) {
            viewTime.value = String(seconds);
        }
        if (Number.isFinite(cost)) {
            costPerView.value = String(cost);
        }

        updateAutoTotalViews();
        calculateBudget();
    });

    executionSpeed.addEventListener('change', calculateBudget);
    viewTime.addEventListener('input', function() {
        if (watchTimeTariff.value) {
            watchTimeTariff.value = '';
        }
    });

    calculateBudget();

    // Image preview
    const previewImage = document.getElementById('preview_image');
    const imagePreview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');

    previewImage.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.style.display = 'none';
        }
    });

    // Form submission
    document.getElementById('createAdForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        // Convert checkboxes to boolean
        data.auto_redirect = formData.has('auto_redirect');
        
        // Handle multiple select
        data.target_countries = formData.getAll('target_countries[]');
        
        const btn = document.getElementById('createAdBtn');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
        
        fetch((window.VSItoA_BASE_PATH || '') + '/api/advisor/ads', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Success', data.message, 'success');
                setTimeout(() => {
                    window.location.href = (window.VSItoA_BASE_PATH || '') + '/advisor/manage-ads';
                }, 2000);
            } else {
                if (data.errors) {
                    showValidationErrors(data.errors);
                } else {
                    showAlert('Error', data.message, 'danger');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error', 'Failed to create advertisement', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
});

function showAlert(title, message, type) {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show slide-in-up`;
    alertElement.innerHTML = `
        <strong>${title}:</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alertElement);
    
    setTimeout(() => {
        if (alertElement.parentNode) {
            alertElement.remove();
        }
    }, 5000);
}

function showValidationErrors(errors) {
    let errorMessage = '';
    for (const [field, messages] of Object.entries(errors)) {
        errorMessage += `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}\n`;
    }
    showAlert('Validation Error', errorMessage, 'danger');
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
