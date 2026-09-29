<?php
/**
 * Flatsome Child – Landing page Bất động sản.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_VERSION', '1.0.0' );
define( 'BDS_DIR', get_stylesheet_directory() );
define( 'BDS_URI', get_stylesheet_directory_uri() );

require_once BDS_DIR . '/inc/helpers.php';
require_once BDS_DIR . '/inc/customizer.php';
require_once BDS_DIR . '/inc/leads.php';
require_once BDS_DIR . '/inc/shortcodes.php';

/**
 * Assets cho landing page. Chỉ nạp ở trang dùng template landing hoặc trang có shortcode bds_*.
 */
function bds_enqueue_assets() {
	if ( ! bds_is_landing_context() ) {
		return;
	}

	wp_enqueue_style( 'bds-landing', BDS_URI . '/assets/css/landing.css', array( 'flatsome-main' ), BDS_VERSION );

	$primary = bds_opt( 'color_primary' );
	$accent  = bds_opt( 'color_accent' );
	wp_add_inline_style(
		'bds-landing',
		sprintf( ':root{--bds-primary:%s;--bds-accent:%s;}', esc_html( $primary ), esc_html( $accent ) )
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
