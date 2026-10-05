<?php
/**
 * Văn bản pháp luật trích dẫn trong bài viết Kiến thức – link toàn văn trên Thư viện Pháp luật.
 * Trong nội dung bài: [[tvpl:khoa]] → tên văn bản có link; [[tvpl:khoa|chữ neo]] → chữ neo có link.
 * Cuối mỗi bài tự thêm mục "Nguồn tham khảo" liệt kê các văn bản đã trích dẫn.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$b = 'https://thuvienphapluat.vn/van-ban/';

return array(
	// Doanh nghiệp – đầu tư.
	'ldn2020'  => array( 'Luật Doanh nghiệp số 59/2020/QH14', $b . 'Doanh-nghiep/Luat-Doanh-nghiep-so-59-2020-QH14-427301.aspx' ),
	'ldn2025'  => array( 'Luật số 76/2025/QH15 sửa đổi, bổ sung Luật Doanh nghiệp', $b . 'Doanh-nghiep/Luat-Doanh-nghiep-sua-doi-2025-so-76-2025-QH15-659899.aspx' ),
	'nd168'    => array( 'Nghị định 168/2025/NĐ-CP về đăng ký doanh nghiệp', $b . 'Doanh-nghiep/Nghi-dinh-168-2025-ND-CP-dang-ky-doanh-nghiep-623074.aspx' ),
	'nd296'    => array( 'Nghị định 296/2026/NĐ-CP sửa đổi Nghị định 168/2025/NĐ-CP', $b . 'Doanh-nghiep/Nghi-dinh-296-2026-ND-CP-sua-doi-Nghi-dinh-168-2025-ND-CP-dang-ky-doanh-nghiep-716470.aspx' ),
	'nd122'    => array( 'Nghị định 122/2021/NĐ-CP xử phạt vi phạm hành chính trong lĩnh vực kế hoạch và đầu tư', $b . 'Dau-tu/Nghi-dinh-122-2021-ND-CP-xu-phat-vi-pham-hanh-chinh-linh-vuc-ke-hoach-285024.aspx' ),
	'ldt2025'  => array( 'Luật Đầu tư số 143/2025/QH15', $b . 'Dau-tu/Luat-Dau-tu-2025-so-143-2025-QH15-681550.aspx' ),
	'nd96'     => array( 'Nghị định 96/2026/NĐ-CP hướng dẫn Luật Đầu tư', $b . 'Dau-tu/Nghi-dinh-96-2026-ND-CP-huong-dan-Luat-Dau-tu-690303.aspx' ),
	'nd07'     => array( 'Nghị định 07/2016/NĐ-CP về văn phòng đại diện, chi nhánh của thương nhân nước ngoài', $b . 'Thuong-mai/Nghi-dinh-07-2016-ND-CP-quy-dinh-chi-tiet-van-phong-dai-dien-chi-nhanh-thuong-nhan-nuoc-ngoai-301477.aspx' ),
	'blds'     => array( 'Bộ luật Dân sự số 91/2015/QH13', $b . 'Quyen-dan-su/Bo-luat-dan-su-2015-296215.aspx' ),
	'ltm'      => array( 'Luật Thương mại số 36/2005/QH11', $b . 'Thuong-mai/Luat-Thuong-mai-2005-36-2005-QH11-2633.aspx' ),
	'lshtt'    => array( 'Luật số 131/2025/QH15 sửa đổi, bổ sung Luật Sở hữu trí tuệ', $b . 'So-huu-tri-tue/Luat-So-huu-tri-tue-sua-doi-2025-so-131-2025-QH15-675267.aspx' ),

	// Thuế.
	'lqlt2025' => array( 'Luật Quản lý thuế số 108/2025/QH15', $b . 'Thue-Phi-Le-Phi/Luat-Quan-ly-thue-2025-so-108-2025-QH15-675268.aspx' ),
	'nd252'    => array( 'Nghị định 252/2026/NĐ-CP hướng dẫn Luật Quản lý thuế', $b . 'Thue-Phi-Le-Phi/Nghi-dinh-252-2026-ND-CP-huong-dan-Luat-Quan-ly-thue-713086.aspx' ),
	'tt89'     => array( 'Thông tư 89/2026/TT-BTC hướng dẫn Luật Quản lý thuế và Nghị định 252/2026/NĐ-CP', $b . 'Thue-Phi-Le-Phi/Thong-tu-89-2026-TT-BTC-huong-dan-Luat-Quan-ly-thue-va-Nghi-dinh-252-2026-ND-CP-714011.aspx' ),
	'ltndn'    => array( 'Luật Thuế thu nhập doanh nghiệp số 67/2025/QH15', $b . 'Doanh-nghiep/Luat-Thue-thu-nhap-doanh-nghiep-2025-so-67-2025-QH15-580594.aspx' ),
	'nd320'    => array( 'Nghị định 320/2025/NĐ-CP hướng dẫn Luật Thuế thu nhập doanh nghiệp', $b . 'Doanh-nghiep/Nghi-dinh-320-2025-ND-CP-huong-dan-Luat-Thue-thu-nhap-doanh-nghiep-665051.aspx' ),
	'lgtgt'    => array( 'Luật Thuế giá trị gia tăng số 48/2024/QH15', $b . 'Thue-Phi-Le-Phi/Luat-Thue-gia-tri-gia-tang-2024-so-48-2024-QH15-556390.aspx' ),
	'nd181'    => array( 'Nghị định 181/2025/NĐ-CP hướng dẫn Luật Thuế giá trị gia tăng', $b . 'Thue-Phi-Le-Phi/Nghi-dinh-181-2025-ND-CP-huong-dan-Luat-Thue-gia-tri-gia-tang-646124.aspx' ),
	'nd174'    => array( 'Nghị định 174/2025/NĐ-CP giảm thuế giá trị gia tăng theo Nghị quyết 204/2025/QH15', $b . 'Thue-Phi-Le-Phi/Nghi-dinh-174-2025-ND-CP-chinh-sach-giam-thue-gia-tri-gia-tang-theo-Nghi-quyet-204-2025-QH15-663151.aspx' ),
	'ltncn'    => array( 'Luật Thuế thu nhập cá nhân số 109/2025/QH15', $b . 'thue-phi-le-phi/Luat-Thue-thu-nhap-ca-nhan-2025-so-109-2025-QH15-665870.aspx' ),
	'nq110'    => array( 'Nghị quyết 110/2025/UBTVQH15 về mức giảm trừ gia cảnh', $b . 'Thue-Phi-Le-Phi/Nghi-quyet-110-2025-UBTVQH15-muc-giam-tru-gia-canh-thue-thu-nhap-ca-nhan-665865.aspx' ),
	'nd253'    => array( 'Nghị định 253/2026/NĐ-CP hướng dẫn Luật Thuế thu nhập cá nhân', $b . 'Thue-Phi-Le-Phi/Nghi-dinh-253-2026-ND-CP-huong-dan-Luat-Thue-thu-nhap-ca-nhan-699193.aspx' ),
	'nd68'     => array( 'Nghị định 68/2026/NĐ-CP về chính sách thuế và quản lý thuế đối với hộ, cá nhân kinh doanh', $b . 'Doanh-nghiep/Nghi-dinh-68-2026-ND-CP-chinh-sach-thue-va-quan-ly-thue-doi-voi-ho-kinh-doanh-ca-nhan-kinh-doanh-685358.aspx' ),
	'nq198'    => array( 'Nghị quyết 198/2025/QH15 về cơ chế, chính sách đặc biệt phát triển kinh tế tư nhân', 'https://thuvienphapluat.vn/chinh-sach-phap-luat-moi/vn/ho-tro-phap-luat/chinh-sach-moi/85065/nghi-quyet-198-cham-dut-thu-nop-le-phi-mon-bai-tu-ngay-01-01-2026' ),
	'tt90'     => array( 'Thông tư 90/2026/TT-BTC quy định về đăng ký thuế', $b . 'Thue-Phi-Le-Phi/Thong-tu-90-2026-TT-BTC-dang-ky-thue-280130.aspx' ),
	'nd123'    => array( 'Nghị định 123/2020/NĐ-CP quy định về hoá đơn, chứng từ', $b . 'Ke-toan-Kiem-toan/Nghi-dinh-123-2020-ND-CP-quy-dinh-hoa-don-chung-tu-445980.aspx' ),
	'nd70'     => array( 'Nghị định 70/2025/NĐ-CP sửa đổi Nghị định 123/2020/NĐ-CP về hoá đơn, chứng từ', $b . 'Thue-Phi-Le-Phi/Nghi-dinh-70-2025-ND-CP-sua-doi-Nghi-dinh-123-2020-ND-CP-hoa-don-chung-tu-577816.aspx' ),
	'nd310'    => array( 'Nghị định 310/2025/NĐ-CP sửa đổi Nghị định 125/2020/NĐ-CP xử phạt vi phạm hành chính về thuế, hoá đơn', $b . 'Thuong-mai/Nghi-dinh-310-2025-ND-CP-sua-doi-Nghi-dinh-125-2020-ND-CP-xu-phat-hanh-chinh-linh-vuc-thue-478004.aspx' ),

	// Kế toán.
	'lkt'      => array( 'Luật Kế toán số 88/2015/QH13', $b . 'Ke-toan-Kiem-toan/Luat-ke-toan-2015-298369.aspx' ),
	'tt99'     => array( 'Thông tư 99/2025/TT-BTC hướng dẫn chế độ kế toán doanh nghiệp', 'https://thuvienphapluat.vn/chinh-sach-phap-luat-moi/vn/ho-tro-phap-luat/chinh-sach-moi/97421/da-co-thong-tu-99-2025-tt-btc-huong-dan-che-do-ke-toan-doanh-nghiep-tu-ngay-01-01-2026-thay-the-thong-tu-200-2014' ),
	'tt133'    => array( 'Thông tư 133/2016/TT-BTC hướng dẫn chế độ kế toán doanh nghiệp nhỏ và vừa', $b . 'Doanh-nghiep/Thong-tu-133-2016-TT-BTC-huong-dan-che-do-ke-toan-doanh-nghiep-nho-va-vua-284997.aspx' ),
	'tt152'    => array( 'Thông tư 152/2025/TT-BTC hướng dẫn kế toán cho hộ, cá nhân kinh doanh', $b . 'Ke-toan-Kiem-toan/Thong-tu-152-2025-TT-BTC-huong-dan-ke-toan-cho-cac-ho-kinh-doanh-680351.aspx' ),

	// Lao động – bảo hiểm.
	'lbhxh'    => array( 'Luật Bảo hiểm xã hội số 41/2024/QH15', $b . 'Bao-hiem/Luat-Bao-hiem-xa-hoi-2024-557190.aspx' ),
	'nd158'    => array( 'Nghị định 158/2025/NĐ-CP hướng dẫn Luật Bảo hiểm xã hội về bảo hiểm xã hội bắt buộc', $b . 'Bao-hiem/Nghi-dinh-158-2025-ND-CP-huong-dan-Luat-Bao-hiem-xa-hoi-ve-bao-hiem-xa-hoi-bat-buoc-634792.aspx' ),
	'nd293'    => array( 'Nghị định 293/2025/NĐ-CP về mức lương tối thiểu vùng', 'https://thuvienphapluat.vn/phap-luat-doanh-nghiep/bai-viet/cap-nhat-muc-luong-toi-thieu-vung-2026-chinh-thuc-theo-nghi-dinh-293-2025-nd-cp-15934.html' ),
);
