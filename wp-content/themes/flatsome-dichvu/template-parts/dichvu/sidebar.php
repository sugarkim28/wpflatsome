<?php
/**
 * Cột phải: form tư vấn, dịch vụ liên quan, bảng giá nhanh, bài viết tham khảo.
 * $args: service (ID dịch vụ hiện tại, 0 nếu không có), form (bool).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_sid  = isset( $args['service'] ) ? absint( $args['service'] ) : 0;
$sgd_form = ! isset( $args['form'] ) || $args['form'];
$sgd_srch = ! empty( $args['search'] );
$sgd_rel  = $sgd_sid ? sgd_related_services( $sgd_sid, 8 ) : get_posts( array( 'post_type' => 'dich_vu', 'posts_per_page' => 8, 'orderby' => array( 'menu_order' => 'ASC' ), 'meta_key' => '_sgd_featured', 'meta_value' => '1', 'no_found_rows' => true ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
$sgd_news = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 5, 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
?>
<div class="sgd-side">
	<?php if ( $sgd_srch ) : ?>
		<form class="sgd-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="sgd-s">Tìm bài viết</label>
			<input id="sgd-s" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Tìm thủ tục, mẫu hồ sơ, thuế…">
			<input type="hidden" name="post_type" value="post">
			<button type="submit" aria-label="Tìm kiếm"><?php echo sgd_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</form>
	<?php endif; ?>
	<?php if ( $sgd_form ) : ?>
		<div class="sgd-wbox sgd-wbox--form" id="dang-ky">
			<p class="sgd-wbox__title">Nhận tư vấn miễn phí</p>
			<div class="sgd-wbox__body">
				<?php
				get_template_part(
					'template-parts/dichvu/lead-form',
					null,
					array(
						'title'   => '',
						'button'  => 'Gửi yêu cầu',
						'source'  => $sgd_sid ? 'Trang dịch vụ' : 'Cột phải',
						'service' => $sgd_sid,
						'perks'   => false,
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $sgd_rel ) : ?>
		<div class="sgd-wbox">
			<p class="sgd-wbox__title"><?php echo $sgd_sid ? 'Dịch vụ liên quan' : 'Dịch vụ nổi bật'; ?></p>
			<ul class="sgd-wbox__links">
				<?php foreach ( $sgd_rel as $sgd_p ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $sgd_p ) ); ?>"><?php echo esc_html( get_the_title( $sgd_p ) ); ?></a>
						<?php if ( sgd_meta( 'price', $sgd_p->ID ) ) : ?><span><?php echo esc_html( sgd_meta( 'price', $sgd_p->ID ) ); ?></span><?php endif; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $sgd_news ) : ?>
		<div class="sgd-wbox">
			<p class="sgd-wbox__title"><?php echo is_singular( 'post' ) || is_home() || is_archive() || is_search() ? 'Bài viết mới' : 'Tham khảo thêm'; ?></p>
			<ul class="sgd-wbox__posts">
				<?php foreach ( $sgd_news as $sgd_i => $sgd_p ) : ?>
					<li<?php echo 0 === $sgd_i ? ' class="is-first"' : ''; ?>>
						<?php if ( 0 === $sgd_i && has_post_thumbnail( $sgd_p ) ) : ?>
							<a class="sgd-wbox__thumb" href="<?php echo esc_url( get_permalink( $sgd_p ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo get_the_post_thumbnail( $sgd_p, 'medium_large', array( 'loading' => 'lazy' ) ); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( get_permalink( $sgd_p ) ); ?>"><?php echo esc_html( get_the_title( $sgd_p ) ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</div>
