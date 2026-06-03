/**
 * VSItoA - Main JavaScript Application
 */

// Global variables
window.VSItoA = {
    config: {
        apiBase: (window.VSItoA_BASE_PATH || '') + '/api',
        token: localStorage.getItem('jwt_token') || null,
        refreshInterval: 30000, // 30 seconds
        notificationInterval: 60000 // 1 minute
    },
    user: null,
    notifications: []
};

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Set up CSRF token
    setupCSRF();

    // Initialize dark mode
    setupDarkMode();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize modals
    initializeModals();
    
    // Set up event listeners
    setupEventListeners();
    
    // Load user data if logged in
    if (window.VSItoA.config.token) {
        loadUserData();
        startAutoRefresh();
    }
}

function setupDarkMode() {
    const darkModeEnabled = localStorage.getItem('dark_mode') === '1';
    document.body.classList.toggle('dark-mode', darkModeEnabled);

    const darkModeToggle = document.getElementById('dark_mode');
    if (darkModeToggle) {
        darkModeToggle.checked = darkModeEnabled;
        darkModeToggle.addEventListener('change', function () {
            const enabled = darkModeToggle.checked;
            document.body.classList.toggle('dark-mode', enabled);
            localStorage.setItem('dark_mode', enabled ? '1' : '0');
        });
    }
}

// CSRF Token Setup
function setupCSRF() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.VSItoA.csrfToken = token.getAttribute('content');
    }
}

// Initialize Bootstrap Tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize Bootstrap Modals
function initializeModals() {
    const modalTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="modal"]'));
    modalTriggerList.map(function (modalTriggerEl) {
        return new bootstrap.Modal(modalTriggerEl);
    });
}

// Setup Event Listeners
function setupEventListeners() {
    // Copy to clipboard functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('copy-btn')) {
            copyToClipboard(e.target);
        }
    });
    
    // Form submissions
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('ajax-form')) {
            e.preventDefault();
            handleAjaxForm(e.target);
        }
    });
    
    // Logout button
    const logoutBtn = document.querySelector('[data-action="logout"]');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
}

// Load User Data
function loadUserData() {
    fetch(window.VSItoA.config.apiBase + '/user/profile', {
        headers: {
            'Authorization': 'Bearer ' + window.VSItoA.config.token
        }
    })
    .then(response => {
        if (response.status === 401) {
            handleUnauthorized();
            throw new Error('Unauthorized');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.VSItoA.user = data.data;
            updateUserInterface();
        }
    })
    .catch(error => {
        console.error('Error loading user data:', error);
    });
}

// Update User Interface
function updateUserInterface() {
    // Update user balance if element exists
    const balanceElements = document.querySelectorAll('[data-user="balance"]');
    balanceElements.forEach(element => {
        element.textContent = parseFloat(window.VSItoA.user.balance).toFixed(8);
    });
    
    // Update username if element exists
    const usernameElements = document.querySelectorAll('[data-user="username"]');
    usernameElements.forEach(element => {
        element.textContent = window.VSItoA.user.username;
    });
}

// Start Auto Refresh
function startAutoRefresh() {
    // Refresh balance
    setInterval(() => {
        refreshBalance();
    }, window.VSItoA.config.refreshInterval);
    
    // Refresh notifications
    setInterval(() => {
        loadNotifications();
    }, window.VSItoA.config.notificationInterval);
}

// Refresh Balance
function refreshBalance() {
    fetch(window.VSItoA.config.apiBase + '/wallet/balance', {
        headers: {
            'Authorization': 'Bearer ' + window.VSItoA.config.token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const balanceElements = document.querySelectorAll('[data-user="balance"]');
            balanceElements.forEach(element => {
                const currentBalance = parseFloat(element.textContent);
                const newBalance = parseFloat(data.data.balance);
                
                if (newBalance > currentBalance) {
                    // Animate balance increase
                    element.classList.add('earnings-pulse');
                    setTimeout(() => {
                        element.classList.remove('earnings-pulse');
                    }, 2000);
                }
                
                element.textContent = newBalance.toFixed(8);
            });
        }
    })
    .catch(error => {
        console.error('Error refreshing balance:', error);
    });
}

// Load Notifications
function loadNotifications() {
    fetch(window.VSItoA.config.apiBase + '/user/notifications', {
        headers: {
            'Authorization': 'Bearer ' + window.VSItoA.config.token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.VSItoA.notifications = data.data.filter(n => !n.is_read);
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error loading notifications:', error);
    });
}

// Update Notification Badge
function updateNotificationBadge() {
    const badgeElements = document.querySelectorAll('.notification-count');
    const count = window.VSItoA.notifications.length;
    
    badgeElements.forEach(element => {
        element.textContent = count;
        element.style.display = count > 0 ? 'block' : 'none';
    });
}

// Handle AJAX Form Submission
function handleAjaxForm(form) {
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch(form.action, {
        method: form.method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.VSItoA.csrfToken
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', data.message, 'success');
            
            // Handle redirect if specified
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
            
            // Reset form if it's a success
            if (form.reset) {
                form.reset();
            }
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
        showAlert('Error', 'An error occurred. Please try again.', 'danger');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Show Alert
function showAlert(title, message, type = 'info') {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show slide-in-up`;
    alertElement.innerHTML = `
        <strong>${title}:</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alertElement);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertElement.parentNode) {
            alertElement.remove();
        }
    }, 5000);
}

