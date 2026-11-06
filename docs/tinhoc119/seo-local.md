# SEO kỹ thuật & SEO địa phương

## Đã có

- **Thẻ meta**: tiêu đề, mô tả, canonical, Open Graph, Twitter Card (tự tắt khi có Rank Math/Yoast/AIOSEO/SEOPress – khi đó plugin vẫn ghi tiêu đề/mô tả do AI tạo vào trường của plugin SEO).
- **Dữ liệu có cấu trúc** (`@graph`): LocalBusiness (tên, địa chỉ, toạ độ, giờ mở cửa, khu vực phục vụ, mạng xã hội), WebSite + SearchAction, BreadcrumbList, TechArticle (tác giả, người kiểm duyệt, ngày cập nhật), Service, FAQPage (chỉ khi FAQ hiển thị thật trên trang). Không có đánh giá sao giả.
- **Robots**: noindex trang tìm kiếm, chuyên mục rỗng, trang kết quả form; `robots.txt` chặn thư mục riêng.
- **Sitemap**: dùng sitemap của WordPress (bỏ sitemap người dùng) hoặc của plugin SEO.
- **Breadcrumb** mọi trang; mục lục với id tiêu đề không dấu; FAQ hiển thị.
- **Ảnh**: tự thêm alt từ tiêu đề nếu thiếu; lazy-load; tạo thêm bản WebP khi bật *Cài đặt → SEO → WebP* (cần máy chủ hỗ trợ WebP).
- **Chuyển hướng 301 + nhật ký 404**: **Tin Học 119 → SEO & chuyển hướng**.
- **Kiểm tra SEO nhanh** (cùng trang): HTTPS, permalink, chặn lập chỉ mục, sitemap, plugin SEO, GA4, Search Console, địa chỉ/toạ độ, logo.

## Google Business Profile (chủ website thực hiện)

1. https://business.google.com → tạo/nhận hồ sơ **Tin Học 119**.
2. Danh mục chính: *Dịch vụ sửa chữa máy tính*; phụ: *Dịch vụ hỗ trợ máy tính*, *Cửa hàng sửa chữa máy tính xách tay*.
3. Địa chỉ **giống hệt** trên website (Hẻm 239 Tân Quý, phường …, Tân Phú, TP.HCM), số điện thoại giống hệt hotline.
4. Giờ mở cửa giống *Cài đặt → Giờ làm việc*. Website: https://tinhoc119.com
5. Ảnh thật: mặt tiền, quầy, kỹ thuật viên đang làm việc.
6. Sau khi xác minh: copy link *Chia sẻ* và mã *Nhúng bản đồ* → dán vào **Tin Học 119 → Cài đặt** (Link Google Maps, Mã nhúng bản đồ, Vĩ độ/Kinh độ).
7. Mời khách thật đánh giá (không mua/đổi quà lấy đánh giá). Đánh giá thật có thể nhập vào mục *Đánh giá* trên web kèm link gốc.

## Google Search Console

1. https://search.google.com/search-console → *Thêm tài sản* → *Tiền tố URL* `https://tinhoc119.com/`.
2. Chọn xác minh *Thẻ HTML* → copy giá trị `content="…"` → dán vào **Cài đặt → Google & chống spam → Mã xác minh Google**.
3. *Sitemaps* → gửi `wp-sitemap.xml` (hoặc `sitemap_index.xml` nếu dùng Rank Math/Yoast).

## Google Analytics 4

1. https://analytics.google.com → tạo thuộc tính → luồng web → copy **Mã đo lường** `G-XXXXXXX`.
2. Dán vào **Cài đặt → Google & chống spam → GA4**. Plugin gửi sự kiện `click_call`, `click_zalo`, `click_request`, `click_directions`, `generate_lead` (gửi form thành công). Đánh dấu `generate_lead`, `click_call`, `click_zalo` là *Sự kiện chính* trong GA4.
3. Nếu đã gắn GA bằng plugin khác (Site Kit…), **để trống** ô GA4 để tránh đếm 2 lần.

## Bing Webmaster Tools

https://www.bing.com/webmasters → *Nhập từ Google Search Console* (nhanh nhất) hoặc xác minh bằng thẻ meta → dán vào **Cài đặt → Google & chống spam → Mã xác minh Bing**.
