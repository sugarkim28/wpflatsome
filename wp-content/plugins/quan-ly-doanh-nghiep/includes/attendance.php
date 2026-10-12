<?php
/**
 * Chấm công: bảng công tháng (ký hiệu X, N, P, L, K, Ô; "X+2" = đi làm + 2 giờ tăng ca)
 * và nhân viên tự chấm vào / ra ca.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ký hiệu chấm công => [nhãn, ngày công thực tế, ngày nghỉ hưởng lương].
 *
 * @return array
 */
function qldn_marks() {
	return array(
		'X' => array( 'Đi làm cả ngày', 1, 0 ),
		'N' => array( 'Làm nửa ngày', 0.5, 0 ),
		'P' => array( 'Nghỉ phép có lương', 0, 1 ),
		'L' => array( 'Nghỉ lễ, tết có lương', 0, 1 ),
		'K' => array( 'Nghỉ không lương', 0, 0 ),
		'O' => array( 'Nghỉ ốm (BHXH chi trả)', 0, 0 ),
	);
}

/**
 * Đọc ô chấm công: "X", "x+2", "N+1.5", "+3" (chỉ tăng ca).
 *
 * @param string $v Giá trị ô.
 * @return array|null [mark, ot_hours] hoặc null nếu ô trống, false nếu sai.
 */
function qldn_parse_mark( $v ) {
	$v = mb_strtoupper( str_replace( array( ' ', ',' ), array( '', '.' ), trim( (string) $v ) ), 'UTF-8' );
	if ( '' === $v ) {
		return null;
	}
	$v = str_replace( 'Ô', 'O', $v );
	if ( ! preg_match( '/^([A-Z]?)(?:\+(\d+(?:\.\d+)?))?$/', $v, $m ) ) {
		return false;
	}
	$mark = $m[1];
	if ( '' !== $mark && ! isset( qldn_marks()[ $mark ] ) ) {
		return false;
	}
	$ot = isset( $m[2] ) ? min( 24, (float) $m[2] ) : 0;
	if ( '' === $mark && ! $ot ) {
		return false;
	}
	return array( $mark, $ot );
}

/**
 * Ô hiển thị từ dòng chấm công.
 *
 * @param object $r Dòng.
 * @return string
 */
function qldn_mark_text( $r ) {
	$ot = (float) $r->ot_hours;
	return $r->mark . ( $ot > 0 ? '+' . qldn_qty( $ot ) : '' );
}

/**
 * Tháng đã chốt lương thì không sửa công.
 *
 * @param string $period Y-m.
 * @return bool
 */
function qldn_period_locked( $period ) {
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . qldn_t( 'payroll' ) . " WHERE period = %s AND status IN ('locked','paid')", $period ) );
}

/**
 * Tháng đang xem (Y-m), mặc định tháng hiện tại.
 *
 * @return string
 */
function qldn_period() {
	$p = qldn_in( 'period' );
	return preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $p ) ? $p : current_time( 'Y-m' );
}

/**
 * Lưu bảng công tháng.
 */
