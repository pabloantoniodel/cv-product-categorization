(function ($, window) {
    'use strict';

    const STORAGE_KEY = 'cvGeoPrefs';
    let isApplying = false;

    function readPrefs() {
        try {
            const raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return null;
            }
            const data = JSON.parse(raw);
            if (data && typeof data === 'object') {
                return data;
            }
        } catch (error) {
            console.warn('[CV Geo Sync] Error parsing stored preferences', error);
        }
        return null;
    }

    function writePrefs(prefs) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
        } catch (error) {
            console.warn('[CV Geo Sync] Error saving preferences', error);
        }
    }

    function floatsEqual(a, b) {
        if (a == null && b == null) {
            return true;
        }
        if (a == null || b == null) {
            return false;
        }
        return Math.abs(parseFloat(a) - parseFloat(b)) < 0.000001;
    }

    function stringsEqual(a, b) {
        return (a || '').trim() === (b || '').trim();
    }

    function deepEqual(a, b) {
        if (!a && !b) {
            return true;
        }
        if (!a || !b) {
            return false;
        }
        return floatsEqual(a.radius, b.radius) &&
            floatsEqual(a.lat, b.lat) &&
            floatsEqual(a.lng, b.lng) &&
            stringsEqual(a.address, b.address);
    }

    function collectPrefs($form) {
        if (!$form || !$form.length) {
            return null;
        }
        const $range = $form.find('input[id$="_radius_range"]');
        const $lat = $form.find('input[id$="_radius_lat"]');
        const $lng = $form.find('input[id$="_radius_lng"]');
        const $addr = $form.find('input.wcfmmp-radius-addr, input[name="radius_addr"]');

        const radius = $range.length ? parseFloat($range.val()) : null;
        const lat = $lat.length ? parseFloat($lat.val()) : null;
        const lng = $lng.length ? parseFloat($lng.val()) : null;
        const address = $addr.length ? $addr.val() : '';

        if (isNaN(radius) && isNaN(lat) && isNaN(lng) && !address) {
            return null;
        }

        return {
            radius: isNaN(radius) ? null : radius,
            lat: isNaN(lat) ? null : lat,
            lng: isNaN(lng) ? null : lng,
            address: address ? address.trim() : ''
        };
    }

    function applyPrefs($form, prefs) {
        if (!$form || !$form.length || !prefs) {
            return;
        }

        const $range = $form.find('input[id$="_radius_range"]');
        const $lat = $form.find('input[id$="_radius_lat"]');
        const $lng = $form.find('input[id$="_radius_lng"]');
        const $addr = $form.find('input.wcfmmp-radius-addr, input[name="radius_addr"]');

        isApplying = true;

        if ($range.length && prefs.radius != null && !floatsEqual($range.val(), prefs.radius)) {
            $range.val(prefs.radius);
            $range.trigger('input');
            $range.trigger('change');
        }

        if ($lat.length && prefs.lat != null && !floatsEqual($lat.val(), prefs.lat)) {
            $lat.val(prefs.lat);
            $lat.trigger('change');
        }

        if ($lng.length && prefs.lng != null && !floatsEqual($lng.val(), prefs.lng)) {
            $lng.val(prefs.lng);
            $lng.trigger('change');
        }

        if ($addr.length && typeof prefs.address === 'string' && !stringsEqual($addr.val(), prefs.address)) {
            $addr.val(prefs.address);
        }

        window.setTimeout(function () {
            isApplying = false;
        }, 0);
    }

    function broadcast(prefs) {
        if (!prefs) {
            return;
        }
        writePrefs(prefs);
        applyPrefs($('.wcfmmp-store-search-form'), prefs);
        applyPrefs($('.wcfmmp-product-geolocate-search-form'), prefs);
    }

    function ensureMonitor($form) {
        if (!$form.length) {
            return;
        }

        let lastSnapshot = collectPrefs($form);

        const check = function () {
            if (isApplying) {
                return;
            }
            const current = collectPrefs($form);
            if (!current) {
                return;
            }
            if (!lastSnapshot || !deepEqual(current, lastSnapshot)) {
                lastSnapshot = current;
                const stored = readPrefs();
                if (!stored || !deepEqual(current, stored)) {
                    broadcast(current);
                }
            }
        };

        // Event-based triggers
        $form.on('input change blur', 'input, select, textarea', function () {
            window.setTimeout(check, 0);
        });

        // Mutation observer (captures programmatic updates)
        const observer = new MutationObserver(function () {
            window.setTimeout(check, 0);
        });
        observer.observe($form.get(0), {
            attributes: true,
            subtree: true,
            attributeFilter: ['value']
        });

        // Periodic safety check
        window.setInterval(check, 1200);
    }

    $(function () {
        const $storeForm = $('.wcfmmp-store-search-form');
        const $productForm = $('.wcfmmp-product-geolocate-search-form');

        const storedPrefs = readPrefs();
        if (storedPrefs) {
            applyPrefs($storeForm, storedPrefs);
            applyPrefs($productForm, storedPrefs);
        }

        ensureMonitor($storeForm);
        ensureMonitor($productForm);
    });
})(jQuery, window);
