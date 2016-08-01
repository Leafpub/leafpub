/* globals Nanobar, Postleaf */
$(function() {
    'use strict';

    var progress = new Nanobar(),
        redirect = $('.login-form').attr('data-redirect');

    // Tooltip
    $('[data-toggle="tooltip"]').tooltip({
        trigger: 'hover'
    });

    // Submit
    $('.login-form').on('submit', function(event) {
        var form = this,
            url;

        event.preventDefault();

        // Determine URL
        switch(Postleaf.template) {
            case 'login.recover':
                url = Postleaf.url('api/login/recover');
                break;
            case 'login.reset':
                url = Postleaf.url('api/login/reset');
                break;
            default:
                url = Postleaf.url('api/login');
                break;
        }

        // Show progress
        progress.go(50);

        // Hide errors
        Postleaf.highlightErrors(form);
        $('.form-message').prop('hidden', true);

        // Send request
        $.ajax({
            url: url,
            type: 'POST',
            data: $(form).serialize()
        })
        .done(function(res) {
            // Show message
            if(res.message) {
                $('.form-message')
                .toggleClass('text-success', res.success)
                .toggleClass('text-warning', !res.success)
                .text(res.message)
                .prop('hidden', false);
            }

            if(res.success) {
                // Disable the form and do nothing if we're recovering
                if(Postleaf.template === 'login.recover') {
                    $(form).find(':input').prop('disabled', true);
                    return;
                }

                // Redirect to a custom URL or to the main admin page
                location.href = redirect ? redirect : Postleaf.adminUrl();
            } else {
                // Show errors
                Postleaf.highlightErrors(form, res.invalid);

                // Shake on error
                $('.login-form')
                .velocity('stop', true)
                .velocity('callout.shake');
            }
        })
        .always(function() {
            // Hide progress
            progress.go(100);
        });
    });
});