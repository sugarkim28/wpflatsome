# Quản lý doanh nghiệp (plugin WordPress)

Phần mềm quản lý cho cửa hàng / doanh nghiệp nhỏ chạy ngay trong trang quản trị WordPress, dùng được với mọi theme (kể cả Flatsome).
Gồm các phân hệ: **Bán hàng – Sổ quỹ & nhà cung cấp – Báo giá, hợp đồng, soạn hồ sơ – Kho – Khách hàng & chăm sóc – Nhân sự: ca làm, chấm công (kết nối máy), đơn từ & nghỉ phép, tính lương – Cổng nhân viên**, cùng Tổng quan, Báo cáo, Nhật ký hoạt động.

## Cài đặt

1. Nén thư mục `quan-ly-doanh-nghiep` thành `.zip` → **Plugin → Cài mới → Tải plugin lên** → Kích hoạt (hoặc chép thư mục vào `wp-content/plugins/`).
2. Menu **Quản lý DN** xuất hiện ở thanh bên trái.
3. Vào **Quản lý DN → Cài đặt**: nhập tên, địa chỉ, điện thoại doanh nghiệp (in trên hoá đơn), kiểm tra tham số lương.
4. Thêm **Nhân viên**, **Sản phẩm** (có thể nhập tồn đầu kỳ), rồi bắt đầu bán hàng.

Yêu cầu: WordPress 6.0+, PHP 7.4+ (có `mbstring`), MySQL/MariaDB. Dữ liệu lưu ở 21 bảng riêng `wp_qldn_*`, không làm nặng bảng bài viết.

## Các phân hệ

