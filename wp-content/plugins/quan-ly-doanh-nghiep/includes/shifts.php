<?php
/**
 * Ca làm việc (kể cả ca đêm qua 0 giờ), ngày nghỉ hằng tuần, ngày lễ; đi muộn / về sớm.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách ca: id => object (id 0 = ca mặc định trong Cài đặt).
 *
 * @return array
 */
function qldn_shifts() {
	global $wpdb;
	static $cache = null;
	if ( null !== $cache && ! defined( 'QLDN_TESTING' ) ) {
		return $cache;
	}
	$o     = qldn_opt();
	$cache = array(
		0 => (object) array( 'id' => 0, 'name' => 'Ca hành chính (mặc định)', 'start_time' => $o['shift_start'] . ':00', 'end_time' => $o['shift_end'] . ':00', 'break_min' => (int) $o['break_min'] ),
	);
	foreach ( $wpdb->get_results( 'SELECT * FROM ' . qldn_t( 'shifts' ) . ' ORDER BY start_time' ) as $s ) {
		$cache[ (int) $s->id ] = $s;
	}
	return $cache;
}

/**
 * Ca của nhân viên.
 *
 * @param int $employee_id Nhân viên.
 * @return object start_time, end_time, break_min, name, overnight.
 */
function qldn_employee_shift( $employee_id ) {
	static $map = array();
	if ( ! isset( $map[ $employee_id ] ) || defined( 'QLDN_TESTING' ) ) {
		$e                   = qldn_get( 'employees', $employee_id );
		$map[ $employee_id ] = $e ? (int) $e->shift_id : 0;
	}
	$all            = qldn_shifts();
	$s              = clone ( $all[ $map[ $employee_id ] ] ?? $all[0] );
	$s->overnight   = $s->end_time <= $s->start_time;
	return $s;
}

/**
 * Ngày công của 1 lần chấm: ca đêm, lần chấm buổi sáng (trước giờ kết thúc ca + 4 tiếng) thuộc ca của hôm trước.
 *
 * @param object $shift Ca.
 * @param string $time  Y-m-d H:i:s.
 * @return string Y-m-d.
 */
function qldn_punch_work_date( $shift, $time ) {
	$date = substr( $time, 0, 10 );
	if ( $shift->overnight ) {
		$limit = strtotime( $date . ' ' . $shift->end_time ) + 4 * HOUR_IN_SECONDS;
		if ( strtotime( $time ) < $limit && substr( $time, 11 ) < $shift->start_time ) {
			return gmdate( 'Y-m-d', strtotime( $date . ' -1 day' ) );
		}
	}
	return $date;
}

/**
 * Giờ bắt đầu / kết thúc ca của 1 ngày công (timestamp).
 *
 * @param object $shift Ca.
 * @param string $date  Y-m-d.
 * @return array [start, end].
 */
function qldn_shift_bounds( $shift, $date ) {
	$start = strtotime( $date . ' ' . $shift->start_time );
	$end   = strtotime( $date . ' ' . $shift->end_time );
	if ( $end <= $start ) {
		$end += DAY_IN_SECONDS;
	}
	return array( $start, $end );
}

/**
 * Ngày nghỉ hằng tuần (0 = Chủ nhật … 6 = Thứ bảy).
 *
 * @return int[]
 */
function qldn_weekly_off() {
	return array_map( 'intval', array_filter( explode( ',', (string) qldn_opt( 'weekly_off' ) ), 'strlen' ) );
}

/**
 * Ngày lễ đã khai báo: Y-m-d => tên.
 *
 * @return array
 */
function qldn_holidays() {
	$out = array();
	foreach ( explode( "\n", (string) qldn_opt( 'holidays' ) ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $p[0] ) ) {
			$out[ $p[0] ] = isset( $p[1] ) && '' !== $p[1] ? $p[1] : 'Nghỉ lễ';
		}
	}
	return $out;
}

/**
 * Ngày làm việc (không phải ngày nghỉ tuần, không phải lễ).
 *
 * @param string $date Y-m-d.
 * @return bool
 */
function qldn_is_workday( $date ) {
	return ! in_array( (int) gmdate( 'w', strtotime( $date ) ), qldn_weekly_off(), true ) && ! isset( qldn_holidays()[ $date ] );
}

/**
 * Lưu ca.
 */
function qldn_handle_shift() {
	qldn_check( 'shift', 'qldn_hr' );
	global $wpdb;
	$id    = qldn_in( 'id', 'int', 0 );
	$time  = function ( $k ) {
		$v = qldn_in( $k );
		return preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $v ) ? substr( '0' . $v, -5 ) . ':00' : '';
	};
	$row   = array(
		'name'       => qldn_in( 'name' ),
		'start_time' => $time( 'start_time' ),
		'end_time'   => $time( 'end_time' ),
		'break_min'  => min( 600, max( 0, qldn_in( 'break_min', 'int', 0 ) ) ),
	);
	if ( 'delete' === qldn_in( 'do', 'key' ) ) {
		$wpdb->delete( qldn_t( 'shifts' ), array( 'id' => $id ) );
		$wpdb->update( qldn_t( 'employees' ), array( 'shift_id' => 0 ), array( 'shift_id' => $id ) );
		qldn_notice( 'Đã xoá ca; nhân viên của ca này chuyển về ca mặc định.' );
		qldn_go( 'shifts' );
	}
	if ( '' === $row['name'] || ! $row['start_time'] || ! $row['end_time'] || $row['start_time'] === $row['end_time'] ) {
		qldn_notice( 'Nhập tên ca, giờ bắt đầu và giờ kết thúc (khác nhau).', 'error' );
		qldn_go( 'shifts' );
	}
	if ( $id ) {
		$wpdb->update( qldn_t( 'shifts' ), $row, array( 'id' => $id ) );
	} else {
		$wpdb->insert( qldn_t( 'shifts' ), $row );
	}
	qldn_notice( 'Đã lưu ca "' . esc_html( $row['name'] ) . '"' . ( $row['end_time'] < $row['start_time'] ? ' (ca qua đêm: giờ ra sáng hôm sau tính cho ngày bắt đầu ca)' : '' ) . '.' );
	qldn_go( 'shifts' );
}
add_action( 'admin_post_qldn_shift', 'qldn_handle_shift' );

