<?php
/**
 * Bài kiến thức – bố cục chuẩn:
 * breadcrumb → H1 → thông tin (cập nhật, thời gian đọc, độ khó, kiểm duyệt) → tóm tắt nhanh → ảnh → mục lục →
 * nội dung (các phần nguyên nhân, cách xử lý, lệnh, cảnh báo… do người viết soạn theo khung) → FAQ hiển thị →
 * dịch vụ liên quan → hộp hỗ trợ → tác giả & kiểm duyệt → bài liên quan.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$th_id      = get_the_ID();
	$th_cats    = get_the_category();
	$th_summary = get_post_meta( $th_id, '_th_summary', true );
	list( $th_html, $th_heads ) = th119t_toc( apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook lõi WP.
	$th_faq     = th119t_faq_html( $th_id );
	if ( $th_faq ) {
		$th_heads[] = array( 2, 'cau-hoi-thuong-gap', 'Câu hỏi thường gặp' );
	}
	$th_services = function_exists( 'th119_related_services' ) ? th119_related_services( $th_id, 3 ) : array();
	$th_related  = function_exists( 'th119_related_posts' ) ? th119_related_posts( $th_id, 4 ) : array();
	$th_cat_link = $th_cats ? '<a class="th-pagehead__cat" href="' . esc_url( get_category_link( $th_cats[0] ) ) . '">' . esc_html( $th_cats[0]->name ) . '</a>' : '';
	?>
	<div id="content" class="th-single">
		<?php th119t_pagehead( get_the_title(), '', $th_cat_link . th119t_post_meta( $th_id ) ); ?>
		<div class="th-wrap th-layout">
			<article <?php post_class( 'th-article' ); ?>>
				<?php if ( $th_summary ) : ?>
					<div class="th-summary"><p class="th-summary__label"><?php echo th119t_ic( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Tóm tắt nhanh</p><?php echo wpautop( esc_html( $th_summary ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="th-article__img"><?php the_post_thumbnail( 'large', array( 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?></figure>
				<?php endif; ?>
				<?php echo th119t_toc_html( $th_heads ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="th-content entry-content"><?php echo $th_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content. ?></div>
				<?php
				wp_link_pages();
				echo $th_faq; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo th119t_services_box( $th_services, 'Dịch vụ liên quan tại ' . th119t_opt( 'brand' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo th119t_help_box(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo th119t_author_box( $th_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$th_tags = get_the_tag_list( '<p class="th-tags">', '', '</p>' );
				if ( $th_tags && ! is_wp_error( $th_tags ) ) {
					echo wp_kses_post( $th_tags );
				}
				?>
			</article>
			<aside class="th-sidebar">
				<div class="th-sticky">
					<?php echo th119t_sc( '[th119_search]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="th-side-card">
						<p class="th-side-card__title">Cần kỹ thuật viên hỗ trợ?</p>
						<?php echo th119t_sc( '[th119_cta show="call,zalo,request"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<?php if ( count( $th_heads ) >= 3 ) : ?>
						<nav class="th-side-toc" aria-label="Mục lục"><p class="th-side-card__title">Mục lục</p><ol>
							<?php foreach ( $th_heads as $th_h ) : ?>
								<?php if ( 2 === $th_h[0] ) : ?>
									<li><a href="#<?php echo esc_attr( $th_h[1] ); ?>"><?php echo esc_html( $th_h[2] ); ?></a></li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ol></nav>
					<?php endif; ?>
				</div>
			</aside>
		</div>
		<?php if ( $th_related ) : ?>
			<section class="th-sec th-sec--alt"><div class="th-wrap"><?php echo th119t_posts_grid( $th_related, 'Bài viết liên quan' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></section>
		<?php endif; ?>
		<?php if ( comments_open() || get_comments_number() ) : ?>
			<div class="th-wrap th-comments"><?php comments_template(); ?></div>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
