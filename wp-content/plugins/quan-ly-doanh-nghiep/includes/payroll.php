<?php
/**
 * Tính lương: lương theo ngày công, tăng ca, phụ cấp, hoa hồng doanh số, thưởng,
 * bảo hiểm phần người lao động, thuế TNCN lũy tiến, tạm ứng, khấu trừ; chốt, in phiếu lương.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thuế TNCN theo biểu lũy tiến từng phần (thu nhập tính thuế / tháng).
 *
 * @param int $taxable Thu nhập tính thuế.
 * @return int
 */
function qldn_pit( $taxable ) {
	$tax  = 0.0;
	$prev = 0;
	foreach ( qldn_tax_brackets() as $b ) {
		if ( $taxable <= $prev ) {
			break;
		}
		$upper = $b[0] > 0 ? min( $taxable, $b[0] ) : $taxable;
		$tax  += ( $upper - $prev ) * $b[1] / 100;
		$prev  = $b[0] > 0 ? $b[0] : $taxable;
	}
	return (int) round( $tax );
}

/**
 * Doanh thu đơn hoàn thành của nhân viên trong tháng.
 *
 * @param int    $employee_id Nhân viên.
 * @param string $period      Y-m.
 * @return int
 */
function qldn_employee_sales( $employee_id, $period ) {
	global $wpdb;
	$from = $period . '-01 00:00:00';
	$to   = gmdate( 'Y-m-t', strtotime( $period . '-01' ) ) . ' 23:59:59';
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(total),0) FROM ' . qldn_t( 'orders' ) . " WHERE staff_id = %d AND status = 'done' AND created_at BETWEEN %s AND %s", $employee_id, $from, $to ) );
}

/**
 * Tính 1 dòng lương.
 *
 * @param object $e      Nhân viên.
 * @param string $period Y-m.
 * @param array  $manual ot_hours (null = lấy từ chấm công), bonus, advance, deduction, note.
 * @return array Dữ liệu dòng bảng lương.
 */
function qldn_payroll_calc( $e, $period, $manual = array() ) {
	$o      = qldn_opt();
	$manual = wp_parse_args( $manual, array( 'ot_hours' => null, 'bonus' => 0, 'advance' => 0, 'deduction' => 0, 'note' => '' ) );
	$att    = qldn_attendance_summary( $e->id, $period );
	$std    = (float) $o['standard_days'] > 0 ? (float) $o['standard_days'] : 26;
	$wh     = (float) $o['work_hours'] > 0 ? (float) $o['work_hours'] : 8;
	$ot     = null === $manual['ot_hours'] ? $att['ot_hours'] : max( 0, (float) $manual['ot_hours'] );
	$base   = (int) $e->base_salary;
	$days   = $att['work_days'] + $att['paid_days'];

	switch ( $e->salary_type ) {
		case 'day':
			$time_pay = $base * $days;
			$hourly   = $base / $wh;
			break;
		case 'hour':
			$time_pay = $base * ( $att['hours'] + $att['paid_days'] * $wh );
			$hourly   = $base;
			break;
		default:
			$time_pay = $base / $std * $days;
			$hourly   = $base / $std / $wh;
	}
	$time_pay   = (int) round( $time_pay );
	$ot_pay     = (int) round( $hourly * $ot * (float) $o['ot_rate'] / 100 );
	$allowance  = $days > 0 ? (int) $e->allowance : 0;
	$sales      = qldn_employee_sales( $e->id, $period );
	$commission = (int) round( $sales * (float) $e->commission_rate / 100 );
	$bonus      = max( 0, (int) $manual['bonus'] );
	$gross      = $time_pay + $ot_pay + $allowance + $commission + $bonus;

	// Bảo hiểm: lương đóng BH (tối đa N lần lương cơ sở) × tỷ lệ; không đi làm ngày nào thì không đóng.
	$ins_base  = min( (int) $e->insurance_salary, (int) round( (float) $o['insurance_cap'] * (int) $o['base_wage'] ) );
	$insurance = $days > 0 ? (int) round( $ins_base * (float) $o['insurance_rate'] / 100 ) : 0;

	// Phần tiền tăng ca trả cao hơn lương ngày thường được miễn thuế TNCN.
	$ot_free  = $o['ot_rate'] > 100 ? (int) round( $ot_pay - $ot_pay * 100 / (float) $o['ot_rate'] ) : 0;
	$family   = (int) $o['personal_deduction'] + (int) $e->dependents * (int) $o['dependent_deduction'];
	$taxable  = max( 0, $gross - $ot_free - $insurance - $family );
	$pit      = qldn_pit( $taxable );
	$advance  = max( 0, (int) $manual['advance'] );
	$deduct   = max( 0, (int) $manual['deduction'] );

	return array(
		'period'        => $period,
		'employee_id'   => (int) $e->id,
		'standard_days' => $std,
		'work_days'     => $att['work_days'],
		'paid_days'     => $att['paid_days'],
		'ot_hours'      => $ot,
		'base_salary'   => $base,
		'time_pay'      => $time_pay,
		'ot_pay'        => $ot_pay,
		'allowance'     => $allowance,
		'sales'         => $sales,
		'commission'    => $commission,
		'bonus'         => $bonus,
		'gross'         => $gross,
		'insurance'     => $insurance,
		'taxable'       => $taxable,
		'pit'           => $pit,
		'advance'       => $advance,
		'deduction'     => $deduct,
		'net'           => $gross - $insurance - $pit - $advance - $deduct,
		'note'          => sanitize_text_field( $manual['note'] ),
		'updated_at'    => qldn_now(),
	);
}

