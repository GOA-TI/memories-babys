(function () {
    function pauseVideosIn(root) {
        if (!root) {
            return;
        }
        root.querySelectorAll('video').forEach(function (video) {
            try {
                video.pause();
            } catch (e) {}
        });
    }

    function initCarousel(root) {
        if (!root || root.dataset.carouselReady === '1') {
            return;
        }
        var slides = root.querySelectorAll('[data-slide]');
        if (slides.length < 2) {
            return;
        }
        root.dataset.carouselReady = '1';
        var current = 0;
        var total = slides.length;
        var label = root.querySelector('[data-carousel-current]');

        function show(index) {
            current = (index + total) % total;
            pauseVideosIn(root);
            slides.forEach(function (slide, i) {
                slide.classList.toggle('is-active', i === current);
            });
            if (label) {
                label.textContent = String(current + 1);
            }
            var active = slides[current];
            if (active && typeof window.samuelLoadMediaIn === 'function') {
                window.samuelLoadMediaIn(active);
            }
            var nextIndex = (current + 1) % total;
            if (nextIndex !== current && slides[nextIndex] && typeof window.samuelLoadMediaIn === 'function') {
                window.samuelLoadMediaIn(slides[nextIndex]);
            }
        }

        var prev = root.querySelector('[data-carousel-prev]');
        var next = root.querySelector('[data-carousel-next]');
        if (prev) {
            prev.addEventListener('click', function () {
                show(current - 1);
            });
        }
        if (next) {
            next.addEventListener('click', function () {
                show(current + 1);
            });
        }

        var startX = null;
        root.addEventListener(
            'touchstart',
            function (e) {
                startX = e.changedTouches[0].clientX;
            },
            { passive: true }
        );
        root.addEventListener(
            'touchend',
            function (e) {
                if (startX === null) {
                    return;
                }
                var dx = e.changedTouches[0].clientX - startX;
                startX = null;
                if (Math.abs(dx) < 40) {
                    return;
                }
                show(dx < 0 ? current + 1 : current - 1);
            },
            { passive: true }
        );

        show(0);
    }

    window.samuelInitPostCarousel = initCarousel;

    document.querySelectorAll('[data-carousel]').forEach(initCarousel);
})();
