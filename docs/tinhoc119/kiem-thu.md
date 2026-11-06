# Kiểm thử

## Đã chạy (môi trường phát triển)

| Kiểm tra | Kết quả |
|---|---|
| `php -l` toàn bộ file PHP plugin + theme (PHP 8.3) | Không lỗi cú pháp |
| `node --check` toàn bộ file JS | Không lỗi |
| `php tests/run.php` – nạp toàn bộ plugin + theme với WordPress giả lập, 51 kiểm tra hàm thuần | Đạt hết |

Phạm vi kiểm thử đơn vị: chuẩn hoá/che số điện thoại, che tên, đọc ngày, tính ngày hết bảo hành (cuối tháng, năm nhuận), token chống spam (quá nhanh, hết hạn, sai form), mã hoá/giải mã khoá API, giờ làm việc, chuẩn hoá đường dẫn chuyển hướng, cắt mô tả SEO, tách FAQ, phát hiện trùng lặp, mục lục bài viết.

## Chưa kiểm thử được – cần staging

Môi trường phát triển **không chạy được WordPress thật** (không tải được WordPress/Flatsome, không truy cập được tinhoc119.com). Vì vậy các phần sau **chưa được kiểm thử thực tế** và phải kiểm trên staging trước khi đưa lên website thật:

- [ ] Kích hoạt plugin: tạo bảng, vai trò, thư mục riêng; không lỗi trên PHP của hosting.
- [ ] Bật theme con trên Flatsome thật; *Thiết lập Tin Học 119 → Áp dụng*; header/footer hiển thị đúng trên máy tính + điện thoại; *Khôi phục* trả về như cũ.
- [ ] URL cũ: mở 5–10 bài cũ, `/dich-vu/…`, `/nhom-dich-vu/…` → không 404; so sánh với danh sách URL đã lưu.
- [ ] Form gửi yêu cầu: gửi có/không ảnh, gửi lỗi (thiếu SĐT), gửi liên tục 4 lần (lần 4 bị chặn), email về hộp thư, yêu cầu hiện trong quản trị, đổi trạng thái, ghi chú công khai.
- [ ] Có plugin cache: form vẫn gửi được sau 1 ngày (token).
- [ ] Tra cứu bảo hành: đúng mã + đúng SĐT → hiện; đúng mã + sai SĐT → không hiện; tắt JavaScript vẫn tra được.
- [ ] Nhập CSV bảo hành 5–10 dòng thử.
- [ ] AI: lưu khoá, *Kiểm tra kết nối AI*, tạo dàn ý + bản nháp, tạo bản nháp; thử bấm *Đăng* bài AI chưa duyệt → bị chuyển "Chờ duyệt"; duyệt → đăng được; xem lịch sử chi phí.
- [ ] Schema: https://search.google.com/test/rich-results với trang chủ, 1 bài, 1 dịch vụ → không lỗi; không trùng LocalBusiness với plugin SEO.
- [ ] Tốc độ: PageSpeed Insights trang chủ + 1 bài (điện thoại).
- [ ] Bảng điều khiển hiện số liệu sau khi có lượt xem/bấm thật.
- [ ] Người dùng vai trò Kỹ thuật viên: vào được Yêu cầu/Bảo hành, không vào được Cài đặt/AI.

Chạy lại kiểm thử đơn vị: `php tests/run.php`.
