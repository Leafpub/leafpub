/* globals Cookies, Editor, Nanobar, Leafpub */
$(function() {
    'use strict';

    var frameDoc,
        post = $('input[name="post"]').val(),
        titleEditor,
        contentEditor,
        cleanState,
        dropTimeout,
        request,
        saveAction = $('[data-editor=save]').hasClass('btn-warning') ? 'ds' : 'pb',
        zenMode = Cookies.get('zen') === 'true',
        ready = false,
        canCreateTags = $('#tags').attr('data-can-create-tags') === 'true',
        progress = new Nanobar();

    // Hide the dropzone after a short delay. We do this to prevent flickering
    // when dragging over child elements of the dropzone.
    function hideDropzone() {
        dropTimeout = setTimeout(function() {
            // Hide the dropzone after a short delay. We do this to prevent flickering
            // when dragging over child elements of the dropzone.
            $('.drop-post-image, .drop-content-image').removeClass('active');
            $('.dropzone').prop('hidden', true);
        }, 10);
    }

    // Updates all post data and content from a revision
    function setPostDataFromHistory(data) {
        var selectize = $('#tags').get(0).selectize;

        // Restore all post data
        titleEditor.setContent(data.title);
        contentEditor.setContent(data.content);

        // Clear undo levels
        titleEditor.clearUndos();
        contentEditor.clearUndos();

        // Update fields
        $('#slug').val(data.slug);
        $('#pub-date').val(data.pub_date);
        $('#pub-time').val(data.pub_time);
        $('#image').val(data.image);
        $('#author').val(data.author);
        $('#status').val(data.status);
        $('#featured').prop('checked', parseInt(data.featured) === 1);
        $('#sticky').prop('checked', parseInt(data.sticky) === 1);
        $('#page').prop('checked', parseInt(data.page) === 1);
        $('#meta-title').val(data.meta_title);
        $('#meta-description').val(data.meta_description);

        // Update tags
        selectize.setValue(data.tags);

        // Update the UI
        setPostImage(data.image);
        updateSearchEnginePreview();
        updateStatus();
        renderPost(serializePost());

        // Reset clean state
        cleanState = JSON.stringify(serializePost());
    }

    // Shows the specified panel
    function showPanel(panel) {
        // Hide existing panels
        hidePanel();

        // Trigger show event
        $(panel).trigger('show.leafpub.panel');

        // Show the specified panel
        $(panel).on('transitionend.leafpub.panel', function() {
            $(panel).off('transitionend.leafpub.panel').trigger('shown.leafpub.panel');
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
                hidePanel(panel);
            }
        })
        // Watch for the escape key
        .on('keydown.leafpub.panel', function(event) {
            if(event.keyCode === 27) {
                event.preventDefault();
                hidePanel(panel);
            }
        });

        // Watch for form submission
        $(panel).find('form').on('submit.leafpub.panel', function(event) {
            event.preventDefault();
            hidePanel(panel);
        });

        // Watch for clicks on the close button
        $(panel).find('[data-panel="hide"]').on('click.leafpub.panel', function(event) {
            event.preventDefault();
            hidePanel(panel);
        });
    }

    // Hides the specified panel or all panels
    function hidePanel(panel) {
        // Hide selected panel OR all panels
        if(!panel) panel = '.panel.active';

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

    // Called when the frame loads. Checks for errors, prevents links/forms, and initializes title
    // and content editors.
    function loadFrame() {
        /* jshint nonew:false */
        frameDoc = $('.editor-frame').get(0).contentWindow.document;

        // Add the HTML class
        $('html', frameDoc).addClass('leafpub');

        // Show the frame
        $('.editor-loader').prop('hidden', true);
        $('.editor-frame').prop('hidden', false);

        // Check for errors
        if($('html', frameDoc).is('[data-leafpub-error]')) {
            // Stop initializing
            return;
        }

        // Show the toolbar and force a resize
        $('.editor-toolbar').prop('hidden', false);
        $(window).trigger('resize.leafpub');

        // Prevent links from loading other pages
        $(frameDoc).on('click mousedown', 'a, area', function(event) {
            // Skip [data-leafpub] elements
            if(!$(this).parents().addBack().is('[data-leafpub-id]')) {
                event.preventDefault();
            }
        });

        // Prevent form submissions
        $(frameDoc).on('submit', 'form', function(event) {
            event.preventDefault();
        });

        // Pass these events through to the main document
        $(frameDoc).on('click keydown mousedown touchstart', function(event) {
            $(document).trigger(event);
        });

        // Title region
        new Editor($('[data-leafpub-id="post:title"]', frameDoc).get(0), {
            allowNewlines: false,
            placeholder: $('.editor-frame').attr('data-default-title'),
            textOnly: true,
            ready: function() {
                titleEditor = this;
                titleEditor.focus();
                makeReady();
            }
        });

        // Content region
        new Editor($('[data-leafpub-id="post:content"]', frameDoc).get(0), {
            placeholder: $('.editor-frame').attr('data-default-content'),
            nodeChange: function() {
                // Update toolbar buttons
                $('[data-editor="alignCenter"]').toggleClass('on', this.alignCenter('test'));
                $('[data-editor="alignJustify"]').toggleClass('on', this.alignJustify('test'));
                $('[data-editor="alignLeft"]').toggleClass('on', this.alignLeft('test'));
                $('[data-editor="alignRight"]').toggleClass('on', this.alignRight('test'));
                $('[data-editor="blockquote"]').toggleClass('on', this.blockquote('test'));
                $('[data-editor="bold"]').toggleClass('on', this.bold('test'));
                $('[data-editor="code"]').toggleClass('on', this.code('test'));
                $('[data-editor="embed"]').toggleClass('on', this.embed('test'));
                $('[data-editor="heading1"]').toggleClass('on', this.heading1('test'));
                $('[data-editor="heading2"]').toggleClass('on', this.heading2('test'));
                $('[data-editor="heading3"]').toggleClass('on', this.heading3('test'));
                $('[data-editor="heading4"]').toggleClass('on', this.heading4('test'));
                $('[data-editor="heading5"]').toggleClass('on', this.heading5('test'));
                $('[data-editor="heading6"]').toggleClass('on', this.heading6('test'));
                $('[data-editor="image"]').toggleClass('on', this.image('test'));
                $('[data-editor="italic"]').toggleClass('on', this.italic('test'));
                $('[data-editor="link"]').toggleClass('on', this.link('test'));
                $('[data-editor="orderedList"]').toggleClass('on', this.orderedList('test'));
                $('[data-editor="paragraph"]').toggleClass('on', this.paragraph('test'));
                $('[data-editor="preformatted"]').toggleClass('on', this.preformatted('test'));
                $('[data-editor="redo"]').prop('disabled', !this.redo('test'));
                $('[data-editor="strikethrough"]').toggleClass('on', this.strikethrough('test'));
                $('[data-editor="subscript"]').toggleClass('on', this.subscript('test'));
                $('[data-editor="superscript"]').toggleClass('on', this.superscript('test'));
                $('[data-editor="underline"]').toggleClass('on', this.underline('test'));
                $('[data-editor="undo"]').prop('disabled', !this.undo('test'));
                $('[data-editor="unorderedList"]').toggleClass('on', this.unorderedList('test'));

                // Highlight dropdown buttons if at least one menu item is `on`
                $('.dropdown-menu').each(function() {
                    var menu = $(this),
                        btn = $(menu).closest('.dropdown-btn');

                    $(btn).toggleClass('on', $(menu).find('.on').length > 0);
                });
            },
            dblclick: function(event) {
                // Show appropriate panels on double-click
                if($(event.target).is('img')) showPanel('.image-panel');
                if($(event.target).is('[data-embed]')) showPanel('.embed-panel');
            },
            paste: function(event) {
                var clipboardData = event.clipboardData || window.clipboardData,
                    pastedData = clipboardData.getData('Text');

                // Check for anything that looks the beginning of a URL
                if(pastedData.match(/^https?:\/\//i)) {
                    event.stopPropagation();
                    event.preventDefault();

                    // Fetch the provider's oEmbed code
                    progress.go(50);
                    $.ajax({
                        url: Leafpub.url('api/oembed'),
                        type: 'GET',
                        data: {
                            url: pastedData
                        }
                    })
                    .done(function(res) {
                        if(res.code) {
                            // A provider was found, insert the embed code
                            contentEditor.embed('insert', {
                                code: res.code
                            });
                        } else {
                            // No provider was found, insert the raw paste data
                            contentEditor.insertContent(pastedData);
                        }
                    })
                    .always(function() {
                        progress.go(100);
                    });
                }
            },
            ready: function() {
                contentEditor = this;
                makeReady();
            }
        });
    }

    // Called after each editor initializes. Once both have been initialized, this function will set
    // ready to true and handle everything that has to happen *after* both editors are initialized.
    function makeReady() {
        if(titleEditor && contentEditor) {
            ready = true;

            // Get clean state
            cleanState = JSON.stringify(serializePost());

            // Watch for dropped files
            $(document).add(frameDoc)
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
                var target = $(event.target).closest('.dropzone-target').attr('data-target');

                event.preventDefault();
                hideDropzone();
                if(!target || !isDraggingFile(event)) return;

                // Upload it
                Leafpub.upload({
                    accept: target === 'post-image' ? 'image' : null,
                    files: event.originalEvent.dataTransfer.files,
                    progress: function(percent) {
                        progress.go(percent);
                    }
                })
                .then(function(res) {
                    // Insert image
                    if(res.uploaded.length) {
                        // Set post image
                        if(target === 'post-image') {
                            setPostImage(res.uploaded[0].relative_path);
                        }

                        // Insert into content
                        if(target === 'content') {
                            if(res.uploaded[0].filename.match(/\.(gif|jpg|jpeg|png|svg)$/i)) {
                                // Insert image
                                contentEditor.image('insert', {
                                    src: res.uploaded[0].relative_path,
                                    alt: res.uploaded[0].filename,
                                    width: res.uploaded[0].width,
                                    height: res.uploaded[0].height
                                });
                            } else {
                                // Insert link
                                contentEditor.insertContent(
                                    $('<a>')
                                    .attr('href', res.uploaded[0].relative_path)
                                    .text(res.uploaded[0].filename)
                                    .get(0).outerHTML
                                );
                            }
                        }
                    }

                    // Show error
                    if(res.failed.length) {
                        $.alertable.alert(res.failed[0].message);
                    }
                });

            });
        }
    }

    // Renders a post based on postData and hot swaps the page in the editor
    function renderPost(postData) {
        //
        // How it works:
        //
        //      1. Create a dummy form and insert the cmd, options, and post data
        //      2. Create a dummy iframe that will receive the form
        //      3. Trigger the form to submit to the iframe
        //      4. Swap out the head and body elements
        //      5. Replace the new content regions with the old editor instances
        //      6. Magic!
        //
        // Capture title and content elements. We do this because they are linked to the editor and
        // we don't want to reinitialize the editors and lose their undo/redo history. We're
        // basically hot swapping the editors from one template to another.
        var defer = $.Deferred(),
            title = titleEditor.getElement(),
            content = contentEditor.getElement(),
            form = $('<form>');

        // Start progress
        progress.go(50);

        // Remove any pending requests that may still be around
        $('form[target="dummy_frame"], iframe[name="dummy_frame"]').remove();

        // Create a dummy frame
        $('<iframe>')
        .hide()
        .attr('name', 'dummy_frame')
        .appendTo('body')
        .one('load', function() {
            // Replace HTML classes
            $('html', frameDoc).attr('class', this.contentWindow.document.documentElement.className);

            // Replace head
            $('head', frameDoc).replaceWith(this.contentWindow.document.head);

            // Replace body
            $('body', frameDoc).replaceWith(this.contentWindow.document.body);

            // Reinsert title/content elements
            $('[data-leafpub-id="post:title"]', frameDoc).replaceWith(title);
            $('[data-leafpub-id="post:content"]', frameDoc).replaceWith(content);

            // Remove the frame
            $(this).remove();

            // Finish progress
            progress.go(100);

            defer.resolve();
        });

        // Create a dummy form and submit it
        $(form)
        .hide()
        .attr('action', Leafpub.url('api/posts/render'))
        .attr('method', 'post')
        .attr('target', 'dummy_frame')
        .append(
            $('<input type="hidden" name="post-json">').val(JSON.stringify(postData))
        )
        .append('<input type="hidden" name="zen" value="' + (zenMode ? 'true' : 'false') + '">')
        .appendTo('body')
        .submit()
        .remove();

        return defer;
    }

    // Runs a command in the content editor
    function runCommand(cmd) {
        // Make sure the editor is ready
        if(!contentEditor) return;

        // Run editor commands
        if(typeof contentEditor[cmd] === 'function') {
            contentEditor[cmd]();
        }

        // Run special commands
        if(cmd === 'save') save();
        if(cmd === 'zen') toggleZenMode();
        if(cmd === 'settings') showPanel('.settings-panel');
        if(cmd === 'link') showPanel('.link-panel');
        if(cmd === 'embed') showPanel('.embed-panel');
        if(cmd === 'image') showPanel('.image-panel');
    }

    $('[data-save]').on('click', function(){
        saveAction = $(this).attr('data-save');
        switch(saveAction){
            case 'pb':
                $('[data-editor=save]').removeClass('btn-warning').removeClass('btn-primary').addClass('btn-success');
                $('.dropdown-toggle-split').removeClass('btn-warning').removeClass('btn-primary').addClass('btn-success');
                break;
            case 'ps':
                $('[data-editor=save]').removeClass('btn-warning').removeClass('btn-success').addClass('btn-primary');
                $('.dropdown-toggle-split').removeClass('btn-warning').removeClass('btn-success').addClass('btn-primary');
                break;
            case 'ds':
                $('[data-editor=save]').removeClass('btn-success').removeClass('btn-success').addClass('btn-warning');
                $('.dropdown-toggle-split').removeClass('btn-success').removeClass('btn-success').addClass('btn-warning');
                break;
        }
    });

    // Saves the post and redirects to the posts page on success
    function save() {
        var type = post === '' ? 'POST' : 'PUT',
            url = Leafpub.url(
                type === 'POST' ? 'api/posts' : 'api/posts/' + encodeURIComponent(post)
            );

        // Don't save if another request is pending
        if(request || !ready) return;

        // Show progress
        progress.go(50);
        Leafpub.highlightErrors('.settings-form');
        var properties = serializePost();

        // Send request
        request = $.ajax({
            url: url,
            type: type,
            data: {
                post: post,
                properties: properties
            }
        })
        .done(function(res) {
            if(res.success) {
                ready = false;

                // Show feedback
                Leafpub.announce(
                    $('meta[name="leafpub:language"]').attr('data-changes-saved'),
                    { style: 'success' }
                ).then(function() {
                    // Remove save confirmation and redirect
                    window.onbeforeunload = null;
                    if (saveAction === 'pb'){
                        location.href = Leafpub.adminUrl('posts');
                    } else {
                        if (type === 'POST'){
                            location.href = Leafpub.adminUrl('posts/' + properties.slug);
                        }
                    }
                });
            } else {
                // Show errors
                Leafpub.highlightErrors('.settings-form', res.invalid);
                $.alertable.alert(res.message);
            }
        })
        .always(function() {
            progress.go(100);
            request = null;
        });
    }

    // Serializes all post data and returns an object
    function serializePost() {
        var i,
            selectize = $('#tags').get(0).selectize,
            tags = selectize.items,
            tagData = [];

        // Get array of tag data
        for(i = 0; i < selectize.items.length; i++) {
            tagData[i] = {
                slug: selectize.getItem(selectize.items[i]).attr('data-value'),
                name: selectize.getItem(selectize.items[i]).text()
            };
        }

        return {
            title: titleEditor ? titleEditor.getContent() : null,
            content: contentEditor ? contentEditor.getContent() : null,
            slug: $('#slug').val() || Leafpub.slug(titleEditor.getContent()),
            pub_date: $('#pub-date').val() + ' ' + $('#pub-time').val(),
            image: $('#image').val(),
            tags: tags,
            tag_data: tagData,
            author: $('#author').val(),
            status: (saveAction === 'ds') ? 'draft' : 'published', //$('#status').val(),
            featured: $('#featured').prop('checked'),
            sticky: $('#sticky').prop('checked'),
            page: $('#page').prop('checked'),
            meta_title: $('#meta-title').val(),
            meta_description: $('#meta-description').val()
        };
    }

    // Sets the post image
    function setPostImage(src) {
        if(src) {
            $('input[name="image"]').val(src).trigger('change');
            $('.post-image').css('background-image', 'url("' + Leafpub.url(src) + '")');
            $('.remove-post-image').prop('hidden', false);
        } else {
            $('input[name="image"]').val('').trigger('change');
            $('.post-image').css('background-image', 'none');
            $('.remove-post-image').prop('hidden', true);
        }
    }

    // Shows the dropzone
    function showDropzone(event) {
        var zone = $(event.target).parents().addBack();

        // Show it
        $('.dropzone').prop('hidden', false);

        // Add the dragging class to the target zone
        $('.dropzone [data-target="post-image"]').toggleClass('active', $(zone).is('[data-target="post-image"]'));
        $('.dropzone [data-target="content"]').toggleClass('active', $(zone).is('[data-target="content"]'));

        clearTimeout(dropTimeout);
    }

    // Toggles zen mode on or off
    function toggleZenMode(on) {
        zenMode = on ? true : !zenMode;
        $('[data-editor="zen"]').toggleClass('active', zenMode);
        $('.editor-frame').addClass('fade-out');
        Cookies.set('zen', zenMode ? 'true' : 'false');
        renderPost(serializePost()).then(function() {
            $('.editor-frame').removeClass('fade-out');
        });
    }

    // Updates the search engine preview based on title/meta title content/meta description
    function updateSearchEnginePreview() {
        var slug = $('#slug').val(),
            title =
                $.trim($('#meta-title').val()) ||
                $.trim(titleEditor.getContent()),
            description =
                $.trim($('#meta-description').val()) ||
                $.trim($(contentEditor.getContent()).text());

        $('.se-slug').text(slug);
        $('.se-title').text(title);
        $('.se-description').text(description);
    }

    // Updates the post status based on the status control
    function updateStatus() {
        var draft = $('#status').val() === 'draft';

        $('[data-editor="save"]')
        .toggleClass('btn-primary', !draft)
        .toggleClass('btn-warning', draft);
    }

    // Tooltips
    $('.editor-toolbar').find('[title]')
    .tooltip({
        trigger: 'hover',
        placement: 'bottom'
    })
    .on('show.bs.tooltip', function(event) {
        // Don't show tooltips on touch-enabled devices
        if('ontouchstart' in document.documentElement) {
            event.preventDefault();
        }
    });

    // Watch for unsaved changes
	window.onbeforeunload = function() {
        if(ready && cleanState !== JSON.stringify(serializePost())) {
            return $('.editor-frame').attr('data-unsaved-changes');
        }
	};

    // Load the frame once we're ready
    $('.editor-loader').prop('hidden', false);
    $('.editor-frame')
    .one('load', loadFrame)
    .attr('src', $('.editor-frame').attr('data-src'));

    // Keep dropdowns on screen
    $('.dropdown-btn').on('shown.bs.dropdown', function() {
        var menu = $(this).find('.dropdown-menu');
        $(menu)
        .removeClass('dropdown-menu-right')
        .toggleClass('dropdown-menu-right', $(menu).is(':off-right'));
    });

    // Prevent clicks on the toolbar from blurring the frame's focus. This will also prevent text
    // selections from getting grayed out when clicking on toolbar buttons.
    $('.editor-toolbar').on('mousedown', function(event) {
        event.preventDefault();
    });

    // Tags via Selectize
    $('#tags').selectize({
        items: JSON.parse($('#tags').attr('data-post-tags')),
        options: JSON.parse($('#tags').attr('data-all-tags')),
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

    // Enforce slug syntax
    $('#slug').on('change', function() {
        this.value = Leafpub.slug(this.value);
    });

    // Upload post image
    $('.upload-post-image').on('change', 'input[type="file"]', function(event) {
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

            // Set the post image
            if(res.uploaded.length) {
                setPostImage(res.uploaded[0].relative_path);
            }

            // Show feedback
            if(res.failed.length) {
                $.alertable.alert(res.failed[0].message);
            }
        });
    });

    // Remove post image
    $('.remove-post-image').on('click', function() {
        setPostImage(null);
    });

    // Toggle status button color
    $('#status').on('change', function() {
        updateStatus();
    });

    // View history
    $('[data-view-history]').on('click', function() {
        window.open($(this).attr('data-url'));
    });

    // Restore history
    $('[data-restore-history]').on('click', function() {
        var id = $(this).attr('data-restore-history'),
            confirm = $('.editor-frame').attr('data-unsaved-changes');

        function restore() {
            // Start progress
            progress.go(50);

            // Send the request
            $.ajax({
                url: Leafpub.url('api/history/' + encodeURIComponent(id)),
                type: 'GET'
            })
            .done(function(res) {
                if(res.success) {
                    setPostDataFromHistory(res.history.post_data);
                }
            })
            .always(function() {
                // Finish progress
                progress.go(100);
            });
        }

        // Check dirty state before restoring
        if(cleanState !== JSON.stringify(serializePost())) {
            $.alertable.confirm(confirm).then(restore);
        } else {
            restore();
        }
    });

    // Delete history
    $('[data-delete-history]').on('click', function() {
        var id = $(this).attr('data-delete-history'),
            confirm = $(this).attr('data-confirm'),
            tr = $(this).closest('tr');

        $.alertable.confirm(confirm).then(function() {
            progress.go(50);

            // Send the request
            $.ajax({
                url: Leafpub.url('api/history/' + encodeURIComponent(id)),
                type: 'DELETE'
            })
            .done(function(res) {
                if(res.success) {
                    $(tr).remove();
                }
            })
            .always(function() {
                progress.go(100);
            });
        });
    });

    // Handle editor commands
    $('[data-editor]').on('click', function() {
        runCommand($(this).attr('data-editor'));
    });

    // Keyboard shortcuts
    $(document).on('keydown', function(event) {
        var cmd = event.metaKey || event.ctrlKey;

        // Settings - cmd + ,
        if(cmd && event.keyCode === 188) {
            event.preventDefault();
            showPanel('.settings-panel');
        }

        // Link - cmd + k
        if(cmd && event.keyCode === 75) {
            event.preventDefault();
            showPanel('.link-panel');
        }

        // Image - cmd + shift + i
        if(cmd && event.shiftKey && event.keyCode === 73) {
            event.preventDefault();
            showPanel('.image-panel');
        }

        // Embed - cmd + shift + e
        if(cmd && event.shiftKey && event.keyCode === 69) {
            event.preventDefault();
            showPanel('.embed-panel');
        }

        // Save - cmd + s
        if(cmd && event.keyCode === 83) {
            event.preventDefault();
            save();
        }

        // Zen Mode - cmd + .
        if(cmd && event.keyCode === 190) {
            event.preventDefault();
            toggleZenMode();
        }
    });

    // Settings
    (function() {
        var btn = $('[data-editor="settings"]');

        // Settings panel
        $('.settings-panel')
        .on('show.leafpub.panel', function() {
            updateSearchEnginePreview();
            $(btn).addClass('active');
        })
        .on('hide.leafpub.panel', function() {
            $(btn).removeClass('active');
        });

        // Re-render the post when settings are changed
        $('.settings-panel :input').on('change', function() {
            renderPost(serializePost());
        });

        // Update search engine preview (title and content fields are updated when panel is shown)
        $('#slug, #meta-title, #meta-description').on('change keyup paste', updateSearchEnginePreview);
    })();

    // Link
    (function() {
        var btn = $('[data-editor="link"]'),
            bookmark,
            link;

        // Link panel
        $('.link-panel')
        .on('show.leafpub.panel', function() {
            var href,
                title,
                classes,
                target;

            // Get bookmark and selected element
            bookmark = contentEditor.getBookmark();
            link = $(contentEditor.getSelectedElement()).closest('a');

            // Get attributes
            href = decodeURI($(link).attr('href') || '');
            title = $(link).attr('title') || '';
            classes = $(link).attr('class') || '';
            target = $(link).attr('target') || '';

            // Set fields
            $('#link-href').val(href);
            $('#link-title').val(title);
            $('#link-class').val(classes);
            $('#link-new-window').prop('checked', target === '_blank');
            $('.link-open').prop('hidden', href.length === 0);
            $('.unlink').prop('hidden', !link.length);

            // Toggle button state
            $(btn).addClass('active');
        })
        .on('shown.leafpub.panel', function() {
            $('#link-href').focus();
        })
        .on('hide.leafpub.panel', function() {
            $(btn).removeClass('active');
            contentEditor.focus();
        });

        // Submit
        $('.link-form').on('submit', function(event) {
            var href = encodeURI($('#link-href').val()),
                title = $('#link-title').val(),
                classes = $('#link-class').val(),
                target = $('#link-new-window').prop('checked') ? '_blank' : '';

            event.preventDefault();

            // Restore bookmark position
            contentEditor.restoreBookmark(bookmark);

            // Insert the link
            if(href.length) {
                contentEditor.link('insert', {
                    href: href,
                    title: title,
                    class: classes,
                    target: target
                });
            } else {
                contentEditor.link('remove');
            }
        });

        // Unlink
        $('.unlink').on('click', function() {
            contentEditor.link('remove');
            hidePanel('.link-panel');
        });

        // Upload file
        $('.upload-file').on('change', 'input[type="file"]', function(event) {
            var input = this;
            if(!event.target.files.length) return;

            // Upload it
            Leafpub.upload({
                files: event.target.files[0],
                progress: function(percent) {
                    progress.go(percent);
                }
            })
            .then(function(res) {
                // Reset the input
                $(input).replaceWith($(input).clone());

                // Update the file
                if(res.uploaded.length) {
                    if(res.uploaded.length) {
                        $('#link-href').val(res.uploaded[0].relative_path).trigger('change');
                    }
                }

                // Show error
                if(res.failed.length) {
                    $.alertable.alert(res.failed[0].message);
                }
            });
        });
    })();

    // Image
    (function() {
        var btn = $('[data-editor="image"]'),
            bookmark,
            //figure,
            //figcaption,
            image,
            width,
            height,
            cssClass,
            caption,
            query = '',
            more = true,
            page = 1,
            request;

        // Image panel
        $('.image-panel')
        .on('show.leafpub.panel', function() {
            var src,
                href,
                alt;

            // Get bookmark and selected element
            bookmark = contentEditor.getBookmark();
            image = contentEditor.getSelectedElement();//).closest('img');
            //figure = $(contentEditor.getSelectedElement()).closest('figure.image');
            //figcaption = figure.find('figcaption');
            
            if ($(image).is('figure')) {
                $('#image-caption').prop('checked', true);
                if ($(image.firstChild).is('a')){
                    image = image.firstChild;
                }
                image = image.firstChild;
            } else {
                $('#image-caption').prop('checked', false);
            }

            // Get attributes
            src = decodeURI($(image).attr('src') || '');
            href = decodeURI($(image).parent().is('a') ? $(image).parent().attr('href') : '');
            alt = $(image).attr('alt') || '';
            width = $(image).attr('width') || null;
            height = $(image).attr('height') || null;
            cssClass = $(image).attr('class') || null;
            //caption = $(figcaption).html();

            // Set alignment radios
            $('.image-align-none').trigger('click');
            if(image) {
                if(contentEditor.alignLeft('test')) $('.image-align-left').trigger('click');
                if(contentEditor.alignCenter('test')) $('.image-align-center').trigger('click');
                if(contentEditor.alignRight('test')) $('.image-align-right').trigger('click');
            }

            // Set fields
            if (src){
                $('.picture').css('background-image', 'url(' + Leafpub.url(src) + ')');
            }
            
            $('#image-src').val(src);
            $('#image-href').val(href);
            $('#image-alt').val(alt);
            $('#image-width').val(width);
            $('#image-height').val(height);
            $('#image-class').val(cssClass);
            $('#image-constrain').prop('checked', true);
            $('.image-open').prop('hidden', href.length === 0);
            //if (image){
                $('.delete-image').prop('hidden', !image.length);
            //}

            // Toggle button state
            $(btn).addClass('active');
        })
        .on('shown.leafpub.panel', function() {
            $('#image-src').focus();
        })
        .on('hide.leafpub.panel', function() {
            $('.media-list').css('display', 'none').html('');
            $(btn).removeClass('active');
            contentEditor.focus();
        });

        $('.media-file').on('click', function(){
            page = 1;
            $('.picture').css('background-image', '');
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
            change: function(values) {
                // Disable toolbar buttons
                var type = this.getAttribute('data-type');
                prepareEdit(values, type);
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

        function prepareEdit(el, type){
            $.ajax({
                url: Leafpub.url('api/upload/' + el),
                type: 'GET'
            })
            .done(function(res) {
                if (res.success === true){
                    if (type === 'post-image'){
                        $('.media-list').css('display', 'none').html('');
                        setPostImage(res.file.path);
                    } else {
                        $('#image-src').val(res.file.path).trigger('change');
                        $('#image-caption').val(res.file.caption);
                    }
                }
            });
        }

        $('#link-to-image').on('change', function(){
            if ($(this).prop('checked')){
                $('#image-href').val($('#image-src').val());
            } else {
                $('#image-href').val();
            }
        });

        // Submit
        $('.image-form').on('submit', function(event) {
            var src = encodeURI($('#image-src').val()),
                href = encodeURI($('#image-href').val()),
                alt = $('#image-alt').val(),
                newWidth = $('#image-width').val(),
                newHeight = $('#image-height').val(),
                cssClass = $('#image-class').val(),
                align = $('.image-form').find('input[name="align"]:checked').val();
                caption = $('#image-caption').prop('checked');

            event.preventDefault();

            // Restore bookmark position
            contentEditor.restoreBookmark(bookmark);

            // Insert the image
            if(src.length) {
                contentEditor.image('insert', {
                    src: src,
                    href: href,
                    alt: alt,
                    width: newWidth,
                    height: newHeight,
                    align: align,
                    "class": cssClass,
                    caption: caption
                });
            } else {
                contentEditor.image('remove');
            }
        });

        // Delete image
        $('.delete-image').on('click', function() {
            contentEditor.image('remove');
            hidePanel('.image-panel');
        });

        // Upload image
        $('.upload-picture').on('change', 'input[type="file"]', function(event) {
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

                // Update the image
                if(res.uploaded.length) {
                    if(res.uploaded.length) {
                        $('.picture').css('background-image', 'url(\'' + res.uploaded[0].thumbnail + '\')');
                        $('.media-list').css('display', 'none').html('');
                        $('#image-src').val(res.uploaded[0].relative_path).trigger('change');
                    }
                }

                // Show error
                if(res.failed.length) {
                    $.alertable.alert(res.failed[0].message);
                }
            });
        });

        // Image changes
        $('#image-src').on('change', function() {
            var src = $(this).val(),
                img;

            if(src.length) {
                src = Leafpub.url(src);
            } else {
                return;
            }

            // Preload the image to get its natural dimensions
            img =
                $('<img>')
                .hide()
                .one('load', function() {
                    width = this.naturalWidth;
                    height = this.naturalHeight;
                    $('#image-width').val(width);
                    $('#image-height').val(height);
                    $(img).remove();
                })
                .on('error', function() {
                    $(img).remove();
                })
                .appendTo('body')
                .attr('src', src);
        });

        // Constrain proportions
        $('#image-width, #image-height').on('change', function() {
            var newWidth = $('#image-width').val(),
                newHeight = $('#image-height').val(),
                constrain = $('#image-constrain').prop('checked');

            // Calculate new width/height
            if(constrain && newWidth && newHeight && width && height) {
                if(parseInt(width) !== parseInt(newWidth)) {
                    newHeight = Math.round((newWidth / width) * newHeight);
                    if(!isNaN(newHeight)) $('#image-height').val(newHeight);
                } else {
                    newWidth = Math.round((newHeight / height) * newWidth);
                    if(!isNaN(newWidth)) $('#image-width').val(newWidth);
                }
            }

            // Remember new width/height
            width = newWidth;
            height = newHeight;
        });
    })();

    // Embed
    (function() {
        var btn = $('[data-editor="embed"]'),
            bookmark,
            embed;

        // Embed panel
        $('.embed-panel')
        .on('show.leafpub.panel', function() {
            var code;

            // Get bookmark and selected element
            bookmark = contentEditor.getBookmark();
            embed = $(contentEditor.getSelectedElement()).closest('[data-embed]');

            // Get attributes
            code = $(embed).attr('data-embed') || '';

            // Set alignment radios
            $('.embed-align-none').trigger('click');
            if(embed) {
                if(contentEditor.alignLeft('test')) $('.embed-align-left').trigger('click');
                if(contentEditor.alignCenter('test')) $('.embed-align-center').trigger('click');
                if(contentEditor.alignRight('test')) $('.embed-align-right').trigger('click');
            }

            // Set fields
            $('#embed-code').val(code);
            $('.delete-embed').prop('hidden', !embed.length);

            // Toggle button state
            $(btn).addClass('active');
        })
        .on('shown.leafpub.panel', function() {
            $('#embed-code').focus();
        })
        .on('hide.leafpub.panel', function() {
            $(btn).removeClass('active');
            contentEditor.focus();
        });

        // Submit
        $('.embed-form').on('submit', function(event) {
            var code = $('#embed-code').val(),
                align = $('.embed-form').find('input[name="align"]:checked').val();

            event.preventDefault();

            // Restore bookmark position
            contentEditor.restoreBookmark(bookmark);

            // Insert the embed
            if(code.length) {
                // Check for anything that looks the beginning of a URL
                if(code.match(/^https?:\/\//i)) {
                    // Fetch the provider's oEmbed code
                    progress.go(50);
                    $.ajax({
                        url: Leafpub.url('api/oembed'),
                        type: 'GET',
                        data: {
                            url: code
                        }
                    })
                    .done(function(res) {
                        if(res.code) {
                            // A provider was found, insert the embed code
                            contentEditor.embed('insert', {
                                code: res.code
                            });
                        } else {
                            // No provider was found, insert the raw paste data
                            contentEditor.insertContent(code);
                        }
                    })
                    .always(function() {
                        progress.go(100);
                    });
                } else {
                    // Insert as-is
                    contentEditor.embed('insert', {
                        code: code,
                        align: align
                    });
                }
            } else {
                contentEditor.embed('remove');
            }
        });

        // Delete embed
        $('.delete-embed').on('click', function() {
            contentEditor.embed('remove');
            hidePanel('.embed-panel');
        });
    })();
});