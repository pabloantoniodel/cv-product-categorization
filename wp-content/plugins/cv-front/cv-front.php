<?php
/**
 * Plugin Name: Ciudad Virtual - Frontend Enhancements
 * Plugin URI: https://ciudadvirtual.app
 * Description: Mejoras visuales para el frontend: Sistema de burbujas animadas para geolocalización de tiendas, login moderno de WooCommerce y más
 * Version: 3.4.1
 * Author: Ciudad Virtual
 * Author URI: https://ciudadvirtual.app
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cv-front
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * 
 * Changelog:
 * 3.2.1 - Mejorada regex para eliminar enlaces de imágenes (prioridad 999, patrón mejorado)
 * 3.2.0 - Desactivar enlaces en imágenes de descripción de productos (evita clics fuera)
 * 3.1.1 - Traducción emails de Ultimate Affiliate Pro: Password changed y Password reset
 * 3.1.0 - Pestañas de productos modernas: Gradientes morados, sombras, iconos FontAwesome, responsive
 * 3.0.9 - Traducción automática de emails WooCommerce: Reset Password y Password Changed al español
 * 3.0.8 - Fix: Tabla de reseñas WCFM con scroll horizontal para ver botones Aprobar/Borrar
 * 3.0.7 - Fix: Logs de debugging para consultas de productos (enquiries)
 * 3.0.6 - Fix: AWS muestra mismo número de productos que WooCommerce (16 en lugar de 3)
 * 3.0.5 - Fix: Ancho columna "Nombre" reducido a 15% en tabla de usuarios del admin
 * 3.0.4 - Feature: Botón toggle limpia localStorage "No mostrar de nuevo" del popup de notificaciones
 * 3.0.3 - Feature: Botón flotante toggle notificaciones (campana/campana-tachada) con AJAX + permiso navegador
 * 3.0.2 - Feature: Campana de notificaciones en My Account para vendedores
 * 3.0.1 - Feature: Toggle notificaciones WCFM (botón desactivar + flotante reactivar + permiso navegador)
 * 3.0.0 - Fix CRÍTICO: Eliminado límite de 50 productos en WCFM (mostraba solo 100 de 3,061)
 * 2.9.9 - Fix: Prioridad alta (999) + validación tipo post para 6 artículos por página
 * 2.9.8 - Feature: 6 artículos por página en archivo de noticias
 * 2.9.7 - Fix: Búsqueda sin tildes + título y excerpt (AUTÓNOMOS = autonomos, más flexible)
 * 2.9.6 - Feature: Filtro ESTRICTO - solo keywords en TÍTULO (de 202 posts, solo 13 con keywords en título)
 * 2.9.5 - Fix: Corregida consulta meta (no filtrar por valor='1') y lógica de filtrado
 * 2.9.4 - Feature: Ocultar posts importados sin keywords en frontend (no se borran, solo se ocultan)
 * 2.9.3 - Feature: Filtro inteligente de keywords (plural/singular) en título y contenido
 * 2.9.2 - Fix: Reemplazar get_page_by_title obsoleta (WP 6.2+)
 * 2.9.1 - Feature: Búsquedas múltiples: autónomos, emprendedores, comercio, ciudadvirtual.app
 * 2.9.0 - Feature: Importación desde noticias.radiocomercio.org además de radiocomercio.com
 * 2.8.9 - Feature: Integración con cv-stats para rastrear consultas de contacto
 * 2.8.8 - Fix: Selectores más específicos para evitar mezcla de imágenes en header
 * 2.8.7 - Fix: Botón X para cerrar popup de instrucciones + botón flotante mejorado
 * 2.8.6 - Fix: Botón cerrar scanner QR ahora cierra correctamente el popup
 * 2.8.5 - Feature: Fallback a miniaturas de YouTube cuando no hay imagen destacada
 * 2.8.4 - Fix: Reforzar estilos inline para bloques de Gutenberg con !important
 * 2.8.3 - Fix: Eliminar filtro de noticias que interfería con block queries de Gutenberg
 * 2.8.2 - Filtro global: Solo mostrar artículos de Radio Comercio en todas las páginas de blog (REVERTIDO)
 * 2.8.1 - Customizador de menús: Renombrar "Noticias CV" a "Noticias" y eliminar duplicado
 * 2.8.0 - Cron diario para importación automática de artículos de Radio Comercio sobre autónomos
 * 2.7.2 - Comentarios desactivados en artículos de Radio Comercio + títulos limpios
 * 2.7.1 - Estilos modernos para blog: lista de artículos y posts individuales
 * 2.7.0 - Visualizador de RSS Feed con diseño moderno y cache
 * 2.6.0 - Nuevo rol 'Comerciales' para vendors con capacidades personalizadas
 * 2.5.9 - Página de contacto con 3 categorías: Plataforma, Comercio y WhatsApp flotante
 * 2.5.8 - Info extra en tiendas: Página web, WhatsApp, tarjeta de visita y QR (Snippet #7)
 * 2.5.6 - Gestión completa: Añadir/Editar/Eliminar videos con títulos (Ajustes > Galería de Videos)
 * 2.5.5 - Configuración admin para añadir Shorts manualmente (Ajustes > Galería de Videos)
 * 2.5.2 - Soporte para Shorts de YouTube en galería de canal (scraping de /shorts)
 * 2.5.1 - Vuelto a modal original: Video grande (no fullscreen) con botón cerrar visible
 * 2.5.0 - iOS: Abrir videos directamente en YouTube (nueva ventana) por limitaciones fullscreen
 * 2.4.9 - Detectar iOS y desactivar autoplay para evitar fullscreen nativo
 * 2.4.8 - Iframe YouTube directo con playsinline en HTML (solución iOS fullscreen)
 * 2.4.7 - Videos con playsinline para iOS (evita fullscreen nativo y muestra botón cerrar)
 * 2.4.6 - Botón cerrar video más visible y grande (rojo con borde blanco)
 * 2.4.5 - Videos de tutorial se reproducen en pantalla completa automáticamente
 * 2.4.4 - Estilos modernos para botones en listas de productos (wrapper glassmorphism)
 * 2.4.0 - Diseño moderno COMPLETO header tienda: Título, botón, código, distancia
 * 2.4.0 - Gradientes modernos: Morado (botón), Verde (distancia), Gris (fondo)
 * 2.4.0 - Responsive total: Flexbox desktop (horizontal) + columna móvil (vertical)
 * 2.4.0 - Efectos hover: translateY(-2px) + shadows dinámicas + transiciones smooth
 * 2.4.0 - Código comercio: Courier New monospace + fondo semitransparente + border
 * 2.4.0 - Info adicional (teléfono, web): Cards blancos + hover effects + iconos morados
 * 2.3.6 - WCFM menú desktop +20% ancho (200px → 240px)
 * 2.3.6 - Búsqueda: mantiene filtro distancia + auto-scroll + "Todas"
 * 2.3.6 - Fix buscador: MutationObserver fuerza "Todas" (anti-cache)
 * 2.3.5 - Menú móvil: Centrado vertical pantalla + Li separados 12px
 * 2.3.4 - Menú móvil: padding-top 85px en ul#menu-principal (evita superposición)
 * 2.3.3 - Fix: Menú móvil simplificado - Quitado margin-top y z-index excesivo
 * 2.3.2 - Menú móvil: margin-top 85px + z-index 999999 + Textos centrados vertical
 * 2.3.1 - Menú móvil minimalista: Fondo blanco + Textos morados + Sin botones
 * 2.3.0 - Menú móvil moderno: Gradiente morado + Animaciones stagger + Efectos hover
 * 2.2.3 - Iconos móvil sin fondo (background: transparent) - Solo color morado
 * 2.2.2 - Iconos móvil morados (scale 1.35) + Fondo semi-transparente + Hover effects
 * 2.2.1 - JavaScript traductor Wallet (translate-wallet.js) + Snippet 68 con HTML buffering
 * 2.2.0 - Iconos carrito y hamburguesa 50% más grandes en móvil (scale 1.5)
 * 2.1.8 - Quitar margin-top del botón Contacto (#from_my_function)
 * 2.1.7 - Añadido texto \"CANTIDAD:\" con ::before si no existe label
 * 2.1.6 - Fix input cantidad: Selectores específicos + Font 20px negro + Borde morado 3px
 * 2.1.5 - Campo cantidad productos más visible (label + input mejorado)
 * 2.1.4 - Ocultar logo/tagline y buscador en Mi cuenta móvil sin login
 * 2.1.3 - Ocultar buscador en Mi cuenta (móvil) cuando no está logueado
 * 2.1.2 - Fix: Centrado títulos móvil + Breadcrumb oculto globalmente
 * 2.1.1 - Títulos más pequeños (1.25rem) + centrados en móviles
 * 2.1.0 - Títulos modernos con gradientes (.entry-title) + Snippet campo teléfono consultas
 * 2.0.3 - Fix: CSS para ocultar subcategorías debajo productos + Reactivadas subcategorías arriba
 * 2.0.2 - Imágenes 300x300 + Sistema completo generación IA
 * 2.0.1 - Diseño moderno para subcategorías: Grid con gradientes de colores y efectos hover
 * 2.0.0 - REESCRITURA TOTAL: Enlaces de categorías ahora usan formato de búsqueda (PHP + JS)
 * 1.9.8 - Fix: Interceptar todos los enlaces .product-categories a (no solo > li > a)
 * 1.9.7 - Fix: Cargar category-modal.js siempre (detección interna en JS)
 * 1.9.6 - Añadido botón "Mostrar todos" al modal de subcategorías
 * 1.9.5 - Modal flotante de subcategorías en Market + Integrado snippet 37
 * 1.9.4 - Fix DEFINITIVO: NO ocultar nada en categorías, solo limpiar sessionStorage y redirección PHP
 * 1.9.3 - Fix: Limpiar sessionStorage en categorías para evitar cv-map-hidden residual
 * 1.9.2 - Fix crítico: Colapso de mapa solo en shop, no en categorías (causaba productos invisibles)
 * 1.9.1 - Fix: Ocultar solo formulario y mapa, no wrapper completo
 * 1.9.0 - Fix WCFM: Deshabilitar filtro de distancia en categorías (PHP + JS, bug de WCFM)
 * 1.8.3 - Fix: Incluir radius_lat y radius_lng al recargar (WCFM requiere los 3 parámetros)
 * 1.8.2 - Fix: Recarga página con radius_range=1200 si no hay productos (en lugar de solo ajustar slider)
 * 1.8.1 - Auto-ajuste de radio a 1200km en categorías de productos
 * 1.8.0 - Optimizaciones de rendimiento para WCFM: caché, carga lazy, límites de productos
 * 1.7.1 - Fix: Corregido posicionamiento del marcador de usuario en mapa de productos (iconAnchor)
 * 1.7.0 - Marcador de usuario en mapa + Zoom adaptativo según cantidad de tiendas
 * 1.6.0 - Proxy backend para Nominatim: caché, rate limiting y sin CORS
 * 1.5.0 - Colapso automático de mapa al filtrar + Botón restablecer distancia
 * 1.4.0 - Scroll automático en paginación, Mi Cuenta + Botón flotante volver arriba (móvil)
 * 1.3.3 - Imágenes de productos a ancho completo con proporción cuadrada
 * 1.3.2 - Botón filtrar con color azul y separación correcta
 * 1.3.1 - Reducido margen inferior del header del sitio
 * 1.3.0 - Fix para error AJAX en registro de vendedores WCFM
 * 1.2.0 - Centrado global de texto en todos los botones del sitio
 * 1.1.0 - Añadido sistema de login moderno para WooCommerce
 * 1.0.0 - Versión inicial con sistema de burbujas de geolocalización
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('CV_FRONT_VERSION', '3.5.1');
define('CV_FRONT_PLUGIN_FILE', __FILE__);
define('CV_FRONT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CV_FRONT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CV_FRONT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class CV_Front {
    
    /**
     * Instancia única del plugin
     */
    private static $instance = null;
    
    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        // Sistema de burbujas de geolocalización
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-store-bubbles.php';
        
        // Sistema de login moderno
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-modern-login.php';
        
        // Fix de AJAX para registro de vendedores
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-ajax-fix.php';
        
        // Proxy para Nominatim
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-nominatim-proxy.php';
        
        // Marcador de ubicación del usuario en el mapa
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-user-map-marker.php';
        
        // Optimizaciones de rendimiento para WCFM
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-wcfm-optimizer.php';
        // require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-wcfm-script-override.php'; // Ya no es necesario, se usa versión no minificada directamente en el plugin personalizado
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-wcfm-notifications-toggle.php';
        // DESACTIVADO: Movido a wcfm-radius-persistence v2.0.0
        // require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-geolocation-toggle.php';
        // require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-geolocation-default-disabled.php';
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-wcfm-reviews-fix.php';
        // DESACTIVADO TEMPORALMENTE: require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-wcfm-admin-access-fix.php';
        
        // Ajustar anchos de columnas en el admin
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-admin-column-fix.php';
        
        // Fix para Advanced Woo Search (AWS)
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-aws-fix.php';
        
        // Fix para sistema de consultas (Enquiry)
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-enquiry-fix.php';
        
        // Fix para filtro de radio en categorías
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-category-radius-fix.php';
        
        // Mostrar subcategorías en categorías
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-subcategories.php';
        
        // Campo de teléfono en formularios de consulta WCFM
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-enquiry-phone.php';
        
        // Botón y modal de consulta genérica
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-product-consultation.php';
        
        // Distancia en header de tienda (oculta en productos)
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-store-distance.php';
        
        // Avatar en Open Graph de tiendas (compartir en redes)
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-store-og-avatar.php';
        
        // Verificación de email y teléfono en reseñas
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-review-verification.php';
        
        // QR en header de tienda
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-store-qr.php';
        
        // Instrucciones QR
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-qr-instructions.php';
        
        // Galería de videos de YouTube
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-video-gallery.php';
        
        // Fix para formularios User Registration
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-user-registration-fix.php';
        
        // Padding superior para iconos header móvil
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-mobile-header-padding.php';
        
        // Normalizador de números de WhatsApp (añadir +34 si falta)
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-whatsapp-phone-normalizer.php';
        
        // Página de contacto con categorías
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-contact-page.php';
        
        // Rol de Comerciales
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-comerciales-role.php';
        
        // Desactivar enlaces en imágenes de productos
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-disable-product-image-links.php';
        
        // RSS Feed Display
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-rss-feed-display.php';
        
        // Estilos modernos para blog
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-blog-modern-styles.php';
        
        // Desactivar comentarios en Radio Comercio
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-disable-comments-radiocomercio.php';
        
        // Importación automática de Radio Comercio
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-radiocomercio-auto-import.php';
        
        // Customizador de menús
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-menu-customizer.php';
        
        // Fallback a miniaturas de YouTube
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-youtube-thumbnail-fallback.php';
        
        // Corrector automático de enlaces WhatsApp en listados y tiendas
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-store-whatsapp-fixer.php';
        
        // Estilos del selector de distancia en Comercios
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-store-radius-style.php';
        
        // Estilos para Login as User
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-login-as-user-style.php';
        
        // Ordenar usuarios por fecha de registro
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-users-order.php';
        
        // Información extra en tiendas (página web, WhatsApp, tarjeta, QR afiliación)
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-store-extra-info.php';
        
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-woocommerce-email-translator.php';
        
        require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-product-tabs-modern.php';
        
        // Configuración de galería de videos
        if (is_admin()) {
            require_once CV_FRONT_PLUGIN_DIR . 'includes/class-cv-video-gallery-settings.php';
        }
        
        // Estilos para botones en listas de productos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_loop_buttons_styles'));

        // DESACTIVADO: Movido a wcfm-radius-persistence v2.0.0
        // add_action('wp_enqueue_scripts', array($this, 'enqueue_geolocation_init'), 1);
        // add_action('wp_enqueue_scripts', array($this, 'enqueue_geolocation_manager'), 125);

        // Sincronización de geolocalización entre listados
        add_action('wp_enqueue_scripts', array($this, 'enqueue_geo_sync_script'), 105);

        // Fix para slider de radio con AJAX
        add_action('wp_enqueue_scripts', array($this, 'enqueue_radius_ajax_fix'), 110);

        // Límite de mapa a Península Ibérica
        add_action('wp_enqueue_scripts', array($this, 'enqueue_iberian_map_bounds_script'), 120);
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Inicializar sistema de burbujas
        new CV_Store_Bubbles();
        
        // Inicializar login moderno
        new CV_Modern_Login();
        
        // Inicializar fix de AJAX
        new CV_AJAX_Fix();
        
        // Inicializar proxy de Nominatim
        new CV_Nominatim_Proxy();
        
        // Inicializar marcador de usuario en mapa
        new CV_User_Map_Marker();
        
        // Inicializar optimizaciones de WCFM
        new CV_WCFM_Optimizer();
        // new CV_WCFM_Script_Override(); // Ya no es necesario
        
        // Inicializar fix de radio en categorías
        new CV_Category_Radius_Fix();
        
        // Inicializar subcategorías
        new CV_Subcategories();
        
        // Inicializar campo teléfono WCFM
        new CV_Enquiry_Phone();
        
        // Inicializar botón de consulta genérica
        new CV_Product_Consultation();
        
        // Inicializar distancia en header de tienda
        new CV_Store_Distance();

        // Estilo del selector de radio en comercios
        new CV_Store_Radius_Style();
        
        // Evitar mapas por defecto y exponer botón de comercios cercanos
        // new CV_Store_List_Lazy(); // REMOVED
        
        // Inicializar verificación de reseñas
        new CV_Review_Verification();
        
        // Inicializar QR en header de tienda
        new CV_Store_QR();
        
        // Inicializar instrucciones QR
        new CV_QR_Instructions();
        
        // Inicializar fix de User Registration
        new CV_User_Registration_Fix();
        
        // Inicializar padding header móvil
        new CV_Mobile_Header_Padding();
        
        // Inicializar normalizador de WhatsApp
        new CV_WhatsApp_Phone_Normalizer();
        
        // Inicializar corrector de enlaces WhatsApp
        new CV_Store_WhatsApp_Fixer();
        
        // Activación del plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Desactivación del plugin
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Activar plugin
     */
    public function activate() {
        error_log('✅ CV Front: Plugin activado correctamente');
    }
    
    /**
     * Desactivar plugin
     */
    public function deactivate() {
        error_log('ℹ️ CV Front: Plugin desactivado');
    }
    
    /**
     * Cargar estilos para botones en listas de productos
     */
    public function enqueue_loop_buttons_styles() {
        wp_enqueue_style(
            'cv-front-loop-buttons',
            CV_FRONT_PLUGIN_URL . 'assets/css/loop-buttons.css',
            array(),
            CV_FRONT_VERSION
        );
    }

    /**
     * Inicialización de geolocalización (desactivada por defecto)
     */
    public function enqueue_geolocation_init() {
        // Cargar en páginas de shop, categorías, tiendas
        if (is_shop() || is_product_category() || is_product_tag() || 
            (function_exists('wcfmmp_is_stores_list_page') && wcfmmp_is_stores_list_page())) {
            
            wp_enqueue_script(
                'cv-front-geolocation-init',
                CV_FRONT_PLUGIN_URL . 'assets/js/geolocation-init.js',
                array('jquery'),
                CV_FRONT_VERSION,
                false // Cargar en el header para ejecutar antes
            );
        }
    }

    /**
     * Sincronizar preferencias de geolocalización entre distintas vistas
     */
    public function enqueue_geo_sync_script() {
        wp_enqueue_script(
            'cv-front-geo-sync',
            CV_FRONT_PLUGIN_URL . 'assets/js/geo-sync.js',
            array('jquery'),
            CV_FRONT_VERSION,
            true
        );
    }

    /**
     * Fix para que el slider de radio funcione correctamente con AJAX
     */
    public function enqueue_radius_ajax_fix() {
        // Cargar en páginas de shop, categorías, tiendas
        if (is_shop() || is_product_category() || is_product_tag() || 
            (function_exists('wcfmmp_is_stores_list_page') && wcfmmp_is_stores_list_page())) {
            
            wp_enqueue_script(
                'cv-front-radius-ajax-fix',
                CV_FRONT_PLUGIN_URL . 'assets/js/radius-ajax-fix.js',
                array('jquery'),
                CV_FRONT_VERSION,
                true
            );
        }
    }

    /**
     * Limitar interacción del mapa de tiendas a la Península Ibérica
     */
    public function enqueue_iberian_map_bounds_script() {
        if (!wp_script_is('wcfmmp_product_list_js', 'enqueued')) {
            return;
        }

        wp_enqueue_script(
            'cv-front-map-iberian-bounds',
            CV_FRONT_PLUGIN_URL . 'assets/js/map-iberian-bounds.js',
            array('wcfmmp_product_list_js'),
            CV_FRONT_VERSION,
            true
        );
    }

    /**
     * Gestor de visibilidad de elementos de geolocalización
     */
    public function enqueue_geolocation_manager() {
        // Cargar en páginas de shop, categorías, tiendas
        if (is_shop() || is_product_category() || is_product_tag() || 
            (function_exists('wcfmmp_is_stores_list_page') && wcfmmp_is_stores_list_page())) {
            
            wp_enqueue_script(
                'cv-front-geolocation-manager',
                CV_FRONT_PLUGIN_URL . 'assets/js/geolocation-manager.js',
                array('jquery'),
                CV_FRONT_VERSION,
                true
            );
        }
    }
}

/**
 * Inicializar el plugin
 */
function cv_front_init() {
    return CV_Front::get_instance();
}

// Iniciar el plugin
add_action('plugins_loaded', 'cv_front_init');




