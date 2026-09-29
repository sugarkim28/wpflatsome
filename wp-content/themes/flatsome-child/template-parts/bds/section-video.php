<?php
/**
 * Video YouTube (nạp khi bấm để trang nhẹ).
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_vid = bds_youtube_id( bds_opt( 'video_url' ) );
if ( ! bds_opt( 'show_video' ) || ! $bds_vid ) {
	return;
}
?>
<section id="video" class="bds-section bds-section--dark">
	<div class="container">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'video_title' ) ); ?></h2>
		</div>
		<button type="button" class="bds-video" data-bds-video="<?php echo esc_attr( $bds_vid ); ?>" aria-label="Phát video"
			style="background-image:url(https://i.ytimg.com/vi/<?php echo esc_attr( $bds_vid ); ?>/hqdefault.jpg)">
			<span class="bds-video__play" aria-hidden="true"></span>
		</button>
	</div>
</section>
