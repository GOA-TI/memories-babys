(function () {
    'use strict';

    var deferredPrompt = null;
    var appName = 'Memorias de Samuel';

    var nameMeta = document.querySelector('meta[name="samuel-pwa-name"]');
    if (nameMeta && nameMeta.getAttribute('content')) {
        appName = nameMeta.getAttribute('content');
    }

    function isDisplayStandalone() {
        if (window.navigator.standalone === true) {
            return true;
        }

        return ['standalone', 'fullscreen', 'minimal-ui'].some(function (mode) {
            return window.matchMedia('(display-mode: ' + mode + ')').matches;
        });
    }

    function isStandalone() {
        if (isDisplayStandalone()) {
            return true;
        }
        try {
            if (window.localStorage.getItem('samuel_pwa_installed') === '1') {
                return true;
            }
        } catch (e) {
            /* ignore */
        }

        return false;
    }

    function isIos() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }

    function isAndroid() {
        return /android/i.test(window.navigator.userAgent);
    }

    function registerServiceWorker() {
        return navigator.serviceWorker.register('/sw.js', {
            scope: '/',
            updateViaCache: 'none',
        }).catch(function () {
            return null;
        });
    }

    function showInstallHelp() {
        var sheet = document.getElementById('samuelPwaInstallSheet');
        var backdrop = document.getElementById('samuelPwaInstallBackdrop');
        if (sheet && backdrop) {
            sheet.classList.add('is-open');
            backdrop.classList.add('is-open');
            sheet.setAttribute('aria-hidden', 'false');
            return;
        }

        var msg = isIos()
            ? 'En Safari: Compartir → Añadir a pantalla de inicio.'
            : (isAndroid()
                ? 'En Chrome: menú (⋮) → Instalar app o Añadir a pantalla de inicio.'
                : 'Usa el icono de instalar en la barra del navegador.');

        window.alert(msg);
    }

    function closeInstallHelp() {
        var sheet = document.getElementById('samuelPwaInstallSheet');
        var backdrop = document.getElementById('samuelPwaInstallBackdrop');
        if (!sheet || !backdrop) return;
        sheet.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
    }

    function triggerNativeInstall() {
        if (!deferredPrompt) {
            showInstallHelp();
            return;
        }

        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function () {
            deferredPrompt = null;
            updateInstallUi();
        });
    }

    function bindInstallButtons() {
        document.querySelectorAll('[data-samuel-pwa-install]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                triggerNativeInstall();
            });
        });

        var nativeBtn = document.getElementById('samuelPwaInstallNativeBtn');
        if (nativeBtn) {
            nativeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                triggerNativeInstall();
            });
        }

        var closeBtn = document.getElementById('samuelPwaInstallClose');
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                closeInstallHelp();
            });
        }

        var closeSecondary = document.getElementById('samuelPwaInstallCloseSecondary');
        if (closeSecondary) {
            closeSecondary.addEventListener('click', function (e) {
                e.preventDefault();
                closeInstallHelp();
            });
        }

        var backdrop = document.getElementById('samuelPwaInstallBackdrop');
        if (backdrop) {
            backdrop.addEventListener('click', closeInstallHelp);
        }
    }

    function updateInstallUi() {
        var installed = isStandalone();
        var showInstall = !installed;

        document.querySelectorAll('[data-samuel-pwa-install-wrap]').forEach(function (el) {
            if (el.hasAttribute('data-samuel-pwa-install-always')) {
                el.hidden = isDisplayStandalone();
                return;
            }
            el.hidden = !showInstall;
        });

        document.querySelectorAll('[data-samuel-hide-standalone]').forEach(function (el) {
            el.hidden = installed;
        });

        if (isDisplayStandalone()) {
            document.documentElement.classList.add('samuel-pwa-standalone');
        }

        var nativeBtn = document.getElementById('samuelPwaInstallNativeBtn');
        if (nativeBtn) {
            nativeBtn.hidden = !deferredPrompt;
        }
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        updateInstallUi();
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        try {
            window.localStorage.setItem('samuel_pwa_installed', '1');
        } catch (e) {
            /* ignore */
        }
        updateInstallUi();
        closeInstallHelp();
    });

    document.addEventListener('DOMContentLoaded', function () {
        bindInstallButtons();
        updateInstallUi();

        if ('serviceWorker' in navigator) {
            registerServiceWorker().then(function () {
                updateInstallUi();
            });
        }
    });
})();
