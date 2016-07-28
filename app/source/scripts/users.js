/* globals Nanobar, Postleaf */
$(function() {
    'use strict';

    var lastQuery = '',
        more = true,
        progress = new Nanobar(),
        page = 1,
        request,
        searchTimeout;

    // Selection
    $('.user-list').selectable({
        items: '.user-list-item',
        multiple: true,
        change: function(values) {
            // Disable toolbar buttons
            $('.edit, .open').prop('disabled', values.length !== 1);
            $('.delete').prop('disabled', values.length === 0);
        },
        doubleClick: function(value, element) {
            location.href = $(element).attr('href');
        },
        getValue: function() {
            return $(this).attr('data-slug');
        }
    });

    // Scrolling
    $('.user-list').on('scroll', function() {
        var list = this,
            scrollTop = $(list).scrollTop(),
            scrollHeight = list.scrollHeight,
            height = $(list).height(),
            padding = 300,
            query = $('.user-search').val();

        if(!request && more && scrollTop + height + padding >= scrollHeight) {
            // Show progress
            progress.go(50);

            // Load next page
            if(request) request.abort();
            request = $.ajax({
                url: Postleaf.url('api/users'),
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

                // Append users if the page is in range
                if(page <= res.pagination.total_pages) {
                    $(list).append(res.html);
                }
            })
            .always(function() {
                // Hide progress
                progress.go(100);
            });
        }
    });

    // Search
    $('.user-search').on('change keyup paste', function() {
        var input = this,
            query = $(input).val(),
            icon = $(input).closest('.inner-addon-group').find('.inner-addon i');

        clearTimeout(searchTimeout);
        if(query === lastQuery) return;

        searchTimeout = setTimeout(function() {
            // Show loader
            icon.removeClass().addClass('loader');

            // Load matching users and reset page count
            if(request) request.abort();
            request = $.ajax({
                url: Postleaf.url('api/users'),
                type: 'GET',
                data: {
                    page: page = 1,
                    query: query
                }
            })
            .done(function(res) {
                request = null;
                lastQuery = query;

                // Are there more pages to load?
                more = page < res.pagination.total_pages;

                // Show users with a subtle fade effect
                $('.user-list').velocity('transition.fadeOut', 100, function() {
                    $(this)
                    // Reset the scroll top before fading back in. The element must be "visible" to
                    // do this so we have to quickly toggle it on then off.
                    .css('display', 'block').scrollTop(0).css('display', 'none')
                    // Insert users
                    .html(res.html)
                    // Trigger change
                    .selectable('change')
                    // Fade in
                    .velocity('transition.fadeIn', 100);
                });
            })
            .always(function() {
                // Hide loader
                icon.removeClass().addClass('fa fa-search');
            });
        }, 300);
    });

    // Edit
    $('.edit').on('click', function() {
        var url = $('.user-list').selectable('getElements', true)[0].getAttribute('href');
        if(url) location.href = url;
    });

    // Open
    $('.open').on('click', function() {
        var url = $('.user-list').selectable('getElements', true)[0].getAttribute('data-url');
        if(url) window.open(url);
    });

    // Delete
    $('.delete').on('click', function() {
        var users = $('.user-list').selectable('value'),
            confirm = $('.delete').attr('data-confirm'),
            numDeleted = 0;

        if(users.length === 0) return;

        // Confirmation
        $.alertable.confirm(confirm).then(function() {
            // Start progress to show the request is processing
            progress.go(100 / users.length / 2);

            // Delete each user
            $.each(users, function(index, value) {
                // Add deferreds to the queue
                $.ajax({
                    url: Postleaf.url('api/users/' + encodeURIComponent(value)),
                    type: 'DELETE'
                })
                .done(function(res) {
                    var item = $('.user-list').selectable('getElements', value);

                    // Remove deleted item
                    if(res.success) {
                        $(item).remove();
                        $('.user-list').selectable('change');
                    }

                    // Show message
                    if(res.message) {
                        $.alertable.alert(res.message);
                    }
                })
                .always(function() {
                    // Advance progress
                    progress.go(100 * (++numDeleted / users.length));
                });
            });
        });
    });
});