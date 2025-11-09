(function ($) {
    'use strict';

    var settings = window.cvProductConsult || {};
    var strings = $.extend({
        modalTitle: 'Consulta sobre este producto',
        contactCTA: '¿Prefieres nuestro formulario general?',
        contactBtn: 'Ir a contacto',
        success: '¡Consulta enviada correctamente!',
        genericError: 'Ha ocurrido un error. Inténtalo nuevamente.',
        emptyMessage: 'Por favor escribe tu consulta.',
        emptyGuest: 'Por favor completa tu nombre, email y teléfono.',
        sendLabel: 'Enviar consulta',
        sending: 'Enviando…',
        loginCTA: '¿Ya tienes cuenta? Inicia sesión y volverás a esta ventana para escribir tu consulta.',
        loginBtn: 'Iniciar sesión'
    }, settings.strings || {});

    var isLoggedIn = !!settings.isLoggedIn;
    var loginUrl = settings.loginUrl || '';
    var modalId = 'cv-consult-modal';
    var modalBuilt = false;
    var refParam = '';
    var storeOriginParam = '';
    var shouldAutoOpen = false;
    var currentProduct = {
        id: 0,
        title: '',
        vendorId: 0
    };

    (function parseQueryParams() {
        try {
            var params = new URLSearchParams(window.location.search || '');
            refParam = params.get('ref') || '';
            storeOriginParam = params.get('store_origin') || '';
            shouldAutoOpen = (params.get('cv_consult') === '1');
        } catch (error) {
            refParam = '';
            storeOriginParam = '';
            shouldAutoOpen = false;
        }
    })();

    function buildModal() {
        if (modalBuilt) {
            return;
        }

        var contactCTA = settings.contactUrl ? (
            '<div class="cv-consult-contact-cta">' +
            '   <p>' + strings.contactCTA + '</p>' +
            '   <a id="cv-consult-contact-link" class="cv-consult-contact-link" href="' + settings.contactUrl + '" target="_blank" rel="noopener noreferrer">' +
            strings.contactBtn +
            '   </a>' +
            '</div>'
        ) : '';

        var loginCTA = (!isLoggedIn && loginUrl) ? (
            '<div class="cv-consult-login-cta">' +
            '   <p>' + strings.loginCTA + '</p>' +
            '   <a id="cv-consult-login-link" class="cv-consult-login-link" href="#">' + strings.loginBtn + '</a>' +
            '</div>'
        ) : '';

        var modalTemplate = '' +
            '<div id="' + modalId + '" role="dialog" aria-modal="true" aria-labelledby="cv-consult-modal-title">' +
            '  <div class="cv-consult-modal__content">' +
            '    <div class="cv-consult-modal__header">' +
            '      <h3 id="cv-consult-modal-title">' + strings.modalTitle + '</h3>' +
            '      <button type="button" class="cv-consult-close" aria-label="Cerrar">×</button>' +
            '    </div>' +
            '    <p class="cv-consult-modal__product"></p>' +
            '    <div id="cv-consult-feedback" class="cv-consult-feedback" role="alert"></div>' +
            '    <form id="cv-consult-form" class="cv-consult-form">' +
            '      <div class="cv-consult-guest-fields">' +
            '        <div class="form-group">' +
            '          <label for="cv-consult-name">Nombre completo *</label>' +
            '          <input type="text" id="cv-consult-name" name="name" autocomplete="name">' +
            '        </div>' +
            '        <div class="form-group">' +
            '          <label for="cv-consult-email">Email *</label>' +
            '          <input type="email" id="cv-consult-email" name="email" autocomplete="email">' +
            '        </div>' +
            '        <div class="form-group">' +
            '          <label for="cv-consult-phone">Teléfono (WhatsApp) *</label>' +
            '          <input type="tel" id="cv-consult-phone" name="phone" autocomplete="tel">' +
            '        </div>' +
            '      </div>' +
            loginCTA +
            '      <div class="form-group">' +
            '        <label for="cv-consult-message-field">Tu consulta</label>' +
            '        <textarea id="cv-consult-message-field" name="message" required placeholder="Escribe aquí tu consulta..."></textarea>' +
            '      </div>' +
            '      <button type="submit" class="cv-consult-submit">' + strings.sendLabel + '</button>' +
            '    </form>' +
            contactCTA +
            '  </div>' +
            '</div>';

        $('body').append(modalTemplate);

        $('#' + modalId).on('click', function (evt) {
            if (evt.target === this) {
                closeModal();
            }
        });

        $(document).on('click', '.cv-consult-close', closeModal);
        $(document).on('keydown', function (evt) {
            if (evt.key === 'Escape') {
                closeModal();
            }
        });

        $(document).on('submit', '#cv-consult-form', handleSubmit);

        modalBuilt = true;
    }

    function openModal() {
        buildModal();
        clearMessage();
        toggleGuestFields();
        prefillUserData();
        updateModalHeader();
        updateContactLink();
        updateLoginLink();

        $('#' + modalId).addClass('active');
        $('body').addClass('cv-consult-modal-open');
    }

    function closeModal() {
        $('#' + modalId).removeClass('active');
        $('body').removeClass('cv-consult-modal-open');
    }

    function toggleGuestFields() {
        var $guestFields = $('.cv-consult-guest-fields');
        if (!$guestFields.length) {
            return;
        }
        if (isLoggedIn) {
            $guestFields.hide();
        } else {
            $guestFields.show();
        }
    }

    function prefillUserData() {
        if (!isLoggedIn) {
            $('#cv-consult-name').val('');
            $('#cv-consult-email').val('');
            $('#cv-consult-phone').val('');
            $('#cv-consult-message-field').val('');
            return;
        }

        if (settings.userName) {
            $('#cv-consult-name').val(settings.userName);
        }
        if (settings.userEmail) {
            $('#cv-consult-email').val(settings.userEmail);
        }
        if (settings.userPhone) {
            $('#cv-consult-phone').val(settings.userPhone);
        }
        $('#cv-consult-message-field').val('');
    }

    function updateModalHeader() {
        var $productTitle = $('.cv-consult-modal__product');
        if ($productTitle.length) {
            $productTitle.text(currentProduct.title || '');
        }
    }

    function clearMessage() {
        $('#cv-consult-feedback').removeClass('success error').text('').hide();
    }

    function showMessage(type, message) {
        var $feedback = $('#cv-consult-feedback');
        $feedback.removeClass('success error');
        if (type === 'success') {
            $feedback.addClass('success');
        } else {
            $feedback.addClass('error');
        }
        $feedback.text(message).show();
    }

    function handleSubmit(evt) {
        evt.preventDefault();

        var $form = $('#cv-consult-form');
        var $button = $form.find('button[type="submit"]');
        var originalLabel = $button.text();

        var payload = {
            action: 'cv_product_consultation_submit',
            nonce: settings.nonce,
            product_id: currentProduct.id,
            message: ($form.find('#cv-consult-message-field').val() || '').trim(),
            ref: refParam,
            store_origin: storeOriginParam
        };

        if (!payload.message) {
            showMessage('error', strings.emptyMessage);
            return;
        }

        if (!isLoggedIn) {
            payload.name = ($form.find('#cv-consult-name').val() || '').trim();
            payload.email = ($form.find('#cv-consult-email').val() || '').trim();
            payload.phone = ($form.find('#cv-consult-phone').val() || '').trim();

            if (!payload.name || !payload.email || !payload.phone) {
                showMessage('error', strings.emptyGuest);
                return;
            }
        } else {
            payload.phone = ($form.find('#cv-consult-phone').val() || '').trim();
        }

        $button.prop('disabled', true).text(strings.sending);

        $.ajax({
            url: settings.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: payload
        }).done(function (response) {
            if (response && response.success) {
                showMessage('success', response.data && response.data.message ? response.data.message : strings.success);
                $form[0].reset();
                setTimeout(function () {
                    closeModal();
                }, 2000);
            } else {
                var errorMessage = (response && response.data && response.data.message) ? response.data.message : strings.genericError;
                showMessage('error', errorMessage);
            }
        }).fail(function () {
            showMessage('error', strings.genericError);
        }).always(function () {
            $button.prop('disabled', false).text(originalLabel);
        });
    }

    function updateContactLink() {
        var $link = $('#cv-consult-contact-link');
        if (!$link.length || !settings.contactUrl) {
            return;
        }

        var params = {
            producto: currentProduct.title || '',
            product_id: currentProduct.id || '',
            enlace: window.location.href
        };

        try {
            var url = new URL(settings.contactUrl, window.location.origin);
            Object.keys(params).forEach(function (key) {
                if (params[key]) {
                    url.searchParams.set(key, params[key]);
                }
            });
            if (!isLoggedIn) {
                url.searchParams.set('nombre', $('#cv-consult-name').val() || '');
                url.searchParams.set('email', $('#cv-consult-email').val() || '');
                url.searchParams.set('telefono', $('#cv-consult-phone').val() || '');
            }
            $link.attr('href', url.toString());
        } catch (error) {
            $link.attr('href', settings.contactUrl);
        }
    }

    function updateLoginLink() {
        if (isLoggedIn || !loginUrl) {
            return;
        }

        var $link = $('#cv-consult-login-link');
        if (!$link.length) {
            return;
        }

        try {
            var targetUrl = new URL(window.location.href);
            targetUrl.searchParams.set('cv_consult', '1');

            var loginTarget = new URL(loginUrl, window.location.origin);
            loginTarget.searchParams.set('redirect_to', targetUrl.toString());

            $link.attr('href', loginTarget.toString());
        } catch (error) {
            $link.attr('href', loginUrl);
        }
    }

    $(document).on('click', '.cv-consultation-button', function (evt) {
        evt.preventDefault();

        currentProduct.id = parseInt($(this).data('product-id'), 10) || 0;
        currentProduct.title = $(this).data('product-title') || '';
        currentProduct.vendorId = parseInt($(this).data('vendor-id'), 10) || 0;

        openModal();
    });

    $(function () {
        if (!shouldAutoOpen) {
            return;
        }

        var $button = $('.cv-consultation-button').first();
        if (!$button.length) {
            return;
        }

        currentProduct.id = parseInt($button.data('product-id'), 10) || 0;
        currentProduct.title = $button.data('product-title') || '';
        currentProduct.vendorId = parseInt($button.data('vendor-id'), 10) || 0;

        openModal();

        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('cv_consult');
            window.history.replaceState({}, document.title, url.toString());
        } catch (error) {
            // Ignorar
        }
    });

})(jQuery);

