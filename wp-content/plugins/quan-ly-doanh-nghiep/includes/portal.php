<?php
/**
 * Cổng nhân viên "Của tôi": chấm công trên web / điện thoại, xem công tháng, quỹ phép, gửi đơn,
 * xem phiếu lương của chính mình, duyệt đơn của nhân viên mình quản lý.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nhân viên tự chấm công vào / ra: ghi như 1 lần chấm của máy chấm công (tính đi muộn / về sớm theo ca).
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
	$now   = qldn_now();
	$shift = qldn_employee_shift( (int) $e->id );
	$date  = qldn_punch_work_date( $shift, $now );
	if ( qldn_period_locked( substr( $date, 0, 7 ) ) ) {
		return new WP_Error( 'qldn_locked', 'Tháng này đã chốt lương.' );
	}
	$pin = $e->device_pin ? qldn_pin( $e->device_pin ) : 'WEB' . $e->id;
	$wpdb->suppress_errors( true );
	$wpdb->insert( qldn_t( 'punches' ), array( 'pin' => $pin, 'punch_time' => $now, 'employee_id' => $e->id, 'device_sn' => 'web', 'source' => 'web', 'created_at' => $now ) );
	$wpdb->suppress_errors( false );
	qldn_punch_apply_many( array( (int) $e->id => array( $date => true ) ) );
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT check_in, check_out FROM ' . qldn_t( 'attendance' ) . ' WHERE employee_id = %d AND work_date = %s', $e->id, $date ) );
	return $row && $row->check_out ? 'out' : 'in';
}

/**
 * Xử lý nút chấm công.
 */
function qldn_handle_checkin() {
	qldn_check( 'checkin', 'qldn_access' );
	$r = qldn_self_checkin( get_current_user_id() );
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
	} else {
		qldn_notice( ( 'in' === $r ? 'Đã chấm công vào ca lúc ' : 'Đã chấm công ra ca lúc ' ) . current_time( 'H:i' ) . '.' );
	}
	qldn_go( 'me' === qldn_in( 'back', 'key' ) ? 'me' : '' );
}
add_action( 'admin_post_qldn_checkin', 'qldn_handle_checkin' );

/**
 * Khối chấm công hôm nay.
 *
 * @param string $back Trang quay về.
 */
