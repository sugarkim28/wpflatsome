<?php
/**
 * Hàm tiện ích dùng chung.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giá trị mặc định cho mọi tuỳ chọn landing (lưu bằng theme_mod với tiền tố bds_).
 *
 * @return array
 */
function bds_defaults() {
	return array(
		// Chung.
		'color_primary'      => '#0a3d62',
		'color_accent'       => '#c8a24a',
		'hotline'            => '0965 078 229',
		'zalo'               => '0965078229',
		'messenger'          => '',
		'lead_email'         => 'saigonluxury229@gmail.com',
		'popup_delay'        => 0,

		// Hero.
		'hero_image'         => '',
		'hero_eyebrow'       => 'Chính thức nhận booking – Chỉ 30 triệu/suất – Chiết khấu 3% cho khách booking sớm',
		'hero_title'         => 'The Collection 688',
		'hero_subtitle'      => 'Căn hộ cao cấp chuẩn sống xanh mặt tiền Đại lộ Bình Dương (Quốc lộ 13) – Lô 198 Quốc lộ 13, khu phố 1, phường Thuận Giao, TP.HCM. Chỉ 300m đến ga C6 Metro số 2.',
		'hero_price'         => 'Chỉ từ 43,688 triệu/m² – Tổng chiết khấu lên đến 12%',
		'hero_cta'           => 'Nhận bảng giá & chính sách',

		// Tổng quan.
		'show_overview'      => 1,
		'overview_title'     => 'Tổng quan dự án',
		'overview_text'      => 'The Collection 688 (tên pháp lý: Chung cư Hòa Lân Thuận Giao) là tổ hợp căn hộ cao cấp tại mặt tiền Đại lộ Bình Dương (Quốc lộ 13), phường Thuận Giao, TP.HCM, do DICERA Holdings (HoSE: DC4) đầu tư và làm tổng thầu EPC với tổng vốn 1.880,5 tỷ đồng. Tòa tháp cao 156,6m, kiến trúc Streamline Moderne, định hướng tiêu chuẩn xanh EDGE, 100% căn hộ có ban công – lựa chọn an cư cho chuyên gia, kỹ sư, gia đình trẻ và kênh đầu tư cho thuê gần các khu công nghiệp VSIP 1, Sóng Thần, Việt Hương.',
		'overview_image'     => '',
		'overview_facts'     => "Tên thương mại | The Collection 688\nTên pháp lý | Chung cư Hòa Lân Thuận Giao\nChủ đầu tư & Tổng thầu EPC | Công ty CP DICERA Holdings (HoSE: DC4)\nVị trí | Lô 198 Quốc lộ 13 (Đại lộ Bình Dương), khu phố 1, phường Thuận Giao, TP.HCM\nDiện tích đất | 6.138,6 m²\nQuy mô | 01 tòa tháp, 02 tầng hầm – 39 tầng nổi, cao 156,6m\nMật độ xây dựng | 40%\nSản phẩm | 688 sản phẩm: 549 căn hộ chung cư, 133 căn hộ thương gia, 6 shophouse\nLoại căn | 1PN+ (46–54m²), 2PN (68–80m²), 3PN (85m²), căn thương gia 142–159m², Duplex, Penthouse\nTổng mức đầu tư | 1.880,5 tỷ đồng\nPháp lý | Quy hoạch 1/500, chấp thuận chủ trương đầu tư & nhà đầu tư, BCNCKT thẩm định 11/2025\nKhởi công | Tháng 12/2025\nBàn giao | Dự kiến Quý II/2029 – căn thương gia full nội thất, căn hộ chung cư hoàn thiện cơ bản\nQuản lý vận hành | Savills\nTổng đại lý phân phối | Công ty CP Bất động sản SG Holdings\nNgân hàng tài trợ | MB",

		// Vị trí.
		'show_location'      => 1,
		'location_title'     => 'Vị trí The Collection 688',
		'location_text'      => 'Mặt tiền Đại lộ Bình Dương (Quốc lộ 13) – trục giao thương kết nối trung tâm TP.HCM với các đô thị công nghiệp phía Bắc. Liền kề các khu công nghiệp VSIP 1, Sóng Thần, Việt Hương, nơi tập trung đông đảo chuyên gia và kỹ sư.',
		'location_points'    => "300m | đến ga C6 – Metro số 2 (Thủ Dầu Một – TP.HCM)\n1,3km | đến đường Vành đai 3\n5 phút | Co.opmart, Lotte Mart, chợ Lái Thiêu\n10 phút | AEON Mall, Mega Market, sân golf Sông Bé, BV Quốc tế Becamex\n15 phút | Làng Đại học Quốc gia TP.HCM\n25 phút | Bến xe Miền Đông mới, sân bay Tân Sơn Nhất",
		'location_image'     => '',
		'location_map'       => '',

		// Tiện ích.
		'show_amenities'     => 1,
		'amenities_title'    => 'Tiện ích The Collection 688',
		'amenities'          => "Tầng 1–3 · Khối đế | 2 sảnh đón chuẩn khách sạn, sinh hoạt cộng đồng, game room, 6 shophouse, nước uống tại vòi\nTầng 9 · Tiện ích động | Hồ bơi tràn bờ người lớn & trẻ em, công viên nước mini, BBQ, Gym & 3D Golf, phòng chiếu phim, karaoke, Dance & Yoga\nTầng 21 · Tiện ích tĩnh | Co-working trong nhà và ngoài trời, 4 phòng họp thương gia, vườn thiền, Sound Bathing, Yoga ngoài trời\n3 tầng bãi đậu thông minh | Bãi đậu và trạm sạc ô tô điện dành cho cư dân\nNăng lượng mặt trời | Điện mặt trời trên mái cho chiếu sáng công cộng và thang máy, giảm phí vận hành\nTiêu chuẩn xanh EDGE | 100% căn hộ có ban công, phòng ngủ có cửa sổ, layout vuông vức, tiết kiệm điện nước",
		'amenities_image'    => '',

		// Số liệu nổi bật (dưới banner).
		'highlights'         => "Chỉ từ | 43,688 triệu/m²\nBooking | 30 triệu/suất\nBooking sớm | Chiết khấu 3%\nTổng chiết khấu | Đến 12%/TGT",

		// Lý do sở hữu.
		'show_reasons'       => 1,
		'reasons_title'      => '6 lý do nên sở hữu The Collection 688',
		'reasons'            => "Vị trí mặt tiền Đại lộ Bình Dương | Trục giao thương xương sống, 300m đến ga C6 Metro số 2, 1,3km đến Vành đai 3, 5–10 phút đến AEON Mall, Lotte Mart, sân golf Sông Bé\nChủ đầu tư DICERA Holdings (DC4) | Hơn 30 năm kinh nghiệm, trực tiếp làm tổng thầu EPC, dấu ấn Ruby Tower và Vung Tau Centre Point\nPháp lý minh bạch | Quy hoạch 1/500, chấp thuận chủ trương đầu tư và nhà đầu tư, báo cáo nghiên cứu khả thi đã thẩm định\nSống xanh chuẩn EDGE | Mật độ xây dựng 40%, 100% căn hộ có ban công, năng lượng mặt trời, nước uống tại vòi\nĐiểm rơi lợi nhuận 2026–2029 | Giá chỉ từ 43,688 triệu/m², tổng chiết khấu đến 12%, thanh toán linh hoạt, hạ tầng Quốc lộ 13 và Metro số 2 hoàn thiện dần\nTiềm năng cho thuê | Liền kề KCN VSIP 1, Sóng Thần, Việt Hương – nguồn khách thuê chuyên gia, kỹ sư ổn định",

		// Chủ đầu tư.
		'show_developer'     => 1,
		'developer_title'    => 'Chủ đầu tư DICERA Holdings',
		'developer_text'     => 'Công ty Cổ phần DICERA Holdings (HoSE: DC4) có hơn 30 năm hoạt động trong lĩnh vực xây dựng và đầu tư phát triển bất động sản, đồng thời là tổng thầu EPC của The Collection 688 – trực tiếp kiểm soát chất lượng và tiến độ thi công.',
		'developer_points'   => "Kinh nghiệm | Hơn 30 năm xây dựng và phát triển dự án\nDự án tiêu biểu | Ruby Tower, Vung Tau Centre Point\nTổng thầu EPC | Tự thi công, chủ động tiến độ và chất lượng\nĐối tác | Savills quản lý vận hành, MB tài trợ vốn",
		'developer_image'    => '',

		// Câu hỏi thường gặp.
		'show_faq'           => 1,
		'faq_title'          => 'Câu hỏi thường gặp',
		'faq'                => "Dự án The Collection 688 nằm ở đâu? | Lô 198 Quốc lộ 13 (Đại lộ Bình Dương), khu phố 1, phường Thuận Giao, TP.HCM (khu vực Thuận Giao, TP. Thuận An cũ), cách ga C6 Metro số 2 khoảng 300m.\nChủ đầu tư dự án là ai? | Công ty Cổ phần DICERA Holdings (HoSE: DC4), đồng thời là tổng thầu EPC của dự án.\nDự án có bao nhiêu sản phẩm? | 688 sản phẩm gồm 549 căn hộ chung cư, 133 căn hộ thương gia và 6 shophouse, cùng quỹ căn Duplex và Penthouse.\nGiá bán và booking thế nào? | Giá chỉ từ 43,688 triệu/m². Booking 30 triệu/suất, khách hàng booking sớm được chiết khấu 3%; tổng chiết khấu lên đến 12% trên tổng giá trị căn hộ. Liên hệ hotline để nhận bảng giá chi tiết từng căn.\nCó những phương thức thanh toán nào? | Thanh toán chuẩn (chiết khấu 6%), ưu đãi 1, ưu đãi 2 (chiết khấu 3%), thanh toán vượt (chiết khấu đến 11%) và hỗ trợ tài chính với ngân hàng giải ngân đến 60%, ân hạn nợ gốc và hỗ trợ lãi suất 24 tháng.\nKhi nào bàn giao? | Dự kiến Quý II/2029. Căn hộ thương gia bàn giao full nội thất, căn hộ chung cư bàn giao hoàn thiện cơ bản.\nPháp lý dự án đến đâu? | Dự án đã có quy hoạch 1/500, chấp thuận chủ trương đầu tư, chấp thuận nhà đầu tư và báo cáo nghiên cứu khả thi được thẩm định tháng 11/2025.",

		// Mặt bằng.
		'show_floorplans'    => 1,
		'floorplans_title'   => 'Mặt bằng & cơ cấu sản phẩm',
		'floorplan_1_title'  => 'Tầng căn hộ điển hình 11–20',
		'floorplan_1_desc'   => '1PN+ 52–54m² · 2PN 68–80m² · 3PN 85m²',
		'floorplan_2_title'  => 'Tầng căn hộ thương gia 5A–8',
		'floorplan_2_desc'   => '1PN+ 46–54m² · 2PN 142–146m² · 3PN 159m² – bàn giao full nội thất',
		'floorplan_3_title'  => 'Tầng 9 – Tiện ích động',
		'floorplan_3_desc'   => 'Vui chơi, kết nối, vận động',
		'floorplan_4_title'  => 'Tầng 21 – Tiện ích tĩnh',
		'floorplan_4_desc'   => 'Thư giãn, cân bằng, tái tạo năng lượng',

		// Thư viện ảnh.
		'show_gallery'       => 1,
		'gallery_title'      => 'Hình ảnh dự án',

		// Video.
		'show_video'         => 1,
		'video_title'        => 'Video dự án',
		'video_url'          => '',

		// Chính sách.
		'show_pricing'       => 1,
		'pricing_title'      => 'Phương thức thanh toán',
		'pricing_items'      => "Booking sớm – Chiết khấu 3% | Booking chỉ 30 triệu/suất, ưu tiên chọn căn đẹp\nTổng chiết khấu đến 12% | Trên tổng giá trị căn hộ khi kết hợp các chính sách ưu đãi\nPTTT chuẩn – Chiết khấu 6% | Thanh toán 12 đợt theo tiến độ, 25% khi nhận bàn giao, 5% khi nhận sổ\nPTTT ưu đãi 1 (Thượng đỉnh) | Thanh toán 30% trong 5 tháng đầu, sau đó chỉ 0,25%/tháng trong 20 tháng, 60% khi nhận bàn giao\nPTTT ưu đãi 2 – Chiết khấu 3% | 50% trong 17 tháng, 45% khi nhận bàn giao, 5% khi nhận sổ\nThanh toán vượt – Chiết khấu đến 11% | Vượt 30% chiết khấu 8%, vượt 50% chiết khấu 10%, vượt 70% chiết khấu 11%\nHỗ trợ tài chính | Ngân hàng giải ngân đến 60%, ân hạn nợ gốc và hỗ trợ lãi suất 24 tháng\nThanh toán trước hạn | Chiết khấu theo dòng tiền trên cơ sở lãi suất 13%/năm",

		// Đăng ký.
		'register_title'     => 'Đăng ký nhận thông tin',
		'register_text'      => 'Để lại thông tin để nhận bảng giá, mặt bằng chi tiết và chính sách thanh toán mới nhất của The Collection 688. Experience Gallery: mặt tiền Đại lộ Bình Dương, phường Lái Thiêu, TP.HCM.',
		'register_image'     => '',
		'agency_name'        => 'Tổng đại lý: Công ty Cổ phần Bất động sản SG Holdings',
		'agency_address'     => 'Trụ sở: Số 45 Hoàng Việt, Phường 4, Quận Tân Bình, TP. Hồ Chí Minh',
		'contact_email'      => 'saigonluxury229@gmail.com',
		'disclaimer'         => 'Lưu ý: Hình ảnh, sơ đồ và thông tin trên trang chỉ nhằm mục đích minh họa, không có tính chất cam kết pháp lý. Khách hàng vui lòng căn cứ vào các tài liệu giao dịch chính thức. Thông tin sản phẩm có thể thay đổi theo chấp thuận hoặc yêu cầu của cơ quan nhà nước có thẩm quyền.',
		'register_success'   => 'Cảm ơn Quý khách! Chuyên viên tư vấn sẽ liên hệ trong thời gian sớm nhất.',
	);
}

