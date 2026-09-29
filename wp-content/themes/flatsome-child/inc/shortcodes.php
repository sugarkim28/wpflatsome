<?php
/**
 * Shortcode dùng được trong UX Builder / trình soạn thảo:
 *  [bds_lead_form title="..." button="..." source="..."]
 *  [bds_section name="hero|overview|location|amenities|floorplans|gallery|video|pricing|reasons|developer|faq|register"]
 *  [bds_hotline]
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form đăng ký.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function bds_shortcode_lead_form( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'  => '',
			'button' => 'Đăng ký ngay',
			'source' => 'Form trang',
			'style'  => 'light',
		),
		$atts,
		'bds_lead_form'
	);

	ob_start();
	get_template_part( 'template-parts/bds/lead-form', null, $atts );
	return ob_get_clean();
}
add_shortcode( 'bds_lead_form', 'bds_shortcode_lead_form' );

/**
 * Chèn một section có sẵn của landing.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function bds_shortcode_section( $atts ) {
	$atts    = shortcode_atts( array( 'name' => '' ), $atts, 'bds_section' );
	$allowed = array( 'hero', 'overview', 'location', 'amenities', 'floorplans', 'gallery', 'video', 'pricing', 'reasons', 'developer', 'faq', 'register' );
	if ( ! in_array( $atts['name'], $allowed, true ) ) {
		return '';
	}
	ob_start();
	get_template_part( 'template-parts/bds/section', $atts['name'] );
	return ob_get_clean();
}
add_shortcode( 'bds_section', 'bds_shortcode_section' );

/**
 * Link hotline.
 *
 * @return string
 */
function bds_shortcode_hotline() {
	$hotline = bds_opt( 'hotline' );
	if ( ! $hotline ) {
		return '';
	}
	return sprintf( '<a class="bds-hotline-link" href="tel:%1$s">%2$s</a>', esc_attr( bds_tel( $hotline ) ), esc_html( $hotline ) );
}
add_shortcode( 'bds_hotline', 'bds_shortcode_hotline' );
