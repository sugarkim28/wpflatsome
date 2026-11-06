<?php
/**
 * Trang: trang chủ và trang dựng bằng UX Builder ([section]) hiển thị toàn chiều rộng;
 * trang thường có breadcrumb + H1 + nội dung.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$th_raw  = get_the_content();
	$th_full = is_front_page() || has_shortcode( $th_raw, 'section' ) || has_shortcode( $th_raw, 'th119_home' ) || has_shortcode( $th_raw, 'ux_slider' );
	?>
	<div id="content" class="th-page<?php echo $th_full ? ' th-page--full' : ''; ?>">
		<?php if ( $th_full ) : ?>
			<?php the_content(); ?>
		<?php else : ?>
			<?php th119t_pagehead( get_the_title() ); ?>
			<div class="th-wrap<?php echo false === strpos( $th_raw, '[th119' ) ? ' th-narrow' : ''; ?> entry-content"><?php the_content(); ?></div>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
