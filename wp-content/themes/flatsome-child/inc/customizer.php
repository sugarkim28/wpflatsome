<?php
/**
 * Customizer: Giao diện > Tuỳ biến > "Landing Bất động sản".
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize checkbox.
 *
 * @param mixed $value Giá trị.
 * @return int
 */
function bds_sanitize_checkbox( $value ) {
	return $value ? 1 : 0;
}

/**
 * Chỉ cho phép URL nhúng Google Maps (hoặc cả thẻ iframe – sẽ lấy src).
 *
 * @param string $value Giá trị.
 * @return string
 */
function bds_sanitize_map( $value ) {
	if ( preg_match( '/src=["\']([^"\']+)["\']/i', (string) $value, $m ) ) {
		$value = $m[1];
	}
	$value = esc_url_raw( trim( (string) $value ) );
	$host  = wp_parse_url( $value, PHP_URL_HOST );
	if ( $host && preg_match( '/(^|\.)google\.[a-z.]+$/i', $host ) ) {
		return $value;
	}
	return '';
}

/**
 * Đăng ký panel/section/control.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function bds_customize_register( $wp_customize ) {
	$d = bds_defaults();

	$wp_customize->add_panel(
		'bds_panel',
		array(
			'title'       => 'Landing Bất động sản',
			'description' => 'Nội dung cho trang dùng template "Landing Page Bất động sản". Mỗi dòng trong ô nhiều dòng có dạng: Tiêu đề | Mô tả',
			'priority'    => 5,
		)
	);

	$sections = array(
		'bds_general'    => 'Cài đặt chung & liên hệ',
		'bds_seo'        => 'SEO & chia sẻ mạng xã hội',
		'bds_hero'       => 'Banner đầu trang (Hero)',
		'bds_overview'   => 'Tổng quan dự án',
		'bds_location'   => 'Vị trí',
		'bds_amenities'  => 'Tiện ích',
		'bds_floorplans' => 'Mặt bằng',
		'bds_gallery'    => 'Thư viện ảnh & Video',
		'bds_pricing'    => 'Chính sách bán hàng',
		'bds_reasons'    => 'Lý do sở hữu',
		'bds_developer'  => 'Chủ đầu tư',
		'bds_faq'        => 'Câu hỏi thường gặp (FAQ)',
		'bds_register'   => 'Form đăng ký',
	);
	$priority = 10;
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			$id,
			array(
				'title'    => $title,
				'panel'    => 'bds_panel',
				'priority' => $priority,
			)
		);
		$priority += 10;
	}

	/*
	 * Định nghĩa field: key => array( section, type, label ).
	 * type: text | textarea | color | image | checkbox | number | email | url | map
	 */
	$fields = array(
		'color_primary'    => array( 'bds_general', 'color', 'Màu chủ đạo' ),
		'color_accent'     => array( 'bds_general', 'color', 'Màu nhấn (nút, điểm nhấn)' ),
		'hotline'          => array( 'bds_general', 'text', 'Hotline' ),
		'zalo'             => array( 'bds_general', 'text', 'Số Zalo (để trống để ẩn)' ),
		'messenger'        => array( 'bds_general', 'url', 'Link Messenger, vd https://m.me/tenpage (để trống để ẩn)' ),
		'lead_email'       => array( 'bds_general', 'email', 'Email nhận thông báo khách hàng đăng ký' ),
		'popup_delay'      => array( 'bds_general', 'number', 'Tự bật popup đăng ký sau N giây (0 = tắt)' ),

		'seo_title'             => array( 'bds_seo', 'text', 'Tiêu đề SEO (khoảng 50–60 ký tự). Bỏ qua nếu dùng Rank Math/Yoast' ),
		'seo_description'       => array( 'bds_seo', 'textarea', 'Mô tả SEO (khoảng 140–160 ký tự). Bỏ qua nếu dùng Rank Math/Yoast' ),
		'seo_image'             => array( 'bds_seo', 'image', 'Ảnh khi chia sẻ Facebook/Zalo (1200×630). Trống = dùng ảnh banner' ),
		'schema_enable'         => array( 'bds_seo', 'checkbox', 'Bật dữ liệu cấu trúc dự án (schema.org)' ),
		'schema_alt_name'       => array( 'bds_seo', 'text', 'Tên khác của dự án (tên pháp lý)' ),
		'schema_street'         => array( 'bds_seo', 'text', 'Địa chỉ dự án – số nhà, đường' ),
		'schema_locality'       => array( 'bds_seo', 'text', 'Địa chỉ dự án – phường/xã' ),
		'schema_region'         => array( 'bds_seo', 'text', 'Địa chỉ dự án – tỉnh/thành phố' ),
		'schema_units'          => array( 'bds_seo', 'number', 'Tổng số sản phẩm' ),
		'schema_agency_name'    => array( 'bds_seo', 'text', 'Tên đơn vị phân phối' ),
		'schema_agency_address' => array( 'bds_seo', 'text', 'Địa chỉ đơn vị phân phối' ),

		'hero_image'       => array( 'bds_hero', 'image', 'Ảnh nền (khuyến nghị 1920×1080)' ),
		'hero_eyebrow'     => array( 'bds_hero', 'text', 'Dòng nhỏ phía trên tiêu đề' ),
		'hero_title'       => array( 'bds_hero', 'text', 'Tên dự án / Tiêu đề' ),
		'hero_subtitle'    => array( 'bds_hero', 'textarea', 'Mô tả ngắn' ),
		'hero_price'       => array( 'bds_hero', 'text', 'Giá / Điểm nổi bật' ),
		'hero_cta'         => array( 'bds_hero', 'text', 'Chữ trên nút kêu gọi' ),
		'highlights'       => array( 'bds_hero', 'textarea', 'Dải số liệu nổi bật dưới banner (Nhãn | Giá trị), tối đa 4 dòng' ),

		'show_overview'    => array( 'bds_overview', 'checkbox', 'Hiển thị mục này' ),
		'overview_title'   => array( 'bds_overview', 'text', 'Tiêu đề' ),
		'overview_text'    => array( 'bds_overview', 'textarea', 'Giới thiệu' ),
		'overview_facts'   => array( 'bds_overview', 'textarea', 'Thông số (Nhãn | Giá trị)' ),
		'overview_image'   => array( 'bds_overview', 'image', 'Ảnh phối cảnh' ),

		'show_location'    => array( 'bds_location', 'checkbox', 'Hiển thị mục này' ),
		'location_title'   => array( 'bds_location', 'text', 'Tiêu đề' ),
		'location_text'    => array( 'bds_location', 'textarea', 'Mô tả' ),
		'location_points'  => array( 'bds_location', 'textarea', 'Kết nối (Thời gian | Địa điểm)' ),
		'location_image'   => array( 'bds_location', 'image', 'Ảnh bản đồ vị trí' ),
		'location_map'     => array( 'bds_location', 'map', 'Google Maps: dán mã nhúng iframe hoặc link embed (hiện khi không có ảnh)' ),

		'show_amenities'   => array( 'bds_amenities', 'checkbox', 'Hiển thị mục này' ),
		'amenities_title'  => array( 'bds_amenities', 'text', 'Tiêu đề' ),
		'amenities'        => array( 'bds_amenities', 'textarea', 'Danh sách tiện ích (Tên | Mô tả)' ),
		'amenities_image'  => array( 'bds_amenities', 'image', 'Ảnh tổng thể tiện ích' ),

		'show_floorplans'  => array( 'bds_floorplans', 'checkbox', 'Hiển thị mục này' ),
		'floorplans_title' => array( 'bds_floorplans', 'text', 'Tiêu đề' ),

		'show_gallery'     => array( 'bds_gallery', 'checkbox', 'Hiển thị thư viện ảnh' ),
		'gallery_title'    => array( 'bds_gallery', 'text', 'Tiêu đề thư viện ảnh' ),

		'show_pricing'     => array( 'bds_pricing', 'checkbox', 'Hiển thị mục này' ),
		'pricing_title'    => array( 'bds_pricing', 'text', 'Tiêu đề' ),
		'pricing_items'    => array( 'bds_pricing', 'textarea', 'Chính sách (Tiêu đề | Mô tả)' ),

		'show_reasons'     => array( 'bds_reasons', 'checkbox', 'Hiển thị mục này' ),
		'reasons_title'    => array( 'bds_reasons', 'text', 'Tiêu đề' ),
		'reasons'          => array( 'bds_reasons', 'textarea', 'Danh sách lý do (Tiêu đề | Mô tả)' ),

		'show_developer'   => array( 'bds_developer', 'checkbox', 'Hiển thị mục này' ),
		'developer_title'  => array( 'bds_developer', 'text', 'Tiêu đề' ),
		'developer_text'   => array( 'bds_developer', 'textarea', 'Giới thiệu' ),
		'developer_points' => array( 'bds_developer', 'textarea', 'Điểm nổi bật (Nhãn | Nội dung)' ),
		'developer_image'  => array( 'bds_developer', 'image', 'Ảnh / logo chủ đầu tư' ),

		'show_faq'         => array( 'bds_faq', 'checkbox', 'Hiển thị mục này' ),
		'faq_title'        => array( 'bds_faq', 'text', 'Tiêu đề' ),
		'faq'              => array( 'bds_faq', 'textarea', 'Câu hỏi | Trả lời (mỗi dòng một câu)' ),

		'register_title'   => array( 'bds_register', 'text', 'Tiêu đề' ),
		'register_text'    => array( 'bds_register', 'textarea', 'Mô tả' ),
		'register_image'   => array( 'bds_register', 'image', 'Ảnh nền' ),
		'agency_name'      => array( 'bds_register', 'text', 'Đơn vị tư vấn / Tổng đại lý' ),
		'agency_address'   => array( 'bds_register', 'text', 'Địa chỉ trụ sở' ),
		'contact_email'    => array( 'bds_register', 'email', 'Email liên hệ hiển thị trên trang' ),
		'register_success' => array( 'bds_register', 'textarea', 'Thông báo sau khi gửi thành công' ),
		'disclaimer'       => array( 'bds_register', 'textarea', 'Dòng lưu ý pháp lý cuối trang (để trống để ẩn)' ),
	);

	for ( $i = 1; $i <= 4; $i++ ) {
		$fields[ "floorplan_{$i}_image" ] = array( 'bds_floorplans', 'image', "Mặt bằng {$i} – Ảnh" );
		$fields[ "floorplan_{$i}_title" ] = array( 'bds_floorplans', 'text', "Mặt bằng {$i} – Tên (vd: Căn 2PN – 68m²)" );
		$fields[ "floorplan_{$i}_desc" ]  = array( 'bds_floorplans', 'text', "Mặt bằng {$i} – Mô tả ngắn" );
	}
	for ( $i = 1; $i <= 8; $i++ ) {
		$fields[ "gallery_{$i}" ] = array( 'bds_gallery', 'image', "Ảnh {$i}" );
	}
	$fields['show_video']  = array( 'bds_gallery', 'checkbox', 'Hiển thị video' );
	$fields['video_title'] = array( 'bds_gallery', 'text', 'Tiêu đề video' );
	$fields['video_url']   = array( 'bds_gallery', 'url', 'Link YouTube' );

	$sanitizers = array(
		'text'     => 'sanitize_text_field',
		'textarea' => 'sanitize_textarea_field',
		'color'    => 'sanitize_hex_color',
		'image'    => 'absint',
		'checkbox' => 'bds_sanitize_checkbox',
		'number'   => 'absint',
		'email'    => 'sanitize_email',
		'url'      => 'esc_url_raw',
		'map'      => 'bds_sanitize_map',
	);

	foreach ( $fields as $key => $field ) {
		list( $section, $type, $label ) = $field;
		$setting = 'bds_' . $key;

		$wp_customize->add_setting(
			$setting,
			array(
				'default'           => isset( $d[ $key ] ) ? $d[ $key ] : '',
				'sanitize_callback' => $sanitizers[ $type ],
				'transport'         => 'refresh',
			)
		);

		if ( 'color' === $type ) {
			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					$setting,
					array(
						'label'   => $label,
						'section' => $section,
					)
				)
			);
		} elseif ( 'image' === $type ) {
			$wp_customize->add_control(
				new WP_Customize_Media_Control(
					$wp_customize,
					$setting,
					array(
						'label'     => $label,
						'section'   => $section,
						'mime_type' => 'image',
					)
				)
			);
		} else {
			$wp_customize->add_control(
				$setting,
				array(
					'label'   => $label,
					'section' => $section,
					'type'    => 'map' === $type ? 'textarea' : $type,
				)
			);
		}
	}
}
add_action( 'customize_register', 'bds_customize_register' );
