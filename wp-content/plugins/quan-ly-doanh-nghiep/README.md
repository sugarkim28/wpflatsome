# Quản lý doanh nghiệp (plugin WordPress)

Phần mềm quản lý cho cửa hàng / doanh nghiệp nhỏ chạy ngay trong trang quản trị WordPress, dùng được với mọi theme (kể cả Flatsome).
Gồm các phân hệ: **Bán hàng – Quản lý kho – Khách hàng & chăm sóc khách hàng – Nhân viên – Chấm công (kết nối máy chấm công) & tính lương**, cùng Tổng quan và Báo cáo.

## Cài đặt

1. Nén thư mục `quan-ly-doanh-nghiep` thành `.zip` → **Plugin → Cài mới → Tải plugin lên** → Kích hoạt (hoặc chép thư mục vào `wp-content/plugins/`).
2. Menu **Quản lý DN** xuất hiện ở thanh bên trái.
3. Vào **Quản lý DN → Cài đặt**: nhập tên, địa chỉ, điện thoại doanh nghiệp (in trên hoá đơn), kiểm tra tham số lương.
4. Thêm **Nhân viên**, **Sản phẩm** (có thể nhập tồn đầu kỳ), rồi bắt đầu bán hàng.

Yêu cầu: WordPress 6.0+, PHP 7.4+ (có `mbstring`), MySQL/MariaDB. Dữ liệu lưu ở 10 bảng riêng `wp_qldn_*`, không làm nặng bảng bài viết.

## Các phân hệ

| Phân hệ | Chức năng |
|---|---|
| **Bán hàng** | Màn hình bán hàng: quét mã vạch / gõ tên để thêm hàng, sửa số lượng, giá, giảm giá; chọn khách hoặc nhập SĐT để tự tạo khách mới; tính tiền thừa / ghi công nợ; tiền mặt, chuyển khoản, thẻ; phím **F9** thanh toán; in hoá đơn khổ 80mm. Danh sách đơn lọc theo ngày, nhân viên, trạng thái, còn nợ; **thu nợ**; **huỷ đơn** (hàng tự nhập lại kho, trừ doanh số khách); xuất CSV. |
| **Kho** | Sản phẩm (mã tự sinh hoặc mã vạch, nhóm hàng, đơn vị, giá vốn, giá bán, tồn tối thiểu); **phiếu nhập** (giá vốn tính lại theo bình quân gia quyền), **phiếu xuất** (hỏng, dùng nội bộ…), **kiểm kê** (nhập số đếm thực tế, hệ thống tự điều chỉnh); **thẻ kho** – lịch sử mọi biến động theo từng mặt hàng; cảnh báo sắp hết hàng; giá trị tồn kho; xuất CSV. Mặc định chặn bán vượt tồn (đổi được trong Cài đặt). |
| **Khách hàng** | Hồ sơ (mã KH, SĐT, nhóm, sinh nhật, nguồn, nhân viên phụ trách), tổng mua, số đơn, **điểm tích luỹ**, **công nợ**, lịch sử mua; tự lên nhóm VIP khi tổng mua đạt ngưỡng; xuất CSV. |
| **Chăm sóc KH** | Ghi nhật ký chăm sóc (gọi điện, Zalo, gặp, email, khiếu nại) kèm kết quả và **lịch hẹn chăm sóc tiếp**; trang việc cần làm: lịch hẹn quá hạn / hôm nay / 7 ngày tới, **sinh nhật trong tháng**, **khách lâu không quay lại**, khách đang nợ. |
| **Nhân viên** | Hồ sơ, bộ phận, chức vụ, CCCD, MST, tài khoản ngân hàng; trả lương theo tháng / ngày / giờ; phụ cấp; lương đóng bảo hiểm; % hoa hồng doanh số; người phụ thuộc; liên kết tài khoản đăng nhập. |
| **Chấm công** | Bảng công tháng dạng lưới: `X` đi làm, `N` nửa ngày, `P` phép, `L` lễ, `K` không lương, `O` ốm, thêm `+giờ` để ghi tăng ca (vd `X+2`); nút **+X** điền nhanh cả tháng trừ Chủ nhật. Nhân viên đã liên kết tài khoản tự bấm **Chấm công vào ca / ra ca** ở trang Tổng quan (làm dưới nửa ca tự tính nửa công). |
| **Máy chấm công** | Kết nối máy vân tay / thẻ / khuôn mặt (ZKTeco, Ronald Jack, Mitaco… giao thức ZK) theo 3 cách: **máy tự gửi lên website** (ADMS / Cloud Server – không cần máy tính), **nhập file** `attlog.dat` từ USB hoặc CSV/TXT xuất từ Wise Eye, Mitaco, hoặc **script đồng bộ** `tools/zk-sync.py` cho máy đời cũ chỉ có mạng LAN. Mỗi lần chấm tự điền giờ vào / ra vào bảng công (lần đầu / cuối ngày), tự tính nửa công, tăng ca theo giờ tan ca (tuỳ chọn); báo cáo đi muộn / về sớm; gán mã chấm công cho nhân viên; máy mới phải được duyệt mới nhận dữ liệu. |
| **Tính lương** | Một nút tính lương cả công ty: lương theo ngày công, tăng ca, phụ cấp, **hoa hồng theo doanh số bán**, thưởng, trừ **BHXH-BHYT-BHTN**, **thuế TNCN lũy tiến** (đã trừ giảm trừ gia cảnh, miễn thuế phần tăng ca vượt lương thường), tạm ứng, khấu trừ → thực lĩnh. Sửa thưởng / tạm ứng / khấu trừ / giờ tăng ca ngay trên bảng; **chốt** (khoá bảng công), **đã trả lương**; in phiếu lương từng người hoặc cả công ty; xuất CSV để chuyển khoản. |
| **Báo cáo** | Doanh thu, giá vốn, lãi gộp, biểu đồ theo ngày, hàng bán chạy, doanh số từng nhân viên, thu tiền theo hình thức, doanh thu theo nhóm khách, chi phí lương, tiền nhập hàng. |

