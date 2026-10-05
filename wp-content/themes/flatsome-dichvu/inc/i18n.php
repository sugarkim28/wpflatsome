<?php
/**
 * Song ngữ Việt – Anh (dùng plugin Polylang miễn phí).
 *
 * - Dịch vụ (dich_vu) và Nhóm dịch vụ (nhom_dich_vu) được Polylang dịch như bài viết / chuyên mục.
 * - Chữ cố định của theme (nút, form, tiêu đề khối, bảng giá…) tự chuyển sang tiếng Anh trên trang /en/
 *   theo từ điển inc/i18n-en.php (không cần nhập tay).
 * - Thông tin ở Tuỳ biến → Website dịch vụ: giá trị mặc định có sẵn bản tiếng Anh; giá trị bạn tự nhập được dịch ở
 *   Ngôn ngữ → Bản dịch chuỗi (nhóm "Website dịch vụ").
 * - Không cài Polylang: website chạy tiếng Việt như cũ.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Polylang đang chạy và đã có ngôn ngữ.
 *
 * @return bool
 */
function sgd_multilang() {
	return function_exists( 'pll_languages_list' ) && count( (array) pll_languages_list() ) > 1;
}

/**
 * Trang hiện tại là tiếng Anh.
 *
 * @return bool
 */
function sgd_is_en() {
	static $en = null;
	if ( null !== $en && did_action( 'wp' ) ) {
		return $en;
	}
	$cur = function_exists( 'pll_current_language' ) ? pll_current_language() : '';
	$v   = 'en' === $cur;
	if ( did_action( 'wp' ) ) {
		$en = $v;
	}
	return $v;
}

/**
 * Chọn chuỗi theo ngôn ngữ.
 *
 * @param string $vi Tiếng Việt.
 * @param string $en Tiếng Anh.
 * @return string
 */
function sgd_t( $vi, $en ) {
	return sgd_is_en() ? $en : $vi;
}

/**
 * Bản dịch (cùng ngôn ngữ trang hiện tại) của 1 trang / bài theo slug tiếng Việt.
 *
 * @param string $slug Slug trang tiếng Việt.
 * @return int ID (0 nếu không có).
 */
function sgd_page_id( $slug ) {
	$p = get_page_by_path( $slug );
	if ( ! $p ) {
		return 0;
	}
	if ( function_exists( 'pll_get_post' ) && sgd_is_en() ) {
		$t = pll_get_post( $p->ID, 'en' );
		return $t ? (int) $t : 0;
	}
	return (int) $p->ID;
}

/**
 * Cho Polylang dịch Dịch vụ và Nhóm dịch vụ (không cần bật tay trong cài đặt Polylang).
 *
 * @param array $types Loại.
 * @param bool  $is_settings Đang ở trang cài đặt.
 * @return array
 */
function sgd_pll_post_types( $types, $is_settings ) {
	if ( ! $is_settings ) {
		$types['dich_vu'] = 'dich_vu';
	}
	return $types;
}
add_filter( 'pll_get_post_types', 'sgd_pll_post_types', 10, 2 );

/**
 * Như trên cho taxonomy.
 *
 * @param array $taxes Taxonomy.
 * @param bool  $is_settings Đang ở trang cài đặt.
 * @return array
 */
function sgd_pll_taxonomies( $taxes, $is_settings ) {
	if ( ! $is_settings ) {
		$taxes['nhom_dich_vu'] = 'nhom_dich_vu';
	}
	return $taxes;
}
add_filter( 'pll_get_taxonomies', 'sgd_pll_taxonomies', 10, 2 );

/**
 * Giá trị tiếng Anh cho các tuỳ chọn mặc định (Tuỳ biến → Website dịch vụ).
 *
 * @return array
 */
