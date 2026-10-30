# Flatsome Học Vui – website giáo dục Tiểu học (lớp 1–5)

Theme con của **Flatsome**: bài học theo Chương trình GDPT 2018, bám bộ sách dùng chung toàn quốc từ năm học 2026–2027 (**Kết nối tri thức với cuộc sống**, **Tiếng Anh Global Success** – QĐ 3588/QĐ-BGDĐT), kèm luyện tập và trò chơi học tập.

## Bản 0.6: kiến thức là chính, trò chơi là phụ

- Mỗi câu hỏi luyện tập có **lời giải thích "Vì sao?"** (viết sau dấu `=>` trong ô Câu hỏi). Trả lời xong, bé đọc/nghe giải thích rồi bấm "Câu tiếp theo".
- Bước mới **"Em có biết?"** trong mỗi bài: một điều thú vị gắn với bài học (ô *Em có biết?* trong trang sửa bài).
- Trang **Sổ tay kiến thức** (`[hv_handbook]`): gom toàn bộ phần Ghi nhớ theo lớp và môn, có nút in.
- Trang chủ đặt bài học lên trước: `[hv_lesson_tabs so="8"]` (tab Lớp 1–5), "Một bài học có gì?", trò chơi chỉ là phần thưởng ở cuối.
- Trang đảo hiển thị bài học dạng thẻ có mô tả ngắn, nút "Học bài", bài ôn tập cuối chủ đề.
- Sửa: HUD và nút Cú Bông che nội dung trên điện thoại, thẻ bài trên đảo bị chồng lên nhau, thêm bản in gọn.
- Nhắc bật **đường dẫn tĩnh "Tên bài viết"** trong trang quản trị nếu site đang dùng `?p=`.
- Shortcode mới: `[hv_lesson_tabs]`, `[hv_handbook]`, `[hv_button text="" link="" page="games" style="white"]`.

## Bản 0.5: trò chơi hấp dẫn hơn, môn Làm quen máy tính, sửa giao diện trên site thật
- **Cảm giác game cho mọi trò**: đếm ngược 3-2-1, điểm + combo 🔥 (đúng liên tiếp điểm nhân lên), chữ "+điểm" bay lên, Cú Bông nhảy mừng/động viên trong khung chơi, khung phát sáng khi combo, kỷ lục riêng từng trò và từng lớp.
- **5 trò mới** (tổng 16): Đua xe tính nhẩm (đua với xe Cú Bông), Đập chuột chũi (45 giây, số chẵn/lẻ, lớn hơn, kết quả phép tính), Hái táo – tập dùng chuột (nháy, nháy đúp, kéo thả), Tập gõ phím (chữ rơi, bàn phím ảo, chỉ ngón tay), Ghép bộ phận máy tính (kéo thả hoặc chạm).
- **Môn Làm quen máy tính** (ngoại khoá, lớp 1–5, 8 bài): bộ phận máy tính, cầm chuột – nháy chuột, nháy đúp – kéo thả, hàng phím cơ sở, gõ 10 ngón, tư thế an toàn, gõ chữ Việt Telex, dùng Internet an toàn. Trùm cuối: Chuột Nghịch Ngợm.
- **Sửa trên site thật**: tự thay logo mặc định của Flatsome bằng logo Học Vui; tắt script emoji của WordPress (hết ảnh vỡ, tải nhanh hơn); menu không rớt dòng (khung header 1240px, bỏ mục "Trang chủ" – bấm logo để về trang chủ); thanh nhân vật gọn 1 dòng; Cú Bông trợ giúp chuyển sang góc trái để không che nút; tải trước ảnh banner.

## Bản 0.4: logo, menu, chân trang, bài giảng theo sách 2026–2027, 4 trò chơi mới
- **Bộ logo** (`assets/img/brand/`, bản gốc ở `docs/brand/hoc-vui/`): logo ngang Học Vui × Tin Học 119 (header), logo chỉ Học Vui, logo trắng (chân trang), logo dọc, huy hiệu tròn, icon app 512/192, favicon, ảnh chia sẻ Facebook/Zalo 1200×630. Đổi kiểu logo header ở *Tuỳ biến → Học Vui → Logo trên header*; tải logo riêng ở *Tuỳ biến → Header → Logo* thì logo riêng được ưu tiên.
- **Menu vui nhộn**: mỗi mục có biểu tượng 3D (class CSS `hv-i-ten`, ví dụ `hv-i-island`), menu xổ dạng lưới 2 cột có dòng mô tả (ô *Mô tả* của mục menu), nút xanh **Chơi ngay** (class `hv-menu-cta`).
- **Chân trang chuẩn web** `[hv_footer]` (UX Block *Chân trang Học Vui*): logo, giới thiệu, đơn vị phát triển Tin Học 119, mạng xã hội, cột Học theo lớp / Môn học / Khám phá / Liên hệ (hotline, Zalo, email, địa chỉ, giờ hỗ trợ), dải bản quyền + Chính sách bảo mật, Điều khoản sử dụng, Liên hệ, Sơ đồ trang. Trang mẫu: Giới thiệu, Liên hệ, Câu hỏi thường gặp (có dữ liệu FAQPage), Chính sách bảo mật, Điều khoản sử dụng.
- **SEO thương hiệu**: thẻ chia sẻ Open Graph, dữ liệu `EducationalOrganization` + `WebSite` (ô tìm kiếm) trên trang chủ.
- **Bài giảng**: 74 bài; tên chủ đề theo sách Kết nối tri thức (Toán 1: Chủ đề 1–9, Toán 5: Chủ đề 2, 5, 7, 8…), Tiếng Anh theo Unit của Global Success (3: Unit 1, 11; 4: Unit 12, 16; 5: Unit 5). Thêm bài: Khối lập phương – khối hộp chữ nhật, Các số đến 100, Độ dài và đo độ dài, Cộng trừ không nhớ phạm vi 100, Vần an–ăn–ân, Số chẵn số lẻ, Tìm hai số khi biết tổng và hiệu, Không khí – gió – bão, Mạch điện đơn giản, Địa đạo Củ Chi, Jobs.
- **4 trò chơi mới**: Bắn bóng bay, Câu cá – Nghe và chọn, Đúng hay Sai 60 giây, Leo núi tri thức (2 quyền trợ giúp).

