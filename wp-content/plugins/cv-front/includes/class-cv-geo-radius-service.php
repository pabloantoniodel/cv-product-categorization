<?php

if (!defined('ABSPATH')) {
    exit;
}

class CV_Geo_Radius_Service {
    private static $localized = false;

    public static function init() {
        add_action('wp_ajax_cv_geo_radius_summary', array(__CLASS__, 'handle_summary'));
        add_action('wp_ajax_nopriv_cv_geo_radius_summary', array(__CLASS__, 'handle_summary'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'localize_script'), 20);
    }

    public static function localize_script() {
        if (self::$localized) {
            return;
        }
        if (!wp_script_is('cv-radius-dialog', 'enqueued')) {
            return;
        }

        $data = array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('cv_radius_summary'),
            'leafletCss'=> 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            'leafletJs' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        );

        wp_localize_script('cv-radius-dialog', 'CV_RADIUS_DIALOG_AJAX', $data);

        if (!wp_style_is('cv-radius-dialog-styles', 'enqueued')) {
            $dialog_css = CV_FRONT_PLUGIN_DIR . 'assets/css/cv-radius-dialog.css';
            wp_enqueue_style(
                'cv-radius-dialog-styles',
                CV_FRONT_PLUGIN_URL . 'assets/css/cv-radius-dialog.css',
                array(),
                file_exists($dialog_css) ? filemtime($dialog_css) : CV_FRONT_VERSION
            );
        }