function qldn_handle_attendance() {
	qldn_check( 'attendance', 'qldn_hr' );
	global $wpdb;
	$period = qldn_period();
	if ( qldn_period_locked( $period ) ) {
		qldn_notice( 'Tháng ' . $period . ' đã chốt lương, không sửa được bảng công. Mở khoá bảng lương trước.', 'error' );
		qldn_go( 'attendance', array( 'period' => $period ) );
	}
	$t      = qldn_t( 'attendance' );
	$days   = (int) gmdate( 't', strtotime( $period . '-01' ) );
	$bad    = array();
	$saved  = 0;
	foreach ( qldn_in( 'att', 'array', array() ) as $eid => $cells ) {
		$eid = (int) $eid;
		if ( ! $eid || ! is_array( $cells ) ) {
			continue;
		}
		$existing = array();
		foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE employee_id = %d AND work_date BETWEEN %s AND %s", $eid, $period . '-01', $period . '-' . $days ) ) as $r ) { // phpcs:ignore WordPress.DB.PreparedSQL
			$existing[ $r->work_date ] = $r;
		}
		foreach ( $cells as $day => $val ) {
			$day = (int) $day;
			if ( $day < 1 || $day > $days ) {
				continue;
			}
			$date = $period . '-' . str_pad( (string) $day, 2, '0', STR_PAD_LEFT );
			$p    = qldn_parse_mark( sanitize_text_field( wp_unslash( $val ) ) );
			$old  = isset( $existing[ $date ] ) ? $existing[ $date ] : null;
			if ( false === $p ) {
				$bad[] = $day . '/' . substr( $period, 5 );
				continue;
			}
			if ( null === $p ) {
				if ( $old && ! $old->check_in ) {
					$wpdb->delete( $t, array( 'id' => $old->id ) );
				} elseif ( $old ) {
					$wpdb->update( $t, array( 'mark' => '', 'ot_hours' => 0 ), array( 'id' => $old->id ) );
				}
				continue;
			}
			if ( $old ) {
				if ( $old->mark !== $p[0] || (float) $old->ot_hours !== (float) $p[1] ) {
					$wpdb->update( $t, array( 'mark' => $p[0], 'ot_hours' => $p[1] ), array( 'id' => $old->id ) );
				}
			} else {
				$wpdb->insert( $t, array( 'employee_id' => $eid, 'work_date' => $date, 'mark' => $p[0], 'ot_hours' => $p[1] ) );
			}
			++$saved;
		}
	}
	if ( $bad ) {
		qldn_notice( 'Ô không hợp lệ (đã bỏ qua): ' . esc_html( implode( ', ', array_unique( $bad ) ) ) . '. Dùng X, N, P, L, K, O, có thể thêm "+giờ tăng ca" (vd X+2).', 'warning' );
	}
	qldn_notice( 'Đã lưu bảng công tháng ' . esc_html( substr( $period, 5 ) . '/' . substr( $period, 0, 4 ) ) . '.' );
	qldn_go( 'attendance', array( 'period' => $period ) );
}
add_action( 'admin_post_qldn_attendance', 'qldn_handle_attendance' );

/**
 * Nhân viên tự chấm công vào / ra ca.
 *
 * @param int $user_id Người dùng.
 * @return string|WP_Error 'in' | 'out'.
 */
function qldn_self_checkin( $user_id ) {
	global $wpdb;
	$e = qldn_current_employee( $user_id );
	if ( ! $e ) {
		return new WP_Error( 'qldn_emp', 'Tài khoản chưa liên kết với nhân viên nào.' );
	}
	$today = qldn_today();
	if ( qldn_period_locked( substr( $today, 0, 7 ) ) ) {
		return new WP_Error( 'qldn_locked', 'Tháng này đã chốt lương.' );
	}
	$t   = qldn_t( 'attendance' );
	$now = current_time( 'H:i:s' );
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE employee_id = %d AND work_date = %s", $e->id, $today ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	if ( ! $row ) {
		$wpdb->insert( $t, array( 'employee_id' => $e->id, 'work_date' => $today, 'mark' => 'X', 'check_in' => $now ) );
		return 'in';
	}
	if ( ! $row->check_in ) {
		$wpdb->update( $t, array( 'check_in' => $now, 'mark' => $row->mark ? $row->mark : 'X' ), array( 'id' => $row->id ) );
		return 'in';
	}
	$data = array( 'check_out' => $now );
	// Làm dưới nửa số giờ chuẩn thì tính nửa công.
	$hours = ( strtotime( $today . ' ' . $now ) - strtotime( $today . ' ' . $row->check_in ) ) / 3600;
	if ( 'X' === $row->mark && $hours < (float) qldn_opt( 'work_hours' ) / 2 ) {
		$data['mark'] = 'N';
	}
	$wpdb->update( $t, $data, array( 'id' => $row->id ) );
	return 'out';
}

