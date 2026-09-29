<?php
/**
 * Chính sách bán hàng.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_items = bds_lines( bds_opt( 'pricing_items' ) );
if ( ! bds_opt( 'show_pricing' ) || ! $bds_items ) {
	return;
}
?>
<section id="chinh-sach" class="bds-section bds-section--alt">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'pricing_title' ) ); ?></h2>
		</div>
		<div class="row bds-grid">
			<?php foreach ( $bds_items as $bds_item ) : ?>
				<div class="col large-4 medium-6 small-12">
					<div class="bds-card bds-policy">
						<i class="icon-checkmark" aria-hidden="true"></i>
						<h3><?php echo esc_html( $bds_item[0] ); ?></h3>
						<?php if ( $bds_item[1] ) : ?>
							<p><?php echo esc_html( $bds_item[1] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="text-center">
			<a href="#dang-ky" class="button bds-btn bds-btn--accent">Nhận chính sách chi tiết</a>
		</div>
	</div>
</section>
