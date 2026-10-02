<?php
/**
 * Khối dựng trang chủ (bản 0.4 – bố cục gọn, khoa học):
 *  [sgd_groupnav]                    – "Bạn cần hỗ trợ việc gì?": ô nhóm dịch vụ + giá từ
 *  [sgd_catalog]                     – danh mục dịch vụ & bảng giá theo tab nhóm
 *  [sgd_pricetabs items="slug:show:Nhãn,…"] – bảng giá trọn gói theo tab
 *  [sgd_cta_strip]                   – dải kêu gọi cuối trang (hotline, Zalo, nút)
 *
 * Tab: nội dung mọi tab đều có sẵn trong HTML (Google đọc được), tab chưa chọn ẩn bằng
 * thuộc tính hidden ngay từ máy chủ nên không nhảy bố cục khi tải trang.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nhóm dịch vụ đã sắp xếp theo "Thứ tự".
 *
 * @return WP_Term[]
 */
function sgd_sorted_groups() {
	$terms = get_terms( array( 'taxonomy' => 'nhom_dich_vu', 'hide_empty' => true, 'parent' => 0 ) );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}
	usort(
		$terms,
		function ( $x, $y ) {
			return (int) get_term_meta( $x->term_id, '_sgd_order', true ) - (int) get_term_meta( $y->term_id, '_sgd_order', true );
		}
	);
	return $terms;
}

/**
 * Dịch vụ của 1 nhóm.
 *
 * @param WP_Term $t Nhóm.
 * @return WP_Post[]
 */
