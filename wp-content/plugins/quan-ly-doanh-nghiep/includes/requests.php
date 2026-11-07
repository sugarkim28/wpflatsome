<?php
/**
 * Đơn từ: nghỉ phép năm, nghỉ việc riêng có lương, nghỉ không lương, nghỉ ốm, công tác, làm thêm giờ,
 * giải trình đi muộn / về sớm / quên chấm công. Quản lý trực tiếp hoặc bộ phận nhân sự duyệt;
 * duyệt xong tự ghi vào bảng công. Quỹ phép năm theo Điều 113, 114 Bộ luật Lao động 2019.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại đơn => [nhãn, ký hiệu ghi bảng công, trừ quỹ phép].
 *
 * @return array
 */
function qldn_request_types() {
	return array(
		'annual'   => array( 'Nghỉ phép năm', 'P', true ),
		'personal' => array( 'Nghỉ việc riêng có lương (cưới, tang…)', 'P', false ),
		'unpaid'   => array( 'Nghỉ không lương', 'K', false ),
		'sick'     => array( 'Nghỉ ốm, thai sản (BHXH chi trả)', 'O', false ),
		'trip'     => array( 'Đi công tác / làm việc bên ngoài', 'X', false ),
		'ot'       => array( 'Đăng ký làm thêm giờ', '', false ),
		'explain'  => array( 'Giải trình đi muộn / về sớm / quên chấm công', '', false ),
	);
}

/**
 * Trạng thái đơn.
 *
 * @return array
 */
function qldn_request_statuses() {
	return array(
		'pending'   => 'Chờ duyệt',
		'approved'  => 'Đã duyệt',
		'rejected'  => 'Từ chối',
		'cancelled' => 'Đã huỷ',
	);
}

/**
 * Huy hiệu trạng thái.
 *
 * @param string $s Trạng thái.
 * @return string HTML.
 */
