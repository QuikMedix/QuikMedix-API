// Run: node tests/smoke/dispatch-stop-map.js
// Executes the shipped dispatch/process stop map against isolated Maps and jQuery boundaries.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/dispatch-stop-map.js'), 'utf8');
// mapCenter() is plain JavaScript in the Blade partial that also prints the stop locations.
const partial = fs.readFileSync(path.join(__dirname, '../../resources/views/dispatching/stop-map-data.blade.php'), 'utf8');
const mapCenter = partial.split('\n').find(line => line.includes('function mapCenter()'));
let checks = 0;
function check(condition, message) { assert(condition, message); checks++; }
// Values built inside the vm context have their own prototypes, so compare plain copies.
const plain = value => JSON.parse(JSON.stringify(value === undefined ? null : value));
function same(actual, expected, message) { assert.deepStrictEqual(plain(actual), plain(expected), message); checks++; }

class LatLng {
    constructor(lat, lng) { this.latitude = lat; this.longitude = lng; }
    lat() { return this.latitude; }
    lng() { return this.longitude; }
    equals(other) { return this.lat() === other.lat() && this.lng() === other.lng(); }
}
class Pair { constructor(a, b) { this.a = a; this.b = b; } }

// Builds a page: Maps and jQuery boundaries plus the globals the Blade page defines before the script.
function page(data, dom = {}) {
    const trace = { maps: [], markers: [], jquery: [] };
    class Marker {
        constructor(options) { this.options = options; this.listeners = {}; trace.markers.push(this); }
        getPosition() {
            const position = this.options.position;
            return position instanceof LatLng ? position : new LatLng(position.lat, position.lng);
        }
        get(name) { return this.options[name]; }
        addListener(name, callback) { this.listeners[name] = callback; }
    }
    const google = { maps: {
        LatLng, Marker, Size: Pair, Point: Pair,
        Map: class { constructor(element, options) { this.element = element; this.options = options; trace.maps.push(this); } },
    } };
    // Records every jQuery call as "selector.method(args)"; lookups answer from the dom fixture.
    function $(selector) {
        const call = (method, ...args) => { trace.jquery.push(`${selector}.${method}(${args.map(arg => JSON.stringify(arg)).join(',')})`); };
        const element = {
            selector,
            length: (dom.present || []).includes(selector) ? 1 : 0,
            find(inner) { return $(`${selector} ${inner}`); },
            index(other) { return (dom.index || {})[`${selector}|${other.selector}`] ?? -1; },
            offset() { return { top: (dom.top || {})[selector] ?? 0 }; },
            val(value) { call('val', value); return element; },
            fadeIn(ms) { call('fadeIn', ms); return element; },
            addClass(name) { call('addClass', name); return element; },
            removeClass(name) { call('removeClass', name); return element; },
            animate(properties, ms) { call('animate', properties, ms); return element; },
        };
        return element;
    }
    const context = vm.createContext({
        google, $, parseFloat, Math, document: { getElementById: id => ({ id }) },
        setup_route: false, setup_marker: undefined, showPatient: true, showPharmacy: true,
        ...data,
    });
    vm.runInContext(mapCenter, context, { filename: 'stop-map-data.blade.php' });
    vm.runInContext(source, context, { filename: 'dispatch-stop-map.js' });
    return { context, trace };
}

const stops = {
    locationDriver: '40.70,-74.00',
    locationPatients: ['40.71,-74.01', '40.72,-74.02', '40.71,-74.01'],
    locationPatientsID: ['11;2', '0', '13;4'],
    locationPharmacy: ['40.73,-74.03', '40.74,-74.04'],
    locationPharmacyID: ['5;1', '0'],
};
const describe = marker => ({
    id: marker.options.id, title: marker.options.title, icon: marker.options.icon.url,
    label: marker.options.label && [marker.options.label.text, marker.options.label.color, marker.options.label.fontWeight, marker.options.label.fontSize],
    lat: marker.getPosition().lat(), lng: marker.getPosition().lng(),
});

// A route with driver, numbered and unnumbered patients and pharmacies.
let { context, trace } = page(stops);
check(typeof context.initMap === 'function', 'Google Maps callback initMap is a global function');
same([context.map, context.markersArray.length], [undefined, 0], 'Map state starts empty until Google calls initMap');
context.initMap();
same([trace.maps.length, trace.maps[0].element.id, trace.maps[0].options.zoom], [1, 'map', 11], 'initMap draws one map in #map at zoom 11');
same(trace.maps[0].options.center, { lat: 40.7, lng: -74 }, 'The map centres on the driver when a location is known');
check(context.map === trace.maps[0], 'The map is kept in the global the page uses');
const markers = trace.markers.map(describe);
same(markers.length, 6, 'One marker each for the driver, three patients and two pharmacies');
same(markers[0], { id: undefined, title: undefined, icon: '/images/i022.svg', label: undefined, lat: 40.7, lng: -74 }, 'Driver marker is drawn first, without an id');
same(markers[1], { id: 'patient11', title: 'patient11', icon: '/images/i033.svg', label: ['2', '#000', 'bold', '8px'], lat: 40.71, lng: -74.01 }, 'Routed patient is labelled with its stop number');
same([markers[2].id, markers[2].label[0]], ['patient0', '0'], 'Unrouted patient keeps the placeholder id and label');
same([markers[3].id, markers[3].label[0]], ['patient13', '4'], 'Second routed patient is labelled with its stop number');
check(markers[3].lat !== 40.71 || markers[3].lng !== -74.01, 'A patient at an already used position is nudged aside');
check(Math.abs(markers[3].lat - 40.71) < 0.0001 && Math.abs(markers[3].lng + 74.01) < 0.0001, 'The nudge stays within a few metres');
same(markers[4], { id: 'pharmacy5', title: 'pharmacy5', icon: '/images/i011.svg', label: ['1', '#fff', 'bold', '8px'], lat: 40.73, lng: -74.03 }, 'Routed pharmacy is labelled with its stop number');
same([markers[5].id, markers[5].label[0]], ['pharmacy0', '0'], 'Unrouted pharmacy keeps the placeholder id and label');
same(context.markersArray.length, 5, 'Patient and pharmacy markers are tracked for overlap checks; the driver is not');
check(trace.markers.every(marker => marker.options.map === context.map), 'Every marker is placed on the page map');
check(trace.markers.every(marker => typeof marker.listeners.click === 'function'), 'Every marker reacts to clicks');

