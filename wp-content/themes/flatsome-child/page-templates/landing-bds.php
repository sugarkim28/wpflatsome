<?php
/**
 * Template name: Landing Page Bất động sản
 *
 * Nội dung chỉnh tại Giao diện > Tuỳ biến > Landing Bất động sản.
 * Nội dung soạn trong trang (UX Builder) sẽ hiển thị ngay sau phần Hero.
 * Khi bật "Tự dùng landing cho trang chủ", trang chủ luôn hiển thị bằng template này.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div id="content" class="bds-landing" role="main">
	<?php
	get_template_part( 'template-parts/bds/section', 'hero' );

	// Chỉ hiện nội dung soạn trong trang (vd đoạn giới thiệu SEO); bỏ qua bộ HTML "tc688-…" cũ.
	if ( is_page() ) {
		while ( have_posts() ) :
			the_post();
			$bds_content = get_the_content();
			if ( '' !== trim( $bds_content ) && ! bds_is_legacy_content( $bds_content ) ) {
				echo '<section class="bds-section bds-page-content"><div class="container">';
				the_content();
				echo '</div></section>';
			}
		endwhile;
	}

	foreach ( array( 'overview', 'location', 'amenities', 'floorplans', 'gallery', 'video', 'pricing', 'reasons', 'developer', 'faq', 'register' ) as $bds_section ) {
		get_template_part( 'template-parts/bds/section', $bds_section );
	}
	?>
</div>
<?php
get_footer();
