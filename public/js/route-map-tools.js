(function (window, document) {
    'use strict';

    function point(location) {
        var parts = String(location || '').split(',');
        if (parts.length !== 2 || !parts[0].trim() || !parts[1].trim()) {
            return null;
        }
        var lat = Number(parts[0]);
        var lng = Number(parts[1]);
        return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180
            ? { lat: lat, lng: lng } : null;
    }

    function points(locations) {
        return locations.map(point).filter(Boolean);
    }

    window.routeMapTools = {
        point: point,
        center: function (locations) {
            return points(locations)[0] || { lat: 40.7128, lng: -74.0060 };
        },
        icon: function (color, scale) {
            return {
                path: window.google.maps.SymbolPath.CIRCLE,
                scale: scale || 12,
                fillColor: color,
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 2,
                labelOrigin: new window.google.maps.Point(0, 0)
            };
        },
        fitStops: function (map, locations) {
            var visible = points(locations);
            if (!visible.length) {
                return;
            }
            var bounds = new window.google.maps.LatLngBounds();
            visible.forEach(function (position) { bounds.extend(position); });
            if (bounds.getNorthEast().equals(bounds.getSouthWest())) {
                map.setCenter(visible[0]);
                map.setZoom(14);
            } else {
                map.fitBounds(bounds, 40);
            }
        },
        areaSelector: function (map, complete) {
            var controls = document.createElement('div');
            controls.className = 'route-map-area-controls';
            var draw = document.createElement('button');
            var finish = document.createElement('button');
            var cancel = document.createElement('button');
            var help = document.createElement('span');
            [draw, finish, cancel].forEach(function (button) {
                button.type = 'button';
                button.className = 'btn btn-light btn-sm';
            });
            draw.textContent = 'Select delivery area';
            finish.textContent = 'Finish area';
            cancel.textContent = 'Cancel area';
            finish.hidden = cancel.hidden = true;
            help.setAttribute('role', 'status');
            controls.append(draw, finish, cancel, help);
            controls.addEventListener('click', function (event) { event.stopPropagation(); });
            var controlList = map.controls[window.google.maps.ControlPosition.TOP_CENTER];
            controlList.push(controls);

            var drawing = false;
            var polygon = null;
            var previousOptions = null;

            function stop() {
                drawing = false;
                draw.hidden = false;
                finish.hidden = cancel.hidden = true;
                help.textContent = '';
                if (previousOptions) {
                    map.setOptions(previousOptions);
                    previousOptions = null;
                }
            }

            draw.addEventListener('click', function () {
                if (polygon) {
                    polygon.setMap(null);
                }
                polygon = new window.google.maps.Polygon({
                    map: map, paths: [], clickable: false,
                    strokeColor: '#c90016', strokeWeight: 2,
                    fillColor: '#c90016', fillOpacity: 0.15
                });
                previousOptions = { draggableCursor: map.get('draggableCursor') || '', disableDoubleClickZoom: !!map.get('disableDoubleClickZoom') };
                map.setOptions({ draggableCursor: 'crosshair', disableDoubleClickZoom: true });
                drawing = true;
                draw.hidden = true;
                finish.hidden = cancel.hidden = false;
                finish.disabled = true;
                help.textContent = 'Click at least three points on the map, then Finish area.';
            });

            var click = map.addListener('click', function (event) {
                if (drawing && event.latLng) {
                    polygon.getPath().push(event.latLng);
                    finish.disabled = polygon.getPath().getLength() < 3;
                }
            });

            finish.addEventListener('click', function () {
                if (!drawing || polygon.getPath().getLength() < 3) {
                    return;
                }
                stop();
                complete(polygon);
            });

            cancel.addEventListener('click', function () {
                if (polygon) {
                    polygon.setMap(null);
                    polygon = null;
                }
                stop();
            });

            return {
                destroy: function () {
                    click.remove();
                    if (polygon) {
                        polygon.setMap(null);
                    }
                    stop();
                    var index = controlList.getArray().indexOf(controls);
                    if (index !== -1) {
                        controlList.removeAt(index);
                    }
                }
            };
        }
    };
})(window, document);
