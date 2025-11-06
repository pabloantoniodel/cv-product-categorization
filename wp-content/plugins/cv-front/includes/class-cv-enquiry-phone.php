<?php
/**
 * Añade campo de teléfono al formulario de consultas WCFM
 *
 * @package CV_Front
 * @version 2.3.7
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Enquiry_Phone {
    
    public function __construct() {
        // Cargar JavaScript para añadir el campo
        add_action('wp_footer', array($this, 'enqueue_phone_field_script'));
        
        // Guardar el teléfono cuando se envía la consulta
        add_action('wcfm_after_enquiry_submit', array($this, 'save_phone'), 10, 2);
    }
    
    /**
     * Encolar JavaScript para añadir campo de teléfono
     */
    public function enqueue_phone_field_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Función para añadir el campo de teléfono
            function addPhoneFieldToEnquiry() {
                // Verificar si ya existe el campo
                if ($("#enquiry_phone").length > 0) {
                    console.log("CVCard: Campo teléfono ya existe");
                    return;
                }
                
                // Buscar el textarea de consulta
                var $enquiryTextarea = $("#enquiry_comment");
                if ($enquiryTextarea.length === 0) {
                    console.log("CVCard: Formulario de consulta no encontrado");
                    return;
                }
                
                // Determinar si el usuario está logueado (si hay campos nombre/email, no está logueado)
                var isLoggedIn = $("#enquiry_author").length === 0;
                
                // SOLO mostrar el campo si el usuario NO está logueado
                if (isLoggedIn) {
                    console.log("CVCard: Usuario logueado - NO mostrar campo teléfono");
                    return;
                }
                
                // HTML del campo de teléfono (SIEMPRE obligatorio para no logueados)
                var phoneFieldHTML = '<p class="wcfm_popup_label">' +
                    '<strong for="enquiry_phone">Teléfono móvil <span class="required">*</span></strong>' +
                    '</p>' +
                    '<input id="enquiry_phone" name="enquiry_phone" type="tel" value="" ' +
                    'class="wcfm_popup_input" placeholder="Ej: +34 644 944 408" required>' +
                    '<div class="wcfm_clearfix"></div>' +
                    '<p class="comment-notes" style="margin-left:39%;">' +
                    '<span id="phone-notes">Introduce tu teléfono para que el vendedor pueda contactarte</span>' +
                    '</p>' +
                    '<div class="wcfm_clearfix"></div>';
                
                // Insertar después del textarea de consulta
                $enquiryTextarea.after(phoneFieldHTML);
                console.log("✅ CVCard: Campo teléfono añadido (obligatorio para no logueados)");
            }
            
            // Ejecutar al cargar la página
            addPhoneFieldToEnquiry();
            
            // Ejecutar cuando se abra el colorbox (formulario popup)
            $(document).on("cbox_complete", function() {
                setTimeout(addPhoneFieldToEnquiry, 100);
            });
            
            // Ejecutar cuando cambie el DOM (por si se recarga dinámicamente)
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        $(mutation.addedNodes).each(function() {
                            if ($(this).find("#enquiry_comment").length > 0 || $(this).attr("id") === "enquiry_comment") {
                                addPhoneFieldToEnquiry();
                            }
                        });
                    }
                });
            });
            
            if (document.body) {
                observer.observe(document.body, { childList: true, subtree: true });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Guardar el teléfono en la base de datos
     *
     * @param int $enquiry_id ID de la consulta
     * @param array $enquiry_data Datos de la consulta
     */
    public function save_phone($enquiry_id, $enquiry_data) {
        if (isset($_POST['enquiry_phone']) && !empty($_POST['enquiry_phone'])) {
            global $wpdb;
            
            $phone = sanitize_text_field($_POST['enquiry_phone']);
            
            // Guardar en wp_wcfm_enquiries_meta (tabla correcta)
            $wpdb->insert(
                $wpdb->prefix . 'wcfm_enquiries_meta',
                array(
                    'enquiry_id' => $enquiry_id,
                    'key' => 'Teléfono móvil',
                    'value' => $phone
                ),
                array('%d', '%s', '%s')
            );
            
            error_log('✅ CVCard: Teléfono guardado en consulta ID ' . $enquiry_id . ': ' . $phone);
        } else {
            error_log('⚠️ CVCard: No se recibió teléfono en la consulta ID ' . $enquiry_id);
        }
    }
}

// Inicializar
new CV_Enquiry_Phone();

