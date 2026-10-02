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
 * Bản 0.6 (tham khảo bố cục tanthanhthinh.com):
 *  [sgd_group_section group="slug,slug" title="" flip="0"] – khối 1 nhóm: ảnh + giới thiệu + danh sách dịch vụ có giá + bài viết của nhóm
 *  [sgd_team]                        – chuyên viên tư vấn (ảnh tròn, chức danh, điện thoại)
 *  [sgd_partners]                    – logo đối tác / khách hàng tiêu biểu
 *  Dải "Tin mới" dưới menu (hook flatsome_after_header)
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
		array( 'stamp', sgd_opt( 'founded' ) ? 'Ngày thành lập: ' . sgd_opt( 'founded' ) : '', '' ),
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

/**
 * Bài viết liên quan tới 1 hoặc nhiều nhóm dịch vụ (tìm theo tên nhóm và tên ngắn dịch vụ), bù bằng bài mới.
 *
 * @param WP_Term[] $terms   Nhóm.
 * @param int       $count   Số bài.
 * @param int[]     $exclude Bài đã dùng.
 * @return WP_Post[]
 */
function sgd_group_posts( $terms, $count = 3, $exclude = array() ) {
	$found = array();
	$keys  = array();
	foreach ( $terms as $t ) {
		$keys[] = preg_replace( '/^Dịch vụ\s+/u', '', $t->name );
		foreach ( sgd_group_services( $t ) as $p ) {
			$keys[] = sgd_meta( 'short', $p->ID ) ? sgd_meta( 'short', $p->ID ) : $p->post_title;
		}
	}
	foreach ( array_unique( array_filter( $keys ) ) as $k ) {
		if ( count( $found ) >= $count ) {
			break;
		}
		$found = array_merge(
			$found,
			get_posts(
				array(
					'post_type'           => 'post',
					's'                   => $k,
					'posts_per_page'      => $count - count( $found ),
					'post__not_in'        => array_merge( $exclude, wp_list_pluck( $found, 'ID' ) ),
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			)
		);
	}
	return $found;
}

/**
 * Khối 1 nhóm dịch vụ (kiểu tanthanhthinh.com): tiêu đề kẻ ngang, ảnh/khung thương hiệu + giới thiệu + danh sách dịch vụ kèm giá,
 * bên dưới là bài viết của nhóm.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_group_section( $atts ) {
	static $used = array();
	$a     = shortcode_atts( array( 'group' => '', 'title' => '', 'text' => '', 'posts' => 3, 'flip' => '0' ), $atts, 'sgd_group_section' );
	$terms = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', $a['group'] ) ) ) as $slug ) {
		$t = get_term_by( 'slug', $slug, 'nhom_dich_vu' );
		if ( $t && ! is_wp_error( $t ) ) {
			$terms[] = $t;
		}
	}
	if ( ! $terms ) {
		return '';
	}
	$main     = $terms[0];
	$services = array();
	foreach ( $terms as $t ) {
		$services = array_merge( $services, sgd_group_services( $t ) );
	}
	$title = $a['title'] ? $a['title'] : $main->name;
	$text  = $a['text'] ? $a['text'] : wp_strip_all_tags( $main->description );
	$img   = get_term_meta( $main->term_id, '_sgd_image', true );
	$price = sgd_min_price_label( $services );

	$visual = $img
		? '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" width="1100" height="700">'
		: '<span class="sgd-gsec__ico">' . sgd_icon( get_term_meta( $main->term_id, '_sgd_icon', true ) ) . '</span><span class="sgd-gsec__vtitle">' . esc_html( $title ) . '</span>'
			. '<span class="sgd-gsec__vmeta">' . count( $services ) . ' dịch vụ' . ( $price ? ' · <b>' . esc_html( $price ) . '</b>' : '' ) . '</span>';
	$out  = '<div class="sgd-gsec' . ( '1' === (string) $a['flip'] ? ' is-flip' : '' ) . '">';
	$out .= '<div class="sgd-ltitle is-lined"><h2 class="sgd-ltitle__text"><span>' . esc_html( $title ) . '</span></h2></div>';
	$out .= '<div class="sgd-gsec__body"><a class="sgd-gsec__visual' . ( $img ? ' has-img' : '' ) . '" href="' . esc_url( get_term_link( $main ) ) . '">' . $visual
		. '<span class="sgd-gsec__bar"><span>' . esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) . '</span><span>' . sgd_icon( 'phone' ) . ' Hotline: ' . esc_html( sgd_opt( 'hotline' ) ) . '</span></span></a>';
	$out .= '<div class="sgd-gsec__text">' . ( $text ? '<p class="sgd-gsec__desc">' . esc_html( sgd_clip( $text, 260 ) ) . '</p>' : '' ) . '<ul class="sgd-gsec__list">';
	foreach ( array_slice( $services, 0, 7 ) as $p ) {
		$pr   = sgd_meta( 'price', $p->ID );
		$out .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '"><span>' . esc_html( get_the_title( $p ) ) . '</span>' . ( $pr ? '<b>' . esc_html( $pr ) . '</b>' : '' ) . '</a></li>';
	}
	$out .= '</ul><p class="sgd-gsec__btns"><a class="button sgd-btn" href="#dang-ky">Nhận báo giá</a><a class="sgd-gsec__more" href="' . esc_url( get_term_link( $main ) ) . '">Xem tất cả ' . count( $services ) . ' dịch vụ →</a></p></div></div>';

	$posts = (int) $a['posts'] > 0 ? sgd_group_posts( $terms, (int) $a['posts'], $used ) : array();
	if ( $posts ) {
		$used = array_merge( $used, wp_list_pluck( $posts, 'ID' ) );
		ob_start();
		echo '<div class="sgd-gsec__posts">';
		global $post;
		$keep = $post;
		foreach ( $posts as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
			get_template_part( 'template-parts/dichvu/post-card', null, array( 'tag' => 'h3' ) );
		}
		$post = $keep; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_reset_postdata();
		echo '</div>';
		$out .= ob_get_clean();
	}
	return $out . '</div>';
}
add_shortcode( 'sgd_group_section', 'sgd_sc_group_section' );

/**
 * Chuyên viên tư vấn (Tuỳ biến → Thông tin công ty → Chuyên viên tư vấn).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_team( $atts ) {
	$a    = shortcode_atts( array( 'title' => 'Chuyên viên tư vấn', 'sub' => 'Gọi trực tiếp chuyên viên phụ trách để được tư vấn nhanh nhất.' ), $atts, 'sgd_team' );
	$rows = array_filter(
		sgd_lines( sgd_opt( 'team' ), 4 ),
		function ( $r ) {
			return '' !== $r[0];
		}
	);
	if ( ! $rows ) {
		return '';
	}
	$out = sgd_sc_title( array( 'text' => $a['title'], 'sub' => $a['sub'], 'class' => 'is-lined' ) ) . '<ul class="sgd-team">';
	foreach ( $rows as $r ) {
		$ini  = mb_strtoupper( mb_substr( trim( preg_replace( '/.*\s/u', '', $r[0] ) ), 0, 1 ) );
		$out .= '<li class="sgd-team__item"><span class="sgd-team__photo">' . ( $r[3] ? '<img src="' . esc_url( $r[3] ) . '" alt="' . esc_attr( $r[0] ) . '" width="140" height="140" loading="lazy">' : '<b>' . esc_html( $ini ) . '</b>' ) . '</span>'
			. '<strong class="sgd-team__name">' . esc_html( $r[0] ) . '</strong>'
			. ( $r[1] ? '<span class="sgd-team__role">' . esc_html( $r[1] ) . '</span>' : '' )
			. ( $r[2] ? '<a class="sgd-team__tel" href="tel:' . esc_attr( sgd_tel( $r[2] ) ) . '">' . esc_html( $r[2] ) . '</a>' : '' ) . '</li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'sgd_team', 'sgd_sc_team' );

/**
 * Logo đối tác / khách hàng tiêu biểu.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgd_sc_partners( $atts ) {
	$a    = shortcode_atts( array( 'title' => 'Đối tác & khách hàng tiêu biểu' ), $atts, 'sgd_partners' );
	$rows = array_filter(
		sgd_lines( sgd_opt( 'partners' ), 2 ),
		function ( $r ) {
			return '' !== $r[1];
		}
	);
	if ( ! $rows ) {
		return '';
	}
	$out = sgd_sc_title( array( 'text' => $a['title'], 'class' => 'is-lined', 'tag' => 'h3' ) ) . '<ul class="sgd-partners">';
	foreach ( $rows as $r ) {
		$out .= '<li><img src="' . esc_url( $r[1] ) . '" alt="' . esc_attr( $r[0] ) . '" loading="lazy" width="180" height="70"></li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'sgd_partners', 'sgd_sc_partners' );

/**
 * Dải "Tin mới" dưới menu (bài viết mới nhất, chạy ngang; dừng khi rê chuột / khi người dùng tắt hiệu ứng).
 */
function sgd_news_ticker() {
	if ( '0' === (string) sgd_opt( 'ticker' ) ) {
		return;
	}
	$posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 6, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
	if ( ! $posts ) {
		return;
	}
	$items = '';
	foreach ( $posts as $p ) {
		$items .= '<a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a>';
	}
	echo '<div class="sgd-ticker" aria-label="Tin mới"><div class="sgd-ticker__in"><span class="sgd-ticker__label">Tin mới</span><div class="sgd-ticker__track"><div class="sgd-ticker__run">' . $items . '<span aria-hidden="true" class="sgd-ticker__dup">' . str_replace( '<a ', '<a tabindex="-1" ', $items ) . '</span></div></div>'
		. '<span class="sgd-ticker__date">' . esc_html( wp_date( 'd/m/Y' ) ) . '</span></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape.
}
add_action( 'flatsome_after_header', 'sgd_news_ticker' );

/**
 * Menu điện thoại: thêm logo + khẩu hiệu ở đầu, hotline / Zalo ở cuối (không cần sửa menu trong quản trị).
 *
 * @param string   $items Mục menu HTML.
 * @param stdClass $args  Tham số menu.
 * @return string
 */
function sgd_mobile_menu_extras( $items, $args ) {
	if ( empty( $args->theme_location ) || 'primary_mobile' !== $args->theme_location ) {
		return $items;
	}
	$hot  = sgd_opt( 'hotline' );
	$zalo = sgd_tel( sgd_opt( 'zalo' ) );
	$head = '<li class="sgd-mnav-head"><a href="' . esc_url( home_url( '/' ) ) . '"><img src="' . esc_url( SGD_URI . '/assets/img/logo-119.svg' ) . '" alt="' . esc_attr( sgd_opt( 'company' ) ) . '" width="190" height="38"></a></li>';
	$foot = '<li class="sgd-mnav-contact"><a class="sgd-mnav-contact__call" href="tel:' . esc_attr( sgd_tel( $hot ) ) . '">' . sgd_icon( 'phone' ) . '<span><small>Hotline tư vấn</small>' . esc_html( $hot ) . '</span></a>'
		. ( $zalo ? '<a class="sgd-mnav-contact__zalo" href="https://zalo.me/' . esc_attr( $zalo ) . '" target="_blank" rel="noopener"><b>Zalo</b><span><small>Chat Zalo</small>' . esc_html( $hot ) . '</span></a>' : '' )
		. ( sgd_opt( 'working_hours' ) ? '<p>' . esc_html( sgd_opt( 'working_hours' ) ) . '</p>' : '' ) . '</li>';
	return $head . $items . $foot;
}
add_filter( 'wp_nav_menu_items', 'sgd_mobile_menu_extras', 10, 2 );
