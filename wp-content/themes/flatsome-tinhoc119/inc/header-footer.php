<?php
/**
 * Header, footer, nút liên hệ nổi và thanh liên hệ dưới đáy (di động).
 * Nội dung header/footer chèn vào Flatsome qua shortcode trong ô HTML của Header Builder và UX Block footer
 * → vẫn chỉnh được trong Customizer, không sửa file Flatsome.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lấy link trang theo slug (fallback trang chủ).
 *
 * @param string $slug     Slug.
 * @param string $fallback Slug dự phòng.
 * @return string
 */
function th119t_page_url( $slug, $fallback = '' ) {
	$p = get_page_by_path( $slug );
	if ( $p && 'publish' === $p->post_status ) {
		return get_permalink( $p );
	}
	return $fallback ? th119t_page_url( $fallback ) : home_url( '/' );
}

/**
 * Thanh trên cùng: slogan + địa chỉ | giờ làm việc + tra cứu bảo hành.
 *
 * @param array $atts side=left|right.
 * @return string
 */
function th119t_topbar( $atts ) {
	$a = shortcode_atts( array( 'side' => 'left' ), $atts, 'th119t_topbar' );
	if ( ! function_exists( 'th119_opt' ) ) {
		return '';
	}
	if ( 'right' === $a['side'] ) {
		$out = '';
		if ( th119_opt( 'hours' ) ) {
			$out .= '<span class="th-tb__item">' . th119_ic( 'clock' ) . ' ' . esc_html( th119_opt( 'hours' ) ) . '</span>';
		}
		$out .= '<a class="th-tb__item" href="' . esc_url( th119t_page_url( 'tra-cuu-bao-hanh' ) ) . '">' . th119_ic( 'shield' ) . ' Tra cứu bảo hành</a>';
		return '<div class="th-tb th-tb--right">' . $out . '</div>';
	}
	return '<div class="th-tb"><strong class="th-tb__slogan">' . esc_html( mb_strtoupper( th119_opt( 'slogan' ), 'UTF-8' ) ) . '</strong><a class="th-tb__item" href="' . esc_url( th119_maps_url() ) . '" target="_blank" rel="noopener" data-track="maps">' . th119_ic( 'pin' ) . ' ' . esc_html( th119_full_address() ) . '</a></div>';
}
add_shortcode( 'th119t_topbar', 'th119t_topbar' );

/**
 * Cụm hành động ở header: tìm kiếm, hotline, Zalo, gửi yêu cầu.
 *
 * @return string
 */
function th119t_header_actions() {
	if ( ! function_exists( 'th119_opt' ) ) {
		return '';
	}
	$out  = '<div class="th-hact">';
	$out .= '<div class="th-hact__search hide-for-medium">' . th119_search_shortcode( array( 'placeholder' => 'Tìm cách sửa lỗi…' ) ) . '</div>';
	$out .= '<a class="th-hact__call" href="' . esc_attr( th119_tel() ) . '" data-track="call">' . th119_ic( 'phone' ) . '<span><small>Hotline' . ( th119_opt( 'contact_person' ) ? ' – ' . esc_html( th119_opt( 'contact_person' ) ) : '' ) . '</small><strong>' . esc_html( th119_opt( 'hotline' ) ) . '</strong></span></a>';
	$out .= '<a class="th-hact__zalo hide-for-medium" href="' . esc_url( th119_zalo_url() ) . '" target="_blank" rel="noopener" data-track="zalo" aria-label="Chat Zalo">' . th119_ic( 'zalo' ) . '</a>';
	$out .= '<a class="th-btn th-btn--primary th-hact__req hide-for-medium" href="' . esc_url( th119t_page_url( 'gui-yeu-cau-ho-tro', 'lien-he' ) ) . '" data-track="cta">Gửi yêu cầu hỗ trợ</a>';
	return $out . '</div>';
}
add_shortcode( 'th119t_header_actions', 'th119t_header_actions' );

/**
 * Footer đầy đủ (đặt trong UX Block footer, xem Giao diện → Thiết lập Tin Học 119).
 *
 * @return string
 */
