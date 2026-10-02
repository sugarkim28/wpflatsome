<?php
/**
 * Khối dựng trang chủ (bản 0.4 – bố cục gọn, khoa học):
 *  [sgd_groupnav]                    – "Bạn cần hỗ trợ việc gì?": ô nhóm dịch vụ + giá từ
 *  [sgd_catalog]                     – danh mục dịch vụ & bảng giá theo tab nhóm
 *  [sgd_pricetabs items="slug:show:Nhãn,…"] – bảng giá trọn gói theo tab
 *  [sgd_cta_strip]                   – dải kêu gọi cuối trang (hotline, Zalo, nút)
 *
 * Bản 0.5 (đồng bộ toàn site, phong cách công ty kế toán – đại lý thuế):
 *  [sgd_header_info]                 – header: hotline, Zalo, giờ làm việc (máy tính) / nút gọi gọn (điện thoại)
 *  [sgd_about]                       – khối giới thiệu công ty + số liệu uy tín
 *  [sgd_commit]                      – cam kết dịch vụ (dùng trên nền xanh đậm)
 *  [sgd_pagehead title sub]          – dải tiêu đề trang (breadcrumb + H1) dùng chung mọi trang
 *  [sgd_contact_list]                – thông tin liên hệ có icon, bỏ dòng trống (footer, trang liên hệ)
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

/**
 * Số liệu uy tín: [[con số, mô tả], ...].
 *
 * @return array
 */
function sgd_stats() {
	return array_slice( array_filter( sgd_lines( sgd_opt( 'stats' ) ), function ( $l ) {
		return '' !== $l[0];
	} ), 0, 4 );
}

/**
 * Header: thông tin liên hệ (máy tính) + nút gọi gọn (điện thoại).
 *
 * @return string
 */
function sgd_sc_header_info() {
	$hot  = sgd_opt( 'hotline' );
	$zalo = sgd_tel( sgd_opt( 'zalo' ) );
	$out  = '<div class="sgd-hinfo">';
	$out .= '<a class="sgd-hinfo__item" href="tel:' . esc_attr( sgd_tel( $hot ) ) . '"><span class="sgd-hinfo__ico is-red">' . sgd_icon( 'phone' ) . '</span><span><small>Hotline tư vấn</small><strong>' . esc_html( $hot ) . '</strong></span></a>';
	if ( $zalo ) {
		$out .= '<a class="sgd-hinfo__item" href="https://zalo.me/' . esc_attr( $zalo ) . '" target="_blank" rel="noopener"><span class="sgd-hinfo__ico is-zalo">Zalo</span><span><small>Chat Zalo</small><strong>' . esc_html( sgd_tel( $hot ) === $zalo ? $hot : sgd_opt( 'zalo' ) ) . '</strong></span></a>';
	}
	if ( sgd_opt( 'working_hours' ) ) {
		$out .= '<span class="sgd-hinfo__item"><span class="sgd-hinfo__ico">' . sgd_icon( 'clock' ) . '</span><span><small>Giờ làm việc</small><strong>' . esc_html( sgd_opt( 'working_hours' ) ) . '</strong></span></span>';
	}
	return $out . '</div><a class="sgd-hcall sgd-hinfo__m" href="tel:' . esc_attr( sgd_tel( $hot ) ) . '" aria-label="Gọi ' . esc_attr( $hot ) . '">' . sgd_icon( 'phone' ) . '<span><small>Hotline tư vấn</small><strong>' . esc_html( $hot ) . '</strong></span></a>';
}
add_shortcode( 'sgd_header_info', 'sgd_sc_header_info' );

/**
 * Khối giới thiệu công ty (trang chủ): thẻ thương hiệu + số liệu bên trái, nội dung bên phải.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_about( $atts ) {
	$a     = shortcode_atts(
		array(
			'title'  => sgd_opt( 'about_title' ),
			'text'   => sgd_opt( 'about_text' ),
			'points' => "Giá trọn gói, công khai – ghi rõ trong báo giá, hợp đồng\nHồ sơ kiểm tra kỹ trước khi nộp, đúng hẹn\nMột chuyên viên phụ trách từ đầu đến cuối\nBảo mật giấy tờ, số liệu doanh nghiệp",
			'more'   => '',
		),
		$atts,
		'sgd_about'
	);
	$stats = '';
	foreach ( sgd_stats() as $st ) {
		$stats .= '<div class="sgd-about__stat"><strong>' . esc_html( $st[0] ) . '</strong><span>' . esc_html( $st[1] ) . '</span></div>';
	}
	$points = '';
	foreach ( sgd_list( str_replace( array( '<br />', '<br>' ), "\n", $a['points'] ) ) as $pt ) {
		$points .= '<li>' . esc_html( $pt ) . '</li>';
	}
	$more = $a['more'] ? $a['more'] : ( get_page_by_path( 'gioi-thieu' ) ? get_permalink( get_page_by_path( 'gioi-thieu' ) ) : '' );
	return '<div class="sgd-about">'
		. '<div class="sgd-about__brand"><img src="' . esc_url( SGD_URI . '/assets/img/logo-119-white.svg' ) . '" alt="' . esc_attr( sgd_opt( 'company' ) ) . '" width="280" height="56" loading="lazy">'
		. '<p class="sgd-about__slogan">' . esc_html( sgd_opt( 'tagline' ) ) . '</p>'
		. ( $stats ? '<div class="sgd-about__stats">' . $stats . '</div>' : '' ) . '</div>'
		. '<div class="sgd-about__text"><p class="sgd-eyebrow">Về ' . esc_html( sgd_opt( 'company' ) ) . '</p>'
		. '<h2 class="sgd-about__title">' . esc_html( $a['title'] ) . '</h2>'
		. ( $a['text'] ? '<p class="sgd-about__lead">' . esc_html( wp_strip_all_tags( $a['text'] ) ) . '</p>' : '' )
		. ( $points ? '<ul class="sgd-check sgd-about__points">' . $points . '</ul>' : '' )
		. '<p class="sgd-about__btns"><a class="button sgd-btn" href="#dang-ky">Nhận tư vấn miễn phí</a>'
		. ( $more ? '<a class="sgd-about__more" href="' . esc_url( $more ) . '">Tìm hiểu về chúng tôi →</a>' : '' ) . '</p>'
		. '</div></div>';
}
add_shortcode( 'sgd_about', 'sgd_sc_about' );

/**
 * Cam kết dịch vụ – mỗi dòng nội dung: "icon | Tiêu đề | Mô tả".
 *
 * @param array  $atts    Thuộc tính.
 * @param string $content Nội dung.
 * @return string
 */
