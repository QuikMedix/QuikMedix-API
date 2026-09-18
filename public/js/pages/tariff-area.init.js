(function () {
    'use strict';
    var editor = document.querySelector('[data-tariff-area-editor]');
    if (!editor) return;

    var form = editor.closest('form');
    var input = editor.querySelector('#polygon');
    var status = editor.querySelector('#area-map-status');
    var add = editor.querySelector('#area-add-corners');
    var finish = editor.querySelector('#area-finish');
    var undo = editor.querySelector('#area-undo');
    var clear = editor.querySelector('#area-clear');
    var mapElement = editor.querySelector('#map');
    var state = form.querySelector('#state');
    var initialized = false;

    function readPoints(value) {
        try {
            var points = JSON.parse(value || '[]');
            return Array.isArray(points) && points.every(function (point) {
                return point && Number.isFinite(point.lat) && Number.isFinite(point.lng)
                    && Math.abs(point.lat) <= 90 && Math.abs(point.lng) <= 180;
            }) ? points : [];
        } catch (error) {
            return [];
        }
    }

    function hasArea(points) {
        return new Set(points.map(function (point) { return point.lat + ',' + point.lng; })).size >= 3;
    }

    form.addEventListener('submit', function (event) {
        if (!hasArea(readPoints(input.value))) {
            event.preventDefault();
            status.textContent = 'Draw an area with at least three distinct corners before saving.';
            status.scrollIntoView({ block: 'center' });
        }
    });

    window.initTariffAreaMap = function () {
        if (initialized || !editor.isConnected) return;
        initialized = true;
        var points = readPoints(input.value);
        var drawing = !hasArea(points);
        var map = new google.maps.Map(mapElement, {
            zoom: 10,
            center: { lat: 40.743798988555, lng: -74.023925802166 },
            clickableIcons: false
        });
        var polygon = new google.maps.Polygon({
            map: map,
            // Keep a first ring even when empty; paths: [] leaves getPath() undefined.
            paths: [points],
            strokeColor: '#c90016',
            strokeWeight: 2,
            fillColor: '#c90016',
            fillOpacity: 0.25,
            editable: true,
            zIndex: 2
        });
        var path = polygon.getPath();

        function sync() {
            var coordinates = path.getArray().map(function (point) { return point.toJSON(); });
            var valid = hasArea(coordinates);
            input.value = valid ? JSON.stringify(coordinates) : '';
            add.disabled = drawing;
            add.textContent = drawing ? 'Adding corners' : 'Add corners';
            finish.disabled = !drawing || !valid;
            undo.disabled = !drawing || path.getLength() === 0;
            clear.disabled = path.getLength() === 0;
            map.setOptions({ draggableCursor: drawing ? 'crosshair' : null });
            status.textContent = drawing
                ? path.getLength() + ' corners. Click to add corners, then finish the area.'
                : 'Area ready. Drag a corner to adjust the boundary, then save.';
        }

        ['insert_at', 'set_at', 'remove_at'].forEach(function (event) {
            google.maps.event.addListener(path, event, sync);
        });
        function addCorner(event) {
            // Vertex and midpoint clicks belong to the polygon's editing handles.
            if (drawing && event.latLng && event.vertex == null && event.edge == null) path.push(event.latLng);
        }
        map.addListener('click', addCorner);
        polygon.addListener('click', addCorner);
        add.addEventListener('click', function () { drawing = true; sync(); });
        finish.addEventListener('click', function () { drawing = false; sync(); });
        undo.addEventListener('click', function () {
            if (drawing && path.getLength()) path.removeAt(path.getLength() - 1);
        });
        clear.addEventListener('click', function () { drawing = true; path.clear(); sync(); });
        sync();

        if (hasArea(points)) {
            var bounds = new google.maps.LatLngBounds();
            points.forEach(function (point) { bounds.extend(point); });
            map.fitBounds(bounds);
        }
        var geocoder = new google.maps.Geocoder();
        function centerState() {
            if (!state.value) return;
            geocoder.geocode({ address: state.value + ', USA' }, function (results, resultStatus) {
                if (resultStatus === 'OK' && results[0]) {
                    map.fitBounds(results[0].geometry.viewport);
                } else {
                    status.textContent = 'Could not locate the state. Pan and zoom the map to your area.';
                }
            });
        }
        state.addEventListener('change', centerState);
        if (!hasArea(points)) centerState();

        var existing = JSON.parse(editor.querySelector('#tariff-area-data').textContent);
        existing.forEach(function (area) {
            var areaPoints = readPoints(JSON.stringify(area.points));
            if (!hasArea(areaPoints)) return;
            var overlay = new google.maps.Polygon({
                map: map, paths: areaPoints, strokeColor: '#4a4a4a',
                strokeWeight: 1, fillColor: '#4a4a4a', fillOpacity: 0.12, zIndex: 1
            });
            overlay.addListener('click', function (event) {
                if (drawing && event.latLng) {
                    path.push(event.latLng);
                    return;
                }
                var label = document.createElement('span');
                label.textContent = area.name;
                new google.maps.InfoWindow({ content: label, position: event.latLng }).open(map);
            });
        });
    };

    // The portal may already have loaded Maps on an earlier page.
    if (window.google && google.maps && google.maps.Map) window.initTariffAreaMap();
})();
