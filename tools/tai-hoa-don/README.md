# Phần mềm tải hoá đơn điện tử

Tải hàng loạt hoá đơn **mua vào / bán ra** từ cổng [hoadondientu.gdt.gov.vn](https://hoadondientu.gdt.gov.vn) cho **nhiều doanh nghiệp**: bảng kê Excel, chi tiết hàng hoá, XML gốc từng hoá đơn.

## Tính năng

- **Danh sách doanh nghiệp** giống Nibot: tìm nhanh theo MST/tên, ghi chú công việc, lịch sử đồng bộ (V: mua vào, R: bán ra), số HĐ mua vào / bán ra / tổng, ẩn doanh nghiệp.
- **Nhập danh sách từ Excel**: copy 3 cột *MST – Tên – Mật khẩu* dán vào là xong; để trống tên thì tự lấy tên theo MST.
- **Lấy tên DN** theo MST khi thêm doanh nghiệp (tên, địa chỉ, cơ quan thuế, tình trạng – từ API công khai của cổng).
- Mỗi doanh nghiệp bật/tắt đồng bộ đầu vào, đầu ra; nút **Lưu & Test đăng nhập**.
- **Xử lý hàng loạt**: chọn các DN (hoặc tất cả), khoảng ngày → phần mềm lần lượt đăng nhập và tải. DN nào lỗi (sai mật khẩu…) được ghi lỗi đỏ dưới tên rồi chuyển sang DN tiếp theo. Sai mật khẩu thì **không thử lại** để tránh bị cổng khoá tài khoản.
- **Tự giải captcha**: có sẵn bảng nhận dạng 30 ký tự của font captcha cổng thuế (đã thử trên captcha thật) – thường không phải gõ. Nếu cổng đổi font, phần mềm hỏi bạn và tự học thêm.
- Tra hoá đơn có mã, không mã, máy tính tiền. Khoảng ngày bất kỳ (tự chia theo tháng, tự lật trang).
- **File Excel theo mẫu Nibot** (`MUA_VAO_<MST>_<kỳ>.xlsx`, `BAN_RA_<MST>_<kỳ>.xlsx`), số liệu chi tiết đọc từ XML:
  - `HoaDon_TongQuat`: loại HĐ, người bán/mua, địa chỉ, ngày, HTTT, ký hiệu, số, trạng thái, kết quả kiểm tra, tiền chưa thuế/thuế/CK TM/phí/thanh toán, dòng Total.
  - `Smart_KTSC_OK`: từng dòng hàng theo mẫu import phần mềm kế toán **Smart Pro** (PC/1111 nếu ≤ 5 triệu, PKT/331 nếu > 5 triệu, TK thuế 1331). HĐ bị huỷ/bị thay thế tách sang `Smart_KTSC_CAN_XEM_XET`.
  - `BangKe_MuaVao`: bảng kê kèm tờ khai 01/GTGT, gom theo hoá đơn + thuế suất; HĐ không chịu thuế / HĐ bán hàng tách sang `BangKe_MuaVao_KCT_HDBH`.
  - `BangKe_HoanThue_OK`: bảng kê kèm Giấy đề nghị hoàn trả.
  - `Doi_TrangThai`: HĐ bị huỷ / thay thế / điều chỉnh so với lần đồng bộ trước.
  - Bán ra: `HoaDon_TongQuat` + `ChiTiet_HangHoa`.
- Chạy lại không tải trùng XML; chỉ tải lại hoá đơn đổi trạng thái.
- **Cổng quá tải** (lỗi "Row retrieval response timeout…"): tự thử lại, rồi chia nhỏ khoảng ngày theo tuần / ngày để tra.
- **Tab Tiện ích**: đọc và xem hoá đơn XML bất kỳ (in / lưu PDF), kiểm tra MST hàng loạt (tên, tình trạng, địa chỉ, cơ quan thuế), nối nhiều file PDF, tách file PDF theo khoảng trang (cần `pypdf`, chay.bat tự cài).
- **Màn hình Đồng bộ** (nút *Đồng bộ* ở mỗi doanh nghiệp hoặc tab Hoá đơn): chọn kỳ (hôm nay, 1 tuần, tháng, quý), 3 nút Đồng bộ ĐẦU VÀO / ĐẦU RA / VÀO-RA; bên phải hiện từng bước (chứng thực, số HĐ tìm thấy, tiến độ tải XML, thời gian) và bảng kết quả từng hoá đơn (Mới / Đổi trạng thái / Đã có, OK / lỗi).
- **Giữ phiên đăng nhập**: trong lúc phần mềm còn mở, đồng bộ lại cùng doanh nghiệp không phải nhập captcha cho tới khi phiên của cổng hết hạn (tự đăng nhập lại khi hết hạn).
- **Tải nhanh hơn**: tải 4 file XML cùng lúc; HĐ đã có XML thì bỏ qua.
- **Tab Hoá đơn** (giống màn hình Nibot): chọn doanh nghiệp, Mua vào / Bán ra / HĐ dịch vụ, lọc theo file (có/không XML, PDF), duyệt nội bộ, trạng thái HĐ, kết quả kiểm tra, ký hiệu, số HĐ, MST/tên/mặt hàng/ghi chú, kỳ (hôm nay, tháng, quý, năm). Bảng có dòng tổng, phân trang 10/20/50/100; sửa trực tiếp ghi chú, duyệt nội bộ, đánh dấu HĐ dịch vụ; mở XML / bản xem HTML / PDF. Nút **Đồng bộ** tải kỳ đang chọn; **Kết xuất** EXCEL.XLSX (mẫu Nibot), XML.ZIP, HTML.ZIP, PDF.ZIP theo đúng bộ lọc.
- **PDF / hoá đơn gốc**:
  - Phần mềm đọc trong XML nhà cung cấp hoá đơn (trường `MSTTCGP`) và mã tra cứu: MISA (`TransactionID`), Viettel (`Mã số bí mật`), BKAV (`InvoiceGUID`), VNPT/MobiFone/Thái Sơn/FAST… Nút **Tra cứu** chép sẵn mã rồi mở trang tra cứu của nhà cung cấp (Viettel cần thêm MST bên bán – hiện khi rê chuột).
  - Tải PDF ở trang đó rồi bấm **+PDF** để gắn vào hoá đơn → lọc "Có PDF", kết xuất PDF.ZIP dùng được file này.
  - Nút **In**: bản thể hiện dựng từ XML (in hoặc "Lưu thành PDF" trong trình duyệt) cho mọi hoá đơn.
  - **Tự tải PDF gốc – MISA meInvoice**: dùng mã tra cứu (`TransactionID`) theo cách tải công khai trong tài liệu của MISA (không cần tài khoản): nút *Tải PDF gốc* ở từng hoá đơn, nút *Tải HĐ gốc hàng loạt* ở tab Hoá đơn, hoặc tích *Tải PDF gốc (MISA)* khi đồng bộ.
  - Viettel, VNPT…: trang tra cứu bắt nhập captcha nên chưa tự động – dùng *Tra cứu* (chép sẵn mã) rồi *+PDF*.

## Cài đặt & chạy

1. Cài [Python 3.8+](https://www.python.org/downloads/) (Windows: tích *Add python.exe to PATH*).
2. Để `taihoadon.py` và `chay.bat` chung một thư mục, bấm đúp **`chay.bat`** (lần đầu tự cài `openpyxl` để xuất Excel).
   macOS/Linux: `pip install openpyxl` rồi `python3 taihoadon.py`.
3. Trình duyệt mở `http://127.0.0.1:8765` → **Thêm doanh nghiệp** hoặc **Nhập danh sách từ Excel** → **Xử lý hàng loạt**.
4. Khi khung *Nhập captcha* hiện ra, gõ ký tự trong ảnh rồi Enter.

## Nơi lưu

```
HoaDon/_cau-hinh/doanh-nghiep.json      danh sách DN (mật khẩu đã mã hoá)
HoaDon/_cau-hinh/captcha-mau.json       mẫu captcha đã học
HoaDon/<MST>/mua-vao_20260901_20260930/
    MUA_VAO_<MST>_20260901_20260930.xlsx      Excel theo mẫu Nibot
    bang-ke-mua-vao_20260901_20260930.csv
    xml/20260905_0101234567_1C26TAA_123.xml   (ngày_MST người bán_ký hiệu_số HĐ)
HoaDon/<MST>/ban-ra_.../
HoaDon/<MST>/_chi-muc.json              hoá đơn đã biết (để đếm HĐ mới, phát hiện đổi trạng thái)
```

## Tuỳ chọn dòng lệnh

| Tuỳ chọn | Ý nghĩa |
|---|---|
| `--out THƯ_MỤC` | Thư mục lưu (mặc định `./HoaDon`) |
| `--port 8765` | Cổng giao diện trên máy |
| `--no-browser` | Không tự mở trình duyệt |
| `--insecure` | Bỏ kiểm tra chứng chỉ SSL – chỉ dùng khi máy báo lỗi SSL với cổng thuế |

## Bảo mật

- Giao diện chỉ mở trên `127.0.0.1` (máy của bạn), có khoá phiên chống trang web khác gọi vào.
- Mật khẩu chỉ lưu trên máy: trên Windows được mã hoá bằng DPAPI (chỉ tài khoản Windows đang dùng giải được); mật khẩu không bao giờ gửi ngược ra giao diện.
- Trên macOS/Linux mật khẩu chỉ được mã hoá đơn giản – hãy bảo vệ thư mục `_cau-hinh`.

## Lưu ý

- Phần mềm dùng các API công khai mà giao diện web của cổng đang dùng; khi Tổng cục Thuế thay đổi cổng, có thể cần cập nhật.
- Nếu tự giải captcha sai, phần mềm tự bỏ mẫu sai và hỏi lại bạn.
- Tải quá nhiều trong thời gian ngắn có thể bị cổng tạm chặn; phần mềm đã giãn cách các lần gọi.

## Kiểm thử

```
python -m unittest test_taihoadon.py
```

Kiểm thử chạy với một cổng giả lập: captcha SVG (học rồi tự giải), đăng nhập nhiều DN, sai mật khẩu, phân trang, chia tháng, XML, chi tiết hàng hoá, phát hiện HĐ bị huỷ, giao diện.
