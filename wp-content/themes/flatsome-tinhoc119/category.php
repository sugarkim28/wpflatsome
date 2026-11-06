<?php
/**
 * Chuyên mục kiến thức: giới thiệu → chuyên mục con → bài nổi bật → bài mới nhất (phân trang) → dịch vụ liên quan → hỗ trợ.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
$th_term  = get_queried_object();
$th_paged = max( 1, (int) get_query_var( 'paged' ) );
$th_intro = get_term_meta( $th_term->term_id, 'th_intro', true );
$th_desc  = term_description( $th_term );
$th_kids  = get_terms( array( 'taxonomy' => 'category', 'parent' => $th_term->term_id, 'hide_empty' => false, 'orderby' => 'meta_value_num', 'meta_key' => 'th_order' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
if ( is_wp_error( $th_kids ) || ! $th_kids ) {
	$th_kids = get_terms( array( 'taxonomy' => 'category', 'parent' => $th_term->term_id, 'hide_empty' => false ) );
}
?>
<div id="content" class="th-archive th-archive--cat">
	<?php th119t_pagehead( single_cat_title( '', false ), 1 === $th_paged ? $th_desc : '' ); ?>
	<div class="th-wrap">
		<?php if ( 1 === $th_paged ) : ?>
			<?php if ( $th_intro ) : ?>
				<div class="th-cat-intro entry-content"><?php echo wp_kses_post( wpautop( $th_intro ) ); ?></div>
			<?php endif; ?>

			<?php if ( ! is_wp_error( $th_kids ) && $th_kids ) : ?>
				<h2 class="th-section-title">Chủ đề trong <?php echo esc_html( $th_term->name ); ?></h2>
				<div class="th-subcats">
					<?php foreach ( $th_kids as $th_k ) : ?>
						<a class="th-subcat" href="<?php echo esc_url( get_term_link( $th_k ) ); ?>">
							<span class="th-subcat__icon"><?php echo th119t_ic( get_term_meta( $th_k->term_id, 'th_icon', true ) ? get_term_meta( $th_k->term_id, 'th_icon', true ) : ( get_term_meta( $th_term->term_id, 'th_icon', true ) ? get_term_meta( $th_term->term_id, 'th_icon', true ) : 'book' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><strong><?php echo esc_html( $th_k->name ); ?></strong><small><?php echo $th_k->count ? esc_html( $th_k->count . ' bài' ) : 'Đang cập nhật'; ?></small></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php
			$th_feat_ids = array_filter( array_map( 'absint', explode( ',', (string) get_term_meta( $th_term->term_id, 'th_featured', true ) ) ) );
			$th_feat     = $th_feat_ids
				? get_posts( array( 'post__in' => $th_feat_ids, 'orderby' => 'post__in', 'numberposts' => 3 ) )
				: get_posts( array( 'cat' => $th_term->term_id, 'numberposts' => 3, 'meta_key' => '_th_featured', 'meta_value' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			if ( $th_feat ) {
				echo '<div class="th-block">' . th119t_posts_grid( $th_feat, 'Nên đọc trước' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		<?php endif; ?>

		<h2 class="th-section-title"><?php echo 1 === $th_paged ? 'Bài mới nhất' : esc_html( 'Bài viết – trang ' . $th_paged ); ?></h2>
		<?php if ( have_posts() ) : ?>
			<div class="th-posts">
				<?php
				while ( have_posts() ) :
					the_post();
					echo th119t_card( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endwhile;
				?>
			</div>
			<?php th119t_pagination(); ?>
		<?php else : ?>
			<p class="th-empty">Chuyên mục đang được cập nhật bài viết. Bạn có thể <a href="<?php echo esc_url( th119t_page_url( 'gui-yeu-cau-ho-tro' ) ); ?>">gửi câu hỏi cho kỹ thuật viên</a>.</p>
		<?php endif; ?>

		<?php
		if ( 1 === $th_paged && function_exists( 'th119_category_services' ) ) {
			$th_sids = th119_category_services( $th_term->term_id );
			if ( $th_sids ) {
				echo th119t_services_box( get_posts( array( 'post_type' => 'dich_vu', 'post__in' => array_slice( $th_sids, 0, 3 ), 'orderby' => 'post__in', 'numberposts' => 3 ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		echo th119t_help_box( 'Không tự xử lý được?' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</div>
<?php
get_footer();
