<?php
/**
 * Form đăng ký. $args: title, button, source, project (ID – 0 = cho khách chọn dự án), perks (bool).
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

$sgp_a = wp_parse_args(
	isset( $args ) ? $args : array(),
	array(
		'title'   => sgp_opt( 'form_title' ),
		'button'  => 'Nhận thông tin ngay',
		'source'  => 'Form',
		'project' => 0,
		'perks'   => true,
	)
);
$sgp_form   = sanitize_title( $sgp_a['source'] );
$sgp_uid    = wp_unique_id( 'sgp-f' );
$sgp_status = '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiện thông báo.
if ( isset( $_GET['sgp_form'], $_GET['sgp_status'] ) && sanitize_title( wp_unslash( $_GET['sgp_form'] ) ) === $sgp_form ) {
	$sgp_status = sanitize_key( wp_unslash( $_GET['sgp_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
$sgp_msgs = array(
	'success' => 'Cảm ơn Quý khách! Chuyên viên sẽ liên hệ trong thời gian sớm nhất.',
	'invalid' => 'Vui lòng nhập họ tên và số điện thoại hợp lệ.',
	'wait'    => 'Bạn vừa gửi thông tin. Vui lòng thử lại sau 1 phút.',
	'expired' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang và gửi lại.',
	'error'   => 'Có lỗi xảy ra, vui lòng gọi hotline để được hỗ trợ.',
);
?>
<form id="sgp-form-<?php echo esc_attr( $sgp_form ); ?>" class="sgp-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php if ( $sgp_a['title'] ) : ?>
		<p class="sgp-form__title"><?php echo esc_html( $sgp_a['title'] ); ?></p>
	<?php endif; ?>
	<?php if ( $sgp_a['perks'] && sgp_lines( sgp_opt( 'form_perks' ) ) ) : ?>
		<ul class="sgp-perks">
			<?php foreach ( sgp_lines( sgp_opt( 'form_perks' ) ) as $sgp_p ) : ?>
				<li><?php echo esc_html( $sgp_p[0] ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( $sgp_status && isset( $sgp_msgs[ $sgp_status ] ) ) : ?>
		<div class="sgp-alert sgp-alert--<?php echo 'success' === $sgp_status ? 'success' : 'error'; ?>" role="alert">
			<?php echo esc_html( $sgp_msgs[ $sgp_status ] ); ?>
			<?php if ( 'success' === $sgp_status ) : ?>
				<div class="sgp-callbtns"><?php echo sgp_call_buttons( absint( $sgp_a['project'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm. ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<input type="hidden" name="action" value="sgp_lead">
	<input type="hidden" name="sgp_source" value="<?php echo esc_attr( $sgp_a['source'] ); ?>">
	<?php wp_nonce_field( 'sgp_lead', 'sgp_nonce', false ); ?>
	<div class="sgp-hp" aria-hidden="true"><label>Website <input type="text" name="sgp_website" tabindex="-1" autocomplete="off"></label></div>

	<label class="screen-reader-text" for="<?php echo esc_attr( $sgp_uid ); ?>-name">Họ và tên</label>
	<input id="<?php echo esc_attr( $sgp_uid ); ?>-name" type="text" name="sgp_name" placeholder="Họ và tên *" required maxlength="100" autocomplete="name">
	<label class="screen-reader-text" for="<?php echo esc_attr( $sgp_uid ); ?>-phone">Số điện thoại</label>
	<input id="<?php echo esc_attr( $sgp_uid ); ?>-phone" type="tel" name="sgp_phone" placeholder="Số điện thoại *" required pattern="^(\+?84|0)[\d\s.\-]{9,13}$" title="Số điện thoại Việt Nam, ví dụ 0909 123 456" autocomplete="tel">
	<label class="screen-reader-text" for="<?php echo esc_attr( $sgp_uid ); ?>-email">Email</label>
	<input id="<?php echo esc_attr( $sgp_uid ); ?>-email" type="email" name="sgp_email" placeholder="Email (không bắt buộc)" autocomplete="email">
	<?php if ( $sgp_a['project'] ) : ?>
		<input type="hidden" name="sgp_project" value="<?php echo esc_attr( absint( $sgp_a['project'] ) ); ?>">
	<?php else : ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $sgp_uid ); ?>-project">Dự án quan tâm</label>
		<select id="<?php echo esc_attr( $sgp_uid ); ?>-project" name="sgp_project">
			<option value="">Dự án quan tâm (không bắt buộc)</option>
			<?php foreach ( get_posts( array( 'post_type' => 'du_an', 'posts_per_page' => 50, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) ) as $sgp_p ) : ?>
				<option value="<?php echo esc_attr( $sgp_p->ID ); ?>"><?php echo esc_html( $sgp_p->post_title ); ?></option>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>
	<button type="submit" class="button sgp-btn expand"><?php echo esc_html( $sgp_a['button'] ); ?></button>
	<p class="sgp-form__note">Thông tin của bạn được bảo mật và chỉ dùng để tư vấn.</p>
</form>
