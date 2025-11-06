<?php
/**
 * Plugin Name: CV - Anti-Spam & Firewall Protection
 * Description: Protección anti-spam: Bloquea registro de usuarios subscriber, añade CAPTCHA y firewall geográfico para wp-admin. Redirige usuarios españoles sin login a /shop. Compatible con "Login as User"
 * Version: 1.4.0
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FIREWALL GEOGRÁFICO - Bloquear wp-admin desde fuera de España
 */
class CV_Geographic_Firewall {
    
    private $allowed_countries = array('ES'); // España
    
    public function __construct() {
        // Proteger wp-admin DESPUÉS de IP2Location (prioridad 10 vs 1)
        // IP2Location bloquea países primero, luego nosotros gestionamos servicios específicos
        add_action('init', array($this, 'protect_wp_admin'), 10);
    }
    
    /**
     * Proteger wp-admin desde fuera de España
     */
    public function protect_wp_admin() {
        // Solo aplicar a wp-admin (excepto admin-ajax.php que es necesario para el frontend)
        global $pagenow;
        
        $is_wp_admin = is_admin() && !wp_doing_ajax();
        $is_login = in_array($pagenow, array('wp-login.php'));
        $has_reauth = isset($_GET['reauth']);
        
        // Si es wp-admin, wp-login o tiene parámetro reauth
        if ($is_wp_admin || $is_login || $has_reauth) {
            
            // EXCEPCIÓN: Permitir bots de búsqueda (Google, Bing, etc.)
            if ($this->is_search_engine_bot()) {
                return; // Bots de búsqueda siempre permitidos
            }
            
            // Permitir usuarios ya logueados como admin
            if (is_user_logged_in() && current_user_can('manage_options')) {
                return; // Administradores siempre permitidos
            }
            
            // EXCEPCIÓN: Si es admin usando "Login as User", permitir acceso para poder volver
            if ($this->is_admin_logged_as_user()) {
                return; // Admin usando "Login as User" puede acceder
            }
            
            $country_code = $this->get_user_country();
            
            // Si no es de España, bloquear con 403
            if (!in_array($country_code, $this->allowed_countries)) {
                $this->block_access($country_code);
            } else {
                // Si ES de España pero NO está logueado como admin, redirigir a /shop
                if (!is_user_logged_in() || !current_user_can('manage_options')) {
                    $this->redirect_to_shop($country_code);
                }
            }
        }
    }
    
