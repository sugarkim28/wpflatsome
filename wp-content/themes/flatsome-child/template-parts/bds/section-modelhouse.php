<?php
/**
 * Nhà mẫu: tab theo loại căn, lưới ảnh bấm để xem lớn.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_groups = bds_modelhouse_groups();
if ( ! bds_opt( 'show_modelhouse' ) || ! $bds_groups ) {
	return;
}
?>
<section id="nha-mau" class="bds-section">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'modelhouse_title' ) ); ?></h2>
			<?php if ( bds_opt( 'modelhouse_text' ) ) : ?>
				<p class="bds-lead"><?php echo esc_html( bds_opt( 'modelhouse_text' ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="bds-tabs" role="tablist" aria-label="<?php echo esc_attr( bds_opt( 'modelhouse_title' ) ); ?>">
			<?php foreach ( $bds_groups as $bds_i => $bds_group ) : ?>
				<button type="button" class="bds-tabs__btn<?php echo 0 === $bds_i ? ' is-active' : ''; ?>" role="tab"
					id="bds-tab-<?php echo esc_attr( $bds_group['slug'] ); ?>"
					aria-controls="bds-panel-<?php echo esc_attr( $bds_group['slug'] ); ?>"
					aria-selected="<?php echo 0 === $bds_i ? 'true' : 'false'; ?>">
					<?php echo esc_html( $bds_group['label'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<?php foreach ( $bds_groups as $bds_i => $bds_group ) : ?>
			<?php $bds_total = count( $bds_group['images'] ); ?>
			<div class="bds-tabs__panel" role="tabpanel" id="bds-panel-<?php echo esc_attr( $bds_group['slug'] ); ?>"
				aria-labelledby="bds-tab-<?php echo esc_attr( $bds_group['slug'] ); ?>"<?php echo 0 === $bds_i ? '' : ' hidden'; ?>>
				<div class="bds-mh-grid">
					<?php foreach ( $bds_group['images'] as $bds_n => $bds_img ) : ?>
						<?php $bds_caption = sprintf( 'Nhà mẫu %1$s (%2$d/%3$d)', $bds_group['label'], $bds_n + 1, $bds_total ); ?>
						<a class="image-lightbox lightbox-gallery bds-mh-grid__item<?php echo 0 === $bds_n ? ' is-cover' : ''; ?>" href="<?php echo esc_url( $bds_img['full'] ); ?>" title="<?php echo esc_attr( $bds_caption ); ?>">
							<img src="<?php echo esc_url( $bds_img['thumb'] ); ?>" alt="<?php echo esc_attr( $bds_caption ); ?>" loading="lazy" decoding="async">
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="text-center">
			<a href="#dang-ky" class="button bds-btn bds-btn--accent">Đăng ký tham quan nhà mẫu</a>
		</div>
	</div>
</section>
