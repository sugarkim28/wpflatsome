<?php
/**
 * Cài đặt chung (Giao diện → Tuỳ biến → Website dịch vụ) và hàm tiện ích.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giá trị mặc định (nội dung mẫu – đổi trong Tuỳ biến).
 *
 * @return array
 */
function sgd_defaults() {
	return array(
		'company'        => 'Tin Học 119',
		'company_full'   => 'Tin Học 119',
		'tagline'        => 'Uy tín tạo niềm tin',
		'hotline'        => '0914 108 322',
		'zalo'           => '0914108322',
		'email'          => '',
		'lead_email'     => '',
		'address'        => 'Số ... đường ..., Phường ..., TP. Hồ Chí Minh',
		'tax_code'       => '',
		'working_hours'  => '8:00 – 17:30, Thứ 2 – Thứ 7',
		'hotlines'       => '',
		'messenger'      => '',
		'branches'       => '',
		'facebook'       => '',
		'youtube'        => '',
		'color_primary'  => '#123fb8',
		'color_accent'   => '#e10b17',
		'archive_title'  => 'Dịch vụ doanh nghiệp trọn gói',
		'archive_intro'  => 'Thành lập công ty, thay đổi giấy phép kinh doanh, kê khai thuế và kế toán trọn gói cho doanh nghiệp vừa và nhỏ. Báo giá rõ ràng, không phát sinh, làm hồ sơ online – khách hàng không cần đi lại.',
		'archive_bottom' => '',
		'blog_intro'     => 'Hướng dẫn thủ tục thành lập, thay đổi giấy phép kinh doanh, kê khai thuế và kế toán cho doanh nghiệp – cập nhật theo quy định mới nhất.',
		'disclaimer'     => 'Phí dịch vụ trên chưa gồm lệ phí nhà nước (nếu có) và có thể thay đổi theo hồ sơ thực tế. Quy định pháp luật có thể thay đổi; vui lòng liên hệ chuyên viên để được tư vấn chính xác cho trường hợp của bạn.',
		'topbar_text'    => 'Tin Học 119 – Uy tín tạo niềm tin',
		'expert'         => '',
		'expert_title'   => '',
		'popup_delay'    => '30',
		'popup_title'    => 'Gửi yêu cầu – nhận ngay ưu đãi',
		'popup_sub'      => 'Tư vấn thành lập công ty, thuế, kế toán miễn phí',
		'form_title'     => 'Nhận tư vấn & báo giá miễn phí',
		'form_perks'     => "Tư vấn miễn phí, trả lời trong 15 phút\nBáo giá trọn gói, cam kết không phát sinh\nSoạn hồ sơ – nộp online – giao kết quả tận nơi",
	);
}

/**
 * Đọc tuỳ chọn.
 *
 * @param string $key Khoá.
 * @return mixed
 */
function sgd_opt( $key ) {
	$d   = sgd_defaults();
	$val = get_theme_mod( 'sgd_' . $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
	if ( '' === $val && in_array( $key, array( 'email', 'lead_email' ), true ) ) {
		$val = get_option( 'admin_email' );
	}
	return $val;
}

/**
 * Số điện thoại chỉ còn chữ số (cho tel: / zalo).
 *
 * @param string $phone Số.
 * @return string
 */
function sgd_tel( $phone ) {
	return preg_replace( '/[^\d+]/', '', (string) $phone );
}

/**
 * Tách ô nhiều dòng "A | B | C" thành mảng [A, B, C] (luôn có ít nhất $min phần tử).
 *
 * @param string $text Nội dung.
 * @param int    $min  Số cột tối thiểu.
 * @return array
 */
function sgd_lines( $text, $min = 2 ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$out[] = array_map( 'trim', array_pad( explode( '|', $line, max( 2, $min ) ), $min, '' ) );
	}
	return $out;
}

/**
 * Hotline theo khu vực: [[nhãn, số], ...]. Không khai báo → [[ 'Hotline', hotline chính ]].
 *
 * @return array
 */
function sgd_hotlines() {
	$out = array();
	foreach ( sgd_lines( sgd_opt( 'hotlines' ) ) as $l ) {
		if ( '' !== $l[1] ) {
			$out[] = array( $l[0], $l[1] );
		} elseif ( preg_match( '/\d{6,}/', sgd_tel( $l[0] ) ) ) {
			$out[] = array( 'Hotline', $l[0] );
		}
	}
	if ( ! $out && sgd_opt( 'hotline' ) ) {
		$out[] = array( 'Hotline', sgd_opt( 'hotline' ) );
	}
	return $out;
}

/**
 * Văn phòng / chi nhánh: [[tên, địa chỉ, điện thoại, email], ...]. Không khai báo → trụ sở chính.
 *
 * @return array
 */
function sgd_branches() {
	$out = sgd_lines( sgd_opt( 'branches' ), 4 );
	if ( ! $out ) {
		$out[] = array( 'Trụ sở chính', sgd_opt( 'address' ), sgd_opt( 'hotline' ), sgd_opt( 'email' ) );
	}
	return $out;
}

