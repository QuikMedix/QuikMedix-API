(function (window, $) {
    'use strict';

    function sync(select) {
        var values = $(select).val() || [];
        $(select).closest('.plus-item').find('input[name="order_id[]"]').val(values.join(','));
    }

    window.routeOrderSelect = {
        options: {
            includeSelectAllOption: true,
            nonSelectedText: 'Choose orders',
            templates: {
                button: '<button type="button" class="multiselect dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><span class="multiselect-selected-text"></span></button>'
            },
            onInitialized: function (select, container) {
                container.find('button.multiselect').on('click.routeOrderSelect', function (event) {
                    event.stopPropagation();
                });
                sync(select);
            }
        },
        sync: sync,
        updateOfficeId: function (ids) {
            ids = ids.map(String);
            $('.plus-item.active-yellow').each(function () {
                var select = $(this).find('.multiple_select');
                var available = [];
                select.find('option').each(function () {
                    var value = $(this).val().toString();
                    if (ids.indexOf(value) === -1) {
                        $(this).remove();
                    } else {
                        available.push(value);
                    }
                });
                ids.forEach(function (id) {
                    if (available.indexOf(id) === -1) {
                        select.append($('<option>').val(id).text(id));
                    }
                });
                select.multiselect('rebuild');
                sync(select);
            });
        }
    };

    $(document).off('change.routeOrderSelect', '.multiple_select')
        .on('change.routeOrderSelect', '.multiple_select', function () {
            sync(this);
        });
})(window, jQuery);
