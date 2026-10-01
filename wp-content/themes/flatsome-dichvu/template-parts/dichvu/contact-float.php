<?php
/**
 * Nút gọi / Zalo nổi (máy tính) + thanh liên hệ dưới cùng (điện thoại).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_zalo = sgd_tel( sgd_opt( 'zalo' ) );
?>
<div class="sgd-float" aria-label="Liên hệ nhanh"><?php echo sgd_call_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm. ?></div>
<nav class="sgd-mbar" aria-label="Liên hệ nhanh">
	<a href="tel:<?php echo esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ); ?>" class="sgd-mbar__call">Gọi ngay</a>
	<?php if ( $sgd_zalo ) : ?>
		<a href="https://zalo.me/<?php echo esc_attr( $sgd_zalo ); ?>" target="_blank" rel="noopener" class="sgd-mbar__zalo">Chat Zalo</a>
	<?php endif; ?>
	<a href="#dang-ky" class="sgd-mbar__cta">Nhận báo giá</a>
</nav>