function sgd_defaults_en() {
	return array(
		'company'       => 'Tin Học 119',
		'company_full'  => 'Tin Học 119',
		'tagline'       => 'Trusted service, built on trust',
		'topbar_text'   => 'Tin Học 119 – Company formation, accounting & tax in Vietnam',
		'working_hours' => '8:00 – 17:30, Mon – Sat',
		'archive_title' => 'Business services in Vietnam',
		'archive_intro' => 'Company formation for foreign investors, representative offices, accounting, tax and trademark registration in Vietnam. Clear fixed fees, English-speaking consultants, filing handled online.',
		'blog_intro'    => 'Guides on company formation, licensing, tax and accounting in Vietnam.',
		'disclaimer'    => 'Service fees exclude government fees (if any) and may vary with your case. Regulations may change; please contact our consultants for advice on your specific situation.',
		'popup_title'   => 'Send a request – get a free quote',
		'popup_sub'     => 'Company formation, tax and accounting in Vietnam',
		'form_title'    => 'Free consultation & quote',
		'form_perks'    => "Free consultation, reply within 15 minutes\nFixed all-inclusive fee, no hidden costs\nEnglish-speaking consultant",
		'about_title'   => 'Your partner from day one in Vietnam',
		'about_text'    => 'Tin Học 119 helps foreign investors and local businesses with company formation, licensing, accounting and tax in Vietnam. Each client has a dedicated consultant, a clear fixed quote up front and regular progress updates.',
		'stats'         => "3 – 5 days | to get a business license\n500,000 VND | monthly accounting from\n100% | online filing\n1 : 1 | dedicated consultant",
		'home_title'    => 'Company formation, accounting & tax services in Vietnam',
		'home_desc'     => 'Company formation for foreign investors, representative offices, accounting, tax and trademark registration in Vietnam. Fixed fees, English-speaking consultants.',
	);
}

/**
 * Giá trị tuỳ chọn theo ngôn ngữ (gọi trong sgd_opt()).
 *
 * @param string $key Khoá.
 * @param mixed  $val Giá trị tiếng Việt.
 * @return mixed
 */
function sgd_opt_lang( $key, $val ) {
	if ( ! is_string( $val ) || '' === $val || is_admin() || ! sgd_is_en() ) {
		return $val;
	}
	$d  = sgd_defaults();
	$en = sgd_defaults_en();
	if ( isset( $en[ $key ] ) && isset( $d[ $key ] ) && $val === $d[ $key ] ) {
		return $en[ $key ];
	}
	return function_exists( 'pll__' ) ? pll__( $val ) : $val;
}

/**
 * Đăng ký các tuỳ chọn chữ để dịch ở Ngôn ngữ → Bản dịch chuỗi.
 */
function sgd_pll_register_strings() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}
	$d = sgd_defaults();
	foreach ( array( 'company', 'company_full', 'tagline', 'topbar_text', 'address', 'working_hours', 'archive_title', 'archive_intro', 'archive_bottom', 'blog_intro', 'disclaimer', 'popup_title', 'popup_sub', 'form_title', 'form_perks', 'about_title', 'about_text', 'stats', 'branches', 'hotlines', 'team', 'home_title', 'home_desc' ) as $k ) {
		$v = get_theme_mod( 'sgd_' . $k, isset( $d[ $k ] ) ? $d[ $k ] : '' );
		if ( is_string( $v ) && '' !== trim( $v ) ) {
			pll_register_string( 'sgd_' . $k, $v, 'Website dịch vụ', false !== strpos( $v, "\n" ) );
		}
	}
}
add_action( 'admin_init', 'sgd_pll_register_strings' );

/**
 * Footer riêng cho tiếng Anh (UX Block "footer-website-en", do nút "Tạo bản tiếng Anh" dựng).
 *
 * @param string $val Slug block.
 * @return string
 */
function sgd_footer_block_lang( $val ) {
	if ( ! is_admin() && sgd_is_en() && get_page_by_path( 'footer-website-en', OBJECT, 'blocks' ) ) {
		return 'footer-website-en';
	}
	return $val;
}
add_filter( 'theme_mod_footer_block', 'sgd_footer_block_lang' );

/**
 * Nút chuyển ngôn ngữ VI | EN (bản dịch của trang hiện tại; chưa có bản dịch thì về trang chủ ngôn ngữ đó).
 *
 * @param string $class Class thêm.
 * @return string
 */
