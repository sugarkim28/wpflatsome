<?php
/**
 * Trang Kiến thức (blog), chuyên mục, thẻ, tìm kiếm – giao diện riêng thay mẫu blog mặc định
 * (không còn widget tiếng Anh "Recent Posts / Recent Comments", "Posted on … by admin").
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$sgd_title = sgd_blog_title();
$sgd_desc  = is_category() || is_tag() ? term_description() : ( is_search() ? '' : sgd_opt( 'blog_intro' ) );
$sgd_cats  = get_categories( array( 'hide_empty' => true, 'number' => 8 ) );
$sgd_here  = is_category() ? get_queried_object() : null;
if ( $sgd_here ) {
	// Đang xem chuyên mục: tab là các chuyên mục con (chuyên mục cha) hoặc anh em (chuyên mục con).
	$sgd_root = $sgd_here->parent ? get_category( $sgd_here->parent ) : $sgd_here;
	$sgd_sub  = get_categories( array( 'parent' => $sgd_root->term_id, 'hide_empty' => true ) );
	if ( $sgd_sub ) {
		usort(
			$sgd_sub,
			function ( $a, $b ) {
				return (int) get_term_meta( $a->term_id, '_sgd_order', true ) <=> (int) get_term_meta( $b->term_id, '_sgd_order', true );
			}
		);
		$sgd_cats = array_merge( array( $sgd_root ), $sgd_sub );
	}
}
$sgd_first = ! is_paged() && ! is_search();
?>
<div id="content" class="sgd-blog">
	<?php echo sgd_pagehead( $sgd_title, trim( wp_strip_all_tags( (string) $sgd_desc ) ) ? wp_kses_post( wpautop( $sgd_desc ) ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape. ?>
	<div class="row sgd-single__row">
		<div class="col large-8">
			<?php if ( count( $sgd_cats ) > 1 ) : ?>
				<nav class="sgd-tabs" aria-label="Chuyên mục">
					<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' ) ); ?>"<?php echo is_home() ? ' class="is-active" aria-current="page"' : ''; ?>>Tất cả</a>
					<?php foreach ( $sgd_cats as $sgd_c ) : ?>
						<a href="<?php echo esc_url( get_category_link( $sgd_c ) ); ?>"<?php echo is_category( $sgd_c->term_id ) ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo esc_html( $sgd_c->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<div class="sgd-blog__grid">
					<?php
					$sgd_i = 0;
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/dichvu/post-card', null, array( 'lead' => $sgd_first && 0 === $sgd_i, 'tag' => 'h2' ) );
						++$sgd_i;
					endwhile;
					?>
				</div>
				<?php
				the_posts_pagination(
					array(
						'mid_size'           => 1,
						'prev_text'          => '‹ Trước',
						'next_text'          => 'Sau ›',
						'class'              => 'sgd-pagination',
						'screen_reader_text' => 'Phân trang',
					)
				);
				?>
			<?php else : ?>
				<p class="sgd-empty">Chưa có bài viết phù hợp<?php echo is_search() ? ' với từ khoá “' . esc_html( get_search_query() ) . '”' : ''; ?>. Hãy thử từ khoá khác hoặc gọi <a href="tel:<?php echo esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ); ?>"><?php echo esc_html( sgd_opt( 'hotline' ) ); ?></a> để được tư vấn.</p>
			<?php endif; ?>
		</div>
		<aside class="col large-4 sgd-single__side">
			<?php get_template_part( 'template-parts/dichvu/sidebar', null, array( 'search' => true ) ); ?>
		</aside>
	</div>
</div>