function qldn_request_badge( $s ) {
	$c = array( 'pending' => 'orange', 'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'gray' );
	return qldn_badge( qldn_request_statuses()[ $s ] ?? $s, $c[ $s ] ?? 'gray' );
}

/**
 * Các ngày làm việc trong khoảng (bỏ ngày nghỉ tuần, ngày lễ).
 *
 * @param string $from Y-m-d.
 * @param string $to   Y-m-d.
 * @return string[]
 */
function qldn_workdays( $from, $to ) {
	$out = array();
	for ( $t = strtotime( $from ); $t <= strtotime( $to ); $t += DAY_IN_SECONDS ) {
		$d = gmdate( 'Y-m-d', $t );
		if ( qldn_is_workday( $d ) ) {
			$out[] = $d;
		}
	}
	return $out;
}

/**
 * Số ngày phép năm được hưởng trong năm.
 *
 * - Điều 113: 12 ngày (tham số "Phép năm cơ bản"); chưa đủ 12 tháng thì theo tỷ lệ số tháng làm việc.
 * - Điều 114: cứ đủ 05 năm làm việc được tăng thêm 01 ngày.
 * - Điều 66 Nghị định 145/2020/NĐ-CP: (ngày phép + ngày thâm niên) / 12 × số tháng làm việc thực tế, làm tròn (≥ 0,5 lên 1).
 *
 * @param object $e    Nhân viên.
 * @param int    $year Năm.
 * @return float
 */
function qldn_leave_entitlement( $e, $year ) {
	$base = (float) qldn_opt( 'annual_leave' );
	if ( ! $e->start_date ) {
		return $base;
	}
	$start = new DateTime( $e->start_date );
	if ( (int) $start->format( 'Y' ) > $year ) {
		return 0;
	}
	$years = (int) $start->diff( new DateTime( $year . '-12-31' ) )->y;
	$full  = $base + floor( $years / 5 );
	if ( (int) $start->format( 'Y' ) < $year ) {
		return $full;
	}
	// Vào làm trong năm: số tháng làm việc tính từ tháng vào làm (vào sau ngày 15 thì không tính tháng đó).
	$months = 12 - (int) $start->format( 'n' ) + ( (int) $start->format( 'j' ) <= 15 ? 1 : 0 );
	return (float) round( $full / 12 * max( 0, $months ), 0, PHP_ROUND_HALF_UP );
}

/**
 * Quỹ phép năm: được hưởng, đã dùng, còn lại.
 *
 * @param int $employee_id Nhân viên.
 * @param int $year        Năm (0 = năm nay).
 * @return array entitled, adjust, used, pending, remaining.
 */
function qldn_leave_balance( $employee_id, $year = 0 ) {
	global $wpdb;
	$year = $year ? $year : (int) current_time( 'Y' );
	$e    = qldn_get( 'employees', $employee_id );
	if ( ! $e ) {
		return array( 'entitled' => 0, 'adjust' => 0, 'used' => 0, 'pending' => 0, 'remaining' => 0 );
	}
	$t    = qldn_t( 'requests' );
	$sum  = function ( $status ) use ( $wpdb, $t, $employee_id, $year ) {
		return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(days),0) FROM $t WHERE employee_id = %d AND type = 'annual' AND status = %s AND from_date BETWEEN %s AND %s", $employee_id, $status, $year . '-01-01', $year . '-12-31' ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	};
	$ent  = qldn_leave_entitlement( $e, $year );
	$adj  = $year === (int) current_time( 'Y' ) ? (float) $e->leave_adjust : 0;
	$used = $sum( 'approved' );
	$pend = $sum( 'pending' );
	return array( 'entitled' => $ent, 'adjust' => $adj, 'used' => $used, 'pending' => $pend, 'remaining' => $ent + $adj - $used );
}

/**
 * Tạo đơn.
 *
 * @param array $d employee_id, type, from_date, to_date, half_day, hours, reason.
 * @return int|WP_Error
 */
function qldn_request_create( $d ) {
	global $wpdb;
	$eid   = (int) ( $d['employee_id'] ?? 0 );
	$type  = $d['type'] ?? '';
	$types = qldn_request_types();
	if ( ! $eid || ! qldn_get( 'employees', $eid ) ) {
		return new WP_Error( 'qldn_emp', 'Chưa chọn nhân viên.' );
	}
	if ( ! isset( $types[ $type ] ) ) {
		return new WP_Error( 'qldn_type', 'Chọn loại đơn.' );
	}
	$from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['from_date'] ?? '' ) ? $d['from_date'] : '';
	$to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['to_date'] ?? '' ) ? $d['to_date'] : $from;
	if ( ! $from ) {
		return new WP_Error( 'qldn_date', 'Chọn ngày.' );
	}
	if ( in_array( $type, array( 'ot', 'explain' ), true ) || ! empty( $d['half_day'] ) ) {
		$to = $from; // Làm thêm, giải trình, nghỉ nửa ngày: 1 ngày.
	}
	if ( $to < $from ) {
		return new WP_Error( 'qldn_date', 'Ngày kết thúc phải sau ngày bắt đầu.' );
	}
	if ( strtotime( $to ) - strtotime( $from ) > 366 * DAY_IN_SECONDS ) {
		return new WP_Error( 'qldn_date', 'Một đơn tối đa 1 năm.' );
	}
	$half  = ! empty( $d['half_day'] ) && ! in_array( $type, array( 'ot', 'explain', 'trip' ), true );
	$days  = 0;
	$hours = 0;
	if ( 'ot' === $type ) {
		$hours = min( 12, max( 0, round( (float) ( $d['hours'] ?? 0 ), 2 ) ) );
		if ( $hours <= 0 ) {
			return new WP_Error( 'qldn_hours', 'Nhập số giờ làm thêm.' );
		}
	} elseif ( 'explain' !== $type ) {
		$days = $half ? 0.5 : count( qldn_workdays( $from, $to ) );
		if ( $days <= 0 ) {
			return new WP_Error( 'qldn_days', 'Khoảng ngày đã chọn không có ngày làm việc (ngày nghỉ tuần / ngày lễ).' );
		}
	}
	if ( '' === trim( (string) ( $d['reason'] ?? '' ) ) && in_array( $type, array( 'explain', 'personal', 'unpaid' ), true ) ) {
		return new WP_Error( 'qldn_reason', 'Ghi lý do.' );
	}
	// Không trùng ngày với đơn nghỉ khác đang chờ / đã duyệt.
	if ( $days > 0 ) {
		$dup = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . qldn_t( 'requests' ) . " WHERE employee_id = %d AND status IN ('pending','approved') AND type NOT IN ('ot','explain') AND from_date <= %s AND to_date >= %s", $eid, $to, $from ) );
		if ( $dup ) {
			return new WP_Error( 'qldn_overlap', 'Đã có đơn nghỉ khác trùng khoảng ngày này.' );
		}
	}
	if ( 'annual' === $type ) {
		$bal = qldn_leave_balance( $eid, (int) substr( $from, 0, 4 ) );
		if ( $days > $bal['remaining'] - $bal['pending'] ) {
			return new WP_Error( 'qldn_balance', sprintf( 'Không đủ phép năm: còn %s ngày (đang chờ duyệt %s ngày), đơn này %s ngày. Chọn "Nghỉ không lương" cho phần vượt.', qldn_qty( $bal['remaining'] ), qldn_qty( $bal['pending'] ), qldn_qty( $days ) ) );
		}
	}
	$wpdb->insert(
		qldn_t( 'requests' ),
		array(
			'employee_id' => $eid,
			'type'        => $type,
			'from_date'   => $from,
			'to_date'     => $to,
			'half_day'    => $half ? 1 : 0,
			'days'        => $days,
			'hours'       => $hours,
			'reason'      => sanitize_textarea_field( $d['reason'] ?? '' ),
			'status'      => 'pending',
			'user_id'     => get_current_user_id(),
			'created_at'  => qldn_now(),
		)
	);
	$id = (int) $wpdb->insert_id;
	qldn_request_notify( $id, 'new' );
	return $id;
}

