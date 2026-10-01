<?php
/**
 * Thẻ dịch vụ trong lưới. $args['tag'] = thẻ tiêu đề (h2/h3).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_tag   = isset( $args['tag'] ) && in_array( $args['tag'], array( 'h2', 'h3', 'h4' ), true ) ? $args['tag'] : 'h3';
$sgd_group = sgd_first_group();
$sgd_icon  = sgd_meta( 'icon' ) ? sgd_meta( 'icon' ) : ( $sgd_group ? get_term_meta( $sgd_group->term_id, '_sgd_icon', true ) : '' );
$sgd_desc  = has_excerpt() ? get_the_excerpt() : sgd_meta( 'subtitle' );
$sgd_items = array_slice( sgd_list( sgd_meta( 'includes' ) ), 0, 3 );
?>
<article class="sgd-card">
	<div class="sgd-card__head">
		<span class="sgd-card__icon"><?php echo sgd_icon( $sgd_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG tĩnh. ?></span>
		<?php if ( $sgd_group ) : ?>
			<span class="sgd-card__group"><?php echo esc_html( $sgd_group->name ); ?></span>
		<?php endif; ?>
	</div>
	<<?php echo esc_html( $sgd_tag ); ?> class="sgd-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo esc_html( $sgd_tag ); ?>>
	<?php if ( $sgd_desc ) : ?>
		<p class="sgd-card__desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $sgd_desc ), 24, '…' ) ); ?></p>
	<?php endif; ?>
	<?php if ( $sgd_items ) : ?>
		<ul class="sgd-check">
			<?php foreach ( $sgd_items as $sgd_i ) : ?>
				<li><?php echo esc_html( $sgd_i ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<div class="sgd-card__foot">
		<span class="sgd-card__price"><?php echo esc_html( sgd_meta( 'price' ) ? sgd_meta( 'price' ) : 'Báo giá: liên hệ' ); ?></span>
		<?php if ( sgd_meta( 'duration' ) ) : ?>
			<span class="sgd-card__time"><?php echo esc_html( sgd_meta( 'duration' ) ); ?></span>
		<?php endif; ?>
	</div>
	<a class="sgd-card__more" href="<?php the_permalink(); ?>">Xem chi tiết & bảng giá<span class="screen-reader-text"> – <?php the_title(); ?></span></a>
</article>
