<?php
/**
 * Shortcode dùng trong UX Builder:
 *  [sgd_services number="6" columns="3" featured="1" group="thanh-lap-doanh-nghiep" tag="h3"]
 *  [sgd_groups columns="4" services="4"]        – các nhóm dịch vụ kèm dịch vụ con
 *  [sgd_pricing service="slug-hoac-ID"]         – bảng giá theo gói của 1 dịch vụ
 *  [sgd_steps layout="row"]Bước | Mô tả (mỗi dòng 1 bước)[/sgd_steps]
 *  [sgd_price_table group="slug"]               – bảng phí tóm tắt: dịch vụ | phí | thời gian
 *  [sgd_lead_form title="" button="" source="" service="" perks="1" note="1"]
 *  [sgd_call_buttons]                          – nút Hotline / Zalo nhấp nháy
 *  [sgd_company field="hotline|email|address|company|company_full|tax_code|working_hours"]
 *  [sgd_icon name="building"]
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nút Hotline / Zalo có số và icon rung.
 *
 * @return string
 */
function sgd_call_buttons() {
	$hotline = sgd_opt( 'hotline' );
	$zalo    = sgd_tel( sgd_opt( 'zalo' ) );
	$html    = '';
	if ( $hotline ) {
		$html .= sprintf(
			'<a class="sgd-callbtn sgd-callbtn--phone" href="tel:%1$s"><span class="sgd-ring"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg></span><span class="sgd-callbtn__txt"><small>Hotline tư vấn</small><strong>%2$s</strong></span></a>',
			esc_attr( sgd_tel( $hotline ) ),
			esc_html( $hotline )
		);
	}
	if ( $zalo ) {
		$html .= sprintf(
			'<a class="sgd-callbtn sgd-callbtn--zalo" href="https://zalo.me/%1$s" target="_blank" rel="noopener"><span class="sgd-ring"><b>Zalo</b></span><span class="sgd-callbtn__txt"><small>Chat Zalo</small><strong>%2$s</strong></span></a>',
			esc_attr( $zalo ),
			esc_html( sgd_tel( $hotline ) === $zalo ? $hotline : $zalo )
		);
	}
	return $html;
}

/**
 * Tìm dịch vụ theo ID hoặc slug.
 *
 * @param string|int $ref ID / slug.
 * @return int
 */
function sgd_find_service( $ref ) {
	if ( is_numeric( $ref ) ) {
		return 'dich_vu' === get_post_type( absint( $ref ) ) ? absint( $ref ) : 0;
	}
	$p = $ref ? get_page_by_path( sanitize_title( $ref ), OBJECT, 'dich_vu' ) : null;
	return $p ? (int) $p->ID : 0;
}

/**
 * Lưới dịch vụ.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_services( $atts ) {
	$a    = shortcode_atts(
		array(
			'number'   => 6,
			'columns'  => 3,
			'featured' => '',
			'group'    => '',
			'exclude'  => '',
			'tag'      => 'h3',
		),
		$atts,
		'sgd_services'
	);
	$args = array(
		'post_type'      => 'dich_vu',
		'posts_per_page' => max( 1, min( 24, absint( $a['number'] ) ) ),
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
		'post__not_in'   => array_filter( array_map( 'absint', explode( ',', $a['exclude'] ) ) ),
	);
	if ( $a['featured'] ) {
		$args['meta_query'] = array( array( 'key' => '_sgd_featured', 'value' => '1' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	if ( $a['group'] ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'nhom_dich_vu', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['group'] ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
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
	echo '<div class="sgd-grid sgd-grid--' . absint( $a['columns'] ) . '">';
	while ( $q->have_posts() ) {
		$q->the_post();
		get_template_part( 'template-parts/dichvu/card', null, array( 'tag' => $a['tag'] ) );
	}
	echo '</div>';
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'sgd_services', 'sgd_sc_services' );

/**
 * Các nhóm dịch vụ (Thành lập – Thay đổi – Thuế – Kế toán) kèm vài dịch vụ con.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_groups( $atts ) {
	$a     = shortcode_atts( array( 'columns' => 4, 'services' => 4 ), $atts, 'sgd_groups' );
	$terms = get_terms( array( 'taxonomy' => 'nhom_dich_vu', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'name' ) );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	usort(
		$terms,
		function ( $x, $y ) {
			return (int) get_term_meta( $x->term_id, '_sgd_order', true ) - (int) get_term_meta( $y->term_id, '_sgd_order', true );
		}
	);
	$out = '<div class="sgd-groups sgd-grid sgd-grid--' . absint( $a['columns'] ) . '">';
	foreach ( $terms as $t ) {
		$link  = get_term_link( $t );
		$posts = get_posts(
			array(
				'post_type'      => 'dich_vu',
				'posts_per_page' => absint( $a['services'] ),
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'tax_query'      => array( array( 'taxonomy' => 'nhom_dich_vu', 'terms' => $t->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'no_found_rows'  => true,
			)
		);
		$out .= '<div class="sgd-group">';
		$out .= '<a class="sgd-group__icon" href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true">' . sgd_icon( get_term_meta( $t->term_id, '_sgd_icon', true ) ) . '</a>';
		$out .= '<h3 class="sgd-group__title"><a href="' . esc_url( $link ) . '">' . esc_html( $t->name ) . '</a></h3>';
		if ( $t->description ) {
			$out .= '<p class="sgd-group__desc">' . esc_html( wp_trim_words( wp_strip_all_tags( $t->description ), 22, '…' ) ) . '</p>';
		}
		if ( $posts ) {
			$out .= '<ul class="sgd-group__list">';
			foreach ( $posts as $p ) {
				$out .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></li>';
			}
			$out .= '</ul>';
		}
		$out .= '<a class="sgd-group__more" href="' . esc_url( $link ) . '">Xem chi tiết →</a></div>';
	}
	return $out . '</div>';
}
add_shortcode( 'sgd_groups', 'sgd_sc_groups' );

/**
 * Bảng giá của 1 dịch vụ.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_pricing( $atts ) {
	$a  = shortcode_atts( array( 'service' => '' ), $atts, 'sgd_pricing' );
	$id = $a['service'] ? sgd_find_service( $a['service'] ) : ( is_singular( 'dich_vu' ) ? get_the_ID() : 0 );
	if ( ! $id ) {
		return '';
	}
	ob_start();
	get_template_part( 'template-parts/dichvu/packages', null, array( 'id' => $id ) );
	return ob_get_clean();
}
add_shortcode( 'sgd_pricing', 'sgd_sc_pricing' );

/**
 * Các bước quy trình. Nội dung: mỗi dòng "Bước | Mô tả".
 *
 * @param array  $atts    Thuộc tính.
 * @param string $content Nội dung.
 * @return string
 */