/**
 * Người dùng hiện tại duyệt được đơn này không.
 *
 * @param object $r Đơn.
 * @return bool
 */
function qldn_can_decide( $r ) {
	$me = qldn_current_employee();
	if ( $me && (int) $me->id === (int) $r->employee_id && ! current_user_can( 'manage_options' ) ) {
		return false; // Không tự duyệt đơn của mình.
	}
	if ( current_user_can( 'qldn_hr' ) ) {
		return true;
	}
	$e = qldn_get( 'employees', $r->employee_id );
	return $me && $e && (int) $e->manager_id === (int) $me->id;
}

/**
 * Duyệt / từ chối / huỷ đơn.
 *
 * @param int    $id       Đơn.
 * @param string $decision approved|rejected|cancelled.
 * @param string $note     Ghi chú.
 * @return true|WP_Error
 */
function qldn_request_decide( $id, $decision, $note = '' ) {
	global $wpdb;
	$r = qldn_get( 'requests', $id );
	if ( ! $r ) {
		return new WP_Error( 'qldn_req', 'Không tìm thấy đơn.' );
	}
	$me = qldn_current_employee();
	if ( 'cancelled' === $decision ) {
		// Người gửi huỷ đơn chờ duyệt; nhân sự huỷ được cả đơn đã duyệt (trả lại bảng công).
		$own = $me && (int) $me->id === (int) $r->employee_id && 'pending' === $r->status;
		if ( ! $own && ! current_user_can( 'qldn_hr' ) ) {
			return new WP_Error( 'qldn_cap', 'Bạn không huỷ được đơn này.' );
		}
		if ( 'approved' === $r->status ) {
			$res = qldn_request_unapply( $r );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
		}
	} else {
		if ( 'pending' !== $r->status ) {
			return new WP_Error( 'qldn_req', 'Đơn đã được xử lý.' );
		}
		if ( ! qldn_can_decide( $r ) ) {
			return new WP_Error( 'qldn_cap', 'Bạn không phải người duyệt đơn này.' );
		}
		if ( 'approved' === $decision ) {
			$res = qldn_request_apply( $r );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
		}
	}
	$wpdb->update(
		qldn_t( 'requests' ),
		array( 'status' => $decision, 'approver_id' => get_current_user_id(), 'decided_at' => qldn_now(), 'decision_note' => sanitize_text_field( $note ) ),
		array( 'id' => $id )
	);
	$e = qldn_get( 'employees', $r->employee_id );
	qldn_log( 'request_decide', 'request', $id, ( $e ? $e->name : '' ) . ' – ' . qldn_request_types()[ $r->type ][0] . ' ' . qldn_date( $r->from_date ) . ' → ' . qldn_request_statuses()[ $decision ] );
	if ( 'cancelled' !== $decision ) {
		qldn_request_notify( $id, 'decided' );
	}
	return true;
}

