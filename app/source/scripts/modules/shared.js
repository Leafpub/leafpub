// Global scripts
$(function() {
    'use strict';

    // Stretch any element to the bottom of the screen by adding the .stretch-down class
    function stretchDown() {
        var winHeight = $(window).height();
        $('.stretch-down').each(function() {
            $(this).outerHeight(winHeight - $(this).offset().top);
        });
    }
    $(window).on('resize.postleaf', stretchDown);
    stretchDown();

    // Show/hide the mobile menu with animation
    function toggleMobileMenu(on) {
        // Show/hide with animation
        $('.mobile-menu-items')
        .stop()
        .toggleClass('on', on)
        .velocity(on ? 'transition.shrinkIn' : 'transition.shrinkOut', 300);

        $('.mobile-menu-toggle i')
        .toggleClass('fa-navicon', !on)
        .toggleClass('fa-remove', on);

        // Watch for ESC
        if(on) {
            $(document).on('keydown.mobile-menu', function(event) {
                if(event.keyCode === 27) {
                    toggleMobileMenu(false);
                }
            });
        } else {
            $(document).off('.mobile-menu');
        }
    }

    // Toggle the mobile menu
    $('.mobile-menu-toggle').on('click', function(event) {
        event.preventDefault();
        toggleMobileMenu(!$('.mobile-menu-items').is('.on'));
    });

    // Main menu tooltips
    $('.main-menu [data-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        placement: 'right'
    });

    // Don't show tooltips on touch-enabled devices
    $('[data-toggle="tooltip"]').on('show.bs.tooltip', function(event) {
        if('ontouchstart' in document.documentElement) {
            event.preventDefault();
        }
    });

    // Platform classes on <html>
    $('html')
    .toggleClass('ios', /iPad|iPhone|iPod/.test(navigator.platform))
    .toggleClass('mac', navigator.appVersion.indexOf('Mac') > -1)
    .toggleClass('linux', navigator.appVersion.indexOf('Linux') > -1)
    .toggleClass('windows', navigator.appVersion.indexOf('Windows') > -1);

    // Remove preload class to prevent transitions (see _overrides.scss)
    $('body').removeClass('preload');

    // Custom alertable defaults
    $.alertable.defaults.okButton =
        '<button class="btn btn-primary" type="submit">' +
        ($('meta[name="postleaf:language"]').attr('data-ok') || 'OK') +
        '</button>';
    $.alertable.defaults.cancelButton =
        '<button class="btn btn-secondary" type="button">' +
        ($('meta[name="postleaf:language"]').attr('data-cancel') || 'Cancel') +
        '</button>';
    $.alertable.defaults.show = function() {
        var modal = this.modal,
            overlay = this.overlay;

        function reposition() {
            var height = $(modal).outerHeight(),
                winHeight = $(window).height(),
                top = (winHeight * .45) - (height / 2); // slightly above halfway up

            $(modal).css('top', top + 'px');
        }

        // Vertically center
        reposition();
        $(window).on('resize.alertable', reposition);

        $(modal).add(overlay).velocity('transition.shrinkIn', 300);

        // Brief delay before focusing to let the transition show the modal
        setTimeout(function() {
            if($(modal).find('.alertable-prompt').length) {
                // Focus on first prompt input
                $(modal).find('.alertable-prompt :input:first').focus();
            } else {
                // Focus on the submit button
                $(modal).find(':submit').focus();
            }
        }, 10);
    };
    $.alertable.defaults.hide = function() {
        $(window).off('.alertable');
        $(this.modal).add(this.overlay).velocity('transition.shrinkOut', 300);
    };
});