// Create Alert Container
function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Show Validation Errors
function showValidationErrors(errors) {
    let errorMessage = '';
    for (const [field, messages] of Object.entries(errors)) {
        errorMessage += `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}\n`;
    }
    showAlert('Validation Error', errorMessage, 'danger');
}

// Copy to Clipboard
function copyToClipboard(button) {
    const text = button.getAttribute('data-copy') || button.previousElementSibling.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.add('text-success');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('text-success');
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy text: ', err);
    });
}

// Handle Logout
function handleLogout(e) {
    e.preventDefault();
    
    if (confirm('Are you sure you want to logout?')) {
        fetch((window.VSItoA_BASE_PATH || '') + '/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.VSItoA.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                localStorage.removeItem('jwt_token');
                window.VSItoA.token = null;
                window.VSItoA.user = null;
                window.location.href = (window.VSItoA_BASE_PATH || '') + '/login';
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            window.location.href = (window.VSItoA_BASE_PATH || '') + '/login';
        });
    }
}

// Handle Unauthorized
function handleUnauthorized() {
    localStorage.removeItem('jwt_token');
    window.VSItoA.token = null;
    window.VSItoA.user = null;
    
    if (!window.location.pathname.includes('/login') && 
        !window.location.pathname.includes('/register')) {
        window.location.href = (window.VSItoA_BASE_PATH || '') + '/login';
    }
}

// API Helper Functions
window.VSItoA.api = {
    get: function(endpoint) {
        return fetch(window.VSItoA.config.apiBase + endpoint, {
            headers: {
                'Authorization': 'Bearer ' + window.VSItoA.config.token
            }
        }).then(response => {
            if (response.status === 401) {
                handleUnauthorized();
                throw new Error('Unauthorized');
            }
            return response.json();
        });
    },
    
    post: function(endpoint, data) {
        return fetch(window.VSItoA.config.apiBase + endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + window.VSItoA.config.token
            },
            body: JSON.stringify(data)
        }).then(response => {
            if (response.status === 401) {
                handleUnauthorized();
                throw new Error('Unauthorized');
            }
            return response.json();
        });
    },
    
    put: function(endpoint, data) {
        return fetch(window.VSItoA.config.apiBase + endpoint, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + window.VSItoA.config.token
            },
            body: JSON.stringify(data)
        }).then(response => {
            if (response.status === 401) {
                handleUnauthorized();
                throw new Error('Unauthorized');
            }
            return response.json();
        });
    },
    
    delete: function(endpoint) {
        return fetch(window.VSItoA.config.apiBase + endpoint, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + window.VSItoA.config.token
            }
        }).then(response => {
            if (response.status === 401) {
                handleUnauthorized();
                throw new Error('Unauthorized');
            }
            return response.json();
        });
    }
};

// Ad Viewing Functions
window.VSItoA.ads = {
    viewAd: function(adId) {
        return window.VSItoA.api.post(`/ads/${adId}/view`);
    },
    
    startTimer: function(duration, onComplete) {
        let timeLeft = duration;
        const timerElement = document.getElementById('ad-timer');
        
        const interval = setInterval(() => {
            timeLeft--;
            
            if (timerElement) {
                timerElement.textContent = timeLeft;
                
                // Add warning classes
                if (timeLeft <= 5) {
                    timerElement.classList.add('text-danger');
                } else if (timeLeft <= 10) {
                    timerElement.classList.add('text-warning');
                }
            }
            
            if (timeLeft <= 0) {
                clearInterval(interval);
                if (onComplete) onComplete();
            }
        }, 1000);
        
        return interval;
    }
};

// Task Functions
window.VSItoA.tasks = {
    complete: function(taskId, proofData) {
        return window.VSItoA.api.post(`/tasks/${taskId}/complete`, proofData);
    },
    
    submitProof: function(taskId, formData) {
        return fetch(`/api/tasks/${taskId}/complete`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + window.VSItoA.config.token
            },
            body: formData
        }).then(response => response.json());
    }
};

// Wallet Functions
window.VSItoA.wallet = {
    getBalance: function() {
        return window.VSItoA.api.get('/wallet/balance');
    },
    
    getTransactions: function(page = 1) {
        return window.VSItoA.api.get(`/wallet/transactions?page=${page}`);
    },
    
    createDeposit: function(data) {
        return window.VSItoA.api.post('/wallet/deposit', data);
    },
    
    createWithdrawal: function(data) {
        return window.VSItoA.api.post('/wallet/withdraw', data);
    }
};

// Utility Functions
window.VSItoA.utils = {
    formatNumber: function(num, decimals = 8) {
        return parseFloat(num).toFixed(decimals);
    },
    
    formatCurrency: function(amount, currency = 'USDT') {
        return `${this.formatNumber(amount)} ${currency}`;
    },
    
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    },
    
    timeAgo: function(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
            }
        }
        
        return 'Just now';
    },
    
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Export for global access
window.VSItoA.showAlert = showAlert;
window.VSItoA.copyToClipboard = copyToClipboard;
window.VSItoA.handleLogout = handleLogout;
