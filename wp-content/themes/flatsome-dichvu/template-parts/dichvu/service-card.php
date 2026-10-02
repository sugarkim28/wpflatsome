<?php
/**
 * Thẻ bài viết dịch vụ chính (dùng cho "Bài viết liên quan"): cùng kiểu thẻ bài viết, kèm phí và thời gian.
 * $args: post (WP_Post), tag (h2/h3).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_p   = isset( $args['post'] ) ? $args['post'] : get_post();
$sgd_tag = isset( $args['tag'] ) && in_array( $args['tag'], array( 'h2', 'h3' ), true ) ? $args['tag'] : 'h3';
$sgd_g   = sgd_first_group( $sgd_p->ID );
$sgd_ex  = has_excerpt( $sgd_p ) ? get_the_excerpt( $sgd_p ) : sgd_meta( 'subtitle', $sgd_p->ID );
?>
<article class="sgd-bcard sgd-bcard--service">
	<a class="sgd-bcard__img" href="<?php echo esc_url( get_permalink( $sgd_p ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail( $sgd_p ) ) : ?>
			<?php echo get_the_post_thumbnail( $sgd_p, 'medium_large', array( 'loading' => 'lazy', 'alt' => esc_attr( get_the_title( $sgd_p ) ) ) ); ?>
		<?php else : ?>
			<span class="sgd-post__noimg"><?php echo sgd_icon( sgd_meta( 'icon', $sgd_p->ID ) ? sgd_meta( 'icon', $sgd_p->ID ) : 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php endif; ?>
	</a>
	<div class="sgd-bcard__body">
		<?php if ( $sgd_g ) : ?>
			<a class="sgd-bcard__cat" href="<?php echo esc_url( get_term_link( $sgd_g ) ); ?>"><?php echo esc_html( $sgd_g->name ); ?></a>
		<?php endif; ?>
		<<?php echo esc_html( $sgd_tag ); ?> class="sgd-bcard__title"><a href="<?php echo esc_url( get_permalink( $sgd_p ) ); ?>"><?php echo esc_html( get_the_title( $sgd_p ) ); ?></a></<?php echo esc_html( $sgd_tag ); ?>>
		<?php if ( $sgd_ex ) : ?>
			<p class="sgd-bcard__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $sgd_ex ), 22, '…' ) ); ?></p>
		<?php endif; ?>
		<p class="sgd-bcard__meta sgd-bcard__svc">
			<?php if ( sgd_meta( 'price', $sgd_p->ID ) ) : ?>
				<b><?php echo esc_html( sgd_meta( 'price', $sgd_p->ID ) ); ?></b>
			<?php endif; ?>
			<?php if ( sgd_meta( 'duration', $sgd_p->ID ) ) : ?>
				<span><?php echo sgd_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sgd_meta( 'duration', $sgd_p->ID ) ); ?></span>
			<?php endif; ?>
		</p>
	</div>
</article>
