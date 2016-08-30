/* globals Editor:true, Postleaf */
/* jshint unused:false */
//
// We're trying to completely abstract TinyMCE so it could be replaced with another library or maybe
// even a plugin somewhere down the road.
//
var Editor;

(function() {
    /* jshint validthis: true */
    'use strict';

    // Constructor
    Editor = function(element, options) {
        var instance = this,
            settings,
            // Get document, window, TinyMCE
            frameDoc = element.ownerDocument,
            frameWin = frameDoc.defaultView,
            tinymce = frameWin.tinymce;

        // Selects the placeholder on focus and restores it when blurring an empty region
        function handlePlaceholders(event) {
            if(event.type === 'focus') {
                // Focus
                if(this.getContent() === instance.options.placeholder) {
                    this.execCommand('selectAll', false, null);
                }
            } else {
                // Blur
                if(!$.trim(this.getContent()).length) {
                    this.setContent(instance.options.placeholder);
                }
            }
        }

        // Prevents the enter key from creating a newline
        function preventNewlines(event) {
            if(event.keyCode === 13) {
                event.preventDefault();
            }
        }

        // Removes data-mce-* attributes from every element in an HTML string
        function removeDataMceAttributes(html) {
            var parser = new DOMParser(),
                doc = parser.parseFromString(html, 'text/html'),
                matches = doc.body.querySelectorAll('*'),
                attribs,
                i, j;

            // Loop through each element
            for(i = 0; i < matches.length; i++) {
                // Get a list of attributes and remove ones that start with data-mce-
                attribs = matches[i].attributes;
                for(j = 0; j < attribs.length; j++) {
                    if(attribs[j].name.match(/^data-mce-/)) {
                        matches[i].removeAttribute(attribs[j].name);
                    }
                }
            }

            return doc.body.innerHTML;
        }

        // Set properties
        instance.element = element;
        instance.options = $.extend({}, {
            allowNewlines: true,
            placeholder: false,
            textOnly: false
        }, options);

        // Merge TinyMCE settings
        settings = {
            browser_spellcheck: true,
            // Document base URL must end with slash per the TinyMCE docs. This is especially
            // important for sites running in a subfolder, otherwise the URLs will be incorrect.
            document_base_url: Postleaf.url().replace(/\/$/, '') + '/',
            element_format: 'html',
            entity_encoding: 'raw',
            extended_valid_elements: 'i[class],iframe[*],script[*]',
            formats: {
                // Align left
                alignleft: [
                    {
                        // Standard elements
                        selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img',
                        classes: 'align-left'
                    },
                    {
                        // Embeds
                        selector: '[data-embed]',
                        classes: 'align-left',
                        ceFalseOverride: true
                    }
                ],
                // Align center
                aligncenter: [
                    {
                        // Standard elements
                        selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img',
                        classes: 'align-center'
                    },
                    {
                        // Embeds
                        selector: '[data-embed]',
                        classes: 'align-center',
                        ceFalseOverride: true
                    }
                ],
                // Align right
                alignright: [
                    {
                        // Standard elements
                        selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img',
                        classes: 'align-right'
                    },
                    {
                        // Embeds
                        selector: '[data-embed]',
                        classes: 'align-right',
                        ceFalseOverride: true
                    }
                ],
                // Justify
                alignjustify: [
                    {
                        // Standard elements
                        selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table',
                        classes: 'align-justify'
                    }
                ],
                // Remove div block formatter
                div: {},
                // Use HTML tags instead of inline styles
                strikethrough: {inline: 'del'},
                underline: {inline: 'u'}
            },
            hidden_input: false,
            inline: true,
            menubar: false,
            object_resizing: false, // CSS selector or `false` to disable
            plugins: 'lists,paste,table,textpattern',
            relative_urls: true,
            selector: '[data-postleaf-id="' + element.getAttribute('data-postleaf-id') + '"]',
            skin: false,
            textpattern_patterns: [
                {start: '*', end: '*', format: 'italic'},
                {start: '_', end: '_', format: 'italic'},
                {start: '**', end: '**', format: 'bold'},
                {start: '__', end: '__', format: 'bold'},
                {start: '~~', end: '~~', format: 'strikethrough'},
                {start: '`', end: '`', format: 'code'},
                {start: '#', format: 'h1'},
                {start: '##', format: 'h2'},
                {start: '###', format: 'h3'},
                {start: '####', format: 'h4'},
                {start: '#####', format: 'h5'},
                {start: '######', format: 'h6'},
                {start: '> ', format: 'blockquote'},
                {start: '1. ', cmd: 'InsertOrderedList'},
                {start: '1) ', cmd: 'InsertOrderedList'},
                {start: '* ', cmd: 'InsertUnorderedList'},
                {start: '- ', cmd: 'InsertUnorderedList'}
            ],
            toolbar: false,
            setup: function(ed) {
                instance.editor = ed;

                // PreInit
                instance.editor.on('PreInit', function() {
                    // Disable TinyMCE's window manager. We do this because certain plugins such
                    // as media/paste/table may trigger a popup in rare cases even though we're
                    // not using those features.
                    instance.editor.windowManager = {
                        alert: function() {},
                        close: function() {},
                        confirm: function() {},
                        getParams: function() {},
                        open: function() {},
                        setParams: function() {}
                    };
                });

                // Prepare embed elements when content is set. We store the original embed code
                // to make sure it's unaltered by the DOM or scripts.
                //
                // In short, this:
                //
                //   <div data-embed="true">{{html}}</div>
                //
                // Will become this:
                //
                //   <div data-embed="{{html}}" contenteditable="false">{{html}}</div>
                //
                instance.editor.on('BeforeSetContent', function(event) {
                    var parser = new DOMParser(),
                        doc = parser.parseFromString(event.content, 'text/html'),
                        matches = doc.body.querySelectorAll('[data-embed]'),
                        i;

                    for(i = 0; i < matches.length; i++) {
                        matches[i].setAttribute(
                            'data-embed',
                            // Remove data-mce-* from pasted embed blocks
                            // See https://github.com/Postleaf/postleaf/issues/7
                            removeDataMceAttributes(matches[i].innerHTML)
                        );
                        matches[i].setAttribute('contenteditable', false);
                    }

                    event.content = doc.body.innerHTML;
                });

                // Restore embed elements when content is fetched.
                //
                // This:
                //
                //   <div data-embed="{{html}}" contenteditable="false">{{html}}</div>
                //
                // Will go back to this:
                //
                //   <div data-embed="true">{{html}}</div>
                //
                instance.editor.on('GetContent', function(event) {
                    var parser = new DOMParser(),
                        doc = parser.parseFromString(event.content, 'text/html'),
                        matches = doc.body.querySelectorAll('[data-embed]'),
                        i;

                    for(i = 0; i < matches.length; i++) {
                        matches[i].removeAttribute('contenteditable');
                        matches[i].innerHTML = matches[i].getAttribute('data-embed');
                        matches[i].setAttribute('data-embed', true);
                    }
                    event.content = doc.body.innerHTML;
                });

                // Allow new lines?
                if(!instance.options.allowNewlines) {
                    instance.editor.on('keydown', preventNewlines);
                }

                // Simulate a placeholder (highlight on focus, restore on blur)
                if(instance.options.placeholder) {
                    instance.editor.on('focus blur', function(event) {
                        handlePlaceholders.call(instance.editor, event);
                    });
                }

                // nodeChange callback
                if(instance.options.nodeChange) {
                    instance.editor.on('NodeChange', function() {
                        instance.options.nodeChange.call(instance);
                    });
                }

                // Focus callback
                if(instance.options.focus) {
                    instance.editor.on('focus', function() {
                        instance.options.focus.call(instance);
                    });
                }

                // Blur callback
                if(instance.options.blur) {
                    instance.editor.on('blur', function() {
                        instance.options.blur.call(instance);
                    });
                }

                // Change callback
                if(instance.options.change) {
                    instance.editor.on('change', function() {
                        instance.options.change.call(instance);
                    });
                }

                // Click callback
                if(instance.options.click) {
                    instance.editor.on('click', function(event) {
                        instance.options.click.call(instance, event);
                    });
                }

                // Double click callback
                if(instance.options.dblclick) {
                    instance.editor.on('dblclick', function(event) {
                        instance.options.dblclick.call(instance, event);
                    });
                }

                // Keydown callback
                if(instance.options.keydown) {
                    instance.editor.getElement().addEventListener('keydown', function(event) {
                        instance.options.keydown.call(instance, event);
                    }, false);
                }

                // Keyup callback
                if(instance.options.keyup) {
                    instance.editor.getElement().addEventListener('keyup', function(event) {
                        instance.options.keyup.call(instance, event);
                    }, false);
                }

                // Paste callback
                if(instance.options.paste) {
                    instance.editor.on('paste', function(event) {
                        instance.options.paste.call(instance, event);
                    });
                }
            },
            init_instance_callback: function() {
                instance.cleanState = instance.editor.getContent();

                // Ready callback
                if(instance.options.ready) {
                    // setTimeout(function() {
                        instance.options.ready.call(instance);
                    // }, 0);
                }
            }
        };

        // Adjust settings for text-only editors
        if(instance.options.textOnly) {
            settings = $.extend({}, settings, {
                forced_root_block: false,
                formats: {
                    // Disable these formatters to prevent shortcuts and the API from applying them
                    h1: {}, h2: {}, h3: {}, h4: {}, h5: {}, h6: {},
                    pre: {}, div: {}, p: {}, blockquote: {},
                    bold: {}, italic: {}, underline: {}, code: {},
                    strikethrough: {}, subscript: {}, superscript: {},
                    alignleft: {}, alignright: {}, aligncenter: {}, alignjustify: {}
                },
                valid_elements: 'br' // can't be blank
            });
        }

        // Initialize TinyMCE
        tinymce.init(settings);
    };

    // Private methods
    function format(format, cmd) {
        switch(cmd) {
            case 'test':
                return this.formatter.match(format);
            case 'apply':
                this.formatter.apply(format);
                this.nodeChanged();
                break;
            case 'remove':
                this.formatter.remove(format);
                this.nodeChanged();
                break;
            default:
                this.formatter.toggle(format);
                this.nodeChanged();
        }
    }

    // Public API
    Editor.prototype = {
        // Disables or enables the editor
        disable: function(disable) {
            this.editor.setMode(disable === false ? 'design' : 'readonly');
        },

        // Gives focus to the editor
        focus: function() {
            this.editor.focus();
        },

        // Returns a bookmark location for the current selection
        getBookmark: function() {
            return this.editor.selection.getBookmark(2);
        },

        // Gets the editor's content
        getContent: function(options) {
            return this.options.textOnly ?
                this.editor.getBody().textContent :
                this.editor.getContent();
        },

        // Gets the original editor element
        getElement: function() {
            return this.element;
        },

        // Gets the element that the cursor was most recently placed in or the parent element if the
        // selection spans multiple elements.
        getSelectedElement: function() {
            return this.editor.selection.getNode();
        },

        // Inserts content at the caret position
        insertContent: function(content) {
            this.editor.execCommand('mceInsertContent', false, content);
        },

        // Returns true if the editor has unsaved changes. Set makeClean to true to reset state
        isDirty: function(makeClean) {
            if(makeClean) {
                // Reset dirty state
                this.cleanState = this.editor.getContent();
            } else {
                // Get dirty state
                return this.cleanState !== this.editor.getContent();
            }
        },

        // Restores the selection to the specified bookmark location
        restoreBookmark: function(bm) {
            this.editor.selection.moveToBookmark(bm);
        },

        // Sets the editor's content
        setContent: function(content) {
            this.editor.setContent(content);
        },

        // Sets the selection to the specified element
        setSelectedElement: function(element) {
            this.editor.selection.select(element);
        },

        // Formatters
        //
        //  Usage:
        //
        //      Toggle: editor.bold()
        //      Apply: editor.bold('apply')
        //      Remove: editor.bold('remove')
        //      Test: editor.bold('test')
        //
        blockquote: function(cmd) { return format.call(this.editor, 'blockquote', cmd); },
        bold: function(cmd) { return format.call(this.editor, 'bold', cmd); },
        code: function(cmd) { return format.call(this.editor, 'code', cmd); },
        heading1: function(cmd) { return format.call(this.editor, 'h1', cmd); },
        heading2: function(cmd) { return format.call(this.editor, 'h2', cmd); },
        heading3: function(cmd) { return format.call(this.editor, 'h3', cmd); },
        heading4: function(cmd) { return format.call(this.editor, 'h4', cmd); },
        heading5: function(cmd) { return format.call(this.editor, 'h5', cmd); },
        heading6: function(cmd) { return format.call(this.editor, 'h6', cmd); },
        italic: function(cmd) { return format.call(this.editor, 'italic', cmd); },
        paragraph: function(cmd) { return format.call(this.editor, 'p', cmd); },
        preformatted: function(cmd) { return format.call(this.editor, 'pre', cmd); },
        strikethrough: function(cmd) { return format.call(this.editor, 'strikethrough', cmd); },
        subscript: function(cmd) { return format.call(this.editor, 'subscript', cmd); },
        superscript: function(cmd) { return format.call(this.editor, 'superscript', cmd); },
        underline: function(cmd) { return format.call(this.editor, 'underline', cmd); },

        // Commands
        //
        //  Usage:
        //
        //      Method: editor.link('method', options);
        //      Test: editor.link('test');
        //
        alignCenter: function(cmd) {
            if(cmd === 'test') {
                return this.editor.formatter.match('aligncenter');
            } else {
                this.editor.execCommand('JustifyCenter');
            }
        },

        alignJustify: function(cmd) {
            if(cmd === 'test') {
                return this.editor.formatter.match('alignjustify');
            } else {
                this.editor.execCommand('JustifyFull');
            }
        },

        alignLeft: function(cmd) {
            if(cmd === 'test') {
                return this.editor.formatter.match('alignleft');
            } else {
                this.editor.execCommand('JustifyLeft');
            }
        },

        alignRight: function(cmd) {
            if(cmd === 'test') {
                return this.editor.formatter.match('alignright');
            } else {
                this.editor.execCommand('JustifyRight');
            }
        },

        clearUndos: function() {
            this.editor.undoManager.clear();
        },

        embed: function(cmd, options) {
            var editor = this.editor,
                embed = editor.dom.getParent(editor.selection.getNode(), '[data-embed]'),
                div;

            options = options || {};

            if(cmd === 'test') {
                return !!embed;
            } else if(cmd === 'insert') {
                if(embed) {
                    // Update it
                    editor.undoManager.transact(function() {
                        embed.setAttribute('data-embed', options.code);
                        embed.setAttribute('contenteditable', false);
                        embed.innerHTML = options.code;

                        // Handle alignment
                        editor.formatter.remove('alignleft');
                        editor.formatter.remove('aligncenter');
                        editor.formatter.remove('alignright');
                        editor.formatter.remove('alignjustify');
                        if(options.align === 'left') editor.formatter.apply('alignleft');
                        if(options.align === 'center') editor.formatter.apply('aligncenter');
                        if(options.align === 'right') editor.formatter.apply('alignright');
                    });
                } else {
                    // Insert it
                    editor.undoManager.transact(function() {
                        div = document.createElement('div');
                        div.setAttribute('data-embed', options.code);
                        div.setAttribute('contenteditable', false);
                        div.innerHTML = options.code;
                        editor.insertContent(div.outerHTML);

                        // Handle alignment
                        if(options.align === 'left') editor.formatter.apply('alignleft');
                        if(options.align === 'center') editor.formatter.apply('aligncenter');
                        if(options.align === 'right') editor.formatter.apply('alignright');
                    });
                }
            } else if(cmd === 'remove') {
                editor.dom.remove(embed);
                editor.undoManager.add();
            }
        },

        image: function(cmd, options) {
            var editor = this.editor,
                image = editor.dom.getParent(editor.selection.getNode(), 'img'),
                link = editor.dom.getParent(editor.selection.getNode(), 'a');

            options = options || {};

            if(cmd === 'test') {
                return !!image;
            } else if(cmd === 'insert') {
                // Is there an existing image?
                if(image) {
                    // Update the image
                    editor.undoManager.transact(function() {
                        editor.dom.setAttribs(image, {
                            src: options.src || '',
                            alt: options.alt || '',
                            width: options.width || null,
                            height: options.height || null
                        });

                        // Handle alignment
                        editor.formatter.remove('alignleft');
                        editor.formatter.remove('aligncenter');
                        editor.formatter.remove('alignright');
                        editor.formatter.remove('alignjustify');
                        if(options.align === 'left') editor.formatter.apply('alignleft');
                        if(options.align === 'center') editor.formatter.apply('aligncenter');
                        if(options.align === 'right') editor.formatter.apply('alignright');

                        // Handle link
                        if(options.href) {
                            if(link) {
                                // Update link
                                editor.dom.setAttribs(link, {
                                    href: options.href
                                });
                            } else {
                                // Wrap with link
                                editor.dom.replace(
                                    editor.dom.create(
                                        'a',
                                        {
                                            href: options.href
                                        },
                                        image.outerHTML
                                    ),
                                    image
                                );
                            }

                            // Add undo state
                            editor.undoManager.add();
                        } else if(link) {
                            // Remove the image
                            editor.dom.replace(image, link);
                            editor.undoManager.add();
                            editor.nodeChanged();
                        }
                    });
                } else {
                    // Insert it
                    editor.undoManager.transact(function() {
                        // Insert a new image
                        image = editor.dom.create('img', {
                            src: options.src || '',
                            alt: options.alt || '',
                            width: options.width || null,
                            height: options.height || null
                        });

                        // Wrap with link
                        if(options.href) {
                            image = editor.dom.create('a', {
                                href: options.href
                            }, image.outerHTML);
                        }

                        editor.insertContent(image.outerHTML);

                        // Handle alignment
                        if(options.align === 'left') editor.formatter.apply('alignleft');
                        if(options.align === 'center') editor.formatter.apply('aligncenter');
                        if(options.align === 'right') editor.formatter.apply('alignright');
                    });
                }
            } else if(cmd === 'remove') {
                editor.dom.remove(image);
                editor.undoManager.add();
            }
        },

        indent: function() {
            this.editor.execCommand('indent');
        },

        link: function(cmd, options) {
            var editor = this.editor;

            options = options || {};

            if(cmd === 'test') {
                return !!editor.dom.getParent(editor.selection.getNode(), 'a');
            } else if(cmd === 'insert') {
                editor.execCommand('mceInsertLink', false, {
                    href: options.href || '',
                    target: options.target || '',
                    title: options.title || '',
                    rel: options.target ? 'noopener' : ''
                });
            } else if(cmd === 'remove') {
                editor.execCommand('unlink');
            }
        },

        orderedList: function(cmd) {
            if(cmd === 'test') {
                return !!this.editor.dom.getParent(this.editor.selection.getNode(), 'ol');
            } else {
                this.editor.execCommand('InsertOrderedList');
            }
        },

        outdent: function() {
            this.editor.execCommand('outdent');
        },

        redo: function(cmd) {
            if(cmd === 'test') {
                return this.editor.undoManager.hasRedo();
            } else {
                this.editor.execCommand('Redo');
            }
        },

        removeFormat: function() {
            this.editor.execCommand('RemoveFormat');
        },

        undo: function(cmd) {
            if(cmd === 'test') {
                return this.editor.undoManager.hasUndo();
            } else {
                this.editor.execCommand('Undo');
            }
        },

        unorderedList: function(cmd) {
            if(cmd === 'test') {
                return !!this.editor.dom.getParent(this.editor.selection.getNode(), 'ul');
            } else {
                this.editor.execCommand('InsertUnorderedList');
            }
        }
    };
})();