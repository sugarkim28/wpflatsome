# Sao lưu & khôi phục

## Lịch sao lưu đề xuất

| Loại | Tần suất | Giữ | Nơi lưu |
|---|---|---|---|
| CSDL | Hằng ngày | 14 bản | Hosting + bản ngoài (Google Drive/OneDrive) |
| Toàn bộ `wp-content/` | Hằng tuần | 8 bản | Bản ngoài |
| Trước mỗi lần cập nhật/triển khai | Thủ công | Đến lần sau | Tải về máy |

Công cụ: sao lưu tự động của hosting (JetBackup / Acronis…) nếu có; nếu không, cài **một** plugin sao lưu (ví dụ UpdraftPlus) gửi bản sao lên Google Drive. Không cài nhiều plugin sao lưu cùng lúc.

Dữ liệu riêng của hệ thống Tin Học 119 nằm trong:

- Bảng CSDL `wp_th119_tickets`, `wp_th119_warranty`, `wp_th119_logs`, `wp_th119_stats`, `wp_th119_ai_log`, `wp_th119_activity`, `wp_th119_redirects`, `wp_th119_notfound` (tiền tố có thể khác `wp_`).
- Option `th119_settings`, `th119_ai` (khoá API đã mã hoá theo `SECURE_AUTH_SALT` của `wp-config.php` – khôi phục sang máy khác cần giữ nguyên salt, hoặc nhập lại khoá).
- Thư mục `wp-content/uploads/th119-private/` (ảnh khách gửi).

## Khôi phục

1. **Một bài viết / trang**: *Bản sửa đổi* (Revisions) trong trình soạn thảo, hoặc *Thùng rác*.
2. **Cấu hình header/footer**: *Giao diện → Thiết lập Tin Học 119 → Khôi phục cấu hình trước*.
3. **Toàn bộ website**:
   1. Bật chế độ bảo trì (hoặc làm ngoài giờ).
   2. phpMyAdmin → chọn CSDL → *Nhập* file `.sql` đã sao lưu.
   3. Chép lại thư mục `wp-content/` từ bản sao lưu.
   4. Kiểm tra `wp-config.php` (tên CSDL, salt).
   5. *Cài đặt → Đường dẫn tĩnh → Lưu* (làm mới rewrite), xoá cache.
4. Sau khi khôi phục: thử gửi form, tra cứu bảo hành, mở 3 bài cũ.

## Kiểm tra định kỳ

Mỗi tháng một lần, khôi phục thử bản sao lưu gần nhất lên staging để chắc chắn bản sao lưu dùng được.

## Gỡ plugin

Gỡ plugin **không xoá dữ liệu** trừ khi bật *Cài đặt → Xoá dữ liệu khi gỡ plugin* (mặc định tắt).
