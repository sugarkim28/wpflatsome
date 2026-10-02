<?php
/**
 * Nút liên hệ nổi (máy tính) + thanh 3 nút dưới cùng (điện thoại): Gọi – Zalo – Messenger/Báo giá.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_zalo = sgd_tel( sgd_opt( 'zalo' ) );
$sgd_msg  = trim( (string) sgd_opt( 'messenger' ), " /\t" );
$sgd_msg  = $sgd_msg ? 'https://m.me/' . rawurlencode( preg_replace( '#^.*(?:m\.me|facebook\.com)/#', '', $sgd_msg ) ) : '';
?>
<div class="sgd-float" aria-label="Liên hệ nhanh">
	<?php echo sgd_call_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm. ?>
	<?php if ( $sgd_msg ) : ?>
		<a class="sgd-callbtn sgd-callbtn--msg" href="<?php echo esc_url( $sgd_msg ); ?>" target="_blank" rel="noopener"><span class="sgd-ring"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.4 2 2 6.1 2 11.4c0 3 1.4 5.6 3.7 7.3V22l3.4-1.9c.9.3 1.9.4 2.9.4 5.6 0 10-4.1 10-9.4S17.6 2 12 2zm1 12.6-2.6-2.7-5 2.7 5.5-5.8 2.6 2.7 5-2.7-5.5 5.8z"/></svg></span><span class="sgd-callbtn__txt"><small>Chat</small><strong>Messenger</strong></span></a>
	<?php endif; ?>
</div>
<nav class="sgd-mbar" aria-label="Liên hệ nhanh">
	<a href="tel:<?php echo esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ); ?>" class="sgd-mbar__call"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg><span>Gọi điện</span></a>
	<?php if ( $sgd_zalo ) : ?>
		<a href="https://zalo.me/<?php echo esc_attr( $sgd_zalo ); ?>" target="_blank" rel="noopener" class="sgd-mbar__zalo"><b>Zalo</b><span>Chat Zalo</span></a>
	<?php endif; ?>
	<?php if ( $sgd_msg ) : ?>
		<a href="<?php echo esc_url( $sgd_msg ); ?>" target="_blank" rel="noopener" class="sgd-mbar__msg"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.4 2 2 6.1 2 11.4c0 3 1.4 5.6 3.7 7.3V22l3.4-1.9c.9.3 1.9.4 2.9.4 5.6 0 10-4.1 10-9.4S17.6 2 12 2zm1 12.6-2.6-2.7-5 2.7 5.5-5.8 2.6 2.7 5-2.7-5.5 5.8z"/></svg><span>Messenger</span></a>
	<?php else : ?>
		<a href="#dang-ky" class="sgd-mbar__cta"><?php echo sgd_icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span>Nhận báo giá</span></a>
	<?php endif; ?>
</nav>
<div class="sgd-popup" id="sgd-popup" role="dialog" aria-modal="true" aria-labelledby="sgd-popup-title" hidden data-delay="<?php echo esc_attr( absint( sgd_opt( 'popup_delay' ) ) ); ?>">
	<div class="sgd-popup__box">
		<button type="button" class="sgd-popup__close" aria-label="Đóng">&times;</button>
		<p class="sgd-popup__title" id="sgd-popup-title"><?php echo esc_html( sgd_opt( 'popup_title' ) ); ?></p>
		<p class="sgd-popup__sub"><?php echo esc_html( sgd_opt( 'popup_sub' ) ); ?></p>
		<p class="sgd-popup__pick" hidden><span>Gói đã chọn</span><strong></strong><b></b></p>
		<?php
		get_template_part(
			'template-parts/dichvu/lead-form',
			null,
			array(
				'title'   => '',
				'button'  => 'Gửi yêu cầu ngay',
				'source'  => 'Popup',
				'service' => is_singular( 'dich_vu' ) ? get_the_ID() : 0,
				'perks'   => false,
			)
		);
		?>
	</div>
</div>
