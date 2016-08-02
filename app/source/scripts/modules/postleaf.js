/* global Postleaf, showdown */
/* jshint unused:false */
var Postleaf;

// The Postleaf object
$(function() {
    'use strict';

    Postleaf = {
        // Postleaf metadata
        template: $('meta[name="postleaf:template"]').attr('content'),

        // Returns the admin URL optionally concatenating a path
        adminUrl: function(path) {
            var url = $('meta[name="postleaf:url"]').attr('data-admin');
            return path ?
                url.replace(/\/$/, '') + '/' + path.replace(/^\//, '') :
                url;
        },

        // Shows feedback that gets automatically hidden after a moment. Return a deferred object.
        announce: function(message, options) {
            var defer = $.Deferred(),
                div = $('<div>'),
                transitionIn,
                transitionOut;

            options = $.extend({}, {
                style: 'primary',
                showSpeed: 500,
                hideSpeed: 500,
                hideDelay: 750
            }, options);

            $('.announce').remove();
            $('body').append(
                $(div)
                .addClass('announce announce-' + options.style)
                .text(message)
                .hide()
            );

            $(div).velocity('transition.perspectiveDownIn', options.showSpeed, function() {
                setTimeout(function() {
                    $(div).velocity('transition.perspectiveDownOut', options.hideSpeed, function() {
                        $(div).remove();
                        defer.resolve();
                    });
                }, options.hideDelay);
            });

            return defer;
        },

        // Highlights form errors
        highlightErrors: function(form, fields) {
            // Remove all
            $(form).find('.has-warning').removeClass('has-warning');

            // Highlight fields (if any)
            $.each(fields, function(index, name) {
                $(form).find(':input[name="' + name + '"]')
                .closest('.form-group')
                .addClass('has-warning');
            });
        },

        // Converts markdown to HTML
        markdownToHtml: function(markdown) {
            var converter = new showdown.Converter();

            return converter.makeHtml(markdown);
        },

        // Returns a slug; same as Postleaf::slug()
        slug: function(string) {
        	return string
        		// Convert spaces and underscores to dashes
        		.replace(/(\s|_)/g, '-')
        		// Remove unsafe characters
        		.replace(/[^A-Z0-9-]/ig, '')
        		// Remove duplicate dashes
        		.replace(/-+/g, '-')
        		// Remove beginning dashes
        		.replace(/^-+/g, '')
        		// Remove trailing dashes
        		.replace(/-+$/g, '')
        		// Make lowercase
        		.toLowerCase();
        },

        // Uploads files using the FileReader API
        //
        //  options = {
        //      files: event.target.files,
        //      progress: function(percent) {},
        //
        //      // Optional type restriction; valid types: image
        //      accept: 'image',
        //
        //      // Optional image processing data
        //      image: {
        //          // Turns the image into a thumbnail
        //          thumbnail: {
        //              width: 100,
        //              height: 75
        //          }
        //      }
        //  }
        //
        upload: function(options) {
            var defer = $.Deferred(),
                formData = new FormData(),
                key, req;

            // Set form data
            if(options.accept) formData.append('accept', options.accept);
            if(options.thumbnail) formData.append('thumbnail', JSON.stringify(options.thumbnail));

            // Set files
            if(options.files.length) {
                // Multiple files were passed in
                for(key = 0; key < options.files.length; key++) {
                    formData.append('files[]', options.files[key]);
                }
            } else {
                // One file was passed in
                formData.append('files[]', options.files);
            }

            // Send request to upload
            $.ajax({
                url: Postleaf.url('api/uploads'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                xhr: function() {
                    req = $.ajaxSettings.xhr();

                    // Monitor progress
                    if(req.upload && options.progress) {
                        req.upload.addEventListener('progress', function(e) {
                            options.progress.call(req, e.loaded / e.total * 100);
                        }, false);
                    }

                    return req;
                }
            })
            .done(function(res) {
                req = null;
                defer.resolve(res);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                defer.reject();
            });

            // Pass the request back as part of the promise
            return defer.promise({
                request: req
            });
        },

        // Returns the website URL optionally concatenating a path
        url: function(path) {
            var url = $('meta[name="postleaf:url"]').attr('data-base');
            return path ?
                url.replace(/\/$/, '') + '/' + path.replace(/^\//, '') :
                url;
        }
    };
});