## Giao diện trời xanh – đảo bay (bản 0.3)
Phong cách tham khảo các nền tảng học tập trẻ em như VioEdu (không dùng hình ảnh, logo hay nội dung của họ): nền trời xanh mây trắng, thẻ trắng bóng đổ mềm, thẻ pastel, nút xanh lá bo tròn, chữ xanh navy, lộ trình cột vòm, tab *Vì sao chọn Học Vui* (Học sinh / Phụ huynh / Giáo viên), chân trang xanh lá đồi cỏ, Cú Bông trợ giúp nổi góc phải.
Biểu tượng 3D: **Microsoft Fluent Emoji** (giấy phép MIT, `assets/img/3d/LICENSE-fluentui-emoji.txt`), 48 ảnh WebP ~400 KB.

## Phong cách "phá đảo" (bản 0.2)
- **5 hòn đảo = 5 lớp**: Đảo Hạt Mầm, San Hô, Rừng Xanh, Núi Lửa, Ngôi Sao – bản đồ có tiến độ từng đảo.
- **Mỗi bài = 1 màn chơi** theo từng bước: Mở màn (Cú Bông kể tình huống) → Khám phá (từng thẻ ngắn) → Bí kíp ghi nhớ → Nhiệm vụ ngoài đời → Đánh trùm màn → Màn thưởng.
- **Trùm cuối mỗi môn** (10 câu trộn từ mọi màn, 3 tim), cấp độ – XP, chuỗi ngày học 🔥, nhiệm vụ hôm nay, huy hiệu, chọn nhân vật.
- **Giọng đọc tiếng Việt ấm**: tự chọn giọng nữ tự nhiên tốt nhất trên máy (HoaiMy – Edge, Google Tiếng Việt – Chrome, Linh – iPhone/iPad), đọc chậm rãi; nút ⚙️ để đổi giọng, tốc độ, bật tự đọc. Lớp 1–2 tự đọc to mặc định.

## Có gì
| Thành phần | Mô tả |
|---|---|
| Bài học (`/bai-hoc/`) | Phân theo **Lớp** (`/chuong-trinh/lop-1/`) và **Môn** (`/mon/toan/`). Mỗi bài: mục tiêu, nội dung, ví dụ, ghi nhớ, câu hỏi luyện tập, trò chơi gợi ý, video YouTube (tuỳ chọn) |
| 63 bài mẫu | `inc/curriculum.txt` – Toán, Tiếng Việt, Tiếng Anh, TN&XH, Khoa học, LS&ĐL, Đạo đức, Tin học; nội dung tự biên soạn, không chép SGK |
| 16 trò chơi | Tính nhẩm siêu tốc · Đua xe tính nhẩm · Đập chuột chũi · Hái táo (tập dùng chuột) · Tập gõ phím · Ghép bộ phận máy tính · Bắn bóng bay · Đếm hình · Xem đồng hồ · Ghép chữ – chính tả · Câu cá (nghe và chọn) · Sắp xếp câu · Lật thẻ tiếng Anh · Đúng hay Sai 60 giây · Leo núi tri thức · Đố vui khoa học – xã hội. Câu hỏi tự sinh theo đúng kiến thức từng lớp |
| Phần thưởng | Sao (1–3/lượt, +1 khi siêu tốc), 20 nhãn dán, tên bé – lưu trên trình duyệt, không thu thập dữ liệu trẻ |
| Thân thiện trẻ nhỏ | Đọc to câu hỏi (lớp 1 tự đọc), âm thanh bật/tắt, nút to, pháo giấy, chạy tốt trên điện thoại |
| SEO | Mô tả trang, JSON-LD `LearningResource` + `Quiz` (tự nhường cho Rank Math/Yoast) |

## Cài đặt
1. Cài theme cha `flatsome` (không kích hoạt), nén thư mục `flatsome-hocvui` thành zip, tải lên và **Kích hoạt**.
2. **Giao diện → Tạo site mẫu → Tạo site mẫu**: tạo lớp, môn, bài học, trang Trang chủ / Trò chơi / Góc của em / Góc phụ huynh, menu chính.
3. **Giao diện → Tuỳ biến → Học Vui**: chữ banner, màu, liên hệ phụ huynh.
4. Thêm bài: **Bài học → Thêm bài học**, chọn Lớp + Môn, điền ô *Thông tin bài học*. Câu hỏi mỗi dòng: `Câu hỏi | Đáp án đúng | Sai | Sai`.

## Shortcode (dùng trong UX Builder)
`[hv_hero]` `[hv_grades]` `[hv_subjects lop="3"]` `[hv_lessons lop="2" mon="toan" so="6"]` `[hv_games lop="1"]` `[hv_game id="tinh-nham" lop="2"]` `[hv_album]` `[hv_name_form]` `[hv_parent_steps]` `[hv_band]` `[hv_section title="…"]…[/hv_section]` `[hv_pagehead title="…"]`

Mã trò chơi: `tinh-nham`, `dem-hinh`, `xem-gio`, `ghep-chu`, `sap-xep-cau`, `lat-the`, `do-vui`.
