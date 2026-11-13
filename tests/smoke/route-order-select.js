// Run: node tests/smoke/route-order-select.js
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const source = fs.readFileSync(path.join(__dirname, '../../public/js/route-order-select.js'), 'utf8');
const offices = [];
const document = {};
const events = {};
let checks = 0;

function check(condition, message) {
    assert(condition, message);
    checks++;
}

function option(value, selected = false) {
    return { kind: 'option', value, selected };
}

function office(options) {
    const slot = { kind: 'slot', hidden: { kind: 'hidden', value: '' } };
    slot.select = { kind: 'select', options, slot, rebuilds: 0 };
    options.forEach(item => { item.select = slot.select; });
    offices.push(slot);
    return slot;
}

class Wrapper {
    constructor(items) { this.items = items; }
    each(callback) { this.items.forEach((item, index) => callback.call(item, index, item)); return this; }
    val(value) {
        const item = this.items[0];
        if (arguments.length) { item.value = value; return this; }
        if (item.kind === 'select') {
            return item.options.filter(option => option.selected).map(option => option.value);
        }
        return item.value;
    }
    text(value) { this.items[0].text = value; return this; }
    closest(selector) {
        assert.strictEqual(selector, '.plus-item');
        return $(this.items[0].slot);
    }
    find(selector) {
        const item = this.items[0];
        if (selector === '.multiple_select') { return $(item.select); }
        if (selector === 'input[name="order_id[]"]') { return $(item.hidden); }
        if (selector === 'option') { return new Wrapper(item.options.slice()); }
        if (selector === 'button.multiselect') { return $(item.button); }
        throw new Error('Unexpected selector: ' + selector);
    }
    append(element) {
        const item = element.items[0];
        item.select = this.items[0];
        this.items[0].options.push(item);
        return this;
    }
    remove() {
        const item = this.items[0];
        item.select.options.splice(item.select.options.indexOf(item), 1);
    }
    multiselect(command) {
        assert.strictEqual(command, 'rebuild');
        this.items[0].rebuilds++;
        return this;
    }
    off(name, selector) { delete events[name + ':' + selector]; return this; }
    on(name, selector, callback) {
        if (typeof selector === 'function') { this.items[0].handler = selector; }
        else { events[name + ':' + selector] = callback; }
        return this;
    }
}

function $(value) {
    if (value instanceof Wrapper) { return value; }
    if (value === '.plus-item.active-yellow') { return new Wrapper(offices); }
    if (value === '<option>') { return $(option('')); }
    return new Wrapper([value]);
}

const window = {};
vm.runInNewContext(source, { window, jQuery: $, document });
const selector = window.routeOrderSelect;
const existing = office([option('4', true), option('5', false)]);
const added = office([]);
const button = {};

selector.options.onInitialized($(existing.select), $({ button }));
check(existing.hidden.value === '4', 'Initialization synchronizes the loaded office selection');
let stopped = false;
button.handler({ stopPropagation() { stopped = true; } });
check(stopped, 'The selector isolates clicks from the legacy layout dropdown handler');
check(selector.options.templates.button.includes('data-bs-toggle="dropdown"'), 'The real Bootstrap 5 dropdown data API handles the click');
check(!selector.options.templates.button.includes(' data-toggle='), 'The button does not trigger the old Bootstrap data API');
check(selector.options.templates.button.includes('data-bs-auto-close="outside"'), 'Multiple checkboxes can be selected without closing the menu');
check(selector.options.nonSelectedText === 'Choose orders', 'An empty selection tells dispatch what to choose');

selector.updateOfficeId([4, 5, 6]);
check(existing.hidden.value === '4', 'Rebuilding retains the existing office choice');
check(existing.select.options.length === 3, 'New route orders become available');
check(added.select.options.map(item => item.value).join(',') === '4,5,6', 'A newly added office receives the route orders');
check(added.hidden.value === '', 'Orders are not silently assigned to a new office');
check(added.select.rebuilds === 1, 'New order choices rebuild the visible selector');

added.select.options[0].selected = true;
added.select.options[2].selected = true;
events['change.routeOrderSelect:.multiple_select'].call(added.select);
check(added.hidden.value === '4,6', 'Selecting multiple deliveries submits exactly their IDs');
check(existing.hidden.value === '4', 'One office selection does not overwrite another office');

selector.updateOfficeId(['4', '5']);
check(added.hidden.value === '4', 'Removed orders are removed from the submitted office selection');
check(added.select.options.every(item => item.value !== '6'), 'Removed orders are no longer selectable');

added.select.options.forEach(item => { item.selected = false; });
events['change.routeOrderSelect:.multiple_select'].call(added.select);
check(added.hidden.value === '', 'Clearing a selection clears the submitted IDs');
selector.updateOfficeId([]);
check(existing.hidden.value === '' && added.hidden.value === '', 'An empty route cannot submit stale office order IDs');
check(existing.select.options.length === 0 && added.select.options.length === 0, 'An empty route empties the office choices');

vm.runInNewContext(source, { window, jQuery: $, document });
check(Object.keys(events).length === 1, 'AJAX initialization does not duplicate selection handlers');
console.log(checks + ' office route selector checks passed.');
