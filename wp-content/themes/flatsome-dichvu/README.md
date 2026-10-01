# Flatsome Dịch vụ – website thành lập công ty, thay đổi giấy phép, thuế, kế toán

Theme con của **Flatsome** cho công ty dịch vụ doanh nghiệp: thành lập công ty, thay đổi giấy phép kinh doanh, dịch vụ thuế, kế toán trọn gói.

Giao diện theo phong cách trang tin dịch vụ (tham khảo bố cục ketoananpha.vn, không dùng logo/ảnh/nội dung của họ): chữ Nunito, xanh `#2a4d9f` + cam `#fd6c2b`, header nền xám nhạt (logo + hotline theo khu vực, menu chữ in hoa ở thanh dưới), trang chủ dạng lưới banner + khối chuyên mục có cột phải, trang dịch vụ dạng bài viết (mục lục khung cam, bảng chi phí, bảng giá ma trận, nút *Gọi ngay* theo miền, cột phải *Dịch vụ liên quan / Tham khảo thêm*), footer liệt kê văn phòng/chi nhánh, thanh 3 nút Gọi – Zalo – Messenger trên điện thoại.

Bổ sung theo bố cục timsen.vn: thanh trên cùng màu xanh (khẩu hiệu, *Gửi yêu cầu tư vấn*, hotline, Facebook/YouTube), nút menu nổi bật *Tư vấn miễn phí*, khối giới thiệu, lưới *Dịch vụ của chúng tôi* (icon giữa), dải kêu gọi nền xanh, 4 gói thành lập có *Tiết kiệm* và *Còn … khi dùng dịch vụ kế toán*, khối *Tại sao nên chọn* (số liệu + danh sách tích), quy trình 4 bước nhiều màu, lưới *Có gì mới?*, form lớn cuối trang, popup form tự mở (viền cam nét đứt), footer nền xanh đậm 4 cột.

## Cài đặt

1. Tải thư mục `flatsome-dichvu` lên `wp-content/themes/` (cần theme cha **Flatsome**, không kích hoạt theme cha).
2. **Giao diện → Giao diện** → kích hoạt *Flatsome Dịch vụ – Thành lập công ty, Thuế, Kế toán*.
3. **Giao diện → Tuỳ biến → Website dịch vụ**: tên công ty, tên pháp nhân, MST, hotline, Zalo, email, địa chỉ, giờ làm việc, màu, và:
   - *Hotline theo khu vực* – mỗi dòng `Nhãn | Số` (vd `Miền Nam | 0900 000 000`) → hiện ở header và khối *Gọi ngay*.
   - *Messenger* – tên trang Facebook → nút Messenger nổi + trên thanh điện thoại.
   - *Văn phòng / chi nhánh* – mỗi dòng `Tên | Địa chỉ | Điện thoại (nhiều số cách dấu phẩy) | Email` → footer.
   - *Câu khẩu hiệu trên thanh trên cùng*, *Facebook*, *YouTube* → thanh trên cùng.
   - Mục *Form tư vấn*: *Popup form tự mở sau N giây* (mặc định 25, mỗi phiên 1 lần, 0 = tắt), tiêu đề và dòng phụ của popup. Liên kết `#dang-ky` (vd nút menu *Tư vấn miễn phí*) ở trang không có form sẽ mở popup.
4. **Giao diện → Tạo site mẫu** → bấm **Tạo site mẫu** (dựng 5 nhóm, 20 dịch vụ, trang chủ, bảng giá, giới thiệu, liên hệ, kiến thức, menu, footer, header).
5. **Khách đăng ký → Cài đặt email**: nhập Gmail + mật khẩu ứng dụng → *Gửi email thử*.
6. **Tuỳ biến → Header → Logo**: tải logo (chưa có logo thì hiện tên công ty dạng chữ).
7. **Header**: thanh trên cùng dùng phần tử *HTML* (trái, `[sgd_topbar side="left"]`) và *HTML 2* (phải, `[sgd_topbar side="right"]`); trình tạo đặt phần tử *HTML 3* (nội dung `[sgd_hotlines style="header"]`) bên phải logo và menu ở *Header Bottom*. Nếu bản Flatsome của bạn đặt tên phần tử khác, vào **Flatsome → Header Builder** kéo một phần tử HTML vào bên phải logo và dán shortcode trên.
8. **Sửa giá**: giá trong dữ liệu mẫu chỉ để minh hoạ (mức tham khảo thị trường). Vào **Dịch vụ** → sửa từng dịch vụ → ô *Giá hiển thị* và *Bảng giá*. Đọc lại nội dung, FAQ và quy định trước khi chạy quảng cáo.

