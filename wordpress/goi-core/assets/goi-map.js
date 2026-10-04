(function () {
    'use strict';

    window.GOIMap = {
        create: function (container, options) {
            if (!container || !window.maplibregl) {
                return null;
            }
            var map = new window.maplibregl.Map({
                container: container,
                style: options.style,
                center: options.center || [0, 20],
                zoom: options.zoom || 1.5,
                attributionControl: true
            });
            map.addControl(new window.maplibregl.NavigationControl(), 'top-right');
            return map;
        }
    };
}());
