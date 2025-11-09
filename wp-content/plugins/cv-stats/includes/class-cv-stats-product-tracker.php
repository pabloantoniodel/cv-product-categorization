<?php
/**
 * Rastreador de Productos
 * 
 * Trackea:
 * - Productos creados hoy
 * - Productos actualizados hoy
 * - Quién los creó/modificó
 * 
 * @package CV_Stats
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Stats_Product_Tracker {
    
    /**
     * Cache del término raíz de Sector.
     *
     * @var int|null
     */
    private static ?int $sector_root_id = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hook para productos nuevos (cuando se publica por primera vez)
        add_action('transition_post_status', array($this, 'track_product_creation'), 10, 3);
        
        // Hook para productos actualizados
        add_action('post_updated', array($this, 'track_product_update'), 10, 3);
    }
    
    /**
     * Trackear creación de productos
     */
    public function track_product_creation($new_status, $old_status, $post) {
        // Solo trackear productos
        if ($post->post_type !== 'product') {
            return;
        }
        
        // Solo si pasa de draft/pending a publish
        if ($new_status === 'publish' && ($old_status === 'draft' || $old_status === 'pending' || $old_status === 'auto-draft')) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'cv_product_activities';
            $category_snapshot = self::get_category_snapshot_json((int) $post->ID);
            
            // Obtener usuario actual (quien creó el producto)
            $created_by = get_current_user_id();
            if (!$created_by) {
                $created_by = $post->post_author; // Fallback al autor del post
            }
            
            // Verificar si ya existe un registro para este producto HOY
            $today_start = date('Y-m-d 00:00:00');
            $existing = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$table_name}
                WHERE product_id = %d
                AND activity_type = 'created'
                AND activity_time >= %s
            ", $post->ID, $today_start));
            
            // Si no existe, insertar
            if (!$existing) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'product_id' => $post->ID,
                        'vendor_id' => $post->post_author,
                        'activity_type' => 'created',
                        'modified_by' => $created_by,
                        'activity_time' => current_time('mysql'),
                        'ip_address' => $this->get_ip_address(),
                        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                        'category_snapshot' => $category_snapshot,
                    ),
                    array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s')
                );
                
                error_log("✅ CV Stats: Producto creado trackeado - ID: {$post->ID}, Vendedor: {$post->post_author}, Creado por: {$created_by}");
            }
        }
    }
    
    /**
     * Trackear actualización de productos
     */
    public function track_product_update($post_id, $post_after, $post_before) {
        // Solo trackear productos publicados
        if ($post_after->post_type !== 'product' || $post_after->post_status !== 'publish') {
            return;
        }
        
        // Si es la misma fecha de modificación, no trackear (evita duplicados en el mismo request)
        if ($post_after->post_modified === $post_before->post_modified) {
            return;
        }
        
        // No trackear si acabamos de crear el producto (evita doble registro)
        $created_recently = (strtotime($post_after->post_date) > (time() - 10)); // 10 segundos
        if ($created_recently) {
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cv_product_activities';
        $category_snapshot = self::get_category_snapshot_json((int) $post_id);
        
        // Obtener usuario actual (quien modificó el producto)
        $modified_by = get_current_user_id();
        if (!$modified_by) {
            $modified_by = $post_after->post_author; // Fallback al autor
        }
        
        // Insertar actividad de actualización
        $wpdb->insert(
            $table_name,
            array(
                'product_id' => $post_id,
                'vendor_id' => $post_after->post_author,
                'activity_type' => 'updated',
                'modified_by' => $modified_by,
                'activity_time' => current_time('mysql'),
                'ip_address' => $this->get_ip_address(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'category_snapshot' => $category_snapshot,
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        error_log("✅ CV Stats: Producto actualizado trackeado - ID: {$post_id}, Vendedor: {$post_after->post_author}, Modificado por: {$modified_by}");
    }
    
    /**
     * Obtener dirección IP del usuario
     */
    private function get_ip_address() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Obtener productos creados hoy
     */
    public static function get_todays_created_products() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cv_product_activities';
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $query = $wpdb->prepare("
            SELECT *
            FROM {$table_name}
            WHERE activity_type = 'created'
            AND activity_time >= %s
            AND activity_time <= %s
            ORDER BY activity_time DESC
        ", $today_start, $today_end);
        
        $results = $wpdb->get_results($query);
        
        // Enriquecer con datos del producto y usuarios
        $products = array();
        foreach ($results as $row) {
            $product = wc_get_product($row->product_id);
            if (!$product) {
                continue; // Producto eliminado
            }
            
            $categories = self::get_category_snapshot_array((int) $row->product_id, $row->category_snapshot ?? null);
            $vendor = get_userdata($row->vendor_id);
            $created_by_user = get_userdata($row->modified_by);
            
            $products[] = array(
                'product_id' => $row->product_id,
                'product_name' => $product->get_name(),
                'product_url' => get_permalink($row->product_id),
                'vendor_id' => $row->vendor_id,
                'vendor_name' => $vendor ? $vendor->display_name : 'Usuario desconocido',
                'vendor_username' => $vendor ? $vendor->user_login : '',
                'created_by_id' => $row->modified_by,
                'created_by_name' => $created_by_user ? $created_by_user->display_name : 'Desconocido',
                'created_by_username' => $created_by_user ? $created_by_user->user_login : '',
                'activity_time' => $row->activity_time,
                'ip_address' => $row->ip_address,
                'categories' => $categories['terms'],
                'sector_categories' => $categories['sector_terms'],
                'category_snapshot' => $categories,
                'categorize_url' => self::get_categorize_url((int) $row->product_id),
            );
        }
        
        return $products;
    }
    
    /**
     * Obtener productos actualizados hoy
     */
    public static function get_todays_updated_products() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cv_product_activities';
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $query = $wpdb->prepare("
            SELECT *
            FROM {$table_name}
            WHERE activity_type = 'updated'
            AND activity_time >= %s
            AND activity_time <= %s
            ORDER BY activity_time DESC
        ", $today_start, $today_end);
        
        $results = $wpdb->get_results($query);
        
        // Enriquecer con datos del producto y usuarios
        $products = array();
        foreach ($results as $row) {
            $product = wc_get_product($row->product_id);
            if (!$product) {
                continue; // Producto eliminado
            }
            
            $categories = self::get_category_snapshot_array((int) $row->product_id, $row->category_snapshot ?? null);
            $vendor = get_userdata($row->vendor_id);
            $modified_by_user = get_userdata($row->modified_by);
            
            $products[] = array(
                'product_id' => $row->product_id,
                'product_name' => $product->get_name(),
                'product_url' => get_permalink($row->product_id),
                'vendor_id' => $row->vendor_id,
                'vendor_name' => $vendor ? $vendor->display_name : 'Usuario desconocido',
                'vendor_username' => $vendor ? $vendor->user_login : '',
                'modified_by_id' => $row->modified_by,
                'modified_by_name' => $modified_by_user ? $modified_by_user->display_name : 'Desconocido',
                'modified_by_username' => $modified_by_user ? $modified_by_user->user_login : '',
                'activity_time' => $row->activity_time,
                'ip_address' => $row->ip_address,
                'categories' => $categories['terms'],
                'sector_categories' => $categories['sector_terms'],
                'category_snapshot' => $categories,
                'categorize_url' => self::get_categorize_url((int) $row->product_id),
            );
        }
        
        return $products;
    }
    
    /**
     * Obtener contadores de hoy
     */
    public static function get_todays_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cv_product_activities';
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $created_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$table_name}
            WHERE activity_type = 'created'
            AND activity_time >= %s
            AND activity_time <= %s
        ", $today_start, $today_end));
        
        $updated_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$table_name}
            WHERE activity_type = 'updated'
            AND activity_time >= %s
            AND activity_time <= %s
        ", $today_start, $today_end));
        
        return array(
            'created' => intval($created_count),
            'updated' => intval($updated_count)
        );
    }

    /**
     * Obtener la instantánea de categorías en formato JSON.
     */
    public static function get_category_snapshot_json(int $product_id): string {
        $snapshot = self::collect_product_categories($product_id);
        return wp_json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Obtener la instantánea de categorías como arreglo estructurado.
     *
     * @param string|null $snapshot_json JSON previamente guardado.
     * @return array<string,mixed>
     */
    public static function get_category_snapshot_array(int $product_id, ?string $snapshot_json = null): array {
        if ($snapshot_json) {
            $decoded = json_decode($snapshot_json, true);
            if (is_array($decoded) && isset($decoded['terms'])) {
                return self::prepare_snapshot_array($decoded);
            }
        }

        return self::collect_product_categories($product_id);
    }

    /**
     * Obtiene la URL de edición del producto en el panel de WCFM.
     *
     * @param int $product_id
     * @param array<string,mixed> $extra_args
     */
    public static function get_manage_url(int $product_id, array $extra_args = array()): string {
        $product_id = absint($product_id);
        if ($product_id <= 0) {
            return get_edit_post_link($product_id, '');
        }

        $dashboard_base = null;
        if (function_exists('wcfm_get_dashboard_url')) {
            $dashboard_base = wcfm_get_dashboard_url();
        }
        if (!$dashboard_base && function_exists('wcfm_get_page_permalink')) {
            $dashboard_base = wcfm_get_page_permalink('dashboard');
        }
        if (!$dashboard_base) {
            $dashboard_base = site_url('/store-manager/');
        }

        if (function_exists('wcfm_get_endpoint_url')) {
            $manage_url = wcfm_get_endpoint_url('wcfm-products-manage', $product_id, $dashboard_base);

            if (!empty($extra_args)) {
                $manage_url = add_query_arg($extra_args, $manage_url);
            }

            return $manage_url;
        }

        return get_edit_post_link($product_id, '');
    }

    /**
     * Genera la URL de categorización directa para un producto.
     */
    public static function get_categorize_url(int $product_id): string {
        return self::get_manage_url($product_id, array('cv_open_cat' => 1));
    }

    /**
     * Normaliza un arreglo recuperado desde la base de datos.
     *
     * @param array<string,mixed> $snapshot
     * @return array<string,mixed>
     */
    private static function prepare_snapshot_array(array $snapshot): array {
        $terms = array_map(static function ($item) {
            return array(
                'term_id' => isset($item['term_id']) ? (int) $item['term_id'] : 0,
                'slug' => isset($item['slug']) ? (string) $item['slug'] : '',
                'name' => isset($item['name']) ? (string) $item['name'] : '',
                'path' => isset($item['path']) ? (string) $item['path'] : (isset($item['name']) ? (string) $item['name'] : ''),
                'is_sector' => !empty($item['is_sector']),
            );
        }, $snapshot['terms'] ?? array());

        $sector_terms = array_filter($terms, static function ($item) {
            return !empty($item['is_sector']);
        });

        return array(
            'terms' => array_values($terms),
            'sector_terms' => array_values($sector_terms),
            'term_ids' => array_values(array_map(static fn($item) => (int) $item['term_id'], $terms)),
            'sector_term_ids' => array_values(array_map(static fn($item) => (int) $item['term_id'], $sector_terms)),
            'generated_at' => isset($snapshot['generated_at']) ? (string) $snapshot['generated_at'] : '',
            'vendor_virtual' => !empty($snapshot['vendor_virtual']),
        );
    }

    /**
     * Construye y recoge la información de categorías del producto.
     *
     * @return array<string,mixed>
     */
    private static function collect_product_categories(int $product_id): array {
        $terms = get_the_terms($product_id, 'product_cat');
        $vendor_id = (int) get_post_field('post_author', $product_id);

        $vendor_virtual = false;
        if ($vendor_id > 0) {
            if (class_exists('\Cv\ProductCategorization\Admin\VendorVirtual')) {
                $vendor_virtual = \Cv\ProductCategorization\Admin\VendorVirtual::is_virtual($vendor_id);
            } else {
                $vendor_virtual = (bool) get_user_meta($vendor_id, 'cv_vendor_virtual_agent', true);
            }
        }

        if (is_wp_error($terms) || empty($terms)) {
            return array(
                'terms' => array(),
                'sector_terms' => array(),
                'term_ids' => array(),
                'sector_term_ids' => array(),
                'generated_at' => current_time('mysql'),
                'vendor_virtual' => $vendor_virtual,
            );
        }

        $mapped = array();
        foreach ($terms as $term) {
            $path = self::build_term_path($term);
            $is_sector = self::is_sector_term($term);

            $mapped[] = array(
                'term_id' => (int) $term->term_id,
                'slug' => (string) $term->slug,
                'name' => (string) $term->name,
                'path' => $path,
                'is_sector' => $is_sector,
            );
        }

        $sector_terms = array_filter($mapped, static function ($item) {
            return !empty($item['is_sector']);
        });

        return array(
            'terms' => array_values($mapped),
            'sector_terms' => array_values($sector_terms),
            'term_ids' => array_values(array_map(static fn($item) => (int) $item['term_id'], $mapped)),
            'sector_term_ids' => array_values(array_map(static fn($item) => (int) $item['term_id'], $sector_terms)),
            'generated_at' => current_time('mysql'),
            'vendor_virtual' => $vendor_virtual,
        );
    }

    /**
     * Construye la ruta jerárquica del término.
     */
    private static function build_term_path(WP_Term $term): string {
        $ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat'));
        $names = array();

        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            if ($ancestor && !is_wp_error($ancestor)) {
                $names[] = $ancestor->name;
            }
        }

        $names[] = $term->name;
        $names = array_filter($names, static function ($name) {
            return $name !== '';
        });

        return implode(' → ', $names);
    }

    /**
     * Determina si el término pertenece a la categoría Sector.
     */
    private static function is_sector_term(WP_Term $term): bool {
        $sector_id = self::get_sector_root_id();
        if (!$sector_id) {
            return false;
        }

        if ((int) $term->term_id === $sector_id) {
            return true;
        }

        $ancestors = get_ancestors($term->term_id, 'product_cat');
        return in_array((int) $sector_id, array_map('intval', $ancestors), true);
    }

    /**
     * Obtiene (con cache) el ID del término raíz "Sector".
     */
    private static function get_sector_root_id(): ?int {
        if (self::$sector_root_id === null) {
            $term = get_term_by('slug', 'sector', 'product_cat');
            if (!$term || is_wp_error($term)) {
                $term = get_term_by('name', 'Sector', 'product_cat');
            }
            self::$sector_root_id = ($term && !is_wp_error($term)) ? (int) $term->term_id : 0;
        }

        return self::$sector_root_id ?: null;
    }
    
    /**
     * Obtener productos creados por rango de fechas
     */
    public static function get_created_products_by_date_range($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cv_product_activities';
        
        $query = $wpdb->prepare("
            SELECT *
            FROM {$table_name}
            WHERE activity_type = 'created'
            AND activity_time >= %s AND activity_time <= %s
            ORDER BY activity_time DESC
        ", $start_date, $end_date);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Obtener productos actualizados por rango de fechas
     */
    public static function get_updated_products_by_date_range($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cv_product_activities';
        
        $query = $wpdb->prepare("
            SELECT *
            FROM {$table_name}
            WHERE activity_type = 'updated'
            AND activity_time >= %s AND activity_time <= %s
            ORDER BY activity_time DESC
        ", $start_date, $end_date);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Obtener estadísticas de productos por rango de fechas
     */
    public static function get_stats_by_date_range($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cv_product_activities';
        
        $created_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$table_name}
            WHERE activity_type = 'created'
            AND activity_time >= %s AND activity_time <= %s
        ", $start_date, $end_date));
        
        $updated_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$table_name}
            WHERE activity_type = 'updated'
            AND activity_time >= %s
            AND activity_time <= %s
        ", $start_date, $end_date));
        
        return array(
            'created' => intval($created_count),
            'updated' => intval($updated_count)
        );
    }
}

new CV_Stats_Product_Tracker();


