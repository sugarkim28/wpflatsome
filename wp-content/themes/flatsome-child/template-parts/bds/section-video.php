<?php
/**
 * Video YouTube (nạp khi bấm để trang nhẹ).
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_vid = bds_youtube_id( bds_opt( 'video_url' ) );
$bds_mp4 = $bds_vid ? '' : bds_video_mp4_url();
if ( ! bds_opt( 'show_video' ) || ( ! $bds_vid && ! $bds_mp4 ) ) {
	return;
}
?>
<section id="video" class="bds-section bds-section--dark">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'video_title' ) ); ?></h2>
		</div>
		<?php if ( $bds_mp4 ) : ?>
			<div class="bds-video">
				<video controls playsinline preload="none" poster="<?php echo esc_url( bds_img_url( bds_image_ref( 'hero_image' ), 'full' ) ); ?>">
					<source src="<?php echo esc_url( $bds_mp4 ); ?>" type="video/mp4">
				</video>
			</div>
		<?php else : ?>
		<button type="button" class="bds-video" data-bds-video="<?php echo esc_attr( $bds_vid ); ?>" aria-label="Phát video"
			style="background-image:url(https://i.ytimg.com/vi/<?php echo esc_attr( $bds_vid ); ?>/hqdefault.jpg)">
			<span class="bds-video__play" aria-hidden="true"></span>
		</button>
		<?php endif; ?>
	</div>
</section>
