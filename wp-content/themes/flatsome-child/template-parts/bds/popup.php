<?php
/**
 * Popup đăng ký 2 cột: logo + thông tin liên hệ | form (tự bật sau N giây, mỗi phiên 1 lần).
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_logo    = bds_logo_url();
$bds_hotline = bds_opt( 'hotline' );
$bds_zalo    = bds_tel( bds_opt( 'zalo' ) );
$bds_email   = bds_opt( 'contact_email' );
$bds_address = bds_opt( 'contact_address' );
?>
<div class="bds-modal" id="bds-modal" role="dialog" aria-modal="true" aria-labelledby="bds-modal-title" hidden>
	<div class="bds-modal__backdrop" data-bds-close></div>
	<div class="bds-modal__box bds-modal__box--split">
		<button type="button" class="bds-modal__close" data-bds-close aria-label="Đóng">×</button>
		<div class="bds-modal__info">
			<?php if ( $bds_logo ) : ?>
				<span class="bds-modal__logo"><img src="<?php echo esc_url( $bds_logo ); ?>" alt="<?php echo esc_attr( bds_opt( 'hero_title' ) ); ?>" width="150" height="150" loading="lazy"></span>
			<?php endif; ?>
			<p class="bds-modal__label">Thông tin liên hệ:</p>
			<ul class="bds-contact-list">
				<?php if ( $bds_address ) : ?>
					<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg><span><?php echo esc_html( $bds_address ); ?></span></li>
				<?php endif; ?>
				<?php if ( $bds_hotline ) : ?>
					<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg><a href="tel:<?php echo esc_attr( bds_tel( $bds_hotline ) ); ?>"><?php echo esc_html( $bds_hotline ); ?></a></li>
				<?php endif; ?>
				<?php if ( $bds_zalo ) : ?>
					<li><b class="bds-zalo-ico" aria-hidden="true">Zalo</b><a href="https://zalo.me/<?php echo esc_attr( $bds_zalo ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $bds_hotline && bds_tel( $bds_hotline ) === $bds_zalo ? $bds_hotline : $bds_zalo ); ?></a></li>
				<?php endif; ?>
				<?php if ( $bds_email ) : ?>
					<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5z"/></svg><a href="mailto:<?php echo esc_attr( $bds_email ); ?>"><?php echo esc_html( $bds_email ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
		<div class="bds-modal__form">
			<h3 id="bds-modal-title" class="bds-form__title"><?php echo esc_html( bds_opt( 'popup_title' ) ); ?></h3>
			<?php
			get_template_part(
				'template-parts/bds/lead-form',
				null,
				array(
					'button' => 'Nhận bảng giá',
					'source' => 'Popup',
				)
			);
			?>
		</div>
	</div>
</div>