        self::$localized = true;
    }

    public static function handle_summary() {
        $nonce_valid = check_ajax_referer('cv_radius_summary', 'nonce', false);

        if (!$nonce_valid) {
            $referer = wp_get_referer();
            $host_ok = false;

            if ($referer) {
                $referer_host = wp_parse_url($referer, PHP_URL_HOST);
                $site_host    = wp_parse_url(home_url(), PHP_URL_HOST);
                if ($referer_host && $site_host && strcasecmp($referer_host, $site_host) === 0) {
                    $host_ok = true;
                }
            }

            if (!$host_ok && isset($_SERVER['HTTP_ORIGIN'])) {
                $origin_host = wp_parse_url(sanitize_text_field(wp_unslash($_SERVER['HTTP_ORIGIN'])), PHP_URL_HOST);
                $site_host   = wp_parse_url(home_url(), PHP_URL_HOST);
                if ($origin_host && $site_host && strcasecmp($origin_host, $site_host) === 0) {
                    $host_ok = true;
                }
            }

            if (!$host_ok) {
                wp_send_json_error(array('message' => __('Nonce inválido', 'cv-front')), 403);
            }
        }

        $lat    = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
        $lng    = isset($_POST['lng']) ? (float) $_POST['lng'] : null;
        $radius = isset($_POST['radius']) ? (float) $_POST['radius'] : null;
        $unit   = isset($_POST['unit']) ? strtolower(sanitize_text_field(wp_unslash($_POST['unit']))) : 'km';
        $context = isset($_POST['context']) ? sanitize_text_field(wp_unslash($_POST['context'])) : 'stores';
        $detailed = isset($_POST['detailed']) && $_POST['detailed'] === '1';
        $min_count = isset($_POST['min_count']) ? max(0, (int) $_POST['min_count']) : 0;
        $bootstrap = isset($_POST['bootstrap']) && $_POST['bootstrap'] === '1';

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[CV Geo Radius] request lat=%s lng=%s radius=%s unit=%s context=%s detailed=%s min_count=%d bootstrap=%s',
                $lat !== null ? $lat : 'null',
                $lng !== null ? $lng : 'null',
                $radius !== null ? $radius : 'null',
                $unit,
                $context,
                $detailed ? '1' : '0',
                $min_count,
                $bootstrap ? '1' : '0'
            ));
        }

        if ($bootstrap && $min_count < 10) {
            $min_count = 10;
        }

        if ($lat === null || $lng === null || $radius === null || $radius <= 0) {
            wp_send_json_error(array('message' => __('Parámetros incompletos', 'cv-front')), 400);
        }

        if ($unit === 'm') {
            $radius = $radius / 1000.0;
        }

        $radius = min($radius, 2000); // seguridad

        $result = self::query_stores_within_radius($lat, $lng, $radius, $detailed, $min_count, 1200, $bootstrap);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[CV Geo Radius] response count=%d radius_used=%s context=%s',
                isset($result['count']) ? (int) $result['count'] : -1,
                isset($result['radius_used']) ? $result['radius_used'] : 'null',
                $context
            ));
        }

        wp_send_json_success(array(
            'count'  => $result['count'],
            'stores' => $result['stores'],
            'radius_used' => $result['radius_used'],
        ));
    }

    private static function query_stores_within_radius($user_lat, $user_lng, $radius_km, $include_stores = false, $min_count = 0, $max_radius = 1200, $bootstrap = false) {
        $response = array(
            'count'  => 0,
            'stores' => array(),
            'radius_used' => $radius_km,
        );

        $wcfmmp_instance = function_exists('wcfmmp') ? wcfmmp() : null;
        if (!$wcfmmp_instance && isset($GLOBALS['WCFMmp']) && is_object($GLOBALS['WCFMmp'])) {
            $wcfmmp_instance = $GLOBALS['WCFMmp'];
        }
        if (!$wcfmmp_instance || !isset($wcfmmp_instance->wcfmmp_vendor)) {
            return $response;
        }

        $vendor_handler = $wcfmmp_instance->wcfmmp_vendor;
        $all_vendor_ids = $vendor_handler->wcfmmp_get_vendor_list( true );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $count_ids = is_array($all_vendor_ids) ? count($all_vendor_ids) : 0;
            error_log(sprintf('[CV Geo Radius] total vendors recuperados: %d', $count_ids));
        }

        if (empty($all_vendor_ids)) {
            return $response;
        }

        $entries = self::collect_vendor_entries($all_vendor_ids, $user_lat, $user_lng);
        if (empty($entries)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CV Geo Radius] sin entradas válidas para los vendedores');
            }
            return $response;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[CV Geo Radius] entradas con coordenadas: %d', count($entries)));
        }

        usort($entries, function ($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });

        $current_radius = max(0, $radius_km);
        if ($bootstrap && $current_radius < 2) {
            $current_radius = 2;
        }

        $filtered = self::filter_entries_by_radius($entries, $current_radius);
        $count = count($filtered);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[CV Geo Radius] filtrados radio=%.3f => %d', $current_radius, $count));
        }

        if ($bootstrap && $count === 0 && $current_radius < 50) {
            $current_radius = 50;
            $filtered = self::filter_entries_by_radius($entries, $current_radius);
            $count = count($filtered);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[CV Geo Radius] bootstrap etapa 50km => %d', $count));
            }
        }

        if ($min_count > 0 && $count < $min_count && $current_radius < $max_radius) {
            $attempts = 0;
            while ($count < $min_count && $current_radius < $max_radius && $attempts < 6) {
                $attempts++;
                $current_radius = min($current_radius * 1.8, $max_radius);
                $filtered = self::filter_entries_by_radius($entries, $current_radius);
                $count = count($filtered);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('[CV Geo Radius] bootstrap expand intento %d radio=%.3f => %d', $attempts, $current_radius, $count));
                }
            }
        }

        $response['count'] = $count;
        $response['radius_used'] = $current_radius;

        if ($include_stores && $filtered) {
            $max_items = 250;
            if (count($filtered) > $max_items) {
                $filtered = array_slice($filtered, 0, $max_items);
            }
            $response['stores'] = $filtered;
        }

        return $response;
    }

    private static function collect_vendor_entries($all_vendor_ids, $user_lat, $user_lng) {
        $entries = array();
        $processed = array();
        $total_candidates = 0;
        $duplicates = 0;
        $no_products = 0;
        $no_coords = 0;

        foreach ($all_vendor_ids as $vendor_key => $vendor_value) {
            $vendor_id = null;

            if (is_array($vendor_value)) {
                if (isset($vendor_value['vendor_id'])) {
                    $vendor_id = (int) $vendor_value['vendor_id'];
                } elseif (isset($vendor_value['ID'])) {
                    $vendor_id = (int) $vendor_value['ID'];
                } elseif (isset($vendor_value['id'])) {
                    $vendor_id = (int) $vendor_value['id'];
                }
            } elseif (is_numeric($vendor_value)) {
                $vendor_id = (int) $vendor_value;
            }

            if ($vendor_id === null || $vendor_id <= 0) {
                if (is_numeric($vendor_key)) {
                    $vendor_id = (int) $vendor_key;
                } elseif (is_string($vendor_key) && is_numeric($vendor_key)) {
                    $vendor_id = (int) $vendor_key;
                }
            }

            $vendor_id = absint($vendor_id);
            if (!$vendor_id) {
                continue;
            }
            $total_candidates++;
            if (isset($processed[$vendor_id])) {
                $duplicates++;
                continue;
            }
            $processed[$vendor_id] = true;

            if (!self::vendor_has_products($vendor_id)) {
                $no_products++;
                continue;
            }

            $coordinates = self::get_store_coordinates($vendor_id);
            if ($coordinates['lat'] === null || $coordinates['lng'] === null) {
                $no_coords++;
                continue;
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                static $logged_vendors = 0;
                if ($logged_vendors < 10) {
                    error_log(sprintf(
                        '[CV Geo Radius] vendor %d coords lat=%s lng=%s',
                        $vendor_id,
                        $coordinates['lat'],
                        $coordinates['lng']
                    ));
                    $logged_vendors++;
                }
            }

            $distance = self::calculate_distance_km($user_lat, $user_lng, $coordinates['lat'], $coordinates['lng']);

            $entries[] = array(
                'id'           => $vendor_id,
                'name'         => self::get_store_name($vendor_id),
                'url'          => self::get_store_url($vendor_id),
                'lat'          => $coordinates['lat'],
                'lng'          => $coordinates['lng'],
                'distance_km'  => round($distance, 3),
                'distance_txt' => self::format_distance($distance),
            );
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[CV Geo Radius] resumen vendors candidatos=%d añadidos=%d duplicados=%d sin_productos=%d sin_coords=%d',
                $total_candidates,
                count($entries),
                $duplicates,
                $no_products,
                $no_coords
            ));
        }

        return $entries;
    }

    private static function filter_entries_by_radius($entries, $radius_km) {
        if ($radius_km <= 0) {
            return array();
        }
        $filtered = array();
        foreach ($entries as $entry) {
            if ($entry['distance_km'] <= $radius_km) {
                $filtered[] = $entry;
            }
        }
        return $filtered;
    }

    private static function vendor_has_products($vendor_id) {
        static $cache = array();

        $vendor_id = (int) $vendor_id;
        if ($vendor_id <= 0) {
            return false;
        }

        if (isset($cache[$vendor_id])) {
            return $cache[$vendor_id];
        }

        $has_products = false;

        if (function_exists('wcfm_get_user_posts_count')) {
            $status = apply_filters('wcfm_limit_check_status', 'publish');
            $count  = (int) wcfm_get_user_posts_count($vendor_id, 'product', $status);
            if ($count > 0) {
                $has_products = true;
            }
        }

        if (!$has_products) {
            $meta_count = (int) get_user_meta($vendor_id, '_wcfmmp_total_products', true);
            if ($meta_count > 0) {
                $has_products = true;
            }
        }

        if (!$has_products) {
            $excluded_slugs = apply_filters('cv_comercios_excluded_product_slugs', array('ticket-de-compra'));
            $excluded_ids   = array();
            if (!empty($excluded_slugs) && is_array($excluded_slugs)) {
                foreach ($excluded_slugs as $slug) {
                    $product = get_page_by_path(sanitize_title($slug), OBJECT, 'product');
                    if ($product instanceof WP_Post) {
                        $excluded_ids[] = (int) $product->ID;
                    }
                }
            }

            $status_list = apply_filters('cv_geo_radius_product_statuses', array('publish'));
            if (!is_array($status_list) || empty($status_list)) {
                $status_list = array('publish');
            }

            $products = get_posts(array(
                'post_type'      => 'product',
                'post_status'    => $status_list,
                'author'         => $vendor_id,
                'fields'         => 'ids',
                'posts_per_page' => 2,
            ));

            if (!empty($products)) {
                if (!empty($excluded_ids)) {
                    $products = array_diff($products, $excluded_ids);
                }
                if (!empty($products)) {
                    $has_products = true;
                }
            }
        }

        $cache[$vendor_id] = (bool) apply_filters('cv_geo_radius_vendor_has_products', $has_products, $vendor_id);

        return $cache[$vendor_id];
    }

    private static function get_store_name($vendor_id) {
        if (!function_exists('wcfmmp_get_store')) {
            $user = get_userdata($vendor_id);
            return $user ? $user->display_name : '';
        }

        $store = wcfmmp_get_store($vendor_id);
        if (!$store) {
            $user = get_userdata($vendor_id);
            return $user ? $user->display_name : '';
        }

        $name = $store->get_shop_name();
        if (!$name) {
            $name = $store->get_name();
        }
        return $name ? wp_strip_all_tags($name) : '';
    }

    private static function get_store_url($vendor_id) {
        if (function_exists('wcfmmp_get_store_url')) {
            $url = trailingslashit(wcfmmp_get_store_url($vendor_id));
            if ($url) {
                return $url;
            }
        }

        $store = function_exists('wcfmmp_get_store') ? wcfmmp_get_store($vendor_id) : null;
        if ($store) {
            if (method_exists($store, 'get_shop_url')) {
                $url = $store->get_shop_url();
                if ($url) {
                    return $url;
                }
            }
            if (method_exists($store, 'get_store_url')) {
                $url = $store->get_store_url();
                if ($url) {
                    return $url;
                }
            }
        }

        return '';
    }

    private static function get_store_coordinates($vendor_id) {
        $lat = get_user_meta($vendor_id, '_wcfm_store_lat', true);
        $lng = get_user_meta($vendor_id, '_wcfm_store_lng', true);

        if (!$lat) {
            $lat = get_user_meta($vendor_id, '_store_lat', true);
        }
        if (!$lng) {
            $lng = get_user_meta($vendor_id, '_store_lng', true);
        }

        $profile = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
        if (!$lat && is_array($profile) && !empty($profile['map']['store_lat'])) {
            $lat = $profile['map']['store_lat'];
        } elseif (!$lat && is_array($profile) && !empty($profile['store_lat'])) {
            $lat = $profile['store_lat'];
        }

        if (!$lng && is_array($profile) && !empty($profile['map']['store_lng'])) {
            $lng = $profile['map']['store_lng'];
        } elseif (!$lng && is_array($profile) && !empty($profile['store_lng'])) {
            $lng = $profile['store_lng'];
        }

        $lat = self::sanitize_coordinate($lat);
        $lng = self::sanitize_coordinate($lng);

        return array(
            'lat' => $lat,
            'lng' => $lng,
        );
    }

    private static function sanitize_coordinate($value) {
        if ($value === '' || $value === null) {
            return null;
        }
        $value = (float) $value;
        if (abs($value) < 0.000001) {
            return null;
        }
        return $value;
    }

    private static function calculate_distance_km($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius * $c;
    }

    private static function format_distance($distance_km) {
        if ($distance_km < 1) {
            return number_format_i18n($distance_km * 1000, 0) . ' m';
        }
        return number_format_i18n($distance_km, 2) . ' km';
    }
}

CV_Geo_Radius_Service::init();

