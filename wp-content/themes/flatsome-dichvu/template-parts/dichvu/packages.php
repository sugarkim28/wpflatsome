<?php
/**
 * Bảng giá theo gói. $args['id'] = ID dịch vụ.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_id   = isset( $args['id'] ) ? absint( $args['id'] ) : get_the_ID();
$sgd_pkgs = sgd_packages( $sgd_id );
if ( ! $sgd_pkgs ) {
	return;
}
?>
<div class="sgd-pricing sgd-pricing--<?php echo esc_attr( min( 4, count( $sgd_pkgs ) ) ); ?>">
	<?php foreach ( $sgd_pkgs as $sgd_p ) : ?>
		<div class="sgd-plan<?php echo $sgd_p['hot'] ? ' is-hot' : ''; ?>">
			<?php if ( $sgd_p['hot'] ) : ?>
				<span class="sgd-plan__tag">Đăng ký nhiều</span>
			<?php endif; ?>
			<p class="sgd-plan__name"><?php echo esc_html( $sgd_p['name'] ); ?></p>
			<p class="sgd-plan__price"><?php echo esc_html( $sgd_p['price'] ? $sgd_p['price'] : 'Liên hệ' ); ?>
				<?php if ( $sgd_p['save'] ) : ?>
					<small>Tiết kiệm <b><?php echo esc_html( $sgd_p['save'] ); ?></b></small>
				<?php endif; ?>
			</p>
			<?php if ( $sgd_p['items'] ) : ?>
				<ul class="sgd-check">
					<?php foreach ( $sgd_p['items'] as $sgd_i ) : ?>
						<li><?php echo esc_html( $sgd_i ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $sgd_p['combo'] ) : ?>
				<?php $sgd_c = preg_split( '/\s*(?=\()/', $sgd_p['combo'], 2 ); ?>
				<p class="sgd-plan__combo">Còn <strong><?php echo esc_html( $sgd_c[0] ); ?></strong><?php echo isset( $sgd_c[1] ) ? '<small>' . esc_html( $sgd_c[1] ) . '</small>' : ''; ?></p>
			<?php endif; ?>
			<a class="button sgd-btn<?php echo $sgd_p['hot'] ? '' : ' is-outline'; ?> expand" href="#dang-ky" data-sgd-package="<?php echo esc_attr( get_the_title( $sgd_id ) . ' – gói ' . $sgd_p['name'] ); ?>" data-sgd-price="<?php echo esc_attr( $sgd_p['price'] ); ?>">Chọn gói này</a>
		</div>
	<?php endforeach; ?>
</div>
