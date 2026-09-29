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
		'hero_eyebrow'       => 'Mở bán đợt 1 – Chiết khấu đến 10%',
		'hero_title'         => 'The Riverside Residence',
		'hero_subtitle'      => 'Căn hộ cao cấp ven sông – Sở hữu lâu dài – Bàn giao quý IV/2027',
		'hero_price'         => 'Chỉ từ 45 triệu/m²',
		'hero_cta'           => 'Nhận bảng giá & chính sách',

		// Tổng quan.
		'show_overview'      => 1,
		'overview_title'     => 'Tổng quan dự án',
		'overview_text'      => 'Dự án căn hộ cao cấp tọa lạc tại vị trí đắc địa, sở hữu tầm nhìn trực diện sông và hệ tiện ích nội khu chuẩn resort. Thiết kế tối ưu công năng, ánh sáng tự nhiên cho mọi căn hộ.',
		'overview_image'     => '',
		'overview_facts'     => "Chủ đầu tư | Công ty CP Đầu tư ABC\nVị trí | Phường X, Quận Y, TP. HCM\nQuy mô | 2 block – 35 tầng – 1.200 căn\nLoại hình | Căn hộ 1PN, 2PN, 3PN, Penthouse\nPháp lý | Sổ hồng lâu dài\nBàn giao | Quý IV/2027 – nội thất cơ bản",

		// Vị trí.
		'show_location'      => 1,
		'location_title'     => 'Vị trí kết nối',
		'location_text'      => 'Kết nối nhanh chóng đến trung tâm thành phố và các tiện ích ngoại khu.',
		'location_points'    => "5 phút | đến trung tâm thương mại\n10 phút | đến sân bay\n3 phút | đến trường quốc tế\n7 phút | đến bệnh viện",
		'location_image'     => '',
		'location_map'       => '',

		// Tiện ích.
		'show_amenities'     => 1,
		'amenities_title'    => 'Tiện ích đẳng cấp',
		'amenities'          => "Hồ bơi tràn bờ | Hồ bơi vô cực 50m view sông\nCông viên nội khu | Hơn 1ha cây xanh, đường dạo bộ\nGym & Yoga | Phòng tập hiện đại, tầm nhìn panorama\nKhu vui chơi trẻ em | An toàn, đạt chuẩn quốc tế\nAn ninh 24/7 | Camera, thẻ từ, bảo vệ chuyên nghiệp\nTrung tâm thương mại | Shophouse, siêu thị, café",
		'amenities_image'    => '',

		// Mặt bằng.
		'show_floorplans'    => 1,
		'floorplans_title'   => 'Mặt bằng & thiết kế căn hộ',

		// Thư viện ảnh.
		'show_gallery'       => 1,
		'gallery_title'      => 'Hình ảnh dự án',

		// Video.
		'show_video'         => 1,
		'video_title'        => 'Video dự án',
		'video_url'          => '',

		// Chính sách.
		'show_pricing'       => 1,
		'pricing_title'      => 'Chính sách bán hàng',
		'pricing_items'      => "Thanh toán 30% nhận nhà | Phần còn lại thanh toán khi nhận sổ\nNgân hàng hỗ trợ 70% | Ân hạn nợ gốc và 0% lãi suất 24 tháng\nChiết khấu đến 10% | Cho khách hàng thanh toán nhanh\nTặng gói nội thất | Trị giá đến 200 triệu đồng",

		// Đăng ký.
		'register_title'     => 'Đăng ký nhận thông tin',
		'register_text'      => 'Để lại thông tin để nhận bảng giá, mặt bằng chi tiết và chính sách ưu đãi mới nhất từ chủ đầu tư.',
		'register_image'     => '',
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
		$title = get_theme_mod( "bds_floorplan_{$i}_title", '' );
		if ( ! $img && '' === $title ) {
			continue;
		}
		$items[] = array(
			'image' => $img,
			'title' => $title,
			'desc'  => get_theme_mod( "bds_floorplan_{$i}_desc", '' ),
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