/**
 * Lấy giá trị tuỳ chọn.
 *
 * @param string $key Khoá (không có tiền tố bds_).
 * @return mixed
 */
function bds_opt( $key ) {
	$defaults = bds_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return get_theme_mod( 'bds_' . $key, $default );
}

/**
 * Tách textarea dạng "Tiêu đề | Mô tả" mỗi dòng thành mảng.
 *
 * @param string $text Nội dung.
 * @return array[] Mỗi phần tử: array( 0 => tiêu đề, 1 => mô tả ).
 */
function bds_lines( $text ) {
	$rows = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts  = array_map( 'trim', explode( '|', $line, 2 ) );
		$rows[] = array( $parts[0], isset( $parts[1] ) ? $parts[1] : '' );
	}
	return $rows;
}

/**
 * URL ảnh từ attachment ID (Customizer media control lưu ID).
 *
 * @param mixed  $id   Attachment ID.
 * @param string $size Kích thước.
 * @return string
 */
function bds_img_url( $id, $size = 'large' ) {
	$id = absint( $id );
	if ( ! $id ) {
		return '';
	}
	$url = wp_get_attachment_image_url( $id, $size );
	return $url ? $url : '';
}

/**
 * Số điện thoại chỉ gồm chữ số / dấu + (dùng cho tel:).
 *
 * @param string $phone Số điện thoại.
 * @return string
 */
