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

    function replyToLine(comment) {
        if (!comment.reply_to_name) {
            return '';
        }

        return (
            '<p class="samuel-wall-comments__reply-to">Respondiendo a <strong>' +
            escapeHtml(comment.reply_to_name) +
            '</strong></p>'
        );
    }

    function replyButtonHtml(comment) {
        var name = comment.author?.name || 'Familia';

        return (
            '<button type="button" class="samuel-wall-comments__reply" data-samuel-comment-reply data-comment-id="' +
            comment.id +
            '" data-author-name="' +
            escapeHtml(name) +
            '">Responder</button>'
        );
    }

    function singleCommentItemHtml(comment, isReply) {
        var deleteBtn = comment.can_delete
            ? '<button type="button" class="samuel-wall-comments__delete" data-samuel-comment-delete data-comment-id="' +
              comment.id +
              '" aria-label="Eliminar tu comentario"><i class="ri-close-line" aria-hidden="true"></i></button>'
            : '';
        var itemClass = isReply
            ? 'samuel-wall-comments__item samuel-wall-comments__item--reply'
            : 'samuel-wall-comments__item';

        return (
            '<li class="' +
            itemClass +
            '" data-samuel-comment-id="' +
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
            replyToLine(comment) +
            '<p class="samuel-wall-comments__body">' +
            escapeHtml(comment.body || '') +
            '</p>' +
            replyButtonHtml(comment) +
            '</div>' +
            deleteBtn +
            '</li>'
        );
    }

    function commentThreadHtml(thread) {
        var html = singleCommentItemHtml(thread, false);
        var replies = thread.replies || [];
        for (var i = 0; i < replies.length; i++) {
            html += singleCommentItemHtml(replies[i], true);
        }

        return html;
    }

    function renderComments(section, comments) {
        var list = section.querySelector('[data-samuel-comments-list]');
        var thread = section.querySelector('[data-samuel-comments-thread]');
        if (!list) {
            return;
        }

        if (!comments || comments.length === 0) {
            list.innerHTML = '';
            list.hidden = true;
            if (thread) {
                thread.hidden = true;
            }
            syncToggle(section, 0);
            return;
        }

        if (thread) {
            thread.hidden = false;
        }
        list.hidden = false;
        list.innerHTML = comments.map(commentThreadHtml).join('');
    }

    function syncToggle(section, count) {
        section.setAttribute('data-samuel-comments-count', String(count));
        var toggle = section.querySelector('[data-samuel-comments-toggle]');
        var list = section.querySelector('[data-samuel-comments-list]');
        var thread = section.querySelector('[data-samuel-comments-thread]');

        if (count <= 0) {
            if (toggle) {
                toggle.remove();
            }
            return;
        }

        if (!toggle) {
            toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'samuel-wall-comments__toggle';
            toggle.setAttribute('data-samuel-comments-toggle', '');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.innerHTML =
                '<i class="ri-chat-3-line" aria-hidden="true"></i>' +
                '<span data-samuel-comments-toggle-label></span>' +
                '<i class="ri-arrow-up-s-line samuel-wall-comments__toggle-chevron" aria-hidden="true"></i>';
            section.insertBefore(toggle, section.firstChild);
        }

        var expanded = toggle.getAttribute('aria-expanded') !== 'false';
        var label = toggle.querySelector('[data-samuel-comments-toggle-label]');
        if (label) {
            label.textContent = expanded
                ? 'Ocultar comentarios (' + count + ')'
                : 'Ver comentarios (' + count + ')';
        }

        var chevron = toggle.querySelector('.samuel-wall-comments__toggle-chevron');
        if (chevron) {
            chevron.classList.toggle('ri-arrow-up-s-line', expanded);
            chevron.classList.toggle('ri-arrow-down-s-line', !expanded);
        }

        if (thread) {
            thread.hidden = !expanded;
        }
        if (list && expanded) {
            list.hidden = false;
        }
    }

    function applyCommentsResponse(section, data) {
        var count = typeof data.count === 'number' ? data.count : (data.comments || []).length;
        renderComments(section, data.comments || []);
        syncToggle(section, count);
    }

    function clearReplyState(section) {
        var form = section.querySelector('[data-samuel-comment-form]');
        if (!form) {
            return;
        }
        var parentInput = form.querySelector('[data-samuel-comment-parent]');
        var input = form.querySelector('[data-samuel-comment-input]');
        var cancelBtn = form.querySelector('[data-samuel-comment-cancel-reply]');
        var hint = section.querySelector('[data-samuel-comment-reply-hint]');
        if (parentInput) {
            parentInput.value = '';
        }
        if (input) {
            input.placeholder = 'Escribe algo lindo…';
        }
        if (cancelBtn) {
            cancelBtn.hidden = true;
        }
        if (hint) {
            hint.hidden = true;
            hint.textContent = '';
        }
    }

    function startReply(section, commentId, authorName) {
        var form = section.querySelector('[data-samuel-comment-form]');
        if (!form) {
            return;
        }
        var parentInput = form.querySelector('[data-samuel-comment-parent]');
        var input = form.querySelector('[data-samuel-comment-input]');
        var cancelBtn = form.querySelector('[data-samuel-comment-cancel-reply]');
        var hint = section.querySelector('[data-samuel-comment-reply-hint]');
        if (parentInput) {
            parentInput.value = String(commentId);
        }
        if (input) {
            input.placeholder = 'Responder a ' + authorName + '…';
            input.focus();
        }
        if (cancelBtn) {
            cancelBtn.hidden = false;
        }
        if (hint) {
            hint.hidden = false;
            hint.textContent = 'Respondiendo a ' + authorName;
        }

        var toggle = section.querySelector('[data-samuel-comments-toggle]');
        if (toggle && toggle.getAttribute('aria-expanded') === 'false') {
            toggle.setAttribute('aria-expanded', 'true');
            var thread = section.querySelector('[data-samuel-comments-thread]');
            var list = section.querySelector('[data-samuel-comments-list]');
            if (thread) {
                thread.hidden = false;
            }
            if (list) {
                list.hidden = false;
            }
            syncToggle(section, parseInt(section.getAttribute('data-samuel-comments-count') || '0', 10));
        }
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

    document.querySelectorAll('[data-samuel-wall-comments]').forEach(function (section) {
        var count = parseInt(section.getAttribute('data-samuel-comments-count') || '0', 10);
        syncToggle(section, count);
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
                applyCommentsResponse(section, data);
                input.value = '';
                clearReplyState(section);
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
        var toggle = event.target.closest('[data-samuel-comments-toggle]');
        if (toggle) {
            var section = toggle.closest('[data-samuel-wall-comments]');
            if (!section) {
                return;
            }
            var expanded = toggle.getAttribute('aria-expanded') !== 'false';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            var count = parseInt(section.getAttribute('data-samuel-comments-count') || '0', 10);
            syncToggle(section, count);
            return;
        }

        var replyBtn = event.target.closest('[data-samuel-comment-reply]');
        if (replyBtn) {
            var sectionReply = replyBtn.closest('[data-samuel-wall-comments]');
            var commentId = replyBtn.getAttribute('data-comment-id');
            var authorName = replyBtn.getAttribute('data-author-name') || 'Familia';
            if (sectionReply && commentId) {
                startReply(sectionReply, commentId, authorName);
            }
            return;
        }

        var cancelReply = event.target.closest('[data-samuel-comment-cancel-reply]');
        if (cancelReply) {
            var sectionCancel = cancelReply.closest('[data-samuel-wall-comments]');
            if (sectionCancel) {
                clearReplyState(sectionCancel);
            }
            return;
        }

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
                applyCommentsResponse(section, data);
            })
            .catch(function () {
                btn.disabled = false;
            });
    });
})();
