<?php
/**
 * Chi tiết dịch vụ: breadcrumb → H1 → mô tả ngắn → ô tóm tắt (giá tham khảo, thời gian, nút) → nội dung →
 * hạng mục → quy trình → FAQ hiển thị → bài kiến thức liên quan → form yêu cầu.
 * Giá chỉ hiển thị khi đã nhập (không tự đặt giá).
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$th_id       = get_the_ID();
	$th_f        = function ( $k ) use ( $th_id ) {
		return function_exists( 'th119_service_field' ) ? th119_service_field( $k, $th_id ) : (string) get_post_meta( $th_id, '_sgd_' . $k, true );
	};
	$th_includes = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $th_f( 'includes' ) ) ) );
	$th_steps    = function_exists( 'th119_pipe_rows' ) ? th119_pipe_rows( $th_f( 'process' ), 2 ) : array();
	$th_groups   = get_the_terms( $th_id, 'nhom_dich_vu' );
	$th_cat_link = $th_groups && ! is_wp_error( $th_groups ) ? '<a class="th-pagehead__cat" href="' . esc_url( get_term_link( $th_groups[0] ) ) . '">' . esc_html( $th_groups[0]->name ) . '</a>' : '';
	list( $th_html, $th_heads ) = th119t_toc( apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook lõi WP.

	// Bài kiến thức liên quan: bài chọn dịch vụ này, hoặc bài trong chuyên mục gắn với dịch vụ.
	$th_kb_posts = get_posts(
		array(
			'numberposts' => 4,
			'meta_key'    => '_th_service', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $th_id, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( count( $th_kb_posts ) < 4 ) {
		$th_kb_cats = array_filter( array_map( 'intval', (array) get_post_meta( $th_id, '_th_kb_cats', true ) ) );
		if ( $th_kb_cats ) {
			$th_kb_posts = array_merge( $th_kb_posts, get_posts( array( 'numberposts' => 4 - count( $th_kb_posts ), 'category__in' => $th_kb_cats, 'post__not_in' => wp_list_pluck( $th_kb_posts, 'ID' ) ) ) );
		}
	}
	$th_sub = $th_f( 'subtitle' );
	?>
	<div id="content" class="th-single th-service">
		<?php th119t_pagehead( get_the_title(), $th_sub ? '<p>' . esc_html( $th_sub ) . '</p>' : '', $th_cat_link ); ?>
		<div class="th-wrap th-layout">
			<article <?php post_class( 'th-article' ); ?>>
				<div class="th-svc-summary">
					<dl>
						<div><dt><?php echo th119t_ic( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Chi phí</dt><dd><?php echo esc_html( $th_f( 'price' ) ? $th_f( 'price' ) : 'Báo giá sau khi kiểm tra' ); ?></dd></div>
						<?php if ( $th_f( 'duration' ) ) : ?>
							<div><dt><?php echo th119t_ic( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Thời gian</dt><dd><?php echo esc_html( $th_f( 'duration' ) ); ?></dd></div>
						<?php endif; ?>
						<div><dt><?php echo th119t_ic( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Khu vực</dt><dd><?php echo esc_html( th119t_opt( 'area_served' ) ? th119t_opt( 'area_served' ) : th119t_opt( 'district' ) ); ?></dd></div>
					</dl>
					<?php echo th119t_sc( '[th119_cta show="call,zalo,booking"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="th-article__img"><?php the_post_thumbnail( 'large', array( 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?></figure>
				<?php endif; ?>
				<?php echo th119t_toc_html( $th_heads ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="th-content entry-content"><?php echo $th_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content. ?></div>

				<?php if ( $th_includes ) : ?>
					<section class="th-block"><h2 id="hang-muc">Hạng mục thực hiện</h2><ul class="th-checklist">
						<?php foreach ( $th_includes as $th_i ) : ?>
							<li><?php echo th119t_ic( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $th_i ); ?></li>
						<?php endforeach; ?>
					</ul></section>
				<?php endif; ?>

				<?php if ( $th_steps ) : ?>
					<section class="th-block"><h2 id="quy-trinh">Quy trình</h2><ol class="th-steps">
						<?php foreach ( $th_steps as $th_n => $th_s ) : ?>
							<li><span class="th-steps__n"><?php echo (int) $th_n + 1; ?></span><div><strong><?php echo esc_html( $th_s[0] ); ?></strong><p><?php echo esc_html( $th_s[1] ); ?></p></div></li>
						<?php endforeach; ?>
					</ol></section>
				<?php endif; ?>

				<?php echo th119t_faq_html( $th_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php if ( $th_kb_posts ) : ?>
					<section class="th-block"><?php echo th119t_posts_grid( $th_kb_posts, 'Hướng dẫn liên quan' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></section>
				<?php endif; ?>

				<section class="th-block" id="gui-yeu-cau">
					<?php echo th119t_sc( '[th119_support_form title="Gửi yêu cầu ' . esc_attr( get_the_title() ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</section>
			</article>
			<aside class="th-sidebar">
				<div class="th-sticky">
					<div class="th-side-card">
						<p class="th-side-card__title">Liên hệ <?php echo esc_html( th119t_opt( 'brand' ) ); ?></p>
						<?php echo th119t_sc( '[th119_contact]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo th119t_sc( '[th119_cta show="call,zalo,request"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<?php
					$th_others = get_posts( array( 'post_type' => 'dich_vu', 'numberposts' => 6, 'post__not_in' => array( $th_id ), 'orderby' => 'menu_order', 'order' => 'ASC' ) );
					if ( $th_others ) :
						?>
						<nav class="th-side-card"><p class="th-side-card__title">Dịch vụ khác</p><ul class="th-side-list">
							<?php foreach ( $th_others as $th_o ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $th_o ) ); ?>"><?php echo esc_html( get_the_title( $th_o ) ); ?></a></li>
							<?php endforeach; ?>
						</ul></nav>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</div>
	<?php
endwhile;
get_footer();
