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
                    if(res.uploaded[0].extension.match(/(gif|jpg|jpeg|png|svg)$/i)) {
                        $('.picture').css('background-image', 'url(\'' + Leafpub.url(res.uploaded[0].img + '?width=300&sign=' + res.uploaded[0].sign) + '\')');
                        $('#image-width').val(res.uploaded[0].width);
                        $('#image-height').val(res.uploaded[0].height);
                        $('#image-slug').val(res.uploaded[0].filename);
                        $('#image-sign').val(res.uploaded[0].sign);
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
            prepareEdit(value);
        },
        getValue: function() {
            return [$(this).data('slug'), $(this).data('sign')];
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
                url: Leafpub.url('api/uploads'),
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
        var element = $('.media-list').selectable('value')[0];
        prepareEdit(element);
    });

    // Delete
    $('.delete').on('click', function() {
        var media = $('.media-list').selectable('value'),
            confirm = $('.delete').attr('data-confirm'),
            numDeleted = 0;

        if(media.length === 0) return;

        // Confirmation
        $.alertable.confirm(confirm).then(function() {
            // Start progress to show the request is processing
            progress.go(100 / media.length / 2);

            // Delete each plugin
            $.each(media, function(index, value) {
                // Add deferreds to the queue
                $.ajax({
                    url: Leafpub.url('api/uploads/' + encodeURIComponent(value[0])),
                    type: 'DELETE'
                })
                .done(function(res) {
                    var item = $('[data-sign=' + value[1] +']');//.selectable('getElements', value);

                    // Remove deleted item
                    if(res.success) {
                        $(item).remove();
                        $('.media-list').selectable('change');
                    }

                    // Show message
                    /*if(res.message) {
                        $.alertable.alert(res.message);
                    }*/
                })
                .always(function() {
                    // Advance progress
                    progress.go(100 * (++numDeleted / media.length));
                });
            });
        });
    });

     // Upload post image
    $('.upload-picture').on('change', 'input[type="file"]', function(event) {
        var input = this;
        if(!event.target.files.length) return;
        // Show progress
        progress.go(50);
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

            if(res.uploaded.length) {
                if(res.uploaded[0].extension.match(/(gif|jpg|jpeg|png|svg)$/i)) {
                    $('.picture').css('background-image', 'url(\'' + Leafpub.url(res.uploaded[0].img + '?width=300&sign=' + res.uploaded[0].sign) + '\')');
                    $('#image-width').val(res.uploaded[0].width);
                    $('#image-height').val(res.uploaded[0].height);
                    $('#image-slug').val(res.uploaded[0].filename);
                    //showPanel();
                }
            }

            // Show error
            if(res.failed.length) {
                $.alertable.alert(res.failed[0].message);
            }
        })
        .always(function(){
            // Show progress
            progress.go(100);
        });
    });

    function getImageFormData(){
        var selectize = $('#image-tags').get(0).selectize,
            tagData = [],
            tags = selectize.items,
            width = $('#image-width').val(),
            height = $('#image-height').val(),
            caption = $('#image-caption').val(),
            i;

        // Get array of tag data
        for(i = 0; i < selectize.items.length; i++) {
            tagData[i] = {
                slug: selectize.getItem(selectize.items[i]).attr('data-value'),
                name: selectize.getItem(selectize.items[i]).text()
            };
        }

        return {
            tags: tags,
            tagData: tagData,
            width: width,
            height: height,
            caption: caption
        };
    }

    // Submit
    $('.image-form').on('submit', function(event) {
        event.preventDefault();
        var slug = $('#image-slug').val();
        // Show progress
        progress.go(50);
        $.ajax({
            url: Leafpub.url('api/uploads/' + slug),
            type: 'PUT',
            data: getImageFormData()
        })
        .done(function(res) {
            if (res.success === true){
                // Show feedback
                Leafpub.announce(
                    $('meta[name="leafpub:language"]').attr('data-changes-saved'),
                    { style: 'success' }
                ).then(function() {
                    // Remove save confirmation and redirect
                    window.onbeforeunload = null;
                    location.href = Leafpub.adminUrl('uploads');
                });
            } else {
                // Show message
                if(res.message) {
                    $.alertable.alert(res.message);
                }
            }
                
        })
        .always(function() {
            // Show progress
            progress.go(100);
            hidePanel();
        });
    });

    $('.new-upload').on('click', function(){
        showPanel();
    });

    function prepareEdit(el){
        progress.go(50);
        $.ajax({
            url: Leafpub.url('api/upload/' + el[0] + '?width=300&sign=' + el[1]),
            type: 'GET'
        })
        .done(function(res) {
            if (res.success === true){
                $('.picture').css('background-image', 'url(\'' + Leafpub.url(res.file.img) + '?width=300&sign=' + res.file.sign +'\')');
                $('#image-caption').val(res.file.caption);
                $('#image-width').val(res.file.width);
                $('#image-height').val(res.file.height);
                $('#image-slug').val(el[0]);
                $('#image-sign').val(res.file.sign);

                if (res.file.tags.length){
                    var tags = res.file.tags,
                        it = $('#image-tags').get(0).selectize;

                    for(var i = 0; i < tags.length; i++){
                        it.addItem(tags[i]);
                    }
                }
            }
            showPanel();
        })
        .always(function() {
            // Show progress
            progress.go(100);
        });
    }

    // Shows the specified panel
    function showPanel() {
        // Hide existing panels
        //hidePanel();
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

        $('.picture').css('background-image', '');
        $('#image-caption').val('');
        $('#image-width').val('');
        $('#image-height').val('');
        $('#image-slug').val('');
        $('#image-tags').get(0).selectize.clear(true);

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
        items: [], //JSON.parse($('#image-tags').attr('data-post-tags')),
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