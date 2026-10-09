(function () {
    'use strict';

    var form = document.getElementById('samuel-profile-form');
    if (!form) {
        return;
    }

    var submitBtn = document.getElementById('samuelProfileSubmit');
    var overlay = document.getElementById('samuelProfileSaving');

    function keepEnabledOnSubmit(el) {
        if (el.type === 'hidden' || el.type === 'file' || el.type === 'radio' || el.type === 'checkbox') {
            return true;
        }
        if (el.name === '_token' || el.name === '_method') {
            return true;
        }

        return false;
    }

    function startSavingUi() {
        if (overlay) {
            overlay.hidden = false;
        }
        document.body.classList.add('samuel-upload-publishing');

        form.querySelectorAll('button, input, select, textarea').forEach(function (el) {
            if (keepEnabledOnSubmit(el)) {
                return;
            }
            el.disabled = true;
        });

        if (submitBtn) {
            submitBtn.textContent = 'Guardando…';
        }
    }

    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
            return;
        }
        window.setTimeout(startSavingUi, 0);
    });
})();
