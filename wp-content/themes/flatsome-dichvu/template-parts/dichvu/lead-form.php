<?php
/**
 * Form tư vấn. $args: title, button, source, service (ID – 0 = cho khách chọn), perks (bool), note (bool).
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_a = wp_parse_args(
	isset( $args ) ? $args : array(),
	array(
		'title'   => sgd_opt( 'form_title' ),
		'button'  => 'Gửi yêu cầu tư vấn',
		'source'  => 'Form',
		'service' => 0,
		'perks'   => true,
		'note'    => true,
	)
);
$sgd_form   = sanitize_title( $sgd_a['source'] );
$sgd_uid    = wp_unique_id( 'sgd-f' );
$sgd_status = '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiện thông báo.
if ( isset( $_GET['sgd_form'], $_GET['sgd_status'] ) && sanitize_title( wp_unslash( $_GET['sgd_form'] ) ) === $sgd_form ) {
	$sgd_status = sanitize_key( wp_unslash( $_GET['sgd_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
$sgd_msgs = array(
	'success' => 'Cảm ơn Quý khách! Chuyên viên tư vấn sẽ liên hệ trong ít phút (giờ hành chính).',
	'invalid' => 'Vui lòng nhập họ tên và số điện thoại hợp lệ.',
	'wait'    => 'Bạn vừa gửi thông tin. Vui lòng thử lại sau 1 phút.',
	'expired' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang và gửi lại.',
	'error'   => 'Có lỗi xảy ra, vui lòng gọi hotline để được hỗ trợ.',
);
?>
<form id="sgd-form-<?php echo esc_attr( $sgd_form ); ?>" class="sgd-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php if ( $sgd_a['title'] ) : ?>
		<p class="sgd-form__title"><?php echo esc_html( $sgd_a['title'] ); ?></p>
	<?php endif; ?>
	<?php if ( $sgd_a['perks'] && sgd_list( sgd_opt( 'form_perks' ) ) ) : ?>
		<ul class="sgd-check sgd-perks">
			<?php foreach ( sgd_list( sgd_opt( 'form_perks' ) ) as $sgd_p ) : ?>
				<li><?php echo esc_html( $sgd_p ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( $sgd_status && isset( $sgd_msgs[ $sgd_status ] ) ) : ?>
		<div class="sgd-alert sgd-alert--<?php echo 'success' === $sgd_status ? 'success' : 'error'; ?>" role="alert">
			<?php echo esc_html( $sgd_msgs[ $sgd_status ] ); ?>
			<?php if ( 'success' === $sgd_status ) : ?>
				<div class="sgd-callbtns"><?php echo sgd_call_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm. ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<input type="hidden" name="action" value="sgd_lead">
	<input type="hidden" name="sgd_source" value="<?php echo esc_attr( $sgd_a['source'] ); ?>">
	<?php if ( sgd_is_en() ) : ?>
		<input type="hidden" name="sgd_lang" value="en">
	<?php endif; ?>
	<?php wp_nonce_field( 'sgd_lead', 'sgd_nonce', false ); ?>
	<div class="sgd-hp" aria-hidden="true"><label>Website <input type="text" name="sgd_website" tabindex="-1" autocomplete="off"></label></div>

	<label class="screen-reader-text" for="<?php echo esc_attr( $sgd_uid ); ?>-name">Họ và tên</label>
	<input id="<?php echo esc_attr( $sgd_uid ); ?>-name" type="text" name="sgd_name" placeholder="Họ và tên *" required maxlength="100" autocomplete="name">
	<label class="screen-reader-text" for="<?php echo esc_attr( $sgd_uid ); ?>-phone">Số điện thoại</label>
	<input id="<?php echo esc_attr( $sgd_uid ); ?>-phone" type="tel" name="sgd_phone" placeholder="Số điện thoại / Zalo *" required pattern="<?php echo esc_attr( sgd_is_en() ? '^\+?[\d\s.\-()]{7,20}$' : '^(\+?84|0)[\d\s.\-]{9,13}$' ); ?>" title="Số điện thoại Việt Nam, ví dụ 0909 123 456" autocomplete="tel">
	<label class="screen-reader-text" for="<?php echo esc_attr( $sgd_uid ); ?>-email">Email</label>
	<input id="<?php echo esc_attr( $sgd_uid ); ?>-email" type="email" name="sgd_email" placeholder="Email (không bắt buộc)" autocomplete="email">
	<?php if ( $sgd_a['service'] ) : ?>
		<input type="hidden" name="sgd_service" value="<?php echo esc_attr( absint( $sgd_a['service'] ) ); ?>">
	<?php else : ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $sgd_uid ); ?>-service">Dịch vụ cần tư vấn</label>
		<select id="<?php echo esc_attr( $sgd_uid ); ?>-service" name="sgd_service">
			<option value="">Dịch vụ cần tư vấn</option>
			<?php foreach ( sgd_sorted_groups() as $sgd_g ) : ?>
				<option value="g<?php echo esc_attr( $sgd_g->term_id ); ?>"><?php echo esc_html( $sgd_g->name ); ?></option>
			<?php endforeach; ?>
			<option value="0">Khác</option>
		</select>
	<?php endif; ?>
	<?php if ( $sgd_a['note'] ) : ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $sgd_uid ); ?>-note">Nội dung cần tư vấn</label>
		<textarea id="<?php echo esc_attr( $sgd_uid ); ?>-note" name="sgd_note" rows="3" maxlength="1000" placeholder="Nội dung cần tư vấn (không bắt buộc)"></textarea>
	<?php endif; ?>
	<button type="submit" class="button sgd-btn expand"><?php echo esc_html( $sgd_a['button'] ); ?></button>
	<p class="sgd-form__note">Thông tin của bạn được bảo mật và chỉ dùng để tư vấn.</p>
</form>
