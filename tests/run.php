<?php
/**
 * Kiểm thử đơn vị cho plugin tinhoc119-core và theme flatsome-tinhoc119 (hàm thuần, không cần WordPress).
 * Chạy: php tests/run.php  (mã thoát 1 nếu có lỗi)
 */

require __DIR__ . '/bootstrap.php';

// Nạp toàn bộ file: kiểm tra không lỗi khi include (không gọi hàm WP chưa có ở cấp file).
$plugin = dirname( __DIR__ ) . '/wp-content/plugins/tinhoc119-core/tinhoc119-core.php';
require $plugin;
$theme = dirname( __DIR__ ) . '/wp-content/themes/flatsome-tinhoc119';
foreach ( array( 'helpers', 'content', 'header-footer', 'home', 'setup' ) as $f ) {
	require $theme . '/inc/' . $f . '.php';
}
echo "Đã nạp plugin + theme: " . count( $GLOBALS['th_hooks'] ) . " hook/shortcode được đăng ký\n";

echo "Số điện thoại\n";
th_eq( th119_normalize_phone( '0914 108 322' ), '0914108322', 'chuẩn hoá có khoảng trắng' );
th_eq( th119_normalize_phone( '+84 914.108.322' ), '0914108322', 'chuẩn hoá +84' );
th_eq( th119_normalize_phone( '84914108322' ), '0914108322', 'chuẩn hoá 84…' );
th_eq( th119_normalize_phone( '12345' ), '', 'số quá ngắn' );
th_eq( th119_normalize_phone( 'abc' ), '', 'không phải số' );
th_eq( th119_mask_phone( '0914108322' ), '0914***322', 'che số' );
th_eq( th119_mask_phone( '12' ), '***', 'che số ngắn' );
th_eq( th119_mask_name( 'Nguyễn Văn An' ), 'N*** V*** An', 'che tên' );
th_eq( th119_mask_name( 'Giang' ), 'Giang', 'tên một chữ' );

echo "Ngày & bảo hành\n";
th_eq( th119_parse_date( '05/11/2026' ), '2026-11-05', 'd/m/Y' );
th_eq( th119_parse_date( '2026-02-30' ), null, 'ngày không tồn tại' );
th_eq( th119_parse_date( '31/04/2026' ), null, '31/4 không tồn tại' );
th_eq( th119_parse_date( 'hôm qua' ), null, 'chuỗi lạ' );
th_eq( th119_warranty_end( '2026-01-15', 3 ), '2026-04-14', 'bảo hành 3 tháng' );
th_eq( th119_warranty_end( '2026-01-31', 1 ), '2026-02-28', 'cuối tháng 1 + 1 tháng (không tràn sang tháng 3)' );
th_eq( th119_warranty_end( '2026-03-31', 1 ), '2026-04-30', '31/3 + 1 tháng → hết 30/4' );
th_eq( th119_warranty_end( '2026-11-06', 12 ), '2027-11-05', '12 tháng qua năm mới' );
th_eq( th119_warranty_end( '2028-02-29', 12 ), '2029-02-28', 'năm nhuận' );
th_eq( th119_warranty_end( '2026-01-01', 0 ), null, '0 tháng' );
th_eq( th119_warranty_end( '', 3 ), null, 'không có ngày' );

echo "Token form chống spam\n";
$tok = th119_form_token( 'ticket' );
th_eq( th119_check_form_token( 'ticket', $tok ), false, 'gửi ngay (< 3 giây) bị chặn' );
$old = ( time() - 10 ) . '.' . substr( hash_hmac( 'sha256', 'ticket|' . ( time() - 10 ), wp_salt( 'nonce' ) ), 0, 20 );
th_eq( th119_check_form_token( 'ticket', $old ), true, 'token hợp lệ sau 10 giây' );
th_eq( th119_check_form_token( 'lookup', $old ), false, 'token form khác bị chặn' );
$exp = ( time() - 4 * DAY_IN_SECONDS ) . '.' . substr( hash_hmac( 'sha256', 'ticket|' . ( time() - 4 * DAY_IN_SECONDS ), wp_salt( 'nonce' ) ), 0, 20 );
th_eq( th119_check_form_token( 'ticket', $exp ), false, 'token hết hạn' );
th_eq( th119_check_form_token( 'ticket', 'abc.def' ), false, 'token rác' );

