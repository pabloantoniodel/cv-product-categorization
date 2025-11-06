<?php
/**
 * Página de Contacto con Categorías
 * 
 * @package CV_Front
 * @since 2.5.9
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Contact_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('cv_contacto', array($this, 'render_contact_page'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_cv_send_contact_form', array($this, 'ajax_send_contact_form'));
        add_action('wp_ajax_nopriv_cv_send_contact_form', array($this, 'ajax_send_contact_form'));
    }
    
    /**
     * Encolar scripts y estilos
     */
    public function enqueue_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'cv_contacto')) {
            wp_enqueue_style(
                'cv-contact-page',
                plugins_url('assets/css/contact-page.css', dirname(__FILE__)),
                array(),
                '2.5.9'
            );
            
            wp_enqueue_script(
                'cv-contact-page',
                plugins_url('assets/js/contact-page.js', dirname(__FILE__)),
                array('jquery'),
                '2.5.9',
                true
            );
            
            wp_localize_script('cv-contact-page', 'cvContactData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cv_contact_nonce'),
                'whatsapp_number' => '+34655573433',
                'whatsapp_message' => urlencode('Hola, me gustaría hablar con el equipo de Ciudad Virtual')
            ));
        }
    }
    
    /**
     * Renderizar página de contacto
     */
    public function render_contact_page($atts) {
        ob_start();
        ?>
        <div class="cv-contact-container">
            <!-- Encabezado -->
            <div class="cv-contact-header">
                <h1 class="cv-contact-title">¿Cómo podemos ayudarte?</h1>
                <p class="cv-contact-subtitle">Selecciona el tipo de consulta que necesitas realizar</p>
            </div>
            
            <!-- Categorías de Contacto -->
            <div class="cv-contact-categories">
                
                <!-- Categoría 1: Consulta Plataforma -->
                <div class="cv-contact-category" data-category="plataforma">
                    <div class="cv-category-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="cv-category-title">Consulta Plataforma</h3>
                    <p class="cv-category-description">¿Tienes dudas sobre el funcionamiento de la plataforma?</p>
                    <button class="cv-category-btn" data-target="plataforma">
                        <i class="fas fa-envelope"></i>
                        <span>Enviar Consulta</span>
                    </button>
                </div>
                
                <!-- Categoría 2: Consulta Comercio -->
                <div class="cv-contact-category" data-category="comercio">
                    <div class="cv-category-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 class="cv-category-title">Consulta Comercio</h3>
                    <p class="cv-category-description">¿Necesitas ayuda con tu tienda o ventas?</p>
                    <button class="cv-category-btn" data-target="comercio">
                        <i class="fas fa-envelope"></i>
                        <span>Enviar Consulta</span>
                    </button>
                </div>
                
                <!-- Categoría 3: Habla con Nosotros -->
                <div class="cv-contact-category" data-category="whatsapp">
                    <div class="cv-category-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3 class="cv-category-title">Habla con Nosotros</h3>
                    <p class="cv-category-description">¿Prefieres hablar directamente? Contacta por WhatsApp</p>
                    <button class="cv-category-btn cv-whatsapp-btn" data-target="whatsapp">
                        <i class="fab fa-whatsapp"></i>
                        <span>Abrir WhatsApp</span>
                    </button>
                </div>
                
            </div>
            
            <!-- Formulario de Contacto (Oculto por defecto) -->
            <div class="cv-contact-form-container" id="cv-contact-form-container" style="display: none;">
                <div class="cv-form-header">
                    <button class="cv-form-back" id="cv-form-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </button>
                    <h3 id="cv-form-title">Formulario de Contacto</h3>
                </div>
                
                <form id="cv-contact-form" class="cv-contact-form">
                    <input type="hidden" name="category" id="contact-category" value="">
                    
                    <div class="cv-form-group">
                        <label for="contact-name">
                            <i class="fas fa-user"></i>
                            Nombre completo *
                        </label>
                        <input type="text" id="contact-name" name="name" required>
                    </div>
                    
                    <div class="cv-form-group">
                        <label for="contact-email">
                            <i class="fas fa-envelope"></i>
                            Email *
                        </label>
                        <input type="email" id="contact-email" name="email" required>
                    </div>
                    
                    <div class="cv-form-group">
                        <label for="contact-phone">
                            <i class="fas fa-phone"></i>
                            Teléfono (opcional)
                        </label>
                        <input type="tel" id="contact-phone" name="phone">
                    </div>
                    
                    <div class="cv-form-group">
                        <label for="contact-subject">
                            <i class="fas fa-tag"></i>
                            Asunto *
                        </label>
                        <input type="text" id="contact-subject" name="subject" required>
                    </div>
                    
                    <div class="cv-form-group">
                        <label for="contact-message">
                            <i class="fas fa-comment-dots"></i>
                            Mensaje *
                        </label>
                        <textarea id="contact-message" name="message" rows="6" required></textarea>
                    </div>
                    
                    <div class="cv-form-actions">
                        <button type="submit" class="cv-form-submit">
                            <i class="fas fa-paper-plane"></i>
                            <span>Enviar Consulta</span>
                        </button>
                    </div>
                    
                    <div class="cv-form-response" id="cv-form-response"></div>
                </form>
            </div>
            
            <!-- WhatsApp Flotante -->
            <div class="cv-whatsapp-float" id="cv-whatsapp-float" style="display: none;">
                <a href="#" id="cv-whatsapp-link" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Procesar envío de formulario por AJAX
     */
    public function ajax_send_contact_form() {
        check_ajax_referer('cv_contact_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $subject = sanitize_text_field($_POST['subject']);
        $message = sanitize_textarea_field($_POST['message']);
        
        // Validar campos requeridos
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            wp_send_json_error(array(
                'message' => 'Por favor, completa todos los campos obligatorios.'
            ));
        }
        
        // Determinar destinatario según categoría
        $to = get_option('admin_email'); // Email por defecto
        
        if ($category === 'plataforma') {
            $category_name = 'Consulta Plataforma';
            // Puedes añadir un email específico si lo deseas
            // $to = 'plataforma@ciudadvirtual.app';
        } else if ($category === 'comercio') {
            $category_name = 'Consulta Comercio';
            // $to = 'comercio@ciudadvirtual.app';
        } else {
            $category_name = 'Consulta General';
        }
        
        // Preparar email
        $email_subject = '[Ciudad Virtual] ' . $category_name . ': ' . $subject;
        
        $email_body = "Nueva consulta desde la página de contacto\n\n";
        $email_body .= "Categoría: " . $category_name . "\n";
        $email_body .= "Nombre: " . $name . "\n";
        $email_body .= "Email: " . $email . "\n";
        
        if (!empty($phone)) {
            $email_body .= "Teléfono: " . $phone . "\n";
        }
        
        $email_body .= "\nAsunto: " . $subject . "\n\n";
        $email_body .= "Mensaje:\n" . $message . "\n\n";
        $email_body .= "---\n";
        $email_body .= "Enviado desde: " . home_url() . "\n";
        $email_body .= "Fecha: " . current_time('d/m/Y H:i') . "\n";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $name . ' <' . $email . '>'
        );
        
        // Enviar email
        $sent = wp_mail($to, $email_subject, $email_body, $headers);
        
        if ($sent) {
            // Log de contacto
            error_log('✅ CV Contact: Email enviado - Categoría: ' . $category . ' - De: ' . $email);
            
            // Disparar hook para estadísticas
            do_action('cv_contact_form_sent', $category, array(
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message
            ));
            
            wp_send_json_success(array(
                'message' => '¡Gracias por tu mensaje! Te responderemos lo antes posible.'
            ));
        } else {
            error_log('❌ CV Contact: Error enviando email - Categoría: ' . $category . ' - De: ' . $email);
            
            wp_send_json_error(array(
                'message' => 'Hubo un error al enviar tu mensaje. Por favor, inténtalo de nuevo más tarde.'
            ));
        }
    }
}

// Inicializar
new CV_Contact_Page();

