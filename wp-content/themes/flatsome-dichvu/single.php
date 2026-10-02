<?php
/**
 * Bài viết (Kiến thức) – chuẩn SEO: breadcrumb → chuyên mục → H1 → ngày cập nhật, thời gian đọc,
 * người kiểm duyệt → ảnh → mục lục → nội dung → khối kêu gọi → bài liên quan. Không hiện bình luận.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$sgd_cats = get_the_category();
	$sgd_cat  = $sgd_cats ? $sgd_cats[0] : null;
	list( $sgd_content, $sgd_heads ) = sgd_content_headings( apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook lõi WP.
	?>
	<div id="content" class="sgd-single sgd-post-single">
		<?php
		$sgd_by = 'Cập nhật <time datetime="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . esc_html( get_the_modified_date( 'd/m/Y' ) ) . '</time> · ' . esc_html( sgd_reading_time() ) . ' phút đọc · '
			. ( sgd_opt( 'expert' ) ? 'Kiểm duyệt: <strong>' . esc_html( trim( sgd_opt( 'expert_title' ) . ' ' . sgd_opt( 'expert' ) ) ) . '</strong>' : 'Biên tập: <strong>' . esc_html( sgd_opt( 'company' ) ) . '</strong>' );
		echo sgd_pagehead( get_the_title(), '<p class="sgd-byline">' . $sgd_by . '</p>', $sgd_cat ? '<a class="sgd-pagehead__cat" href="' . esc_url( get_category_link( $sgd_cat ) ) . '">' . esc_html( $sgd_cat->name ) . '</a>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape từng phần.
		?>
		<div class="row sgd-single__row">
			<article class="col large-8 sgd-single__main">
				<?php if ( has_excerpt() ) : ?>
					<p class="sgd-single__intro"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="sgd-single__img"><?php the_post_thumbnail( 'large', array( 'alt' => esc_attr( get_the_title() ), 'fetchpriority' => 'high' ) ); ?></figure>
				<?php endif; ?>
				<?php if ( count( $sgd_heads ) >= 3 ) : ?>
					<nav class="sgd-toc" aria-label="Mục lục">
						<p class="sgd-toc__title">Nội dung chính:</p>
						<ul>
							<?php foreach ( $sgd_heads as $sgd_t ) : ?>
								<li><a href="#<?php echo esc_attr( $sgd_t[0] ); ?>"><?php echo esc_html( $sgd_t[1] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>
				<div class="sgd-content entry-content"><?php echo $sgd_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nội dung bài viết đã qua the_content. ?></div>

				<div class="sgd-postcta">
					<div>
						<p class="sgd-postcta__title">Cần hỗ trợ thủ tục cho doanh nghiệp của bạn?</p>
						<p>Chuyên viên <?php echo esc_html( sgd_opt( 'company' ) ); ?> tư vấn miễn phí, báo giá trọn gói trong 15 phút.</p>
					</div>
					<p class="sgd-summary__btns">
						<a class="button sgd-btn" href="#dang-ky">Nhận báo giá</a>
						<a class="button sgd-btn is-outline" href="tel:<?php echo esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ); ?>"><?php echo sgd_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sgd_opt( 'hotline' ) ); ?></a>
					</p>
				</div>

				<?php $sgd_tags = get_the_tags(); ?>
				<?php if ( $sgd_tags ) : ?>
					<p class="sgd-tags"><span>Từ khoá:</span>
						<?php foreach ( $sgd_tags as $sgd_tg ) : ?>
							<a href="<?php echo esc_url( get_tag_link( $sgd_tg ) ); ?>"><?php echo esc_html( $sgd_tg->name ); ?></a>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
			</article>
			<aside class="col large-4 sgd-single__side">
				<?php get_template_part( 'template-parts/dichvu/sidebar', null, array( 'search' => true ) ); ?>
			</aside>
		</div>

		<?php
		$sgd_rel = get_posts(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => 3,
				'post__not_in'        => array( get_the_ID() ),
				'category__in'        => $sgd_cat ? array( $sgd_cat->term_id ) : array(),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		?>
		<?php if ( $sgd_rel ) : ?>
			<section class="sgd-related">
				<div class="container">
					<h2 class="sgd-htab"><span>Bài viết liên quan</span></h2>
					<div class="sgd-blog__grid sgd-blog__grid--3">
						<?php
						global $post;
						foreach ( $sgd_rel as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $post );
							get_template_part( 'template-parts/dichvu/post-card', null, array( 'tag' => 'h3' ) );
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
