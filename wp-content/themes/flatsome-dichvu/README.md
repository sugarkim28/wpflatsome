# Flatsome Dịch vụ – website thành lập công ty, thay đổi giấy phép, thuế, kế toán

Theme con của **Flatsome** cho công ty dịch vụ doanh nghiệp: thành lập công ty, thay đổi giấy phép kinh doanh, dịch vụ thuế, kế toán trọn gói.

Giao diện theo phong cách trang tin dịch vụ (tham khảo bố cục ketoananpha.vn, không dùng logo/ảnh/nội dung của họ): chữ Nunito, xanh `#2a4d9f` + cam `#fd6c2b`, header nền xám nhạt (logo + hotline theo khu vực, menu chữ in hoa ở thanh dưới), trang chủ dạng lưới banner + khối chuyên mục có cột phải, trang dịch vụ dạng bài viết (mục lục khung cam, bảng chi phí, bảng giá ma trận, nút *Gọi ngay* theo miền, cột phải *Dịch vụ liên quan / Tham khảo thêm*), footer liệt kê văn phòng/chi nhánh, thanh 3 nút Gọi – Zalo – Messenger trên điện thoại.

Bổ sung theo bố cục timsen.vn: thanh trên cùng màu xanh (khẩu hiệu, *Gửi yêu cầu tư vấn*, hotline, Facebook/YouTube), nút menu nổi bật *Tư vấn miễn phí*, khối giới thiệu, lưới *Dịch vụ của chúng tôi* (icon giữa), dải kêu gọi nền xanh, 4 gói thành lập có *Tiết kiệm* và *Còn … khi dùng dịch vụ kế toán*, khối *Tại sao nên chọn* (số liệu + danh sách tích), quy trình 4 bước nhiều màu, lưới *Có gì mới?*, form lớn cuối trang, popup form tự mở (viền cam nét đứt), footer nền xanh đậm 4 cột.

## Thiết kế hiện đại & chuẩn SEO (bản 0.3)

Tổng hợp từ việc đo đạc 2 website tham khảo (trang chủ + trang dịch vụ, khổ điện thoại):

| Tiêu chí | ketoananpha.vn | timsen.vn | Theme này |
|---|---|---|---|
| H1 trang chủ | không có | 3 thẻ H1 | **1 H1** ở banner đầu trang |
| Meta description | trang dịch vụ 412 ký tự (bị cắt) | 128–154 ký tự | tự cắt ≤ 155 ký tự (khi không dùng plugin SEO) |
| Title | 58–59 ký tự, có giá | 59–74 ký tự, có giá | tự thêm giá: *Tên dịch vụ – Từ 250.000đ \| Thương hiệu* |
| Schema | ProfessionalService, FAQ | WebSite, Breadcrumb, LocalBusiness | WebSite, ProfessionalService + **OfferCatalog** (giá từng dịch vụ), Service + Offer, FAQPage (trang chủ & dịch vụ), BreadcrumbList, CollectionPage, WebPage + **reviewedBy** |
| Nhảy bố cục (CLS) | 0.368 (banner ảnh) | 0 | ~0: banner dịch vụ vẽ bằng CSS, không slider |
| JavaScript | 47 KB | 165 KB, 54 request | ~4 KB, tải trì hoãn (defer) |
| Popup | không | tự bật ngay | chỉ tự mở trên máy tính sau 30 giây (popup che màn hình điện thoại bị Google hạ điểm) |
| E-E-A-T (YMYL: pháp lý, thuế) | ngày đăng | ISO, số năm | *Cập nhật dd/mm/yyyy · Kiểm duyệt nội dung: Luật sư …* + schema reviewedBy |

Giữ lại điểm mạnh bán hàng của cả hai: giá ngay trong tiêu đề, bảng chi phí tách phí dịch vụ/lệ phí, bảng giá ma trận, 4 gói có *tiết kiệm* và *giá khi dùng kèm kế toán*, hotline theo miền, cột phải dịch vụ liên quan, menu chứa liên kết nhóm dịch vụ.