echo "Mã hoá khoá API\n";
$enc = th119_encrypt( 'sk-test-123' );
th_assert( 0 === strpos( $enc, 's1:' ) || 0 === strpos( $enc, 'b0:' ), 'có tiền tố định dạng' );
th_assert( false === strpos( $enc, 'sk-test-123' ), 'không lưu dạng rõ' );
th_eq( th119_decrypt( $enc ), 'sk-test-123', 'giải mã đúng' );
th_eq( th119_decrypt( 's1:rác' ), '', 'chuỗi hỏng trả rỗng' );
th_eq( th119_encrypt( '' ), '', 'chuỗi rỗng' );
th_eq( th119_mask_secret( 'sk-abcdef1234' ), 'Đã lưu (13 ký tự, kết thúc "…1234")', 'che khoá' );

echo "Giờ làm việc\n";
th_eq( th119_sanitize_hours( "Mo,Tu,We | 08:00 | 20:00\nXx | 1 | 2\nSu | 08:00 | 12:00" ), "Mo,Tu,We | 08:00 | 20:00\nSu | 08:00 | 12:00", 'lọc dòng sai' );

echo "Đường dẫn chuyển hướng\n";
th_eq( th119_normalize_path( 'https://tinhoc119.com/dich-vu/sua-laptop/' ), '/dich-vu/sua-laptop', 'URL đầy đủ' );
th_eq( th119_normalize_path( '/' ), '/', 'trang chủ' );
th_eq( th119_normalize_path( 'bai-cu?p=1' ), '/bai-cu?p=1', 'giữ query' );
th_eq( th119_normalize_path( '/s%E1%BB%ADa-may/' ), '/sửa-may', 'giải mã %' );

echo "Cắt mô tả SEO\n";
th_eq( th119_clip( 'Ngắn gọn.' ), 'Ngắn gọn.', 'ngắn giữ nguyên' );
$long = str_repeat( 'sửa máy tính laptop ', 20 );
$c    = th119_clip( $long, 155 );
th_assert( mb_strlen( $c ) <= 155, 'không vượt 155 ký tự (' . mb_strlen( $c ) . ')' );
th_assert( '…' === mb_substr( $c, -1 ), 'kết thúc bằng …' );
th_eq( th119_clip( '<p>Có <b>thẻ</b> [th_lenh]x[/th_lenh]</p>' ), 'Có thẻ x', 'bỏ thẻ và shortcode' );

echo "Bảng dữ liệu dạng A | B\n";
th_eq( th119_pipe_rows( "Hỏi 1 | Đáp 1\n\nchỉ một cột\nHỏi 2 | Đáp | có gạch\n | thiếu hỏi", 2 ), array( array( 'Hỏi 1', 'Đáp 1' ), array( 'Hỏi 2', 'Đáp | có gạch' ) ), 'tách FAQ' );

echo "Kiểm tra trùng lặp (shingle)\n";
$a = th119_shingles( 'Cách sửa lỗi màn hình xanh trên Windows 11 nhanh chóng và an toàn' );
$b = th119_shingles( 'Cách sửa lỗi màn hình xanh trên Windows 11 nhanh chóng và an toàn' );
th_eq( count( array_intersect_key( $a, $b ) ), count( $a ), 'văn bản giống hệt trùng 100%' );
th_assert( count( $a ) > 0, 'có shingle' );
th_eq( th119_shingles( 'quá ngắn' ), array(), 'văn bản < 5 từ' );

echo "Mục lục bài viết (theme)\n";
list( $html, $heads ) = th119t_toc( '<h2>Nguyên nhân</h2><p>x</p><h3>Do ổ cứng</h3><h2 id="co-san">Cách xử lý</h2><h2>Nguyên nhân</h2><h2></h2>' );
th_eq( count( $heads ), 4, 'đếm tiêu đề (bỏ tiêu đề rỗng)' );
th_eq( $heads[0][1], 'nguyen-nhan', 'id không dấu' );
th_eq( $heads[1], array( 3, 'do-o-cung', 'Do ổ cứng' ), 'H3' );
th_eq( $heads[2][1], 'co-san', 'giữ id có sẵn' );
th_eq( $heads[3][1], 'nguyen-nhan-2', 'id trùng được đánh số' );
th_assert( false !== strpos( $html, '<h2 id="nguyen-nhan">' ), 'đã gắn id vào HTML' );
th_eq( th119t_toc_html( array_slice( $heads, 0, 2 ) ), '', 'ít hơn 3 mục thì không hiện mục lục' );

$n = $GLOBALS['th_tests'];
$f = isset( $GLOBALS['th_fail'] ) ? count( $GLOBALS['th_fail'] ) : 0;
echo "\n$n kiểm tra, " . ( $n - $f ) . " đạt, $f lỗi\n";
exit( $f ? 1 : 0 );
