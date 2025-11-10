<?php
/**
 * Plugin Name: CV Store Category Sector Filter
 * Description: Limita el selector "Filtrar por categoría" de comercios a las categorías bajo "Sector".
 * Version: 1.0.0
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('cv_get_sector_category_terms')) {
    function cv_get_sector_category_terms() {
        static $allowed_terms = null;

        if ($allowed_terms !== null) {
            return $allowed_terms;
        }

        $allowed_terms = array();

        // Intentar localizar el término "Sector" por slug y por nombre.
        $sector_term = get_term_by('slug', 'sector', 'product_cat');
        if (!$sector_term) {
            $sector_term = get_term_by('name', 'Sector', 'product_cat');
        }

        if ($sector_term && !is_wp_error($sector_term)) {
            $allowed_terms = array_map('intval', get_term_children($sector_term->term_id, 'product_cat'));
            $allowed_terms[] = (int) $sector_term->term_id;
            $allowed_terms   = array_unique($allowed_terms);
        }

        return $allowed_terms;
    }
}

add_filter('wcfm_is_allow_store_list_taxomony_by_id', function ($allow, $term_id, $taxonomy) {
    if (!$allow || $taxonomy !== 'product_cat') {
        return $allow;
    }

    $allowed_terms = cv_get_sector_category_terms();
    if (empty($allowed_terms)) {
        // Si no encontramos el término "Sector", no forzamos el filtro.
        return $allow;
    }

    return in_array((int) $term_id, $allowed_terms, true);
}, 10, 3);

