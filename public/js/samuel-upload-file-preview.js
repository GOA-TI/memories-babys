(function () {
    var HEIC_BRANDS = ['heic', 'heix', 'hevc', 'hevx', 'mif1', 'msf1', 'avif'];
    var VIDEO_EXT = /\.(mp4|mov|webm|m4v|3gp|3gpp|avi|mkv)$/i;
    var managedUrls = [];
    var previewCache = new Map();
    /** Copias estables para subir (iOS invalida el input tras vista previa). */
    var retainedUploadFiles = [];

    function retainFileCopy(file) {
        if (!file || typeof file.slice !== 'function') {
            return file;
        }
        var type = file.type || 'application/octet-stream';

        return new File([file.slice(0, file.size, type)], file.name || 'archivo', {
            type: type,
            lastModified: file.lastModified || Date.now(),
        });
    }

    function setRetainedFiles(fileList) {
        retainedUploadFiles = Array.prototype.map.call(fileList || [], retainFileCopy);
    }

    function getRetainedFiles() {
        return retainedUploadFiles.slice();
    }

    function clearRetainedFiles() {
        retainedUploadFiles = [];
    }

    function shouldSkipServerPreview(file) {
        return isVideoFile(file) || (file.size || 0) > 48 * 1024 * 1024;
    }

    function isVideoFile(file) {
        if (!file) {
            return false;
        }
        var type = (file.type || '').toLowerCase();
        if (type.indexOf('video/') === 0) {
            return true;
        }

        return VIDEO_EXT.test(file.name || '');
    }

    function isHeicByMeta(file) {
        var name = (file.name || '').toLowerCase();
        var type = (file.type || '').toLowerCase();

        return /\.(heic|heif)$/.test(name) || type.indexOf('heic') !== -1 || type.indexOf('heif') !== -1;
    }

    function looksLikeHeicBuffer(buffer) {
        if (!buffer || buffer.byteLength < 12) {
            return false;
        }
        var arr = new Uint8Array(buffer);
        if (String.fromCharCode(arr[4], arr[5], arr[6], arr[7]) !== 'ftyp') {
            return false;
        }
        var brand = String.fromCharCode(arr[8], arr[9], arr[10], arr[11]);

        return HEIC_BRANDS.indexOf(brand) !== -1;
    }

    function sniffHeic(file) {
        return new Promise(function (resolve) {
            if (!file || typeof file.slice !== 'function') {
                resolve(false);
                return;
            }
            var reader = new FileReader();
            reader.onload = function () {
                resolve(looksLikeHeicBuffer(reader.result));
            };
            reader.onerror = function () {
                resolve(false);
            };
            reader.readAsArrayBuffer(file.slice(0, 32));
        });
    }

    function testImageObjectUrl(url) {
        return new Promise(function (resolve) {
            var img = new Image();
            img.onload = function () {
                resolve(true);
            };
            img.onerror = function () {
                resolve(false);
            };
            img.src = url;
        });
    }

    function tryHeic2Any(file) {
        if (typeof heic2any !== 'function') {
            return Promise.resolve(null);
        }

        return heic2any({
            blob: file,
            toType: 'image/jpeg',
            quality: 0.92,
        })
            .then(function (result) {
                if (!result) {
                    return null;
                }
                if (Array.isArray(result)) {
                    return result[0] || null;
                }

                return result;
            })
            .catch(function () {
                return null;
            });
    }

    function csrfToken() {
        var input = document.querySelector('#samuel-upload-form [name="_token"]');

        return input ? input.value : '';
    }

    function serverPreviewUrl(file) {
        var endpoint = document.getElementById('samuel-upload-form');
        endpoint = endpoint && endpoint.dataset.previewUrl;
        if (!endpoint) {
            return Promise.resolve(null);
        }

        var fd = new FormData();
        fd.append('file', file, file.name || 'archivo');
        fd.append('_token', csrfToken());

        return fetch(endpoint, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                Accept: 'image/jpeg',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (res) {
                if (!res.ok) {
                    return null;
                }

                return res.blob();
            })
            .catch(function () {
                return null;
            });
    }

    function trackUrl(url) {
        if (url) {
            managedUrls.push(url);
        }

        return url;
    }

    function cacheKey(file) {
        return [file.name || '', file.size || 0, file.lastModified || 0].join('|');
    }

    function revokeAll() {
        managedUrls.forEach(function (u) {
            URL.revokeObjectURL(u);
        });
        managedUrls = [];
        previewCache.clear();
    }

    function resolvePreviewUncached(file) {
        if (!file) {
            return Promise.resolve({ url: null, kind: 'empty' });
        }

        if (isVideoFile(file)) {
            return Promise.resolve({ url: trackUrl(URL.createObjectURL(file)), kind: 'video' });
        }

        return sniffHeic(file).then(function (sniffHeic) {
            var heic = isHeicByMeta(file) || sniffHeic;

            if (heic) {
                return tryHeic2Any(file).then(function (blob) {
                    if (blob) {
                        return { url: trackUrl(URL.createObjectURL(blob)), kind: 'image' };
                    }

                    if (shouldSkipServerPreview(file)) {
                        return { url: null, kind: 'heic-failed' };
                    }

                    return serverPreviewUrl(file).then(function (jpegBlob) {
                        if (jpegBlob) {
                            return { url: trackUrl(URL.createObjectURL(jpegBlob)), kind: 'image' };
                        }

                        return { url: null, kind: 'heic-failed' };
                    });
                });
            }

            var direct = URL.createObjectURL(file);

            return testImageObjectUrl(direct).then(function (ok) {
                if (ok) {
                    return { url: trackUrl(direct), kind: 'image' };
                }
                URL.revokeObjectURL(direct);

                return tryHeic2Any(file).then(function (blob) {
                    if (blob) {
                        return { url: trackUrl(URL.createObjectURL(blob)), kind: 'image' };
                    }

                    if (shouldSkipServerPreview(file)) {
                        return { url: null, kind: 'failed' };
                    }

                    return serverPreviewUrl(file).then(function (jpegBlob) {
                        if (jpegBlob) {
                            return { url: trackUrl(URL.createObjectURL(jpegBlob)), kind: 'image' };
                        }

                        return { url: null, kind: 'failed' };
                    });
                });
            });
        });
    }

    function appendFormFields(form, fd) {
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.name || el.disabled) {
                return;
            }
            if (el.type === 'file') {
                return;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (!el.checked) {
                    return;
                }
                fd.append(el.name, el.value);
                return;
            }
            if (el.type === 'select-multiple') {
                Array.prototype.forEach.call(el.options, function (opt) {
                    if (opt.selected) {
                        fd.append(el.name, opt.value);
                    }
                });
                return;
            }
            fd.append(el.name, el.value);
        });
    }

    function submitPublicationForm(form, onStart) {
        var files = getRetainedFiles();
        if (!files.length) {
            var mediaInput = document.getElementById('media');
            if (mediaInput) {
                mediaInput.click();
            }

            return Promise.reject(new Error('missing_files'));
        }

        if (window.samuelBackgroundUpload && window.samuelBackgroundUpload.submitPublicationForm) {
            return window.samuelBackgroundUpload.submitPublicationForm(form, files, onStart);
        }

        var fd = new FormData();
        appendFormFields(form, fd);
        files.forEach(function (file) {
            fd.append('media[]', file, file.name || 'archivo');
        });

        if (typeof onStart === 'function') {
            onStart();
        }

        return fetch(form.action, {
            method: (form.method || 'POST').toUpperCase(),
            body: fd,
            credentials: 'same-origin',
            redirect: 'follow',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        }).then(function (res) {
            return res.json().then(function (json) {
                if (json && json.redirect) {
                    window.location.href = json.redirect;
                    return;
                }
                window.location.href = res.url;
            }).catch(function () {
                window.location.href = res.url;
            });
        });
    }

    function resolvePreview(file) {
        var key = cacheKey(file);
        if (previewCache.has(key)) {
            return Promise.resolve(previewCache.get(key));
        }

        return resolvePreviewUncached(file).then(function (result) {
            previewCache.set(key, result);

            return result;
        });
    }

    function renderInlineThumbs(files) {
        var host = document.getElementById('samuel-upload-thumbs');
        if (!host) {
            return Promise.resolve();
        }

        revokeAll();
        host.innerHTML = '';
        if (!files || !files.length) {
            host.hidden = true;
            return Promise.resolve();
        }

        host.hidden = false;
        host.setAttribute('aria-busy', 'true');

        if (window.samuelGlobalProgress && files.length > 0) {
            window.samuelGlobalProgress.start({ label: 'Preparando miniaturas…' });
        }

        var jobs = Array.prototype.map.call(files, function (file, index) {
            var cell = document.createElement('div');
            cell.className = 'samuel-upload-thumbs__cell';
            cell.innerHTML =
                '<div class="samuel-upload-thumbs__loading" aria-hidden="true"></div>' +
                '<span class="samuel-upload-thumbs__badge">' + (index + 1) + '</span>';
            host.appendChild(cell);

            return resolvePreview(file).then(function (result) {
                cell.innerHTML = '';
                var badge = document.createElement('span');
                badge.className = 'samuel-upload-thumbs__badge';
                badge.textContent = String(index + 1);
                cell.appendChild(badge);

                if (result.kind === 'video' && result.url) {
                    var video = document.createElement('video');
                    video.src = result.url;
                    video.muted = true;
                    video.playsInline = true;
                    video.preload = 'metadata';
                    video.className = 'samuel-upload-thumbs__media';
                    cell.appendChild(video);
                    var play = document.createElement('span');
                    play.className = 'samuel-upload-thumbs__play';
                    play.innerHTML = '<i class="ri-play-fill"></i>';
                    cell.appendChild(play);
                    return;
                }

                if (result.url) {
                    var img = document.createElement('img');
                    img.src = result.url;
                    img.alt = file.name || '';
                    img.className = 'samuel-upload-thumbs__media';
                    cell.appendChild(img);
                    return;
                }

                cell.classList.add('samuel-upload-thumbs__cell--fallback');
                cell.innerHTML =
                    '<span class="samuel-upload-thumbs__fallback"><i class="ri-image-line"></i></span>' +
                    '<span class="samuel-upload-thumbs__badge">' +
                    (index + 1) +
                    '</span>' +
                    '<span class="samuel-upload-thumbs__note">Vista previa limitada; al publicar sí se sube.</span>';
            });
        });

        return Promise.all(jobs).then(function () {
            host.setAttribute('aria-busy', 'false');
            if (
                window.samuelGlobalProgress &&
                window.samuelGlobalProgress.isVisible() &&
                !(window.samuelBackgroundUpload && window.samuelBackgroundUpload.isActive())
            ) {
                window.samuelGlobalProgress.finish({ delayMs: 280 });
            }
        });
    }

    window.samuelUploadFilePreview = {
        resolvePreview: resolvePreview,
        revokeAll: revokeAll,
        renderInlineThumbs: renderInlineThumbs,
        isVideoFile: isVideoFile,
        getRetainedFiles: getRetainedFiles,
        clearRetainedFiles: clearRetainedFiles,
        submitPublicationForm: submitPublicationForm,
    };

    var mediaInput = document.getElementById('media');
    if (mediaInput) {
        mediaInput.addEventListener('change', function () {
            var files = mediaInput.files ? Array.prototype.slice.call(mediaInput.files) : [];
            setRetainedFiles(files);
            renderInlineThumbs(getRetainedFiles());
        });
    }
})();
