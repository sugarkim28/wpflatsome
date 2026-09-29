<?php
/**
 * Tiện ích.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

if ( ! bds_opt( 'show_amenities' ) ) {
	return;
}
$bds_img = absint( bds_opt( 'amenities_image' ) );
?>
<section id="tien-ich" class="bds-section">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'amenities_title' ) ); ?></h2>
		</div>
		<?php if ( $bds_img ) : ?>
			<a class="image-lightbox lightbox-gallery bds-media bds-media--wide" href="<?php echo esc_url( bds_img_url( $bds_img, 'full' ) ); ?>">
				<?php echo wp_get_attachment_image( $bds_img, 'full', false, array( 'loading' => 'lazy' ) ); ?>
			</a>
		<?php endif; ?>
		<div class="row bds-grid">
			<?php foreach ( bds_lines( bds_opt( 'amenities' ) ) as $bds_i => $bds_item ) : ?>
				<div class="col large-4 medium-6 small-12">
					<div class="bds-feature">
						<span class="bds-feature__num"><?php echo esc_html( str_pad( (string) ( $bds_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<h3><?php echo esc_html( $bds_item[0] ); ?></h3>
						<?php if ( $bds_item[1] ) : ?>
							<p><?php echo esc_html( $bds_item[1] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
