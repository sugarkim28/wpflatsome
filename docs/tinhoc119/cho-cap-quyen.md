# Việc đang chờ chủ website cung cấp

Mã nguồn đã hoàn tất trong repo. Các việc dưới đây **cần quyền truy cập hoặc thông tin của chủ website**, chưa thể làm từ phía phát triển:

| # | Cần gì | Để làm gì | Ai làm |
|---|---|---|---|
| 1 | Quyền quản trị WordPress hoặc hosting (cPanel/FTP) của tinhoc119.com, hoặc staging | Cài plugin + theme, chạy kiểm thử thật | Chủ website cấp quyền, hoặc tự làm theo [trien-khai.md](trien-khai.md) |
| 2 | Bản sao lưu CSDL + `wp-content` hiện tại | Bắt buộc trước khi triển khai | Chủ website / hosting |
| 3 | Danh sách URL hiện có (sitemap) hoặc quyền xem website | Khảo sát website đang chạy: không truy cập được tinhoc119.com từ môi trường phát triển (bị chặn), nên chưa đối chiếu được URL/bài cũ | Chủ website |
| 4 | Khoá API AI (Anthropic / OpenAI / Gemini) + mức ngân sách/tháng | Bật module AI nội dung | Chủ website tạo khoá, nhập trong trang quản trị hoặc `wp-config.php` |
| 5 | Tài khoản Google Business Profile, Search Console, GA4, Bing | SEO địa phương, đo lường | Chủ website (xem [seo-local.md](seo-local.md)) |
| 6 | Logo gốc (SVG/PNG nền trong) | Thay logo SVG dựng lại tạm thời | Chủ website |
| 7 | Phường, link Google Maps, toạ độ, giờ làm việc chính xác | NAP, LocalBusiness schema, bản đồ | Chủ website |
| 8 | Email nhận thông báo yêu cầu hỗ trợ; cấu hình SMTP | Email không vào spam | Chủ website |
| 9 | Xác nhận nội dung có đánh dấu `[BỔ SUNG …]`, `[XÁC NHẬN …]` | Giới thiệu, Chính sách bảo mật, Điều khoản, mục "Vì sao chọn" | Chủ website |
| 10 | Ảnh thật cửa hàng, kỹ thuật viên, dự án; đánh giá thật kèm link nguồn | Khối dự án, đánh giá, E-E-A-T | Chủ website |
| 11 | Giá dịch vụ đang áp dụng (nếu muốn hiển thị) | Hiện giá tham khảo; hiện tại để "Báo giá sau kiểm tra" | Chủ website |
| 12 | Cloudflare Turnstile (tuỳ chọn) | Chống spam form mạnh hơn | Chủ website |

**Số điện thoại**: hệ thống đọc lại hotline đang cấu hình (mặc định `0914 108 322` từ theme cũ). Không tự thay đổi. Nếu cần đổi, chủ website sửa trong **Tin Học 119 → Cài đặt** (có ghi nhật ký).
