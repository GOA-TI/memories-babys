(function () {
    var root = document.getElementById('samuelMediaLightbox');
    if (!root) {
        return;
    }

    var contentEl = root.querySelector('[data-samuel-lightbox-content]');
    var captionEl = root.querySelector('[data-samuel-lightbox-caption]');
    var countEl = root.querySelector('[data-samuel-lightbox-count]');
    var downloadEl = root.querySelector('[data-samuel-lightbox-download]');
    var prevBtn = root.querySelector('[data-samuel-lightbox-prev]');
    var nextBtn = root.querySelector('[data-samuel-lightbox-next]');
    var items = [];
    var index = 0;
    var lastFocus = null;

    function parseItem(el) {
        var raw = el.getAttribute('data-samuel-lightbox-item');
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function collectGroup(fromFrame) {
        var host =
            fromFrame.closest('.samuel-post-media') ||
            fromFrame.closest('.samuel-wall-card__media') ||
            fromFrame.closest('.samuel-grid__item') ||
            fromFrame.closest('.samuel-fav-card') ||
            fromFrame.parentElement;
        if (!host) {
            return [];
        }
        var nodes = host.querySelectorAll('[data-samuel-lightbox-item]');
        var list = [];
        nodes.forEach(function (node) {
            var item = parseItem(node);
            if (item && item.url) {
                list.push(item);
            }
        });
        return list;
    }

    function pauseVideos() {
        contentEl.querySelectorAll('video').forEach(function (video) {
            try {
                video.pause();
            } catch (e) {}
        });
    }

    function renderSlide() {
        pauseVideos();
        contentEl.innerHTML = '';
        var item = items[index];
        if (!item) {
            return;
        }

        if (item.type === 'video') {
            var video = document.createElement('video');
            video.src = item.url;
            video.controls = true;
            video.playsInline = true;
            video.preload = 'metadata';
            video.className = 'samuel-media-lightbox__video';
            contentEl.appendChild(video);
        } else {
            var img = document.createElement('img');
            img.src = item.url;
            img.alt = item.caption || item.filename || 'Recuerdo';
            img.className = 'samuel-media-lightbox__img';
            img.decoding = 'async';
            contentEl.appendChild(img);
        }

        if (captionEl) {
            var cap = item.caption || '';
            if (cap) {
                captionEl.textContent = cap;
                captionEl.hidden = false;
            } else {
                captionEl.textContent = '';
                captionEl.hidden = true;
            }
        }

        if (countEl) {
            if (items.length > 1) {
                countEl.textContent = index + 1 + ' / ' + items.length;
                countEl.hidden = false;
            } else {
                countEl.hidden = true;
            }
        }

        if (prevBtn) {
            prevBtn.hidden = items.length < 2;
        }
        if (nextBtn) {
            nextBtn.hidden = items.length < 2;
        }

        if (downloadEl) {
            if (item.download) {
                downloadEl.href = item.download;
                downloadEl.setAttribute('download', item.filename || 'recuerdo');
                downloadEl.hidden = false;
            } else {
                downloadEl.href = '#';
                downloadEl.removeAttribute('download');
                downloadEl.hidden = true;
            }
        }
    }

    function show(at) {
        if (!items.length) {
            return;
        }
        index = (at + items.length) % items.length;
        renderSlide();
    }

    function close() {
        pauseVideos();
        root.hidden = true;
        document.body.classList.remove('samuel-media-lightbox-open');
        contentEl.innerHTML = '';
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    function open(fromFrame) {
        var group = collectGroup(fromFrame);
        var clicked = parseItem(fromFrame);
        if (!clicked || !clicked.url) {
            return;
        }
        items = group.length ? group : [clicked];
        index = Math.max(
            0,
            items.findIndex(function (it) {
                return it.url === clicked.url && it.type === clicked.type;
            })
        );
        if (index < 0) {
            index = 0;
        }
        lastFocus = document.activeElement;
        root.hidden = false;
        document.body.classList.add('samuel-media-lightbox-open');
        renderSlide();
        var closeBtn = root.querySelector('[data-samuel-lightbox-close][aria-label="Cerrar"]');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function shouldOpenFromClick(e, frame) {
        if (e.target.closest('.samuel-media-frame__open')) {
            return true;
        }
        if (e.target.closest('video')) {
            return false;
        }
        if (frame.querySelector('video') && ! frame.classList.contains('samuel-media-frame--video-poster')) {
            return false;
        }
        return true;
    }

    function onFrameActivate(e) {
        var frame = e.currentTarget;
        if (e.type === 'keydown') {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            open(frame);
            return;
        }
        if (!shouldOpenFromClick(e, frame)) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        open(frame);
    }

    function bindFrames(scope) {
        (scope || document).querySelectorAll('[data-samuel-lightbox-item]').forEach(function (frame) {
            if (frame.dataset.samuelLightboxBound === '1') {
                return;
            }
            frame.dataset.samuelLightboxBound = '1';
            frame.addEventListener('click', onFrameActivate);
            frame.addEventListener('keydown', onFrameActivate);
        });
    }

    root.querySelectorAll('[data-samuel-lightbox-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            close();
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            show(index - 1);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            show(index + 1);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (root.hidden) {
            return;
        }
        if (e.key === 'Escape') {
            close();
        } else if (e.key === 'ArrowLeft') {
            show(index - 1);
        } else if (e.key === 'ArrowRight') {
            show(index + 1);
        }
    });

    var touchStartX = null;
    root.addEventListener(
        'touchstart',
        function (e) {
            if (root.hidden) {
                return;
            }
            touchStartX = e.changedTouches[0].clientX;
        },
        { passive: true }
    );
    root.addEventListener(
        'touchend',
        function (e) {
            if (root.hidden || touchStartX === null || items.length < 2) {
                return;
            }
            var dx = e.changedTouches[0].clientX - touchStartX;
            touchStartX = null;
            if (Math.abs(dx) < 50) {
                return;
            }
            show(dx < 0 ? index + 1 : index - 1);
        },
        { passive: true }
    );

    window.samuelBindMediaLightbox = bindFrames;
    bindFrames(document);
})();
