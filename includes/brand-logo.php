<?php
// includes/brand-logo.php - Shared brand logo helper

function renderBrandLogo(array $options = []) {
    $src = BASE_URL . '/assets/logo.svg';
    $class = trim(($options['class'] ?? '') . ' brand-logo');
    $alt = $options['alt'] ?? 'Lapify';
    $ariaLabel = $options['aria-label'] ?? null;
    $style = $options['style'] ?? null;

    $attributes = [
        'src' => $src,
        'alt' => $alt,
        'class' => $class,
        'loading' => 'lazy',
        'onerror' => "this.onerror=null;this.outerHTML='<span class=\"brand-fallback\">Lapify</span>';",
    ];

    if ($ariaLabel !== null) {
        $attributes['aria-label'] = $ariaLabel;
    }
    if ($style !== null) {
        $attributes['style'] = $style;
    }

    $html = '<img';
    foreach ($attributes as $name => $value) {
        $html .= ' ' . $name . '="' . escape($value) . '"';
    }
    $html .= '>';

    return $html;
}
