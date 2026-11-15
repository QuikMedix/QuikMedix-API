// Run: node tests/smoke/driver-route-map.js
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const toolsSource = fs.readFileSync(path.join(__dirname, '../../public/js/route-map-tools.js'), 'utf8');
const viewSource = fs.readFileSync(path.join(__dirname, '../../resources/views/routes/driver.blade.php'), 'utf8');
const markerFunctions = viewSource.slice(viewSource.indexOf('    function addMarker('), viewSource.indexOf('</script>', viewSource.indexOf('    function addMarker(')));
let checks = 0;
function check(condition, message) { assert(condition, message); checks++; }

class Target {
    constructor() { this.listeners = {}; this.children = []; this.hidden = false; }
    append(...children) { this.children.push(...children); }
    setAttribute(name, value) { this[name] = value; }
    addEventListener(name, callback) { (this.listeners[name] ||= []).push(callback); }
    addListener(name, callback) {
        this.addEventListener(name, callback);
        return { remove: () => { this.listeners[name] = this.listeners[name].filter(item => item !== callback); } };
    }
    fire(name, data) { (this.listeners[name] || []).forEach(callback => callback(data)); }
}

class Values {
    constructor() { this.values = []; }
    push(value) { this.values.push(value); }
    getLength() { return this.values.length; }
    getArray() { return this.values; }
    removeAt(index) { this.values.splice(index, 1); }
}

class LatLng {
    constructor(lat, lng) {
        this.latitude = typeof lat === 'object' ? lat.lat : lat;
        this.longitude = typeof lat === 'object' ? lat.lng : lng;
    }
    lat() { return this.latitude; }
    lng() { return this.longitude; }
    equals(other) { return this.lat() === other.lat() && this.lng() === other.lng(); }
}

class Bounds {
    constructor() { this.points = []; }
    extend(point) { this.points.push(point); }
    getNorthEast() { return new LatLng(Math.max(...this.points.map(item => item.lat)), Math.max(...this.points.map(item => item.lng))); }
    getSouthWest() { return new LatLng(Math.min(...this.points.map(item => item.lat)), Math.min(...this.points.map(item => item.lng))); }
}

class MapBoundary extends Target {
    constructor(element, options = {}) {
        super();
        this.options = { ...options };
        this.controls = { top: new Values() };
        this.markers = [];
    }
    get(name) { return this.options[name]; }
    setOptions(options) { Object.assign(this.options, options); }
    setCenter(center) { this.options.center = center; }
    setZoom(zoom) { this.options.zoom = zoom; }
    fitBounds(bounds, padding) { this.bounds = bounds; this.padding = padding; }
}

class Polygon {
    constructor(options) { this.options = options; this.map = options.map; this.path = new Values(); }
    getPath() { return this.path; }
    setMap(map) { this.map = map; }
}

class Marker extends Target {
    constructor(options) {
        super();
        this.options = options;
        this.id = options.id;
        options.map.markers.push(this);
    }
    getPosition() { return this.options.position instanceof LatLng ? this.options.position : new LatLng(this.options.position); }
    get(name) { return this.options[name]; }
    getLabel() { return this.options.label; }
    setLabel(label) { this.options.label = label; }
}

const document = { createElement: () => new Target(), getElementById: () => new Target() };
const google = { maps: {
    Map: MapBoundary, Polygon, Marker, LatLng, LatLngBounds: Bounds,
    Point: class { constructor(x, y) { this.x = x; this.y = y; } },
    SymbolPath: { CIRCLE: 'circle' }, ControlPosition: { TOP_CENTER: 'top' },
    geometry: { poly: { containsLocation: position => position.lat() < 41 } }
} };
const window = { google };
const fields = [];
const message = {};
let confirmations = 0;
function $(selector) {
    return {
        text(value) { message.text = value; return this; },
        prop(name, value) { message[name] = value; return this; },
        remove() { fields.length = 0; return this; },
        append(value) { fields.push(value); return this; },
        fadeIn() { confirmations++; return this; }
    };
}
const context = { window, document, google, $, showPatient: true, showPharmacy: true, setup_route: false, markersArray: [], areaSelector: null, map: null, _myPolygon: null };
vm.createContext(context);
vm.runInContext(toolsSource, context);
const tools = window.routeMapTools;
['', ',', 'not-a-point', '91,0', '0,181', 'Infinity,0', '40,-73,2'].forEach(value => {
    check(tools.point(value) === null, 'Invalid coordinates are skipped: ' + value);
});
check(tools.point(' 40 , -73 ').lat === 40 && tools.point('0,0').lng === 0, 'Valid coordinates, including zero, are retained');
check(tools.center(['invalid', '40,-73']).lat === 40, 'Center uses the first valid location');
check(tools.center([]).lat === 40.7128, 'An empty map has a valid default center');

const one = new MapBoundary();
tools.fitStops(one, ['40,-73', '40,-73']);
check(one.options.center.lat === 40 && one.options.zoom === 14, 'One unique location receives a usable zoom');
const multiple = new MapBoundary();
tools.fitStops(multiple, ['bad', '40,-73', '41,-74']);
check(multiple.bounds.points.length === 2 && multiple.padding === 40, 'Bounds contain valid stops and viewport padding');

