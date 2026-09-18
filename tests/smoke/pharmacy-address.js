// Run: node tests/smoke/pharmacy-address.js
// Exercise address submission and recovery without network access or real records.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/pages/pharmacy-address.init.js'), 'utf8');
let checks = 0;
function check(condition, message) { assert(condition, message); checks++; }

function editorFor(value = '', available = true, constructorFails = false) {
    class Target {
        constructor() { this.listeners = {}; this.value = ''; }
        addEventListener(name, callback) { (this.listeners[name] ||= []).push(callback); }
        fire(name, event = {}) { return Promise.all((this.listeners[name] || []).map(callback => callback(event))); }
        setAttribute() {}
        remove() { this.removed = true; }
        before(widget) { this.widget = widget; }
        focus() { this.focused = true; }
        reportValidity() { this.validated = true; }
    }
    const form = new Target(), input = new Target(), status = new Target(), label = new Target();
    input.id = 'searchTextField'; input.value = value; input.type = 'text';
    input.closest = () => form;
    label.id = 'pharmacy-address-label'; label.htmlFor = input.id;
    const widgets = [];
    class PlaceAutocompleteElement extends Target {
        constructor(options) {
            super();
            if (constructorFails) throw new Error('Library unavailable');
            this.value = options.value;
            widgets.push(this);
        }
    }
    const container = { isConnected: true, querySelector: selector => selector === '[name="address"]' ? input : status };
    const google = { maps: { places: { PlaceAutocompleteElement } } };
    const context = {
        window: {},
        document: { querySelector: () => container, getElementById: () => label },
    };
    if (available) { context.google = google; context.window.google = google; }
    vm.createContext(context);
    vm.runInContext(source, context);
    return {
        input, status, label, widgets, context,
        load() { context.google = google; context.window.google = google; context.window.initPharmacyAddress(); },
        async submit() { let blocked = false; await form.fire('submit', { preventDefault() { blocked = true; } }); return !blocked; },
        select(place) { return widgets[0].fire('gmp-select', { placePrediction: { toPlace: () => place } }); },
    };
}

async function main() {
    const manual = editorFor('350 5th Ave, New York, NY', false);
    check(await manual.submit() && manual.input.type === 'text', 'Without Maps, manual address entry must remain usable.');
    manual.load(); manual.load();
    check(manual.widgets.length === 1, 'Delayed loading and duplicate callbacks must create one widget.');
    const widget = manual.widgets[0];
    check(widget.value === manual.input.value && manual.input.type === 'hidden', 'Enhancement must preserve the restored address.');
    check(manual.label.htmlFor === widget.id, 'The address label must point to the visible widget.');
    widget.value = '123 Main St, New York, NY';
    await widget.fire('input');
    check(manual.input.value === widget.value, 'Typing must update the submitted address.');
    widget.value = '456 Main St, New York, NY';
    check(await manual.submit() && manual.input.value === widget.value, 'Submit must capture the latest widget value even without an input event.');
    await manual.select({ formattedAddress: '350 5th Ave, New York, NY 10118, USA', async fetchFields() {} });
    check(manual.input.value === '350 5th Ave, New York, NY 10118, USA', 'A selected place must submit its formatted street address.');
    await widget.fire('focus');
    check(manual.input.value === widget.value, 'Refocusing must not restore the pre-selection search query.');

    let complete;
    const selecting = manual.select({ formattedAddress: 'An older address', fetchFields: () => new Promise(resolve => { complete = resolve; }) });
    check(!await manual.submit(), 'Saving must wait for selected address details.');
    widget.value = 'A newer manually typed address';
    await widget.fire('input');
    complete(); await selecting;
    check(manual.input.value === 'A newer manually typed address' && await manual.submit(), 'A slow selection must not overwrite newer input.');
    await widget.fire('gmp-error');
    check(widget.removed && manual.input.type === 'text', 'API denial must restore the native field instead of a blocking popup.');
    check(manual.input.value === 'A newer manually typed address' && await manual.submit(), 'API denial must preserve manual address submission.');
    check(manual.status.textContent.includes('suggestions are unavailable'), 'API denial must explain manual entry.');
    check(manual.label.htmlFor === manual.input.id, 'Fallback must restore the native field label.');

    const empty = editorFor('Old address');
    empty.widgets[0].value = '';
    check(!await empty.submit() && empty.input.value === '', 'Clearing the widget must clear the submitted address and block saving.');
    check(empty.input.focused && empty.input.validated, 'An empty address must receive focus and required-field validation.');
    const failed = editorFor('Restored address');
    await failed.select({ async fetchFields() { throw new Error('Request denied'); } });
    check(failed.input.type === 'text' && failed.input.value === 'Restored address', 'Failed place details must preserve the address in a usable field.');
    const unavailable = editorFor('Restored address', true, true);
    check(unavailable.input.type === 'text' && await unavailable.submit(), 'A constructor failure must leave manual entry usable.');
    const auth = editorFor('Address retained on authentication failure');
    auth.context.window.gm_authFailure();
    check(auth.widgets[0].removed && auth.input.value === 'Address retained on authentication failure', 'Authentication failure must preserve the address and remove the widget.');
    const script = editorFor('Address retained on script failure', false);
    script.context.window.pharmacyAddressUnavailable();
    check(script.input.type === 'text' && await script.submit(), 'Script download failure must not block manual entry.');
    console.log(`${checks} pharmacy address checks passed.`);
}
main().catch(error => { console.error(error); process.exitCode = 1; });
