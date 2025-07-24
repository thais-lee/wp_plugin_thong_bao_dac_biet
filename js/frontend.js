jQuery(document).ready(function($) {
    // Lặp qua tất cả các container shortcode thông báo trên trang
    $('.tbd-shortcode-notification').each(function() {
        var $this = $(this); // Cache đối tượng jQuery hiện tại
        var notificationId = $this.data('notification-id'); // Lấy ID thông báo từ thuộc tính data

        if (notificationId) {
            // Lấy thông báo qua AJAX cho từng ID cụ thể
            $.ajax({
                url: tbd_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'tbd_get_notification', // Tên action AJAX
                    nonce:  tbd_ajax_object.nonce,   // Nonce bảo mật
                    notification_id: notificationId // Gửi ID thông báo lên PHP
                },
                success: function(response) {
                    if (response.success && (response.data.title || response.data.content)) {
                        var notificationHTML = '';

                        if (response.data.title) {
                            // Tiêu đề vẫn nên được thoát HTML để tránh XSS từ tiêu đề
                            notificationHTML += '<h2>' + $('<div>').text(response.data.title).html() + '</h2>';
                        }
                        // Chèn nội dung THÔ (HTML/JS)
                        notificationHTML += response.data.content; 

                        $this.html(notificationHTML).fadeIn(); // Hiển thị thông báo trong container của nó

                        // Ẩn thông báo sau thời gian hiển thị, nếu duration > 0
                        if (response.data.duration > 0) {
                            setTimeout(function() {
                                $this.fadeOut();
                            }, response.data.duration * 1000); // Chuyển giây sang mili giây
                        }

                    } else {
                        // console.log('Không có thông báo để hiển thị hoặc lỗi cho ID: ' + notificationId + ' - ' + (response.data || 'Unknown error'));
                        $this.remove(); // Xóa container nếu không có thông báo hoặc lỗi
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX khi lấy thông báo cho ID ' + notificationId + ':', status, error);
                    $this.remove(); // Xóa container nếu có lỗi AJAX
                }
            });
        }
    });
});