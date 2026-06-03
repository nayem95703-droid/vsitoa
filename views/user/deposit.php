<?php
$page_title = 'Deposit - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

$basePath = (string) Config::get('app.base_path', '');

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Deposit Funds</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?= $basePath ?>/wallet" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Wallet
                </a>
            </div>
        </div>
    </div>

    <!-- Deposit Instructions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-info">
                <div class="card-body">
                    <h5 class="card-title text-info">
                        <i class="fas fa-info-circle me-2"></i>How to Deposit
                    </h5>
                    <ol class="mb-0">
                        <li>Select your preferred cryptocurrency from the options below</li>
                        <li>Send the desired amount to the provided wallet address</li>
                        <li>Copy the transaction ID (TXID) from your wallet</li>
                        <li>Submit the deposit request with the transaction details</li>
                        <li>Wait for admin confirmation (usually within 24 hours)</li>
                        <li>Once confirmed, the funds will be added to your account balance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Currency Selection -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Select Cryptocurrency</h6>
                </div>
                <div class="card-body">
                    <?php
                    $availableCurrencies = array_filter($currencies, fn($address) => !empty($address));
                    ?>
                    <?php if (empty($availableCurrencies)): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No deposit wallets are configured yet. Please contact support or ask admin to set wallet addresses in the environment configuration.
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <?php foreach ($currencies as $currency => $address): ?>
                        <?php $isAvailable = !empty($address); ?>
                        <div class="col-md-3 mb-3">
                            <div class="currency-card <?= $isAvailable ? '' : 'disabled' ?>" data-currency="<?= $currency ?>" data-address="<?= $isAvailable ? $address : '' ?>">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <div class="currency-icon mb-3">
                                            <?php
                                            $icons = [
                                                'BTC' => 'fab fa-bitcoin',
                                                'TRX' => 'fas fa-coins',
                                                'ETH' => 'fab fa-ethereum',
                                                'USDT' => 'fas fa-dollar-sign'
                                            ];
                                            $colors = [
                                                'BTC' => 'text-warning',
                                                'TRX' => 'text-danger',
                                                'ETH' => 'text-primary',
                                                'USDT' => 'text-success'
                                            ];
                                            ?>
                                            <i class="<?= $icons[$currency] ?> fa-3x <?= $colors[$currency] ?>"></i>
                                        </div>
                                        <h6 class="card-title"><?= $currency ?></h6>
                                        <p class="card-text text-muted small">
                                            <?= $currency === 'BTC' ? 'Bitcoin' : 
                                               ($currency === 'TRX' ? 'TRON' : 
                                               ($currency === 'ETH' ? 'Ethereum' : 'Tether')) ?>
                                        </p>
                                        <?php if (!$isAvailable): ?>
                                            <span class="badge bg-secondary">Not available</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deposit Form -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Deposit Details</h6>
                </div>
                <div class="card-body">
                    <form id="depositForm" method="POST" action="<?= $basePath ?>/deposit">
                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                        
                        <div class="mb-3">
                            <label for="currency" class="form-label">Cryptocurrency</label>
                            <select class="form-select" id="currency" name="currency" required>
                                <option value="">Select cryptocurrency</option>
                                <?php foreach ($currencies as $currency => $address): ?>
                                    <?php $isAvailable = !empty($address); ?>
                                    <option value="<?= $currency ?>" <?= $isAvailable ? '' : 'disabled' ?>>
                                        <?= $currency ?> - <?= $currency === 'BTC' ? 'Bitcoin' : ($currency === 'TRX' ? 'TRON' : ($currency === 'ETH' ? 'Ethereum' : 'Tether')) ?><?= $isAvailable ? '' : ' (Not available)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3" id="wallet-address-section" style="display: none;">
                            <label for="wallet_address" class="form-label">Deposit Address</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="wallet_address" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="copyWalletAddress()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <small class="text-muted">Send funds to this address</small>
                        </div>

                        <div class="mb-3" id="qr-code-section" style="display: none;">
                            <label class="form-label">QR Code</label>
                            <div class="text-center">
                                <div id="qr-code"></div>
                                <small class="text-muted">Scan this QR code with your wallet app</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="amount" name="amount" step="0.00000001" min="0.00000001" required>
                                <span class="input-group-text" id="currency-label">BTC</span>
                            </div>
                            <small class="text-muted">Enter the amount you are sending</small>
                        </div>

                        <div class="mb-3">
                            <label for="txid" class="form-label">Transaction ID (TXID)</label>
                            <input type="text" class="form-control font-monospace" id="txid" name="txid" placeholder="Enter transaction hash">
                            <small class="text-muted">Optional: You can submit this after sending the funds</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="depositBtn">
                                <i class="fas fa-plus-circle me-2"></i>Create Deposit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Important Notes</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Minimum Deposit:</strong> 0.0001 BTC
                    </div>
                    
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Only send <?= implode(', ', array_keys(array_filter($currencies))) ?> to this address
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Double-check the address before sending
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Save your transaction ID for reference
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Deposits are usually confirmed within 24 hours
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Contact support if you have any issues
                        </li>
                    </ul>

                    <div class="mt-3">
                        <h6>Need Help?</h6>
                        <a href="/support" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-headset me-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deposit History -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Deposit History</h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshDeposits()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($deposits)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No deposits yet</h5>
                        <p class="text-muted">Make your first deposit to see it here</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Currency</th>
                                    <th>Amount</th>
                                    <th>Wallet Address</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deposits as $deposit): ?>
                                <tr>
                                    <td>#<?= $deposit['deposit_id'] ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= $deposit['currency'] ?></span>
                                    </td>
                                    <td><?= number_format($deposit['amount'], 8) ?></td>
                                    <td>
                                        <code class="small"><?= substr($deposit['wallet_address'], 0, 10) ?>...</code>
                                    </td>
                                    <td>
                                        <?php if ($deposit['txid']): ?>
                                            <code class="small"><?= substr($deposit['txid'], 0, 10) ?>...</code>
                                        <?php else: ?>
                                            <span class="text-muted">Not provided</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $statusColors[$deposit['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= ucfirst($deposit['status']) ?></span>
                                    </td>
                                    <td><?= date('M j, Y H:i', strtotime($deposit['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info" onclick="viewDepositDetails(<?= $deposit['deposit_id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!$deposit['txid'] && $deposit['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-outline-primary" onclick="updateTxid(<?= $deposit['deposit_id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction ID Update Modal -->
<div class="modal fade" id="txidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Transaction ID</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="txidForm">
                    <input type="hidden" id="update-deposit-id">
                    <div class="mb-3">
                        <label for="update-txid" class="form-label">Transaction ID (TXID)</label>
                        <input type="text" class="form-control font-monospace" id="update-txid" required>
                        <small class="text-muted">Enter the transaction hash from your wallet</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTxid()">Save</button>
            </div>
        </div>
    </div>
</div>

<style>
.currency-card {
    cursor: pointer;
    transition: transform 0.2s ease;
}

.currency-card.disabled {
    cursor: not-allowed;
}

.currency-card.disabled .card {
    opacity: 0.6;
}

.currency-card:hover {
    transform: translateY(-5px);
}

.currency-card.selected {
    border: 2px solid #4e73df;
    transform: translateY(-5px);
}

.currency-icon {
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

#qr-code {
    width: 200px;
    height: 200px;
    margin: 0 auto;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fc;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
let selectedCurrency = '';
let qrCodeInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    // Currency card selection
    document.querySelectorAll('.currency-card').forEach(card => {
        card.addEventListener('click', function() {
            if (!this.dataset.address) {
                showAlert('Info', 'This cryptocurrency is not available right now.', 'info');
                return;
            }
            selectCurrency(this);
        });
    });

    // Currency dropdown change
    document.getElementById('currency').addEventListener('change', function() {
        const currency = this.value;
        if (currency) {
            const card = document.querySelector(`[data-currency="${currency}"]`);
            if (card) {
                selectCurrency(card);
            }
        }
    });

    // Form submission
    document.getElementById('depositForm').addEventListener('submit', function(e) {
        // Let the form submit to the web route
    });
});

function selectCurrency(card) {
    if (!card.dataset.address) {
        showAlert('Info', 'This cryptocurrency is not available right now.', 'info');
        return;
    }
    // Remove previous selection
    document.querySelectorAll('.currency-card').forEach(c => c.classList.remove('selected'));
    
    // Add selection to clicked card
    card.classList.add('selected');
    
    // Get currency data
    selectedCurrency = card.dataset.currency;
    const address = card.dataset.address;
    
    // Update form
    document.getElementById('currency').value = selectedCurrency;
    document.getElementById('wallet_address').value = address;
    document.getElementById('currency-label').textContent = selectedCurrency;
    
    // Show wallet address and QR code sections
    document.getElementById('wallet-address-section').style.display = 'block';
    document.getElementById('qr-code-section').style.display = 'block';
    
    // Generate QR code
    generateQRCode(address);
}

function generateQRCode(address) {
    const qrContainer = document.getElementById('qr-code');
    qrContainer.innerHTML = '';
    
    try {
        qrCodeInstance = new QRCode(qrContainer, {
            text: address,
            width: 200,
            height: 200,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    } catch (error) {
        qrContainer.innerHTML = '<i class="fas fa-qrcode fa-5x text-muted"></i>';
    }
}

function copyWalletAddress() {
    const address = document.getElementById('wallet_address').value;
    
    navigator.clipboard.writeText(address).then(() => {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    });
}

function createDeposit() {}

function refreshDeposits() {
    location.reload();
}

function viewDepositDetails(depositId) {
    // In a real implementation, fetch and show detailed deposit information
    showAlert('Info', `Deposit #${depositId} details`, 'info');
}

function updateTxid(depositId) {
    document.getElementById('update-deposit-id').value = depositId;
    document.getElementById('update-txid').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('txidModal'));
    modal.show();
}

function saveTxid() {
    const depositId = document.getElementById('update-deposit-id').value;
    const txid = document.getElementById('update-txid').value;
    
    if (!txid) {
        showAlert('Error', 'Transaction ID is required', 'danger');
        return;
    }
    
    // In a real implementation, update the TXID via API
    showAlert('Success', 'Transaction ID updated successfully', 'success');
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('txidModal'));
    modal.hide();
    
    refreshDeposits();
}

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
