<?php
/**
 * Corrector automático de enlaces WhatsApp en listados y headers de tiendas
 * Corrige el formato incorrecto wa.me/PHONE&text= a wa.me/PHONE?text=
 * Y normaliza números de 9 dígitos añadiendo +34
 *
 * @package CV_Front
 * @since 2.4.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Store_WhatsApp_Fixer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'add_whatsapp_fixer_script'), 1000);
    }
    
    /**
     * Agregar script que corrige enlaces de WhatsApp mal formados
     */
    public function add_whatsapp_fixer_script() {
        // Solo ejecutar en páginas de comercios y listados
        if (!is_shop() && !is_product_category() && !wcfmmp_is_stores_list_page() && !wcfmmp_is_store_page()) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        (function($) {
            function fixWhatsAppLinks() {
                // Función para normalizar números de teléfono
                function normalizePhone(phone) {
                    if (!phone) return phone;
                    
                    // Limpiar el teléfono (quitar espacios, guiones, etc.)
                    var cleanPhone = phone.replace(/[^0-9+]/g, '');
                    
                    // Si está vacío, devolver original
                    if (cleanPhone.length === 0) {
                        return phone;
                    }
                    
                    // Si ya tiene prefijo +, dejarlo como está
                    if (cleanPhone.indexOf('+') === 0) {
                        return cleanPhone;
                    }
                    
                    // Si empieza con 00, convertir a +
                    if (cleanPhone.indexOf('00') === 0) {
                        return '+' + cleanPhone.substring(2);
                    }
                    
                    // Si tiene exactamente 9 dígitos, añadir +34 (España)
                    if (cleanPhone.length === 9) {
                        return '+34' + cleanPhone;
                    }
                    
                    // Si tiene 11 dígitos y empieza con 34, añadir +
                    if (cleanPhone.length === 11 && cleanPhone.indexOf('34') === 0) {
                        return '+' + cleanPhone;
                    }
                    
                    // En cualquier otro caso, devolver limpio
                    return cleanPhone;
                }
                
                // Buscar TODOS los enlaces de WhatsApp en la página
                $('a[href*="wa.me"]').each(function() {
                    var $link = $(this);
                    var href = $link.attr('href');
                    var originalHref = href;
                    
                    // Decodificar &amp; a &
                    href = href.replace(/&amp;/g, '&');
                    
                    // Extraer el número de teléfono del enlace
                    var phoneMatch = href.match(/wa\.me\/([^?&]+)/);
                    if (phoneMatch && phoneMatch[1]) {
                        var phone = phoneMatch[1];
                        var normalizedPhone = normalizePhone(phone);
                        
                        // Si el número cambió, actualizar
                        if (phone !== normalizedPhone) {
                            href = href.replace('wa.me/' + phone, 'wa.me/' + normalizedPhone);
                            console.log('📱 CV WhatsApp Fixer: Número normalizado', phone, '→', normalizedPhone);
                        }
                    }
                    
                    // Corregir &text= a ?text= si es necesario
                    if (href.indexOf('&text=') !== -1 && href.indexOf('?') === -1) {
                        href = href.replace('&text=', '?text=');
                        console.log('🔧 CV WhatsApp Fixer: Formato corregido (& → ?)');
                    }
                    
                    // Si el href cambió, actualizarlo
                    if (href !== originalHref) {
                        $link.attr('href', href);
                        console.log('✅ CV WhatsApp Fixer: Enlace actualizado:', originalHref, '→', href);
                    }
                });
            }
            
            // Ejecutar inmediatamente al cargar
            $(document).ready(function() {
                console.log('🔍 CV WhatsApp Fixer: Iniciando corrección de enlaces...');
                fixWhatsAppLinks();
            });
            
            // Re-ejecutar después de AJAX (para listados con paginación)
            $(document).ajaxComplete(function() {
                setTimeout(function() {
                    console.log('🔄 CV WhatsApp Fixer: Re-ejecutando después de AJAX...');
                    fixWhatsAppLinks();
                }, 500);
            });
            
            // Observar cambios en el DOM por si se cargan nuevos elementos
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    var shouldFix = false;
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length > 0) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    if ($(node).find('a[href*="wa.me"]').length > 0 || 
                                        $(node).is('a[href*="wa.me"]')) {
                                        shouldFix = true;
                                    }
                                }
                            });
                        }
                    });
                    
                    if (shouldFix) {
                        console.log('👀 CV WhatsApp Fixer: Nuevos enlaces detectados, corrigiendo...');
                        fixWhatsAppLinks();
                    }
                });
                
                // Observar cambios en el contenido principal
                var targetNode = document.querySelector('body');
                if (targetNode) {
                    observer.observe(targetNode, {
                        childList: true,
                        subtree: true
                    });
                }
            }
        })(jQuery);
        </script>
        <?php
    }
}

