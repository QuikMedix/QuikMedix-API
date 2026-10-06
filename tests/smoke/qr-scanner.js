// Run: node tests/smoke/qr-scanner.js
// Exercise the shipped scanner script with isolated DOM/request boundaries.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/qr-scanner.js'), 'utf8');
let checks = 0;
function check(condition, message) { assert(condition, message); checks++; }

function scanner() {
    class Target {
        constructor() { this.listeners = {}; this.attributes = {}; this.hidden = true; this.value = ''; }
        addEventListener(name, callback) { (this.listeners[name] ||= []).push(callback); }
        fire(name, event = {}) { for (const callback of this.listeners[name] || []) callback(event); }
        setAttribute(name, value) { this.attributes[name] = value; }
        focus() { this.focused = true; }
    }
    const dialog = new Target(), form = new Target(), input = new Target(), error = new Target();
    const submit = new Target(), launcher = new Target(), close = new Target();
    form.elements = { _token: { value: 'test-csrf-token' } };
    form.querySelector = () => submit;
    form.reset = () => { input.value = ''; };
    dialog.querySelector = () => close;
    dialog.showModal = () => { dialog.open = true; };
    dialog.close = () => { dialog.open = false; dialog.fire('close'); };
    const requests = [], preview = { visible: false, html: null, hidden: null };
    let destination = null;
    function request(method, url, data, dataType) {
        const callbacks = { done: [], fail: [], always: [] };
        const result = {
            method, url, data, dataType, finished: false,
            done(callback) { callbacks.done.push(callback); return this; },
            fail(callback) { callbacks.fail.push(callback); return this; },
            always(callback) { callbacks.always.push(callback); return this; },
            resolve(response) {
                if (this.finished) return;
                this.finished = true;
                for (const callback of callbacks.done) callback(response);
                for (const callback of callbacks.always) callback();
            },
            reject(status, reason = 'error') {
                if (this.finished) return;
                this.finished = true;
                for (const callback of callbacks.fail) callback({ status }, reason);
                for (const callback of callbacks.always) callback();
            },
            abort() { this.aborted = true; this.reject(0, 'abort'); },
        };
        requests.push(result);
        return result;
    }
    const $ = selector => ({
        on(name, callback) { preview.hidden = callback; },
        html(value) { preview.html = value; },
        modal(action) { preview.visible = action === 'show'; },
    });
    $.get = url => request('GET', url);
    $.post = (url, data, callback, dataType) => request('POST', url, data, dataType);
    const targets = { 'qr-scanner': dialog, 'qr-scanner-form': form, 'scan-code': input, 'qr-scanner-error': error };
    const context = {
        document: { getElementById: id => targets[id], querySelector: () => launcher },
        window: { location: { assign(value) { destination = value; } } }, $, 
    };
    vm.runInNewContext(source, context);
    return {
        dialog, form, input, error, submit, launcher, requests, preview,
        open() { launcher.fire('click'); },
        submitCode(value) { input.value = value; form.fire('submit', { preventDefault() {} }); },
        close() { close.fire('click'); },
        destination() { return destination; },
    };
}

for (const code of ['123', '123_2', 'QM-000123', 'qm-000123', '  QM-000123  ', '000123', '12345678901234567890_2']) {
    const view = scanner();
    view.open(); view.submitCode(code);
    const expected = code.startsWith('123456') ? '/orders/preview/12345678901234567890' : '/orders/preview/123';
    check(view.requests[0].method === 'GET' && view.requests[0].url === expected, 'Order labels and displayed numbers must request the matching order without numeric precision loss.');
    check(view.submit.disabled && view.input.readOnly && view.form.attributes['aria-busy'] === 'true', 'Pending lookups must prevent duplicate input/submission.');
    view.submitCode(code);
    check(view.requests.length === 1, 'A pending lookup must not send another request.');
    view.requests[0].resolve('<article>Order preview</article>');
    check(!view.dialog.open && view.preview.visible && view.preview.html === '<article>Order preview</article>', 'Success must dismiss the scanner and display the order preview.');
    check(!view.requests[0].aborted && !view.submit.disabled && !view.input.readOnly, 'Successful lookup must settle without aborting the response.');
    view.preview.hidden();
    check(view.launcher.focused, 'Closing the preview must return focus to Scan label.');
}

for (const [status, message] of [[401, 'session expired'], [419, 'session expired'], [403, 'available to your account'], [404, 'available to your account'], [500, 'lookup failed'], [0, 'lookup failed']]) {
    const view = scanner();
    view.open(); view.submitCode('123_1'); view.requests[0].reject(status);
    check(view.dialog.open && !view.error.hidden && view.error.textContent.includes(message) && view.input.focused, 'Failed lookup must remain open and explain how to recover.');
    check(!view.submit.disabled && !view.input.readOnly, 'Failure must allow a retry.');
    view.submitCode('124_1');
    check(view.requests.length === 2 && view.requests[1].url === '/orders/preview/124' && view.error.hidden, 'A retry must use the corrected code and clear the old error.');
}

const canceled = scanner();
canceled.open(); canceled.submitCode('123_1'); canceled.close();
check(canceled.requests[0].aborted && !canceled.dialog.open && canceled.error.hidden, 'Closing the scanner must abort its request without showing a lookup failure.');
check(!canceled.submit.disabled && !canceled.input.readOnly, 'Aborting must restore editable controls.');
canceled.open();
check(canceled.input.value === '' && canceled.error.hidden, 'Reopening must start with an empty field and no old error.');
canceled.submitCode('124_1');
check(canceled.requests[1].url === '/orders/preview/124', 'The scanner must work after cancellation.');

const blank = scanner();
blank.open(); blank.submitCode('   ');
check(blank.requests.length === 0 && !blank.error.hidden && blank.error.textContent.includes('Enter an order number'), 'Blank input must explain the required value without a request.');

const driver = scanner();
driver.open(); driver.submitCode('driver-qr-text');
check(driver.requests[0].method === 'POST' && driver.requests[0].url === '/drivers/qr' && driver.requests[0].data._token === 'test-csrf-token' && driver.requests[0].dataType === 'json', 'Driver QR lookup must keep its endpoint, CSRF token, and JSON response contract.');
driver.requests[0].resolve({ user_id: 45 });
check(!driver.dialog.open && driver.destination() === '/drivers/45/packages', 'A valid driver QR must open that driver’s packages.');

const missingDriver = scanner();
missingDriver.open(); missingDriver.submitCode('unknown-driver');
missingDriver.requests[0].resolve({ message: 'No matching driver was found.' });
check(missingDriver.dialog.open && !missingDriver.error.hidden && missingDriver.destination() === null, 'An unknown driver must show an error without navigation.');

console.log(`${checks} QR scanner checks passed with isolated requests.`);
