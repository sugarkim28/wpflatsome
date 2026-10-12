<?php
/**
 * Kết nối máy chấm công (vân tay, thẻ, khuôn mặt – ZKTeco, Ronald Jack, Mitaco… và các máy cùng giao thức).
 *
 * 3 cách đưa dữ liệu vào:
 * 1. Đẩy trực tiếp (ADMS / Push / "Cloud server"): máy tự gửi về https://ten-mien/iclock/cdata.
 * 2. Nhập file: attlog.dat (USB), CSV/TXT xuất từ Wise Eye, Mitaco, Excel (lưu dạng CSV).
 * 3. API: script đồng bộ chạy trong mạng LAN (tools/zk-sync.py) đọc máy qua cổng 4370 rồi gửi lên.
 *
 * Mọi lần quẹt lưu ở bảng punches, sau đó tự điền giờ vào / ra (lần quẹt đầu / cuối ngày) vào bảng công.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ *
 * Lưu và áp dụng dữ liệu chấm công
 * ------------------------------------------------------------------ */

/**
 * Chuẩn hoá thời điểm chấm: "2026-10-01 08:01:02", "01/10/2026 08:01", "2026/10/01 8:01".
 *
 * @param string $s Chuỗi.
 * @return string|false Y-m-d H:i:s.
 */
function qldn_punch_time( $s ) {
	$s = trim( (string) $s );
	if ( preg_match( '#^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})[ T]+(\d{1,2}):(\d{2})(?::(\d{2}))?#', $s, $m ) ) {
		list( , $y, $mo, $d, $h, $i ) = $m;
	} elseif ( preg_match( '#^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})[ T]+(\d{1,2}):(\d{2})(?::(\d{2}))?#', $s, $m ) ) {
		list( , $d, $mo, $y, $h, $i ) = $m;
	} else {
		return false;
	}
	$sec = isset( $m[6] ) && '' !== $m[6] ? (int) $m[6] : 0;
	if ( ! checkdate( (int) $mo, (int) $d, (int) $y ) || $h > 23 || $i > 59 || $sec > 59 ) {
		return false;
	}
	return sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $y, $mo, $d, $h, $i, $sec );
}

/**
 * Mã chấm công hợp lệ: chữ số / chữ cái, bỏ số 0 đứng đầu (máy thường in "0005" hay "5" tuỳ phần mềm).
 *
 * @param string $pin Mã.
 * @return string Rỗng nếu không hợp lệ.
 */
function qldn_pin( $pin ) {
	$pin = strtoupper( trim( (string) $pin, " \t\"'" ) );
	if ( ! preg_match( '/^[A-Z0-9]{1,20}$/', $pin ) ) {
		return '';
	}
	$trim = ltrim( $pin, '0' );
	return ctype_digit( $pin ) ? ( '' === $trim ? '0' : $trim ) : $pin;
}

/**
 * Bảng mã chấm công => nhân viên.
 *
 * @return array
 */
function qldn_pin_map() {
	global $wpdb;
	$out = array();
	foreach ( $wpdb->get_results( 'SELECT id, device_pin FROM ' . qldn_t( 'employees' ) . " WHERE device_pin <> ''" ) as $r ) {
		$out[ qldn_pin( $r->device_pin ) ] = (int) $r->id;
	}
	return $out;
}

/**
 * Lưu nhiều lần chấm rồi cập nhật bảng công.
 *
 * @param array  $punches [ [pin, time, state?, verify?] ].
 * @param string $source  adms|file|api.
 * @param string $sn      Số serial máy.
 * @return array new, dup, invalid, unknown (số lần chấm chưa gán nhân viên), locked (ngày thuộc tháng đã chốt).
 */
function qldn_punches_save( $punches, $source, $sn = '' ) {
	global $wpdb;
	$t     = qldn_t( 'punches' );
	$map   = qldn_pin_map();
	$stat  = array( 'new' => 0, 'dup' => 0, 'invalid' => 0, 'unknown' => 0, 'locked' => 0 );
	$touch = array();
	$now   = qldn_now();
	$wpdb->suppress_errors( true );
	foreach ( $punches as $p ) {
		$pin  = qldn_pin( isset( $p['pin'] ) ? $p['pin'] : '' );
		$time = qldn_punch_time( isset( $p['time'] ) ? $p['time'] : '' );
		if ( '' === $pin || ! $time ) {
			++$stat['invalid'];
			continue;
		}
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE pin = %s AND punch_time = %s", $pin, $time ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
			++$stat['dup'];
			continue;
		}
		$eid = isset( $map[ $pin ] ) ? $map[ $pin ] : 0;
		$ok  = $wpdb->insert(
			$t,
			array(
				'pin'         => $pin,
				'punch_time'  => $time,
				'employee_id' => $eid,
				'device_sn'   => substr( sanitize_text_field( $sn ), 0, 64 ),
				'state'       => isset( $p['state'] ) ? substr( preg_replace( '/\W/', '', (string) $p['state'] ), 0, 4 ) : '',
				'verify'      => isset( $p['verify'] ) ? substr( preg_replace( '/\W/', '', (string) $p['verify'] ), 0, 4 ) : '',
				'source'      => $source,
				'created_at'  => $now,
			)
		);
		if ( ! $ok ) { // Trùng khoá do 2 lần gửi cùng lúc.
			++$stat['dup'];
			continue;
		}
		++$stat['new'];
		if ( $eid ) {
			$touch[ $eid . '|' . substr( $time, 0, 10 ) ] = true;
		} else {
			++$stat['unknown'];
		}
	}
	$wpdb->suppress_errors( false );
	foreach ( array_keys( $touch ) as $k ) {
		list( $eid, $date ) = explode( '|', $k );
		if ( ! qldn_punch_apply( (int) $eid, $date ) ) {
			++$stat['locked'];
		}
	}
	return $stat;
}

/**
 * Điền giờ vào / ra (lần chấm đầu / cuối) của 1 nhân viên trong 1 ngày vào bảng công.
 *
 * Ký hiệu P, L, K, O đã chấm tay được giữ nguyên; ô trống hoặc X/N được tính lại theo số giờ làm.
 *
 * @param int    $employee_id Nhân viên.
 * @param string $date        Y-m-d.
 * @return bool False nếu tháng đã chốt lương.
 */