/**
 * Ghi đơn đã duyệt vào bảng công.
 *
 * @param object $r Đơn.
 * @return true|WP_Error
 */
function qldn_request_apply( $r ) {
	global $wpdb;
	$t     = qldn_t( 'attendance' );
	$dates = in_array( $r->type, array( 'ot', 'explain' ), true ) ? array( $r->from_date ) : qldn_workdays( $r->from_date, $r->to_date );
	foreach ( array_unique( array_map( function ( $d ) { return substr( $d, 0, 7 ); }, $dates ) ) as $p ) {
		if ( qldn_period_locked( $p ) ) {
			return new WP_Error( 'qldn_locked', 'Tháng ' . $p . ' đã chốt lương – mở khoá bảng lương trước khi duyệt.' );
		}
	}
	$mark = qldn_request_types()[ $r->type ][1];
	if ( $r->half_day && 'P' === $mark ) {
		$mark = 'H'; // Nửa ngày làm + nửa ngày phép.
	} elseif ( $r->half_day && 'K' === $mark ) {
		$mark = 'N'; // Nửa ngày làm, nửa ngày nghỉ không lương.
	}
	foreach ( $dates as $d ) {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE employee_id = %d AND work_date = %s", $r->employee_id, $d ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( 'ot' === $r->type ) {
			$data = array( 'ot_hours' => ( $row ? (float) $row->ot_hours : 0 ) + (float) $r->hours );
		} elseif ( 'explain' === $r->type ) {
			$data = array( 'late_min' => 0, 'early_min' => 0, 'mark' => $row && $row->mark ? $row->mark : 'X' );
		} else {
			$data = array( 'mark' => $mark, 'late_min' => 0, 'early_min' => 0 );
		}
		if ( $row ) {
			$wpdb->update( $t, $data, array( 'id' => $row->id ) );
		} else {
			$wpdb->insert( $t, array_merge( array( 'employee_id' => $r->employee_id, 'work_date' => $d ), $data ) );
		}
	}
	return true;
}

/**
 * Bỏ ghi bảng công của đơn đã duyệt (khi huỷ), tính lại từ máy chấm công nếu có.
 *
 * @param object $r Đơn.
 * @return true|WP_Error
 */
function qldn_request_unapply( $r ) {
	global $wpdb;
	$t     = qldn_t( 'attendance' );
	$dates = in_array( $r->type, array( 'ot', 'explain' ), true ) ? array( $r->from_date ) : qldn_workdays( $r->from_date, $r->to_date );
	foreach ( $dates as $d ) {
		if ( qldn_period_locked( substr( $d, 0, 7 ) ) ) {
			return new WP_Error( 'qldn_locked', 'Tháng đã chốt lương – mở khoá bảng lương trước.' );
		}
	}
	foreach ( $dates as $d ) {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE employee_id = %d AND work_date = %s", $r->employee_id, $d ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $row ) {
			continue;
		}
		if ( 'ot' === $r->type ) {
			$wpdb->update( $t, array( 'ot_hours' => max( 0, (float) $row->ot_hours - (float) $r->hours ) ), array( 'id' => $row->id ) );
		} elseif ( 'explain' !== $r->type && in_array( $row->mark, array( 'P', 'H', 'K', 'O', 'X', 'N' ), true ) ) {
			$wpdb->update( $t, array( 'mark' => '' ), array( 'id' => $row->id ) );
		}
	}
	qldn_punch_apply_many( array( (int) $r->employee_id => array_fill_keys( $dates, true ) ) );
	return true;
}

