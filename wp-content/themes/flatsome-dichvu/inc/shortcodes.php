<?php
/**
 * Shortcode dùng trong UX Builder:
 *  [sgd_featured ids="slug1,slug2,slug3,slug4"]  – lưới banner nổi bật (1 lớn + 3), trống = 4 dịch vụ nổi bật
 *  [sgd_htab text="" link=""]                   – tiêu đề khối dạng thẻ
 *  [sgd_group_block group="slug" number="5"]    – khối chuyên mục: 1 dịch vụ lớn + danh sách
 *  [sgd_posts number="5" category="" style="magazine|grid|links|cards" columns="4"] – bài viết
 *  [sgd_hero title="" highlight="" sub=""]          – banner đầu trang chủ: H1 duy nhất + form + dịch vụ phổ biến
 *  [sgd_faq]Câu hỏi | Trả lời (mỗi dòng)[/sgd_faq] – hỏi đáp + dữ liệu FAQPage cho Google
 *  [sgd_title text="" sub=""]                    – tiêu đề giữa, kẻ ngang hai bên
 *  [sgd_topbar side="left|right"]                – nội dung thanh trên cùng (Flatsome Top Bar)
 *  [sgd_groups style="card|simple|hub"]          – hub: thẻ nhóm + danh sách dịch vụ kèm giá (trang chủ)
 *  [sgd_services number="6" columns="3" featured="1" group="thanh-lap-doanh-nghiep" layout="grid|list|mini" tag="h3"]
 *  [sgd_hotlines style="pills|header|compact"]   – hotline theo khu vực / 1 số gọn
 *  [sgd_branches]                                – văn phòng / chi nhánh
 *  [sgd_sidebar form="1"]                        – cột phải
 *  [sgd_groups columns="4" services="4"]        – các nhóm dịch vụ kèm dịch vụ con
 *  [sgd_pricing service="slug-hoac-ID" show="all|table|packages"] – chi phí, bảng giá, các gói của 1 dịch vụ
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
 * Lấy danh sách dịch vụ theo tham số shortcode (featured rỗng thì lấy dịch vụ thường).
 *
 * @param array $a number, featured, group, exclude.
 * @return WP_Post[]
 */