Giao diện: 1 hàng header (logo – menu – nút *Nhận báo giá*), banner đầu trang có form ngay màn hình đầu, thẻ dịch vụ dạng icon, bo góc 14px, bóng nhẹ, chữ thường (không in hoa toàn bộ), phông *Be Vietnam Pro* (thiết kế cho tiếng Việt, 4 độ đậm, preconnect).

Trang chủ (bản 0.7 – tổng hợp ketoananpha.vn, timsen.vn, tanthanhthinh.com theo hành trình ra quyết định: Nhu cầu → Giá → Tin cậy → Gói → Giải đáp → Hành động; mỗi khối 1 việc, không lặp): 1 Banner H1 + form → 2 Cam kết (thẻ nổi trên mép banner) → 3 Dịch vụ & bảng giá (tab nhóm, thay cho ô nhóm trùng lặp) → 4 Về công ty + số liệu → 5 Quy trình + cam kết → 6 Bảng giá trọn gói (tab) → 7 Chuyên viên `[sgd_team]` (trống = ẩn) → 8 Hỏi đáp + form → 9 Tin tức + đối tác → 10 Gọi ngay. Điện thoại: gói giá, tin tức, tab vuốt ngang; quy trình/cam kết lưới 2 cột; ẩn dải "Tin mới" và form trùng. `[sgd_group_section]` (khối nhóm kiểu Tân Thành Thịnh) vẫn dùng được trong UX Builder nhưng không đặt ở trang chủ để tránh lặp danh mục.

Đồng bộ (bản 0.5): header 2 tầng (logo + hotline/Zalo/giờ làm việc `[sgd_header_info]`, thanh menu xanh toàn chiều ngang); mọi trang (dịch vụ, nhóm, kiến thức, bài viết, bảng giá, giới thiệu, liên hệ) dùng chung dải tiêu đề `[sgd_pagehead]` (breadcrumb + H1); 1 kiểu form, nút, thẻ; footer có thông tin liên hệ kèm icon `[sgd_contact_list]` (tự bỏ dòng trống/chưa nhập). Số liệu uy tín và đoạn giới thiệu sửa ở *Tuỳ biến → Website dịch vụ → Thông tin công ty* (chỉ ghi số liệu thật).

### Việc cần làm để đạt chuẩn SEO

1. **Tuỳ biến → Website dịch vụ**: nhập *Người kiểm duyệt nội dung* + *Chức danh* (luật sư, đại lý thuế, kế toán trưởng…) – quan trọng với nội dung pháp lý, thuế.
2. **Logo**: tải logo (dùng cho og:image khi chia sẻ Facebook/Zalo và schema).
3. **Ảnh đại diện dịch vụ** (tuỳ chọn): ngang 16:9, WebP < 150 KB, tên file có từ khoá, theme tự gán alt = tên dịch vụ.
4. **Rank Math** (khuyên dùng): theme tự nhường tiêu đề/mô tả/OG/Organization/Breadcrumb cho Rank Math. Đặt tiêu đề *Dịch vụ*: `%title% – %customfield(_sgd_price)% | %sitename%`, Schema của *Dịch vụ* = *None* (theme đã xuất Service + Offer + FAQ); sitemap bật Dịch vụ, Nhóm dịch vụ, Trang, Bài viết.
5. **Nội dung**: mỗi dịch vụ 800–1.500 chữ, chia H2/H3, có bảng giá, hồ sơ, quy trình, FAQ; liên kết sang 2–3 dịch vụ liên quan và bài viết hướng dẫn.
6. **Google Business Profile** cùng tên – địa chỉ – số điện thoại với website (NAP thống nhất); khai báo đủ chi nhánh ở *Văn phòng / chi nhánh*.

## Thương hiệu Tin Học 119 & máy chủ nginx

