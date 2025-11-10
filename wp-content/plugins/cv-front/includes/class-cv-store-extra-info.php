<?php
/**
 * Información extra en listado y header de tiendas
 * 
 * Añade campos personalizados en:
 * - Listado de tiendas (wcfmmp_store_list_after_store_info)
 * - Header de tienda individual (after_wcfmmp_store_header_info)
 * 
 * Campos mostrados:
 * - Página web
 * - WhatsApp (chat directo)
 * - Tarjeta de visita (enlace)
 * - QR Code para afiliación
 * 
 * @package CV_Front
 * @since 1.0.0
 * Migrado desde Snippet #7
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Store_Extra_Info {

    /**
     * Controla la impresión del estilo para badges.
     *
     * @var bool
     */
    private static $printed_virtual_style = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook para listado de tiendas
        add_action('wcfmmp_store_list_after_store_info', array($this, 'store_list_extra_info'), 10, 2);
        
        // Hook para header de tienda individual
        add_action('after_wcfmmp_store_header_info', array($this, 'store_header_extra_info'), 10, 1);

        // Ocultar tarjetas de agencias comerciales en el listado público
        add_filter('wcfmmp_store_list_card_valid', array($this, 'filter_virtual_store_cards'));
        add_filter('wcfmmp_exclude_vendors_list', array($this, 'exclude_virtual_vendors_from_list'), 10, 2);
    }
    
    /**
     * Mostrar información extra en listado de tiendas
     * 
     * @param int $vendor_id ID del vendedor
     * @param array $store_info Información de la tienda
     */
    public function store_list_extra_info($vendor_id, $store_info) {
        $vendor_id = (int) $vendor_id;
        $is_virtual = $this->is_virtual_vendor($vendor_id);

        if ($is_virtual) {
            $this->ensure_virtual_style();
            ?>
            <p class="store-phone">
                <span class="cv-store-badge is-virtual" title="Atención comercial sin tienda física">
                    🛰️ Agente comercial • Atención online
                </span>
            </p>
            <?php
        }

        $site_url = get_user_meta($vendor_id, 'pagina-web', true);
        $telefono_whatsapp = get_user_meta($vendor_id, 'telefono-whatsapp', true);
        
        // Página web
        if ($site_url) {
            $site_url_href = $site_url;
            if (strpos($site_url_href, 'http') === false) {
                $site_url_href = "http://" . $site_url_href;
            }
            ?>
            <p class="store-phone">
                <i class="wcfmfa fa-globe" aria-hidden="true"></i>
                &nbsp;<a target="_blank" href="<?php echo esc_url($site_url_href); ?>"><?php echo esc_html($site_url); ?></a>
            </p>
            <?php
        }
        
        // WhatsApp
        if ($telefono_whatsapp) {
            $whatsapphref = "https://wa.me/" . $telefono_whatsapp . "&text=Solicito informacion";
            ?>
            <p class="store-phone">
                <i class="wcfmfa fa-phone" aria-hidden="true"></i>
                &nbsp;<a target="_blank" href="<?php echo esc_url($whatsapphref); ?>">CHAT WHATSAPP</a>
            </p>
            <?php
        }
    }
    
    /**
     * Mostrar información extra en header de tienda individual
     * 
     * @param int $vendor_id ID del vendedor
     */
    public function store_header_extra_info($vendor_id) {
        global $indeed_db;
        
        $vendor_id = (int) $vendor_id;
        $site_url = get_user_meta($vendor_id, 'pagina-web', true);
        $telefono_whatsapp = get_user_meta($vendor_id, 'telefono-whatsapp', true);
        
        // Obtener datos del usuario para enlaces
        $user = get_user_by('id', $vendor_id);
        if (!$user) {
            return;
        }
        
        $link_mi_red = $user->user_login;
        $link_mi_tarjeta = "https://ciudadvirtual.app/card/" . $user->user_login;
        
        $is_virtual = $this->is_virtual_vendor($vendor_id);
        if ($is_virtual) {
            $this->ensure_virtual_style();
            ?>
            <div class="store_info_parallal">
                <span class="cv-store-badge is-virtual" title="Atención comercial sin tienda física">
                    🛰️ Agente comercial • Atención online
                </span>
            </div>
            <div class="spacer"></div>
            <?php
        }

        // Página web
        if ($site_url) {
            $site_url_href = $site_url;
            if (strpos($site_url_href, 'http') === false) {
                $site_url_href = "http://" . $site_url_href;
            }
            ?>
            <div class="store_info_parallal">
                <i class="wcfmfa fa-globe" aria-hidden="true"></i>
                <span>
                    <a target="_blank" href="<?php echo esc_url($site_url_href); ?>"><?php echo esc_html($site_url); ?></a>
                </span>
            </div>
            <div class="spacer"></div>
            <?php
        }
        
        // WhatsApp
        if ($telefono_whatsapp) {
            $whatsapphref = "https://wa.me/" . $telefono_whatsapp . "&text=Solicito informacion";
            ?>
            <p class="store-phone">
                <i class="wcfmfa fa-phone" aria-hidden="true"></i>
                &nbsp;<a target="_blank" href="<?php echo esc_url($whatsapphref); ?>">CHAT WHATSAPP</a>
            </p>
            <?php
        }
        
        // Botón de tarjeta de visita (SIEMPRE visible)
        ?>
        <p class="store-phone">
            <a class="button" target="_blank" href="<?php echo esc_url($link_mi_tarjeta); ?>">Tarjeta de visita</a>
        </p>
        
        <script>
            function showQR() {
                jQuery('#qrCode').css("display", "flex");
            }
        </script>
        
        <div id="qrCode" style="height: 200px; background-color: #EEE; display:flex; justify-content: center; align-items: center; display:none">
            <p class="store-phone">
                <a data-store="<?php echo esc_attr($vendor_id); ?>" data-product="0" href="#" onclick="showQR();return false;">
                    <?php echo do_shortcode('[kaya_qrcode content="https://ciudadvirtual.app/become-an-affiliate/?ref=' . urlencode($link_mi_red) . '"]'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    /**
     * Determina si un vendedor está marcado como agente comercial sin tienda física.
     */
    private function is_virtual_vendor(int $vendor_id): bool {
        if ($vendor_id <= 0) {
            return false;
        }

        if (class_exists('\Cv\ProductCategorization\Admin\VendorVirtual')) {
            return \Cv\ProductCategorization\Admin\VendorVirtual::is_virtual($vendor_id);
        }

        return (bool) get_user_meta($vendor_id, 'cv_vendor_virtual_agent', true);
    }

    /**
     * Imprime estilos para las insignias personalizadas una única vez.
     */
    private function ensure_virtual_style(): void {
        if (self::$printed_virtual_style) {
            return;
        }

        self::$printed_virtual_style = true;
        ?>
        <style>
            .cv-store-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.02em;
                background: #e2e8f0;
                color: #1e293b;
            }
            .cv-store-badge.is-virtual {
                background: linear-gradient(120deg, #6366f1 0%, #3b82f6 100%);
                color: #ffffff;
                box-shadow: 0 8px 18px rgba(79, 70, 229, 0.25);
            }
        </style>
        <?php
    }

    /**
     * Evita que los agentes comerciales sin tienda aparezcan en la parrilla de comercios.
     *
     * @param mixed $store_id Valor original del filtro (normalmente el ID del vendedor).
     * @return mixed False si debe ocultarse, o el valor original en cualquier otro caso.
     */
    public function filter_virtual_store_cards($store_id) {
        $vendor_id = (int) $store_id;
        if ($vendor_id > 0 && $this->is_virtual_vendor($vendor_id)) {
            return false;
        }

        return $store_id;
    }

    /**
     * Añade los agentes comerciales a la lista de vendedores excluidos en la parrilla de comercios.
     *
     * @param array<int,int|string> $exclude
     * @param array<string,mixed>   $search_data
     * @return array<int,int>
     */
    public function exclude_virtual_vendors_from_list($exclude, $search_data): array {
        if (!is_array($exclude)) {
            $exclude = [];
        }

        $virtualIds = $this->get_virtual_vendor_ids();
        if (empty($virtualIds)) {
            return array_map('intval', $exclude);
        }

        $exclude = array_map('intval', $exclude);
        $exclude = array_merge($exclude, $virtualIds);

        return array_values(array_unique($exclude));
    }

    /**
     * Obtiene la lista cacheada de IDs de agentes comerciales.
     *
     * @return array<int,int>
     */
    private function get_virtual_vendor_ids(): array {
        $cached = wp_cache_get('cv_virtual_vendor_ids', 'cv_front');
        if ($cached !== false && is_array($cached)) {
            return array_map('intval', $cached);
        }

        $roles = apply_filters('wcfmmp_allwoed_vendor_user_roles', ['wcfm_vendor']);
        $roleList = is_array($roles) ? $roles : ['wcfm_vendor'];
        $roleList = array_filter(array_map('strval', $roleList));
        if (empty($roleList)) {
            $roleList = ['wcfm_vendor', 'vendor', 'seller', 'shop_vendor'];
        }

        $users = get_users([
            'role__in'   => $roleList,
            'meta_key'   => class_exists('\Cv\ProductCategorization\Admin\VendorVirtual') ? \Cv\ProductCategorization\Admin\VendorVirtual::META_KEY : 'cv_virtual_vendor',
            'meta_value' => 'yes',
            'fields'     => 'ID',
            'number'     => -1,
        ]);

        $ids = array_map('intval', $users);

        wp_cache_set('cv_virtual_vendor_ids', $ids, 'cv_front', MINUTE_IN_SECONDS * 5);

        return $ids;
    }
}

// Inicializar
new CV_Store_Extra_Info();

