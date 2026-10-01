<?php
/**
 * Thẻ dự án trong lưới. $args['tag'] = thẻ tiêu đề (h2/h3).
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

$sgp_tag    = isset( $args['tag'] ) && in_array( $args['tag'], array( 'h2', 'h3', 'h4' ), true ) ? $args['tag'] : 'h3';
$sgp_status = sgp_first_term( 'trang_thai' );
$sgp_type   = sgp_first_term( 'loai_hinh' );
$sgp_area   = sgp_first_term( 'khu_vuc' );
$sgp_dev    = sgp_first_term( 'chu_dau_tu' );
?>
<article class="sgp-card">
	<a class="sgp-card__img" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'medium_large', array( 'alt' => esc_attr( get_the_title() ), 'loading' => 'lazy' ) );
		} else {
			echo '<span class="sgp-card__noimg"></span>';
		}
		?>
		<?php if ( $sgp_status ) : ?>
			<span class="sgp-badge sgp-badge--<?php echo esc_attr( $sgp_status->slug ); ?>"><?php echo esc_html( $sgp_status->name ); ?></span>
		<?php endif; ?>
		<?php if ( sgp_meta( 'policy' ) ) : ?>
			<span class="sgp-card__policy"><?php echo esc_html( sgp_meta( 'policy' ) ); ?></span>
		<?php endif; ?>
	</a>
	<div class="sgp-card__body">
		<p class="sgp-card__meta"><?php echo esc_html( implode( ' · ', array_filter( array( $sgp_type ? $sgp_type->name : '', $sgp_area ? $sgp_area->name : '' ) ) ) ); ?></p>
		<<?php echo esc_html( $sgp_tag ); ?> class="sgp-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo esc_html( $sgp_tag ); ?>>
		<?php if ( sgp_meta( 'address' ) ) : ?>
			<p class="sgp-card__addr"><?php echo esc_html( sgp_meta( 'address' ) ); ?></p>
		<?php endif; ?>
		<div class="sgp-card__foot">
			<span class="sgp-card__price"><?php echo esc_html( sgp_meta( 'price' ) ? sgp_meta( 'price' ) : 'Giá: liên hệ' ); ?></span>
			<?php if ( $sgp_dev ) : ?>
				<span class="sgp-card__dev"><?php echo esc_html( $sgp_dev->name ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</article>