function bds_tel( $phone ) {
	return preg_replace( '/[^0-9+]/', '', (string) $phone );
}

/**
 * Trang hiện tại có phải landing không.
 *
 * @return bool
 */
function bds_is_landing_context() {
	if ( ! is_singular() ) {
		return false;
	}
	if ( is_page_template( 'page-templates/landing-bds.php' ) ) {
		return true;
	}
	$post = get_post();
	return $post && false !== strpos( $post->post_content, '[bds_' );
}

/**
 * Danh sách mặt bằng: mảng array( image_id, title, desc ).
 *
 * @return array[]
 */
function bds_floorplans() {
	$items = array();
	for ( $i = 1; $i <= 4; $i++ ) {
		$img   = absint( get_theme_mod( "bds_floorplan_{$i}_image" ) );
		$title = bds_opt( "floorplan_{$i}_title" );
		if ( ! $img && '' === $title ) {
			continue;
		}
		$items[] = array(
			'image' => $img,
			'title' => $title,
			'desc'  => bds_opt( "floorplan_{$i}_desc" ),
		);
	}
	return $items;
}

/**
 * Danh sách ID ảnh thư viện.
 *
 * @return int[]
 */
function bds_gallery_ids() {
	$ids = array();
	for ( $i = 1; $i <= 8; $i++ ) {
		$id = absint( get_theme_mod( "bds_gallery_{$i}" ) );
		if ( $id ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/**
 * Chuyển link YouTube thành ID video.
 *
 * @param string $url Link YouTube.
 * @return string
 */
function bds_youtube_id( $url ) {
	if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', (string) $url, $m ) ) {
		return $m[1];
	}
	return '';
}
