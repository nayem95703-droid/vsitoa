<?php

function renderBlueprintTick(string $size = 'normal', bool $showTooltip = true, string $tooltipText = 'Blueprint Approved'): void
{
    $class = 'blueprint-tick';
    if ($size === 'large') {
        $class .= ' blueprint-tick-large';
    } elseif ($size === 'small') {
        $class .= ' blueprint-tick-small';
    }

    $tooltipAttr = $showTooltip ? ' data-tooltip="' . htmlspecialchars($tooltipText) . '"' : '';
    $tooltipClass = $showTooltip ? ' verified-tooltip' : '';

    echo '<span class="' . $class . $tooltipClass . '"' . $tooltipAttr . '></span>';
}
