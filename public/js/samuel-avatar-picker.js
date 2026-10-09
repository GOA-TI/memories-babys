(function () {
    'use strict';

    var inputs = document.querySelectorAll('[data-samuel-avatar-input]');
    if (!inputs.length) {
        return;
    }

    inputs.forEach(function (input) {
        var form = input.closest('form');
        if (!form) {
            return;
        }

        var root = form.querySelector('[data-avatar-preview-root]');
        var emojiInput = form.querySelector('[data-samuel-avatar-emoji-input], #avatar_emoji');
        var nameEl = form.querySelector('[data-samuel-avatar-filename]');
        var removeCheckbox = form.querySelector('input[name="remove_avatar_photo"]');
        var noteEl = form.querySelector('[data-samuel-avatar-preview-note]');
        var objectUrl = null;

        function setNote(text) {
            if (!noteEl) {
                return;
            }
            if (text) {
                noteEl.textContent = text;
                noteEl.hidden = false;
            } else {
                noteEl.textContent = '';
                noteEl.hidden = true;
            }
        }

        function hasSelectedFile() {
            return !!(input.files && input.files.length);
        }

        function isHeicFile(file) {
            if (!file) {
                return false;
            }
            var name = (file.name || '').toLowerCase();
            var type = (file.type || '').toLowerCase();

            return /\.(heic|heif)$/.test(name) || type.indexOf('heic') !== -1 || type.indexOf('heif') !== -1;
        }

        function defaultEmoji() {
            if (emojiInput && emojiInput.value) {
                return emojiInput.value;
            }

            return '🙂';
        }

        function clearObjectUrl() {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
        }

        function renderEmoji(emoji) {
            if (!root) {
                return;
            }
            clearObjectUrl();
            root.innerHTML =
                '<span class="samuel-member-avatar samuel-member-avatar--lg samuel-member-avatar--emoji" aria-hidden="true">' +
                (emoji || '🙂') +
                '</span>';
        }

        function renderPhoto(file) {
            if (!root || !file) {
                return;
            }
            clearObjectUrl();
            setNote('');

            if (isHeicFile(file)) {
                renderEmoji('📷');
                setNote('Vista previa limitada en iPhone; al guardar sí se sube la foto.');
                return;
            }

            objectUrl = URL.createObjectURL(file);
            var img = document.createElement('img');
            img.alt = '';
            img.className =
                'samuel-member-avatar samuel-member-avatar--lg samuel-member-avatar--photo samuel-avatar-preview__img';
            img.src = objectUrl;
            img.addEventListener('error', function () {
                renderEmoji('📷');
                setNote('No pudimos previsualizar; al guardar intentamos subirla igual.');
            });
            root.innerHTML = '';
            root.appendChild(img);
        }

        function syncFilename(file) {
            if (!nameEl) {
                return;
            }
            if (file && file.name) {
                nameEl.textContent = file.name;
                nameEl.hidden = false;
            } else {
                nameEl.textContent = '';
                nameEl.hidden = true;
            }
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (removeCheckbox) {
                removeCheckbox.checked = false;
            }
            if (!file) {
                syncFilename(null);
                setNote('');
                if (!removeCheckbox || !removeCheckbox.checked) {
                    renderEmoji(defaultEmoji());
                }
                return;
            }
            var looksLikeImage =
                (file.type && file.type.startsWith('image/')) || /\.(jpe?g|png|webp|gif|heic|heif)$/i.test(file.name || '');
            if (!looksLikeImage) {
                syncFilename(null);
                window.alert('Elige una imagen (JPG, PNG o similar).');
                input.value = '';
                return;
            }
            renderPhoto(file);
            syncFilename(file);
        });

        if (emojiInput) {
            emojiInput.addEventListener('input', function () {
                if (hasSelectedFile()) {
                    return;
                }
                setNote('');
                renderEmoji(defaultEmoji());
            });
        }

        if (removeCheckbox) {
            removeCheckbox.addEventListener('change', function () {
                if (removeCheckbox.checked) {
                    input.value = '';
                    syncFilename(null);
                    setNote('');
                    renderEmoji(defaultEmoji());
                }
            });
        }

        if (!hasSelectedFile() && (!removeCheckbox || !removeCheckbox.checked)) {
            if (root && root.querySelector('img')) {
                return;
            }
            renderEmoji(defaultEmoji());
        }
    });
})();
