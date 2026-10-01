<?php
/**
 * Trang danh sách: /dich-vu/ và /nhom-dich-vu/...
 * Cột trái: breadcrumb → H1 → giới thiệu → chuyển nhóm → lưới dịch vụ → nội dung SEO. Cột phải: form, dịch vụ nổi bật, bài viết.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_term   = is_tax( 'nhom_dich_vu' ) ? get_queried_object() : null;
$sgd_intro  = $sgd_term ? term_description( $sgd_term ) : wpautop( esc_html( sgd_opt( 'archive_intro' ) ) );
$sgd_bottom = $sgd_term ? '' : sgd_opt( 'archive_bottom' );
$sgd_groups = get_terms( array( 'taxonomy' => 'nhom_dich_vu', 'hide_empty' => true, 'parent' => 0 ) );
if ( $sgd_groups && ! is_wp_error( $sgd_groups ) ) {
	usort(
		$sgd_groups,
		function ( $x, $y ) {
			return (int) get_term_meta( $x->term_id, '_sgd_order', true ) - (int) get_term_meta( $y->term_id, '_sgd_order', true );
		}
	);
}
?>
<div id="content" class="sgd-listing">
	<div class="row sgd-single__row">
		<div class="col large-8">
			<?php sgd_breadcrumbs(); ?>
			<h1 class="sgd-single__title"><?php echo esc_html( sgd_listing_h1() ); ?></h1>
			<?php if ( trim( wp_strip_all_tags( (string) $sgd_intro ) ) ) : ?>
				<div class="sgd-single__intro"><?php echo wp_kses_post( $sgd_intro ); ?></div>
			<?php endif; ?>
			<?php if ( $sgd_groups && ! is_wp_error( $sgd_groups ) ) : ?>
				<nav class="sgd-tabs" aria-label="Nhóm dịch vụ">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'dich_vu' ) ); ?>"<?php echo $sgd_term ? '' : ' class="is-active" aria-current="page"'; ?>>Tất cả</a>
					<?php foreach ( $sgd_groups as $sgd_g ) : ?>
						<a href="<?php echo esc_url( get_term_link( $sgd_g ) ); ?>"<?php echo ( $sgd_term && $sgd_term->term_id === $sgd_g->term_id ) ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo esc_html( $sgd_g->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<div class="sgd-grid sgd-grid--2">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/dichvu/card', null, array( 'tag' => 'h2' ) );
					endwhile;
					?>
				</div>
				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 1,
						'prev_text' => '‹',
						'next_text' => '›',
						'class'     => 'sgd-pagination',
					)
				);
				?>
			<?php else : ?>
				<p class="sgd-empty">Chưa có dịch vụ trong mục này. <a href="<?php echo esc_url( get_post_type_archive_link( 'dich_vu' ) ); ?>">Xem tất cả dịch vụ</a> hoặc để lại thông tin để được tư vấn.</p>
			<?php endif; ?>

			<?php get_template_part( 'template-parts/dichvu/call-now' ); ?>

			<?php if ( $sgd_bottom && ! is_paged() ) : ?>
				<div class="sgd-bottom entry-content"><?php echo wp_kses_post( wpautop( $sgd_bottom ) ); ?></div>
			<?php endif; ?>
		</div>
		<aside class="col large-4 sgd-single__side">
			<?php get_template_part( 'template-parts/dichvu/sidebar' ); ?>
		</aside>
	</div>
</div>
