<?php
/**
 * Añadir marcador de ubicación del usuario en el mapa de productos
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_User_Map_Marker {
    
    public function __construct() {
        // Interceptar respuesta AJAX de marcadores de tiendas
        add_action('wp_ajax_wcfmmp_stores_list_map_markers', array($this, 'intercept_markers_ajax'), 1);
        add_action('wp_ajax_nopriv_wcfmmp_stores_list_map_markers', array($this, 'intercept_markers_ajax'), 1);
        
        // Añadir CSS para el marcador del usuario
        add_action('wp_head', array($this, 'add_user_marker_css'));
    }
    
    /**
     * Añadir CSS para el marcador del usuario
     */
    public function add_user_marker_css() {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        ?>
        <style>
            /* Marcador de usuario (SVG) siempre encima con máxima prioridad */
            #wcfmmp-product-list-map .leaflet-marker-pane img[src^="data:image/svg+xml"] {
                z-index: 999999 !important;
                position: relative !important;
            }
            
            /* Animación de pulso para marcador de usuario */
            @keyframes cv-user-marker-pulse {
                0% {
                    transform: scale(1);
                    filter: drop-shadow(0 5px 12px rgba(255,87,34,0.7));
                }
                50% {
                    transform: scale(1.1);
                    filter: drop-shadow(0 8px 20px rgba(255,87,34,0.9));
                }
                100% {
                    transform: scale(1);
                    filter: drop-shadow(0 5px 12px rgba(255,87,34,0.7));
                }
            }
            
            #wcfmmp-product-list-map .leaflet-marker-pane img[src^="data:image/svg+xml"] {
                animation: cv-user-marker-pulse 2s ease-in-out infinite !important;
            }
        </style>
        <?php
    }
    
    /**
     * Interceptar AJAX de marcadores de tiendas
     */
    public function intercept_markers_ajax() {
        // Iniciar output buffering para capturar la respuesta
        ob_start(array($this, 'modify_markers_response'));
    }
    
    /**
     * Modificar la respuesta de marcadores antes de enviarla
     */
    public function modify_markers_response($buffer) {
        // La respuesta es un JSON con formato: {"success":true,"data":"[{...},{...}]"}
        $response = json_decode($buffer, true);
        
        if (!isset($response['success']) || !$response['success']) {
            return $buffer; // No modificar si hay error
        }
        
        // Obtener el array de marcadores
        $markers_json = $response['data'];
        $modified_markers = $this->add_user_location_marker($markers_json);
        
        // Actualizar la respuesta
        $response['data'] = $modified_markers;
        
        return json_encode($response);
    }
    
    /**
     * Añadir marcador de ubicación del usuario al array de marcadores
     */
    public function add_user_location_marker($markers_json) {
        // Decodificar el JSON de marcadores
        $markers = json_decode($markers_json, true);
        
        if (!is_array($markers)) {
            $markers = array();
        }
        
        // Obtener coordenadas del usuario desde GET o cookie
        $user_lat = isset($_GET['radius_lat']) ? floatval($_GET['radius_lat']) : null;
        $user_lng = isset($_GET['radius_lng']) ? floatval($_GET['radius_lng']) : null;
        $user_addr = isset($_GET['radius_addr']) ? sanitize_text_field($_GET['radius_addr']) : '';
        
        // Si no están en GET, intentar obtener de cookie
        if (!$user_lat || !$user_lng) {
            if (isset($_COOKIE['wcfm_radius_settings'])) {
                $radius_data = json_decode(stripslashes($_COOKIE['wcfm_radius_settings']), true);
                if ($radius_data) {
                    $user_lat = isset($radius_data['radius_lat']) ? floatval($radius_data['radius_lat']) : null;
                    $user_lng = isset($radius_data['radius_lng']) ? floatval($radius_data['radius_lng']) : null;
                    $user_addr = isset($radius_data['radius_addr']) ? sanitize_text_field($radius_data['radius_addr']) : '';
                }
            }
        }
        
        // Solo añadir si hay coordenadas
        if ($user_lat && $user_lng) {
            error_log('📍 CV User Marker: Añadiendo marcador de usuario - Lat: ' . $user_lat . ', Lng: ' . $user_lng);
            
            // Crear marcador del usuario con icono personalizado
            $user_marker = array(
                'name' => '📍 Tu ubicación',
                'lat' => (string) $user_lat,
                'lang' => (string) $user_lng, // WCFM usa 'lang' no 'lng'
                'url' => '#',
                'address' => $user_addr,
                'gravatar' => CV_FRONT_PLUGIN_URL . 'assets/images/user-location-icon.png',
                'info_window_content' => '<div class="wcfm_map_info_wrapper" style="text-align: center;">' .
                    '<div class="wcfm_map_info_logo" style="background: #FF5722; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: 0 3px 10px rgba(0,0,0,0.3);">' .
                    '<span style="font-size: 40px;">📍</span>' .
                    '</div>' .
                    '<div class="wcfm_map_info_content">' .
                    '<div class="wcfm_map_info_store" style="font-weight: bold; color: #FF5722; margin-top: 10px;">Tu ubicación</div>' .
                    ($user_addr ? '<p class="wcfm_map_info_addr" style="font-size: 12px; margin-top: 5px;">' . esc_html($user_addr) . '</p>' : '') .
                    '</div>' .
                    '</div>',
                'icon' => $this->get_user_location_icon_data_url() // Icono personalizado inline (SVG)
            );
            
            // Añadir el marcador del usuario al PRINCIPIO del array (para que se vea primero)
            array_unshift($markers, $user_marker);
            
            error_log('✅ CV User Marker: Marcador añadido correctamente. Total marcadores: ' . count($markers));
        } else {
            error_log('⚠️ CV User Marker: No hay coordenadas de usuario para añadir marcador');
        }
        
        // Re-codificar a JSON
        return json_encode($markers);
    }
    
    /**
     * Generar icono personalizado como Data URL
     */
    private function get_user_location_icon_data_url() {
        // WCFM no define iconAnchor explícitamente
        // Leaflet por defecto usa el CENTRO del icono como anchor
        // Pero viendo que aparece arriba-izquierda, el anchor es (0, 0)
        // Solución: SVG pequeño 30x30 con círculo exactamente en (0, 0)
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="-15 -15 30 30">
            <defs>
                <radialGradient id="userPulse">
                    <stop offset="0%" style="stop-color:#FF5722;stop-opacity:0.9"/>
                    <stop offset="70%" style="stop-color:#FF5722;stop-opacity:0.4"/>
                    <stop offset="100%" style="stop-color:#FF5722;stop-opacity:0"/>
                </radialGradient>
            </defs>
            
            <!-- Círculo de pulso centrado en (0,0) - las coordenadas exactas -->
            <circle cx="0" cy="0" r="14" fill="url(#userPulse)"/>
            
            <!-- Círculo principal naranja centrado en (0,0) -->
            <circle cx="0" cy="0" r="10" fill="#FF5722" stroke="white" stroke-width="2.5"/>
            
            <!-- Punto central blanco -->
            <circle cx="0" cy="0" r="3" fill="white"/>
        </svg>';
        
        // Convertir a Data URL
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