| Phân hệ | Chức năng |
|---|---|
| **Bán hàng** | Màn hình bán hàng: quét mã vạch / gõ tên để thêm hàng, sửa số lượng, giá, giảm giá; chọn khách hoặc nhập SĐT để tự tạo khách mới; tính tiền thừa / ghi công nợ; tiền mặt, chuyển khoản, thẻ; phím **F9** thanh toán; in hoá đơn khổ 80mm. Danh sách đơn lọc theo ngày, nhân viên, trạng thái, còn nợ; **thu nợ**; **huỷ đơn** (hàng tự nhập lại kho, trừ doanh số khách); xuất CSV. |
| **Sổ quỹ** | Phiếu thu / phiếu chi, quỹ **tiền mặt** và **ngân hàng**: tồn đầu kỳ, thu, chi, tồn cuối kỳ; lọc, xuất CSV, in phiếu (số tiền bằng chữ, 5 chữ ký). **Phiếu tự động** khi bán hàng có thu tiền, thu nợ khách, huỷ đơn đã thu (hoàn tiền), thu tiền hợp đồng, nhập hàng có trả tiền, trả nợ nhà cung cấp, trả lương – phiếu tự động không xoá được, muốn sửa thì huỷ chứng từ gốc. Thu nợ khách theo tổng: tự trừ vào các đơn còn nợ, cũ trước. |
| **Nhà cung cấp** | Hồ sơ NCC, **công nợ phải trả**: phiếu nhập chọn NCC + số tiền trả ngay, phần còn lại ghi nợ; trả nợ lập phiếu chi; lịch sử phiếu nhập và thanh toán từng NCC. |
| **Báo giá** | Lập báo giá từ hàng trong kho hoặc dịch vụ tự nhập, giảm giá, thuế GTGT (0/5/8/10%), hiệu lực, điều khoản; trạng thái nháp / đã gửi / đồng ý / từ chối / hết hiệu lực; in, tải Word; chuyển thành **đơn bán hàng** (trừ kho, ghi công nợ) hoặc **hợp đồng**. |
| **Hợp đồng** | Mua bán hàng hoá, cung cấp dịch vụ (với khách hàng), lao động (với nhân viên – cần quyền nhân sự). Số hợp đồng tự đánh dạng `01/2026/HĐDV`, giá trị, đã thanh toán / còn phải thu, ngày ký, hiệu lực; **nhắc hợp đồng sắp hết hạn** ở Tổng quan; kiểm tra hợp đồng lao động xác định thời hạn không quá 36 tháng; lập hợp đồng là có sẵn văn bản từ mẫu. |
| **Soạn hồ sơ** | Thư viện mẫu với trường trộn `{{kh_ten}}`, `{{hd_gia_tri_chu}}`, `{{bang_hang}}`… tự điền thông tin công ty, khách hàng (mã số thuế, người đại diện), nhân viên (ngày sinh, CCCD, lương), **số tiền bằng chữ**; trường thiếu dữ liệu để "……" cho điền tay; sửa văn bản trước khi **in / lưu PDF / tải Word**. Có sẵn 5 mẫu: thư báo giá, HĐ mua bán hàng hoá, HĐ dịch vụ, HĐ lao động, biên bản thanh lý. |
| **Kho** | Sản phẩm (mã tự sinh hoặc mã vạch, nhóm hàng, đơn vị, giá vốn, giá bán, tồn tối thiểu); **phiếu nhập** (giá vốn tính lại theo bình quân gia quyền), **phiếu xuất** (hỏng, dùng nội bộ…), **kiểm kê** (nhập số đếm thực tế, hệ thống tự điều chỉnh); **thẻ kho** – lịch sử mọi biến động theo từng mặt hàng; cảnh báo sắp hết hàng; giá trị tồn kho; xuất CSV. Mặc định chặn bán vượt tồn (đổi được trong Cài đặt). |
| **Khách hàng** | Hồ sơ (mã KH, SĐT, nhóm, sinh nhật, nguồn, nhân viên phụ trách), tổng mua, số đơn, **điểm tích luỹ**, **công nợ**, lịch sử mua; tự lên nhóm VIP khi tổng mua đạt ngưỡng; xuất CSV. |
| **Chăm sóc KH** | Ghi nhật ký chăm sóc (gọi điện, Zalo, gặp, email, khiếu nại) kèm kết quả và **lịch hẹn chăm sóc tiếp**; trang việc cần làm: lịch hẹn quá hạn / hôm nay / 7 ngày tới, **sinh nhật trong tháng**, **khách lâu không quay lại**, khách đang nợ. |
| **Nhân viên** | Hồ sơ, bộ phận, chức vụ, CCCD, MST, tài khoản ngân hàng; trả lương theo tháng / ngày / giờ; phụ cấp; lương đóng bảo hiểm; % hoa hồng doanh số; người phụ thuộc; liên kết tài khoản đăng nhập. |
| **Ca làm việc** | Nhiều ca (ca hành chính, ca chiều, **ca đêm qua 0 giờ** – lần chấm buổi sáng tính cho ngày bắt đầu ca), nghỉ giữa ca, xếp ca cho từng nhân viên; ngày nghỉ hằng tuần, danh sách ngày lễ. |
| **Chấm công** | Bảng công tháng dạng lưới: `X` đi làm, `N` nửa ngày, `H` nửa ngày làm + nửa ngày phép, `P` phép, `L` lễ, `K` không lương, `O` ốm, thêm `+giờ` để ghi tăng ca (vd `X+2`); ngày nghỉ tuần / ngày lễ tô màu; nút **+X** điền X ngày làm việc, L ngày lễ. Tính **phút đi muộn / về sớm** theo ca (có thời gian cho phép). Nhân viên tự chấm công trên web / điện thoại – ghi như 1 lần chấm của máy. |
| **Đơn từ & nghỉ phép** | Đơn nghỉ phép năm, nghỉ việc riêng có lương, nghỉ không lương, nghỉ ốm, công tác, đăng ký làm thêm giờ, giải trình đi muộn / quên chấm công; nghỉ nửa ngày; **quản lý trực tiếp hoặc nhân sự duyệt** (không tự duyệt đơn của mình), email báo đơn; duyệt xong **tự ghi bảng công**, huỷ đơn đã duyệt thì trả lại bảng công. **Quỹ phép năm** từng người: được hưởng (Điều 113, 114 BLLĐ; vào làm trong năm tính theo tháng – Điều 66 NĐ 145/2020), đã nghỉ, chờ duyệt, còn lại; chặn đơn vượt quỹ phép, trùng ngày. |
| **Của tôi** (cổng nhân viên) | Mỗi nhân viên đăng nhập (máy tính hoặc điện thoại) thấy: nút chấm công, lịch công tháng (giờ vào / ra, đi muộn), phép năm còn lại, gửi / huỷ đơn, **phiếu lương của chính mình** (tháng đã chốt); quản lý thấy đơn chờ mình duyệt. Nhân viên chỉ có quyền chấm công đăng nhập là vào thẳng trang này. |
| **Máy chấm công** | Kết nối máy vân tay / thẻ / khuôn mặt (ZKTeco, Ronald Jack, Mitaco… giao thức ZK) theo 3 cách: **máy tự gửi lên website** (ADMS / Cloud Server – không cần máy tính), **nhập file** `attlog.dat` từ USB hoặc CSV/TXT xuất từ Wise Eye, Mitaco, hoặc **script đồng bộ** `tools/zk-sync.py` cho máy đời cũ chỉ có mạng LAN. Mỗi lần chấm tự điền giờ vào / ra vào bảng công (lần đầu / cuối ngày), tự tính nửa công, tăng ca theo giờ tan ca (tuỳ chọn); báo cáo đi muộn / về sớm; gán mã chấm công cho nhân viên; máy mới phải được duyệt mới nhận dữ liệu. |
| **Tính lương** | Một nút tính lương cả công ty: lương theo ngày công, **trừ đúng thời gian không làm việc khi đi muộn / về sớm** (tuỳ chọn), tăng ca, phụ cấp, **hoa hồng theo doanh số bán**, thưởng, trừ **BHXH-BHYT-BHTN**, **thuế TNCN lũy tiến** (đã trừ giảm trừ gia cảnh, miễn thuế phần tăng ca vượt lương thường), tạm ứng, khấu trừ → thực lĩnh. Sửa thưởng / tạm ứng / khấu trừ / giờ tăng ca ngay trên bảng; **chốt** (khoá bảng công), **đã trả lương**; in phiếu lương từng người hoặc cả công ty; xuất CSV để chuyển khoản. |
| **Báo cáo** | Doanh thu, giá vốn, lãi gộp, biểu đồ theo ngày, hàng bán chạy, doanh số từng nhân viên, thu tiền theo hình thức, doanh thu theo nhóm khách, chi phí lương, tiền nhập hàng. |
| **Nhật ký hoạt động** | Ghi lại ai làm gì, lúc nào: tạo / huỷ đơn, thu nợ, phiếu kho, kiểm kê, sửa giá, thu chi, sửa lương nhân viên, sửa bảng công, tính / chốt / mở khoá / trả lương, duyệt đơn, hợp đồng, cài đặt. Chỉ xem, không sửa / xoá được. |

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
| Nhân viên bán hàng | Bán hàng, đơn hàng, khách hàng, chăm sóc KH, báo giá, hợp đồng (không thấy giá vốn) |
| Thủ kho | Sản phẩm, nhập / xuất / kiểm kho, xem nhà cung cấp |
| Kế toán - nhân sự | Nhân viên, ca, chấm công, đơn từ, tính lương, sổ quỹ, nhà cung cấp, hợp đồng, báo cáo |
| Nhân viên (chỉ chấm công) | Trang **Của tôi**: chấm công, xem công, gửi đơn, xem phiếu lương của mình |

