# Vận hành hằng ngày

Menu quản trị: **Tin Học 119**.

## Yêu cầu hỗ trợ / đặt lịch

- Khách gửi form → hệ thống tạo **mã yêu cầu** (`YC…`, đặt lịch `LH…`), lưu CSDL, gửi email tới *Email nhận thông báo* (Cài đặt), hiện số yêu cầu mới trên menu.
- **Tin Học 119 → Yêu cầu hỗ trợ**: lọc theo trạng thái, tìm theo mã/tên/SĐT, xuất CSV.
- Mở một yêu cầu: đổi trạng thái (Mới → Đang kiểm tra → Đang xử lý → Chờ linh kiện/khách → Hoàn thành / Huỷ), giao kỹ thuật viên, ghi chú. Ghi chú đánh dấu **"Hiện cho khách"** sẽ xuất hiện khi khách tra cứu; ghi chú nội bộ thì không.
- Ảnh khách gửi được lưu ở thư mục riêng (không truy cập công khai), đã xoá thông tin EXIF.
- Nút **Tạo phiếu bảo hành** điền sẵn thông tin từ yêu cầu.
- Vai trò **Kỹ thuật viên**: chỉ xem/cập nhật yêu cầu và bảo hành, không vào được cài đặt.

## Bảo hành

- **Tin Học 119 → Bảo hành → Thêm phiếu**: mã phiếu, serial, khách, SĐT, thiết bị, dịch vụ, ngày nhận/trả, số tháng bảo hành (ngày hết hạn tự tính; 31/01 + 1 tháng → 28/02).
- **Nhập CSV** (nhiều phiếu cũ): dòng đầu là tên cột: `code,serial,customer_name,phone,device,service,received_at,returned_at,warranty_months,status,public_note,internal_note` (bắt buộc: customer_name, phone, device); ngày dạng `dd/mm/yyyy` hoặc `yyyy-mm-dd`. Phiếu trùng mã được bỏ qua (không ghi đè); bỏ trống `code` thì hệ thống tự cấp mã. Tối đa 5000 dòng mỗi lần.
- Khách tra cứu tại trang **Tra cứu bảo hành** bằng *mã phiếu / serial / mã yêu cầu* **kèm SĐT**. Chỉ hiện khi khớp cả hai; tên được che (N*** V*** An); giới hạn 10 lần / 15 phút / IP.
- Phiếu hết hạn tự chuyển trạng thái "Hết hạn" mỗi ngày.

## Viết bài kiến thức (chuẩn 15 phần)

1. **Bài viết → Viết bài mới**: nội dung tự điền khung 15 phần (mô tả vấn đề, dấu hiệu, nguyên nhân, cách xử lý từng bước, lệnh, cảnh báo, khi nào cần kỹ thuật viên…).
2. Hộp **Bài kiến thức – thông tin chuẩn**: tóm tắt nhanh, độ khó, thời gian, FAQ (`Câu hỏi | Trả lời` mỗi dòng), dịch vụ liên quan, người kiểm duyệt kỹ thuật, ngày kiểm duyệt, bài nổi bật.
3. Shortcode trong bài: `[th_lenh]sfc /scannow[/th_lenh]` (khung lệnh + nút sao chép), `[th_canh_bao]…[/th_canh_bao]`, `[th_luu_y]…[/th_luu_y]`, `[th_ky_thuat_vien]…[/th_ky_thuat_vien]`.
4. Hộp bên phải **Kiểm tra chất lượng & trùng lặp** trước khi đăng.
5. Hồ sơ người dùng: điền *Chức danh* và *Tiểu sử* để hiện hộp tác giả (E-E-A-T).

## AI nội dung

- Bật và nhập khoá ở **Tin Học 119 → AI nội dung → Cài đặt** (hoặc hằng số `TH119_AI_KEY`). Chọn nhà cung cấp: Anthropic (Claude), OpenAI, Google Gemini, hoặc máy chủ tương thích OpenAI. Đổi nhà cung cấp bất cứ lúc nào.
- **Giới hạn chi phí**: ngân sách USD/tháng + số lượt/ngày; vượt là dừng. Lịch sử từng lượt (tác vụ, người gọi, token, chi phí) ở tab *Lịch sử & chi phí*.
- Tab *Lập kế hoạch*: gợi ý chủ đề, phân tích từ khoá (không bịa số lượt tìm), cụm chủ đề, tiêu đề.
- Tab *Soạn bài*: dàn ý → bản nháp → meta SEO → FAQ → gợi ý liên kết nội bộ (chỉ tới trang có thật) → **Tạo bản nháp**.
- AI đánh dấu chỗ cần kiểm chứng `[CẦN KIỂM TRA: …]` và chỗ cần ảnh thật `[ẢNH: …]`.
- Tab *Bản nháp & duyệt*: người có quyền duyệt (Quản trị/Biên tập) đọc lại, tick xác nhận, chọn *Duyệt, chưa đăng* / *Duyệt & đăng ngay* / *Duyệt & lên lịch*. **Bài AI chưa duyệt không thể đăng** (kể cả bấm Đăng trong trình soạn thảo).

## Bảng điều khiển

**Tin Học 119 → Tổng quan**: yêu cầu mới/đang xử lý, bảo hành, lượt xem 30 ngày, lượt bấm Gọi/Zalo/nút yêu cầu, nguồn truy cập, trang xem nhiều, tình trạng nội dung. Ô nào chưa có số liệu hiện "Chưa có dữ liệu" (không hiển thị số giả). Lượt xem do plugin tự đếm (bỏ bot, bỏ người đăng nhập); số liệu đầy đủ hơn xem trong GA4.

## Nhật ký & lỗi

**Tin Học 119 → Nhật ký & lỗi**: đăng nhập, đăng/sửa/xoá bài, bật/tắt plugin, đổi theme, đổi hotline, cập nhật; lỗi PHP nghiêm trọng gần nhất. Nhật ký giữ 180 ngày.
