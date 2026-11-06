<?php
/**
 * Dự án đã thực hiện (/du-an/). Chỉ hiển thị dự án thật do quản trị nhập.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div id="content" class="th-archive th-projects">
	<?php th119t_pagehead( 'Dự án đã thực hiện', '<p>Một số công việc thực tế cho gia đình, văn phòng và doanh nghiệp.</p>' ); ?>
	<div class="th-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="th-posts">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article class="th-post"><a class="th-post__link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="th-post__media"><?php the_post_thumbnail( 'medium_large', array( 'class' => 'th-post__img', 'loading' => 'lazy' ) ); ?></div>
						<?php endif; ?>
						<div class="th-post__body">
							<?php if ( function_exists( 'th119_field' ) && th119_field( 'location' ) ) : ?>
								<span class="th-post__cat"><?php echo esc_html( th119_field( 'location' ) ); ?></span>
							<?php endif; ?>
							<h2 class="th-post__title"><?php the_title(); ?></h2>
							<p class="th-post__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
						</div>
					</a></article>
				<?php endwhile; ?>
			</div>
			<?php th119t_pagination(); ?>
		<?php else : ?>
			<p class="th-empty">Đang cập nhật dự án.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
