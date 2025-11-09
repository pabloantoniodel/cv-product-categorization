<?php
/**
 * Plugin Name: CV - AWS Extended Search
 * Description: Amplía el índice de Advanced Woo Search para incluir categorías y vendedores con pesos coherentes.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', static function (): void {
    if (!function_exists('AWS')) {
        return;
    }

    if (!class_exists('AWS_Helpers', false)) {
        $helpers_path = null;
        if (defined('AWS_DIR')) {
            $helpers_path = trailingslashit(AWS_DIR) . 'includes/class-aws-helpers.php';
        } elseif (defined('WP_PLUGIN_DIR')) {
            $helpers_path = trailingslashit(WP_PLUGIN_DIR) . 'advanced-woo-search/includes/class-aws-helpers.php';
        }

        if ($helpers_path && file_exists($helpers_path)) {
            require_once $helpers_path;
        }
    }

    if (!class_exists('AWS_Helpers', false)) {
        return;
    }

    /**
     * Añade términos adicionales (categorías y vendedor) al índice de AWS.
     *
     * @param array<string,mixed> $data
     * @param int                 $product_id
     *
     * @return array<string,mixed>
     */
    $callback = static function (array $data, int $product_id): array {
        if (empty($product_id) || empty($data['terms']) || !is_array($data['terms'])) {
            return $data;
        }

        $additional_sources = [];

        // Categorías del producto (nombres y rutas completas).
        $categories = wp_get_post_terms($product_id, 'product_cat', [
            'hide_empty' => false,
        ]);

        if (!is_wp_error($categories) && !empty($categories)) {
            $category_terms = [];

            foreach ($categories as $category) {
                if (!$category instanceof WP_Term) {
                    continue;
                }

                $category_terms[] = $category->name;

                $ancestors = get_ancestors($category->term_id, 'product_cat');
                if (!empty($ancestors)) {
                    $ancestors = array_reverse($ancestors);
                    $path_parts = [];
                    foreach ($ancestors as $ancestor_id) {
                        $ancestor = get_term($ancestor_id, 'product_cat');
                        if ($ancestor instanceof WP_Term) {
                            $path_parts[] = $ancestor->name;
                        }
                    }
                    $path_parts[] = $category->name;
                    $category_terms[] = implode(' ', $path_parts);
                }
            }

            if (!empty($category_terms)) {
                $category_terms = array_unique(array_filter(array_map('trim', $category_terms)));
                if (!empty($category_terms)) {
                    $additional_sources['cv_category'] = AWS_Helpers::extract_terms(implode(' ', $category_terms), 'cv_category');
                }
            }
        }

        // Datos del vendedor (store name, display name, login, slug).
        $vendor_terms = [];
        $author_id = (int) get_post_field('post_author', $product_id);

        $vendor_id = $author_id;
        if (function_exists('wcfm_get_vendor_id_by_post')) {
            $maybe_vendor = wcfm_get_vendor_id_by_post($product_id);
            if ($maybe_vendor) {
                $vendor_id = (int) $maybe_vendor;
            }
        }

        if ($vendor_id > 0) {
            $vendor_user = get_user_by('id', $vendor_id);
            if ($vendor_user instanceof WP_User) {
                $vendor_terms[] = $vendor_user->display_name;
                $vendor_terms[] = $vendor_user->user_login;
            }

            if (function_exists('wcfmmp_get_store')) {
                $store = wcfmmp_get_store($vendor_id);
                if ($store && method_exists($store, 'get_shop_name')) {
                    $vendor_terms[] = $store->get_shop_name();
                }
                if ($store && method_exists($store, 'get_shop_url')) {
                    $slug = basename(untrailingslashit($store->get_shop_url()));
                    if ($slug) {
                        $vendor_terms[] = str_replace(['-', '_'], ' ', $slug);
                    }
                }
            } else {
                $store_slug = get_user_meta($vendor_id, 'wcfmmp_store_slug', true);
                if ($store_slug) {
                    $vendor_terms[] = str_replace(['-', '_'], ' ', (string) $store_slug);
                }
                $store_name = get_user_meta($vendor_id, 'wcfmmp_store_name', true);
                if ($store_name) {
                    $vendor_terms[] = (string) $store_name;
                }
            }
        }

        $vendor_terms = array_unique(array_filter(array_map('trim', $vendor_terms)));
        if (!empty($vendor_terms)) {
            $additional_sources['cv_vendor'] = AWS_Helpers::extract_terms(implode(' ', $vendor_terms), 'cv_vendor');
        }

        if (!empty($additional_sources)) {
            foreach ($additional_sources as $source_key => $terms) {
                if (empty($terms) || !is_array($terms)) {
                    continue;
                }

                if (!isset($data['terms'][$source_key])) {
                    $data['terms'][$source_key] = [];
                }

                foreach ($terms as $term => $count) {
                    if (!is_string($term) || $term === '') {
                        continue;
                    }
                    $existing = $data['terms'][$source_key][$term] ?? 0;
                    $data['terms'][$source_key][$term] = max($existing, max(1, (int) $count));
                }
            }
        }

        return $data;
    };

    add_filter('aws_indexed_data', $callback, 15, 2);
});


