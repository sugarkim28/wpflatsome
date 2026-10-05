<?php
/**
 * Nút chia sẻ mạng xã hội (bài viết, trang dịch vụ, bài nhóm).
 * Facebook / X / LinkedIn / Telegram mở cửa sổ chia sẻ; Zalo: điện thoại mở bảng chia sẻ của máy (có Zalo),
 * máy tính sao chép liên kết để dán vào Zalo; "Sao chép" lấy liên kết bài.
 * $args: url, title (mặc định = bài hiện tại).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_url   = ! empty( $args['url'] ) ? $args['url'] : get_permalink();
$sgd_title = ! empty( $args['title'] ) ? $args['title'] : get_the_title();
$sgd_u     = rawurlencode( $sgd_url );
$sgd_t     = rawurlencode( wp_strip_all_tags( $sgd_title ) );
$sgd_links = array(
	'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $sgd_u, '<path d="M14 8h3V4h-3c-2.8 0-4 1.8-4 4.4V11H7v4h3v9h4v-9h3l1-4h-4V8.6c0-.4.3-.6.6-.6z"/>' ),
	'zalo'     => array( 'Zalo', '', '' ),
	'x'        => array( 'X', 'https://twitter.com/intent/tweet?url=' . $sgd_u . '&text=' . $sgd_t, '<path d="M17.8 3h3.1l-6.8 7.7L22 21h-6.2l-4.8-6.3L5.5 21H2.4l7.3-8.3L2 3h6.3l4.4 5.8zM16.7 19.2h1.7L7.4 4.7H5.5z"/>' ),
	'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $sgd_u, '<path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9.5h4V21H3zM9.5 9.5h3.8v1.6h.1c.5-1 1.8-2 3.8-2 4 0 4.8 2.6 4.8 6V21h-4v-5.2c0-1.2 0-2.8-1.7-2.8s-2 1.3-2 2.7V21h-4z"/>' ),
	'telegram' => array( 'Telegram', 'https://t.me/share/url?url=' . $sgd_u . '&text=' . $sgd_t, '<path d="M21.9 4.3 18.8 19c-.2 1-.8 1.3-1.7.8l-4.6-3.4-2.2 2.1c-.2.2-.5.5-1 .5l.3-4.7 8.6-7.8c.4-.3-.1-.5-.6-.2L7 13l-4.6-1.4c-1-.3-1-1 .2-1.5L20.5 3c.8-.3 1.6.2 1.4 1.3z"/>' ),
);
?>
<div class="sgd-share" data-url="<?php echo esc_url( $sgd_url ); ?>" data-title="<?php echo esc_attr( wp_strip_all_tags( $sgd_title ) ); ?>">
	<span class="sgd-share__label">Chia sẻ:</span>
	<?php foreach ( $sgd_links as $sgd_k => $sgd_l ) : ?>
		<?php if ( 'zalo' === $sgd_k ) : ?>
			<button type="button" class="sgd-share__btn sgd-share__btn--zalo" data-share="zalo" aria-label="Chia sẻ qua Zalo"><b>Zalo</b></button>
		<?php else : ?>
			<a class="sgd-share__btn sgd-share__btn--<?php echo esc_attr( $sgd_k ); ?>" href="<?php echo esc_url( $sgd_l[1] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( 'Chia sẻ lên ' . $sgd_l[0] ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><?php echo $sgd_l[2]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG cố định. ?></svg></a>
		<?php endif; ?>
	<?php endforeach; ?>
	<button type="button" class="sgd-share__btn sgd-share__btn--copy" data-share="copy" aria-label="Sao chép liên kết"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 13.4a1 1 0 0 1 0-1.4l3.5-3.5a3 3 0 1 1 4.2 4.2l-2 2a1 1 0 1 1-1.4-1.4l2-2a1 1 0 1 0-1.4-1.4L12 13.4a1 1 0 0 1-1.4 0zm2.8-2.8a1 1 0 0 1 0 1.4l-3.5 3.5a3 3 0 1 1-4.2-4.2l2-2a1 1 0 1 1 1.4 1.4l-2 2a1 1 0 1 0 1.4 1.4l3.5-3.5a1 1 0 0 1 1.4 0z"/></svg><span>Sao chép</span></button>
	<span class="sgd-share__msg" role="status" aria-live="polite"></span>
</div>
