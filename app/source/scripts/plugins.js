/* globals Nanobar, Leafpub */
$(function() {
    'use strict';

    var lastQuery = '',
        more = true,
        progress = new Nanobar(),
        page = 1,
        request,
        searchTimeout;

    // Selection
    $('.plugin-list').selectable({
        items: '.plugin-list-item',
        multiple: true,
        change: function(values) {
            // Disable toolbar buttons
            $('.edit').prop('disabled', values.length !== 1);
            $('.delete').prop('disabled', values.length === 0);
        },
        doubleClick: function(value, element) {
            toggleState(element);
        },
        getValue: function() {
            return $(this).attr('data-dir');
        }
    });

    // Scrolling
    $('.plugin-list').on('scroll', function() {
        var list = this,
            scrollTop = $(list).scrollTop(),
            scrollHeight = list.scrollHeight,
            height = $(list).height(),
            padding = 300,
            query = $('.plugin-search').val();

        if(!request && more && scrollTop + height + padding >= scrollHeight) {
            // Show progress
            progress.go(50);

            // Load next page
            if(request) request.abort();
            request = $.ajax({
                url: Leafpub.url('api/plugins'),
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
                progress.go(100);
            });
        }
    });

    // Search
    $('.plugin-search').on('change keyup paste', function() {
        var input = this,
            query = $(input).val(),
            icon = $(input).closest('.inner-addon-group').find('.inner-addon i');

        clearTimeout(searchTimeout);
        if(query === lastQuery) return;

        searchTimeout = setTimeout(function() {
            // Show loader
            icon.removeClass().addClass('loader');

            // Load matching plugins and reset page count
            if(request) request.abort();
            request = $.ajax({
                url: Leafpub.url('api/plugins'),
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

                // Show plugins with a subtle fade effect
                $('.plugin-list').velocity('transition.fadeOut', 100, function() {
                    $(this)
                    // Reset the scroll top before fading back in. The element must be "visible" to
                    // do this so we have to quickly toggle it on then off.
                    .css('display', 'block').scrollTop(0).css('display', 'none')
                    // Insert plugins
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
    $('.edit').on('click', function(){
        var element = $('.plugin-list').selectable('getElements', true)[0];
        console.log(element);
        return toggleState(element);
    });

    var toggleState = function(element) {
        if (!request){
            progress.go(50);

            var plugin = $(element).attr('data-dir'),
                enable = $(element).attr('data-enabled');

            if(request) request.abort();
            request = $.ajax({
                url: Leafpub.url('api/plugins'),
                type: 'POST',
                data: {
                    plugin: plugin,
                    enable: enable,
                }
            })
            .done(function(res) {
                request = null;
                $(element).toggleClass('enabled');
                $(element).attr('data-enabled', !enable);
            })
            .always(function() {
                // Hide progress
                progress.go(100);
            });
        }
    };

    // Delete
    $('.delete').on('click', function() {
        var plugins = $('.plugin-list').selectable('value'),
            confirm = $('.delete').attr('data-confirm'),
            numDeleted = 0;

        if(plugins.length === 0) return;

        // Confirmation
        $.alertable.confirm(confirm).then(function() {
            // Start progress to show the request is processing
            progress.go(100 / plugins.length / 2);

            // Delete each plugin
            $.each(plugins, function(index, value) {
                // Add deferreds to the queue
                $.ajax({
                    url: Leafpub.url('api/plugins/' + encodeURIComponent(value)),
                    type: 'DELETE'
                })
                .done(function(res) {
                    var item = $('.plugin-list').selectable('getElements', value);

                    // Remove deleted item
                    if(res.success) {
                        $(item).remove();
                        $('.plugin-list').selectable('change');
                    }

                    // Show message
                    if(res.message) {
                        $.alertable.alert(res.message);
                    }
                })
                .always(function() {
                    // Advance progress
                    progress.go(100 * (++numDeleted / plugins.length));
                });
            });
        });
    });
});