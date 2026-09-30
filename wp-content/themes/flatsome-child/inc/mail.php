<?php
/**
 * Gửi email báo khách mới qua SMTP (Gmail…), không cần plugin.
 * Khách đăng ký → Cài đặt email: nhập Gmail + Mật khẩu ứng dụng, bấm "Gửi email thử".
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cấu hình SMTP đã lưu.
 *
 * @return array
 */
function bds_smtp_settings() {
	return wp_parse_args(
		(array) get_option( 'bds_smtp', array() ),
		array(
			'host'      => 'smtp.gmail.com',
			'port'      => 465,
			'secure'    => 'ssl',
			'user'      => '',
			'pass'      => '',
			'from_name' => '',
		)
	);
}

/**
 * Đã nhập đủ tài khoản SMTP chưa.
 *
 * @return bool
 */
function bds_smtp_enabled() {
	$s = bds_smtp_settings();
	return '' !== $s['user'] && '' !== $s['pass'] && '' !== $s['host'];
}

/**
 * Cấu hình PHPMailer dùng SMTP.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mailer Mailer.
 */
function bds_smtp_phpmailer( $mailer ) {
	if ( ! bds_smtp_enabled() ) {
		return;
	}
	$s = bds_smtp_settings();
	$mailer->isSMTP();
	$mailer->Host       = $s['host']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Port       = absint( $s['port'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->SMTPAuth   = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->SMTPSecure = in_array( $s['secure'], array( 'ssl', 'tls' ), true ) ? $s['secure'] : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Username   = $s['user']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Password   = $s['pass']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Timeout    = 20; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	// Gmail chỉ cho gửi "From" chính tài khoản đăng nhập.
	$mailer->setFrom( $s['user'], $s['from_name'] ? $s['from_name'] : get_bloginfo( 'name' ), false );
	$mailer->Sender = $s['user']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	if ( ! empty( $GLOBALS['bds_smtp_debug'] ) ) {
		$mailer->SMTPDebug   = 2; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->Debugoutput = 'bds_smtp_debug_collect'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}

/**
 * Gom phản hồi của máy chủ SMTP khi gửi thử. Chỉ giữ dòng máy chủ trả lời (không chứa mật khẩu).
 *
 * @param string $line Dòng debug.
 */
function bds_smtp_debug_collect( $line ) {
	$line = trim( (string) $line );
	if ( 0 === stripos( $line, 'SERVER -> CLIENT:' ) || 0 === stripos( $line, 'SMTP ERROR' ) || 0 === stripos( $line, 'SMTP connect' ) || false !== stripos( $line, 'Connection' ) ) {
		$GLOBALS['bds_smtp_log'][] = substr( $line, 0, 300 );
	}
}

/**
 * Hiện mật khẩu đã lưu dạng che: số ký tự + 2 ký tự cuối.
 *
 * @param string $pass Mật khẩu.
 * @return string
 */
function bds_mask_pass( $pass ) {
	$len = strlen( $pass );
	return $len ? sprintf( '%d ký tự, kết thúc bằng "…%s"', $len, substr( $pass, -2 ) ) : 'chưa lưu';
}
add_action( 'phpmailer_init', 'bds_smtp_phpmailer', 99 );

/**
 * Ghi lại lỗi gửi mail gần nhất để hiện trong trang quản trị.
 *
 * @param WP_Error $error Lỗi.
 */
function bds_mail_log_error( $error ) {
	update_option(
		'bds_mail_last_error',
		array(
			'time'    => time(),
			'message' => $error->get_error_message(),
		),
		false
	);
}
add_action( 'wp_mail_failed', 'bds_mail_log_error' );

/**
 * Trang: Khách đăng ký → Cài đặt email.
 */
function bds_mail_admin_menu() {
	add_submenu_page( 'edit.php?post_type=bds_lead', 'Cài đặt email báo khách', 'Cài đặt email', 'manage_options', 'bds-mail', 'bds_mail_admin_page' );
}
add_action( 'admin_menu', 'bds_mail_admin_menu' );

/**
 * Nội dung trang cài đặt.
 */
function bds_mail_admin_page() {
	$s   = bds_smtp_settings();
	$err = get_option( 'bds_mail_last_error' );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiện thông báo.
	$msg = isset( $_GET['bds_mail'] ) ? sanitize_key( wp_unslash( $_GET['bds_mail'] ) ) : '';
	?>
	<div class="wrap">
		<h1>Cài đặt email báo khách mới</h1>
		<?php if ( 'saved' === $msg ) : ?>
			<div class="notice notice-success"><p>Đã lưu cài đặt.</p></div>
		<?php elseif ( 'sent' === $msg ) : ?>
			<div class="notice notice-success"><p><strong>Đã gửi email thử</strong> tới <?php echo esc_html( bds_opt( 'lead_email' ) ); ?>. Kiểm tra hộp thư (cả mục Spam/Quảng cáo).</p></div>
		<?php elseif ( 'fail' === $msg ) : ?>
			<div class="notice notice-error"><p><strong>Gửi thử thất bại.</strong> Xem lỗi bên dưới.</p></div>
		<?php endif; ?>

		<p>Email nhận thông báo: <strong><?php echo esc_html( bds_opt( 'lead_email' ) ); ?></strong>
			(đổi ở <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=bds_general' ) ); ?>">Tuỳ biến → Landing Bất động sản → Cài đặt chung</a>).</p>
		<p>Trạng thái gửi: <?php echo bds_smtp_enabled() ? '<strong style="color:#00a32a">Đang gửi qua SMTP ' . esc_html( $s['host'] ) . '</strong>' : '<strong style="color:#b32d2e">Chưa cấu hình SMTP – đang dùng mail() của host (Gmail hay chặn hoặc đưa vào Spam)</strong>'; ?></p>

		<?php if ( is_array( $err ) && ! empty( $err['message'] ) ) : ?>
			<div class="notice notice-warning inline"><p><strong>Lỗi gửi mail gần nhất</strong> (<?php echo esc_html( wp_date( 'd/m/Y H:i', $err['time'] ) ); ?>): <code><?php echo esc_html( $err['message'] ); ?></code></p></div>
		<?php endif; ?>

		<?php $log = get_option( 'bds_smtp_last_log' ); ?>
		<?php if ( 'fail' === $msg && $log ) : ?>
			<p><strong>Phản hồi của máy chủ mail khi gửi thử:</strong></p>
			<pre style="background:#fff;border:1px solid #ccd0d4;padding:10px;white-space:pre-wrap;max-width:900px"><?php echo esc_html( implode( "\n", (array) $log ) ); ?></pre>
			<p class="description">"535 … Username and Password not accepted" = sai Gmail hoặc sai mật khẩu ứng dụng · "534 … Application-specific password required" = đang dùng mật khẩu Gmail thường ·
				Không kết nối được / timeout = host chặn cổng, thử 587 + TLS.</p>
		<?php endif; ?>
		<?php if ( bds_smtp_enabled() ) : ?>
			<p>Đang lưu: Gmail <code><?php echo esc_html( $s['user'] ); ?></code> · mật khẩu ứng dụng <code><?php echo esc_html( bds_mask_pass( $s['pass'] ) ); ?></code>
				(mật khẩu ứng dụng đúng luôn là <strong>16 ký tự chữ thường</strong>).</p>
		<?php endif; ?>

		<h2>Gửi qua Gmail (khuyên dùng)</h2>
		<ol>
			<li>Đăng nhập Gmail sẽ dùng để gửi → bật <strong>Xác minh 2 bước</strong> tại <a href="https://myaccount.google.com/security" target="_blank" rel="noopener">myaccount.google.com/security</a>.</li>
			<li>Vào <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a> → tạo <strong>Mật khẩu ứng dụng</strong> (đặt tên "Website") → chép 16 ký tự.</li>
			<li>Nhập Gmail + mật khẩu ứng dụng bên dưới → <strong>Lưu</strong> → bấm <strong>Gửi email thử</strong>.</li>
		</ol>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="bds_smtp_save">
			<?php wp_nonce_field( 'bds_smtp_save' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><label for="bds-smtp-user">Gmail gửi đi</label></th>
					<td><input id="bds-smtp-user" name="user" type="email" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $s['user'] ); ?>" placeholder="vd: saigonluxury229@gmail.com"></td></tr>
				<tr><th><label for="bds-smtp-pass">Mật khẩu ứng dụng</label></th>
					<td><input id="bds-smtp-pass" name="pass" type="text" class="regular-text" value="" autocomplete="off" spellcheck="false" style="-webkit-text-security:disc" onfocus="this.style.webkitTextSecurity='none'" placeholder="<?php echo $s['pass'] ? '•••••••• (đã lưu – để trống nếu không đổi)' : '16 ký tự, không phải mật khẩu Gmail'; ?>">
						<?php if ( $s['pass'] ) : ?><label><input type="checkbox" name="clear_pass" value="1"> Xoá mật khẩu đã lưu</label><?php endif; ?></td></tr>
				<tr><th><label for="bds-smtp-name">Tên người gửi</label></th>
					<td><input id="bds-smtp-name" name="from_name" type="text" class="regular-text" value="<?php echo esc_attr( $s['from_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td></tr>
				<tr><th>Máy chủ SMTP</th>
					<td><input name="host" type="text" value="<?php echo esc_attr( $s['host'] ); ?>" class="regular-text">
						Cổng <input name="port" type="number" value="<?php echo esc_attr( $s['port'] ); ?>" style="width:80px">
						<select name="secure">
							<?php foreach ( array( 'ssl' => 'SSL', 'tls' => 'TLS', '' => 'Không' ) as $k => $v ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['secure'], $k ); ?>><?php echo esc_html( $v ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">Gmail: smtp.gmail.com – 465 – SSL (hoặc 587 – TLS nếu host chặn cổng 465).</p></td></tr>
			</table>
			<?php submit_button( 'Lưu cài đặt' ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="bds_smtp_test">
			<?php wp_nonce_field( 'bds_smtp_test' ); ?>
			<?php submit_button( 'Gửi email thử', 'secondary' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Lưu cài đặt SMTP.
 */
function bds_smtp_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'bds_smtp_save' );
	$old  = bds_smtp_settings();
	$pass = isset( $_POST['pass'] ) ? preg_replace( '/\s+/', '', wp_unslash( $_POST['pass'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- mật khẩu, giữ nguyên ký tự.
	if ( '' === $pass ) {
		$pass = empty( $_POST['clear_pass'] ) ? $old['pass'] : '';
	}
	$secure = isset( $_POST['secure'] ) ? sanitize_key( wp_unslash( $_POST['secure'] ) ) : 'ssl';
	update_option(
		'bds_smtp',
		array(
			'host'      => isset( $_POST['host'] ) ? sanitize_text_field( wp_unslash( $_POST['host'] ) ) : 'smtp.gmail.com',
			'port'      => isset( $_POST['port'] ) ? absint( $_POST['port'] ) : 465,
			'secure'    => in_array( $secure, array( 'ssl', 'tls' ), true ) ? $secure : '',
			'user'      => isset( $_POST['user'] ) ? sanitize_email( wp_unslash( $_POST['user'] ) ) : '',
			'pass'      => $pass,
			'from_name' => isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : '',
		),
		false
	);
	wp_safe_redirect( admin_url( 'edit.php?post_type=bds_lead&page=bds-mail&bds_mail=saved' ) );
	exit;
}
add_action( 'admin_post_bds_smtp_save', 'bds_smtp_save' );

/**
 * Gửi email thử tới email nhận thông báo.
 */
function bds_smtp_test() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'bds_smtp_test' );
	delete_option( 'bds_mail_last_error' );
	$GLOBALS['bds_smtp_debug'] = true;
	$GLOBALS['bds_smtp_log']   = array();
	$ok = wp_mail(
		bds_opt( 'lead_email' ),
		'[' . get_bloginfo( 'name' ) . '] Email thử – thông báo khách đăng ký',
		"Nếu bạn nhận được email này thì thông báo khách đăng ký đã hoạt động.\n\n" . home_url( '/' )
	);
	update_option( 'bds_smtp_last_log', array_slice( (array) $GLOBALS['bds_smtp_log'], -14 ), false );
	wp_safe_redirect( admin_url( 'edit.php?post_type=bds_lead&page=bds-mail&bds_mail=' . ( $ok ? 'sent' : 'fail' ) ) );
	exit;
}
add_action( 'admin_post_bds_smtp_test', 'bds_smtp_test' );

/**
 * Nhắc cấu hình email ở trang danh sách khách.
 */
function bds_mail_admin_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-bds_lead' !== $screen->id || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$err = get_option( 'bds_mail_last_error' );
	if ( bds_smtp_enabled() && empty( $err ) ) {
		return;
	}
	$url = admin_url( 'edit.php?post_type=bds_lead&page=bds-mail' );
	echo '<div class="notice notice-warning"><p><strong>Email báo khách mới có thể không tới hộp thư.</strong> ';
	echo bds_smtp_enabled() ? 'Lần gửi gần nhất bị lỗi. ' : 'Chưa cấu hình gửi mail qua Gmail (SMTP). ';
	echo '<a href="' . esc_url( $url ) . '">Mở Cài đặt email →</a></p></div>';
}
add_action( 'admin_notices', 'bds_mail_admin_notice' );
