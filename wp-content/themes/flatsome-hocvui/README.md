# Flatsome Học Vui – website giáo dục Tiểu học (lớp 1–5)

Theme con của **Flatsome**: bài học theo Chương trình GDPT 2018, bám bộ sách dùng chung toàn quốc từ năm học 2026–2027 (**Kết nối tri thức với cuộc sống**, **Tiếng Anh Global Success** – QĐ 3588/QĐ-BGDĐT), kèm luyện tập và trò chơi học tập.

## Bản 0.9: độ khó và đồng hồ đếm ngược, giáo án cho thầy cô, giọng đọc liền mạch hơn

- **Trò chơi có 3 mức độ khó** chọn ngay ở màn chào: 🐣 Dễ · 🐯 Vừa · 🐲 Khó (nhớ theo từng trò chơi).
  - Trò hỏi–đáp: Dễ 20 giây/câu, 3 lựa chọn, xen câu lớp dưới; Vừa 12 giây, điểm x1,5; Khó 7 giây, 3 tim, câu "⚡ Thử thách" của lớp trên, điểm x2. Câu đọc chữ/xem hình được thêm giờ.
  - **Đồng hồ đếm ngược** dạng vòng tròn xanh → cam → đỏ, tích tắc 5 giây cuối, hết giờ tính là sai; trả lời nhanh được **điểm thưởng thời gian**. Có công tắc tắt đếm ngược cho bé nhỏ.
  - Trò riêng: Đúng/Sai 80/60/45 giây, bóng bay, xe đua, chuột chũi nhanh dần, gõ phím 7/5/3 tim, leo núi 2/2/1 quyền trợ giúp, lật thẻ ít/nhiều cặp.
  - Kỷ lục lưu riêng từng mức; đạt 3 sao gợi ý "Lên mức Khó", điểm thấp gợi ý mức dễ hơn. Bài luyện tập trong bài học không đổi (kiến thức là chính).
- **Giáo án cho thầy cô** (`?giao-an=1` trên trang bài học, trang `giao-an` với `[hv_lesson_plans]`): kế hoạch bài dạy theo Công văn 2345/BGDĐT-GDTH soạn tự động từ bài học – yêu cầu cần đạt, đồ dùng, 4 hoạt động có cột GV/HS, câu hỏi luyện tập kèm đáp án và lời giải, điều chỉnh sau bài dạy. In/Lưu PDF khổ A4 hoặc **tải file Word** (`&tai=word`) để chỉnh sửa.
  - Ô mới **Học liệu điện tử hoclieu.vn** trong trang sửa bài: dán đường dẫn bài tương ứng trên hoclieu.vn (sách điện tử NXB Giáo dục) để giáo án và trang bài học dẫn thẳng tới đó. Nội dung hoclieu.vn thuộc bản quyền NXB Giáo dục nên theme chỉ dẫn link, không sao chép.
- **Giọng đọc AI tự nhiên hơn**: gộp các câu ngắn liền nhau để ngữ điệu liền mạch, tải trước 2 đoạn kế tiếp, ngắt nghỉ 350 ms giữa câu (Azure), Cú Bông tươi và nhẹ nhàng hơn; hỗ trợ giọng Google **Chirp 3 HD** (ví dụ `vi-VN-Chirp3-HD-Aoede`).

## Bản 0.8: giọng đọc AI tự nhiên trên mọi máy

- **Giao diện → Giọng đọc AI**: chọn Microsoft Azure Speech (khuyên dùng – giọng HoaiMy nữ, NamMinh nam, gói miễn phí 500 000 ký tự/tháng) hoặc Google Cloud Text-to-Speech, dán khoá API, bấm Nghe thử.
  - Máy chủ tạo MP3 cho từng câu **một lần** rồi lưu ở `uploads/hocvui-tts/`; lần sau phát ngay, không tốn phí. REST: `GET hocvui/v1/tts?t=…&v=female|male|owl&l=vi|en&r=slow|normal|fast` → chuyển tới file MP3.
  - Chống lạm dụng: tối đa 400 ký tự/câu, 300 câu mới/giờ cho mỗi IP, giới hạn ký tự mỗi ngày (chỉnh được). Câu đã lưu luôn phát được.
  - Lỗi/hết hạn mức → tự chuyển sang giọng trình duyệt trong 5 phút.
- Người đọc thứ ba **Cú Bông** (giọng kể chuyện cho bé lớp 1–2).
- **Phát âm chuẩn hơn**: phép tính, ô trống ("4 + … = 9" → "bốn cộng chỗ trống bằng chín"), phân số ("3/4" → "ba phần tư"), ngày tháng ("Quốc khánh 2/9"), giờ ("7:30" → "7 giờ 30 phút"), đơn vị đo (cm, m², kg, km/h…), số có dấu cách hàng nghìn ("10 000"), số thập phân ("2,5" → "2 phẩy 5"), viết tắt (TP. HCM, VD:, SGK), khoảng ("lớp 1–5" → "lớp 1 đến 5"); bảng đọc từng hàng có ngắt nghỉ; bỏ biểu tượng cảm xúc.
- Không dùng chung với plugin/đoạn mã "Học Vui 2.2 – Giọng đọc AI" (ghi đè speechSynthesis, dùng đường dẫn không chính thức của Google Dịch): hãy gỡ bỏ.

## Bản 0.7: giọng đọc Cô giáo / Thầy giáo, tài khoản phụ huynh nhiều bé

- **Giọng đọc** (nút ⚙️): chọn *Cô giáo* (giọng nữ) hoặc *Thầy giáo* (giọng nam), tốc độ *Chậm – Vừa phải – Nhanh* (mặc định vừa phải). Bài đọc được tách từng câu nên rõ ràng, có ngắt nghỉ; phép tính đọc thành lời ("5 + 3 = 8" → "năm cộng ba bằng tám").
  Giọng tự nhiên nhất: Microsoft Edge (HoaiMy nữ, NamMinh nam). Máy không có giọng nam tiếng Việt thì Thầy giáo đọc giọng trầm.
- **Tài khoản phụ huynh** – trang `tai-khoan` với shortcode `[hv_parent]`:
  - Đăng ký (họ tên, email, mật khẩu) / đăng nhập ngay trên trang, quên mật khẩu dùng chức năng của WordPress. Bật/tắt tự đăng ký ở Tuỳ biến → Học Vui.
  - Một phụ huynh thêm tối đa 8 bé (tên, lớp, nhân vật). Mỗi bé có tiến độ riêng lưu trên máy chủ → học tiếp trên mọi thiết bị.
  - Báo cáo từng bé: bài đã học, sao, chuỗi ngày, tiến độ từng môn của lớp, bài tiếp theo, bài nên ôn lại.
  - Sửa bé, cho bé học lại từ đầu, xoá bé, xoá tài khoản.
  - Máy dùng chung: nút 👪 trên thanh nhân vật để đổi bé; đăng nhập mà chưa chọn bé thì hiện "Hôm nay bé nào học?". Đăng xuất thì máy quay về chế độ khách.
  - Lần đầu thêm bé có thể chuyển tiến độ đang học trên máy (chế độ khách) vào hồ sơ của bé.
- Phụ huynh có vai trò riêng `hv_parent`: không thấy thanh quản trị, không vào được wp-admin.
- REST API `hocvui/v1/kids` (cần đăng nhập): trình duyệt tự gửi tiến độ của bé đang học, máy chủ gộp lại để không bao giờ mất sao/XP.

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
