<?php
/**
 * Hàm tiện ích của theme (an toàn khi plugin chưa bật).
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đọc cài đặt plugin (giá trị dự phòng khi plugin chưa bật).
 *
 * @param string $key Khoá.
 * @return string
 */
function th119t_opt( $key ) {
	if ( function_exists( 'th119_opt' ) ) {
		return (string) th119_opt( $key );
	}
	$fallback = array(
		'brand'   => get_bloginfo( 'name' ),
		'slogan'  => 'Uy tín tạo niềm tin',
		'hotline' => '',
	);
	return isset( $fallback[ $key ] ) ? $fallback[ $key ] : '';
}

/**
 * Icon SVG.
 *
 * @param string $name Tên.
 * @return string
 */
function th119t_ic( $name ) {
	return function_exists( 'th119_ic' ) ? th119_ic( $name ) : '';
}

/**
 * Gọi shortcode của plugin nếu có.
 *
 * @param string $code Shortcode.
 * @return string
 */
function th119t_sc( $code ) {
	return function_exists( 'th119_opt' ) ? do_shortcode( $code ) : '';
}

/**
 * Thời gian đọc (phút).
 *
 * @param WP_Post|null $post Bài.
 * @return int
 */
function th119t_reading_time( $post = null ) {
	$post  = get_post( $post );
	$words = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ), -1, PREG_SPLIT_NO_EMPTY ) );
	return max( 1, (int) ceil( $words / 220 ) );
}

/**
 * Dải tiêu đề trang: breadcrumb + H1 + mô tả.
 *
 * @param string $title H1.
 * @param string $desc  Mô tả (HTML đã an toàn hoặc text).
 * @param string $extra HTML thêm (đã escape).
 */
function th119t_pagehead( $title, $desc = '', $extra = '' ) {
	echo '<header class="th-pagehead"><div class="th-wrap">';
	if ( function_exists( 'th119_breadcrumb_html' ) ) {
		echo th119_breadcrumb_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm.
	}
	echo '<h1 class="th-pagehead__title">' . esc_html( $title ) . '</h1>';
	if ( $desc ) {
		echo '<div class="th-pagehead__desc">' . wp_kses_post( $desc ) . '</div>';
	}
	echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML do theme tạo, đã escape.
	echo '</div></header>';
}

/**
 * Phân trang.
 */
function th119t_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'list',
			'prev_text' => '‹ Trước',
			'next_text' => 'Sau ›',
		)
	);
	if ( $links ) {
		echo '<nav class="th-pagination" aria-label="Phân trang">' . wp_kses_post( $links ) . '</nav>';
	}
}

/**
 * Thẻ bài viết (dùng hàm plugin nếu có).
 *
 * @param WP_Post $p Bài.
 * @return string
 */
function th119t_card( $p ) {
	if ( function_exists( 'th119_post_card' ) ) {
		return th119_post_card( $p );
	}
	return '<article class="th-post"><a class="th-post__link" href="' . esc_url( get_permalink( $p ) ) . '"><div class="th-post__body"><h3 class="th-post__title">' . esc_html( get_the_title( $p ) ) . '</h3></div></a></article>';
}
