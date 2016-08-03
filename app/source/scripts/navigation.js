/* globals Nanobar, Postleaf, Sortable */
$(function() {
    'use strict';

    var progress = new Nanobar();

    function addItem(label, link) {
        var template = $('.navigation-template').html();

        $(template)
        .find('.label-input').val(label).end()
        .find('.link-input').val(link).end()
        .appendTo('.navigation-list');
    }

    // Submit
    $('.navigation-form').on('submit', function(event) {
        var form = this,
            ready = true;

        event.preventDefault();

        // Is the form ready to be submitted?
        $('.navigation-form .label-input').each(function() {
            var item = $(this).closest('.navigation-list-item'),
                empty = $(this).val() === '';

            if(empty) {
                $(item).closest('.navigation-list-item')
                .addClass('has-warning')
                .velocity('callout.shake');
                ready = false;
            } else {
                $(item).removeClass('has-warning');
            }
        });

        if(ready) {
            // Show progress
            progress.go(50);

            // Send request
            $.ajax({
                url: Postleaf.url('api/navigation'),
                type: 'PUT',
                data: $(form).serialize()
            })
            .done(function(res) {
                if(res.success) {
                    Postleaf.announce(
                        $('meta[name="postleaf:language"]').attr('data-changes-saved'),
                        { style: 'success' }
                    );
                } else {
                    $.alertable.alert(res.message);
                }
            })
            .always(function() {
                // Hide progress
                progress.go(100);
            });
        }
    });

    // Format
    $('.add-format').on('change', function() {
        var option = $(this).find(':selected').get(0),
            format = $(option).val(),
            placeholder = $(option).attr('data-placeholder');

        // Disable the link input if there's no % placeholder
        $('.add-link')
            .prop('placeholder', placeholder)
            .prop('disabled', format.indexOf('%') === -1);
    }).trigger('change'); // Force the placeholder to update

    // Add
    $('.add-form').on('submit', function(event) {
        var format = $('.add-format').val(),
            formatter = $('.add-format').find(':selected').attr('data-formatter'),
            label = $.trim($('.add-label').val()),
            link = $.trim($('.add-link').val());

        event.preventDefault();

        // Make sure label isn't empty. If format contains a % placeholder, then don't let the link
        // be empty either.
        if(!label.length || (format.indexOf('%') > -1 && !link.length)) {
            $('.add-form').addClass('has-warning').velocity('callout.shake');
            return;
        } else {
            $('.add-form').removeClass('has-warning');
        }

        // Format the link
        switch(formatter) {
            case 'slug':
                link = Postleaf.slug(link);
                break;
            case 'encode':
                // Remove slashes from search queries because they don't play nice with the server
                link = link.replace('/', ' ').replace('\\', ' ');
                link = encodeURIComponent(link);
                break;
        }
        link = format.replace('%', link);

        // Add it
        addItem(label, link);

        // Reset the fields
        $('.add-label, .add-link').val('');
        $('.add-format')
        .find('option:first').prop('selected', true).end()
        .trigger('change');
    });

    // Delete
    $('.navigation-list').on('click', '.delete', function() {
        var item = $(this).closest('.navigation-list-item');

        $(item).remove();
    });

    // Submit button
    $('.submit').on('click', function() {
        $('.navigation-form').submit();
    });

    // Enable sortable plugin
    Sortable.create($('.navigation-list').get(0), {
        animation: 150,
        handle: '.handle',
        draggable: '.navigation-list-item'
    });
});