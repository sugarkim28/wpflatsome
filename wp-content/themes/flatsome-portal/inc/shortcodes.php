<?php
/**
 * Shortcode dùng trong UX Builder:
 *  [sgp_projects number="6" columns="3" featured="1" khu_vuc="" chu_dau_tu="" loai_hinh="" trang_thai="" tag="h3"]
 *  [sgp_search]                         – bộ lọc tìm dự án (gửi tới /du-an/)
 *  [sgp_terms taxonomy="khu_vuc" columns="4" style="cards|logos"]
 *  [sgp_lead_form title="" button="" source="" project=""]
 *  [sgp_call_buttons]                   – nút Hotline / Zalo nhấp nháy
 *  [sgp_company field="hotline|email|address|company_full|tax_code|working_hours"]
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nút Hotline / Zalo có số và icon rung.
 *
 * @param int $project ID dự án (dùng hotline riêng nếu có).
 * @return string
 */
function sgp_call_buttons( $project = 0 ) {
	$hotline = sgp_project_hotline( $project );
	$zalo    = sgp_tel( sgp_opt( 'zalo' ) );
	$html    = '';
	if ( $hotline ) {
		$html .= sprintf(
			'<a class="sgp-callbtn sgp-callbtn--phone" href="tel:%1$s"><span class="sgp-ring"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg></span><span class="sgp-callbtn__txt"><small>Hotline 24/7</small><strong>%2$s</strong></span></a>',
			esc_attr( sgp_tel( $hotline ) ),
			esc_html( $hotline )
		);
	}
	if ( $zalo ) {
		$html .= sprintf(
			'<a class="sgp-callbtn sgp-callbtn--zalo" href="https://zalo.me/%1$s" target="_blank" rel="noopener"><span class="sgp-ring"><b>Zalo</b></span><span class="sgp-callbtn__txt"><small>Chat Zalo</small><strong>%2$s</strong></span></a>',
			esc_attr( $zalo ),
			esc_html( sgp_tel( sgp_opt( 'hotline' ) ) === $zalo ? sgp_opt( 'hotline' ) : $zalo )
		);
	}
	return $html;
}

/**
 * Lưới dự án.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgp_sc_projects( $atts ) {
	$a    = shortcode_atts(
		array(
			'number'     => 6,
			'columns'    => 3,
			'featured'   => '',
			'khu_vuc'    => '',
			'chu_dau_tu' => '',
			'loai_hinh'  => '',
			'trang_thai' => '',
			'exclude'    => '',
			'tag'        => 'h3',
		),
		$atts,
		'sgp_projects'
	);
	$args = array(
		'post_type'      => 'du_an',
		'posts_per_page' => max( 1, min( 24, absint( $a['number'] ) ) ),
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
		'post__not_in'   => array_filter( array_map( 'absint', explode( ',', $a['exclude'] ) ) ),
	);
	if ( $a['featured'] ) {
		$args['meta_query'] = array( array( 'key' => '_sgp_featured', 'value' => '1' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	$tq = array();
	foreach ( array( 'khu_vuc', 'chu_dau_tu', 'loai_hinh', 'trang_thai' ) as $tax ) {
		if ( $a[ $tax ] ) {
			$tq[] = array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a[ $tax ] ) ) );
		}
	}
	if ( $tq ) {
		$args['tax_query'] = $tq; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() && $a['featured'] ) {
		unset( $args['meta_query'] );
		$q = new WP_Query( $args );
	}
	if ( ! $q->have_posts() ) {
		return '';
	}
	ob_start();
	echo '<div class="sgp-grid sgp-grid--' . absint( $a['columns'] ) . '">';
	while ( $q->have_posts() ) {
		$q->the_post();
		get_template_part( 'template-parts/portal/card', null, array( 'tag' => $a['tag'] ) );
	}
	echo '</div>';
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'sgp_projects', 'sgp_sc_projects' );

/**
 * Bộ lọc tìm dự án (dùng ở trang chủ).
 *
 * @return string
 */
