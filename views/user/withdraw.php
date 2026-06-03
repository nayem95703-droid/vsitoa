<?php
$page_title = 'Withdraw - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Withdraw Funds</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="/wallet" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Wallet
                </a>
            </div>
        </div>
    </div>

    <!-- Withdraw Instructions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Withdrawal Information
                    </h5>
                    <ol class="mb-0">
                        <li>Enter your withdrawal wallet address</li>
                        <li>Select the cryptocurrency you want to withdraw</li>
                        <li>Enter the amount you wish to withdraw</li>
                        <li>Minimum withdrawal amount: 0.0001 BTC</li>
                        <li>Withdrawal requests are processed within 24-48 hours</li>
                        <li>Network fees will be deducted from the withdrawal amount</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Balance -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow border-left-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Available Balance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($user['earning_balance'] ?? 0, 2) ?> USDT
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow border-left-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Withdrawn
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($user['total_withdrawn'] ?? 0, 2) ?> USDT
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdrawal Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Request Withdrawal</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="/withdraw">
                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="currency" class="form-label">Cryptocurrency</label>
                                <select class="form-select" id="currency" name="currency" required>
                                    <option value="BTC">Bitcoin (BTC)</option>
                                    <option value="ETH">Ethereum (ETH)</option>
                                    <option value="LTC">Litecoin (LTC)</option>
                                    <option value="DOGE">Dogecoin (DOGE)</option>
                                    <option value="USDT">Tether (USDT)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="wallet_address" class="form-label">Wallet Address</label>
                                <input type="text" class="form-control" id="wallet_address" name="wallet_address" 
                                       placeholder="Enter your wallet address" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Amount (USDT)</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="<?= $minimumWithdrawal ?>" max="<?= $user['earning_balance'] ?? 0 ?>" 
                                       placeholder="0.00" required>
                                <small class="form-text text-muted">Minimum: <?= number_format($minimumWithdrawal, 2) ?> USDT</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_amount" class="form-label">Confirm Amount</label>
                                <input type="number" class="form-control" id="confirm_amount" name="confirm_amount" 
                                       step="0.01" min="<?= $minimumWithdrawal ?>" max="<?= $user['earning_balance'] ?? 0 ?>" 
                                       placeholder="Re-enter amount" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any additional information..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Important:</strong> Please double-check your wallet address before submitting. 
                            Withdrawal requests cannot be cancelled once submitted.
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Withdrawal Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Withdrawals -->
    <?php if (!empty($pendingWithdrawals)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow border-left-warning">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Pending Withdrawals</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Currency</th>
                                    <th>Amount</th>
                                    <th>Wallet Address</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingWithdrawals as $withdrawal): ?>
                                <tr>
                                    <td><?= $withdrawal['withdrawal_id'] ?></td>
                                    <td><?= $withdrawal['currency'] ?></td>
                                    <td><?= number_format($withdrawal['amount'], 8) ?></td>
                                    <td><?= substr($withdrawal['wallet_address'], 0, 20) ?>...</td>
                                    <td>
                                        <?php if ($withdrawal['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Processing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y H:i', strtotime($withdrawal['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

include ROOT_PATH . '/views/layouts/main.php';
?>