function sgd_group_services( $t ) {
	return get_posts(
		array(
			'post_type'      => 'dich_vu',
			'posts_per_page' => 30,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'tax_query'      => array( array( 'taxonomy' => 'nhom_dich_vu', 'terms' => $t->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'no_found_rows'  => true,
		)
	);
}

/**
 * Giá thấp nhất trong nhóm, dạng "Từ 250.000đ" (rỗng nếu không đọc được giá).
 *
 * @param WP_Post[] $posts Dịch vụ.
 * @return string
 */
function sgd_min_price_label( $posts ) {
	$min = 0;
	foreach ( $posts as $p ) {
		$n = sgd_price_number( sgd_meta( 'price', $p->ID ) );
		if ( $n && ( ! $min || $n < $min ) ) {
			$min = $n;
		}
	}
	return $min ? 'Từ ' . number_format( $min, 0, ',', '.' ) . 'đ' : '';
}

/**
 * Bộ khung tab dùng chung.
 *
 * @param string $id     Tiền tố id.
 * @param array  $tabs   [[nhãn HTML an toàn, nội dung HTML], ...].
 * @param string $layout row|side.
 * @return string
 */
function sgd_tabset( $id, $tabs, $layout = 'row' ) {
	$nav    = '';
	$panels = '';
	foreach ( $tabs as $i => $t ) {
		$tid     = $id . '-tab-' . $i;
		$pid     = $id . '-panel-' . $i;
		$nav    .= '<button type="button" role="tab" id="' . esc_attr( $tid ) . '" aria-controls="' . esc_attr( $pid ) . '" aria-selected="' . ( 0 === $i ? 'true' : 'false' ) . '"' . ( 0 === $i ? '' : ' tabindex="-1"' ) . '>' . $t[0] . '</button>';
		$panels .= '<div class="sgd-tabset__panel" role="tabpanel" id="' . esc_attr( $pid ) . '" aria-labelledby="' . esc_attr( $tid ) . '"' . ( 0 === $i ? '' : ' hidden' ) . '>' . $t[1] . '</div>';
	}
	return '<div class="sgd-tabset sgd-tabset--' . esc_attr( $layout ) . '"><div class="sgd-tabset__nav" role="tablist">' . $nav . '</div><div class="sgd-tabset__panels">' . $panels . '</div></div>';
}

/**
 * "Bạn cần hỗ trợ việc gì?" – ô nhóm dịch vụ.
 *
 * @return string
 */
function sgd_sc_groupnav() {
	$out = '<div class="sgd-gnav">';
	foreach ( sgd_sorted_groups() as $i => $t ) {
		$posts = sgd_group_services( $t );
		$price = sgd_min_price_label( $posts );
		$out  .= '<a class="sgd-gnav__item sgd-gnav__item--' . ( $i % 2 ? 'red' : 'blue' ) . '" href="' . esc_url( get_term_link( $t ) ) . '">'
			. '<span class="sgd-gnav__icon">' . sgd_icon( get_term_meta( $t->term_id, '_sgd_icon', true ) ) . '</span>'
			. '<span class="sgd-gnav__name">' . esc_html( $t->name ) . '</span>'
			. '<span class="sgd-gnav__meta">' . count( $posts ) . ' dịch vụ' . ( $price ? ' · <b>' . esc_html( $price ) . '</b>' : '' ) . '</span>'
			. '</a>';
	}
	return $out . '</div>';
}
add_shortcode( 'sgd_groupnav', 'sgd_sc_groupnav' );

/**
 * Danh mục dịch vụ & bảng giá theo tab nhóm.
 *
 * @return string
 */
function sgd_sc_catalog() {
	$tabs = array();
	foreach ( sgd_sorted_groups() as $t ) {
		$posts = sgd_group_services( $t );
		if ( ! $posts ) {
			continue;
		}
		$label = '<span class="sgd-ico-wrap">' . sgd_icon( get_term_meta( $t->term_id, '_sgd_icon', true ) ) . '</span><span>' . esc_html( $t->name ) . '<small>' . count( $posts ) . ' dịch vụ</small></span>';
		$body  = '<div class="sgd-cat__head"><h3 class="sgd-cat__title">' . esc_html( $t->name ) . '</h3>';
		if ( $t->description ) {
			$body .= '<p class="sgd-cat__desc">' . esc_html( sgd_clip( wp_strip_all_tags( $t->description ), 190 ) ) . '</p>';
		}
		$body .= '</div><ul class="sgd-cat__list">';
		foreach ( $posts as $p ) {
			$price = sgd_meta( 'price', $p->ID );
			$time  = sgd_meta( 'duration', $p->ID );
			$body .= '<li class="sgd-cat__row"><a href="' . esc_url( get_permalink( $p ) ) . '">'
				. '<span class="sgd-cat__name"><strong>' . esc_html( get_the_title( $p ) ) . '</strong>'
				. ( sgd_meta( 'subtitle', $p->ID ) ? '<small>' . esc_html( sgd_clip( sgd_meta( 'subtitle', $p->ID ), 90 ) ) . '</small>' : '' ) . '</span>'
				. '<span class="sgd-cat__time">' . ( $time ? sgd_icon( 'clock' ) . ' ' . esc_html( $time ) : '' ) . '</span>'
				. '<span class="sgd-cat__price">' . esc_html( $price ? $price : 'Liên hệ' ) . '</span>'
				. '<span class="sgd-cat__go" aria-hidden="true">›</span></a></li>';
		}
		$body  .= '</ul><a class="sgd-cat__more" href="' . esc_url( get_term_link( $t ) ) . '">Xem tất cả dịch vụ ' . esc_html( mb_strtolower( $t->name ) ) . ' →</a>';
		$tabs[] = array( $label, $body );
	}
	return $tabs ? sgd_tabset( 'sgd-cat', $tabs, 'side' ) : '';
}
add_shortcode( 'sgd_catalog', 'sgd_sc_catalog' );

/**
 * Bảng giá trọn gói theo tab: items="thanh-lap-cong-ty-tnhh:packages:Thành lập công ty,ke-toan-tron-goi:table:Kế toán trọn gói".
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_pricetabs( $atts ) {
	$a    = shortcode_atts( array( 'items' => '' ), $atts, 'sgd_pricetabs' );
	$tabs = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', $a['items'] ) ) ) as $item ) {
		$parts = array_map( 'trim', explode( ':', $item ) );
		$id    = sgd_find_service( $parts[0] );
		if ( ! $id ) {
			continue;
		}
		$html = sgd_sc_pricing( array( 'service' => $id, 'show' => isset( $parts[1] ) ? $parts[1] : 'all' ) );
		if ( ! $html ) {
			continue;
		}
		$html  .= '<p class="sgd-pricetabs__more"><a href="' . esc_url( get_permalink( $id ) ) . '">Xem chi tiết: ' . esc_html( get_the_title( $id ) ) . ' →</a></p>';
		$tabs[] = array( esc_html( isset( $parts[2] ) ? $parts[2] : get_the_title( $id ) ), $html );
	}
	return $tabs ? sgd_tabset( 'sgd-price', $tabs, 'row' ) : '';
}
add_shortcode( 'sgd_pricetabs', 'sgd_sc_pricetabs' );

/**
 * Dải kêu gọi cuối trang.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_cta_strip( $atts ) {
	$a    = shortcode_atts( array( 'title' => 'Cần tư vấn ngay? Gọi cho chúng tôi', 'sub' => 'Tư vấn miễn phí – báo giá trọn gói trong 15 phút (giờ hành chính).' ), $atts, 'sgd_cta_strip' );
	$hot  = sgd_opt( 'hotline' );
	$zalo = sgd_tel( sgd_opt( 'zalo' ) );
	return '<div class="sgd-ctastrip"><div class="sgd-ctastrip__text"><p class="sgd-ctastrip__title">' . esc_html( $a['title'] ) . '</p><p>' . esc_html( $a['sub'] ) . '</p></div>'
		. '<div class="sgd-ctastrip__btns">'
		. '<a class="sgd-ctastrip__call" href="tel:' . esc_attr( sgd_tel( $hot ) ) . '">' . sgd_icon( 'phone' ) . '<span><small>Hotline</small>' . esc_html( $hot ) . '</span></a>'
		. ( $zalo ? '<a class="sgd-ctastrip__zalo" href="https://zalo.me/' . esc_attr( $zalo ) . '" target="_blank" rel="noopener"><b>Zalo</b><span><small>Chat Zalo</small>' . esc_html( $hot ) . '</span></a>' : '' )
		. '<a class="button sgd-btn" href="#dang-ky">Nhận báo giá</a></div></div>';
}
add_shortcode( 'sgd_cta_strip', 'sgd_sc_cta_strip' );
