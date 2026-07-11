<?php
$page_title = 'Wallet - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = true;
$show_footer = true;

// Get user data with safe defaults
$userId = \Core\Auth::id();

if (!$userId) {
    (new \Core\Response())->redirect('/login');
    exit;
}

ob_start();
$basePath = Config::get('app.base_path');
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Wallet</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?= $basePath ?>/deposit" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Deposit
                </a>
                <a href="<?= $basePath ?>/withdraw" class="btn btn-danger">
                    <i class="fas fa-minus me-2"></i>Withdraw
                </a>
            </div>
        </div>
    </div>

    <!-- Balance Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Earning Balance (USDT)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 earnings-pulse" data-user="earning">
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

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Advisor Balance (USDT)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" data-user="advisor">
                                <?= number_format($user['advisor_balance'] ?? 0, 2) ?> USDT
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Withdrawn (USDT)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($user['total_withdrawn'] ?? 0, 2) ?> USDT
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Transfer Balance</h6>
                    <span class="badge bg-warning text-dark">Only Earning → Advisor</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $basePath ?>/wallet/transfer-advisor" class="row g-3 align-items-end">
                        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
                        <div class="col-md-4">
                            <label class="form-label">From</label>
                            <input type="text" class="form-control" value="Earning Balance" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">To</label>
                            <input type="text" class="form-control" value="Advisor Balance" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Amount (USDT)</label>
                            <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-exchange-alt me-2"></i>Transfer
                            </button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">You can use your earning balance to run ads. Advisor balance cannot be transferred back.</small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <a href="<?= $basePath ?>/deposit" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                Deposit Funds
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= $basePath ?>/withdraw" class="btn btn-danger btn-lg w-100">
                                <i class="fas fa-minus-circle fa-2x mb-2"></i><br>
                                Withdraw Funds
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= $basePath ?>/wallet#transactions" class="btn btn-info btn-lg w-100">
                                <i class="fas fa-history fa-2x mb-2"></i><br>
                                Transaction History
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= $basePath ?>/referral" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-users fa-2x mb-2"></i><br>
                                Referral Earnings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <?php if (!empty($pendingDeposits) || !empty($pendingWithdrawals)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Pending Requests</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($pendingDeposits)): ?>
                    <h6 class="text-success">Pending Deposits</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Currency</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingDeposits as $deposit): ?>
                                <tr>
                                    <td>#<?= $deposit['deposit_id'] ?></td>
                                    <td><?= $deposit['currency'] ?></td>
                                    <td><?= number_format($deposit['amount'], 8) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($deposit['created_at'])) ?></td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($pendingWithdrawals)): ?>
                    <h6 class="text-danger">Pending Withdrawals</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Currency</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingWithdrawals as $withdrawal): ?>
                                <tr>
                                    <td>#<?= $withdrawal['withdrawal_id'] ?></td>
                                    <td><?= $withdrawal['currency'] ?></td>
                                    <td><?= number_format($withdrawal['amount'], 8) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($withdrawal['created_at'])) ?></td>
                                    <td><span class="badge bg-warning"><?= ucfirst($withdrawal['status']) ?></span></td>
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
    <?php endif; ?>

    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="refreshTransactions()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="loadMoreTransactions()">
                            <i class="fas fa-plus"></i> Load More
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No transactions yet</h5>
                        <p class="text-muted">Start earning to see your transaction history</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="transactions-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $typeIcons = [
                                            'earn' => 'fa-plus-circle text-success',
                                            'deposit' => 'fa-plus-circle text-info',
                                            'withdraw' => 'fa-minus-circle text-danger',
                                            'ad_spend' => 'fa-minus-circle text-warning',
                                            'referral' => 'fa-gift text-primary',
                                            'bonus' => 'fa-star text-warning'
                                        ];
                                        $icon = $typeIcons[$transaction['type']] ?? 'fa-exchange-alt text-secondary';
                                        ?>
                                        <i class="fas <?= $icon ?> me-2"></i>
                                        <?= ucfirst($transaction['type']) ?>
                                    </td>
                                    <td class="<?= $transaction['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $transaction['amount'] >= 0 ? '+' : '' ?><?= number_format($transaction['amount'], 8) ?> USDT
                                    </td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><?= date('M j, Y H:i:s', strtotime($transaction['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-success">Completed</span>
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

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transaction-details">
                <!-- Transaction details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.transaction-row {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.transaction-row:hover {
    background-color: #f8f9fc;
}

.earnings-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.02);
    }
    100% {
        transform: scale(1);
    }
}
</style>

<script>
let currentPage = 1;
let isLoading = false;
const basePath = <?= json_encode((string) Config::get('app.base_path', '')) ?>;
const apiBase = (basePath ? basePath : '') + '/api';
const token = localStorage.getItem('jwt_token');

// Auto-refresh balance
if (token) {
    setInterval(() => {
        updateBalance();
    }, 30000);
}

function updateBalance() {
    if (!token) {
        return;
    }
    fetch(`${apiBase}/wallet/balance`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const earningElement = document.querySelector('[data-user="earning"]');
            const advisorElement = document.querySelector('[data-user="advisor"]');
            const newEarning = parseFloat(data.data.earning_balance || 0);
            const newAdvisor = parseFloat(data.data.advisor_balance || 0);

            if (earningElement) {
                const currentEarning = parseFloat(earningElement.textContent);
                if (newEarning > currentEarning) {
                    earningElement.classList.add('earnings-pulse');
                    setTimeout(() => {
                        earningElement.classList.remove('earnings-pulse');
                    }, 2000);
                }
                earningElement.textContent = newEarning.toFixed(2) + ' USDT';
            }

            if (advisorElement) {
                advisorElement.textContent = newAdvisor.toFixed(2) + ' USDT';
            }
        }
    })
    .catch(error => {
        console.error('Error updating balance:', error);
    });
}

