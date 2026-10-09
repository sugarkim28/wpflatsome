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
  - **Tự tải PDF gốc – MISA meInvoice**: nút *Tải PDF gốc* (từng hoá đơn) hoặc *Tải HĐ gốc hàng loạt* (các dòng đã chọn hoặc tất cả đang lọc). Trình duyệt của bạn tải PDF từ link "Tải hóa đơn dạng PDF" của MISA (`/tra-cuu/DownloadHandler.ashx?Type=pdf&Code=<mã>`) về thư mục **Downloads**; phần mềm theo dõi Downloads và tự gắn file vào đúng hoá đơn. (MISA chặn chương trình tự động nhưng không chặn trình duyệt.) Nếu Chrome hỏi "tải nhiều tệp", chọn *Cho phép*. Thư mục khác: chạy với `--downloads "D:\TaiVe"`.
  - Viettel, VNPT…: trang tra cứu bắt nhập captcha nên chưa tự động – dùng *Tra cứu* (chép sẵn mã) rồi *+PDF*.
  - **Gắn PDF gốc có sẵn** (tab Hoá đơn): chọn cùng lúc nhiều file PDF hoá đơn đã có trên máy – phần mềm đọc ký hiệu, số hoá đơn, MST trong PDF và tự gắn vào đúng hoá đơn (chỉ gắn khi khớp chắc chắn một hoá đơn; báo rõ file nào không khớp).
  - **Tự tải PDF gốc – EasyInvoice (SoftDreams)**: *Tải PDF gốc* / *Tải HĐ gốc hàng loạt* – phần mềm tự vào trang tra cứu của người bán (`<MST>hd.easyinvoice.com.vn`), tự đọc captcha 4 chữ số, tra bằng mã tra cứu trong XML rồi tải PDF như nút "Tải PDF & đính kèm" và gắn vào hoá đơn.
  - Nút *Tra cứu*: MISA mở thẳng đúng hoá đơn (`?sc=<mã>`); EasyInvoice mở trang tra cứu riêng của người bán (`<MST>hd.easyinvoice.com.vn`).

## Cài đặt & chạy