/**
 * Xếp ca nhanh cho nhiều nhân viên.
 */
function qldn_handle_shift_assign() {
	qldn_check( 'shift_assign', 'qldn_hr' );
	global $wpdb;
	$n = 0;
	foreach ( qldn_in( 'emp_shift', 'array', array() ) as $eid => $sid ) {
		$sid = (int) $sid;
		if ( isset( qldn_shifts()[ $sid ] ) ) {
			$n += (int) $wpdb->update( qldn_t( 'employees' ), array( 'shift_id' => $sid ), array( 'id' => (int) $eid ) );
		}
	}
	qldn_notice( sprintf( 'Đã xếp ca cho %d nhân viên. Dữ liệu máy chấm công đã nhận có thể cập nhật lại ở Máy chấm công → Dữ liệu chấm công.', $n ) );
	qldn_go( 'shifts' );
}
add_action( 'admin_post_qldn_shift_assign', 'qldn_handle_shift_assign' );

/**
 * Trang Ca làm việc.
 */
function qldn_page_shifts() {
	global $wpdb;
	$shifts = qldn_shifts();
	echo '<div class="wrap qldn"><h1>Ca làm việc</h1>';
	qldn_print_notices();
	echo '<p class="description">Đi muộn / về sớm, nửa công, tăng ca tính theo ca của từng nhân viên. Ca qua đêm (VD 22:00–06:00): lần chấm buổi sáng tính cho ngày bắt đầu ca. Ngày nghỉ hằng tuần, ngày lễ, thời gian cho phép muộn đặt ở <a href="' . esc_url( qldn_url( 'settings' ) ) . '">Cài đặt</a>.</p>';
	echo '<table class="widefat striped qldn-table qldn-narrow"><thead><tr><th>Ca</th><th>Giờ vào</th><th>Giờ ra</th><th class="num">Nghỉ giữa ca</th><th class="num">Giờ làm</th><th></th></tr></thead><tbody>';
	foreach ( $shifts as $s ) {
		list( $a, $b ) = qldn_shift_bounds( $s, '2000-01-01' );
		echo '<tr><td><strong>' . esc_html( $s->name ) . '</strong>' . ( $s->end_time <= $s->start_time ? ' ' . qldn_badge( 'qua đêm', 'blue' ) : '' ) . '</td><td>' . esc_html( substr( $s->start_time, 0, 5 ) ) . '</td><td>' . esc_html( substr( $s->end_time, 0, 5 ) ) . '</td><td class="num">' . esc_html( $s->break_min ) . ' phút</td><td class="num">' . esc_html( qldn_qty( ( $b - $a ) / 3600 - $s->break_min / 60 ) ) . 'h</td><td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( $s->id ) {
			qldn_form_open( 'shift', array( 'class' => 'qldn-inline', 'onsubmit' => "return confirm('Xoá ca này?')" ) );
			echo '<input type="hidden" name="id" value="' . esc_attr( $s->id ) . '"><input type="hidden" name="do" value="delete"><button class="button-link qldn-neg">Xoá</button></form>';
		} else {
			echo '<a href="' . esc_url( qldn_url( 'settings' ) ) . '#qldn-shift_start">Sửa ở Cài đặt</a>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table><h2>Thêm ca</h2>';
	qldn_form_open( 'shift', array( 'class' => 'qldn-filter' ) );
	echo '<input type="text" name="name" placeholder="Tên ca (VD: Ca sáng, Ca đêm)" required> Vào <input type="time" name="start_time" required> Ra <input type="time" name="end_time" required> Nghỉ giữa ca <input type="number" name="break_min" class="small-text" value="60" min="0"> phút <button class="button button-primary">Thêm ca</button></form>';

	$emps = $wpdb->get_results( 'SELECT id, code, name, department, shift_id FROM ' . qldn_t( 'employees' ) . " WHERE status = 'active' ORDER BY department, name" );
	echo '<h2>Xếp ca</h2>';
	if ( ! $emps ) {
		echo '<p>Chưa có nhân viên.</p></div>';
		return;
	}
	$opts = array();
	foreach ( $shifts as $s ) {
		$opts[ $s->id ] = $s->name . ' (' . substr( $s->start_time, 0, 5 ) . '–' . substr( $s->end_time, 0, 5 ) . ')';
	}
	qldn_form_open( 'shift_assign' );
	echo '<table class="widefat striped qldn-table qldn-narrow"><thead><tr><th>Nhân viên</th><th>Bộ phận</th><th>Ca</th></tr></thead><tbody>';
	foreach ( $emps as $e ) {
		echo '<tr><td>' . esc_html( $e->name ) . ' <small>' . esc_html( $e->code ) . '</small></td><td>' . esc_html( $e->department ) . '</td><td><select name="emp_shift[' . esc_attr( $e->id ) . ']">' . qldn_options_html( $opts, $e->shift_id ) . '</select></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';
	submit_button( 'Lưu xếp ca' );
	echo '</form></div>';
}
