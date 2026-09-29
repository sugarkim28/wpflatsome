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
		'color_primary'      => '#5a1f1a',
		'color_accent'       => '#c8875f',
		'hotline'            => '0965 078 229',
		'zalo'               => '0965078229',
		'messenger'          => '',
		'lead_email'         => 'saigonluxury229@gmail.com',
		'popup_delay'        => 0,
		'landing_front'      => 1,
		'own_header'         => 1,
		'logo'               => '',
		'nav_items'          => "Tổng quan | #tong-quan\nVị trí | #vi-tri\nTiện ích | #tien-ich\nMặt bằng | #mat-bang\nGiá bán | #chinh-sach\nChủ đầu tư | #chu-dau-tu\nFAQ | #faq",
		'nav_cta'            => 'Nhận bảng giá',

		// SEO.
		'seo_title'              => 'The Collection 688 Thuận Giao | Giá từ 43,688 triệu/m²',
		'seo_description'        => 'The Collection 688 mặt tiền QL13, Thuận Giao, cách ga Metro số 2 chỉ 300m. Giá từ 43,688 triệu/m², booking 30 triệu, chiết khấu đến 12%. Hotline 0965 078 229.',
		'seo_image'              => '',
		'schema_enable'          => 1,
		'schema_alt_name'        => 'Chung cư Hòa Lân Thuận Giao',
		'schema_street'          => 'Lô 198 Quốc lộ 13, khu phố 1',
		'schema_locality'        => 'Phường Thuận Giao',
		'schema_region'          => 'TP. Hồ Chí Minh',
		'schema_units'           => 688,
		'schema_agency_name'     => 'Công ty Cổ phần Bất động sản SG Holdings',
		'schema_agency_address'  => 'Số 45 Hoàng Việt, Phường 4, Quận Tân Bình, TP. Hồ Chí Minh',

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
		'overview_facts'     => "Tên thương mại | The Collection 688\nTên pháp lý | Chung cư Hòa Lân Thuận Giao\nChủ đầu tư & Tổng thầu EPC | Công ty CP DICERA Holdings (HoSE: DC4)\nVị trí | Lô 198 Quốc lộ 13 (Đại lộ Bình Dương), khu phố 1, phường Thuận Giao, TP.HCM\nDiện tích đất | 6.138,6 m²\nQuy mô | 01 tòa tháp, 02 tầng hầm – 39 tầng nổi, cao 156,6m\nMật độ xây dựng | 40%\nSản phẩm | 688 sản phẩm: 549 căn hộ chung cư, 133 căn hộ thương gia, 6 shophouse\nLoại căn | 1PN+ 46–54m², 2PN 68–80m², 3PN 85m², 2PN/3PN sân vườn 142–159m², Duplex, Penthouse 137–166m²\nTổng mức đầu tư | 1.880,5 tỷ đồng\nPháp lý | Quy hoạch 1/500, chấp thuận chủ trương đầu tư & nhà đầu tư, BCNCKT thẩm định 11/2025\nKhởi công | Tháng 12/2025\nBàn giao | Dự kiến Quý II/2029 – căn thương gia full nội thất, căn hộ chung cư hoàn thiện cơ bản\nQuản lý vận hành | Savills\nTổng đại lý phân phối | Công ty CP Bất động sản SG Holdings\nNgân hàng tài trợ | MB",

		// Vị trí.
		'show_location'      => 1,
		'location_title'     => 'Vị trí The Collection 688',
		'location_text'      => 'Mặt tiền Đại lộ Bình Dương (Quốc lộ 13) – trục giao thương kết nối trung tâm TP.HCM với các đô thị công nghiệp phía Bắc. Liền kề các khu công nghiệp VSIP 1, Sóng Thần, Việt Hương, nơi tập trung đông đảo chuyên gia và kỹ sư.',
		'location_points'    => "300m | đến ga C6 – Metro số 2 (Thủ Dầu Một – TP.HCM)\n3 phút | Nhà ga C6 Metro số 2\n5 phút | Trường học các cấp, chợ Búng, Bệnh viện Columbia Asia\n10 phút | Mega Market, AEON Mall, sân golf Sông Bé, KCN Việt Hương 1, VSIP 1, Sóng Thần 2\n15 phút | BV Quốc tế Becamex, BV Quốc tế Hạnh Phúc, BV Đa khoa Thuận An\n30 phút | Trung tâm TP.HCM\n1,3km | đến đường Vành đai 3",
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
		'reasons'            => "Vị trí mặt tiền Đại lộ Bình Dương | Trục giao thương xương sống, 300m đến ga C6 Metro số 2, 1,3km đến Vành đai 3, 10 phút đến AEON Mall, Mega Market, sân golf Sông Bé\nChủ đầu tư DICERA Holdings (DC4) | Hơn 30 năm kinh nghiệm, trực tiếp làm tổng thầu EPC, dấu ấn Ruby Tower và Vung Tau Centre Point\nPháp lý minh bạch | Quy hoạch 1/500, chấp thuận chủ trương đầu tư và nhà đầu tư, báo cáo nghiên cứu khả thi đã thẩm định\nSống xanh chuẩn EDGE | Mật độ xây dựng 40%, 100% căn hộ có ban công, năng lượng mặt trời, nước uống tại vòi\nĐiểm rơi lợi nhuận 2026–2029 | Giá chỉ từ 43,688 triệu/m², tổng chiết khấu đến 12%, thanh toán linh hoạt, hạ tầng Quốc lộ 13 và Metro số 2 hoàn thiện dần\nTiềm năng cho thuê | Liền kề KCN VSIP 1, Sóng Thần, Việt Hương – nguồn khách thuê chuyên gia, kỹ sư ổn định",

		// Chủ đầu tư.
		'show_developer'     => 1,
		'developer_title'    => 'Chủ đầu tư DICERA Holdings',
		'developer_text'     => 'Công ty Cổ phần DICERA Holdings (HoSE: DC4) có hơn 30 năm hoạt động trong lĩnh vực xây dựng và đầu tư phát triển bất động sản, đồng thời là tổng thầu EPC của The Collection 688 – trực tiếp kiểm soát chất lượng và tiến độ thi công.',
		'developer_points'   => "Kinh nghiệm | Hơn 30 năm xây dựng và phát triển dự án\nDự án tiêu biểu | Ruby Tower, Vung Tau Centre Point\nTổng thầu EPC | Tự thi công, chủ động tiến độ và chất lượng\nĐối tác | Savills quản lý vận hành, MB tài trợ vốn",
		'developer_image'    => '',

		// Câu hỏi thường gặp.
		'show_faq'           => 1,
		'faq_title'          => 'Câu hỏi thường gặp',
		'faq'                => "Dự án The Collection 688 nằm ở đâu? | Lô 198 Quốc lộ 13 (Đại lộ Bình Dương), khu phố 1, phường Thuận Giao, TP.HCM (khu vực Thuận Giao, TP. Thuận An cũ), cách ga C6 Metro số 2 khoảng 300m.\nChủ đầu tư dự án là ai? | Công ty Cổ phần DICERA Holdings (HoSE: DC4), đồng thời là tổng thầu EPC của dự án.\nDự án có bao nhiêu sản phẩm? | 688 sản phẩm gồm 549 căn hộ chung cư (1PN+, 2PN, 3PN, Duplex, Penthouse), 133 căn hộ thương gia (có căn 2PN, 3PN sân vườn) và 6 shophouse.\nGiá bán và booking thế nào? | Giá chỉ từ 43,688 triệu/m². Booking 30 triệu/suất, khách hàng booking sớm được chiết khấu 3%; tổng chiết khấu lên đến 12% trên tổng giá trị căn hộ. Liên hệ hotline để nhận bảng giá chi tiết từng căn.\nCó những phương thức thanh toán nào? | Thanh toán chuẩn (chiết khấu 6%), Quốc tế (chiết khấu 3%), Thượng đỉnh (chỉ 0,25%/tháng), thanh toán vượt (chiết khấu đến 11%) và hỗ trợ tài chính với ngân hàng giải ngân đến 60%, ân hạn nợ gốc và hỗ trợ lãi suất 24 tháng.\nKhi nào bàn giao? | Dự kiến Quý II/2029. Căn hộ thương gia bàn giao full nội thất, căn hộ chung cư bàn giao hoàn thiện cơ bản.\nPháp lý dự án đến đâu? | Dự án đã có quy hoạch 1/500, chấp thuận chủ trương đầu tư, chấp thuận nhà đầu tư và báo cáo nghiên cứu khả thi được thẩm định tháng 11/2025.",

		// Mặt bằng.
		'show_floorplans'    => 1,
		'floorplans_title'   => 'Mặt bằng & cơ cấu sản phẩm',
		'floorplan_1_title'  => 'Mặt bằng tầng căn hộ điển hình 11–20',
		'floorplan_1_desc'   => '549 căn: 1PN+ 52–54m² (81 căn) · 2PN 68–80m² (351 căn) · 3PN 85m² (107 căn) · Duplex 155,3m² (1 căn) · Penthouse 137–166m² (9 căn)',
		'floorplan_2_title'  => 'Mặt bằng tầng căn hộ thương gia 5A–8',
		'floorplan_2_desc'   => '133 căn: 1PN+ 46–54m² (35) · 2PN 68–80m² (48) · 2PN sân vườn 142–146m² (25) · 3PN 85m² (18) · 3PN sân vườn 159m² (5) · Duplex 155,5–156,8m² (2) – bàn giao full nội thất',
		'floorplan_3_title'  => '',
		'floorplan_3_desc'   => '',
		'floorplan_4_title'  => '',
		'floorplan_4_desc'   => '',

		// Thư viện ảnh.
		'show_gallery'       => 1,
		'gallery_title'      => 'Hình ảnh dự án',

		// Video.
		'show_video'         => 1,
		'video_title'        => 'Video dự án',
		'video_url'          => '',
		'video_mp4'          => '',

		// Chính sách.
		'show_pricing'       => 1,
		'pricing_title'      => 'Phương thức thanh toán',
		'pricing_items'      => "Booking sớm – Chiết khấu 3% | Booking chỉ 30 triệu/suất, ưu tiên chọn căn đẹp\nTổng chiết khấu đến 12% | Trên tổng giá trị căn hộ khi kết hợp các chính sách ưu đãi\nPTTT chuẩn – Chiết khấu 6% | Thanh toán 12 đợt theo tiến độ, 25% khi nhận bàn giao, 5% khi nhận sổ\nPTTT Thượng đỉnh | Thanh toán 30% trong 5 tháng đầu, sau đó chỉ 0,25%/tháng trong 20 tháng, 60% khi nhận bàn giao\nPTTT Quốc tế – Chiết khấu 3% | 50% trong 17 tháng, 45% khi nhận bàn giao, 5% khi nhận sổ\nThanh toán vượt – Chiết khấu đến 11% | Vượt 30% chiết khấu 8%, vượt 50% chiết khấu 10%, vượt 70% chiết khấu 11%\nHỗ trợ tài chính | Ngân hàng giải ngân đến 60%, ân hạn nợ gốc và hỗ trợ lãi suất 24 tháng\nThanh toán trước hạn | Chiết khấu theo dòng tiền trên cơ sở lãi suất 13%/năm",

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
 * Ảnh mặc định đóng gói trong theme (assets/img/), dùng khi chưa chọn ảnh trong Tuỳ biến.
 * Khoá = tên tuỳ chọn ảnh (không có tiền tố bds_), giá trị = tên file không có đuôi.
 *
 * @return array
 */
