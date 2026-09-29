<?php
/**
 * Form đăng ký nhận thông tin.
 *
 * Tham số ($args): title, button, source, full (bool – hiện thêm nhu cầu & ghi chú).
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_args = wp_parse_args(
	isset( $args ) ? $args : array(),
	array(
		'title'  => '',
		'button' => 'Đăng ký ngay',
		'source' => 'Form',
		'full'   => false,
	)
);

$bds_form_id = sanitize_title( $bds_args['source'] );
$bds_uid     = wp_unique_id( 'bds-f' );
$bds_status  = '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiển thị thông báo.
if ( isset( $_GET['bds_form'], $_GET['bds_status'] ) && sanitize_title( wp_unslash( $_GET['bds_form'] ) ) === $bds_form_id ) {
	$bds_status = sanitize_key( wp_unslash( $_GET['bds_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
$bds_messages = array(
	'success' => bds_opt( 'register_success' ),
	'invalid' => 'Vui lòng nhập họ tên và số điện thoại hợp lệ.',
	'wait'    => 'Bạn vừa gửi thông tin. Vui lòng thử lại sau 1 phút.',
	'expired' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang và gửi lại.',
	'error'   => 'Có lỗi xảy ra, vui lòng gọi hotline để được hỗ trợ.',
);
?>
<form id="bds-form-<?php echo esc_attr( $bds_form_id ); ?>" class="bds-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php if ( $bds_args['title'] ) : ?>
		<h3 class="bds-form__title"><?php echo esc_html( $bds_args['title'] ); ?></h3>
	<?php endif; ?>

	<?php if ( $bds_status && isset( $bds_messages[ $bds_status ] ) ) : ?>
		<div class="bds-alert bds-alert--<?php echo 'success' === $bds_status ? 'success' : 'error'; ?>" role="alert">
			<?php echo esc_html( $bds_messages[ $bds_status ] ); ?>
		</div>
	<?php endif; ?>

	<input type="hidden" name="action" value="bds_lead">
	<input type="hidden" name="bds_source" value="<?php echo esc_attr( $bds_args['source'] ); ?>">
	<?php wp_nonce_field( 'bds_lead', 'bds_nonce', false ); ?>
	<div class="bds-hp" aria-hidden="true">
		<label>Website <input type="text" name="bds_website" tabindex="-1" autocomplete="off"></label>
	</div>

	<label class="screen-reader-text" for="<?php echo esc_attr( $bds_uid ); ?>-name">Họ và tên</label>
	<input id="<?php echo esc_attr( $bds_uid ); ?>-name" type="text" name="bds_name" placeholder="Họ và tên *" required maxlength="100" autocomplete="name">

	<label class="screen-reader-text" for="<?php echo esc_attr( $bds_uid ); ?>-phone">Số điện thoại</label>
	<input id="<?php echo esc_attr( $bds_uid ); ?>-phone" type="tel" name="bds_phone" placeholder="Số điện thoại *" required
		pattern="^(\+?84|0)[\d\s.\-]{9,13}$" title="Số điện thoại Việt Nam, ví dụ 0909 123 456" autocomplete="tel">

	<label class="screen-reader-text" for="<?php echo esc_attr( $bds_uid ); ?>-email">Email</label>
	<input id="<?php echo esc_attr( $bds_uid ); ?>-email" type="email" name="bds_email" placeholder="Email (không bắt buộc)" autocomplete="email">

	<?php if ( $bds_args['full'] ) : ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $bds_uid ); ?>-need">Nhu cầu</label>
		<select id="<?php echo esc_attr( $bds_uid ); ?>-need" name="bds_need">
			<option value="">Nhu cầu của bạn</option>
			<?php foreach ( bds_lead_needs() as $bds_key => $bds_label ) : ?>
				<option value="<?php echo esc_attr( $bds_key ); ?>"><?php echo esc_html( $bds_label ); ?></option>
			<?php endforeach; ?>
		</select>

		<label class="screen-reader-text" for="<?php echo esc_attr( $bds_uid ); ?>-msg">Ghi chú</label>
		<textarea id="<?php echo esc_attr( $bds_uid ); ?>-msg" name="bds_message" rows="3" placeholder="Loại căn quan tâm, thời gian liên hệ..." maxlength="1000"></textarea>
	<?php endif; ?>

	<button type="submit" class="button bds-btn bds-btn--accent expand"><?php echo esc_html( $bds_args['button'] ); ?></button>
	<p class="bds-form__note">Thông tin của bạn được bảo mật và chỉ dùng để tư vấn dự án.</p>
</form>