Object.assign(context, {
    locationDriver: '37,-122',
    locationOffice: ['40,-73', '41,-74'], locationOfficeID: ['1', '2'],
    locationPatients: ['40.2,-73.2', '40.3,-73.3'], locationPatientsID: ['20', '21'],
    locationPharmacy: ['40.1,-73.1', '42,-75'], locationPharmacyID: ['2', '4'],
    mapCenter: () => tools.center([context.locationDriver].concat(context.locationPharmacy, context.locationPatients, context.locationOffice))
});
vm.runInContext(markerFunctions, context);
context.initMap();
check(!google.maps.drawing && context.map.markers.length === 7, 'The actual map initializer renders every marker without the retired drawing library');
check(context.map.markers.every(marker => marker.options.icon.path === 'circle' && !marker.options.icon.url), 'Every marker uses a built-in symbol instead of missing image files');
check(context.map.markers.some(marker => marker.id === 'pharmacy4' && marker.getPosition().lat() === 42), 'A pharmacy marker retains its matching ID and location');
check(context.map.bounds.points.length === 7 && message.hidden, 'All visible stops fit and the empty-map message stays hidden');
check(context.markersArray.length === 4, 'Only patient/pharmacy markers participate in route priority updates');
const driverMarker = context.map.markers.find(marker => marker.id === undefined);
driverMarker.fire('click');
context.setup_route = true;
driverMarker.fire('click');
context.setup_route = false;
check(driverMarker.getLabel().text === 'D' && confirmations === 0, 'Clicking the driver marker does nothing instead of failing or opening the stop dialog');

const oldMap = context.map;
context.showPatient = false;
context.initMap();
check(context.map.markers.length === 5 && context.markersArray.length === 2, 'Toggling patients rebuilds markers without accumulating old entries');
check(oldMap.controls.top.getLength() === 0 && oldMap.listeners.click.length === 0, 'Map reinitialization removes old area controls and listeners');
context.showPharmacy = false;
context.initMap();
check(context.map.markers.length === 3, 'Offices and driver remain visible with pharmacy/patient filters off');

Object.assign(context, { locationDriver: 'invalid', locationOffice: ['40,-73', 'bad'], locationOfficeID: ['1', '2'], locationPatients: [], locationPatientsID: [], locationPharmacy: [], locationPharmacyID: [] });
context.initMap();
check(context.map.markers.length === 1 && context.map.options.center.lat === 40, 'An invalid driver/stop does not prevent valid office markers');
context.locationOffice = [];
context.locationOfficeID = [];
context.initMap();
check(context.map.markers.length === 0 && !message.hidden, 'An empty route has an explanation instead of an unexplained blank map');
context.locationDriver = '37,-122';
context.initMap();
check(context.map.markers.length === 1 && !message.hidden, 'A driver-only map still explains that there are no matching stops');

const areaMap = new MapBoundary(null, { disableDoubleClickZoom: false });
const completed = [];
const selector = tools.areaSelector(areaMap, polygon => completed.push(polygon));
const [draw, finish, cancel, help] = areaMap.controls.top.values[0].children;
draw.fire('click');
check(finish.disabled && draw.hidden && help.textContent.includes('three points'), 'Area selection starts with clear instructions');
areaMap.fire('click', { latLng: new LatLng(40, -73) });
finish.fire('click');
check(completed.length === 0, 'An area cannot finish with fewer than three points');
areaMap.fire('click', { latLng: new LatLng(40.5, -73) });
areaMap.fire('click', { latLng: new LatLng(40.5, -73.5) });
check(!finish.disabled, 'Three points enable finishing the delivery area');
finish.fire('click');
check(completed.length === 1 && !draw.hidden && !areaMap.options.disableDoubleClickZoom, 'Completing an area restores map interaction and emits the polygon');
check(typeof areaMap.options.draggableCursor === 'string', 'Restoring the default map cursor uses a valid string option');
draw.fire('click');
check(completed[0].map === null, 'Starting a new area removes the previous polygon');
cancel.fire('click');
check(!draw.hidden && finish.hidden && completed.length === 1, 'Cancel does not apply an unfinished area');
selector.destroy();
check(areaMap.controls.top.getLength() === 0 && areaMap.listeners.click.length === 0, 'Destroy removes the drawing controls and map listeners');

Object.assign(context, {
    showPatient: true, showPharmacy: true,
    locationDriver: '', locationOffice: ['40,-73'], locationOfficeID: ['1'],
    locationPatients: ['40.2,-73.2', 'invalid'], locationPatientsID: ['20;3', '21;4'],
    locationPharmacy: ['40.1,-73.1'], locationPharmacyID: ['2;2']
});
context.initMap();
const areaControls = context.map.controls.top.values[0].children;
areaControls[0].fire('click');
for (let n = 0; n < 3; n++) { context.map.fire('click', { latLng: new LatLng(40 + n / 10, -73) }); }
areaControls[1].fire('click');
check(confirmations === 1 && fields.length === 3, 'The actual region-selection callback offers matching office/pharmacy/patient stops');
check(fields.some(field => field.includes('name="patients_id[]" value="20"')) && fields.some(field => field.includes('name="pharmacys_id[]" value="2"')), 'Region selection submits real IDs rather than map priority labels');
check(!fields.some(field => field.includes('21')), 'Invalid coordinates are not submitted by area selection');

console.log(checks + ' driver route map checks passed without the Drawing library.');
