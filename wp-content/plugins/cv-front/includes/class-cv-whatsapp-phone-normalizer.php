<?php
/**
 * Normalizador de números de teléfono para WhatsApp
 * Añade prefijo +34 a números españoles de 9 dígitos sin prefijo internacional
 *
 * @package CV_Front
 * @since 2.4.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_WhatsApp_Phone_Normalizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook para interceptar y normalizar números antes de generar enlaces
        add_filter('cv_whatsapp_phone', array($this, 'normalize_phone'), 10, 1);
        
        // Hook para wp_footer para normalizar enlaces ya existentes con JavaScript
        add_action('wp_footer', array($this, 'add_js_normalizer'), 999);
    }
    
    /**
     * Normalizar número de teléfono
     * Si tiene 9 dígitos (sin prefijo internacional), añadir +34
     * 
     * @param string $phone Número de teléfono original
     * @return string Número normalizado con prefijo internacional
     */
    public function normalize_phone($phone) {
        // Limpiar el teléfono (quitar espacios, guiones, paréntesis, etc.)
        $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Si está vacío, devolver vacío
        if (empty($clean_phone)) {
            return $phone;
        }
        
        // Si ya tiene prefijo internacional (+), dejarlo como está
        if (strpos($clean_phone, '+') === 0) {
            return $clean_phone;
        }
        
        // Si empieza con 00 (formato internacional alternativo), convertir a +
        if (strpos($clean_phone, '00') === 0) {
            return '+' . substr($clean_phone, 2);
        }
        
        // Si tiene exactamente 9 dígitos, es un número español sin prefijo
        if (strlen($clean_phone) === 9) {
            return '+34' . $clean_phone;
        }
        
        // Si tiene 11 dígitos y empieza con 34, añadir el +
        if (strlen($clean_phone) === 11 && strpos($clean_phone, '34') === 0) {
            return '+' . $clean_phone;
        }
        
        // En cualquier otro caso, devolver el número limpio
        return $clean_phone;
    }
    
    /**
     * Añadir JavaScript para normalizar enlaces de WhatsApp existentes
     */
    public function add_js_normalizer() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Función para normalizar números de teléfono
            function normalizeWhatsAppPhone(phone) {
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
            
            // Normalizar todos los enlaces de WhatsApp existentes
            $('a[href*="wa.me"], a[href*="whatsapp.com"], a[href*="api.whatsapp.com"]').each(function() {
                var href = $(this).attr('href');
                
                // Extraer el número de teléfono del enlace
                var phoneMatch = href.match(/phone=([^&]*)/);
                if (phoneMatch && phoneMatch[1]) {
                    var oldPhone = decodeURIComponent(phoneMatch[1]);
                    var newPhone = normalizeWhatsAppPhone(oldPhone);
                    
                    if (oldPhone !== newPhone) {
                        var newHref = href.replace('phone=' + phoneMatch[1], 'phone=' + encodeURIComponent(newPhone));
                        $(this).attr('href', newHref);
                        console.log('📱 CV WhatsApp: Normalizado ' + oldPhone + ' → ' + newPhone);
                    }
                }
                
                // También para wa.me/NUMERO directo
                var waMatch = href.match(/wa\.me\/([0-9+]+)/);
                if (waMatch && waMatch[1]) {
                    var oldPhone = waMatch[1];
                    var newPhone = normalizeWhatsAppPhone(oldPhone);
                    
                    if (oldPhone !== newPhone) {
                        var newHref = href.replace('wa.me/' + oldPhone, 'wa.me/' + newPhone);
                        $(this).attr('href', newHref);
                        console.log('📱 CV WhatsApp: Normalizado ' + oldPhone + ' → ' + newPhone);
                    }
                }
            });
        });
        </script>
        <?php
    }
}

/**
 * Función helper global para normalizar teléfonos
 */
function cv_normalize_whatsapp_phone($phone) {
    static $normalizer = null;
    
    if ($normalizer === null) {
        $normalizer = new CV_WhatsApp_Phone_Normalizer();
    }
    
    return $normalizer->normalize_phone($phone);
}

