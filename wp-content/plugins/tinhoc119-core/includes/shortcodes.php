<?php
/**
 * Shortcode hiển thị (dùng được trong UX Builder của Flatsome, trình soạn thảo, mọi theme).
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * In icon an toàn.
 *
 * @param string $name Icon.
 * @return string
 */
function th119_ic( $name ) {
	return wp_kses( th119_icon( $name ), th119_svg_kses() );
}

/**
 * Nút liên hệ (Gọi, Zalo, Gửi yêu cầu, Đặt lịch).
 *
 * @param array $atts show="call,zalo,request,booking" style="".
 * @return string
 */
function th119_cta_buttons( $atts = array() ) {
	$a    = shortcode_atts( array( 'show' => 'call,zalo,request', 'class' => '' ), $atts, 'th119_cta' );
	$show = array_map( 'trim', explode( ',', $a['show'] ) );
	$req  = get_page_by_path( 'gui-yeu-cau-ho-tro' );
	$book = get_page_by_path( 'dat-lich' );
	$out  = '<div class="th-cta ' . esc_attr( $a['class'] ) . '">';
	foreach ( $show as $s ) {
		switch ( $s ) {
			case 'call':
				$out .= '<a class="th-btn th-btn--red" href="' . esc_attr( th119_tel() ) . '" data-track="call">' . th119_ic( 'phone' ) . '<span>Gọi ' . esc_html( th119_opt( 'hotline' ) ) . '</span></a>';
				break;
			case 'zalo':
				$out .= '<a class="th-btn th-btn--zalo" href="' . esc_url( th119_zalo_url() ) . '" target="_blank" rel="noopener" data-track="zalo">' . th119_ic( 'zalo' ) . '<span>Chat Zalo</span></a>';
				break;
			case 'request':
				$out .= '<a class="th-btn th-btn--primary" href="' . esc_url( $req ? get_permalink( $req ) : home_url( '/lien-he/' ) ) . '" data-track="cta">' . th119_ic( 'support' ) . '<span>Gửi yêu cầu hỗ trợ</span></a>';
				break;
			case 'booking':
				$out .= '<a class="th-btn th-btn--ghost" href="' . esc_url( $book ? get_permalink( $book ) : ( $req ? get_permalink( $req ) : home_url( '/' ) ) ) . '" data-track="cta">' . th119_ic( 'calendar' ) . '<span>Đặt lịch</span></a>';
				break;
			case 'maps':
				$out .= '<a class="th-btn th-btn--ghost" href="' . esc_url( th119_maps_url() ) . '" target="_blank" rel="noopener" data-track="maps">' . th119_ic( 'pin' ) . '<span>Chỉ đường</span></a>';
				break;
		}
	}
	th119_enqueue_front();
	return $out . '</div>';
}
add_shortcode( 'th119_cta', 'th119_cta_buttons' );

/**
 * Thông tin liên hệ (NAP).
 *
 * @return string
 */
