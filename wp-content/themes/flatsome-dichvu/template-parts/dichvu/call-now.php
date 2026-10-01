<?php
/**
 * Khối "GỌI NGAY" – các nút hotline theo khu vực (đặt sau bảng giá, giữa bài).
 * $args: title (mặc định "Gọi ngay").
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_lines = sgd_hotlines();
if ( ! $sgd_lines ) {
	return;
}
?>
<div class="sgd-callnow">
	<p class="sgd-callnow__title"><?php echo esc_html( isset( $args['title'] ) ? $args['title'] : 'Gọi ngay' ); ?></p>
	<div class="sgd-callnow__list">
		<?php foreach ( $sgd_lines as $sgd_l ) : ?>
			<a class="sgd-pill" href="tel:<?php echo esc_attr( sgd_tel( $sgd_l[1] ) ); ?>"><small><?php echo esc_html( $sgd_l[0] ); ?></small><strong><?php echo esc_html( $sgd_l[1] ); ?></strong></a>
		<?php endforeach; ?>
	</div>
</div>
