<?php
/**
 * Trang chủ Tin Học 119 (thay bố cục mặc định của shortcode [th119_home] qua bộ lọc th119_home_html).
 * Thứ tự: Hero (H1 duy nhất) → Lỗi thường gặp → Dịch vụ → Trung tâm kiến thức → Bài nổi bật →
 * Quy trình → Vì sao chọn → Tra cứu bảo hành → Dự án (nếu có) → Đánh giá thật (nếu có) → Gọi ngay.
 * Khối nào chưa có dữ liệu thật thì tự ẩn.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bộ lọc trang chủ.
 *
 * @param string $html HTML hiện tại.
 * @return string
 */
function th119t_home_html( $html ) {
	if ( $html || ! function_exists( 'th119_opt' ) ) {
		return $html;
	}
	$brand = th119_opt( 'brand' );
	$kb    = get_page_by_path( 'kien-thuc' );

	// 1. Hero.
	$out  = '<section class="th-sec th-hero th-hero--split"><div class="th-wrap th-hero__grid"><div class="th-hero__text">';
	$out .= '<p class="th-hero__eyebrow">' . esc_html( mb_strtoupper( th119_opt( 'slogan' ), 'UTF-8' ) ) . '</p>';
	$out .= '<h1>Sửa máy tính, laptop &amp; dịch vụ IT tại ' . esc_html( th119_opt( 'district' ) ) . '<span> – hướng dẫn tự xử lý lỗi máy tính</span></h1>';
	$out .= '<p class="th-hero__lead">' . esc_html( $brand ) . ' kiểm tra và báo giá trước khi sửa, hỗ trợ tận nơi cho gia đình và văn phòng. Thư viện kiến thức giúp bạn tự xử lý các lỗi thường gặp.</p>';
	$out .= th119_search_shortcode();
	$out .= th119_cta_buttons( array( 'show' => 'call,zalo,request' ) );
	$out .= '</div><div class="th-hero__art"><img src="' . esc_url( TH119T_URI . '/assets/img/hero-tech.svg' ) . '" alt="" width="560" height="440" fetchpriority="high"></div></div></section>';

	// 2. Lỗi thường gặp: lối tắt tới chuyên mục con có bài.
	$quick = th119t_quick_topics();
	if ( $quick ) {
		$out .= '<section class="th-sec th-sec--tight"><div class="th-wrap"><h2 class="th-section-title th-section-title--sm">Bạn đang gặp lỗi gì?</h2><div class="th-chips">' . $quick . '</div></div></section>';
	}

	// 3. Dịch vụ.
	$out .= '<section class="th-sec"><div class="th-wrap"><div class="th-head"><h2 class="th-section-title">Dịch vụ sửa chữa &amp; IT</h2><a class="th-head__more" href="' . esc_url( get_post_type_archive_link( 'dich_vu' ) ? get_post_type_archive_link( 'dich_vu' ) : home_url( '/' ) ) . '">Tất cả dịch vụ →</a></div>' . th119_services_shortcode() . '</div></section>';

	// 4. Trung tâm kiến thức.
	$out .= '<section class="th-sec th-sec--alt"><div class="th-wrap"><div class="th-head"><h2 class="th-section-title">Trung tâm kiến thức</h2>' . ( $kb ? '<a class="th-head__more" href="' . esc_url( get_permalink( $kb ) ) . '">Vào thư viện →</a>' : '' ) . '</div>' . th119_kb_categories_shortcode() . '</div></section>';

	// 5. Bài nổi bật / mới nhất.
	$posts = th119_posts_shortcode( array( 'number' => 6, 'featured' => '1' ) );
	if ( $posts ) {
		$out .= '<section class="th-sec"><div class="th-wrap"><h2 class="th-section-title">Bài hướng dẫn nổi bật</h2>' . $posts . '</div></section>';
	}

	// 6. Quy trình + 7. Vì sao chọn.
	$out .= '<section class="th-sec th-sec--alt"><div class="th-wrap"><h2 class="th-section-title">Quy trình làm việc minh bạch</h2>' . th119_process_shortcode( array() ) . '</div></section>';
	$out .= '<section class="th-sec"><div class="th-wrap"><h2 class="th-section-title">Vì sao chọn ' . esc_html( $brand ) . '</h2>' . th119_why_shortcode( array() ) . '</div></section>';

	// 8. Tra cứu bảo hành.
	$out .= '<section class="th-sec th-sec--navy"><div class="th-wrap th-narrow">' . do_shortcode( '[th119_lookup]' ) . '</div></section>';

	// 9. Dự án, 10. Đánh giá – chỉ hiện khi có dữ liệu thật.
	$proj = th119_projects_shortcode();
	if ( $proj ) {
		$out .= '<section class="th-sec th-sec--alt"><div class="th-wrap"><h2 class="th-section-title">Dự án đã thực hiện</h2>' . $proj . '</div></section>';
	}
	$rev = th119_reviews_shortcode();
	if ( $rev ) {
		$out .= '<section class="th-sec"><div class="th-wrap"><h2 class="th-section-title">Khách hàng nói gì</h2>' . $rev . '</div></section>';
	}

	// 11. Gọi ngay.
	$out .= '<section class="th-sec th-cta-band"><div class="th-wrap"><h2>Máy tính gặp sự cố?</h2><p>Gọi ' . esc_html( th119_opt( 'contact_person' ) ) . ' hoặc gửi yêu cầu – kỹ thuật viên sẽ tư vấn cách xử lý phù hợp.</p>' . th119_cta_buttons( array( 'show' => 'call,zalo,request,booking' ) ) . '</div></section>';
	return $out;
}
add_filter( 'th119_home_html', 'th119t_home_html' );

/**
 * Lối tắt: các chuyên mục con có nhiều bài nhất (chỉ chuyên mục có bài thật).
 *
 * @return string
 */
function th119t_quick_topics() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 10,
			'childless'  => true,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
		)
	);
	if ( is_wp_error( $terms ) || count( $terms ) < 3 ) {
		return '';
	}
	$out = '';
	foreach ( $terms as $t ) {
		$out .= '<a class="th-chip" href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>';
	}
	return $out;
}
