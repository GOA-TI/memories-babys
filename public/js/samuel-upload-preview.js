(function () {
    var legacyPublishOverlay = document.getElementById('samuelUploadPublishing');
    if (legacyPublishOverlay) {
        legacyPublishOverlay.remove();
    }

    var form = document.getElementById('samuel-upload-form');
    if (!form || form.dataset.uploadPreview !== '1') {
        return;
    }

    var previewBtn = document.getElementById('samuel-upload-preview-btn');
    var previewApi = window.samuelUploadFilePreview;

    function previewNodes() {
        return {
            sheet: document.getElementById('samuelUploadPreview'),
            backdrop: document.getElementById('samuelUploadPreviewBackdrop'),
        };
    }
    var mediaHost = document.getElementById('samuelPreviewMedia');
    var captionEl = document.getElementById('samuelPreviewCaption');
    var pubLine = document.getElementById('samuelPreviewPubLine');
    var ageLine = document.getElementById('samuelPreviewAgeLine');
    var publishBtn = document.getElementById('samuelUploadPreviewPublish');
    var backBtn = document.getElementById('samuelUploadPreviewBack');
    var closeBtn = document.getElementById('samuelUploadPreviewClose');

    var babyName = form.dataset.samuelName || 'Samuel';
    var birthIso = form.dataset.samuelBirth || '';

    function describeAge(birth, at) {
        if (!birth || at < birth) {
            return null;
        }
        if (at.toDateString() === birth.toDateString()) {
            var hours = Math.floor((at - birth) / 3600000);
            if (hours < 1) {
                return 'Recién nacido';
            }
            return hours === 1 ? '1 hora' : hours + ' horas';
        }
        var b = new Date(birth.getFullYear(), birth.getMonth(), birth.getDate());
        var a = new Date(at.getFullYear(), at.getMonth(), at.getDate());
        var y = a.getFullYear() - b.getFullYear();
        var m = a.getMonth() - b.getMonth();
        var d = a.getDate() - b.getDate();
        if (d < 0) {
            m -= 1;
            d += new Date(a.getFullYear(), a.getMonth(), 0).getDate();
        }
        if (m < 0) {
            y -= 1;
            m += 12;
        }
        if (y >= 1) {
            var parts = [y === 1 ? '1 año' : y + ' años'];
            if (m > 0) {
                parts.push(m === 1 ? '1 mes' : m + ' meses');
            }
            return parts.join(' y ');
        }
        if (m >= 1) {
            var mp = [m === 1 ? '1 mes' : m + ' meses'];
            if (d > 0) {
                mp.push(d === 1 ? '1 día' : d + ' días');
            }
            return mp.join(' y ');
        }
        if (d >= 1) {
            return d === 1 ? '1 día' : d + ' días';
        }
        return 'Recién nacido';
    }

    function formatShortDate(d) {
        return d.toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function memoryMomentFromForm() {
        var taken = form.querySelector('input[name="memory_taken"]:checked');
        if (taken && taken.value === 'today') {
            return new Date();
        }
        var dateInput = form.querySelector('#memory_date');
        var timeInput = form.querySelector('#memory_time');
        if (!dateInput || !dateInput.value) {
            return new Date();
        }
        var parts = dateInput.value.split('-');
        var time = (timeInput && timeInput.value) ? timeInput.value : '12:00';
        var tp = time.split(':');
        return new Date(
            parseInt(parts[0], 10),
            parseInt(parts[1], 10) - 1,
            parseInt(parts[2], 10),
            parseInt(tp[0], 10) || 0,
            parseInt(tp[1], 10) || 0
        );
    }

    function appendFallback(parent, file) {
        var wrap = document.createElement('div');
        wrap.className = 'samuel-upload-preview__heic';
        wrap.setAttribute('role', 'img');
        wrap.setAttribute('aria-label', file.name || 'Archivo');
        wrap.innerHTML =
            '<span class="samuel-upload-preview__heic-icon" aria-hidden="true"><i class="ri-image-line"></i></span>' +
            '<p class="samuel-upload-preview__heic-title">Preparando vista previa</p>' +
            '<p class="samuel-upload-preview__heic-note">Si no se ve aquí, al publicar el servidor sí procesa la foto.</p>';
        parent.appendChild(wrap);
    }

    function appendResolvedMedia(parent, file, result) {
        if (result.kind === 'video' && result.url) {
            var video = document.createElement('video');
            video.src = result.url;
            video.controls = true;
            video.playsInline = true;
            video.preload = 'metadata';
            video.className = 'samuel-upload-preview__video';
            parent.appendChild(video);
            return;
        }
        if (result.url) {
            var img = document.createElement('img');
            img.src = result.url;
            img.alt = file.name || '';
            img.className = 'samuel-upload-preview__img';
            parent.appendChild(img);
            return;
        }
        appendFallback(parent, file);
    }

    function buildPreviewMedia(files) {
        mediaHost.innerHTML =
            '<div class="samuel-upload-preview__loading" aria-live="polite">' +
            '<span class="samuel-upload-publishing__spinner" aria-hidden="true"></span>' +
            '<p>Preparando fotos…</p></div>';

        if (!previewApi) {
            mediaHost.innerHTML = '<p class="samuel-upload-preview__heic-note">No se pudo cargar la vista previa.</p>';
            return Promise.resolve();
        }

        var jobs = files.map(function (file) {
            return previewApi.resolvePreview(file).then(function (result) {
                return { file: file, result: result };
            });
        });

        return Promise.all(jobs).then(function (items) {
            mediaHost.innerHTML = '';

            if (items.length === 1) {
                appendResolvedMedia(mediaHost, items[0].file, items[0].result);
                return;
            }

            var carousel = document.createElement('div');
            carousel.className = 'samuel-post-media samuel-post-media--carousel samuel-upload-preview__carousel';
            carousel.setAttribute('data-carousel', '');

            var track = document.createElement('div');
            track.className = 'samuel-post-media__track';

            items.forEach(function (item, index) {
                var slide = document.createElement('div');
                slide.className = 'samuel-post-media__slide' + (index === 0 ? ' is-active' : '');
                slide.setAttribute('data-slide', String(index));
                appendResolvedMedia(slide, item.file, item.result);
                track.appendChild(slide);
            });

            carousel.appendChild(track);

            var nav = document.createElement('div');
            nav.className = 'samuel-post-media__nav';
            nav.setAttribute('aria-hidden', 'true');

            var prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.className = 'samuel-post-media__arrow';
            prevBtn.setAttribute('data-carousel-prev', '');
            prevBtn.setAttribute('aria-label', 'Anterior');
            prevBtn.innerHTML = '<i class="ri-arrow-left-s-line"></i>';

            var count = document.createElement('span');
            count.className = 'samuel-post-media__count';
            count.innerHTML = '<span data-carousel-current>1</span>/' + items.length;

            var nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = 'samuel-post-media__arrow';
            nextBtn.setAttribute('data-carousel-next', '');
            nextBtn.setAttribute('aria-label', 'Siguiente');
            nextBtn.innerHTML = '<i class="ri-arrow-right-s-line"></i>';

            nav.appendChild(prevBtn);
            nav.appendChild(count);
            nav.appendChild(nextBtn);
            carousel.appendChild(nav);

            mediaHost.appendChild(carousel);

            if (typeof window.samuelInitPostCarousel === 'function') {
                window.samuelInitPostCarousel(carousel);
            }
        });
    }

    function flashMediaHint(message) {
        var hint = document.getElementById('media-file-name');
        if (!hint) {
            return;
        }
        hint.textContent = message;
        hint.classList.add('samuel-upload-form__hint--warn');
        window.setTimeout(function () {
            hint.classList.remove('samuel-upload-form__hint--warn');
        }, 3200);
    }

    function openPreview() {
        var nodes = previewNodes();
        if (!nodes.sheet || !nodes.backdrop) {
            return;
        }

        var mediaInput = document.getElementById('media');
        var files = previewApi && previewApi.getRetainedFiles ? previewApi.getRetainedFiles() : [];
        if (!files.length && mediaInput && mediaInput.files && mediaInput.files.length) {
            files = Array.prototype.slice.call(mediaInput.files);
        }
        if (!files.length) {
            flashMediaHint('Primero elige al menos una foto o un video.');
            if (mediaInput) {
                mediaInput.click();
            }
            return;
        }

        var caption = (form.querySelector('#caption') || {}).value || '';
        if (caption.trim()) {
            captionEl.textContent = caption.trim();
            captionEl.hidden = false;
        } else {
            captionEl.hidden = true;
        }

        var moment = memoryMomentFromForm();
        var today = new Date();
        var sameDay = moment.toDateString() === today.toDateString();
        pubLine.textContent = 'Publicado hoy · Foto del ' + formatShortDate(moment) + (sameDay ? ' (hoy)' : '');

        if (birthIso) {
            var birth = new Date(birthIso);
            var age = describeAge(birth, moment);
            if (age) {
                ageLine.textContent = babyName + ' tenía ' + age;
                ageLine.hidden = false;
            } else {
                ageLine.hidden = true;
            }
        } else {
            ageLine.hidden = true;
        }

        nodes.sheet.setAttribute('aria-hidden', 'false');
        nodes.backdrop.setAttribute('aria-hidden', 'false');
        nodes.sheet.classList.add('is-open');
        nodes.backdrop.classList.add('is-open');
        document.body.classList.add('samuel-upload-preview-open');
        if (window.samuelPageScrollLock && !document.body.classList.contains('samuel-page-upload')) {
            window.samuelPageScrollLock.lock();
        }

        buildPreviewMedia(files);
    }

    function closePreview() {
        var nodes = previewNodes();
        if (!nodes.sheet || !nodes.backdrop) {
            return;
        }
        nodes.sheet.setAttribute('aria-hidden', 'true');
        nodes.backdrop.setAttribute('aria-hidden', 'true');
        nodes.sheet.classList.remove('is-open');
        nodes.backdrop.classList.remove('is-open');
        document.body.classList.remove('samuel-upload-preview-open');
        if (window.samuelPageScrollLock && !document.body.classList.contains('samuel-page-upload')) {
            if (document.body.classList.contains('samuel-upload-background-active') && window.samuelPageScrollLock.forceUnlock) {
                window.samuelPageScrollLock.forceUnlock();
            } else {
                window.samuelPageScrollLock.unlock();
            }
        }
        if (mediaHost) {
            mediaHost.innerHTML = '';
        }
    }

    function shouldKeepEnabledOnSubmit(el) {
        if (el.type === 'hidden' || el.type === 'file') {
            return true;
        }
        if (el.name === '_token' || el.name === '_method') {
            return true;
        }
        return false;
    }

    function startPublishingUi() {
        if (window.samuelGlobalProgress && window.samuelGlobalProgress.unlockUploadNavigation) {
            window.samuelGlobalProgress.unlockUploadNavigation();
        }
        closePreview();

        if (window.samuelGlobalProgress) {
            window.samuelGlobalProgress.start({
                kind: 'upload',
                label: 'Publicando recuerdo… puedes seguir navegando',
                determinate: true,
                percent: 4,
            });
        }

        if (publishBtn) {
            publishBtn.textContent = 'Publicando…';
        }
        var previewTrigger = document.getElementById('samuel-upload-preview-btn');
        if (previewTrigger) {
            previewTrigger.textContent = 'Publicando en segundo plano…';
        }
    }

    function publishNow() {
        if (window.samuelGlobalProgress && window.samuelGlobalProgress.unlockUploadNavigation) {
            window.samuelGlobalProgress.unlockUploadNavigation();
        }
        closePreview();

        var files = previewApi && previewApi.getRetainedFiles ? previewApi.getRetainedFiles() : [];
        if (!files.length) {
            flashMediaHint('Vuelve a elegir las fotos o el video e intenta publicar de nuevo.');
            var mediaInput = document.getElementById('media');
            if (mediaInput) {
                mediaInput.click();
            }
            return;
        }

        if (!previewApi || !previewApi.submitPublicationForm) {
            form.submit();
            return;
        }

        previewApi
            .submitPublicationForm(form, startPublishingUi)
            .catch(function () {
                flashMediaHint('No se pudo publicar. Vuelve a elegir los archivos e intenta otra vez.');
                if (window.samuelGlobalProgress) {
                    window.samuelGlobalProgress.fail('No se pudo publicar');
                }
            });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        publishNow();
    });

    if (previewBtn) {
        previewBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openPreview();
        });
    }

    [backBtn, closeBtn, previewNodes().backdrop].forEach(function (el) {
        if (el) {
            el.addEventListener('click', closePreview);
        }
    });

    if (publishBtn) {
        publishBtn.addEventListener('click', function () {
            var captionOk = true;
            form.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (el.type === 'file' || el.disabled) {
                    return;
                }
                if (!el.checkValidity()) {
                    captionOk = false;
                }
            });
            if (!captionOk) {
                form.reportValidity();
                return;
            }
            publishNow();
        });
    }
})();
