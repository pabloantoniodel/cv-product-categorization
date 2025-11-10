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
            $this->notify_url   = WC()->api_request_url( 'woocommerce_dsk' );
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
            $order        = wc_get_order( $order_id );

            if ( ! $order ) {
                wc_get_logger()->error( sprintf( '[Waynance] Pedido %d no encontrado al generar el formulario.', $order_id ), array( 'source' => 'waynance' ) );
                return '';
            }

            $amount       = (float) $order->get_total();
            $id_order     = $order->get_id();
            $email_client = $order->get_billing_email();

            $rest_url     = $this->api_url;
            $sHash        = hash( 'sha256', $this->hashKey . $amount . $id_order );

            update_post_meta( $order_id, '_waynance_secure_hash', $sHash );
            update_post_meta( $order_id, '_waynance_expected_total', $amount );
            update_post_meta( $order_id, '_waynance_email', $email_client );
           
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

        return '<form action="' . esc_url( $rest_url ) . '" method="post">
                    <input type="hidden" name="secureHash" value="' . esc_attr( $sHash ) . '" />
                    <input type="hidden" name="url" value="' . esc_attr( $this->merchantid ) . '" />
                    <input type="hidden" name="hashKey" value="' . esc_attr( $this->hashKey ) . '" />
                    <input type="hidden" name="orden" value="' . esc_attr( $id_order ) . '" />
                    <input type="hidden" name="invno" value="' . esc_attr( $amount ) . '" />
                    <input type="hidden" name="postURL" value="' . esc_attr( $this->notify_url ) . '" />
                    <input type="hidden" name="email" value="' . esc_attr( $email_client ) . '" />
                    <input type="hidden" name="currency" value="' . esc_attr( get_woocommerce_currency() ) . '" />

                    <input type="submit" class="button-alt" id="submit_dsk_payment_form" value="' . esc_attr__( 'Pagar a través de WAYNANCE', 'dsk-chearaan-woo' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . esc_html__( 'Cancel order &amp; restore cart', 'dsk-chearaan-woo' ) . '</a>
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
        function successful_request() {
            if ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
                status_header( 405 );
                exit;
            }

            $params = filter_input_array(
                INPUT_GET,
                array(
                    'pedido'        => FILTER_SANITIZE_NUMBER_INT,
                    'estatus'       => FILTER_UNSAFE_RAW,
                    'hash'          => FILTER_UNSAFE_RAW,
                    'pagado'        => FILTER_UNSAFE_RAW,
                    'metodoDePago'  => FILTER_UNSAFE_RAW,
                    'adicional'     => FILTER_UNSAFE_RAW,
                )
            );

            $order_id = isset( $params['pedido'] ) ? absint( $params['pedido'] ) : 0;
            if ( ! $order_id ) {
                status_header( 400 );
                exit;
            }

            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                wc_get_logger()->warning( sprintf( '[Waynance] Pedido %d no encontrado en callback.', $order_id ), array( 'source' => 'waynance' ) );
                status_header( 404 );
                exit;
            }

            $received_hash = isset( $params['hash'] ) ? sanitize_text_field( wp_unslash( $params['hash'] ) ) : '';
            $expected_hash = (string) get_post_meta( $order_id, '_waynance_secure_hash', true );

            if ( empty( $received_hash ) || empty( $expected_hash ) || ! hash_equals( $expected_hash, $received_hash ) ) {
                $order->add_order_note( __( 'Waynance: hash inválido en la notificación.', 'dsk-chearaan-woo' ) );
                wc_get_logger()->warning( sprintf( '[Waynance] Hash inválido para pedido %d.', $order_id ), array( 'source' => 'waynance' ) );
                status_header( 400 );
                exit;
            }

            $expected_total = (float) get_post_meta( $order_id, '_waynance_expected_total', true );
            $paid_amount    = isset( $params['pagado'] ) ? (float) $params['pagado'] : 0.0;

            if ( $expected_total > 0 && $paid_amount > 0 && abs( $expected_total - $paid_amount ) > 0.01 ) {
                $order->add_order_note( sprintf( __( 'Waynance: importe recibido (%1$s) no coincide con el esperado (%2$s).', 'dsk-chearaan-woo' ), $paid_amount, $expected_total ) );
                wc_get_logger()->warning( sprintf( '[Waynance] Pedido %d con importe inconsistente.', $order_id ), array( 'source' => 'waynance' ) );
                status_header( 400 );
                exit;
            }

            $status = isset( $params['estatus'] ) ? sanitize_text_field( wp_unslash( $params['estatus'] ) ) : '';

            if ( '0' === $status || '2' === $status ) {
                $order->reduce_order_stock();

                if ( '2' === $status ) {
                    $order->update_status( $this->order_status );
                } else {
                    $order->update_status( 'on-hold' );
                }

                $note = __( 'Pago recibido a través de Waynance.', 'dsk-chearaan-woo' );
                $method = isset( $params['metodoDePago'] ) ? sanitize_text_field( wp_unslash( $params['metodoDePago'] ) ) : '';
                if ( $method ) {
                    $note .= '<br>' . esc_html__( 'Método:', 'dsk-chearaan-woo' ) . ' ' . esc_html( $method );
                }
                if ( ! empty( $params['adicional'] ) ) {
                    $extra = explode( ',', $params['adicional'] );
                    if ( isset( $extra[0] ) ) {
                        $note .= '<br>' . esc_html__( 'RED:', 'dsk-chearaan-woo' ) . ' ' . esc_html( $extra[0] );
                    }
                    if ( isset( $extra[1] ) ) {
                        $note .= '<br>' . esc_html__( 'MONEDA:', 'dsk-chearaan-woo' ) . ' ' . esc_html( $extra[1] );
                    }
                }
                if ( $paid_amount ) {
                    $note .= '<br>' . esc_html__( 'PAGADO:', 'dsk-chearaan-woo' ) . ' ' . wp_kses_post( wc_price( $paid_amount ) );
                }
                $note .= '<br>' . esc_html__( 'HASH:', 'dsk-chearaan-woo' ) . ' ' . esc_html( $received_hash );

                $order->add_order_note( $note );

                wp_safe_redirect( $this->get_return_url( $order ) );
                exit;
            }

            if ( '1' === $status ) {
                $order->update_status( 'failed', __( 'Waynance: pago fallido.', 'dsk-chearaan-woo' ) . '<br>HASH: ' . esc_html( $received_hash ) );
            } else {
                $order->update_status( 'cancelled', __( 'Waynance: el cliente canceló la operación.', 'dsk-chearaan-woo' ) );
            }

            wp_safe_redirect( $order->get_cancel_order_url() );
            exit;
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