<?php
$page_title = 'Execute Task - ' . Config::get('app.name');
$show_navbar = true;
$show_sidebar = false;
$show_footer = true;

$basePath = (string) Config::get('app.base_path', '');
$expiresAt = $userTask['expires_at'] ?? null;
$taskTitle = $task['title'] ?? 'Task';
$taskDesc = $task['description'] ?? '';
$taskUrl = $task['target_website_url'] ?? '';
$taskReward = $task['payment_per_execution'] ?? 0;
$taskTime = $task['max_completion_time'] ?? 30;
$workerIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

ob_start();
?>

<style>
.exec-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px 16px;
}
.exec-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 16px;
    border: 1px solid rgba(255,255,255,0.08);
}
.exec-header h1 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 4px 0;
}
.exec-header .task-meta {
    font-size: 0.78rem;
    color: #94a3b8;
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}
.exec-header .task-meta span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.timer-box {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.timer-box .timer-label {
    font-size: 0.85rem;
    color: #f87171;
    font-weight: 600;
}
.timer-box .timer-value {
    font-family: 'Courier New', monospace;
    font-size: 1.8rem;
    font-weight: 700;
    color: #ef4444;
    letter-spacing: 2px;
}
.timer-box .timer-value.safe {
    color: #22c55e;
}
.timer-box .timer-value.warn {
    color: #f59e0b;
}
.timer-box .timer-value.danger {
    color: #ef4444;
}

.warn-box {
    background: rgba(251,191,36,0.06);
    border: 1px solid rgba(251,191,36,0.2);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 16px;
}
.warn-box h6 {
    color: #fbbf24;
    font-weight: 700;
    margin-bottom: 10px;
    font-size: 0.85rem;
}
.warn-box ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.warn-box li {
    font-size: 0.78rem;
    color: #94a3b8;
    padding: 4px 0;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.warn-box li i {
    color: #fbbf24;
    margin-top: 2px;
    flex-shrink: 0;
}
.warn-box .ip-tag {
    display: inline-block;
    background: rgba(239,68,68,0.12);
    color: #f87171;
    padding: 2px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.72rem;
    font-weight: 600;
}

.exec-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
}
.exec-card h6 {
    color: #e2e8f0;
    font-weight: 600;
    margin-bottom: 12px;
    font-size: 0.9rem;
}
.exec-card .task-desc {
    font-size: 0.82rem;
    color: #94a3b8;
    line-height: 1.6;
    white-space: pre-wrap;
}
.exec-card .task-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #60a5fa;
    font-size: 0.82rem;
    text-decoration: none;
    padding: 8px 14px;
    background: rgba(96,165,250,0.08);
    border: 1px solid rgba(96,165,250,0.2);
    border-radius: 8px;
    margin-top: 8px;
    transition: all 0.15s;
}
.exec-card .task-link:hover {
    background: rgba(96,165,250,0.15);
    color: #93c5fd;
}

.report-form textarea {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.12);
    color: #e2e8f0;
    border-radius: 10px;
    font-size: 0.85rem;
    resize: vertical;
}
.report-form textarea:focus {
    background: rgba(255,255,255,0.06);
    border-color: #2563eb;
    color: #e2e8f0;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.15);
}
.report-form textarea::placeholder {
    color: #475569;
}
.report-form .form-label {
    color: #cbd5e1;
    font-size: 0.82rem;
    font-weight: 600;
    margin-bottom: 6px;
}

.upload-zone {
    border: 2px dashed rgba(255,255,255,0.12);
    border-radius: 10px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: #2563eb;
    background: rgba(37,99,235,0.05);
}
.upload-zone .upload-icon {
    font-size: 2rem;
    color: #475569;
    margin-bottom: 8px;
}
.upload-zone .upload-text {
    font-size: 0.82rem;
    color: #64748b;
}
.upload-zone .upload-text strong {
    color: #60a5fa;
}
.upload-zone .paste-hint {
    font-size: 0.72rem;
    color: #475569;
    margin-top: 6px;
}
.upload-zone input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.image-preview {
    margin-top: 12px;
    display: none;
}
.image-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
}
.image-preview .remove-img {
    display: inline-block;
    margin-top: 6px;
    font-size: 0.75rem;
    color: #ef4444;
    cursor: pointer;
}

