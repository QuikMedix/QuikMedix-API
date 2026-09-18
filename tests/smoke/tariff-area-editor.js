// Run: node tests/smoke/tariff-area-editor.js
// Test drawing interactions with the supported Maps primitives, without network access.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/pages/tariff-area.init.js'), 'utf8');
let checks = 0;
function check(condition, message) { assert(condition, message); checks++; }

function editorFor(initial = [], mapsAvailable = true) {
    class Target {
        constructor() { this.listeners = {}; this.disabled = false; this.value = ''; this.textContent = ''; }
        addEventListener(name, callback) { (this.listeners[name] ||= []).push(callback); }
        addListener(name, callback) { this.addEventListener(name, callback); }
        fire(name, event = {}) { (this.listeners[name] || []).forEach(callback => callback(event)); }
        click() { if (!this.disabled) this.fire('click'); }
        scrollIntoView() {}
    }
    class LatLng {
        constructor(lat, lng) { this.lat = lat; this.lng = lng; }
        toJSON() { return { lat: this.lat, lng: this.lng }; }
    }
    class MapPath extends Target {
        constructor(points) { super(); this.points = points.map(p => new LatLng(p.lat, p.lng)); }
        getLength() { return this.points.length; }
        getArray() { return this.points; }
        push(point) { this.points.push(point); this.fire('insert_at'); }
        setAt(index, point) { this.points[index] = point; this.fire('set_at'); }
        insertAt(index, point) { this.points.splice(index, 0, point); this.fire('insert_at'); }
        removeAt(index) { this.points.splice(index, 1); this.fire('remove_at'); }
        clear() { this.points = []; this.fire('remove_at'); }
    }
    const maps = [], polygons = [];
    class GoogleMap extends Target {
        constructor() { super(); maps.push(this); }
        setOptions() {}
        fitBounds() { this.fitted = true; }
    }
    class Polygon extends Target {
        constructor(options) {
            super();
            // Maps treats paths: [] as no rings, so getPath() is undefined.
            // An explicit first ring, paths: [[]], supports drawing from scratch.
            if (options.paths.length) {
                this.path = new MapPath(Array.isArray(options.paths[0]) ? options.paths[0] : options.paths);
            }
            polygons.push(this);
        }
        getPath() { return this.path; }
    }
    class Bounds { extend() { return this; } }
    const elements = {};
    for (const id of ['polygon', 'area-map-status', 'area-add-corners', 'area-finish', 'area-undo', 'area-clear', 'map', 'state', 'tariff-area-data']) elements[id] = new Target();
    elements.polygon.value = JSON.stringify(initial);
    elements['tariff-area-data'].textContent = '[]';
    const form = new Target();
    form.querySelector = selector => elements[selector.slice(1)];
    const editor = { isConnected: true, closest: () => form, querySelector: form.querySelector };
    const document = { querySelector: () => editor, createElement: () => new Target() };
    const google = { maps: {
        Map: GoogleMap, Polygon, LatLngBounds: Bounds,
        Geocoder: class {},
        event: { addListener: (target, name, callback) => target.addListener(name, callback) },
    } };
    // No google.maps.drawing: the editor must not depend on the retired library.
    const context = { document, window: {} };
    if (mapsAvailable) { context.google = google; context.window.google = google; }
    vm.createContext(context);
    vm.runInContext(source, context);
    return {
        elements, maps, polygons, LatLng,
        submit() { let blocked = false; form.fire('submit', { preventDefault() { blocked = true; } }); return !blocked; },
        points() { return JSON.parse(elements.polygon.value || '[]'); },
        loadMaps() { context.google = google; context.window.google = google; context.window.initTariffAreaMap(); },
        clickMap(lat, lng) { maps[0].fire('click', { latLng: new LatLng(lat, lng) }); },
    };
}

