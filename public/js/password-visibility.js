(function () {
    'use strict';

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        var input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) {
            return;
        }

        button.addEventListener('click', function () {
            var showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';

            var label = showPassword ? button.dataset.hideLabel : button.dataset.showLabel;
            button.setAttribute('aria-label', label);
            button.setAttribute('aria-pressed', String(showPassword));
            button.title = label;
            button.querySelector('i').classList.toggle('mdi-eye-outline', !showPassword);
            button.querySelector('i').classList.toggle('mdi-eye-off-outline', showPassword);
        });
    });
})();