function bds_default_images() {
	$map = array(
		'hero_image'        => 'hero',
		'overview_image'    => 'tong-quan',
		'location_image'    => 'vi-tri',
		'amenities_image'   => 'tien-ich',
		'developer_image'   => 'chu-dau-tu',
		'register_image'    => 'dang-ky',
		'floorplan_1_image' => 'mat-bang-tang-11-20',
		'floorplan_2_image' => 'mat-bang-tang-5a-8',
		'floorplan_3_image' => 'mat-bang-3',
		'floorplan_4_image' => 'mat-bang-4',
	);
	for ( $i = 1; $i <= 8; $i++ ) {
		$map[ "gallery_{$i}" ] = "thu-vien-{$i}";
	}
	return $map;
}

/**
 * Tên file ảnh mặc định (tìm theo đuôi .jpg, .webp, .png) hoặc chuỗi rỗng.
 *
 * @param string $key Tên tuỳ chọn ảnh.
 * @return string
 */
function bds_default_image_file( $key ) {
	$map = bds_default_images();
	if ( ! isset( $map[ $key ] ) ) {
		return '';
	}
	foreach ( array( 'jpg', 'webp', 'png' ) as $ext ) {
		$name = $map[ $key ] . '.' . $ext;
		if ( file_exists( BDS_DIR . '/assets/img/' . $name ) ) {
			return $name;
		}
	}
	return '';
}

