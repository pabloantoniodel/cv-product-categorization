<?php
/**
 * CV QR Instructions
 *
 * Añade instrucciones en la página QR con checkbox "No mostrar de nuevo"
 *
 * @package CV_Front
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_QR_Instructions {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'add_qr_instructions'));
    }
    
    /**
     * Añade las instrucciones en la página QR
     */
    public function add_qr_instructions() {
        // Solo en la página QR
        if (!is_page('qr')) {
            return;
        }
        
        // Verificar si el usuario ya ocultó las instrucciones
        $hide_instructions = isset($_COOKIE['cv_qr_hide_instructions']) && $_COOKIE['cv_qr_hide_instructions'] === 'true';
        
        if ($hide_instructions) {
            return;
        }
        
        ?>
        <style>
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Z-index alto para el diálogo del QR scanner - sobre carrito y menú */
        .qsr-dialog-title,
        .qsr-dialog-scanner-title,
        .MuiDialogTitle-root,
        .MuiDialog-root,
        .MuiDialog-container,
        .MuiDialog-paper,
        div[class*="qsr-dialog"],
        div[class*="MuiDialog"],
        #qrscannerredirect {
            z-index: 99999999 !important;
        }
        
        /* Asegurar que los botones y controles sean clicables */
        .MuiIconButton-root,
        button[aria-label="Close"],
        .qsr-dialog-title button,
        .qsr-dialog-scanner-title button,
        .MuiDialogTitle-root button,
        .MuiIconButton-edgeEnd {
            z-index: 999999999 !important;
            position: relative !important;
            pointer-events: auto !important;
        }
        
        /* Asegurar que todo el título sea clicable */
        .qsr-dialog-title,
        .qsr-dialog-scanner-title,
        .MuiDialogTitle-root {
            pointer-events: auto !important;
        }
        </style>
        
        <div id="cv-qr-instructions" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); z-index: 999999; max-width: 500px; width: 90%; max-height: 85vh; overflow-y: auto;">
            <!-- Botón X para cerrar el popup -->
            <button id="cv-qr-instructions-close-x" style="position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.2); color: white; border: 2px solid white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 24px; font-weight: bold; line-height: 1; display: flex; align-items: center; justify-content: center; transition: all 0.3s; z-index: 10;" onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='rotate(90deg)';" onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='rotate(0)';">
                ×
            </button>
            
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 70px; margin-bottom: 15px; animation: pulse 2s ease-in-out infinite;">📱</div>
                <h2 style="margin: 0; font-size: 26px; font-weight: 700;">Escanear QR de Comercio</h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 14px;">Captura tus tickets fácilmente</p>
            </div>
            
            <div style="background: rgba(255,255,255,0.15); border-radius: 15px; padding: 25px; margin-bottom: 20px; backdrop-filter: blur(10px);">
                <h3 style="margin: 0 0 15px 0; font-size: 18px; font-weight: 600;">📸 Pasos para escanear:</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="background: white; color: #667eea; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</div>
                        <div><strong>Escanea el QR</strong> del comercio que encuentres en la tienda</div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="background: white; color: #667eea; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</div>
                        <div><strong>Captura la foto</strong> de tu ticket de compra</div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="background: white; color: #667eea; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">3</div>
                        <div><strong>Introduce el importe</strong> del ticket</div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="background: white; color: #667eea; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">4</div>
                        <div><strong>Envía el ticket</strong> al comercio</div>
                    </div>
                </div>
            </div>
            
            <div style="background: rgba(255,255,255,0.2); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600;">✨ Beneficios:</h3>
                <ul style="line-height: 1.8; padding-left: 20px; margin: 0;">
                    <li>💰 Acumula descuentos en tus compras</li>
                    <li>🎁 Recibe promociones exclusivas</li>
                    <li>📊 Controla todos tus tickets</li>
                    <li>🏆 Accede a la plataforma completa</li>
                </ul>
            </div>
            
            <div style="text-align: center; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.3);">
                <label style="display: inline-flex; align-items: center; cursor: pointer; color: white; margin-bottom: 15px;">
                    <input type="checkbox" id="cv-qr-hide-checkbox" style="margin-right: 8px; cursor: pointer;">
                    <span>No mostrar de nuevo</span>
                </label>
                <br>
                <button id="cv-qr-close-btn" style="background: white; color: #667eea; border: none; padding: 18px 40px; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.2); min-height: 60px; display: inline-flex; align-items: center; justify-content: center; line-height: 1.4;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                    <span style="display: block;">Entendido<br>Iniciar Escáner</span>
                </button>
            </div>
        </div>
        
        <div id="cv-qr-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 999998;"></div>
        
        <!-- Botón flotante para cerrar el scanner QR -->
        <div id="cv-qr-close-floating" style="display: none; position: fixed; top: 20px; right: 20px; z-index: 9999999999; background: #FF4444; color: white; width: 60px; height: 60px; border-radius: 50%; border: 3px solid white; cursor: pointer; box-shadow: 0 4px 20px rgba(0,0,0,0.5); font-size: 32px; font-weight: bold; line-height: 60px; text-align: center;">
            ×
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Botón X para cerrar solo el popup de instrucciones (sin abrir scanner)
            $('#cv-qr-instructions-close-x').on('click', function() {
                console.log('CV QR: Cerrando popup de instrucciones sin abrir scanner');
                $('#cv-qr-instructions').fadeOut(300);
                $('#cv-qr-overlay').fadeOut(300);
            });
            
            // Cerrar modal y activar QR scanner
            $('#cv-qr-close-btn').on('click', function() {
                var hideInstructions = $('#cv-qr-hide-checkbox').is(':checked');
                
                if (hideInstructions) {
                    // Guardar en cookie por 1 año
                    var date = new Date();
                    date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
                    document.cookie = 'cv_qr_hide_instructions=true; expires=' + date.toUTCString() + '; path=/';
                }
                
                // Ocultar instrucciones
                $('#cv-qr-instructions').fadeOut(300);
                $('#cv-qr-overlay').fadeOut(300, function() {
                    // Después de cerrar, activar el QR scanner
                    if (typeof window.qrscannerredirect !== 'undefined' && typeof window.qrscannerredirect.open === 'function') {
                        window.qrscannerredirect.open();
                    } else {
                        // Fallback: hacer clic en el elemento que abre el scanner
                        $('.qr-scanner-redirect-open').first().trigger('click');
                    }
                    
                    // Mostrar botón flotante de cerrar después de 500ms
                    setTimeout(function() {
                        $('#cv-qr-close-floating').show();
                    }, 500);
                });
            });
            
            // Cerrar al hacer clic en el overlay (también activa scanner)
            $('#cv-qr-overlay').on('click', function() {
                $('#cv-qr-close-btn').trigger('click');
            });
            
            // Botón flotante para cerrar TANTO el popup de instrucciones COMO el scanner
            $(document).on('click', '#cv-qr-close-floating', function(e) {
                console.log('CV QR: Botón flotante clickeado');
                e.preventDefault();
                e.stopPropagation();
                
                var $btn = $(this);
                
                // Ocultar el botón inmediatamente
                $btn.hide();
                
                // PRIMERO: Cerrar el popup de instrucciones si está visible
                if ($('#cv-qr-instructions:visible').length > 0) {
                    console.log('CV QR: Cerrando popup de instrucciones');
                    $('#cv-qr-instructions').fadeOut(300);
                    $('#cv-qr-overlay').fadeOut(300);
                }
                
                // SEGUNDO: Cerrar el scanner QR con múltiples intentos
                var closeAttempts = 0;
                var maxAttempts = 5;
                
                var tryClose = function() {
                    closeAttempts++;
                    console.log('CV QR: Intento de cierre scanner #' + closeAttempts);
                    
                    // Método 1: Click en el botón X original (MUI)
                    var $closeBtn = $('.MuiIconButton-root[aria-label="Close"], button[aria-label="Close"], .qsr-dialog-title button, .MuiDialogTitle-root button, .MuiIconButton-edgeEnd');
                    if ($closeBtn.length > 0) {
                        console.log('CV QR: Encontrado ' + $closeBtn.length + ' botones close');
                        $closeBtn.each(function() {
                            this.click();
                        });
                    }
                    
                    // Método 2: Cerrar via API del plugin
                    if (typeof window.qrscannerredirect !== 'undefined') {
                        if (typeof window.qrscannerredirect.close === 'function') {
                            console.log('CV QR: Cerrando via API close()');
                            window.qrscannerredirect.close();
                        }
                        if (typeof window.qrscannerredirect.hide === 'function') {
                            console.log('CV QR: Cerrando via API hide()');
                            window.qrscannerredirect.hide();
                        }
                    }
                    
                    // Método 3: Ocultar el diálogo directamente
                    $('.MuiDialog-root, .qsr-dialog-root, #qrscannerredirect, div[role="dialog"]').fadeOut(200);
                    
                    // Método 4: Remover backdrop
                    $('.MuiBackdrop-root, .MuiModal-backdrop').fadeOut(200);
                    
                    // Si aún no cerró, intentar de nuevo
                    if (closeAttempts < maxAttempts) {
                        var isOpen = $('.MuiDialog-root:visible, #qrscannerredirect:visible').length > 0;
                        if (isOpen) {
                            setTimeout(tryClose, 200);
                        }
                    }
                };
                
                // Iniciar intentos de cierre del scanner
                tryClose();
            });
            
            // Detectar cuando se cierra el scanner por otros medios y ocultar el botón
            $(document).on('click', '.MuiIconButton-root[aria-label="Close"], button[aria-label="Close"]', function() {
                $('#cv-qr-close-floating').fadeOut(200);
            });
        });
        </script>
        <?php
    }
}

