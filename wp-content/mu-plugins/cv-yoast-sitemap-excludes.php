<?php
/**
 * Plugin Name: CV Yoast Sitemap Excludes
 * Description: Excluye páginas de navegación del sitemap de Yoast.
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wpseo_exclude_from_sitemap_by_post_ids', function (array $excluded_ids): array {
    $menu_page_ids = [
        25,      // Comercios
        371,     // Captura tu ticket (QR Ticket)
        7,       // Todos los productos / Shop
        154662,  // Tutoriales del Marketplace
        154781,  // Noticias CV
        154744,  // Contacto
        8453,    // Noticias (legacy landing)
    ];

    return array_unique(array_merge($excluded_ids, $menu_page_ids));
});

