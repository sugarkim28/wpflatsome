<?php
/**
 * Thẻ dịch vụ. $args: tag (h2/h3/h4), layout (grid|list|mini).
 *  grid – banner trên, tiêu đề + giá + mô tả dưới (lưới dịch vụ)
 *  list – banner nhỏ bên trái, tiêu đề + giá bên phải (khối chuyên mục trang chủ)
 *  mini – chỉ tiêu đề + giá (cột phải)
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_tag    = isset( $args['tag'] ) && in_array( $args['tag'], array( 'h2', 'h3', 'h4' ), true ) ? $args['tag'] : 'h3';
$sgd_layout = isset( $args['layout'] ) && in_array( $args['layout'], array( 'grid', 'list', 'mini' ), true ) ? $args['layout'] : 'grid';
$sgd_desc   = has_excerpt() ? get_the_excerpt() : sgd_meta( 'subtitle' );
?>
<article class="sgd-card sgd-card--<?php echo esc_attr( $sgd_layout ); ?>">
	<?php if ( 'mini' !== $sgd_layout ) : ?>
		<?php get_template_part( 'template-parts/dichvu/banner', null, array( 'id' => get_the_ID(), 'size' => 'list' === $sgd_layout ? 'xs' : 'md' ) ); ?>
	<?php endif; ?>
	<div class="sgd-card__body">
		<<?php echo esc_html( $sgd_tag ); ?> class="sgd-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo esc_html( $sgd_tag ); ?>>
		<p class="sgd-card__meta">
			<span class="sgd-card__price"><?php echo esc_html( sgd_meta( 'price' ) ? sgd_meta( 'price' ) : 'Báo giá: liên hệ' ); ?></span>
			<?php if ( sgd_meta( 'duration' ) && 'mini' !== $sgd_layout ) : ?>
				<span class="sgd-card__time"><?php echo sgd_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sgd_meta( 'duration' ) ); ?></span>
			<?php endif; ?>
		</p>
		<?php if ( 'grid' === $sgd_layout && $sgd_desc ) : ?>
			<p class="sgd-card__desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $sgd_desc ), 26, '…' ) ); ?></p>
		<?php endif; ?>
	</div>
</article>
