<?php
/**
 * Thư viện ảnh.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_ids = bds_gallery_ids();
if ( ! bds_opt( 'show_gallery' ) || ! $bds_ids ) {
	return;
}
?>
<section id="hinh-anh" class="bds-section">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'gallery_title' ) ); ?></h2>
		</div>
		<div class="row row-small bds-gallery">
			<?php foreach ( $bds_ids as $bds_id ) : ?>
				<div class="col large-3 medium-4 small-6">
					<a class="image-lightbox lightbox-gallery bds-gallery__item" href="<?php echo esc_url( bds_img_url( $bds_id, 'full' ) ); ?>" title="<?php echo esc_attr( bds_img_caption( $bds_id, bds_opt( 'hero_title' ) ) ); ?>">
						<?php echo bds_img_tag( $bds_id, 'medium_large', bds_opt( 'gallery_title' ) ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