// Clicking a routed patient that is on the route card highlights and scrolls to it.
({ context, trace } = page(stops, { present: ['.wrapper .patient11'], top: { '.wrapper .patient11': 900 } }));
context.initMap();
trace.markers[1].listeners.click();
same(trace.jquery, [
    '.office_block.removeClass("focus")', '.pharmacie_block.removeClass("focus")', '.patient_block.removeClass("focus")',
    '.plus-item.removeClass("focus")', '.patient11.addClass("focus")',
    '.wrapper.animate({"scrollTop":320},500)', 'html, body.animate({"scrollTop":0},400)',
], 'A patient on the route is focused and the route list scrolls to it');

// A patient that is not on the route scrolls the patient list instead.
({ context, trace } = page(stops, { top: { '.patient13': 500 } }));
context.initMap();
trace.markers[3].listeners.click();
same(trace.jquery.slice(-2), ['#patients_list.animate({"scrollTop":180},500)', 'html, body.animate({"scrollTop":0},400)'], 'An unrouted patient scrolls the patient list');

// Pharmacies scroll the route list or the pharmacy list the same way.
({ context, trace } = page(stops, { top: { '.pharmacy5': 420 } }));
context.initMap();
trace.markers[4].listeners.click();
same(trace.jquery.slice(-3), ['.pharmacy5.addClass("focus")', '#pharmacies_list.animate({"scrollTop":100},500)', 'html, body.animate({"scrollTop":0},400)'], 'An unrouted pharmacy scrolls the pharmacy list');
({ context, trace } = page(stops, { present: ['.wrapper .pharmacy5'], top: { '.wrapper .pharmacy5': 700 } }));
context.initMap();
trace.markers[4].listeners.click();
same(trace.jquery.slice(-2), ['.wrapper.animate({"scrollTop":120},500)', 'html, body.animate({"scrollTop":0},400)'], 'A pharmacy on the route scrolls the route list');

// Route setup mode opens the priority dialog instead, prefilled with the stop position.
({ context, trace } = page(stops, { index: { '.wrapper .plus-item|.wrapper .patient11': 2 } }));
context.initMap();
context.setup_route = true;
trace.markers[1].listeners.click();
same(trace.jquery, ['.modal2 input[name="id"].val("patient11")', '.modal2 input[name="priority"].val(3)', '.modal2.fadeIn(300)'], 'Setup mode asks for the priority of a stop already on the route');
check(context.setup_marker === trace.markers[1], 'Setup mode remembers the clicked marker');
({ context, trace } = page(stops));
context.initMap();
context.setup_route = true;
trace.markers[3].listeners.click();
same(trace.jquery[1], '.modal2 input[name="priority"].val(1)', 'A stop that is not on the route starts at priority 1');

// Page filters and missing data.
({ context, trace } = page({ ...stops, showPatient: false }));
context.initMap();
same(trace.markers.map(marker => marker.options.id), [undefined, 'pharmacy5', 'pharmacy0'], 'Hidden patients are not drawn');
({ context, trace } = page({ ...stops, showPharmacy: false }));
context.initMap();
same(trace.markers.map(marker => marker.options.id), [undefined, 'patient11', 'patient0', 'patient13'], 'Hidden pharmacies are not drawn');
({ context, trace } = page({ ...stops, locationDriver: '' }));
context.initMap();
same([trace.markers.length, trace.markers[0].options.id], [5, 'patient11'], 'No driver marker is drawn before the driver shares a location');
same(trace.maps[0].options.center, { lat: 40.73, lng: -74.03 }, 'Without a driver the map centres on the first pharmacy');
({ context, trace } = page({ locationDriver: '', locationPatients: [], locationPatientsID: [], locationPharmacy: [], locationPharmacyID: [] }));
context.initMap();
same([trace.markers.length, trace.maps[0].options.center], [0, { lat: 40.7128, lng: -74.006 }], 'An empty map falls back to the default centre');

// The office marker helper is defined for the page even though initMap does not use it.
({ context, trace } = page(stops));
context.map = new context.google.maps.Map({ id: 'map' }, {});
context.addMarkerOffice('40.75,-74.05', '', '', '', 'office7', context.map);
same([trace.markers[0].options.id, trace.markers[0].options.icon.url, context.markersArray.length], ['office7', '/images/i044.svg', 0], 'Office markers are drawn untracked with the office icon');

console.log(`${checks} dispatch stop map checks passed with isolated Maps and jQuery boundaries.`);