function sgd_sc_commit( $atts, $content = '' ) {
	$text  = trim( wp_strip_all_tags( str_ireplace( array( '<br>', '<br/>', '<br />', '</p>' ), "\n", (string) $content ) ) );
	$items = $text ? sgd_lines( $text, 3 ) : array(
		array( 'wallet', 'Đúng giá', 'Báo giá trọn gói, không thu thêm ngoài hợp đồng' ),
		array( 'clock', 'Đúng hạn', 'Hoàn thành đúng thời gian đã cam kết' ),
		array( 'shield', 'Bảo mật', 'Giữ kín giấy tờ, số liệu của doanh nghiệp' ),
		array( 'users', 'Đồng hành', 'Hỗ trợ miễn phí các câu hỏi sau dịch vụ' ),
	);
	$out = '<ul class="sgd-commit">';
	foreach ( $items as $it ) {
		$out .= '<li><span class="sgd-commit__ico">' . sgd_icon( sanitize_key( $it[0] ) ) . '</span><span><strong>' . esc_html( $it[1] ) . '</strong>' . esc_html( $it[2] ) . '</span></li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'sgd_commit', 'sgd_sc_commit' );

/**
 * Dải tiêu đề trang dùng chung: breadcrumb + (nhãn) + H1 + dòng phụ / thông tin.
 *
 * @param string $title H1.
 * @param string $sub   Dòng phụ (HTML an toàn).
 * @param string $pre   HTML trước H1 (vd nhãn chuyên mục).
 * @return string
 */
function sgd_pagehead( $title, $sub = '', $pre = '' ) {
	ob_start();
	sgd_breadcrumbs();
	$crumbs = ob_get_clean();
	return '<div class="sgd-pagehead"><div class="row"><div class="col large-12">' . $crumbs . $pre
		. '<h1 class="sgd-pagehead__title">' . esc_html( $title ) . '</h1>'
		. ( $sub ? '<div class="sgd-pagehead__sub">' . $sub . '</div>' : '' ) . '</div></div></div>';
}

/**
 * Shortcode dải tiêu đề cho trang dựng bằng UX Builder.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_pagehead( $atts ) {
	$a = shortcode_atts( array( 'title' => get_the_title(), 'sub' => '' ), $atts, 'sgd_pagehead' );
	return sgd_pagehead( $a['title'], $a['sub'] ? '<p>' . esc_html( $a['sub'] ) . '</p>' : '' );
}
add_shortcode( 'sgd_pagehead', 'sgd_sc_pagehead' );

/**
 * Thông tin liên hệ có icon (bỏ qua mục trống).
 *
 * @return string
 */
function sgd_sc_contact_list() {
	$rows = array(
		array( 'pin', sgd_opt( 'address' ), '' ),
		array( 'phone', sgd_opt( 'hotline' ), 'tel:' . sgd_tel( sgd_opt( 'hotline' ) ) ),
		array( 'mail', sgd_opt( 'email' ), 'mailto:' . sgd_opt( 'email' ) ),
		array( 'doc', sgd_opt( 'tax_code' ) ? 'MST: ' . sgd_opt( 'tax_code' ) : '', '' ),
		array( 'clock', sgd_opt( 'working_hours' ), '' ),
	);
	$out = '<ul class="sgd-clist">';
	foreach ( $rows as $r ) {
		if ( '' === trim( (string) $r[1] ) || false !== strpos( $r[1], '...' ) ) {
			continue;
		}
		$val  = $r[2] ? '<a href="' . esc_url( $r[2], array( 'tel', 'mailto' ) ) . '">' . esc_html( $r[1] ) . '</a>' : esc_html( $r[1] );
		$out .= '<li>' . sgd_icon( $r[0] ) . '<span>' . $val . '</span></li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'sgd_contact_list', 'sgd_sc_contact_list' );
