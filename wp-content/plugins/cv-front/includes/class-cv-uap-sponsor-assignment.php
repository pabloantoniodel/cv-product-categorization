<?php
/**
 * Asignación automática de sponsors en UAP para clientes
 * 
 * Cuando un cliente consulta o compra, se le asigna el vendedor como sponsor.
 * También se maneja la referencia desde ref-tarjeta.
 *
 * @package CV_Front
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_UAP_Sponsor_Assignment {

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
        // Asignar sponsor después de registrar usuario desde consulta
        add_action('wcfm_after_enquiry_submit', array($this, 'assign_sponsor_from_enquiry'), 10, 6);
        
        // Asignar sponsor cuando se procesa un pedido
        add_action('woocommerce_checkout_order_processed', array($this, 'assign_sponsor_from_order'), 10, 3);
        
        // Asignar sponsor cuando se registra un usuario con referencia
        add_action('user_register', array($this, 'assign_sponsor_from_reference'), 20, 1);
    }

    /**
     * Asignar sponsor desde consulta
     * 
     * @param int $enquiry_id ID de la consulta
     * @param int $customer_id ID del cliente
     * @param int $product_id ID del producto
     * @param int $vendor_id ID del vendedor
     * @param string $message Mensaje de la consulta
     * @param array $extra_data Datos adicionales
     */
    public function assign_sponsor_from_enquiry($enquiry_id, $customer_id, $product_id, $vendor_id, $message, $extra_data = array()) {
        if (!$customer_id || !$vendor_id) {
            return;
        }

        error_log('🔄 CV UAP Sponsor: Asignando sponsor desde consulta - Cliente: ' . $customer_id . ', Vendedor: ' . $vendor_id);

        $this->assign_vendor_as_sponsor($customer_id, $vendor_id);
    }

    /**
     * Asignar sponsor desde pedido
     * 
     * @param int $order_id ID del pedido
     * @param array $posted_data Datos del pedido
     * @param WC_Order $order Objeto del pedido
     */
    public function assign_sponsor_from_order($order_id, $posted_data, $order) {
        if (!$order instanceof WC_Order) {
            return;
        }

        $customer_id = $order->get_user_id();
        if (!$customer_id) {
            return;
        }

        // Obtener el primer producto del pedido
        $items = $order->get_items();
        if (empty($items)) {
            return;
        }

        $first_item = reset($items);
        $product_id = $first_item->get_product_id();
        
        // Obtener vendor del producto
        if (function_exists('wcfm_get_vendor_id_by_post')) {
            $vendor_id = wcfm_get_vendor_id_by_post($product_id);
        } else {
            $vendor_id = get_post_field('post_author', $product_id);
        }

        if (!$vendor_id || $vendor_id <= 0) {
            return;
        }

        error_log('🔄 CV UAP Sponsor: Asignando sponsor desde pedido - Cliente: ' . $customer_id . ', Vendedor: ' . $vendor_id);

        $this->assign_vendor_as_sponsor($customer_id, $vendor_id);
    }

    /**
     * Asignar sponsor desde referencia (ref-tarjeta) al registrarse
     * 
     * @param int $user_id ID del usuario recién registrado
     */
    public function assign_sponsor_from_reference($user_id) {
        // Verificar si viene con referencia en cookie o parámetro
        $referido = $this->get_referido_from_request();
        
        if (!$referido) {
            return;
        }

        error_log('🔄 CV UAP Sponsor: Asignando sponsor desde referencia - Usuario: ' . $user_id . ', Referido: ' . $referido);

        // Buscar usuario referido
        $referido_user = $this->find_user_by_reference($referido);
        
        if ($referido_user) {
            $referido_id = $referido_user->ID;
            error_log('✅ CV UAP Sponsor: Usuario referido encontrado - ID: ' . $referido_id);
            
            // Asignar como sponsor
            $this->assign_user_as_sponsor($user_id, $referido_id);
        } else {
            error_log('⚠️ CV UAP Sponsor: Usuario referido no encontrado: ' . $referido);
        }
    }

    /**
     * Asignar vendedor como sponsor del cliente
     * 
     * @param int $customer_id ID del cliente
     * @param int $vendor_id ID del vendedor
     */
    private function assign_vendor_as_sponsor($customer_id, $vendor_id) {
        global $indeed_db;

        if (!class_exists('Indeed_Db') || !$indeed_db) {
            error_log('⚠️ CV UAP Sponsor: UAP no disponible');
            return;
        }

        // Verificar si el cliente es afiliado, si no lo es, registrarlo
        $customer_affiliate_id = $indeed_db->get_affiliate_id_by_wpuid($customer_id);
        
        if (!$customer_affiliate_id) {
            // Registrar como afiliado
            $customer_affiliate_id = $indeed_db->save_affiliate($customer_id);
            
            if (!$customer_affiliate_id) {
                error_log('❌ CV UAP Sponsor: No se pudo registrar cliente como afiliado');
                return;
            }
            
            error_log('✅ CV UAP Sponsor: Cliente registrado como afiliado - ID: ' . $customer_affiliate_id);
        }

        // Verificar si el vendedor es afiliado
        $vendor_affiliate_id = $indeed_db->get_affiliate_id_by_wpuid($vendor_id);
        
        if (!$vendor_affiliate_id) {
            error_log('⚠️ CV UAP Sponsor: Vendedor no es afiliado - ID: ' . $vendor_id);
            return;
        }

        // Verificar si ya tiene un padre MLM asignado
        $current_parent = $indeed_db->mlm_get_parent($customer_affiliate_id);
        
        if ($current_parent && $current_parent > 0 && $current_parent != $vendor_affiliate_id) {
            error_log('ℹ️ CV UAP Sponsor: Cliente ya tiene sponsor (' . $current_parent . '), no se modifica');
            return;
        }

        // Asignar vendedor como sponsor (solo si no tiene padre o es el mismo)
        if ($current_parent != $vendor_affiliate_id) {
            // Usar add_new_mlm_relation que actualiza o inserta la relación
            $indeed_db->add_new_mlm_relation($customer_affiliate_id, $vendor_affiliate_id);
            
            error_log('✅ CV UAP Sponsor: Sponsor asignado - Cliente (Affiliate ' . $customer_affiliate_id . ') → Vendedor (Affiliate ' . $vendor_affiliate_id . ')');
        } else {
            error_log('ℹ️ CV UAP Sponsor: Cliente ya tiene este sponsor asignado');
        }
    }

    /**
     * Asignar usuario como sponsor de otro usuario
     * 
     * @param int $user_id ID del usuario
     * @param int $sponsor_user_id ID del sponsor
     */
    private function assign_user_as_sponsor($user_id, $sponsor_user_id) {
        global $indeed_db;

        if (!class_exists('Indeed_Db') || !$indeed_db) {
            error_log('⚠️ CV UAP Sponsor: UAP no disponible');
            return;
        }

        // Verificar si el usuario es afiliado, si no lo es, registrarlo
        $affiliate_id = $indeed_db->get_affiliate_id_by_wpuid($user_id);
        
        if (!$affiliate_id) {
            // Registrar como afiliado
            $affiliate_id = $indeed_db->save_affiliate($user_id);
            
            if (!$affiliate_id) {
                error_log('❌ CV UAP Sponsor: No se pudo registrar usuario como afiliado');
                return;
            }
        }

        // Verificar si el sponsor es afiliado
        $sponsor_affiliate_id = $indeed_db->get_affiliate_id_by_wpuid($sponsor_user_id);
        
        if (!$sponsor_affiliate_id) {
            error_log('⚠️ CV UAP Sponsor: Sponsor no es afiliado - ID: ' . $sponsor_user_id);
            return;
        }

        // Verificar si ya tiene un padre MLM asignado
        $current_parent = $indeed_db->mlm_get_parent($affiliate_id);
        
        if ($current_parent && $current_parent > 0 && $current_parent != $sponsor_affiliate_id) {
            error_log('ℹ️ CV UAP Sponsor: Usuario ya tiene sponsor (' . $current_parent . '), no se modifica');
            return;
        }

        // Asignar sponsor (solo si no tiene padre o es el mismo)
        if ($current_parent != $sponsor_affiliate_id) {
            $indeed_db->add_new_mlm_relation($affiliate_id, $sponsor_affiliate_id);
            
            error_log('✅ CV UAP Sponsor: Sponsor asignado - Usuario (Affiliate ' . $affiliate_id . ') → Sponsor (Affiliate ' . $sponsor_affiliate_id . ')');
        }
    }

    /**
     * Obtener referido desde request (cookie, GET, POST)
     * 
     * @return string|false Referido o false si no se encuentra
     */
    private function get_referido_from_request() {
        // 1. Buscar en user_registration_referido (ya guardado en meta)
        // Esto se maneja en otro lugar, pero lo dejamos como referencia
        
        // 2. Buscar en ref-tarjeta (GET/POST)
        if (isset($_GET['ref-tarjeta']) && !empty($_GET['ref-tarjeta'])) {
            return sanitize_text_field($_GET['ref-tarjeta']);
        }
        
        if (isset($_POST['ref-tarjeta']) && !empty($_POST['ref-tarjeta'])) {
            return sanitize_text_field($_POST['ref-tarjeta']);
        }

        // 3. Buscar en cookie de UAP
        if (isset($_COOKIE['uap_referral']) && !empty($_COOKIE['uap_referral'])) {
            $cookie_data = unserialize(stripslashes($_COOKIE['uap_referral']));
            if (!empty($cookie_data['affiliate_id'])) {
                // Convertir affiliate_id a user_id
                global $indeed_db;
                if ($indeed_db && method_exists($indeed_db, 'get_uid_by_affiliate_id')) {
                    $user_id = $indeed_db->get_uid_by_affiliate_id(intval($cookie_data['affiliate_id']));
                    if ($user_id) {
                        return $user_id;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Buscar usuario por referencia (puede ser ID, email, login, etc.)
     * 
     * @param string|int $referido Referido a buscar
     * @return WP_User|false Usuario encontrado o false
     */
    private function find_user_by_reference($referido) {
        if (empty($referido)) {
            return false;
        }

        // Si es numérico, buscar por ID
        if (is_numeric($referido)) {
            $user = get_user_by('ID', intval($referido));
            if ($user) {
                return $user;
            }
        }

        // Si contiene @, buscar por email
        if (strpos($referido, '@') !== false) {
            $user = get_user_by('email', $referido);
            if ($user) {
                return $user;
            }
        }

        // Buscar por login
        $user = get_user_by('login', $referido);
        if ($user) {
            return $user;
        }

        return false;
    }
}

// Inicializar
new CV_UAP_Sponsor_Assignment();

