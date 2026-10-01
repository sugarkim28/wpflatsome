<?php
/**
 * Nút gọi / Zalo nổi (máy tính) + thanh liên hệ dưới cùng (điện thoại).
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

$sgp_project = is_singular( 'du_an' ) ? get_the_ID() : 0;
$sgp_hotline = sgp_project_hotline( $sgp_project );
$sgp_zalo    = sgp_tel( sgp_opt( 'zalo' ) );
?>
<div class="sgp-float" aria-label="Liên hệ nhanh"><?php echo sgp_call_buttons( $sgp_project ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm. ?></div>
<nav class="sgp-mbar" aria-label="Liên hệ nhanh">
	<a href="tel:<?php echo esc_attr( sgp_tel( $sgp_hotline ) ); ?>" class="sgp-mbar__call">Gọi ngay</a>
	<?php if ( $sgp_zalo ) : ?>
		<a href="https://zalo.me/<?php echo esc_attr( $sgp_zalo ); ?>" target="_blank" rel="noopener" class="sgp-mbar__zalo">Chat Zalo</a>
	<?php endif; ?>
	<a href="#dang-ky" class="sgp-mbar__cta">Nhận báo giá</a>
</nav>
