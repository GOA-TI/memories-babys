(function () {
    function markLoaded(frame) {
        frame.classList.add('is-loaded');
    }

    function bindMedia(el, frame) {
        if (el.tagName === 'IMG') {
            if (el.complete && el.naturalWidth > 0) {
                markLoaded(frame);
                return;
            }
            el.addEventListener('load', function () {
                markLoaded(frame);
            }, { once: true });
            el.addEventListener('error', function () {
                frame.classList.add('is-error');
                markLoaded(frame);
            }, { once: true });
            return;
        }

        if (el.tagName === 'VIDEO') {
            el.addEventListener('loadeddata', function () {
                markLoaded(frame);
            }, { once: true });
            el.addEventListener('error', function () {
                frame.classList.add('is-error');
                markLoaded(frame);
            }, { once: true });
        }
    }

    function hydrateSrc(el) {
        var deferred = el.getAttribute('data-samuel-src');
        if (!deferred) {
            return;
        }
        if (el.tagName === 'IMG' && !el.getAttribute('src')) {
            el.src = deferred;
        }
        if (el.tagName === 'VIDEO' && !el.getAttribute('src')) {
            el.src = deferred;
            el.load();
        }
        el.removeAttribute('data-samuel-src');
    }

    window.samuelLoadMediaIn = function (root) {
        if (!root) {
            return;
        }
        root.querySelectorAll('[data-samuel-media-frame]').forEach(function (frame) {
            var img = frame.querySelector('img.samuel-media-frame__img');
            var video = frame.querySelector('video');
            var media = img || video;
            if (!media) {
                markLoaded(frame);
                return;
            }
            hydrateSrc(media);
            bindMedia(media, frame);
        });
    };

    function observeDeferredFrames() {
        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('[data-samuel-media-frame]').forEach(function (frame) {
                var img = frame.querySelector('img.samuel-media-frame__img');
                var video = frame.querySelector('video');
                var media = img || video;
                if (!media) {
                    markLoaded(frame);
                    return;
                }
                if (media.getAttribute('data-samuel-src') && !media.getAttribute('src')) {
                    hydrateSrc(media);
                    bindMedia(media, frame);
                    return;
                }
                bindMedia(media, frame);
            });
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    var frame = entry.target;
                    observer.unobserve(frame);
                    var img = frame.querySelector('img.samuel-media-frame__img');
                    var video = frame.querySelector('video');
                    var media = img || video;
                    if (!media) {
                        markLoaded(frame);
                        return;
                    }
                    hydrateSrc(media);
                    bindMedia(media, frame);
                });
            },
            { root: null, rootMargin: '180px 0px', threshold: 0.01 }
        );

        document.querySelectorAll('[data-samuel-media-frame]').forEach(function (frame) {
            var img = frame.querySelector('img.samuel-media-frame__img');
            var video = frame.querySelector('video');
            var media = img || video;
            if (!media) {
                markLoaded(frame);
                return;
            }
            if (media.getAttribute('data-samuel-src') && !media.getAttribute('src')) {
                observer.observe(frame);
                return;
            }
            bindMedia(media, frame);
        });
    }

    observeDeferredFrames();
})();
