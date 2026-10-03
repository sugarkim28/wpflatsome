<?php
/**
 * Thông tin liên hệ chèn dưới mỗi bài viết (bài viết, dịch vụ, bài nhóm): thông tin công ty + nút gọi/Zalo + form tư vấn.
 * $args: source (nguồn form), service (ID dịch vụ – tự chọn sẵn).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_src = isset( $args['source'] ) ? $args['source'] : 'Cuối bài viết';
?>
<section class="sgd-cbox" aria-labelledby="sgd-cbox-title">
	<div class="sgd-cbox__info">
		<p class="sgd-eyebrow">Liên hệ tư vấn</p>
		<h2 class="sgd-cbox__title" id="sgd-cbox-title"><?php echo esc_html( sgd_opt( 'company_full' ) ); ?></h2>
		<p class="sgd-cbox__lead">Tư vấn miễn phí, báo giá trọn gói trong 15 phút. Gọi hoặc nhắn Zalo cho chuyên viên, hoặc để lại thông tin – chúng tôi gọi lại ngay.</p>
		<?php echo do_shortcode( '[sgd_contact_list]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="sgd-callbtns"><?php echo sgd_call_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</div>
	<div class="sgd-cbox__form">
		<?php
		get_template_part(
			'template-parts/dichvu/lead-form',
			null,
			array(
				'title'   => 'Gửi yêu cầu tư vấn',
				'source'  => $sgd_src,
				'service' => isset( $args['service'] ) ? absint( $args['service'] ) : 0,
				'perks'   => false,
				'button'  => 'Gửi yêu cầu tư vấn',
			)
		);
		?>
	</div>
</section>
