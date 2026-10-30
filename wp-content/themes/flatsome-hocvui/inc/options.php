<?php
/**
 * Tuỳ biến → Học Vui: chữ trên banner trang chủ, màu chủ đạo.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các tuỳ chọn: key => [nhãn, kiểu, mặc định].
 *
 * @return array
 */
function hv_option_fields() {
	return array(
		'hero_title'    => array( 'Tiêu đề banner (bọc chữ nổi bật trong *…*)', 'text', 'Học mà chơi, *chơi mà học!*' ),
		'hero_lead'     => array( 'Mô tả dưới tiêu đề', 'textarea', 'Bài học lớp 1 đến lớp 5 theo chương trình sách giáo khoa mới, kèm trò chơi tính nhẩm, ghép chữ, đố vui, tiếng Anh… Mỗi bài làm đúng là nhận sao, sưu tập nhãn dán thật vui!' ),
		'hero_bubble'   => array( 'Lời chào của bạn Cú Bông', 'text', 'Chào bạn! Hôm nay mình học gì nhỉ? 🦉' ),
		'color_primary' => array( 'Màu chủ đạo', 'color', '#7b4fd6' ),
		'color_accent'  => array( 'Màu nhấn (nút vàng, viền)', 'color', '#ffb400' ),
		'contact'       => array( 'Liên hệ cho phụ huynh (email/Zalo, hiện ở trang Góc phụ huynh)', 'text', '' ),
	);
}

/**
 * Đọc tuỳ chọn.
 *
 * @param string $key Khoá.
 * @return string
 */
function hv_opt( $key ) {
	$f = hv_option_fields();
	$d = isset( $f[ $key ] ) ? $f[ $key ][2] : '';
	$v = get_theme_mod( 'hv_' . $key, $d );
	return '' === $v && 'contact' !== $key ? $d : (string) $v;
}

/**
 * Khai báo trong Tuỳ biến.
 *
 * @param WP_Customize_Manager $wpc Customizer.
 */
function hv_customize( $wpc ) {
	$wpc->add_section( 'hv_main', array( 'title' => 'Học Vui', 'priority' => 25 ) );
	foreach ( hv_option_fields() as $key => $f ) {
		$wpc->add_setting(
			'hv_' . $key,
			array(
				'default'           => $f[2],
				'sanitize_callback' => 'color' === $f[1] ? 'sanitize_hex_color' : ( 'textarea' === $f[1] ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
			)
		);
		if ( 'color' === $f[1] ) {
			$wpc->add_control( new WP_Customize_Color_Control( $wpc, 'hv_' . $key, array( 'label' => $f[0], 'section' => 'hv_main' ) ) );
		} else {
			$wpc->add_control( 'hv_' . $key, array( 'label' => $f[0], 'section' => 'hv_main', 'type' => $f[1] ) );
		}
	}
}
add_action( 'customize_register', 'hv_customize' );
