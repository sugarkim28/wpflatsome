<?php
/**
 * Quy trình dạng timeline. $args['steps'] = [[bước, mô tả, thời gian (tuỳ chọn)], ...];
 * $args['row'] = true → timeline ngang (trang chủ), mặc định timeline dọc (trang dịch vụ, bài viết).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_steps = isset( $args['steps'] ) ? (array) $args['steps'] : array();
if ( ! $sgd_steps ) {
	return;
}
$sgd_h = ! empty( $args['row'] );
?>
<ol class="sgd-tl <?php echo $sgd_h ? 'sgd-tl--h' : 'sgd-tl--v'; ?>" style="--n:<?php echo esc_attr( count( $sgd_steps ) ); ?>">
	<?php foreach ( $sgd_steps as $sgd_n => $sgd_s ) : ?>
		<li class="sgd-tl__item">
			<span class="sgd-tl__dot" aria-hidden="true"><?php echo esc_html( $sgd_n + 1 ); ?></span>
			<div class="sgd-tl__body">
				<?php if ( ! empty( $sgd_s[2] ) ) : ?>
					<span class="sgd-tl__time"><?php echo sgd_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $sgd_s[2] ); ?></span>
				<?php endif; ?>
				<p class="sgd-tl__title"><span class="screen-reader-text">Bước <?php echo esc_html( $sgd_n + 1 ); ?>: </span><?php echo esc_html( $sgd_s[0] ); ?></p>
				<?php if ( ! empty( $sgd_s[1] ) ) : ?>
					<p class="sgd-tl__desc"><?php echo esc_html( $sgd_s[1] ); ?></p>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ol>
