/* globals Nanobar, Leafpub */
$(function() {
    'use strict';

    var data = {
        importer: '',
        file: '',
        flush: false,
        user: false,
        media: '',
        category: false
    };

    var progress = new Nanobar();

    // Submit
    $('.import-form').on('submit', function(event) {
        var form = this;

        event.preventDefault();

        // Show progress
        progress.go(50);
        Leafpub.highlightErrors(form);

        // Send request
        $.ajax({
            url: Leafpub.url('api/import'),
            type: 'PUT',
            data: data
        })
        .done(function(res) {
            if(res.success) {
                Leafpub.announce(
                    $('meta[name="leafpub:language"]').attr('data-changes-saved'),
                    { style: 'success' }
                );
            } else {
                if (res.failed.length > 0){
                    // Show errors
                    var err = '';
                    res.failed.forEach(function(element){
                        err += element[0] + ':<br>' + element[1] + '<br>';
                    });
                    $.alertable.alert(err, {html: true});
                } else {
                    $.alertable.alert('Nothing imported!');
                }
            }
        })
        .always(function() {
            // Hide progress
            progress.go(100);
        });
    });

    // Submit button
    $('.submit').on('click', function() {
        $(this).addClass('disabled');
        $('.import-form').submit();
    });

    $('.dropin').on('change', function(event){
        data.importer = event.currentTarget.value;
        $('.upload-xml').removeClass('disabled').addClass('visible'); //.prop('hidden', false)
    });

    $('input[type=checkbox]').on('click', function(event){
        var option = event.currentTarget.dataset.option;
        data[option] = event.currentTarget.checked;
    });

    $('input[type=radio]').on('click', function(event){
        var option = event.currentTarget.dataset.option;
        data[option] = event.currentTarget.dataset.type;
    });

    $('.upload-xml').on('change', 'input[type="file"]', function(event) {
        if ($(event.delegateTarget).hasClass('disabled')) {
            return;
        }

        if(!event.target.files.length) return;

        // Upload it
        Leafpub.upload({
            accept: 'xml',
            files: event.target.files[0],
            progress: function(percent) {
                progress.go(percent);
            },
            url: 'api/import',
        })
        .then(function(res) {
            
            if (res.uploaded.length){
                $('.submit').removeClass('disabled');
                $('.upload-xml').addClass('disabled');
                data.file = res.uploaded[0];
            }
            // Show message
            if(res.failed.length) {
                $.alertable.alert(res.failed[0].message);
            }
        });
    });
});