function sgd_sc_steps( $atts, $content = '' ) {
	$a    = shortcode_atts( array( 'layout' => '' ), $atts, 'sgd_steps' );
	$text = str_ireplace( array( '<br>', '<br/>', '<br />', '</p>' ), "\n", (string) $content );
	ob_start();
	get_template_part( 'template-parts/dichvu/process', null, array( 'steps' => sgd_lines( wp_strip_all_tags( $text ) ), 'row' => 'row' === $a['layout'] ) );
	return ob_get_clean();
}
add_shortcode( 'sgd_steps', 'sgd_sc_steps' );

/**
 * Form tư vấn.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_lead_form( $atts ) {
	$a = shortcode_atts(
		array(
			'title'   => sgd_opt( 'form_title' ),
			'button'  => 'Gửi yêu cầu tư vấn',
			'source'  => 'Form trang',
			'service' => '',
			'perks'   => '1',
			'note'    => '1',
		),
		$atts,
		'sgd_lead_form'
	);
	$a['perks']   = '1' === (string) $a['perks'];
	$a['note']    = '1' === (string) $a['note'];
	$a['service'] = $a['service'] ? sgd_find_service( $a['service'] ) : 0;
	ob_start();
	echo '<div class="sgd-card-form">';
	get_template_part( 'template-parts/dichvu/lead-form', null, $a );
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'sgd_lead_form', 'sgd_sc_lead_form' );

/**
 * Nút Hotline / Zalo.
 *
 * @return string
 */
function sgd_sc_call_buttons() {
	return '<div class="sgd-callbtns">' . sgd_call_buttons() . '</div>';
}
add_shortcode( 'sgd_call_buttons', 'sgd_sc_call_buttons' );

/**
 * Thông tin công ty (dùng trong footer / trang liên hệ).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_company( $atts ) {
	$a   = shortcode_atts( array( 'field' => 'company_full' ), $atts, 'sgd_company' );
	$key = sanitize_key( $a['field'] );
	if ( ! in_array( $key, array( 'company', 'company_full', 'tagline', 'tax_code', 'address', 'hotline', 'zalo', 'email', 'working_hours' ), true ) ) {
		return '';
	}
	$val = sgd_opt( $key );
	if ( 'hotline' === $key ) {
		return '<a href="tel:' . esc_attr( sgd_tel( $val ) ) . '">' . esc_html( $val ) . '</a>';
	}
	if ( 'email' === $key ) {
		return '<a href="mailto:' . esc_attr( $val ) . '">' . esc_html( $val ) . '</a>';
	}
	return esc_html( $val );
}
add_shortcode( 'sgd_company', 'sgd_sc_company' );

/**
 * Icon SVG.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_icon( $atts ) {
	$a = shortcode_atts( array( 'name' => 'doc' ), $atts, 'sgd_icon' );
	return '<span class="sgd-iconbox">' . sgd_icon( sanitize_key( $a['name'] ) ) . '</span>';
}
add_shortcode( 'sgd_icon', 'sgd_sc_icon' );

/**
 * Bảng phí tóm tắt các dịch vụ (theo nhóm).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_price_table( $atts ) {
	$a    = shortcode_atts( array( 'group' => '' ), $atts, 'sgd_price_table' );
	$args = array(
		'post_type'      => 'dich_vu',
		'posts_per_page' => 50,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
	);
	if ( $a['group'] ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'nhom_dich_vu', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['group'] ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
	$posts = get_posts( $args );
	if ( ! $posts ) {
		return '';
	}
	$out = '<div class="sgd-ptable"><table><thead><tr><th scope="col">Dịch vụ</th><th scope="col">Phí dịch vụ</th><th scope="col">Thời gian</th><th scope="col"><span class="screen-reader-text">Chi tiết</span></th></tr></thead><tbody>';
	foreach ( $posts as $p ) {
		$price = sgd_meta( 'price', $p->ID );
		$out  .= '<tr><td data-label="Dịch vụ"><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></td>'
			. '<td data-label="Phí dịch vụ" class="sgd-ptable__price">' . esc_html( $price ? $price : 'Liên hệ' ) . '</td>'
			. '<td data-label="Thời gian">' . esc_html( sgd_meta( 'duration', $p->ID ) ) . '</td>'
			. '<td><a class="sgd-ptable__more" href="' . esc_url( get_permalink( $p ) ) . '#bang-gia">Xem gói</a></td></tr>';
	}
	return $out . '</tbody></table></div>';
}
add_shortcode( 'sgd_price_table', 'sgd_sc_price_table' );
