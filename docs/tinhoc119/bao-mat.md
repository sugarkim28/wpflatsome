# Bảo mật

## Đã có trong plugin

| Lớp | Mô tả |
|---|---|
| Form công khai | Token ký HMAC (hoạt động cả khi có cache trang), honeypot, kiểm tra cùng nguồn, giới hạn 3 yêu cầu/10 phút/IP, tuỳ chọn Cloudflare Turnstile |
| Tra cứu bảo hành | Bắt buộc mã + SĐT, giới hạn 10 lần/15 phút/IP, che tên, chỉ hiện lịch sử được đánh dấu công khai |
| Ảnh khách gửi | Tối đa 3 ảnh × 5 MB, chỉ JPG/PNG/WebP, mã hoá lại ảnh (loại EXIF/mã độc), lưu thư mục chặn truy cập, chỉ người có quyền xem qua đường dẫn có nonce |
| Đăng nhập | Khoá IP 15 phút sau 5 lần sai (tự tắt nếu có plugin bảo mật khác), thông báo lỗi chung chung |
| Dò tài khoản | Chặn `?author=N` và REST `/wp/v2/users` với khách |
| XML-RPC | Tắt (tuỳ chọn) |
| Header | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS khi có HTTPS |
| Sửa file | `DISALLOW_FILE_EDIT` – không sửa code trong trang quản trị |
| Phân quyền | Quyền riêng: quản lý yêu cầu, bảo hành, xem báo cáo, dùng AI, duyệt AI, cài đặt. Vai trò Kỹ thuật viên |
| Khoá API | Mã hoá (libsodium) trong CSDL, hoặc hằng số trong `wp-config.php`; không bao giờ in ra trang |
| Xuất CSV | Chặn chèn công thức Excel |
| Nhật ký | Ghi thao tác quản trị quan trọng |

## Khoá API AI trong wp-config.php (khuyên dùng)

Thêm phía trên dòng `/* That's all, stop editing! */`:

```php
define( 'TH119_AI_KEY', 'dán-khoá-ở-đây' );
```

File `wp-config.php` không nằm trong repo; không gửi khoá qua chat/email.

## Proxy / Cloudflare

Nếu website đứng sau Cloudflare hoặc proxy, thêm `define( 'TH119_TRUST_PROXY', true );` để giới hạn theo IP thật của khách. **Không** bật khi không có proxy (kẻ xấu có thể giả IP).

## Nên làm thêm ở hosting

- Cập nhật WordPress, Flatsome, plugin hằng tháng (sau khi sao lưu).
- Bật xác thực 2 lớp cho tài khoản quản trị (plugin Two Factor, hoặc tính năng của plugin bảo mật đang dùng).
- Đổi mật khẩu quản trị mạnh; xoá tài khoản không dùng.
- HTTPS bắt buộc (chuyển hướng http → https ở hosting).
- Nginx: chặn `/wp-content/uploads/th119-private/` (xem [trien-khai.md](trien-khai.md)).
