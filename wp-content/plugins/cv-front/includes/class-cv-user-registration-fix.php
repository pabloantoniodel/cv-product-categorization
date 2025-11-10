<?php
/**
 * CV User Registration Fix
 *
 * Mejora el diseño del formulario de registro en pantallas grandes
 *
 * @package CV_Front
 * @since 2.4.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_User_Registration_Fix {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('user_register', array($this, 'maybe_mark_virtual_vendor'), 25, 1);
    }
    
    /**
     * Enqueue estilos para páginas de User Registration
     */
    public function enqueue_styles() {
        // Solo en páginas con formularios de User Registration
        if (is_page() && (has_shortcode(get_post()->post_content, 'user_registration_form') || 
            strpos($_SERVER['REQUEST_URI'], 'tarjeta-visita-registro') !== false)) {
            
            wp_enqueue_style(
                'cv-user-registration-fix',
                CV_FRONT_PLUGIN_URL . 'assets/css/user-registration-fix.css',
                array(),
                CV_FRONT_VERSION
            );
            
            error_log('✅ CV Front: CSS de User Registration Fix cargado');
        }
    }

    /**
     * Marca automáticamente como agente comercial a los registros provenientes del formulario de tarjeta.
     *
     * @param int $user_id
     */
    public function maybe_mark_virtual_vendor(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        $form_slug    = get_user_meta($user_id, 'user_registration_form_slug', true);
        $form_id_meta = get_user_meta($user_id, 'user_registration_form_id', true);
        $ur_form_id   = get_user_meta($user_id, 'ur_form_id', true);

        $allowed_slugs = [
            'tarjeta-visita-registro',
            'tarjeta-de-visita',
            'tarjeta-visita',
            'formulario-por-defecto',
        ];

        $allowed_ids = [
            'tarjeta_de_visita_form',
            'tarjeta_visita_registro',
            261,
        ];

        $is_tarjeta_form = false;

        if (is_string($form_slug) && in_array($form_slug, $allowed_slugs, true)) {
            $is_tarjeta_form = true;
        }

        if (!$is_tarjeta_form && is_string($form_id_meta) && in_array($form_id_meta, $allowed_ids, true)) {
            $is_tarjeta_form = true;
        }

        if (!$is_tarjeta_form && is_numeric($ur_form_id)) {
            if (in_array((int) $ur_form_id, array_map('intval', $allowed_ids), true)) {
                $is_tarjeta_form = true;
            } else {
                $slug_from_post = get_post_field('post_name', (int) $ur_form_id);
                if ($slug_from_post && in_array($slug_from_post, $allowed_slugs, true)) {
                    $is_tarjeta_form = true;
                }
            }
        }

        if (!$is_tarjeta_form) {
            return;
        }

        // Asegurar rol wcfm_vendor/comerciales
        $user = get_user_by('id', $user_id);
        if ($user instanceof \WP_User) {
            if (!in_array('wcfm_vendor', $user->roles, true)) {
                $user->add_role('wcfm_vendor');
            }
            if (!in_array('comerciales', $user->roles, true)) {
                $user->add_role('comerciales');
            }
        }

        if (class_exists('\Cv\ProductCategorization\Admin\VendorVirtual')) {
            \Cv\ProductCategorization\Admin\VendorVirtual::handle_settings_save($user_id, ['cv_virtual_vendor' => 'yes']);
        } else {
            update_user_meta($user_id, 'cv_vendor_virtual_agent', 'yes');
        }

        $sector_terms = get_user_meta($user_id, 'cv_vendor_sector_terms', true);
        if (!is_array($sector_terms)) {
            $sector_terms = [];
        }

        $sector_terms = array_map('intval', $sector_terms);
        $sector_terms = array_unique(array_merge($sector_terms, $this->get_agent_sector_ids()));

        update_user_meta($user_id, 'cv_vendor_sector_terms', $sector_terms);
    }

    /**
     * @return array<int,int>
     */
    private function get_agent_sector_ids(): array {
        $ids = [];

        $representacion = get_term_by('slug', 'representacion-comercial', 'product_cat');
        if ($representacion && !is_wp_error($representacion)) {
            $ids[] = (int) $representacion->term_id;
        }

        $agente = get_term_by('slug', 'agente-comercial', 'product_cat');
        if ($agente && !is_wp_error($agente)) {
            $ids[] = (int) $agente->term_id;
        }

        return $ids;
    }
}


