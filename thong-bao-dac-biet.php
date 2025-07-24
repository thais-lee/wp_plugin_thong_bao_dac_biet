<?php
/*
Plugin Name: Thông Báo Đặc Biệt
Plugin URI:  https://yourwebsite.com/
Description: Một plugin hiển thị nhiều thông báo tùy chỉnh trên trang web, không lưu cache, có thể đính bằng nhiều shortcode, với thời gian hiển thị cụ thể và hỗ trợ nội dung HTML/JS.
Version:     1.4
Author:      Your Name
Author URI:  https://yourwebsite.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: thong-bao-dac-biet
*/

// Ngăn chặn truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Định nghĩa các hằng số
 */
define( 'TBD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TBD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bao gồm các file cần thiết
 */
require_once TBD_PLUGIN_DIR . 'admin/admin-page.php'; // Trang admin để cấu hình

/**
 * Đăng ký các script và style cho cả frontend và backend
 */
function tbd_enqueue_scripts() {
    // Frontend scripts and styles
    wp_enqueue_style( 'tbd-frontend-style', TBD_PLUGIN_URL . 'css/frontend.css', array(), '1.0' );
    wp_enqueue_script( 'tbd-frontend-script', TBD_PLUGIN_URL . 'js/frontend.js', array( 'jquery' ), '1.0', true );

    // Pass PHP data to JavaScript
    wp_localize_script( 'tbd-frontend-script', 'tbd_ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'tbd_get_notification_nonce' ) // Nonce bảo mật
    ) );
}
add_action( 'wp_enqueue_scripts', 'tbd_enqueue_scripts' );

function tbd_enqueue_admin_scripts() {
    // Admin scripts and styles
    wp_enqueue_style( 'tbd-admin-style', TBD_PLUGIN_URL . 'css/admin.css', array(), '1.0' );
    wp_enqueue_script( 'tbd-admin-script', TBD_PLUGIN_URL . 'js/admin.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-timepicker-addon' ), '1.0', true );

    // Styles cho datepicker và timepicker
    wp_enqueue_style( 'jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
    wp_enqueue_style( 'jquery-ui-timepicker-addon-css', TBD_PLUGIN_URL . 'css/jquery-ui-timepicker-addon.min.css' );

    // Media uploader (nếu cần cho nút chọn ảnh trong TinyMCE - mặc dù bạn dùng rich text nên có thể đã có sẵn)
    wp_enqueue_media();

    // Editor script cho TinyMCE (rich text editor)
    wp_enqueue_editor();
}
add_action( 'admin_enqueue_scripts', 'tbd_enqueue_admin_scripts' );

/**
 * Xử lý AJAX để lấy thông báo cụ thể
 */
function tbd_get_notification_ajax_callback() {
    // Kiểm tra nonce để đảm bảo yêu cầu hợp lệ
    check_ajax_referer( 'tbd_get_notification_nonce', 'nonce' );

    // Lấy ID thông báo từ yêu cầu AJAX
    $notification_id = sanitize_key( $_POST['notification_id'] );
    if ( empty( $notification_id ) ) {
        wp_send_json_error( 'Notification ID is missing.' );
    }

    // Lấy tất cả thông báo đã lưu
    $all_notifications = get_option( 'tbd_all_notifications', array() );
    $notification_data = $all_notifications[ $notification_id ] ?? null;

    if ( ! $notification_data ) {
        wp_send_json_error( 'Notification not found.' );
    }

    $notification_title    = sanitize_text_field( $notification_data['title'] ?? '' );
    // Bỏ wp_kses_post() để cho phép HTML và JS trong nội dung
    $notification_content  = $notification_data['content'] ?? ''; // KHÔNG LỌC NỘI DUNG NỮA
    $notification_duration = absint( $notification_data['duration'] ?? 5 );
    $start_datetime_str    = sanitize_text_field( $notification_data['start_datetime'] ?? '' );
    $end_datetime_str      = sanitize_text_field( $notification_data['end_datetime'] ?? '' );

    $current_time = current_time( 'timestamp' ); // Lấy thời gian hiện tại của WordPress

    $show_notification = false;

    // Kiểm tra nếu có tiêu đề hoặc nội dung và nằm trong khoảng thời gian hiển thị
    if ( ! empty( $notification_title ) || ! empty( $notification_content ) ) {
        if ( ! empty( $start_datetime_str ) && ! empty( $end_datetime_str ) ) {
            $start_timestamp = strtotime( $start_datetime_str );
            $end_timestamp   = strtotime( $end_datetime_str );

            if ( $current_time >= $start_timestamp && $current_time <= $end_timestamp ) {
                $show_notification = true;
            }
        } elseif ( empty( $start_datetime_str ) && empty( $end_datetime_str ) ) {
            // Nếu không có thời gian cụ thể, luôn hiển thị
            $show_notification = true;
        }
    }

    if ( $show_notification ) {
        wp_send_json_success( array(
            'title'    => $notification_title,
            'content'  => $notification_content,
            'duration' => $notification_duration
        ) );
    } else {
        wp_send_json_error( 'Notification not configured or not within display period.' );
    }
}
// Hook cho người dùng đã đăng nhập và khách
add_action( 'wp_ajax_tbd_get_notification', 'tbd_get_notification_ajax_callback' );
add_action( 'wp_ajax_nopriv_tbd_get_notification', 'tbd_get_notification_ajax_callback' );