function qldn_punch_apply( $employee_id, $date ) {
	global $wpdb;
	if ( qldn_period_locked( substr( $date, 0, 7 ) ) ) {
		return false;
	}
	$times = $wpdb->get_col( $wpdb->prepare( 'SELECT punch_time FROM ' . qldn_t( 'punches' ) . ' WHERE employee_id = %d AND punch_time BETWEEN %s AND %s ORDER BY punch_time', $employee_id, $date . ' 00:00:00', $date . ' 23:59:59' ) );
	$t     = qldn_t( 'attendance' );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE employee_id = %d AND work_date = %s", $employee_id, $date ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	if ( ! $times ) {
		// Lần chấm bị gỡ (đổi mã chấm công): chỉ xoá giờ, giữ ký hiệu chấm tay.
		if ( $row && $row->check_in ) {
			$wpdb->update( $t, array( 'check_in' => null, 'check_out' => null ), array( 'id' => $row->id ) );
		}
		return true;
	}
	$o     = qldn_opt();
	$first = strtotime( $times[0] );
	$last  = strtotime( end( $times ) );
	// Quẹt lại trong vòng 5 phút coi như cùng 1 lần (chưa có giờ ra).
	$out   = ( $last - $first ) >= 300 ? $last : 0;
	$data  = array(
		'check_in'  => gmdate( 'H:i:s', $first ),
		'check_out' => $out ? gmdate( 'H:i:s', $out ) : null,
	);
	$mark = $row ? $row->mark : '';
	if ( '' === $mark || 'X' === $mark || 'N' === $mark ) {
		$data['mark'] = ( $out && ( $out - $first ) / 3600 < (float) $o['work_hours'] / 2 ) ? 'N' : 'X';
	}
	if ( $o['auto_ot'] && $out ) {
		$end   = strtotime( $date . ' ' . $o['shift_end'] . ':00' );
		$extra = ( $out - $end ) / 60;
		$data['ot_hours'] = $extra >= max( 1, (int) $o['ot_min'] ) ? floor( $extra / 30 ) / 2 : 0;
	}
	if ( $row ) {
		$wpdb->update( $t, $data, array( 'id' => $row->id ) );
	} else {
		$wpdb->insert( $t, array_merge( array( 'employee_id' => $employee_id, 'work_date' => $date ), $data ) );
	}
	return true;
}

/**
 * Gán lại nhân viên cho các lần chấm theo mã chấm công (sau khi nhập / sửa mã ở hồ sơ nhân viên).
 *
 * @param int    $employee_id Nhân viên.
 * @param string $old_pin     Mã cũ.
 * @param string $new_pin     Mã mới.
 */