- Logo SVG có sẵn: `assets/img/logo-119.svg` (header), `logo-119-white.svg` (footer nền tối), `icon-119.svg` (favicon). Theme tự dùng khi chưa tải logo; tải logo PNG thật ở **Tuỳ biến → Header → Logo** để ảnh chia sẻ Facebook/Zalo và Google hiển thị chuẩn (2 nền tảng này không đọc SVG).
- Màu mặc định: xanh `#123fb8`, đỏ `#e10b17` (nút, giá), nền tối `#0b1f5c`; khẩu hiệu *Uy tín tạo niềm tin*; hotline/Zalo 0914 108 322.
- **Máy chủ nginx**: nếu đường dẫn có dạng `/index.php/dich-vu/...` là nginx chưa có quy tắc rewrite của WordPress. Thêm vào khối `server` của site: `location / { try_files $uri $uri/ /index.php?$args; }` rồi vào **Cài đặt → Đường dẫn tĩnh** chọn *Tên bài viết* để có đường dẫn gọn (tốt cho SEO). Theme không dùng đường dẫn cứng nên chạy được cả hai dạng.

## Bài viết Kiến thức theo mục nhỏ (bản 0.12)

- 64 bài mới, **4 bài cho mỗi mục nhỏ** của *Kiến thức kế toán* và *Kiến thức pháp lý* (16 mục), nằm trong `inc/posts/*.php` (phap-ly-1, phap-ly-2, thue-1, ke-toan-2). Cộng 10 bài cũ ở `inc/demo-posts.php` là 74 bài.
- Trích dẫn văn bản: trong nội dung viết `[[tvpl:khoa]]` hoặc `[[tvpl:khoa|chữ neo]]` → link toàn văn trên Thư viện Pháp luật (danh sách khoá ở `inc/posts/sources.php`). Cuối mỗi bài tự thêm mục **Nguồn tham khảo** liệt kê các văn bản đã trích.
- Nhập bài: *Giao diện → Tạo site mẫu → **Nhập bài viết Kiến thức*** – chỉ tạo bài còn thiếu và cập nhật bài mẫu chưa sửa tay; bài bạn đã sửa giữ nguyên; trang chủ, dịch vụ, menu không bị đụng.
- Cập nhật Nghị định 141/2026/NĐ-CP: ngưỡng miễn thuế hộ, cá nhân kinh doanh **1 tỷ đồng/năm** (thay 500 triệu), doanh nghiệp doanh thu năm đến 1 tỷ đồng miễn thuế TNDN – đã sửa cả bài *Thuế hộ kinh doanh năm 2026* cũ.

## Bản tiếng Anh (bản 0.11 – Polylang)

1. Cài và kích hoạt **Polylang** (bản miễn phí hoặc Pro). Không cần chạy trình hướng dẫn của Polylang.
2. *Giao diện → Tạo site mẫu → **Tạo bản tiếng Anh***. Nút này: tạo ngôn ngữ Tiếng Việt (mặc định, URL giữ nguyên) và English (`/en/`); 3 nhóm (*Company formation, Accounting & tax, Trademark & other services*) nối với nhóm tiếng Việt; 4 dịch vụ (*FDI company establishment, Representative office, Tax and accounting service, Trademark registration*) nối bản dịch với dịch vụ tiếng Việt tương ứng; trang chủ `/en/`, *About us*, *Contact us*; footer (UX Block `footer-website-en`) và menu *Main menu (English)* gán cho ngôn ngữ English. Nội dung tiếng Việt giữ nguyên.
3. Nếu trình hướng dẫn Polylang đã chọn English làm mặc định (nội dung tiếng Việt bị gán nhầm English, URL có /vi/), bấm lại nút này: theme đặt Tiếng Việt làm mặc định, trả nội dung tiếng Việt về đúng ngôn ngữ và gán lại menu.
4. Nút **VI | EN** tự hiện trên thanh trên cùng và menu điện thoại; Polylang tự thêm thẻ `hreflang` cho Google.