function th119t_footer() {
	if ( ! function_exists( 'th119_opt' ) ) {
		return '';
	}
	$logo = TH119T_URI . '/assets/img/logo-119-white.svg';
	$col1 = '<div class="th-foot__col th-foot__brand"><img src="' . esc_url( $logo ) . '" alt="' . esc_attr( th119_opt( 'brand' ) ) . '" width="200" height="60" loading="lazy"><p class="th-foot__slogan">' . esc_html( mb_strtoupper( th119_opt( 'slogan' ), 'UTF-8' ) ) . '</p><p>Sửa chữa máy tính, laptop, nâng cấp – bảo trì, dịch vụ IT doanh nghiệp và thư viện kiến thức sửa lỗi máy tính.</p></div>';

	$col2 = '<div class="th-foot__col"><h3>Liên hệ</h3>' . th119_contact_shortcode() . '</div>';

	$links = '';
	$roots = function_exists( 'th119_kb_roots' ) ? th119_kb_roots() : array();
	foreach ( array_slice( $roots, 0, 7 ) as $t ) {
		$links .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
	}
	$col3 = $links ? '<div class="th-foot__col"><h3>Kiến thức</h3><ul>' . $links . '</ul></div>' : '';

	$svc   = get_posts( array( 'post_type' => 'dich_vu', 'numberposts' => 6, 'orderby' => 'menu_order', 'order' => 'ASC', 'no_found_rows' => true ) );
	$slist = '';
	foreach ( $svc as $s ) {
		$slist .= '<li><a href="' . esc_url( get_permalink( $s ) ) . '">' . esc_html( get_the_title( $s ) ) . '</a></li>';
	}
	$col4 = $slist ? '<div class="th-foot__col"><h3>Dịch vụ</h3><ul>' . $slist . '</ul></div>' : '';

	$help  = '<li><a href="' . esc_url( th119t_page_url( 'gui-yeu-cau-ho-tro' ) ) . '">Gửi yêu cầu hỗ trợ</a></li>';
	$help .= '<li><a href="' . esc_url( th119t_page_url( 'dat-lich' ) ) . '">Đặt lịch</a></li>';
	$help .= '<li><a href="' . esc_url( th119t_page_url( 'tra-cuu-bao-hanh' ) ) . '">Tra cứu bảo hành</a></li>';
	foreach ( array( 'gioi-thieu' => 'Giới thiệu', 'chinh-sach-bao-mat' => 'Chính sách bảo mật', 'dieu-khoan-su-dung' => 'Điều khoản sử dụng' ) as $slug => $label ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status ) {
			$help .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	$col5 = '<div class="th-foot__col"><h3>Hỗ trợ</h3><ul>' . $help . '</ul></div>';

	$social = '';
	foreach ( array( 'facebook' => 'Facebook', 'youtube' => 'YouTube' ) as $k => $label ) {
		if ( th119_opt( $k ) ) {
			$social .= '<a href="' . esc_url( th119_opt( $k ) ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a>';
		}
	}
	$bottom = '<div class="th-foot__bottom"><span>© ' . esc_html( gmdate( 'Y' ) ) . ' ' . esc_html( th119_opt( 'legal_name' ) ? th119_opt( 'legal_name' ) : th119_opt( 'brand' ) ) . '</span>' . ( $social ? '<span class="th-foot__social">' . $social . '</span>' : '' ) . '</div>';

	return '<div class="th-foot"><div class="th-wrap"><div class="th-foot__grid">' . $col1 . $col2 . $col3 . $col4 . $col5 . '</div>' . $bottom . '</div></div>';
}
add_shortcode( 'th119t_footer', 'th119t_footer' );

/**
 * Nút nổi (máy tính) + thanh liên hệ cố định dưới đáy (di động): Gọi, Zalo, Gửi yêu cầu, Đặt lịch.
 */
function th119t_contact_dock() {
	if ( ! function_exists( 'th119_opt' ) || is_admin() ) {
		return;
	}
	$tel  = th119_tel();
	$zalo = th119_zalo_url();
	$req  = th119t_page_url( 'gui-yeu-cau-ho-tro', 'lien-he' );
	$book = th119t_page_url( 'dat-lich', 'gui-yeu-cau-ho-tro' );
	?>
	<div class="th-float" aria-label="Liên hệ nhanh">
		<a class="th-float__btn th-float__btn--zalo" href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener" data-track="zalo" aria-label="Chat Zalo"><?php echo th119_ic( 'zalo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<a class="th-float__btn th-float__btn--call" href="<?php echo esc_attr( $tel ); ?>" data-track="call" aria-label="Gọi <?php echo esc_attr( th119_opt( 'hotline' ) ); ?>"><?php echo th119_ic( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	</div>
	<nav class="th-dock" aria-label="Liên hệ nhanh">
		<a href="<?php echo esc_attr( $tel ); ?>" data-track="call" class="th-dock__call"><?php echo th119_ic( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span>Gọi</span></a>
		<a href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener" data-track="zalo"><?php echo th119_ic( 'zalo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span>Zalo</span></a>
		<a href="<?php echo esc_url( $req ); ?>" data-track="cta" class="th-dock__main"><?php echo th119_ic( 'support' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span>Gửi yêu cầu</span></a>
		<a href="<?php echo esc_url( $book ); ?>" data-track="cta"><?php echo th119_ic( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span>Đặt lịch</span></a>
	</nav>
	<?php
}
add_action( 'wp_footer', 'th119t_contact_dock', 5 );

/**
 * Footer dự phòng khi chưa gán UX Block footer: in footer của theme ngay trước footer Flatsome.
 */
function th119t_footer_fallback() {
	if ( get_theme_mod( 'footer_block' ) ) {
		return;
	}
	echo th119t_footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm.
}
add_action( 'flatsome_footer', 'th119t_footer_fallback', 5 );
