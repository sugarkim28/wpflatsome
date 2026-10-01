# Flatsome Dịch vụ – website thành lập công ty, thay đổi giấy phép, thuế, kế toán

Theme con của **Flatsome** cho công ty dịch vụ doanh nghiệp: thành lập công ty, thay đổi giấy phép kinh doanh, dịch vụ thuế, kế toán trọn gói.

## Cài đặt

1. Tải thư mục `flatsome-dichvu` lên `wp-content/themes/` (cần theme cha **Flatsome**, không kích hoạt theme cha).
2. **Giao diện → Giao diện** → kích hoạt *Flatsome Dịch vụ – Thành lập công ty, Thuế, Kế toán*.
3. **Giao diện → Tuỳ biến → Website dịch vụ**: tên công ty, tên pháp nhân, MST, hotline, Zalo, email, địa chỉ, giờ làm việc, màu.
4. **Giao diện → Tạo site mẫu** → bấm **Tạo site mẫu** (dựng 4 nhóm dịch vụ, 16 dịch vụ, trang chủ, bảng giá, giới thiệu, liên hệ, tin tức, menu, footer, header).
5. **Khách đăng ký → Cài đặt email**: nhập Gmail + mật khẩu ứng dụng → *Gửi email thử*.
6. **Tuỳ biến → Header → Logo**: tải logo (chưa có logo thì hiện tên công ty dạng chữ).
7. **Sửa giá**: giá trong dữ liệu mẫu chỉ để minh hoạ. Vào **Dịch vụ** → sửa từng dịch vụ → ô *Giá hiển thị* và *Bảng giá*. Đọc lại nội dung, FAQ và quy định trước khi chạy quảng cáo.

## Cấu trúc website

| Trang | URL | Nội dung |
|---|---|---|
| Trang chủ | `/` | Banner + form, 4 nhóm dịch vụ, dịch vụ nổi bật, bảng giá (tab), quy trình 4 bước, lý do chọn, FAQ, tin tức, form |
| Tất cả dịch vụ | `/dich-vu/` | H1, giới thiệu, nút chuyển nhóm, lưới dịch vụ, nội dung SEO cuối trang, form |
| Nhóm dịch vụ | `/nhom-dich-vu/thanh-lap-doanh-nghiep/` … | Mô tả nhóm (150–300 chữ) + các dịch vụ trong nhóm |
| Chi tiết dịch vụ | `/dich-vu/thanh-lap-cong-ty-tnhh/` | H1, phí, thời gian, mục lục, **bảng giá theo gói**, công việc thực hiện, hồ sơ cần chuẩn bị, quy trình, nội dung, FAQ, dịch vụ liên quan, form gắn dịch vụ |
| Bảng giá | `/bang-gia/` | Bảng phí tóm tắt theo nhóm (tự cập nhật khi sửa giá dịch vụ) |
| Giới thiệu, Liên hệ, Tin tức | `/gioi-thieu/`, `/lien-he/`, `/tin-tuc/` | Trang UX Builder |

Nhóm & dịch vụ mẫu:

- **Thành lập doanh nghiệp**: công ty TNHH, công ty cổ phần, hộ kinh doanh, chi nhánh/VPĐD/địa điểm kinh doanh, công ty vốn nước ngoài.
- **Thay đổi giấy phép kinh doanh**: địa chỉ trụ sở; tên công ty, người đại diện; vốn điều lệ, thành viên/cổ đông; ngành nghề; tạm ngừng/giải thể.
- **Dịch vụ thuế**: khai thuế ban đầu, báo cáo thuế tháng/quý, quyết toán thuế & BCTC, hoàn thuế/giải trình.
- **Dịch vụ kế toán**: kế toán trọn gói, rà soát – làm lại sổ sách.

## Thêm / sửa một dịch vụ

**Dịch vụ → Thêm dịch vụ**:

- **Tiêu đề** = tên dịch vụ (thành H1), **Tóm tắt (Excerpt)** 1–2 câu = mô tả trên Google.
- **Nội dung**: viết thêm bằng *Tiêu đề 2 (H2)* – tự vào mục lục.
- **Thông tin dịch vụ** (hộp bên dưới):
  - *Giá hiển thị*, *Thời gian hoàn thành*, *Icon*.
  - *Công việc chúng tôi thực hiện*, *Hồ sơ khách hàng cần chuẩn bị*: mỗi dòng 1 ý.
  - *Quy trình*: mỗi dòng `Bước | Mô tả`.
  - *Bảng giá*: mỗi dòng `Tên gói | Giá | ý 1; ý 2; ý 3 | *` (dấu `*` ở cột cuối = gói nổi bật). Bấm *Chọn gói này* trên web sẽ tự ghi tên gói vào form.
  - *Câu hỏi thường gặp*: mỗi dòng `Câu hỏi | Trả lời` (xuất dữ liệu FAQPage cho Google).
  - *Email nhận khách riêng*, tick *Dịch vụ nổi bật* để lên trang chủ.
- Cột phải: chọn **Nhóm dịch vụ**. **Thứ tự** (Thuộc tính trang) để sắp xếp (số nhỏ đứng trước).
- **Nhóm dịch vụ**: sửa nhóm để đặt icon, thứ tự, tiêu đề H1 và mô tả.

## Shortcode cho UX Builder

```
[sgd_groups columns="4" services="4"]                  – các nhóm dịch vụ kèm dịch vụ con
[sgd_services number="6" columns="3" featured="1" group="dich-vu-thue"]
[sgd_pricing service="thanh-lap-cong-ty-tnhh"]        – bảng giá theo gói của 1 dịch vụ
[sgd_price_table group="ke-toan"]                      – bảng phí tóm tắt
[sgd_steps layout="row"]Bước | Mô tả (mỗi dòng 1 bước)[/sgd_steps]
[sgd_lead_form title="" source="Trang chủ" service="slug-hoac-ID" perks="1" note="1"]
[sgd_call_buttons]
[sgd_company field="company|company_full|hotline|zalo|email|address|tax_code|working_hours"]
[sgd_icon name="building|edit|tax|calculator|stamp|chart|shield|doc|clock|users|wallet|globe"]
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
