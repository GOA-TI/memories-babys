(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

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

    function updateReactions(root, data) {
        if (!root || !data || !data.reactions) {
            return;
        }
        var summary = root.querySelector('[data-samuel-reactions-summary]');
        var openWho = root.querySelector('[data-samuel-reactions-open-who]');
        var counts = data.reactions.counts || [];
        var mine = data.reactions.mine || null;
        var people = data.reactions.people || [];

        if (typeof window.samuelUpdateReactionsPeople === 'function') {
            window.samuelUpdateReactionsPeople(root, people);
        } else {
            root.setAttribute('data-samuel-reactions-people', JSON.stringify(people));
        }

        if (summary) {
            summary.innerHTML = '';
            counts.forEach(function (row) {
                var pill = document.createElement('span');
                pill.className = 'samuel-reactions__pill samuel-reactions__pill--summary';
                pill.title = row.label || row.emotion;
                pill.textContent = row.emoji + ' ' + row.count;
                summary.appendChild(pill);
            });
        }

        if (openWho) {
            openWho.hidden = counts.length === 0;
        }

        root.querySelectorAll('.samuel-reactions__btn').forEach(function (btn) {
            var form = btn.closest('form');
            var input = form ? form.querySelector('input[name="emotion"]') : null;
            var emotion = input ? input.value : null;
            btn.classList.toggle('is-active', mine && emotion === mine);
        });
    }

    function updateFavorite(form, data) {
        var btn = form.querySelector('[data-samuel-favorite-btn]');
        if (!btn || typeof data.is_favorite === 'undefined') {
            return;
        }
        var active = !!data.is_favorite;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-label', active ? 'Quitar favorito' : 'Marcar favorito');
        var icon = btn.querySelector('i');
        if (icon) {
            icon.className = active ? 'ri-heart-3-fill' : 'ri-heart-3-line';
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }

        if (form.classList.contains('samuel-reactions__form')) {
            event.preventDefault();
            var root = form.closest('[data-samuel-reactions]');
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
            }
            postForm(form)
                .then(function (data) {
                    updateReactions(root, data);
                })
                .catch(function () {
                    form.submit();
                })
                .finally(function () {
                    if (btn) {
                        btn.disabled = false;
                    }
                });
            return;
        }

        if (form.hasAttribute('data-samuel-favorite-form')) {
            event.preventDefault();
            var favBtn = form.querySelector('[data-samuel-favorite-btn]');
            if (favBtn) {
                favBtn.disabled = true;
            }
            postForm(form)
                .then(function (data) {
                    updateFavorite(form, data);
                })
                .catch(function () {
                    form.submit();
                })
                .finally(function () {
                    if (favBtn) {
                        favBtn.disabled = false;
                    }
                });
        }
    });
})();
