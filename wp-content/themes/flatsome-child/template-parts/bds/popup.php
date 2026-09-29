<?php
/**
 * Popup đăng ký (tự bật sau N giây, mỗi phiên 1 lần).
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="bds-modal" id="bds-modal" role="dialog" aria-modal="true" aria-labelledby="bds-modal-title" hidden>
	<div class="bds-modal__backdrop" data-bds-close></div>
	<div class="bds-modal__box">
		<button type="button" class="bds-modal__close" data-bds-close aria-label="Đóng">×</button>
		<p class="bds-kicker text-center"><?php echo esc_html( bds_opt( 'hero_title' ) ); ?></p>
		<h3 id="bds-modal-title" class="bds-form__title"><?php echo esc_html( bds_opt( 'popup_title' ) ); ?></h3>
		<?php
		get_template_part(
			'template-parts/bds/lead-form',
			null,
			array(
				'button'  => 'Nhận bảng giá ngay',
				'source'  => 'Popup',
				'compact' => true,
				'perks'   => true,
			)
		);
		?>
	</div>
</div>
