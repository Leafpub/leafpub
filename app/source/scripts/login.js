/* globals Nanobar, Leafpub */
const Nanobar = require('nanobar');
import lp from './modules/leafpub';
import 'bootstrap';
import 'velocity-animate';

$(function() {
    'use strict';

    var progress = new Nanobar(),
        redirect = $('.login-form').attr('data-redirect'),
        Leafpub = new lp();

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
        switch(Leafpub.template) {
            case 'login.recover':
                url = Leafpub.url('api/login/recover');
                break;
            case 'login.reset':
                url = Leafpub.url('api/login/reset');
                break;
            default:
                url = Leafpub.url('api/login');
                break;
        }

        // Show progress
        progress.go(50);

        // Hide errors
        Leafpub.highlightErrors(form);
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
                if(Leafpub.template === 'login.recover') {
                    $(form).find(':input').prop('disabled', true);
                    return;
                }

                // Redirect to a custom URL or to the main admin page
                location.href = redirect ? redirect : Leafpub.adminUrl();
            } else {
                // Show errors
                Leafpub.highlightErrors(form, res.invalid);

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