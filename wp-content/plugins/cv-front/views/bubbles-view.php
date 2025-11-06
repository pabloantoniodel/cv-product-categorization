<?php
/**
 * Vista de Burbujas de Tiendas
 * 
 * @package CV_Front
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$radius = isset($atts['radius']) ? intval($atts['radius']) : 10;
$limit = isset($atts['limit']) ? intval($atts['limit']) : 50;
$default_view = isset($atts['view']) ? $atts['view'] : 'bubbles';
?>

<div class="cv-store-view-wrapper">
    
    <!-- Toggle entre vistas -->
    <div class="cv-view-controls">
        <div class="cv-view-toggle">
            <button class="cv-toggle-btn <?php echo $default_view === 'bubbles' ? 'active' : ''; ?>" 
                    data-view="bubbles">
                <span class="wcfmfa fa-circle"></span>
                Vista Burbujas
            </button>
            <button class="cv-toggle-btn <?php echo $default_view === 'map' ? 'active' : ''; ?>" 
                    data-view="map">
                <span class="wcfmfa fa-map-marked-alt"></span>
                Vista Mapa
            </button>
        </div>
        
        <div class="cv-location-controls">
            <button class="cv-btn-locate">
                <span class="wcfmfa fa-crosshairs"></span>
                Usar Mi Ubicación
            </button>
            <input type="number" id="cv-radius-input" class="cv-radius-input" 
                   value="<?php echo $radius; ?>" min="1" max="100" step="1">
            <span class="cv-radius-label">km</span>
        </div>
    </div>
    
    <!-- Vista Burbujas -->
    <div id="cv-bubbles-view" class="cv-bubbles-container" 
         style="display: <?php echo $default_view === 'bubbles' ? 'block' : 'none'; ?>;">
        
        <div class="cv-bubbles-loading">
            <div class="cv-loading-spinner"></div>
            <p>Cargando tiendas cercanas...</p>
        </div>
        
        <canvas id="cv-bubbles-canvas"></canvas>
        
        <!-- Tooltip flotante al hover -->
        <div id="cv-bubble-tooltip" class="cv-bubble-tooltip" style="display:none;">
            <div class="cv-tooltip-photo-wrap">
                <img src="" class="cv-tooltip-photo" alt="">
            </div>
            <h3 class="cv-tooltip-name"></h3>
            <p class="cv-tooltip-distance"></p>
            <p class="cv-tooltip-location"></p>
            <button class="cv-tooltip-btn">
                Ver Tienda
                <span class="wcfmfa fa-arrow-right"></span>
            </button>
        </div>
        
        <!-- Info de resultados -->
        <div class="cv-bubbles-info">
            <span id="cv-stores-count">0</span> tiendas encontradas en 
            <span id="cv-radius-display"><?php echo $radius; ?></span> km
        </div>
    </div>
    
    <!-- Vista Mapa (WCFM original) -->
    <div id="cv-map-view" class="cv-map-container" 
         style="display: <?php echo $default_view === 'map' ? 'block' : 'none'; ?>;">
        
        <div class="cv-map-placeholder">
            <p>Cargando mapa...</p>
            <p><small>Aquí se cargará el mapa original de WCFM</small></p>
        </div>
        
        <!-- El mapa original de WCFM se insertará aquí via JavaScript -->
        <?php 
        // Shortcode de WCFM para el mapa de tiendas
        if (shortcode_exists('wcfm_stores_map')) {
            echo do_shortcode('[wcfm_stores_map]');
        }
        ?>
    </div>
    
    <!-- Inputs hidden para coordenadas -->
    <input type="hidden" id="cv-user-lat" value="">
    <input type="hidden" id="cv-user-lng" value="">
    <input type="hidden" id="cv-radius-value" value="<?php echo $radius; ?>">
    <input type="hidden" id="cv-limit-value" value="<?php echo $limit; ?>">
</div>