function qldn_punch_relink( $employee_id, $old_pin, $new_pin ) {
	global $wpdb;
	$t     = qldn_t( 'punches' );
	$old   = qldn_pin( $old_pin );
	$new   = qldn_pin( $new_pin );
	if ( $old === $new ) {
		return;
	}
	$dates = array();
	if ( '' !== $old ) {
		$dates = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT DATE(punch_time) FROM $t WHERE pin = %s AND employee_id = %d", $old, $employee_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->update( $t, array( 'employee_id' => 0 ), array( 'pin' => $old, 'employee_id' => $employee_id ) );
	}
	if ( '' !== $new ) {
		$wpdb->update( $t, array( 'employee_id' => $employee_id ), array( 'pin' => $new ) );
		$dates = array_merge( $dates, $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT DATE(punch_time) FROM $t WHERE employee_id = %d AND pin = %s", $employee_id, $new ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
	foreach ( array_unique( $dates ) as $d ) {
		qldn_punch_apply( $employee_id, $d );
	}
}

/**
 * Áp dụng lại toàn bộ dữ liệu máy chấm công trong khoảng ngày (sau khi đổi giờ ca, mở khoá tháng…).
 *
 * @param string $from Y-m-d.
 * @param string $to   Y-m-d.
 * @return int Số ngày công đã cập nhật.
 */
function qldn_punch_reapply( $from, $to ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT employee_id, DATE(punch_time) AS d FROM ' . qldn_t( 'punches' ) . ' WHERE employee_id > 0 AND punch_time BETWEEN %s AND %s', $from . ' 00:00:00', $to . ' 23:59:59' ) );
	$n    = 0;
	foreach ( $rows as $r ) {
		$n += qldn_punch_apply( (int) $r->employee_id, $r->d ) ? 1 : 0;
	}
	return $n;
}

/**
 * Câu thông báo kết quả nhập.
 *
 * @param array $s Kết quả qldn_punches_save.
 * @return string
 */
function qldn_punch_stat_text( $s ) {
	$txt = sprintf( 'Mới: <strong>%d</strong> lần chấm, trùng (đã có): %d, dòng không đọc được: %d.', $s['new'], $s['dup'], $s['invalid'] );
	if ( $s['unknown'] ) {
		$txt .= sprintf( ' <strong>%d</strong> lần chấm chưa gán nhân viên – nhập <em>Mã chấm công</em> ở hồ sơ nhân viên.', $s['unknown'] );
	}
	if ( $s['locked'] ) {
		$txt .= sprintf( ' %d ngày thuộc tháng đã chốt lương nên không cập nhật bảng công.', $s['locked'] );
	}
	return $txt;
}

/* ------------------------------------------------------------------ *
 * 1. Máy đẩy trực tiếp (giao thức ADMS / Push của ZKTeco)
 * ------------------------------------------------------------------ */

/**
 * Danh sách máy: SN => [name, approved, last_seen, ip, stamp, count].
 *
 * @return array
 */
function qldn_devices() {
	$d   = get_option( 'qldn_devices', array() );
	$out = array();
	foreach ( is_array( $d ) ? $d : array() as $sn => $v ) {
		$out[ $sn ] = array_merge( array( 'name' => '', 'approved' => 0, 'last_seen' => '', 'ip' => '', 'stamp' => '', 'count' => 0 ), (array) $v );
	}
	return $out;
}

/**
 * Lưu danh sách máy.
 *
 * @param array $d Danh sách.
 */
function qldn_devices_save( $d ) {
	update_option( 'qldn_devices', $d, false );
}

/**
 * Đường dẫn /iclock/... cố định trong firmware máy chấm công.
 */
function qldn_iclock_rewrite() {
	add_rewrite_rule( '^iclock/(cdata|getrequest|devicecmd)(?:\.aspx)?/?$', 'index.php?qldn_iclock=$matches[1]', 'top' );
}
add_action( 'init', 'qldn_iclock_rewrite' );

/**
 * Làm mới đường dẫn sau khi kích hoạt / nâng cấp.
 */
function qldn_maybe_flush_rewrite() {
	if ( get_option( 'qldn_flush_rewrite' ) ) {
		delete_option( 'qldn_flush_rewrite' );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'qldn_maybe_flush_rewrite', 99 );

/**
 * Biến truy vấn.
 *
 * @param array $vars Biến.
 * @return array
 */
function qldn_iclock_query_var( $vars ) {
	$vars[] = 'qldn_iclock';
	return $vars;
}
add_filter( 'query_vars', 'qldn_iclock_query_var' );

/**
 * Trả lời máy chấm công (text/plain) rồi dừng – trước khi WordPress chuyển hướng / dựng giao diện.
 *
 * @param string $body Nội dung.
 * @param int    $code Mã HTTP.
 */
function qldn_iclock_reply( $body, $code = 200 ) {
	status_header( $code );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- văn bản thuần cho máy.
	exit;
}

/**
 * Xử lý yêu cầu từ máy.
 *
 * @param WP $wp Yêu cầu.
 */
function qldn_iclock_handle( $wp ) {
	if ( empty( $wp->query_vars['qldn_iclock'] ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification -- máy chấm công không có nonce; xác thực bằng SN đã duyệt + IP.
	$what = $wp->query_vars['qldn_iclock'];
	$sn   = isset( $_GET['SN'] ) ? substr( preg_replace( '/[^A-Za-z0-9_-]/', '', wp_unslash( $_GET['SN'] ) ), 0, 64 ) : '';
	// phpcs:enable
	if ( '' === $sn ) {
		qldn_iclock_reply( 'ERROR: missing SN', 400 );
	}
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$allowed = array_filter( array_map( 'trim', preg_split( '/[\s,;]+/', (string) qldn_opt( 'device_ips' ) ) ) );
	if ( $allowed && ! in_array( $ip, $allowed, true ) ) {
		qldn_iclock_reply( 'ERROR: IP not allowed', 403 );
	}

	$devices = qldn_devices();
	// Chặn spam số serial giả: tối đa 20 máy chờ duyệt.
	if ( ! isset( $devices[ $sn ] ) && count( array_filter( wp_list_pluck( $devices, 'approved' ) ) ) + 20 <= count( $devices ) ) {
		qldn_iclock_reply( 'ERROR: too many pending devices', 403 );
	}
	$dev     = isset( $devices[ $sn ] ) ? $devices[ $sn ] : array( 'name' => '', 'approved' => 0, 'last_seen' => '', 'ip' => '', 'stamp' => '', 'count' => 0 );
	$now     = qldn_now();
	// Ghi "lần cuối kết nối" tối đa 1 lần / phút để đỡ ghi CSDL (máy hỏi lệnh vài giây 1 lần).
	if ( ! isset( $devices[ $sn ] ) || strtotime( $now ) - strtotime( $dev['last_seen'] ? $dev['last_seen'] : '2000-01-01' ) >= 60 || $dev['ip'] !== $ip ) {
		$dev['last_seen'] = $now;
		$dev['ip']        = $ip;
		$devices[ $sn ]   = $dev;
		qldn_devices_save( $devices );
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
	if ( 'cdata' === $what && 'GET' === $method ) {
		// Bắt tay: báo máy gửi dữ liệu chấm công theo thời gian thực, múi giờ Việt Nam.
		$tz = (int) round( (float) get_option( 'gmt_offset', 7 ) );
		qldn_iclock_reply(
			"GET OPTION FROM: {$sn}\n" .
			'ATTLOGStamp=' . ( $dev['stamp'] ? $dev['stamp'] : 'None' ) . "\n" .
			"OPERLOGStamp=9999\nATTPHOTOStamp=None\nErrorDelay=60\nDelay=30\nTransTimes=00:00;14:05\nTransInterval=1\n" .
			"TransFlag=TransData AttLog\nTimeZone={$tz}\nRealtime=1\nEncrypt=None\n"
		);
	}
	if ( 'cdata' === $what ) {
		// phpcs:ignore WordPress.Security.NonceVerification
		$table = isset( $_GET['table'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['table'] ) ) ) : '';
		$body  = (string) file_get_contents( 'php://input' );
		if ( 'ATTLOG' !== $table ) {
			qldn_iclock_reply( 'OK' ); // Nhật ký thao tác, ảnh… không cần lưu.
		}
		if ( empty( $dev['approved'] ) ) {
			// Chưa duyệt: không nhận, máy giữ dữ liệu và gửi lại sau khi được duyệt.
			qldn_iclock_reply( 'ERROR: device not approved', 403 );
		}
		$punches = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $body ) as $line ) {
			$f = explode( "\t", trim( $line ) );
			if ( count( $f ) >= 2 ) {
				$punches[] = array( 'pin' => $f[0], 'time' => $f[1], 'state' => isset( $f[2] ) ? $f[2] : '', 'verify' => isset( $f[3] ) ? $f[3] : '' );
			}
		}
		$stat = qldn_punches_save( $punches, 'adms', $sn );
		// phpcs:ignore WordPress.Security.NonceVerification
		$stamp            = isset( $_GET['Stamp'] ) ? preg_replace( '/\D/', '', wp_unslash( $_GET['Stamp'] ) ) : '';
		$devices          = qldn_devices();
		$devices[ $sn ]   = array_merge( $devices[ $sn ], array( 'count' => (int) $devices[ $sn ]['count'] + $stat['new'], 'last_seen' => $now ) + ( '' !== $stamp ? array( 'stamp' => $stamp ) : array() ) );
		qldn_devices_save( $devices );
		qldn_iclock_reply( 'OK: ' . count( $punches ) );
	}
	// getrequest (hỏi lệnh) và devicecmd (báo kết quả lệnh): không gửi lệnh nào.
	qldn_iclock_reply( 'OK' );
}
add_action( 'parse_request', 'qldn_iclock_handle', 1 );

/* ------------------------------------------------------------------ *
 * 2. Nhập file
 * ------------------------------------------------------------------ */

/**
 * Đọc file chấm công. Mỗi dòng: mã chấm công ở cột đầu, sau đó ngày giờ.
 * Hỗ trợ: attlog.dat ("5<TAB>2026-10-01 08:01:02<TAB>0<TAB>1…"), CSV/TXT dấu phẩy, chấm phẩy, tab,
 * ngày và giờ cùng cột hoặc tách cột, nhiều giờ trên 1 dòng ("05;Nguyễn A;01/10/2026;07:58;17:32").
 *
 * @param string $text Nội dung file.
 * @return array [ [pin, time] ].
 */
function qldn_parse_punch_file( $text ) {
	// File "Unicode" của Excel / phần mềm chấm công là UTF-16.
	if ( 0 === strpos( $text, "\xFF\xFE" ) || 0 === strpos( $text, "\xFE\xFF" ) ) {
		$text = mb_convert_encoding( substr( $text, 2 ), 'UTF-8', 0 === strpos( $text, "\xFF\xFE" ) ? 'UTF-16LE' : 'UTF-16BE' );
	}
	$text = preg_replace( '/^\xEF\xBB\xBF/', '', $text );
	$out  = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
		$cols = array_values( array_filter( array_map( 'trim', preg_split( '/[\t,;|]/', $line ) ), 'strlen' ) );
		if ( count( $cols ) < 2 ) {
			continue;
		}
		$pin = qldn_pin( $cols[0] );
		if ( '' === $pin ) {
			continue; // Dòng tiêu đề.
		}
		$rest = implode( ' ', array_slice( $cols, 1 ) );
		if ( preg_match( '#(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})#', $rest, $dm, PREG_OFFSET_CAPTURE ) ) {
			$date = sprintf( '%04d-%02d-%02d', $dm[1][0], $dm[2][0], $dm[3][0] );
		} elseif ( preg_match( '#(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})#', $rest, $dm, PREG_OFFSET_CAPTURE ) ) {
			$date = sprintf( '%04d-%02d-%02d', $dm[3][0], $dm[2][0], $dm[1][0] );
		} else {
			continue;
		}
		$after = substr( $rest, $dm[0][1] + strlen( $dm[0][0] ) );
		if ( preg_match_all( '/(?<![\d:])(\d{1,2}):(\d{2})(?::(\d{2}))?(?![\d:])/', $after, $tm, PREG_SET_ORDER ) ) {
			foreach ( $tm as $x ) {
				$out[] = array( 'pin' => $pin, 'time' => $date . ' ' . $x[1] . ':' . $x[2] . ':' . ( isset( $x[3] ) && '' !== $x[3] ? $x[3] : '00' ) );
			}
		}
	}
	return $out;
}

/**
 * Xử lý tải file lên.
 */
function qldn_handle_punch_import() {
	qldn_check( 'punch_import', 'qldn_hr' );
	// phpcs:ignore WordPress.Security.NonceVerification -- đã kiểm tra ở qldn_check.
	$f = isset( $_FILES['file'] ) ? $_FILES['file'] : null;
	if ( ! $f || UPLOAD_ERR_OK !== $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) ) {
		qldn_notice( 'Chưa chọn file hoặc tải lên lỗi.', 'error' );
		qldn_go( 'devices', array( 'tab' => 'import' ) );
	}
	if ( $f['size'] > 20 * MB_IN_BYTES ) {
		qldn_notice( 'File quá lớn (tối đa 20 MB).', 'error' );
		qldn_go( 'devices', array( 'tab' => 'import' ) );
	}
	$ext = strtolower( pathinfo( $f['name'], PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'dat', 'txt', 'csv', 'log' ), true ) ) {
		qldn_notice( 'Chỉ nhận file .dat, .txt, .csv. File Excel (.xlsx) hãy mở bằng Excel rồi <em>Lưu dạng → CSV UTF-8</em>.', 'error' );
		qldn_go( 'devices', array( 'tab' => 'import' ) );
	}
	$rows = qldn_parse_punch_file( (string) file_get_contents( $f['tmp_name'] ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $rows ) {
		qldn_notice( 'Không đọc được dòng chấm công nào. Mỗi dòng cần có mã chấm công ở cột đầu và ngày giờ (vd <code>5,01/10/2026 07:58</code>).', 'error' );
		qldn_go( 'devices', array( 'tab' => 'import' ) );
	}
	$s = qldn_punches_save( $rows, 'file', qldn_in( 'sn' ) );
	qldn_notice( 'Đã nhập file ' . esc_html( $f['name'] ) . '. ' . qldn_punch_stat_text( $s ), $s['unknown'] ? 'warning' : 'success' );
	qldn_go( 'devices', array( 'tab' => 'log' ) );
}
add_action( 'admin_post_qldn_punch_import', 'qldn_handle_punch_import' );

/* ------------------------------------------------------------------ *
 * 3. API cho script đồng bộ trong mạng LAN
 * ------------------------------------------------------------------ */

/**
 * POST /wp-json/qldn/v1/punches  (header X-QLDN-Key)
 * Body JSON: {"device":"SN","punches":[{"pin":"5","time":"2026-10-01 08:01:02","state":0,"verify":1}]}
 */
function qldn_rest_routes() {
	register_rest_route(
		'qldn/v1',
		'/punches',
		array(
			'methods'             => 'POST',
			'callback'            => 'qldn_rest_punches',
			'permission_callback' => 'qldn_rest_auth',
		)
	);
	register_rest_route(
		'qldn/v1',
		'/ping',
		array(
			'methods'             => 'GET',
			'callback'            => function () {
				return array( 'ok' => true, 'site' => get_bloginfo( 'name' ) );
			},
			'permission_callback' => 'qldn_rest_auth',
		)
	);
}
add_action( 'rest_api_init', 'qldn_rest_routes' );

/**
 * Kiểm tra khoá API.
 *
 * @param WP_REST_Request $req Yêu cầu.
 * @return bool|WP_Error
 */
function qldn_rest_auth( $req ) {
	$key  = (string) get_option( 'qldn_api_key', '' );
	$sent = (string) $req->get_header( 'x_qldn_key' );
	if ( '' === $key || '' === $sent || ! hash_equals( $key, $sent ) ) {
		return new WP_Error( 'qldn_key', 'Sai khoá API.', array( 'status' => 401 ) );
	}
	return true;
}

/**
 * Nhận dữ liệu chấm công.
 *
 * @param WP_REST_Request $req Yêu cầu.
 * @return array|WP_Error
 */
function qldn_rest_punches( $req ) {
	$list = $req->get_param( 'punches' );
	if ( ! is_array( $list ) || count( $list ) > 5000 ) {
		return new WP_Error( 'qldn_body', 'Cần mảng "punches" (tối đa 5000 dòng / lần).', array( 'status' => 400 ) );
	}
	$sn   = substr( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $req->get_param( 'device' ) ), 0, 64 );
	$rows = array();
	foreach ( $list as $p ) {
		if ( is_array( $p ) ) {
			$rows[] = array(
				'pin'    => isset( $p['pin'] ) ? (string) $p['pin'] : '',
				'time'   => isset( $p['time'] ) ? (string) $p['time'] : '',
				'state'  => isset( $p['state'] ) ? (string) $p['state'] : '',
				'verify' => isset( $p['verify'] ) ? (string) $p['verify'] : '',
			);
		}
	}
	$stat = qldn_punches_save( $rows, 'api', $sn );
	if ( $sn ) {
		$devices        = qldn_devices();
		$dev            = isset( $devices[ $sn ] ) ? $devices[ $sn ] : array( 'name' => '', 'approved' => 1, 'stamp' => '', 'count' => 0 );
		$dev['last_seen'] = qldn_now();
		$dev['ip']      = 'API';
		$dev['count']   = (int) $dev['count'] + $stat['new'];
		$devices[ $sn ] = $dev;
		qldn_devices_save( $devices );
	}
	return $stat;
}

