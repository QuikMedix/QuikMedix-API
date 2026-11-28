// The route offer form: counts the stops ticked, shows what the route would pay at the zone's
// rate, and ticks or clears every order at once. Two orders for one patient are one stop.
(function () {
    var form = document.getElementById('offer-form');
    var pay = document.getElementById('offer-pay');
    var summary = document.getElementById('offer-summary');
    if (!form || !pay || !summary) {
        return;
    }
    var boxes = Array.prototype.slice.call(form.querySelectorAll('.offer-order'));
    var all = document.getElementById('offer-all');

    function refresh() {
        var patients = {};
        var orders = 0;
        boxes.forEach(function (box) {
            if (box.checked) {
                patients[box.getAttribute('data-patient')] = true;
                orders++;
            }
        });
        var stops = Object.keys(patients).length;
        var amount = (stops * parseFloat(pay.getAttribute('data-per-stop') || '0')).toFixed(2);
        pay.placeholder = amount;
        summary.textContent = stops + (stops === 1 ? ' stop' : ' stops') + ' ticked (' + orders + (orders === 1 ? ' order' : ' orders') + '), $' + amount + ' at the zone rate.';
        if (all) {
            all.checked = orders > 0 && orders === boxes.length;
        }
    }

    boxes.forEach(function (box) {
        box.addEventListener('change', refresh);
    });
    if (all) {
        all.addEventListener('change', function () {
            boxes.forEach(function (box) {
                box.checked = all.checked;
            });
            refresh();
        });
    }
    refresh();
})();
