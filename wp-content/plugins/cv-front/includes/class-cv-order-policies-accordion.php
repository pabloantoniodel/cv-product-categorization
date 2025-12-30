<?php
/**
 * CV Front: Order Policies Accordion
 * 
 * Convierte la tabla de políticas en un acordeón moderno con pestañas verticales
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CV_Order_Policies_Accordion {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// Interceptar el output de políticas en la página de pedido (prioridad 30 para ejecutarse después del original)
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'replace_policies_table_with_accordion' ), 30, 1 );
		
		// También en la página de confirmación (thankyou) - recibe order_id, no order
		add_action( 'woocommerce_thankyou', array( $this, 'replace_policies_table_with_accordion_thankyou' ), 30, 1 );
		
		// Cargar estilos y scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_accordion_assets' ) );
		
		// Añadir CSS para ancho de página
		add_action( 'wp_head', array( $this, 'add_page_width_css' ), 999 );
	}
	
	/**
	 * Handler para woocommerce_thankyou (recibe order_id, no order)
	 */
	public function replace_policies_table_with_accordion_thankyou( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$this->replace_policies_table_with_accordion( $order );
		}
	}
	
	/**
	 * Añadir CSS para ancho de página (90% con 10% de margen a cada lado)
	 */
	public function add_page_width_css() {
		if ( is_checkout() || is_wc_endpoint_url( 'order-received' ) || is_account_page() ) {
			?>
			<style id="cv-order-policies-page-width">
				/* Ancho de página: 90% con 10% de margen a cada lado */
				.woocommerce-checkout .entry-content,
				.woocommerce-thankyou .entry-content,
				.woocommerce-order-received .entry-content,
				.woocommerce .entry-content,
				.woocommerce-checkout .site-content,
				.woocommerce-thankyou .site-content,
				.woocommerce-order-received .site-content,
				.woocommerce .site-content {
					max-width: 90% !important;
					width: 90% !important;
					margin-left: 5% !important;
					margin-right: 5% !important;
				}
				
				/* Contenedores de pedido */
				.woocommerce-order-details,
				.woocommerce-order-details__section,
				.woocommerce .col2-set,
				.woocommerce .woocommerce-order-details {
					max-width: 100% !important;
					width: 100% !important;
				}
			</style>
			<?php
		}
	}
	
	/**
	 * Cargar estilos y scripts del acordeón
	 */
	public function enqueue_accordion_assets() {
		// Cargar en páginas de cuenta y en la página de confirmación (checkout)
		if ( is_account_page() || is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
			wp_enqueue_style(
				'cv-order-policies-accordion',
				CV_FRONT_PLUGIN_URL . 'assets/css/order-policies-accordion.css',
				array(),
				CV_FRONT_VERSION
			);
			
			wp_enqueue_script(
				'cv-order-policies-accordion',
				CV_FRONT_PLUGIN_URL . 'assets/js/order-policies-accordion.js',
				array( 'jquery' ),
				CV_FRONT_VERSION,
				true
			);
		}
	}
	
	/**
	 * Reemplazar la tabla de políticas con un acordeón moderno
	 */
	public function replace_policies_table_with_accordion( $order ) {
		global $WCFM, $WCFMmp;
		
		// Verificar si las políticas están habilitadas
		if ( ! apply_filters( 'wcfm_is_pref_policies', true ) ) {
			return;
		}
		
		$wcfm_vendor_invoice_options = get_option( 'wcfm_vendor_invoice_options', array() );
		if ( isset( $wcfm_vendor_invoice_options['policies'] ) ) {
			$wcfm_vendor_invoice_policies = isset( $wcfm_vendor_invoice_options['policies'] ) ? 'yes' : '';
		} else {
			$wcfm_vendor_invoice_policies = apply_filters( 'wcfm_is_allow_policies_under_order_details', true );
		}
		
		if ( ! $wcfm_vendor_invoice_policies || ! apply_filters( 'wcfm_is_allow_policies_under_order_details', true ) ) {
			return;
		}
		
		$order_items = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
		$processed_vendor_ids = array();
		$policies_data = array();
		
		// Recopilar todas las políticas de los vendedores
		foreach ( $order_items as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$vendor_id  = wcfm_get_vendor_id_by_post( $product_id );
			
			if ( ! $vendor_id || ! wcfm_is_vendor( $vendor_id ) ) {
				continue;
			}
			
			if ( ( apply_filters( 'wcfm_is_allow_order_item_policies_by_vendor', true ) || ! apply_filters( 'wcfm_is_show_marketplace_itemwise_orders', true ) ) && in_array( $vendor_id, $processed_vendor_ids ) ) {
				continue;
			}
			
			$processed_vendor_ids[ $vendor_id ] = $vendor_id;
			
			if ( wcfm_vendor_has_capability( $vendor_id, 'policy' ) && wcfm_vendor_has_capability( $vendor_id, 'vendor_policy' ) ) {
				$store_name          = wcfm_get_vendor_store_name( $vendor_id );
				$shipping_policy     = $WCFM->wcfm_policy->get_shipping_policy( $product_id );
				$refund_policy       = $WCFM->wcfm_policy->get_refund_policy( $product_id );
				$cancellation_policy = $WCFM->wcfm_policy->get_cancellation_policy( $product_id );
				$customer_support_details = wcfmmp_get_store( $vendor_id )->get_customer_support_details();
				
				if ( wcfm_empty( $shipping_policy ) && wcfm_empty( $refund_policy ) && wcfm_empty( $cancellation_policy ) && wcfm_empty( $customer_support_details ) ) {
					continue;
				}
				
				$policies_data[ $vendor_id ] = array(
					'store_name'          => $store_name,
					'shipping_policy'     => $shipping_policy,
					'refund_policy'       => $refund_policy,
					'cancellation_policy' => $cancellation_policy,
					'customer_support'    => $customer_support_details,
					'product_id'          => $product_id,
				);
			}
		}
		
		// Si hay políticas, mostrar el acordeón
		if ( ! empty( $policies_data ) ) {
			// Ocultar la tabla original usando CSS (se aplicará vía JavaScript también)
			echo '<style id="cv-policies-accordion-hide-original">
				/* Ocultar tablas de políticas originales */
				table[width="100%"]:has(th:contains("Shipping Policy")),
				table[width="100%"]:has(th:contains("Refund Policy")),
				table[width="100%"]:has(th:contains("Cancellation")),
				table[width="100%"]:has(th:contains("Customer Support")),
				h2[style*="font-size: 18px"]:has-text("Policies") {
					display: none !important;
				}
			</style>';
			
			// Mostrar el acordeón
			$this->render_policies_accordion( $policies_data );
		}
	}
	
	/**
	 * Renderizar el acordeón de políticas
	 */
	private function render_policies_accordion( $policies_data ) {
		foreach ( $policies_data as $vendor_id => $policies ) {
			$store_name = $policies['store_name'];
			$has_policies = false;
			$policy_items = array();
			
			// Preparar los items del acordeón
			if ( ! wcfm_empty( $policies['shipping_policy'] ) ) {
				$policy_items[] = array(
					'title'   => apply_filters( 'wcfm_shipping_policies_heading', __( 'Política de Envío', 'wc-frontend-manager' ) ),
					'content' => $policies['shipping_policy'],
					'icon'    => 'fa-truck',
					'id'      => 'shipping-' . $vendor_id,
				);
				$has_policies = true;
			}
			
			if ( ! wcfm_empty( $policies['refund_policy'] ) ) {
				$policy_items[] = array(
					'title'   => apply_filters( 'wcfm_refund_policies_heading', __( 'Política de Reembolso', 'wc-frontend-manager' ) ),
					'content' => $policies['refund_policy'],
					'icon'    => 'fa-undo',
					'id'      => 'refund-' . $vendor_id,
				);
				$has_policies = true;
			}
			
			if ( ! wcfm_empty( $policies['cancellation_policy'] ) ) {
				$policy_items[] = array(
					'title'   => apply_filters( 'wcfm_cancellation_policies_heading', __( 'Política de Cancelación / Devolución / Intercambio', 'wc-frontend-manager' ) ),
					'content' => $policies['cancellation_policy'],
					'icon'    => 'fa-times-circle',
					'id'      => 'cancellation-' . $vendor_id,
				);
				$has_policies = true;
			}
			
			if ( ! wcfm_empty( $policies['customer_support'] ) && wcfm_vendor_has_capability( $vendor_id, 'customer_support' ) ) {
				$policy_items[] = array(
					'title'   => apply_filters( 'wcfm_customer_support_heading', __( 'Atención al Cliente', 'wc-frontend-manager' ) ),
					'content' => $policies['customer_support'],
					'icon'    => 'fa-headset',
					'id'      => 'support-' . $vendor_id,
				);
				$has_policies = true;
			}
			
			if ( $has_policies ) {
				?>
				<div class="cv-policies-accordion-wrapper" data-vendor-id="<?php echo esc_attr( $vendor_id ); ?>" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; display: block !important; float: none !important; clear: both !important; margin-left: 0 !important; margin-right: 0 !important;">
					<h3 class="cv-policies-store-title">
						<?php echo esc_html__( 'Políticas', 'wc-multivendor-marketplace' ); ?>
					</h3>
					<div class="cv-policies-accordion">
						<?php foreach ( $policy_items as $index => $item ) : ?>
							<div class="cv-accordion-item <?php echo $index === 0 ? 'active' : ''; ?>">
								<div class="cv-accordion-header" data-target="<?php echo esc_attr( $item['id'] ); ?>">
									<div class="cv-accordion-header-content">
										<i class="fas <?php echo esc_attr( $item['icon'] ); ?>"></i>
										<span class="cv-accordion-title"><?php echo esc_html( $item['title'] ); ?></span>
									</div>
									<i class="fas fa-chevron-down cv-accordion-arrow"></i>
								</div>
								<div class="cv-accordion-content" id="<?php echo esc_attr( $item['id'] ); ?>">
									<div class="cv-accordion-content-inner">
										<?php echo wp_kses_post( $item['content'] ); ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php
			}
		}
	}
}

// Inicializar
new CV_Order_Policies_Accordion();

