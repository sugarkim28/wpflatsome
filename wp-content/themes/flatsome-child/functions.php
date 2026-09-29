<?php
/**
 * Flatsome Child – Landing page Bất động sản.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_VERSION', '1.4.0' );
define( 'BDS_DIR', get_stylesheet_directory() );
define( 'BDS_URI', get_stylesheet_directory_uri() );

require_once BDS_DIR . '/inc/helpers.php';
require_once BDS_DIR . '/inc/customizer.php';
require_once BDS_DIR . '/inc/leads.php';
require_once BDS_DIR . '/inc/shortcodes.php';
require_once BDS_DIR . '/inc/seo.php';
require_once BDS_DIR . '/inc/importer.php';

/**
 * Assets cho landing page. Chỉ nạp ở trang dùng template landing hoặc trang có shortcode bds_*.
 */
function bds_enqueue_assets() {
	if ( ! bds_is_landing_context() ) {
		return;
	}

	wp_enqueue_style( 'bds-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'bds-landing', BDS_URI . '/assets/css/landing.css', array( 'flatsome-main' ), BDS_VERSION );

	$primary = bds_opt( 'color_primary' );
	$accent  = bds_opt( 'color_accent' );
	wp_add_inline_style(
		'bds-landing',
		sprintf( ':root{--bds-primary:%s;--bds-accent:%s;--bds-beige:%s;}', esc_html( $primary ), esc_html( $accent ), esc_html( bds_opt( 'color_beige' ) ) )
	);

	wp_enqueue_script( 'bds-landing', BDS_URI . '/assets/js/landing.js', array(), BDS_VERSION, true );
	wp_localize_script(
		'bds-landing',
		'bdsLanding',
		array(
			'popupDelay' => absint( bds_opt( 'popup_delay' ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'bds_enqueue_assets', 120 );

/**
 * Nút liên hệ nổi (Hotline / Zalo / Messenger) + popup đăng ký.
 */
function bds_render_floating_contact() {
	if ( ! bds_is_landing_context() ) {
		return;
	}
	get_template_part( 'template-parts/bds/floating-contact' );
	if ( absint( bds_opt( 'popup_delay' ) ) > 0 ) {
		get_template_part( 'template-parts/bds/popup' );
	}
}
add_action( 'wp_footer', 'bds_render_floating_contact' );

/**
 * Trang chủ tự dùng template landing khi bật tuỳ chọn "Tự dùng landing cho trang chủ".
 *
 * @param string $template Đường dẫn template WordPress đã chọn.
 * @return string
 */
function bds_front_page_template( $template ) {
	if ( bds_is_forced_front() ) {
		$landing = locate_template( 'page-templates/landing-bds.php' );
		if ( $landing ) {
			return $landing;
		}
	}
	return $template;
}
add_filter( 'template_include', 'bds_front_page_template', 99 );

/**
 * Thanh menu riêng của landing (thay header Flatsome trên trang landing).
 */
function bds_render_landing_header() {
	if ( bds_is_landing_page() && bds_opt( 'own_header' ) ) {
		get_template_part( 'template-parts/bds/header' );
	}
}
add_action( 'flatsome_before_header', 'bds_render_landing_header' );

/**
 * Class cho body để CSS ẩn header Flatsome khi dùng thanh menu riêng.
 *
 * @param array $classes Class.
 * @return array
 */
function bds_body_class( $classes ) {
	if ( bds_is_landing_page() ) {
		$classes[] = 'bds-lux';
		if ( bds_opt( 'own_header' ) ) {
			$classes[] = 'bds-own-header';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'bds_body_class' );
