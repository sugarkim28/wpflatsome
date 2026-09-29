<?php
/**
 * Lý do sở hữu.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_items = bds_lines( bds_opt( 'reasons' ) );
if ( ! bds_opt( 'show_reasons' ) || ! $bds_items ) {
	return;
}
?>
<section id="ly-do" class="bds-section bds-section--dark">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'reasons_title' ) ); ?></h2>
		</div>
		<div class="row bds-grid">
			<?php foreach ( $bds_items as $bds_i => $bds_item ) : ?>
				<div class="col large-4 medium-6 small-12">
					<div class="bds-reason">
						<span class="bds-reason__num"><?php echo esc_html( (string) ( $bds_i + 1 ) ); ?></span>
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
