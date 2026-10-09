(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function avatarHtml(author) {
        if (author.photo_url) {
            return (
                '<img src="' +
                escapeHtml(author.photo_url) +
                '" alt="" class="samuel-member-avatar samuel-member-avatar--xs samuel-member-avatar--photo" loading="lazy">'
            );
        }

        return (
            '<span class="samuel-member-avatar samuel-member-avatar--xs samuel-member-avatar--emoji">' +
            escapeHtml(author.emoji || '🙂') +
            '</span>'
        );
    }

    function commentItemHtml(comment) {
        var deleteBtn = comment.can_delete
            ? '<button type="button" class="samuel-wall-comments__delete" data-samuel-comment-delete data-comment-id="' +
              comment.id +
              '" aria-label="Eliminar tu comentario"><i class="ri-close-line" aria-hidden="true"></i></button>'
            : '';

        return (
            '<li class="samuel-wall-comments__item" data-samuel-comment-id="' +
            comment.id +
            '">' +
            '<div class="samuel-wall-comments__avatar" aria-hidden="true">' +
            avatarHtml(comment.author || {}) +
            '</div>' +
            '<div class="samuel-wall-comments__bubble">' +
            '<p class="samuel-wall-comments__meta">' +
            '<span class="samuel-wall-comments__name">' +
            escapeHtml(comment.author?.name || 'Familia') +
            '</span>' +
            '<span class="samuel-wall-comments__when">' +
            escapeHtml(comment.created_at_human || '') +
            '</span>' +
            '</p>' +
            '<p class="samuel-wall-comments__body">' +
            escapeHtml(comment.body || '') +
            '</p>' +
            '</div>' +
            deleteBtn +
            '</li>'
        );
    }

    function renderComments(section, comments) {
        var list = section.querySelector('[data-samuel-comments-list]');
        if (!list) {
            return;
        }

        if (!comments || comments.length === 0) {
            list.innerHTML = '';
            list.hidden = true;
            return;
        }

        list.hidden = false;
        list.innerHTML = comments.map(commentItemHtml).join('');
    }

    function postForm(form) {
        var body = new FormData(form);
        if (token && !body.has('_token')) {
            body.append('_token', token);
        }

        return fetch(form.action, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (res) {
            if (!res.ok) {
                throw new Error('request failed');
            }
            return res.json();
        });
    }

    function destroyComment(section, commentId) {
        var template = section.getAttribute('data-samuel-comment-destroy-url') || '';
        var url = template.replace('__ID__', String(commentId));

        return fetch(url, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        }).then(function (res) {
            if (!res.ok) {
                throw new Error('request failed');
            }
            return res.json();
        });
    }

    function syncSendButton(form) {
        var input = form.querySelector('[data-samuel-comment-input]');
        var send = form.querySelector('[data-samuel-comment-send]');
        if (!input || !send) {
            return;
        }
        send.disabled = !String(input.value || '').trim();
    }

    document.querySelectorAll('[data-samuel-comment-form]').forEach(function (form) {
        var input = form.querySelector('[data-samuel-comment-input]');
        if (input) {
            input.addEventListener('input', function () {
                syncSendButton(form);
            });
            syncSendButton(form);
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.hasAttribute('data-samuel-comment-form')) {
            return;
        }

        event.preventDefault();
        var section = form.closest('[data-samuel-wall-comments]');
        var input = form.querySelector('[data-samuel-comment-input]');
        var send = form.querySelector('[data-samuel-comment-send]');
        if (!section || !input || !String(input.value || '').trim()) {
            return;
        }

        if (send) {
            send.disabled = true;
        }

        postForm(form)
            .then(function (data) {
                renderComments(section, data.comments || []);
                input.value = '';
                syncSendButton(form);
                var list = section.querySelector('[data-samuel-comments-list]');
                if (list && !list.hidden) {
                    list.scrollTop = list.scrollHeight;
                }
            })
            .catch(function () {
                form.submit();
            })
            .finally(function () {
                syncSendButton(form);
            });
    });

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-samuel-comment-delete]');
        if (!btn) {
            return;
        }

        var section = btn.closest('[data-samuel-wall-comments]');
        var commentId = btn.getAttribute('data-comment-id');
        if (!section || !commentId) {
            return;
        }

        btn.disabled = true;
        destroyComment(section, commentId)
            .then(function (data) {
                renderComments(section, data.comments || []);
            })
            .catch(function () {
                btn.disabled = false;
            });
    });
})();
