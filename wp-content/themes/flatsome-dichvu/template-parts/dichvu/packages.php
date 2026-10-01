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
				<span class="sgd-plan__tag">Được chọn nhiều</span>
			<?php endif; ?>
			<p class="sgd-plan__name"><?php echo esc_html( $sgd_p['name'] ); ?></p>
			<p class="sgd-plan__price"><?php echo esc_html( $sgd_p['price'] ? $sgd_p['price'] : 'Liên hệ' ); ?></p>
			<?php if ( $sgd_p['items'] ) : ?>
				<ul class="sgd-check">
					<?php foreach ( $sgd_p['items'] as $sgd_i ) : ?>
						<li><?php echo esc_html( $sgd_i ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<a class="button sgd-btn<?php echo $sgd_p['hot'] ? '' : ' is-outline'; ?> expand" href="#dang-ky" data-sgd-package="<?php echo esc_attr( get_the_title( $sgd_id ) . ' – gói ' . $sgd_p['name'] ); ?>">Chọn gói này</a>
		</div>
	<?php endforeach; ?>
</div>