## Kết nối máy chấm công

1. Ở **Nhân viên**, nhập *Mã chấm công* = số ID của người đó trên máy (`0005` và `5` coi là một).
2. **Quản lý DN → Máy chấm công → Kết nối máy** làm theo 1 trong 3 cách:
   - **Cách 1 – ADMS (khuyên dùng):** trên máy vào *COMM → Cloud Server Setting*, nhập tên miền website, cổng 80 (hoặc 443 + HTTPS), khởi động lại máy → máy hiện trong danh sách *Chờ duyệt* → tick **Cho phép**. Cần bật *Đường dẫn tĩnh* (Cài đặt → Đường dẫn tĩnh → Tên bài viết) và website đặt ở gốc tên miền (máy luôn gửi tới `/iclock/`). Máy đời cũ không hỗ trợ HTTPS: hosting phải cho truy cập `http://ten-mien/iclock/` không chuyển sang https.
   - **Cách 2 – Nhập file:** tab *Nhập file*, chọn `attlog.dat` / `.csv` / `.txt`. Nhập lại file cũ không bị trùng.
   - **Cách 3 – Script LAN:** tạo *Khoá API*, cài Python + `pip install pyzk requests`, sửa cấu hình đầu file `tools/zk-sync.py`, đặt lịch chạy 10 phút / lần (Task Scheduler hoặc cron).
3. Tab **Dữ liệu chấm công**: xem giờ vào / ra, đi muộn / về sớm, gán nhân viên cho mã chưa nhận ra, nút *Cập nhật lại bảng công*.
4. Giờ ca, phút cho phép muộn, tự tính tăng ca: **Cài đặt → Chấm công & tính lương**.

Quy tắc: giờ vào = lần chấm đầu, giờ ra = lần chấm cuối trong ngày (quẹt lại trong 5 phút tính 1 lần); làm dưới nửa số giờ chuẩn → `N`; ký hiệu `P`, `L`, `K`, `O` chấm tay được giữ; tháng đã chốt lương không bị thay đổi. Ca đêm qua 0 giờ chưa hỗ trợ (giờ ra sau nửa đêm được tính vào ngày hôm sau).

API cho phần mềm khác: `POST /wp-json/qldn/v1/punches`, header `X-QLDN-Key`, body `{"device":"SN","punches":[{"pin":"5","time":"2026-10-01 08:01:02"}]}`.

