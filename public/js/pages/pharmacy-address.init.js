(function () {
    'use strict';
    var container = document.querySelector('[data-pharmacy-address]');
    if (!container) return;

    var input = container.querySelector('[name="address"]');
    var form = input.closest('form');
    var label = document.getElementById('pharmacy-address-label');
    var status = container.querySelector('#pharmacy-address-status');
    var autocomplete = null;
    var revision = 0;
    var selecting = false;

    function sync() {
        if (autocomplete) input.value = autocomplete.value || '';
    }

    function unavailable() {
        sync();
        revision++;
        selecting = false;
        if (autocomplete) autocomplete.remove();
        autocomplete = null;
        input.type = 'text';
        label.htmlFor = input.id;
        status.textContent = 'Address suggestions are unavailable. Enter the full street address, city, state and ZIP code.';
    }
    window.pharmacyAddressUnavailable = unavailable;
    window.gm_authFailure = unavailable;

    form.addEventListener('submit', function (event) {
        sync();
        if (selecting) {
            event.preventDefault();
            status.textContent = 'Finishing address lookup. Please try Save again in a moment.';
        } else if (!input.value.trim()) {
            event.preventDefault();
            // Restore native required-field validation and focus when the widget is empty.
            unavailable();
            status.textContent = 'Enter a pharmacy address before saving.';
            input.focus();
            input.reportValidity();
        }
    });

    window.initPharmacyAddress = function () {
        if (autocomplete || !container.isConnected) return;
        try {
            var widget = new google.maps.places.PlaceAutocompleteElement({
                requestedRegion: 'us',
                requestedLanguage: 'en',
                placeholder: 'Enter a pharmacy address',
                value: input.value
            });
            widget.id = 'pharmacy-address-autocomplete';
            widget.className = 'pharmacy-address-autocomplete';
            widget.setAttribute('aria-labelledby', label.id);
            widget.description = 'Enter the full street address, city, state and ZIP code.';
            widget.addEventListener('input', function () {
                revision++;
                selecting = false;
                sync();
            });
            widget.addEventListener('gmp-select', async function (event) {
                var selection = ++revision;
                selecting = true;
                sync();
                try {
                    var place = event.placePrediction.toPlace();
                    await place.fetchFields({ fields: ['formattedAddress'] });
                    if (selection !== revision || autocomplete !== widget) return;
                    if (!place.formattedAddress) throw new Error('No address returned');
                    widget.value = place.formattedAddress;
                    sync();
                    status.textContent = 'Address selected. You can edit it before saving.';
                } catch (error) {
                    if (selection === revision && autocomplete === widget) unavailable();
                } finally {
                    if (selection === revision) selecting = false;
                }
            });
            widget.addEventListener('gmp-error', unavailable);
            autocomplete = widget;
            input.before(widget);
            input.type = 'hidden';
            label.htmlFor = widget.id;
        } catch (error) {
            unavailable();
        }
    };

    if (window.google && google.maps && google.maps.places && google.maps.places.PlaceAutocompleteElement) {
        window.initPharmacyAddress();
    }
})();
