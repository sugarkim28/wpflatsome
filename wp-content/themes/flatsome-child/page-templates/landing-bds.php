<?php
/**
 * Template name: Landing Page Bất động sản
 *
 * Nội dung chỉnh tại Giao diện > Tuỳ biến > Landing Bất động sản.
 * Nội dung soạn trong trang (UX Builder) sẽ hiển thị ngay sau phần Hero.
 *
 * @package Flatsome_Child_BDS
 */

get_header();
?>
<div id="content" class="bds-landing" role="main">
	<?php
	get_template_part( 'template-parts/bds/section', 'hero' );

	while ( have_posts() ) :
		the_post();
		if ( '' !== trim( get_the_content() ) ) {
			echo '<div class="bds-page-content">';
			the_content();
			echo '</div>';
		}
	endwhile;

	foreach ( array( 'overview', 'location', 'amenities', 'floorplans', 'gallery', 'video', 'pricing', 'reasons', 'developer', 'faq', 'register' ) as $bds_section ) {
		get_template_part( 'template-parts/bds/section', $bds_section );
	}
	?>
</div>
<?php
get_footer();