/**
 * Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function sgd_customize_register( $wp_customize ) {
	$d = sgd_defaults();
	$wp_customize->add_panel( 'sgd_panel', array( 'title' => 'Website dịch vụ', 'priority' => 5 ) );
	$sections = array(
		'sgd_company' => 'Thông tin công ty & liên hệ',
		'sgd_listing' => 'Trang danh sách dịch vụ',
		'sgd_form'    => 'Form tư vấn & màu sắc',
	);
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section( $id, array( 'title' => $title, 'panel' => 'sgd_panel' ) );
	}
	$fields = array(
		'company'        => array( 'sgd_company', 'text', 'Tên thương hiệu' ),
		'company_full'   => array( 'sgd_company', 'text', 'Tên pháp nhân đầy đủ' ),
		'tagline'        => array( 'sgd_company', 'text', 'Khẩu hiệu' ),
		'tax_code'       => array( 'sgd_company', 'text', 'Mã số thuế' ),
		'address'        => array( 'sgd_company', 'text', 'Địa chỉ trụ sở' ),
		'hotline'        => array( 'sgd_company', 'text', 'Hotline' ),
		'zalo'           => array( 'sgd_company', 'text', 'Số Zalo' ),
		'email'          => array( 'sgd_company', 'email', 'Email liên hệ (hiển thị) – trống = email quản trị' ),
		'lead_email'     => array( 'sgd_company', 'email', 'Email nhận thông báo khách mới – trống = email quản trị' ),
		'working_hours'  => array( 'sgd_company', 'text', 'Giờ làm việc' ),
		'hotlines'       => array( 'sgd_company', 'textarea', 'Hotline theo khu vực – mỗi dòng: Nhãn | Số (vd: Miền Nam | 0900 000 000). Trống = dùng Hotline chính' ),
		'messenger'      => array( 'sgd_company', 'text', 'Messenger – tên trang Facebook (vd: tencongty) để hiện nút chat' ),
		'branches'       => array( 'sgd_company', 'textarea', 'Văn phòng / chi nhánh ở footer – mỗi dòng: Tên | Địa chỉ | Điện thoại | Email' ),
		'facebook'       => array( 'sgd_company', 'url', 'Facebook' ),
		'youtube'        => array( 'sgd_company', 'url', 'YouTube' ),
		'archive_title'  => array( 'sgd_listing', 'text', 'Tiêu đề H1 trang /dich-vu/' ),
		'archive_intro'  => array( 'sgd_listing', 'textarea', 'Đoạn giới thiệu đầu trang /dich-vu/' ),
		'archive_bottom' => array( 'sgd_listing', 'textarea', 'Nội dung SEO cuối trang /dich-vu/ (cho phép HTML)' ),
		'blog_intro'     => array( 'sgd_listing', 'textarea', 'Đoạn giới thiệu đầu trang Kiến thức (blog)' ),
		'disclaimer'     => array( 'sgd_listing', 'textarea', 'Lưu ý cuối trang dịch vụ' ),
		'topbar_text'    => array( 'sgd_company', 'text', 'Câu khẩu hiệu trên thanh trên cùng' ),
		'expert'         => array( 'sgd_company', 'text', 'Người kiểm duyệt nội dung dịch vụ (vd: Nguyễn Văn A) – tăng uy tín E-E-A-T, hiện cuối trang dịch vụ' ),
		'expert_title'   => array( 'sgd_company', 'text', 'Chức danh người kiểm duyệt (vd: Luật sư, Đại lý thuế)' ),
		'popup_delay'    => array( 'sgd_form', 'text', 'Popup form tự mở sau N giây – chỉ trên máy tính, mỗi phiên 1 lần (điện thoại không tự mở để tránh bị Google phạt quảng cáo xen ngang) – 0 = tắt' ),
		'popup_title'    => array( 'sgd_form', 'text', 'Tiêu đề popup' ),
		'popup_sub'      => array( 'sgd_form', 'text', 'Dòng phụ của popup' ),
		'form_title'     => array( 'sgd_form', 'text', 'Tiêu đề form' ),
		'form_perks'     => array( 'sgd_form', 'textarea', 'Lợi ích khi đăng ký (mỗi dòng 1 ý)' ),
		'color_primary'  => array( 'sgd_form', 'color', 'Màu chủ đạo' ),
		'color_accent'   => array( 'sgd_form', 'color', 'Màu nhấn (nút)' ),
	);
	$san = array(
		'text'     => 'sanitize_text_field',
		'email'    => 'sanitize_email',
		'url'      => 'esc_url_raw',
		'textarea' => 'wp_kses_post',
		'color'    => 'sanitize_hex_color',
	);
	foreach ( $fields as $key => $f ) {
		$wp_customize->add_setting( 'sgd_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => $san[ $f[1] ] ) );
		if ( 'color' === $f[1] ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sgd_' . $key, array( 'label' => $f[2], 'section' => $f[0] ) ) );
		} else {
			$wp_customize->add_control( 'sgd_' . $key, array( 'label' => $f[2], 'section' => $f[0], 'type' => $f[1] ) );
		}
	}
}
add_action( 'customize_register', 'sgd_customize_register' );