/**
 * Email báo đơn mới cho người duyệt / kết quả cho người gửi.
 *
 * @param int    $id   Đơn.
 * @param string $what new|decided.
 */
function qldn_request_notify( $id, $what ) {
	$r = qldn_get( 'requests', $id );
	$e = $r ? qldn_get( 'employees', $r->employee_id ) : null;
	if ( ! $e ) {
		return;
	}
	$label = qldn_request_types()[ $r->type ][0] . ' ' . qldn_date( $r->from_date ) . ( $r->to_date !== $r->from_date ? ' – ' . qldn_date( $r->to_date ) : '' );
	if ( 'new' === $what ) {
		$m  = $e->manager_id ? qldn_get( 'employees', $e->manager_id ) : null;
		$to = $m && $m->email ? $m->email : get_option( 'admin_email' );
		wp_mail( $to, '[Đơn từ] ' . $e->name . ': ' . $label, $e->name . ' gửi đơn: ' . $label . "\nLý do: " . $r->reason . "\n\nDuyệt tại: " . qldn_url( 'requests' ) );
	} elseif ( $e->email ) {
		wp_mail( $e->email, '[Đơn từ] ' . qldn_request_statuses()[ $r->status ] . ': ' . $label, 'Đơn ' . $label . ' của bạn: ' . qldn_request_statuses()[ $r->status ] . ( $r->decision_note ? "\nGhi chú: " . $r->decision_note : '' ) );
	}
}

/**
 * Xử lý gửi đơn (nhân viên tự gửi hoặc nhân sự tạo hộ).
 */
function qldn_handle_request() {
	qldn_check( 'request', 'qldn_access' );
	$me  = qldn_current_employee();
	$eid = current_user_can( 'qldn_hr' ) && qldn_in( 'employee_id', 'int', 0 ) ? qldn_in( 'employee_id', 'int', 0 ) : ( $me ? (int) $me->id : 0 );
	$r   = qldn_request_create(
		array(
			'employee_id' => $eid,
			'type'        => qldn_in( 'type', 'key' ),
			'from_date'   => qldn_in( 'from_date', 'date' ),
			'to_date'     => qldn_in( 'to_date', 'date' ),
			'half_day'    => qldn_in( 'half_day', 'int', 0 ),
			'hours'       => qldn_in( 'hours', 'qty', 0 ),
			'reason'      => qldn_in( 'reason', 'textarea' ),
		)
	);
	$back = 'me' === qldn_in( 'back', 'key' ) ? 'me' : 'requests';
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( $back );
	}
	// Nhân sự tạo hộ và chọn "duyệt luôn".
	if ( qldn_in( 'approve_now', 'int', 0 ) && current_user_can( 'qldn_hr' ) ) {
		$d = qldn_request_decide( $r, 'approved' );
		qldn_notice( is_wp_error( $d ) ? 'Đã tạo đơn nhưng chưa duyệt được: ' . $d->get_error_message() : 'Đã tạo và duyệt đơn, bảng công đã cập nhật.', is_wp_error( $d ) ? 'warning' : 'success' );
	} else {
		qldn_notice( 'Đã gửi đơn, chờ duyệt.' );
	}
	qldn_go( $back );
}
add_action( 'admin_post_qldn_request', 'qldn_handle_request' );

/**
 * Xử lý duyệt / từ chối / huỷ.
 */
