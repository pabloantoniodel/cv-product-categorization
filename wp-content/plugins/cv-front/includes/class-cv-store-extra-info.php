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
     * Constructor
     */
    public function __construct() {
        // Hook para listado de tiendas
        add_action('wcfmmp_store_list_after_store_info', array($this, 'store_list_extra_info'), 10, 2);
        
        // Hook para header de tienda individual
        add_action('after_wcfmmp_store_header_info', array($this, 'store_header_extra_info'), 10, 1);
    }
    
    /**
     * Mostrar información extra en listado de tiendas
     * 
     * @param int $vendor_id ID del vendedor
     * @param array $store_info Información de la tienda
     */
    public function store_list_extra_info($vendor_id, $store_info) {
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
        
        $site_url = get_user_meta($vendor_id, 'pagina-web', true);
        $telefono_whatsapp = get_user_meta($vendor_id, 'telefono-whatsapp', true);
        
        // Obtener datos del usuario para enlaces
        $user = get_user_by('id', $vendor_id);
        if (!$user) {
            return;
        }
        
        $link_mi_red = $user->user_login;
        $link_mi_tarjeta = "https://ciudadvirtual.app/card/" . $user->user_login;
        
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
}

// Inicializar
new CV_Store_Extra_Info();

