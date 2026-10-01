<?php
/**
 * Thẻ dịch vụ. $args: tag (h2/h3/h4), layout (grid|list|mini).
 *  grid – banner trên, tiêu đề + giá + mô tả dưới (lưới dịch vụ)
 *  list – banner nhỏ bên trái, tiêu đề + giá bên phải (khối chuyên mục trang chủ)
 *  mini – chỉ tiêu đề + giá (cột phải)
 *  feature – thẻ icon hiện đại: icon, nhóm, tiêu đề, mô tả, 3 ý chính, giá + thời gian (trang chủ)
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_tag    = isset( $args['tag'] ) && in_array( $args['tag'], array( 'h2', 'h3', 'h4' ), true ) ? $args['tag'] : 'h3';
$sgd_layout = isset( $args['layout'] ) && in_array( $args['layout'], array( 'grid', 'list', 'mini', 'feature' ), true ) ? $args['layout'] : 'grid';
$sgd_desc   = has_excerpt() ? get_the_excerpt() : sgd_meta( 'subtitle' );
?>
<?php if ( 'feature' === $sgd_layout ) : ?>
	<?php
	$sgd_group = sgd_first_group();
	$sgd_icon  = sgd_meta( 'icon' ) ? sgd_meta( 'icon' ) : ( $sgd_group ? get_term_meta( $sgd_group->term_id, '_sgd_icon', true ) : '' );
	?>
	<article class="sgd-fcard">
		<div class="sgd-fcard__top">
			<span class="sgd-fcard__icon"><?php echo sgd_icon( $sgd_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php if ( $sgd_group ) : ?><span class="sgd-fcard__group"><?php echo esc_html( $sgd_group->name ); ?></span><?php endif; ?>
		</div>
		<<?php echo esc_html( $sgd_tag ); ?> class="sgd-fcard__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo esc_html( $sgd_tag ); ?>>
		<?php if ( $sgd_desc ) : ?>
			<p class="sgd-fcard__desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $sgd_desc ), 22, '…' ) ); ?></p>
		<?php endif; ?>
		<?php $sgd_items = array_slice( sgd_list( sgd_meta( 'includes' ) ), 0, 3 ); ?>
		<?php if ( $sgd_items ) : ?>
			<ul class="sgd-check">
				<?php foreach ( $sgd_items as $sgd_i ) : ?>
					<li><?php echo esc_html( $sgd_i ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<div class="sgd-fcard__foot">
			<span><small>Phí dịch vụ</small><strong><?php echo esc_html( sgd_meta( 'price' ) ? sgd_meta( 'price' ) : 'Liên hệ' ); ?></strong></span>
			<?php if ( sgd_meta( 'duration' ) ) : ?><span><small>Thời gian</small><b><?php echo esc_html( sgd_meta( 'duration' ) ); ?></b></span><?php endif; ?>
		</div>
		<a class="sgd-fcard__more" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">Xem chi tiết →</a>
	</article>
	<?php return; ?>
<?php endif; ?>
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
