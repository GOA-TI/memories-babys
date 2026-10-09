(function () {
    'use strict';

    var bar = null;
    var fill = null;
    var labelEl = null;
    var pulseTimer = null;
    var visible = false;
    var mode = 'indeterminate';
    var generation = 0;

    function nodes() {
        if (!bar) {
            bar = document.getElementById('samuelGlobalProgress');
            fill = document.getElementById('samuelGlobalProgressFill');
            labelEl = document.getElementById('samuelGlobalProgressLabel');
        }

        return { bar: bar, fill: fill, labelEl: labelEl };
    }

    function clearPulse() {
        if (pulseTimer) {
            window.clearInterval(pulseTimer);
            pulseTimer = null;
        }
    }

    function setFillPct(pct) {
        var n = nodes();
        if (!n.fill) {
            return;
        }
        var clamped = Math.max(0, Math.min(100, pct));
        n.fill.style.width = clamped + '%';
        n.fill.style.opacity = '1';
    }

    function showBar(kind) {
        var n = nodes();
        if (!n.bar) {
            return;
        }
        n.bar.hidden = false;
        n.bar.setAttribute('aria-hidden', 'false');
        n.bar.classList.toggle('samuel-global-progress--upload', kind === 'upload');
        document.documentElement.classList.add('samuel-global-progress-active');
        visible = true;
    }

    function hideBar() {
        var n = nodes();
        if (!n.bar) {
            return;
        }
        n.bar.hidden = true;
        n.bar.setAttribute('aria-hidden', 'true');
        n.bar.classList.remove('samuel-global-progress--upload');
        document.documentElement.classList.remove('samuel-global-progress-active');
        visible = false;
        clearPulse();
    }

    function start(options) {
        options = options || {};
        generation += 1;
        showBar(options.kind || (options.upload ? 'upload' : 'default'));
        mode = options.determinate ? 'determinate' : 'indeterminate';
        if (nodes().labelEl) {
            nodes().labelEl.textContent = options.label || 'Cargando…';
        }
        clearPulse();
        if (mode === 'indeterminate') {
            setFillPct(8);
            var dir = 1;
            var pos = 8;
            pulseTimer = window.setInterval(function () {
                pos += dir * 4;
                if (pos >= 92) {
                    dir = -1;
                }
                if (pos <= 8) {
                    dir = 1;
                }
                setFillPct(pos);
            }, 120);
        } else {
            setFillPct(typeof options.percent === 'number' ? options.percent : 0);
        }
    }

    function setProgress(percent, label) {
        mode = 'determinate';
        clearPulse();
        setFillPct(percent);
        if (label && nodes().labelEl) {
            nodes().labelEl.textContent = label;
        }
    }

    function setLabel(text) {
        if (nodes().labelEl && text) {
            nodes().labelEl.textContent = text;
        }
    }

    function finish(options) {
        options = options || {};
        var finishGen = generation;
        clearPulse();
        mode = 'determinate';
        setFillPct(100);
        if (options.label && nodes().labelEl) {
            nodes().labelEl.textContent = options.label;
        }
        window.setTimeout(function () {
            if (finishGen !== generation) {
                return;
            }
            hideBar();
        }, options.delayMs != null ? options.delayMs : 450);
    }

    function fail(message) {
        clearPulse();
        if (nodes().labelEl) {
            nodes().labelEl.textContent = message || 'No se pudo completar';
        }
        if (nodes().fill) {
            nodes().fill.style.background = '#ef4444';
        }
        window.setTimeout(function () {
            if (nodes().fill) {
                nodes().fill.style.background = '';
            }
            hideBar();
        }, 3200);
    }

    function unlockUploadNavigation() {
        document.body.classList.remove('samuel-upload-preview-open', 'samuel-upload-publishing');
        document.body.classList.add('samuel-upload-background-active');

        ['samuelUploadPreviewBackdrop', 'samuelUploadPreview'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) {
                return;
            }
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
        });

        var legacy = document.getElementById('samuelUploadPublishing');
        if (legacy) {
            legacy.remove();
        }
    }

    function lockUploadNavigationEnded() {
        document.body.classList.remove('samuel-upload-background-active');
    }

    window.samuelGlobalProgress = {
        start: start,
        setProgress: setProgress,
        setLabel: setLabel,
        finish: finish,
        fail: fail,
        isVisible: function () {
            return visible;
        },
        unlockUploadNavigation: unlockUploadNavigation,
        lockUploadNavigationEnded: lockUploadNavigationEnded,
    };
})();
