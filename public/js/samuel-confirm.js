(function () {
    var backdrop = document.getElementById('samuelConfirmBackdrop');
    var dialog = document.getElementById('samuelConfirmDialog');
    var titleEl = document.getElementById('samuelConfirmTitle');
    var textEl = document.getElementById('samuelConfirmText');
    var btnCancel = document.getElementById('samuelConfirmCancel');
    var btnOk = document.getElementById('samuelConfirmOk');

    if (!backdrop || !dialog || !titleEl || !textEl || !btnCancel || !btnOk) {
        return;
    }

    var pendingForm = null;

    function closeDialog() {
        backdrop.classList.remove('is-open');
        dialog.classList.remove('is-open');
        dialog.setAttribute('aria-hidden', 'true');
        backdrop.setAttribute('aria-hidden', 'true');
        pendingForm = null;
    }

    function openDialog(form) {
        pendingForm = form;
        titleEl.textContent = form.getAttribute('data-samuel-confirm-title') || '¿Eliminar publicación?';
        textEl.textContent =
            form.getAttribute('data-samuel-confirm-text') ||
            'Esta acción no se puede deshacer.';
        var okLabel = form.getAttribute('data-samuel-confirm-ok') || 'Sí, eliminar';
        btnOk.textContent = okLabel;

        backdrop.classList.add('is-open');
        dialog.classList.add('is-open');
        dialog.setAttribute('aria-hidden', 'false');
        backdrop.setAttribute('aria-hidden', 'false');
        btnCancel.focus();
    }

    document.addEventListener(
        'submit',
        function (e) {
            var form = e.target;
            if (!form || !form.matches || !form.matches('form[data-samuel-confirm]')) {
                return;
            }
            if (form.dataset.samuelConfirmBypass === '1') {
                form.dataset.samuelConfirmBypass = '';
                return;
            }
            e.preventDefault();
            openDialog(form);
        },
        true
    );

    btnCancel.addEventListener('click', closeDialog);
    backdrop.addEventListener('click', closeDialog);

    btnOk.addEventListener('click', function () {
        if (!pendingForm) {
            closeDialog();
            return;
        }
        var form = pendingForm;
        closeDialog();
        form.dataset.samuelConfirmBypass = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && dialog.classList.contains('is-open')) {
            closeDialog();
        }
    });
})();