/**
 * Xử lý nút chấm công ở trang Tổng quan.
 */
function qldn_handle_checkin() {
	qldn_check( 'checkin', 'qldn_access' );
	$r = qldn_self_checkin( get_current_user_id() );
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
	} else {
		qldn_notice( ( 'in' === $r ? 'Đã chấm công vào ca lúc ' : 'Đã chấm công ra ca lúc ' ) . current_time( 'H:i' ) . '.' );
	}
	qldn_go();
}
add_action( 'admin_post_qldn_checkin', 'qldn_handle_checkin' );

/**
 * Tổng hợp công tháng của 1 nhân viên.
 *
 * @param int    $employee_id Nhân viên.
 * @param string $period      Y-m.
 * @return array work_days, paid_days, ot_hours, hours (giờ làm thực tế), marks (đếm theo ký hiệu).
 */
function qldn_attendance_summary( $employee_id, $period ) {
	global $wpdb;
	$days  = (int) gmdate( 't', strtotime( $period . '-01' ) );
	$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'attendance' ) . ' WHERE employee_id = %d AND work_date BETWEEN %s AND %s', $employee_id, $period . '-01', $period . '-' . $days ) );
	$marks = qldn_marks();
	$wh    = (float) qldn_opt( 'work_hours' );
	$out   = array( 'work_days' => 0, 'paid_days' => 0, 'ot_hours' => 0, 'hours' => 0, 'marks' => array_fill_keys( array_keys( $marks ), 0 ) );
	foreach ( $rows as $r ) {
		$out['ot_hours'] += (float) $r->ot_hours;
		if ( ! isset( $marks[ $r->mark ] ) ) {
			continue;
		}
		$m = $marks[ $r->mark ];
		++$out['marks'][ $r->mark ];
		$out['work_days'] += $m[1];
		$out['paid_days'] += $m[2];
		if ( $m[1] > 0 ) {
			if ( $r->check_in && $r->check_out && $r->check_out > $r->check_in ) {
				$out['hours'] += min( $wh * $m[1], ( strtotime( '2000-01-01 ' . $r->check_out ) - strtotime( '2000-01-01 ' . $r->check_in ) ) / 3600 );
			} else {
				$out['hours'] += $wh * $m[1];
			}
		}
	}
	$out['hours'] = round( $out['hours'], 2 );
	return $out;
}

/**
 * Trang bảng chấm công.
 */
