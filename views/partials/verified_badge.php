<?php
/**
 * Verified Badge Component
 * Displays verification status with different sizes and styles
 */

function renderVerifiedBadge($size = 'normal', $showTooltip = true, $tooltipText = 'Verified Account') {
    $badgeClass = 'verified-badge';
    if ($size === 'large') $badgeClass .= ' verified-badge-large';
    elseif ($size === 'small') $badgeClass .= ' verified-badge-small';
    
    $tooltipAttr = $showTooltip ? " data-tooltip=\"$tooltipText\"" : '';
    $tooltipClass = $showTooltip ? ' verified-tooltip' : '';
    
    echo "<span class=\"$badgeClass$tooltipClass\"$tooltipAttr>";
    echo "<span class=\"verified-badge-icon\"></span>";
    echo "<span>Verified</span>";
    echo "</span>";
}

function renderVerifiedProfileCard($user, $showFeatures = true) {
    $isVerified = $user['is_verified'] ?? false;
    
    echo "<div class=\"verified-profile-card\">";
    echo "<div style=\"display: flex; align-items: center; gap: 12px;\">";
    
    // Profile image
    $profileImage = $user['profile_image'] ?? '/assets/images/default-avatar.png';
    echo "<img src=\"$profileImage\" alt=\"Profile\" style=\"width: 60px; height: 60px; border-radius: 50%; object-fit: cover;\">";
    
    // User info
    echo "<div style=\"flex: 1;\">";
    echo "<div style=\"display: flex; align-items: center; gap: 8px;\">";
    echo "<h3 style=\"margin: 0; font-size: 18px; color: #1f2937;\">" . htmlspecialchars($user['username'] ?? 'User') . "</h3>";
    if ($isVerified) {
        renderVerifiedBadge('small', true, 'Verified User with Enhanced Protection');
    }
    echo "</div>";
    
    if (!empty($user['first_name']) || !empty($user['last_name'])) {
        echo "<p style=\"margin: 4px 0; color: #6b7280; font-size: 14px;\">";
        echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        echo "</p>";
    }
    
    if (!empty($user['company_name'])) {
        echo "<p style=\"margin: 4px 0; color: #6b7280; font-size: 14px;\">";
        echo "🏢 " . htmlspecialchars($user['company_name']);
        echo "</p>";
    }
    
    echo "</div>";
    echo "</div>";
    
    if ($isVerified && $showFeatures) {
        echo "<div class=\"verified-features\">";
        echo "<div class=\"verified-feature-item\">";
        echo "<span class=\"verified-feature-icon\">✓</span>";
        echo "<span>Increased Account Protection</span>";
        echo "</div>";
        echo "<div class=\"verified-feature-item\">";
        echo "<span class=\"verified-feature-icon\">✓</span>";
        echo "<span>Enhanced Support Priority</span>";
        echo "</div>";
        echo "<div class=\"verified-feature-item\">";
        echo "<span class=\"verified-feature-icon\">✓</span>";
        echo "<span>Upgraded Profile Links</span>";
        echo "</div>";
        echo "<div class=\"verified-feature-item\">";
        echo "<span class=\"verified-feature-icon\">✓</span>";
        echo "<span>Search Optimization</span>";
        echo "</div>";
        echo "<div class=\"verified-feature-item\">";
        echo "<span class=\"verified-feature-icon\">✓</span>";
        echo "<span>Exclusive Stickers</span>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
}

function renderVerifiedSticker($text, $type = 'default') {
    $stickerClass = 'verified-sticker';
    
    switch ($type) {
        case 'protection':
            echo "<span class=\"$stickerClass\" style=\"background: linear-gradient(135deg, #10b981, #34d399);\">🛡️ $text</span>";
            break;
        case 'support':
            echo "<span class=\"$stickerClass\" style=\"background: linear-gradient(135deg, #8b5cf6, #a78bfa);\">💬 $text</span>";
            break;
        case 'search':
            echo "<span class=\"$stickerClass\" style=\"background: linear-gradient(135deg, #f59e0b, #fbbf24);\">🔍 $text</span>";
            break;
        case 'exclusive':
            echo "<span class=\"$stickerClass\" style=\"background: linear-gradient(135deg, #ec4899, #f472b6);\">⭐ $text</span>";
            break;
        default:
            echo "<span class=\"$stickerClass\">$text</span>";
    }
}

function renderVerifiedShield($size = 'normal') {
    $shieldClass = 'verified-shield';
    if ($size === 'small') {
        $shieldClass .= ' verified-badge-small';
    } elseif ($size === 'large') {
        $shieldClass .= ' verified-badge-large';
    }
    
    echo "<span class=\"$shieldClass\"></span>";
}
?>
