<?php
/**
 * Cài đặt chung (Giao diện → Tuỳ biến → Website dự án) và hàm tiện ích.
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giá trị mặc định.
 *
 * @return array
 */
function sgp_defaults() {
	return array(
		'company'        => 'SG Holdings',
		'company_full'   => 'Công ty Cổ phần Bất động sản SG Holdings',
		'tagline'        => 'Phân phối dự án bất động sản chính thức',
		'hotline'        => '0965 078 229',
		'zalo'           => '0965078229',
		'email'          => 'saigonluxury229@gmail.com',
		'lead_email'     => 'saigonluxury229@gmail.com',
		'address'        => 'Số 45 Hoàng Việt, Phường 4, Quận Tân Bình, TP. Hồ Chí Minh',
		'tax_code'       => '',
		'working_hours'  => '8:00 – 21:00, tất cả các ngày',
		'facebook'       => '',
		'youtube'        => '',
		'color_primary'  => '#0d1b3e',
		'color_accent'   => '#d4a437',
		'archive_title'  => 'Dự án bất động sản đang phân phối',
		'archive_intro'  => 'Danh sách dự án căn hộ, nhà phố, đất nền do SG Holdings trực tiếp phân phối và tư vấn: thông tin pháp lý, giá bán, chính sách thanh toán được cập nhật thường xuyên.',
		'archive_bottom' => '',
		'disclaimer'     => 'Thông tin, hình ảnh và giá bán chỉ mang tính tham khảo, có thể thay đổi theo chính sách của chủ đầu tư tại từng thời điểm. Vui lòng liên hệ chuyên viên để nhận thông tin chính thức.',
		'form_title'     => 'Nhận bảng giá & tư vấn',
		'form_perks'     => "Bảng giá, chính sách mới nhất từ chủ đầu tư\nTư vấn chọn căn, phương án tài chính\nĐặt lịch tham quan nhà mẫu",
	);
}

/**
 * Đọc tuỳ chọn.
 *
 * @param string $key Khoá.
 * @return mixed
 */
function sgp_opt( $key ) {
	$d = sgp_defaults();
	return get_theme_mod( 'sgp_' . $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
}

/**
 * Số điện thoại chỉ còn chữ số (cho tel: / zalo).
 *
 * @param string $phone Số.
 * @return string
 */
function sgp_tel( $phone ) {
	return preg_replace( '/[^\d+]/', '', (string) $phone );
}

/**
 * Tách ô nhiều dòng "A | B" thành mảng [A, B].
 *
 * @param string $text Nội dung.
 * @return array
 */
function sgp_lines( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$out[] = array_map( 'trim', array_pad( explode( '|', $line, 2 ), 2, '' ) );
	}
	return $out;
}

/**
 * Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function sgp_customize_register( $wp_customize ) {
	$d = sgp_defaults();
	$wp_customize->add_panel( 'sgp_panel', array( 'title' => 'Website dự án', 'priority' => 5 ) );
	$sections = array(
		'sgp_company' => 'Thông tin công ty & liên hệ',
		'sgp_listing' => 'Trang danh sách dự án',
		'sgp_form'    => 'Form đăng ký & màu sắc',
	);
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section( $id, array( 'title' => $title, 'panel' => 'sgp_panel' ) );
	}
	$fields = array(
		'company'        => array( 'sgp_company', 'text', 'Tên thương hiệu' ),
		'company_full'   => array( 'sgp_company', 'text', 'Tên pháp nhân đầy đủ' ),
		'tagline'        => array( 'sgp_company', 'text', 'Khẩu hiệu' ),
		'tax_code'       => array( 'sgp_company', 'text', 'Mã số thuế' ),
		'address'        => array( 'sgp_company', 'text', 'Địa chỉ trụ sở' ),
		'hotline'        => array( 'sgp_company', 'text', 'Hotline' ),
		'zalo'           => array( 'sgp_company', 'text', 'Số Zalo' ),
		'email'          => array( 'sgp_company', 'email', 'Email liên hệ (hiển thị)' ),
		'lead_email'     => array( 'sgp_company', 'email', 'Email nhận thông báo khách mới' ),
		'working_hours'  => array( 'sgp_company', 'text', 'Giờ làm việc' ),
		'facebook'       => array( 'sgp_company', 'url', 'Facebook' ),
		'youtube'        => array( 'sgp_company', 'url', 'YouTube' ),
		'archive_title'  => array( 'sgp_listing', 'text', 'Tiêu đề H1 trang /du-an/' ),
		'archive_intro'  => array( 'sgp_listing', 'textarea', 'Đoạn giới thiệu đầu trang /du-an/' ),
		'archive_bottom' => array( 'sgp_listing', 'textarea', 'Nội dung SEO cuối trang /du-an/ (cho phép HTML)' ),
		'disclaimer'     => array( 'sgp_listing', 'textarea', 'Lưu ý miễn trừ cuối trang dự án' ),
		'form_title'     => array( 'sgp_form', 'text', 'Tiêu đề form' ),
		'form_perks'     => array( 'sgp_form', 'textarea', 'Lợi ích khi đăng ký (mỗi dòng 1 ý)' ),
		'color_primary'  => array( 'sgp_form', 'color', 'Màu chủ đạo' ),
		'color_accent'   => array( 'sgp_form', 'color', 'Màu nhấn' ),
	);
	foreach ( $fields as $key => $f ) {
		$san = array(
			'text'     => 'sanitize_text_field',
			'email'    => 'sanitize_email',
			'url'      => 'esc_url_raw',
			'textarea' => 'wp_kses_post',
			'color'    => 'sanitize_hex_color',
		);
		$wp_customize->add_setting( 'sgp_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => $san[ $f[1] ] ) );
		if ( 'color' === $f[1] ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sgp_' . $key, array( 'label' => $f[2], 'section' => $f[0] ) ) );
		} else {
			$wp_customize->add_control( 'sgp_' . $key, array( 'label' => $f[2], 'section' => $f[0], 'type' => $f[1] ) );
		}
	}
}
add_action( 'customize_register', 'sgp_customize_register' );
