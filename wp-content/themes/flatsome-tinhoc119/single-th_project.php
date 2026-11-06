<?php
/**
 * Chi tiết dự án.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$th_get   = function ( $k ) {
		return function_exists( 'th119_field' ) ? th119_field( $k ) : '';
	};
	$th_facts = array_filter(
		array(
			'Khách hàng' => $th_get( 'client' ),
			'Khu vực'    => $th_get( 'location' ),
			'Thời gian'  => $th_get( 'date' ),
		)
	);
	$th_scope = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $th_get( 'scope' ) ) ) );
	?>
	<div id="content" class="th-single th-project">
		<?php th119t_pagehead( get_the_title() ); ?>
		<div class="th-wrap th-narrow">
			<?php if ( $th_facts ) : ?>
				<dl class="th-facts">
					<?php foreach ( $th_facts as $th_k => $th_v ) : ?>
						<div><dt><?php echo esc_html( $th_k ); ?></dt><dd><?php echo esc_html( $th_v ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="th-article__img"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>
			<div class="entry-content"><?php the_content(); ?></div>
			<?php if ( $th_scope ) : ?>
				<h2>Hạng mục</h2>
				<ul class="th-checklist">
					<?php foreach ( $th_scope as $th_s ) : ?>
						<li><?php echo th119t_ic( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $th_s ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $th_get( 'result' ) ) : ?>
				<h2>Kết quả</h2>
				<?php echo wp_kses_post( wpautop( $th_get( 'result' ) ) ); ?>
			<?php endif; ?>
			<?php echo th119t_help_box( 'Cần triển khai tương tự?' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<?php
endwhile;
get_footer();