function qldn_checkin_box( $back = '' ) {
	global $wpdb;
	$me = qldn_current_employee();
	if ( ! $me ) {
		return;
	}
	$shift = qldn_employee_shift( (int) $me->id );
	$date  = qldn_punch_work_date( $shift, qldn_now() );
	$row   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'attendance' ) . ' WHERE employee_id = %d AND work_date = %s', $me->id, $date ) );
	echo '<div class="qldn-checkin"><div><strong>Xin chào ' . esc_html( $me->name ) . '</strong> · ' . esc_html( $shift->name . ' ' . substr( $shift->start_time, 0, 5 ) . '–' . substr( $shift->end_time, 0, 5 ) ) . '<br>';
	if ( $row && $row->check_in ) {
		echo 'Vào ca: ' . esc_html( substr( $row->check_in, 0, 5 ) ) . ( $row->check_out ? ' · Ra ca: ' . esc_html( substr( $row->check_out, 0, 5 ) ) : '' ) . ( $row->late_min ? ' ' . qldn_badge( 'muộn ' . $row->late_min . ' phút', 'red' ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
	} else {
		echo 'Hôm nay bạn chưa chấm công.';
	}
	echo '</div>';
	qldn_form_open( 'checkin' );
	echo '<input type="hidden" name="back" value="' . esc_attr( $back ) . '"><button class="button button-primary button-hero">' . ( $row && $row->check_in ? ( $row->check_out ? 'Chấm công ra ca lại' : 'Chấm công ra ca' ) : 'Chấm công vào ca' ) . '</button></form></div>';
}

/**
 * Phiếu lương của chính mình (chỉ tháng đã chốt).
 */
function qldn_handle_my_payslip() {
	qldn_check( 'my_payslip', 'qldn_access' );
	$me     = qldn_current_employee();
	$period = qldn_period();
	if ( ! $me ) {
		wp_die( 'Tài khoản chưa liên kết với nhân viên.' );
	}
	$rows = qldn_payroll_rows( $period, (int) $me->id );
	if ( ! $rows || 'draft' === $rows[0]->status ) {
		wp_die( 'Bảng lương tháng này chưa chốt.' );
	}
	qldn_payslip_output( $period, (int) $me->id );
}
add_action( 'admin_post_qldn_my_payslip', 'qldn_handle_my_payslip' );

/**
 * Trang "Của tôi".
 */
function qldn_page_me() {
	global $wpdb;
	$me = qldn_current_employee();
	echo '<div class="wrap qldn"><h1>Của tôi</h1>';
	qldn_print_notices();
	if ( ! $me ) {
		echo '<p>Tài khoản của bạn chưa liên kết với hồ sơ nhân viên. Nhờ bộ phận nhân sự vào <em>Nhân viên → Sửa → Tài khoản đăng nhập</em> để liên kết.</p></div>';
		return;
	}
	qldn_checkin_box( 'me' );

	$period = qldn_period();
	$ts     = strtotime( $period . '-01' );
	$sum    = qldn_attendance_summary( $me->id, $period );
	$bal    = qldn_leave_balance( $me->id );
	echo '<div class="qldn-cards">';
	echo '<div class="qldn-card"><span>Công tháng ' . esc_html( gmdate( 'm/Y', $ts ) ) . '</span><strong>' . esc_html( qldn_qty( $sum['work_days'] ) ) . ' ngày</strong><em>Phép / lễ ' . esc_html( qldn_qty( $sum['paid_days'] ) ) . ' · tăng ca ' . esc_html( qldn_qty( $sum['ot_hours'] ) ) . 'h</em></div>';
	echo '<div class="qldn-card' . ( $sum['late_min'] ? ' qldn-card-warn' : '' ) . '"><span>Đi muộn / về sớm</span><strong>' . esc_html( $sum['late_min'] ) . ' phút</strong><em>Có lý do: gửi đơn giải trình</em></div>';
	echo '<div class="qldn-card"><span>Phép năm ' . esc_html( current_time( 'Y' ) ) . ' còn lại</span><strong>' . esc_html( qldn_qty( $bal['remaining'] ) ) . ' ngày</strong><em>Được ' . esc_html( qldn_qty( $bal['entitled'] + $bal['adjust'] ) ) . ' · đã nghỉ ' . esc_html( qldn_qty( $bal['used'] ) ) . ( $bal['pending'] ? ' · chờ duyệt ' . esc_html( qldn_qty( $bal['pending'] ) ) : '' ) . '</em></div>';
	echo '</div>';

	// Lịch công tháng.
	echo '<h2>Bảng công của tôi</h2><form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-me">Tháng <input type="month" name="period" value="' . esc_attr( $period ) . '"> <button class="button">Xem</button></form>';
	$days = (int) gmdate( 't', $ts );
	$att  = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'attendance' ) . ' WHERE employee_id = %d AND work_date BETWEEN %s AND %s', $me->id, $period . '-01', $period . '-' . $days ) ) as $r ) {
		$att[ $r->work_date ] = $r;
	}
	$marks = qldn_marks();
	echo '<div class="qldn-cal"><div class="qldn-cal-h">T2</div><div class="qldn-cal-h">T3</div><div class="qldn-cal-h">T4</div><div class="qldn-cal-h">T5</div><div class="qldn-cal-h">T6</div><div class="qldn-cal-h">T7</div><div class="qldn-cal-h">CN</div>';
	$pad = ( (int) gmdate( 'w', $ts ) + 6 ) % 7;
	echo str_repeat( '<div class="qldn-cal-d qldn-cal-empty"></div>', $pad ); // phpcs:ignore WordPress.Security.EscapeOutput
	$hol = qldn_holidays();
	for ( $d = 1; $d <= $days; $d++ ) {
		$ds  = sprintf( '%s-%02d', $period, $d );
		$r   = $att[ $ds ] ?? null;
		$cls = isset( $hol[ $ds ] ) ? ' qldn-hol' : ( qldn_is_workday( $ds ) ? '' : ' qldn-sun' );
		echo '<div class="qldn-cal-d' . esc_attr( $cls ) . ( $ds === qldn_today() ? ' qldn-cal-today' : '' ) . '"><b>' . esc_html( $d ) . '</b>';
		if ( $r && $r->mark ) {
			echo '<span class="qldn-cal-mark" title="' . esc_attr( $marks[ $r->mark ][0] ?? '' ) . '">' . esc_html( qldn_mark_text( $r ) ) . '</span>';
		}
		if ( $r && $r->check_in ) {
			echo '<small>' . esc_html( substr( $r->check_in, 0, 5 ) . ( $r->check_out ? '–' . substr( $r->check_out, 0, 5 ) : '' ) ) . '</small>';
		}
		if ( $r && ( $r->late_min || $r->early_min ) ) {
			echo '<small class="qldn-neg">' . esc_html( trim( ( $r->late_min ? 'muộn ' . $r->late_min . "'" : '' ) . ' ' . ( $r->early_min ? 'sớm ' . $r->early_min . "'" : '' ) ) ) . '</small>';
		}
		if ( isset( $hol[ $ds ] ) ) {
			echo '<small>' . esc_html( $hol[ $ds ] ) . '</small>';
		}
		echo '</div>';
	}
	echo '</div>';

	echo '<div class="qldn-cols"><div class="qldn-col"><h2>Gửi đơn</h2>';
	qldn_request_form( false, 'me' );
	echo '</div><div class="qldn-col"><h2>Đơn của tôi</h2>';
	$mine = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'requests' ) . ' WHERE employee_id = %d ORDER BY id DESC LIMIT 30', $me->id ) );
	qldn_request_table( $mine, 'me', true );

	$team = $wpdb->get_results( $wpdb->prepare( 'SELECT r.*, e.name, e.code FROM ' . qldn_t( 'requests' ) . ' r JOIN ' . qldn_t( 'employees' ) . " e ON e.id = r.employee_id WHERE e.manager_id = %d AND r.status = 'pending' ORDER BY r.id", $me->id ) );
	if ( $team ) {
		echo '<h2>Đơn chờ tôi duyệt</h2>';
		qldn_request_table( $team, 'me' );
	}

	echo '<h2>Phiếu lương</h2><table class="widefat striped qldn-table"><tbody>';
	$slips = $wpdb->get_results( $wpdb->prepare( 'SELECT period, net, status FROM ' . qldn_t( 'payroll' ) . " WHERE employee_id = %d AND status IN ('locked','paid') ORDER BY period DESC LIMIT 12", $me->id ) );
	if ( ! $slips ) {
		echo '<tr><td>Chưa có bảng lương đã chốt.</td></tr>';
	}
	foreach ( $slips as $s ) {
		echo '<tr><td>Tháng ' . esc_html( substr( $s->period, 5 ) . '/' . substr( $s->period, 0, 4 ) ) . '</td><td class="num"><strong>' . esc_html( qldn_money( $s->net ) ) . '</strong></td><td>' . ( 'paid' === $s->status ? qldn_badge( 'Đã trả', 'green' ) : qldn_badge( 'Đã chốt', 'blue' ) ) . '</td><td><a target="_blank" href="' . esc_url( qldn_action_url( 'my_payslip', array( 'period' => $s->period ) ) ) . '">Xem phiếu lương</a></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table></div></div></div>';
}
