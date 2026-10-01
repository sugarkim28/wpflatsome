<?php
/**
 * Flatsome Portal – website tổng hợp nhiều dự án bất động sản.
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

define( 'SGP_VERSION', '0.1.0' );
define( 'SGP_DIR', get_stylesheet_directory() );
define( 'SGP_URI', get_stylesheet_directory_uri() );

require_once SGP_DIR . '/inc/options.php';
require_once SGP_DIR . '/inc/projects.php';
require_once SGP_DIR . '/inc/leads.php';
require_once SGP_DIR . '/inc/mail.php';
require_once SGP_DIR . '/inc/seo.php';
require_once SGP_DIR . '/inc/shortcodes.php';
require_once SGP_DIR . '/inc/demo.php';

/**
 * CSS/JS giao diện.
 */
function sgp_enqueue_assets() {
	wp_enqueue_style( 'sgp-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'sgp-portal', SGP_URI . '/assets/css/portal.css', array( 'flatsome-main' ), SGP_VERSION );
	wp_add_inline_style(
		'sgp-portal',
		sprintf( ':root{--sgp-primary:%s;--sgp-accent:%s;}', esc_html( sgp_opt( 'color_primary' ) ), esc_html( sgp_opt( 'color_accent' ) ) )
	);
	wp_enqueue_script( 'sgp-portal', SGP_URI . '/assets/js/portal.js', array(), SGP_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'sgp_enqueue_assets', 120 );

/**
 * Nút liên hệ nổi + thanh liên hệ dưới cùng trên điện thoại.
 */
function sgp_render_contact_bar() {
	get_template_part( 'template-parts/portal/contact-float' );
}
add_action( 'wp_footer', 'sgp_render_contact_bar' );

/**
 * Class cho body.
 *
 * @param array $classes Class.
 * @return array
 */
function sgp_body_class( $classes ) {
	$classes[] = 'sgp';
	return $classes;
}
add_filter( 'body_class', 'sgp_body_class' );
