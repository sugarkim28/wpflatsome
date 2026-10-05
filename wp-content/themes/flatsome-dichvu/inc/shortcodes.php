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
	$a    = shortcode_atts( array( 'group' => '', 'title' => '', 'note' => '' ), $atts, 'sgd_price_table' );
	$args = array(
		'post_type'      => 'dich_vu',
		'posts_per_page' => 50,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
	);
	$names = array();
	if ( $a['group'] ) {
		$slugs             = array_map( 'trim', explode( ',', $a['group'] ) );
		$args['tax_query'] = array( array( 'taxonomy' => 'nhom_dich_vu', 'field' => 'slug', 'terms' => $slugs ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		foreach ( $slugs as $slug ) {
			$t = get_term_by( 'slug', $slug, 'nhom_dich_vu' );
			if ( $t && ! is_wp_error( $t ) ) {
				$names[] = mb_strtolower( preg_replace( '/^Dịch vụ\s+/u', '', $t->name ) );
			}
		}
	}
	$posts = get_posts( $args );
	if ( ! $posts ) {
		return '';
	}
	$title = $a['title'] ? $a['title'] : 'Bảng giá dịch vụ' . ( $names ? ' ' . implode( ' – ', $names ) : '' );
	// Cùng phong cách bảng giá kế toán: thanh tiêu đề xanh đậm, hàng tiêu đề xanh nhạt, cột đầu in đậm, ghi chú (*) trong khung.
	$out = '<div class="sgd-mtable sgd-mtable--list"><p class="sgd-mtable__title">' . esc_html( $title ) . '</p><div class="sgd-mtable__scroll"><table class="sgd-t">'
		. '<thead><tr><th scope="col">Dịch vụ</th><th scope="col">Phí dịch vụ</th><th scope="col" class="sgd-mtable__time">Thời gian</th><th scope="col" class="sgd-mtable__go"><span class="screen-reader-text">Chi tiết</span></th></tr></thead><tbody>';
	foreach ( $posts as $p ) {
		$price = sgd_meta( 'price', $p->ID );
		$time  = sgd_meta( 'duration', $p->ID );
		$out  .= '<tr><th scope="row"><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a>' . ( $time ? '<small>' . esc_html( $time ) . '</small>' : '' ) . '</th>'
			. '<td class="sgd-mtable__price">' . esc_html( $price ? $price : 'Liên hệ' ) . '</td>'
			. '<td class="sgd-mtable__time">' . esc_html( $time ) . '</td>'
			. '<td class="sgd-mtable__go"><a href="' . esc_url( get_permalink( $p ) ) . '#bang-gia">Chi tiết ›</a></td></tr>';
	}
	$note = $a['note'] ? $a['note'] : 'Phí dịch vụ chưa gồm lệ phí nhà nước (nếu có). Bấm tên dịch vụ để xem chi tiết công việc, hồ sơ và các gói.';
	return $out . '</tbody></table></div><p class="sgd-mtable__note">(*) ' . esc_html( $note ) . '</p></div>';
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
	$out  = '<span class="sgd-topbar">' . sgd_lang_switch( 'sgd-lang--top' );
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
 * Các slide giới thiệu dịch vụ ở đầu trang chủ.
 * Tuỳ biến tại Customizer (hero_slides) hoặc filter sgd_hero_slides.
 *
 * @return array
 */
function sgd_hero_slides() {
	$raw = trim( (string) sgd_opt( 'hero_slides' ) );
	if ( '' === $raw && sgd_is_en() ) {
		$raw = "Company formation | Company formation in Vietnam | for foreign investors | Investment and enterprise certificates, seal, capital account and tax registration – handled end to end by English-speaking consultants. | 100% foreign-owned company or joint venture; Market-access check before filing; Sign documents abroad, we file online | company-formation\n"
			. "Accounting & tax | Accounting & tax services | monthly, reports in English | Bookkeeping, VAT and income tax returns, payroll, annual financial statements and tax finalisation – always on time. | Fixed monthly fee; Every deadline met; Management reports in English | accounting-tax\n"
			. 'Trademark | Trademark registration | protect your brand | Clearance search, filing with the IP Office of Vietnam and follow-up until your certificate is granted. | Vietnam is first-to-file – register early; Valid 10 years, renewable; Handled end to end | other-services';
	}
	if ( '' === $raw ) {
		$raw = "Thành lập công ty | Dịch vụ thành lập công ty | trọn gói từ A – Z | Có giấy phép sau 3 – 5 ngày làm việc. Tư vấn chọn loại hình, ngành nghề, vốn điều lệ; soạn hồ sơ, nộp online và giao giấy phép, con dấu tận nơi. | Tư vấn miễn phí loại hình TNHH, cổ phần, hộ kinh doanh; Soạn và nộp hồ sơ trực tuyến, không cần đi lại; Hỗ trợ khai thuế ban đầu, chữ ký số, hóa đơn điện tử | thanh-lap-doanh-nghiep\n"
			. "Dịch vụ kế toán | Dịch vụ kế toán thuế | trọn gói hằng tháng | Kê khai thuế, làm sổ sách, báo cáo tài chính và quyết toán cuối năm – đúng hạn, đúng luật, chuyên viên riêng phụ trách. | Báo cáo thuế đúng hạn, không lo bị phạt; Sổ sách, báo cáo tài chính đầy đủ, khớp số liệu; Chịu trách nhiệm khi cơ quan thuế kiểm tra | ke-toan\n"
			. 'Thay đổi GPKD | Thay đổi giấy phép kinh doanh | nhanh – đúng luật | Đổi tên, địa chỉ, ngành nghề, vốn điều lệ, người đại diện, thành viên – trọn gói từ soạn hồ sơ đến nhận kết quả. | Rà soát và soạn hồ sơ trong ngày; Có kết quả sau 3 – 5 ngày làm việc; Cập nhật thông tin thuế, hóa đơn sau khi thay đổi | thay-doi-giay-phep';
	}
	$slides = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$c = array_map( 'trim', explode( '|', $line ) );
		if ( count( $c ) < 2 || '' === $c[1] ) {
			continue;
		}
		$c   = array_pad( $c, 6, '' );
		$url = '';
		if ( $c[5] ) {
			$term = get_term_by( 'slug', $c[5], 'nhom_dich_vu' );
			$url  = $term ? get_term_link( $term ) : '';
			$url  = is_wp_error( $url ) ? '' : $url;
		}
		$slides[] = array(
			'label'     => $c[0] ? $c[0] : $c[1],
			'title'     => $c[1],
			'highlight' => $c[2],
			'sub'       => $c[3],
			'points'    => array_values( array_filter( array_map( 'trim', explode( ';', $c[4] ) ) ) ),
			'url'       => $url,
		);
	}
	return apply_filters( 'sgd_hero_slides', $slides );
}

/**
 * Banner đầu trang chủ: H1 duy nhất của trang, slider giới thiệu 3 dịch vụ chính (thành lập công ty,
 * kế toán, thay đổi giấy phép) bên trái, form tư vấn bên phải. Slide xếp chồng bằng CSS grid nên
 * không nhảy bố cục; slide đầu hiển thị sẵn, không cần JS.
 * slider="0" → banner tĩnh như bản cũ (title, highlight, sub, points).
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
			'slider'    => '1',
			'h1'        => 'Dịch vụ thành lập công ty, kế toán thuế, thay đổi giấy phép kinh doanh',
			'interval'  => '6',
		),
		$atts,
		'sgd_hero'
	);
	$slides  = '1' === (string) $a['slider'] ? sgd_hero_slides() : array();
	$popular = '1' === (string) $a['popular'] ? sgd_query_services( array( 'number' => 6, 'featured' => '1', 'group' => '', 'exclude' => '' ) ) : array();
	$tel     = sgd_tel( sgd_opt( 'hotline' ) );
	ob_start();
	?>
	<div class="sgd-hero<?php echo $slides ? ' sgd-hero--slider' : ''; ?>">
		<div class="sgd-hero__text">
			<?php if ( $slides ) : ?>
				<h1 class="sgd-hero__kicker sgd-hero__h1"><?php echo esc_html( $a['h1'] ); ?></h1>
				<div class="sgd-hslider" data-interval="<?php echo esc_attr( max( 3, (int) $a['interval'] ) * 1000 ); ?>">
					<div class="sgd-hslider__tabs" role="tablist" aria-label="Dịch vụ chính">
						<?php foreach ( $slides as $i => $sl ) : ?>
							<button type="button" class="sgd-hslider__tab<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tab" id="sgd-hs-tab-<?php echo (int) $i; ?>" aria-controls="sgd-hs-<?php echo (int) $i; ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>"><?php echo esc_html( $sl['label'] ); ?><i></i></button>
						<?php endforeach; ?>
					</div>
					<div class="sgd-hslider__track">
						<?php foreach ( $slides as $i => $sl ) : ?>
							<div class="sgd-hslide<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tabpanel" id="sgd-hs-<?php echo (int) $i; ?>" aria-labelledby="sgd-hs-tab-<?php echo (int) $i; ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>>
								<h2 class="sgd-hero__title"><?php echo esc_html( $sl['title'] ); ?><?php echo $sl['highlight'] ? ' <span>' . esc_html( $sl['highlight'] ) . '</span>' : ''; ?></h2>
								<?php if ( $sl['sub'] ) : ?>
									<p class="sgd-hero__sub"><?php echo esc_html( $sl['sub'] ); ?></p>
								<?php endif; ?>
								<?php if ( $sl['points'] ) : ?>
									<ul class="sgd-check sgd-hero__points">
										<?php foreach ( $sl['points'] as $pt ) : ?>
											<li><?php echo esc_html( $pt ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<p class="sgd-hero__btns">
									<?php if ( $sl['url'] ) : ?>
										<a class="button sgd-btn" href="<?php echo esc_url( $sl['url'] ); ?>"<?php echo 0 === $i ? '' : ' tabindex="-1"'; ?>><?php echo esc_html( sgd_is_en() ? 'Learn more' : 'Xem ' . mb_strtolower( $sl['title'] ) ); ?></a>
									<?php else : ?>
										<a class="button sgd-btn" href="#dang-ky"<?php echo 0 === $i ? '' : ' tabindex="-1"'; ?>>Nhận báo giá miễn phí</a>
									<?php endif; ?>
									<a class="button sgd-btn is-outline" href="tel:<?php echo esc_attr( $tel ); ?>"<?php echo 0 === $i ? '' : ' tabindex="-1"'; ?>><?php echo sgd_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sgd_opt( 'hotline' ) ); ?></a>
								</p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php else : ?>
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
					<a class="button sgd-btn is-outline" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo sgd_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sgd_opt( 'hotline' ) ); ?></a>
				</p>
			<?php endif; ?>
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

/**
 * [sgd_lookup] – Tra cứu nhanh: dẫn tới các cổng tra cứu chính thức của cơ quan nhà nước.
 * Mỗi thẻ: tên, mô tả, dịch vụ liên quan của công ty (để khách cần hỗ trợ thì liên hệ).
 *
 * @return string
 */
function sgd_sc_lookup() {
	$items = array(
		array( 'building', 'Thông tin doanh nghiệp', 'Tra cứu tên, mã số, địa chỉ, người đại diện, tình trạng hoạt động của doanh nghiệp đã đăng ký.', 'https://dangkykinhdoanh.gov.vn/', 'Cổng thông tin quốc gia về đăng ký doanh nghiệp' ),
		array( 'tax', 'Mã số thuế doanh nghiệp', 'Tra cứu thông tin người nộp thuế là doanh nghiệp, tổ chức theo mã số thuế hoặc tên.', 'https://tracuunnt.gdt.gov.vn/tcnnt/mstdn.jsp', 'Cục Thuế – Bộ Tài chính' ),
		array( 'users', 'Mã số thuế cá nhân', 'Tra cứu mã số thuế, tình trạng đăng ký thuế của cá nhân.', 'https://tracuunnt.gdt.gov.vn/tcnnt/mstcn.jsp', 'Cục Thuế – Bộ Tài chính' ),
		array( 'doc', 'Hoá đơn điện tử', 'Kiểm tra hoá đơn điện tử đã được cơ quan thuế cấp mã / tiếp nhận hay chưa.', 'https://hoadondientu.gdt.gov.vn/', 'Cổng hoá đơn điện tử – Cục Thuế' ),
		array( 'calculator', 'Thuế điện tử (eTax)', 'Nộp tờ khai, nộp thuế, tra cứu nghĩa vụ thuế của doanh nghiệp.', 'https://thuedientu.gdt.gov.vn/', 'Cục Thuế – Bộ Tài chính' ),
		array( 'shield', 'Bảo hiểm xã hội', 'Tra cứu quá trình đóng BHXH, mã số BHXH, cơ sở khám chữa bệnh.', 'https://baohiemxahoi.gov.vn/', 'Bảo hiểm xã hội Việt Nam' ),
		array( 'trademark', 'Nhãn hiệu, logo', 'Tra cứu nhãn hiệu đã nộp đơn / được bảo hộ trước khi đăng ký thương hiệu.', 'https://wipopublish.ipvietnam.gov.vn/', 'Cục Sở hữu trí tuệ' ),
		array( 'search', 'Văn bản pháp luật', 'Tra cứu luật, nghị định, thông tư về doanh nghiệp, thuế, kế toán.', 'https://vbpl.vn/', 'Cơ sở dữ liệu quốc gia về văn bản pháp luật' ),
	);
	$out = '<div class="sgd-lookup">';
	foreach ( $items as $it ) {
		$out .= '<a class="sgd-lookup__item" href="' . esc_url( $it[3] ) . '" target="_blank" rel="noopener nofollow">'
			. '<span class="sgd-lookup__ico">' . sgd_icon( $it[0] ) . '</span>'
			. '<span class="sgd-lookup__body"><strong>' . esc_html( $it[1] ) . '</strong><span>' . esc_html( $it[2] ) . '</span><small>' . esc_html( $it[4] ) . ' ↗</small></span></a>';
	}
	return $out . '</div><p class="sgd-lookup__note">Các liên kết dẫn tới trang chính thức của cơ quan nhà nước. Cần hỗ trợ tra cứu hoặc xử lý kết quả? Gọi <a href="tel:' . esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ) . '">' . esc_html( sgd_opt( 'hotline' ) ) . '</a> – chuyên viên kiểm tra giúp miễn phí.</p>';
}
add_shortcode( 'sgd_lookup', 'sgd_sc_lookup' );