    /**
     * Verificar si un admin está usando "Login as User"
     */
    private function is_admin_logged_as_user() {
        // Verificar si tiene la acción de "volver a admin" en la URL
        if (isset($_GET['action']) && $_GET['action'] === 'login_as_olduser') {
            return true;
        }
        
        // Verificar si el plugin "Login as User" está activo y tiene sesión
        // El plugin guarda el ID del admin original en la sesión
        if (function_exists('login_as_user_get_olduser_id')) {
            $old_user_id = login_as_user_get_olduser_id();
            if ($old_user_id) {
                // Verificar que el usuario original es admin
                $old_user = get_userdata($old_user_id);
                if ($old_user && user_can($old_user, 'manage_options')) {
                    return true;
                }
            }
        }
        
        // Verificar si hay cookie o meta del plugin "Login as User"
        if (isset($_COOKIE['login_as_user_olduser_id'])) {
            $old_user_id = intval($_COOKIE['login_as_user_olduser_id']);
            if ($old_user_id > 0) {
                $old_user = get_userdata($old_user_id);
                if ($old_user && user_can($old_user, 'manage_options')) {
                    return true;
                }
            }
        }
        
        // Verificar meta del usuario actual que indica que es una sesión de "Login as User"
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $is_switched = get_user_meta($user_id, '_login_as_user_switched', true);
            if ($is_switched) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si el visitante es un bot de motor de búsqueda
     */
    private function is_search_engine_bot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Lista de bots de búsqueda conocidos (igual que IP2Location)
        $search_bots = array(
            'googlebot',
            'bingbot',
            'slurp',        // Yahoo
            'duckduckbot',  // DuckDuckGo
            'baiduspider',  // Baidu
            'yandexbot',    // Yandex
            'sogou',        // Sogou
            'exabot',       // Exalead
            'ia_archiver',  // Alexa
            'msnbot',       // Microsoft
            'applebot',     // Apple
            'facebookexternalhit', // Facebook
            'linkedinbot',  // LinkedIn
            'twitterbot',   // Twitter
            'whatsapp',     // WhatsApp
            'gptbot',       // ChatGPT
            'perplexity',   // Perplexity AI
        );
        
        $user_agent_lower = strtolower($user_agent);
        
        foreach ($search_bots as $bot) {
            if (strpos($user_agent_lower, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Redirigir a /shop a usuarios de España no autorizados
     */
    private function redirect_to_shop($country_code) {
        $ip = $this->get_user_ip();
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        // Log del intento
        error_log(sprintf(
            '[CV Firewall] 🔄 REDIRIGIDO A SHOP | IP: %s | País: %s | URI: %s',
            $ip,
            $country_code,
            $request_uri
        ));
        
        // Redirigir a la tienda
        wp_redirect(home_url('/shop'));
        exit;
    }
    
    /**
     * Obtener país del usuario usando IP2Location si está disponible
     */
    private function get_user_country() {
        $ip = $this->get_user_ip();
        
        // Intentar usar IP2Location plugin si está instalado
        if (class_exists('IP2Location\Database')) {
            try {
                $db_path = WP_CONTENT_DIR . '/uploads/ip2location/IP2LOCATION-LITE-DB1.BIN';
                
                if (file_exists($db_path)) {
                    $db = new \IP2Location\Database($db_path, \IP2Location\Database::FILE_IO);
                    $result = $db->lookup($ip, \IP2Location\Database::ALL);
                    
                    if ($result && isset($result['countryCode'])) {
                        return $result['countryCode'];
                    }
                }
            } catch (Exception $e) {
                error_log('[CV Firewall] Error IP2Location: ' . $e->getMessage());
            }
        }
        
        // Fallback: Usar servicio gratuito de geolocalización
        $country = $this->get_country_from_api($ip);
        
        return $country;
    }
    
    /**
     * Obtener IP real del usuario
     */
    private function get_user_ip() {
        // Orden de prioridad para obtener IP real (considerando proxies/CDN)
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Si hay múltiples IPs (proxy chain), tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validar que sea una IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Obtener país desde API gratuita (fallback)
     */
    private function get_country_from_api($ip) {
        // IPs locales siempre permitidas (desarrollo)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'ES'; // Permitir IPs locales
        }
        
        // Cache de 1 hora para no sobrecargar la API
        $cache_key = 'cv_geoip_' . md5($ip);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Usar API gratuita ip-api.com (sin clave requerida, límite 45 req/min)
        $api_url = "http://ip-api.com/json/{$ip}?fields=countryCode";
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 2,
            'sslverify' => false
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['countryCode'])) {
                $country = $data['countryCode'];
                set_transient($cache_key, $country, HOUR_IN_SECONDS);
                return $country;
            }
        }
        
        // Si falla, por seguridad NO bloqueamos (evitar bloqueo accidental)
        error_log('[CV Firewall] No se pudo determinar país para IP: ' . $ip);
        return 'ES'; // Asumir España si falla la detección
    }
    
    /**
     * Bloquear acceso y registrar
     */
    private function block_access($country_code) {
        $ip = $this->get_user_ip();
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        // Log del intento
        error_log(sprintf(
            '[CV Firewall] 🚫 ACCESO BLOQUEADO | IP: %s | País: %s | URI: %s | User-Agent: %s',
            $ip,
            $country_code,
            $request_uri,
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ));
        
        // Bloquear con código 403
        status_header(403);
        nocache_headers();
        
        // Página de bloqueo
        wp_die(
            '<h1>🚫 Acceso Denegado</h1>' .
            '<p>El acceso al panel de administración está restringido geográficamente.</p>' .
            '<p><strong>País detectado:</strong> ' . esc_html($country_code) . '</p>' .
            '<p><strong>IP:</strong> ' . esc_html($ip) . '</p>' .
            '<hr>' .
            '<p><small>Si eres administrador legítimo, contacta con soporte técnico.</small></p>',
            'Acceso Restringido - Firewall Geográfico',
            array(
                'response' => 403,
                'back_link' => false
            )
        );
    }
}

// Firewall geográfico ACTIVADO - No interfiere con WP Statistics
// Protege wp-admin desde fuera de España (excepto admin-ajax.php)
new CV_Geographic_Firewall();

class CV_Anti_Spam_Protection {
    
