<?php
/**
 * Fix para el error de AJAX en registro de vendedores
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_AJAX_Fix {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'add_ajax_fix'), 5);
    }
    
    /**
     * Agregar fix de AJAX
     */
    public function add_ajax_fix() {
        wp_add_inline_script('jquery', '
            console.log("CV FIX: Script de correccion AJAX cargado globalmente");
            jQuery(document).ready(function($) {
                var isVendorRegister = window.location.href.indexOf("vendor-register") !== -1;
                console.log("CV FIX: Pagina vendor-register detectada?", isVendorRegister);
                
                if (isVendorRegister) {
                    console.log("CV FIX: Instalando ajaxPrefilter para corregir WCFM");
                    if ($.ajaxPrefilter) {
                        $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
                            console.log("CV FIX: Peticion AJAX interceptada - tipo de data:", typeof options.data);
                            if (options.data && typeof options.data === "object" && !(options.data instanceof FormData)) {
                                console.log("CV FIX: Detectado objeto, convirtiendo a string...");
                                if (!options.contentType || options.contentType.indexOf("application/x-www-form-urlencoded") !== -1) {
                                    var oldData = JSON.stringify(options.data);
                                    options.data = $.param(options.data);
                                    console.log("CV FIX: CONVERTIDO OK - antes era objeto, ahora es string");
                                }
                            }
                        });
                        console.log("CV FIX: ajaxPrefilter INSTALADO Y ACTIVO");
                    } else {
                        console.error("CV FIX ERROR: ajaxPrefilter no esta disponible en jQuery");
                    }
                }
            });
        ', 'after');
    }
}

