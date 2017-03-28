/* globals Nanobar, Leafpub */
$(function() {
    'use strict';

    var gridStack = $('.grid-stack'),
        isDirty = false,
        options = {
            width: 12,
            alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
            animate: true,
            resizable:{
                handles: 'sw, se'
            },
        };

    function makeReady(){
        $('.widget-loader').prop('hidden', false);

        gridStack.gridstack(options);
       
        gridStack.on('removed', function(){
           isDirty = true; 
           console.log(isDirty);
        });
        
        gridStack.on('dragstart', function(){
           isDirty = true; 
           console.log('dragstart', isDirty);
        });
        
        gridStack.on('resizestart', function(){
           isDirty = true; 
           console.log('resizestart', isDirty);
        });
        
        gridStack.on('added', function(){
           isDirty = true; 
           console.log('added', isDirty);
        });
        
        $('.widget-loader').prop('hidden', true);
        gridStack.prop('hidden', false);
    }

    // Shows the specified panel
    function showPanel() {
        // Hide existing panels
        //hidePanel();
        var panel = $('.widget-panel');
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
        /*
        $('.picture').css('background-image', '');
        $('#image-caption').val('');
        $('#image-width').val('');
        $('#image-height').val('');
        $('#image-slug').val('');
        $('#image-tags').get(0).selectize.clear(true);
        */
        // Show the specified panel
        $(panel).on('transitionend.leafpub.panel', function() {
            $(panel).off('transitionend.leafpub.panel').trigger('hidden.leafpub.panel');
        }).removeClass('active');
    }

    $('#new-widget').on('click', function(){
        //showPanel();
        var grid = gridStack.data('gridstack');
        var html = '<div class="grid-stack-item"><div class="grid-stack-item-content card">';
                html += '<div class="card-header">';
                html += 'Featured';
                html += '</div>';
                html += '<div class="card-block">';
                html += '<h4 class="card-title">Test</h4>';
                html += ' <p class="card-text">Goodbye</p>';
                html += '</div></div></div>';
            grid.addWidget($(html), 0, 0, 2, 3, true);
    });

    makeReady();

    window.onbeforeunload = function(){
        if (!isDirty) return true;
        var res = _.map($('.grid-stack .grid-stack-item:visible'), function (el) {
            el = $(el);
            var node = el.data('_gridstack_node');
            return {
                id: el.attr('id'),
                x: node.x,
                y: node.y,
                width: node.width,
                height: node.height
            };
        });
        
        $.ajax({
            type: 'POST',
            url: Leafpub.url('api/dashboard'),
            data: {
                data: JSON.stringify(res)
            }
        })
        .done(function(res){
            if (res.success){
                return true;
            } else {
                return false;   
            }
        });
    };
});