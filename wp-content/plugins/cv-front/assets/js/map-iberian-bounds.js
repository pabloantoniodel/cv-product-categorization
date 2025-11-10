(function () {
    'use strict';

    var BOUNDS = [[34.0, -10.0], [45.0, 5.0]];
    var MAX_ATTEMPTS = 40;
    var attempts = 0;
    var appliedLeaflet = false;
    var appliedGoogle = false;

    function getLeafletBounds() {
        if (typeof L === 'undefined' || typeof L.latLngBounds !== 'function') {
            return null;
        }
        return L.latLngBounds(BOUNDS[0], BOUNDS[1]);
    }

    function clampLeaflet(map) {
        var bounds = getLeafletBounds();
        if (!bounds) {
            return false;
        }

        if (typeof map.setMaxBounds === 'function') {
            map.setMaxBounds(bounds);
        }
        map.options.maxBounds = bounds;
        map.options.maxBoundsViscosity = 1.0;

        function enforce() {
            map.panInsideBounds(bounds, { animate: false });
        }

        map.whenReady(enforce);
        map.on('drag', enforce);
        map.on('zoomend', enforce);
        appliedLeaflet = true;
        return true;
    }

    function clampGoogle(map) {
        if (typeof google === 'undefined' || !google.maps || typeof google.maps.LatLngBounds !== 'function') {
            return false;
        }

        var restrictionBounds = new google.maps.LatLngBounds(
            new google.maps.LatLng(BOUNDS[0][0], BOUNDS[0][1]),
            new google.maps.LatLng(BOUNDS[1][0], BOUNDS[1][1])
        );

        if (typeof map.setOptions === 'function') {
            map.setOptions({
                restriction: {
                    latLngBounds: restrictionBounds,
                    strictBounds: true
                }
            });
        }

        function enforce() {
            var center = map.getCenter();
            if (restrictionBounds.contains(center)) {
                return;
            }
            var clampedLat = Math.min(Math.max(center.lat(), BOUNDS[0][0]), BOUNDS[1][0]);
            var clampedLng = Math.min(Math.max(center.lng(), BOUNDS[0][1]), BOUNDS[1][1]);
            map.setCenter(new google.maps.LatLng(clampedLat, clampedLng));
        }

        if (typeof map.addListener === 'function') {
            map.addListener('dragend', enforce);
            map.addListener('idle', enforce);
        }

        appliedGoogle = true;
        return true;
    }

    function detectMap() {
        if (!appliedLeaflet && typeof L !== 'undefined' && typeof L.Map !== 'undefined' && typeof L.Map.addInitHook === 'function') {
            L.Map.addInitHook(function () {
                if (this._container && this._container.id === 'wcfmmp-product-list-map') {
                    clampLeaflet(this);
                }
            });
            appliedLeaflet = true;
        }

        if (!appliedGoogle && typeof google !== 'undefined' && google.maps && typeof google.maps.Map === 'function') {
            if (!google.maps.__cvBoundWrapper) {
                var OriginalMap = google.maps.Map;
                google.maps.Map = function (element, opts) {
                    var instance = new OriginalMap(element, opts);
                    if (element && element.id === 'wcfmmp-product-list-map') {
                        clampGoogle(instance);
                    }
                    return instance;
                };
                for (var key in OriginalMap) {
                    if (Object.prototype.hasOwnProperty.call(OriginalMap, key)) {
                        google.maps.Map[key] = OriginalMap[key];
                    }
                }
                google.maps.Map.prototype = OriginalMap.prototype;
                google.maps.__cvBoundWrapper = true;
            }
            appliedGoogle = true;
        }

        if (!appliedLeaflet && typeof window.store_list_map !== 'undefined' && window.store_list_map && typeof window.store_list_map.getBounds === 'function') {
            return clampLeaflet(window.store_list_map);
        }
        if (!appliedGoogle && typeof window.store_list_map !== 'undefined' && window.store_list_map && typeof window.store_list_map.getCenter === 'function' && typeof google !== 'undefined') {
            return clampGoogle(window.store_list_map);
        }
        return false;
    }

    function scheduleCheck() {
        detectMap();
        if (appliedLeaflet && appliedGoogle) {
            return;
        }
        attempts += 1;
        if (attempts > MAX_ATTEMPTS) {
            return;
        }
        setTimeout(scheduleCheck, 250);
    }

    scheduleCheck();
})();

