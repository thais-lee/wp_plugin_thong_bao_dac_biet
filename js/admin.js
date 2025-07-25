jQuery(document).on('change', '.tbd-toggle-active', function() {
    var id = jQuery(this).data('id');
    var checked = jQuery(this).is(':checked') ? 1 : 0;
    jQuery.post(ajaxurl, {
        action: 'tbd_toggle_active',
        id: id,
        active: checked,
        _ajax_nonce: tbd_admin_vars.nonce
    }, function(response) {
        if(!response.success) {
            alert('Lỗi khi cập nhật trạng thái!');
        }
    });
});