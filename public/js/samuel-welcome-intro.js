(function () {
    var sheet = document.getElementById('samuelWelcomeSheet');
    var backdrop = document.getElementById('samuelWelcomeBackdrop');
    var confettiRoot = document.getElementById('samuelWelcomeConfetti');
    if (!sheet || !backdrop) {
        return;
    }

    var seenUrl = sheet.getAttribute('data-welcome-seen-url');
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var closing = false;

    var confettiPieces = ['🎀', '✨', '💕', '🎉', '⭐', '💖', '🌸', '👶'];

    function burstConfetti() {
        if (!confettiRoot) {
            return;
        }
        confettiRoot.innerHTML = '';
        confettiRoot.classList.add('is-active');
        var count = 28;
        for (var i = 0; i < count; i++) {
            var piece = document.createElement('span');
            piece.className = 'samuel-welcome-confetti__piece';
            piece.textContent = confettiPieces[i % confettiPieces.length];
            piece.style.left = Math.random() * 100 + '%';
            piece.style.animationDelay = Math.random() * 0.45 + 's';
            piece.style.animationDuration = 2.2 + Math.random() * 1.4 + 's';
            piece.style.fontSize = 0.85 + Math.random() * 0.9 + 'rem';
            confettiRoot.appendChild(piece);
        }
        window.setTimeout(function () {
            confettiRoot.classList.remove('is-active');
        }, 3200);
    }

    function markSeen() {
        if (!seenUrl || closing) {
            return;
        }
        closing = true;
        fetch(seenUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        }).catch(function () {});
    }

    function openSheet() {
        backdrop.classList.add('is-open');
        sheet.classList.add('is-open');
        sheet.setAttribute('aria-hidden', 'false');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('samuel-modal-open');
        window.requestAnimationFrame(function () {
            sheet.classList.add('is-celebrate');
            burstConfetti();
        });
    }

    function closeSheet() {
        sheet.classList.remove('is-celebrate');
        backdrop.classList.remove('is-open');
        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('samuel-modal-open');
        markSeen();
    }

    var btnEnter = document.getElementById('samuelWelcomeEnter');
    var btnClose = document.getElementById('samuelWelcomeClose');
    if (btnEnter) {
        btnEnter.addEventListener('click', closeSheet);
    }
    if (btnClose) {
        btnClose.addEventListener('click', closeSheet);
    }
    backdrop.addEventListener('click', closeSheet);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sheet.classList.contains('is-open')) {
            closeSheet();
        }
    });

    openSheet();
})();
