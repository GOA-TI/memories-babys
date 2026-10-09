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

    var openBtn = document.getElementById('cpMobileMenuOpen');
    var closeBtn = document.getElementById('cpMobileMenuClose');
    var drawer = document.getElementById('cpMobileDrawer');
    var backdrop = document.getElementById('cpMobileDrawerBackdrop');

    function setOpen(open) {
        if (!drawer || !backdrop) {
            return;
        }
        if (open) {
            closeUploadPreviewIfOpen();
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
            setOpen(true);
        });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setOpen(false);
        });
    }

    var reloadBtn = document.getElementById('samuelDrawerReload');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', function () {
            setOpen(false);
            if (typeof window.samuelHardReload === 'function') {
                window.samuelHardReload();
            } else {
                window.location.reload();
            }
        });
    }
})();