function qldn_handle_request_decide() {
	qldn_check( 'request_decide', 'qldn_access' );
	$r = qldn_request_decide( qldn_in( 'id', 'int', 0 ), qldn_in( 'decision', 'key' ), qldn_in( 'note' ) );
	qldn_notice( is_wp_error( $r ) ? $r->get_error_message() : 'Đã cập nhật đơn.', is_wp_error( $r ) ? 'error' : 'success' );
	qldn_go( 'me' === qldn_in( 'back', 'key' ) ? 'me' : 'requests', array_filter( array( 'tab' => qldn_in( 'tab', 'key' ) ) ) );
}
add_action( 'admin_post_qldn_request_decide', 'qldn_handle_request_decide' );

/**
 * Form gửi đơn.
 *
 * @param bool   $hr   Hiện ô chọn nhân viên + duyệt luôn.
 * @param string $back Trang quay về.
 */
function qldn_request_form( $hr, $back ) {
	qldn_form_open( 'request', array( 'class' => 'qldn-request-form' ) );
	echo '<input type="hidden" name="back" value="' . esc_attr( $back ) . '"><table class="form-table">';
	if ( $hr ) {
		qldn_field( 'Nhân viên', 'employee_id', 0, array( 'type' => 'select', 'options' => array( 0 => 'Chọn…' ) + qldn_employee_options(), 'required' => true ) );
	}
	$types = array();
	foreach ( qldn_request_types() as $k => $t ) {
		$types[ $k ] = $t[0];
	}
	qldn_field( 'Loại đơn', 'type', 'annual', array( 'type' => 'select', 'options' => $types ) );
	qldn_field( 'Từ ngày', 'from_date', qldn_today(), array( 'type' => 'date', 'class' => '', 'required' => true ) );
	qldn_field( 'Đến ngày', 'to_date', '', array( 'type' => 'date', 'class' => '', 'help' => 'Để trống nếu 1 ngày. Ngày nghỉ hằng tuần, ngày lễ không tính.' ) );
	qldn_field( 'Nghỉ nửa ngày', 'half_day', 0, array( 'type' => 'select', 'options' => array( 0 => 'Không', 1 => 'Có – nghỉ nửa ngày' ) ) );
	qldn_field( 'Số giờ làm thêm', 'hours', '', array( 'class' => 'small-text', 'help' => 'Chỉ với đơn làm thêm giờ (tối đa theo Điều 107 BLLĐ: 50% giờ làm bình thường / ngày).' ) );
	qldn_field( 'Lý do', 'reason', '', array( 'type' => 'textarea' ) );
	if ( $hr ) {
		qldn_field( 'Duyệt luôn', 'approve_now', 0, array( 'type' => 'select', 'options' => array( 0 => 'Không – chờ quản lý duyệt', 1 => 'Có – duyệt và ghi bảng công ngay' ) ) );
	}
	echo '</table>';
	submit_button( 'Gửi đơn' );
	echo '</form>';
}

/**
 * Bảng đơn.
 *
 * @param array  $rows    Đơn (kèm name, code).
 * @param string $back    Trang quay về.
 * @param bool   $own     Đơn của chính mình (hiện nút huỷ).
 */