## Cấu trúc website

| Trang | URL | Nội dung |
|---|---|---|
| Trang chủ | `/` | Lưới banner 4 dịch vụ chủ lực, dải số liệu, khối chuyên mục (1 dịch vụ lớn + danh sách) cho từng nhóm, cột phải (bảng giá nhanh, form, bài viết), bảng giá thành lập + bảng giá kế toán, Gọi ngay, lý do chọn, quy trình |
| Tất cả dịch vụ | `/dich-vu/` | H1, giới thiệu, nút chuyển nhóm, lưới dịch vụ, nội dung SEO cuối trang, form |
| Nhóm dịch vụ | `/nhom-dich-vu/thanh-lap-doanh-nghiep/` … | Mô tả nhóm (150–300 chữ) + các dịch vụ trong nhóm |
| Chi tiết dịch vụ | `/dich-vu/thanh-lap-cong-ty-tnhh/` | H1, phí, thời gian, banner, đoạn mở đầu, mục lục, **chi phí trọn gói**, **bảng giá ma trận**, **các gói**, Gọi ngay, công việc thực hiện, hồ sơ cần chuẩn bị, quy trình, nội dung, FAQ; cột phải: form, dịch vụ liên quan, tham khảo thêm; cuối trang: cùng chuyên mục |
| Bảng giá | `/bang-gia/` | Bảng phí tóm tắt theo nhóm (tự cập nhật khi sửa giá dịch vụ) |
| Giới thiệu, Liên hệ, Tin tức | `/gioi-thieu/`, `/lien-he/`, `/tin-tuc/` | Trang UX Builder |

Nhóm & dịch vụ mẫu:

- **Thành lập doanh nghiệp**: công ty TNHH, công ty cổ phần, hộ kinh doanh, chi nhánh/VPĐD/địa điểm kinh doanh, công ty vốn nước ngoài.
- **Thay đổi giấy phép kinh doanh**: địa chỉ trụ sở; tên công ty, người đại diện; vốn điều lệ, thành viên/cổ đông; ngành nghề; tạm ngừng/giải thể.
- **Dịch vụ thuế**: khai thuế ban đầu, báo cáo thuế tháng/quý, quyết toán thuế & BCTC, hoàn thuế/giải trình.
- **Dịch vụ kế toán**: kế toán trọn gói (bảng giá theo ngành × số hóa đơn/quý), rà soát – làm lại sổ sách, kế toán hộ kinh doanh.
- **Dịch vụ khác**: bảo hiểm xã hội, chữ ký số – hóa đơn điện tử, đăng ký nhãn hiệu.

## Thêm / sửa một dịch vụ

**Dịch vụ → Thêm dịch vụ**:

- **Tiêu đề** = tên dịch vụ (thành H1), **Tóm tắt (Excerpt)** 1–2 câu = mô tả trên Google.
- **Nội dung**: viết thêm bằng *Tiêu đề 2 (H2)* – tự vào mục lục.
- **Thông tin dịch vụ** (hộp bên dưới):
  - *Tên ngắn trên banner*, *Giá hiển thị*, *Thời gian hoàn thành*, *Icon*. Chưa có ảnh đại diện thì theme tự vẽ banner (tên ngắn + giá); có ảnh đại diện thì dùng ảnh.
  - *Chi phí trọn gói*: mỗi dòng `Khoản | Số tiền`; dòng bắt đầu bằng "Tổng" được tô đậm.
  - *Bảng giá dạng bảng*: dòng `## Tiêu đề` mở bảng, dòng kế là tiêu đề cột, các dòng sau `Ô 1 | Ô 2 | …` (dòng ít ô hơn thì ô cuối tự trải hết hàng; để trống ô đầu `  | …` thì gộp với ô nhóm phía trên), dòng `* ...` là ghi chú.
  - *Các gói*: `Tên | Giá | ý 1; ý 2 | * | Tiết kiệm | Giá ưu đãi (ghi chú)` – vd `Cơ bản | 1.900.000đ | … | | 300.000đ | 1.400.000đ (khi dùng dịch vụ kế toán)`.
  - Trong *Nội dung* có thể chèn bảng (bảng so sánh…): hàng đầu tự tô nền xanh; *Tiêu đề 3* hiện màu đỏ.
  - *Công việc chúng tôi thực hiện*, *Hồ sơ khách hàng cần chuẩn bị*: mỗi dòng 1 ý.
  - *Quy trình*: mỗi dòng `Bước | Mô tả`.
  - *Bảng giá*: mỗi dòng `Tên gói | Giá | ý 1; ý 2; ý 3 | *` (dấu `*` ở cột cuối = gói nổi bật). Bấm *Chọn gói này* trên web sẽ tự ghi tên gói vào form.
  - *Câu hỏi thường gặp*: mỗi dòng `Câu hỏi | Trả lời` (xuất dữ liệu FAQPage cho Google).
  - *Email nhận khách riêng*, tick *Dịch vụ nổi bật* để lên trang chủ.
