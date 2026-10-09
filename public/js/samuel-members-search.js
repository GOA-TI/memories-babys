(function () {
    'use strict';

    var input = document.getElementById('samuelMembersSearch');
    var list = document.getElementById('samuelMembersList');
    if (!input || !list) {
        return;
    }

    var rows = list.querySelectorAll('[data-member-search]');
    var emptyEl = document.getElementById('samuelMembersSearchEmpty');
    var countEl = document.getElementById('samuelMembersCount');
    var clearBtn = document.getElementById('samuelMembersSearchClear');
    var total = countEl ? parseInt(countEl.getAttribute('data-total') || '0', 10) : rows.length;

    function stripAccents(text) {
        return (text || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function digitsOnly(text) {
        return (text || '').replace(/\D+/g, '');
    }

    function applyFilter() {
        var raw = input.value.trim();
        var qText = stripAccents(raw);
        var qDigits = digitsOnly(raw);

        var visible = 0;
        rows.forEach(function (row) {
            var hay = row.getAttribute('data-member-search') || '';
            var hayText = stripAccents(hay);
            var hayDigits = digitsOnly(hay);
            var match =
                qText === '' ||
                hayText.indexOf(qText) !== -1 ||
                (qDigits.length >= 2 && hayDigits.indexOf(qDigits) !== -1);

            row.classList.toggle('is-search-hidden', !match);
            if (match) {
                visible += 1;
            }
        });

        if (emptyEl) {
            emptyEl.hidden = qText === '' || visible > 0;
        }

        if (countEl) {
            if (qText === '') {
                countEl.textContent = total + (total === 1 ? ' persona' : ' personas');
            } else {
                countEl.textContent = visible + ' de ' + total;
            }
        }

        if (clearBtn) {
            clearBtn.hidden = raw === '';
        }
    }

    input.addEventListener('input', applyFilter);

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            input.value = '';
            applyFilter();
            input.focus();
        });
    }
})();
