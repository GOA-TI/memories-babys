(function () {
    'use strict';

    var config = window.samuelNotificationsConfig || {};

    function SamuelNotificationManager(cfg) {
        this.config = cfg;
        this.badge = document.getElementById('samuel-notification-badge');
        this.list = document.getElementById('samuel-notifications-list');
        this.pushHint = document.getElementById('samuel-push-hint');
        this.pushWrap = document.getElementById('samuel-notif-push-wrap');
        this.enablePushBtn = document.getElementById('samuel-enable-push');
        this.retryPushBtn = document.getElementById('samuel-retry-push-sw');
        this.markAllBtn = document.getElementById('samuelNotifMarkAll');
        this.vapidPublicKey = null;
        this.pushConfigured = false;
        this.swRegistration = null;
        this.swRegisterError = null;
    }

    SamuelNotificationManager.prototype.csrfToken = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    SamuelNotificationManager.prototype.escapeHtml = function (text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    };

    SamuelNotificationManager.prototype.escapeAttr = function (text) {
        return this.escapeHtml(text).replace(/"/g, '&quot;');
    };

    SamuelNotificationManager.prototype.getTimeAgo = function (iso) {
        if (!iso) {
            return '';
        }
        var then = new Date(iso).getTime();
        if (Number.isNaN(then)) {
            return '';
        }
        var diff = Math.max(0, Date.now() - then);
        var mins = Math.floor(diff / 60000);
        if (mins < 1) {
            return 'Ahora';
        }
        if (mins < 60) {
            return mins === 1 ? 'Hace 1 min' : 'Hace ' + mins + ' min';
        }
        var hours = Math.floor(mins / 60);
        if (hours < 24) {
            return hours === 1 ? 'Hace 1 h' : 'Hace ' + hours + ' h';
        }
        var days = Math.floor(hours / 24);
        return days === 1 ? 'Ayer' : 'Hace ' + days + ' días';
    };

    SamuelNotificationManager.prototype.registerServiceWorker = function (options) {
        var self = this;
        options = options || {};
        if (!('serviceWorker' in navigator)) {
            return Promise.resolve(null);
        }
        if (this.swRegistration && !options.force) {
            return Promise.resolve(this.swRegistration);
        }
        var swUrl = this.config.swUrl || '/sw.js';
        return navigator.serviceWorker.register(swUrl, { scope: '/' }).then(function (reg) {
            self.swRegistration = reg;
            self.swRegisterError = null;
            return reg;
        }).catch(function (err) {
            self.swRegisterError = err;
            return null;
        });
    };

    SamuelNotificationManager.prototype.loadNotifications = function () {
        var self = this;
        var url = this.config.indexUrl;
        if (!url) {
            return Promise.resolve();
        }
        return fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) {
                    return;
                }
                self.updateBadge(data.unread_count || 0);
                self.renderList(data.notifications || []);
                self.pushConfigured = !!data.push_configured;
                self.vapidPublicKey = data.vapid_public_key || null;
                self.updatePushUi();
            })
            .catch(function (err) {
                console.warn('Samuel notificaciones:', err);
            });
    };

    SamuelNotificationManager.prototype.updateBadge = function (count) {
        if (!this.badge) {
            return;
        }
        if (count > 0) {
            this.badge.textContent = count > 99 ? '99+' : String(count);
            this.badge.hidden = false;
        } else {
            this.badge.hidden = true;
        }
    };

    SamuelNotificationManager.prototype.renderList = function (notifications) {
        if (!this.list) {
            return;
        }
        if (!notifications.length) {
            this.list.innerHTML =
                '<p class="cp-mobile-notif-empty">No hay avisos todavía. Te avisaremos cuando alguien publique, comente o reaccione.</p>';
            return;
        }
        var self = this;
        this.list.innerHTML = notifications
            .map(function (n) {
                var thumb = n.image_url
                    ? '<span class="cp-mobile-notif-item__thumb"><img src="' +
                      self.escapeAttr(n.image_url) +
                      '" alt="" loading="lazy"></span>'
                    : '<i class="ri-camera-lens-line"></i>';
                var href = self.escapeAttr(n.url || '#');
                return (
                    '<a href="' +
                    href +
                    '" class="cp-mobile-notif-item ' +
                    (n.is_unread ? 'is-unread' : '') +
                    (n.image_url ? ' has-thumb' : '') +
                    '" data-notif-id="' +
                    n.id +
                    '">' +
                    thumb +
                    '<span><strong>' +
                    self.escapeHtml(n.title) +
                    '</strong><span class="cp-mobile-notif-item__body">' +
                    self.escapeHtml(n.body) +
                    '</span><span class="cp-mobile-notif-item__time">' +
                    self.getTimeAgo(n.created_at) +
                    '</span></span></a>'
                );
            })
            .join('');

        this.list.querySelectorAll('a[data-notif-id]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                self.followLink(e, link.getAttribute('data-notif-id'));
            });
        });
    };

    SamuelNotificationManager.prototype.markReadUrl = function (id) {
        return (this.config.markReadUrl || '').replace('__ID__', String(id));
    };

    SamuelNotificationManager.prototype.followLink = function (event, id) {
        var url = this.markReadUrl(id);
        if (!url) {
            return;
        }
        event.preventDefault();
        var target = event.currentTarget.getAttribute('href') || '/';
        fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrfToken(),
            },
            credentials: 'same-origin',
        }).finally(function () {
            window.location.href = target;
        });
    };

    SamuelNotificationManager.prototype.markAllAsRead = function () {
        var url = this.config.markAllReadUrl;
        if (!url) {
            return;
        }
        var self = this;
        fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrfToken(),
            },
            credentials: 'same-origin',
        })
            .then(function () {
                return self.loadNotifications();
            })
            .catch(function () {});
    };

    SamuelNotificationManager.prototype.isIos = function () {
        return (
            /iPad|iPhone|iPod/.test(navigator.userAgent) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
        );
    };

    SamuelNotificationManager.prototype.isStandalonePwa = function () {
        return (
            window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true
        );
    };

    SamuelNotificationManager.prototype.browserPushSupport = function () {
        if (!window.isSecureContext) {
            return { ok: false, reason: 'secure' };
        }
        if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            return { ok: false, reason: 'unsupported' };
        }
        if (this.isIos() && !this.isStandalonePwa()) {
            return { ok: false, reason: 'ios_install' };
        }
        return { ok: true };
    };

    SamuelNotificationManager.prototype.setPushHint = function (text, visible) {
        if (!this.pushHint) {
            return;
        }
        if (!visible || !text) {
            this.pushHint.hidden = true;
            this.pushHint.textContent = '';
            return;
        }
        this.pushHint.hidden = false;
        this.pushHint.textContent = text;
    };

    SamuelNotificationManager.prototype.updatePushUi = function () {
        if (!this.pushWrap) {
            return;
        }
        if (Notification.permission === 'granted') {
            this.pushWrap.hidden = true;
            this.setPushHint('', false);
            return;
        }

        var support = this.browserPushSupport();
        this.pushWrap.hidden = false;
        if (this.enablePushBtn) {
            this.enablePushBtn.hidden = true;
        }
        if (this.retryPushBtn) {
            this.retryPushBtn.hidden = true;
        }

        if (!support.ok) {
            if (support.reason === 'ios_install') {
                this.setPushHint(
                    'En iPhone: añade Memorias a la pantalla de inicio, ábrela desde el icono y activa alertas aquí.',
                    true
                );
            } else if (support.reason === 'secure') {
                this.setPushHint('Las alertas push requieren HTTPS.', true);
            } else {
                this.setPushHint('Tu navegador no admite alertas push en este dispositivo.', true);
            }
            return;
        }

        if (!this.pushConfigured || !this.vapidPublicKey) {
            this.setPushHint(
                'Las alertas en el celular aún no están activadas en el servidor. La campana sigue mostrando avisos aquí.',
                true
            );
            return;
        }

        if (!this.swRegistration || !this.swRegistration.active) {
            this.setPushHint('Preparando alertas… espera unos segundos o pulsa reintentar.', true);
            if (this.retryPushBtn) {
                this.retryPushBtn.hidden = false;
            }
            return;
        }

        this.setPushHint('', false);
        if (Notification.permission === 'denied') {
            this.setPushHint('Permite notificaciones en ajustes del teléfono y recarga.', true);
            return;
        }
        if (this.enablePushBtn) {
            this.enablePushBtn.hidden = false;
        }
    };

    SamuelNotificationManager.prototype.urlBase64ToUint8Array = function (base64String) {
        var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = window.atob(base64);
        var output = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) {
            output[i] = raw.charCodeAt(i);
        }
        return output;
    };

    SamuelNotificationManager.prototype.enablePushNotifications = function () {
        var self = this;
        return this.registerServiceWorker()
            .then(function () {
                return self.loadNotifications();
            })
            .then(function () {
                if (!self.vapidPublicKey) {
                    return;
                }
                return Notification.requestPermission();
            })
            .then(function (perm) {
                if (perm !== 'granted' || !self.swRegistration || !self.vapidPublicKey) {
                    self.updatePushUi();
                    return;
                }
                return self.swRegistration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: self.urlBase64ToUint8Array(self.vapidPublicKey),
                });
            })
            .then(function (subscription) {
                if (!subscription) {
                    return;
                }
                var json = subscription.toJSON();
                return fetch(self.config.pushSubscribeUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': self.csrfToken(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        endpoint: json.endpoint,
                        keys: json.keys,
                        contentEncoding: 'aesgcm',
                    }),
                });
            })
            .then(function () {
                self.updatePushUi();
            })
            .catch(function (err) {
                console.warn('Samuel push:', err);
                self.updatePushUi();
            });
    };

    SamuelNotificationManager.prototype.init = function () {
        var self = this;
        this.registerServiceWorker().then(function () {
            return self.loadNotifications();
        });
        if (this.markAllBtn) {
            this.markAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                self.markAllAsRead();
            });
        }
        if (this.enablePushBtn) {
            this.enablePushBtn.addEventListener('click', function (e) {
                e.preventDefault();
                self.enablePushNotifications();
            });
        }
        if (this.retryPushBtn) {
            this.retryPushBtn.addEventListener('click', function (e) {
                e.preventDefault();
                self.registerServiceWorker({ force: true }).then(function () {
                    return self.loadNotifications();
                });
            });
        }
        window.setInterval(function () {
            self.loadNotifications();
        }, 30000);
    };

    if (!config.indexUrl) {
        return;
    }

    window.samuelNotificationManager = new SamuelNotificationManager(config);
    window.samuelNotificationManager.init();
})();