function sgd_lang_switch( $class = '' ) {
	if ( ! function_exists( 'pll_the_languages' ) || ! sgd_multilang() ) {
		return '';
	}
	$langs = pll_the_languages( array( 'raw' => 1, 'hide_if_no_translation' => 0, 'force_home' => 0 ) );
	if ( ! $langs ) {
		return '';
	}
	$out = '<span class="sgd-lang ' . esc_attr( $class ) . '">';
	$i   = 0;
	foreach ( $langs as $l ) {
		$label = strtoupper( 'vi' === $l['slug'] ? 'VI' : $l['slug'] );
		$url   = $l['no_translation'] ? pll_home_url( $l['slug'] ) : $l['url'];
		$out  .= ( $i++ ? '<span class="sgd-lang__sep">|</span>' : '' )
			. '<a href="' . esc_url( $url ) . '" hreflang="' . esc_attr( $l['locale'] ? str_replace( '_', '-', $l['locale'] ) : $l['slug'] ) . '" lang="' . esc_attr( $l['slug'] ) . '"'
			. ( $l['current_lang'] ? ' class="is-current" aria-current="true"' : '' ) . ' title="' . esc_attr( $l['name'] ) . '">' . esc_html( $label ) . '</a>';
	}
	return $out . '</span>';
}

/**
 * Chuỗi cho JavaScript.
 *
 * @return array
 */
function sgd_js_strings() {
	return sgd_is_en()
		? array( 'picked' => 'I choose: ', 'copyPrompt' => 'Copy link:', 'copiedZalo' => 'Link copied – paste it into Zalo to send.', 'copied' => 'Link copied.' )
		: array( 'picked' => 'Tôi chọn: ', 'copyPrompt' => 'Sao chép liên kết:', 'copiedZalo' => 'Đã sao chép liên kết – dán vào Zalo để gửi.', 'copied' => 'Đã sao chép liên kết.' );
}

/**
 * Trang tiếng Anh: chuyển chữ cố định của theme sang tiếng Anh (bắt toàn bộ HTML trang).
 */
function sgd_i18n_start() {
	if ( is_admin() || wp_doing_ajax() || is_feed() || is_robots() || ! sgd_is_en() ) {
		return;
	}
	ob_start( 'sgd_i18n_html' );
}
add_action( 'template_redirect', 'sgd_i18n_start', 0 );

/**
 * Dịch HTML: từ điển (cụm dài trước) + mẫu có số (giá, số dịch vụ, ngày…).
 *
 * @param string $html HTML.
 * @return string
 */
function sgd_i18n_html( $html ) {
	if ( '' === $html || ! preg_match( '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu', $html ) ) {
		return $html;
	}
	static $dict = null;
	if ( null === $dict ) {
		$dict = require SGD_DIR . '/inc/i18n-en.php';
	}
	$html = strtr( $html, $dict );
	$re   = array(
		'/(\d{1,3}(?:\.\d{3})+)\s?(?:đ|VNĐ|vnđ)(?![\p{L}])/u'        => static function ( $m ) {
			return str_replace( '.', ',', $m[1] ) . ' VND';
		},
		'/\bTừ (?=\d)/u'                                              => 'From ',
		'/\/tháng\b/u'                                                => '/month',
		'/\/quý\b/u'                                                  => '/quarter',
		'/(\d+)\s*–\s*(\d+) ngày làm việc/u'                          => '$1 – $2 working days',
		'/(\d+) ngày làm việc/u'                                      => '$1 working days',
		'/(\d+) dịch vụ/u'                                            => '$1 services',
		'/(\d+) phút đọc/u'                                           => '$1 min read',
		'/(\d+) bài viết/u'                                           => '$1 articles',
		'/Xem tất cả (\d+)/u'                                         => 'View all $1',
		'/Trang (\d+)/u'                                              => 'Page $1',
		'/Bước (\d+):/u'                                             => 'Step $1:',
		'/Gọi (?=[\d+])/u'                                           => 'Call ',
		'/Về (?=Tin)/u'                                               => 'About ',
	);
	foreach ( $re as $pattern => $rep ) {
		$html = is_callable( $rep ) ? preg_replace_callback( $pattern, $rep, $html ) : preg_replace( $pattern, $rep, $html );
	}
	return $html;
}