1. Cài [Python 3.8+](https://www.python.org/downloads/) (Windows: tích *Add python.exe to PATH*).
2. Để `taihoadon.py` và `chay.bat` chung một thư mục, bấm đúp **`chay.bat`** (lần đầu tự cài `openpyxl` để xuất Excel).
   macOS/Linux: `pip install openpyxl` rồi `python3 taihoadon.py`.
3. Trình duyệt mở `http://127.0.0.1:8765` → **Thêm doanh nghiệp** hoặc **Nhập danh sách từ Excel** → **Xử lý hàng loạt**.
4. Khi khung *Nhập captcha* hiện ra, gõ ký tự trong ảnh rồi Enter.

## Bản web trên VPS (nhiều nhân viên)

Cùng một file `taihoadon.py`, chạy với `--server` thành **bản web**: mọi người làm việc qua trình duyệt tại `https://tên-miền-của-bạn`, dữ liệu nằm chung trên máy chủ.

- **Đăng nhập & phân quyền**: *Quản trị* thấy tất cả doanh nghiệp, thêm/xoá/nhập DN, quản lý người dùng; *Nhân viên* chỉ thấy và đồng bộ các DN được giao (tab **Quản trị** → *Thêm người dùng* → tích các DN). Khoá tài khoản hoặc đặt lại mật khẩu thì phiên của người đó bị đăng xuất ngay.
- Mỗi người một lượt đồng bộ riêng, chạy song song (mặc định tối đa 4 lượt cùng lúc trên máy chủ); hai người không đồng bộ trùng một DN cùng lúc.
- **Đồng bộ tự động hằng ngày** (tab Quản trị): đến giờ đặt sẵn, máy chủ tự đồng bộ mua vào + bán ra cho mọi DN đang hiển thị, tự giải captcha. Kỳ mặc định: tháng này, tới ngày 20 thì gồm cả tháng trước (kịp kê khai).
- **Chuông thông báo**: HĐ đổi trạng thái (bị huỷ, thay thế, điều chỉnh), HĐ mới và lỗi của lượt tự động – mỗi người chỉ thấy thông báo của DN mình phụ trách.
- **PDF gốc MISA**: máy chủ thử tải trước; nếu MISA chặn, trình duyệt của bạn tải về máy rồi bấm *Gắn PDF gốc có sẵn* chọn các file vừa tải (bản web không nhìn thấy thư mục Downloads trên máy bạn).

### Cài trên VPS có FASTPANEL (khuyên dùng nếu VPS đã cài FASTPANEL)

FASTPANEL đã có nginx + SSL, nên **không dùng Docker/Caddy** (sẽ tranh cổng 80/443). App chạy nền bằng systemd ở `127.0.0.1:8765`, FASTPANEL làm site *Reverse proxy* có HTTPS.

1. **Tên miền**: tạo bản ghi A `hoadon.congty.vn` → IP VPS.
2. **Cài app** (SSH vào VPS bằng root):
   ```bash
   git clone https://github.com/sugarkim28/wpflatsome.git   # hoặc chép thư mục tai-hoa-don lên bằng WinSCP
   sudo bash wpflatsome/tools/tai-hoa-don/web/fastpanel/cai-dat.sh hoadon.congty.vn
   ```
   Cuối màn hình in **tài khoản quản trị đầu tiên** (`admin` + mật khẩu). Ghi lại.
3. **FASTPANEL** → *Tạo site* → chọn mẫu **Reverse proxy** → tên miền `hoadon.congty.vn`, địa chỉ chuyển tiếp (proxy) **`http://127.0.0.1:8765`** → tạo.
4. Trong site vừa tạo: **SSL** → *Let's Encrypt* → cấp chứng chỉ, bật **chuyển hướng HTTP → HTTPS**.
5. Site → **Cấu hình nginx**: dán nội dung file `web/fastpanel/nginx-them.conf` vào khối `server { … }` (tăng giới hạn upload 80 MB, thời gian chờ 15 phút, chuyển đúng tên miền / HTTPS cho app) → Lưu.
6. Mở `https://hoadon.congty.vn` → đăng nhập `admin` → **đổi mật khẩu** → thêm doanh nghiệp, người dùng.

| Việc | Lệnh |
|---|---|
| Cập nhật phiên bản mới | `cd wpflatsome && git pull && sudo bash tools/tai-hoa-don/web/fastpanel/cai-dat.sh` |
| Xem nhật ký | `journalctl -u taihoadon -f` |
| Dừng / chạy lại | `systemctl stop taihoadon` / `systemctl restart taihoadon` |
| Quên mật khẩu quản trị | `sudo bash tools/tai-hoa-don/web/fastpanel/dat-mat-khau.sh admin` |
| Sao lưu (giữ 14 bản, vào `/root/sao-luu-taihoadon`) | `sudo bash tools/tai-hoa-don/web/fastpanel/sao-luu.sh` – hằng ngày: `crontab -e` → `30 23 * * * bash /root/wpflatsome/tools/tai-hoa-don/web/fastpanel/sao-luu.sh` |

Dữ liệu nằm ở `/var/lib/taihoadon` (thay cho `web/data` của cách Docker), cấu hình ở `/etc/taihoadon.env`. Nếu FASTPANEL có mục *Backup*, thêm thư mục `/var/lib/taihoadon` vào sao lưu.

Lỗi thường gặp: *502 Bad Gateway* → app chưa chạy (`systemctl status taihoadon`); *Host không hợp lệ* → tên miền trong `/etc/taihoadon.env` khác tên miền site, sửa rồi `systemctl restart taihoadon`; gắn nhiều PDF báo *413* → chưa dán `nginx-them.conf`.

### Cài trên VPS trống bằng Docker (khoảng 10 phút)

Cần: một VPS Ubuntu 22.04/24.04 (khuyên dùng **VPS đặt tại Việt Nam** – cổng thuế và trang tra cứu nhà cung cấp ổn định hơn với IP trong nước; 2 CPU, 2 GB RAM, ổ 40 GB+ tuỳ số hoá đơn) và một tên miền con (vd `hoadon.congty.vn`) có bản ghi **A** trỏ về IP VPS.

```bash
# trên VPS, đăng nhập root
git clone https://github.com/sugarkim28/wpflatsome.git && cd wpflatsome/tools/tai-hoa-don/web   # hoặc chép thư mục tai-hoa-don lên VPS bằng WinSCP
sudo bash cai-dat.sh          # hỏi tên miền, cài Docker, chạy app + Caddy (HTTPS tự động)
```

Cuối màn hình in ra **tài khoản quản trị đầu tiên** (`admin` + mật khẩu ngẫu nhiên) → mở `https://tên-miền`, đăng nhập, **đổi mật khẩu ngay**, rồi thêm doanh nghiệp (hoặc *Nhập danh sách từ Excel*) và người dùng.

Chuyển dữ liệu từ bản chạy trên máy: chép thư mục `HoaDon` của bạn vào `web/data` (trước khi chạy lần đầu, hoặc dừng app rồi chép), rồi `sudo chown -R 1000:1000 data`. Mật khẩu DN lưu bằng DPAPI của Windows không giải được trên máy chủ → nhập lại mật khẩu (dùng *Nhập danh sách từ Excel* cho nhanh).

| Việc | Lệnh (trong thư mục `web`) |
|---|---|
| Xem nhật ký / mật khẩu quản trị ban đầu | `docker compose logs app` |
| Quên mật khẩu quản trị | `docker compose exec app python taihoadon.py --set-password admin` |
| Cập nhật phiên bản mới | `git pull && docker compose up -d --build` |
| Dừng / chạy lại | `docker compose down` / `docker compose up -d` |
| Sao lưu (giữ 14 bản) | `./sao-luu.sh` – hằng ngày: `crontab -e` → `30 23 * * * /đường/dẫn/web/sao-luu.sh` |

Cấu hình trong `web/.env`: `DOMAIN`, `TAIHOADON_ADMIN_USER`, `TAIHOADON_ADMIN_PASSWORD`, `TAIHOADON_MAX_JOBS`.

### Bảo mật bản web

- HTTPS bắt buộc (Caddy tự lấy chứng chỉ Let's Encrypt). Cookie phiên `HttpOnly`, `Secure`, `SameSite`; mọi lệnh có khoá chống giả mạo (CSRF); chỉ nhận đúng tên miền đã cấu hình.
- Mật khẩu người dùng băm PBKDF2-SHA256 (200.000 vòng). Đăng nhập sai 8 lần trong 15 phút → tạm khoá theo IP và theo tên đăng nhập. Nhật ký đăng nhập / thao tác quản trị: `data/_cau-hinh/nhat-ky.log`.
- Mật khẩu cổng thuế của DN mã hoá AES (Fernet) bằng khoá `data/_cau-hinh/khoa-bi-mat.key` (tạo tự động, quyền 600). **Sao lưu giữ cả khoá này** – mất khoá thì phải nhập lại mật khẩu DN; ai có bản sao lưu là đọc được mật khẩu, hãy cất bản sao lưu cẩn thận.
- Thư mục `data` chỉ chủ sở hữu đọc được (700); app chạy bằng người dùng thường trong container.
- Nhiều DN đăng nhập từ cùng một IP: phần mềm đã giãn cách và giới hạn số lượt chạy cùng lúc; nếu cổng thuế báo chặn, giảm `TAIHOADON_MAX_JOBS` xuống 2.

Chạy bản web không dùng Docker: `pip install openpyxl pypdf cryptography` rồi `python3 taihoadon.py --server --domain hoadon.congty.vn --trust-proxy` sau một reverse proxy HTTPS (Caddy/Nginx) trỏ về cổng 8765.

## Nơi lưu

```
HoaDon/_cau-hinh/doanh-nghiep.json      danh sách DN (mật khẩu đã mã hoá)
HoaDon/_cau-hinh/captcha-mau.json       mẫu captcha đã học
HoaDon/_cau-hinh/nguoi-dung.json        (bản web) người dùng, mật khẩu đã băm, DN được giao
HoaDon/_cau-hinh/khoa-bi-mat.key        (bản web) khoá mã hoá mật khẩu DN – sao lưu cùng dữ liệu
HoaDon/_cau-hinh/thong-bao.json, cai-dat.json, nhat-ky.log   (bản web) thông báo, lịch tự động, nhật ký
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
| `--server` | Bản web nhiều người dùng (xem mục *Bản web trên VPS*) |
| `--domain`, `--trust-proxy`, `--bind`, `--max-jobs` | Tên miền được phép, chạy sau Caddy/Nginx, địa chỉ lắng nghe, số lượt đồng bộ cùng lúc |
| `--set-password TÊN` | Tạo / đặt lại mật khẩu quản trị rồi thoát |

## Bảo mật

- Bản chạy trên máy: giao diện chỉ mở trên `127.0.0.1` (máy của bạn), có khoá phiên chống trang web khác gọi vào. Bản web: xem *Bảo mật bản web* ở trên.
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

Kiểm thử chạy với một cổng giả lập: captcha SVG (học rồi tự giải), đăng nhập nhiều DN, sai mật khẩu, phân trang, chia tháng, XML, chi tiết hàng hoá, phát hiện HĐ bị huỷ, giao diện; bản web: đăng nhập, CSRF, phân quyền nhân viên, khoá tài khoản, giới hạn đăng nhập sai, đồng bộ tự động, thông báo.
