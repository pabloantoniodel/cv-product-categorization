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
        tabGuest: 'Sin cuenta',
        tabLogin: 'Ya soy usuario',
        loginTitle: 'Inicia sesión para continuar',
        loginUserLabel: 'Usuario o email',
        loginPassLabel: 'Contraseña',
        loginRemember: 'Recordarme',
        loginSubmit: 'Iniciar sesión',
        loginProcessing: 'Accediendo…',
        loginError: 'No pudimos iniciar sesión con esos datos.',
        loginSuccess: 'Sesión iniciada, ya puedes escribir tu consulta.'
    }, settings.strings || {});

    var isLoggedIn = !!settings.isLoggedIn;
    var modalId = 'cv-consult-modal';
    var modalBuilt = false;
    var refParam = '';
    var storeOriginParam = '';
    var shouldAutoOpen = false;
    var activeTab = 'guest';
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

        var hasTabs = !isLoggedIn;
        var tabsHtml = hasTabs ? (
            '<div class="cv-consult-tabs">' +
            '   <button type="button" class="cv-consult-tab active" data-tab="guest">' + strings.tabGuest + '</button>' +
            '   <button type="button" class="cv-consult-tab" data-tab="login">' + strings.tabLogin + '</button>' +
            '</div>'
        ) : '';

        var contactCTA = settings.contactUrl ? (
            '<div class="cv-consult-contact-cta">' +
            '   <p>' + strings.contactCTA + '</p>' +
            '   <a id="cv-consult-contact-link" class="cv-consult-contact-link" href="' + settings.contactUrl + '" target="_blank" rel="noopener noreferrer">' +
            strings.contactBtn +
            '   </a>' +
            '</div>'
        ) : '';

        var loginPanel = !isLoggedIn ? (
            '<div id="cv-consult-login-panel" class="cv-consult-login-panel" style="display:none;">' +
            '   <h4>' + strings.loginTitle + '</h4>' +
            '   <div id="cv-consult-login-feedback" class="cv-consult-feedback" role="alert" style="display:none;"></div>' +
            '   <form id="cv-consult-login-form" class="cv-consult-login-form">' +
            '       <div class="form-group">' +
            '           <label for="cv-consult-login-user">' + strings.loginUserLabel + '</label>' +
            '           <input type="text" id="cv-consult-login-user" name="login" autocomplete="username">' +
            '       </div>' +
            '       <div class="form-group">' +
            '           <label for="cv-consult-login-pass">' + strings.loginPassLabel + '</label>' +
            '           <input type="password" id="cv-consult-login-pass" name="password" autocomplete="current-password">' +
            '       </div>' +
            '       <label class="cv-consult-login-remember">' +
            '           <input type="checkbox" id="cv-consult-login-remember" name="remember">' +
            '           <span>' + strings.loginRemember + '</span>' +
            '       </label>' +
            '       <button type="submit" class="cv-consult-login-submit">' + strings.loginSubmit + '</button>' +
            '   </form>' +
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
            tabsHtml +
            '    <div id="cv-consult-feedback" class="cv-consult-feedback" role="alert"></div>' +
            '    <div id="cv-consult-panel-guest" class="cv-consult-panel">' +
            '      <form id="cv-consult-form" class="cv-consult-form">' +
            '        <div class="cv-consult-guest-fields">' +
            '          <div class="form-group">' +
            '            <label for="cv-consult-name">Nombre completo *</label>' +
            '            <input type="text" id="cv-consult-name" name="name" autocomplete="name">' +
            '          </div>' +
            '          <div class="form-group">' +
            '            <label for="cv-consult-email">Email *</label>' +
            '            <input type="email" id="cv-consult-email" name="email" autocomplete="email">' +
            '          </div>' +
            '          <div class="form-group">' +
            '            <label for="cv-consult-phone">Teléfono (WhatsApp) *</label>' +
            '            <input type="tel" id="cv-consult-phone" name="phone" autocomplete="tel">' +
            '          </div>' +
            '        </div>' +
            '        <div class="form-group">' +
            '          <label for="cv-consult-message-field">Tu consulta</label>' +
            '          <textarea id="cv-consult-message-field" name="message" required placeholder="Escribe aquí tu consulta..."></textarea>' +
            '        </div>' +
            '        <button type="submit" class="cv-consult-submit">' + strings.sendLabel + '</button>' +
            '      </form>' +
            '    </div>' +
            loginPanel +
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
        $(document).on('click', '.cv-consult-tab', handleTabClick);
        $(document).on('submit', '#cv-consult-login-form', handleLoginSubmit);

        modalBuilt = true;
    }

    function openModal() {
        buildModal();
        clearMessage();
        toggleTabs();
        toggleGuestFields();
        prefillUserData();
        updateModalHeader();
        updateContactLink();
        setActiveTab(isLoggedIn ? 'guest' : activeTab);

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

    function toggleTabs() {
        var $tabs = $('.cv-consult-tabs');
        if (!$tabs.length) {
            return;
        }

        if (isLoggedIn) {
            $tabs.hide();
            $('#cv-consult-login-panel').hide();
        } else {
            $tabs.show();
        }
    }

    function handleTabClick(evt) {
        evt.preventDefault();

        var target = $(this).data('tab');
        if (!target || (target === 'login' && isLoggedIn)) {
            return;
        }

        setActiveTab(target);
    }

    function setActiveTab(tab) {
        activeTab = tab === 'login' && !isLoggedIn ? 'login' : 'guest';

        $('.cv-consult-tab').removeClass('active');
        $('.cv-consult-tab[data-tab="' + activeTab + '"]').addClass('active');

        if (activeTab === 'login' && !isLoggedIn) {
            $('#cv-consult-panel-guest').hide();
            $('#cv-consult-login-panel').show();
            $('#cv-consult-login-user').trigger('focus');
        } else {
            $('#cv-consult-panel-guest').show();
            $('#cv-consult-login-panel').hide();
        }
    }

    function handleLoginSubmit(evt) {
        evt.preventDefault();

        if (isLoggedIn) {
            return;
        }

        var $form = $('#cv-consult-login-form');
        if (!$form.length) {
            return;
        }

        var payload = {
            action: 'cv_product_consultation_login',
            nonce: settings.loginNonce,
            login: ($form.find('#cv-consult-login-user').val() || '').trim(),
            password: $form.find('#cv-consult-login-pass').val() || '',
            remember: $form.find('#cv-consult-login-remember').is(':checked') ? 1 : 0,
            return_url: getConsultReturnUrl()
        };

        if (!payload.login || !payload.password) {
            showLoginFeedback('error', strings.loginError);
            return;
        }

        var $button = $form.find('button[type="submit"]');
        var originalLabel = $button.text();
        $button.prop('disabled', true).text(strings.loginProcessing);

        $.ajax({
            url: settings.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: payload
        }).done(function (response) {
            if (response && response.success && response.data) {
                isLoggedIn = true;
                settings.isLoggedIn = true;
                settings.nonce = response.data.nonce || settings.nonce;
                settings.loginNonce = response.data.login_nonce || settings.loginNonce;
                settings.userName = response.data.user_name || '';
                settings.userEmail = response.data.user_email || '';
                settings.userPhone = response.data.user_phone || '';

                var loginForm = $form.get(0);
                if (loginForm) {
                    loginForm.reset();
                }

                toggleTabs();
                toggleGuestFields();
                prefillUserData();
                updateContactLink();

                setActiveTab('guest');
                showMessage('success', strings.loginSuccess);

                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('cv_consult', '1');
                    window.history.replaceState({}, document.title, url.toString());
                } catch (error) {
                    // Ignorar
                }
            } else {
                var errMsg = (response && response.data && response.data.message) ? response.data.message : strings.loginError;
                showLoginFeedback('error', errMsg);
            }
        }).fail(function () {
            showLoginFeedback('error', strings.loginError);
        }).always(function () {
            $button.prop('disabled', false).text(originalLabel);
        });
    }

    function getConsultReturnUrl() {
        try {
            var targetUrl = new URL(window.location.href);
            targetUrl.searchParams.set('cv_consult', '1');
            return targetUrl.toString();
        } catch (error) {
            return window.location.href;
        }
    }

    function showLoginFeedback(type, message) {
        var $feedback = $('#cv-consult-login-feedback');
        if (!$feedback.length) {
            showMessage(type, message);
            return;
        }

        $feedback.removeClass('success error');
        if (type === 'success') {
            $feedback.addClass('success');
        } else {
            $feedback.addClass('error');
        }
        $feedback.text(message).show();

        if (type === 'error') {
            $('#cv-consult-login-pass').trigger('focus');
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