function sgp_sc_search() {
	ob_start();
	get_template_part( 'template-parts/portal/filter', null, array( 'action' => get_post_type_archive_link( 'du_an' ), 'compact' => true ) );
	return ob_get_clean();
}
add_shortcode( 'sgp_search', 'sgp_sc_search' );

/**
 * Danh sách khu vực / chủ đầu tư / loại hình.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgp_sc_terms( $atts ) {
	$a = shortcode_atts( array( 'taxonomy' => 'khu_vuc', 'columns' => 4, 'style' => 'cards', 'number' => 12 ), $atts, 'sgp_terms' );
	if ( ! in_array( $a['taxonomy'], array( 'khu_vuc', 'chu_dau_tu', 'loai_hinh' ), true ) ) {
		return '';
	}
	$terms = get_terms( array( 'taxonomy' => $a['taxonomy'], 'hide_empty' => true, 'number' => absint( $a['number'] ), 'parent' => 'khu_vuc' === $a['taxonomy'] ? 0 : '' ) );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	$out = '<div class="sgp-terms sgp-terms--' . esc_attr( $a['style'] ) . ' sgp-grid sgp-grid--' . absint( $a['columns'] ) . '">';
	foreach ( $terms as $t ) {
		$img  = absint( get_term_meta( $t->term_id, '_sgp_image', true ) );
		$size = 'logos' === $a['style'] ? 'medium' : 'medium_large';
		$out .= '<a class="sgp-term" href="' . esc_url( get_term_link( $t ) ) . '">';
		$out .= $img ? wp_get_attachment_image( $img, $size, false, array( 'alt' => esc_attr( $t->name ), 'loading' => 'lazy' ) ) : '<span class="sgp-term__noimg"></span>';
		$out .= '<span class="sgp-term__name">' . esc_html( $t->name ) . '</span>';
		$out .= '<span class="sgp-term__count">' . esc_html( $t->count ) . ' dự án</span></a>';
	}
	return $out . '</div>';
}
add_shortcode( 'sgp_terms', 'sgp_sc_terms' );

/**
 * Form đăng ký.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgp_sc_lead_form( $atts ) {
	$a = shortcode_atts( array( 'title' => sgp_opt( 'form_title' ), 'button' => 'Nhận thông tin ngay', 'source' => 'Form trang', 'project' => 0, 'perks' => '1' ), $atts, 'sgp_lead_form' );
	$a['perks']   = '1' === (string) $a['perks'];
	$a['project'] = absint( $a['project'] );
	ob_start();
	echo '<div class="sgp-card-form">';
	get_template_part( 'template-parts/portal/lead-form', null, $a );
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'sgp_lead_form', 'sgp_sc_lead_form' );

/**
 * Nút Hotline / Zalo.
 *
 * @return string
 */
function sgp_sc_call_buttons() {
	return '<div class="sgp-callbtns">' . sgp_call_buttons() . '</div>';
}
add_shortcode( 'sgp_call_buttons', 'sgp_sc_call_buttons' );

/**
 * Thông tin công ty (dùng trong footer / trang liên hệ).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgp_sc_company( $atts ) {
	$a   = shortcode_atts( array( 'field' => 'company_full' ), $atts, 'sgp_company' );
	$key = sanitize_key( $a['field'] );
	if ( ! array_key_exists( $key, sgp_defaults() ) ) {
		return '';
	}
	$val = sgp_opt( $key );
	if ( 'hotline' === $key ) {
		return '<a href="tel:' . esc_attr( sgp_tel( $val ) ) . '">' . esc_html( $val ) . '</a>';
	}
	if ( 'email' === $key ) {
		return '<a href="mailto:' . esc_attr( $val ) . '">' . esc_html( $val ) . '</a>';
	}
	return esc_html( $val );
}
add_shortcode( 'sgp_company', 'sgp_sc_company' );