/**
 * Tham chiếu ảnh của một tuỳ chọn: attachment ID nếu đã chọn trong Tuỳ biến,
 * nếu không thì "file:<tên>" của ảnh mặc định trong theme (nếu có), ngược lại 0.
 *
 * @param string $key Tên tuỳ chọn ảnh (không có tiền tố bds_).
 * @return int|string
 */
function bds_image_ref( $key ) {
	$id = absint( get_theme_mod( 'bds_' . $key ) );
	if ( $id ) {
		return $id;
	}
	$file = bds_default_image_file( $key );
	return $file ? 'file:' . $file : 0;
}

/**
 * URL ảnh từ tham chiếu (attachment ID hoặc "file:<tên>").
 *
 * @param int|string $ref  Tham chiếu ảnh.
 * @param string     $size Kích thước (với attachment).
 * @return string
 */
function bds_img_url( $ref, $size = 'large' ) {
	if ( is_string( $ref ) && 0 === strpos( $ref, 'file:' ) ) {
		return BDS_URI . '/assets/img/' . rawurlencode( substr( $ref, 5 ) );
	}
	$id = absint( $ref );
	if ( ! $id ) {
		return '';
	}
	$url = wp_get_attachment_image_url( $id, $size );
	return $url ? $url : '';
}

/**
 * Thẻ <img> từ tham chiếu ảnh.
 *
 * @param int|string $ref  Tham chiếu ảnh.
 * @param string     $size Kích thước (với attachment).
 * @param string     $alt  Chữ thay thế (dùng cho ảnh mặc định).
 * @return string
 */
