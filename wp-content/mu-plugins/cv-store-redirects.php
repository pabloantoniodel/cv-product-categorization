<?php
/**
 * Plugin Name: CV Store Redirects
 * Description: Gestiona redirecciones 301 para URLs antiguas o mal formadas de las tiendas WCFM (/store/...).
 * Version:     1.0.0
 * Author:      Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Redirecciones manuales de slugs obsoletos o mal escritos.
 *
 * Clave: slug sin barra inicial (ej: 'r2defi')
 * Valor: slug correcto existente (ej: 'r2rdefi')
 *
 * Añadir nuevos pares aquí cuando se detecten 404 en WP Statistics.
 *
 * @return array<string,string>
 */
function cv_store_redirects_manual_map(): array
{
    return [
        'r2defi' => 'r2rdefi',
    ];
}

/**
 * Lista de pestañas soportadas por WCFM Marketplace que pueden recibir redirección directa.
 * Clave = nombre legible usado en antiguos enlaces, valor = query var de WCFM.
 *
 * @return array<string,string>
 */
function cv_store_redirects_supported_tabs(): array
{
    return [
        'about'          => 'about',
        'reviews'        => 'reviews',
        'products'       => 'products',
        'settings'       => 'store-settings',
        'followers'      => 'followers',
        'articles'       => 'articles',
        'coupons'        => 'coupon',
        'collections'    => 'collection',
        'tweets'         => 'tweets',
        'support'        => 'store-support',
        'contact'        => 'store-lists',
        'articles-list'  => 'articles',
        'articles_list'  => 'articles',
        'reviews-list'   => 'reviews',
        'reviews_list'   => 'reviews',
        'followers-list' => 'followers',
        'followers_list' => 'followers',
    ];
}

/**
 * Obtiene el slug base de las tiendas (por defecto `store`).
 */
function cv_store_redirects_get_base_slug(): string
{
    if (!function_exists('wcfm_get_option')) {
        return 'store';
    }
    $base = (string) wcfm_get_option('wcfm_store_url', 'store');
    $base = trim($base, '/');
    return $base !== '' ? $base : 'store';
}

/**
 * Verifica si un slug pertenece a un vendedor activo.
 *
 * @param string $slug
 * @return bool
 */
function cv_store_redirects_slug_exists(string $slug): bool
{
    global $wpdb;

    // Se consulta sobre user_nicename (slug público de WP).
    $user_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} WHERE user_nicename = %s LIMIT 1",
            $slug
        )
    );

    if (!$user_id) {
        return false;
    }

    // Confirmar que tenga capacidad de vendedor.
    $caps = get_user_meta((int) $user_id, $wpdb->get_blog_prefix() . 'capabilities', true);
    if (!is_array($caps)) {
        return false;
    }
    return !empty($caps['wcfm_vendor']);
}

/**
 * Redirige automáticamente URLs de tienda mal formadas.
 */
function cv_store_redirects_maybe_redirect(): void
{
    if (!function_exists('wcfm_get_option')) {
        return;
    }

    if (is_admin()) {
        return;
    }

    $requested_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!$requested_path) {
        return;
    }

    $requested_path = trim($requested_path);
    if ($requested_path === '') {
        return;
    }

    $base_slug = cv_store_redirects_get_base_slug();
    $base_prefix = '/' . $base_slug . '/';

    if (strpos($requested_path . '/', $base_prefix) !== 0) {
        return;
    }

    // Extraer la parte posterior a /store/
    $relative = substr($requested_path, strlen($base_prefix));
    $relative = trim($relative, '/');

    if ($relative === '') {
        return;
    }

    // Separar slug e hito adicional (si existe)
    $parts = explode('/', $relative);
    $slug = strtolower(array_shift($parts));
    $rest = $parts;

    // 1. Redirecciones manuales
    $manual_map = cv_store_redirects_manual_map();
    if (isset($manual_map[$slug])) {
        $target_slug = $manual_map[$slug];
        $target_url = home_url($base_prefix . $target_slug . '/');
        wp_redirect($target_url, 301);
        exit;
    }

    // 2. Si no es un slug válido actual, no continuar (404 permanece)
    if (!cv_store_redirects_slug_exists($slug)) {
        return;
    }

    // 3. Si no hay resto y falta la barra final -> normalizar
    if (empty($rest) && substr($requested_path, -1) !== '/') {
        wp_redirect(home_url($base_prefix . $slug . '/'), 301);
        exit;
    }

    // 4. Gestionar pestañas típicas (/store/slug/about, /store/slug/reviews, etc.)
    if (!empty($rest)) {
        $tab_candidate = strtolower($rest[0]);
        $tabs = cv_store_redirects_supported_tabs();
        if (isset($tabs[$tab_candidate])) {
            $target = home_url($base_prefix . $slug . '/?tab=' . rawurlencode($tabs[$tab_candidate]));
            wp_redirect($target, 301);
            exit;
        }

        // Para rutas antiguas tipo category/... enviamos al root de la tienda
        if (in_array($tab_candidate, ['category', 'collections', 'collection'], true)) {
            wp_redirect(home_url($base_prefix . $slug . '/'), 301);
            exit;
        }

        // Rutas generadas por algunos themes/plugins (newtab, new-tab, etc.)
        $noise = ['newtab', 'new-tab', 'newtab/', 'new-tab/'];
        if (in_array($tab_candidate, $noise, true)) {
            wp_redirect(home_url($base_prefix . $slug . '/'), 301);
            exit;
        }
    }
}
add_action('template_redirect', 'cv_store_redirects_maybe_redirect', 9);


