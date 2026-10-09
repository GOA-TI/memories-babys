(function () {
    'use strict';

    if (!document.body.classList.contains('samuel-page-upload')) {
        return;
    }

    var nav = document.querySelector('.cp-mobile-bottom-nav');
    if (!nav || !window.visualViewport) {
        return;
    }

    function syncNavWithKeyboard() {
        var vv = window.visualViewport;
        if (!vv) {
            nav.style.transform = '';
            return;
        }
        var gap = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
        if (gap > 8) {
            nav.style.transform = 'translate3d(0, -' + gap + 'px, 0)';
        } else {
            nav.style.transform = '';
        }
    }

    window.visualViewport.addEventListener('resize', syncNavWithKeyboard);
    window.visualViewport.addEventListener('scroll', syncNavWithKeyboard);
    syncNavWithKeyboard();
})();