function bds_img_tag( $ref, $size = 'large', $alt = '' ) {
	if ( is_string( $ref ) && 0 === strpos( $ref, 'file:' ) ) {
		$path = BDS_DIR . '/assets/img/' . substr( $ref, 5 );
		$dim  = is_readable( $path ) ? @getimagesize( $path ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return sprintf(
			'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async"%3$s>',
			esc_url( bds_img_url( $ref ) ),
			esc_attr( $alt ),
			$dim ? sprintf( ' width="%d" height="%d"', $dim[0], $dim[1] ) : ''
		);
	}
	return wp_get_attachment_image( absint( $ref ), $size, false, array( 'loading' => 'lazy' ) );
}

/**
 * Chú thích ảnh (attachment caption, hoặc chữ cho sẵn với ảnh mặc định).
 *
 * @param int|string $ref      Tham chiếu ảnh.
 * @param string     $fallback Chữ mặc định.
 * @return string
 */
function bds_img_caption( $ref, $fallback = '' ) {
	if ( is_numeric( $ref ) && absint( $ref ) ) {
		$caption = wp_get_attachment_caption( absint( $ref ) );
		return $caption ? $caption : $fallback;
	}
	return $fallback;
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
	if ( bds_is_landing_page() ) {
		return true;
	}
	if ( ! is_singular() ) {
		return false;
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
		$img   = bds_image_ref( "floorplan_{$i}_image" );
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
 * Danh sách tham chiếu ảnh thư viện.
 *
 * @return array
 */
function bds_gallery_ids() {
	$ids = array();
	for ( $i = 1; $i <= 8; $i++ ) {
		$ref = bds_image_ref( "gallery_{$i}" );
		if ( $ref ) {
			$ids[] = $ref;
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

/**
 * Trang chủ có đang được ép hiển thị landing không (tuỳ chọn "Tự dùng landing cho trang chủ").
 *
 * @return bool
 */
function bds_is_forced_front() {
	return is_front_page() && bds_opt( 'landing_front' );
}

/**
 * Trang hiện tại hiển thị bằng template landing (chọn template, hoặc trang chủ được ép).
 *
 * @return bool
 */
function bds_is_landing_page() {
	return bds_is_forced_front() || ( is_singular() && is_page_template( 'page-templates/landing-bds.php' ) );
}

/**
 * Nội dung dựng tay cũ (bộ HTML "tc688-…") không dùng với landing – bỏ qua để khỏi vỡ giao diện.
 *
 * @param string $content Nội dung trang.
 * @return bool
 */
function bds_is_legacy_content( $content ) {
	return false !== strpos( $content, 'tc688-' );
}

/**
 * URL logo: logo riêng của landing, nếu trống thì dùng logo Flatsome (Tuỳ biến > Header > Logo).
 *
 * @return string
 */
function bds_logo_url() {
	$url = bds_img_url( absint( bds_opt( 'logo' ) ), 'medium' );
	if ( $url ) {
		return $url;
	}
	$flatsome = get_theme_mod( 'site_logo', '' );
	if ( is_numeric( $flatsome ) ) {
		return bds_img_url( $flatsome, 'medium' );
	}
	return ( $flatsome && false === strpos( $flatsome, '/flatsome/assets/img/logo.png' ) ) ? $flatsome : '';
}

/**
 * Video mp4 tự lưu trữ: link trong Tuỳ biến, nếu trống thì file đóng gói sẵn
 * wp-content/uploads/bds-688/the-collection-688.mp4 (nếu có).
 *
 * @return string
 */
function bds_video_mp4_url() {
	$url = bds_opt( 'video_mp4' );
	if ( $url ) {
		return $url;
	}
	$file = '/uploads/bds-688/the-collection-688.mp4';
	return file_exists( WP_CONTENT_DIR . $file ) ? content_url( $file ) : '';
}
