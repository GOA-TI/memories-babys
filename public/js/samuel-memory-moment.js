(function () {
    'use strict';

    var form = document.getElementById('samuel-upload-form') || document.querySelector('form.samuel-upload-form');
    var custom = document.getElementById('memory-taken-custom');
    if (!form || !custom) {
        return;
    }

    var dateInput = form.querySelector('#memory_date');
    var timeInput = form.querySelector('#memory_time');
    var noteEl = form.querySelector('.samuel-memory-when__note');

    function syncMemoryTakenFields() {
        var taken = form.querySelector('input[name="memory_taken"]:checked');
        var other = taken && taken.value === 'other';

        custom.hidden = !other;

        if (dateInput) {
            dateInput.disabled = !other;
            dateInput.required = other;
        }
        if (timeInput) {
            timeInput.disabled = !other;
        }
        if (noteEl) {
            noteEl.hidden = !other;
        }
    }

    form.querySelectorAll('input[name="memory_taken"]').forEach(function (radio) {
        radio.addEventListener('change', syncMemoryTakenFields);
    });

    syncMemoryTakenFields();
})();
