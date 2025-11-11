<?php
/**
 * Geolocation Toggle Button
 * 
 * Botón flotante morado para activar/desactivar geolocalización
 * 
 * @package CV_Front
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Geolocation_Toggle {
    
    public function __construct() {
        // Cargar dashicons en el frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashicons'));
        
        // Añadir botón flotante de geolocalización
        add_action('wp_footer', array($this, 'add_geolocation_button'));
    }
    
    /**
     * Cargar dashicons en el frontend
     */
    public function enqueue_dashicons() {
        wp_enqueue_style('dashicons');
        if (!wp_style_is('cv-comercios-listing', 'enqueued')) {
            wp_enqueue_style(
                'cv-comercios-listing',
                CV_FRONT_PLUGIN_URL . 'assets/css/comercios.css',
                array(),
                CV_FRONT_VERSION
            );
        }
        if (!wp_style_is('cv-radius-dialog-styles', 'enqueued')) {
            $dialog_css = CV_FRONT_PLUGIN_DIR . 'assets/css/cv-radius-dialog.css';
            wp_enqueue_style(
                'cv-radius-dialog-styles',
                CV_FRONT_PLUGIN_URL . 'assets/css/cv-radius-dialog.css',
                array(),
                file_exists($dialog_css) ? filemtime($dialog_css) : CV_FRONT_VERSION
            );
        }
        wp_enqueue_script(
            'cv-radius-dialog',
            CV_FRONT_PLUGIN_URL . 'assets/js/geo-radius-dialog.js',
            array('jquery'),
            CV_FRONT_VERSION,
            true
        );
    }
    
    /**
     * Añadir botón flotante de geolocalización
     */
    public function add_geolocation_button() {
        // Solo en páginas donde tiene sentido la geolocalización
        if (!is_shop() && !is_product_category() && !is_product_tag() && 
            !(function_exists('wcfmmp_is_stores_list_page') && wcfmmp_is_stores_list_page())) {
            return;
        }
        
        ?>
        <div id="cv-geolocation-toggle-float" 
             title="Geolocalización desactivada"
             style="
            position: fixed;
            top: 150px;
            left: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999999;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        ">
            <span class="dashicons dashicons-location" id="cv-geolocation-icon" style="
                font-size: 24px;
                color: white;
                width: 24px;
                height: 24px;
                position: relative;
            "></span>
            <!-- Línea diagonal para tachar el icono -->
            <div id="cv-geolocation-slash" style="
                position: absolute;
                width: 2px;
                height: 38px;
                background: white;
                transform: rotate(45deg);
                box-shadow: 0 0 3px rgba(0,0,0,0.3);
            "></div>
        </div>
        
        <style>
        #cv-geolocation-toggle-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }
        
        #cv-geolocation-toggle-float.enabled #cv-geolocation-slash {
            display: none;
        }
        
        #cv-geolocation-toggle-float.disabled #cv-geolocation-slash {
            display: block;
        }
        
        #cv-geolocation-toggle-float.processing {
            pointer-events: none;
            opacity: 0.6;
        }
        
        #cv-geolocation-toggle-float.processing #cv-geolocation-icon {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var $btn = $('#cv-geolocation-toggle-float');
            var $icon = $('#cv-geolocation-icon');
            var $slash = $('#cv-geolocation-slash');
            
            // Estado inicial: desactivado (con tachado)
            $btn.addClass('disabled');
            
            function setCookie(name, value, days) {
                var expires = '';
                if (typeof days === 'number') {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = '; expires=' + date.toUTCString();
                }
                document.cookie = name + '=' + (value || '') + expires + '; path=/';
            }
            
            function getStoredSettings() {
                try {
                    var raw = localStorage.getItem('cv_geo_radius_dialog_settings');
                    if (!raw) {
                        return null;
                    }
                    return JSON.parse(raw);
                } catch (error) {
                    console.warn('cvRadiusDialog: no se pudo leer configuración almacenada', error);
                    return null;
                }
            }

            function waitForDialog(callback) {
                if (window.cvRadiusDialogManager) {
                    callback(window.cvRadiusDialogManager);
                    return;
                }
                window.addEventListener('cvRadiusDialogReady', function handler(event) {
                    window.removeEventListener('cvRadiusDialogReady', handler);
                    var manager = event && event.detail ? event.detail : window.cvRadiusDialogManager;
                    callback(manager);
                }, { once: true });
            }

            function showMessage(text, color) {
                $('<div style="position: fixed; top: 210px; left: 20px; background: ' + color + '; color: white; padding: 15px 25px; border-radius: 8px; z-index: 999998; box-shadow: 0 4px 15px rgba(0,0,0,0.2); max-width: 320px; line-height: 1.4;"></div>')
                    .appendTo('body')
                    .html(text)
                    .delay(3500)
                    .fadeOut(300, function () { $(this).remove(); });
            }

            function setButtonState(enabled) {
                if (enabled) {
                    $btn.removeClass('disabled processing').addClass('enabled');
                    $btn.attr('title', 'Geolocalización activada');
                } else {
                    $btn.removeClass('enabled processing').addClass('disabled');
                    $btn.attr('title', 'Geolocalización desactivada');
                }
            }

            function persistActivation(data) {
                var unit = data.unit || 'km';
                var radiusKm = parseFloat(data.range);
                if (isNaN(radiusKm) || radiusKm <= 0) {
                    radiusKm = 50;
                }
                var radiusRaw = (data.rangeRaw !== undefined) ? parseFloat(data.rangeRaw) : null;
                if (unit === 'm') {
                    if (isNaN(radiusRaw) || radiusRaw <= 0) {
                        radiusRaw = Math.round(radiusKm * 1000);
                    }
                } else {
                    radiusRaw = radiusKm;
                }

                localStorage.setItem('cv_geolocation_enabled', 'true');
                if (data.lat !== null && data.lat !== undefined) {
                    localStorage.setItem('cv_user_lat', data.lat);
                }
                if (data.lng !== null && data.lng !== undefined) {
                    localStorage.setItem('cv_user_lng', data.lng);
                }
                localStorage.setItem('cv_geo_radius', radiusKm);
                localStorage.setItem('cv_geo_unit', unit);
                localStorage.setItem('cv_geo_radius_raw', radiusRaw);

                setCookie('cv_geolocation_enabled', 'true', 30);
                setCookie('cv_geo_radius', radiusKm, 30);
                setCookie('cv_geo_radius_wcfm', radiusKm, 30);
                setCookie('cv_geo_radius_raw', radiusRaw, 30);

                if (data.lat !== null && data.lng !== null) {
                    var payload = {
                        addr: data.address || '',
                        lat: data.lat,
                        lng: data.lng,
                        range: radiusKm,
                        range_raw: radiusRaw,
                        unit: unit,
                        timestamp: Date.now()
                    };
                    setCookie('wcfm_stores_radius_filter', JSON.stringify(payload), 30);
                }

                if (window.cvRadiusDialogManager && typeof window.cvRadiusDialogManager.configure === 'function') {
                    window.cvRadiusDialogManager.configure({
                        range: radiusKm,
                        rangeRaw: radiusRaw,
                        unit: unit,
                        unitLabel: unit === 'm' ? 'm' : 'Km',
                        lat: data.lat,
                        lng: data.lng,
                        address: data.address || '',
                        context: 'stores',
                        rangeFieldMode: (unit === 'm') ? 'raw' : 'km'
                    });
                }
            }

            function clearGeoState() {
                localStorage.setItem('cv_geolocation_enabled', 'false');
                localStorage.removeItem('cv_user_lat');
                localStorage.removeItem('cv_user_lng');
                localStorage.removeItem('cv_geo_radius');
                localStorage.removeItem('cv_geo_unit');
                localStorage.removeItem('cv_geo_radius_raw');
                localStorage.removeItem('cv_geo_radius_dialog_settings');

                setCookie('cv_geolocation_enabled', '', -1);
                setCookie('cv_geo_radius', '', -1);
                setCookie('cv_geo_radius_wcfm', '', -1);
                setCookie('cv_geo_radius_raw', '', -1);
                setCookie('wcfm_stores_radius_filter', '', -1);
            }

            function openActivationDialog(lat, lng) {
                waitForDialog(function (manager) {
                    var stored = getStoredSettings() || {};
                    var storedRange = typeof stored.range === 'number' ? stored.range : (localStorage.getItem('cv_geo_radius') ? parseFloat(localStorage.getItem('cv_geo_radius')) : 50);
                    var storedRangeRaw = typeof stored.rangeRaw === 'number' ? stored.rangeRaw : null;
                    var unitValue = stored.unit || localStorage.getItem('cv_geo_unit') || 'km';
                    var unitLabel = unitValue === 'm' ? 'm' : 'Km';
                    if (unitValue === 'm') {
                        if (storedRangeRaw === null || isNaN(storedRangeRaw)) {
                            storedRangeRaw = localStorage.getItem('cv_geo_radius_raw') ? parseFloat(localStorage.getItem('cv_geo_radius_raw')) : null;
                        }
                        if (storedRangeRaw === null || isNaN(storedRangeRaw)) {
                            storedRangeRaw = Math.round(parseFloat(storedRange || 0) * 1000);
                        }
                    } else {
                        if (storedRangeRaw === null || isNaN(storedRangeRaw)) {
                            storedRangeRaw = localStorage.getItem('cv_geo_radius_raw') ? parseFloat(localStorage.getItem('cv_geo_radius_raw')) : null;
                        }
                        if (storedRangeRaw === null || isNaN(storedRangeRaw)) {
                            storedRangeRaw = storedRange;
                        }
                    }

                    manager.open({
                        origin: 'activate',
                        lat: lat,
                        lng: lng,
                        range: storedRange,
                        rangeRaw: storedRangeRaw,
                        defaultRange: stored.defaultRange || storedRange,
                        max: stored.max || 500,
                        unit: unitValue,
                        unitLabel: stored.unitLabel || unitLabel,
                        address: '',
                        context: 'stores',
                        rangeFieldMode: 'auto',
                        storageKey: 'cv_geo_radius_dialog_settings',
                        submitOnApply: false,
                        enableReverseGeocode: true,
                        allowForwardGeocode: true,
                        selectors: {},
                        deactivation: {
                            enabled: false,
                            label: 'Desactivar geolocalización',
                            notice: null,
                            callback: null
                        },
                        hintMessage: null,
                        applyCallbacks: [
                            function (result) {
                                persistActivation(result);
                                setButtonState(true);
                                showMessage(
                                    '<strong>📍 Geolocalización activada</strong><br>Orden: “Más cercanos”. Ajusta el radio cuando lo necesites desde el botón de distancia.',
                                    '#4CAF50'
                                );
                                if (window.GeoManager) {
                                    window.GeoManager.applyGeoState();
                                }
                            }
                        ]
                    });
                });
            }

            function openDeactivationDialog() {
                waitForDialog(function (manager) {
                    $btn.removeClass('processing');

                    var stored = getStoredSettings() || {};
                    var lat = stored.lat;
                    var lng = stored.lng;

                    if ((lat === undefined || lat === null) && localStorage.getItem('cv_user_lat')) {
                        var parsedLat = parseFloat(localStorage.getItem('cv_user_lat'));
                        lat = isNaN(parsedLat) ? null : parsedLat;
                    }
                    if ((lng === undefined || lng === null) && localStorage.getItem('cv_user_lng')) {
                        var parsedLng = parseFloat(localStorage.getItem('cv_user_lng'));
                        lng = isNaN(parsedLng) ? null : parsedLng;
                    }

                    var unitValue = stored.unit || localStorage.getItem('cv_geo_unit') || 'km';
                    unitValue = unitValue === 'm' ? 'm' : 'km';

                    var storedRange = typeof stored.range === 'number'
                        ? stored.range
                        : (localStorage.getItem('cv_geo_radius') ? parseFloat(localStorage.getItem('cv_geo_radius')) : 50);
                    if (isNaN(storedRange) || storedRange <= 0) {
                        storedRange = 50;
                    }

                    var storedRangeRaw = typeof stored.rangeRaw === 'number' ? stored.rangeRaw : null;
                    if (storedRangeRaw === null || isNaN(storedRangeRaw)) {
                        var rawValue = localStorage.getItem('cv_geo_radius_raw');
                        if (rawValue !== null) {
                            var parsedRawValue = parseFloat(rawValue);
                            if (!isNaN(parsedRawValue)) {
                                storedRangeRaw = parsedRawValue;
                            }
                        }
                    }

                    if (unitValue === 'm') {
                        if (storedRangeRaw !== null && !isNaN(storedRangeRaw)) {
                            storedRange = storedRangeRaw / 1000;
                        } else {
                            storedRangeRaw = Math.round(storedRange * 1000);
                        }
                    } else {
                        storedRangeRaw = storedRangeRaw !== null && !isNaN(storedRangeRaw)
                            ? storedRangeRaw
                            : storedRange;
                    }

                    var address = stored.address || '';
                    if (!address) {
                        var summaryText = $('#cvRadiusSummaryAddress').length ? $('#cvRadiusSummaryAddress').text() : '';
                        if (summaryText) {
                            var cleaned = summaryText.replace(/^Ubicación:\s*/i, '').trim();
                            if (cleaned && cleaned.toLowerCase() !== 'ubicación sin definir') {
                                address = cleaned;
                            }
                        }
                    }
                    if (!address) {
                        try {
                            var cookieEntry = document.cookie.split(';').map(function (item) {
                                return item.trim();
                            }).find(function (item) {
                                return item.indexOf('wcfm_stores_radius_filter=') === 0;
                            });
                            if (cookieEntry) {
                                var cookieValue = decodeURIComponent(cookieEntry.split('=').slice(1).join('='));
                                var parsedCookie = JSON.parse(cookieValue);
                                if (parsedCookie && parsedCookie.addr) {
                                    address = parsedCookie.addr;
                                }
                            }
                        } catch (cookieError) {
                            // Silenciar errores de parsing de cookie
                        }
                    }

                    var deactivateNotice = 'Mantén tus ajustes actuales o pulsa “Desactivar geolocalización” para volver al listado general sin filtros de distancia.';

                    manager.open({
                        origin: 'deactivate',
                        lat: lat,
                        lng: lng,
                        range: storedRange,
                        rangeRaw: storedRangeRaw,
                        unit: unitValue,
                        unitLabel: unitValue === 'm' ? 'm' : 'Km',
                        address: address,
                        hintMessage: deactivateNotice,
                        rangeFieldMode: 'auto',
                        context: 'stores',
                        deactivation: {
                            enabled: true,
                            label: 'Desactivar geolocalización',
                            notice: deactivateNotice,
                            callback: function () {
                                clearGeoState();
                                setButtonState(false);
                                showMessage(
                                    '<strong>🔕 Geolocalización desactivada</strong><br>El filtro por distancia se eliminó y verás el listado completo.',
                                    '#ff704d'
                                );
                                if (window.GeoManager) {
                                    window.GeoManager.applyGeoState();
                                    if (typeof window.GeoManager.clearGeoParams === 'function') {
                                        window.GeoManager.clearGeoParams();
                                    }
                                }
                            }
                        },
                        applyCallbacks: [
                            function (result) {
                                persistActivation(result);
                                setButtonState(true);
                                showMessage(
                                    '<strong>✅ Ajustes actualizados</strong><br>Seguimos mostrando comercios cercanos con tus nuevos parámetros.',
                                    '#4CAF50'
                                );
                                if (window.GeoManager) {
                                    window.GeoManager.applyGeoState();
                                }
                            }
                        ]
                    });
                });
            }

            $('#cv-geolocation-toggle-float').on('click', function () {
                if ($btn.hasClass('processing')) {
                    return;
                }

                var isEnabled = $btn.hasClass('enabled');

                if (!isEnabled) {
                    $btn.addClass('processing');

                    if (!('geolocation' in navigator)) {
                        $btn.removeClass('processing');
                        alert('❌ Tu navegador no soporta geolocalización');
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        function (position) {
                            $btn.removeClass('processing');
                            openActivationDialog(position.coords.latitude, position.coords.longitude);
                        },
                        function (error) {
                            $btn.removeClass('processing');
                            var errorMsg = 'No se pudo obtener tu ubicación';
                            if (error.code === 1) {
                                errorMsg = 'Permiso de ubicación denegado';
                            } else if (error.code === 2) {
                                errorMsg = 'Ubicación no disponible';
                            } else if (error.code === 3) {
                                errorMsg = 'Tiempo de espera agotado';
                            }
                            alert('❌ ' + errorMsg);
                            console.error('Error geolocalización:', error);
                        },
                        { enableHighAccuracy: false, timeout: 10000, maximumAge: 0 }
                    );
                } else {
                    $btn.addClass('processing');
                    openDeactivationDialog();
                }
            });

            var geoEnabled = localStorage.getItem('cv_geolocation_enabled') === 'true';
            setButtonState(geoEnabled);
            if (!geoEnabled) {
                setCookie('cv_geolocation_enabled', '', -1);
            }
        });
        </script>
        <?php
    }
}

new CV_Geolocation_Toggle();

