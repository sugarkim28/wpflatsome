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
		'hero_title'    => array( 'Tiêu đề banner (bọc chữ nổi bật trong *…*)', 'text', 'Học vui, *hiểu sâu, nhớ lâu!*' ),
		'hero_lead'     => array( 'Mô tả dưới tiêu đề', 'textarea', 'Hơn 80 bài học lớp 1–5 bám sát sách Kết nối tri thức với cuộc sống: giải thích dễ hiểu, ví dụ gần gũi, bí kíp ghi nhớ, “Em có biết?” và câu hỏi luyện tập có lời giải thích. Học xong, bé chơi một chút để nhớ lâu hơn.' ),
		'hero_bubble'   => array( 'Lời chào của bạn Cú Bông', 'text', 'Chào em! Mình là Cú Bông. Hôm nay mình học gì nhỉ? 🦉' ),
		'color_primary' => array( 'Màu chủ đạo', 'color', '#7c4dff' ),
		'color_accent'  => array( 'Màu nhấn (nút vàng, viền)', 'color', '#ffc83d' ),
		'contact'       => array( 'Ghi chú liên hệ thêm (hiện ở trang Góc phụ huynh)', 'text', '' ),
		'logo_style'    => array( 'Logo trên header', 'select', 'cobrand', array( 'cobrand' => 'Học Vui × Tin Học 119', 'hocvui' => 'Chỉ Học Vui', 'tinhoc119' => 'Chỉ Tin Học 119' ) ),
		'org_name'      => array( 'Đơn vị phát triển', 'text', 'Tin Học 119' ),
		'org_slogan'    => array( 'Khẩu hiệu đơn vị', 'text', 'Uy tín tạo niềm tin' ),
		'hotline'       => array( 'Hotline', 'text', '0914 108 322' ),
		'zalo'          => array( 'Số Zalo', 'text', '0914 108 322' ),
		'email'         => array( 'Email', 'text', '' ),
		'address'       => array( 'Địa chỉ', 'text', '' ),
		'hours'         => array( 'Giờ hỗ trợ', 'text', '8:00 – 21:00, thứ 2 – chủ nhật' ),
		'facebook'      => array( 'Link Facebook', 'url', '' ),
		'youtube'       => array( 'Link YouTube', 'url', '' ),
		'tiktok'        => array( 'Link TikTok', 'url', '' ),
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
	return '' === $v ? $d : (string) $v;
}

/**
 * Số điện thoại dạng tel: (chỉ chữ số, đầu +).
 *
 * @param string $phone Số hiển thị.
 * @return string
 */
function hv_tel( $phone ) {
	return preg_replace( '/[^0-9+]/', '', (string) $phone );
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
				'sanitize_callback' => 'color' === $f[1] ? 'sanitize_hex_color' : ( 'textarea' === $f[1] ? 'sanitize_textarea_field' : ( 'url' === $f[1] ? 'esc_url_raw' : 'sanitize_text_field' ) ),
			)
		);
		if ( 'color' === $f[1] ) {
			$wpc->add_control( new WP_Customize_Color_Control( $wpc, 'hv_' . $key, array( 'label' => $f[0], 'section' => 'hv_main' ) ) );
		} elseif ( 'select' === $f[1] ) {
			$wpc->add_control( 'hv_' . $key, array( 'label' => $f[0], 'section' => 'hv_main', 'type' => 'select', 'choices' => $f[3] ) );
		} else {
			$wpc->add_control( 'hv_' . $key, array( 'label' => $f[0], 'section' => 'hv_main', 'type' => $f[1] ) );
		}
	}
}
add_action( 'customize_register', 'hv_customize' );