/* ------------------------------------------------------------------ *
 * Trang quản trị "Máy chấm công"
 * ------------------------------------------------------------------ */

/**
 * Thao tác với máy: duyệt, đổi tên, xoá, tạo khoá API, áp dụng lại, gán mã.
 */
function qldn_handle_device() {
	qldn_check( 'device', 'qldn_hr' );
	global $wpdb;
	$do      = qldn_in( 'do', 'key' );
	$sn      = preg_replace( '/[^A-Za-z0-9_-]/', '', qldn_in( 'sn' ) );
	$devices = qldn_devices();
	$tab     = 'connect';
	if ( 'save' === $do && isset( $devices[ $sn ] ) ) {
		$devices[ $sn ]['name']     = qldn_in( 'name' );
		$devices[ $sn ]['approved'] = qldn_in( 'approved', 'int', 0 ) ? 1 : 0;
		if ( qldn_in( 'reset_stamp', 'int', 0 ) ) {
			$devices[ $sn ]['stamp'] = ''; // Máy gửi lại toàn bộ dữ liệu đang lưu.
		}
		qldn_devices_save( $devices );
		qldn_notice( 'Đã lưu máy ' . esc_html( $sn ) . '.' );
	} elseif ( 'add' === $do && $sn ) {
		$devices[ $sn ] = array( 'name' => qldn_in( 'name' ), 'approved' => 1, 'last_seen' => '', 'ip' => '', 'stamp' => '', 'count' => 0 );
		qldn_devices_save( $devices );
		qldn_notice( 'Đã thêm máy ' . esc_html( $sn ) . '. Cài đặt máy theo hướng dẫn bên dưới.' );
	} elseif ( 'delete' === $do ) {
		unset( $devices[ $sn ] );
		qldn_devices_save( $devices );
		qldn_notice( 'Đã xoá máy khỏi danh sách (dữ liệu chấm công đã nhận vẫn giữ).' );
	} elseif ( 'api_key' === $do ) {
		update_option( 'qldn_api_key', wp_generate_password( 40, false ), false );
		qldn_notice( 'Đã tạo khoá API mới. Cập nhật khoá trong file cấu hình của script đồng bộ.' );
	} elseif ( 'reapply' === $do ) {
		$from = qldn_in( 'from', 'date', current_time( 'Y-m-01' ) );
		$to   = qldn_in( 'to', 'date', qldn_today() );
		qldn_notice( sprintf( 'Đã cập nhật lại %d ngày công từ dữ liệu máy chấm công.', qldn_punch_reapply( $from, $to ) ) );
		$tab = 'log';
	} elseif ( 'map' === $do ) {
		$pin = qldn_pin( qldn_in( 'pin' ) );
		$eid = qldn_in( 'employee_id', 'int', 0 );
		$e   = $eid ? qldn_get( 'employees', $eid ) : null;
		if ( $e && '' !== $pin ) {
			$wpdb->update( qldn_t( 'employees' ), array( 'device_pin' => $pin ), array( 'id' => $eid ) );
			qldn_punch_relink( $eid, $e->device_pin, $pin );
			qldn_notice( sprintf( 'Đã gán mã chấm công %s cho %s.', esc_html( $pin ), esc_html( $e->name ) ) );
		}
		$tab = 'log';
	}
	qldn_go( 'devices', array( 'tab' => $tab ) );
}
add_action( 'admin_post_qldn_device', 'qldn_handle_device' );

