# Thông Báo Đặc Biệt (Special Notification)

## Mô tả
Plugin WordPress cho phép bạn tạo, quản lý và hiển thị nhiều thông báo tùy chỉnh (badge/banner) trên website bằng shortcode. Hỗ trợ nội dung HTML/JS/CSS, kiểm soát thời gian hiển thị, bật/tắt từng badge dễ dàng trong trang quản trị.

## Tính năng
- Tạo nhiều thông báo/badge với nội dung tùy ý (HTML, CSS, JS)
- Hiển thị thông báo bằng shortcode: `[thong_bao_dac_biet id="your_id"]`
- Đặt thời gian bắt đầu/kết thúc hiển thị cho từng badge
- Bật/tắt từng badge bằng switch toggle trong trang quản trị
- Hỗ trợ AJAX để bật/tắt nhanh không cần reload
- Không cache, hiển thị realtime

## Cài đặt
1. Tải plugin về hoặc clone repo:
   ```
   git clone https://github.com/thais-lee/wp_plugin_thong_bao_dac_biet.git
   ```
2. Đặt thư mục vào `wp-content/plugins/thong-bao-dac-biet`
3. Kích hoạt plugin trong WordPress Admin > Plugins

## Hướng dẫn sử dụng
### 1. Tạo thông báo mới
- Vào menu **Thông Báo Đặc Biệt** trong trang quản trị
- Nhấn **Thêm mới**
- Nhập ID (không dấu, không trùng), tiêu đề, nội dung (có thể dùng HTML/JS/CSS)
- Chọn thời gian hiển thị (hoặc để trống để luôn hiển thị)
- Tick "Kích hoạt" để bật badge
- Lưu lại

### 2. Hiển thị thông báo trên website
- Dán shortcode vào bất kỳ trang/bài viết nào:
  ```
  [thong_bao_dac_biet id="your_id"]
  ```
- Badge chỉ hiển thị khi đang bật (active) và trong thời gian hợp lệ

### 3. Bật/tắt badge
- Trong danh sách badge, dùng switch toggle ở cột "Kích hoạt" để bật/tắt nhanh
- Thay đổi sẽ được lưu ngay (AJAX)

### 4. Sửa/xóa badge
- Nhấn **Sửa** để chỉnh nội dung, thời gian, trạng thái
- Nhấn **Xóa** để xóa badge khỏi hệ thống

## Cấu hình nâng cao
- Bạn có thể chèn CSS/JS vào nội dung badge (chỉ nên dùng cho admin, không cấp quyền cho user không tin cậy)
- Nếu cần style riêng, chỉnh file `css/admin.css` hoặc `css/frontend.css`
- Nếu muốn badge chỉ hiển thị cho một số trang, hãy kiểm tra điều kiện trong template hoặc dùng nhiều shortcode với ID khác nhau

## Hỗ trợ
- Tác giả: Lê Thái ([phapduyen.com](https://phapduyen.com/))
- Github: [https://github.com/thais-lee/wp_plugin_thong_bao_dac_biet](https://github.com/thais-lee/wp_plugin_thong_bao_dac_biet)

Nếu gặp lỗi hoặc cần hỗ trợ, hãy tạo issue trên Github hoặc liên hệ tác giả. 