/**
 * Hàm xử lý Shortcode
 * Chấp nhận thuộc tính 'id' để xác định thông báo cụ thể
 * Ví dụ: [thong_bao_dac_biet id="thong_bao_chao_tet"]
 */
function tbd_notification_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'id' => '',
        ),
        $atts,
        'thong_bao_dac_biet'
    );

    $notification_id = sanitize_key( $atts['id'] );
    if ( empty( $notification_id ) ) {
        return '';
    }

    // Lấy tất cả thông báo đã lưu
    $all_notifications = get_option( 'tbd_all_notifications', array() );
    $notification_data = $all_notifications[ $notification_id ] ?? null;
    if ( ! $notification_data ) {
        return '';
    }

    $notification_title    = $notification_data['title'] ?? '';
    $notification_content  = $notification_data['content'] ?? '';
    $notification_duration = absint( $notification_data['duration'] ?? 5 );
    $start_datetime_str    = $notification_data['start_datetime'] ?? '';
    $end_datetime_str      = $notification_data['end_datetime'] ?? '';

    $current_time = current_time( 'timestamp' );
    $show_notification = false;
    if ( ! empty( $notification_title ) || ! empty( $notification_content ) ) {
        if ( ! empty( $start_datetime_str ) && ! empty( $end_datetime_str ) ) {
            $start_timestamp = strtotime( $start_datetime_str );
            $end_timestamp   = strtotime( $end_datetime_str );
            if ( $current_time >= $start_timestamp && $current_time <= $end_timestamp ) {
                $show_notification = true;
            }
        } elseif ( empty( $start_datetime_str ) && empty( $end_datetime_str ) ) {
            $show_notification = true;
        }
    }
    if ( ! $show_notification ) {
        return '';
    }

    // Xuất nội dung trực tiếp, không escape/lọc
    return $notification_content;
}
add_shortcode( 'thong_bao_dac_biet', 'tbd_notification_shortcode' );

/**
 * Thêm liên kết Cài đặt vào trang Plugin (Chuyển hướng đến trang danh sách)
 */
function tbd_add_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=thong-bao-dac-biet">' . __( 'Quản lý thông báo', 'thong-bao-dac-biet' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tbd_add_settings_link' );

// Cho phép script/style và mọi thuộc tính trong TinyMCE + KSES cho admin
add_filter('tiny_mce_before_init', function($init) {
    $init['valid_elements'] = '*[*]';
    $init['extended_valid_elements'] = '*[*]';
    return $init;
});
add_filter('wp_kses_allowed_html', function($tags, $context) {
    if ($context === 'post' && current_user_can('manage_options')) {
        $tags['script'] = array(
            'type' => true, 'src' => true, 'async' => true, 'defer' => true, 'charset' => true
        );
        $tags['style'] = array(
            'type' => true, 'media' => true
        );
        $tags['iframe'] = array(
            'src' => true, 'height' => true, 'width' => true, 'frameborder' => true, 'allowfullscreen' => true, 'allow' => true
        );
        // Cho phép mọi thuộc tính cho mọi thẻ
        foreach ($tags as $tag => &$attrs) {
            $attrs = true;
        }
    }
    return $tags;
}, 10, 2);

// Tắt hoàn toàn lọc khi lưu option tbd_all_notifications
add_filter('pre_update_option_tbd_all_notifications', function($value, $old_value, $option) {
    // Không lọc gì cả, trả về nguyên gốc
    return $value;
}, 10, 3);

