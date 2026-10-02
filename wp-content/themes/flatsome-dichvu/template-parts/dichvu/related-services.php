<?php
/**
 * "Bài viết liên quan" = bài viết dịch vụ chính cùng mục. $args: posts (WP_Post[]), title.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_list = isset( $args['posts'] ) ? (array) $args['posts'] : array();
if ( ! $sgd_list ) {
	return;
}
?>
<section class="sgd-related">
	<div class="container">
		<h2 class="sgd-htab"><span><?php echo esc_html( isset( $args['title'] ) ? $args['title'] : 'Bài viết liên quan' ); ?></span></h2>
		<div class="sgd-blog__grid sgd-blog__grid--3">
			<?php foreach ( $sgd_list as $sgd_rp ) : ?>
				<?php get_template_part( 'template-parts/dichvu/service-card', null, array( 'post' => $sgd_rp ) ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
