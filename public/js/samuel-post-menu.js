(function () {
    function closeAll(except) {
        document.querySelectorAll('[data-post-menu]').forEach(function (root) {
            if (except && root === except) {
                return;
            }
            var panel = root.querySelector('[data-post-menu-panel]');
            var toggle = root.querySelector('[data-post-menu-toggle]');
            if (panel) {
                panel.hidden = true;
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            root.classList.remove('is-open');
        });
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-post-menu-toggle]');
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            var root = toggle.closest('[data-post-menu]');
            var panel = root && root.querySelector('[data-post-menu-panel]');
            if (!panel) {
                return;
            }
            var willOpen = panel.hidden;
            closeAll(willOpen ? root : null);
            panel.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            root.classList.toggle('is-open', willOpen);
            return;
        }

        if (!e.target.closest('[data-post-menu]')) {
            closeAll(null);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAll(null);
        }
    });
})();
