# Landing page Bất động sản trên Flatsome

Child theme `wp-content/themes/flatsome-child` biến Flatsome 3.20.x thành landing page dự án bất động sản.

## Có gì

| Thành phần | Mô tả |
|---|---|
| Template **Landing Page Bất động sản** | Hero + form, Tổng quan, Vị trí (ảnh hoặc Google Maps), Tiện ích, Mặt bằng, Thư viện ảnh, Video YouTube, Chính sách bán hàng, Form đăng ký cuối trang |
| Tuỳ biến nội dung | **Giao diện → Tuỳ biến → Landing Bất động sản** – sửa chữ, ảnh, màu, hotline, bật/tắt từng mục (không cần code) |
| Quản lý khách hàng | Menu **Khách đăng ký** trong admin: danh sách, chi tiết, nút **Xuất Excel (CSV)**; email báo khách mới |
| Liên hệ nhanh | Nút Hotline / Zalo / Messenger nổi (desktop), thanh "Gọi ngay – Zalo – Nhận báo giá" (mobile) |
| Popup đăng ký | Tự bật sau N giây (mỗi phiên 1 lần), đặt 0 để tắt |
| Chống spam | Nonce, honeypot, giới hạn 1 lần/phút/IP, kiểm tra số điện thoại Việt Nam |

## Cài đặt

1. **Giao diện → Giao diện → Thêm mới → Tải lên**: cài `flatsome-3.20.11.zip` (theme cha, **không kích hoạt**).
2. Nén thư mục `wp-content/themes/flatsome-child` thành `flatsome-child.zip`, tải lên và **Kích hoạt** (hoặc copy thư mục vào `wp-content/themes/` qua FTP).
3. Kích hoạt license Flatsome (**Flatsome → Register**) để nhận cập nhật.
4. **Trang → Thêm mới** → đặt tên (vd "Trang chủ") → ở khung *Thuộc tính trang* chọn template **Landing Page Bất động sản** → Đăng.
5. **Cài đặt → Đọc** → *Trang chủ hiển thị* = Trang tĩnh → chọn trang vừa tạo.
6. **Giao diện → Tuỳ biến → Landing Bất động sản**: nhập nội dung, ảnh, hotline, Zalo, email nhận khách.

### Menu cuộn trong trang
Tạo menu (Giao diện → Menu) với *Liên kết tuỳ chỉnh*, gán vào vị trí Main Menu của Flatsome:

| Mục | URL |
|---|---|
| Tổng quan | `#tong-quan` |
| Vị trí | `#vi-tri` |
| Tiện ích | `#tien-ich` |
| Mặt bằng | `#mat-bang` |
| Hình ảnh | `#hinh-anh` |
| Chính sách | `#chinh-sach` |
| Đăng ký | `#dang-ky` |

Gợi ý Flatsome: **Tuỳ biến → Header** bật *Sticky header*, header trong suốt cho Hero đẹp hơn.

### Ô nhiều dòng
Mỗi dòng là một mục, dạng `Tiêu đề | Mô tả`, ví dụ:
```
Hồ bơi tràn bờ | Hồ bơi vô cực 50m view sông
Gym & Yoga | Phòng tập hiện đại
```

### Google Maps
Google Maps → Chia sẻ → *Nhúng bản đồ* → copy mã `<iframe ...>` và dán vào ô Google Maps (chỉ chấp nhận link google.com). Nếu đã chọn *Ảnh bản đồ vị trí* thì ảnh được ưu tiên.

## Dùng với UX Builder
Nội dung soạn trong trang bằng UX Builder hiện ngay dưới Hero. Có thể chèn các shortcode vào trang bất kỳ:

```
[bds_lead_form title="Nhận bảng giá" button="Gửi ngay" source="Banner giữa"]
[bds_section name="amenities"]   (hero, overview, location, amenities, floorplans, gallery, video, pricing, register)
[bds_hotline]
```
`source` dùng để biết khách đăng ký từ form nào (hiện trong danh sách khách hàng).

## Lưu ý
- **Email**: hosting thường chặn `mail()`; cài plugin SMTP (vd WP Mail SMTP) để email báo khách tới được hộp thư.
- **Cache trang**: nếu dùng plugin cache, đặt thời gian cache ≤ 10 giờ (nonce của form hết hạn sau 12–24 giờ).
- **Tích hợp CRM / Google Sheets / Telegram**: dùng hook `bds_lead_created`:
  ```php
  add_action( 'bds_lead_created', function ( $post_id, $data ) {
      // $data: name, phone, email, need, message, source
  }, 10, 2 );
  ```
- Theme cha Flatsome là theme thương mại nên không đưa vào repo (xem `.gitignore`).
