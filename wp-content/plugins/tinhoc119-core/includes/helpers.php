<?php
/**
 * Hàm dùng chung.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giá trị mặc định của cài đặt. Thông tin liên hệ lấy theo dữ liệu đang có trên website
 * (theme Flatsome Dịch vụ) nếu có, không tự đổi số điện thoại.
 *
 * @return array
 */
function th119_defaults() {
	$old = (array) get_option( 'theme_mods_flatsome-dichvu', array() );
	$get = function ( $key, $fallback ) use ( $old ) {
		return isset( $old[ 'sgd_' . $key ] ) && '' !== $old[ 'sgd_' . $key ] ? $old[ 'sgd_' . $key ] : $fallback;
	};
	return array(
		// Doanh nghiệp (NAP – phải trùng Google Business Profile).
		'brand'            => 'Tin Học 119',
		'legal_name'       => '',
		'slogan'           => 'Uy tín tạo niềm tin',
		'hotline'          => $get( 'hotline', '0914 108 322' ),
		'zalo'             => $get( 'zalo', '0914108322' ),
		'email'            => $get( 'email', '' ),
		'contact_person'   => 'Giang',
		'street'           => 'Hẻm 239 Tân Quý',
		'ward'             => '',
		'district'         => 'Tân Phú',
		'city'             => 'TP. Hồ Chí Minh',
		'postal_code'      => '',
		'lat'              => '',
		'lng'              => '',
		'maps_url'         => '',
		'maps_embed'       => '',
		'hours'            => $get( 'working_hours', '' ),
		'hours_schema'     => '',
		'area_served'      => 'Tân Phú, Tân Bình, Bình Tân, TP. Hồ Chí Minh',
		'facebook'         => $get( 'facebook', '' ),
		'youtube'          => $get( 'youtube', '' ),
		'founded'          => '',
		'logo_id'          => 0,
		// Thông báo.
		'notify_emails'    => '',
		// Tích hợp Google / Bing.
		'ga4_id'           => '',
		'gsc_verify'       => '',
		'bing_verify'      => '',
		'turnstile_site'   => '',
		'turnstile_secret' => '',
		// Bảo mật.
		'disable_xmlrpc'   => 1,
		'hide_version'     => 1,
		'security_headers' => 1,
		'login_limit'      => 1,
		'block_user_enum'  => 1,
		// SEO.
		'seo_enabled'      => 1,
		'schema_enabled'   => 1,
		'redirects'        => 1,
		'log_404'          => 1,
		'home_title'       => 'Sửa laptop, máy tính Tân Phú – Dịch vụ IT tận nơi | Tin Học 119',
		'home_desc'        => '',
		'og_image_id'      => 0,
		'webp'             => 1,
		// Bảo hành.
		'warranty_default' => 3,
		// Thống kê.
		'track_views'      => 1,
		// Gỡ plugin có xoá dữ liệu không.
		'delete_on_uninstall' => 0,
	);
}

/**
 * Đọc 1 cài đặt.
 *
 * @param string $key Khoá.
 * @return mixed
 */
function th119_opt( $key ) {
	if ( ! isset( $GLOBALS['th119_settings_cache'] ) ) {
		$GLOBALS['th119_settings_cache'] = wp_parse_args( (array) get_option( 'th119_settings', array() ), th119_defaults() );
	}
	$all = $GLOBALS['th119_settings_cache'];
	return isset( $all[ $key ] ) ? $all[ $key ] : '';
}

/**
 * Xoá cache cài đặt sau khi lưu.
 */
function th119_flush_opt_cache() {
	unset( $GLOBALS['th119_settings_cache'] );
}
add_action( 'update_option_th119_settings', 'th119_flush_opt_cache' );
add_action( 'add_option_th119_settings', 'th119_flush_opt_cache' );

/**
 * Chỉ giữ chữ số.
 *
 * @param string $phone Số điện thoại.
 * @return string
 */