- Chữ cố định của theme (form, nút, tiêu đề khối, bảng giá, breadcrumb…) tự chuyển tiếng Anh trên trang `/en/` theo từ điển `inc/i18n-en.php`; giá `1.000.000đ` hiện `1,000,000 VND`.
- Thông tin ở Tuỳ biến → Website dịch vụ: giá trị mặc định có sẵn bản tiếng Anh; giá trị tự nhập dịch ở *Ngôn ngữ → Bản dịch chuỗi* (nhóm "Website dịch vụ").
- Form tiếng Anh nhận số điện thoại quốc tế (+44…); khách đăng ký từ bản tiếng Anh có nguồn bắt đầu bằng `[EN]`.
- Thêm dịch vụ / bài viết tiếng Anh: sửa bài tiếng Việt → khung *Ngôn ngữ* của Polylang → bấm dấu **+** ở English.

## Menu, trang chủ, bảng giá (bản 0.9)

- Menu kiểu ketoananpha.vn (chữ in hoa, thanh sáng, menu con hộp trắng): Giới thiệu · Dịch vụ thành lập · Dịch vụ kế toán (gồm thuế) · Thay đổi GPKD · Dịch vụ khác · Kiến thức · Liên hệ. Không có "Bảng giá" và nút màu trên menu. Mỗi mục là 1 trang có bài giới thiệu; menu con mở đầu bằng trang "Tổng quan …" của nhóm.
- Trang chủ giới thiệu 4 dịch vụ chính kiểu tanthanhthinh.com `[sgd_group_section … price="0"]` (không hiện giá).
- Menu: THÀNH LẬP CÔNG TY (Công ty TNHH, cổ phần, vốn nước ngoài, chi nhánh, hộ kinh doanh) · DỊCH VỤ KẾ TOÁN (kế toán trọn gói, nội bộ, hộ kinh doanh, khai thuế ban đầu, báo cáo tài chính, quyết toán thuế, làm sổ sách, hoàn thuế GTGT, hoàn thuế TNCN) · THAY ĐỔI GPKD · DỊCH VỤ KHÁC.
- Dưới mỗi bài (bài viết, dịch vụ, bài nhóm) có khối thông tin liên hệ + form; "Bài viết liên quan" là bài dịch vụ chính cùng mục.
- Mọi bảng cùng 1 phong cách (thanh tiêu đề xanh đậm, hàng tiêu đề xanh nhạt, cột đầu in đậm, ghi chú (*) trong khung): bảng giá, chi phí trọn gói, bảng trong bài viết (tự áp dụng; `<caption>` của bảng thành thanh tiêu đề).
- Quy trình hiện dạng timeline (`[sgd_steps]` mỗi dòng `Bước | Mô tả | Thời gian`; `layout="row"` = timeline ngang, tự chuyển dọc trên điện thoại).
- Bảng giá nằm trong bài viết lớn của từng nhóm (`[sgd_price_table group="…"]`, `[sgd_pricing service="…" show="packages|table"]`); bài tự viết chưa chèn bảng giá thì theme tự thêm ở cuối.

## Khối nhóm ở trang chủ lấy theo menu (bản 0.9.5)

Khối `[sgd_group_section group="…"]` (Tư vấn thành lập công ty, Kế toán – thuế, Thay đổi GPKD, Dịch vụ khác): các thẻ bên dưới **lấy đúng các mục con trên menu chính** của mục tương ứng, theo thứ tự trong menu (bỏ mục "Tổng quan" trỏ lại chính nhóm). Thêm / bớt / đổi thứ tự trong **Giao diện → Menu** là trang chủ tự cập nhật. Mục con có thể là dịch vụ, trang, bài viết, chuyên mục hoặc liên kết tự nhập. Thẻ tự chia hàng đầy, cân đối (bản 0.9.6): 3 → 3 · 4 → 4 · 5 → 2 thẻ lớn nằm ngang + 3 · 6 → 3+3 · 7 → 4+3 · 8 → 4+4 · 9 → 3+3+3 · 10 → 4+3+3. Điện thoại: thẻ vuốt ngang. Muốn quay lại kiểu cũ (bài viết Kiến thức liên quan): thêm `source="posts"`.

## Trang Kiến thức dạng chủ đề (bản 0.10.4)

