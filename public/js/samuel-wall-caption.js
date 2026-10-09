(function () {
    document.querySelectorAll('[data-caption-expand]').forEach(function (block) {
        var toggle = block.querySelector('[data-caption-toggle]');
        var shortEl = block.querySelector('[data-caption-short]');
        var fullEl = block.querySelector('[data-caption-full]');
        if (!toggle || !shortEl || !fullEl) {
            return;
        }
        toggle.addEventListener('click', function () {
            var showingFull = !fullEl.hidden;
            if (showingFull) {
                fullEl.hidden = true;
                shortEl.hidden = false;
                toggle.textContent = 'Ver más';
            } else {
                fullEl.hidden = false;
                shortEl.hidden = true;
                toggle.textContent = 'Ver menos';
            }
        });
    });
})();
