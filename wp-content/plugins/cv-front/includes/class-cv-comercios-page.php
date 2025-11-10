<?php
/**
 * Listado personalizado de comercios bajo el control de CV Front.
 *
 * @package CV_Front
 * @since 3.5.5
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Comercios_Page {

    const PAGE_SLUG   = 'asociados';
    const OPTION_KEY  = 'cv_comercios_page_id';
    const PER_PAGE    = 12;

    /**
     * Page ID cache.
     *
     * @var int|null
     */
    private $page_id = null;

    public function __construct() {
        add_action('init', array($this, 'register_shortcode'));
        add_action('init', array($this, 'maybe_create_page'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function register_shortcode() {
        add_shortcode('cv_comercios', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts = array(), $content = '') {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        global $WCFMmp;
        $has_instance = function_exists('wcfmmp');
        if (!$has_instance && class_exists('WCFMmp')) {
            $has_instance = ($WCFMmp instanceof WCFMmp);
        }
        if (!$has_instance) {
            return '<p class="cv-comercios__notice">El módulo de comercios requiere WCFM Marketplace activo.</p>';
        }

        $wcfmmp_instance = null;
        if (function_exists('wcfmmp')) {
            $wcfmmp_instance = wcfmmp();
        } elseif (class_exists('WCFMmp') && ($WCFMmp instanceof WCFMmp)) {
            $wcfmmp_instance = $WCFMmp;
        }

        if (!$wcfmmp_instance || !isset($wcfmmp_instance->wcfmmp_vendor)) {
            return '<p class="cv-comercios__notice">No se pudo inicializar WCFM Marketplace correctamente.</p>';
        }

        $marketplace_options = isset($wcfmmp_instance->wcfmmp_marketplace_options) ? $wcfmmp_instance->wcfmmp_marketplace_options : array();
        $max_radius_to_search = isset($marketplace_options['max_radius_to_search']) ? (float) $marketplace_options['max_radius_to_search'] : 100.0;
        $radius_unit_option   = isset($marketplace_options['radius_unit']) ? (string) $marketplace_options['radius_unit'] : 'km';
        $radius_start_divisor = (float) apply_filters('wcfmmp_radius_filter_start_distance', 10);
        if ($radius_start_divisor <= 0) {
            $radius_start_divisor = 10.0;
        }

        $radius_default_value = $max_radius_to_search / $radius_start_divisor;
        $radius_icon_url      = trailingslashit($wcfmmp_instance->plugin_url) . 'assets/images/locate.svg';
        $radius_unit_label    = $this->get_radius_unit_label($radius_unit_option);

        $paged = get_query_var('paged');
        if (!$paged) {
            $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        }
        $paged = max(1, $paged);

        $search_term = isset($_GET['cv_search']) ? sanitize_text_field(wp_unslash($_GET['cv_search'])) : '';
        $order_provided = isset($_GET['order']);
        $default_order  = 'newness_desc';
        $alt_order      = 'distance_asc';
        $order_select   = $order_provided ? sanitize_text_field(wp_unslash($_GET['order'])) : $alt_order;
        if (!in_array($order_select, array('newness_desc', 'newness_asc', 'alphabetical_asc', 'alphabetical_desc', 'rating_desc', 'distance_asc'), true)) {
            $order_select = $alt_order;
        }

        $geo_lat_raw    = isset($_GET['wcfmmp_radius_lat']) ? wp_unslash($_GET['wcfmmp_radius_lat']) : null;
        $geo_lng_raw    = isset($_GET['wcfmmp_radius_lng']) ? wp_unslash($_GET['wcfmmp_radius_lng']) : null;
        $geo_range_raw  = isset($_GET['wcfmmp_radius_range']) ? wp_unslash($_GET['wcfmmp_radius_range']) : null;

        $geo_lat    = (null !== $geo_lat_raw && $geo_lat_raw !== '') ? (float) $geo_lat_raw : null;
        $geo_lng    = (null !== $geo_lng_raw && $geo_lng_raw !== '') ? (float) $geo_lng_raw : null;
        $geo_radius = (null !== $geo_range_raw && $geo_range_raw !== '') ? (float) $geo_range_raw : null;

        if ($geo_lat !== null && abs($geo_lat) < 0.000001) {
            $geo_lat = null;
        }
        if ($geo_lng !== null && abs($geo_lng) < 0.000001) {
            $geo_lng = null;
        }

        if (null === $geo_radius && isset($_COOKIE['cv_geo_radius_wcfm'])) {
            $geo_radius = (float) $_COOKIE['cv_geo_radius_wcfm'];
        }
        if ($geo_radius !== null && $geo_radius <= 0) {
            $geo_radius = null;
        }
        $geo_unit = isset($_COOKIE['cv_geo_unit']) ? sanitize_text_field($_COOKIE['cv_geo_unit']) : 'km';

        $geo_active = ($geo_lat !== null && $geo_lng !== null && $geo_radius !== null);

        if (!$geo_active && $order_select === $alt_order) {
            $order_select = $default_order;
        }

        if (!$geo_active && !$order_provided) {
            $order_select = $default_order;
        }

        $per_page = apply_filters('cv_comercios_per_page', self::PER_PAGE);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[CV Comercios] per_page (raw) = ' . var_export($per_page, true));
            error_log(sprintf('[CV Comercios] per_page filter result: %s', var_export($per_page, true)));
        }
        $offset   = ($paged - 1) * $per_page;

        $vendor_handler = $wcfmmp_instance->wcfmmp_vendor;

        $all_vendor_ids = $vendor_handler->wcfmmp_get_vendor_list(
            true,
            '',
            '',
            '',
            array(),
            'DESC',
            'newness_desc',
            array(),
            '',
            ''
        );

        $store_rows_all = array();
        if (!empty($all_vendor_ids)) {
            foreach ($all_vendor_ids as $vendor_id => $unused) {
                $store = wcfmmp_get_store($vendor_id);
                if (!$store) {
                    continue;
                }
                $row = $this->prepare_store_row($store);
                if ($row) {
                    $store_rows_all[] = $row;
                }
            }
        }

        if ($search_term) {
            $search_lower = $this->normalize_string($search_term);
            $store_rows_all = array_filter($store_rows_all, function ($row) use ($search_lower) {
                return strpos($row['search_blob'], $search_lower) !== false;
            });
        }

        $store_rows_all = array_values($store_rows_all);

        if ($geo_active) {
            $store_rows_all = $this->apply_geo_filter($store_rows_all, $geo_lat, $geo_lng, $geo_radius, $geo_unit);
        }

        $store_rows_all = $this->sort_store_rows($store_rows_all, $order_select);

        $total_count = count($store_rows_all);
        $total_pages = max(1, (int) ceil($total_count / $per_page));

        $store_rows_page = array_slice($store_rows_all, $offset, $per_page);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[CV Comercios] slice debug: total=%d offset=%d per_page=%s result=%d', count($store_rows_all), $offset, var_export($per_page, true), count($store_rows_page)));
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[CV Comercios] total=%d, page=%d, per_page=%d, page_count=%d',
                $total_count,
                $paged,
                $per_page,
                count($store_rows_page)
            ));
            error_log(sprintf('[CV Comercios] slice_offset=%d, slice_count=%d', $offset, count($store_rows_page)));
        }

        $pagination_links = paginate_links(array(
            'base'      => add_query_arg('paged', '%#%'),
            'format'    => '',
            'current'   => $paged,
            'total'     => $total_pages,
            'add_args'  => array_filter(array(
                'cv_search'           => $search_term ? $search_term : null,
                'order'               => ($order_select !== $default_order) ? $order_select : null,
                'wcfmmp_radius_lat'   => $geo_active ? $geo_lat : null,
                'wcfmmp_radius_lng'   => $geo_active ? $geo_lng : null,
                'wcfmmp_radius_range' => $geo_active ? $geo_radius : null,
            )),
            'type'      => 'array',
        ));

        $view_path = CV_FRONT_PLUGIN_DIR . 'views/comercios/listing.php';

        $data = array(
            'store_rows'         => $store_rows_page,
            'store_rows_total'   => $store_rows_all,
            'search_term'        => $search_term,
            'pagination_links'   => $pagination_links,
            'total_count'        => $total_count,
            'paged'              => $paged,
            'total_pages'        => $total_pages,
            'per_page'           => $per_page,
            'page_url'           => $this->get_page_url(),
            'order_select'       => $order_select,
            'geo_active'         => $geo_active,
            'geo_params'         => $geo_active ? array(
                'lat'        => $geo_lat,
                'lng'        => $geo_lng,
                'lat_raw'    => $geo_lat_raw,
                'lng_raw'    => $geo_lng_raw,
                'radius'     => $geo_radius,
                'radius_raw' => $geo_range_raw,
                'unit'       => $geo_unit,
            ) : null,
            'radius_config'      => array(
                'max'            => $max_radius_to_search,
                'unit'           => $radius_unit_option,
                'unit_label'     => $radius_unit_label,
                'start_divisor'  => $radius_start_divisor,
                'default'        => $radius_default_value,
                'icon_url'       => $radius_icon_url,
            ),
        );

        ob_start();
        if (file_exists($view_path)) {
            extract($data, EXTR_SKIP);
            include $view_path;
        } else {
            echo '<pre>';
            print_r($data);
            echo '</pre>';
        }
        return ob_get_clean();
    }

    public function enqueue_assets() {
        if (!$this->is_comercios_context()) {
            return;
        }

        wp_enqueue_style(
            'cv-comercios-listing',
            CV_FRONT_PLUGIN_URL . 'assets/css/comercios.css',
            array(),
            CV_FRONT_VERSION
        );
    }

    public function maybe_create_page() {
        $page_id = $this->get_page_id();
        if ($page_id) {
            $this->maybe_update_page_slug($page_id);
            return;
        }

        $existing = get_page_by_path(self::PAGE_SLUG);
        if ($existing) {
            $this->maybe_update_page_slug($existing->ID);
            update_option(self::OPTION_KEY, $existing->ID);
            $this->page_id = (int) $existing->ID;
            return;
        }

        // Migrar página legacy con slug anterior.
        $legacy = get_page_by_path('comercios-cv');
        if ($legacy) {
            $this->maybe_update_page_slug($legacy->ID);
            update_option(self::OPTION_KEY, $legacy->ID);
            $this->page_id = (int) $legacy->ID;
            return;
        }

        $page_data = array(
            'post_title'   => __('Asociados', 'cv-front'),
            'post_name'    => self::PAGE_SLUG,
            'post_content' => '[cv_comercios]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        $new_id = wp_insert_post($page_data);
        if (!is_wp_error($new_id) && $new_id) {
            update_option(self::OPTION_KEY, $new_id);
            $this->page_id = (int) $new_id;
        }
    }

    private function maybe_update_page_slug($page_id) {
        $page = get_post($page_id);
        if (!$page || $page->post_status === 'trash') {
            return;
        }

        $needs_update = false;
        $update_args  = array('ID' => $page->ID);

        if ($page->post_name !== self::PAGE_SLUG) {
            $update_args['post_name'] = self::PAGE_SLUG;
            $needs_update = true;
        }

        if ($page->post_title === 'Comercios (Nuevo)' || $page->post_title === 'Comercios') {
            $update_args['post_title'] = __('Asociados', 'cv-front');
            $needs_update = true;
        }

        if ($needs_update) {
            wp_update_post($update_args);
        }
    }

    private function get_page_id() {
        if (null !== $this->page_id) {
            return $this->page_id;
        }
        $stored = get_option(self::OPTION_KEY);
        if ($stored) {
            $page = get_post($stored);
            if ($page && $page->post_type === 'page' && $page->post_status !== 'trash') {
                $this->page_id = (int) $stored;
                return $this->page_id;
            }
        }
        return 0;
    }

    private function get_page_url() {
        $page_id = $this->get_page_id();
        if ($page_id) {
            return get_permalink($page_id);
        }
        return home_url('/' . self::PAGE_SLUG . '/');
    }

    private function is_comercios_context() {
        if (is_page(self::PAGE_SLUG)) {
            return true;
        }

        if (is_singular()) {
            $post = get_post();
            if ($post && has_shortcode($post->post_content, 'cv_comercios')) {
                return true;
            }
        }

        return false;
    }

    private function prepare_store_row($store) {
        if (!$store) {
            return null;
        }

        $store_id   = $store->get_id();
        $store_name = $store->get_shop_name();
        if (!$store_name) {
            $store_name = $store->get_name();
        }

        $store_url = '';
        if (function_exists('wcfmmp_get_store_url')) {
            $store_url = trailingslashit(wcfmmp_get_store_url($store_id));
        }
        if (!$store_url && method_exists($store, 'get_shop_url')) {
            $store_url = $store->get_shop_url();
        }
        if (!$store_url && method_exists($store, 'get_store_url')) {
            $store_url = $store->get_store_url();
        }
        $store_banner = $store->get_list_banner();
        if (!$store_banner) {
            $store_banner = $store->get_banner();
        }

        $store_avatar = $store->get_avatar();
        if (!$store_avatar) {
            $store_avatar = get_avatar_url($store_id, array('size' => 220));
        }

        $store_excerpt = wp_strip_all_tags($store->get_shop_description());

        $store_address = '';
        if (method_exists($store, 'get_address_string')) {
            $store_address = $store->get_address_string();
        }
        if (!$store_address && method_exists($store, 'get_formatted_address')) {
            $store_address = $store->get_formatted_address();
        }
        $store_address = $store_address ? wp_strip_all_tags($store_address) : '';

        $store_phone      = $store->get_phone();
        $store_phone_href = $store_phone ? preg_replace('/[^0-9\+]/', '', $store_phone) : '';

        $store_email = '';
        if (method_exists($store, 'show_email') && 'yes' === $store->show_email()) {
            $store_email = $store->get_email();
        }

        $store_categories = $this->get_store_category_names($store_id);

        $store_rating       = method_exists($store, 'get_avg_review_rating') ? (float) $store->get_avg_review_rating() : 0.0;
        $store_review_count = method_exists($store, 'get_total_review_count') ? (int) $store->get_total_review_count() : 0;
        $rating_percent     = $store_rating > 0 ? max(0, min(100, ($store_rating / 5) * 100)) : 0;

        $user               = get_userdata($store_id);
        $register_timestamp = $user ? strtotime($user->user_registered) : 0;

        $coordinates = $this->get_store_coordinates($store_id);
        $store_lat   = $coordinates['lat'];
        $store_lng   = $coordinates['lng'];

        $search_parts = array_filter(array(
            $store_name,
            $store_excerpt,
            $store_address,
            $store_phone,
            $store_email,
            implode(' ', $store_categories),
        ));
        $search_blob = $this->normalize_string(implode(' ', $search_parts));

        return array(
            'store'              => $store,
            'id'                 => $store_id,
            'name'               => $store_name,
            'url'                => $store_url,
            'avatar'             => $store_avatar,
            'banner'             => $store_banner,
            'excerpt'            => $store_excerpt,
            'address'            => $store_address,
            'phone'              => $store_phone,
            'phone_href'         => $store_phone_href,
            'email'              => $store_email,
            'categories'         => $store_categories,
            'rating'             => $store_rating,
            'rating_percent'     => $rating_percent,
            'review_count'       => $store_review_count,
            'register_timestamp' => $register_timestamp,
            'lat'                => $store_lat,
            'lng'                => $store_lng,
            'distance_km'        => null,
            'distance_display'   => '',
            'search_blob'        => $search_blob,
        );
    }

    private function normalize_string($value) {
        $value = wp_strip_all_tags((string) $value);
        if (function_exists('remove_accents')) {
            $value = remove_accents($value);
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }
        return strtolower($value);
    }

    private function apply_geo_filter($rows, $user_lat, $user_lng, $radius, $unit = 'km') {
        if (empty($rows)) {
            return $rows;
        }
        if ($unit === 'm') {
            $radius = $radius / 1000;
        }

        $filtered = array();
        foreach ($rows as $row) {
            if ($row['lat'] === null || $row['lng'] === null) {
                continue;
            }
            $distance = $this->calculate_distance_km($user_lat, $user_lng, $row['lat'], $row['lng']);
            if ($distance <= $radius) {
                $row['distance_km']      = $distance;
                $row['distance_display'] = $this->format_distance($distance, $unit);
                $filtered[]              = $row;
            }
        }

        return $filtered;
    }

    private function calculate_distance_km($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius * $c;
    }

    private function format_distance($distance_km, $unit) {
        if ($unit === 'm') {
            $meters = $distance_km * 1000;
            if ($meters < 1000) {
                return round($meters) . ' m';
            }
            return number_format_i18n($distance_km, 2) . ' km';
        }

        if ($distance_km < 1) {
            return number_format_i18n($distance_km * 1000, 0) . ' m';
        }

        return number_format_i18n($distance_km, 2) . ' km';
    }

    private function sort_store_rows($rows, $order_select) {
        if (empty($rows)) {
            return $rows;
        }

        switch ($order_select) {
            case 'newness_asc':
                usort($rows, function ($a, $b) {
                    return $a['register_timestamp'] <=> $b['register_timestamp'];
                });
                break;
            case 'alphabetical_asc':
                usort($rows, function ($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });
                break;
            case 'alphabetical_desc':
                usort($rows, function ($a, $b) {
                    return strcasecmp($b['name'], $a['name']);
                });
                break;
            case 'rating_desc':
                usort($rows, function ($a, $b) {
                    $a_rating = (float) $a['rating'];
                    $b_rating = (float) $b['rating'];

                    $a_has_rating = $a_rating > 0;
                    $b_has_rating = $b_rating > 0;

                    if ($a_has_rating && $b_has_rating) {
                        if ($a_rating === $b_rating) {
                            return strcasecmp($a['name'], $b['name']);
                        }
                        return ($a_rating < $b_rating) ? 1 : -1;
                    }

                    if ($a_has_rating && !$b_has_rating) {
                        return -1;
                    }

                    if (!$a_has_rating && $b_has_rating) {
                        return 1;
                    }

                    return strcasecmp($a['name'], $b['name']);
                });
                break;
            case 'distance_asc':
                usort($rows, function ($a, $b) {
                    $a_distance = $a['distance_km'];
                    $b_distance = $b['distance_km'];

                    if ($a_distance === null && $b_distance === null) {
                        return 0;
                    }
                    if ($a_distance === null) {
                        return 1;
                    }
                    if ($b_distance === null) {
                        return -1;
                    }

                    if ($a_distance === $b_distance) {
                        return strcasecmp($a['name'], $b['name']);
                    }

                    return ($a_distance < $b_distance) ? -1 : 1;
                });
                break;
            case 'newness_desc':
            default:
                usort($rows, function ($a, $b) {
                    return $b['register_timestamp'] <=> $a['register_timestamp'];
                });
                break;
        }

        return $rows;
    }

    private function get_store_coordinates($store_id) {
        $lat = get_user_meta($store_id, '_wcfm_store_lat', true);
        $lng = get_user_meta($store_id, '_wcfm_store_lng', true);

        if (!$lat) {
            $lat = get_user_meta($store_id, '_store_lat', true);
        }
        if (!$lng) {
            $lng = get_user_meta($store_id, '_store_lng', true);
        }

        $profile = get_user_meta($store_id, 'wcfmmp_profile_settings', true);
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

        $lat = $this->sanitize_coordinate($lat);
        $lng = $this->sanitize_coordinate($lng);

        if ($lat === null || $lng === null) {
            return array(
                'lat' => null,
                'lng' => null,
            );
        }

        return array(
            'lat' => $lat,
            'lng' => $lng,
        );
    }

    private function get_radius_unit_label($unit) {
        $unit = strtolower((string) $unit);
        switch ($unit) {
            case 'km':
                return __('Km', 'wc-multivendor-marketplace');
            case 'mile':
            case 'miles':
                return __('Miles', 'wc-multivendor-marketplace');
            case 'm':
                return __('m', 'wc-multivendor-marketplace');
            default:
                return ucfirst($unit);
        }
    }

    private function sanitize_coordinate($value) {
        if ($value === '' || $value === null) {
            return null;
        }
        $value = (float) $value;
        if (abs($value) < 0.000001) {
            return null;
        }
        return $value;
    }

    private function get_store_category_names($store_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcfm_marketplace_store_taxonomies';
        $term_ids = $wpdb->get_col($wpdb->prepare("SELECT term FROM {$table} WHERE vendor_id = %d", $store_id));
        if (empty($term_ids)) {
            return array();
        }

        $term_ids = array_unique(array_map('intval', $term_ids));
        $names    = array();

        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $names[] = $term->name;
            }
        }

        if (!empty($names)) {
            natcasesort($names);
            $names = array_values($names);
        }

        return $names;
    }
}

