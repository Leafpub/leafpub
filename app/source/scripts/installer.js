/* globals Nanobar, Postleaf */
$(function() {
    'use strict';

    var progress = new Nanobar();

    // Submit
    $('#installer-form').on('submit', function(event) {
        var form = this,
            url = $(form).attr('action');

        event.preventDefault();

        // Show progress
        progress.go(50);
        $.ajax({
            url: url,
            type: 'POST',
            data: $(form).serialize()
        })
        .done(function(res) {
            if(res.success) {
                location.href = res.redirect;
            }

            // Show errors
            if(res.invalid && res.invalid.length) {
                Postleaf.highlightErrors(form, res.invalid);
            }

            // Show message
            if(res.message) {
                $.alertable.alert(res.message, {
                    ok: 'OK',
                    cancel: 'Cancel'
                });
            }
        })
        .always(function() {
            // Hide progress
            progress.go(100);
        });
    });

    // Force slug syntax for username
    $('#username').on('change', function() {
        $(this).val(
            Postleaf.slug($(this).val())
        );
    });

    // Strip invalid chars from table prefix
    $('#db-prefix').on('change', function() {
        $(this).val(
            $(this).val().replace(/[^A-Za-z_-]/g, '_')
        );
    });
});