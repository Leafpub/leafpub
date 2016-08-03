/* globals Nanobar, Postleaf */
$(function() {
    'use strict';

    var progress = new Nanobar();

    // Submit
    $('.settings-form').on('submit', function(event) {
        var form = this;

        event.preventDefault();

        // Show progress
        progress.go(50);
        Postleaf.highlightErrors(form);

        // Send request
        $.ajax({
            url: Postleaf.url('api/settings'),
            type: 'POST',
            data: $(form).serialize()
        })
        .done(function(res) {
            if(res.success) {
                Postleaf.announce(
                    $('meta[name="postleaf:language"]').attr('data-changes-saved'),
                    { style: 'success' }
                );
            } else {
                // Show errors
                Postleaf.highlightErrors(form, res.invalid);
                $.alertable.alert(res.message);
            }
        })
        .always(function() {
            // Hide progress
            progress.go(100);
        });
    });

    // Submit button
    $('.submit').on('click', function() {
        $('.settings-form').submit();
    });

    // Remove @ from Twitter handle
    $('#twitter').on('change', function() {
        this.value = this.value.replace(/@/g, '');
    });

    // Upload cover
    $('.upload-cover').on('change', 'input[type="file"]', function(event) {
        var input = this;
        if(!event.target.files.length) return;

        // Upload it
        Postleaf.upload({
            accept: 'image',
            files: event.target.files[0],
            progress: function(percent) {
                progress.go(percent);
            }
        })
        .then(function(res) {
            // Reset the input
            $(input).replaceWith($(input).clone());

            // Update cover
            if(res.uploaded.length) {
                $('input[name="cover"]').val(res.uploaded[0].relative_path);
                $('.cover').css('background-image', 'url("' + res.uploaded[0].url + '")');
                $('.remove-cover').prop('hidden', false);
            }

            // Show message
            if(res.failed.length) {
                $.alertable.alert(res.failed[0].message);
            }
        });
    });

    // Remove cover
    $('.remove-cover').on('click', function() {
        $('input[name="cover"]').val('');
        $('.cover').css('background-image', 'none');
        $('.remove-cover').prop('hidden', true);
    });

    // Upload logo
    $('.upload-logo').on('change', 'input[type="file"]', function(event) {
        var input = this;
        if(!event.target.files.length) return;

        // Upload it
        Postleaf.upload({
            accept: 'image',
            files: event.target.files[0],
            progress: function(percent) {
                progress.go(percent);
            }
        })
        .then(function(res) {
            // Reset the input
            $(input).replaceWith($(input).clone());

            // Update logo
            if(res.uploaded.length) {
                $('input[name="logo"]').val(res.uploaded[0].relative_path);
                $('.logo').css('background-image', 'url("' + res.uploaded[0].url + '")');
                $('.remove-logo').prop('hidden', false);
            }

            // Show error
            if(res.failed.length) {
                $.alertable.alert(res.failed[0].message);
            }
        });
    });

    // Remove logo
    $('.remove-logo').on('click', function() {
        $('input[name="logo"]').val('');
        $('.logo').css('background-image', 'none');
        $('.remove-logo').prop('hidden', true);
    });

    // Upload favicon
    $('.upload-favicon').on('change', 'input[type="file"]', function(event) {
        var input = this;
        if(!event.target.files.length) return;

        // Upload it
        Postleaf.upload({
            accept: 'image',
            image: {
                thumbnail: {
                    width: 512,
                    height: 512
                }
            },
            files: event.target.files[0],
            progress: function(percent) {
                progress.go(percent);
            }
        })
        .then(function(res) {
            // Reset the input
            $(input).replaceWith($(input).clone());

            // Update favicon
            if(res.uploaded.length) {
                $('input[name="favicon"]').val(res.uploaded[0].relative_path);
                $('.favicon').css('background-image', 'url("' + res.uploaded[0].url + '")');
                $('.remove-favicon').prop('hidden', false);
            }

            // Show feedback
            if(res.failed.length) {
                $.alertable.alert(res.failed[0].message);
            }
        });
    });

    // Remove favicon
    $('.remove-favicon').on('click', function() {
        $('input[name="favicon"]').val('');
        $('.favicon').css('background-image', 'none');
        $('.remove-favicon').prop('hidden', true);
    });

    // Clear cache
    $('[data-clear-cache]').on('click', function() {
        progress.go(50);
        $.ajax({
            url: Postleaf.url('api/settings/cache'),
            type: 'DELETE'
        })
        .done(function(res) {
            Postleaf.announce(res.message, {
                style: 'info'
            });
        })
        .always(function() {
            progress.go(100);
        });
    });

    // Create backup
    $('[data-create-backup]').on('click', function() {
        // Show the loader and disable the button
        $('.create-backup-loader').prop('hidden', false);
        $('[data-create-backup]').prop('disabled', true);

        // Create the backup
        $.ajax({
            url: Postleaf.url('api/backup'),
            type: 'POST'
        })
        .done(function(res) {
            // Update backups list
            if(res.success) {
                $('.available-backups').html(res.html);
            }

            // Show error
            if(res.message) {
                $.alertable.alert(res.message);
            }
        })
        .always(function() {
            // Hide the loader and enable the button
            $('.create-backup-loader').prop('hidden', true);
            $('[data-create-backup]').prop('disabled', false);
        });
    });

    // Delete backup
    $('.available-backups').on('click', '[data-delete-backup]', function() {
        var backup = $(this).attr('data-delete-backup'),
            confirm = $(this).attr('data-confirm');

        $.alertable.confirm(confirm).then(function() {
            progress.go(50);

            // Send the request
            $.ajax({
                url: Postleaf.url('api/backup/' + encodeURIComponent(backup)),
                type: 'DELETE'
            })
            .done(function(res) {
                $('.available-backups').html(res.html);
            })
            .always(function() {
                progress.go(100);
            });
        });
    });

    // Download backup
    $('.available-backups').on('click', '[data-download-backup]', function() {
        location.href = Postleaf.url(
            'api/backup/' + encodeURIComponent($(this).attr('data-download-backup'))
        );
    });

    // Restore backup
    $('.available-backups').on('click', '[data-restore-backup]', function() {
        var button = $(this),
            backup = $(button).attr('data-restore-backup'),
            confirm = $(button).attr('data-confirm'),
            loader = $(button).closest('td').find('.loader');

        $.alertable.prompt(confirm, {
            okButton:
                '<button class="btn btn-warning" type="submit">' +
                $('meta[name="postleaf:language"]').attr('data-ok') +
                '</button>',
            prompt:
                '<div class="form-group">' +
                '<input type="password" class="form-control" id="login-prompt-password" name="password" autocomplete="off">' +
                '</div>'
        }).then(function(data) {
            // Show loader
            $(button).prop('hidden', true);
            $(loader).prop('hidden', false);

            // Send the request
            $.ajax({
                url: Postleaf.url('api/backup/' + encodeURIComponent(backup) + '/restore'),
                type: 'POST',
                data: {
                    password: data.password
                }
            })
            .done(function(res) {
                // Show message
                if(res.message) {
                    $.alertable.alert(res.message);
                }
            })
            .always(function() {
                // Hide loader
                $(loader).prop('hidden', true);
                $(button).prop('hidden', false);
            });
        });
    });
});