    public function __construct() {
        // BLOQUEAR registro de subscriber (solo spam)
        // Los registros legítimos son: customer, dc_vendor, etc. (NO subscriber)
        add_action('user_register', array($this, 'block_subscriber_registration'), 10, 1);
        
        // Agregar CAPTCHA en formularios de Contact Form 7
        add_filter('wpcf7_form_elements', array($this, 'add_captcha_to_cf7'));
        
        // Asegurar que CAPTCHA esté activo en login/registro de WooCommerce
        add_action('woocommerce_register_form', array($this, 'add_captcha_to_woocommerce_register'), 10);
        
        // Asegurar que CAPTCHA esté en formulario de registro de User Registration
        add_action('user_registration_register_form', array($this, 'add_captcha_to_user_registration'), 10);
        
        // Log de registros para monitoreo
        add_action('user_register', array($this, 'log_user_registration'), 5, 1);
    }
    
    /**
     * BLOQUEAR subscriber = SPAM
     * Registros legítimos usan roles:
     * - customer (compras WooCommerce)
     * - dc_vendor (tarjetas de visita)
     * - administrator (admin)
     * subscriber = SOLO BOTS
     */
    public function block_subscriber_registration($user_id) {
        $user = get_userdata($user_id);
        
        // Si es subscriber y no es desde admin, es SPAM
        if ($user && in_array('subscriber', (array) $user->roles)) {
            // Permitir solo si es desde admin
            if (!is_admin()) {
                // Log del spam
                error_log('[CV Anti-Spam] 🚫 SPAM bloqueado - Subscriber: ' . $user->user_email . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                
                // Eliminar el usuario spam
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user($user_id);
                
                // Bloquear con mensaje
                wp_die(
                    'Registro no permitido. Los registros deben realizarse a través de compra de productos o creación de tarjetas de visita.',
                    'Acceso Denegado',
                    array('response' => 403, 'back_link' => true)
                );
            }
        }
    }
    
    /**
     * Log de todos los registros de usuario para monitoreo
     */
    public function log_user_registration($user_id) {
        $user = get_userdata($user_id);
        
        if ($user) {
            $roles = implode(', ', (array) $user->roles);
            $log_entry = sprintf(
                '[CV Anti-Spam] ✅ Usuario registrado | Role: %s | Email: %s | IP: %s',
                $roles,
                $user->user_email,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            );
            
            error_log($log_entry);
        }
    }
    
    /**
     * Añadir CAPTCHA a User Registration
     */
    public function add_captcha_to_user_registration() {
        if (function_exists('gglcptch_display')) {
            echo '<div class="user-registration-captcha-wrapper">';
            echo gglcptch_display();
            echo '</div>';
        }
    }
    
    /**
     * Añadir CAPTCHA a Contact Form 7
     */
    public function add_captcha_to_cf7($form_html) {
        // Solo si no tiene ya el shortcode de captcha
        if (strpos($form_html, '[recaptcha]') === false && function_exists('gglcptch_display')) {
            // Añadir CAPTCHA antes del botón submit
            $form_html = str_replace('[submit', gglcptch_display() . "\n[submit", $form_html);
        }
        
        return $form_html;
    }
    
    /**
     * Añadir CAPTCHA al formulario de registro de WooCommerce
     */
    public function add_captcha_to_woocommerce_register() {
        if (function_exists('gglcptch_display')) {
            echo '<div class="woocommerce-captcha-wrapper">';
            echo gglcptch_display();
            echo '</div>';
        }
    }
}

// Inicializar
new CV_Anti_Spam_Protection();

/**
 * Comandos WP-CLI para firewall y anti-spam
 */
if (defined('WP_CLI') && WP_CLI) {
    
    /**
     * Monitorear intentos de acceso bloqueados y redirigidos
     *
     * ## EXAMPLES
     *
     *     wp cv-firewall logs
     *     wp cv-firewall logs --lines=50
     *     wp cv-firewall logs --type=blocked
     *     wp cv-firewall logs --type=redirect
     *
     * @when after_wp_load
     */
    WP_CLI::add_command('cv-firewall logs', function($args, $assoc_args) {
        $lines = $assoc_args['lines'] ?? 20;
        $type = $assoc_args['type'] ?? 'all'; // all, blocked, redirect
        
        $log_file = ini_get('error_log');
        if (!$log_file || !file_exists($log_file)) {
            $log_file = WP_CONTENT_DIR . '/debug.log';
        }
        
        if (!file_exists($log_file)) {
            WP_CLI::error('No se encontró el archivo de log.');
        }
        
        // Preparar grep según el tipo
        switch ($type) {
            case 'blocked':
                $grep_pattern = 'CV Firewall.*BLOQUEADO';
                WP_CLI::log("Últimos {$lines} accesos BLOQUEADOS:\n");
                break;
            case 'redirect':
                $grep_pattern = 'CV Firewall.*REDIRIGIDO';
                WP_CLI::log("Últimos {$lines} accesos REDIRIGIDOS:\n");
                break;
            default:
                $grep_pattern = 'CV Firewall';
                WP_CLI::log("Últimos {$lines} eventos del firewall:\n");
        }
        
        $command = "tail -n 1000 {$log_file} | grep '{$grep_pattern}' | tail -n {$lines}";
        $output = shell_exec($command);
        
        if ($output) {
            WP_CLI::log($output);
            
            // Contar por tipo
            $blocked = substr_count($output, 'BLOQUEADO');
            $redirected = substr_count($output, 'REDIRIGIDO');
            
            WP_CLI::log("\n" . str_repeat('-', 50));
            WP_CLI::log("Resumen:");
            if ($blocked > 0) WP_CLI::log("🚫 Bloqueados: {$blocked}");
            if ($redirected > 0) WP_CLI::log("🔄 Redirigidos: {$redirected}");
        } else {
            WP_CLI::success('No hay eventos registrados del tipo solicitado.');
        }
    });
    
    /**
     * Verificar país de una IP
     *
     * ## EXAMPLES
     *
     *     wp cv-firewall check-ip 8.8.8.8
     *
     * @when after_wp_load
     */
    WP_CLI::add_command('cv-firewall check-ip', function($args) {
        if (empty($args[0])) {
            WP_CLI::error('Debes proporcionar una IP. Ejemplo: wp cv-firewall check-ip 8.8.8.8');
        }
        
        $ip = $args[0];
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            WP_CLI::error('IP inválida: ' . $ip);
        }
        
        WP_CLI::log("Verificando IP: {$ip}...\n");
        
        // Usar API
        $api_url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,isp";
        $response = wp_remote_get($api_url, array('timeout' => 5));
        
        if (is_wp_error($response)) {
            WP_CLI::error('Error al consultar la API: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data && $data['status'] === 'success') {
            WP_CLI::log("País: {$data['country']} ({$data['countryCode']})");
            WP_CLI::log("Ciudad: {$data['city']}");
            WP_CLI::log("ISP: {$data['isp']}");
            
            if ($data['countryCode'] === 'ES') {
                WP_CLI::success('✓ Esta IP sería PERMITIDA (España)');
            } else {
                WP_CLI::warning('✗ Esta IP sería BLOQUEADA (no es de España)');
            }
        } else {
            WP_CLI::error('No se pudo obtener información de la IP.');
        }
    });
    
    /**
     * Limpiar caché de geolocalización
     *
     * ## EXAMPLES
     *
     *     wp cv-firewall clear-cache
     *
     * @when after_wp_load
     */
    WP_CLI::add_command('cv-firewall clear-cache', function() {
        global $wpdb;
        
        $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cv_geoip_%' OR option_name LIKE '_transient_timeout_cv_geoip_%'");
        
        WP_CLI::success("Caché de geolocalización limpiado. {$deleted} entradas eliminadas.");
    });
    
    /**
     * Elimina usuarios subscriber spam
     *
     * ## EXAMPLES
     *
     *     wp cv-antispam delete-spam
     *
     * @when after_wp_load
     */
    WP_CLI::add_command('cv-antispam delete-spam', function() {
        
        $args = array(
            'role' => 'subscriber',
            'fields' => array('ID', 'user_email', 'user_registered')
        );
        
        $subscribers = get_users($args);
        
        if (empty($subscribers)) {
            WP_CLI::success('No hay usuarios subscriber para eliminar.');
            return;
        }
        
        WP_CLI::log(sprintf('Encontrados %d usuarios subscriber.', count($subscribers)));
        
        $deleted = 0;
        $progress = \WP_CLI\Utils\make_progress_bar('Eliminando usuarios spam', count($subscribers));
        
        foreach ($subscribers as $user) {
            // Eliminar el usuario
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            $result = wp_delete_user($user->ID);
            
            if ($result) {
                $deleted++;
                WP_CLI::log(sprintf('✓ Eliminado: %s (ID: %d)', $user->user_email, $user->ID));
            } else {
                WP_CLI::warning(sprintf('✗ Error al eliminar: %s (ID: %d)', $user->user_email, $user->ID));
            }
            
            $progress->tick();
        }
        
        $progress->finish();
        
        WP_CLI::success(sprintf('Eliminados %d de %d usuarios subscriber spam.', $deleted, count($subscribers)));
    });
}