.btn-submit {
    background: #22c55e;
    color: #fff;
    border: none;
    padding: 10px 28px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-submit:hover {
    background: #16a34a;
    transform: translateY(-1px);
}
.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
.btn-refuse {
    background: transparent;
    color: #ef4444;
    border: 1px solid rgba(239,68,68,0.3);
    padding: 10px 28px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-refuse:hover {
    background: rgba(239,68,68,0.1);
    border-color: rgba(239,68,68,0.5);
}

.expired-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.85);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.expired-overlay.show {
    display: flex;
}
.expired-box {
    background: #1e293b;
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 16px;
    padding: 40px;
    text-align: center;
    max-width: 400px;
}
.expired-box h3 {
    color: #ef4444;
    font-size: 1.3rem;
    margin-bottom: 10px;
}
.expired-box p {
    color: #94a3b8;
    font-size: 0.88rem;
    margin-bottom: 20px;
}

.success-box {
    background: #1e293b;
    border: 1px solid rgba(34,197,94,0.3);
    border-radius: 16px;
    padding: 40px;
    text-align: center;
    max-width: 400px;
}
.success-box h3 {
    color: #22c55e;
    font-size: 1.3rem;
    margin-bottom: 10px;
}
.success-box p {
    color: #94a3b8;
    font-size: 0.88rem;
    margin-bottom: 20px;
}
</style>

<div class="exec-container">
    <!-- Header -->
    <div class="exec-header">
        <h1><?= htmlspecialchars($taskTitle) ?></h1>
        <div class="task-meta">
            <span><i class="fas fa-coins"></i> <?= number_format((float) $taskReward, 2) ?> USDT</span>
            <span><i class="fas fa-clock"></i> <?= (int) $taskTime ?> min limit</span>
            <span><i class="fas fa-shield-alt"></i> IP: <span class="ip-tag"><?= htmlspecialchars($workerIp) ?></span></span>
        </div>
    </div>

    <!-- Timer -->
    <div class="timer-box" id="timerBox">
        <div>
            <div class="timer-label"><i class="fas fa-stopwatch me-1"></i>Time Remaining</div>
            <small style="color:#94a3b8; font-size:0.72rem;">If timer expires, your work will be lost</small>
        </div>
        <div class="timer-value safe" id="countdownTimer">--:--</div>
    </div>

    <!-- Warning Box -->
    <div class="warn-box">
        <h6><i class="fas fa-exclamation-triangle me-1"></i>Warning for freebie lovers</h6>
        <ul>
            <li><i class="fas fa-globe"></i> Your IP address <span class="ip-tag"><?= htmlspecialchars($workerIp) ?></span> is recorded. VPN/proxy use will result in a permanent ban.</li>
            <li><i class="fas fa-ban"></i> Asking for money in exchange for a review or submitting fake proof = <strong style="color:#ef4444;">instant BAN</strong> and balance forfeiture.</li>
            <li><i class="fas fa-eye"></i> All submissions are manually reviewed. Low-quality or copied reports will be rejected.</li>
            <li><i class="fas fa-user-shield"></i> Complete the task honestly. You only get paid for genuine work.</li>
        </ul>
    </div>

    <!-- Task Instructions -->
    <div class="exec-card">
        <h6><i class="fas fa-list-check me-2"></i>Task Instructions</h6>
        <div class="task-desc"><?= nl2br(htmlspecialchars($taskDesc)) ?></div>
        <?php if (!empty($taskUrl)): ?>
            <a href="<?= htmlspecialchars($taskUrl) ?>" target="_blank" class="task-link">
                <i class="fas fa-external-link-alt"></i> Open target website
                <i class="fas fa-arrow-up-right-from-square" style="font-size:0.65rem;"></i>
            </a>
        <?php endif; ?>
    </div>

    <!-- Report Form -->
    <?php if ($userTask && $userTask['status'] === 'submitted'): ?>
        <div class="exec-card" style="border-color: rgba(251,191,36,0.3);">
            <div style="text-align:center; padding: 20px 0;">
                <i class="fas fa-hourglass-half fa-2x" style="color:#fbbf24; margin-bottom:12px;"></i>
                <h6 style="color:#fbbf24;">Proof Submitted - Awaiting Review</h6>
                <p style="color:#94a3b8; font-size:0.82rem; margin:0;">Your submission is being reviewed by admin. You'll be notified once approved.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="exec-card report-form">
            <h6><i class="fas fa-pen-to-square me-2"></i>Submit Your Report</h6>
            <form id="reportForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">

                <div class="mb-3">
                    <label class="form-label">Describe what you did *</label>
                    <textarea name="report_text" class="form-control" rows="5" required
                        placeholder="Write your task completion report here.&#10;&#10;Example: I visited the website, explored the content, and completed the required action as instructed."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Target URL (if visited a site)</label>
                    <input type="url" name="submitted_url" class="form-control" placeholder="https://example.com" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.12); color:#e2e8f0; border-radius:10px; font-size:0.85rem;">
                </div>

                <div class="mb-3">
                    <label class="form-label">Attach Screenshot (optional)</label>
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="proof_image" id="proofImage" accept="image/*">
                        <div class="upload-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                        <div class="upload-text">
                            <strong>Click to upload</strong> or drag and drop
                        </div>
                        <div class="paste-hint">You can also paste an image with <kbd style="background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:3px;">Ctrl+V</kbd></div>
                    </div>
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImg" src="" alt="Preview">
                        <div class="remove-img" onclick="removeImage()"><i class="fas fa-times me-1"></i>Remove image</div>
                    </div>
                </div>

                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane me-1"></i>Send Report
                    </button>
                    <button type="button" class="btn-refuse" onclick="refuseTask()">
                        <i class="fas fa-times me-1"></i>Refuse to perform
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Expired Overlay -->
<div class="expired-overlay" id="expiredOverlay">
    <div class="expired-box">
        <i class="fas fa-clock fa-3x" style="color:#ef4444; margin-bottom:16px;"></i>
        <h3>Time Expired!</h3>
        <p>You ran out of time to complete this task. Please go back and start a new task.</p>
        <a href="<?= $basePath ?>/tasks" class="btn-submit" style="text-decoration:none; display:inline-block;">
            <i class="fas fa-arrow-left me-1"></i>Back to Tasks
        </a>
    </div>
