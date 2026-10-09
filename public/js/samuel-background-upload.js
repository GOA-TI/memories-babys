(function () {
    'use strict';

    var DB_NAME = 'samuel-background-uploads';
    var DB_VERSION = 1;
    var STORE = 'jobs';
    var CHANNEL = 'samuel-upload-progress';
    var broadcast = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel(CHANNEL) : null;

    var activeJobId = null;
    var xhrRef = null;

    function uuid() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }

        return 'upload-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function openDb() {
        return new Promise(function (resolve, reject) {
            var req = indexedDB.open(DB_NAME, DB_VERSION);
            req.onupgradeneeded = function () {
                if (!req.result.objectStoreNames.contains(STORE)) {
                    req.result.createObjectStore(STORE);
                }
            };
            req.onsuccess = function () {
                resolve(req.result);
            };
            req.onerror = function () {
                reject(req.error);
            };
        });
    }

    function idbGet(key) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readonly');
                var store = tx.objectStore(STORE);
                var req = store.get(key);
                req.onsuccess = function () {
                    resolve(req.result || null);
                };
                req.onerror = function () {
                    reject(req.error);
                };
            });
        });
    }

    function idbPut(key, value) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readwrite');
                tx.objectStore(STORE).put(value, key);
                tx.oncomplete = function () {
                    resolve();
                };
                tx.onerror = function () {
                    reject(tx.error);
                };
            });
        });
    }

    function idbDelete(key) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readwrite');
                tx.objectStore(STORE).delete(key);
                tx.oncomplete = function () {
                    resolve();
                };
                tx.onerror = function () {
                    reject(tx.error);
                };
            });
        });
    }

    function emit(event) {
        if (broadcast) {
            broadcast.postMessage(event);
        }
        if (typeof window.samuelGlobalProgress === 'undefined') {
            return;
        }
        if (event.type === 'progress') {
            window.samuelGlobalProgress.setProgress(event.percent, event.label);
        } else if (event.type === 'start') {
            window.samuelGlobalProgress.start({
                kind: 'upload',
                label: event.label,
                determinate: true,
                percent: event.percent || 0,
            });
            if (window.samuelGlobalProgress.unlockUploadNavigation) {
                window.samuelGlobalProgress.unlockUploadNavigation();
            }
        } else if (event.type === 'done') {
            window.samuelGlobalProgress.finish({ label: event.label || 'Listo', delayMs: 350 });
        } else if (event.type === 'error') {
            window.samuelGlobalProgress.fail(event.message);
        }
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

    function buildFormDataFromJob(job) {
        var fd = new FormData();
        (job.fields || []).forEach(function (pair) {
            fd.append(pair.name, pair.value);
        });
        (job.files || []).forEach(function (fileMeta) {
            var blob = fileMeta.blob;
            fd.append(fileMeta.fieldName, blob, fileMeta.fileName || 'archivo');
        });

        return fd;
    }

    function serializeJob(form, files) {
        var fields = [];
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.name || el.disabled || el.type === 'file') {
                return;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (!el.checked) {
                    return;
                }
                fields.push({ name: el.name, value: el.value });
                return;
            }
            if (el.type === 'select-multiple') {
                Array.prototype.forEach.call(el.options, function (opt) {
                    if (opt.selected) {
                        fields.push({ name: el.name, value: opt.value });
                    }
                });
                return;
            }
            fields.push({ name: el.name, value: el.value });
        });

        return {
            id: uuid(),
            action: form.action,
            method: (form.method || 'POST').toUpperCase(),
            fields: fields,
            files: files.map(function (file) {
                return {
                    fieldName: 'media[]',
                    fileName: file.name || 'archivo',
                    type: file.type || 'application/octet-stream',
                    blob: file,
                };
            }),
            status: 'pending',
            redirect: null,
            error: null,
            createdAt: Date.now(),
        };
    }

    function totalBytes(job) {
        var sum = 0;
        (job.files || []).forEach(function (f) {
            sum += f.blob && f.blob.size ? f.blob.size : 0;
        });

        return sum || 1;
    }

    function notifyServiceWorker(jobId, type) {
        if (!navigator.serviceWorker || !navigator.serviceWorker.controller) {
            return;
        }
        navigator.serviceWorker.controller.postMessage({
            type: type,
            jobId: jobId,
        });
    }

    function handleUploadResponse(xhr) {
        var redirect = xhr.responseURL || null;
        var json = null;
        try {
            if (xhr.getResponseHeader('Content-Type') && xhr.getResponseHeader('Content-Type').indexOf('json') !== -1) {
                json = JSON.parse(xhr.responseText || '{}');
            }
        } catch (e) {
            json = null;
        }

        if (xhr.status >= 200 && xhr.status < 300 && json && json.ok) {
            return { ok: true, redirect: json.redirect || redirect, message: json.message };
        }

        if (xhr.status === 422 && json && json.message) {
            return { ok: false, message: json.message };
        }

        if (xhr.status >= 200 && xhr.status < 400 && redirect) {
            return { ok: true, redirect: redirect, message: null };
        }

        return { ok: false, message: (json && json.message) || 'No se pudo publicar. Intenta de nuevo.' };
    }

    function runXhrUpload(job) {
        return new Promise(function (resolve, reject) {
            var fd = buildFormDataFromJob(job);
            var xhr = new XMLHttpRequest();
            xhrRef = xhr;
            var total = totalBytes(job);

            xhr.open(job.method, job.action, true);
            xhr.withCredentials = true;
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.addEventListener('progress', function (ev) {
                if (!ev.lengthComputable) {
                    return;
                }
                var pct = Math.round((ev.loaded / ev.total) * 88);
                emit({
                    type: 'progress',
                    jobId: job.id,
                    percent: pct,
                    label: 'Subiendo recuerdo… ' + pct + '%',
                });
            });

            xhr.onload = function () {
                xhrRef = null;
                resolve(handleUploadResponse(xhr));
            };

            xhr.onerror = function () {
                xhrRef = null;
                reject(new Error('network'));
            };

            xhr.onabort = function () {
                xhrRef = null;
                reject(new Error('aborted'));
            };

            emit({
                type: 'start',
                jobId: job.id,
                percent: 2,
                label: 'Preparando subida…',
            });

            xhr.send(fd);
        });
    }

    function endUploadNavigationLock() {
        if (window.samuelGlobalProgress && window.samuelGlobalProgress.lockUploadNavigationEnded) {
            window.samuelGlobalProgress.lockUploadNavigationEnded();
        }
    }

    function completeJob(jobId, result) {
        activeJobId = null;
        return idbDelete(jobId).then(function () {
            idbDelete('current').catch(function () {});
            if (result.ok) {
                emit({ type: 'done', jobId: jobId, label: '¡Recuerdo guardado!' });
                endUploadNavigationLock();
                if (result.redirect) {
                    window.setTimeout(function () {
                        window.location.href = result.redirect;
                    }, 380);
                }
                return;
            }
            endUploadNavigationLock();
            emit({ type: 'error', jobId: jobId, message: result.message });
        });
    }

    function failJob(jobId, message) {
        activeJobId = null;
        endUploadNavigationLock();
        return idbDelete(jobId)
            .catch(function () {})
            .then(function () {
                return idbDelete('current');
            })
            .catch(function () {})
            .then(function () {
                emit({ type: 'error', jobId: jobId, message: message });
            });
    }

    function runJob(job) {
        activeJobId = job.id;
        job.status = 'uploading';
        return idbPut(job.id, job)
            .then(function () {
                return idbPut('current', { id: job.id, startedAt: Date.now() });
            })
            .then(function () {
                return runXhrUpload(job);
            })
            .then(function (result) {
                return completeJob(job.id, result);
            })
            .catch(function (err) {
                if (err && err.message === 'aborted') {
                    return;
                }
                return failJob(job.id, 'No se pudo publicar. Revisa la conexión e intenta otra vez.');
            });
    }

    function submitPublicationForm(form, files, onStart) {
        if (!files || !files.length) {
            return Promise.reject(new Error('missing_files'));
        }

        if (window.samuelGlobalProgress && window.samuelGlobalProgress.unlockUploadNavigation) {
            window.samuelGlobalProgress.unlockUploadNavigation();
        }

        var job = serializeJob(form, files);

        return idbPut(job.id, job)
            .then(function () {
                return idbPut('current', { id: job.id, startedAt: Date.now() });
            })
            .then(function () {
                if (typeof onStart === 'function') {
                    onStart();
                }
                return runJob(job);
            });
    }

    function resumeIfNeeded() {
        return idbGet('current').then(function (current) {
            if (!current || !current.id) {
                return;
            }
            if (activeJobId === current.id) {
                return;
            }
            return idbGet(current.id).then(function (job) {
                if (!job || job.status === 'done') {
                    return idbDelete('current');
                }
                if (job.status === 'uploading') {
                    emit({
                        type: 'start',
                        jobId: job.id,
                        percent: 12,
                        label: 'Subiendo recuerdo…',
                    });
                    notifyServiceWorker(job.id, 'SAMUEL_UPLOAD_TAKEOVER');
                    return;
                }
                return runJob(job);
            });
        });
    }

    function onUploadEvent(data) {
        if (data.type === 'progress' || data.type === 'start' || data.type === 'done' || data.type === 'error') {
            emit(data);
        }
        if (data.type === 'done' && data.redirect && !activeJobId) {
            window.setTimeout(function () {
                window.location.href = data.redirect;
            }, 420);
        }
    }

    if (broadcast) {
        broadcast.onmessage = function (ev) {
            onUploadEvent(ev.data || {});
        };
    }

    if (navigator.serviceWorker) {
        navigator.serviceWorker.addEventListener('message', function (ev) {
            onUploadEvent(ev.data || {});
        });
    }

    window.addEventListener('pagehide', function () {
        if (!activeJobId || !xhrRef) {
            return;
        }
        try {
            xhrRef.abort();
        } catch (e) {}
        notifyServiceWorker(activeJobId, 'SAMUEL_UPLOAD_TAKEOVER');
    });

    window.samuelBackgroundUpload = {
        submitPublicationForm: submitPublicationForm,
        resumeIfNeeded: resumeIfNeeded,
        isActive: function () {
            return activeJobId !== null;
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', resumeIfNeeded);
    } else {
        resumeIfNeeded();
    }
})();
