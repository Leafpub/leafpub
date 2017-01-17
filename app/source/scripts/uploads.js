/* globals Nanobar, Leafpub */
$(function() {
    'use strict';

    var lastQuery = '',
        more = true,
        progress = new Nanobar(),
        page = 1,
        request,
        dropTimeout,
        canCreateTags = $('#image-tags').attr('data-can-create-tags') === 'true',
        searchTimeout;

    // Hide the dropzone after a short delay. We do this to prevent flickering
    // when dragging over child elements of the dropzone.
    function hideDropzone() {
        dropTimeout = setTimeout(function() {
            // Hide the dropzone after a short delay. We do this to prevent flickering
            // when dragging over child elements of the dropzone.
            $('.drop-content-image').removeClass('active');
            $('.dropzone').prop('hidden', true);
        }, 10);
    }

    $(document)
        .on('dragover', function(event) {
            event.preventDefault();
            if(!isDraggingFile(event)) return;

            showDropzone(event);
        })
        .on('dragleave', function(event) {
            event.preventDefault();
            hideDropzone();
        })
        .on('drop', function(event) {
            event.preventDefault();
            hideDropzone();
            if(!isDraggingFile(event)) return;

            // Upload it
            Leafpub.upload({
                accept: 'image',
                files: event.originalEvent.dataTransfer.files,
                progress: function(percent) {
                    progress.go(percent);
                }
            })
            .then(function(res) {
                // Insert image
                if(res.uploaded.length) {
                    if(res.uploaded[0].filename.match(/\.(gif|jpg|jpeg|png|svg)$/i)) {
                        $('.picture').css('background-image', 'url(\'' + res.uploaded[0].thumbnail + '\')');
                        $('#image-width').val(res.uploaded[0].width);
                        $('#image-height').val(res.uploaded[0].height);
                        showPanel();
                    }
                }

                    // Show error
                if(res.failed.length) {
                        $.alertable.alert(res.failed[0].message);
                }
            });
    });

    // Shows the dropzone
    function showDropzone(event) {
        var zone = $(event.target).parents().addBack();

        // Show it
        $('.dropzone').prop('hidden', false);

        // Add the dragging class to the target zone
        $('.dropzone [data-target="database"]').toggleClass('active', $(zone).is('[data-target="database"]'));

        clearTimeout(dropTimeout);
    }

    var toggleState = function(element) {
        if (!request){
            progress.go(50);

            var plugin = element.getAttribute('data-dir'),
                enable = element.classList.contains('enabled');

            if(request) request.abort();
            request = $.ajax({
                url: Leafpub.url('api/plugins'),
                type: 'PUT',
                data: {
                    plugin: plugin,
                    enable: enable,
                }
            })
            .done(function(res) {
                if (res.success === true){
                    request = null;
                    element.classList.toggle('enabled');
                    element.setAttribute('data-enabled', (enable === 0 ? 1 : 0));
                }
            })
            .always(function() {
                // Hide progress
                progress.go(100);
            });
        }
    };
    
    // Selection
    $('.media-list').selectable({
        items: '.media-list-item',
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
    $('.media-list').on('scroll', function() {
        var list = this,
            scrollTop = $(list).scrollTop(),
            scrollHeight = list.scrollHeight,
            height = $(list).height(),
            padding = 300,
            query = $('.media-search').val();

        if(!request && more && scrollTop + height + padding >= scrollHeight) {
            // Show progress
            progress.go(50);

            // Load next page
            if(request) request.abort();
            request = $.ajax({
                url: Leafpub.url('api/upload'),
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
    $('.media-search').on('change keyup paste', function() {
        var input = this,
            query = $(input).val(),
            icon = $(input).closest('.inner-addon-group').find('.inner-addon i');

        clearTimeout(searchTimeout);
        if(query === lastQuery) return;

        searchTimeout = setTimeout(function() {
            // Show loader
            icon.removeClass().addClass('loader');

            // Load matching medias and reset page count
            if(request) request.abort();
            request = $.ajax({
                url: Leafpub.url('api/upload'),
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
                $('.media-list').velocity('transition.fadeOut', 100, function() {
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
        var element = $('.media-list').selectable('getElements', true)[0];
        return toggleState(element);
    });

    // Delete
    $('.delete').on('click', function() {
        var plugins = $('.media-list').selectable('value'),
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
                    url: Leafpub.url('api/upload/' + encodeURIComponent(value)),
                    type: 'DELETE'
                })
                .done(function(res) {
                    var item = $('.media-list').selectable('getElements', value);

                    // Remove deleted item
                    if(res.success) {
                        $(item).remove();
                        $('.media-list').selectable('change');
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

    $('.new-upload').on('click', function(){
        showPanel();
    });

    // Upload post image
    $('.upload-new-plugin').on('change', 'input[type="file"]', function(event) {
        var input = this;
        if(!event.target.files.length) return;

        // Upload it
        Leafpub.upload({
            accept: 'zip',
            files: event.target.files[0],
            url: '/api/plugins',
            progress: function(percent) {
                progress.go(percent);
            }
        })
        .then(function(res) {
            // Reset the input
            $(input).replaceWith($(input).clone());

            if(res.success === true) {
                location.reload();
            } else {
                $.alertable.alert(res.message);
            }
        });
    });

    // Shows the specified panel
    function showPanel() {
        // Hide existing panels
        hidePanel();
        var panel = $('.media-panel');
        // Trigger show event
        panel.trigger('show.leafpub.panel');

        // Show the specified panel
        panel.on('transitionend.leafpub.panel', function() {
            panel.off('transitionend.leafpub.panel').trigger('shown.leafpub.panel');
        }).addClass('active');

        $(document)
        // Watch for keypresses or clicks outside the panel
        .on('touchstart.leafpub.panel keydown.leafpub.panel mousedown.leafpub.panel', function(event) {
            if(
                // Is it outside the panel?
                !$(event.target).parents().addBack().is(panel) &&
                // Ignore modifier keypresses
                !(event.metaKey || event.cmdKey || event.shiftKey)
            ) {
                hidePanel();
            }
        })
        // Watch for the escape key
        .on('keydown.leafpub.panel', function(event) {
            if(event.keyCode === 27) {
                event.preventDefault();
                hidePanel();
            }
        });

        // Watch for form submission
        panel.find('form').on('submit.leafpub.panel', function(event) {
            event.preventDefault();
            hidePanel();
        });

        // Watch for clicks on the close button
        panel.find('[data-panel="hide"]').on('click.leafpub.panel', function(event) {
            event.preventDefault();
            hidePanel();
        });
    }

    // Hides the specified panel or all panels
    function hidePanel() {
        // Hide selected panel OR all panels
        var panel = '.panel.active';

        // Don't hide the panel is there's an active alertable modal. We do this because we don't
        // want interactions made while an alertable (alert, confirm, prompt) is open to hide the
        // active panel.
        if($('.alertable:visible').length) {
            return false;
        }

        // Remove bindings
        $(panel).find('[data-panel="hide"]').off('.leafpub.panel');
        $(document).off('.leafpub.panel');

        // Trigger hide event
        $(panel).trigger('hide.leafpub.panel');

        // Show the specified panel
        $(panel).on('transitionend.leafpub.panel', function() {
            $(panel).off('transitionend.leafpub.panel').trigger('hidden.leafpub.panel');
        }).removeClass('active');
    }

    // Returns true if the user is dragging an actual file
    function isDraggingFile(event) {
        if(event.originalEvent === undefined) return false;
        if(event.originalEvent.dataTransfer === undefined) return false;
        return $.inArray('Files', event.originalEvent.dataTransfer.types) > -1;
    }

    // Tags via Selectize
    $('#image-tags').selectize({
        items: JSON.parse($('#image-tags').attr('data-post-tags')),
        options: JSON.parse($('#image-tags').attr('data-all-tags')),
        valueField: 'slug',
        labelField: 'name',
        delimiter: ',',
        highlight: false,
        createOnBlur: true,
        persist: false,
        searchField: ['slug', 'name'],
        create: canCreateTags ?
            function(input) {
                var slug = Leafpub.slug(input);
                return slug.length ? { slug: slug, name: input } : false;
            } : false,
        render: {
            // Remove the 'Add' text and use an icon instead
            option_create: function(data, escape) {
                return '<div class="create"><i class="fa fa-plus-circle"></i> ' + escape(data.input) + '</div>';
            }
        }
    });
});