<?php
/**
 * Flatsome Học Vui – website giáo dục vui nhộn cho học sinh Tiểu học (lớp 1–5).
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

define( 'HV_VERSION', '0.1.0' );
define( 'HV_DIR', get_stylesheet_directory() );
define( 'HV_URI', get_stylesheet_directory_uri() );

require_once HV_DIR . '/inc/options.php';
require_once HV_DIR . '/inc/content.php';
require_once HV_DIR . '/inc/games.php';
require_once HV_DIR . '/inc/home.php';
require_once HV_DIR . '/inc/seo.php';
require_once HV_DIR . '/inc/demo.php';

/**
 * CSS/JS giao diện + trò chơi.
 */
function hv_enqueue_assets() {
	// Baloo 2 (tiêu đề tròn, vui) + Nunito (chữ thường dễ đọc) – cả hai có bộ ký tự tiếng Việt.
	wp_enqueue_style( 'hv-fonts', 'https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;800&family=Nunito:wght@400;600;800&display=swap', array(), null );
	wp_enqueue_style( 'hv-main', HV_URI . '/assets/css/hocvui.css', array( 'flatsome-main' ), HV_VERSION . '.' . filemtime( HV_DIR . '/assets/css/hocvui.css' ) );
	wp_add_inline_style(
		'hv-main',
		sprintf(
			':root{--hv-primary:%s;--hv-accent:%s;--hv-mascot:url("%s");}',
			esc_html( hv_opt( 'color_primary' ) ),
			esc_html( hv_opt( 'color_accent' ) ),
			esc_url( HV_URI . '/assets/img/mascot.svg' )
		)
	);
	wp_enqueue_script( 'hv-main', HV_URI . '/assets/js/hocvui.js', array(), HV_VERSION . '.' . filemtime( HV_DIR . '/assets/js/hocvui.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'hv-main', 'window.hvConfig=' . wp_json_encode( array( 'gamesUrl' => hv_games_url() ) ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'hv_enqueue_assets', 120 );

/**
 * Kết nối sớm tới máy chủ phông chữ.
 *
 * @param array  $urls          URL.
 * @param string $relation_type Loại.
 * @return array
 */
function hv_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'hv_resource_hints', 10, 2 );

/**
 * Class cho body.
 *
 * @param array $classes Class.
 * @return array
 */
function hv_body_class( $classes ) {
	$classes[] = 'hv';
	return $classes;
}
add_filter( 'body_class', 'hv_body_class' );

/**
 * Logo mặc định Học Vui khi chưa tải logo ở Tuỳ biến → Header → Logo.
 *
 * @param mixed $logo Logo đã lưu.
 * @return mixed
 */
function hv_default_logo( $logo ) {
	return $logo ? $logo : HV_URI . '/assets/img/logo.svg';
}
add_filter( 'theme_mod_site_logo', 'hv_default_logo' );

/**
 * Favicon mặc định.
 */
function hv_default_favicon() {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( HV_URI . '/assets/img/icon.svg' ) . "\">\n";
	}
}
add_action( 'wp_head', 'hv_default_favicon', 2 );

/**
 * Kích hoạt theme: đăng ký đường dẫn bài học, lớp, môn.
 */
function hv_after_switch() {
	hv_register_content();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'hv_after_switch' );