function th119_contact_shortcode() {
	th119_enqueue_front();
	$rows = array(
		array( 'pin', 'Địa chỉ', esc_html( th119_full_address() ), th119_maps_url() ),
		array( 'phone', 'Hotline', esc_html( th119_opt( 'hotline' ) ), th119_tel() ),
		array( 'zalo', 'Zalo', esc_html( th119_opt( 'hotline' ) ), th119_zalo_url() ),
		array( 'mail', 'Email', esc_html( th119_opt( 'email' ) ), th119_opt( 'email' ) ? 'mailto:' . th119_opt( 'email' ) : '' ),
		array( 'clock', 'Giờ làm việc', esc_html( th119_opt( 'hours' ) ), '' ),
		array( 'support', 'Người liên hệ', esc_html( th119_opt( 'contact_person' ) ), '' ),
	);
	$out  = '<ul class="th-contact">';
	foreach ( $rows as $r ) {
		if ( '' === $r[2] ) {
			continue;
		}
		$val  = $r[3] ? '<a href="' . esc_attr( $r[3] ) . '"' . ( 0 === strpos( $r[3], 'http' ) ? ' target="_blank" rel="noopener"' : '' ) . ( 'phone' === $r[0] ? ' data-track="call"' : ( 'zalo' === $r[0] ? ' data-track="zalo"' : ( 'pin' === $r[0] ? ' data-track="maps"' : '' ) ) ) . '>' . $r[2] . '</a>' : $r[2];
		$out .= '<li>' . th119_ic( $r[0] ) . '<span><small>' . esc_html( $r[1] ) . '</small>' . $val . '</span></li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'th119_contact', 'th119_contact_shortcode' );

/**
 * Bản đồ Google (iframe tải trễ) hoặc nút mở Google Maps.
 *
 * @return string
 */
function th119_map_shortcode() {
	$src = th119_opt( 'maps_embed' );
	if ( $src && 0 === strpos( $src, 'https://www.google.com/maps/embed' ) ) {
		return '<div class="th-map"><iframe src="' . esc_url( $src ) . '" title="Bản đồ ' . esc_attr( th119_opt( 'brand' ) ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>';
	}
	return '<p><a class="th-btn th-btn--ghost" href="' . esc_url( th119_maps_url() ) . '" target="_blank" rel="noopener" data-track="maps">' . th119_ic( 'pin' ) . ' Xem đường đi trên Google Maps</a></p>';
}
add_shortcode( 'th119_map', 'th119_map_shortcode' );

/**
 * Ô tìm kiếm kiến thức.
 *
 * @param array $atts placeholder.
 * @return string
 */
function th119_search_shortcode( $atts = array() ) {
	$a = shortcode_atts( array( 'placeholder' => 'Tìm lỗi máy tính, laptop, Windows, máy in…' ), $atts, 'th119_search' );
	return '<form class="th-search" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '"><label class="screen-reader-text" for="th-s-' . esc_attr( wp_rand( 100, 999 ) ) . '">Tìm kiếm</label><input type="search" name="s" placeholder="' . esc_attr( $a['placeholder'] ) . '" value="' . esc_attr( get_search_query() ) . '" required><input type="hidden" name="post_type" value="post"><button type="submit" aria-label="Tìm">' . th119_ic( 'search' ) . '</button></form>';
}
add_shortcode( 'th119_search', 'th119_search_shortcode' );

/**
 * 6 dịch vụ chính (khi chưa có bài Dịch vụ thì hiển thị danh mục cố định trỏ về trang Dịch vụ).
 *
 * @return array
 */
function th119_core_services() {
	return array(
		array( 'laptop', 'Sửa chữa laptop', 'Không lên nguồn, không sạc, lỗi màn hình, bàn phím, quạt kêu, máy nóng, chạy chậm.' ),
		array( 'pc', 'Sửa chữa máy tính bàn', 'Kiểm tra nguồn, mainboard, RAM, ổ cứng, card màn hình; cài đặt và tối ưu hệ điều hành.' ),
		array( 'upgrade', 'Nâng cấp máy tính', 'Tư vấn và nâng cấp SSD, RAM, card màn hình phù hợp nhu cầu và ngân sách.' ),
		array( 'shield', 'Bảo trì máy tính', 'Vệ sinh, thay keo tản nhiệt, kiểm tra ổ cứng, cập nhật và sao lưu dữ liệu định kỳ.' ),
		array( 'building', 'Dịch vụ IT doanh nghiệp', 'Bảo trì định kỳ, quản lý mạng nội bộ, máy in, sao lưu dữ liệu cho văn phòng.' ),
		array( 'support', 'Hỗ trợ kỹ thuật', 'Hỗ trợ từ xa hoặc tận nơi: cài phần mềm, sửa lỗi Office, máy in, Wi-Fi.' ),
	);
}

/**
 * Lưới dịch vụ.
 *
 * @param array $atts number, columns, group.
 * @return string
 */
function th119_services_shortcode( $atts = array() ) {
	$a     = shortcode_atts( array( 'number' => 6, 'columns' => 3, 'group' => '', 'featured' => '1' ), $atts, 'th119_services' );
	$args  = array(
		'post_type'      => 'dich_vu',
		'posts_per_page' => (int) $a['number'],
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	);
	if ( $a['group'] ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'nhom_dich_vu', 'field' => 'slug', 'terms' => explode( ',', $a['group'] ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	}
	$posts = array();
	if ( '1' === $a['featured'] && ! $a['group'] ) {
		$posts = get_posts( array_merge( $args, array( 'meta_key' => '_sgd_featured', 'meta_value' => '1' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( ! $posts ) {
		$posts = get_posts( $args );
	}
	th119_enqueue_front();
	$out = '<div class="th-cards th-cols-' . (int) $a['columns'] . '">';
	if ( ! $posts ) {
		$arch = get_post_type_archive_link( 'dich_vu' );
		foreach ( th119_core_services() as $s ) {
			$out .= '<a class="th-card th-card--service" href="' . esc_url( $arch ) . '"><span class="th-card__icon">' . th119_ic( $s[0] ) . '</span><h3>' . esc_html( $s[1] ) . '</h3><p>' . esc_html( $s[2] ) . '</p><span class="th-card__more">Xem dịch vụ ' . th119_ic( 'arrow' ) . '</span></a>';
		}
		return $out . '</div>';
	}
	foreach ( $posts as $p ) {
		$icon  = th119_service_field( 'icon', $p->ID );
		$price = th119_service_field( 'price', $p->ID );
		$desc  = th119_service_field( 'subtitle', $p->ID );
		if ( ! $desc ) {
			$desc = $p->post_excerpt ? $p->post_excerpt : wp_trim_words( wp_strip_all_tags( $p->post_content ), 22 );
		}
		$out .= '<a class="th-card th-card--service" href="' . esc_url( get_permalink( $p ) ) . '"><span class="th-card__icon">' . th119_ic( $icon ? $icon : 'tool' ) . '</span><h3>' . esc_html( get_the_title( $p ) ) . '</h3><p>' . esc_html( $desc ) . '</p>' . ( $price ? '<span class="th-card__price">' . esc_html( $price ) . '</span>' : '' ) . '<span class="th-card__more">Xem chi tiết ' . th119_ic( 'arrow' ) . '</span></a>';
	}
	return $out . '</div>';
}
add_shortcode( 'th119_services', 'th119_services_shortcode' );

/**
 * Lưới chuyên mục kiến thức.
 *
 * @param array $atts children="4".
 * @return string
 */
function th119_kb_categories_shortcode( $atts = array() ) {
	$a     = shortcode_atts( array( 'children' => 4 ), $atts, 'th119_kb_categories' );
	$roots = th119_kb_roots();
	th119_enqueue_front();
	if ( ! $roots ) {
		return current_user_can( 'manage_options' ) ? '<p class="th-empty">Chưa có chuyên mục kiến thức. Vào <a href="' . esc_url( admin_url( 'admin.php?page=th119-tools' ) ) . '">Tin Học 119 → Công cụ</a> để tạo cây chuyên mục.</p>' : '';
	}
	$out = '<div class="th-cards th-cols-4 th-kb-grid">';
	foreach ( $roots as $t ) {
		$icon     = get_term_meta( $t->term_id, 'th_icon', true );
		$children = get_terms( array( 'taxonomy' => 'category', 'parent' => $t->term_id, 'hide_empty' => false, 'number' => (int) $a['children'] ) );
		$total    = (int) $t->count;
		$kids     = '';
		foreach ( is_wp_error( $children ) ? array() : $children as $c ) {
			$total += (int) $c->count;
			$kids  .= '<li><a href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . '</a></li>';
		}
		$out .= '<div class="th-card th-card--kb"><a class="th-card__head" href="' . esc_url( get_term_link( $t ) ) . '"><span class="th-card__icon">' . th119_ic( $icon ? $icon : 'book' ) . '</span><h3>' . esc_html( $t->name ) . '</h3></a>' . ( $total ? '<span class="th-card__count">' . (int) $total . ' bài viết</span>' : '<span class="th-card__count">Đang cập nhật</span>' ) . ( $kids ? '<ul>' . $kids . '</ul>' : '' ) . '<a class="th-card__more" href="' . esc_url( get_term_link( $t ) ) . '">Xem tất cả ' . th119_ic( 'arrow' ) . '</a></div>';
	}
	return $out . '</div>';
}
add_shortcode( 'th119_kb_categories', 'th119_kb_categories_shortcode' );

/**
 * Thẻ bài viết.
 *
 * @param WP_Post $p     Bài.
 * @param bool    $image Có ảnh.
 * @return string
 */
function th119_post_card( $p, $image = true ) {
	$cats = get_the_category( $p->ID );
	$img  = $image && has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'lazy', 'class' => 'th-post__img' ) ) : '';
	return '<article class="th-post"><a href="' . esc_url( get_permalink( $p ) ) . '" class="th-post__link">' . ( $img ? '<div class="th-post__media">' . $img . '</div>' : '' ) . '<div class="th-post__body">' . ( $cats ? '<span class="th-post__cat">' . esc_html( $cats[0]->name ) . '</span>' : '' ) . '<h3 class="th-post__title">' . esc_html( get_the_title( $p ) ) . '</h3><p class="th-post__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $p ), 20 ) ) . '</p><time datetime="' . esc_attr( get_the_modified_date( 'c', $p ) ) . '">Cập nhật ' . esc_html( get_the_modified_date( 'd/m/Y', $p ) ) . '</time></div></a></article>';
}

/**
 * Danh sách bài viết.
 *
 * @param array $atts number, cat (slug/ID), featured, columns, image.
 * @return string
 */
function th119_posts_shortcode( $atts = array() ) {
	$a    = shortcode_atts( array( 'number' => 6, 'cat' => '', 'featured' => '0', 'columns' => 3, 'image' => '1', 'exclude' => '' ), $atts, 'th119_posts' );
	$args = array(
		'posts_per_page'      => (int) $a['number'],
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post__not_in'        => array_filter( array_map( 'absint', explode( ',', $a['exclude'] ) ) ),
	);
	if ( $a['cat'] ) {
		$args[ is_numeric( $a['cat'] ) ? 'cat' : 'category_name' ] = $a['cat'];
	}
	$posts = array();
	if ( '1' === $a['featured'] ) {
		$posts = get_posts( array_merge( $args, array( 'meta_key' => '_th_featured', 'meta_value' => 1 ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( count( $posts ) < (int) $a['number'] ) {
			$more  = get_posts( array_merge( $args, array( 'posts_per_page' => (int) $a['number'] - count( $posts ), 'post__not_in' => array_merge( $args['post__not_in'], wp_list_pluck( $posts, 'ID' ) ) ) ) );
			$posts = array_merge( $posts, $more );
		}
	} else {
		$posts = get_posts( $args );
	}
	if ( ! $posts ) {
		return '';
	}
	th119_enqueue_front();
	$out = '<div class="th-posts th-cols-' . (int) $a['columns'] . '">';
	foreach ( $posts as $p ) {
		$out .= th119_post_card( $p, '1' === $a['image'] );
	}
	return $out . '</div>';
}
add_shortcode( 'th119_posts', 'th119_posts_shortcode' );

/**
 * Trung tâm kiến thức (trang /kien-thuc/).
 *
 * @return string
 */
function th119_kb_hub_shortcode() {
	$out  = '<div class="th-kb-hub">';
	$out .= '<div class="th-kb-hub__search">' . th119_search_shortcode() . '</div>';
	$out .= '<h2 class="th-section-title">Chủ đề kiến thức</h2>' . th119_kb_categories_shortcode();
	$feat = th119_posts_shortcode( array( 'number' => 3, 'featured' => '1' ) );
	if ( $feat ) {
		$out .= '<h2 class="th-section-title">Bài viết nổi bật</h2>' . $feat;
	}
	$new = th119_posts_shortcode( array( 'number' => 9 ) );
	if ( $new ) {
		$out .= '<h2 class="th-section-title">Bài viết mới</h2>' . $new;
	}
	$out .= '<div class="th-kb-hub__help"><h2>Không tìm thấy cách xử lý?</h2><p>Mô tả sự cố, kỹ thuật viên ' . esc_html( th119_opt( 'brand' ) ) . ' sẽ tư vấn miễn phí cách kiểm tra trước khi quyết định sửa.</p>' . th119_cta_buttons( array( 'show' => 'call,zalo,request' ) ) . '</div>';
	return $out . '</div>';
}
add_shortcode( 'th119_kb_hub', 'th119_kb_hub_shortcode' );

/**
 * Dự án đã thực hiện (ẩn khi chưa có).
 *
 * @param array $atts number.
 * @return string
 */
function th119_projects_shortcode( $atts = array() ) {
	$a     = shortcode_atts( array( 'number' => 3 ), $atts, 'th119_projects' );
	$posts = get_posts( array( 'post_type' => 'th_project', 'posts_per_page' => (int) $a['number'], 'no_found_rows' => true ) );
	if ( ! $posts ) {
		return '';
	}
	th119_enqueue_front();
	$out = '<div class="th-posts th-cols-3">';
	foreach ( $posts as $p ) {
		$meta = array_filter( array( th119_field( 'client', $p->ID ), th119_field( 'location', $p->ID ), th119_field( 'date', $p->ID ) ) );
		$img  = has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'lazy', 'class' => 'th-post__img' ) ) : '';
		$out .= '<article class="th-post th-post--project"><a class="th-post__link" href="' . esc_url( get_permalink( $p ) ) . '">' . ( $img ? '<div class="th-post__media">' . $img . '</div>' : '' ) . '<div class="th-post__body"><h3 class="th-post__title">' . esc_html( get_the_title( $p ) ) . '</h3>' . ( $meta ? '<p class="th-post__meta">' . esc_html( implode( ' · ', $meta ) ) . '</p>' : '' ) . '<p class="th-post__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $p ), 18 ) ) . '</p></div></a></article>';
	}
	return $out . '</div>';
}
add_shortcode( 'th119_projects', 'th119_projects_shortcode' );

/**
 * Đánh giá khách hàng thật (ẩn khi chưa có).
 *
 * @param array $atts number.
 * @return string
 */
function th119_reviews_shortcode( $atts = array() ) {
	$a     = shortcode_atts( array( 'number' => 6 ), $atts, 'th119_reviews' );
	$posts = get_posts( array( 'post_type' => 'th_review', 'posts_per_page' => (int) $a['number'], 'no_found_rows' => true ) );
	if ( ! $posts ) {
		return '';
	}
	th119_enqueue_front();
	$out = '<div class="th-reviews">';
	foreach ( $posts as $p ) {
		$rating = (int) th119_field( 'rating', $p->ID );
		$src    = th119_field( 'source', $p->ID );
		$link   = th119_field( 'link', $p->ID );
		$stars  = $rating ? '<span class="th-stars" aria-label="' . $rating . ' trên 5 sao">' . str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) . '</span>' : '';
		$out   .= '<figure class="th-review">' . $stars . '<blockquote>' . wp_kses_post( wpautop( $p->post_content ) ) . '</blockquote><figcaption><strong>' . esc_html( get_the_title( $p ) ) . '</strong>' . ( $src ? ' · ' . ( $link ? '<a href="' . esc_url( $link ) . '" target="_blank" rel="nofollow noopener">' . esc_html( $src ) . '</a>' : esc_html( $src ) ) : '' ) . ( th119_field( 'date', $p->ID ) ? ' · ' . esc_html( th119_field( 'date', $p->ID ) ) : '' ) . '</figcaption></figure>';
	}
	return $out . '</div>';
}
add_shortcode( 'th119_reviews', 'th119_reviews_shortcode' );

/**
 * Quy trình làm việc. Nội dung: mỗi dòng "Bước | Mô tả" (trống = quy trình mặc định).
 *
 * @param array  $atts    Thuộc tính.
 * @param string $content Nội dung.
 * @return string
 */
function th119_process_shortcode( $atts, $content = '' ) {
	$steps = th119_pipe_rows( wp_strip_all_tags( (string) $content ) );
	if ( ! $steps ) {
		$steps = array(
			array( 'Tiếp nhận', 'Nhận yêu cầu qua điện thoại, Zalo, website hoặc tại cửa hàng; cấp mã phiếu để theo dõi.' ),
			array( 'Kiểm tra & báo giá', 'Kỹ thuật viên kiểm tra, giải thích nguyên nhân và báo giá trước khi sửa.' ),
			array( 'Khách hàng xác nhận', 'Chỉ tiến hành sửa chữa, thay linh kiện khi khách hàng đồng ý.' ),
			array( 'Sửa chữa & kiểm thử', 'Xử lý lỗi, kiểm tra lại toàn bộ chức năng trước khi bàn giao.' ),
			array( 'Bàn giao & bảo hành', 'Bàn giao kèm phiếu bảo hành; tra cứu bảo hành trực tuyến bằng mã phiếu.' ),
		);
	}
	th119_enqueue_front();
	$out = '<ol class="th-steps">';
	foreach ( $steps as $i => $s ) {
		$out .= '<li><span class="th-steps__n">' . ( $i + 1 ) . '</span><h3>' . esc_html( $s[0] ) . '</h3><p>' . esc_html( $s[1] ) . '</p></li>';
	}
	return $out . '</ol>';
}
add_shortcode( 'th119_process', 'th119_process_shortcode' );

/**
 * Lý do chọn. Nội dung: mỗi dòng "Tiêu đề | Mô tả" (trống = mặc định – hãy sửa cho đúng thực tế).
 *
 * @param array  $atts    Thuộc tính.
 * @param string $content Nội dung.
 * @return string
 */
function th119_why_shortcode( $atts, $content = '' ) {
	$items = th119_pipe_rows( wp_strip_all_tags( (string) $content ) );
	if ( ! $items ) {
		$items = array(
			array( 'Báo giá trước khi sửa', 'Kiểm tra, giải thích lỗi rõ ràng; không phát sinh chi phí khi chưa được khách đồng ý.' ),
			array( 'Minh bạch linh kiện', 'Linh kiện thay thế được thông báo rõ loại, nguồn gốc và thời hạn bảo hành.' ),
			array( 'Tra cứu bảo hành online', 'Mỗi máy có mã phiếu, khách tự tra cứu tình trạng và thời hạn bảo hành trên website.' ),
			array( 'Hỗ trợ tận nơi', 'Hỗ trợ tại nhà, văn phòng khu vực ' . th119_opt( 'district' ) . ' và lân cận.' ),
			array( 'Bảo mật dữ liệu', 'Không truy cập dữ liệu cá nhân khi không cần thiết cho việc sửa chữa.' ),
			array( 'Đồng hành với doanh nghiệp', 'Bảo trì định kỳ, hỗ trợ từ xa, tư vấn hệ thống IT văn phòng.' ),
		);
	}
	th119_enqueue_front();
	$icons = array( 'check', 'shield', 'search', 'pin', 'shield', 'building' );
	$out   = '<div class="th-why">';
	foreach ( $items as $i => $it ) {
		$out .= '<div class="th-why__item">' . th119_ic( $icons[ $i % 6 ] ) . '<h3>' . esc_html( $it[0] ) . '</h3><p>' . esc_html( $it[1] ) . '</p></div>';
	}
	return $out . '</div>';
}
add_shortcode( 'th119_why', 'th119_why_shortcode' );

/* ---------------------------------------------------------------------------
 * Shortcode trình bày trong bài kiến thức.
 * ------------------------------------------------------------------------- */

add_shortcode(
	'th_canh_bao',
	function ( $atts, $content = '' ) {
		return '<div class="th-note th-note--warn" role="note">' . th119_ic( 'warning' ) . '<div><strong>Cảnh báo</strong>' . wpautop( do_shortcode( wp_kses_post( $content ) ) ) . '</div></div>';
	}
);
add_shortcode(
	'th_luu_y',
	function ( $atts, $content = '' ) {
		return '<div class="th-note th-note--info" role="note">' . th119_ic( 'info' ) . '<div><strong>Lưu ý</strong>' . wpautop( do_shortcode( wp_kses_post( $content ) ) ) . '</div></div>';
	}
);
add_shortcode(
	'th_lenh',
	function ( $atts, $content = '' ) {
		$a    = shortcode_atts( array( 'label' => 'Lệnh' ), $atts, 'th_lenh' );
		$code = trim( html_entity_decode( wp_strip_all_tags( str_replace( array( '<br>', '<br />', '<br/>' ), "\n", (string) $content ) ), ENT_QUOTES, 'UTF-8' ) );
		th119_enqueue_front();
		return '<div class="th-code"><div class="th-code__bar"><span>' . esc_html( $a['label'] ) . '</span><button type="button" class="th-code__copy">Sao chép</button></div><pre><code>' . esc_html( $code ) . '</code></pre></div>';
	}
);
add_shortcode(
	'th_ky_thuat_vien',
	function () {
		return '<div class="th-tech-box"><h3>Cần kỹ thuật viên hỗ trợ?</h3><p>Nếu đã thử các bước trên mà máy vẫn lỗi, hoặc gặp các dấu hiệu nguy hiểm (mùi khét, pin phồng, máy vào nước), hãy ngừng sử dụng và liên hệ ' . esc_html( th119_opt( 'brand' ) ) . ' để được kiểm tra.</p>' . th119_cta_buttons( array( 'show' => 'call,zalo,request' ) ) . '</div>';
	}
);

/**
 * Để shortcode [th_lenh] giữ nguyên nội dung lệnh (không bị wptexturize đổi dấu nháy).
 *
 * @param array $shortcodes Danh sách.
 * @return array
 */
function th119_no_texturize( $shortcodes ) {
	$shortcodes[] = 'th_lenh';
	return $shortcodes;
}
add_filter( 'no_texturize_shortcodes', 'th119_no_texturize' );

/**
 * Trang chủ dựng sẵn (theme Tin Học 119 có thể thay bằng bố cục riêng qua bộ lọc th119_home_html).
 *
 * @return string
 */
function th119_home_shortcode() {
	$custom = apply_filters( 'th119_home_html', '' );
	if ( $custom ) {
		return $custom;
	}
	$kb   = get_page_by_path( 'kien-thuc' );
	$out  = '<section class="th-sec th-hero"><div class="th-wrap"><p class="th-hero__eyebrow">' . esc_html( th119_opt( 'slogan' ) ) . '</p><h1>' . esc_html( th119_opt( 'brand' ) ) . ' – Sửa máy tính, laptop &amp; dịch vụ IT ' . esc_html( th119_opt( 'district' ) ) . '</h1><p>Kiểm tra, báo giá trước khi sửa. Hỗ trợ tận nơi cho gia đình và văn phòng.</p>' . th119_search_shortcode() . th119_cta_buttons( array( 'show' => 'call,zalo,request' ) ) . '</div></section>';
	$out .= '<section class="th-sec"><div class="th-wrap"><h2 class="th-section-title">Dịch vụ</h2>' . th119_services_shortcode() . '</div></section>';
	$out .= '<section class="th-sec th-sec--alt"><div class="th-wrap"><h2 class="th-section-title">Kiến thức sửa lỗi máy tính</h2>' . th119_kb_categories_shortcode() . ( $kb ? '<p class="th-center"><a class="th-btn th-btn--ghost" href="' . esc_url( get_permalink( $kb ) ) . '">Vào Trung tâm kiến thức</a></p>' : '' ) . '</div></section>';
	$posts = th119_posts_shortcode( array( 'number' => 6, 'featured' => '1' ) );
	if ( $posts ) {
		$out .= '<section class="th-sec"><div class="th-wrap"><h2 class="th-section-title">Bài viết nổi bật</h2>' . $posts . '</div></section>';
	}
	$out .= '<section class="th-sec th-sec--alt"><div class="th-wrap"><h2 class="th-section-title">Quy trình làm việc</h2>' . th119_process_shortcode( array() ) . '</div></section>';
	$out .= '<section class="th-sec"><div class="th-wrap"><h2 class="th-section-title">Vì sao chọn ' . esc_html( th119_opt( 'brand' ) ) . '</h2>' . th119_why_shortcode( array() ) . '</div></section>';
	$proj = th119_projects_shortcode();
	if ( $proj ) {
		$out .= '<section class="th-sec th-sec--alt"><div class="th-wrap"><h2 class="th-section-title">Dự án đã thực hiện</h2>' . $proj . '</div></section>';
	}
	$rev = th119_reviews_shortcode();
	if ( $rev ) {
		$out .= '<section class="th-sec"><div class="th-wrap"><h2 class="th-section-title">Khách hàng nói gì</h2>' . $rev . '</div></section>';
	}
	$out .= '<section class="th-sec th-cta-band"><div class="th-wrap"><h2>Máy tính gặp sự cố?</h2><p>Gọi ngay hoặc gửi yêu cầu – kỹ thuật viên sẽ tư vấn cách xử lý.</p>' . th119_cta_buttons( array( 'show' => 'call,zalo,request,booking' ) ) . '</div></section>';
	return $out;
}
add_shortcode( 'th119_home', 'th119_home_shortcode' );
