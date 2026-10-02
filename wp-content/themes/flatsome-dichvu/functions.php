<?php
/**
 * Flatsome Dịch vụ – website dịch vụ thành lập công ty, thay đổi giấy phép, thuế, kế toán.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

define( 'SGD_VERSION', '0.7.2' );
define( 'SGD_DIR', get_stylesheet_directory() );
define( 'SGD_URI', get_stylesheet_directory_uri() );

require_once SGD_DIR . '/inc/options.php';
require_once SGD_DIR . '/inc/services.php';
require_once SGD_DIR . '/inc/leads.php';
require_once SGD_DIR . '/inc/mail.php';
require_once SGD_DIR . '/inc/seo.php';
require_once SGD_DIR . '/inc/shortcodes.php';
require_once SGD_DIR . '/inc/home.php';
require_once SGD_DIR . '/inc/demo.php';

/**
 * CSS/JS giao diện.
 */
function sgd_enqueue_assets() {
	// Be Vietnam Pro: phông thiết kế riêng cho tiếng Việt, 4 độ đậm (ít tải hơn), display=swap tránh chữ trắng khi tải.
	wp_enqueue_style( 'sgd-fonts', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;800&display=swap', array(), null );
	// Phiên bản theo thời điểm sửa file: cập nhật theme là trình duyệt tải CSS/JS mới ngay, không bị dùng bản cũ.
	wp_enqueue_style( 'sgd-main', SGD_URI . '/assets/css/dichvu.css', array( 'flatsome-main' ), SGD_VERSION . '.' . filemtime( SGD_DIR . '/assets/css/dichvu.css' ) );
	wp_add_inline_style(
		'sgd-main',
		sprintf( ':root{--sgd-primary:%s;--sgd-accent:%s;}', esc_html( sgd_opt( 'color_primary' ) ), esc_html( sgd_opt( 'color_accent' ) ) )
	);
	wp_enqueue_script( 'sgd-main', SGD_URI . '/assets/js/dichvu.js', array(), SGD_VERSION . '.' . filemtime( SGD_DIR . '/assets/js/dichvu.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	$sgd_contact = get_page_by_path( 'lien-he' );
	wp_add_inline_script( 'sgd-main', 'window.sgdContactUrl=' . wp_json_encode( ( $sgd_contact ? get_permalink( $sgd_contact ) : home_url( '/' ) ) . '#dang-ky' ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'sgd_enqueue_assets', 120 );

/**
 * Kết nối sớm tới máy chủ phông chữ (tải chữ nhanh hơn, cải thiện LCP).
 *
 * @param array  $urls          URL.
 * @param string $relation_type Loại.
 * @return array
 */
function sgd_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'sgd_resource_hints', 10, 2 );

/**
 * Nút liên hệ nổi + thanh liên hệ dưới cùng trên điện thoại.
 */
function sgd_render_contact_bar() {
	get_template_part( 'template-parts/dichvu/contact-float' );
}
add_action( 'wp_footer', 'sgd_render_contact_bar' );

/**
 * Class cho body.
 *
 * @param array $classes Class.
 * @return array
 */
function sgd_body_class( $classes ) {
	$classes[] = 'sgd';
	return $classes;
}
add_filter( 'body_class', 'sgd_body_class' );

/**
 * Logo mặc định Tin Học 119 (SVG trong theme) khi chưa tải logo ở Tuỳ biến → Header → Logo.
 *
 * @param mixed $logo Logo đã lưu (ID ảnh hoặc URL).
 * @return mixed
 */
function sgd_default_logo( $logo ) {
	return $logo ? $logo : SGD_URI . '/assets/img/logo-119.svg';
}
add_filter( 'theme_mod_site_logo', 'sgd_default_logo' );

/**
 * Biểu tượng trang (favicon) mặc định khi chưa đặt Biểu tượng trang web trong WordPress.
 */
function sgd_default_favicon() {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( SGD_URI . '/assets/img/icon-119.svg' ) . "\">\n";
	}
}
add_action( 'wp_head', 'sgd_default_favicon', 2 );
