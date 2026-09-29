<?php
/**
 * Vị trí.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

if ( ! bds_opt( 'show_location' ) ) {
	return;
}
$bds_img = absint( bds_opt( 'location_image' ) );
$bds_map = bds_opt( 'location_map' );
?>
<section id="vi-tri" class="bds-section bds-section--alt">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'location_title' ) ); ?></h2>
			<p class="bds-lead"><?php echo nl2br( esc_html( bds_opt( 'location_text' ) ) ); ?></p>
		</div>
		<div class="row row-large align-middle">
			<div class="col large-5 medium-12 small-12">
				<ul class="bds-connect">
					<?php foreach ( bds_lines( bds_opt( 'location_points' ) ) as $bds_point ) : ?>
						<li>
							<strong><?php echo esc_html( $bds_point[0] ); ?></strong>
							<span><?php echo esc_html( $bds_point[1] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="col large-7 medium-12 small-12">
				<?php if ( $bds_img ) : ?>
					<a class="image-lightbox lightbox-gallery bds-media" href="<?php echo esc_url( bds_img_url( $bds_img, 'full' ) ); ?>">
						<?php echo wp_get_attachment_image( $bds_img, 'large', false, array( 'loading' => 'lazy' ) ); ?>
					</a>
				<?php elseif ( $bds_map ) : ?>
					<div class="bds-map">
						<iframe src="<?php echo esc_url( $bds_map ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="<?php esc_attr_e( 'Bản đồ vị trí', 'flatsome' ); ?>"></iframe>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
