<?php
/**
 * Trang Kiến thức (trang bài viết).
 * Trang 1: giao diện chủ đề kiểu ketoananpha.vn (khi đã có chuyên mục cha – con); trang 2 trở đi: danh sách bài.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_hub = false;
if ( ! is_paged() ) {
	foreach ( get_categories( array( 'parent' => 0, 'hide_empty' => false ) ) as $sgd_c ) {
		if ( get_categories( array( 'parent' => $sgd_c->term_id, 'hide_empty' => false, 'number' => 1 ) ) ) {
			$sgd_hub = true;
			break;
		}
	}
}
get_header();
get_template_part( 'template-parts/dichvu/' . ( $sgd_hub ? 'knowledge-hub' : 'blog-list' ) );
get_footer();