- Cột phải: chọn **Nhóm dịch vụ**. **Thứ tự** (Thuộc tính trang) để sắp xếp (số nhỏ đứng trước).
- **Nhóm dịch vụ**: sửa nhóm để đặt icon, thứ tự, tiêu đề H1 và mô tả.

## Shortcode cho UX Builder

```
[sgd_featured ids="slug1,slug2,slug3,slug4"]           – lưới banner 1 lớn + 3 (trống = 4 dịch vụ nổi bật)
[sgd_htab text="Tiêu đề" link="/dich-vu/"]             – tiêu đề khối dạng thẻ
[sgd_group_block group="ke-toan" number="5"]           – khối chuyên mục: 1 dịch vụ lớn + danh sách
[sgd_hotlines style="pills|header" title="Gọi ngay"]   – hotline theo khu vực
[sgd_branches]                                         – văn phòng / chi nhánh
[sgd_sidebar form="1"]                                 – cột phải (form, dịch vụ nổi bật, bài viết)
[sgd_groups columns="3" services="3" style="card|simple"] – các nhóm dịch vụ kèm dịch vụ con
[sgd_title text="Có gì mới?" sub=""]                   – tiêu đề giữa, kẻ ngang hai bên
[sgd_posts number="4" style="magazine|grid|links"]     – bài viết
[sgd_topbar side="left|right"]                         – nội dung thanh trên cùng
[sgd_services number="6" columns="3" featured="1" group="dich-vu-thue" layout="grid|list|mini"]
[sgd_pricing service="ke-toan-tron-goi" show="all|table|packages"] – chi phí, bảng giá, các gói của 1 dịch vụ
[sgd_price_table group="ke-toan"]                      – bảng phí tóm tắt
[sgd_steps layout="row"]Bước | Mô tả (mỗi dòng 1 bước)[/sgd_steps]
[sgd_lead_form title="" source="Trang chủ" service="slug-hoac-ID" perks="1" note="1"]
[sgd_call_buttons]
[sgd_company field="company|company_full|hotline|zalo|email|address|tax_code|working_hours"]
[sgd_icon name="building|edit|tax|calculator|stamp|chart|shield|doc|clock|users|wallet|globe|pin|phone|mail|trademark"]
```

Nút hoặc liên kết trỏ tới `#dang-ky` sẽ cuộn xuống form. Trang nào không có form thì nút tự chuyển sang `/lien-he/#dang-ky`.

## Khách đăng ký

- Form gồm họ tên, điện thoại/Zalo, email, dịch vụ cần tư vấn (nhóm theo nhóm dịch vụ), nội dung.
- Lưu ở menu **Khách đăng ký**: lọc theo dịch vụ, xem chi tiết, **Xuất Excel (CSV)**.
- Email báo về email chung **và** email riêng của dịch vụ (nếu đặt). Hook `sgd_lead_created` để nối CRM/Google Sheets/Telegram.
- Chống spam: nonce, honeypot, giới hạn 1 lần/phút/IP, kiểm tra số điện thoại Việt Nam.

## SEO

- Schema: `ProfessionalService` (thông tin công ty), `Service` + `Offer` (giá từng gói, VND), `FAQPage`, `CollectionPage/ItemList`, `BreadcrumbList`.
- Dùng cùng Rank Math: Rank Math lo tiêu đề/mô tả/sitemap; theme tự nhận Rank Math để không xuất trùng Organization/Breadcrumb. Trong Rank Math đặt Schema của *Dịch vụ* = *None*; sitemap bật Dịch vụ, Nhóm dịch vụ, Trang, Bài viết. UX Blocks tự loại khỏi sitemap.
- Không có plugin SEO: theme tự in thẻ meta description.
