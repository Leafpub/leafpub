/* globals Nanobar, Postleaf */
$(function() {
    'use strict';

    var lastQuery = '',
        more = true,
        progress = new Nanobar(),
        page = 1,
        request,
        searchTimeout;

    function updatePreview() {
        var items = $('.post-list').selectable('getElements', true),
            url = $(items[0]).attr('data-url');

        // Hide all panels
        $('.preview-panel').prop('hidden', true);

        // Show the appropriate panel
        if(items.length === 0) {
            // No posts selected
            $('.preview-none').prop('hidden', false);
        } else if(items.length === 1) {
            // One post selected
            $('.preview-loader').prop('hidden', false);
            $('.preview-frame')
            .one('load', function() {
                $('.preview-loader').prop('hidden', true);
                $('.preview-one').prop('hidden', false);

                // Disable user interaction and selection on all elements in the frame
                $('body *', $('.preview-frame').get(0).contentWindow.document)
                .css('pointer-events', 'none')
                .css('user-select', 'none');
            });
            // Replace the URL so it doesn't get saved in the browser's history
            $('.preview-frame').get(0).contentWindow.document.location.replace(url);
        } else {
            // Multiple posts selected
            $('.preview-multiple').prop('hidden', false);
        }
    }

    // Selection
    $('.post-list').selectable({
        items: '.post-list-item',
        multiple: true,
        change: function(values) {
            // Disable toolbar buttons
            $('.edit, .open').prop('disabled', values.length !== 1);
            $('.delete').prop('disabled', values.length === 0);

            // Update selection count
            $('.num-selected').text(values.length);

            // Update the preview
            updatePreview();
        },
        doubleClick: function(value, element) {
            location.href = $(element).attr('href');
        },
        getValue: function() {
            return $(this).attr('data-slug');
        }
    });

    // Scrolling
    $('.post-list').on('scroll', function() {
        var list = this,
            scrollTop = $(list).scrollTop(),
            scrollHeight = list.scrollHeight,
            height = $(list).height(),
            padding = 300,
            query = $('.post-search').val();

        if(!request && more && scrollTop + height + padding >= scrollHeight) {
            // Show progress
            progress.go(50);

            // Load next page
            if(request) request.abort();
            request = $.ajax({
                url: Postleaf.url('api/posts'),
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

                // Append posts if the page is in range
                if(page <= res.pagination.total_pages) {
                    $(list).append(res.html);
                }
            })
            .always(function() {
                // Hide request
                progress.go(100);
            });
        }
    });

    // Search
    $('.post-search').on('change keyup paste', function() {
        var input = this,
            query = $(input).val(),
            icon = $(input).closest('.inner-addon-group').find('.inner-addon i');

        clearTimeout(searchTimeout);
        if(query === lastQuery) return;

        searchTimeout = setTimeout(function() {
            // Show loader
            icon.removeClass().addClass('loader');

            // Load matching posts and reset page count
            if(request) request.abort();
            request = $.ajax({
                url: Postleaf.url('api/posts'),
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

                // Show posts with a subtle fade effect
                $('.post-list').velocity('transition.fadeOut', 100, function() {
                    $(this)
                    // Reset the scroll top before fading back in. The element must be "visible" to
                    // do this so we have to quickly toggle it on then off.
                    .css('display', 'block').scrollTop(0).css('display', 'none')
                    // Insert posts
                    .html(res.html)
                    // Trigger change
                    .selectable('change')
                    // Fade in
                    .velocity('transition.fadeIn', 100);

                    // Update the preview
                    updatePreview();
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
        var url = $('.post-list').selectable('getElements', true)[0].getAttribute('href');
        if(url) location.href = url;
    });

    // Open
    $('.open').on('click', function() {
        var url = $('.post-list').selectable('getElements', true)[0].getAttribute('data-url');
        if(url) window.open(url);
    });

    // Delete
    $('.delete').on('click', function() {
        var posts = $('.post-list').selectable('value'),
            confirm = $('.delete').attr('data-confirm'),
            numDeleted = 0;

        if(posts.length === 0) return;

        // Confirmation
        $.alertable.confirm(confirm).then(function() {
            // Start progress to show the request is processing
            progress.go(100 / posts.length / 2);

            // Delete each tag
            $.each(posts, function(index, value) {
                // Add deferreds to the queue
                $.ajax({
                    url: Postleaf.url('api/posts/' + encodeURIComponent(value)),
                    type: 'DELETE'
                })
                .done(function(res) {
                    var item = $('.post-list').selectable('getElements', value);

                    // Remove deleted item
                    if(res.success) {
                        $(item).remove();
                        $('.post-list').selectable('change');
                        updatePreview();
                    }
                })
                .always(function() {
                    // Advance progress
                    progress.go(100 * (++numDeleted / posts.length));
                });
            });
        });
    });
});