</div>

<!-- Success Overlay -->
<div class="expired-overlay" id="successOverlay">
    <div class="success-box">
        <i class="fas fa-check-circle fa-3x" style="color:#22c55e; margin-bottom:16px;"></i>
        <h3>Report Sent!</h3>
        <p>Your submission has been sent for review. You'll be notified once it's approved.</p>
        <a href="<?= $basePath ?>/tasks" class="btn-submit" style="text-decoration:none; display:inline-block; background:#2563eb;">
            <i class="fas fa-arrow-left me-1"></i>Back to Tasks
        </a>
    </div>
</div>

<script>
(function() {
    const expiresAt = "<?= $expiresAt ?>";
    const timerEl = document.getElementById('countdownTimer');
    const timerBox = document.getElementById('timerBox');
    const expiredOverlay = document.getElementById('expiredOverlay');
    const reportForm = document.getElementById('reportForm');
    const submitBtn = document.getElementById('submitBtn');

    let endTime = new Date(expiresAt + " UTC").getTime();

    function updateTimer() {
        let now = Date.now();
        let diff = endTime - now;

        if (diff <= 0) {
            clearInterval(timerInterval);
            timerEl.textContent = 'EXPIRED';
            timerEl.className = 'timer-value danger';
            timerBox.style.borderColor = 'rgba(239,68,68,0.5)';
            timerBox.style.background = 'rgba(239,68,68,0.12)';
            expiredOverlay.classList.add('show');
            if (reportForm) {
                reportForm.style.display = 'none';
            }
            return;
        }

        let mins = Math.floor(diff / 60000);
        let secs = Math.floor((diff % 60000) / 1000);
        timerEl.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');

        if (diff < 60000) {
            timerEl.className = 'timer-value danger';
        } else if (diff < 300000) {
            timerEl.className = 'timer-value warn';
        } else {
            timerEl.className = 'timer-value safe';
        }
    }

    updateTimer();
    let timerInterval = setInterval(updateTimer, 1000);

    // Paste-to-upload
    document.addEventListener('paste', function(e) {
        const items = e.clipboardData?.items;
        if (!items) return;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                e.preventDefault();
                const file = items[i].getAsFile();
                const input = document.getElementById('proofImage');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                showImagePreview(file);
                break;
            }
        }
    });

    // Drag and drop
    const uploadZone = document.getElementById('uploadZone');
    if (uploadZone) {
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        uploadZone.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const input = document.getElementById('proofImage');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                showImagePreview(file);
            }
        });

        document.getElementById('proofImage').addEventListener('change', function() {
            if (this.files[0]) {
                showImagePreview(this.files[0]);
            }
        });
    }

    function showImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
            uploadZone.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    window.removeImage = function() {
        document.getElementById('proofImage').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('previewImg').src = '';
        uploadZone.style.display = '';
    };

    // Submit form
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const diff = endTime - Date.now();
            if (diff <= 0) {
                expiredOverlay.classList.add('show');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';

            const formData = new FormData(this);
            const taskId = <?= (int) $taskId ?>;

            fetch((window.VSItoA_BASE_PATH || '') + '/tasks/' + taskId + '/submit', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
                },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('successOverlay').classList.add('show');
                } else {
                    alert(data.message || 'Submission failed');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Send Report';
                }
            })
            .catch(() => {
                alert('Network error. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Send Report';
            });
        });
    }
})();

function refuseTask() {
    if (!confirm('Are you sure you want to refuse this task?')) return;

    const taskId = <?= (int) $taskId ?>;
    fetch((window.VSItoA_BASE_PATH || '') + '/tasks/' + taskId + '/refuse', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
        }
    })
    .then(r => r.json())
    .then(data => {
        window.location.href = (window.VSItoA_BASE_PATH || '') + '/tasks';
    })
    .catch(() => {
        window.location.href = (window.VSItoA_BASE_PATH || '') + '/tasks';
    });
}
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
