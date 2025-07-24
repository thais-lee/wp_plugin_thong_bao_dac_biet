<?php
// Ngăn chặn truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Thêm trang quản lý thông báo vào menu Admin
 */
function tbd_add_admin_menu() {
    add_menu_page(
        __( 'Quản lý Thông Báo Đặc Biệt', 'thong-bao-dac-biet' ), // Tiêu đề trang
        __( 'Thông Báo Đặc Biệt', 'thong-bao-dac-biet' ),               // Tên menu
        'manage_options',                                     // Quyền truy cập
        'thong-bao-dac-biet',                                 // Slug của trang chính
        'tbd_notifications_page_handler',                     // Hàm xử lý
        'dashicons-megaphone',                                // Icon
        6                                                     // Vị trí
    );
}
add_action( 'admin_menu', 'tbd_add_admin_menu' );

/**
 * Hàm điều hướng cho trang quản lý thông báo
 */
function tbd_notifications_page_handler() {
    $action = $_GET['action'] ?? 'list';
    $notification_id = sanitize_key( $_GET['id'] ?? '' );

    switch ( $action ) {
        case 'add':
            tbd_add_edit_notification_form();
            break;
        case 'edit':
            if ( ! empty( $notification_id ) ) {
                tbd_add_edit_notification_form( $notification_id );
            } else {
                echo '<div class="notice notice-error"><p>' . __( 'Thông báo không tìm thấy.', 'thong-bao-dac-biet' ) . '</p></div>';
                tbd_list_notifications_table();
            }
            break;
        case 'delete':
            if ( ! empty( $notification_id ) ) {
                tbd_delete_notification( $notification_id );
            }
            tbd_list_notifications_table(); // Sau khi xóa, hiển thị lại danh sách
            break;
        case 'list':
        default:
            tbd_list_notifications_table();
            break;
    }
}

/**
 * Hàm hiển thị bảng danh sách các thông báo
 */
