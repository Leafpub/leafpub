// Locater
$(function() {
    'use strict';

    var searchTimeout,
        lastQuery,
        request;

    // Hides the locater control
    function hide() {
        // Remove bindings
        $(document).off('.postleaf.locater');
        $('.locater-overlay').off('.postleaf.locater');
        $('.locater-input').off('.postleaf.locater');

        // Hide it
        $('.locater, .locater-overlay')
        .velocity('fadeOut', 100, function() {
            $(this).prop('hidden', true);
        });
    }

    // Ensures the selected item is visible
    function keepInView(direction) {
        var selected = $('.locater-results').find('.active'),
            height = $('.locater-results').outerHeight(),
            scrollTop = $('.locater-results').scrollTop(),
            selectedTop = $(selected).position().top + scrollTop,
            selectedHeight = $(selected).outerHeight();

        // Is it partially hidden?
        if(selectedTop < scrollTop || selectedTop + selectedHeight > scrollTop + height) {
            if(direction === 'up') {
                $('.locater-results').scrollTop(selectedTop);
            } else {
                $('.locater-results').scrollTop(selectedTop - height + selectedHeight);
            }
        }
    }

    // Moves the selection up
    function moveUp() {
        var items = $('.locater-results a'),
            selected = $('.locater-results .active');

        if($(selected).length) {
            // Clear selection
            $(items).removeClass('active');

            if($(selected).prev('a').length) {
                // Select previous item
                $(selected).prev('a').addClass('active');
            } else {
                // Cycle to last item
                $(items).last().addClass('active');
            }

            keepInView('up');
        }
    }

    // Moves the selection down
    function moveDown() {
        var items = $('.locater-results a'),
            selected = $('.locater-results .active');

        if($(selected).length) {
            // Clear selection
            $(items).removeClass('active');

            if($(selected).next('a').length) {
                // Select next item
                $(selected).next('a').addClass('active');
            } else {
                // Cycle to first item
                $(items).first().addClass('active');
            }

            keepInView('down');
        }
    }

    // Resets the control to its original state
    function reset() {
        // Clear it
        $('.locater-input').val('');
        $('.locater-results').html('');
        lastQuery = '';
    }

    // Shows the locater control
    function show() {
        // Reset it
        reset();

        // Show it
        $('.locater, .locater-overlay')
        .css('display', 'none')
        .prop('hidden', false)
        .velocity('fadeIn', 100, function() {
            $('.locater-input').focus();
        });

        // Watch for key presses
        $(document).on('keydown.postleaf.locater', function(event) {
            // Escape or tab closes it
            if(event.keyCode === 9 || event.keyCode === 27) {
                event.preventDefault();
                hide();
            }

            // Enter selects it
            if(event.keyCode === 13) {
                event.preventDefault();

                // Go to the selected item
                if($('.locater-results .active').length) {
                    location.href = $('.locater-results .active').attr('href');
                }

                hide();
            }

            // Move up
            if(event.keyCode === 38) {
                event.preventDefault();
                moveUp();
            }

            // Move down
            if(event.keyCode === 40) {
                event.preventDefault();
                moveDown();
            }
        });

        // Close when the overlay is clicked
        $('.locater-overlay').on('click.postleaf.locater', function() {
            hide();
        });

        // Watch the search field for changes
        $('.locater-input').on('keyup.postleaf.locater', function(event) {
            var query = $(this).val();

            clearTimeout(searchTimeout);
            if(query === lastQuery) return;
            if(request) request.abort();
            if(query === '') reset();
            searchTimeout = setTimeout(function() {
                var icon = $('.locater-input').closest('.form-group').find('.inner-addon i');

                // Show loader
                $(icon).removeClass().addClass('loader');

                // Send request
                request = $.ajax({
                    url: Postleaf.url('api/locater'),
                    type: 'GET',
                    data: {
                        query: query
                    }
                })
                .done(function(res) {
                    request = null;
                    lastQuery = query;

                    if(res.success) {
                        $('.locater-results').html(res.html);
                        $('.locater-results a:first').addClass('active');
                    } else {
                        reset();
                    }
                })
                .always(function() {
                    // Hide loader
                    $(icon).removeClass().addClass('fa fa-search');
                });
            }, 300);
        });
    }

    // Is the locater available on this page?
    if($('.locater').length) {
        // Show the locater with CMD/CTRL + P
        $(document).on('keydown', function(event) {
            if((event.metaKey || event.ctrlKey ) && event.keyCode === 80) {
                event.preventDefault();
                $('.locater').is(':visible') ? hide() : show();
            }
        });
    }
});