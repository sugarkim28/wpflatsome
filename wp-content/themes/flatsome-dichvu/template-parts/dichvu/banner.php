<?php
/**
 * Banner dịch vụ (ô ảnh): dùng ảnh đại diện nếu có, nếu không vẽ banner chữ (tên ngắn + giá).
 * $args: id (ID dịch vụ), size (lg|md|sm|xs – xs chỉ icon, dùng cạnh tiêu đề), caption (bool – hiện tiêu đề đầy đủ phủ dưới đáy), tag.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_id      = ! empty( $args['id'] ) ? absint( $args['id'] ) : get_the_ID();
$sgd_size    = isset( $args['size'] ) && in_array( $args['size'], array( 'lg', 'md', 'sm', 'xs' ), true ) ? $args['size'] : 'md';
$sgd_caption = ! empty( $args['caption'] );
$sgd_tag     = isset( $args['tag'] ) && in_array( $args['tag'], array( 'h2', 'h3', 'p' ), true ) ? $args['tag'] : 'h3';
$sgd_group   = sgd_first_group( $sgd_id );
$sgd_icon    = sgd_meta( 'icon', $sgd_id ) ? sgd_meta( 'icon', $sgd_id ) : ( $sgd_group ? get_term_meta( $sgd_group->term_id, '_sgd_icon', true ) : '' );
$sgd_price   = sgd_meta( 'price', $sgd_id );
$sgd_kicker  = sgd_meta( 'featured', $sgd_id ) ? 'Trọn gói' : ( $sgd_group ? $sgd_group->name : 'Dịch vụ' );
$sgd_thumb   = has_post_thumbnail( $sgd_id );
?>
<a class="sgd-tile sgd-tile--<?php echo esc_attr( $sgd_size ); ?><?php echo $sgd_thumb ? ' has-img' : ''; ?><?php echo $sgd_caption ? ' has-caption' : ''; ?>" href="<?php echo esc_url( get_permalink( $sgd_id ) ); ?>"<?php echo $sgd_caption ? '' : ' tabindex="-1" aria-hidden="true"'; ?>>
	<?php if ( $sgd_thumb ) : ?>
		<?php echo get_the_post_thumbnail( $sgd_id, 'lg' === $sgd_size ? 'large' : 'medium_large', array( 'loading' => 'lazy', 'alt' => esc_attr( get_the_title( $sgd_id ) ) ) ); ?>
	<?php else : ?>
		<span class="sgd-tile__art" aria-hidden="true">
			<span class="sgd-tile__ico"><?php echo sgd_icon( $sgd_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG tĩnh. ?></span>
			<span class="sgd-tile__kicker"><?php echo esc_html( $sgd_kicker ); ?></span>
			<span class="sgd-tile__name"><?php echo esc_html( sgd_short_title( $sgd_id ) ); ?></span>
			<?php if ( $sgd_price ) : ?>
				<span class="sgd-tile__price"><?php echo esc_html( $sgd_price ); ?></span>
			<?php endif; ?>
		</span>
	<?php endif; ?>
	<?php if ( $sgd_caption ) : ?>
		<<?php echo esc_html( $sgd_tag ); ?> class="sgd-tile__caption"><?php echo esc_html( get_the_title( $sgd_id ) . ( $sgd_price ? ' – ' . $sgd_price : '' ) ); ?></<?php echo esc_html( $sgd_tag ); ?>>
	<?php endif; ?>
</a>
