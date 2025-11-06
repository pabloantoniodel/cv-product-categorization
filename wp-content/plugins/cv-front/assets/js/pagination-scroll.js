/**
 * Scroll automático a los productos al cambiar de página
 * y scroll a contenido de Mi Cuenta
 */
(function($) {
    'use strict';
    
    $(window).on('load', function() {
        console.log('CV Front: Script de paginación cargado');
        
        // === SCROLL PARA PAGINACIÓN Y BÚSQUEDA DE PRODUCTOS ===
        var urlParams = new URLSearchParams(window.location.search);
        var hasPage = window.location.href.indexOf('/page/') !== -1;
        var hasSearch = urlParams.has('s') || urlParams.has('product_cat');
        var isStorePage = $('body').hasClass('woocommerce-shop') || window.location.href.indexOf('/store/') !== -1;
        var shouldScroll = sessionStorage.getItem('cv_should_scroll') === 'true';
        
        // Hacer scroll si hay paginación O si hay búsqueda O si es página de vendedor O si sessionStorage lo indica
        if (hasPage || hasSearch || isStorePage || shouldScroll) {
            if (shouldScroll) {
                console.log('CV Front: sessionStorage indica que debe hacer scroll');
                sessionStorage.removeItem('cv_should_scroll');
            }
            if (hasPage) {
                console.log('CV Front: Paginación detectada (página ' + window.location.href.match(/\/page\/(\d+)/)[1] + '), haciendo scroll');
            }
            if (hasSearch) {
                console.log('CV Front: Búsqueda/filtro detectado, haciendo scroll a productos');
            }
            if (isStorePage) {
                console.log('CV Front: Página de vendedor detectada, haciendo scroll a productos');
            }
            
            setTimeout(function() {
                var targetElement = null;
                
                // Prioridad 1: Orderby (dropdown de ordenar)
                targetElement = $('.orderby').first();
                
                // Prioridad 2: Shopper sorting
                if (!targetElement.length) {
                    targetElement = $('.shopper-sorting').first();
                }
                
                // Prioridad 3: Header de productos WooCommerce
                if (!targetElement.length) {
                    targetElement = $('.woocommerce-products-header').first();
                }
                
                // Prioridad 4: Lista de productos
                if (!targetElement.length) {
                    targetElement = $('ul.products').first();
                }
                
                // Prioridad 5: Contenedor de productos del vendedor
                if (!targetElement.length) {
                    targetElement = $('.wcfm_store_container').first();
                }
                
                // Prioridad 6: Tab "Productos" en página de vendedor
                if (!targetElement.length) {
                    targetElement = $('.tab-product').first();
                }
                
                // Prioridad 7: Contenedor principal WooCommerce
                if (!targetElement.length) {
                    targetElement = $('.woocommerce').first();
                }
                
                if (targetElement.length) {
                    var offset = targetElement.offset().top - 50;
                    console.log('CV Front: Elemento encontrado:', targetElement[0].className);
                    console.log('CV Front: Haciendo scroll a offset:', offset);
                    $('html, body').animate({
                        scrollTop: offset
                    }, 500);
                } else {
                    console.log('CV Front: No se encontró elemento para hacer scroll');
                }
            }, 300);
        } else {
            console.log('CV Front: Primera visita a shop, NO haciendo scroll');
        }
        
        // === SCROLL PARA MI CUENTA ===
        var isMyAccount = $('body').hasClass('woocommerce-account') || 
                         window.location.href.indexOf('/my-account/') !== -1;
        
        if (isMyAccount) {
            console.log('CV Front: Página Mi Cuenta detectada');
            
            // Verificar si hay un parámetro de navegación previa guardado
            var shouldScroll = sessionStorage.getItem('cv_myaccount_scroll');
            
            if (shouldScroll === 'true') {
                console.log('CV Front: Click en menú detectado, haciendo scroll');
                sessionStorage.removeItem('cv_myaccount_scroll');
                
                setTimeout(function() {
                    var contentElement = $('.woocommerce-MyAccount-content').first();
                    
                    if (contentElement.length) {
                        var offset = contentElement.offset().top - 20;
                        console.log('CV Front: Scroll a contenido Mi Cuenta en', offset);
                        $('html, body').scrollTop(offset);
                    }
                }, 100);
            }
        }
    });
    
    // === INTERCEPTAR CLICS EN MENÚ DE MI CUENTA ===
    $(document).ready(function() {
        $('.woocommerce-MyAccount-navigation-link a').on('click', function() {
            console.log('CV Front: Clic en menú Mi Cuenta - guardando estado para scroll');
            sessionStorage.setItem('cv_myaccount_scroll', 'true');
        });
    });
    
    // === BOTÓN FLOTANTE VOLVER ARRIBA (SOLO MÓVILES) ===
    $(document).ready(function() {
        // Solo en móviles
        if ($(window).width() <= 768) {
            console.log('CV Front: Creando botón flotante para móvil');
            
            // Crear botón flotante
            $('body').append('<button id="cv-scroll-top" class="cv-scroll-top-btn" aria-label="Volver arriba" title="Volver arriba">↑</button>');
            
            var scrollTopBtn = $('#cv-scroll-top');
            
            // Mostrar/ocultar según scroll
            $(window).on('scroll', function() {
                if ($(window).scrollTop() > 300) {
                    scrollTopBtn.addClass('visible');
                } else {
                    scrollTopBtn.removeClass('visible');
                }
            });
            
            // Clic para volver arriba
            scrollTopBtn.on('click', function() {
                $('html, body').animate({
                    scrollTop: 0
                }, 400);
            });
        }
    });
    
    // === PROXY DE NOMINATIM ===
    
    // ==========================================
    // INTERCEPTOR DE NOMINATIM - Redirigir a nuestro proxy backend
    // ==========================================
    
    console.log('🔧 CV Front: Instalando proxy de Nominatim...');
    
    // Función helper para convertir URL de Nominatim a nuestro proxy
    function nominatimToProxy(originalUrl) {
        try {
            var url = new URL(originalUrl);
            var params = new URLSearchParams(url.search);
            
            // Base URL del proxy (construir automáticamente si no existe variable global)
            var baseUrl = window.location.origin + '/wp-json/cv-front/v1/nominatim/';
            
            // Detectar tipo de petición (reverse o search)
            if (originalUrl.indexOf('/reverse') !== -1) {
                // Reverse geocoding
                var lat = params.get('lat');
                var lon = params.get('lon');
                
                if (lat && lon) {
                    var proxyUrl = baseUrl + 'reverse?lat=' + lat + '&lon=' + lon;
                    console.log('🔀 Proxy reverse:', originalUrl, '->', proxyUrl);
                    return proxyUrl;
                }
            } else if (originalUrl.indexOf('/search') !== -1) {
                // Forward geocoding
                var query = params.get('q');
                var format = params.get('format') || 'json';
                
                if (query) {
                    var proxyUrl = baseUrl + 'search?q=' + encodeURIComponent(query) + '&format=' + format;
                    console.log('🔀 Proxy search:', originalUrl, '->', proxyUrl);
                    return proxyUrl;
                }
            }
        } catch (e) {
            console.error('❌ Error al convertir URL de Nominatim:', e);
        }
        
        console.log('⚠️ Fallback - usando URL original:', originalUrl);
        return originalUrl; // Fallback
    }
    
    // 1. INTERCEPTOR DE jQuery.ajax() - REDIRIGIR A PROXY
    (function() {
        var originalAjax = $.ajax;
        $.ajax = function(settings) {
            var url = settings.url || '';
            var isNominatim = url.indexOf('nominatim.openstreetmap.org') !== -1;
            
            if (isNominatim) {
                var newUrl = nominatimToProxy(url);
                console.log('🔀 CV Front: Redirigiendo Nominatim a proxy local');
                console.log('   Original:', url);
                console.log('   Proxy:', newUrl);
                settings.url = newUrl;
            }
            
            return originalAjax.apply(this, arguments);
        };
        console.log('✅ CV Front: Interceptor jQuery.ajax() instalado (proxy mode)');
    })();
    
    // 2. INTERCEPTOR DE fetch() - REDIRIGIR A PROXY
    (function() {
        if (typeof window.fetch === 'function') {
            var originalFetch = window.fetch;
            window.fetch = function(url, options) {
                var urlStr = typeof url === 'string' ? url : url.url || '';
                var isNominatim = urlStr.indexOf('nominatim.openstreetmap.org') !== -1;
                
                if (isNominatim) {
                    var newUrl = nominatimToProxy(urlStr);
                    console.log('🔀 CV Front: Redirigiendo fetch() Nominatim a proxy local');
                    console.log('   Original:', urlStr);
                    console.log('   Proxy:', newUrl);
                    
                    if (typeof url === 'string') {
                        url = newUrl;
                    } else {
                        url.url = newUrl;
                    }
                }
                
                return originalFetch.apply(this, arguments);
            };
            console.log('✅ CV Front: Interceptor fetch() instalado (proxy mode)');
        }
    })();
    
    // 3. INTERCEPTOR DE $.get() - REDIRIGIR A PROXY
    (function() {
        var originalGet = $.get;
        $.get = function(url, data, callback, type) {
            var urlStr = typeof url === 'string' ? url : '';
            var isNominatim = urlStr.indexOf('nominatim.openstreetmap.org') !== -1;
            
            if (isNominatim) {
                var newUrl = nominatimToProxy(urlStr);
                console.log('🔀 CV Front: Redirigiendo $.get() Nominatim a proxy local');
                console.log('   Original:', urlStr);
                console.log('   Proxy:', newUrl);
                url = newUrl;
            }
            
            return originalGet.apply(this, arguments);
        };
        console.log('✅ CV Front: Interceptor $.get() instalado (proxy mode)');
    })();
    
    console.log('✅ CV Front: Proxy de Nominatim instalado - Todas las peticiones van al servidor local');
    
    // ==========================================
    // DETECCIÓN INMEDIATA DE clear_radius (ANTES de document.ready)
    // ==========================================
    
    // Detectar cuando se limpia el filtro y forzar recarga completa INMEDIATAMENTE
    var urlParams = new URLSearchParams(window.location.search);
    var hasClearRadius = urlParams.get('clear_radius');
    
    if (hasClearRadius) {
        console.log('⚡ CV Front: clear_radius detectado - limpiando ubicación PERO manteniendo distancia');
        sessionStorage.removeItem('cv_map_collapsed');
        
        // Construir nueva URL manteniendo SOLO radius_range (la distancia del slider)
        var newUrl = window.location.pathname;
        var radiusRange = urlParams.get('radius_range');
        
        // Si hay distancia seleccionada, mantenerla
        if (radiusRange) {
            newUrl += '?radius_range=' + radiusRange;
            console.log('📏 CV Front: Manteniendo distancia:', radiusRange);
        }
        
        console.log('🔄 CV Front: Redirigiendo a:', newUrl);
        
        // Usar replace para no añadir entrada al historial
        window.location.replace(newUrl);
        
        // Detener ejecución del resto del script
        throw new Error('Redirecting...');
    }
    
    // === LISTENER PARA PAGINACIÓN EN TIENDAS DE VENDEDOR ===
    // Usar delegación de eventos para capturar clics en paginación
    $(document).on('click', '.woocommerce-pagination a, .page-numbers a', function(e) {
        var href = $(this).attr('href');
        // Solo capturar si el enlace tiene /page/ Y tiene clases específicas de paginación
        if (href && href.indexOf('/page/') !== -1 && $(this).closest('.woocommerce-pagination, .page-numbers').length) {
            console.log('CV Front: Paginación clickeada, guardando estado');
            sessionStorage.setItem('cv_should_scroll', 'true');
        }
    });
    
    $(document).ready(function() {
        // El colapso de mapa solo debe funcionar en la página shop principal, NO en categorías
        var isShopPage = $('body').hasClass('woocommerce-shop') && !$('body').hasClass('tax-product_cat');
        var isCategory = $('body').hasClass('tax-product_cat') || $('body').hasClass('product-category');
        
        // IMPORTANTE: Limpiar sessionStorage en categorías para evitar que cv-map-hidden afecte
        if (isCategory) {
            sessionStorage.removeItem('cv_map_collapsed');
            console.log('🧹 CV Front: sessionStorage limpiado en categoría (evitar cv-map-hidden)');
        }
        
        if (isShopPage) {
            console.log('CV Front: Inicializando colapso de mapa al filtrar (solo en shop)');
            
            // Al hacer clic en "Filtrar"
            $('.wcfmmp-product-geolocate-search-form button[type="submit"]').on('click', function() {
                console.log('CV Front: Botón Filtrar clickeado - colapsando mapa');
                
                // Guardar en sessionStorage que se ha filtrado
                sessionStorage.setItem('cv_map_collapsed', 'true');
            });
            
            // Al cargar la página, verificar si debe estar colapsado
            // IMPORTANTE: Hacer ANTES de que WCFM inicialice el mapa
            var shouldBeCollapsed = sessionStorage.getItem('cv_map_collapsed') === 'true';
            
            if (shouldBeCollapsed) {
                console.log('CV Front: Mapa debe estar colapsado - BLOQUEANDO GEOCODING AUTOMÁTICO');
                
                // Bloquear geolocalización automática de WCFM temporalmente
                if (typeof wcfmmp_product_list_options !== 'undefined') {
                    wcfmmp_product_list_options.is_geolocate = false;
                    console.log('CV Front: is_geolocate desactivado para evitar Nominatim');
                }
                
                // Usar clase CSS para ocultar (mantiene elementos en DOM)
                $('#wcfmmp-product-list-map').addClass('cv-map-hidden');
                $('.wcfmmp-product-geolocate-search-form').addClass('cv-map-hidden');
                console.log('CV Front: Clase cv-map-hidden añadida');
            }
            
            // Interceptar el botón de limpiar filtro existente
            $(document).on('click', 'input[name="clear_radius"], button[name="clear_radius"], a[href*="clear_radius"]', function(e) {
                console.log('CV Front: Botón limpiar filtro clickeado');
                
                // Limpiar sessionStorage
                sessionStorage.removeItem('cv_map_collapsed');
            });
        } else {
            console.log('CV Front: No es página shop, colapso de mapa desactivado');
        }
        
        // NOTA: El marcador de usuario ahora se añade desde el backend (PHP)
        // Ver class-cv-user-map-marker.php
        
        console.log('📍 CV Front: Marcador de usuario se añade desde el backend via AJAX');
        
        // ============================================================
        // FIX: Corregir iconAnchor del marcador de usuario en Leaflet
        // ============================================================
        // WCFM no especifica iconAnchor, lo que hace que Leaflet use [0,0] (esquina superior izquierda)
        // Necesitamos interceptar la creación del marcador y corregirlo
        
        // Monkey-patch del L.Marker para corregir el anchor del marcador de usuario
        if (typeof L !== 'undefined' && L.Marker) {
            var originalMarker = L.Marker.prototype.setIcon;
            L.Marker.prototype.setIcon = function(icon) {
                // Si el icono es nuestro SVG de usuario (data:image/svg+xml)
                if (icon && icon.options && icon.options.iconUrl && 
                    icon.options.iconUrl.indexOf('data:image/svg+xml') === 0) {
                    
                    console.log('📍 CV Front: Detectado marcador de usuario, corrigiendo iconAnchor...');
                    
                    // El SVG es 30x30, el centro visual debe estar en [15, 15]
                    icon.options.iconAnchor = [15, 15];
                    icon.options.popupAnchor = [0, -15];
                    
                    console.log('✅ CV Front: iconAnchor corregido a [15, 15]');
                }
                
                // Llamar al método original
                return originalMarker.call(this, icon);
            };
            
            console.log('📍 CV Front: Monkey-patch de L.Marker aplicado para corregir iconAnchor');
        }
        
        // ============================================================
        // FIX PARA CATEGORÍAS - NO OCULTAR NADA
        // ============================================================
        // La redirección PHP ya se encarga de eliminar parámetros radius_*
        // No ocultamos nada para no romper el layout
        if ($('body').hasClass('tax-product_cat') || $('body').hasClass('product-category')) {
            console.log('✅ CV Front: Categoría de productos - Redirección PHP activa para eliminar filtros de distancia');
        }
    });
})(jQuery);

