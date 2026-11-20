// Run: node tests/smoke/driver-route-pay.js
// Exercise the Completed button's script with a stand-in page.
'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

let checks = 0;
const check = (actual, expected, what) => { assert.deepStrictEqual(actual, expected, what); checks++; };

function page(chosenType, typedAmount) {
  const sent = { type_pay: { value: '' }, pay_value: { value: '' } };
  const state = { alerts: [], confirms: 0, submitted: 0, confirmAnswer: true, click: null };
  const closeForm = { querySelector: (s) => sent[/name="(\w+)"/.exec(s)[1]], submit: () => { state.submitted++; } };
  const window = {
    document: {
      querySelector(selector) {
        if (selector === '.tariff [name="type_pay"]:checked') return chosenType === null ? null : { value: chosenType };
        if (selector === '.tariff [name="pay_value"]') return { value: typedAmount };
        return null;
      },
      getElementById: (id) => (id === 'close_route' ? closeForm : { addEventListener: (type, fn) => { state.click = fn; } }),
    },
    alert: (text) => state.alerts.push(text),
    confirm: () => { state.confirms++; return state.confirmAnswer; },
  };
  vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/driver-route-pay.js'), 'utf8'), { window });
  return { window, sent, state };
}

// A flat rate with its amount is copied into the closing form and sent.
let p = page('1', '50.00');
p.state.click();
check([p.sent.type_pay.value, p.sent.pay_value.value, p.state.submitted, p.state.confirms, p.state.alerts], ['1', '50.00', 1, 1, []], 'flat rate is sent with its amount');

// Half and Full need no amount, and a stale amount is not sent with them.
p = page('4', '');
p.state.click();
check([p.sent.type_pay.value, p.sent.pay_value.value, p.state.submitted], ['4', '', 1], 'half is sent without an amount');
p = page('5', '75');
p.state.click();
check([p.sent.type_pay.value, p.sent.pay_value.value, p.state.submitted], ['5', '', 1], 'full ignores a typed amount');

// A flat rate without a usable amount is stopped before anything is asked or sent.
for (const amount of ['', '-1', '0', 'abc']) {
  p = page('1', amount);
  p.state.click();
  check([p.state.alerts, p.state.confirms, p.state.submitted], [['Enter the amount to pay the driver.'], 0, 0], 'flat rate needs an amount: "' + amount + '"');
}

// Nothing chosen.
p = page(null, '50');
p.state.click();
check([p.state.alerts, p.state.submitted], [['Choose Half, Full or Flat rate first.'], 0], 'a choice is needed');

// Declining the confirmation sends nothing.
p = page('1', '50');
p.state.confirmAnswer = false;
p.state.click();
check([p.state.confirms, p.state.submitted, p.sent.type_pay.value], [1, 0, ''], 'declining sends nothing');

check(p.window.driverRoutePay.problem('1', '0.01'), null, 'one cent is an amount');

console.log(checks + ' route pay checks passed with a stand-in page.');
