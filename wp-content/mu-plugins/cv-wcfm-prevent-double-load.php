<?php
/**
 * Plugin Name: CV WCFM Prevent Double Load
 * Description: Previene la carga duplicada de comercios interceptando refreshStoreList()
 * Version: 1.0.0
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inyectar JavaScript que intercepta refreshStoreList() antes de que WCFM lo ejecute
 */
function cv_wcfm_prevent_double_load_script() {
    // Solo en la página de comercios
    if (!is_page('comercios') && !is_post_type_archive('wcfm_vendor')) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    (function() {
        console.log('[CV Double Load Fix] 🚀 Interceptor instalado');
        
        // Guardar referencia a la función original cuando se cargue
        var originalRefreshStoreList = null;
        var interceptorInstalled = false;
        
        // Función para instalar el interceptor
        function installInterceptor() {
            if (interceptorInstalled) return;
            
            if (typeof window.refreshStoreList === 'function') {
                console.log('[CV Double Load Fix] ✅ refreshStoreList() encontrada, instalando interceptor');
                originalRefreshStoreList = window.refreshStoreList;
                interceptorInstalled = true;
                
                window.refreshStoreList = function() {
                    var existingStores = jQuery('.wcfmmp-single-store').length;
                    console.log('[CV Double Load Fix] 🏪 refreshStoreList() llamada - Comercios en HTML:', existingStores);
                    
                    // Solo ejecutar si NO hay comercios ya cargados
                    if (existingStores === 0) {
                        console.log('[CV Double Load Fix] ✅ No hay comercios, ejecutando refreshStoreList()');
                        return originalRefreshStoreList.apply(this, arguments);
                    } else {
                        console.log('[CV Double Load Fix] ⏭️ Ya hay comercios cargados, SALTANDO refreshStoreList()');
                        return false;
                    }
                };
            }
        }
        
        // Intentar instalar inmediatamente
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', installInterceptor);
        } else {
            installInterceptor();
        }
        
        // También intentar después de que jQuery esté listo
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(function() {
                setTimeout(installInterceptor, 100);
                setTimeout(installInterceptor, 500);
                setTimeout(installInterceptor, 1000);
            });
        }
    })();
    </script>
    <?php
}
add_action('wp_head', 'cv_wcfm_prevent_double_load_script', 1); // Prioridad 1 para ejecutar antes que WCFM

