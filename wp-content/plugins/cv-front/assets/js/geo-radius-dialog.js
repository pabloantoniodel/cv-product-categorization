(function (window, document, $) {
    'use strict';

    var dialog;
    var closeButton;
    var applyButton;
    var resetButton;
    var addressInput;
    var rangeInput;
    var rangeCurrentLabel;
    var rangeMinLabel;
    var rangeMaxLabel;
    var unitInputs;

    var summaryRangeEl;
    var summaryAddressEl;
    var hiddenLatEl;
    var hiddenLngEl;
    var hiddenRangeEl;
    var hiddenAddressEl;
    var hiddenUnitEl;
    var openButtonEls = [];
    var formEl;
    var summaryCountEls = [];
    var mapButton;
    var deactivateButton;
    var hintTextEl;
    var locateButton;
    var useDeviceButton;
    var suggestionsList;
    var mapDialog;
    var mapCloseButton;
    var mapContainer;
    var mapFooterStats;
    var mapVersionEl;
    var mapUserMarker;
    var mapRadiusCircle;
    var mapStoreMarkers = [];
    var mapInstance;
    var mapZoomListenerBound = false;
    var leafletRequested = false;
    var leafletReady = false;
    var leafletCallbacks = [];
    var autoZoomState = null;

    var summaryDebounceTimer = null;
    var pendingSummaryRequest = null;
    var lastSummaryKey = null;
    var lastSummaryData = null;
    var lastDetailedData = null;
    var initialBootstrapPending = false;

    var SPAIN_BOUNDS = [
        [27.5, -18.5], // Suroeste (incluye Canarias)
        [44.5, 5.5]    // Noreste (incluye Baleares)
    ];

    var globalCallbacks = [];
    var dialogReadyDispatched = false;

    var MAP_DIALOG_VERSION = '20251111-geo-09';
    var MAP_DIALOG_LOADED_AT = new Date().toISOString();
    var DEFAULT_HINT_TEXT = 'Estos ajustes se guardan para próximas visitas. Al aplicar se actualizará el listado con el nuevo radio.';

    if (!window.__CV_RADIUS_DIALOG_LOGS) {
        window.__CV_RADIUS_DIALOG_LOGS = [];
    }
    window.__CV_RADIUS_DIALOG_LOGS.push({
        version: MAP_DIALOG_VERSION,
        loadedAt: MAP_DIALOG_LOADED_AT,
        notedAt: new Date().toISOString()
    });
    if (window.console && typeof window.console.info === 'function') {
        console.info('cvRadiusDialog map version', MAP_DIALOG_VERSION, 'loaded at', MAP_DIALOG_LOADED_AT);
    }

    var defaultConfig = {
        unitLabel: 'Km',
        unit: 'km',
        max: 1200,
        maxKm: 1200,
        maxM: 2000,
        defaultRange: 50,
        range: 50,
        rangeRaw: 50,
        lat: null,
        lng: null,
        address: '',
        hintMessage: null,
        storageKey: 'cv_geo_radius_dialog_settings',
        submitOnApply: false,
        enableReverseGeocode: true,
        allowForwardGeocode: true,
        selectors: {
            summaryCount: ['#cvRadiusDialogCount']
        },
        applyCallbacks: [],
        origin: 'generic',
        context: 'stores',
        rangeFieldMode: 'auto',
        deactivation: {
            enabled: false,
            label: 'Desactivar geolocalización',
            notice: null,
            callback: null,
            closeOnSuccess: true
        }
    };

    var currentConfig = $.extend(true, {}, defaultConfig);

    function formatVersionTimestamp(iso) {
        if (!iso) {
            return '';
        }
        try {
            var date = new Date(iso);
            if (!isNaN(date.getTime())) {
                return date.toLocaleString('es-ES', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
            }
        } catch (err) {
            // ignore
        }
        return iso;
    }

    function toRaw(rangeKm, unit) {
        var numeric = parseFloat(rangeKm);
        if (isNaN(numeric)) {
            return 0;
        }
        if (unit === 'm') {
            return Math.round(numeric * 1000);
        }
        return numeric;
    }

    function fromRaw(rawValue, unit) {
        var numeric = parseFloat(rawValue);
        if (isNaN(numeric)) {
            return null;
        }
        if (unit === 'm') {
            return numeric / 1000;
        }
        return numeric;
    }

    function ensureDialog() {
        if (!dialog) {
            dialog = document.getElementById('cvRadiusDialog');
            if (!dialog) {
                createDialogMarkup();
                dialog = document.getElementById('cvRadiusDialog');
            }
            cacheCommonElements();
            bindDialogEvents();
        }
    }

    function hasValidLocation() {
        return currentConfig &&
            currentConfig.lat !== null && currentConfig.lat !== undefined &&
            currentConfig.lng !== null && currentConfig.lng !== undefined;
    }

    function currentRadiusKm() {
        if (!currentConfig) {
            return 0;
        }
        if (currentConfig.unit === 'm') {
            return currentConfig.rangeRaw ? (currentConfig.rangeRaw / 1000) : 0;
        }
        return currentConfig.range || 0;
    }

    function formatCountLabel(count) {
        if (count === null || count === undefined || isNaN(count)) {
            return 'Comercios: —';
        }
        return 'Comercios: ' + parseInt(count, 10);
    }

    function refreshMapButtonState() {
        if (!mapButton) {
            return;
        }
        if (mapButton.dataset.loading === '1') {
            mapButton.disabled = true;
            return;
        }
        mapButton.disabled = !hasValidLocation();
    }

    function updateHintMessage(message) {
        if (!hintTextEl) {
            return;
        }
        var defaultText = hintTextEl.dataset.defaultText || DEFAULT_HINT_TEXT;
        if (message && typeof message === 'string') {
            hintTextEl.textContent = message;
        } else {
            hintTextEl.textContent = defaultText || DEFAULT_HINT_TEXT;
        }
    }

    function updateDeactivateButton() {
        if (!deactivateButton) {
            return;
        }
        var cfg = currentConfig && currentConfig.deactivation ? currentConfig.deactivation : {};
        var enabled = !!(cfg && cfg.enabled);
        var originAllows = currentConfig && currentConfig.origin === 'deactivate';
        var shouldShow = enabled && originAllows;

        if (shouldShow) {
            deactivateButton.classList.add('is-visible');
            deactivateButton.textContent = cfg.label || 'Desactivar geolocalización';
            if (cfg.ariaLabel) {
                deactivateButton.setAttribute('aria-label', cfg.ariaLabel);
            } else {
                deactivateButton.removeAttribute('aria-label');
            }
            if (cfg.tooltip) {
                deactivateButton.title = cfg.tooltip;
            } else {
                deactivateButton.removeAttribute('title');
            }
            deactivateButton.disabled = !!(deactivateButton.dataset.loading === '1');
        } else {
            deactivateButton.classList.remove('is-visible');
            deactivateButton.dataset.loading = '0';
            deactivateButton.disabled = false;
            deactivateButton.removeAttribute('aria-label');
            deactivateButton.removeAttribute('title');
        }

        if (dialog) {
            if (shouldShow) {
                dialog.classList.add('cv-radius-dialog--with-deactivate');
            } else {
                dialog.classList.remove('cv-radius-dialog--with-deactivate');
            }
        }
    }

    function clearSuggestions() {
        if (!suggestionsList) {
            return;
        }
        suggestionsList.innerHTML = '';
        suggestionsList.hidden = true;
        suggestionsList.dataset.state = 'empty';
    }

    function showSuggestionMessage(message, type) {
        if (!suggestionsList) {
            return;
        }
        suggestionsList.innerHTML = '';
        var item = document.createElement('li');
        item.className = 'cv-radius-dialog__suggestion cv-radius-dialog__suggestion--message' + (type ? ' cv-radius-dialog__suggestion--' + type : '');
        item.textContent = message;
        suggestionsList.appendChild(item);
        suggestionsList.hidden = false;
        suggestionsList.dataset.state = type || 'info';
    }

    function renderSuggestions(results) {
        if (!suggestionsList) {
            return;
        }
        suggestionsList.innerHTML = '';
        if (!Array.isArray(results) || !results.length) {
            showSuggestionMessage('No encontramos coincidencias en España para esa búsqueda.', 'warning');
            return;
        }
        results.forEach(function (item) {
            if (!item || !item.lat || !item.lon) {
                return;
            }
            var li = document.createElement('li');
            li.className = 'cv-radius-dialog__suggestion';
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'cv-radius-dialog__suggestion-btn';
            button.dataset.lat = item.lat;
            button.dataset.lng = item.lon;
            button.dataset.display = item.display_name || '';
            button.innerHTML = '<strong>' + (item.display_name || 'Ubicación sin nombre') + '</strong>'
                + (item.type ? '<span>' + item.type + '</span>' : '');
            li.appendChild(button);
            suggestionsList.appendChild(li);
        });
        suggestionsList.hidden = false;
        suggestionsList.dataset.state = 'list';
    }

    function setLocateLoading(isLoading) {
        if (!locateButton) {
            return;
        }
        if (isLoading) {
            if (!locateButton.dataset.originalText) {
                locateButton.dataset.originalText = locateButton.textContent;
            }
            locateButton.disabled = true;
            locateButton.dataset.loading = '1';
            locateButton.textContent = 'Buscando…';
        } else {
            locateButton.disabled = false;
            locateButton.dataset.loading = '0';
            if (locateButton.dataset.originalText) {
                locateButton.textContent = locateButton.dataset.originalText;
            }
        }
    }

    function setUseDeviceLoading(isLoading) {
        if (!useDeviceButton) {
            return;
        }
        if (isLoading) {
            if (!useDeviceButton.dataset.originalText) {
                useDeviceButton.dataset.originalText = useDeviceButton.textContent;
            }
            useDeviceButton.disabled = true;
            useDeviceButton.dataset.loading = '1';
            useDeviceButton.textContent = 'Obteniendo…';
        } else {
            useDeviceButton.disabled = false;
            useDeviceButton.dataset.loading = '0';
            if (useDeviceButton.dataset.originalText) {
                useDeviceButton.textContent = useDeviceButton.dataset.originalText;
            }
        }
    }

    function applyLocationSelection(lat, lng, displayName, options) {
        options = options || {};
        if (typeof lat !== 'number' || isNaN(lat) || typeof lng !== 'number' || isNaN(lng)) {
            return;
        }

        var prevLat = currentConfig.lat;
        var prevLng = currentConfig.lng;
        var prevRange = currentConfig.range;

        currentConfig.lat = lat;
        currentConfig.lng = lng;

        var locationChanged = (
            prevLat === undefined || prevLng === undefined ||
            Math.abs(parseFloat(prevLat) - lat) > 0.00001 ||
            Math.abs(parseFloat(prevLng) - lng) > 0.00001
        );

        if (hiddenLatEl) {
            hiddenLatEl.value = lat;
        }
        if (hiddenLngEl) {
            hiddenLngEl.value = lng;
        }

        if (displayName) {
            if (addressInput) {
                addressInput.value = displayName;
            }
            if (hiddenAddressEl) {
                hiddenAddressEl.value = displayName;
            }
            if (summaryAddressEl) {
                summaryAddressEl.textContent = 'Ubicación: ' + displayName;
            }
        } else {
            if (hiddenAddressEl) {
                hiddenAddressEl.value = '';
            }
            if (summaryAddressEl) {
                summaryAddressEl.textContent = 'Ubicación sin definir';
            }
        }

        refreshMapButtonState();
        saveToStorage({
            range: currentConfig.range,
            rangeRaw: currentConfig.rangeRaw,
            lat: lat,
            lng: lng,
            address: displayName || '',
            unit: currentConfig.unit,
            unitLabel: currentConfig.unitLabel,
            origin: currentConfig.origin
        }, currentConfig.storageKey);

        if (locationChanged || options.forceBootstrap) {
            initialBootstrapPending = true;
        }

        var fetchOptions = {};
        if (initialBootstrapPending) {
            fetchOptions.bootstrap = true;
            fetchOptions.minCount = 10;
        }

        scheduleSummaryFetch(true, fetchOptions);

        if (options.focusRange && rangeInput) {
            rangeInput.focus();
        }
    }

    function reverseGeocodeAndFill(lat, lng) {
        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=es&lat='
            + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&countrycodes=es';
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (json) {
                if (!json) {
                    return;
                }
                var display = json.display_name || '';
                applyLocationSelection(lat, lng, display);
            })
            .catch(function (error) {
                console.warn('cvRadiusDialog: reverse geocode manual falló', error);
            });
    }

    function onLocateClick() {
        if (!addressInput) {
            return;
        }
        var query = addressInput.value.trim();
        if (!query) {
            showSuggestionMessage('Escribe una dirección o ciudad dentro de España para buscar.', 'info');
            return;
        }
        if (locateButton && locateButton.dataset.loading === '1') {
            return;
        }
        setLocateLoading(true);
        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=6'
            + '&countrycodes=es&q=' + encodeURIComponent(query);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (results) {
                renderSuggestions(results);
            })
            .catch(function (error) {
                console.warn('cvRadiusDialog: búsqueda de dirección falló', error);
                showSuggestionMessage('Ocurrió un error al buscar. Intenta de nuevo.', 'error');
            })
            .finally(function () {
                setLocateLoading(false);
            });
    }

    function onSuggestionClick(event) {
        if (!event || !event.target || !event.target.closest) {
            return;
        }
        var button = event.target.closest('.cv-radius-dialog__suggestion-btn');
        if (!button) {
            return;
        }
        var lat = parseFloat(button.dataset.lat);
        var lng = parseFloat(button.dataset.lng);
        var display = button.dataset.display || '';
        if (isNaN(lat) || isNaN(lng)) {
            return;
        }
        applyLocationSelection(lat, lng, display, { focusRange: true });
        clearSuggestions();
    }

    function onUseDeviceClick() {
        if (!('geolocation' in navigator)) {
            showSuggestionMessage('Tu navegador no permite obtener la ubicación automática.', 'error');
            return;
        }
        if (useDeviceButton && useDeviceButton.dataset.loading === '1') {
            return;
        }
        clearSuggestions();
        setUseDeviceLoading(true);
        navigator.geolocation.getCurrentPosition(function (position) {
            setUseDeviceLoading(false);
            if (!position || !position.coords) {
                return;
            }
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            applyLocationSelection(lat, lng, 'Ubicación detectada');
            reverseGeocodeAndFill(lat, lng);
        }, function (error) {
            setUseDeviceLoading(false);
            var message = 'No se pudo obtener tu ubicación.';
            if (error && error.code === 1) {
                message = 'Permiso de ubicación denegado.';
            } else if (error && error.code === 3) {
                message = 'Tiempo de espera agotado obteniendo ubicación.';
            }
            showSuggestionMessage(message, 'error');
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 0 });
    }

    function setMapButtonLoading(isLoading) {
        if (!mapButton) {
            return;
        }
        if (isLoading) {
            if (!mapButton.dataset.originalText) {
                mapButton.dataset.originalText = mapButton.textContent;
            }
            mapButton.dataset.loading = '1';
            mapButton.textContent = 'Cargando mapa…';
            mapButton.disabled = true;
        } else {
            mapButton.dataset.loading = '0';
            if (mapButton.dataset.originalText) {
                mapButton.textContent = mapButton.dataset.originalText;
            }
            refreshMapButtonState();
        }
    }

    function applyServerRadius(radiusKm) {
        var numeric = parseFloat(radiusKm);
        if (isNaN(numeric) || numeric <= 0) {
            return;
        }
        if (Math.abs(numeric - currentRadiusKm()) < 0.01) {
            return;
        }

        currentConfig.unit = 'km';
        currentConfig.unitLabel = 'Km';
        setUnit('km');

        currentConfig.range = numeric;
        currentConfig.rangeRaw = numeric;

        updateRangeDisplay(numeric);

        if (rangeInput) {
            rangeInput.value = numeric;
        }

        updateSelectors({
            range: numeric,
            rangeRaw: numeric,
            lat: currentConfig.lat,
            lng: currentConfig.lng,
            address: currentConfig.address,
            unit: 'km'
        });
    }

    function updateSummaryCount(count) {
        if (summaryCountEls.length) {
            var label = formatCountLabel(count);
            summaryCountEls.forEach(function (el) {
                el.textContent = label;
            });
            if (window.console) {
                console.info('cvRadiusDialog: comercios dentro del radio →', label);
            }
        }
        refreshMapButtonState();
    }

    function buildSummaryKey() {
        if (!hasValidLocation()) {
            return null;
        }
        var lat = parseFloat(currentConfig.lat).toFixed(6);
        var lng = parseFloat(currentConfig.lng).toFixed(6);
        var rangeKey = currentRadiusKm().toFixed(3);
        var context = currentConfig.context || 'stores';
        return [lat, lng, rangeKey, context].join('|');
    }

    function scheduleSummaryFetch(forceNow, extraOptions) {
        extraOptions = extraOptions || {};
        refreshMapButtonState();

        if (!hasValidLocation()) {
            updateSummaryCount(null);
            return;
        }

        if (summaryDebounceTimer) {
            clearTimeout(summaryDebounceTimer);
        }

        var delay = forceNow ? 0 : 250;
        var key = buildSummaryKey();
        if (!forceNow && lastSummaryData && lastSummaryData.key === key) {
            updateSummaryCount(lastSummaryData.count);
        }

        var minCount = 0;
        if (typeof extraOptions.minCount === 'number') {
            minCount = Math.max(0, Math.floor(extraOptions.minCount));
        }
        if (initialBootstrapPending) {
            minCount = Math.max(minCount, 10);
        }
        var bootstrapFlag = (extraOptions.bootstrap !== undefined)
            ? (extraOptions.bootstrap ? 1 : 0)
            : (initialBootstrapPending ? 1 : 0);

        summaryDebounceTimer = window.setTimeout(function () {
            summaryDebounceTimer = null;
            fetchSummary({
                detailed: false,
                force: !!forceNow,
                minCount: minCount,
                bootstrap: bootstrapFlag
            }).catch(function (error) {
                if (window.console) {
                    console.warn('cvRadiusDialog: no se pudo actualizar el resumen', error);
                }
                updateSummaryCount(null);
            });
        }, delay);
    }

    function fetchSummary(options) {
        options = options || {};

        if (!window.CV_RADIUS_DIALOG_AJAX || !CV_RADIUS_DIALOG_AJAX.ajaxUrl) {
            return Promise.reject('ajax_config_missing');
        }

        if (!hasValidLocation()) {
            updateSummaryCount(null);
            return Promise.resolve({ count: 0, stores: [] });
        }

        var key = buildSummaryKey();

        if (!options.force && lastSummaryData && lastSummaryData.key === key) {
            if (!options.detailed || lastSummaryData.detailed) {
                updateSummaryCount(lastSummaryData.count);
                return Promise.resolve(lastSummaryData);
            }
        }

        if (pendingSummaryRequest && typeof pendingSummaryRequest.abort === 'function') {
            pendingSummaryRequest.abort();
        }

        var radiusKm = currentRadiusKm();

        var minCount = (typeof options.minCount === 'number')
            ? Math.max(0, Math.floor(options.minCount))
            : 0;
        var bootstrapFlag = options.bootstrap ? 1 : 0;

        var data = {
            action: 'cv_geo_radius_summary',
            nonce: CV_RADIUS_DIALOG_AJAX.nonce,
            lat: currentConfig.lat,
            lng: currentConfig.lng,
            radius: radiusKm,
            unit: 'km',
            context: currentConfig.context || 'stores',
            detailed: options.detailed ? '1' : '0',
            min_count: minCount,
            bootstrap: bootstrapFlag ? '1' : '0'
        };

        return new Promise(function (resolve, reject) {
            pendingSummaryRequest = $.ajax({
                url: CV_RADIUS_DIALOG_AJAX.ajaxUrl,
                method: 'POST',
                data: data,
                dataType: 'json'
            }).done(function (response) {
                if (!response || !response.success) {
                    if (window.console) {
                        console.warn('cvRadiusDialog: resumen inválido', response);
                    }
                    reject(response && response.data ? response.data : 'error');
                    return;
                }

                var payload = response.data || {};
                var result = {
                    key: key,
                    count: payload.count ? parseInt(payload.count, 10) : 0,
                    stores: Array.isArray(payload.stores) ? payload.stores : [],
                    detailed: !!options.detailed,
                    radius_used: payload.radius_used ? parseFloat(payload.radius_used) : radiusKm
                };

                if (window.console) {
                    console.info('cvRadiusDialog: resumen recibido', result);
                }

                if (options.detailed) {
                    lastDetailedData = result;
                } else {
                    lastSummaryData = result;
                    updateSummaryCount(result.count);
                }
                if (options.bootstrap) {
                    initialBootstrapPending = false;
                }
                if (!options.detailed && typeof result.radius_used === 'number') {
                    applyServerRadius(result.radius_used);
                }
                resolve(result);
            }).fail(function (jqXHR, textStatus) {
                if (textStatus === 'abort') {
                    return;
                }
                if (window.console) {
                    console.warn('cvRadiusDialog: petición fallida', {
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        error: textStatus,
                        response: jqXHR.responseText
                    });
                }
                reject(textStatus || 'error');
            }).always(function () {
                pendingSummaryRequest = null;
            });
        });
    }

    function ensureMapDialog() {
        if (mapDialog) {
            return;
        }

        var markup = ''
            + '<dialog id="cvRadiusMapDialog" class="cv-radius-map-dialog">'
            + '  <div class="cv-radius-map-dialog__content">'
            + '    <header class="cv-radius-map-dialog__header">'
            + '      <h2>Mapa de comercios cercanos</h2>'
            + '      <button type="button" id="cvRadiusMapClose" class="cv-radius-map-dialog__close" aria-label="Cerrar">×</button>'
            + '    </header>'
            + '    <div class="cv-radius-map-dialog__body">'
            + '      <div id="cvRadiusMapContainer" class="cv-radius-map"></div>'
            + '    </div>'
            + '    <footer class="cv-radius-map-dialog__footer">'
            + '      <div class="cv-radius-map-dialog__legend">'
            + '        <span><span class="cv-radius-map-dialog__legend-indicator cv-radius-map-dialog__legend-indicator--user"></span>Tú</span>'
            + '        <span><span class="cv-radius-map-dialog__legend-indicator cv-radius-map-dialog__legend-indicator--store"></span>Comercios</span>'
            + '      </div>'
            + '      <div class="cv-radius-map-dialog__meta">'
            + '        <div id="cvRadiusMapFooterStats" class="cv-radius-map-dialog__footer-note"></div>'
            + '        <div id="cvRadiusMapVersion" class="cv-radius-map-dialog__version"></div>'
            + '      </div>'
            + '    </footer>'
            + '  </div>'
            + '</dialog>';

        document.body.insertAdjacentHTML('beforeend', markup);
        mapDialog = document.getElementById('cvRadiusMapDialog');
        mapCloseButton = document.getElementById('cvRadiusMapClose');
        mapContainer = document.getElementById('cvRadiusMapContainer');
        mapFooterStats = document.getElementById('cvRadiusMapFooterStats');
        mapVersionEl = document.getElementById('cvRadiusMapVersion');
        if (mapVersionEl) {
            mapVersionEl.textContent = 'Versión ' + MAP_DIALOG_VERSION + ' · ' + formatVersionTimestamp(MAP_DIALOG_LOADED_AT);
        }
        if (mapDialog && !mapDialog.dataset.open) {
            mapDialog.dataset.open = '0';
        }

        if (mapCloseButton) {
            mapCloseButton.addEventListener('click', closeMapDialog);
        }

        if (mapDialog) {
            mapDialog.addEventListener('cancel', function (event) {
                event.preventDefault();
                closeMapDialog();
            });
            mapDialog.addEventListener('close', function () {
                mapDialog.dataset.open = '0';
                mapDialog.classList.remove('is-open');
                refreshMapButtonState();
            });
        }
    }

    function closeMapDialog() {
        if (!mapDialog) {
            return;
        }

        if (typeof mapDialog.close === 'function') {
            try {
                if (mapDialog.open) {
                    mapDialog.close();
                } else {
                    mapDialog.dispatchEvent(new Event('close'));
                }
                return;
            } catch (err) {
                // fall back to manual closing
            }
        }

        mapDialog.dataset.open = '0';
        mapDialog.classList.remove('is-open');
        refreshMapButtonState();
    }

    function loadLeaflet() {
        if (leafletReady && window.L) {
            return Promise.resolve();
        }

        return new Promise(function (resolve, reject) {
            leafletCallbacks.push({ resolve: resolve, reject: reject });

            if (leafletRequested) {
                return;
            }

            leafletRequested = true;

            var cssUrl = (window.CV_RADIUS_DIALOG_AJAX && CV_RADIUS_DIALOG_AJAX.leafletCss) || 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            var jsUrl = (window.CV_RADIUS_DIALOG_AJAX && CV_RADIUS_DIALOG_AJAX.leafletJs) || 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = cssUrl;
            document.head.appendChild(link);

            var script = document.createElement('script');
            script.src = jsUrl;
            script.onload = function () {
                leafletReady = true;
                var callbacks = leafletCallbacks.splice(0);
                callbacks.forEach(function (cb) {
                    cb.resolve();
                });
            };
            script.onerror = function (error) {
                var callbacks = leafletCallbacks.splice(0);
                callbacks.forEach(function (cb) {
                    cb.reject(error);
                });
            };

            document.head.appendChild(script);
        });
    }

    function openMapDialog() {
        if (!hasValidLocation()) {
            refreshMapButtonState();
            return;
        }

        ensureMapDialog();
        setMapButtonLoading(true);

        loadLeaflet().then(function () {
            if (mapDialog) {
                mapDialog.dataset.open = '1';
                mapDialog.classList.add('is-open');
                if (typeof mapDialog.showModal === 'function') {
                    try {
                        mapDialog.showModal();
                    } catch (err) {
                        // fallback to manual open
                    }
                }
            }
            return fetchSummary({ detailed: true, force: true, minCount: 5, bootstrap: 1 });
        }).then(function (data) {
            renderMap(data);
        }).catch(function (error) {
            console.warn('cvRadiusDialog: no se pudo cargar el mapa', error);
            renderMap({ count: 0, stores: [] });
        }).finally(function () {
            setMapButtonLoading(false);
        });
    }

    function toRadians(value) {
        return value * Math.PI / 180;
    }

    function distanceMeters(lat1, lng1, lat2, lng2) {
        var R = 6371000;
        var dLat = toRadians(lat2 - lat1);
        var dLng = toRadians(lng2 - lng1);
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function getStoreKey(store, fallbackIndex) {
        if (!store) {
            return 'idx:' + fallbackIndex;
        }
        if (store.id !== undefined && store.id !== null) {
            return 'id:' + store.id;
        }
        if (store.vendor_id !== undefined && store.vendor_id !== null) {
            return 'vendor:' + store.vendor_id;
        }
        if (store.store_id !== undefined && store.store_id !== null) {
            return 'store:' + store.store_id;
        }
        if (store.user_id !== undefined && store.user_id !== null) {
            return 'user:' + store.user_id;
        }
        if (store.slug) {
            return 'slug:' + store.slug;
        }
        if (store.url) {
            return 'url:' + store.url;
        }
        if (store.lat !== undefined && store.lng !== undefined) {
            return 'coord:' + store.lat + '|' + store.lng + '|' + fallbackIndex;
        }
        return 'idx:' + fallbackIndex;
    }

    function arraysEqual(a, b) {
        if (a === b) {
            return true;
        }
        if (!Array.isArray(a) || !Array.isArray(b)) {
            return false;
        }
        if (a.length !== b.length) {
            return false;
        }
        for (var i = 0; i < a.length; i++) {
            if (a[i] !== b[i]) {
                return false;
            }
        }
        return true;
    }

    function offsetCoordinates(lat, lng, eastMeters, northMeters) {
        var earthRadius = 6378137;
        var latRad = lat * Math.PI / 180;
        var dLat = northMeters / earthRadius;
        var dLng = eastMeters / (earthRadius * Math.cos(latRad));
        var newLat = lat + (dLat * 180 / Math.PI);
        var newLng = lng + (dLng * 180 / Math.PI);
        return [newLat, newLng];
    }

    function performAutoZoomStep() {
        if (!autoZoomState || !mapInstance) {
            return;
        }
        var maxZoom = (mapInstance.getMaxZoom && mapInstance.getMaxZoom()) || 19;
        var currentZoom = mapInstance.getZoom ? mapInstance.getZoom() : 10;
        var targetZoom = Math.min(maxZoom, currentZoom + 1.5);
        if (targetZoom <= currentZoom + 0.01) {
            autoZoomState = null;
            return;
        }
        mapInstance.flyTo([autoZoomState.lat, autoZoomState.lng], targetZoom, { duration: 0.35 });
    }

    function computeClusterThresholdMeters(summary) {
        summary = summary || {};
        var total = summary.count || (summary.stores ? summary.stores.length : 0) || 0;
        var zoom = mapInstance ? mapInstance.getZoom() : 10;
        var lat = hasValidLocation() ? parseFloat(currentConfig.lat) : 40.4168;
        if (isNaN(lat)) {
            lat = 40.4168;
        }
        var maxZoom = (mapInstance && typeof mapInstance.getMaxZoom === 'function') ? mapInstance.getMaxZoom() : 19;
        if (zoom >= maxZoom - 0.001) {
            return 0;
        }

        var metersPerPixel = 156543.03392 * Math.cos(lat * Math.PI / 180) / Math.pow(2, zoom);
        var basePixels = 32;
        if (zoom >= 16) {
            basePixels = 22;
        } else if (zoom >= 15) {
            basePixels = 26;
        } else if (zoom >= 14) {
            basePixels = 30;
        } else if (total > 80) {
            basePixels = 70;
        } else if (total > 40) {
            basePixels = 56;
        } else if (total > 20) {
            basePixels = 46;
        } else if (total > 10) {
            basePixels = 38;
        }
        var threshold = metersPerPixel * basePixels;
        threshold = Math.max(14, Math.min(threshold, 480));
        return threshold;
    }

    function groupStoresByProximity(stores, thresholdMeters) {
        var maxDistance = thresholdMeters || 120;
        var groups = [];
        (stores || []).forEach(function (store) {
            if (!store || store.lat === null || store.lng === null) {
                return;
            }
            var lat = parseFloat(store.lat);
            var lng = parseFloat(store.lng);
            if (isNaN(lat) || isNaN(lng)) {
                return;
            }

            var group = null;
            for (var i = 0; i < groups.length; i++) {
                var existing = groups[i];
                if (distanceMeters(existing.lat, existing.lng, lat, lng) <= maxDistance) {
                    group = existing;
                    break;
                }
            }

            if (!group) {
                group = {
                    stores: [],
                    count: 0,
                    sumLat: 0,
                    sumLng: 0,
                    lat: lat,
                    lng: lng
                };
                groups.push(group);
            }

            group.stores.push(store);
            group.count += 1;
            group.sumLat += lat;
            group.sumLng += lng;
            group.lat = group.sumLat / group.count;
            group.lng = group.sumLng / group.count;
        });
        return groups;
    }

    function spreadGroupMarkers(group, bounds, summaries) {
        var stores = (group && group.stores) ? group.stores.slice() : [];
        if (!stores.length) {
            return;
        }
        autoZoomState = null;

        var baseLat = group.lat;
        var baseLng = group.lng;
        var markersCreated = 0;
        var remaining = stores.length;
        var storeIndex = 0;
        var ring = 0;
        var maxPerRing = 8;

        while (remaining > 0) {
            var perRing = Math.min(maxPerRing, remaining);
            var angleStep = (2 * Math.PI) / perRing;
            var radiusMeters = 10 + (ring * 8);
            if (ring === 0 && perRing === 1) {
                radiusMeters = 0;
            }

            for (var j = 0; j < perRing; j++) {
                if (storeIndex >= stores.length) {
                    break;
                }
                var store = stores[storeIndex++];
                var angle = angleStep * j;
                var east = Math.sin(angle) * radiusMeters;
                var north = Math.cos(angle) * radiusMeters;
                var coords = [parseFloat(store.lat), parseFloat(store.lng)];
                if (isNaN(coords[0]) || isNaN(coords[1])) {
                    coords = [baseLat, baseLng];
                }
                if (radiusMeters > 0) {
                    coords = offsetCoordinates(coords[0], coords[1], east, north);
                }

                var lat = parseFloat(coords[0]);
                var lng = parseFloat(coords[1]);
                if (isNaN(lat) || isNaN(lng)) {
                    continue;
                }

                var marker = L.circleMarker([lat, lng], {
                    radius: 6,
                    weight: 1,
                    color: '#ffffff',
                    fillColor: '#6544ff',
                    fillOpacity: 0.9,
                    bubblingMouseEvents: false
                }).addTo(mapInstance);

                var popupContent = '<strong>' + (store.url ? '<a href="' + store.url + '" target="_blank" rel="noopener noreferrer">' + store.name + '</a>' : store.name) + '</strong>';
                if (store.distance_txt) {
                    popupContent += '<br>' + store.distance_txt;
                }
                marker.bindPopup(popupContent);

                mapStoreMarkers.push(marker);
                bounds.push([lat, lng]);
                if (summaries) {
                    summaries.push({
                        type: 'split',
                        count: 1,
                        lat: lat,
                        lng: lng,
                        originalLat: store.lat,
                        originalLng: store.lng
                    });
                }

                markersCreated++;
            }

            remaining -= perRing;
            ring++;
        }

        return markersCreated;
    }

    function handleAutoZoomPostRender(storeGroups) {
        if (!autoZoomState || !mapInstance) {
            return;
        }

        var maxZoom = (mapInstance.getMaxZoom && mapInstance.getMaxZoom()) || 19;
        var currentZoom = mapInstance.getZoom ? mapInstance.getZoom() : 10;
        var state = autoZoomState;
        var matchingGroup = null;

        storeGroups.some(function (group) {
            if (!group || !group.__keys) {
                return false;
            }
            if (arraysEqual(group.__keys, state.storeKeys)) {
                matchingGroup = group;
                return true;
            }
            return false;
        });

        if (!matchingGroup || matchingGroup.count < state.originalCount || matchingGroup.count <= 1) {
            autoZoomState = null;
            return;
        }

        if (currentZoom >= maxZoom - 0.001 || state.attempts >= 6) {
            autoZoomState = null;
            return;
        }

        state.attempts += 1;
        window.setTimeout(function () {
            performAutoZoomStep();
        }, 160);
    }

    function renderMap(summary, options) {
        summary = summary || { count: 0, stores: [] };
        options = options || {};
        var skipViewAdjust = !!options.skipViewAdjust;

        loadLeaflet().then(function () {
            if (!mapContainer) {
                return;
            }

            if (!mapInstance) {
                mapInstance = L.map(mapContainer);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(mapInstance);
                if (!mapZoomListenerBound) {
                    mapInstance.on('zoomend', function () {
                        var dataForZoom = lastDetailedData || lastSummaryData;
                        if (mapInstance && dataForZoom) {
                            renderMap(dataForZoom, { skipViewAdjust: true });
                        }
                    });
                    mapZoomListenerBound = true;
                }
            }

            if (mapRadiusCircle) {
                mapInstance.removeLayer(mapRadiusCircle);
                mapRadiusCircle = null;
            }
            if (mapUserMarker) {
                mapInstance.removeLayer(mapUserMarker);
                mapUserMarker = null;
            }
            mapStoreMarkers.forEach(function (marker) {
                mapInstance.removeLayer(marker);
            });
            mapStoreMarkers = [];

            var radiusKm = currentRadiusKm();
            var effectiveRadiusKm = radiusKm;
            if (summary.radius_used && !isNaN(summary.radius_used)) {
                effectiveRadiusKm = Math.min(Math.max(parseFloat(summary.radius_used), radiusKm), currentConfig.maxKm || 1200);
            }
            var radiusMeters = effectiveRadiusKm * 1000;
            var defaultCenter = [40.4168, -3.7038];
            var userLat = hasValidLocation() ? parseFloat(currentConfig.lat) : defaultCenter[0];
            var userLng = hasValidLocation() ? parseFloat(currentConfig.lng) : defaultCenter[1];

            var bounds = [];

            if (hasValidLocation()) {
                mapUserMarker = L.circleMarker([userLat, userLng], {
                    radius: 8,
                    weight: 2,
                    color: '#ffffff',
                    fillColor: '#ff4f81',
                    fillOpacity: 0.95
                }).addTo(mapInstance);
                mapUserMarker.bindPopup('Tu ubicación aproximada');

                mapRadiusCircle = L.circle([userLat, userLng], {
                    radius: radiusMeters,
                    color: '#6544ff',
                    weight: 1,
                    dashArray: '4 6',
                    fillColor: '#6544ff',
                    fillOpacity: 0.08
                }).addTo(mapInstance);

                bounds.push([userLat, userLng]);
            }

            var thresholdMeters = computeClusterThresholdMeters(summary);
            var storeGroups;
            if (!summary.stores || !summary.stores.length) {
                storeGroups = [];
                if (window.console && typeof window.console.info === 'function') {
                    window.console.info('cvRadiusDialog: resumen sin stores, solicitando detalle de nuevo');
                }
                return fetchSummary({ detailed: true, force: true, minCount: Math.max(5, summary.count || 0) })
                    .then(function (fallback) {
                        if (fallback && fallback.stores && fallback.stores.length) {
                            summary.stores = fallback.stores;
                            renderMap(summary, options);
                        } else {
                            if (window.console) {
                                console.warn('cvRadiusDialog: fallback tampoco devolvió stores');
                            }
                            updateSummaryCount(summary.count || 0);
                        }
                    }).catch(function (error) {
                        if (window.console) {
                            console.warn('cvRadiusDialog: error solicitando fallback detallado', error);
                        }
                    });
            } else {
                storeGroups = groupStoresByProximity(summary.stores, thresholdMeters);
            }
            var groupSummaries = [];
            if (window.console && typeof window.console.info === 'function') {
                window.console.info('cvRadiusDialog: render map → grupos formados', {
                    totalStores: (summary.stores || []).length,
                    groups: storeGroups.length,
                    thresholdMeters: thresholdMeters,
                    radiusKm: effectiveRadiusKm
                });
            }

            storeGroups.forEach(function (group) {
                if (!group || !group.count) {
                    return;
                }

                var groupKeys = group.stores.map(function (store, idx) {
                    return getStoreKey(store, idx);
                });
                group.__keys = groupKeys;

                if (group.count === 1) {
                    var store = group.stores[0];
                    var latSingle = parseFloat(store.lat);
                    var lngSingle = parseFloat(store.lng);
                    if (isNaN(latSingle) || isNaN(lngSingle)) {
                        return;
                    }
                    var singleMarker = L.circleMarker([latSingle, lngSingle], {
                        radius: 6,
                        weight: 1,
                        color: '#ffffff',
                        fillColor: '#6544ff',
                        fillOpacity: 0.9
                    }).addTo(mapInstance);

                    var popupContent = '<strong>' + (store.url ? '<a href="' + store.url + '" target="_blank" rel="noopener noreferrer">' + store.name + '</a>' : store.name) + '</strong>';
                    if (store.distance_txt) {
                        popupContent += '<br>' + store.distance_txt;
                    }
                    singleMarker.bindPopup(popupContent);
                    mapStoreMarkers.push(singleMarker);
                    bounds.push([latSingle, lngSingle]);
                    groupSummaries.push({
                        type: 'single',
                        count: 1,
                        lat: latSingle,
                        lng: lngSingle
                    });
                    return;
                }

                var currentZoomLevel = mapInstance ? mapInstance.getZoom() : 10;
                var maxZoomLevel = (mapInstance && typeof mapInstance.getMaxZoom === 'function') ? mapInstance.getMaxZoom() : 19;
                if (currentZoomLevel >= maxZoomLevel - 0.001) {
                    spreadGroupMarkers(group, bounds, groupSummaries);
                    return;
                }

                var clusterLatLng = [group.lat, group.lng];
        var clusterRadius = Math.min(34, Math.max(16, 14 + Math.log(group.count + 1) * 6));
                var clusterMarker = L.circleMarker(clusterLatLng, {
                    radius: clusterRadius,
                    weight: 2,
                    color: '#ffffff',
                    fillColor: '#6544ff',
                    fillOpacity: 0.92,
                    bubblingMouseEvents: false
                }).addTo(mapInstance);

                clusterMarker.bindTooltip(String(group.count), {
                    permanent: true,
                    direction: 'center',
                    className: 'cv-radius-cluster-tooltip'
                });

                var names = group.stores.slice(0, 6).map(function (store) {
                    var safeName = store.name || 'Comercio';
                    if (store.url) {
                        return '<a href="' + store.url + '" target="_blank" rel="noopener noreferrer">' + safeName + '</a>';
                    }
                    return safeName;
                });
                if (group.count > 6) {
                    names.push('…');
                }

                clusterMarker.bindPopup(
                    '<strong>' + group.count + ' comercios en esta zona</strong><br>' +
                    names.join('<br>')
                );

                var clusterBounds = L.latLngBounds([]);
                group.stores.forEach(function (store) {
                    var lat = parseFloat(store.lat);
                    var lng = parseFloat(store.lng);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        clusterBounds.extend([lat, lng]);
                        bounds.push([lat, lng]);
                    }
                });

                clusterMarker.on('click', function () {
                    var maxZoomLevel = (mapInstance && typeof mapInstance.getMaxZoom === 'function') ? mapInstance.getMaxZoom() : 19;
                    autoZoomState = {
                        storeKeys: groupKeys.slice(),
                        originalCount: group.count,
                        lat: clusterLatLng[0],
                        lng: clusterLatLng[1],
                        attempts: 0
                    };
                    if (clusterBounds.isValid()) {
                        mapInstance.fitBounds(clusterBounds, { maxZoom: maxZoomLevel, padding: [24, 24] });
                    }
                    window.setTimeout(function () {
                        performAutoZoomStep();
                    }, 140);
                });

                mapStoreMarkers.push(clusterMarker);
                groupSummaries.push({
                    type: 'cluster',
                    count: group.count,
                    lat: group.lat,
                    lng: group.lng
                });
            });

            if (window.console && typeof window.console.info === 'function') {
                window.console.info('cvRadiusDialog: render map → grupos agregados', groupSummaries);
            }

            handleAutoZoomPostRender(storeGroups);

            window.setTimeout(function () {
                mapInstance.invalidateSize();
                if (!skipViewAdjust) {
                    if (bounds.length > 1) {
                        mapInstance.fitBounds(bounds, { padding: [30, 30] });
                    } else if (hasValidLocation()) {
                        mapInstance.setView([userLat, userLng], effectiveRadiusKm > 20 ? 8 : 12);
                    } else {
                        mapInstance.setView(defaultCenter, 5);
                    }
                }
            }, 60);

            if (mapFooterStats) {
                var count = summary.count || 0;
                var countText = count === 1 ? '1 comercio' : count + ' comercios';
                var radiusText = effectiveRadiusKm >= 1
                    ? effectiveRadiusKm.toFixed(1) + ' km'
                    : Math.max(1, Math.round(effectiveRadiusKm * 1000)) + ' m';
                var isRadiusVeryLarge = effectiveRadiusKm >= 80;
                if (count === 0) {
                    mapFooterStats.textContent = 'No se encontraron comercios dentro del radio actual. Aumenta el radio para ampliar la búsqueda.';
                    if (!skipViewAdjust) {
                        mapInstance.setView([userLat, userLng], 6);
                    }
                } else {
                    mapFooterStats.textContent = 'Encontrados ' + countText + ' en un radio de ' + radiusText + '.';
                    if (effectiveRadiusKm > radiusKm + 0.05) {
                        mapFooterStats.textContent += ' (Se amplió temporalmente el radio para mostrar más resultados)';
                    } else if (count < 5 && !isRadiusVeryLarge) {
                        mapFooterStats.textContent += ' • Consejo: aumenta un poco el radio para ver más comercios.';
                    }
                }
            }
        }).catch(function (error) {
            console.warn('cvRadiusDialog: error al renderizar el mapa', error);
        });
    }

    function createDialogMarkup() {
        var markup = ''
            + '<dialog id="cvRadiusDialog" class="cv-radius-dialog">'
            + '  <form method="dialog" class="cv-radius-dialog__content">'
            + '    <header class="cv-radius-dialog__header">'
            + '      <h2>Ajustar búsqueda por distancia</h2>'
            + '      <button type="button" id="cvRadiusClose" aria-label="Cerrar">×</button>'
            + '    </header>'
            + '    <div class="cv-radius-dialog__body">'
            + '      <label for="cvRadiusAddress">Dirección base</label>'
            + '      <input type="text" id="cvRadiusAddress" placeholder="Inserta tu dirección…"/>'
            + '      <div class="cv-radius-dialog__address-actions">'
            + '        <button type="button" id="cvRadiusLocate" class="cv-radius-dialog__secondary">Ubicar</button>'
            + '        <button type="button" id="cvRadiusUseDevice" class="cv-radius-dialog__secondary">Mi ubicación</button>'
            + '      </div>'
            + '      <ul id="cvRadiusAddressResults" class="cv-radius-dialog__suggestions" hidden></ul>'
            + '      <div class="cv-radius-dialog__unit">'
            + '        <span>Unidad</span>'
            + '        <div class="cv-radius-dialog__unit-options">'
            + '          <label>'
            + '            <input type="radio" name="cvRadiusUnit" value="km" checked/>'
            + '            Kilómetros'
            + '          </label>'
            + '          <label>'
            + '            <input type="radio" name="cvRadiusUnit" value="m"/>'
            + '            Metros'
            + '          </label>'
            + '        </div>'
            + '      </div>'
            + '      <div class="cv-radius-dialog__slider">'
            + '        <label for="cvRadiusRange">Radio</label>'
            + '        <input type="range" id="cvRadiusRange" min="1" max="1200" step="0.1" value="50"/>'
            + '        <div class="cv-radius-dialog__range-labels">'
            + '          <span id="cvRadiusRangeMin">1 Km</span>'
            + '          <span id="cvRadiusRangeCurrent">50 Km</span>'
            + '          <span id="cvRadiusRangeMax">1200 Km</span>'
            + '        </div>'
            + '      </div>'
            + '      <p class="cv-radius-dialog__hint" data-default-hint="1"></p>'
            + '      <div class="cv-radius-dialog__result">'
            + '        <span id="cvRadiusDialogCount" class="cv-radius-summary-count" data-cv-radius-count="dialog">Comercios: —</span>'
            + '        <small class="cv-radius-dialog__result-hint">Se actualiza automáticamente según el radio seleccionado.</small>'
            + '      </div>'
            + '    </div>'
            + '    <footer class="cv-radius-dialog__footer">'
            + '      <button type="button" id="cvRadiusReset" class="cv-radius-dialog__reset">Restablecer</button>'
            + '      <button type="button" id="cvRadiusMap" class="cv-radius-dialog__map">Ver mapa</button>'
            + '      <button type="button" id="cvRadiusDeactivate" class="cv-radius-dialog__deactivate">Desactivar geolocalización</button>'
            + '      <button type="submit" id="cvRadiusApply" class="cv-radius-dialog__apply">Aplicar y cerrar</button>'
            + '    </footer>'
            + '  </form>'
            + '</dialog>';

        document.body.insertAdjacentHTML('beforeend', markup);
    }

    function cacheCommonElements() {
        closeButton = document.getElementById('cvRadiusClose');
        applyButton = document.getElementById('cvRadiusApply');
        resetButton = document.getElementById('cvRadiusReset');
        addressInput = document.getElementById('cvRadiusAddress');
        rangeInput = document.getElementById('cvRadiusRange');
        rangeCurrentLabel = document.getElementById('cvRadiusRangeCurrent');
        rangeMinLabel = document.getElementById('cvRadiusRangeMin');
        rangeMaxLabel = document.getElementById('cvRadiusRangeMax');
        unitInputs = document.querySelectorAll('input[name="cvRadiusUnit"]');
        mapButton = document.getElementById('cvRadiusMap');
        deactivateButton = document.getElementById('cvRadiusDeactivate');
        hintTextEl = dialog.querySelector('.cv-radius-dialog__hint');
        locateButton = document.getElementById('cvRadiusLocate');
        useDeviceButton = document.getElementById('cvRadiusUseDevice');
        suggestionsList = document.getElementById('cvRadiusAddressResults');

        if (hintTextEl) {
            var storedDefault = hintTextEl.dataset.defaultText;
            var currentText = hintTextEl.textContent ? hintTextEl.textContent.trim() : '';
            if (!storedDefault) {
                if (!currentText) {
                    currentText = DEFAULT_HINT_TEXT;
                    hintTextEl.textContent = currentText;
                }
                hintTextEl.dataset.defaultText = currentText;
            }
        }

        if (suggestionsList && !suggestionsList.dataset.state) {
            suggestionsList.dataset.state = 'empty';
            suggestionsList.hidden = true;
        }

        if (deactivateButton && !deactivateButton.dataset.loading) {
            deactivateButton.dataset.loading = '0';
        }
    }

    function bindDialogEvents() {
        if (!dialog || dialog.dataset.bound === '1') {
            return;
        }

        dialog.addEventListener('cancel', function (event) {
            event.preventDefault();
            dialog.close();
        });

        if (closeButton) {
            closeButton.addEventListener('click', function () {
                dialog.close();
            });
        }

        if (applyButton) {
            applyButton.addEventListener('click', onApply);
        }

        if (resetButton) {
            resetButton.addEventListener('click', onReset);
        }

        if (rangeInput) {
            rangeInput.addEventListener('input', onSliderInput);
            rangeInput.addEventListener('change', onSliderInput);
        }

        if (mapButton && !mapButton.dataset.bound) {
            mapButton.addEventListener('click', function () {
                if (mapButton.disabled) {
                    return;
                }
                openMapDialog();
            });
            mapButton.dataset.bound = '1';
        }

        if (deactivateButton && !deactivateButton.dataset.bound) {
            deactivateButton.addEventListener('click', onDeactivate);
            deactivateButton.dataset.bound = '1';
        }

        if (locateButton && !locateButton.dataset.bound) {
            locateButton.addEventListener('click', onLocateClick);
            locateButton.dataset.bound = '1';
        }

        if (useDeviceButton && !useDeviceButton.dataset.bound) {
            useDeviceButton.addEventListener('click', onUseDeviceClick);
            useDeviceButton.dataset.bound = '1';
        }

        if (suggestionsList && !suggestionsList.dataset.bound) {
            suggestionsList.addEventListener('click', onSuggestionClick);
            suggestionsList.dataset.bound = '1';
        }

        if (unitInputs && unitInputs.length) {
            unitInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    if (this.checked) {
                        setUnit(this.value);
                    }
                });
            });
        }

        if (addressInput && !addressInput.dataset.bound) {
            addressInput.addEventListener('input', function () {
                if (!this.value.trim()) {
                    clearSuggestions();
                }
            });
            addressInput.dataset.bound = '1';
        }

        dialog.dataset.bound = '1';
    }

    function onReset(event) {
        event.preventDefault();
        currentConfig.range = currentConfig.defaultRange;
        lastSummaryData = null;
        if (rangeInput) {
            setUnit(currentConfig.unit);
        } else {
            updateRangeDisplay(currentConfig.range);
        }
        if (addressInput) {
            addressInput.value = '';
        }
        if (hiddenLatEl) {
            hiddenLatEl.value = '';
        }
        if (hiddenLngEl) {
            hiddenLngEl.value = '';
        }
        if (hiddenAddressEl) {
            hiddenAddressEl.value = '';
        }
        if (summaryAddressEl) {
            summaryAddressEl.textContent = 'Ubicación sin definir';
        }
        updateSummaryCount(null);
        clearSuggestions();
        refreshMapButtonState();
        try {
            saveToStorage({
                range: currentConfig.range,
                lat: null,
                lng: null,
                address: '',
                unit: currentConfig.unit,
                unitLabel: currentConfig.unitLabel,
                origin: currentConfig.origin
            }, currentConfig.storageKey);
        } catch (err) {
            console.warn('cvRadiusDialog: no se pudo guardar reset', err);
        }
    }

    function onDeactivate(event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        if (!deactivateButton) {
            return;
        }
        if (deactivateButton.dataset.loading === '1') {
            return;
        }

        var deactivationConfig = currentConfig && currentConfig.deactivation ? currentConfig.deactivation : {};
        var callback = typeof deactivationConfig.callback === 'function' ? deactivationConfig.callback : null;
        var closeOnSuccess = deactivationConfig && deactivationConfig.closeOnSuccess !== false;

        if (!callback) {
            if (closeOnSuccess && dialog) {
                dialog.close();
            }
            return;
        }

        deactivateButton.dataset.loading = '1';
        deactivateButton.disabled = true;

        var finalize = function (shouldClose) {
            deactivateButton.dataset.loading = '0';
            deactivateButton.disabled = false;
            if (shouldClose && closeOnSuccess && dialog) {
                dialog.close();
            }
        };

        try {
            var result = callback(currentConfig, event);
            if (result && typeof result.then === 'function') {
                result.then(function () {
                    finalize(true);
                }).catch(function (error) {
                    console.warn('cvRadiusDialog: desactivación fallida', error);
                    finalize(false);
                });
            } else {
                finalize(true);
            }
        } catch (error) {
            console.warn('cvRadiusDialog: error en callback de desactivación', error);
            finalize(false);
        }
    }

    function onSliderInput() {
        if (!rangeInput) {
            return;
        }
        var displayValue = parseFloat(rangeInput.value);
        if (isNaN(displayValue)) {
            displayValue = currentConfig.unit === 'm'
                ? currentConfig.range * 1000
                : currentConfig.range;
        }

        if (currentConfig.unit === 'm') {
            if (displayValue < 0) {
                displayValue = 0;
            }
            if (displayValue > currentConfig.maxM) {
                displayValue = currentConfig.maxM;
            }
            currentConfig.rangeRaw = Math.round(displayValue);
            currentConfig.range = currentConfig.rangeRaw / 1000;
        } else {
            if (displayValue < 1) {
                displayValue = 1;
            }
            if (displayValue > currentConfig.maxKm) {
                displayValue = currentConfig.maxKm;
            }
            currentConfig.range = displayValue;
            currentConfig.rangeRaw = displayValue;
        }

        if (rangeInput) {
            rangeInput.value = displayValue;
        }
        updateRangeDisplay(currentConfig.range);
        initialBootstrapPending = false;
        scheduleSummaryFetch(false);
    }

    function formatRange(value) {
        var val = parseFloat(value);
        if (isNaN(val)) {
            val = currentConfig.defaultRange;
        }
        if (currentConfig.unit === 'm') {
            return Math.round(val * 1000) + ' m';
        }
        var formatted = val.toFixed(2);
        if (formatted.indexOf('.') !== -1) {
            formatted = formatted.replace(/\.?0+$/, '');
        }
        return formatted + ' ' + (currentConfig.unitLabel || 'Km');
    }

    function updateRangeDisplay(value) {
        var numericValue = parseFloat(value);
        if (isNaN(numericValue)) {
            numericValue = currentConfig.defaultRange;
        }
        currentConfig.range = numericValue;
        currentConfig.rangeRaw = currentConfig.unit === 'm'
            ? Math.round(numericValue * 1000)
            : numericValue;
        if (rangeCurrentLabel) {
            rangeCurrentLabel.textContent = formatRange(numericValue);
        }
        if (summaryRangeEl) {
            summaryRangeEl.textContent = formatRange(numericValue);
        }
        if (hiddenRangeEl) {
            var mode = currentConfig.rangeFieldMode || 'auto';
            var hiddenValue;
            if (mode === 'km') {
                hiddenValue = currentConfig.range;
            } else if (mode === 'raw') {
                hiddenValue = currentConfig.rangeRaw;
            } else {
                hiddenValue = currentConfig.unit === 'm' ? currentConfig.rangeRaw : currentConfig.range;
            }
            hiddenRangeEl.value = hiddenValue;
        }
        if (hiddenUnitEl) {
            hiddenUnitEl.value = currentConfig.unit;
        }
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (typeof days === 'number') {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/';
    }

    function setUnit(unit) {
        var normalized = (unit === 'm') ? 'm' : 'km';
        currentConfig.unit = normalized;
        currentConfig.unitLabel = normalized === 'm' ? 'm' : 'Km';
        if (hiddenUnitEl) {
            hiddenUnitEl.value = normalized;
        }

        if (unitInputs && unitInputs.length) {
            unitInputs.forEach(function (input) {
                input.checked = (input.value === normalized);
            });
        }

        var maxDisplay = normalized === 'm' ? currentConfig.maxM : currentConfig.maxKm;
        var minDisplay = normalized === 'm' ? 0 : 1;
        var displayValue = normalized === 'm' ? currentConfig.rangeRaw : currentConfig.range;

        if (normalized === 'm') {
            if (displayValue < minDisplay) {
                displayValue = minDisplay;
            }
            if (displayValue > maxDisplay) {
                displayValue = maxDisplay;
            }
            currentConfig.rangeRaw = Math.round(displayValue);
            currentConfig.range = currentConfig.rangeRaw / 1000;
        } else {
            if (displayValue < minDisplay) {
                displayValue = minDisplay;
            }
            if (displayValue > maxDisplay) {
                displayValue = maxDisplay;
            }
            currentConfig.range = displayValue;
            currentConfig.rangeRaw = displayValue;
        }

        if (rangeInput) {
            rangeInput.min = minDisplay;
            rangeInput.max = maxDisplay;
            rangeInput.step = normalized === 'km' ? '0.1' : '1';
            rangeInput.value = displayValue;
        }

        if (rangeMinLabel) {
            rangeMinLabel.textContent = normalized === 'm' ? '0 m' : '1 ' + currentConfig.unitLabel;
        }
        if (rangeMaxLabel) {
            rangeMaxLabel.textContent = normalized === 'm'
                ? Math.round(maxDisplay) + ' m'
                : Math.round(maxDisplay) + ' ' + currentConfig.unitLabel;
        }

        onSliderInput();
    }

    function saveToStorage(data, storageKey) {
        if (!storageKey) {
            return;
        }
        try {
            localStorage.setItem(storageKey, JSON.stringify(data));
        } catch (error) {
            console.warn('cvRadiusDialog: no se pudo guardar en storage', error);
        }
    }

    function loadFromStorage(storageKey) {
        if (!storageKey) {
            return null;
        }
        try {
            var raw = localStorage.getItem(storageKey);
            if (!raw) {
                return null;
            }
            return JSON.parse(raw);
        } catch (error) {
            console.warn('cvRadiusDialog: no se pudo leer storage', error);
            return null;
        }
    }

    function updateSelectors(data) {
        if (hiddenAddressEl) {
            hiddenAddressEl.value = data.address || '';
        }
        var rangeRaw = (data && typeof data.rangeRaw !== 'undefined')
            ? parseFloat(data.rangeRaw)
            : null;
        var rangeKm = parseFloat(data.range);
        if (isNaN(rangeKm)) {
            rangeKm = currentConfig.defaultRange;
        }
        if (isNaN(rangeRaw)) {
            rangeRaw = data.unit === 'm'
                ? Math.round(rangeKm * 1000)
                : rangeKm;
        }
        if (hiddenRangeEl) {
            var mode = currentConfig.rangeFieldMode || 'auto';
            var hiddenValue;
            if (mode === 'km') {
                hiddenValue = rangeKm;
            } else if (mode === 'raw') {
                hiddenValue = rangeRaw;
            } else {
                hiddenValue = (currentConfig.unit === 'm') ? rangeRaw : rangeKm;
            }
            hiddenRangeEl.value = hiddenValue;
        }
        currentConfig.rangeRaw = rangeRaw;
        currentConfig.range = rangeKm;
        if (hiddenLatEl && data.lat !== undefined) {
            hiddenLatEl.value = data.lat !== null ? data.lat : '';
        }
        if (hiddenLngEl && data.lng !== undefined) {
            hiddenLngEl.value = data.lng !== null ? data.lng : '';
        }
        if (data.lat !== undefined) {
            currentConfig.lat = data.lat;
        }
        if (data.lng !== undefined) {
            currentConfig.lng = data.lng;
        }
        currentConfig.address = data.address || '';
        if (summaryRangeEl) {
            summaryRangeEl.textContent = formatRange(data.range);
        }
        if (summaryAddressEl) {
            summaryAddressEl.textContent = data.address
                ? 'Ubicación: ' + data.address
                : 'Ubicación sin definir';
        }
        if (summaryCountEls.length) {
            updateSummaryCount(lastSummaryData && lastSummaryData.key === buildSummaryKey() ? lastSummaryData.count : null);
        } else {
            refreshMapButtonState();
        }
        if (hiddenUnitEl && data.unit) {
            hiddenUnitEl.value = data.unit;
        }
    }

    function collectValues() {
        var rangeVal = currentConfig.range;
        if (typeof rangeVal !== 'number' || isNaN(rangeVal)) {
            rangeVal = currentConfig.defaultRange;
        }
        currentConfig.range = rangeVal;
        currentConfig.rangeRaw = currentConfig.unit === 'm'
            ? Math.round(rangeVal * 1000)
            : rangeVal;
        var latVal = null;
        var lngVal = null;

        if (hiddenLatEl && hiddenLatEl.value !== '') {
            latVal = parseFloat(hiddenLatEl.value);
        } else if (currentConfig.lat !== null && currentConfig.lat !== undefined) {
            latVal = currentConfig.lat;
        }

        if (hiddenLngEl && hiddenLngEl.value !== '') {
            lngVal = parseFloat(hiddenLngEl.value);
        } else if (currentConfig.lng !== null && currentConfig.lng !== undefined) {
            lngVal = currentConfig.lng;
        }

        return {
            range: rangeVal,
            rangeRaw: currentConfig.rangeRaw,
            lat: latVal,
            lng: lngVal,
            address: addressInput ? addressInput.value.trim() : '',
            unit: currentConfig.unit,
            unitLabel: currentConfig.unitLabel,
            origin: currentConfig.origin
        };
    }

    function onApply(event) {
        event.preventDefault();

        var data = collectValues();
        var addressChanged = data.address && data.address !== currentConfig.address;
        var needsForward = currentConfig.allowForwardGeocode &&
            data.address &&
            (addressChanged || data.lat === null || data.lng === null);

        if (needsForward) {
            if (applyButton) {
                applyButton.disabled = true;
            }
            forwardGeocode(data.address)
                .then(function (coords) {
                    if (coords) {
                        data.lat = coords.lat;
                        data.lng = coords.lng;
                    }
                })
                .catch(function (error) {
                    console.warn('cvRadiusDialog: forward geocode falló', error);
                })
                .finally(function () {
                    if (applyButton) {
                        applyButton.disabled = false;
                    }
                    finalizeApply(data);
                });
        } else {
            finalizeApply(data);
        }
    }

    function finalizeApply(data) {
        currentConfig.lat = data.lat;
        currentConfig.lng = data.lng;
        currentConfig.address = data.address;
        currentConfig.range = parseFloat(data.range);
        if (isNaN(currentConfig.range) || currentConfig.range <= 0) {
            currentConfig.range = currentConfig.defaultRange;
        }
        currentConfig.rangeRaw = (data.rangeRaw !== undefined)
            ? parseFloat(data.rangeRaw)
            : toRaw(currentConfig.range, currentConfig.unit);
        if (isNaN(currentConfig.rangeRaw) || currentConfig.rangeRaw < 0) {
            currentConfig.rangeRaw = toRaw(currentConfig.range, currentConfig.unit);
        }

        updateSelectors({
            range: currentConfig.range,
            rangeRaw: currentConfig.rangeRaw,
            lat: data.lat,
            lng: data.lng,
            address: data.address,
            unit: currentConfig.unit
        });

        setCookie('cv_geo_unit', currentConfig.unit, 30);
        setCookie('cv_geo_radius', currentConfig.range, 30);
        setCookie('cv_geo_radius_wcfm', currentConfig.range, 30);
        if (currentConfig.unit === 'm') {
            setCookie('cv_geo_radius_raw', currentConfig.rangeRaw, 30);
        } else {
            setCookie('cv_geo_radius_raw', '', -1);
        }

        saveToStorage({
            range: currentConfig.range,
            rangeRaw: currentConfig.rangeRaw,
            lat: data.lat,
            lng: data.lng,
            address: data.address,
            unit: currentConfig.unit,
            unitLabel: currentConfig.unitLabel,
            origin: currentConfig.origin
        }, currentConfig.storageKey);

        initialBootstrapPending = false;
        scheduleSummaryFetch(true);

        if (Array.isArray(currentConfig.applyCallbacks)) {
            currentConfig.applyCallbacks.forEach(function (cb) {
                if (typeof cb === 'function') {
                    cb(data, currentConfig);
                }
            });
        }

        globalCallbacks.forEach(function (cb) {
            if (typeof cb === 'function') {
                cb(data, currentConfig);
            }
        });

        if (currentConfig.submitOnApply && formEl) {
            formEl.submit();
        }

        clearSuggestions();
        dialog.close();
    }

    function maybeFetchAddress(lat, lng) {
        if (!currentConfig.enableReverseGeocode) {
            return;
        }
        if (!lat || !lng) {
            return;
        }
        if (!addressInput || addressInput.value) {
            return;
        }

        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=es&lat='
            + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (json) {
                if (!json) {
                    return;
                }
                var display = json.display_name || '';
                if (addressInput) {
                    addressInput.value = display;
                }
                if (hiddenAddressEl) {
                    hiddenAddressEl.value = display;
                }
                if (summaryAddressEl) {
                    summaryAddressEl.textContent = display
                        ? 'Ubicación: ' + display
                        : 'Ubicación sin definir';
                }
                saveToStorage(collectValues(), currentConfig.storageKey);
                scheduleSummaryFetch(true);
            })
            .catch(function (error) {
                console.warn('cvRadiusDialog: reverse geocode falló', error);
            });
    }

    function forwardGeocode(address) {
        if (!address) {
            return Promise.resolve(null);
        }

        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&accept-language=es&q='
            + encodeURIComponent(address);

        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (json) {
                if (!Array.isArray(json) || !json.length) {
                    return null;
                }
                var item = json[0];
                return {
                    lat: item.lat ? parseFloat(item.lat) : null,
                    lng: item.lon ? parseFloat(item.lon) : null
                };
            });
    }

    function applyConfig(config) {
        ensureDialog();

        currentConfig = $.extend(true, {}, defaultConfig, config || {});
        currentConfig.applyCallbacks = Array.isArray(currentConfig.applyCallbacks)
            ? currentConfig.applyCallbacks
            : [];
        currentConfig.context = currentConfig.context || 'stores';
        currentConfig.rangeFieldMode = currentConfig.rangeFieldMode || 'auto';

        currentConfig.defaultRange = parseFloat(currentConfig.defaultRange);
        if (isNaN(currentConfig.defaultRange) || currentConfig.defaultRange <= 0) {
            currentConfig.defaultRange = 50;
        }

        currentConfig.maxKm = parseFloat(currentConfig.maxKm || currentConfig.max || 1200);
        if (isNaN(currentConfig.maxKm) || currentConfig.maxKm <= 0) {
            currentConfig.maxKm = 1200;
        }
        currentConfig.maxM = parseFloat(currentConfig.maxM || 2000);
        if (isNaN(currentConfig.maxM) || currentConfig.maxM <= 0) {
            currentConfig.maxM = 2000;
        }
        currentConfig.max = currentConfig.maxKm;

        currentConfig.unit = currentConfig.unit === 'm' ? 'm' : 'km';
        currentConfig.unitLabel = currentConfig.unit === 'm' ? 'm' : (currentConfig.unitLabel || 'Km');
        currentConfig.address = typeof currentConfig.address === 'string' ? currentConfig.address : '';

        var suppliedRaw = (currentConfig.rangeRaw !== undefined) ? parseFloat(currentConfig.rangeRaw) : null;
        if (isNaN(suppliedRaw)) {
            suppliedRaw = null;
        }

        currentConfig.range = parseFloat(currentConfig.range);
        if (!isNaN(suppliedRaw)) {
            if (currentConfig.unit === 'm') {
                currentConfig.range = suppliedRaw / 1000;
            } else {
                currentConfig.range = suppliedRaw;
            }
        }
        if (isNaN(currentConfig.range) || currentConfig.range <= 0) {
            currentConfig.range = currentConfig.defaultRange;
        }
        if (currentConfig.range > currentConfig.maxKm) {
            currentConfig.range = currentConfig.maxKm;
        }

        if (currentConfig.unit === 'm') {
            currentConfig.rangeRaw = !isNaN(suppliedRaw) ? Math.round(suppliedRaw) : Math.round(currentConfig.range * 1000);
            if (currentConfig.rangeRaw > currentConfig.maxM) {
                currentConfig.rangeRaw = currentConfig.maxM;
                currentConfig.range = currentConfig.rangeRaw / 1000;
            }
            if (currentConfig.rangeRaw < 0) {
                currentConfig.rangeRaw = 0;
                currentConfig.range = 0;
            }
        } else {
            currentConfig.rangeRaw = !isNaN(suppliedRaw) ? suppliedRaw : currentConfig.range;
            if (currentConfig.rangeRaw > currentConfig.maxKm) {
                currentConfig.rangeRaw = currentConfig.maxKm;
                currentConfig.range = currentConfig.rangeRaw;
            }
            if (currentConfig.rangeRaw < 1) {
                currentConfig.rangeRaw = 1;
                currentConfig.range = 1;
            }
        }

        var hasStoredRange = !isNaN(currentConfig.rangeRaw) && currentConfig.rangeRaw > 0;
        var hasLocation = currentConfig.lat !== null && currentConfig.lat !== undefined &&
            currentConfig.lng !== null && currentConfig.lng !== undefined;
        initialBootstrapPending = hasLocation && !!currentConfig.initialBootstrap;
        if (!initialBootstrapPending && hasLocation && !hasStoredRange) {
            initialBootstrapPending = true;
        }
        if (initialBootstrapPending) {
            currentConfig.unit = 'km';
            currentConfig.unitLabel = 'Km';
            if (currentConfig.range < 2) {
                currentConfig.range = 2;
                currentConfig.rangeRaw = 2;
            }
        }

        var selectors = currentConfig.selectors || {};
        hiddenRangeEl = selectors.hiddenRange ? document.querySelector(selectors.hiddenRange) : null;
        hiddenLatEl = selectors.hiddenLat ? document.querySelector(selectors.hiddenLat) : null;
        hiddenLngEl = selectors.hiddenLng ? document.querySelector(selectors.hiddenLng) : null;
        hiddenAddressEl = selectors.hiddenAddress ? document.querySelector(selectors.hiddenAddress) : null;
        hiddenUnitEl = selectors.hiddenUnit ? document.querySelector(selectors.hiddenUnit) : null;
        summaryRangeEl = selectors.summaryRange ? document.querySelector(selectors.summaryRange) : null;
        summaryCountEls = [];
        var summarySelectors = selectors.summaryCount;
        if (summarySelectors) {
            if (!Array.isArray(summarySelectors)) {
                summarySelectors = [summarySelectors];
            }
            summarySelectors.forEach(function (selector) {
                if (!selector) {
                    return;
                }
                var nodeList = document.querySelectorAll(selector);
                if (!nodeList || !nodeList.length) {
                    return;
                }
                Array.prototype.forEach.call(nodeList, function (node) {
                    if (node && summaryCountEls.indexOf(node) === -1) {
                        summaryCountEls.push(node);
                    }
                });
            });
        }
        summaryAddressEl = selectors.summaryAddress ? document.querySelector(selectors.summaryAddress) : null;
        formEl = selectors.form ? document.querySelector(selectors.form) : null;

        if (!currentConfig.deactivation) {
            currentConfig.deactivation = $.extend({}, defaultConfig.deactivation);
        }
        if (currentConfig.origin !== 'deactivate') {
            currentConfig.deactivation.enabled = false;
        }

        var hintMessage = currentConfig.hintMessage;
        if (!hintMessage && currentConfig.deactivation && currentConfig.deactivation.enabled && currentConfig.deactivation.notice && currentConfig.origin === 'deactivate') {
            hintMessage = currentConfig.deactivation.notice;
        }
        updateHintMessage(hintMessage);
        updateDeactivateButton();

        openButtonEls = [];
        if (selectors.openButton) {
            var foundButtons = document.querySelectorAll(selectors.openButton);
            if (foundButtons && foundButtons.length) {
                openButtonEls = Array.prototype.slice.call(foundButtons);
                openButtonEls.forEach(function (btn) {
                    if (!btn.dataset.cvRadiusBound) {
                        btn.addEventListener('click', function () {
                            manager.open();
                        });
                        btn.dataset.cvRadiusBound = '1';
                    }
                });
            }
        }

        setUnit(currentConfig.unit);
        updateRangeDisplay(currentConfig.range);

        if (addressInput) {
            addressInput.value = currentConfig.address || '';
        }

        updateSelectors(currentConfig);
        scheduleSummaryFetch(true);
    }

    var manager = {
        initFromConfig: function (config) {
            if (!config) {
                return;
            }

            var merged = $.extend(true, {}, defaultConfig, config);
            var stored = loadFromStorage(merged.storageKey);

            if (stored) {
                if (typeof stored.range === 'number') {
                    merged.range = stored.range;
                }
                if (typeof stored.rangeRaw === 'number') {
                    merged.rangeRaw = stored.rangeRaw;
                }
                if (stored.lat !== undefined && stored.lat !== null) {
                    merged.lat = stored.lat;
                }
                if (stored.lng !== undefined && stored.lng !== null) {
                    merged.lng = stored.lng;
                }
                if (typeof stored.address === 'string') {
                    merged.address = stored.address;
                }
                if (stored.unit) {
                    merged.unit = stored.unit;
                }
                if (stored.unitLabel) {
                    merged.unitLabel = stored.unitLabel;
                }
            }
            merged.applyCallbacks = merged.applyCallbacks || [];

            applyConfig(merged);
        },

        open: function (options) {
            ensureDialog();
            var merged = $.extend(true, {}, defaultConfig, currentConfig);

            if (options) {
                merged = $.extend(true, merged, options);
                if (options.hasOwnProperty('selectors')) {
                    merged.selectors = options.selectors || {};
                }
                if (options.hasOwnProperty('applyCallbacks')) {
                    merged.applyCallbacks = options.applyCallbacks || [];
                }
            }

            applyConfig(merged);
            maybeFetchAddress(currentConfig.lat, currentConfig.lng);
            dialog.showModal();
            scheduleSummaryFetch(true);
        },

        configure: function (options) {
            var merged = $.extend(true, {}, defaultConfig, currentConfig);

            if (options) {
                merged = $.extend(true, merged, options);
                if (options.hasOwnProperty('selectors')) {
                    merged.selectors = options.selectors || {};
                }
                if (options.hasOwnProperty('applyCallbacks')) {
                    merged.applyCallbacks = options.applyCallbacks || [];
                }
            }

            applyConfig(merged);
            scheduleSummaryFetch(true);
        },

        close: function () {
            if (dialog) {
                dialog.close();
            }
        },

        setLatLng: function (lat, lng) {
            ensureDialog();
            currentConfig.lat = lat;
            currentConfig.lng = lng;
            if (hiddenLatEl) {
                hiddenLatEl.value = lat !== null ? lat : '';
            }
            if (hiddenLngEl) {
                hiddenLngEl.value = lng !== null ? lng : '';
            }
            scheduleSummaryFetch(true);
        },

        setRange: function (range) {
            ensureDialog();
            currentConfig.range = range;
            if (rangeInput) {
                rangeInput.value = range;
            }
            updateRangeDisplay(range);
            scheduleSummaryFetch(false);
        },

        onApply: function (callback) {
            if (typeof callback === 'function') {
                globalCallbacks.push(callback);
            }
            return manager;
        }
    };

    function processQueuedConfigs() {
        var queue = window.CV_RADIUS_DIALOG_CONFIG;
        if (!queue) {
            dispatchReady();
            return;
        }

        if (!Array.isArray(queue)) {
            queue = [queue];
        }

        queue.forEach(function (cfg) {
            manager.initFromConfig(cfg);
        });

        window.CV_RADIUS_DIALOG_CONFIG = [];
        dispatchReady();
    }

    function dispatchReady() {
        if (dialogReadyDispatched) {
            return;
        }
        dialogReadyDispatched = true;
        var event = new CustomEvent('cvRadiusDialogReady', { detail: manager });
        window.dispatchEvent(event);
    }

    window.cvRadiusDialogManager = manager;

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(processQueuedConfigs, 0);
    } else {
        document.addEventListener('DOMContentLoaded', processQueuedConfigs);
    }
})(window, document, jQuery);

