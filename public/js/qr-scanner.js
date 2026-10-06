(function () {
    'use strict';

    const dialog = document.getElementById('qr-scanner');
    if (!dialog) {
        return;
    }

    const form = document.getElementById('qr-scanner-form');
    const input = document.getElementById('scan-code');
    const error = document.getElementById('qr-scanner-error');
    const submit = form.querySelector('[type="submit"]');
    const launcher = document.querySelector('.scan-qr');
    let pendingRequest = null;

    function showError(message) {
        error.textContent = message;
        error.hidden = false;
        input.focus();
    }

    function setBusy(busy) {
        submit.disabled = busy;
        input.readOnly = busy;
        form.setAttribute('aria-busy', String(busy));
        submit.textContent = busy ? 'Looking up…' : 'Find order or driver';
    }

    launcher.addEventListener('click', function () {
        form.reset();
        error.hidden = true;
        dialog.showModal();
    });

    dialog.querySelector('[data-close-scanner]').addEventListener('click', function () {
        dialog.close();
    });

    dialog.addEventListener('close', function () {
        if (pendingRequest) {
            pendingRequest.abort();
        }
    });

    $('#order_preview_popup').on('hidden.bs.modal', function () {
        launcher.focus();
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (pendingRequest) {
            return;
        }

        const code = input.value.trim();
        if (!code) {
            showError('Enter an order number or scan a label.');
            return;
        }

        error.hidden = true;
        setBusy(true);
        const orderCode = /^(?:QM-)?0*([1-9]\d*)(?:_[1-9]\d*)?$/i.exec(code);
        pendingRequest = orderCode
            ? $.get('/orders/preview/' + orderCode[1])
            : $.post('/drivers/qr', {code: code, _token: form.elements._token.value}, null, 'json');

        pendingRequest.done(function (response) {
            pendingRequest = null;
            if (orderCode) {
                dialog.close();
                $('#order_preview_popup .modal-body').html(response);
                $('#order_preview_popup').modal('show');
            } else if (Number.isInteger(Number(response.user_id)) && Number(response.user_id) > 0) {
                dialog.close();
                window.location.assign('/drivers/' + Number(response.user_id) + '/packages');
            } else {
                showError(response.message || 'No matching driver was found. Check the code and try again.');
            }
        }).fail(function (xhr, status) {
            if (status === 'abort') {
                return;
            }
            if (xhr.status === 401 || xhr.status === 419) {
                showError('Your session expired. Refresh the page and sign in again.');
            } else if (xhr.status === 403 || xhr.status === 404) {
                showError('No matching order or driver is available to your account. Check the code and try again.');
            } else {
                showError('The lookup failed. Check your connection and try again.');
            }
        }).always(function () {
            pendingRequest = null;
            setBusy(false);
        });
    });
})();
