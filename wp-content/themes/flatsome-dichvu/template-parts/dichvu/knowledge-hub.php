<?php
/**
 * Trang Kiến thức (trang 1) kiểu ketoananpha.vn/kien-thuc.html:
 * mỗi chuyên mục cha (Kiến thức kế toán, Kiến thức pháp lý…) là một khối gồm tiêu đề, đoạn giới thiệu
 * và lưới thẻ chủ đề (chuyên mục con); cuối trang là "Bài viết mới nhất" (1 bài lớn + danh sách 2 cột).
 * Chuyên mục cha hiện ở đây = chuyên mục cấp 1 có chuyên mục con; thứ tự theo term meta _sgd_order.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_order = function ( $terms ) {
	usort(
		$terms,
		function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, '_sgd_order', true ) <=> (int) get_term_meta( $b->term_id, '_sgd_order', true );
		}
	);
	return $terms;
};
$sgd_parents = array();
foreach ( get_categories( array( 'parent' => 0, 'hide_empty' => false ) ) as $sgd_p ) {
	$sgd_kids = get_categories( array( 'parent' => $sgd_p->term_id, 'hide_empty' => false ) );
	if ( $sgd_kids ) {
		$sgd_parents[] = array( $sgd_p, $sgd_order( $sgd_kids ) );
	}
}
usort(
	$sgd_parents,
	function ( $a, $b ) {
		return (int) get_term_meta( $a[0]->term_id, '_sgd_order', true ) <=> (int) get_term_meta( $b[0]->term_id, '_sgd_order', true );
	}
);
$sgd_latest = get_posts( array( 'posts_per_page' => 9, 'ignore_sticky_posts' => true ) );
$sgd_lead   = $sgd_latest ? array_shift( $sgd_latest ) : null;
$sgd_more   = (int) wp_count_posts()->publish > 9;
?>
<div id="content" class="sgd-blog sgd-kt">
	<?php echo sgd_pagehead( sgd_blog_title(), sgd_opt( 'blog_intro' ) ? '<p>' . esc_html( sgd_opt( 'blog_intro' ) ) . '</p>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape. ?>
	<div class="row sgd-kt__wrap"><div class="col large-12">
		<?php foreach ( $sgd_parents as $sgd_sec ) : ?>
			<?php list( $sgd_parent, $sgd_kids ) = $sgd_sec; ?>
			<section class="sgd-kt__sec" aria-labelledby="kt-<?php echo esc_attr( $sgd_parent->slug ); ?>">
				<h2 class="sgd-kt__title" id="kt-<?php echo esc_attr( $sgd_parent->slug ); ?>"><a href="<?php echo esc_url( get_category_link( $sgd_parent ) ); ?>"><?php echo esc_html( $sgd_parent->name ); ?></a></h2>
				<?php if ( $sgd_parent->description ) : ?>
					<p class="sgd-kt__intro"><?php echo esc_html( wp_strip_all_tags( $sgd_parent->description ) ); ?></p>
				<?php endif; ?>
				<div class="sgd-kt__grid">
					<?php foreach ( $sgd_kids as $sgd_k ) : ?>
						<?php $sgd_n = (int) $sgd_k->count; ?>
						<a class="sgd-kt__card<?php echo $sgd_n ? '' : ' is-empty'; ?>" href="<?php echo esc_url( get_category_link( $sgd_k ) ); ?>">
							<span class="sgd-kt__body">
								<span class="sgd-kt__name"><?php echo esc_html( $sgd_k->name ); ?></span>
								<span class="sgd-kt__count"><?php echo $sgd_n ? esc_html( $sgd_n . ' bài viết' ) : 'Đang cập nhật'; ?></span>
								<span class="sgd-kt__btn">Xem chi tiết</span>
							</span>
							<span class="sgd-kt__art" aria-hidden="true"><?php echo sgd_icon( get_term_meta( $sgd_k->term_id, '_sgd_icon', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG tĩnh. ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

		<?php if ( $sgd_lead ) : ?>
			<section class="sgd-kt__sec" aria-labelledby="kt-moi-nhat">
				<h2 class="sgd-kt__title" id="kt-moi-nhat">Bài viết mới nhất</h2>
				<div class="sgd-kt__latest">
					<article class="sgd-kt__lead">
						<a class="sgd-kt__leadimg" href="<?php echo esc_url( get_permalink( $sgd_lead ) ); ?>" tabindex="-1" aria-hidden="true">
							<?php if ( has_post_thumbnail( $sgd_lead ) ) : ?>
								<?php echo get_the_post_thumbnail( $sgd_lead, 'medium_large', array( 'loading' => 'lazy', 'alt' => esc_attr( get_the_title( $sgd_lead ) ) ) ); ?>
							<?php else : ?>
								<span class="sgd-post__noimg"><?php echo sgd_icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						</a>
						<h3 class="sgd-kt__leadtitle"><a href="<?php echo esc_url( get_permalink( $sgd_lead ) ); ?>"><?php echo esc_html( get_the_title( $sgd_lead ) ); ?></a></h3>
						<p class="sgd-kt__leadex"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $sgd_lead ) ), 26, '…' ) ); ?></p>
						<a class="sgd-kt__leadmore" href="<?php echo esc_url( get_permalink( $sgd_lead ) ); ?>">Xem chi tiết <span aria-hidden="true">»</span></a>
					</article>
					<ul class="sgd-kt__list">
						<?php foreach ( $sgd_latest as $sgd_post ) : ?>
							<li>
								<a class="sgd-kt__ltitle" href="<?php echo esc_url( get_permalink( $sgd_post ) ); ?>"><?php echo esc_html( get_the_title( $sgd_post ) ); ?></a>
								<a class="sgd-kt__lmore" href="<?php echo esc_url( get_permalink( $sgd_post ) ); ?>" tabindex="-1" aria-hidden="true">Xem thêm »</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php if ( $sgd_more ) : ?>
					<p class="sgd-kt__all"><a class="button sgd-btn is-outline" href="<?php echo esc_url( get_pagenum_link( 2 ) ); ?>">Xem các bài viết trước ›</a></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</div></div>
</div>