function qldn_page_attendance() {
	global $wpdb;
	$period = qldn_period();
	$ts     = strtotime( $period . '-01' );
	$days   = (int) gmdate( 't', $ts );
	$locked = qldn_period_locked( $period );
	$emps   = $wpdb->get_results( 'SELECT * FROM ' . qldn_t( 'employees' ) . " WHERE status = 'active' ORDER BY department, name" );
	$grid   = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'attendance' ) . ' WHERE work_date BETWEEN %s AND %s', $period . '-01', $period . '-' . $days ) ) as $r ) {
		$grid[ $r->employee_id ][ (int) substr( $r->work_date, 8, 2 ) ] = $r;
	}
	$dow = array( 'CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7' );

	echo '<div class="wrap qldn"><h1>Chấm công</h1>';
	qldn_print_notices();
	echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-attendance">Tháng <input type="month" name="period" value="' . esc_attr( $period ) . '"> <button class="button">Xem</button> ';
	echo '<a class="button" href="' . esc_url( qldn_url( 'attendance', array( 'period' => gmdate( 'Y-m', strtotime( '-1 month', $ts ) ) ) ) ) . '">‹ Tháng trước</a> <a class="button" href="' . esc_url( qldn_url( 'attendance', array( 'period' => gmdate( 'Y-m', strtotime( '+1 month', $ts ) ) ) ) ) . '">Tháng sau ›</a> ';
	echo '<a class="button" href="' . esc_url( qldn_url( 'devices', array( 'tab' => 'log' ) ) ) . '">Dữ liệu máy chấm công</a> <a class="button button-primary" href="' . esc_url( qldn_url( 'payroll', array( 'period' => $period ) ) ) . '">Bảng lương tháng này</a></form>';

	echo '<p class="qldn-legend">';
	foreach ( qldn_marks() as $k => $m ) {
		echo '<span><code>' . esc_html( $k ) . '</code> ' . esc_html( $m[0] ) . '</span> ';
	}
	echo '<span><code>X+2</code> đi làm + 2 giờ tăng ca</span></p>';
	if ( $locked ) {
		echo '<div class="notice notice-warning inline"><p>Tháng này đã chốt lương – bảng công chỉ xem.</p></div>';
	}
	if ( ! $emps ) {
		echo '<p>Chưa có nhân viên. <a href="' . esc_url( qldn_url( 'employees', array( 'edit' => 'new' ) ) ) . '">Thêm nhân viên</a>.</p></div>';
		return;
	}

	qldn_form_open( 'attendance', array( 'class' => 'qldn-att-form' ) );
	echo '<input type="hidden" name="period" value="' . esc_attr( $period ) . '">';
	echo '<div class="qldn-att-wrap"><table class="widefat qldn-att"><thead><tr><th class="qldn-att-name">Nhân viên</th>';
	for ( $d = 1; $d <= $days; $d++ ) {
		$w = (int) gmdate( 'w', strtotime( $period . '-' . $d ) );
		echo '<th class="' . ( 0 === $w ? 'qldn-sun' : '' ) . '">' . esc_html( $d ) . '<br><small>' . esc_html( $dow[ $w ] ) . '</small></th>';
	}
	echo '<th>Công</th><th>Phép/Lễ</th><th>Tăng ca</th></tr></thead><tbody>';
	foreach ( $emps as $e ) {
		$sum = qldn_attendance_summary( $e->id, $period );
		echo '<tr><th class="qldn-att-name">' . esc_html( $e->name ) . '<br><small>' . esc_html( $e->code ) . '</small>' . ( $locked ? '' : ' <a href="#" class="qldn-fill" title="Điền X các ngày trống trừ Chủ nhật">+X</a>' ) . '</th>';
		for ( $d = 1; $d <= $days; $d++ ) {
			$r     = isset( $grid[ $e->id ][ $d ] ) ? $grid[ $e->id ][ $d ] : null;
			$w     = (int) gmdate( 'w', strtotime( $period . '-' . $d ) );
			$title = $r && $r->check_in ? 'Vào ' . substr( $r->check_in, 0, 5 ) . ( $r->check_out ? ' – Ra ' . substr( $r->check_out, 0, 5 ) : '' ) : '';
			echo '<td class="' . ( 0 === $w ? 'qldn-sun' : '' ) . '"><input type="text" maxlength="6" name="att[' . esc_attr( $e->id ) . '][' . esc_attr( $d ) . ']" value="' . esc_attr( $r ? qldn_mark_text( $r ) : '' ) . '" title="' . esc_attr( $title ) . '"' . ( $locked ? ' readonly' : '' ) . '></td>';
		}
		echo '<td class="num"><strong>' . esc_html( qldn_qty( $sum['work_days'] ) ) . '</strong></td><td class="num">' . esc_html( qldn_qty( $sum['paid_days'] ) ) . '</td><td class="num">' . esc_html( qldn_qty( $sum['ot_hours'] ) ) . 'h</td></tr>';
	}
	echo '</tbody></table></div>';
	if ( ! $locked ) {
		submit_button( 'Lưu bảng công' );
	}
	echo '</form><p class="description">Ngày công chuẩn: ' . esc_html( qldn_qty( qldn_opt( 'standard_days' ) ) ) . ' ngày/tháng (sửa ở Cài đặt). Ô có giờ vào/ra do nhân viên tự chấm – rê chuột lên ô để xem.</p></div>';
}
