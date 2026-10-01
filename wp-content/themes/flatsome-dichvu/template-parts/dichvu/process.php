<?php
/**
 * Các bước quy trình. $args['steps'] = [[bước, mô tả], ...].
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_steps = isset( $args['steps'] ) ? (array) $args['steps'] : array();
if ( ! $sgd_steps ) {
	return;
}
?>
<ol class="sgd-steps<?php echo empty( $args['row'] ) ? '' : ' sgd-steps--row'; ?>">
	<?php foreach ( $sgd_steps as $sgd_n => $sgd_s ) : ?>
		<li class="sgd-step">
			<span class="sgd-step__num"><?php echo esc_html( $sgd_n + 1 ); ?></span>
			<div>
				<p class="sgd-step__title"><?php echo esc_html( $sgd_s[0] ); ?></p>
				<?php if ( ! empty( $sgd_s[1] ) ) : ?>
					<p class="sgd-step__desc"><?php echo esc_html( $sgd_s[1] ); ?></p>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ol>
