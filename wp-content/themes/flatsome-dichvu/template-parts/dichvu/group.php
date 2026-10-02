<?php
/**
 * Trang nhóm dịch vụ (/nhom-dich-vu/...) – dạng bài viết chuyên mục như tanthanhthinh.com:
 * dải tiêu đề (H1) → ảnh nhóm → mục lục → bài viết của nhóm (bảng giá nằm trong bài)
 * → gọi ngay → bài viết liên quan. Cột phải: form, dịch vụ nổi bật, bài viết mới.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_term     = get_queried_object();
$sgd_services = sgd_group_services( $sgd_term );
list( $sgd_article, $sgd_heads ) = sgd_content_headings( sgd_group_article( $sgd_term ) );
$sgd_img   = get_term_meta( $sgd_term->term_id, '_sgd_image', true );
$sgd_price = sgd_min_price_label( $sgd_services );
$sgd_intro = trim( wp_strip_all_tags( term_description( $sgd_term ) ) );
$sgd_posts = sgd_group_posts( array( $sgd_term ), 3 );
?>
<div id="content" class="sgd-listing sgd-group-page">
	<?php echo sgd_pagehead( sgd_listing_h1(), $sgd_intro ? '<p>' . esc_html( $sgd_intro ) . '</p>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape. ?>
	<div class="row sgd-single__row">
		<div class="col large-8 sgd-single__main">

			<figure class="sgd-group__visual<?php echo $sgd_img ? ' has-img' : ''; ?>">
				<?php if ( $sgd_img ) : ?>
					<img src="<?php echo esc_url( $sgd_img ); ?>" alt="<?php echo esc_attr( sgd_listing_h1() ); ?>" width="1100" height="560" fetchpriority="high">
				<?php else : ?>
					<span class="sgd-gsec__ico"><?php echo sgd_icon( get_term_meta( $sgd_term->term_id, '_sgd_icon', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="sgd-gsec__vtitle"><?php echo esc_html( sgd_listing_h1() ); ?></span>
					<span class="sgd-gsec__vmeta"><?php echo esc_html( count( $sgd_services ) . ' dịch vụ' ); ?><?php echo $sgd_price ? ' · <b>' . esc_html( $sgd_price ) . '</b>' : ''; ?></span>
				<?php endif; ?>
				<span class="sgd-gsec__bar"><span><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span><span><?php echo sgd_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Hotline: <?php echo esc_html( sgd_opt( 'hotline' ) ); ?></span></span>
			</figure>


			<?php if ( count( $sgd_heads ) >= 2 ) : ?>
				<nav class="sgd-toc" aria-label="Mục lục">
					<p class="sgd-toc__title">Nội dung chính:</p>
					<ul>
						<?php foreach ( $sgd_heads as $sgd_t ) : ?>
							<li><a href="#<?php echo esc_attr( $sgd_t[0] ); ?>"><?php echo esc_html( $sgd_t[1] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<?php if ( $sgd_article ) : ?>
				<div class="sgd-content entry-content"><?php echo $sgd_article; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nội dung quản trị viên nhập (đã lọc wp_kses_post khi lưu). ?></div>
			<?php endif; ?>

			<?php get_template_part( 'template-parts/dichvu/call-now' ); ?>

			<?php if ( $sgd_posts ) : ?>
				<section class="sgd-related-posts">
					<h2 class="sgd-htab"><span>Bài viết liên quan</span></h2>
					<div class="sgd-blog__grid sgd-blog__grid--3">
						<?php
						global $post;
						foreach ( $sgd_posts as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $post );
							get_template_part( 'template-parts/dichvu/post-card', null, array( 'tag' => 'h3' ) );
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>
		</div>
		<aside class="col large-4 sgd-single__side">
			<?php get_template_part( 'template-parts/dichvu/sidebar' ); ?>
		</aside>
	</div>
</div>