function sgd_query_services( $a ) {
	$args = array(
		'post_type'      => 'dich_vu',
		'posts_per_page' => max( 1, min( 24, absint( $a['number'] ) ) ),
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
		'post__not_in'   => array_filter( array_map( 'absint', explode( ',', (string) $a['exclude'] ) ) ),
	);
	if ( ! empty( $a['group'] ) ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'nhom_dich_vu', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['group'] ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
	if ( ! empty( $a['featured'] ) ) {
		$f = $args;
		$f['meta_query'] = array( array( 'key' => '_sgd_featured', 'value' => '1' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$posts = get_posts( $f );
		if ( $posts ) {
			return $posts;
		}
	}
	return get_posts( $args );
}

/**
 * In danh sách thẻ dịch vụ.
 *
 * @param WP_Post[] $posts  Dịch vụ.
 * @param string    $layout grid|list|mini.
 * @param string    $tag    Thẻ tiêu đề.
 * @return string
 */
function sgd_render_cards( $posts, $layout = 'grid', $tag = 'h3' ) {
	global $post;
	ob_start();
	foreach ( $posts as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
		get_template_part( 'template-parts/dichvu/card', null, array( 'tag' => $tag, 'layout' => $layout ) );
	endforeach;
	wp_reset_postdata();
	return ob_get_clean();
}

/**
 * Lưới / danh sách dịch vụ.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_services( $atts ) {
	$a     = shortcode_atts(
		array(
			'number'   => 6,
			'columns'  => 3,
			'featured' => '',
			'group'    => '',
			'exclude'  => '',
			'layout'   => 'grid',
			'tag'      => 'h3',
		),
		$atts,
		'sgd_services'
	);
	$posts = sgd_query_services( $a );
	if ( ! $posts ) {
		return '';
	}
	$cls = in_array( $a['layout'], array( 'grid', 'feature' ), true ) ? 'sgd-grid sgd-grid--' . absint( $a['columns'] ) : 'sgd-stack';
	return '<div class="' . esc_attr( $cls ) . '">' . sgd_render_cards( $posts, $a['layout'], $a['tag'] ) . '</div>';
}
add_shortcode( 'sgd_services', 'sgd_sc_services' );

/**
 * Lưới banner nổi bật kiểu tạp chí: 1 ô lớn bên trái, 1 ô ngang + 2 ô nhỏ bên phải.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_featured( $atts ) {
	$a     = shortcode_atts( array( 'ids' => '', 'group' => '' ), $atts, 'sgd_featured' );
	$posts = array();
	if ( $a['ids'] ) {
		foreach ( explode( ',', $a['ids'] ) as $ref ) {
			$id = sgd_find_service( trim( $ref ) );
			if ( $id ) {
				$posts[] = get_post( $id );
			}
		}
	} else {
		$posts = sgd_query_services( array( 'number' => 4, 'featured' => '1', 'group' => $a['group'], 'exclude' => '' ) );
	}
	$posts = array_slice( $posts, 0, 4 );
	if ( ! $posts ) {
		return '';
	}
	$sizes = array( 'lg', 'md', 'sm', 'sm' );
	ob_start();
	echo '<div class="sgd-mosaic sgd-mosaic--' . count( $posts ) . '">';
	foreach ( $posts as $i => $p ) {
		get_template_part( 'template-parts/dichvu/banner', null, array( 'id' => $p->ID, 'size' => $sizes[ $i ], 'caption' => true, 'tag' => 'h2' ) );
	}
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'sgd_featured', 'sgd_sc_featured' );

/**
 * Tiêu đề khối dạng thẻ (nền xanh nhạt + gạch chân).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_htab( $atts ) {
	$a    = shortcode_atts( array( 'text' => '', 'link' => '', 'tag' => 'h2', 'more' => 'Xem tất cả' ), $atts, 'sgd_htab' );
	$tag  = in_array( $a['tag'], array( 'h2', 'h3', 'p' ), true ) ? $a['tag'] : 'h2';
	$text = esc_html( $a['text'] );
	$out  = '<' . $tag . ' class="sgd-htab"><span>' . ( $a['link'] ? '<a href="' . esc_url( $a['link'] ) . '">' . $text . '</a>' : $text ) . '</span>';
	if ( $a['link'] && $a['more'] ) {
		$out .= '<a class="sgd-htab__more" href="' . esc_url( $a['link'] ) . '">' . esc_html( $a['more'] ) . ' »</a>';
	}
	return $out . '</' . $tag . '>';
}
add_shortcode( 'sgd_htab', 'sgd_sc_htab' );

/**
 * Khối chuyên mục kiểu tạp chí: tiêu đề thẻ + 1 dịch vụ lớn bên trái + danh sách bên phải.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_group_block( $atts ) {
	$a    = shortcode_atts( array( 'group' => '', 'number' => 5, 'title' => '' ), $atts, 'sgd_group_block' );
	$term = $a['group'] ? get_term_by( 'slug', $a['group'], 'nhom_dich_vu' ) : null;
	if ( ! $term ) {
		return '';
	}
	$posts = sgd_query_services( array( 'number' => $a['number'], 'group' => $a['group'], 'featured' => '', 'exclude' => '' ) );
	if ( ! $posts ) {
		return '';
	}
	$out  = sgd_sc_htab( array( 'text' => $a['title'] ? $a['title'] : $term->name, 'link' => get_term_link( $term ) ) );
	$out .= '<div class="sgd-gblock"><div class="sgd-gblock__main">' . sgd_render_cards( array_slice( $posts, 0, 1 ), 'grid', 'h3' ) . '</div>';
	$out .= '<div class="sgd-gblock__list sgd-stack">' . sgd_render_cards( array_slice( $posts, 1 ), 'list', 'h3' ) . '</div></div>';
	return $out;
}
add_shortcode( 'sgd_group_block', 'sgd_sc_group_block' );

/**
 * Bài viết kiểu tạp chí: 1 bài lớn + danh sách có ảnh nhỏ.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_posts( $atts ) {
	$a     = shortcode_atts( array( 'number' => 5, 'category' => '', 'style' => 'magazine', 'columns' => 4 ), $atts, 'sgd_posts' );
	$posts = get_posts(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => max( 1, min( 12, absint( $a['number'] ) ) ),
			'category_name'       => sanitize_title( $a['category'] ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( ! $posts ) {
		return '';
	}
	if ( 'links' === $a['style'] ) {
		$out = '<ul class="sgd-plinks">';
		foreach ( $posts as $p ) {
			$out .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></li>';
		}
		return $out . '</ul>';
	}
	if ( 'cards' === $a['style'] ) {
		global $post;
		ob_start();
		echo '<div class="sgd-blog__grid sgd-blog__grid--' . ( 4 === count( $posts ) ? 4 : 3 ) . '">';
		foreach ( $posts as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
			get_template_part( 'template-parts/dichvu/post-card', null, array( 'tag' => 'h3' ) );
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}
		if ( 'grid' === $a['style'] ) {
		$out = '<div class="sgd-pgrid sgd-grid sgd-grid--' . absint( $a['columns'] ) . '">';
		foreach ( $posts as $p ) {
			$link  = esc_url( get_permalink( $p ) );
			$thumb = has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'lazy' ) ) : '<span class="sgd-post__noimg">' . sgd_icon( 'doc' ) . '</span>';
			$out  .= '<article class="sgd-pcard"><a class="sgd-pcard__img" href="' . $link . '" tabindex="-1" aria-hidden="true">' . $thumb . '</a>'
				. '<h3 class="sgd-pcard__title"><a href="' . $link . '">' . esc_html( get_the_title( $p ) ) . '</a></h3></article>';
		}
		return $out . '</div>';
	}
	$out = '<div class="sgd-gblock sgd-posts">';
	foreach ( $posts as $i => $p ) {
		if ( 1 === $i ) {
			$out .= '<div class="sgd-gblock__list sgd-stack">';
		}
		$thumb = has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 0 === $i ? 'medium_large' : 'thumbnail', array( 'loading' => 'lazy' ) ) : '<span class="sgd-post__noimg">' . sgd_icon( 'doc' ) . '</span>';
		$link  = esc_url( get_permalink( $p ) );
		$out  .= '<article class="sgd-post' . ( 0 === $i ? ' sgd-post--lead sgd-gblock__main' : '' ) . '">'
			. '<a class="sgd-post__img" href="' . $link . '" tabindex="-1" aria-hidden="true">' . $thumb . '</a>'
			. '<div><h3 class="sgd-post__title"><a href="' . $link . '">' . esc_html( get_the_title( $p ) ) . '</a></h3>'
			. '<p class="sgd-post__date">' . esc_html( get_the_date( 'd/m/Y', $p ) ) . '</p>'
			. ( 0 === $i ? '<p class="sgd-post__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $p ), 30, '…' ) ) . '</p>' : '' )
			. '</div></article>';
	}
	if ( count( $posts ) > 1 ) {
		$out .= '</div>';
	}
	return $out . '</div>';
}
add_shortcode( 'sgd_posts', 'sgd_sc_posts' );

/**
 * Hotline theo khu vực. style="pills" (giữa bài) | "header" (đầu trang).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_hotlines( $atts ) {
	$a = shortcode_atts( array( 'style' => 'pills', 'title' => '' ), $atts, 'sgd_hotlines' );
	if ( 'compact' === $a['style'] ) {
		$h = sgd_opt( 'hotline' );
		return '<a class="sgd-hcall" href="tel:' . esc_attr( sgd_tel( $h ) ) . '">' . sgd_icon( 'phone' ) . '<span><small>Hotline tư vấn</small><strong>' . esc_html( $h ) . '</strong></span></a>';
	}
	if ( 'header' === $a['style'] ) {
		$out = '<div class="sgd-hhot">';
		foreach ( sgd_hotlines() as $l ) {
			$out .= '<a href="tel:' . esc_attr( sgd_tel( $l[1] ) ) . '"><small>' . esc_html( $l[0] ) . '</small><strong>' . esc_html( $l[1] ) . '</strong></a>';
		}
		return $out . '</div>';
	}
	ob_start();
	get_template_part( 'template-parts/dichvu/call-now', null, $a['title'] ? array( 'title' => $a['title'] ) : array() );
	return ob_get_clean();
}
add_shortcode( 'sgd_hotlines', 'sgd_sc_hotlines' );

/**
 * Văn phòng / chi nhánh (footer).
 *
 * @return string
 */
function sgd_sc_branches() {
	$out = '<div class="sgd-branches">';
	foreach ( sgd_branches() as $b ) {
		$out .= '<div class="sgd-branch"><p class="sgd-branch__name">' . esc_html( $b[0] ) . '</p>';
		if ( $b[1] ) {
			$out .= '<p class="sgd-branch__row">' . sgd_icon( 'pin' ) . '<span>' . esc_html( $b[1] ) . '</span></p>';
		}
		if ( $b[3] ) {
			$out .= '<p class="sgd-branch__row">' . sgd_icon( 'mail' ) . '<a href="mailto:' . esc_attr( $b[3] ) . '">' . esc_html( $b[3] ) . '</a></p>';
		}
		foreach ( array_filter( array_map( 'trim', explode( ',', $b[2] ) ) ) as $tel ) {
			$out .= '<p class="sgd-branch__row sgd-branch__tel">' . sgd_icon( 'phone' ) . '<a href="tel:' . esc_attr( sgd_tel( $tel ) ) . '">' . esc_html( $tel ) . '</a></p>';
		}
		$out .= '</div>';
	}
	return $out . '</div>';
}
add_shortcode( 'sgd_branches', 'sgd_sc_branches' );

/**
 * Cột phải (form + dịch vụ nổi bật + bài viết) để đặt trong UX Builder.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_sidebar( $atts ) {
	$a = shortcode_atts( array( 'form' => '1' ), $atts, 'sgd_sidebar' );
	ob_start();
	get_template_part( 'template-parts/dichvu/sidebar', null, array( 'form' => '1' === (string) $a['form'] ) );
	return ob_get_clean();
}
add_shortcode( 'sgd_sidebar', 'sgd_sc_sidebar' );

/**
 * Các nhóm dịch vụ (Thành lập – Thay đổi – Thuế – Kế toán) kèm vài dịch vụ con.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_groups( $atts ) {
	$a     = shortcode_atts( array( 'columns' => 4, 'services' => 4, 'style' => 'card' ), $atts, 'sgd_groups' );
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
	if ( 'hub' === $a['style'] ) {
		return sgd_groups_hub( $terms, absint( $a['services'] ) );
	}
	$out = '<div class="sgd-groups sgd-groups--' . esc_attr( sanitize_key( $a['style'] ) ) . ' sgd-grid sgd-grid--' . absint( $a['columns'] ) . '">';
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
 * Danh mục dịch vụ dạng thẻ (style="hub"): mỗi nhóm 1 thẻ – icon, tên nhóm, số dịch vụ,
 * danh sách dịch vụ kèm giá, liên kết xem cả nhóm. Lưới 3 cột, đọc theo hàng ngang.
 *
 * @param WP_Term[] $terms Nhóm dịch vụ (đã sắp xếp).
 * @param int       $limit Số dịch vụ tối đa mỗi nhóm (0 = tất cả).
 * @return string
 */
function sgd_groups_hub( $terms, $limit = 0 ) {
	$out = '<div class="sgd-hub">';
	foreach ( $terms as $i => $t ) {
		$posts = get_posts(
			array(
				'post_type'      => 'dich_vu',
				'posts_per_page' => $limit ? $limit : 30,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'tax_query'      => array( array( 'taxonomy' => 'nhom_dich_vu', 'terms' => $t->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'no_found_rows'  => true,
			)
		);
		if ( ! $posts ) {
			continue;
		}
		$link = get_term_link( $t );
		$out .= '<section class="sgd-hub__card sgd-hub__card--' . ( $i % 2 ? 'red' : 'blue' ) . '">';
		$out .= '<header class="sgd-hub__head"><span class="sgd-hub__icon">' . sgd_icon( get_term_meta( $t->term_id, '_sgd_icon', true ) ) . '</span>';
		$out .= '<div><h3 class="sgd-hub__title"><a href="' . esc_url( $link ) . '">' . esc_html( $t->name ) . '</a></h3>';
		$out .= '<p class="sgd-hub__count">' . count( $posts ) . ' dịch vụ</p></div></header>';
		$out .= '<ul class="sgd-hub__list">';
		foreach ( $posts as $p ) {
			$price = sgd_meta( 'price', $p->ID );
			$out  .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '"><span>' . esc_html( get_the_title( $p ) ) . '</span>'
				. ( $price ? '<b>' . esc_html( $price ) . '</b>' : '' ) . '</a></li>';
		}
		$out .= '</ul><a class="sgd-hub__more" href="' . esc_url( $link ) . '">Xem nhóm ' . esc_html( mb_strtolower( $t->name ) ) . ' →</a></section>';
	}
	return $out . '</div>';
}

/**
 * Bảng giá của 1 dịch vụ.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_pricing( $atts ) {
	$a  = shortcode_atts( array( 'service' => '', 'show' => 'all' ), $atts, 'sgd_pricing' );
	$id = $a['service'] ? sgd_find_service( $a['service'] ) : ( is_singular( 'dich_vu' ) ? get_the_ID() : 0 );
	if ( ! $id ) {
		return '';
	}
	ob_start();
	if ( 'packages' !== $a['show'] ) {
		get_template_part( 'template-parts/dichvu/price-tables', null, array( 'id' => $id ) );
	}
	if ( 'table' !== $a['show'] ) {
		get_template_part( 'template-parts/dichvu/packages', null, array( 'id' => $id ) );
	}
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
	get_template_part( 'template-parts/dichvu/process', null, array( 'steps' => sgd_lines( wp_strip_all_tags( $text ), 3 ), 'row' => 'row' === $a['layout'] ) );
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

/**
 * Tiêu đề giữa trang có kẻ ngang hai bên (+ dòng phụ).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_title( $atts ) {
	$a   = shortcode_atts( array( 'text' => '', 'sub' => '', 'tag' => 'h2', 'class' => '' ), $atts, 'sgd_title' );
	$tag = in_array( $a['tag'], array( 'h1', 'h2', 'h3' ), true ) ? $a['tag'] : 'h2';
	return '<div class="sgd-ltitle ' . esc_attr( $a['class'] ) . '"><' . $tag . ' class="sgd-ltitle__text"><span>' . esc_html( $a['text'] ) . '</span></' . $tag . '>'
		. ( $a['sub'] ? '<p class="sgd-ltitle__sub">' . esc_html( $a['sub'] ) . '</p>' : '' ) . '</div>';
}
add_shortcode( 'sgd_title', 'sgd_sc_title' );

/**
 * Nội dung thanh trên cùng: trái = khẩu hiệu; phải = gửi yêu cầu + hotline + mạng xã hội.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_topbar( $atts ) {
	$a = shortcode_atts( array( 'side' => 'left' ), $atts, 'sgd_topbar' );
	if ( 'left' === $a['side'] ) {
		return '<span class="sgd-topbar__slogan">' . esc_html( sgd_opt( 'topbar_text' ) ) . '</span>';
	}
	$out  = '<span class="sgd-topbar">';
	if ( sgd_opt( 'email' ) ) {
		$out .= '<a href="mailto:' . esc_attr( sgd_opt( 'email' ) ) . '" class="sgd-topbar__mail">' . sgd_icon( 'mail' ) . ' ' . esc_html( sgd_opt( 'email' ) ) . '</a>';
	}
	$out .= '<a href="#dang-ky" class="sgd-topbar__req">Gửi yêu cầu tư vấn →</a>';
	foreach ( array( 'facebook' => 'f', 'youtube' => '▶' ) as $k => $label ) {
		if ( sgd_opt( $k ) ) {
			$out .= '<a class="sgd-topbar__soc" href="' . esc_url( sgd_opt( $k ) ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( ucfirst( $k ) ) . '">' . esc_html( $label ) . '</a>';
		}
	}
	return $out . '</span>';
}
add_shortcode( 'sgd_topbar', 'sgd_sc_topbar' );

/**
 * Banner đầu trang chủ: H1 duy nhất của trang, lợi ích, nút gọi/báo giá, dịch vụ phổ biến kèm giá,
 * form tư vấn ngay màn hình đầu (không dùng slider – nhẹ, LCP nhanh, không nhảy bố cục).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_hero( $atts ) {
	$a = shortcode_atts(
		array(
			'title'     => 'Dịch vụ thành lập công ty, thuế & kế toán',
			'highlight' => 'trọn gói – không phát sinh',
			'sub'       => sgd_opt( 'archive_intro' ),
			'points'    => "Có giấy phép sau 3 – 5 ngày làm việc\nKế toán trọn gói từ 500.000đ/tháng\nLàm hồ sơ online, giao kết quả tận nơi",
			'popular'   => '1',
		),
		$atts,
		'sgd_hero'
	);
	$popular = '1' === (string) $a['popular'] ? sgd_query_services( array( 'number' => 6, 'featured' => '1', 'group' => '', 'exclude' => '' ) ) : array();
	ob_start();
	?>
	<div class="sgd-hero">
		<div class="sgd-hero__text">
			<p class="sgd-hero__kicker"><?php echo esc_html( sgd_opt( 'topbar_text' ) ? sgd_opt( 'topbar_text' ) : sgd_opt( 'company' ) ); ?></p>
			<h1 class="sgd-hero__title"><?php echo esc_html( $a['title'] ); ?> <span><?php echo esc_html( $a['highlight'] ); ?></span></h1>
			<?php if ( $a['sub'] ) : ?>
				<p class="sgd-hero__sub"><?php echo esc_html( sgd_clip( $a['sub'], 220 ) ); ?></p>
			<?php endif; ?>
			<ul class="sgd-check sgd-hero__points">
				<?php foreach ( sgd_list( str_replace( array( '<br />', '<br>', '\n' ), "\n", $a['points'] ) ) as $pt ) : ?>
					<li><?php echo esc_html( $pt ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p class="sgd-hero__btns">
				<a class="button sgd-btn" href="#dang-ky">Nhận báo giá miễn phí</a>
				<a class="button sgd-btn is-outline" href="tel:<?php echo esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ); ?>"><?php echo sgd_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sgd_opt( 'hotline' ) ); ?></a>
			</p>
			<?php if ( $popular ) : ?>
				<p class="sgd-hero__poplabel">Dịch vụ được chọn nhiều:</p>
				<ul class="sgd-hero__pop">
					<?php foreach ( $popular as $p ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( sgd_short_title( $p->ID ) ); ?><?php echo sgd_meta( 'price', $p->ID ) ? ' <b>' . esc_html( sgd_meta( 'price', $p->ID ) ) . '</b>' : ''; ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="sgd-hero__form" id="dang-ky">
			<?php get_template_part( 'template-parts/dichvu/lead-form', null, array( 'title' => sgd_opt( 'form_title' ), 'source' => 'Trang chủ – đầu trang', 'perks' => false, 'note' => false, 'button' => 'Nhận tư vấn ngay' ) ); ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'sgd_hero', 'sgd_sc_hero' );

/**
 * Hỏi đáp (mỗi dòng "Câu hỏi | Trả lời") + gom dữ liệu FAQPage.
 *
 * @param array  $atts    Thuộc tính.
 * @param string $content Nội dung.
 * @return string
 */
function sgd_sc_faq( $atts, $content = '' ) {
	$text  = str_ireplace( array( '<br>', '<br/>', '<br />', '</p>' ), "\n", (string) $content );
	$items = sgd_lines( wp_strip_all_tags( $text ) );
	if ( ! $items ) {
		return '';
	}
	$out = '<div class="sgd-faq">';
	foreach ( $items as $i => $f ) {
		if ( '' === $f[1] ) {
			continue;
		}
		sgd_collect_faq( $f[0], $f[1] );
		$out .= '<details' . ( 0 === $i ? ' open' : '' ) . '><summary><h3>' . esc_html( $f[0] ) . '</h3></summary><p>' . esc_html( $f[1] ) . '</p></details>';
	}
	return $out . '</div>';
}
add_shortcode( 'sgd_faq', 'sgd_sc_faq' );
