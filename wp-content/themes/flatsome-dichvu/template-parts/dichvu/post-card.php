<?php
/**
 * Thẻ bài viết (trang Kiến thức). $args: lead (bool – thẻ lớn nằm ngang), tag (h2/h3).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_lead = ! empty( $args['lead'] );
$sgd_tag  = isset( $args['tag'] ) && in_array( $args['tag'], array( 'h2', 'h3' ), true ) ? $args['tag'] : 'h2';
$sgd_cats = get_the_category();
$sgd_cat  = $sgd_cats ? $sgd_cats[0] : null;
?>
<article class="sgd-bcard<?php echo $sgd_lead ? ' sgd-bcard--lead' : ''; ?>">
	<a class="sgd-bcard__img" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( $sgd_lead ? 'large' : 'medium_large', array( 'loading' => $sgd_lead ? 'eager' : 'lazy', 'alt' => esc_attr( get_the_title() ) ) ); ?>
		<?php else : ?>
			<span class="sgd-post__noimg"><?php echo sgd_icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php endif; ?>
	</a>
	<div class="sgd-bcard__body">
		<?php if ( $sgd_cat ) : ?>
			<a class="sgd-bcard__cat" href="<?php echo esc_url( get_category_link( $sgd_cat ) ); ?>"><?php echo esc_html( $sgd_cat->name ); ?></a>
		<?php endif; ?>
		<<?php echo esc_html( $sgd_tag ); ?> class="sgd-bcard__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo esc_html( $sgd_tag ); ?>>
		<p class="sgd-bcard__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), $sgd_lead ? 38 : 22, '…' ) ); ?></p>
		<p class="sgd-bcard__meta"><time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></time> · <?php echo esc_html( sgd_reading_time() ); ?> phút đọc</p>
	</div>
</article>
