/* globals Nanobar, Leafpub */
$(function() {
    'use strict';

    $('.startUpdate').on('click', function(e){
        e.preventDefault();
        $.ajax({
            method: 'POST',
            url: Leafpub.url('api/update')
        })
        .done(function(res){
            if (res.success){
                location.href = Leafpub.adminUrl('posts');
            }
        });
    });
});