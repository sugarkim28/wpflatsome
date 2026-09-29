<?php
/**
 * Mặt bằng.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_plans = bds_floorplans();
if ( ! bds_opt( 'show_floorplans' ) || ! $bds_plans ) {
	return;
}
$bds_col = count( $bds_plans ) >= 4 ? 'large-3' : ( 3 === count( $bds_plans ) ? 'large-4' : 'large-6' );
?>
<section id="mat-bang" class="bds-section bds-section--alt">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'floorplans_title' ) ); ?></h2>
		</div>
		<div class="row bds-grid">
			<?php foreach ( $bds_plans as $bds_plan ) : ?>
				<div class="col <?php echo esc_attr( $bds_col ); ?> medium-6 small-12">
					<div class="bds-card bds-plan">
						<?php if ( $bds_plan['image'] ) : ?>
							<a class="image-lightbox lightbox-gallery bds-plan__img" href="<?php echo esc_url( bds_img_url( $bds_plan['image'], 'full' ) ); ?>" title="<?php echo esc_attr( $bds_plan['title'] ); ?>">
								<?php echo bds_img_tag( $bds_plan['image'], 'medium_large', $bds_plan['title'] ); ?>
							</a>
						<?php endif; ?>
						<h3><?php echo esc_html( $bds_plan['title'] ); ?></h3>
						<?php if ( $bds_plan['desc'] ) : ?>
							<p><?php echo esc_html( $bds_plan['desc'] ); ?></p>
						<?php endif; ?>
						<a href="#dang-ky" class="bds-link">Nhận mặt bằng chi tiết →</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
