<?php
/**
 * Flatsome Dịch vụ – website dịch vụ thành lập công ty, thay đổi giấy phép, thuế, kế toán.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

define( 'SGD_VERSION', '0.1.0' );
define( 'SGD_DIR', get_stylesheet_directory() );
define( 'SGD_URI', get_stylesheet_directory_uri() );

require_once SGD_DIR . '/inc/options.php';
require_once SGD_DIR . '/inc/services.php';
require_once SGD_DIR . '/inc/leads.php';
require_once SGD_DIR . '/inc/mail.php';
require_once SGD_DIR . '/inc/seo.php';
require_once SGD_DIR . '/inc/shortcodes.php';
require_once SGD_DIR . '/inc/demo.php';

/**
 * CSS/JS giao diện.
 */
function sgd_enqueue_assets() {
	wp_enqueue_style( 'sgd-fonts', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'sgd-main', SGD_URI . '/assets/css/dichvu.css', array( 'flatsome-main' ), SGD_VERSION );
	wp_add_inline_style(
		'sgd-main',
		sprintf( ':root{--sgd-primary:%s;--sgd-accent:%s;}', esc_html( sgd_opt( 'color_primary' ) ), esc_html( sgd_opt( 'color_accent' ) ) )
	);
	wp_enqueue_script( 'sgd-main', SGD_URI . '/assets/js/dichvu.js', array(), SGD_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'sgd_enqueue_assets', 120 );

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
