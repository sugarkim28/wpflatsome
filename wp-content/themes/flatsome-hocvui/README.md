# Flatsome Học Vui – website giáo dục Tiểu học (lớp 1–5)

Theme con của **Flatsome**: bài học theo Chương trình GDPT 2018, bám bộ sách dùng chung toàn quốc từ năm học 2026–2027 (**Kết nối tri thức với cuộc sống**, **Tiếng Anh Global Success** – QĐ 3588/QĐ-BGDĐT), kèm luyện tập và trò chơi học tập.

## Có gì
| Thành phần | Mô tả |
|---|---|
| Bài học (`/bai-hoc/`) | Phân theo **Lớp** (`/chuong-trinh/lop-1/`) và **Môn** (`/mon/toan/`). Mỗi bài: mục tiêu, nội dung, ví dụ, ghi nhớ, câu hỏi luyện tập, trò chơi gợi ý, video YouTube (tuỳ chọn) |
| 63 bài mẫu | `inc/curriculum.txt` – Toán, Tiếng Việt, Tiếng Anh, TN&XH, Khoa học, LS&ĐL, Đạo đức, Tin học; nội dung tự biên soạn, không chép SGK |
| 7 trò chơi | Tính nhẩm siêu tốc · Đếm hình · Xem đồng hồ · Ghép chữ – chính tả · Sắp xếp câu · Lật thẻ tiếng Anh (có phát âm) · Đố vui khoa học – xã hội. Câu hỏi tự sinh theo đúng kiến thức từng lớp |
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
