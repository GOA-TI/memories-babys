/* Samuel Memorias — PWA (scope /) */
const SW_VERSION = 'samuel-memorias-v8';
const AVATAR_CACHE = 'samuel-avatar-images-v1';
const UPLOAD_DB = 'samuel-background-uploads';
const UPLOAD_STORE = 'jobs';
const UPLOAD_CHANNEL = 'samuel-upload-progress';

function openUploadDb() {
    return new Promise(function (resolve, reject) {
        var req = indexedDB.open(UPLOAD_DB, 1);
        req.onupgradeneeded = function () {
            if (!req.result.objectStoreNames.contains(UPLOAD_STORE)) {
                req.result.createObjectStore(UPLOAD_STORE);
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

function idbGetUpload(key) {
    return openUploadDb().then(function (db) {
        return new Promise(function (resolve, reject) {
            var tx = db.transaction(UPLOAD_STORE, 'readonly');
            var req = tx.objectStore(UPLOAD_STORE).get(key);
            req.onsuccess = function () {
                resolve(req.result || null);
            };
            req.onerror = function () {
                reject(req.error);
            };
        });
    });
}

function idbDeleteUpload(key) {
    return openUploadDb().then(function (db) {
        return new Promise(function (resolve, reject) {
            var tx = db.transaction(UPLOAD_STORE, 'readwrite');
            tx.objectStore(UPLOAD_STORE).delete(key);
            tx.oncomplete = function () {
                resolve();
            };
            tx.onerror = function () {
                reject(tx.error);
            };
        });
    });
}

function broadcastUpload(payload) {
    if (typeof BroadcastChannel === 'undefined') {
        return;
    }
    var channel = new BroadcastChannel(UPLOAD_CHANNEL);
    channel.postMessage(payload);
    channel.close();
}

function buildFormDataFromJob(job) {
    var fd = new FormData();
    (job.fields || []).forEach(function (pair) {
        fd.append(pair.name, pair.value);
    });
    (job.files || []).forEach(function (fileMeta) {
        fd.append(fileMeta.fieldName, fileMeta.blob, fileMeta.fileName || 'archivo');
    });

    return fd;
}

function runBackgroundUpload(jobId) {
    return idbGetUpload(jobId).then(function (job) {
        if (!job || job.status === 'done') {
            return;
        }

        job.status = 'uploading';
        broadcastUpload({
            type: 'start',
            jobId: jobId,
            percent: 15,
            label: 'Subiendo recuerdo…',
        });

        return fetch(job.action, {
            method: job.method || 'POST',
            body: buildFormDataFromJob(job),
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (res) {
            return res.text().then(function (text) {
                var json = null;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    json = null;
                }

                if (res.ok && json && json.ok) {
                    broadcastUpload({
                        type: 'done',
                        jobId: jobId,
                        label: '¡Recuerdo guardado!',
                        redirect: json.redirect,
                    });
                    return Promise.all([idbDeleteUpload(jobId), idbDeleteUpload('current')]);
                }

                var message =
                    (json && json.message) ||
                    'No se pudo publicar. Vuelve a la pantalla de subida e intenta otra vez.';
                broadcastUpload({ type: 'error', jobId: jobId, message: message });
                return Promise.all([idbDeleteUpload(jobId), idbDeleteUpload('current')]);
            });
        });
    });
}

self.addEventListener('message', function (event) {
    var data = event.data || {};
    if (data.type === 'SAMUEL_UPLOAD_TAKEOVER' && data.jobId) {
        event.waitUntil(runBackgroundUpload(data.jobId));
    }
});

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys
                    .filter(function (key) {
                        return key.startsWith('samuel-avatar-images-') && key !== AVATAR_CACHE;
                    })
                    .map(function (key) {
                        return caches.delete(key);
                    })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

function isAvatarRequest(url) {
    return /\/familia\/\d+\/avatar/.test(url.pathname);
}

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    var url = new URL(event.request.url);

    if (isAvatarRequest(url)) {
        event.respondWith(
            caches.open(AVATAR_CACHE).then(function (cache) {
                return cache.match(event.request).then(function (cached) {
                    if (cached) {
                        return cached;
                    }

                    return fetch(event.request).then(function (response) {
                        if (response && response.ok) {
                            cache.put(event.request, response.clone());
                        }

                        return response;
                    });
                });
            })
        );

        return;
    }

    event.respondWith(fetch(event.request));
});
