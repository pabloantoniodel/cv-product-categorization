/**
 * Shopper Modern - Custom JavaScript
 * 
 * @package Shopper Modern
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Smooth scroll para enlaces ancla
         */
        $('a[href*="#"]:not([href="#"])').on('click', function(e) {
            if (location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') && 
                location.hostname === this.hostname) {
                
                const target = $(this.hash);
                const $target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
                
                if ($target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 600);
                }
            }
        });
        
        /**
         * Añadir clase al scroll (para header sticky con efectos)
         */
        $(window).on('scroll', function() {
            const scroll = $(window).scrollTop();
            
            if (scroll >= 50) {
                $('.site-header').addClass('scrolled');
            } else {
                $('.site-header').removeClass('scrolled');
            }
        });
        
        /**
         * Lazy loading mejorado para imágenes (opcional)
         */
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img.lazy').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        /**
         * Animación de entrada para elementos (fade in)
         */
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);
        
        // Observar elementos con clase 'animate-on-scroll'
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
        
        /**
         * Mejorar UX de cantidad en productos WooCommerce
         */
        $('.quantity').each(function() {
            const $qty = $(this).find('.qty');
            const min = parseFloat($qty.attr('min')) || 0;
            const max = parseFloat($qty.attr('max')) || 999;
            
            // Botón menos
            $(this).prepend('<button type="button" class="qty-btn qty-minus">−</button>');
            
            // Botón más
            $(this).append('<button type="button" class="qty-btn qty-plus">+</button>');
        });
        
        $(document).on('click', '.qty-minus', function() {
            const $input = $(this).siblings('.qty');
            const currentVal = parseFloat($input.val()) || 0;
            const min = parseFloat($input.attr('min')) || 0;
            
            if (currentVal > min) {
                $input.val(currentVal - 1).trigger('change');
            }
        });
        
        $(document).on('click', '.qty-plus', function() {
            const $input = $(this).siblings('.qty');
            const currentVal = parseFloat($input.val()) || 0;
            const max = parseFloat($input.attr('max')) || 999;
            
            if (currentVal < max) {
                $input.val(currentVal + 1).trigger('change');
            }
        });
        
        /**
         * Mejorar búsqueda con loading state
         */
        $('.search-form').on('submit', function() {
            const $btn = $(this).find('button[type="submit"]');
            $btn.addClass('loading').prop('disabled', true);
        });
        
        /**
         * Añadir clase a productos cuando se añaden al carrito
         */
        $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
            button.addClass('added').text('✓ Añadido');
            
            setTimeout(function() {
                button.removeClass('added');
            }, 2000);
        });
        
        /**
         * Console log para debug (remover en producción)
         */
        console.log('🎨 Shopper Modern theme loaded successfully!');
        
    });
    
})(jQuery);



