<?php
/**
 * Plugin Name: CV - Remove Sponsored Products Blocks
 * Description: Oculta los bloques de widgets marcados como productos patrocinados.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('render_block', function ($block_content, $block) {
    if (empty($block_content)) {
        return $block_content;
    }

    $class_attr = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

    if ($class_attr !== '' && strpos($class_attr, 'productos_patrocinados') !== false) {
        return '';
    }

    if (
        isset($block['blockName']) &&
        $block['blockName'] === 'woocommerce/handpicked-products' &&
        strpos($block_content, 'productos_patrocinados') !== false
    ) {
        return '';
    }

    return $block_content;
}, 10, 2);