function th119_digits( $phone ) {
	return preg_replace( '/\D+/', '', (string) $phone );
}

/**
 * Chuẩn hoá số điện thoại Việt Nam về dạng 0xxxxxxxxx. Trả về '' nếu không hợp lệ.
 *
 * @param string $phone Số.
 * @return string
 */
function th119_normalize_phone( $phone ) {
	$d = th119_digits( $phone );
	if ( 0 === strpos( $d, '84' ) && strlen( $d ) >= 11 ) {
		$d = '0' . substr( $d, 2 );
	}
	return preg_match( '/^0\d{9,10}$/', $d ) ? $d : '';
}

/**
 * Che số điện thoại: 0914***322.
 *
 * @param string $phone Số.
 * @return string
 */
function th119_mask_phone( $phone ) {
	$d = th119_digits( $phone );
	if ( strlen( $d ) < 7 ) {
		return '***';
	}
	return substr( $d, 0, 4 ) . str_repeat( '*', strlen( $d ) - 7 ) . substr( $d, -3 );
}

/**
 * Che tên: Nguyễn Văn An → N*** V*** An.
 *
 * @param string $name Tên.
 * @return string
 */
function th119_mask_name( $name ) {
	$parts = preg_split( '/\s+/u', trim( (string) $name ) );
	$last  = array_pop( $parts );
	$out   = array();
	foreach ( $parts as $p ) {
		$out[] = mb_substr( $p, 0, 1 ) . '***';
	}
	$out[] = $last;
	return trim( implode( ' ', $out ) );
}

/**
 * IP người truy cập (không tin header proxy trừ khi khai báo TH119_TRUST_PROXY).
 *
 * @return string
 */
function th119_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( defined( 'TH119_TRUST_PROXY' ) && TH119_TRUST_PROXY ) {
		foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR' ) as $h ) {
			if ( ! empty( $_SERVER[ $h ] ) ) {
				$list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $h ] ) ) );
				$ip   = trim( $list[0] );
				break;
			}
		}
	}
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Băm IP để lưu (không lưu IP thô của khách).
 *
 * @param string $ip IP.
 * @return string
 */
