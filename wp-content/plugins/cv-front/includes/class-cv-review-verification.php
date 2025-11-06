<?php
/**
 * Verificación de email y teléfono en reseñas de productos
 * Similar a las consultas WCFM
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Review_Verification {
    
    public function __construct() {
        // Añadir campos al formulario de reseñas
        add_filter('comment_form_default_fields', array($this, 'add_review_fields'), 10, 1);
        
        // WooCommerce tiene su propio filtro para reseñas
        add_filter('woocommerce_product_review_comment_form_args', array($this, 'add_review_fields_wc'), 10, 1);
        
        // Hook para formulario de reseñas de tienda WCFM
        add_action('wcfmmp_store_before_new_review', array($this, 'add_store_review_fields'), 10, 1);
        
        // Validar campos antes de guardar
        add_action('pre_comment_on_post', array($this, 'validate_review_fields'), 10, 1);
        
        // Guardar campos personalizados y manejar verificación de email
        add_action('comment_post', array($this, 'save_review_fields'), 10, 3);
        
        // Mostrar campos en el admin
        add_action('add_meta_boxes', array($this, 'add_review_meta_box'));
        
        // Cargar CSS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // Manejar verificación de email
        add_action('init', array($this, 'handle_email_verification'));
    }
    
    /**
     * Cargar estilos CSS
     */
    public function enqueue_styles() {
        if (is_product()) {
            wp_enqueue_style(
                'cv-review-verification',
                CV_FRONT_PLUGIN_URL . 'assets/css/review-verification.css',
                array(),
                CV_FRONT_VERSION
            );
        }
    }
    
    /**
     * Añadir campos de email y teléfono al formulario de reseñas
     */
    public function add_review_fields($fields) {
        // Solo en productos
        if (!is_product()) {
            return $fields;
        }
        
        // Añadir campo de teléfono móvil (para usuarios no logueados)
        if (!is_user_logged_in()) {
            $fields['phone'] = '<p class="comment-form-phone">
                <label for="phone">Teléfono móvil <span class="required">*</span></label>
                <input id="phone" name="phone" type="tel" value="" size="30" required />
            </p>';
        }
        
        return $fields;
    }
    
    /**
     * Añadir campos para WooCommerce específicamente
     */
    public function add_review_fields_wc($comment_form) {
        // Solo para usuarios no logueados
        if (!is_user_logged_in()) {
            $phone_field = '<p class="comment-form-phone">
                <label for="phone">Teléfono móvil <span class="required">*</span></label>
                <input id="phone" name="phone" type="tel" value="" size="30" required />
            </p>';
            
            // Añadir después del campo de email
            if (isset($comment_form['fields'])) {
                $comment_form['fields']['phone'] = $phone_field;
            } else {
                $comment_form['fields'] = array('phone' => $phone_field);
            }
        }
        
        return $comment_form;
    }
    
    /**
     * Añadir campos al formulario de reseñas de tienda WCFM
     */
    public function add_store_review_fields($store_id) {
        // Solo para usuarios no logueados
        if (!is_user_logged_in()) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Añadir campos después del textarea de reseña
                var phoneField = '<div class="review_contact_fields" style="margin: 10px 0;">' +
                    '<p style="margin-bottom: 10px;">' +
                        '<label for="store_review_email" style="display: block; margin-bottom: 5px; font-weight: 600;">Email <span style="color: red;">*</span></label>' +
                        '<input type="email" id="store_review_email" name="store_review_email" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />' +
                    '</p>' +
                    '<p style="margin-bottom: 10px;">' +
                        '<label for="store_review_phone" style="display: block; margin-bottom: 5px; font-weight: 600;">Teléfono móvil <span style="color: red;">*</span></label>' +
                        '<input type="tel" id="store_review_phone" name="store_review_phone" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />' +
                    '</p>' +
                '</div>';
                
                $('#wcfmmp_store_review_comment').after(phoneField);
            });
            </script>
            <?php
        }
    }
    
    /**
     * Validar campos antes de guardar
     */
    public function validate_review_fields($post_id) {
        // Solo validar en productos
        if (!is_product()) {
            return;
        }
        
        // Solo para usuarios no logueados
        if (is_user_logged_in()) {
            return;
        }
        
        // Validar email (ya viene por defecto de WooCommerce)
        if (empty($_POST['email'])) {
            wp_die('Error: El email es obligatorio.');
        }
        
        // Validar teléfono móvil
        if (empty($_POST['phone'])) {
            wp_die('Error: El teléfono móvil es obligatorio.');
        }
        
        // Validar formato de teléfono (básico)
        $phone = sanitize_text_field($_POST['phone']);
        if (!preg_match('/^[0-9+\s()-]{9,20}$/', $phone)) {
            wp_die('Error: Formato de teléfono no válido.');
        }
    }
    
    /**
     * Guardar campos personalizados en meta del comentario
     */
    public function save_review_fields($comment_id, $comment_approved, $commentdata) {
        // Solo en productos
        if (!isset($commentdata['comment_post_ID'])) {
            return;
        }
        
        $post_id = $commentdata['comment_post_ID'];
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Guardar teléfono móvil
        if (isset($_POST['phone'])) {
            $phone = sanitize_text_field($_POST['phone']);
            update_comment_meta($comment_id, 'review_phone', $phone);
        }
        
        // Para usuarios no logueados, marcar como pendiente de verificación
        if (!is_user_logged_in() && $comment_approved == 1) {
            // Generar token de verificación
            $verification_token = wp_generate_password(32, false);
            update_comment_meta($comment_id, 'review_verification_token', $verification_token);
            
            // Marcar como pendiente
            wp_set_comment_status($comment_id, 'hold');
            
            // Enviar email de verificación
            $this->send_verification_email($comment_id, $commentdata['comment_author_email'], $verification_token);
        }
    }
    
    /**
     * Enviar email de verificación
     */
    private function send_verification_email($comment_id, $email, $token) {
        $product_id = get_comment($comment_id)->comment_post_ID;
        $product_title = get_the_title($product_id);
        $verification_url = add_query_arg(array(
            'cv_verify_review' => $token,
            'comment_id' => $comment_id
        ), home_url());
        
        $subject = 'Verifica tu email para publicar tu reseña';
        $message = "Hola,\n\n";
        $message .= "Has enviado una reseña para el producto: " . $product_title . "\n\n";
        $message .= "Para publicar tu reseña, por favor verifica tu email haciendo clic en el siguiente enlace:\n\n";
        $message .= $verification_url . "\n\n";
        $message .= "Si no has enviado esta reseña, puedes ignorar este email.\n\n";
        $message .= "Gracias.";
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Manejar verificación de email
     */
    public function handle_email_verification() {
        if (isset($_GET['cv_verify_review']) && isset($_GET['comment_id'])) {
            $token = sanitize_text_field($_GET['cv_verify_review']);
            $comment_id = intval($_GET['comment_id']);
            
            $stored_token = get_comment_meta($comment_id, 'review_verification_token', true);
            
            if ($stored_token === $token) {
                // Token válido, aprobar comentario
                wp_set_comment_status($comment_id, 'approve');
                delete_comment_meta($comment_id, 'review_verification_token');
                
                // Redirigir con mensaje de éxito
                $product_id = get_comment($comment_id)->comment_post_ID;
                wp_redirect(add_query_arg('verified', '1', get_permalink($product_id)));
                exit;
            } else {
                // Token inválido
                wp_die('Token de verificación inválido o expirado.');
            }
        }
    }
    
    /**
     * Añadir columna en lista de comentarios del admin
     */
    public function add_review_meta_box() {
        // Añadir columna en lista de comentarios
        add_filter('manage_edit-comments_columns', array($this, 'add_phone_column'));
        add_action('manage_comments_custom_column', array($this, 'display_phone_column'), 10, 2);
    }
    
    /**
     * Añadir columna de teléfono
     */
    public function add_phone_column($columns) {
        $columns['phone'] = 'Teléfono';
        return $columns;
    }
    
    /**
     * Mostrar teléfono en la columna
     */
    public function display_phone_column($column, $comment_id) {
        if ($column === 'phone') {
            $phone = get_comment_meta($comment_id, 'review_phone', true);
            echo esc_html($phone ? $phone : '-');
        }
    }
}

new CV_Review_Verification();

