<?php
/**
 * Sửa lỗi SEO kỹ thuật (theo báo cáo audit):
 * - Bộ nhớ đệm ngôn ngữ Polylang còn giữ địa chỉ http cũ → /en/ chuyển hướng về http, hreflang lỗi.
 * - Trang UX Block (/blocks/…) lộ ra ngoài: không có <head>, <title>, H1, trùng nội dung.
 * - Thẻ robots dư "nofollow, follow".
 * - HSTS, bảo vệ email của Cloudflare (/cdn-cgi/l/email-protection báo 404).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Xoá bộ nhớ đệm danh sách ngôn ngữ của Polylang (lưu sẵn địa chỉ trang chủ từng ngôn ngữ).
 */
function sgd_pll_clean_cache() {
	if ( function_exists( 'PLL' ) && PLL() && isset( PLL()->model ) ) {
		if ( isset( PLL()->model->languages ) && method_exists( PLL()->model->languages, 'clean_cache' ) ) {
			PLL()->model->languages->clean_cache();
		} elseif ( method_exists( PLL()->model, 'clean_languages_cache' ) ) {
			PLL()->model->clean_languages_cache();
		}
	}
	delete_transient( 'pll_languages_list' );
}

/**
 * Đổi địa chỉ website (http → https, đổi tên miền): làm mới bộ nhớ đệm ngôn ngữ và quy tắc đường dẫn.
 */
function sgd_on_url_change() {
	sgd_pll_clean_cache();
	update_option( 'sgd_flush_rules', 1, false );
}
add_action( 'update_option_home', 'sgd_on_url_change' );
add_action( 'update_option_siteurl', 'sgd_on_url_change' );

/**
 * Lần đầu chạy phiên bản theme mới: làm mới bộ nhớ đệm ngôn ngữ (kèm dựng lại quy tắc – xem permalinks.php).
 */
function sgd_pll_cache_on_update() {
	if ( get_option( 'sgd_pll_cache_ver' ) !== SGD_VERSION ) {
		sgd_pll_clean_cache();
		update_option( 'sgd_pll_cache_ver', SGD_VERSION, false );
	}
}
add_action( 'init', 'sgd_pll_cache_on_update', 98 );

/**
 * UX Block (khối footer…) chỉ dùng để ghép vào trang, không phải trang riêng:
 * người xem thường mở /blocks/… sẽ được chuyển 301 về trang chủ; người sửa nội dung vẫn xem trước được.
 */
function sgd_blocks_redirect() {
	if ( is_singular( 'blocks' ) && ! current_user_can( 'edit_posts' ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'sgd_blocks_redirect', 1 );

/**
 * Bỏ UX Block khỏi sitemap của WordPress.
 *
 * @param array $types Loại bài.
 * @return array
 */
function sgd_sitemap_post_types( $types ) {
	unset( $types['blocks'] );
	return $types;
}
add_filter( 'wp_sitemaps_post_types', 'sgd_sitemap_post_types' );

/**
 * Chuyên mục, nhóm dịch vụ chưa có bài không vào sitemap.
 *
 * @param array  $args Tham số truy vấn.
 * @return array
 */
function sgd_sitemap_tax_args( $args ) {
	$args['hide_empty'] = true;
	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'sgd_sitemap_tax_args' );

/**
 * Bỏ trang tác giả khỏi sitemap (website doanh nghiệp, không cần).
 *
 * @param WP_Sitemaps_Provider $provider Nguồn sitemap.
 * @param string               $name     Tên.
 * @return WP_Sitemaps_Provider|false
 */
function sgd_sitemap_no_users( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'sgd_sitemap_no_users', 10, 2 );

/**
 * HSTS: trình duyệt luôn dùng HTTPS cho website (chỉ gửi khi đang chạy HTTPS).
 */
function sgd_hsts_header() {
	if ( is_ssl() && ! headers_sent() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
}
add_action( 'send_headers', 'sgd_hsts_header' );

/**
 * Cloudflare tự đổi địa chỉ email thành link /cdn-cgi/l/email-protection (công cụ SEO báo 404).
 * Đánh dấu toàn trang để Cloudflare không đổi – email vẫn hiện bình thường.
 */
function sgd_cf_email_off_open() {
	static $done = false;
	if ( ! $done ) {
		$done = true;
		echo "<!--email_off-->\n";
	}
}
add_action( 'wp_body_open', 'sgd_cf_email_off_open', 0 );
add_action( 'flatsome_after_body_open', 'sgd_cf_email_off_open', 0 );

/**
 * Đóng vùng email_off.
 */
function sgd_cf_email_off_close() {
	echo "\n<!--/email_off-->";
}
add_action( 'wp_footer', 'sgd_cf_email_off_close', 9999 );
