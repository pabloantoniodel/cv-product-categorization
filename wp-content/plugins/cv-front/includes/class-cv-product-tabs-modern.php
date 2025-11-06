<?php
/**
 * Estilos modernos para pestañas de productos WooCommerce
 * Diseño limpio y moderno con gradientes y sombras
 * 
 * @package CV_Front
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Product_Tabs_Modern {
    
    public function __construct() {
        // Añadir estilos CSS para las pestañas
        add_action('wp_head', array($this, 'add_modern_tabs_css'), 999);
    }
    
    /**
     * Añadir CSS moderno para pestañas de productos
     */
    public function add_modern_tabs_css() {
        // Solo en páginas de productos
        if (!is_product()) {
            return;
        }
        ?>
        <style id="cv-product-tabs-modern">
            /* ========== PRECIO DE PRODUCTO PROFESIONAL ========== */
            
            .product .price {
                text-align: right !important;
                margin: 20px 0 !important;
            }
            
            .product .price .woocommerce-Price-amount {
                font-size: 36px !important;
                font-weight: 700 !important;
                color: #1f2937 !important;
                display: inline-block !important;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                -webkit-background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                background-clip: text !important;
                letter-spacing: -0.5px !important;
            }
            
            .product .price .woocommerce-Price-amount bdi {
                font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif !important;
            }
            
            .product .price .woocommerce-Price-currencySymbol {
                font-size: 28px !important;
                font-weight: 600 !important;
                margin-left: 4px !important;
            }
            
            /* Si hay precio tachado (oferta) */
            .product .price del {
                opacity: 0.5 !important;
                font-size: 24px !important;
                display: block !important;
                margin-bottom: 8px !important;
            }
            
            .product .price ins {
                text-decoration: none !important;
                display: block !important;
            }
            
            /* Responsive móvil */
            @media (max-width: 768px) {
                /* Título del producto centrado en móvil */
                .product .product_title.entry-title,
                .product .summary .product_title,
                .single-product .product_title {
                    text-align: center !important;
                }
                
                .product .price {
                    text-align: center !important;
                }
                
                .product .price .woocommerce-Price-amount {
                    font-size: 32px !important;
                }
                
                .product .price .woocommerce-Price-currencySymbol {
                    font-size: 24px !important;
                }
            }
            
            /* ========== PESTAÑAS DE PRODUCTOS MODERNAS ========== */
            
            .woocommerce-tabs.wc-tabs-wrapper {
                margin-top: 40px !important;
                padding: 0 !important;
            }
            
            /* Contenedor de pestañas */
            .woocommerce-tabs ul.tabs {
                margin: 0 !important;
                padding: 0 !important;
                list-style: none !important;
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
                border-bottom: none !important;
                background: transparent !important;
                margin-bottom: 30px !important;
                justify-content: flex-start !important;
            }
            
            /* Cada pestaña */
            .woocommerce-tabs ul.tabs li {
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                background: none !important;
                border-radius: 12px !important;
                overflow: hidden !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                flex: 1 1 auto !important;
                min-width: 0 !important;
            }
            
            /* Enlaces de pestañas */
            .woocommerce-tabs ul.tabs li a {
                display: block !important;
                padding: 14px 20px !important;
                color: #6b7280 !important;
                font-weight: 600 !important;
                font-size: 14px !important;
                text-decoration: none !important;
                background: #f3f4f6 !important;
                border: 2px solid transparent !important;
                border-radius: 12px !important;
                transition: all 0.3s ease !important;
                position: relative !important;
                overflow: hidden !important;
                text-align: center !important;
                white-space: nowrap !important;
                width: 100% !important;
            }
            
            /* Hover en pestañas */
            .woocommerce-tabs ul.tabs li a:hover {
                color: #667eea !important;
                background: #ede9fe !important;
                border-color: #c7d2fe !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15) !important;
            }
            
            /* Pestaña activa */
            .woocommerce-tabs ul.tabs li.active a {
                color: #ffffff !important;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                border-color: #667eea !important;
                box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3) !important;
            }
            
            /* Efecto de brillo en pestaña activa */
            .woocommerce-tabs ul.tabs li.active a:before {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: -100% !important;
                width: 100% !important;
                height: 100% !important;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent) !important;
                animation: cv-tab-shine 3s infinite !important;
            }
            
            @keyframes cv-tab-shine {
                0% { left: -100%; }
                50% { left: 100%; }
                100% { left: 100%; }
            }
            
            /* Contenido de pestañas */
            .woocommerce-Tabs-panel {
                padding: 30px !important;
                background: #ffffff !important;
                border-radius: 16px !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
                border: 1px solid #e5e7eb !important;
                margin-top: 0 !important;
            }
            
            /* Títulos dentro de las pestañas */
            .woocommerce-Tabs-panel h2 {
                color: #1f2937 !important;
                font-size: 22px !important;
                font-weight: 700 !important;
                margin-bottom: 20px !important;
                padding-bottom: 15px !important;
                border-bottom: 3px solid #667eea !important;
                display: inline-block !important;
            }
            
            /* Estilo para "Aún no hay..." */
            .woocommerce-Tabs-panel .woocommerce-noreviews,
            .woocommerce-Tabs-panel .wcfm-noenquiries {
                padding: 40px 20px !important;
                text-align: center !important;
                color: #9ca3af !important;
                font-size: 16px !important;
                background: #f9fafb !important;
                border-radius: 12px !important;
                border: 2px dashed #e5e7eb !important;
            }
            
            /* Formulario de reseñas */
            .woocommerce-Tabs-panel #review_form {
                background: #f9fafb !important;
                padding: 25px !important;
                border-radius: 12px !important;
                border: 1px solid #e5e7eb !important;
                margin-top: 25px !important;
            }
            
            .woocommerce-Tabs-panel #review_form .comment-reply-title {
                color: #1f2937 !important;
                font-size: 18px !important;
                font-weight: 600 !important;
                margin-bottom: 20px !important;
            }
            
            /* Estrellas de valoración */
            .woocommerce-Tabs-panel .stars a {
                color: #fbbf24 !important;
                font-size: 20px !important;
            }
            
            .woocommerce-Tabs-panel .stars a:hover,
            .woocommerce-Tabs-panel .stars a:focus {
                color: #f59e0b !important;
            }
            
            /* Botón enviar reseña */
            .woocommerce-Tabs-panel #respond .form-submit input[type="submit"] {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: #ffffff !important;
                border: none !important;
                padding: 12px 32px !important;
                border-radius: 8px !important;
                font-weight: 600 !important;
                font-size: 15px !important;
                cursor: pointer !important;
                transition: all 0.3s ease !important;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
            }
            
            .woocommerce-Tabs-panel #respond .form-submit input[type="submit"]:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
            }
            
            /* Campos de formulario */
            .woocommerce-Tabs-panel #respond textarea,
            .woocommerce-Tabs-panel #respond input[type="text"],
            .woocommerce-Tabs-panel #respond input[type="email"] {
                border: 2px solid #e5e7eb !important;
                border-radius: 8px !important;
                padding: 12px 16px !important;
                font-size: 15px !important;
                transition: all 0.3s ease !important;
            }
            
            .woocommerce-Tabs-panel #respond textarea:focus,
            .woocommerce-Tabs-panel #respond input[type="text"]:focus,
            .woocommerce-Tabs-panel #respond input[type="email"]:focus {
                border-color: #667eea !important;
                outline: none !important;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
            }
            
            /* Responsive móvil */
            @media (max-width: 768px) {
                .woocommerce-tabs ul.tabs {
                    flex-direction: column !important;
                    gap: 6px !important;
                }
                
                .woocommerce-tabs ul.tabs li {
                    width: 100% !important;
                }
                
                .woocommerce-tabs ul.tabs li a {
                    padding: 16px 20px !important;
                    text-align: center !important;
                }
                
                .woocommerce-Tabs-panel {
                    padding: 20px !important;
                }
                
                .woocommerce-Tabs-panel h2 {
                    font-size: 20px !important;
                }
            }
            
            /* Iconos para cada pestaña */
            .woocommerce-tabs ul.tabs li.description_tab a:before {
                content: '\f15c' !important;
                font-family: 'Font Awesome 5 Free' !important;
                font-weight: 900 !important;
                margin-right: 8px !important;
            }
            
            .woocommerce-tabs ul.tabs li.reviews_tab a:before {
                content: '\f005' !important;
                font-family: 'Font Awesome 5 Free' !important;
                font-weight: 900 !important;
                margin-right: 8px !important;
            }
            
            .woocommerce-tabs ul.tabs li.wcfm_product_multivendor_tab_tab a:before {
                content: '\f468' !important;
                font-family: 'Font Awesome 5 Free' !important;
                font-weight: 900 !important;
                margin-right: 8px !important;
            }
            
            .woocommerce-tabs ul.tabs li.wcfm_policies_tab_tab a:before {
                content: '\f0f6' !important;
                font-family: 'Font Awesome 5 Free' !important;
                font-weight: 900 !important;
                margin-right: 8px !important;
            }
            
            .woocommerce-tabs ul.tabs li.wcfm_enquiry_tab_tab a:before {
                content: '\f059' !important;
                font-family: 'Font Awesome 5 Free' !important;
                font-weight: 900 !important;
                margin-right: 8px !important;
            }
        </style>
        <?php
    }
}

new CV_Product_Tabs_Modern();

