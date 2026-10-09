(function () {
    'use strict';

    async function clearAppCaches() {
        if ('caches' in window) {
            var keys = await caches.keys();
            await Promise.all(keys.map(function (key) {
                return caches.delete(key);
            }));
        }
    }

    async function unregisterServiceWorkers() {
        if (!('serviceWorker' in navigator)) {
            return;
        }
        var registrations = await navigator.serviceWorker.getRegistrations();
        await Promise.all(registrations.map(function (registration) {
            return registration.unregister();
        }));
    }

    window.samuelHardReload = async function () {
        try {
            await clearAppCaches();
            await unregisterServiceWorkers();
        } catch (e) {
            /* continuar con recarga aunque falle la limpieza */
        }

        var url = new URL(window.location.href);
        url.searchParams.set('samuel_refresh', String(Date.now()));
        window.location.replace(url.toString());
    };
})();
