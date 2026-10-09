(function () {
    var sheet = document.getElementById('samuelReactionsWhoSheet');
    var backdrop = document.getElementById('samuelReactionsWhoBackdrop');
    var listEl = document.getElementById('samuelReactionsWhoList');
    var emptyEl = document.getElementById('samuelReactionsWhoEmpty');
    var closeBtn = document.getElementById('samuelReactionsWhoClose');

    if (!sheet || !listEl) {
        return;
    }

    function parsePeople(raw) {
        if (!raw) {
            return [];
        }
        try {
            var data = JSON.parse(raw);
            return Array.isArray(data) ? data : [];
        } catch (e) {
            return [];
        }
    }

    function renderPeople(people) {
        listEl.innerHTML = '';
        if (!people.length) {
            emptyEl.hidden = false;
            return;
        }
        emptyEl.hidden = true;
        people.forEach(function (person) {
            var li = document.createElement('li');
            li.className = 'samuel-reactions-who-list__item';

            var emoji = document.createElement('span');
            emoji.className = 'samuel-reactions-who-list__emoji';
            emoji.setAttribute('aria-hidden', 'true');
            emoji.textContent = person.emoji || '✨';

            var meta = document.createElement('div');
            meta.className = 'samuel-reactions-who-list__meta';

            var name = document.createElement('strong');
            name.className = 'samuel-reactions-who-list__name';
            name.textContent = person.name || 'Familia';

            meta.appendChild(name);

            var sub = document.createElement('span');
            sub.className = 'samuel-reactions-who-list__sub';
            var bits = [];
            if (person.label) {
                bits.push(person.label);
            }
            if (person.relationship) {
                bits.push(person.relationship);
            }
            sub.textContent = bits.join(' · ');
            if (bits.length) {
                meta.appendChild(sub);
            }

            li.appendChild(emoji);
            li.appendChild(meta);
            listEl.appendChild(li);
        });
    }

    function openSheet(people) {
        renderPeople(people);
        sheet.classList.add('is-open');
        sheet.setAttribute('aria-hidden', 'false');
        if (backdrop) {
            backdrop.classList.add('is-open');
            backdrop.setAttribute('aria-hidden', 'false');
        }
        document.body.classList.add('samuel-reactions-who-open');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeSheet() {
        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
        if (backdrop) {
            backdrop.classList.remove('is-open');
            backdrop.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('samuel-reactions-who-open');
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-samuel-reactions-open-who]');
        if (!trigger) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var root = trigger.closest('[data-samuel-reactions]');
        if (!root) {
            return;
        }
        var people = parsePeople(root.getAttribute('data-samuel-reactions-people'));
        openSheet(people);
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSheet);
    }
    if (backdrop) {
        backdrop.addEventListener('click', closeSheet);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sheet.classList.contains('is-open')) {
            closeSheet();
        }
    });

    window.samuelUpdateReactionsPeople = function (root, people) {
        if (!root || !Array.isArray(people)) {
            return;
        }
        root.setAttribute('data-samuel-reactions-people', JSON.stringify(people));
    };
})();
