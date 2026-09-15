// Run with: node tests/smoke/table-navigation.js
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

function linksFor(file, pathname, search = '') {
    const fragments = [];
    const collection = {
        responsiveTable() { return this; },
        prepend() { return this; },
        append(html) { fragments.push(html); return this; },
    };
    const context = {
        window: { location: { pathname, href: 'http://localhost' + pathname + search } },
        $(value) {
            if (typeof value === 'function') value();
            return collection;
        },
    };
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../..', file), 'utf8'), context);
    if (context.initAll) context.initAll();
    return [...fragments.join('').matchAll(/href="([^"]+)"/g)].map(match => match[1]);
}

for (const file of ['resources/js/pages/table-responsive.init.js', 'public/js/pages/table-responsive.init.js']) {
    assert(linksFor(file, '/orders').includes('/orders/add'), file);
    assert(linksFor(file, '/orders/17/').includes('/orders/17/add'), file);
    assert.deepStrictEqual(linksFor(file, '/settings/drivers'), ['/settings/users/add'], file);
    assert.deepStrictEqual(linksFor(file, '/settings/admins'), ['/settings/users/add'], file);
    assert.deepStrictEqual(linksFor(file, '/pharmacy/17/users/'), ['/pharmacy/17/users/add'], file);
    assert(linksFor(file, '/patients/17/').includes('/patients/17/removed'), file);
    assert.deepStrictEqual(linksFor(file, '/patients/17/removed'), [], file);
    assert.deepStrictEqual(linksFor(file, '/drivers/17/payouts'), [], file);
    assert.deepStrictEqual(linksFor(file, '/orders/17/add'), [], file);
    assert.deepStrictEqual(linksFor(file, '/reports', '?return=/orders'), [], file);
}

const served = 'public/js/pages/table-responsive.init.js';
assert(linksFor(served, '/orders').includes('/orders/facilitys_add'));
assert(linksFor(served, '/orders/17/').includes('/orders/17/facilitys_add'));
assert.deepStrictEqual(linksFor(served, '/drivers/17/users/'), ['/drivers/17/users/add']);
console.log('23 toolbar navigation checks passed.');
