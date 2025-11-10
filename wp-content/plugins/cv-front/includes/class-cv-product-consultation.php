<?php
/**
 * Botón y modal de consulta genérica en productos
 *
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Product_Consultation
{
    /**
     * URL de contacto genérica.
     *
     * @var string
     */
    private $contact_url = '';

    /**
     * Slugs considerados como contacto.
     *
     * @var string[]
     */
    private $candidate_slugs = [
        'contacto',
        'contactar',
        'contactanos',
        'contact',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'bootstrap']);
    }

    /**
     * Inicializa hooks necesarios.
     */
    public function bootstrap(): void
    {
        $this->contact_url = apply_filters('cv_product_consultation_contact_url', $this->resolve_contact_url());

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('woocommerce_single_product_summary', [$this, 'render_consult_box'], 31);
        add_filter('wcfm_is_pref_enquiry_button', [$this, 'maybe_disable_wcfm_button'], 20, 1);
        add_filter('login_redirect', [$this, 'maybe_force_consult_redirect'], 999, 3);
        add_filter('woocommerce_login_redirect', [$this, 'maybe_force_consult_redirect_woo'], 999, 2);
        add_action('wp_ajax_nopriv_cv_product_consultation_login', [$this, 'handle_login']);
        add_action('wp_ajax_cv_product_consultation_submit', [$this, 'handle_submission']);
        add_action('wp_ajax_nopriv_cv_product_consultation_submit', [$this, 'handle_submission']);
    }

    /**
     * Carga CSS/JS solo en fichas de producto.
     */
    public function enqueue_assets(): void
    {
        if (!is_product()) {
            return;
        }

        wp_enqueue_style(
            'cv-product-consultation',
            CV_FRONT_PLUGIN_URL . 'assets/css/product-consultation.css',
            [],
            CV_FRONT_VERSION
        );

        $deps = ['jquery'];
        if (wp_script_is('cv-inmobiliaria-script', 'enqueued') || wp_script_is('cv-inmobiliaria-script', 'registered')) {
            $deps[] = 'cv-inmobiliaria-script';
        }

        wp_enqueue_script(
            'cv-product-consultation',
            CV_FRONT_PLUGIN_URL . 'assets/js/product-consultation.js',
            $deps,
            CV_FRONT_VERSION,
            true
        );

        wp_localize_script('cv-product-consultation', 'cvProductConsult', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('cv_inmobiliaria_nonce'),
            'isLoggedIn' => is_user_logged_in(),
            'contactUrl' => esc_url($this->contact_url),
            'loginUrl'   => esc_url(wp_login_url()),
            'userName'   => $this->get_current_user_name(),
            'userEmail'  => $this->get_current_user_email(),
            'userPhone'  => $this->get_current_user_phone(),
            'loginNonce' => wp_create_nonce('cv_product_consult_login'),
            'strings'    => [
                'modalTitle'      => __('Consulta sobre este producto', 'cv-front'),
                'contactCTA'      => __('¿Prefieres nuestro formulario general?', 'cv-front'),
                'contactBtn'      => __('Ir a contacto', 'cv-front'),
                'success'         => __('¡Consulta enviada correctamente!', 'cv-front'),
                'genericError'    => __('Ha ocurrido un error. Inténtalo nuevamente.', 'cv-front'),
                'emptyMessage'    => __('Por favor escribe tu consulta.', 'cv-front'),
                'emptyGuest'      => __('Por favor completa tu nombre, email y teléfono.', 'cv-front'),
                'sending'         => __('Enviando…', 'cv-front'),
                'sendLabel'       => __('Enviar consulta', 'cv-front'),
                'tabGuest'        => __('Sin cuenta', 'cv-front'),
                'tabLogin'        => __('Ya soy usuario', 'cv-front'),
                'loginTitle'      => __('Inicia sesión para continuar', 'cv-front'),
                'loginUserLabel'  => __('Usuario o email', 'cv-front'),
                'loginPassLabel'  => __('Contraseña', 'cv-front'),
                'loginRemember'   => __('Recordarme', 'cv-front'),
                'loginSubmit'     => __('Iniciar sesión', 'cv-front'),
                'loginProcessing' => __('Accediendo…', 'cv-front'),
                'loginError'      => __('No pudimos iniciar sesión con esos datos.', 'cv-front'),
                'loginSuccess'    => __('Sesión iniciada, ya puedes escribir tu consulta.', 'cv-front'),
            ],
        ]);
    }

    /**
     * Renderiza texto explicativo y botón debajo del carrito.
     */
    public function render_consult_box(): void
    {
        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        if ($this->is_inmobiliaria_product((int) $product->get_id())) {
            // Para inmobiliaria ya existe un flujo específico.
            return;
        }

        $product_id    = (int) $product->get_id();
        $product_title = $product->get_name();
        $vendor_id     = $this->resolve_vendor_id($product_id);

        $description = apply_filters(
            'cv_product_consultation_text',
            __('¿Tienes dudas? Usa el botón Consultar y nos pondremos en contacto contigo lo antes posible.', 'cv-front'),
            $product
        );

        echo '<div class="cv-product-consult-box">';

        if (!empty($description)) {
            echo '<p class="cv-product-consult-text">' . esc_html($description) . '</p>';
        }

        echo '<button type="button" class="button alt cv-consultation-button"';
        echo ' data-product-id="' . esc_attr($product_id) . '"';
        echo ' data-product-title="' . esc_attr($product_title) . '"';
        echo ' data-vendor-id="' . esc_attr($vendor_id) . '"';
        echo '>';
        echo esc_html__('Consultar', 'cv-front');
        echo '</button>';

        echo '</div>';
    }

    /**
     * Desactiva el botón original de WCFM en ficha de producto.
     *
     * @param bool $allowed Estado actual.
     *
     * @return bool
     */
    public function maybe_disable_wcfm_button(bool $allowed): bool
    {
        if (!is_product()) {
            return $allowed;
        }

        return false;
    }

    /**
     * Fuerza la redirección de login cuando proviene de la consulta.
     */
    public function maybe_force_consult_redirect(string $redirect_to, string $requested_redirect_to, $user): string
    {
        $target = $this->resolve_consult_return_url();
        if ('' !== $target) {
            return $target;
        }

        return $redirect_to;
    }

    /**
     * Equivalente para el filtro de WooCommerce.
     */
    public function maybe_force_consult_redirect_woo(string $redirect_to, $user): string
    {
        $target = $this->resolve_consult_return_url();
        if ('' !== $target) {
            return $target;
        }

        return $redirect_to;
    }

    /**
     * Obtiene la URL de retorno solicitada para la consulta.
     */
    private function resolve_consult_return_url(): string
    {
        if (!isset($_REQUEST['cv_consult_return'])) { // phpcs:ignore WordPress.Security.NonceVerification
            return '';
        }

        $raw = wp_unslash((string) $_REQUEST['cv_consult_return']); // phpcs:ignore WordPress.Security.NonceVerification
        if ('' === $raw) {
            return '';
        }

        $validated = wp_validate_redirect($raw, '');
        if (!$validated) {
            return '';
        }

        return $validated;
    }

    /**
     * Autentica al usuario desde el modal sin abandonar la página.
     */
    public function handle_login(): void
    {
        if (!check_ajax_referer('cv_product_consult_login', 'nonce', false)) {
            wp_send_json_error(['message' => __('Sesión expirada, recarga la página.', 'cv-front')], 400);
        }

        $login    = isset($_POST['login']) ? sanitize_text_field((string) $_POST['login']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $remember = !empty($_POST['remember']);

        if ('' === $login || '' === $password) {
            wp_send_json_error(['message' => __('Indica usuario y contraseña.', 'cv-front')], 400);
        }

        $creds = [
            'user_login'    => $login,
            'user_password' => wp_unslash($password),
            'remember'      => $remember,
        ];

        $user = wp_signon($creds, false);

        if ($user instanceof WP_Error) {
            wp_send_json_error(['message' => $user->get_error_message()], 401);
        }

        wp_set_current_user($user->ID);

        $return_url = isset($_POST['return_url']) ? wp_validate_redirect((string) $_POST['return_url'], '') : '';

        wp_send_json_success([
            'nonce'       => wp_create_nonce('cv_inmobiliaria_nonce'),
            'login_nonce' => wp_create_nonce('cv_product_consult_login'),
            'user_name'   => $this->get_current_user_name(),
            'user_email'  => $this->get_current_user_email(),
            'user_phone'  => $this->get_current_user_phone(),
            'return_url'  => $return_url,
        ]);
    }

    /**
     * Obtiene la URL de contacto disponible.
     */
    private function resolve_contact_url(): string
    {
        foreach ($this->candidate_slugs as $slug) {
            $page = get_page_by_path($slug);
            if ($page instanceof WP_Post) {
                $permalink = get_permalink($page);
                if ($permalink) {
                    return $permalink;
                }
            }
        }

        // Fallback.
        return home_url('/contacto/');
    }

    /**
     * Determina si el producto pertenece a Inmobiliaria o derivados.
     */
    private function is_inmobiliaria_product(int $product_id): bool
    {
        $terms = wp_get_post_terms($product_id, 'product_cat');
        if (empty($terms) || is_wp_error($terms)) {
            return false;
        }

        foreach ($terms as $term) {
            if (in_array($term->slug, ['inmobiliaria', 'inmobiliaria-sector'], true)) {
                return true;
            }

            $ancestors = get_ancestors($term->term_id, 'product_cat');
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, 'product_cat');
                if ($ancestor && in_array($ancestor->slug, ['inmobiliaria', 'inmobiliaria-sector'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Obtiene el vendor ID vinculado.
     */
    private function resolve_vendor_id(int $product_id): int
    {
        if (function_exists('wcfm_get_vendor_id_by_post')) {
            $vendor_id = (int) wcfm_get_vendor_id_by_post($product_id);
            if ($vendor_id > 0) {
                return $vendor_id;
            }
        }

        return (int) get_post_field('post_author', $product_id);
    }

    /**
     * Maneja la recepción de una consulta desde el modal.
     */
    public function handle_submission(): void
    {
        if (!check_ajax_referer('cv_inmobiliaria_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Sesión expirada, recarga la página.', 'cv-front')], 400);
        }

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $message    = isset($_POST['message']) ? sanitize_textarea_field((string) $_POST['message']) : '';

        if ($product_id <= 0 || '' === $message) {
            wp_send_json_error(['message' => __('Faltan datos obligatorios.', 'cv-front')], 400);
        }

        if (!get_post($product_id)) {
            wp_send_json_error(['message' => __('El producto indicado no existe.', 'cv-front')], 404);
        }

        $is_guest   = !is_user_logged_in();
        $guest_name = $is_guest ? sanitize_text_field((string) ($_POST['name'] ?? '')) : '';
        $guest_email = $is_guest ? sanitize_email((string) ($_POST['email'] ?? '')) : '';
        $guest_phone = sanitize_text_field((string) ($_POST['phone'] ?? ''));

        if ($is_guest) {
            if ('' === $guest_name || '' === $guest_phone || !is_email($guest_email)) {
                wp_send_json_error(['message' => __('Necesitamos tu nombre, email y teléfono para responderte.', 'cv-front')], 400);
            }
        }

        $customer_id    = 0;
        $customer_name  = $guest_name;
        $customer_email = $guest_email;

        if (!$is_guest) {
            $user = wp_get_current_user();
            if ($user instanceof WP_User) {
                $customer_id    = (int) $user->ID;
                $customer_email = (string) $user->user_email;
                $customer_name  = trim(sprintf(
                    '%s %s',
                    (string) $user->first_name,
                    (string) $user->last_name
                ));
                if ('' === $customer_name) {
                    $customer_name = $user->display_name ?: $user->user_login;
                }

                if ('' === $guest_phone) {
                    $guest_phone = get_user_meta($user->ID, 'billing_phone', true) ?: get_user_meta($user->ID, 'phone', true);
                    $guest_phone = is_string($guest_phone) ? $guest_phone : '';
                }
            }
        }

        $vendor_id = $this->resolve_vendor_id($product_id);

        global $wpdb, $WCFM;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'wcfm_enquiries',
            [
                'enquiry'        => $message,
                'reply'          => '',
                'product_id'     => $product_id,
                'author_id'      => $vendor_id,
                'vendor_id'      => $vendor_id,
                'customer_id'    => $customer_id,
                'customer_name'  => $customer_name,
                'customer_email' => $customer_email,
                'reply_by'       => 0,
                'is_private'     => 1,
                'posted'         => current_time('mysql'),
                'replied'        => '0000-00-00',
            ],
            ['%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        if (!$inserted) {
            wp_send_json_error(['message' => __('No se pudo registrar tu consulta. Inténtalo de nuevo.', 'cv-front')], 500);
        }

        $enquiry_id = (int) $wpdb->insert_id;

        if ('' !== $guest_phone) {
            $wpdb->insert(
                $wpdb->prefix . 'wcfm_enquiries_meta',
                [
                    'enquiry_id' => $enquiry_id,
                    'key'        => __('Teléfono móvil', 'cv-front'),
                    'value'      => $guest_phone,
                ],
                ['%d', '%s', '%s']
            );
        }

        if (isset($_POST['ref']) && $_POST['ref'] !== '') {
            $wpdb->insert(
                $wpdb->prefix . 'wcfm_enquiries_meta',
                [
                    'enquiry_id' => $enquiry_id,
                    'key'        => 'cv_ref',
                    'value'      => sanitize_text_field((string) $_POST['ref']),
                ],
                ['%d', '%s', '%s']
            );
        }

        if (isset($_POST['store_origin']) && $_POST['store_origin'] !== '') {
            $wpdb->insert(
                $wpdb->prefix . 'wcfm_enquiries_meta',
                [
                    'enquiry_id' => $enquiry_id,
                    'key'        => 'cv_store_origin',
                    'value'      => sanitize_text_field((string) $_POST['store_origin']),
                ],
                ['%d', '%s', '%s']
            );
        }

        if ($WCFM && method_exists($WCFM->wcfm_notification, 'wcfm_send_direct_message')) {
            $notification = sprintf(
                __('Nueva consulta de producto: %s', 'cv-front'),
                get_the_title($product_id)
            );

            $WCFM->wcfm_notification->wcfm_send_direct_message(
                $customer_id,
                $vendor_id,
                0,
                1,
                $notification,
                'enquiry',
                false
            );
        }

        wp_send_json_success(['message' => __('¡Consulta enviada correctamente!', 'cv-front')]);
    }

    private function get_current_user_name(): string
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $user = wp_get_current_user();
        if (!$user instanceof WP_User) {
            return '';
        }

        $name = trim(sprintf('%s %s', (string) $user->first_name, (string) $user->last_name));
        if ('' === $name) {
            $name = $user->display_name ?: $user->user_login;
        }

        return $name;
    }

    private function get_current_user_email(): string
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $user = wp_get_current_user();
        return $user instanceof WP_User ? (string) $user->user_email : '';
    }

    private function get_current_user_phone(): string
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $user_id = get_current_user_id();
        $phone   = get_user_meta($user_id, 'billing_phone', true);
        if (!$phone) {
            $phone = get_user_meta($user_id, 'phone', true);
        }

        return is_string($phone) ? $phone : '';
    }
}