## Phân quyền

Plugin tạo sẵn vai trò, gán ở **Người dùng → Sửa → Vai trò**:

| Vai trò | Được dùng |
|---|---|
| Quản lý cửa hàng | Tất cả |
| Nhân viên bán hàng | Bán hàng, đơn hàng, khách hàng, chăm sóc KH (không thấy giá vốn) |
| Thủ kho | Sản phẩm, nhập / xuất / kiểm kho |
| Kế toán - nhân sự | Nhân viên, chấm công, tính lương, báo cáo |
| Nhân viên (chỉ chấm công) | Tự chấm công vào / ra ca |

Quản trị viên luôn có toàn quyền. Người dùng chỉ có vai trò của plugin đăng nhập xong vào thẳng trang **Quản lý DN**.

## Tham số lương mặc định (kiểm tra lại khi có quy định mới)

| Tham số | Mặc định | Căn cứ |
|---|---|---|
| Ngày công chuẩn | 26 ngày/tháng, 8 giờ/ngày | Tuỳ nội quy công ty |
| Tăng ca | 150% | Điều 98 Bộ luật Lao động 2019 (ngày nghỉ tuần 200%, lễ tết 300% – nhập số giờ quy đổi) |
| Bảo hiểm người lao động đóng | 10,5% (BHXH 8%, BHYT 1,5%, BHTN 1%) | Luật BHXH, Luật BHYT, Luật Việc làm |
| Trần lương đóng BH | 20 × lương cơ sở 2.340.000 ₫ | Lương cơ sở theo Nghị định 73/2024/NĐ-CP |
| Giảm trừ gia cảnh | 15.500.000 ₫ bản thân, 6.200.000 ₫/người phụ thuộc | Nghị quyết 110/2025/UBTVQH15, áp dụng từ kỳ tính thuế 2026 |
| Biểu thuế TNCN | 5 bậc: đến 10 tr 5%, 10–30 tr 10%, 30–60 tr 20%, 60–100 tr 30%, trên 100 tr 35% | Luật Thuế TNCN 2025 |

Tất cả sửa được ở **Cài đặt**. Phụ cấp được tính là thu nhập chịu thuế. Nếu công ty có khoản phụ cấp miễn thuế (ăn ca trong mức quy định…), hãy điều chỉnh qua ô *Khấu trừ* hoặc nhờ kế toán đối chiếu.

## Gợi ý quy trình hằng ngày / hằng tháng

- **Hằng ngày**: nhân viên bấm chấm công → bán hàng ở màn hình *Bán hàng* → cuối ngày xem *Tổng quan*, *Chăm sóc KH* (gọi lại khách đã hẹn, chúc mừng sinh nhật).
- **Khi nhập hàng**: *Nhập / xuất kho → Phiếu nhập*, ghi nhà cung cấp, đơn giá nhập.
- **Cuối tháng**: kiểm kê kho → hoàn thiện *Chấm công* → *Tính lương* → nhập thưởng / tạm ứng → **Chốt** → in phiếu lương, xuất CSV chuyển khoản → **Đã trả lương** → xem *Báo cáo*.

## Mở rộng

```php
add_action( 'qldn_order_created', function ( $order_id ) {
	// Gửi Zalo/Telegram, đẩy Google Sheets, xuất hoá đơn điện tử...
} );
add_action( 'qldn_order_cancelled', function ( $order_id ) {} );
```

Các hàm nghiệp vụ dùng lại được: `qldn_order_create()`, `qldn_stock_move()`, `qldn_customer_save()`, `qldn_care_add()`, `qldn_payroll_build()`, `qldn_pit()`.

## Lưu ý

- Huỷ plugin **không xoá dữ liệu**. Sao lưu cơ sở dữ liệu định kỳ (bảng `wp_qldn_*`).
- Hoá đơn in từ plugin là hoá đơn bán lẻ nội bộ, **không thay thế hoá đơn điện tử** theo Nghị định 123/2020/NĐ-CP (đã sửa đổi bởi Nghị định 70/2025/NĐ-CP).
- Plugin quản lý 1 kho. Nhiều chi nhánh/kho cần mở rộng thêm.