- Trang **Kiến thức** (trang 1) trình bày như ketoananpha.vn/kien-thuc.html: khối **Kiến thức kế toán** và **Kiến thức pháp lý**, mỗi khối có đoạn giới thiệu + lưới thẻ chủ đề (tên, số bài, nút "Xem chi tiết", hình minh hoạ), cuối trang **Bài viết mới nhất** (1 bài lớn + danh sách 2 cột). Trang 2 trở đi là danh sách bài như cũ.
- Thẻ chủ đề = **chuyên mục con**; khối = **chuyên mục cha**. Thêm/sửa ở Bài viết → Chuyên mục (chọn "Chuyên mục hiện tại" là Kiến thức kế toán / Kiến thức pháp lý). Mô tả chuyên mục cha là đoạn giới thiệu của khối.
- Chủ đề chưa có bài hiện "Đang cập nhật" (nút xám) và trang chuyên mục đó tự **noindex** cho tới khi có bài.
- Menu Kiến thức → Kiến thức kế toán, Kiến thức pháp lý. Trang chuyên mục có tab các chủ đề con.

## Đính chính 10 bài theo văn bản gốc (bản 0.10.3)

- Đối chiếu lại với luatvietnam.vn: bổ sung Nghị định 253/2026 (TNCN: tiền ăn ca 1,2 triệu, giảm trừ y tế 23 triệu / giáo dục 24 triệu, khấu trừ 10% từ 5 triệu/lần), Nghị định 68/2026 + Thông tư 152/2025 (hộ kinh doanh: bảng tỷ lệ thuế theo ngành, thuế suất 15/17/20%, chi phí được trừ, hoá đơn từ 1 tỷ), Thông tư 20/2026 + CV 218/CST-TN (chứng từ không dùng tiền mặt), Nghị định 296/2026 (chủ sở hữu hưởng lợi từ 25%, xác thực điện tử, tạm ngừng), Thông tư 99/2025 (đổi tên tài khoản, quy chế hạch toán).
- Sửa: ngưỡng cũ của hộ kinh doanh là 100 triệu; bỏ "TK 332"; bỏ việc huỷ hoá đơn (Nghị định 70/2025 đã bãi bỏ).
- Bấm **Cập nhật menu (giữ trang chủ)**: bài chưa sửa tay tự lên bản mới; bài bạn đã sửa giữ nguyên.

## 10 bài Kiến thức & Đào tạo (bản 0.10.2)

- **Kiến thức** (5 bài, chuyên mục *Kiến thức pháp lý*, *Kiến thức thuế*): thủ tục thành lập công ty 2026 · lịch nộp tờ khai thuế 2026 · khi nào phải thay đổi giấy phép kinh doanh · thuế hộ kinh doanh 2026 (bỏ thuế khoán) · hoá đơn điện tử 2026.
- **Đào tạo** (5 bài, chuyên mục *Bài học kế toán*): kế toán tổng hợp cho người mới · kê khai thuế GTGT · tính thuế TNCN từ tiền lương 2026 · điểm mới Thông tư 99/2025 · chi phí được trừ thuế TNDN.
- Nội dung tự biên soạn, cập nhật theo văn bản có hiệu lực đến 10/2026 (Luật DN sửa đổi 76/2025, NĐ 168/2025 + 296/2026, Luật QLT 108/2025 + NĐ 252/2026, Luật TNDN 67/2025 + NĐ 320/2025, Luật TNCN 109/2025 + NQ 110/2025, Luật GTGT 48/2024 + NĐ 181/2025 + 144/2026, NĐ 68/2026, NĐ 70/2025, TT 99/2025, NQ 198/2025…). Nên rà lại khi có văn bản mới.
- Link nội bộ về trang dịch vụ viết dạng `[[slug-dich-vu|chữ neo]]` / `[[nhom:slug-nhom|chữ neo]]` trong `inc/demo-posts.php`, tự đổi thành link thật khi nhập; dịch vụ chưa có thì chỉ hiện chữ.
- 3 bài "(bài mẫu)" cũ được thay nội dung, giữ nguyên đường dẫn. Bài đã nhập hoặc bạn tự sửa không bị ghi đè khi bấm lại.
- Menu: Đào tạo → *Bài học kế toán*; Kiến thức → *Kiến thức pháp lý*, *Kiến thức thuế*.
- Cập nhật: tải theme đè lên → **Tạo site mẫu → Cập nhật menu (giữ trang chủ)**.

