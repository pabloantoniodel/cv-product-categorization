/**
 * JavaScript para Página de Contacto
 * @package CV_Front
 * @since 2.5.9
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        const $categoriesContainer = $('.cv-contact-categories');
        const $formContainer = $('#cv-contact-form-container');
        const $form = $('#cv-contact-form');
        const $formTitle = $('#cv-form-title');
        const $formBack = $('#cv-form-back');
        const $categoryInput = $('#contact-category');
        const $formResponse = $('#cv-form-response');
        const $whatsappFloat = $('#cv-whatsapp-float');
        const $whatsappLink = $('#cv-whatsapp-link');
        
        // Títulos de formulario según categoría
        const formTitles = {
            'plataforma': 'Consulta sobre la Plataforma',
            'comercio': 'Consulta sobre tu Comercio'
        };
        
        /**
         * Mostrar formulario de contacto
         */
        function showContactForm(category) {
            console.log('📝 Mostrando formulario para:', category);
            
            // Ocultar categorías
            $categoriesContainer.fadeOut(300, function() {
                // Configurar formulario
                $categoryInput.val(category);
                $formTitle.text(formTitles[category] || 'Formulario de Contacto');
                
                // Limpiar formulario
                $form[0].reset();
                $formResponse.hide().removeClass('success error');
                
                // Mostrar formulario
                $formContainer.fadeIn(300);
                
                // Scroll suave al formulario
                $('html, body').animate({
                    scrollTop: $formContainer.offset().top - 100
                }, 500);
            });
        }
        
        /**
         * Ocultar formulario y mostrar categorías
         */
        function hideContactForm() {
            console.log('🔙 Volviendo a categorías');
            
            $formContainer.fadeOut(300, function() {
                $categoriesContainer.fadeIn(300);
                
                // Scroll suave a categorías
                $('html, body').animate({
                    scrollTop: $('.cv-contact-header').offset().top - 100
                }, 500);
            });
        }
        
        /**
         * Abrir WhatsApp
         */
        function openWhatsApp() {
            const phoneNumber = cvContactData.whatsapp_number;
            const message = cvContactData.whatsapp_message;
            const whatsappUrl = `https://wa.me/${phoneNumber.replace(/\+/g, '')}?text=${message}`;
            
            console.log('📱 Abriendo WhatsApp:', whatsappUrl);
            
            // Ocultar categorías
            $categoriesContainer.fadeOut(300);
            
            // Mostrar WhatsApp flotante
            $whatsappFloat.fadeIn(500);
            $whatsappLink.attr('href', whatsappUrl);
            
            // Abrir WhatsApp en nueva ventana
            window.open(whatsappUrl, '_blank');
            
            // Mensaje informativo
            setTimeout(function() {
                alert('Se ha abierto WhatsApp en una nueva ventana. Si no se abrió automáticamente, puedes usar el botón flotante que aparece en la esquina inferior derecha.');
            }, 500);
        }
        
        /**
         * Click en botones de categorías
         */
        $('.cv-category-btn').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this).data('target');
            console.log('🖱️ Click en categoría:', target);
            
            if (target === 'whatsapp') {
                openWhatsApp();
            } else {
                showContactForm(target);
            }
        });
        
        /**
         * Click en botón volver
         */
        $formBack.on('click', function(e) {
            e.preventDefault();
            hideContactForm();
            
            // Ocultar WhatsApp flotante si está visible
            $whatsappFloat.fadeOut(300);
        });
        
        /**
         * Envío de formulario
         */
        $form.on('submit', function(e) {
            e.preventDefault();
            
            console.log('📤 Enviando formulario...');
            
            const $submitBtn = $form.find('.cv-form-submit');
            const originalText = $submitBtn.find('span').text();
            
            // Deshabilitar botón y cambiar texto
            $submitBtn.prop('disabled', true);
            $submitBtn.find('span').text('Enviando...');
            $submitBtn.find('i').removeClass('fa-paper-plane').addClass('fa-spinner fa-spin');
            
            // Ocultar mensaje anterior
            $formResponse.hide().removeClass('success error');
            
            // Preparar datos
            const formData = {
                action: 'cv_send_contact_form',
                nonce: cvContactData.nonce,
                category: $('#contact-category').val(),
                name: $('#contact-name').val(),
                email: $('#contact-email').val(),
                phone: $('#contact-phone').val(),
                subject: $('#contact-subject').val(),
                message: $('#contact-message').val()
            };
            
            console.log('📊 Datos del formulario:', formData);
            
            // Enviar AJAX
            $.ajax({
                url: cvContactData.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('✅ Respuesta del servidor:', response);
                    
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        $formResponse
                            .addClass('success')
                            .html('<i class="fas fa-check-circle"></i> ' + response.data.message)
                            .fadeIn(300);
                        
                        // Limpiar formulario
                        $form[0].reset();
                        
                        // Scroll al mensaje
                        $('html, body').animate({
                            scrollTop: $formResponse.offset().top - 150
                        }, 500);
                        
                        // Ocultar mensaje después de 10 segundos
                        setTimeout(function() {
                            $formResponse.fadeOut(300, function() {
                                hideContactForm();
                            });
                        }, 10000);
                        
                    } else {
                        // Mostrar mensaje de error
                        $formResponse
                            .addClass('error')
                            .html('<i class="fas fa-exclamation-circle"></i> ' + response.data.message)
                            .fadeIn(300);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error en AJAX:', {xhr, status, error});
                    
                    $formResponse
                        .addClass('error')
                        .html('<i class="fas fa-exclamation-circle"></i> Error de conexión. Por favor, inténtalo de nuevo.')
                        .fadeIn(300);
                },
                complete: function() {
                    // Restaurar botón
                    $submitBtn.prop('disabled', false);
                    $submitBtn.find('span').text(originalText);
                    $submitBtn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-paper-plane');
                }
            });
        });
        
        /**
         * Click en WhatsApp flotante (cerrar)
         */
        $whatsappFloat.on('contextmenu', function(e) {
            e.preventDefault();
            $(this).fadeOut(300);
            $categoriesContainer.fadeIn(300);
        });
        
        console.log('✅ CV Contact Page: JavaScript cargado correctamente');
    });
    
})(jQuery);