/**
 * Trang Máy chấm công.
 */
function qldn_page_devices() {
	$tabs = array(
		'connect' => 'Kết nối máy',
		'import'  => 'Nhập file',
		'log'     => 'Dữ liệu chấm công',
	);
	$tab  = qldn_in( 'tab', 'key', 'connect' );
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'connect';
	echo '<div class="wrap qldn"><h1>Máy chấm công</h1>';
	qldn_print_notices();
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $v ) {
		echo '<a class="nav-tab' . ( $k === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( qldn_url( 'devices', array( 'tab' => $k ) ) ) . '">' . esc_html( $v ) . '</a>';
	}
	echo '</nav>';
	call_user_func( 'qldn_devices_tab_' . $tab );
	echo '</div>';
}

/**
 * Form 1 nút thao tác máy.
 *
 * @param array  $fields  Trường ẩn.
 * @param string $label   Nút.
 * @param string $confirm Câu hỏi xác nhận.
 * @param string $class   Lớp nút.
 */
function qldn_device_button( $fields, $label, $confirm = '', $class = 'button' ) {
	qldn_form_open( 'device', array( 'class' => 'qldn-inline' ) + ( $confirm ? array( 'onsubmit' => 'return confirm(' . wp_json_encode( $confirm ) . ')' ) : array() ) );
	foreach ( $fields as $k => $v ) {
		echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
	}
	echo '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
}