## Slider dịch vụ đầu trang chủ & menu mới (bản 0.10.1)

- Banner đầu trang chủ thành **slider 3 dịch vụ**: Thành lập công ty · Dịch vụ kế toán · Thay đổi GPKD (form tư vấn vẫn ở bên phải). Tự chuyển sau 6 giây, dừng khi rê chuột, bấm tab hoặc vuốt để chuyển. Trang chủ vẫn chỉ có 1 H1.
- Sửa nội dung slide: **Tuỳ biến → Danh sách & trang chủ → Slide đầu trang chủ**, mỗi dòng `Nhãn | Tiêu đề | Chữ nổi bật | Mô tả | Ý 1; Ý 2; Ý 3 | slug nhóm dịch vụ`. Tắt slider: `[sgd_hero slider="0"]`.
- Menu: "Dịch vụ thành lập" → **Thành lập công ty**; thêm **Chữ ký số và hoá đơn điện tử** vào Dịch vụ khác; bỏ "Tra cứu" khỏi menu (trang /tra-cuu/ vẫn còn).
- Cập nhật trên web đang chạy: tải theme mới đè lên → **Giao diện → Tạo site mẫu → Cập nhật menu (giữ trang chủ)**. Không bấm "Tạo site mẫu".

## Menu giống ketoananpha.vn & trang Tra cứu (bản 0.10.0)

- Menu: Giới thiệu · Dịch vụ thành lập · Dịch vụ kế toán · Thay đổi GPKD (Thay đổi tên, Đổi địa chỉ, Thêm ngành nghề, Tăng vốn điều lệ, Thêm cổ đông, Đổi đại diện pháp luật, Đổi loại hình công ty, Cập nhật CCCD) · Dịch vụ khác · Đào tạo · Kiến thức · **Tra cứu** · Liên hệ.
- Trang **Tra cứu** (`/tra-cuu/`, shortcode `[sgd_lookup]`): liên kết tới cổng tra cứu chính thức – doanh nghiệp, mã số thuế doanh nghiệp / cá nhân, hoá đơn điện tử, thuế điện tử, BHXH, nhãn hiệu, văn bản pháp luật – kèm form hỗ trợ.
- Trang chủ: 3 khối chính đổi tên *Dịch vụ thành lập công ty – Dịch vụ kế toán – Thay đổi giấy phép kinh doanh* (nút *Cập nhật menu* tự đổi trên trang chủ đang dùng, không đụng nội dung khác).
- Trang "Chữ ký số, hóa đơn điện tử" (gói chung) vẫn giữ nguyên.

## Menu dịch vụ mới (bản 0.9.9)

Menu chính: GIỚI THIỆU · DỊCH VỤ THÀNH LẬP (Thành lập công ty, Công ty TNHH, Công ty cổ phần, Công ty vốn nước ngoài, FDI company establishment, Chi nhánh công ty, Hộ kinh doanh cá thể) · DỊCH VỤ KẾ TOÁN (Kế toán trọn gói, nội bộ, hộ kinh doanh, Tax and accounting service, Khai thuế ban đầu, Báo cáo tài chính, Quyết toán thuế cuối năm, Làm sổ sách kế toán, Hoàn thuế GTGT, Hoàn thuế TNCN) · THAY ĐỔI GPKD · DỊCH VỤ KHÁC (Hóa đơn điện tử, Bảo hiểm xã hội, Tạm ngừng kinh doanh, Giải thể doanh nghiệp, Đăng ký kinh doanh, VPĐD nước ngoài, Đăng ký nhãn hiệu – logo, Chữ ký số, Đăng ký MST cá nhân, Soạn thảo hợp đồng) · ĐÀO TẠO (Kế toán tổng hợp, Kế toán thuế, Sổ sách kế toán) · KIẾN THỨC · LIÊN HỆ.