function tbd_list_notifications_table() {
    $all_notifications = get_option( 'tbd_all_notifications', array() );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Danh sách Thông Báo', 'thong-bao-dac-biet' ); ?> <a href="?page=thong-bao-dac-biet&action=add" class="page-title-action"><?php esc_html_e( 'Thêm mới', 'thong-bao-dac-biet' ); ?></a></h1>
        <?php
        if ( isset( $_GET['message'] ) ) {
            $message = sanitize_text_field( $_GET['message'] );
            if ( $message === 'saved' ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Thông báo đã được lưu.', 'thong-bao-dac-biet' ) . '</p></div>';
            } elseif ( $message === 'deleted' ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Thông báo đã được xóa.', 'thong-bao-dac-biet' ) . '</p></div>';
            }
        }
        ?>
        <table class="wp-list-table widefat fixed striped tbd-admin-table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'ID Shortcode', 'thong-bao-dac-biet' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Tiêu đề', 'thong-bao-dac-biet' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Nội dung (trích dẫn)', 'thong-bao-dac-biet' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Thời gian hiển thị', 'thong-bao-dac-biet' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Hành động', 'thong-bao-dac-biet' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $all_notifications ) ) : ?>
                    <?php foreach ( $all_notifications as $id => $data ) : ?>
                        <tr>
                            <td><code>[thong_bao_dac_biet id="<?php echo esc_attr( $id ); ?>"]</code></td>
                            <td><?php echo esc_html( $data['title'] ?? '' ); ?></td>
                            <td><?php echo wp_trim_words( ( $data['content'] ?? '' ), 10 ); ?></td><td>
                                <?php
                                $start = $data['start_datetime'] ?? '';
                                $end = $data['end_datetime'] ?? '';
                                if ( ! empty( $start ) && ! empty( $end ) ) {
                                    echo esc_html( $start ) . ' - ' . esc_html( $end );
                                } else {
                                    esc_html_e( 'Luôn hiển thị', 'thong-bao-dac-biet' );
                                }
                                ?>
                            </td>
                            <td>
                                <a href="?page=thong-bao-dac-biet&action=edit&id=<?php echo esc_attr( $id ); ?>" class="button button-primary"><?php esc_html_e( 'Sửa', 'thong-bao-dac-biet' ); ?></a>
                                <a href="?page=thong-bao-dac-biet&action=delete&id=<?php echo esc_attr( $id ); ?>&_wpnonce=<?php echo wp_create_nonce( 'tbd_delete_notification_' . $id ); ?>" class="button button-danger" onclick="return confirm('<?php esc_attr_e( 'Bạn có chắc chắn muốn xóa thông báo này?', 'thong-bao-dac-biet' ); ?>');"><?php esc_html_e( 'Xóa', 'thong-bao-dac-biet' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e( 'Chưa có thông báo nào.', 'thong-bao-dac-biet' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Hàm hiển thị form thêm/sửa thông báo
 * @param string $notification_id ID của thông báo cần sửa (null nếu thêm mới)
 */
function tbd_add_edit_notification_form( $notification_id = null ) {
    $is_editing = ! is_null( $notification_id );
    $title_prefix = $is_editing ? __( 'Sửa Thông Báo', 'thong-bao-dac-biet' ) : __( 'Thêm Thông Báo Mới', 'thong-bao-dac-biet' );

    $notification_data = array(
        'id'             => '',
        'title'          => '',
        'content'        => '',
        'duration'       => 5,
        'start_date'     => '',
        'start_time'     => '',
        'end_date'       => '',
        'end_time'       => '',
    );

    if ( $is_editing ) {
        $all_notifications = get_option( 'tbd_all_notifications' );
        // Đảm bảo $all_notifications là array
        if (!is_array($all_notifications)) {
            $all_notifications = array();
        }
        
        $current_data = isset($all_notifications[$notification_id]) ? $all_notifications[$notification_id] : null;

        if ( $current_data ) {
            $notification_data['id'] = $notification_id;
            $notification_data['title'] = $current_data['title'] ?? '';
            $notification_data['content'] = $current_data['content'] ?? '';
            $notification_data['duration'] = $current_data['duration'] ?? 5;

            // Tách chuỗi datetime để hiển thị trong input
            $start_datetime_stored = $current_data['start_datetime'] ?? '';
            $end_datetime_stored   = $current_data['end_datetime'] ?? '';

            if ( ! empty( $start_datetime_stored ) ) {
                $start_parts = explode( ' ', $start_datetime_stored );
                $notification_data['start_date'] = $start_parts[0] ?? '';
                $notification_data['start_time'] = $start_parts[1] ?? '';
            }
            if ( ! empty( $end_datetime_stored ) ) {
                $end_parts = explode( ' ', $end_datetime_stored );
                $notification_data['end_date'] = $end_parts[0] ?? '';
                $notification_data['end_time'] = $end_parts[1] ?? '';
            }
        } else {
            // Nếu không tìm thấy thông báo để sửa, chuyển về trang danh sách
            echo '<div class="notice notice-error"><p>' . __( 'Thông báo cần sửa không tồn tại.', 'thong-bao-dac-biet' ) . '</p></div>';
            tbd_list_notifications_table();
            return;
        }
    }

    // Xử lý lưu form
    if ( isset( $_POST['tbd_notification_settings_nonce'] ) && wp_verify_nonce( $_POST['tbd_notification_settings_nonce'], 'tbd_save_notification_settings' ) ) {
        $new_id                = sanitize_key( $_POST['tbd_notification_id'] );
        $notification_title    = sanitize_text_field( $_POST['tbd_notification_title'] );
        // BỎ wp_kses_post() ở đây để cho phép HTML và JS
        $notification_content  = $_POST['tbd_notification_content'];
        $notification_duration = absint( $_POST['tbd_notification_duration'] );
        $start_date            = sanitize_text_field( $_POST['tbd_notification_start_date'] );
        $start_time            = sanitize_text_field( $_POST['tbd_notification_start_time'] );
        $end_date              = sanitize_text_field( $_POST['tbd_notification_end_date'] );
        $end_time              = sanitize_text_field( $_POST['tbd_notification_end_time'] );

        $start_datetime = ! empty( $start_date ) && ! empty( $start_time ) ? $start_date . ' ' . $start_time : '';
        $end_datetime   = ! empty( $end_date ) && ! empty( $end_time ) ? $end_date . ' ' . $end_time : '';

        // Lấy tất cả thông báo hiện có
        $all_notifications = get_option( 'tbd_all_notifications' );
        // Đảm bảo $all_notifications là array
        if (!is_array($all_notifications)) {
            $all_notifications = array();
        }

        // Kiểm tra trùng lặp ID khi thêm mới
        if ( ! $is_editing && isset( $all_notifications[ $new_id ] ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __( 'ID Shortcode đã tồn tại. Vui lòng chọn một ID khác.', 'thong-bao-dac-biet' ) . '</p></div>';
            // Cập nhật lại dữ liệu form để người dùng không phải nhập lại
            $notification_data['id'] = $new_id;
            $notification_data['title'] = $notification_title;
            $notification_data['content'] = $notification_content;
            $notification_data['duration'] = $notification_duration;
            $notification_data['start_date'] = $start_date;
            $notification_data['start_time'] = $start_time;
            $notification_data['end_date'] = $end_date;
            $notification_data['end_time'] = $end_time;
        } else {
            // Cập nhật hoặc thêm mới thông báo
            $all_notifications[ $new_id ] = array(
                'title'          => $notification_title,
                'content'        => $notification_content, // LƯU NỘI DUNG THÔ
                'duration'       => $notification_duration,
                'start_datetime' => $start_datetime,
                'end_datetime'   => $end_datetime,
            );
            update_option( 'tbd_all_notifications', $all_notifications );
            // Chuyển hướng sau khi lưu thành công
            wp_redirect( admin_url( 'admin.php?page=thong-bao-dac-biet&message=saved' ) );
            exit;
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( $title_prefix ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'tbd_save_notification_settings', 'tbd_notification_settings_nonce' ); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="tbd_notification_id"><?php esc_html_e( 'ID Shortcode', 'thong-bao-dac-biet' ); ?></label></th>
                    <td>
                        <input type="text" name="tbd_notification_id" id="tbd_notification_id" value="<?php echo esc_attr( $notification_data['id'] ); ?>" class="regular-text" <?php echo $is_editing ? 'readonly' : ''; ?> />
                        <p class="description"><?php esc_html_e( 'ID duy nhất cho thông báo này. Ví dụ: `chao_xuan_2025`. ID này sẽ được dùng trong shortcode: [thong_bao_dac_biet id="your_id"]', 'thong-bao-dac-biet' ); ?></p>
                        <?php if ( $is_editing ) : ?>
                            <p class="description"><?php esc_html_e( 'Không thể thay đổi ID khi chỉnh sửa.', 'thong-bao-dac-biet' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="tbd_notification_title"><?php esc_html_e( 'Tiêu đề Thông báo', 'thong-bao-dac-biet' ); ?></label></th>
                    <td><input type="text" name="tbd_notification_title" id="tbd_notification_title" value="<?php echo esc_attr( $notification_data['title'] ); ?>" class="regular-text" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="tbd_notification_duration"><?php esc_html_e( 'Thời gian hiển thị (giây) sau khi xuất hiện', 'thong-bao-dac-biet' ); ?></label><br><small><?php esc_html_e( '(0 để hiển thị liên tục trong khoảng thời gian đã chọn)', 'thong-bao-dac-biet' ); ?></small></th>
                    <td><input type="number" name="tbd_notification_duration" id="tbd_notification_duration" value="<?php echo esc_attr( $notification_data['duration'] ); ?>" min="0" class="small-text" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Thời gian bắt đầu hiển thị', 'thong-bao-dac-biet' ); ?></th>
                    <td>
                        <input type="text" name="tbd_notification_start_date" id="tbd_notification_start_date" value="<?php echo esc_attr( $notification_data['start_date'] ); ?>" class="tbd-datepicker" placeholder="YYYY-MM-DD" />
                        <input type="text" name="tbd_notification_start_time" id="tbd_notification_start_time" value="<?php echo esc_attr( $notification_data['start_time'] ); ?>" class="tbd-timepicker" placeholder="HH:MM:SS" />
                        <p class="description"><?php esc_html_e( 'Để trống cả hai trường nếu muốn thông báo luôn hiển thị (không giới hạn thời gian).', 'thong-bao-dac-biet' ); ?></p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Thời gian kết thúc hiển thị', 'thong-bao-dac-biet' ); ?></th>
                    <td>
                        <input type="text" name="tbd_notification_end_date" id="tbd_notification_end_date" value="<?php echo esc_attr( $notification_data['end_date'] ); ?>" class="tbd-datepicker" placeholder="YYYY-MM-DD" />
                        <input type="text" name="tbd_notification_end_time" id="tbd_notification_end_time" value="<?php echo esc_attr( $notification_data['end_time'] ); ?>" class="tbd-timepicker" placeholder="HH:MM:SS" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="tbd_notification_content"><?php esc_html_e( 'Nội dung Thông báo', 'thong-bao-dac-biet' ); ?></label></th>
                    <td>
                        <?php
                        wp_editor(
                            $notification_data['content'],
                            'tbd_notification_content',
                            array(
                                'textarea_name' => 'tbd_notification_content',
                                'teeny'         => false,
                                'media_buttons' => true,
                                'textarea_rows' => 10,
                                'quicktags'     => true,
                            )
                        );
                        ?>
                        <p class="description notification-warning-message"><?php esc_html_e( 'Cảnh báo: Nội dung này cho phép nhập mã HTML và JavaScript. Hãy cẩn thận khi sử dụng để tránh các vấn đề bảo mật.', 'thong-bao-dac-biet' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Lưu Thông báo', 'thong-bao-dac-biet' ) ); ?>
            <a href="?page=thong-bao-dac-biet" class="button"><?php esc_html_e( 'Quay lại danh sách', 'thong-bao-dac-biet' ); ?></a>
        </form>
    </div>
    <?php
}

/**
 * Hàm xóa thông báo
 * @param string $notification_id ID của thông báo cần xóa
 */
function tbd_delete_notification( $notification_id ) {
    // Kiểm tra nonce để đảm bảo yêu cầu hợp lệ
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'tbd_delete_notification_' . $notification_id ) ) {
        wp_die( __( 'Bạn không có quyền thực hiện hành động này.', 'thong-bao-dac-biet' ) );
    }

    $all_notifications = get_option( 'tbd_all_notifications', array() );

    if ( isset( $all_notifications[ $notification_id ] ) ) {
        unset( $all_notifications[ $notification_id ] );
        update_option( 'tbd_all_notifications', $all_notifications );
        wp_redirect( admin_url( 'admin.php?page=thong-bao-dac-biet&message=deleted' ) );
        exit;
    } else {
        wp_redirect( admin_url( 'admin.php?page=thong-bao-dac-biet&message=not_found' ) );
        exit;
    }
}