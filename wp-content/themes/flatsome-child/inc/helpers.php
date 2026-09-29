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
		'hotline'            => '0909 000 000',
		'zalo'               => '0909000000',
		'messenger'          => '',
		'lead_email'         => get_option( 'admin_email' ),
		'popup_delay'        => 0,

		// Hero.
		'hero_image'         => '',
		'hero_eyebrow'       => 'Chính thức nhận booking – Đăng ký nhu cầu chỉ 50 triệu',
		'hero_title'         => 'The Collection 688',
		'hero_subtitle'      => 'Tọa độ Metro lý tưởng – Mặt tiền Quốc lộ 13 (Đại lộ Bình Dương), phường Thuận Giao, TP.HCM. Chỉ 688 sản phẩm, bàn giao dự kiến Quý II/2029.',
		'hero_price'         => 'Giá chỉ từ 43,688 triệu/m²',
		'hero_cta'           => 'Nhận bảng giá & chính sách',

		// Tổng quan.
		'show_overview'      => 1,
		'overview_title'     => 'Tổng quan dự án',
		'overview_text'      => 'The Collection 688 là dự án căn hộ cao cấp mặt tiền Quốc lộ 13, chỉ 300m đến ga C6 tuyến Metro số 2 (Thủ Dầu Một – TP.HCM). Kiến trúc Streamline Moderne, định hướng tiêu chuẩn xanh EDGE, 100% căn hộ có ban công, do DICERA Holdings phát triển và Savills quản lý vận hành.',
		'overview_image'     => '',
		'overview_facts'     => "Tên pháp lý | Chung cư Hòa Lân Thuận Giao\nTên thương mại | The Collection 688\nVị trí | Quốc lộ 13 (Đại lộ Bình Dương), phường Thuận Giao, TP.HCM\nDiện tích đất | 6.138 m²\nQuy mô | 02 tầng hầm – 39 tầng nổi\nSản phẩm | 688 sản phẩm: 549 căn hộ chung cư, 133 căn hộ thương gia, 6 shophouse\nLoại căn | 1PN+ (46–54m²), 2PN (68–80m²), 3PN (85m²), căn thương gia 142–159m², Duplex, Penthouse\nBàn giao | Dự kiến Quý II/2029 – căn hộ thương gia full nội thất, căn hộ chung cư hoàn thiện cơ bản\nPhát triển & Tổng thầu EPC | Công ty CP DICERA Holdings\nQuản lý vận hành | Savills\nPhân phối & tiếp thị | BSG Holdings\nNgân hàng tài trợ | MB",

		// Vị trí.
		'show_location'      => 1,
		'location_title'     => 'Tâm điểm hạ tầng – Chạm nhịp đô thị',
		'location_text'      => 'Tọa lạc trên trục Đại lộ Bình Dương, The Collection 688 nằm tại điểm giao của hệ sinh thái đô thị đang ngày càng hoàn thiện – nơi giao thông, thương mại, giáo dục, y tế và kinh tế cùng hội tụ. Quốc lộ 13 được mở rộng 10–14 làn xe, lộ giới đến 60m.',
		'location_points'    => "300m | đến ga C6 – Metro số 2 (Thủ Dầu Một – TP.HCM), khoảng 5 phút đi bộ\n1,3km | đến đường Vành đai 3\n3 phút | trường học các cấp, Bệnh viện Columbia Asia\n10 phút | BV Quốc tế Becamex, BV Quốc tế Hạnh Phúc, BV Đa khoa Thuận An\n15 phút | AEON Mall, Mega Market, sân golf Sông Bé\n30 phút | Trung tâm TP.HCM",
		'location_image'     => '',
		'location_map'       => '',

		// Tiện ích.
		'show_amenities'     => 1,
		'amenities_title'    => 'Top lý do sở hữu The Collection 688',
		'amenities'          => "Tọa độ Metro lý tưởng | Chỉ 300m đến ga C6 tuyến Metro số 2, kết nối trung tâm TP.HCM dễ dàng\nHạ tầng Quốc lộ 13 | Mở rộng 10–14 làn xe, lộ giới đến 60m, gần Vành đai 3 chỉ 1,3km\nFacade khác biệt | Kiến trúc Streamline Moderne hiếm hoi tại Đông Bắc TP.HCM\nTiêu chuẩn xanh EDGE | Vật liệu sang trọng, bền vững, tiết kiệm năng lượng\n3 tầng bãi đậu & sạc ô tô | Bãi đậu thông minh, trạm sạc xe điện phục vụ cư dân\nNăng lượng mặt trời | Hệ thống điện mặt trời trên mái cho chiếu sáng công cộng và thang máy, giảm phí vận hành\nTiện ích động & tĩnh | Tầng 9: vui chơi, kết nối, vận động – Tầng 21: thư giãn, cân bằng, tái tạo năng lượng\nBộ sưu tập tiện ích doanh nhân | 4 phòng họp thương gia, co-working space trong nhà và ngoài trời\nNước uống tại vòi | Nước sạch tinh khiết tại khu tầng trệt, tầng 9 và tầng 21\n100% căn hộ có ban công | Layout vuông vức, đón nắng, đón gió, dễ bố trí nội thất\nBàn giao full nội thất | Áp dụng cho căn hộ thương gia\nPháp lý đầy đủ | Minh bạch, rõ ràng, an tâm sở hữu\nChủ đầu tư DICERA | 32 năm kinh nghiệm xây dựng và phát triển dự án\nVận hành bởi Savills | Đơn vị quản lý bất động sản hàng đầu thế giới\nChỉ 688 sản phẩm | Cộng đồng cư dân tinh hoa, giá trị gia tăng bền vững",
		'amenities_image'    => '',

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
		'pricing_items'      => "PTTT chuẩn – Chiết khấu 6% | Thanh toán 12 đợt theo tiến độ, 25% khi nhận bàn giao, 5% khi nhận sổ\nPTTT ưu đãi 1 (Thượng đỉnh) | Thanh toán 30% trong 5 tháng đầu, sau đó chỉ 0,25%/tháng trong 20 tháng, 60% khi nhận bàn giao\nPTTT ưu đãi 2 – Chiết khấu 3% | 50% trong 17 tháng, 45% khi nhận bàn giao, 5% khi nhận sổ\nThanh toán vượt – Chiết khấu đến 11% | Vượt 30% chiết khấu 8%, vượt 50% chiết khấu 10%, vượt 70% chiết khấu 11%\nHỗ trợ tài chính | Ngân hàng giải ngân đến 60%, ân hạn nợ gốc và hỗ trợ lãi suất 24 tháng\nThanh toán trước hạn | Chiết khấu theo dòng tiền trên cơ sở lãi suất 13%/năm",

		// Đăng ký.
		'register_title'     => 'Đăng ký nhận thông tin',
		'register_text'      => 'Để lại thông tin để nhận bảng giá, mặt bằng chi tiết và chính sách thanh toán mới nhất của The Collection 688. Experience Gallery: mặt tiền Đại lộ Bình Dương, phường Lái Thiêu, TP.HCM.',
		'register_image'     => '',
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
