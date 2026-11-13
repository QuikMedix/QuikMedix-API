(function (window, document) {
    'use strict';

    if (window.officeOrderPicker) {
        window.officeOrderPicker.destroy();
    }

    var candidates = [];
    var available = [];
    var dialog;
    var routeForm;
    var activeButton;
    var activeInput;

    function ids(values) {
        var result = [];
        values.forEach(function (value) {
            String(value).split(',').forEach(function (id) {
                if (/^[1-9]\d*$/.test(id) && result.indexOf(id) === -1) {
                    result.push(id);
                }
            });
        });
        return result;
    }

    function inputFor(button) {
        return button.closest('.plus-item').querySelector('input[name="order_id[]"]');
    }

    function refreshButtons() {
        routeForm.querySelectorAll('[data-office-order-picker]').forEach(function (button) {
            var selected = ids([inputFor(button).value]);
            button.textContent = selected.length ? selected.join(', ') : 'Choose orders';
            button.title = selected.length ? 'Selected orders: ' + selected.join(', ') : 'Choose deliveries for this office';
        });
    }

    function showError(message) {
        var error = dialog.querySelector('#office-order-error');
        error.textContent = message;
        error.hidden = !message;
    }

    function open(button) {
        activeButton = button;
        activeInput = inputFor(button);
        var stop = button.closest('.plus-item');
        var officeId = stop.querySelector('input[name="type_id[]"]').value;
        var selected = ids([activeInput.value]);
        var choices = ids(available.concat(selected));
        var options = dialog.querySelector('#office-order-options');
        options.replaceChildren();
        dialog.querySelector('#office-order-title').textContent = 'Choose orders for Office #' + officeId;
        choices.forEach(function (id) {
            var label = document.createElement('label');
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'office_order';
            checkbox.value = id;
            checkbox.checked = selected.indexOf(id) !== -1;
            label.append(checkbox, document.createTextNode('Order ' + id));
            options.append(label);
        });
        dialog.querySelector('[data-office-picker-apply]').disabled = choices.length === 0;
        showError(choices.length ? '' : 'No eligible orders are available. Check the order filters or add an eligible delivery, then try again.');
        if (!dialog.open) {
            dialog.showModal();
        }
        var first = options.querySelector('input') || dialog.querySelector('[data-office-picker-cancel]');
        first.focus();
    }

    function click(event) {
        var button = event.target.closest('[data-office-order-picker]');
        if (button && routeForm.contains(button)) {
            event.preventDefault();
            open(button);
        }
    }

    function apply(event) {
        event.preventDefault();
        var selected = [];
        dialog.querySelectorAll('input[name="office_order"]:checked').forEach(function (checkbox) {
            selected.push(checkbox.value);
        });
        if (selected.length === 0) {
            showError('Select at least one order for this office stop.');
            return;
        }
        activeInput.value = ids(selected).join(',');
        refreshButtons();
        dialog.close();
    }

    function cancel() {
        dialog.close();
    }

    function closed() {
        if (activeButton && activeButton.isConnected) {
            activeButton.focus();
        }
    }

    function submitRoute(event) {
        var buttons = routeForm.querySelectorAll('[data-office-order-picker]');
        for (var i = 0; i < buttons.length; i++) {
            if (ids([inputFor(buttons[i]).value]).length === 0) {
                event.preventDefault();
                open(buttons[i]);
                showError('Select at least one order for this office stop before saving.');
                return;
            }
        }
    }

    window.officeOrderPicker = {
        init: function (orderIds) {
            candidates = ids(orderIds);
            available = candidates.slice();
            dialog = document.querySelector('#office-order-dialog');
            routeForm = document.querySelector('#driver-route-form');
            document.addEventListener('click', click);
            routeForm.addEventListener('submit', submitRoute);
            dialog.querySelector('form').addEventListener('submit', apply);
            dialog.querySelector('[data-office-picker-cancel]').addEventListener('click', cancel);
            dialog.addEventListener('close', closed);
            refreshButtons();
        },
        updateOfficeId: function (routeIds) {
            available = ids(candidates.concat(routeIds));
            refreshButtons();
        },
        destroy: function () {
            document.removeEventListener('click', click);
            if (routeForm) {
                routeForm.removeEventListener('submit', submitRoute);
            }
            if (dialog) {
                dialog.querySelector('form').removeEventListener('submit', apply);
                dialog.querySelector('[data-office-picker-cancel]').removeEventListener('click', cancel);
                dialog.removeEventListener('close', closed);
                if (dialog.open) {
                    dialog.close();
                }
            }
        }
    };
})(window, document);
