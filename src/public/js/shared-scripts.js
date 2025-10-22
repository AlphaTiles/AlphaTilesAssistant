(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('#formAddItems').forEach(function(form) {
            var message = form.dataset.addItemsMessage || "Make sure you only include a single column of data.";
            form.addEventListener('submit', function(event) {
                var textEl = form.querySelector('#txtAddItems');
                var text = textEl ? textEl.value : '';
                var lines = text.split('\n');
                var pattern = /[\t,;]/;
                var hasMultipleColumns = lines.some(function(line) { return pattern.test(line); });

                if (hasMultipleColumns) {
                    event.preventDefault();
                    Swal.fire({
                        title: 'Are you sure?',
                        html: message,
                        showCancelButton: true,
                        confirmButtonText: 'Yes, proceed!',
                        cancelButtonText: 'Cancel',
                        allowOutsideClick: false,
                    }).then(function(result) {
                        if (result.isConfirmed) form.submit();
                    });
                }
            });
        });
    });
})();