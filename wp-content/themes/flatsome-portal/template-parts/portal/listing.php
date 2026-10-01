<?php
/**
 * Trang danh sách: /du-an/, /khu-vuc/..., /chu-dau-tu/..., /loai-hinh/...
 * H1 → giới thiệu (mô tả mục) → bộ lọc → lưới dự án (H2) → phân trang → nội dung SEO cuối trang → form.
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

$sgp_term   = is_tax() ? get_queried_object() : null;
$sgp_intro  = $sgp_term ? term_description( $sgp_term ) : wpautop( esc_html( sgp_opt( 'archive_intro' ) ) );
$sgp_bottom = $sgp_term ? get_term_meta( $sgp_term->term_id, '_sgp_bottom', true ) : sgp_opt( 'archive_bottom' );
$sgp_img    = $sgp_term ? absint( get_term_meta( $sgp_term->term_id, '_sgp_image', true ) ) : 0;
global $wp_query;
?>
<div id="content" class="sgp-listing">
	<header class="sgp-lhead">
		<div class="container">
			<?php sgp_breadcrumbs(); ?>
			<div class="sgp-lhead__row">
				<?php if ( $sgp_img && $sgp_term && 'chu_dau_tu' === $sgp_term->taxonomy ) : ?>
					<span class="sgp-lhead__logo"><?php echo wp_get_attachment_image( $sgp_img, 'medium', false, array( 'alt' => esc_attr( $sgp_term->name ) ) ); ?></span>
				<?php endif; ?>
				<div>
					<h1 class="sgp-lhead__title"><?php echo esc_html( sgp_listing_h1() ); ?></h1>
					<p class="sgp-lhead__count"><?php echo esc_html( sprintf( '%d dự án', (int) $wp_query->found_posts ) ); ?></p>
				</div>
			</div>
			<?php if ( trim( wp_strip_all_tags( (string) $sgp_intro ) ) ) : ?>
				<div class="sgp-lhead__intro"><?php echo wp_kses_post( $sgp_intro ); ?></div>
			<?php endif; ?>
			<?php get_template_part( 'template-parts/portal/filter' ); ?>
		</div>
	</header>

	<div class="container sgp-listing__body">
		<?php if ( have_posts() ) : ?>
			<div class="sgp-grid sgp-grid--3">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/portal/card', null, array( 'tag' => 'h2' ) );
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => '‹',
					'next_text' => '›',
					'class'     => 'sgp-pagination',
				)
			);
			?>
		<?php else : ?>
			<p class="sgp-empty">Chưa có dự án phù hợp. <a href="<?php echo esc_url( get_post_type_archive_link( 'du_an' ) ); ?>">Xem tất cả dự án</a> hoặc để lại thông tin để chuyên viên tư vấn.</p>
		<?php endif; ?>

		<?php if ( $sgp_bottom && ! sgp_active_filters() && ! is_paged() ) : ?>
			<div class="sgp-bottom entry-content"><?php echo wp_kses_post( wpautop( $sgp_bottom ) ); ?></div>
		<?php endif; ?>
	</div>

	<section class="sgp-cta" id="dang-ky">
		<div class="container sgp-cta__row">
			<div class="sgp-cta__text">
				<h2>Cần tư vấn chọn dự án phù hợp?</h2>
				<p>Để lại thông tin, chuyên viên gửi bảng giá, chính sách và so sánh các dự án theo ngân sách của bạn.</p>
				<div class="sgp-callbtns"><?php echo sgp_call_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm. ?></div>
			</div>
			<div class="sgp-card-form"><?php get_template_part( 'template-parts/portal/lead-form', null, array( 'source' => 'Trang danh sách', 'perks' => false ) ); ?></div>
		</div>
	</section>
</div>
