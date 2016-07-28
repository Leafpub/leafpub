// Allow users to reauthenticate on-page when an AJAX request fails with a 401 HTTP error
$(function() {
    'use strict';

    // Hook into AJAX errors to check for 401 Unauthorized
    $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
        var defer = $.Deferred();

        // Resolve successful requests normally
        jqXHR.done(defer.resolve);

        // Hook into failed requests to check for 401 Unauthorized
        jqXHR.fail(function() {
            var args = Array.prototype.slice.call(arguments);

            function reject() {
                defer.rejectWith(jqXHR, args);
            }

            function loginPrompt() {
                var json = jqXHR.responseJSON,
                    message = json.message,
                    username = json.language.username,
                    password = json.language.password;

                // Prompt for username/password
                $.alertable.prompt(message, {
                    prompt:
                        '<div class="form-group">' +
                        '<label for="login-prompt-username">' +
                        username +
                        '</label>' +
                        '<input type="text" class="form-control" id="login-prompt-username" name="username" autocomplete="off">' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label for="login-prompt-password">' +
                        password +
                        '</label>' +
                        '<input type="password" class="form-control" id="login-prompt-password" name="password" autocomplete="off">' +
                        '</div>'
                }).then(function(data) {
                    // Attempt login
                    $.ajax({
                        url: Postleaf.url('api/login'),
                        type: 'POST',
                        data: {
                            username: data.username,
                            password: data.password
                        }
                    })
                    .done(function(res) {
                        if(res.success) {
                            // Resubmit original request and pass along resolve/reject
                            $.ajax(originalOptions).then(defer.resolve, defer.reject);
                        } else {
                            // Show error message
                            $.alertable.alert(res.message).then(loginPrompt, reject);
                        }
                    });
                }, function() {
                    reject();
                });
            }

            // Unauthorized?
            if(jqXHR.status === 401) {
                // Show login prompt
                loginPrompt();
            } else {
                reject();
            }
        });

        return defer.promise(jqXHR);
    });
});