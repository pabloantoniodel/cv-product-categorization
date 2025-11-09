<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CV_Stats_Aws_Search_Log
{
    private const TABLE_SUFFIX = 'cv_aws_search_log';

    public function __construct()
    {
        add_action('aws_search_start', [$this, 'handle_search'], 50, 1);
    }

    /**
     * Crea o actualiza la tabla de logs.
     */
    public static function create_table(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_SUFFIX;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            search_term varchar(190) NOT NULL,
            store_slug varchar(190) DEFAULT '',
            vendor_id bigint(20) unsigned DEFAULT 0,
            user_id bigint(20) unsigned DEFAULT 0,
            user_login varchar(190) DEFAULT '',
            ip_address varchar(100) DEFAULT '',
            page_url text,
            PRIMARY KEY (id),
            KEY store_slug (store_slug),
            KEY created_at (created_at),
            KEY vendor_id (vendor_id),
            KEY user_id (user_id),
            KEY user_login (user_login),
            KEY ip_address (ip_address),
            KEY search_term (search_term)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    /**
     * Crea la tabla sólo si aún no existe.
     */
    public static function maybe_create_table(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_SUFFIX;
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if ($table_exists !== $table_name) {
            self::create_table();
            return;
        }

        $columns_to_check = [
            'user_id'    => "ALTER TABLE {$table_name} ADD COLUMN user_id bigint(20) unsigned DEFAULT 0 AFTER vendor_id",
            'user_login' => "ALTER TABLE {$table_name} ADD COLUMN user_login varchar(190) DEFAULT '' AFTER user_id",
            'ip_address' => "ALTER TABLE {$table_name} ADD COLUMN ip_address varchar(100) DEFAULT '' AFTER user_login",
        ];

        foreach ($columns_to_check as $column => $sql) {
            $column_exists = $wpdb->get_var(
                $wpdb->prepare("SHOW COLUMNS FROM {$table_name} LIKE %s", $column)
            );
            if ($column_exists === null) {
                $wpdb->query($sql);
            }
        }
    }

    /**
     * Maneja el evento de búsqueda de AWS.
     *
     * @param string $normalized_term
     */
    public function handle_search(string $normalized_term): void
    {
        if (empty($_POST) && empty($_GET)) {
            return;
        }

        $raw_term = '';
        if (isset($_POST['keyword'])) {
            $raw_term = sanitize_text_field(wp_unslash((string) $_POST['keyword']));
        } elseif (!empty($normalized_term)) {
            $raw_term = sanitize_text_field($normalized_term);
        }

        if ($raw_term === '') {
            return;
        }

        $page_url = '';
        if (isset($_POST['pageurl'])) {
            $page_url = esc_url_raw(wp_unslash((string) $_POST['pageurl']));
        }

        if ($page_url === '' && isset($_SERVER['HTTP_REFERER'])) {
            $page_url = esc_url_raw(wp_unslash((string) $_SERVER['HTTP_REFERER']));
        }

        if ($page_url === '') {
            return;
        }

        $store_slug = '';
        $vendor_id = 0;

        $parsed = wp_parse_url($page_url);
        if (is_array($parsed)) {
            $path = $parsed['path'] ?? '';
            if (!empty($path)) {
                $segments = array_values(array_filter(explode('/', trim($path, '/'))));
                if (!empty($segments) && $segments[0] === 'store' && isset($segments[1])) {
                    $store_slug = sanitize_title($segments[1]);
                }
            }
        }

        if ($store_slug === '') {
            $store_slug = 'global';
        } else {
            if (function_exists('wcfmmp_get_store_id_by_slug')) {
                $vendor_id = (int) wcfmmp_get_store_id_by_slug($store_slug);
            }

            if (!$vendor_id) {
                global $wpdb;
                $vendor_id = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wcfmmp_store_slug' AND meta_value = %s LIMIT 1",
                        $store_slug
                    )
                );
            }
        }

        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_SUFFIX;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            // Intentar crear la tabla “al vuelo” por si aún no existe.
            self::create_table();
        }

        $user_id = get_current_user_id();
        $user_login = '';
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            if ($user instanceof WP_User) {
                $user_login = (string) $user->user_login;
            }
        }

        $ip_address = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip_address = sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']));
        }

        $wpdb->insert(
            $table_name,
            [
                'created_at'  => current_time('mysql'),
                'search_term' => $raw_term,
                'store_slug'  => $store_slug,
                'vendor_id'   => $vendor_id,
                'user_id'     => $user_id,
                'user_login'  => $user_login,
                'ip_address'  => $ip_address,
                'page_url'    => $page_url,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );
    }
}