function refreshTransactions() {
    if (isLoading) return;
    
    isLoading = true;
    currentPage = 1;
    
    if (!token) {
        return;
    }
    fetch(`${apiBase}/wallet/transactions?page=1`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTransactionsTable(data.data);
        }
    })
    .catch(error => {
        console.error('Error refreshing transactions:', error);
    })
    .finally(() => {
        isLoading = false;
    });
}

function loadMoreTransactions() {
    if (isLoading) return;
    
    isLoading = true;
    currentPage++;
    
    if (!token) {
        return;
    }
    fetch(`${apiBase}/wallet/transactions?page=${currentPage}`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.length > 0) {
            appendTransactionsToTable(data.data);
        } else {
            currentPage--; // Reset page if no data
        }
    })
    .catch(error => {
        console.error('Error loading more transactions:', error);
    })
    .finally(() => {
        isLoading = false;
    });
}

function updateTransactionsTable(transactions) {
    const tbody = document.querySelector('#transactions-table tbody');
    
    if (transactions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                    No transactions found
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = transactions.map(transaction => createTransactionRow(transaction)).join('');
}

function appendTransactionsToTable(transactions) {
    const tbody = document.querySelector('#transactions-table tbody');
    const rows = transactions.map(transaction => createTransactionRow(transaction)).join('');
    tbody.insertAdjacentHTML('beforeend', rows);
}

function createTransactionRow(transaction) {
    const typeIcons = {
        'earn': 'fa-plus-circle text-success',
        'deposit': 'fa-plus-circle text-info',
        'withdraw': 'fa-minus-circle text-danger',
        'ad_spend': 'fa-minus-circle text-warning',
        'referral': 'fa-gift text-primary',
        'bonus': 'fa-star text-warning'
    };
    
    const icon = typeIcons[transaction.type] || 'fa-exchange-alt text-secondary';
    const amountClass = transaction.amount >= 0 ? 'text-success' : 'text-danger';
    const amountSign = transaction.amount >= 0 ? '+' : '';
    
    return `
        <tr class="transaction-row" onclick="showTransactionDetails(${transaction.transaction_id})">
            <td>
                <i class="fas ${icon} me-2"></i>
                ${transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1)}
            </td>
            <td class="${amountClass}">
                ${amountSign}${parseFloat(transaction.amount).toFixed(8)} USDT
            </td>
            <td>${transaction.description}</td>
            <td>${new Date(transaction.created_at).toLocaleString()}</td>
            <td><span class="badge bg-success">Completed</span></td>
        </tr>
    `;
}

function showTransactionDetails(transactionId) {
    // In a real implementation, you would fetch detailed transaction info
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    const details = document.getElementById('transaction-details');
    
    details.innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <p>Loading transaction details...</p>
        </div>
    `;
    
    modal.show();
    
    // Simulate loading (in real app, fetch from API)
    setTimeout(() => {
        details.innerHTML = `
            <div class="mb-3">
                <strong>Transaction ID:</strong> #${transactionId}
            </div>
            <div class="mb-3">
                <strong>Type:</strong> Transaction
            </div>
            <div class="mb-3">
                <strong>Status:</strong> <span class="badge bg-success">Completed</span>
            </div>
            <div class="mb-3">
                <strong>Date:</strong> ${new Date().toLocaleString()}
            </div>
        `;
    }, 500);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
