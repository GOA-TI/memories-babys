(function () {
    'use strict';

    var scrollLockDepth = 0;
    var scrollLockY = 0;

    window.samuelPageScrollLock = {
        lock: function () {
            if (scrollLockDepth === 0) {
                scrollLockY = window.scrollY || document.documentElement.scrollTop || 0;
                document.documentElement.classList.add('samuel-scroll-locked');
                document.body.classList.add('samuel-scroll-locked');
                document.body.style.top = '-' + scrollLockY + 'px';
            }
            scrollLockDepth += 1;
        },
        unlock: function () {
            if (scrollLockDepth <= 0) {
                return;
            }
            scrollLockDepth -= 1;
            if (scrollLockDepth === 0) {
                document.documentElement.classList.remove('samuel-scroll-locked');
                document.body.classList.remove('samuel-scroll-locked');
                document.body.style.top = '';
                window.scrollTo(0, scrollLockY);
            }
        },
        forceUnlock: function () {
            scrollLockDepth = 0;
            document.documentElement.classList.remove('samuel-scroll-locked');
            document.body.classList.remove('samuel-scroll-locked');
            document.body.style.top = '';
        },
    };

    function closeUploadPreviewIfOpen() {
        var sheet = document.getElementById('samuelUploadPreview');
        if (!sheet || !sheet.classList.contains('is-open')) {
            return;
        }
        var closeBtn = document.getElementById('samuelUploadPreviewClose');
        if (closeBtn) {
            closeBtn.click();
        }
    }

    function closeDrawerIfOpen() {
        var drawer = document.getElementById('cpMobileDrawer');
        if (drawer && drawer.classList.contains('is-open')) {
            var closeBtn = document.getElementById('cpMobileMenuClose');
            if (closeBtn) {
                closeBtn.click();
            }
        }
    }

    var openBtn = document.getElementById('cpMobileMenuOpen');
    var closeBtn = document.getElementById('cpMobileMenuClose');
    var drawer = document.getElementById('cpMobileDrawer');
    var backdrop = document.getElementById('cpMobileDrawerBackdrop');

    function setDrawerOpen(open) {
        if (!drawer || !backdrop) {
            return;
        }
        if (open) {
            closeUploadPreviewIfOpen();
            setNotifOpen(false);
        }
        drawer.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('samuel-drawer-open', open);
        if (open) {
            window.samuelPageScrollLock.lock();
        } else {
            window.samuelPageScrollLock.unlock();
        }
    }

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            setDrawerOpen(true);
        });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setDrawerOpen(false);
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setDrawerOpen(false);
        });
    }

    var notifOpen = document.getElementById('samuelNotifOpen');
    var notifClose = document.getElementById('samuelNotifClose');
    var notifPanel = document.getElementById('samuelNotifPanel');
    var notifBackdrop = document.getElementById('samuelNotifBackdrop');

    function setNotifOpen(open) {
        if (!notifPanel || !notifBackdrop) {
            return;
        }
        if (open) {
            closeUploadPreviewIfOpen();
            closeDrawerIfOpen();
        }
        notifPanel.classList.toggle('is-open', open);
        notifBackdrop.hidden = !open;
        notifPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('samuel-notif-open', open);
        if (open) {
            window.samuelPageScrollLock.lock();
            if (window.samuelNotificationManager) {
                window.samuelNotificationManager.loadNotifications();
            }
        } else {
            window.samuelPageScrollLock.unlock();
        }
    }

    if (notifOpen) {
        notifOpen.addEventListener('click', function () {
            setNotifOpen(true);
        });
    }
    if (notifClose) {
        notifClose.addEventListener('click', function () {
            setNotifOpen(false);
        });
    }
    if (notifBackdrop) {
        notifBackdrop.addEventListener('click', function () {
            setNotifOpen(false);
        });
    }

    var reloadBtn = document.getElementById('samuelDrawerReload');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', function () {
            setDrawerOpen(false);
            if (typeof window.samuelHardReload === 'function') {
                window.samuelHardReload();
            } else {
                window.location.reload();
            }
        });
    }
})();
