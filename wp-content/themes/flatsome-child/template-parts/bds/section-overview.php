<?php
/**
 * Tổng quan dự án.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

if ( ! bds_opt( 'show_overview' ) ) {
	return;
}
$bds_img = absint( bds_opt( 'overview_image' ) );
?>
<section id="tong-quan" class="bds-section">
	<span id="gioi-thieu" class="bds-anchor" aria-hidden="true"></span>
	<div class="container">
		<div class="row row-large align-middle">
			<div class="col <?php echo $bds_img ? 'large-6' : 'large-12'; ?> medium-12 small-12">
				<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'overview_title' ) ); ?></h2>
				<p class="bds-lead"><?php echo nl2br( esc_html( bds_opt( 'overview_text' ) ) ); ?></p>
				<?php $bds_facts = bds_lines( bds_opt( 'overview_facts' ) ); ?>
				<?php if ( $bds_facts ) : ?>
					<dl class="bds-facts">
						<?php foreach ( $bds_facts as $bds_fact ) : ?>
							<div class="bds-facts__row">
								<dt><?php echo esc_html( $bds_fact[0] ); ?></dt>
								<dd><?php echo esc_html( $bds_fact[1] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>
			<?php if ( $bds_img ) : ?>
				<div class="col large-6 medium-12 small-12">
					<a class="image-lightbox lightbox-gallery bds-media" href="<?php echo esc_url( bds_img_url( $bds_img, 'full' ) ); ?>">
						<?php echo wp_get_attachment_image( $bds_img, 'large', false, array( 'loading' => 'lazy' ) ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
