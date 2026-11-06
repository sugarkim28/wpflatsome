<?php
/**
 * Bảo mật nhẹ: tắt XML-RPC, ẩn phiên bản, header bảo mật, khoá đăng nhập sai nhiều lần,
 * chặn dò tên đăng nhập, tắt sửa file trong admin, ghi lỗi PHP nghiêm trọng.
 * Không thay thế tường lửa/hosting – xem tài liệu vận hành.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

// Không cho sửa mã theme/plugin trực tiếp trong trang quản trị (tránh chèn mã độc khi lộ mật khẩu).
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/**
 * Có plugin bảo mật chuyên dụng không.
 *
 * @return bool
 */
function th119_has_security_plugin() {
	return defined( 'WORDFENCE_VERSION' ) || defined( 'ITSEC_CORE_DIR' ) || class_exists( 'AIO_WP_Security' ) || defined( 'LLAR_PLUGIN_URL' ) || defined( 'SOLID_SECURITY_VERSION' );
}

if ( th119_opt( 'disable_xmlrpc' ) ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter(
		'wp_headers',
		function ( $headers ) {
			unset( $headers['X-Pingback'] );
			return $headers;
		}
	);
}

if ( th119_opt( 'hide_version' ) ) {
	remove_action( 'wp_head', 'wp_generator' );
	add_filter( 'the_generator', '__return_empty_string' );
}

/**
 * Header bảo mật.
 */
function th119_security_headers() {
	if ( ! th119_opt( 'security_headers' ) || headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=15552000' );
	}
}
add_action( 'send_headers', 'th119_security_headers' );

/* ---------------------------------------------------------------------------
 * Khoá đăng nhập sau 5 lần sai trong 15 phút (theo IP).
 * ------------------------------------------------------------------------- */

/**
 * Có bật giới hạn đăng nhập không.
 *
 * @return bool
 */
function th119_login_limit_on() {
	return th119_opt( 'login_limit' ) && ! th119_has_security_plugin();
}

/**
 * Chặn đăng nhập khi đang bị khoá.
 *
 * @param WP_User|WP_Error|null $user Người dùng.
 * @return WP_User|WP_Error|null
 */
function th119_login_check_lock( $user ) {
	if ( th119_login_limit_on() && ! th119_rate_ok( 'login', 5, 15 * MINUTE_IN_SECONDS, false ) ) {
		return new WP_Error( 'th119_locked', 'Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau 15 phút.' );
	}
	return $user;
}
add_filter( 'authenticate', 'th119_login_check_lock', 30 );

/**
 * Ghi nhận đăng nhập sai.
 *
 * @param string $username Tên đăng nhập.
 */
function th119_login_failed( $username ) {
	if ( th119_login_limit_on() ) {
		th119_rate_ok( 'login', 5, 15 * MINUTE_IN_SECONDS, true );
	}
	th119_log_activity( 'login_failed', sanitize_user( $username ), '', 0 );
}
add_action( 'wp_login_failed', 'th119_login_failed' );

/**
 * Thông báo lỗi đăng nhập chung chung (không tiết lộ tài khoản có tồn tại).
 *
 * @param string $error Thông báo.
 * @return string
 */
function th119_login_error_message( $error ) {
	if ( false !== strpos( (string) $error, 'th119' ) || false !== strpos( (string) $error, '15 phút' ) ) {
		return $error;
	}
	return '<strong>Lỗi:</strong> Tên đăng nhập hoặc mật khẩu không đúng.';
}
add_filter( 'login_errors', 'th119_login_error_message' );

/* ---------------------------------------------------------------------------
 * Chặn dò tên đăng nhập.
 * ------------------------------------------------------------------------- */

/**
 * ?author=N → về trang chủ với khách chưa đăng nhập.
 */
function th119_block_author_scan() {
	if ( th119_opt( 'block_user_enum' ) && ! is_user_logged_in() && isset( $_GET['author'] ) && ! is_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'th119_block_author_scan', 0 );

/**
 * REST /wp/v2/users chỉ cho người đã đăng nhập.
 *
 * @param array $endpoints Endpoint.
 * @return array
 */
function th119_rest_users_guard( $endpoints ) {
	if ( th119_opt( 'block_user_enum' ) && ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'th119_rest_users_guard' );

/* ---------------------------------------------------------------------------
 * Ghi lỗi PHP nghiêm trọng (Fatal) để quản trị viên xem trong "Nhật ký & lỗi".
 * ------------------------------------------------------------------------- */

/**
 * Bắt lỗi nghiêm trọng khi kết thúc request.
 */
function th119_capture_fatal() {
	$e = error_get_last();
	if ( ! $e || ! in_array( $e['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
		return;
	}
	$list   = (array) get_option( 'th119_php_errors', array() );
	$list[] = array(
		'time' => current_time( 'mysql' ),
		'msg'  => substr( str_replace( ABSPATH, '', (string) $e['message'] ), 0, 1000 ),
		'file' => str_replace( ABSPATH, '', (string) $e['file'] ) . ':' . (int) $e['line'],
		'url'  => isset( $_SERVER['REQUEST_URI'] ) ? substr( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 0, 200 ) : '',
	);
	update_option( 'th119_php_errors', array_slice( $list, -50 ), false );
}
register_shutdown_function( 'th119_capture_fatal' );
