<?php
/**
 * Plugin Name: CV - Store Category Sanitizer
 * Description: Normaliza el árbol de categorías visible en la ficha WCFM de vendedores específicos.
 * Version:     1.0.0
 * Author:      Ciudad Virtual
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_filter(
    'wcfm_vendor_store_taxomonies',
    static function ($vendorCategories, $vendorId, $taxonomy) {
        if ('product_cat' !== $taxonomy) {
            return $vendorCategories;
        }

        $targetVendorId = cv_store_category_sanitizer_get_vendor_id_by_slug('asociacion_propietarios');
        if (!$targetVendorId || (int) $vendorId !== $targetVendorId) {
            return $vendorCategories;
        }

        $sanitizedTree = cv_store_category_sanitizer_build_tree($targetVendorId);
        if (!empty($sanitizedTree)) {
            return $sanitizedTree;
        }

        return $vendorCategories;
    },
    20,
    3
);

/**
 * Obtiene el ID del vendedor usando su slug de tienda.
 */
function cv_store_category_sanitizer_get_vendor_id_by_slug(string $slug): int
{
    static $cache = [];

    if (isset($cache[$slug])) {
        return $cache[$slug];
    }

    $storeId = 0;
    if (function_exists('wcfmmp_get_store_id_by_slug')) {
        $storeId = (int) wcfmmp_get_store_id_by_slug($slug);
    }

    if (!$storeId) {
        global $wpdb;
        $storeId = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wcfmmp_store_slug' AND meta_value = %s LIMIT 1",
                $slug
            )
        );

        if (!$storeId) {
            $likePattern = sprintf('%%"store_slug";s:%%:"%s"%%', $wpdb->esc_like($slug));
            $storeId = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wcfmmp_profile_settings' AND meta_value LIKE %s LIMIT 1",
                    $likePattern
                )
            );
        }
    }

    $cache[$slug] = $storeId;
    return $storeId;
}

/**
 * Devuelve el listado de IDs de términos permitidos (Inmobiliaria y Sector Inmobiliaria).
 *
 * @return array<int,bool>
 */
function cv_store_category_sanitizer_get_anchor_ids(): array
{
    static $anchors = null;
    if (is_array($anchors)) {
        return $anchors;
    }

    $anchors = [];
    $slugs = [
        'inmobiliaria',
        'inmobiliaria-sector',
    ];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if (!$term || is_wp_error($term)) {
            continue;
        }

        $anchors[(int) $term->term_id] = true;
    }

    return $anchors;
}

/**
 * Construye un árbol fresco de categorías limitado a los términos permitidos.
 *
 * @return array<int,mixed>
 */
function cv_store_category_sanitizer_build_tree(int $vendorId): array
{
    $anchors = cv_store_category_sanitizer_get_anchor_ids();
    if (empty($anchors)) {
        return [];
    }

    $products = get_posts([
        'post_type'      => 'product',
        'post_status'    => ['publish', 'pending', 'draft'],
        'fields'         => 'ids',
        'author'         => $vendorId,
        'nopaging'       => true,
        'suppress_filters' => false,
    ]);

    if (empty($products)) {
        return [];
    }

    $tree = [];

    foreach ($products as $productId) {
        $terms = wp_get_post_terms((int) $productId, 'product_cat');
        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            $termId = (int) $term->term_id;
            $path = cv_store_category_sanitizer_get_allowed_path($termId, $anchors);
            if ($path === null) {
                continue;
            }
            cv_store_category_sanitizer_insert_path($tree, $path);
        }
    }

    // Aseguramos que las raíces siempre aparezcan aunque no tengan productos directos.
    foreach (array_keys($anchors) as $rootId) {
        if (!array_key_exists($rootId, $tree)) {
            $tree[$rootId] = [];
        }
    }

    if (empty($tree)) {
        return [];
    }

    return cv_store_category_sanitizer_sort_tree($tree);
}

/**
 * Inserta un camino ya validado en el árbol.
 */
function cv_store_category_sanitizer_insert_path(array &$tree, array $path): void
{
    $ref =& $tree;
    $lastIndex = count($path) - 1;

    foreach ($path as $index => $currentId) {
        if (!array_key_exists($currentId, $ref) || !is_array($ref[$currentId])) {
            $ref[$currentId] = [];
        }

        if ($index < $lastIndex) {
            $ref =& $ref[$currentId];
        }
    }
}

/**
 * Ordena el árbol recursivamente por nombre de término.
 *
 * @param array<int,mixed> $tree
 *
 * @return array<int,mixed>
 */
function cv_store_category_sanitizer_sort_tree(array $tree): array
{
    uksort($tree, static function ($a, $b) {
        return strcasecmp(
            cv_store_category_sanitizer_get_term_name((int) $a),
            cv_store_category_sanitizer_get_term_name((int) $b)
        );
    });

    foreach ($tree as $key => $children) {
        if (is_array($children) && !empty($children)) {
            $tree[$key] = cv_store_category_sanitizer_sort_tree($children);
        }
    }

    return $tree;
}

/**
 * Devuelve el nombre de un término con memoización simple.
 */
function cv_store_category_sanitizer_get_term_name(int $termId): string
{
    static $cache = [];

    if (isset($cache[$termId])) {
        return $cache[$termId];
    }

    $term = get_term($termId, 'product_cat');
    if (!$term || is_wp_error($term)) {
        $cache[$termId] = '';
        return '';
    }

    $cache[$termId] = $term->name;
    return $cache[$termId];
}

/**
 * Obtiene el camino desde el ancla permitido hasta el término.
 */
function cv_store_category_sanitizer_get_allowed_path(int $termId, array $anchors): ?array
{
    $ancestors = get_ancestors($termId, 'product_cat');
    if (is_wp_error($ancestors)) {
        return null;
    }

    $path = array_reverse($ancestors);
    $path[] = $termId;

    $filtered = [];
    $anchorFound = false;

    foreach ($path as $nodeId) {
        $nodeId = (int) $nodeId;
        if (isset($anchors[$nodeId])) {
            $filtered = [$nodeId];
            $anchorFound = true;
            continue;
        }

        if ($anchorFound) {
            $filtered[] = $nodeId;
        }
    }

    if (!$anchorFound) {
        return null;
    }

    return $filtered;
}