/**
 * Tính (lại) bảng lương tháng. Giữ thưởng, tạm ứng, khấu trừ, ghi chú đã nhập.
 *
 * @param string     $period Y-m.
 * @param array|null $manual employee_id => [ot_hours, bonus, advance, deduction, note]; null = giữ số cũ, tăng ca lấy từ chấm công.
 * @return int|WP_Error Số dòng.
 */
function qldn_payroll_build( $period, $manual = null ) {
	global $wpdb;
	if ( qldn_period_locked( $period ) ) {
		return new WP_Error( 'qldn_locked', 'Bảng lương tháng này đã chốt.' );
	}
	$t        = qldn_t( 'payroll' );
	$te       = qldn_t( 'employees' );
	$existing = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE period = %s", $period ) ) as $r ) { // phpcs:ignore WordPress.DB.PreparedSQL
		$existing[ $r->employee_id ] = $r;
	}
	// Nhân viên đang làm + người đã có trong bảng (nghỉ giữa tháng vẫn giữ dòng lương).
	$ids  = array_unique( array_merge( array_map( 'intval', $wpdb->get_col( "SELECT id FROM $te WHERE status = 'active'" ) ), array_keys( $existing ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$n    = 0;
	foreach ( $ids as $id ) {
		$e = qldn_get( 'employees', $id );
		if ( ! $e ) {
			continue;
		}
		$old = isset( $existing[ $id ] ) ? $existing[ $id ] : null;
		if ( is_array( $manual ) && isset( $manual[ $id ] ) ) {
			$m = $manual[ $id ];
		} else {
			$m = $old ? array( 'bonus' => $old->bonus, 'advance' => $old->advance, 'deduction' => $old->deduction, 'note' => $old->note ) : array();
		}
		$row = qldn_payroll_calc( $e, $period, $m );
		if ( $old ) {
			$wpdb->update( $t, $row, array( 'id' => $old->id ) );
		} else {
			$row['status'] = 'draft';
			$wpdb->insert( $t, $row );
		}
		++$n;
	}
	return $n;
}

/**
 * Đổi trạng thái bảng lương tháng.
 *
 * @param string $period Y-m.
 * @param string $status draft|locked|paid.
 */
function qldn_payroll_set_status( $period, $status ) {
	global $wpdb;
	$wpdb->update( qldn_t( 'payroll' ), array( 'status' => $status ), array( 'period' => $period ) );
}

/**
 * Xử lý: tính lương / lưu bảng / chốt / mở khoá / đã trả.
 */
function qldn_handle_payroll() {
	qldn_check( 'payroll', 'qldn_hr' );
	$period = qldn_period();
	$do     = qldn_in( 'do', 'key' );
	if ( 'build' === $do || 'save' === $do ) {
		$manual = null;
		if ( 'save' === $do ) {
			$manual = array();
			foreach ( qldn_in( 'pr', 'array', array() ) as $eid => $v ) {
				if ( ! is_array( $v ) ) {
					continue;
				}
				$manual[ (int) $eid ] = array(
					'ot_hours'  => isset( $v['ot_hours'] ) && '' !== $v['ot_hours'] ? qldn_num( wp_unslash( $v['ot_hours'] ), true ) : null,
					'bonus'     => isset( $v['bonus'] ) ? qldn_num( wp_unslash( $v['bonus'] ) ) : 0,
					'advance'   => isset( $v['advance'] ) ? qldn_num( wp_unslash( $v['advance'] ) ) : 0,
					'deduction' => isset( $v['deduction'] ) ? qldn_num( wp_unslash( $v['deduction'] ) ) : 0,
					'note'      => isset( $v['note'] ) ? sanitize_text_field( wp_unslash( $v['note'] ) ) : '',
				);
			}
		}
		$r = qldn_payroll_build( $period, $manual );
		qldn_notice( is_wp_error( $r ) ? $r->get_error_message() : sprintf( 'Đã tính lương %d nhân viên.', $r ), is_wp_error( $r ) ? 'error' : 'success' );
	} elseif ( 'lock' === $do ) {
		qldn_payroll_set_status( $period, 'locked' );
		qldn_notice( 'Đã chốt bảng lương. Bảng công tháng này bị khoá.' );
	} elseif ( 'paid' === $do ) {
		qldn_payroll_set_status( $period, 'paid' );
		qldn_notice( 'Đã đánh dấu đã trả lương.' );
	} elseif ( 'unlock' === $do ) {
		if ( ! current_user_can( 'qldn_settings' ) ) {
			wp_die( 'Chỉ quản lý mới mở khoá được bảng lương.', 403 );
		}
		qldn_payroll_set_status( $period, 'draft' );
		qldn_notice( 'Đã mở khoá bảng lương.' );
	}
	qldn_go( 'payroll', array( 'period' => $period ) );
}
add_action( 'admin_post_qldn_payroll', 'qldn_handle_payroll' );

/**
 * Dòng bảng lương kèm tên nhân viên.
 *
 * @param string $period Y-m.
 * @param int    $employee_id 0 = tất cả.
 * @return array
 */
function qldn_payroll_rows( $period, $employee_id = 0 ) {
	global $wpdb;
	$sql = $wpdb->prepare( 'SELECT p.*, e.code, e.name, e.department, e.position, e.bank_account, e.salary_type, e.dependents FROM ' . qldn_t( 'payroll' ) . ' p JOIN ' . qldn_t( 'employees' ) . ' e ON e.id = p.employee_id WHERE p.period = %s', $period );
	if ( $employee_id ) {
		$sql .= $wpdb->prepare( ' AND p.employee_id = %d', $employee_id );
	}
	return $wpdb->get_results( $sql . ' ORDER BY e.department, e.name' ); // phpcs:ignore WordPress.DB.PreparedSQL
}

/**
 * Xuất CSV bảng lương.
 */
function qldn_export_payroll() {
	qldn_check( 'export_payroll', 'qldn_hr' );
	$period = qldn_period();
	$out    = array();
	foreach ( qldn_payroll_rows( $period ) as $r ) {
		$out[] = array( $r->code, $r->name, $r->department, (float) $r->work_days, (float) $r->paid_days, (float) $r->ot_hours, $r->base_salary, $r->time_pay, $r->ot_pay, $r->allowance, $r->sales, $r->commission, $r->bonus, $r->gross, $r->insurance, $r->taxable, $r->pit, $r->advance, $r->deduction, $r->net, $r->bank_account, $r->note );
	}
	qldn_csv( 'bang-luong-' . $period . '.csv', array( 'Mã NV', 'Họ tên', 'Bộ phận', 'Ngày công', 'Phép/lễ', 'Giờ tăng ca', 'Lương cơ bản', 'Lương thời gian', 'Tiền tăng ca', 'Phụ cấp', 'Doanh số', 'Hoa hồng', 'Thưởng', 'Tổng thu nhập', 'BHXH-BHYT-BHTN', 'Thu nhập tính thuế', 'Thuế TNCN', 'Tạm ứng', 'Khấu trừ khác', 'Thực lĩnh', 'Tài khoản ngân hàng', 'Ghi chú' ), $out );
}
add_action( 'admin_post_qldn_export_payroll', 'qldn_export_payroll' );

/**
 * In phiếu lương (1 người hoặc cả bảng).
 */
function qldn_print_payslip() {
	qldn_check( 'print_payslip', 'qldn_hr' );
	$period = qldn_period();
	$rows   = qldn_payroll_rows( $period, qldn_in( 'employee', 'int', 0 ) );
	$opt    = qldn_opt();
	$label  = substr( $period, 5 ) . '/' . substr( $period, 0, 4 );
	echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Phiếu lương ' . esc_html( $label ) . '</title><style>body{font-family:Arial,sans-serif;font-size:13px;margin:0 auto;max-width:720px}.slip{border:1px solid #999;padding:14px 18px;margin:12px 0;page-break-inside:avoid}h2{text-align:center;margin:4px 0 10px;font-size:17px}table{width:100%;border-collapse:collapse}td{padding:4px 6px;border-bottom:1px dotted #bbb}td.r{text-align:right;white-space:nowrap}.t td{font-weight:bold;border-top:1px solid #000}.muted{color:#555}.sig{display:flex;justify-content:space-around;margin-top:16px;text-align:center}@media print{.noprint{display:none}.slip{page-break-after:always}}</style></head><body onload="window.print()">';
	if ( ! $rows ) {
		echo '<p>Chưa có bảng lương tháng ' . esc_html( $label ) . '.</p>';
	}
	foreach ( $rows as $r ) {
		$lines = array(
			array( 'Ngày công thực tế / chuẩn', qldn_qty( $r->work_days ) . ' / ' . qldn_qty( $r->standard_days ) ),
			array( 'Nghỉ phép, lễ hưởng lương', qldn_qty( $r->paid_days ) . ' ngày' ),
			array( 'Lương cơ bản', qldn_money( $r->base_salary ) ),
			array( 'Lương theo ngày công', qldn_money( $r->time_pay ) ),
			array( 'Tăng ca (' . qldn_qty( $r->ot_hours ) . ' giờ)', qldn_money( $r->ot_pay ) ),
			array( 'Phụ cấp', qldn_money( $r->allowance ) ),
			array( 'Hoa hồng (doanh số ' . qldn_money( $r->sales, false ) . ')', qldn_money( $r->commission ) ),
			array( 'Thưởng', qldn_money( $r->bonus ) ),
			array( '<strong>Tổng thu nhập</strong>', '<strong>' . qldn_money( $r->gross ) . '</strong>' ),
			array( 'Trừ BHXH, BHYT, BHTN', ( $r->insurance ? '-' : '' ) . qldn_money( $r->insurance ) ),
			array( 'Trừ thuế TNCN (thu nhập tính thuế ' . qldn_money( $r->taxable, false ) . ')', ( $r->pit ? '-' : '' ) . qldn_money( $r->pit ) ),
			array( 'Trừ tạm ứng', ( $r->advance ? '-' : '' ) . qldn_money( $r->advance ) ),
			array( 'Khấu trừ khác', ( $r->deduction ? '-' : '' ) . qldn_money( $r->deduction ) ),
		);
		echo '<div class="slip"><div class="muted">' . esc_html( $opt['company_name'] ) . '</div><h2>PHIẾU LƯƠNG THÁNG ' . esc_html( $label ) . '</h2>';
		echo '<p>Nhân viên: <strong>' . esc_html( $r->name ) . '</strong> (' . esc_html( $r->code ) . ')' . ( $r->department ? ' – ' . esc_html( $r->department ) : '' ) . ( $r->position ? ', ' . esc_html( $r->position ) : '' ) . '</p><table>';
		foreach ( $lines as $l ) {
			echo '<tr><td>' . wp_kses_post( $l[0] ) . '</td><td class="r">' . wp_kses_post( $l[1] ) . '</td></tr>';
		}
		echo '<tr class="t"><td>THỰC LĨNH</td><td class="r">' . esc_html( qldn_money( $r->net ) ) . '</td></tr></table>';
		if ( $r->note ) {
			echo '<p class="muted">Ghi chú: ' . esc_html( $r->note ) . '</p>';
		}
		if ( $r->bank_account ) {
			echo '<p class="muted">Chuyển khoản: ' . esc_html( $r->bank_account ) . '</p>';
		}
		echo '<div class="sig"><div>Người lập<br><br><br></div><div>Người nhận<br><br><br></div></div></div>';
	}
	echo '<p class="noprint" style="text-align:center"><button onclick="window.print()">In</button></p></body></html>';
	exit;
}
add_action( 'admin_post_qldn_print_payslip', 'qldn_print_payslip' );

/**
 * Trang bảng lương.
 */
function qldn_page_payroll() {
	$period = qldn_period();
	$ts     = strtotime( $period . '-01' );
	$rows   = qldn_payroll_rows( $period );
	$status = $rows ? $rows[0]->status : '';
	$edit   = 'draft' === $status;
	$labels = array( 'draft' => qldn_badge( 'Bản nháp', 'orange' ), 'locked' => qldn_badge( 'Đã chốt', 'blue' ), 'paid' => qldn_badge( 'Đã trả lương', 'green' ) );

	echo '<div class="wrap qldn"><h1>Bảng lương tháng ' . esc_html( gmdate( 'm/Y', $ts ) ) . ' ' . ( $status ? $labels[ $status ] : '' ) . '</h1>'; // phpcs:ignore WordPress.Security.EscapeOutput
	qldn_print_notices();
	echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-payroll">Tháng <input type="month" name="period" value="' . esc_attr( $period ) . '"> <button class="button">Xem</button> ';
	echo '<a class="button" href="' . esc_url( qldn_url( 'payroll', array( 'period' => gmdate( 'Y-m', strtotime( '-1 month', $ts ) ) ) ) ) . '">‹ Tháng trước</a> <a class="button" href="' . esc_url( qldn_url( 'payroll', array( 'period' => gmdate( 'Y-m', strtotime( '+1 month', $ts ) ) ) ) ) . '">Tháng sau ›</a> <a class="button" href="' . esc_url( qldn_url( 'attendance', array( 'period' => $period ) ) ) . '">Bảng chấm công</a></form>';

	$btn = function ( $do, $label, $class = 'button', $confirm = '' ) use ( $period ) {
		qldn_form_open( 'payroll', array( 'class' => 'qldn-inline' ) + ( $confirm ? array( 'onsubmit' => 'return confirm(' . wp_json_encode( $confirm ) . ')' ) : array() ) );
		echo '<input type="hidden" name="period" value="' . esc_attr( $period ) . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '"><button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
	};
	echo '<p>';
	if ( ! $status || $edit ) {
		$btn( 'build', $rows ? 'Tính lại từ bảng công' : 'Tính lương tháng này', 'button button-primary', $rows ? 'Tính lại: giờ tăng ca lấy lại từ bảng công, giữ thưởng / tạm ứng / khấu trừ. Tiếp tục?' : '' );
	}
	if ( $edit ) {
		$btn( 'lock', 'Chốt bảng lương', 'button', 'Sau khi chốt, bảng công và bảng lương tháng này không sửa được nữa. Chốt?' );
	}
	if ( 'locked' === $status ) {
		$btn( 'paid', 'Đánh dấu đã trả lương', 'button button-primary' );
	}
	if ( $status && ! $edit && current_user_can( 'qldn_settings' ) ) {
		$btn( 'unlock', 'Mở khoá', 'button', 'Mở khoá để sửa bảng công / bảng lương?' );
	}
	if ( $rows ) {
		echo '<a class="button" href="' . esc_url( qldn_action_url( 'export_payroll', array( 'period' => $period ) ) ) . '">Xuất Excel (CSV)</a> <a class="button" target="_blank" href="' . esc_url( qldn_action_url( 'print_payslip', array( 'period' => $period ) ) ) . '">In tất cả phiếu lương</a>';
	}
	echo '</p>';

	if ( ! $rows ) {
		echo '<p>Chưa có bảng lương. Chấm công xong, bấm <strong>Tính lương tháng này</strong>.</p></div>';
		return;
	}
	$money = function ( $name, $v, $eid ) use ( $edit ) {
		return $edit ? '<input type="text" inputmode="numeric" class="qldn-money qldn-cell" name="pr[' . esc_attr( $eid ) . '][' . esc_attr( $name ) . ']" value="' . esc_attr( qldn_money( $v, false ) ) . '">' : esc_html( qldn_money( $v, false ) );
	};
	if ( $edit ) {
		qldn_form_open( 'payroll' );
		echo '<input type="hidden" name="period" value="' . esc_attr( $period ) . '"><input type="hidden" name="do" value="save">';
	}
	echo '<div class="qldn-att-wrap"><table class="widefat striped qldn-table qldn-payroll"><thead><tr><th>Nhân viên</th><th class="num">Công</th><th class="num">Phép/lễ</th><th class="num">Giờ TC</th><th class="num">Lương thời gian</th><th class="num">Tăng ca</th><th class="num">Phụ cấp</th><th class="num">Hoa hồng</th><th class="num">Thưởng</th><th class="num">Tổng thu nhập</th><th class="num">Bảo hiểm</th><th class="num">Thuế TNCN</th><th class="num">Tạm ứng</th><th class="num">Khấu trừ</th><th class="num">Thực lĩnh</th><th>Ghi chú</th><th></th></tr></thead><tbody>';
	$tot = array_fill_keys( array( 'time_pay', 'ot_pay', 'allowance', 'commission', 'bonus', 'gross', 'insurance', 'pit', 'advance', 'deduction', 'net' ), 0 );
	foreach ( $rows as $r ) {
		foreach ( $tot as $k => $v ) {
			$tot[ $k ] += (int) $r->$k;
		}
		$eid = $r->employee_id;
		echo '<tr><td><strong>' . esc_html( $r->name ) . '</strong><br><small>' . esc_html( $r->code . ( $r->department ? ' · ' . $r->department : '' ) ) . '</small></td>';
		echo '<td class="num">' . esc_html( qldn_qty( $r->work_days ) ) . '</td><td class="num">' . esc_html( qldn_qty( $r->paid_days ) ) . '</td>';
		echo '<td class="num">' . ( $edit ? '<input type="text" inputmode="decimal" class="qldn-cell qldn-cell-s" name="pr[' . esc_attr( $eid ) . '][ot_hours]" value="' . esc_attr( qldn_qty( $r->ot_hours ) ) . '">' : esc_html( qldn_qty( $r->ot_hours ) ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="num">' . esc_html( qldn_money( $r->time_pay, false ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->ot_pay, false ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->allowance, false ) ) . '</td>';
		echo '<td class="num" title="Doanh số ' . esc_attr( qldn_money( $r->sales ) ) . '">' . esc_html( qldn_money( $r->commission, false ) ) . '</td>';
		echo '<td class="num">' . $money( 'bonus', $r->bonus, $eid ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="num"><strong>' . esc_html( qldn_money( $r->gross, false ) ) . '</strong></td><td class="num">' . esc_html( qldn_money( $r->insurance, false ) ) . '</td><td class="num" title="Thu nhập tính thuế ' . esc_attr( qldn_money( $r->taxable ) ) . '">' . esc_html( qldn_money( $r->pit, false ) ) . '</td>';
		echo '<td class="num">' . $money( 'advance', $r->advance, $eid ) . '</td><td class="num">' . $money( 'deduction', $r->deduction, $eid ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="num"><strong>' . esc_html( qldn_money( $r->net, false ) ) . '</strong></td>';
		echo '<td>' . ( $edit ? '<input type="text" class="qldn-cell" name="pr[' . esc_attr( $eid ) . '][note]" value="' . esc_attr( $r->note ) . '">' : esc_html( $r->note ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td><a target="_blank" href="' . esc_url( qldn_action_url( 'print_payslip', array( 'period' => $period, 'employee' => $eid ) ) ) . '">Phiếu lương</a></td></tr>';
	}
	echo '</tbody><tfoot><tr><th>Tổng cộng (' . count( $rows ) . ' người)</th><th></th><th></th><th></th>';
	foreach ( $tot as $v ) {
		echo '<th class="num">' . esc_html( qldn_money( $v, false ) ) . '</th>';
	}
	echo '<th></th><th></th></tr></tfoot></table></div>';
	if ( $edit ) {
		submit_button( 'Lưu & tính lại' );
		echo '</form>';
	}
	$o = qldn_opt();
	echo '<p class="description">Cách tính: lương tháng ÷ ' . esc_html( qldn_qty( $o['standard_days'] ) ) . ' ngày công chuẩn × (ngày công + phép/lễ); tăng ca = lương giờ × ' . esc_html( qldn_qty( $o['ot_rate'] ) ) . '%; bảo hiểm ' . esc_html( qldn_qty( $o['insurance_rate'] ) ) . '% lương đóng BH (tối đa ' . esc_html( qldn_money( $o['insurance_cap'] * $o['base_wage'] ) ) . '); thuế TNCN lũy tiến sau giảm trừ ' . esc_html( qldn_money( $o['personal_deduction'] ) ) . ' + ' . esc_html( qldn_money( $o['dependent_deduction'] ) ) . '/người phụ thuộc, phần tăng ca vượt lương thường được miễn thuế. Thay đổi tham số ở <a href="' . esc_url( qldn_url( 'settings' ) ) . '">Cài đặt</a>.</p></div>';
}
