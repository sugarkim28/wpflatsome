# Triển khai: staging → website thật

> Mục tiêu: website không ngừng hoạt động, không mất bài viết, không đổi URL cũ.

## 0. Trước khi bắt đầu (bắt buộc)

1. **Sao lưu đầy đủ** website hiện tại: toàn bộ thư mục `wp-content/` + xuất CSDL (xem [sao-luu-khoi-phuc.md](sao-luu-khoi-phuc.md)). Tải bản sao về máy, kiểm tra file mở được.
2. Ghi lại: theme đang bật, danh sách plugin đang bật, *Cài đặt → Đường dẫn tĩnh*, *Cài đặt → Đọc* (trang chủ), menu đang gán.
3. Xuất danh sách URL đang có: `https://tinhoc119.com/wp-sitemap.xml` hoặc sitemap của plugin SEO → lưu lại để so sánh sau khi triển khai.

## 1. Tạo staging

- Cách 1 (khuyên dùng): công cụ Staging của hosting (cPanel *WordPress Toolkit*, DirectAdmin *Installatron*, hoặc tương đương) → tạo bản sao tại `staging.tinhoc119.com`.
- Cách 2: plugin WP Staging (bản miễn phí đủ dùng) – tạo bản sao trong thư mục con.
- Đặt staging **không cho Google lập chỉ mục** (*Cài đặt → Đọc → Ngăn công cụ tìm kiếm*), chỉ trên staging.

## 2. Cài trên staging

1. Đảm bảo theme cha **Flatsome** đã cài (bản quyền của chủ website; repo không chứa Flatsome).
2. Tải 2 thư mục lên qua FTP/Trình quản lý file:
   - `wp-content/plugins/tinhoc119-core/`
   - `wp-content/themes/flatsome-tinhoc119/`
3. **Plugin → Kích hoạt "Tin Học 119 – Lõi hệ thống"**. Plugin chỉ tạo bảng mới (`wp_th119_*`), vai trò "Kỹ thuật viên" và thư mục `uploads/th119-private/`; không sửa dữ liệu có sẵn.
4. Vào **Tin Học 119 → Cài đặt** → kiểm tra/điền: tên, hotline (đọc từ theme cũ – *không tự đổi*), Zalo, email nhận thông báo, địa chỉ, phường, giờ làm việc, link Google Maps, toạ độ.
5. **Tin Học 119 → Công cụ** (mỗi công cụ chỉ tạo phần còn thiếu, có ghi nhật ký):
   - *Tạo cây chuyên mục kiến thức* (7 nhóm A–G + chuyên mục con). Chuyên mục trùng slug có sẵn được giữ nguyên.
   - *Tạo các trang chuẩn* (Kiến thức, Gửi yêu cầu, Đặt lịch, Tra cứu bảo hành, Liên hệ; Giới thiệu/Chính sách/Điều khoản ở dạng nháp). Ô "đặt làm trang chủ" **để trống** ở bước này.
   - *Tạo dịch vụ mẫu* (nháp, giá "Báo giá sau kiểm tra") – chỉ khi chưa có dịch vụ.
   - *Tạo menu* – xem trước, chưa gán vị trí.
6. **Giao diện → Giao diện → Kích hoạt "Flatsome Tin Học 119"**.
7. **Giao diện → Thiết lập Tin Học 119 → Áp dụng** (header, footer, màu). Có nút *Khôi phục* nếu cần.
8. Khi đã xem ổn: *Công cụ* → đặt trang chủ mới và gán menu.
9. Nếu đang dùng plugin cache (LiteSpeed, WP Rocket…): xoá cache.

## 3. Kiểm tra trên staging

Làm theo danh sách trong [kiem-thu.md](kiem-thu.md) (form, tra cứu, email, URL cũ, giao diện điện thoại, tốc độ, schema).

## 4. Đưa lên website thật

1. Sao lưu lại website thật **ngay trước khi** đưa lên (bước 0).
2. Chọn giờ ít khách (tối muộn).
3. Lặp lại bước 2.2 → 2.9 trên website thật (không đồng bộ CSDL staging đè lên website thật, vì website thật có thể đã có bài/khách mới).
4. Kiểm tra nhanh: trang chủ, 3 bài cũ, 1 dịch vụ cũ, gửi 1 yêu cầu thử, tra cứu bảo hành thử, xem email.
5. Search Console → *Sitemaps* → gửi lại sitemap; theo dõi *Trang* / *Lỗi 404* trong 2 tuần (đồng thời xem **Tin Học 119 → SEO & chuyển hướng → Lỗi 404**).

## 5. Quay lui (nếu có sự cố)

- Lỗi giao diện: **Giao diện → Giao diện** → bật lại theme cũ (dữ liệu nằm trong plugin, không mất).
- Header/footer sai: **Giao diện → Thiết lập Tin Học 119 → Khôi phục cấu hình trước**.
- Lỗi nặng (trắng trang): đổi tên thư mục `wp-content/plugins/tinhoc119-core` qua FTP → WordPress tự tắt plugin. Nếu vẫn lỗi → khôi phục bản sao lưu ở bước 4.1.

## Máy chủ Nginx

Apache/LiteSpeed tự đọc file `.htaccess` chặn truy cập ảnh khách gửi. Với **Nginx**, thêm vào cấu hình site:

```nginx
location ^~ /wp-content/uploads/th119-private/ { deny all; return 404; }
```
