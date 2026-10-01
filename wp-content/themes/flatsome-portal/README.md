# Flatsome Portal – website tổng hợp dự án bất động sản

Theme con của **Flatsome** cho website giới thiệu nhiều dự án (8–10 dự án, mở rộng dần).

## Cài đặt

1. Tải thư mục `flatsome-portal` lên `wp-content/themes/` (cần theme cha **Flatsome**).
2. **Giao diện → Giao diện** → kích hoạt *Flatsome Portal – Tổng hợp dự án BĐS*.
3. **Giao diện → Tạo site mẫu** → bấm **Tạo site mẫu** (dựng phân loại, 8 dự án, trang chủ, menu, footer).
4. **Giao diện → Tuỳ biến → Website dự án**: tên công ty, hotline, Zalo, email, địa chỉ, màu.
5. **Khách đăng ký → Cài đặt email**: nhập Gmail + mật khẩu ứng dụng → *Gửi email thử*.
6. **Tuỳ biến → Header → Logo**: tải logo công ty (chưa có logo thì hiện tên công ty dạng chữ).

## Cấu trúc URL (chuẩn SEO)

| Trang | URL | Ghi chú |
|---|---|---|
| Danh sách dự án | `/du-an/` | H1 + giới thiệu + bộ lọc + lưới dự án + nội dung SEO cuối trang |
| Chi tiết dự án | `/du-an/ten-du-an/` | 1 H1, bảng thông tin, điểm nổi bật, mục lục tự động, ảnh, bản đồ, FAQ |
| Khu vực | `/khu-vuc/binh-duong/` | Có trang riêng, nên viết mô tả 150–300 chữ |
| Chủ đầu tư | `/chu-dau-tu/ten-cdt/` | Có trang riêng, có logo |
| Loại hình | `/loai-hinh/can-ho/` | Có trang riêng |
| Trạng thái, Khoảng giá | — | Chỉ để lọc, **không** có trang riêng (tránh trang mỏng) |
| Trang đang lọc (`?kv=…&lh=…`) | — | Tự **noindex, follow** |

## Thêm một dự án

**Dự án → Thêm dự án**:

- **Tiêu đề** = tên dự án (thành H1). **Tóm tắt (Excerpt)** 1–2 câu = mô tả trên Google.
- **Ảnh đại diện** ngang 4:3 hoặc 16:9, WebP < 200KB, tên file có nghĩa.
- **Nội dung**: chia mục bằng *Tiêu đề 2 (H2)* – Tổng quan, Vị trí, Tiện ích, Mặt bằng, Giá & thanh toán, Pháp lý… (từ 3 mục H2 trở lên tự có mục lục).
- **Thông tin dự án** (hộp bên dưới): giá, địa chỉ, quy mô, bàn giao, pháp lý, điểm nổi bật, FAQ, thư viện ảnh, bản đồ, website riêng, hotline/email riêng.
- Cột phải: chọn Khu vực, Chủ đầu tư, Loại hình, Trạng thái, Khoảng giá. Tick **Dự án nổi bật** để lên trang chủ.
- **Thứ tự** (Thuộc tính trang) để xếp dự án lên đầu danh sách (số nhỏ đứng trước).

## Shortcode cho UX Builder

```
[sgp_projects number="6" columns="3" featured="1" khu_vuc="binh-duong" loai_hinh="can-ho"]
[sgp_search]
[sgp_terms taxonomy="khu_vuc|chu_dau_tu|loai_hinh" style="cards|logos" columns="4"]
[sgp_lead_form title="" source="Trang chủ" project="ID"]
[sgp_call_buttons]
[sgp_company field="hotline|email|address|company_full|tax_code|working_hours"]
[sgp_count what="projects|areas|developers"]
```

## Cấu hình Rank Math khuyên dùng

- **Titles & Meta → Dự án**: tiêu đề `%title% – Giá & chính sách %currentyear% | %sitename%`; Schema type: *None* (theme đã xuất schema ApartmentComplex/Residence + FAQPage).
- **Sitemap**: bật Dự án, Khu vực, Chủ đầu tư, Loại hình, Bài viết; tắt Tag, Tác giả. (UX Blocks đã tự loại khỏi sitemap.)
- **Breadcrumbs**: bật nếu muốn dùng breadcrumb của Rank Math (theme tự nhận diện, không xuất trùng).

## Khách đăng ký

- Mỗi khách gắn với dự án (form trang dự án tự gắn; form chung cho khách chọn dự án).
- Email báo về email chung **và** email riêng của dự án (nếu đặt).
- Lọc theo dự án, xuất Excel (CSV) theo dự án. Hook `sgp_lead_created` để nối CRM/Google Sheets.

## Xoá dữ liệu mẫu

**Giao diện → Tạo site mẫu → Xoá dữ liệu mẫu**: xoá 7 dự án mẫu + 3 bài mẫu + ảnh minh hoạ (giữ The Collection 688 và mọi nội dung bạn tự tạo).