const editor = editorFor();
check(editor.elements['area-map-status'].textContent.startsWith('0 corners.'), 'An empty area must initialize its drawing controls.');
check(!editor.submit(), 'Saving with no boundary must be blocked.');
editor.clickMap(40.7, -74);
editor.clickMap(40.8, -74);
check(editor.elements['area-finish'].disabled, 'Two corners cannot finish an area.');
check(!editor.submit(), 'Two corners cannot be submitted.');
editor.clickMap(40.8, -73.9);
check(!editor.elements['area-finish'].disabled, 'Three distinct corners enable finishing.');
check(editor.points().length === 3, 'Drawing must populate the submitted polygon.');
editor.elements['area-finish'].click();
check(editor.submit(), 'A completed boundary can be saved.');
editor.clickMap(41, -73);
check(editor.points().length === 3, 'Map navigation must not add corners after finishing.');
const editablePath = editor.polygons[0].getPath();
editablePath.setAt(0, new editor.LatLng(40.6, -74));
check(editor.points()[0].lat === 40.6, 'Dragging a corner must update submitted geometry.');
editablePath.insertAt(1, new editor.LatLng(40.75, -74.1));
check(editor.points().length === 4, 'Inserting a corner must update submitted geometry.');
editablePath.removeAt(1);
check(editor.points().length === 3, 'Removing a corner must update submitted geometry.');
editor.elements['area-add-corners'].click();
editor.polygons[0].fire('click', { latLng: new editor.LatLng(40.75, -73.98) });
check(editor.points().length === 4, 'Clicking inside the current polygon must add a corner while drawing.');
editor.polygons[0].fire('click', { latLng: new editor.LatLng(40.6, -74), vertex: 0 });
editor.polygons[0].fire('click', { latLng: new editor.LatLng(40.7, -74), edge: 0 });
check(editor.points().length === 4, 'Clicking editing handles must not append duplicate corners.');
editor.elements['area-undo'].click();
editor.clickMap(41, -73);
check(editor.points().length === 4, 'Users can resume adding corners.');
editor.elements['area-undo'].click();
check(editor.points().length === 3, 'Undo must remove the last corner.');
editor.elements['area-clear'].click();
check(editor.points().length === 0 && !editor.submit(), 'Clearing a boundary must clear the submitted geometry.');
editor.clickMap(40, -74); editor.clickMap(40, -74); editor.clickMap(40, -74);
check(!editor.submit(), 'Repeated copies of one corner are not an area.');
editor.elements['area-clear'].click();
editor.clickMap(40.7, -74); editor.clickMap(40.8, -74); editor.clickMap(40.8, -73.9);
check(editor.points().length === 3 && !editor.elements['area-finish'].disabled, 'Drawing a new boundary after clearing must still work.');
editor.elements['area-finish'].click();
editor.polygons[0].fire('click', { latLng: new editor.LatLng(40.75, -73.98) });
check(editor.points().length === 3, 'Clicks inside a finished polygon must not add corners.');

const existing = [{ lat: 40.7, lng: -74 }, { lat: 40.8, lng: -74 }, { lat: 40.8, lng: -73.9 }];
const edit = editorFor(existing);
check(edit.submit() && edit.maps[0].fitted, 'Existing boundaries must load and fit the map.');
edit.clickMap(42, -72);
check(edit.points().length === 3, 'An existing boundary starts in edit mode, not drawing mode.');
edit.polygons[0].getPath().removeAt(2);
check(!edit.submit(), 'Editing below three corners must prevent saving.');
const unavailable = editorFor([], false);
check(!unavailable.submit(), 'Unavailable Maps must not silently save an empty boundary.');
unavailable.loadMaps();
check(unavailable.maps.length === 1, 'Delayed Maps loading must initialize the editor.');
unavailable.loadMaps();
check(unavailable.maps.length === 1, 'Duplicate callbacks must not initialize multiple editors.');
console.log(`${checks} tariff area drawing checks passed without the retired Drawing library.`);
