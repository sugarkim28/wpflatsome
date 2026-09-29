<?php
/**
 * Nút liên hệ nổi (desktop) + thanh liên hệ dưới cùng (mobile).
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_hotline   = bds_opt( 'hotline' );
$bds_zalo      = bds_tel( bds_opt( 'zalo' ) );
$bds_messenger = bds_opt( 'messenger' );
?>
<div class="bds-float" aria-label="Liên hệ nhanh">
	<?php if ( $bds_hotline ) : ?>
		<a class="bds-float__btn bds-float__btn--phone" href="tel:<?php echo esc_attr( bds_tel( $bds_hotline ) ); ?>">
			<i class="icon-phone" aria-hidden="true"></i><span><?php echo esc_html( $bds_hotline ); ?></span>
		</a>
	<?php endif; ?>
	<?php if ( $bds_zalo ) : ?>
		<a class="bds-float__btn bds-float__btn--zalo" href="https://zalo.me/<?php echo esc_attr( $bds_zalo ); ?>" target="_blank" rel="noopener">
			<b aria-hidden="true">Zalo</b><span>Chat Zalo</span>
		</a>
	<?php endif; ?>
	<?php if ( $bds_messenger ) : ?>
		<a class="bds-float__btn bds-float__btn--fb" href="<?php echo esc_url( $bds_messenger ); ?>" target="_blank" rel="noopener">
			<i class="icon-facebook" aria-hidden="true"></i><span>Messenger</span>
		</a>
	<?php endif; ?>
</div>

<nav class="bds-mbar" aria-label="Liên hệ nhanh">
	<?php if ( $bds_hotline ) : ?>
		<a href="tel:<?php echo esc_attr( bds_tel( $bds_hotline ) ); ?>"><i class="icon-phone" aria-hidden="true"></i> Gọi ngay</a>
	<?php endif; ?>
	<?php if ( $bds_zalo ) : ?>
		<a href="https://zalo.me/<?php echo esc_attr( $bds_zalo ); ?>" target="_blank" rel="noopener"><b>Zalo</b> Chat</a>
	<?php endif; ?>
	<a href="#dang-ky" class="bds-mbar__cta" data-bds-open-popup>Nhận báo giá</a>
</nav>