Mọi vai trò đều có trang **Của tôi**; ai được chọn làm *Quản lý trực tiếp* ở hồ sơ nhân viên thì duyệt đơn của nhân viên đó. Quản trị viên luôn có toàn quyền.

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

- **Lần đầu**: *Tổng quan* → bấm **Tạo dữ liệu mẫu** để xem thử mọi chức năng (chỉ khi phần mềm còn trống), hoặc nhập thật: Cài đặt → Ca làm việc → Nhân viên → Sản phẩm, nhà cung cấp → Số dư đầu kỳ ở Sổ quỹ.
- **Hằng ngày**: nhân viên chấm công (máy hoặc trang *Của tôi*) → bán hàng → quản lý duyệt đơn từ → cuối ngày xem *Tổng quan*, *Sổ quỹ*, *Chăm sóc KH*.
- **Khi nhập hàng**: *Nhập / xuất kho → Phiếu nhập*, chọn nhà cung cấp, đơn giá, số tiền trả ngay (phần còn lại ghi nợ NCC).
- **Cuối tháng**: kiểm kê kho → duyệt hết đơn từ → hoàn thiện *Chấm công* → *Tính lương* → thưởng / tạm ứng → **Chốt** (nhân viên xem được phiếu lương ở *Của tôi*) → **Đã trả lương** (tự lập phiếu chi lương) → *Báo cáo*.

## Mở rộng

```php
add_action( 'qldn_order_created', function ( $order_id ) {
	// Gửi Zalo/Telegram, đẩy Google Sheets, xuất hoá đơn điện tử...
} );
add_action( 'qldn_order_cancelled', function ( $order_id ) {} );
```

Các hàm nghiệp vụ dùng lại được: `qldn_order_create()`, `qldn_stock_move()`, `qldn_customer_save()`, `qldn_care_add()`, `qldn_payroll_build()`, `qldn_pit()`.

## Quy mô

