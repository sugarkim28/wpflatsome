# Tin Học 119 – tài liệu triển khai & vận hành

Website **tinhoc119.com** – trung tâm kiến thức công nghệ + dịch vụ sửa chữa máy tính, IT, hỗ trợ kỹ thuật.
Khẩu hiệu: **UY TÍN TẠO NIỀM TIN** · Địa chỉ: Hẻm 239 Tân Quý, Tân Phú, TP.HCM · Liên hệ: Giang.

| Tài liệu | Nội dung |
|---|---|
| [trien-khai.md](trien-khai.md) | Cài đặt trên staging → kiểm tra → đưa lên website thật, từng bước |
| [van-hanh.md](van-hanh.md) | Sử dụng hằng ngày: yêu cầu hỗ trợ, bảo hành, viết bài, AI, SEO, bảng điều khiển |
| [sao-luu-khoi-phuc.md](sao-luu-khoi-phuc.md) | Kế hoạch sao lưu, khôi phục, phương án quay lui |
| [bao-mat.md](bao-mat.md) | Các lớp bảo mật đã có, cấu hình máy chủ cần thêm, khoá API |
| [seo-local.md](seo-local.md) | SEO kỹ thuật, Google Business Profile, Search Console, GA4, Bing |
| [cho-cap-quyen.md](cho-cap-quyen.md) | **Việc đang chờ chủ website cung cấp quyền / thông tin** |
| [kiem-thu.md](kiem-thu.md) | Đã kiểm thử gì, chưa kiểm thử gì, danh sách kiểm tra trên staging |

## Kiến trúc

```
wp-content/
├── plugins/tinhoc119-core/      ← DỮ LIỆU + CHỨC NĂNG (đổi theme không mất gì)
│   ├── includes/                  form hỗ trợ, bảo hành, AI, SEO, chuyển hướng, bảo mật, nhật ký, dashboard
│   └── assets/                    CSS/JS giao diện công khai + quản trị
└── themes/flatsome-tinhoc119/   ← GIAO DIỆN (theme con của Flatsome)
    ├── inc/                       header/footer, trang chủ, mục lục, FAQ, tác giả, công cụ thiết lập
    └── *.php                      template bài viết, chuyên mục, dịch vụ, dự án, tìm kiếm, 404
tests/run.php                    ← kiểm thử đơn vị (php tests/run.php)
```

Nguyên tắc:

- **Không phải web bán hàng**: không giỏ hàng, không thanh toán.
- **Giữ URL cũ**: plugin đăng ký lại `dich_vu` (/dich-vu/…) và `nhom_dich_vu` (/nhom-dich-vu/…) giống theme cũ nếu theme cũ không còn bật; dữ liệu dịch vụ (`_sgd_*`) và khách đăng ký cũ (`sgd_lead`) được đọc lại, không chuyển đổi, không xoá.
- **Không trùng plugin**: SEO tự nhường cho Rank Math / Yoast / AIOSEO / SEOPress; chống dò mật khẩu tự tắt khi có Wordfence / Solid Security / AIOS / Limit Login Attempts; chuyển hướng tự tắt khi có Redirection / Rank Math redirections.
- **Không dữ liệu giả**: bảng điều khiển chỉ hiện số đo được; khối đánh giá/dự án chỉ hiện khi có dữ liệu thật; trang Giới thiệu, Chính sách, Điều khoản tạo ở dạng **nháp** có đánh dấu `[BỔ SUNG …]`.
- **AI chỉ tạo nháp**: bài do AI tạo không thể xuất bản cho tới khi người có quyền duyệt bấm phê duyệt.
- **Không ghi khoá API vào mã nguồn**: khoá nhập trong trang quản trị (mã hoá trong CSDL) hoặc đặt hằng số trong `wp-config.php`.