function th119_ip_hash( $ip = '' ) {
	return substr( hash_hmac( 'sha256', $ip ? $ip : th119_ip(), wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * Giới hạn số lần thực hiện trong khoảng thời gian (theo IP).
 *
 * @param string $action  Tên hành động.
 * @param int    $max     Số lần tối đa.
 * @param int    $seconds Khoảng thời gian.
 * @param bool   $hit     Có tính lần này không.
 * @return bool True nếu còn được phép.
 */
function th119_rate_ok( $action, $max, $seconds, $hit = true ) {
	$key  = 'th119_rl_' . md5( $action . '|' . th119_ip_hash() );
	$data = get_transient( $key );
	$now  = time();
	if ( ! is_array( $data ) || $data['start'] + $seconds < $now ) {
		$data = array(
			'start' => $now,
			'n'     => 0,
		);
	}
	if ( $data['n'] >= $max ) {
		return false;
	}
	if ( $hit ) {
		$data['n']++;
		set_transient( $key, $data, $seconds );
	}
	return true;
}

/**
 * Mã hoá chuỗi bí mật (API key) trước khi lưu database.
 *
 * @param string $plain Chuỗi.
 * @return string
 */
function th119_encrypt( $plain ) {
	if ( '' === (string) $plain ) {
		return '';
	}
	if ( function_exists( 'sodium_crypto_secretbox' ) ) {
		$key   = sodium_crypto_generichash( wp_salt( 'secure_auth' ), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		return 's1:' . base64_encode( $nonce . sodium_crypto_secretbox( $plain, $nonce, $key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}
	return 'b0:' . base64_encode( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

/**
 * Giải mã chuỗi đã mã hoá.
 *
 * @param string $stored Chuỗi đã lưu.
 * @return string
 */
function th119_decrypt( $stored ) {
	$stored = (string) $stored;
	if ( 0 === strpos( $stored, 's1:' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
		$raw = base64_decode( substr( $stored, 3 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return '';
		}
		$key   = sodium_crypto_generichash( wp_salt( 'secure_auth' ), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
		$plain = sodium_crypto_secretbox_open( substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), $key );
		return false === $plain ? '' : $plain;
	}
	if ( 0 === strpos( $stored, 'b0:' ) ) {
		$plain = base64_decode( substr( $stored, 3 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return false === $plain ? '' : $plain;
	}
	return '';
}

/**
 * Che chuỗi bí mật để hiển thị: "…ab12 (đã lưu)".
 *
 * @param string $secret Chuỗi.
 * @return string
 */
function th119_mask_secret( $secret ) {
	$len = strlen( (string) $secret );
	return $len ? sprintf( 'Đã lưu (%d ký tự, kết thúc "…%s")', $len, substr( $secret, -4 ) ) : 'Chưa nhập';
}

/**
 * Số điện thoại hotline dạng tel:.
 *
 * @return string
 */
function th119_tel() {
	return 'tel:' . th119_digits( th119_opt( 'hotline' ) );
}

/**
 * Link Zalo.
 *
 * @return string
 */
function th119_zalo_url() {
	$z = trim( (string) th119_opt( 'zalo' ) );
	if ( '' === $z ) {
		$z = th119_digits( th119_opt( 'hotline' ) );
	}
	return 0 === strpos( $z, 'http' ) ? $z : 'https://zalo.me/' . th119_digits( $z );
}

/**
 * Link Google Maps (đã khai báo hoặc tìm theo địa chỉ).
 *
 * @return string
 */
function th119_maps_url() {
	$u = trim( (string) th119_opt( 'maps_url' ) );
	if ( $u ) {
		return $u;
	}
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( th119_opt( 'brand' ) . ', ' . th119_full_address() );
}

/**
 * Địa chỉ đầy đủ.
 *
 * @return string
 */
function th119_full_address() {
	return implode( ', ', array_filter( array( th119_opt( 'street' ), th119_opt( 'ward' ), th119_opt( 'district' ), th119_opt( 'city' ) ) ) );
}

/**
 * Bảng tên trạng thái yêu cầu hỗ trợ.
 *
 * @return array
 */
function th119_ticket_statuses() {
	return array(
		'new'        => 'Mới tiếp nhận',
		'checking'   => 'Đang kiểm tra',
		'processing' => 'Đang xử lý',
		'waiting'    => 'Chờ phản hồi khách hàng',
		'done'       => 'Đã hoàn thành',
		'cancelled'  => 'Đã huỷ',
	);
}

/**
 * Bảng tên trạng thái phiếu bảo hành.
 *
 * @return array
 */
function th119_warranty_statuses() {
	return array(
		'received'   => 'Đã tiếp nhận',
		'checking'   => 'Đang kiểm tra',
		'processing' => 'Đang xử lý',
		'waiting'    => 'Chờ linh kiện / chờ khách',
		'ready'      => 'Đã xong – chờ nhận máy',
		'returned'   => 'Đã trả máy',
		'active'     => 'Đang trong bảo hành',
		'expired'    => 'Hết bảo hành',
	);
}

/**
 * Tên bảng có tiền tố.
 *
 * @param string $name Tên ngắn.
 * @return string
 */
function th119_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'th119_' . $name;
}

/**
 * Ngày giờ hiện tại theo múi giờ website (MySQL).
 *
 * @return string
 */
function th119_now() {
	return current_time( 'mysql' );
}

/**
 * Định dạng ngày hiển thị dd/mm/yyyy.
 *
 * @param string $mysql Ngày.
 * @param bool   $time  Có giờ.
 * @return string
 */
function th119_date( $mysql, $time = false ) {
	if ( ! $mysql || 0 === strpos( $mysql, '0000' ) ) {
		return '—';
	}
	$ts = strtotime( $mysql );
	return $ts ? gmdate( $time ? 'd/m/Y H:i' : 'd/m/Y', $ts ) : '—';
}

/**
 * Gửi email thông báo cho quản trị viên.
 *
 * @param string $subject Tiêu đề.
 * @param string $body    Nội dung (text).
 * @return bool
 */
function th119_notify_admins( $subject, $body ) {
	$to = array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', (string) th119_opt( 'notify_emails' ) ) ) );
	if ( ! $to ) {
		$to = array( get_option( 'admin_email' ) );
	}
	return wp_mail( $to, '[' . th119_opt( 'brand' ) . '] ' . $subject, $body );
}

/**
 * Icon SVG nội tuyến (nhẹ, không cần thư viện icon).
 *
 * @param string $name Tên.
 * @return string
 */
function th119_icon( $name ) {
	$paths = array(
		'phone'    => '<path d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1z" fill="currentColor"/>',
		'zalo'     => '<path d="M12 3C6.5 3 2 6.9 2 11.7c0 2.7 1.4 5.1 3.6 6.7L5 21.5l3.4-1.7c1.1.3 2.3.5 3.6.5 5.5 0 10-3.9 10-8.6S17.5 3 12 3z" fill="currentColor"/><text x="12" y="14.6" font-size="6.4" font-family="Arial" font-weight="700" text-anchor="middle" fill="#fff">Zalo</text>',
		'tool'     => '<path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z" fill="currentColor"/>',
		'laptop'   => '<path d="M4 5h16a1 1 0 011 1v10H3V6a1 1 0 011-1zm-3 12h22v1.5A1.5 1.5 0 0121.5 20h-19A1.5 1.5 0 011 18.5z" fill="currentColor"/>',
		'pc'       => '<path d="M3 4h18a1 1 0 011 1v11a1 1 0 01-1 1h-7v2h3v2H7v-2h3v-2H3a1 1 0 01-1-1V5a1 1 0 011-1zm1 2v9h16V6z" fill="currentColor"/>',
		'upgrade'  => '<path d="M12 3l7 7h-4v6H9v-6H5zm-7 15h14v2H5z" fill="currentColor"/>',
		'shield'   => '<path d="M12 2l8 3v6c0 5-3.4 9.7-8 11-4.6-1.3-8-6-8-11V5zm-1 13l6-6-1.4-1.4L11 12.2 8.4 9.6 7 11z" fill="currentColor"/>',
		'building' => '<path d="M4 21V3h11v4h5v14h-6v-4h-4v4zm3-15v2h2V6zm4 0v2h2V6zM7 10v2h2v-2zm4 0v2h2v-2zm6 1v2h2v-2zM7 14v2h2v-2zm4 0v2h2v-2zm6 1v2h2v-2z" fill="currentColor"/>',
		'support'  => '<path d="M12 2a9 9 0 00-9 9v6a3 3 0 003 3h2v-8H5v-1a7 7 0 0114 0v1h-3v8h3v1h-6v2h6a2 2 0 002-2v-9a9 9 0 00-9-9z" fill="currentColor"/>',
		'windows'  => '<path d="M3 5.5l7.5-1v7H3zm8.5-1.2L21 3v8.5h-9.5zM3 12.5h7.5v7L3 18.5zm8.5 0H21V21l-9.5-1.3z" fill="currentColor"/>',
		'chip'     => '<path d="M8 2h2v2h4V2h2v2h2a2 2 0 012 2v2h2v2h-2v4h2v2h-2v2a2 2 0 01-2 2h-2v2h-2v-2h-4v2H8v-2H6a2 2 0 01-2-2v-2H2v-2h2v-4H2V8h2V6a2 2 0 012-2h2zm-1 5v10h10V7zm3 3h4v4h-4z" fill="currentColor"/>',
		'network'  => '<path d="M10 2h4v4h-1v3h6v4h1v4h-4v-4h1v-2H7v2h1v4H4v-4h1V9h6V6h-1zm0 16h4v4h-4zm1-5h2v5h-2z" fill="currentColor"/>',
		'app'      => '<path d="M4 4h7v7H4zm9 0h7v7h-7zM4 13h7v7H4zm12.5 0l3.5 3.5-3.5 3.5-3.5-3.5z" fill="currentColor"/>',
		'printer'  => '<path d="M6 2h12v5H6zM4 8h16a2 2 0 012 2v6h-4v4H6v-4H2v-6a2 2 0 012-2zm4 7v3h8v-3z" fill="currentColor"/>',
		'book'     => '<path d="M4 3h7a3 3 0 013 3v15a2 2 0 00-2-2H4zm16 0h-4a3 3 0 00-1 .2V20a3 3 0 012-1h3z" fill="currentColor"/>',
		'search'   => '<path d="M10 3a7 7 0 015.6 11.2l5.1 5.1-1.4 1.4-5.1-5.1A7 7 0 1110 3zm0 2a5 5 0 100 10 5 5 0 000-10z" fill="currentColor"/>',
		'check'    => '<path d="M9 16.2l-3.5-3.5L4 14.2l5 5 11-11-1.4-1.4z" fill="currentColor"/>',
		'clock'    => '<path d="M12 2a10 10 0 110 20 10 10 0 010-20zm1 5h-2v6l5 3 1-1.7-4-2.3z" fill="currentColor"/>',
		'pin'      => '<path d="M12 2a7 7 0 017 7c0 5-7 13-7 13S5 14 5 9a7 7 0 017-7zm0 4a3 3 0 100 6 3 3 0 000-6z" fill="currentColor"/>',
		'mail'     => '<path d="M3 5h18a1 1 0 011 1v12a1 1 0 01-1 1H3a1 1 0 01-1-1V6a1 1 0 011-1zm9 7l8-5H4z" fill="currentColor"/>',
		'calendar' => '<path d="M7 2h2v2h6V2h2v2h3a1 1 0 011 1v15a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1h3zm-2 7v10h14V9z" fill="currentColor"/>',
		'warning'  => '<path d="M12 2l10 18H2zm-1 7v5h2V9zm0 7v2h2v-2z" fill="currentColor"/>',
		'info'     => '<path d="M12 2a10 10 0 110 20 10 10 0 010-20zm-1 8v7h2v-7zm0-4v2h2V6z" fill="currentColor"/>',
		'arrow'    => '<path d="M13 5l7 7-7 7-1.4-1.4 4.6-4.6H4v-2h12.2l-4.6-4.6z" fill="currentColor"/>',
		'device'   => '<path d="M2 6h14v10H2zm16-1h4v14h-4zM5 18h8v2H5z" fill="currentColor"/>',
		'cloud'    => '<path d="M6.5 19A4.5 4.5 0 016 10a6 6 0 0111.7 1.5A3.8 3.8 0 0117.5 19z" fill="currentColor"/>',
		'star'     => '<path d="M12 2l3 6.6 7 .6-5.3 4.7 1.6 7.1L12 17.3 5.7 21l1.6-7.1L2 9.2l7-.6z" fill="currentColor"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		$name = 'tool';
	}
	return '<svg class="th-ic th-ic-' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Cho phép thẻ SVG khi in icon qua wp_kses.
 *
 * @return array
 */
function th119_svg_kses() {
	return array(
		'svg'  => array( 'class' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'aria-hidden' => true, 'focusable' => true ),
		'path' => array( 'd' => true, 'fill' => true ),
		'text' => array( 'x' => true, 'y' => true, 'font-size' => true, 'font-family' => true, 'font-weight' => true, 'text-anchor' => true, 'fill' => true ),
	);
}
