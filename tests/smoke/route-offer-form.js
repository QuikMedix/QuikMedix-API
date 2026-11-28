// Run: node tests/smoke/route-offer-form.js
// Exercise the route offer form's script with a stand-in page.
'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

let checks = 0;
const check = (actual, expected, what) => { assert.deepStrictEqual(actual, expected, what); checks++; };

// Each order is [patient id, ticked].
function page(orders, perStop) {
  const listen = () => { const el = { handlers: {}, addEventListener(type, fn) { el.handlers[type] = fn; } }; return el; };
  const boxes = orders.map(([patient, checked]) => Object.assign(listen(), { checked, getAttribute: () => String(patient) }));
  const pay = { placeholder: '', getAttribute: () => String(perStop) };
  const summary = { textContent: '' };
  const all = Object.assign(listen(), { checked: false });
  const form = { querySelectorAll: () => boxes };
  const elements = { 'offer-form': form, 'offer-pay': pay, 'offer-summary': summary, 'offer-all': all };
  const document = { getElementById: (id) => elements[id] || null };
  vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/route-offer-form.js'), 'utf8'), { document });
  return { boxes, pay, summary, all };
}

// Two orders for one patient are one stop; the pay shown is the stops at the zone rate.
let p = page([[101, true], [101, true], [102, true], [103, false]], 3.75);
check([p.summary.textContent, p.pay.placeholder, p.all.checked], ['2 stops ticked (3 orders), $7.50 at the zone rate.', '7.50', false], 'counts stops, not orders');

// Ticking one more order updates the count and the pay.
p.boxes[3].checked = true;
p.boxes[3].handlers.change();
check([p.summary.textContent, p.pay.placeholder, p.all.checked], ['3 stops ticked (4 orders), $11.25 at the zone rate.', '11.25', true], 'follows a tick');

// The box in the heading clears and ticks every order.
p.all.checked = false;
p.all.handlers.change();
check([p.boxes.map((box) => box.checked), p.summary.textContent, p.pay.placeholder], [[false, false, false, false], '0 stops ticked (0 orders), $0.00 at the zone rate.', '0.00'], 'clears every order');
p.all.checked = true;
p.all.handlers.change();
check([p.boxes.every((box) => box.checked), p.pay.placeholder], [true, '11.25'], 'ticks every order');

// One order is one stop, in the singular.
p = page([[101, true]], 4);
check(p.summary.textContent, '1 stop ticked (1 order), $4.00 at the zone rate.', 'singular');

// On a page without the form the script does nothing.
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/route-offer-form.js'), 'utf8'), { document: { getElementById: () => null } });
checks++;

console.log('route-offer-form: ' + checks + ' checks passed');
