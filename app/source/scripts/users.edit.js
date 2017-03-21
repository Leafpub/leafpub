/* globals Nanobar, Leafpub */
$(function() {
    'use strict';

    var request,
        query = '',
        avatar = false,
        more = true,
        page = 1,
        progress = new Nanobar(),
        user = $('input[name="user"]').val();

    // Submit
    $('.user-form').on('submit', function(event) {
        var form = this,
            type = user === '' ? 'POST' : 'PUT',
            url = Leafpub.url(
                type === 'POST' ? 'api/users' : 'api/users/' + encodeURIComponent(user)
            );

        event.preventDefault();

        // Don't submit if another request is pending
        if(request) return;

        // Show progress
        progress.go(50);
        Leafpub.highlightErrors(form);

        // Send request
        request = $.ajax({
            url: url,
            type: type,
            data: $(form).serialize()
        })
        .done(function(res) {
            if(res.success) {
                // Prevent resubmissions
                $(form).off('submit').on('submit', function(event) {
                    event.preventDefault();
                });

                // Show feedback and redirect
                Leafpub.announce(
                    $('meta[name="leafpub:language"]').attr('data-changes-saved'),
                    { style: 'success' }
                ).then(function() {
                    location.href = $('.user-form').attr('data-redirect');
                });
            } else {
                // Show errors
                Leafpub.highlightErrors(form, res.invalid);
                $.alertable.alert(res.message);
            }
        })
        .always(function() {
            request = null;
            progress.go(100);
        });
    });

    // Open
    $('.open').on('click', function() {
        window.open( $(this).attr('data-url') );
    });

    // Delete
    $('.delete').on('click', function() {
        var confirm = $(this).attr('data-confirm');

        // Confirmation
        $.alertable.confirm(confirm).then(function() {
            // Show progress
            progress.go(50);

            // Send request
            $.ajax({
                url: Leafpub.url('api/users/' + encodeURIComponent(user)),
                type: 'DELETE'
            })
            .done(function(res) {
                // Show feedback
                if(res.message) {
                    $.alertable.alert(res.message);
                }

                // Redirect
                if(res.success) {
                    location.href = $('.user-form').attr('data-redirect');
                }
            })
            .always(function() {
                // Hide progress
                progress.go(100);
            });
        });
    });

    // Submit button
    $('.submit').on('click', function() {
        $('.user-form').submit();
    });

    // Enforce slug syntax
    $('#username').on('change', function() {
        this.value = Leafpub.slug(this.value);
    });

    // Guess username when name changes
    $('#name').on('change', function() {
        var slug = Leafpub.slug(this.value);

        // Only guess if no username is present
        if($('#username').val() === '') {
            $('#username').val(slug);
        }
    });

    // Remove @ from Twitter handle
    $('#twitter').on('change', function() {
        this.value = this.value.replace(/@/g, '');
    });

    // Upload avatar
    $('.upload-avatar').on('change', 'input[type="file"]', function(event) {
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

            // Update avatar
            if(res.uploaded.length) {
                $('input[name="avatar"]').val(res.uploaded[0].relative_path);
                $('.avatar .image').attr('src', res.uploaded[0].url).prop('hidden', false);
                $('.avatar .none').prop('hidden', true);
            }

            // Show feedback
            if(res.failed.length) {
                $.alertable.alert(res.failed[0].message);
            }
        });
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
                $('input[name="cover"]').val(res.uploaded[0].relative_path);
                $('.cover').css('background-image', 'url("' + res.uploaded[0].url + '")');
                $('.remove-cover').prop('hidden', false);
            }

            // Show feedback
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

    $('.media-file').on('click', function(){
        page = 1;
        avatar = this.hasAttribute('avatar');
        if (!avatar){
            $('.cover').css('background-image', '');
        }
        $('.avatar').css('display', 'none');
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
        });
    });

    $('.media-list').selectable({
            items: '.media-list-item',
            multiple: false,
            doubleClick: function(value) {
                setImage(value);
                
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
                    $('.avatar').css('display', 'block');
                    if (!avatar){
                        $('input[name="cover"]').val(res.file.path);
                        $('.cover').css('background-image', 'url("' + Leafpub.url(res.file.path) + '")');
                        $('.remove-cover').prop('hidden', false);
                    } else {
                        $('input[name="avatar"]').val(res.file.path);
                        $('.avatar .image').attr('src', Leafpub.url(res.file.path)).prop('hidden', false);
                        $('.avatar .none').prop('hidden', true);
                    }
                }
            });
        }
});