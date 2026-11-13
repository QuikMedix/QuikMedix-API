// Run: node tests/smoke/office-order-picker.js
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/office-order-picker.js'), 'utf8');
let checks = 0;
function check(condition, message) { assert(condition, message); checks++; }

class Element {
    constructor(tag, attributes = {}) {
        this.tag = tag;
        this.attributes = attributes;
        this.children = [];
        this.listeners = {};
        this.value = '';
        this.hidden = false;
    }
    append(...children) { children.forEach(child => { child.parent = this; this.children.push(child); }); }
    replaceChildren() { this.children = []; }
    addEventListener(type, handler) { (this.listeners[type] ||= []).push(handler); }
    removeEventListener(type, handler) { this.listeners[type] = (this.listeners[type] || []).filter(item => item !== handler); }
    fire(type, values = {}) {
        const event = { target: this, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }, ...values };
        (this.listeners[type] || []).forEach(handler => handler(event));
        return event;
    }
    matches(selector) {
        if (selector.startsWith('#')) { return this.attributes.id === selector.slice(1); }
        if (selector === '.plus-item') { return this.attributes.class === 'plus-item'; }
        if (selector.startsWith('[data-')) { return !!this.attributes[selector.slice(1, -1)]; }
        if (selector.startsWith('input[name=')) {
            const name = selector.match(/name="([^"]+)"/)[1];
            return this.tag === 'input' && this.name === name && (!selector.endsWith(':checked') || this.checked);
        }
        return this.tag === selector;
    }
    closest(selector) { return this.matches(selector) ? this : this.parent?.closest(selector); }
    querySelectorAll(selector) {
        return this.children.flatMap(child => [...(child.matches(selector) ? [child] : []), ...child.querySelectorAll(selector)]);
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    contains(element) { return element === this || this.children.some(child => child.contains(element)); }
    get isConnected() { return document.contains(this); }
    focus() { document.activeElement = this; }
    showModal() { this.open = true; }
    close() { this.open = false; this.fire('close'); }
}

const document = new Element('document');
document.createElement = tag => new Element(tag);
document.createTextNode = text => Object.assign(new Element('#text'), { textContent: text });
const routeForm = new Element('form', { id: 'driver-route-form' });
const dialog = new Element('dialog', { id: 'office-order-dialog' });
const pickerForm = new Element('form');
const title = new Element('h5', { id: 'office-order-title' });
const options = new Element('div', { id: 'office-order-options' });
const error = new Element('p', { id: 'office-order-error' });
const cancel = new Element('button', { 'data-office-picker-cancel': true });
const apply = new Element('button', { 'data-office-picker-apply': true });
pickerForm.append(title, options, error, cancel, apply);
dialog.append(pickerForm);
document.append(routeForm, dialog);

function office(id, orders = '') {
    const slot = new Element('div', { class: 'plus-item' });
    const input = Object.assign(new Element('input'), { name: 'order_id[]', value: orders });
    const destination = Object.assign(new Element('input'), { name: 'type_id[]', value: String(id) });
    const button = new Element('button', { 'data-office-order-picker': true });
    slot.append(input, destination, button);
    routeForm.append(slot);
    return { slot, input, button };
}

const first = office(1, '');
const window = {};
const context = { window, document };
vm.runInNewContext(source, context);
window.officeOrderPicker.init([4, 5, '4', '<script>']);
check(first.button.textContent === 'Choose orders', 'An office without orders has a usable launcher');
document.fire('click', { target: first.button });
check(dialog.open, 'Clicking the launcher opens the native dialog without Bootstrap or Maps');
check(title.textContent === 'Choose orders for Office #1', 'The picker identifies its office');
check(options.querySelectorAll('input').map(item => item.value).join(',') === '4,5', 'Eligible orders are offered before any delivery stop is added');
check(document.activeElement === options.querySelector('input'), 'The first checkbox receives keyboard focus');
check(!apply.disabled, 'Available orders can be applied');
pickerForm.fire('submit');
check(dialog.open && !error.hidden, 'Applying no orders keeps the picker open with an error');

options.querySelectorAll('input').forEach(input => { input.checked = true; });
pickerForm.fire('submit');
check(first.input.value === '4,5', 'Only explicit selections are written into the stop');
check(first.button.textContent === '4, 5', 'The card shows committed order numbers');
check(!dialog.open && document.activeElement === first.button, 'Apply closes the picker and returns focus');

document.fire('click', { target: first.button });
check(options.querySelectorAll('input').every(input => input.checked), 'Reopening restores committed selections');
options.querySelector('input').checked = false;
cancel.fire('click');
check(first.input.value === '4,5', 'Cancel discards pending checkbox edits');
document.fire('click', { target: first.button });
check(options.querySelectorAll('input').every(input => input.checked), 'Cancelled edits do not affect the next opening');
dialog.close();
check(first.input.value === '4,5', 'Native dismissal preserves committed choices');

const second = office(2);
check(routeForm.fire('submit').defaultPrevented, 'An empty dynamically added office prevents route submission');
check(dialog.open && title.textContent === 'Choose orders for Office #2', 'Saving opens the empty office picker');
check(error.textContent.includes('before saving'), 'Saving explains which selection is required');
window.officeOrderPicker.updateOfficeId(['6']);
cancel.fire('click');
document.fire('click', { target: second.button });
check(options.querySelectorAll('input').map(item => item.value).join(',') === '4,5,6', 'Eligible orders remain available when other route stops change');
options.querySelectorAll('input')[2].checked = true;
pickerForm.fire('submit');
check(second.input.value === '6' && first.input.value === '4,5', 'A newly added office updates its own hidden input');
check(!routeForm.fire('submit').defaultPrevented, 'A route with selected office orders can submit');

vm.runInNewContext(source, context);
window.officeOrderPicker.init([]);
check(document.listeners.click.length === 1 && routeForm.listeners.submit.length === 1, 'AJAX initialization removes old listeners');
second.input.value = '';
document.fire('click', { target: second.button });
check(apply.disabled && !error.hidden && error.textContent.includes('No eligible orders'), 'An empty candidate pool has an explicit explanation');
check(options.querySelectorAll('input').length === 0, 'No unrelated order is invented for an empty picker');
console.log(checks + ' native office picker checks passed.');