// Tắt các filter có thể can thiệp vào việc lưu option
remove_all_filters('option_tbd_all_notifications');
remove_all_filters('sanitize_option_tbd_all_notifications');

// Tắt lọc content khi lưu
add_filter('content_save_pre', function($content) {
    // Nếu đang lưu thông báo, không lọc
    if (isset($_POST['tbd_notification_content'])) {
        return $_POST['tbd_notification_content'];
    }
    return $content;
}, 1);

// Tắt wp_kses_post và các hàm lọc khác cho nội dung thông báo
add_filter('wp_kses_allowed_html', function($tags, $context) {
    // Cho phép mọi thẻ và thuộc tính cho admin
    if (current_user_can('manage_options')) {
        $tags = array();
        $tags['*'] = array('*' => true);
    }
    return $tags;
}, 1, 2);

// Tắt hoàn toàn wp_kses_post cho nội dung thông báo
add_filter('wp_kses_allowed_html', function($tags, $context) {
    if (current_user_can('manage_options')) {
        // Cho phép mọi thẻ HTML
        $tags['*'] = array('*' => true);
        $tags['script'] = array('*' => true);
        $tags['style'] = array('*' => true);
        $tags['iframe'] = array('*' => true);
        $tags['img'] = array('*' => true);
        $tags['a'] = array('*' => true);
        $tags['div'] = array('*' => true);
        $tags['span'] = array('*' => true);
        $tags['p'] = array('*' => true);
        $tags['h1'] = array('*' => true);
        $tags['h2'] = array('*' => true);
        $tags['h3'] = array('*' => true);
        $tags['h4'] = array('*' => true);
        $tags['h5'] = array('*' => true);
        $tags['h6'] = array('*' => true);
        $tags['ul'] = array('*' => true);
        $tags['ol'] = array('*' => true);
        $tags['li'] = array('*' => true);
        $tags['br'] = array('*' => true);
        $tags['strong'] = array('*' => true);
        $tags['em'] = array('*' => true);
        $tags['b'] = array('*' => true);
        $tags['i'] = array('*' => true);
        $tags['u'] = array('*' => true);
        $tags['code'] = array('*' => true);
        $tags['pre'] = array('*' => true);
        $tags['blockquote'] = array('*' => true);
        $tags['table'] = array('*' => true);
        $tags['tr'] = array('*' => true);
        $tags['td'] = array('*' => true);
        $tags['th'] = array('*' => true);
        $tags['thead'] = array('*' => true);
        $tags['tbody'] = array('*' => true);
        $tags['tfoot'] = array('*' => true);
    }
    return $tags;
}, 999, 2);

// Tắt escape cho nội dung thông báo
add_filter('content_save_pre', function($content) {
    // Nếu đang lưu thông báo, không escape
    if (isset($_POST['tbd_notification_content'])) {
        return stripslashes($_POST['tbd_notification_content']);
    }
    return $content;
}, 1);

// Tắt escape cho option
add_filter('pre_update_option_tbd_all_notifications', function($value, $old_value, $option) {
    // Đảm bảo nội dung không bị escape
    if (is_array($value)) {
        foreach ($value as $key => $notification) {
            if (isset($notification['content'])) {
                $value[$key]['content'] = stripslashes($notification['content']);
            }
        }
    }
    return $value;
}, 1, 3);

// Tắt escape cho TinyMCE
add_filter('tiny_mce_before_init', function($init) {
    $init['valid_elements'] = '*[*]';
    $init['extended_valid_elements'] = '*[*]';
    $init['entities'] = false;
    $init['verify_html'] = false;
    $init['cleanup'] = false;
    $init['cleanup_on_startup'] = false;
    $init['validate_children'] = false;
    return $init;
}, 999);

// Tắt escape cho wp_editor
add_filter('wp_editor_settings', function($settings, $editor_id) {
    if ($editor_id === 'tbd_notification_content') {
        $settings['wpautop'] = false;
        $settings['media_buttons'] = false;
        $settings['textarea_rows'] = 15;
        $settings['teeny'] = false;
        $settings['tinymce'] = array(
            'valid_elements' => '*[*]',
            'extended_valid_elements' => '*[*]',
            'entities' => false,
            'verify_html' => false,
            'cleanup' => false,
            'cleanup_on_startup' => false,
            'validate_children' => false,
        );
    }
    return $settings;
}, 10, 2);