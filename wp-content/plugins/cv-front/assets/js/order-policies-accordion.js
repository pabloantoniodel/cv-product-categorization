/**
 * CV Order Policies Accordion JavaScript
 * Funcionalidad del acordeón de políticas
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		
		// Manejar el click en los headers del acordeón
		$('.cv-accordion-header').on('click', function(e) {
			e.preventDefault();
			
			var $item = $(this).closest('.cv-accordion-item');
			var $accordion = $item.closest('.cv-policies-accordion');
			var isActive = $item.hasClass('active');
			
			// Si está activo, cerrarlo
			if (isActive) {
				$item.removeClass('active');
			} else {
				// Cerrar todos los items
				$accordion.find('.cv-accordion-item').removeClass('active');
				// Abrir el item clickeado
				$item.addClass('active');
			}
		});
		
		// Ocultar la tabla original de políticas usando JavaScript como respaldo
		setTimeout(function() {
			// Ocultar tablas de políticas
			$('table[width="100%"]').each(function() {
				var $table = $(this);
				var hasPolicyHeaders = false;
				
				$table.find('th').each(function() {
					var text = $(this).text().toLowerCase();
					if (text.indexOf('shipping policy') !== -1 || 
						text.indexOf('refund policy') !== -1 || 
						text.indexOf('cancellation') !== -1 ||
						text.indexOf('customer support') !== -1 ||
						text.indexOf('política') !== -1) {
						hasPolicyHeaders = true;
						return false;
					}
				});
				
				if (hasPolicyHeaders) {
					$table.hide();
				}
			});
			
			// Ocultar títulos de políticas (h2 que contengan "Policies" o "Políticas")
			$('h2').each(function() {
				var text = $(this).text().toLowerCase();
				if (text.indexOf('policies') !== -1 || text.indexOf('políticas') !== -1) {
					// Verificar que no sea nuestro título del acordeón
					if (!$(this).hasClass('cv-policies-store-title')) {
						$(this).hide();
					}
				}
			});
		}, 100);
		
	});
	
})(jQuery);