function qldn_request_table( $rows, $back, $own = false ) {
	$types = qldn_request_types();
	echo '<table class="widefat striped qldn-table"><thead><tr>' . ( $own ? '' : '<th>Nhân viên</th>' ) . '<th>Loại</th><th>Thời gian</th><th class="num">Số ngày / giờ</th><th>Lý do</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="7">Không có đơn.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$amount = 'ot' === $r->type ? qldn_qty( $r->hours ) . ' giờ' : ( 'explain' === $r->type ? '' : qldn_qty( $r->days ) . ' ngày' );
		echo '<tr>' . ( $own ? '' : '<td>' . esc_html( $r->name ) . ' <small>' . esc_html( $r->code ) . '</small></td>' ) . '<td>' . esc_html( $types[ $r->type ][0] ?? $r->type ) . '</td>';
		echo '<td>' . esc_html( qldn_date( $r->from_date ) . ( $r->to_date !== $r->from_date ? ' – ' . qldn_date( $r->to_date ) : '' ) . ( $r->half_day ? ' (nửa ngày)' : '' ) ) . '</td><td class="num">' . esc_html( $amount ) . '</td><td>' . esc_html( wp_trim_words( $r->reason, 20 ) ) . ( $r->decision_note ? '<br><em>' . esc_html( $r->decision_note ) . '</em>' : '' ) . '</td><td>' . qldn_request_badge( $r->status ) . '</td><td class="qldn-actions">'; // phpcs:ignore WordPress.Security.EscapeOutput
		$btn = function ( $decision, $label, $class, $confirm = '' ) use ( $r, $back ) {
			qldn_form_open( 'request_decide', array( 'class' => 'qldn-inline' ) + ( $confirm ? array( 'onsubmit' => 'return confirm(' . wp_json_encode( $confirm ) . ')' ) : array() ) );
			echo '<input type="hidden" name="id" value="' . esc_attr( $r->id ) . '"><input type="hidden" name="decision" value="' . esc_attr( $decision ) . '"><input type="hidden" name="back" value="' . esc_attr( $back ) . '"><input type="hidden" name="tab" value="' . esc_attr( qldn_in( 'tab', 'key' ) ) . '">';
			if ( 'rejected' === $decision ) {
				echo '<input type="text" name="note" placeholder="Lý do từ chối" class="small-text" style="width:110px"> ';
			}
			echo '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
		};
		if ( 'pending' === $r->status && ! $own && qldn_can_decide( $r ) ) {
			$btn( 'approved', 'Duyệt', 'button button-primary button-small' );
			$btn( 'rejected', 'Từ chối', 'button button-small' );
		}
		if ( ( $own && 'pending' === $r->status ) || ( ! $own && 'approved' === $r->status && current_user_can( 'qldn_hr' ) ) ) {
			$btn( 'cancelled', 'Huỷ', 'button-link qldn-neg', 'Huỷ đơn này?' . ( 'approved' === $r->status ? ' Bảng công các ngày của đơn sẽ được trả lại.' : '' ) );
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * Trang Đơn từ & nghỉ phép.
 */
function qldn_page_requests() {
	global $wpdb;
	$hr  = current_user_can( 'qldn_hr' );
	$me  = qldn_current_employee();
	$tr  = qldn_t( 'requests' );
	$te  = qldn_t( 'employees' );
	$tab = qldn_in( 'tab', 'key', 'pending' );
	echo '<div class="wrap qldn"><h1>Đơn từ & nghỉ phép</h1>';
	qldn_print_notices();
	if ( ! $hr ) {
		// Quản lý trực tiếp: chỉ đơn của nhân viên mình quản lý.
		if ( ! $me ) {
			echo '<p>Tài khoản chưa liên kết với nhân viên. Gửi đơn ở trang <a href="' . esc_url( qldn_url( 'me' ) ) . '">Của tôi</a>.</p></div>';
			return;
		}
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT r.*, e.name, e.code FROM $tr r JOIN $te e ON e.id = r.employee_id WHERE e.manager_id = %d ORDER BY r.status = 'pending' DESC, r.id DESC LIMIT 200", $me->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		echo '<p>Đơn của nhân viên bạn quản lý trực tiếp. Gửi đơn của mình ở trang <a href="' . esc_url( qldn_url( 'me' ) ) . '">Của tôi</a>.</p>';
		qldn_request_table( $rows, 'requests' );
		echo '</div>';
		return;
	}
	$tabs = array( 'pending' => 'Chờ duyệt', 'all' => 'Tất cả đơn', 'balance' => 'Quỹ phép năm', 'new' => 'Tạo đơn hộ' );
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $v ) {
		$n = 'pending' === $k ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM $tr WHERE status = 'pending'" ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL
		echo '<a class="nav-tab' . ( $k === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( qldn_url( 'requests', array( 'tab' => $k ) ) ) . '">' . esc_html( $v ) . ( $n ? ' (' . esc_html( $n ) . ')' : '' ) . '</a>';
	}
	echo '</nav>';
	if ( 'new' === $tab ) {
		qldn_request_form( true, 'requests' );
	} elseif ( 'balance' === $tab ) {
		$year = qldn_in( 'year', 'int', (int) current_time( 'Y' ) );
		echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-requests"><input type="hidden" name="tab" value="balance">Năm <input type="number" name="year" class="small-text" value="' . esc_attr( $year ) . '"> <button class="button">Xem</button></form>';
		echo '<table class="widefat striped qldn-table qldn-narrow"><thead><tr><th>Nhân viên</th><th>Ngày vào làm</th><th class="num">Được hưởng</th><th class="num">Điều chỉnh</th><th class="num">Đã nghỉ</th><th class="num">Chờ duyệt</th><th class="num">Còn lại</th></tr></thead><tbody>';
		foreach ( $wpdb->get_results( "SELECT id, code, name, start_date FROM $te WHERE status = 'active' ORDER BY department, name" ) as $e ) { // phpcs:ignore WordPress.DB.PreparedSQL
			$b = qldn_leave_balance( $e->id, $year );
			echo '<tr><td>' . esc_html( $e->name ) . ' <small>' . esc_html( $e->code ) . '</small></td><td>' . esc_html( qldn_date( $e->start_date ) ) . '</td><td class="num">' . esc_html( qldn_qty( $b['entitled'] ) ) . '</td><td class="num">' . esc_html( qldn_qty( $b['adjust'] ) ) . '</td><td class="num">' . esc_html( qldn_qty( $b['used'] ) ) . '</td><td class="num">' . esc_html( qldn_qty( $b['pending'] ) ) . '</td><td class="num"><strong>' . esc_html( qldn_qty( $b['remaining'] ) ) . '</strong></td></tr>';
		}
		echo '</tbody></table><p class="description">Được hưởng = phép năm cơ bản (Cài đặt) + 1 ngày mỗi 5 năm làm việc (Điều 114); vào làm trong năm tính theo số tháng làm việc (Điều 66 Nghị định 145/2020/NĐ-CP). Nhập ngày vào làm ở hồ sơ nhân viên để tính đúng; điều chỉnh (phép tồn chuyển sang, thưởng phép) ở ô "Điều chỉnh phép năm".</p>';
	} else {
		$where = 'pending' === $tab ? " WHERE r.status = 'pending'" : ' WHERE 1=1';
		$type  = qldn_in( 'type', 'key' );
		$month = qldn_in( 'month' );
		if ( 'all' === $tab ) {
			if ( isset( qldn_request_types()[ $type ] ) ) {
				$where .= $wpdb->prepare( ' AND r.type = %s', $type );
			}
			if ( preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
				$where .= $wpdb->prepare( ' AND r.from_date <= %s AND r.to_date >= %s', gmdate( 'Y-m-t', strtotime( $month . '-01' ) ), $month . '-01' );
			}
			$types = array();
			foreach ( qldn_request_types() as $k => $t ) {
				$types[ $k ] = $t[0];
			}
			echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-requests"><input type="hidden" name="tab" value="all"><select name="type"><option value="">Mọi loại</option>' . qldn_options_html( $types, $type ) . '</select> Tháng <input type="month" name="month" value="' . esc_attr( $month ) . '"> <button class="button">Lọc</button></form>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		$rows = $wpdb->get_results( "SELECT r.*, e.name, e.code FROM $tr r JOIN $te e ON e.id = r.employee_id $where ORDER BY r.id DESC LIMIT 300" ); // phpcs:ignore WordPress.DB.PreparedSQL
		qldn_request_table( $rows, 'requests' );
	}
	echo '</div>';
}
