# Phần mềm tải hoá đơn điện tử

Tải hàng loạt hoá đơn **mua vào / bán ra** từ cổng [hoadondientu.gdt.gov.vn](https://hoadondientu.gdt.gov.vn) của Tổng cục Thuế: bảng kê Excel + file XML gốc từng hoá đơn.

## Tính năng

- Đăng nhập bằng tài khoản doanh nghiệp trên cổng (MST + mật khẩu + captcha hiển thị ngay trong giao diện).
- Tra **mua vào** (đã cấp mã, không mã, máy tính tiền) và **bán ra** (có mã/không mã, máy tính tiền).
- Khoảng ngày bất kỳ – tự chia theo tháng (cổng chỉ cho tra tối đa 1 tháng/lần), tự lật trang, bỏ trùng.
- Bảng kê **.xlsx** (có dòng tổng, lọc, định dạng tiền) và **.csv** (mở được bằng Excel, đúng tiếng Việt).
- Tải **XML gốc** từng hoá đơn (kèm file HTML xem nhanh nếu cổng có). Chạy lại sẽ bỏ qua hoá đơn đã tải.
- Theo dõi tiến độ, nút **Dừng**, tự thử lại khi cổng quá tải.

## Cài đặt & chạy

1. Cài [Python 3.8+](https://www.python.org/downloads/) (Windows: tích *Add python.exe to PATH*).
2. Windows: bấm đúp **`chay.bat`** (lần đầu tự cài `openpyxl` để xuất Excel).
   macOS/Linux: `pip install openpyxl` rồi `python3 taihoadon.py`.
3. Trình duyệt tự mở `http://127.0.0.1:8765` → đăng nhập → chọn ngày, loại hoá đơn → **Tải hoá đơn**.

Không có `openpyxl` vẫn chạy được, chỉ xuất `.csv`.

## Nơi lưu

```
HoaDon/<MST>/mua-vao_20260901_20260930/
    bang-ke-mua-vao_20260901_20260930.xlsx
    bang-ke-mua-vao_20260901_20260930.csv
    xml/20260905_0101234567_1C26TAA_123.xml   (ngày_MST người bán_ký hiệu_số HĐ)
HoaDon/<MST>/ban-ra_20260901_20260930/...
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
- Mật khẩu chỉ gửi thẳng tới cổng thuế, **không lưu** ở đâu; token đăng nhập chỉ nằm trong bộ nhớ, tắt chương trình là mất.

## Lưu ý

- Phần mềm dùng các API công khai mà giao diện web của cổng đang dùng; khi Tổng cục Thuế thay đổi cổng, có thể cần cập nhật.
- Phiên đăng nhập của cổng hết hạn sau một thời gian – phần mềm sẽ báo và yêu cầu đăng nhập lại; chạy lại sẽ tiếp tục, không tải trùng XML.
- Tải quá nhiều trong thời gian ngắn có thể bị cổng tạm chặn; phần mềm đã giãn cách các lần gọi.

## Kiểm thử

```
python -m unittest test_taihoadon.py
```

Kiểm thử chạy với một cổng giả lập (đăng nhập, phân trang, chia tháng, tải XML, bảng kê, chống gọi trái phép vào giao diện).