Đã thử với **100 nhân viên** và **1 năm dữ liệu máy chấm công (86.000 lần chấm)** trên máy chủ giới hạn 30 giây / yêu cầu:

| Việc | Kết quả |
|---|---|
| Mở bảng chấm công 100 người × 30 ngày | ~0,7 giây |
| Lưu bảng chấm công / bảng lương 100 người | ~1,3 giây (trình duyệt gom ô thành 1 trường, không bị giới hạn `max_input_vars` 1000 của PHP) |
| Tính lương 100 người | ~0,4 giây |
| Máy chấm công gửi 1 lần chấm | ~5 ms |
| Nhập file 1 năm (2,7 MB) | ~40 giây, tự chia nhiều bước dưới 15 giây, không bị hết thời gian |

Đề xuất máy chủ cho 100–300 nhân viên: VPS hoặc hosting WordPress 2 GB RAM, PHP 8.1+, MySQL / MariaDB, bật sao lưu hằng ngày, `upload_max_filesize` ≥ 8 MB.

## Mẫu văn bản

Mẫu gốc (Bộ luật Dân sự 2015, Luật Thương mại 2005, Bộ luật Lao động 2019, Nghị định 145/2020/NĐ-CP) là **bản tham khảo** – cần luật sư / pháp chế rà soát, bổ sung các chỗ "……" (tỷ lệ phạt, thời hạn thanh toán, bảo hành…) cho từng giao dịch. Sửa hoặc thêm mẫu ở **Soạn hồ sơ → Mẫu văn bản** (quyền Cài đặt); nút *Thêm lại các mẫu gốc đã xoá* khôi phục mẫu gốc. File Word tải về là định dạng `.doc` (HTML) – Word, WPS, LibreOffice mở và sửa được, lưu lại thành `.docx` nếu cần.

## Lưu ý pháp lý khi tính công, lương

- **Không phạt tiền đi muộn**: Điều 127 Bộ luật Lao động 2019 cấm phạt tiền, cắt lương thay cho xử lý kỷ luật lao động. Tuỳ chọn "Trừ lương đi muộn / về sớm" chỉ trừ tiền lương của đúng số phút không làm việc (lương giờ ÷ 60 × số phút); đi muộn nhiều lần thì xử lý theo nội quy lao động.
- **Làm thêm giờ**: tối đa 50% giờ làm bình thường / ngày, 40 giờ / tháng, 200 giờ / năm, một số ngành đến 300 giờ / năm (Điều 107 BLLĐ) – phần mềm ghi nhận, không tự chặn.
- **Làm việc ban đêm** (22h–6h) được trả thêm ít nhất 30% (Điều 98) – nhập vào *Thưởng / phụ cấp* hoặc tạo ca đêm với lương riêng.
- **Ngày lễ**: nhập danh sách ngày nghỉ lễ, tết theo thông báo hằng năm ở Cài đặt (Tết Âm lịch, Giỗ Tổ thay đổi theo năm).

## Lịch sử phiên bản

- **2.0.0**: sổ quỹ (phiếu thu / chi tự động), nhà cung cấp & công nợ phải trả, ca làm việc (ca đêm), ngày nghỉ tuần & ngày lễ, đi muộn / về sớm & trừ lương theo thời gian thực tế, đơn từ & duyệt theo quản lý trực tiếp, quỹ phép năm, cổng nhân viên *Của tôi*, nhật ký hoạt động, dữ liệu mẫu, tuỳ chọn xoá dữ liệu khi gỡ.
- **1.2.0**: báo giá, hợp đồng, soạn hồ sơ từ mẫu, số tiền bằng chữ.
- **1.1.x**: kết nối máy chấm công (ADMS, nhập file, script LAN); chạy tốt với 100+ nhân viên.
- **1.0.0**: bán hàng, kho, khách hàng & chăm sóc, nhân viên, chấm công, tính lương, báo cáo.

## Lưu ý

- Tắt / xoá plugin **không xoá dữ liệu** (trừ khi chọn "Xoá toàn bộ dữ liệu phần mềm" ở Cài đặt rồi mới xoá plugin). Sao lưu cơ sở dữ liệu định kỳ (bảng `wp_qldn_*`).
- Hoá đơn in từ plugin là hoá đơn bán lẻ nội bộ, **không thay thế hoá đơn điện tử** theo Nghị định 123/2020/NĐ-CP (đã sửa đổi bởi Nghị định 70/2025/NĐ-CP).
- Plugin quản lý 1 kho. Nhiều chi nhánh/kho cần mở rộng thêm.