- Nút **Giao diện → Tạo site mẫu → Cập nhật menu (giữ trang chủ)**: chỉ tạo các trang dịch vụ còn thiếu (13 trang mới, dữ liệu ở `inc/demo-data-more.php`, giá để "Liên hệ") và dựng lại Menu chính – không ghi đè trang chủ, dịch vụ đã sửa, header, footer.
- Thanh dưới cùng điện thoại: bỏ "Nhận báo giá", còn Gọi điện – Chat Zalo (– Messenger nếu có), icon nhấp nháy.

## Chia sẻ & nút liên hệ (bản 0.9.8)

- Nút chia sẻ ở cuối bài viết, trang dịch vụ, trang nhóm: Facebook, Zalo, X, LinkedIn, Telegram, Sao chép liên kết. Zalo: điện thoại mở bảng chia sẻ của máy (chọn Zalo); máy tính sao chép liên kết để dán vào Zalo (Zalo không có link chia sẻ web công khai).
- Nút Hotline nổi (máy tính): icon đỏ, số điện thoại hiện sẵn; nút Gọi / Zalo / Messenger rung + sóng lan nhấp nháy; thanh dưới cùng điện thoại: nút Gọi nền đỏ, icon Gọi và Zalo nhấp nháy. Tự tắt hiệu ứng khi máy bật "giảm chuyển động".

## Tiêu đề & mô tả trang chủ (bản 0.9.7)

Khi không dùng plugin SEO, trang chủ có tiêu đề chứa từ khoá *Dịch vụ thành lập công ty, kế toán thuế trọn gói – Tin Học 119* (thay cho *Tin Học 119 – Uy tín tạo niềm tin*) và mô tả mở đầu bằng dịch vụ, kết bằng hotline (≤ 155 ký tự). Sửa ở *Tuỳ biến → Website dịch vụ → Trang danh sách dịch vụ → Tiêu đề / Mô tả trang chủ*. Dùng Rank Math thì đặt trong Rank Math.

## Trang nhóm dịch vụ (bản 0.8)

Mỗi mục lớn trên menu (Thành lập công ty, Thay đổi GPKD, Kế toán & Thuế, Dịch vụ khác…) mở trang nhóm dạng bài viết chuyên mục như tanthanhthinh.com: dải tiêu đề (H1) → ảnh nhóm → danh sách dịch vụ gọn (tên – thời gian – giá) → mục lục "Nội dung chính" → bài viết của nhóm (H2/H3) → Gọi ngay → Bài viết liên quan. Sửa bài ở *Dịch vụ → Nhóm dịch vụ → sửa nhóm → Bài viết của nhóm* (trống = bài mẫu trong `inc/demo-articles.php`). Ảnh nhóm: ô "Ảnh đại diện nhóm".

Trang chủ chỉ còn 1 khối giá dạng gọn `[sgd_catalog limit="4" compact="1"]` (tối đa 4 dịch vụ/nhóm, không mô tả); bảng gói chi tiết nằm ở trang dịch vụ và trang Bảng giá.

## Trang Kiến thức (blog)

Theme có giao diện blog riêng (`home.php`, `archive.php`, `search.php`, `single.php`) thay mẫu blog mặc định của Flatsome: không còn widget tiếng Anh, có thẻ bài viết kèm ảnh, chuyên mục, ô tìm kiếm tiếng Việt, form tư vấn, dịch vụ nổi bật. Bài viết hiện ngày cập nhật, thời gian đọc, người kiểm duyệt, mục lục tự động, khối kêu gọi cuối bài, bài liên quan; dữ liệu BlogPosting + BreadcrumbList cho Google; tắt bình luận (tránh spam). Nên đặt **ảnh đại diện** 16:9 cho mỗi bài.

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
[sgd_lead_form source="Trang chủ" service="slug-hoac-ID"]  (mọi form dùng chung 1 mẫu: tiêu đề, 3 lợi ích, 5 ô, nút "Gửi yêu cầu tư vấn")
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
