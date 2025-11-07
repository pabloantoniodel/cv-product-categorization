<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/*
    Plugin Name: Waynance Woocomerce
    Plugin URI: https://waynance.com
    Description: Pagos con criptomonedas.
    Version: 2.0
    Author: Luis Yanez y Alejandro H. 
    Text domain: waynance
*/

add_action('plugins_loaded', 'init_dsk', 0);

function init_dsk() {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    class woocommerce_dsk extends WC_Payment_Gateway {

        public function __construct() { 
            global $woocommerce;

            $this->id           = 'dsk';
            $this->method_title = __('Waynance Pago', 'dsk-chearaan-woo');
            $this->icon         = plugins_url( '/imagenes/logo.png', __FILE__ );

            $this->has_fields   = false;
            $this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'woocommerce_dsk', home_url( '/' ) ) );
            $this->method_description  = __( 'Sistema de pago con criptomonedas con orden de pago en 1 click.', 'dsk-chearaan-woo');

            // Load the form fields.
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Define user set variables
            $this->api_url              = $this->settings['api_url'] ?? 'https://es.waynance.com/wordpress/';
            $this->title                = $this->settings['title'];
            $this->description          = $this->settings['description'];
            $this->order_status         = $this->get_option('order_status');
            $this->merchantid           = $this->settings['merchantid'];
            $this->hashKey              = $this->settings['hashKey'];
            $this->transactionDate      = date('Y-m-d H:i:s O');
            $this->woo_version          = $this->get_woo_version();

            // Actions
            add_action('init', array(&$this, 'successful_request'));
            add_action('woocommerce_api_woocommerce_dsk', array( &$this, 'successful_request' ));
            add_action('woocommerce_receipt_dsk', array(&$this, 'receipt_page'));
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ));
            }
        } 

        // Inicializar campos de formulario de configuración de puerta de enlace
        function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                                'title' => __( 'Activo/Desactivar:', 'dsk-chearaan-woo' ), 
                                'type' => 'checkbox', 
                                'label' => __( 'Activar Waynance', 'dsk-chearaan-woo' ), 
                                'default' => 'yes'
                            ), 
                'title' => array(
                                'title' => __( 'Título:', 'dsk-chearaan-woo' ), 
                                'type' => 'text', 
                                'desc_tip'    => true,
                                'description' => __( 'El título que el usuario ve durante el pago.', 'dsk-chearaan-woo' ), 
                                'default' => __( 'Waynance Pago', 'dsk-chearaan-woo' )
                            ),
                'description' => array(
                                'title' => __( 'Descripción:', 'dsk-chearaan-woo' ), 
                                'type' => 'textarea', 
                                'desc_tip'    => true,
                                'description' => __( 'Descripción que el usuario ve durante el pago.', 'dsk-chearaan-woo' ), 
                                'default' => __('Sistema de compra con criptomonedas con orden de pago en 1 click.', 'dsk-chearaan-woo')
                            ),
                'order_status' => array(
                                'title'       => __( 'Estado del pedido', 'dsk-chearaan-woo' ),
                                'type'        => 'select',
                                'class'       => 'wc-enhanced-select',
                                'description' => __( 'Elija si el estado del pedido que desea después del pago si es exitoso.', 'dsk-chearaan-woo' ),
                                'default'     => 'wc-processing',
                                'desc_tip'    => true,
                                'options'     => wc_get_order_statuses()
                ),
                'merchantid' => array(
                                'title' => __( 'URL:', 'dsk-chearaan-woo' ), 
                                'type' => 'text', 
                                'description' => __( 'Coloque la URL donde esta el Wordpress (https://waynance.com)', 'dsk-chearaan-woo' ), 
                                'default' => ''
                            ),
                'hashKey' => array(
                                'title' => __( 'APIKEY:', 'dsk-chearaan-woo' ), 
                                'type' => 'text', 
                                'description' => __( 'Copie el APIKEY del CRM de Waynance de su país.', 'dsk-chearaan-woo' ), 
                                'default' => ''
                            ),
                'api_url' => array(
                                'title'       => __( 'País', 'dsk-chearaan-woo' ),
                                'type'        => 'select',
                                'class'       => 'wc-enhanced-select',
                                'default'     => 'España',
                                'desc_tip'    => true,
                                'options' => array(
                                    'https://es.waynance.com/wordpress/' => 'España',
                                ),
                ),
            );

        }


        public function admin_options() {
            ?>
            <h3>WAYNANCE PAGO</h3>
            <p><?php _e('Sistema de pago con criptomonedas con orden de pago en 1 click.', 'dsk-chearaan-woo'); ?></p>

            <table class="form-table">
            <?php
                // Genere el HTML para el formulario de configuración.
                $this->generate_settings_html();
            ?>
            </table>
            <?php
            // EUR y USD
            //echo get_woocommerce_currency();
        } 

        // No hay campos de pago, pero queremos mostrar la descripción si está configurada.
        function payment_fields() {
            if ($this->description) echo wpautop(wptexturize($this->description));
        }

        /**
         * Generate the button link
         **/
        public function generate_dsk_form( $order_id ) {
            
            global $woocommerce;
            $order = new WC_Order($order_id);
            $amount = $order->get_total();
            $id_order = $order->get_id();
            $email_client = $order->get_billing_email();;

            $rest_url = $this->api_url;

            $sHash = hash('sha256', $this->hashKey.$amount.$id_order);
           
            wc_enqueue_js('
                jQuery(function(){
                            jQuery("body").block(
                                { 
                                    message: "<img src=\"'.$woocommerce->plugin_url().'/imagenes/uploading.gif\" alt=\"gig\" style=\"float:left; margin-right: 10px;\" />'.__('Gracias por su orden. Ahora lo estamos redirigiendo a WAYNANCE para realizar el pago..', 'dsk-chearaan-woo').'", 
                                    overlayCSS: 
                                    { 
                                        background: "#fff", 
                                        opacity: 0.5 
                                    },
                                    css: { 
                                        padding:        18, 
                                        textAlign:      "center", 
                                        color:          "#555", 
                                        border:         "2px solid #aaa", 
                                        backgroundColor:"#fff", 
                                        cursor:         "wait",
                                        lineHeight:     "30px"
                                    } 
                                });
                            jQuery("#submit_dsk_payment_form").click();
                        });
            ');

        return '<form action="https://wordpress.waynance.app/api/pay" method="post">
                    <input type="hidden" name="secureHash" value="'.$sHash.'" />
                    <input type="hidden" name="url" value="'.$this->merchantid.'" />
                    <input type="hidden" name="hashKey" value="'.$this->hashKey.'" />
                    <input type="hidden" name="orden" value="'.$id_order.'" />
                    <input type="hidden" name="invno" value="'.$amount.'" />
                    <input type="hidden" name="postURL" value="'.$this->notify_url.'" />
                    <input type="hidden" name="email" value="'.$email_client.'" />
                    <input type="hidden" name="currency" value="'.get_woocommerce_currency().'" />
                    
                    <input type="submit" class="button-alt" id="submit_dsk_payment_form" value="'.__('Pagar a través de WAYNANCE
', 'dsk-chearaan-woo').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'dsk-chearaan-woo').'</a>
                </form>';           
        }

        // Procesar el pago y devolver el resultado
        function process_payment( $order_id ) {
            global $woocommerce;
            $order = new WC_Order( $order_id );

            if($this->woo_version >= 2.1){
                $redirect = $order->get_checkout_payment_url( true );           
            }else if( $this->woo_version < 2.1 ){
                $redirect = add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))));
            }else{
                $redirect = add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))));
            }

            return array(
                'result'    => 'success',
                'redirect'  => $redirect
            );

        }

        function receipt_page( $order ) {
            echo '<p>'.__('Please click the button below to pay with Dsk.', 'dsk-chearaan-woo').'</p>';
            echo $this->generate_dsk_form( $order );
        }

        // La devolución de llamada del servidor fue válida, procesar la devolución de llamada (orden de actualización como aprobado/fallido, etc.).
        function successful_request($dsk_response) {
            global $woocommerce;

                header( 'HTTP/1.1 200 OK' );

                $order = new WC_Order( $_GET['pedido'] );

                if( $_GET['estatus'] == 0 || $_GET['estatus'] == 2 )
                {
                    // Reduce stock levels
                    $order->reduce_order_stock();
                    //$order->payment_complete();
                    if( $_GET['estatus'] == 2 ){
						$order->update_status($this->order_status);
					}
					else{ $order->update_status( 'on-hold' ); }
                    
					
					if( $_GET['metodoDePago'] == 'WPAY' )
					{ 
						$cortar = explode(',',$_GET['adicional']);
						$colocar = '<br>Método: WAYNANCE PAY<br>RED: '.$cortar[0].'<br>MONEDA: '.$cortar[1];
						$condicionPago = 'Pago exitoso';
					}
					if( $_GET['metodoDePago'] == 'TARJETA' )
					{ $colocar = '<br>Método: TARJETA'; $condicionPago = 'Pago exitoso'; }
					if( $_GET['metodoDePago'] == 'TRANSFERENCIA' )
					{ $colocar = '<br>Método: TRANSFERENCIA'; $condicionPago = 'En Espera'; }
					
                    $order->add_order_note($condicionPago.'<br>HASH: '.$_GET['hash'].'<br>PAGADO: '.$_GET['pagado'].$colocar);
                     // Remove cart
                    WC()->cart->empty_cart();
                    wp_redirect( $this->get_return_url($order) ); 
                    exit;
                }
                else
                {
                    if( $_GET['estatus'] == 1 )
                    {
                        $order->update_status('failed', sprintf(__('Pago fallido'.'<br>HASH: '.$_GET['hash'], 'dsk-chearaan-woo') ) );
                    }
                    else
                    {
       
                        $order->update_status('awaiting-shipment', sprintf(__('El cliente anulo la opereción.', 'dsk-chearaan-woo') ) );
                    }
                    wp_redirect($order->get_cancel_order_url()); exit;
                }  
        }

        function get_woo_version() {

            // If get_plugins() isn't available, require it
            if ( ! function_exists( 'get_plugins' ) )
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            // Create the plugins folder and file variables
            $plugin_folder = get_plugins( '/woocommerce' );
            $plugin_file = 'woocommerce.php';

            // If the plugin version number is set, return it 
            if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
                return $plugin_folder[$plugin_file]['Version'];

            } else {
                return NULL;
            }
        }
    }
}

function add_dsk( $methods ) {
    $methods[] = 'woocommerce_dsk'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_dsk' );