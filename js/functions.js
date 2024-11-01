jQuery(function($) {
    $('#wk_update_thumb').click(function() {
        var url = $('#wk_thumb_url').val();
        if (!url) {
            $('#wk_thumb_url').addClass('wk_error_field');
            return;
        }
        $('#wk_thumb_url').removeClass('wk_error_field');

        var post_id = $(this).data('post_id');
        $('.wk_url2wpthumb_loader').show();
        $.post(wk_ajax.ajax_url, {action: 'wk_update_thumb', post_id: post_id, url: url}, function(html) {
            $('.inside', '#postimagediv').html(html);
            $('.wk_url2wpthumb_loader').hide();
            $('#wk_thumb_url').val("");
        });
    });

    $('#wk_url2thumb_save_settings').click(function() {
        var post_types = $('#wk_url2thumb_form').serializeArray();
        var saving = $(this).data('saving');
        var save = $(this).data('save');
        var $this = $(this);
        $this.text(saving);
        $.post(wk_ajax.ajax_url, {action: 'wk_url2thumb_save_settings', post_types: post_types}, function() {
            $this.text(save);
        });
    });
});