/**
 * Tab kết nối: danh sách máy + hướng dẫn cài đặt máy + khoá API.
 */
function qldn_devices_tab_connect() {
	$devices = qldn_devices();
	$home    = wp_parse_url( home_url() );
	$host    = isset( $home['host'] ) ? $home['host'] : '';
	$https   = isset( $home['scheme'] ) && 'https' === $home['scheme'];
	$port    = isset( $home['port'] ) ? $home['port'] : ( $https ? 443 : 80 );
	$sub     = isset( $home['path'] ) ? trim( $home['path'], '/' ) : '';
	$pretty  = (bool) get_option( 'permalink_structure' );

	echo '<h2>Máy đã kết nối</h2>';
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Số serial (SN)</th><th>Tên / vị trí</th><th>Trạng thái</th><th>Lần cuối kết nối</th><th>IP</th><th class="num">Lần chấm đã nhận</th><th></th></tr></thead><tbody>';
	if ( ! $devices ) {
		echo '<tr><td colspan="7">Chưa có máy nào. Cài đặt máy theo hướng dẫn bên dưới – máy tự xuất hiện ở đây sau lần kết nối đầu tiên, bấm <strong>Duyệt</strong> để bắt đầu nhận dữ liệu.</td></tr>';
	}
	foreach ( $devices as $sn => $d ) {
		$online = $d['last_seen'] && ( strtotime( qldn_now() ) - strtotime( $d['last_seen'] ) ) < 15 * MINUTE_IN_SECONDS;
		echo '<tr><td><code>' . esc_html( $sn ) . '</code></td><td>';
		qldn_form_open( 'device', array( 'class' => 'qldn-inline' ) );
		echo '<input type="hidden" name="do" value="save"><input type="hidden" name="sn" value="' . esc_attr( $sn ) . '"><input type="text" name="name" value="' . esc_attr( $d['name'] ) . '" placeholder="VD: Cửa chính"> ';
		echo '<label><input type="checkbox" name="approved" value="1"' . checked( ! empty( $d['approved'] ), true, false ) . '> Cho phép</label> ';
		echo '<label title="Máy gửi lại toàn bộ dữ liệu đang lưu trong máy ở lần kết nối tới"><input type="checkbox" name="reset_stamp" value="1"> Lấy lại toàn bộ</label> <button class="button button-small">Lưu</button></form></td>';
		echo '<td>' . ( empty( $d['approved'] ) ? qldn_badge( 'Chờ duyệt', 'orange' ) : ( $online ? qldn_badge( 'Đang kết nối', 'green' ) : qldn_badge( 'Đã duyệt', 'blue' ) ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td>' . esc_html( $d['last_seen'] ? qldn_date( $d['last_seen'], true ) : 'Chưa kết nối' ) . '</td><td>' . esc_html( $d['ip'] ) . '</td><td class="num">' . esc_html( number_format( (int) $d['count'], 0, ',', '.' ) ) . '</td><td>';
		qldn_device_button( array( 'do' => 'delete', 'sn' => $sn ), 'Xoá', 'Xoá máy ' . $sn . ' khỏi danh sách?', 'button-link qldn-neg' );
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	echo '<p>';
	qldn_form_open( 'device', array( 'class' => 'qldn-inline' ) );
	echo '<input type="hidden" name="do" value="add"><input type="text" name="sn" placeholder="Số serial máy (SN)" required pattern="[A-Za-z0-9_-]+"> <input type="text" name="name" placeholder="Tên máy"> <button class="button">Thêm & duyệt trước</button></form></p>';

	echo '<h2>Cách 1 – Máy gửi dữ liệu thẳng lên website (khuyên dùng)</h2>';
	echo '<p>Dành cho máy có chức năng <strong>ADMS / Cloud Server / Push</strong> (đa số máy ZKTeco, Ronald Jack, Mitaco, Hikvision dòng ZK đời mới). Không cần máy tính bật, không cần phần mềm.</p>';
	if ( ! $pretty ) {
		echo '<div class="notice notice-error inline"><p>Website đang dùng đường dẫn dạng <code>?p=123</code>. Vào <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">Cài đặt → Đường dẫn tĩnh</a>, chọn <em>Tên bài viết</em> rồi Lưu để máy chấm công gửi được dữ liệu.</p></div>';
	}
	if ( $sub ) {
		echo '<div class="notice notice-warning inline"><p>Website đặt trong thư mục con <code>/' . esc_html( $sub ) . '/</code>: máy chấm công luôn gửi tới <code>/iclock/</code> ở gốc tên miền. Cần chuyển website ra gốc tên miền (hoặc tên miền phụ riêng, vd <code>chamcong.' . esc_html( $host ) . '</code>).</p></div>';
	}
	echo '<ol class="qldn-steps">';
	echo '<li>Trên máy: <strong>Menu → Cài đặt hệ thống / COMM (Truyền thông) → Cloud Server Setting (ADMS)</strong>.</li>';
	echo '<li>Bật <em>Domain name / Enable Domain Name</em> và nhập: <code class="qldn-copy">' . esc_html( $host ) . '</code><br>Máy không có ô tên miền: nhập địa chỉ IP của máy chủ website.</li>';
	echo '<li>Cổng máy chủ (Server Port): <code>' . esc_html( $port ) . '</code>' . ( $https ? ' &nbsp;và bật <em>HTTPS / SSL</em> nếu máy có. Máy đời cũ không hỗ trợ HTTPS: nhờ bên hosting cho phép truy cập <code>http://' . esc_html( $host ) . '/iclock/</code> không chuyển sang https.' : '' ) . '</li>';
	echo '<li>Tắt <em>Proxy</em>. Máy cần cắm mạng có Internet (mục <em>Ethernet / Wi-Fi</em>, DNS 8.8.8.8).</li>';
	echo '<li>Khởi động lại máy. Trong 1–2 phút, máy hiện ở bảng trên với trạng thái <em>Chờ duyệt</em> → tick <strong>Cho phép</strong> → Lưu. Máy gửi lên toàn bộ dữ liệu đang lưu, sau đó mỗi lần chấm được gửi ngay.</li>';
	echo '</ol>';
	echo '<p class="description">Địa chỉ máy gửi tới: <code>' . esc_html( home_url( '/iclock/cdata' ) ) . '</code> – mở thử trong trình duyệt, thấy <code>ERROR: missing SN</code> là website đã sẵn sàng.</p>';
	echo '<p class="description">Bảo mật: chỉ máy đã duyệt mới được nhận dữ liệu. Để chặn chặt hơn, nhập IP Internet tĩnh của văn phòng ở ô <em>Chỉ nhận từ IP</em> bên dưới.</p>';
	qldn_form_open( 'save_device_ips' );
	echo '<p><label>Chỉ nhận từ IP: <input type="text" class="regular-text" name="device_ips" value="' . esc_attr( qldn_opt( 'device_ips' ) ) . '" placeholder="Để trống = mọi IP; nhiều IP cách nhau dấu phẩy"></label> <button class="button">Lưu</button></p></form>';

	echo '<h2>Cách 2 – Nhập file từ máy</h2><p>Máy không kết nối được Internet: tải dữ liệu ra USB (<em>Quản lý USB → Tải dữ liệu chấm công</em>, file <code>attlog.dat</code> hoặc <code>1_attlog.dat</code>) hoặc xuất từ phần mềm Wise Eye / Mitaco, rồi vào tab <a href="' . esc_url( qldn_url( 'devices', array( 'tab' => 'import' ) ) ) . '">Nhập file</a>.</p>';

	$key = (string) get_option( 'qldn_api_key', '' );
	echo '<h2>Cách 3 – Đồng bộ qua máy tính trong văn phòng</h2>';
	echo '<p>Dành cho máy đời cũ không có ADMS, chỉ kết nối trong mạng LAN (cổng 4370). Một máy tính trong văn phòng chạy script <code>tools/zk-sync.py</code> (trong thư mục plugin) định kỳ đọc máy chấm công và gửi lên website. Hướng dẫn chi tiết ở đầu file script.</p>';
	echo '<table class="form-table"><tr><th>Địa chỉ website</th><td><code>' . esc_html( home_url( '/' ) ) . '</code></td></tr><tr><th>Khoá API</th><td>' . ( $key ? '<code class="qldn-copy">' . esc_html( $key ) . '</code> ' : '<em>Chưa tạo</em> ' );
	qldn_device_button( array( 'do' => 'api_key' ), $key ? 'Tạo khoá mới' : 'Tạo khoá API', $key ? 'Khoá cũ sẽ ngừng hoạt động. Tiếp tục?' : '' );
	echo '</td></tr></table>';
}

/**
 * Lưu danh sách IP được phép.
 */
function qldn_handle_save_device_ips() {
	qldn_check( 'save_device_ips', 'qldn_hr' );
	$ips = array_filter( preg_split( '/[\s,;]+/', qldn_in( 'device_ips' ) ), 'rest_is_ip_address' );
	$o   = (array) get_option( 'qldn_settings', array() );
	$o['device_ips'] = implode( ', ', $ips );
	update_option( 'qldn_settings', $o, false );
	qldn_notice( $ips ? 'Chỉ nhận dữ liệu máy chấm công từ: ' . esc_html( $o['device_ips'] ) : 'Nhận dữ liệu từ mọi IP (máy phải được duyệt).' );
	qldn_go( 'devices' );
}
add_action( 'admin_post_qldn_save_device_ips', 'qldn_handle_save_device_ips' );

/**
 * Tab nhập file.
 */
function qldn_devices_tab_import() {
	qldn_form_open( 'punch_import', array( 'enctype' => 'multipart/form-data' ) );
	echo '<table class="form-table"><tr><th>File chấm công</th><td><input type="file" name="file" accept=".dat,.txt,.csv,.log" required><p class="description">.dat (USB của máy), .txt, .csv – tối đa 20 MB. Nhập lại file cũ không bị trùng: lần chấm đã có được bỏ qua.</p></td></tr>';
	echo '<tr><th>Máy (tuỳ chọn)</th><td><input type="text" name="sn" class="regular-text" placeholder="Tên / SN máy để ghi nhận nguồn"></td></tr></table>';
	submit_button( 'Nhập dữ liệu' );
	echo '</form>';
	echo '<h3>Định dạng đọc được</h3><p>Mỗi dòng: <strong>mã chấm công ở cột đầu</strong>, sau đó là ngày và giờ (cùng cột hoặc tách cột, dấu phân cách tab, phẩy, chấm phẩy). Ví dụ:</p>';
	echo '<pre class="qldn-pre">5	2026-10-01 07:58:12	0	1	0	0        ← attlog.dat từ USB
5,01/10/2026 07:58
5;Nguyễn Văn A;01/10/2026;07:58;12:01;13:02;17:31   ← nhiều giờ trên 1 dòng
"0005","Nguyễn Văn A","2026-10-01","07:58:12"</pre>';
	echo '<p class="description">Dòng tiêu đề và dòng không có ngày giờ được bỏ qua. Mã <code>0005</code> và <code>5</code> được coi là một. File Excel (.xlsx): mở bằng Excel → <em>Lưu dạng</em> → <em>CSV UTF-8</em>.</p>';
}

/**
 * Tab dữ liệu chấm công: tổng hợp theo ngày (giờ vào / ra, đi muộn, về sớm) và mã chưa gán.
 */
function qldn_devices_tab_log() {
	global $wpdb;
	$t    = qldn_t( 'punches' );
	$from = qldn_in( 'from', 'date', gmdate( 'Y-m-d', strtotime( qldn_today() . ' -6 days' ) ) );
	$to   = qldn_in( 'to', 'date', qldn_today() );
	$emp  = qldn_in( 'employee', 'int', 0 );
	$o    = qldn_opt();
	$args = array( $from . ' 00:00:00', $to . ' 23:59:59' );

	$unknown = $wpdb->get_results( "SELECT pin, COUNT(*) AS n, MIN(punch_time) AS first, MAX(punch_time) AS last FROM $t WHERE employee_id = 0 GROUP BY pin ORDER BY pin LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL
	if ( $unknown ) {
		$opts = qldn_employee_options();
		echo '<h2>Mã chấm công chưa gán nhân viên</h2><table class="widefat striped qldn-table qldn-narrow"><thead><tr><th>Mã trên máy</th><th class="num">Lần chấm</th><th>Từ – đến</th><th>Gán cho nhân viên</th></tr></thead><tbody>';
		foreach ( $unknown as $u ) {
			echo '<tr><td><code>' . esc_html( $u->pin ) . '</code></td><td class="num">' . esc_html( $u->n ) . '</td><td>' . esc_html( qldn_date( $u->first ) . ' – ' . qldn_date( $u->last ) ) . '</td><td>';
			qldn_form_open( 'device', array( 'class' => 'qldn-inline' ) );
			echo '<input type="hidden" name="do" value="map"><input type="hidden" name="pin" value="' . esc_attr( $u->pin ) . '"><select name="employee_id" required><option value="">Chọn nhân viên…</option>';
			qldn_options( $opts, '' );
			echo '</select> <button class="button button-small">Gán</button></form></td></tr>';
		}
		echo '</tbody></table>';
	}

	echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-devices"><input type="hidden" name="tab" value="log">Từ <input type="date" name="from" value="' . esc_attr( $from ) . '"> đến <input type="date" name="to" value="' . esc_attr( $to ) . '"> <select name="employee"><option value="">Mọi nhân viên</option>';
	qldn_options( qldn_employee_options( true ), $emp );
	echo '</select> <button class="button">Xem</button></form>';

	$sql  = 'SELECT p.employee_id, e.name, e.code, DATE(p.punch_time) AS d, MIN(p.punch_time) AS tin, MAX(p.punch_time) AS tout, COUNT(*) AS n FROM ' . $t . ' p JOIN ' . qldn_t( 'employees' ) . ' e ON e.id = p.employee_id WHERE p.punch_time BETWEEN %s AND %s';
	$sql .= $emp ? $wpdb->prepare( ' AND p.employee_id = %d', $emp ) : '';
	$rows = $wpdb->get_results( $wpdb->prepare( $sql . ' GROUP BY p.employee_id, e.name, e.code, DATE(p.punch_time) ORDER BY d DESC, e.name LIMIT 1000', $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL

	$grace    = (int) $o['late_grace'];
	$late_sum = array();
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Ngày</th><th>Nhân viên</th><th>Giờ vào</th><th>Giờ ra</th><th class="num">Số giờ</th><th>Đi muộn</th><th>Về sớm</th><th class="num">Lần chấm</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="8">Chưa có dữ liệu chấm công trong khoảng này.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$in    = strtotime( $r->tin );
		$out   = strtotime( $r->tout ) - $in >= 300 ? strtotime( $r->tout ) : 0;
		$late  = (int) floor( ( $in - strtotime( $r->d . ' ' . $o['shift_start'] . ':00' ) ) / 60 );
		$early = $out ? (int) floor( ( strtotime( $r->d . ' ' . $o['shift_end'] . ':00' ) - $out ) / 60 ) : 0;
		$late  = $late > $grace ? $late : 0;
		$early = $early > $grace ? $early : 0;
		if ( ! isset( $late_sum[ $r->employee_id ] ) ) {
			$late_sum[ $r->employee_id ] = array( $r->name, 0, 0, 0, 0 );
		}
		$late_sum[ $r->employee_id ][1] += $late ? 1 : 0;
		$late_sum[ $r->employee_id ][2] += $late;
		$late_sum[ $r->employee_id ][3] += $early ? 1 : 0;
		$late_sum[ $r->employee_id ][4] += $early;
		echo '<tr><td>' . esc_html( qldn_date( $r->d ) ) . '</td><td>' . esc_html( $r->name ) . ' <small>' . esc_html( $r->code ) . '</small></td><td>' . esc_html( gmdate( 'H:i', $in ) ) . '</td><td>' . ( $out ? esc_html( gmdate( 'H:i', $out ) ) : qldn_badge( 'Thiếu giờ ra', 'orange' ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="num">' . esc_html( $out ? qldn_qty( ( $out - $in ) / 3600 ) : '' ) . '</td><td>' . ( $late ? qldn_badge( $late . ' phút', 'red' ) : '' ) . '</td><td>' . ( $early ? qldn_badge( $early . ' phút', 'red' ) : '' ) . '</td><td class="num">' . esc_html( $r->n ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';

	if ( $late_sum ) {
		echo '<h2>Tổng hợp đi muộn / về sớm</h2><table class="widefat striped qldn-table qldn-narrow"><thead><tr><th>Nhân viên</th><th class="num">Số lần muộn</th><th class="num">Tổng phút muộn</th><th class="num">Số lần về sớm</th><th class="num">Tổng phút về sớm</th></tr></thead><tbody>';
		foreach ( $late_sum as $s ) {
			echo '<tr><td>' . esc_html( $s[0] ) . '</td><td class="num">' . esc_html( $s[1] ) . '</td><td class="num">' . esc_html( $s[2] ) . '</td><td class="num">' . esc_html( $s[3] ) . '</td><td class="num">' . esc_html( $s[4] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '<p class="description">Ca làm: ' . esc_html( $o['shift_start'] . ' – ' . $o['shift_end'] ) . ', cho phép lệch ' . esc_html( $grace ) . ' phút (sửa ở <a href="' . esc_url( qldn_url( 'settings' ) ) . '">Cài đặt</a>). Giờ vào = lần chấm đầu, giờ ra = lần chấm cuối trong ngày; quẹt lại trong 5 phút tính là 1 lần.</p>';
	echo '<p>';
	qldn_device_button( array( 'do' => 'reapply', 'from' => $from, 'to' => $to ), 'Cập nhật lại bảng công từ ' . qldn_date( $from ) . ' đến ' . qldn_date( $to ), 'Ghi lại giờ vào / ra từ dữ liệu máy vào bảng công (không đụng tháng đã chốt lương)?' );
	echo '</p>';
}
