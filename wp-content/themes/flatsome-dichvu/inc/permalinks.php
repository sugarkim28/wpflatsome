<?php
/**
 * Đường dẫn chuyên mục gọn, chuẩn SEO: bỏ tiền tố /category/.
 *   /category/kien-thuc-ke-toan/thue-tncn  →  /kien-thuc-ke-toan/thue-tncn
 * Quy tắc được dựng lại tự động
 * khi thêm, sửa, xoá chuyên mục hoặc khi cập nhật theme.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tiền tố chuyên mục đang dùng (mặc định "category").
 *
 * @return string
 */
function sgd_cat_base() {
	$base = trim( (string) get_option( 'category_base' ), '/' );
	return '' !== $base ? $base : 'category';
}

/**
 * Bỏ tiền tố khỏi link chuyên mục.
 *
 * @param string  $link Link.
 * @param WP_Term $term Chuyên mục.
 * @param string  $tax  Taxonomy.
 * @return string
 */
function sgd_cat_strip_base( $link, $term, $tax ) {
	if ( 'category' !== $tax || ! get_option( 'permalink_structure' ) ) {
		return $link;
	}
	return preg_replace( '#/' . preg_quote( sgd_cat_base(), '#' ) . '/#', '/', $link, 1 );
}
add_filter( 'term_link', 'sgd_cat_strip_base', 99, 3 );

/**
 * Đường dẫn đầy đủ của chuyên mục (cha/con).
 *
 * @param WP_Term $term Chuyên mục.
 * @return string
 */
function sgd_cat_path( $term ) {
	$slugs = array( $term->slug );
	foreach ( get_ancestors( $term->term_id, 'category', 'taxonomy' ) as $parent_id ) {
		$parent = get_term( $parent_id, 'category' );
		if ( $parent && ! is_wp_error( $parent ) ) {
			array_unshift( $slugs, $parent->slug );
		}
	}
	return implode( '/', $slugs );
}

/**
 * Thêm quy tắc cho từng chuyên mục (đặt trước các quy tắc khác).
 *
 * @param array $rules Quy tắc.
 * @return array
 */
function sgd_cat_rewrite_rules( $rules ) {
	$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return $rules;
	}
	$default = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
	$new     = array();
	foreach ( $terms as $term ) {
		$path = sgd_cat_path( $term );
		// Không lấn đường dẫn của trang/bài/dịch vụ trùng tên.
		if ( get_page_by_path( $path, OBJECT, array( 'page', 'post', 'dich_vu' ) ) ) {
			continue;
		}
		$lang  = function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $term->term_id ) : '';
		$pre   = ( $lang && $lang !== $default ) ? $lang . '/' : '';
		$query = 'index.php?category_name=' . $path . ( $pre ? '&lang=' . $lang : '' );
		$regex = $pre . preg_quote( $path, '#' );
		$new[ $regex . '/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = $query . '&feed=$matches[1]';
		$new[ $regex . '/page/?([0-9]{1,})/?$' ]                  = $query . '&paged=$matches[1]';
		$new[ $regex . '/?$' ]                                    = $query;
	}
	return $new + $rules;
}
add_filter( 'rewrite_rules_array', 'sgd_cat_rewrite_rules', 99 );

/**
 * Đánh dấu cần dựng lại quy tắc khi chuyên mục thay đổi.
 */
function sgd_cat_mark_flush() {
	update_option( 'sgd_flush_rules', 1, false );
}
add_action( 'created_category', 'sgd_cat_mark_flush' );
add_action( 'edited_category', 'sgd_cat_mark_flush' );
add_action( 'delete_category', 'sgd_cat_mark_flush' );

/**
 * Dựng lại quy tắc khi cần (chuyên mục đổi, hoặc lần đầu chạy phiên bản theme mới).
 */
function sgd_cat_maybe_flush() {
	if ( get_option( 'sgd_flush_rules' ) || get_option( 'sgd_rules_ver' ) !== SGD_VERSION ) {
		flush_rewrite_rules( false );
		delete_option( 'sgd_flush_rules' );
		update_option( 'sgd_rules_ver', SGD_VERSION, false );
	}
}
add_action( 'init', 'sgd_cat_maybe_flush', 99 );
