/* globals Nanobar, Leafpub */
$(function() {
    'use strict';

    var progress = new Nanobar(),
        page = 1, request, more, query;

    // Submit
    $('.settings-form').on('submit', function(event) {
        var form = this;

        event.preventDefault();

        // Show progress
        progress.go(50);
        Leafpub.highlightErrors(form);

        // Send request
        $.ajax({
            url: Leafpub.url('api/settings'),
            type: 'POST',
            data: $(form).serialize()
        })
        .done(function(res) {
            if(res.success) {
                Leafpub.announce(
                    $('meta[name="leafpub:language"]').attr('data-changes-saved'),
                    { style: 'success' }
                );
            } else {
                // Show errors
                Leafpub.highlightErrors(form, res.invalid);
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
        Leafpub.upload({
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
                $('input[name="cover"]').val(res.uploaded[0].img);
                $('.cover').css('background-image', 'url("' + res.uploaded[0].img + '?width=300&sign="' + res.uploaded[0].sign + '")');
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
        Leafpub.upload({
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
                $('input[name="logo"]').val(res.uploaded[0].img);
                $('.logo').css('background-image', 'url("' + res.uploaded[0].img + '")');
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
        Leafpub.upload({
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
                $('input[name="favicon"]').val(res.uploaded[0].img);
                $('.favicon').css('background-image', 'url("' + res.uploaded[0].img + '")');
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
            url: Leafpub.url('api/settings/cache'),
            type: 'DELETE'
        })
        .done(function(res) {
            Leafpub.announce(res.message, {
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
            url: Leafpub.url('api/backup'),
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
                url: Leafpub.url('api/backup/' + encodeURIComponent(backup)),
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
        location.href = Leafpub.url(
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
                $('meta[name="leafpub:language"]').attr('data-ok') +
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
                url: Leafpub.url('api/backup/' + encodeURIComponent(backup) + '/restore'),
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

    $('.media-file').on('click', function(){
        page = 1;
        $.ajax({
            url: Leafpub.url('api/uploads'),
            type: 'GET',
            data: {
                page: page++,
                query: query
            }
        })
        .done(function(res){
            $('.media-list').css('display', 'flex').html(res.html);
            $('.cover').css('background-image', '');
            $('.cover .controls').prop('hidden', true);
        });
    });

    $('.media-list').selectable({
        items: '.media-list-item',
        multiple: false,
        doubleClick: function(value) {
            setImage(value);
            $('.cover .controls').prop('hidden', false);
        },
        getValue: function() {
            return $(this).attr('data-slug');
        }
    });

    $('.media-list').on('scroll', function() {
        var list = this,
            scrollTop = $(list).scrollTop(),
            scrollHeight = list.scrollHeight,
            height = $(list).height(),
            padding = 150,
            query = $('.media-search').val();

        if(!request && more && scrollTop + height + padding >= scrollHeight) {
            // Show progress
            //progress.go(50);

            // Load next page
            if(request) request.abort();
            request = $.ajax({
                url: Leafpub.url('api/uploads'),
                type: 'GET',
                data: {
                    page: ++page,
                    query: query
                }
            })
            .done(function(res) {
                request = null;

                // Are there more pages to load?
                more = page < res.pagination.total_pages;

                // Append plugins if the page is in range
                if(page <= res.pagination.total_pages) {
                    $(list).append(res.html);
                }
            })
            .always(function() {
                // Hide progress
                //progress.go(100);
            });
        }
    });

    function setImage(el){
        $.ajax({
            url: Leafpub.url('api/upload/' + el),
            type: 'GET'
        })
        .done(function(res) {
            if (res.success === true){
                $('.media-list').css('display', 'none').html('');
                $('input[name="cover"]').val(res.file.img);
                $('.cover').css('background-image', 'url("' + Leafpub.url(res.file.img + "?width=300&sign=" + res.file.sign) + '")');
                $('.remove-cover').prop('hidden', false);
            }
        });
    }
    
    var connectEvents = function(){
        $('.update').on('click', function(e){
            e.preventDefault();
            var el = $(this);
            if (!request){
                if (request) request.abort();
                progress.go(50);
                request = $.ajax({
                    url: Leafpub.url('api/update'),
                    type: 'PATCH',
                    data: {
                        name: el.data('name'),
                        sign: el.data('sign')
                    }
                })
                .done(function(res) {
                    request = null;
                    if (res.success){
                        $(el).remove();
                    }
                })
                .always(function() {
                    // Hide progress
                    progress.go(100);
                });
            }
        });
    };

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (e.target.href.includes('#updates')){
            $('.check-updates-loader').prop('hidden', false);
            progress.go(50);
            $.ajax({
                type: 'GET',
                url: Leafpub.url('api/update-check')
            })
            .done(function(res){
                $('.available-updates').html(res.html);
                connectEvents();
            })
            .always(function(){
                progress.go(100);
            });
            $('.check-updates-loader').prop('hidden', true);
        }
    });
});