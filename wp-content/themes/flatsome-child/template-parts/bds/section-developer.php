<?php
/**
 * Chủ đầu tư.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

if ( ! bds_opt( 'show_developer' ) ) {
	return;
}
$bds_img    = bds_image_ref( 'developer_image' );
$bds_points = bds_lines( bds_opt( 'developer_points' ) );
?>
<section id="chu-dau-tu" class="bds-section">
	<div class="container">
		<div class="row row-large align-middle">
			<?php if ( $bds_img ) : ?>
				<div class="col large-5 medium-12 small-12">
					<div class="bds-media">
						<?php echo bds_img_tag( $bds_img, 'large', bds_opt( 'developer_title' ) ); ?>
					</div>
				</div>
			<?php endif; ?>
			<div class="col <?php echo $bds_img ? 'large-7' : 'large-12'; ?> medium-12 small-12">
				<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'developer_title' ) ); ?></h2>
				<p class="bds-lead"><?php echo nl2br( esc_html( bds_opt( 'developer_text' ) ) ); ?></p>
				<?php if ( $bds_points ) : ?>
					<div class="row row-small bds-dev-points">
						<?php foreach ( $bds_points as $bds_point ) : ?>
							<div class="col medium-6 small-12">
								<div class="bds-dev-point">
									<strong><?php echo esc_html( $bds_point[0] ); ?></strong>
									<span><?php echo esc_html( $bds_point[1] ); ?></span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
