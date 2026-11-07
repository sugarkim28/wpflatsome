<?php
/**
 * Sổ quỹ (phiếu thu / phiếu chi, quỹ tiền mặt & ngân hàng) và nhà cung cấp (công nợ phải trả).
 *
 * Phiếu tự động: bán hàng có thu tiền, thu nợ khách, huỷ đơn đã thu (hoàn tiền), thu tiền hợp đồng,
 * trả lương, nhập hàng có trả tiền, trả nợ nhà cung cấp. Phiếu tự động không xoá được – huỷ chứng từ gốc.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quỹ.
 *
 * @return array
 */
function qldn_funds() {
	return array(
		'cash' => 'Tiền mặt',
		'bank' => 'Ngân hàng',
	);
}

/**
 * Loại thu / chi.
 *
 * @param string $dir in|out.
 * @return array
 */
function qldn_cash_categories( $dir ) {
	if ( 'in' === $dir ) {
		return array( 'Thu tiền bán hàng', 'Thu nợ khách hàng', 'Thu tiền hợp đồng', 'Số dư đầu kỳ', 'Góp vốn / vay', 'Thu khác' );
	}
	return array( 'Trả nhà cung cấp', 'Trả lương', 'Hoàn tiền khách', 'Thuê mặt bằng', 'Điện, nước, internet', 'Vận chuyển', 'Marketing, quảng cáo', 'Nộp thuế, phí', 'Chi phí khác' );
}

/**
 * Quỹ theo hình thức thanh toán của đơn.
 *
 * @param string $payment cash|transfer|card.
 * @return string
 */
function qldn_fund_of( $payment ) {
	return 'cash' === $payment ? 'cash' : 'bank';
}

/**
 * Ghi phiếu thu / chi.
 *
 * @param array $d direction, fund, category, amount, entry_date, partner_type, partner_id, partner_name, ref_type, ref_id, note.
 * @return int|WP_Error
 */
function qldn_cash_add( $d ) {
	global $wpdb;
	$dir    = 'out' === ( $d['direction'] ?? '' ) ? 'out' : 'in';
	$amount = (int) ( $d['amount'] ?? 0 );
	if ( $amount <= 0 ) {
		return new WP_Error( 'qldn_amount', 'Số tiền phải lớn hơn 0.' );
	}
	$date = $d['entry_date'] ?? '';
	$row  = array(
		'direction'    => $dir,
		'fund'         => isset( qldn_funds()[ $d['fund'] ?? '' ] ) ? $d['fund'] : 'cash',
		'category'     => sanitize_text_field( $d['category'] ?? '' ),
		'amount'       => $amount,
		'entry_date'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : qldn_today(),
		'partner_type' => sanitize_key( $d['partner_type'] ?? '' ),
		'partner_id'   => (int) ( $d['partner_id'] ?? 0 ),
		'partner_name' => sanitize_text_field( $d['partner_name'] ?? '' ),
		'ref_type'     => sanitize_key( $d['ref_type'] ?? '' ),
		'ref_id'       => (int) ( $d['ref_id'] ?? 0 ),
		'note'         => sanitize_textarea_field( $d['note'] ?? '' ),
		'user_id'      => get_current_user_id(),
		'created_at'   => qldn_now(),
	);
	if ( '' === $row['category'] ) {
		$row['category'] = 'in' === $dir ? 'Thu khác' : 'Chi phí khác';
	}
	if ( '' === $row['partner_name'] && $row['partner_id'] ) {
		$p                   = qldn_get( 'supplier' === $row['partner_type'] ? 'suppliers' : ( 'employee' === $row['partner_type'] ? 'employees' : 'customers' ), $row['partner_id'] );
		$row['partner_name'] = $p ? $p->name : '';
	}
	$wpdb->insert( qldn_t( 'cashbook' ), $row );
	$id = (int) $wpdb->insert_id;
	$wpdb->update( qldn_t( 'cashbook' ), array( 'code' => qldn_code( 'in' === $dir ? 'PT' : 'PC', $id ) ), array( 'id' => $id ) );
	return $id;
}

/* ---------- Phiếu tự động từ các nghiệp vụ khác ---------- */

add_action(
	'qldn_order_created',
	function ( $id ) {
		$o = qldn_get( 'orders', $id );
		if ( $o && $o->paid > 0 ) {
			qldn_cash_add( array( 'direction' => 'in', 'fund' => qldn_fund_of( $o->payment ), 'category' => 'Thu tiền bán hàng', 'amount' => $o->paid, 'partner_type' => $o->customer_id ? 'customer' : '', 'partner_id' => $o->customer_id, 'partner_name' => $o->customer_id ? '' : 'Khách lẻ', 'ref_type' => 'order', 'ref_id' => $id, 'note' => 'Đơn ' . $o->code ) );
		}
	},
	20
);
add_action(
	'qldn_order_paid',
	function ( $id, $amount, $fund = '' ) {
		$o = qldn_get( 'orders', $id );
		qldn_cash_add( array( 'direction' => 'in', 'fund' => $fund ? $fund : qldn_fund_of( $o->payment ), 'category' => 'Thu nợ khách hàng', 'amount' => $amount, 'partner_type' => 'customer', 'partner_id' => $o->customer_id, 'ref_type' => 'order', 'ref_id' => $id, 'note' => 'Thu nợ đơn ' . $o->code ) );
	},
	10,
	3
);
add_action(
	'qldn_order_cancelled',
	function ( $id ) {
		$o = qldn_get( 'orders', $id );
		if ( $o && $o->paid > 0 ) {
			qldn_cash_add( array( 'direction' => 'out', 'fund' => qldn_fund_of( $o->payment ), 'category' => 'Hoàn tiền khách', 'amount' => $o->paid, 'partner_type' => $o->customer_id ? 'customer' : '', 'partner_id' => $o->customer_id, 'partner_name' => $o->customer_id ? '' : 'Khách lẻ', 'ref_type' => 'order', 'ref_id' => $id, 'note' => 'Huỷ đơn ' . $o->code ) );
		}
	},
	20
);
add_action(
	'qldn_contract_paid',
	function ( $id, $amount, $fund ) {
		$c = qldn_get( 'contracts', $id );
		qldn_cash_add( array( 'direction' => 'in', 'fund' => $fund, 'category' => 'Thu tiền hợp đồng', 'amount' => $amount, 'partner_type' => 'customer', 'partner_id' => $c->party_id, 'ref_type' => 'contract', 'ref_id' => $id, 'note' => 'Hợp đồng ' . $c->code ) );
	},
	10,
	3
);
add_action(
	'qldn_payroll_paid',
	function ( $period ) {
		global $wpdb;
		$ref = (int) str_replace( '-', '', $period );
		if ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . qldn_t( 'cashbook' ) . " WHERE ref_type = 'payroll' AND ref_id = %d", $ref ) ) ) {
			return; // Đã ghi chi lương tháng này.
		}
		$net = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(net),0) FROM ' . qldn_t( 'payroll' ) . ' WHERE period = %s AND net > 0', $period ) );
		if ( $net > 0 ) {
			qldn_cash_add( array( 'direction' => 'out', 'fund' => 'bank', 'category' => 'Trả lương', 'amount' => $net, 'partner_name' => 'Nhân viên', 'ref_type' => 'payroll', 'ref_id' => $ref, 'note' => 'Lương tháng ' . substr( $period, 5 ) . '/' . substr( $period, 0, 4 ) . ' (thực lĩnh)' ) );
		}
		qldn_log( 'payroll_paid', 'payroll', 0, 'Tháng ' . $period . ' – ' . qldn_money( $net ) );
	}
);

/**
 * Thu nợ khách theo tổng: trừ dần vào các đơn còn nợ, cũ trước.
 *
 * @param int    $customer_id Khách.
 * @param int    $amount      Số tiền.
 * @param string $fund        Quỹ.
 * @return int Số tiền đã thu.
 */
function qldn_customer_collect( $customer_id, $amount, $fund = 'cash' ) {
	global $wpdb;
	$left   = (int) $amount;
	$orders = $wpdb->get_results( $wpdb->prepare( 'SELECT id, total, paid FROM ' . qldn_t( 'orders' ) . " WHERE customer_id = %d AND status = 'done' AND paid < total ORDER BY id", $customer_id ) );
	$done   = 0;
	foreach ( $orders as $o ) {
		if ( $left <= 0 ) {
			break;
		}
		$r = qldn_order_pay( $o->id, min( $left, $o->total - $o->paid ), $fund );
		if ( ! is_wp_error( $r ) ) {
			$left -= $r;
			$done += $r;
		}
	}
	return $done;
}

/* ---------- Nhà cung cấp & nhập hàng ---------- */

/**
 * Lưu nhà cung cấp.
 *
 * @param array $d  Dữ liệu.
 * @param int   $id ID khi sửa.
 * @return int|WP_Error
 */
function qldn_supplier_save( $d, $id = 0 ) {
	global $wpdb;
	$row = array(
		'name'         => sanitize_text_field( $d['name'] ?? '' ),
		'phone'        => qldn_phone( $d['phone'] ?? '' ),
		'email'        => sanitize_email( $d['email'] ?? '' ),
		'address'      => sanitize_text_field( $d['address'] ?? '' ),
		'tax_code'     => sanitize_text_field( $d['tax_code'] ?? '' ),
		'bank_account' => sanitize_text_field( $d['bank_account'] ?? '' ),
		'note'         => sanitize_textarea_field( $d['note'] ?? '' ),
	);
	if ( '' === $row['name'] ) {
		return new WP_Error( 'qldn_name', 'Chưa nhập tên nhà cung cấp.' );
	}
	if ( $id ) {
		$wpdb->update( qldn_t( 'suppliers' ), $row, array( 'id' => $id ) );
		return $id;
	}
	$row['created_at'] = qldn_now();
	$wpdb->insert( qldn_t( 'suppliers' ), $row );
	$id = (int) $wpdb->insert_id;
	$wpdb->update( qldn_t( 'suppliers' ), array( 'code' => qldn_code( 'NCC', $id, 4 ) ), array( 'id' => $id ) );
	return $id;
}

/**
 * Ghi nhận mua hàng của phiếu nhập: tăng công nợ nhà cung cấp, ghi phiếu chi phần trả ngay.
 *
 * @param string $ref         Số phiếu nhập.
 * @param int    $supplier_id Nhà cung cấp.
 * @param array  $lines       [ [qty, unit_cost] ].
 * @param int    $paid        Trả ngay.
 * @param string $fund        Quỹ.
 * @return int Tổng tiền hàng.
 */
function qldn_purchase_record( $ref, $supplier_id, $lines, $paid, $fund = 'cash' ) {
	global $wpdb;
	$total = 0;
	foreach ( $lines as $l ) {
		$total += (int) round( abs( (float) $l['qty'] ) * max( 0, (int) ( $l['unit_cost'] ?? 0 ) ) );
	}
	$paid = min( max( 0, (int) $paid ), $total );
	$wpdb->insert( qldn_t( 'purchases' ), array( 'code' => $ref, 'supplier_id' => $supplier_id, 'total' => $total, 'paid' => $paid, 'user_id' => get_current_user_id(), 'created_at' => qldn_now() ) );
	if ( $supplier_id ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . qldn_t( 'suppliers' ) . ' SET total_purchase = total_purchase + %d, debt = debt + %d WHERE id = %d', $total, $total - $paid, $supplier_id ) );
	}
	if ( $paid > 0 ) {
		qldn_cash_add( array( 'direction' => 'out', 'fund' => $fund, 'category' => 'Trả nhà cung cấp', 'amount' => $paid, 'partner_type' => $supplier_id ? 'supplier' : '', 'partner_id' => $supplier_id, 'ref_type' => 'purchase', 'ref_id' => (int) $wpdb->insert_id, 'note' => 'Phiếu nhập ' . $ref ) );
	}
	return $total;
}

/**
 * Trả nợ nhà cung cấp.
 *
 * @param int    $supplier_id NCC.
 * @param int    $amount      Số tiền.
 * @param string $fund        Quỹ.
 * @param string $note        Ghi chú.
 * @return int|WP_Error
 */
function qldn_supplier_pay( $supplier_id, $amount, $fund = 'cash', $note = '' ) {
	global $wpdb;
	$s = qldn_get( 'suppliers', $supplier_id );
	if ( ! $s ) {
		return new WP_Error( 'qldn_supplier', 'Không tìm thấy nhà cung cấp.' );
	}
	$amount = (int) $amount;
	if ( $amount <= 0 ) {
		return new WP_Error( 'qldn_amount', 'Số tiền phải lớn hơn 0.' );
	}
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . qldn_t( 'suppliers' ) . ' SET debt = debt - %d WHERE id = %d', $amount, $supplier_id ) );
	$id = qldn_cash_add( array( 'direction' => 'out', 'fund' => $fund, 'category' => 'Trả nhà cung cấp', 'amount' => $amount, 'partner_type' => 'supplier', 'partner_id' => $supplier_id, 'ref_type' => 'supplier', 'ref_id' => $supplier_id, 'note' => $note ? $note : 'Trả nợ ' . $s->name ) );
	qldn_log( 'supplier_pay', 'supplier', $supplier_id, $s->name . ' – ' . qldn_money( $amount ) );
	return $id;
}

/**
 * Danh sách NCC cho ô chọn.
 *
 * @return array
 */
function qldn_supplier_options() {
	global $wpdb;
	$out = array();
	foreach ( $wpdb->get_results( 'SELECT id, code, name FROM ' . qldn_t( 'suppliers' ) . ' ORDER BY name' ) as $r ) {
		$out[ $r->id ] = $r->name . ' (' . $r->code . ')';
	}
	return $out;
}

/* ---------- Xử lý form ---------- */

/**
 * Lập phiếu thu / chi tay.
 */
function qldn_handle_cash() {
	qldn_check( 'cash', 'qldn_cash' );
	$dir = qldn_in( 'direction', 'key', 'in' );
	$sid = qldn_in( 'supplier_id', 'int', 0 );
	$cid = qldn_in( 'customer_id', 'int', 0 );
	$cat = qldn_in( 'category' );
	// Thu nợ khách / trả nợ NCC qua phiếu tay vẫn trừ công nợ.
	if ( 'in' === $dir && $cid && 'Thu nợ khách hàng' === $cat ) {
		$n = qldn_customer_collect( $cid, qldn_in( 'amount', 'money', 0 ), qldn_in( 'fund', 'key', 'cash' ) );
		qldn_notice( $n ? 'Đã thu nợ ' . qldn_money( $n ) . ' (trừ vào các đơn còn nợ, cũ trước).' : 'Khách không còn nợ.', $n ? 'success' : 'warning' );
		qldn_go( 'cash' );
	}
	if ( 'out' === $dir && $sid && 'Trả nhà cung cấp' === $cat ) {
		$r = qldn_supplier_pay( $sid, qldn_in( 'amount', 'money', 0 ), qldn_in( 'fund', 'key', 'cash' ), qldn_in( 'note', 'textarea' ) );
	} else {
		$r = qldn_cash_add(
			array(
				'direction'    => $dir,
				'fund'         => qldn_in( 'fund', 'key', 'cash' ),
				'category'     => $cat,
				'amount'       => qldn_in( 'amount', 'money', 0 ),
				'entry_date'   => qldn_in( 'entry_date', 'date' ),
				'partner_type' => $sid ? 'supplier' : ( $cid ? 'customer' : '' ),
				'partner_id'   => $sid ? $sid : $cid,
				'partner_name' => qldn_in( 'partner_name' ),
				'note'         => qldn_in( 'note', 'textarea' ),
			)
		);
		if ( ! is_wp_error( $r ) ) {
			$e = qldn_get( 'cashbook', $r );
			qldn_log( 'cash_entry', 'cash', $r, $e->code . ' – ' . $e->category . ' – ' . qldn_money( $e->amount ) );
		}
	}
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'cash', array( 'tab' => $dir ) );
	}
	$e = qldn_get( 'cashbook', $r );
	qldn_notice( sprintf( 'Đã lập %s <strong>%s</strong> – %s. <a target="_blank" href="%s">In phiếu</a>', 'in' === $dir ? 'phiếu thu' : 'phiếu chi', esc_html( $e->code ), esc_html( qldn_money( $e->amount ) ), esc_url( qldn_action_url( 'cash_print', array( 'id' => $r ) ) ) ) );
	qldn_go( 'cash' );
}
add_action( 'admin_post_qldn_cash', 'qldn_handle_cash' );

/**
 * Xoá phiếu tay.
 */
function qldn_handle_cash_delete() {
	qldn_check( 'cash_delete', 'qldn_cash' );
	global $wpdb;
	$e = qldn_get( 'cashbook', qldn_in( 'id', 'int', 0 ) );
	if ( $e && '' === $e->ref_type ) {
		$wpdb->delete( qldn_t( 'cashbook' ), array( 'id' => $e->id ) );
		qldn_log( 'cash_delete', 'cash', $e->id, $e->code . ' – ' . $e->category . ' – ' . qldn_money( $e->amount ) );
		qldn_notice( 'Đã xoá phiếu ' . esc_html( $e->code ) . '.' );
	} else {
		qldn_notice( 'Phiếu tự động từ đơn hàng / hợp đồng / lương / nhập hàng không xoá được – hãy huỷ chứng từ gốc.', 'error' );
	}
	qldn_go( 'cash' );
}
add_action( 'admin_post_qldn_cash_delete', 'qldn_handle_cash_delete' );

/**
 * Lưu nhà cung cấp.
 */
function qldn_handle_supplier() {
	if ( ! current_user_can( 'qldn_cash' ) && ! current_user_can( 'qldn_stock' ) ) {
		wp_die( 'Bạn không có quyền.', 403 );
	}
	check_admin_referer( 'qldn_supplier' );
	$id = qldn_in( 'id', 'int', 0 );
	$r  = qldn_supplier_save(
		array(
			'name'         => qldn_in( 'name' ),
			'phone'        => qldn_in( 'phone' ),
			'email'        => qldn_in( 'email', 'email' ),
			'address'      => qldn_in( 'address' ),
			'tax_code'     => qldn_in( 'tax_code' ),
			'bank_account' => qldn_in( 'bank_account' ),
			'note'         => qldn_in( 'note', 'textarea' ),
		),
		$id
	);
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'suppliers', array( 'edit' => $id ? $id : 'new' ) );
	}
	qldn_notice( $id ? 'Đã cập nhật nhà cung cấp.' : 'Đã thêm nhà cung cấp.' );
	qldn_go( 'suppliers', array( 'view' => $r ) );
}
add_action( 'admin_post_qldn_supplier', 'qldn_handle_supplier' );

/**
 * Trả nợ NCC từ trang nhà cung cấp.
 */
function qldn_handle_supplier_pay() {
	qldn_check( 'supplier_pay', 'qldn_cash' );
	$sid = qldn_in( 'id', 'int', 0 );
	$r   = qldn_supplier_pay( $sid, qldn_in( 'amount', 'money', 0 ), qldn_in( 'fund', 'key', 'cash' ), qldn_in( 'note' ) );
	qldn_notice( is_wp_error( $r ) ? $r->get_error_message() : 'Đã lập phiếu chi trả nợ nhà cung cấp.', is_wp_error( $r ) ? 'error' : 'success' );
	qldn_go( 'suppliers', array( 'view' => $sid ) );
}
add_action( 'admin_post_qldn_supplier_pay', 'qldn_handle_supplier_pay' );

/**
 * Thu nợ khách (tổng) từ hồ sơ khách hàng.
 */
function qldn_handle_customer_collect() {
	qldn_check( 'customer_collect', 'qldn_sales' );
	$cid = qldn_in( 'id', 'int', 0 );
	$n   = qldn_customer_collect( $cid, qldn_in( 'amount', 'money', 0 ), qldn_in( 'fund', 'key', 'cash' ) );
	qldn_notice( $n ? 'Đã thu nợ ' . qldn_money( $n ) . ' (trừ vào các đơn còn nợ, cũ trước).' : 'Không có khoản nợ nào được thu.', $n ? 'success' : 'warning' );
	qldn_go( 'customers', array( 'view' => $cid ) );
}
add_action( 'admin_post_qldn_customer_collect', 'qldn_handle_customer_collect' );

/**
 * Xuất CSV sổ quỹ theo bộ lọc.
 */
function qldn_export_cash() {
	qldn_check( 'export_cash', 'qldn_cash' );
	global $wpdb;
	$f    = qldn_cash_filter();
	$rows = $wpdb->get_results( 'SELECT * FROM ' . qldn_t( 'cashbook' ) . ' WHERE 1=1' . $f['where'] . ' ORDER BY entry_date, id' ); // phpcs:ignore WordPress.DB.PreparedSQL
	$out  = array();
	foreach ( $rows as $r ) {
		$out[] = array( qldn_date( $r->entry_date ), $r->code, qldn_funds()[ $r->fund ] ?? $r->fund, $r->category, $r->partner_name, 'in' === $r->direction ? $r->amount : '', 'out' === $r->direction ? $r->amount : '', $r->note );
	}
	qldn_csv( 'so-quy-' . $f['from'] . '-' . $f['to'] . '.csv', array( 'Ngày', 'Số phiếu', 'Quỹ', 'Loại', 'Đối tượng', 'Thu', 'Chi', 'Diễn giải' ), $out );
}
add_action( 'admin_post_qldn_export_cash', 'qldn_export_cash' );

/**
 * In phiếu thu / chi.
 */
function qldn_handle_cash_print() {
	qldn_check( 'cash_print', 'qldn_cash' );
	$e = qldn_get( 'cashbook', qldn_in( 'id', 'int', 0 ) );
	if ( ! $e ) {
		wp_die( 'Không tìm thấy phiếu.' );
	}
	$o   = qldn_opt();
	$in  = 'in' === $e->direction;
	$h   = '<table class="qldn-head" style="width:100%"><tbody><tr><td style="text-align:left"><strong>' . esc_html( $o['company_name'] ) . '</strong><br>' . esc_html( $o['company_address'] ) . '</td><td style="text-align:right">Số: <strong>' . esc_html( $e->code ) . '</strong><br>Quỹ: ' . esc_html( qldn_funds()[ $e->fund ] ?? '' ) . '</td></tr></tbody></table>';
	$h  .= '<h1>' . ( $in ? 'PHIẾU THU' : 'PHIẾU CHI' ) . '</h1><p style="text-align:center"><em>Ngày ' . esc_html( qldn_date( $e->entry_date ) ) . '</em></p>';
	$h  .= '<p>Họ tên người ' . ( $in ? 'nộp' : 'nhận' ) . ' tiền: <strong>' . esc_html( $e->partner_name ? $e->partner_name : '……………………' ) . '</strong></p>';
	$h  .= '<p>Lý do ' . ( $in ? 'nộp' : 'chi' ) . ': ' . esc_html( $e->category . ( $e->note ? ' – ' . $e->note : '' ) ) . '</p>';
	$h  .= '<p>Số tiền: <strong>' . esc_html( qldn_money( $e->amount ) ) . '</strong></p><p>Viết bằng chữ: <em>' . esc_html( qldn_money_words( $e->amount ) ) . '</em></p>';
	$h  .= '<table class="qldn-sign" style="width:100%;margin-top:16pt"><tbody><tr>';
	foreach ( array( 'Giám đốc', 'Kế toán', 'Người lập phiếu', 'Thủ quỹ', $in ? 'Người nộp tiền' : 'Người nhận tiền' ) as $s ) {
		$h .= '<td style="text-align:center;width:20%"><strong>' . esc_html( $s ) . '</strong><br><em>(Ký, họ tên)</em><br><br><br><br></td>';
	}
	$h .= '</tr></tbody></table>';
	qldn_doc_output( ( $in ? 'Phieu thu ' : 'Phieu chi ' ) . $e->code, $h, 'print' );
}
add_action( 'admin_post_qldn_cash_print', 'qldn_handle_cash_print' );

/* ---------- Trang Sổ quỹ ---------- */

/**
 * Bộ lọc sổ quỹ.
 *
 * @return array from, to, fund, where.
 */
function qldn_cash_filter() {
	global $wpdb;
	$from = qldn_in( 'from', 'date', current_time( 'Y-m-01' ) );
	$to   = qldn_in( 'to', 'date', qldn_today() );
	$fund = qldn_in( 'fund', 'key' );
	$dir  = qldn_in( 'dir', 'key' );
	$w    = $wpdb->prepare( ' AND entry_date BETWEEN %s AND %s', $from, $to );
	if ( isset( qldn_funds()[ $fund ] ) ) {
		$w .= $wpdb->prepare( ' AND fund = %s', $fund );
	}
	if ( in_array( $dir, array( 'in', 'out' ), true ) ) {
		$w .= $wpdb->prepare( ' AND direction = %s', $dir );
	}
	$w .= qldn_like( qldn_in( 's' ), array( 'code', 'partner_name', 'note', 'category' ) );
	return array( 'from' => $from, 'to' => $to, 'fund' => $fund, 'dir' => $dir, 'where' => $w );
}

/**
 * Tồn quỹ đến hết 1 ngày.
 *
 * @param string $date Y-m-d (tồn trước ngày này nếu $before).
 * @param string $fund Quỹ ('' = tất cả).
 * @param bool   $before Chỉ tính trước ngày.
 * @return int
 */
function qldn_fund_balance( $date, $fund = '', $before = true ) {
	global $wpdb;
	$sql = $wpdb->prepare( "SELECT COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END),0) FROM " . qldn_t( 'cashbook' ) . ' WHERE entry_date ' . ( $before ? '<' : '<=' ) . ' %s', $date ); // phpcs:ignore WordPress.DB.PreparedSQL
	if ( $fund ) {
		$sql .= $wpdb->prepare( ' AND fund = %s', $fund );
	}
	return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL
}

/**
 * Trang Sổ quỹ.
 */
function qldn_page_cash() {
	$tab = qldn_in( 'tab', 'key', 'book' );
	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Sổ quỹ</h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'cash', array( 'tab' => 'in' ) ) ) . '">Lập phiếu thu</a> <a class="page-title-action" href="' . esc_url( qldn_url( 'cash', array( 'tab' => 'out' ) ) ) . '">Lập phiếu chi</a><hr class="wp-header-end">';
	qldn_print_notices();
	if ( 'in' === $tab || 'out' === $tab ) {
		qldn_cash_form( $tab );
	} else {
		qldn_cash_book();
	}
	echo '</div>';
}

/**
 * Sổ quỹ: tồn đầu, thu, chi, tồn cuối từng quỹ + danh sách phiếu.
 */
function qldn_cash_book() {
	global $wpdb;
	$f = qldn_cash_filter();
	$t = qldn_t( 'cashbook' );
	echo '<div class="qldn-cards">';
	foreach ( array_merge( qldn_funds(), array( '' => 'Tổng cộng' ) ) as $k => $label ) {
		$open = qldn_fund_balance( $f['from'], $k );
		$sum  = $wpdb->get_row( $wpdb->prepare( "SELECT COALESCE(SUM(CASE WHEN direction = 'in' THEN amount END),0) AS i, COALESCE(SUM(CASE WHEN direction = 'out' THEN amount END),0) AS o FROM $t WHERE entry_date BETWEEN %s AND %s" . ( $k ? $wpdb->prepare( ' AND fund = %s', $k ) : '' ), $f['from'], $f['to'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		echo '<div class="qldn-card"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( qldn_money( $open + $sum->i - $sum->o ) ) . '</strong><em>Đầu kỳ ' . esc_html( qldn_money( $open, false ) ) . ' · thu ' . esc_html( qldn_money( $sum->i, false ) ) . ' · chi ' . esc_html( qldn_money( $sum->o, false ) ) . '</em></div>';
	}
	echo '</div>';
	echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-cash">Từ <input type="date" name="from" value="' . esc_attr( $f['from'] ) . '"> đến <input type="date" name="to" value="' . esc_attr( $f['to'] ) . '"> <select name="fund"><option value="">Mọi quỹ</option>' . qldn_options_html( qldn_funds(), $f['fund'] ) . '</select> <select name="dir"><option value="">Thu và chi</option>' . qldn_options_html( array( 'in' => 'Chỉ thu', 'out' => 'Chỉ chi' ), $f['dir'] ) . '</select> <input type="search" name="s" value="' . esc_attr( qldn_in( 's' ) ) . '" placeholder="Số phiếu / đối tượng / nội dung"> <button class="button">Lọc</button> ';
	echo '<a class="button" href="' . esc_url( qldn_action_url( 'export_cash', array_filter( array( 'from' => $f['from'], 'to' => $f['to'], 'fund' => $f['fund'], 'dir' => $f['dir'], 's' => qldn_in( 's' ) ) ) ) ) . '">Xuất CSV</a></form>';
	$per             = 100;
	list( $paged, $off ) = qldn_paged( $per );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE 1=1" . $f['where'] ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE 1=1" . $f['where'] . ' ORDER BY entry_date DESC, id DESC LIMIT %d OFFSET %d', $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$links = array( 'order' => array( 'orders', 'view' ), 'contract' => array( 'contracts', 'view' ), 'supplier' => array( 'suppliers', 'view' ) );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Ngày</th><th>Số phiếu</th><th>Quỹ</th><th>Loại</th><th>Đối tượng</th><th>Diễn giải</th><th class="num">Thu</th><th class="num">Chi</th><th></th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="9">Chưa có phiếu thu / chi trong khoảng này.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$note = esc_html( $r->note );
		if ( isset( $links[ $r->ref_type ] ) && $r->ref_id ) {
			$note = '<a href="' . esc_url( qldn_url( $links[ $r->ref_type ][0], array( $links[ $r->ref_type ][1] => $r->ref_id ) ) ) . '">' . $note . '</a>';
		}
		echo '<tr><td>' . esc_html( qldn_date( $r->entry_date ) ) . '</td><td>' . esc_html( $r->code ) . '</td><td>' . esc_html( qldn_funds()[ $r->fund ] ?? $r->fund ) . '</td><td>' . esc_html( $r->category ) . ( $r->ref_type ? ' ' . qldn_badge( 'tự động', 'gray' ) : '' ) . '</td><td>' . esc_html( $r->partner_name ) . '</td><td>' . $note . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="num qldn-pos">' . ( 'in' === $r->direction ? esc_html( qldn_money( $r->amount, false ) ) : '' ) . '</td><td class="num qldn-neg">' . ( 'out' === $r->direction ? esc_html( qldn_money( $r->amount, false ) ) : '' ) . '</td><td class="qldn-actions"><a target="_blank" href="' . esc_url( qldn_action_url( 'cash_print', array( 'id' => $r->id ) ) ) . '">In</a>';
		if ( '' === $r->ref_type ) {
			echo ' · <a class="qldn-neg" onclick="return confirm(\'Xoá phiếu này?\')" href="' . esc_url( qldn_action_url( 'cash_delete', array( 'id' => $r->id ) ) ) . '">Xoá</a>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
	echo '<p class="description">Phiếu "tự động" sinh từ bán hàng, thu nợ, huỷ đơn, hợp đồng, nhập hàng, trả lương. Nhập số dư đầu kỳ bằng phiếu thu loại "Số dư đầu kỳ".</p>';
}

/**
 * Form lập phiếu thu / chi.
 *
 * @param string $dir in|out.
 */
function qldn_cash_form( $dir ) {
	global $wpdb;
	$in   = 'in' === $dir;
	$cats = qldn_cash_categories( $dir );
	echo '<h2>' . ( $in ? 'Lập phiếu thu' : 'Lập phiếu chi' ) . '</h2>';
	qldn_form_open( 'cash' );
	echo '<input type="hidden" name="direction" value="' . esc_attr( $dir ) . '"><table class="form-table">';
	qldn_field( 'Loại', 'category', qldn_in( 'cat' ) ? qldn_in( 'cat' ) : $cats[0], array( 'type' => 'select', 'options' => array_combine( $cats, $cats ) ) );
	qldn_field( 'Số tiền', 'amount', qldn_in( 'amount', 'money', '' ), array( 'type' => 'money', 'required' => true ) );
	qldn_field( 'Quỹ', 'fund', 'cash', array( 'type' => 'select', 'options' => qldn_funds() ) );
	qldn_field( 'Ngày', 'entry_date', qldn_today(), array( 'type' => 'date', 'class' => '' ) );
	if ( $in ) {
		$opts = array( 0 => '—' );
		foreach ( $wpdb->get_results( 'SELECT id, code, name, debt FROM ' . qldn_t( 'customers' ) . ' ORDER BY debt DESC, name LIMIT 2000' ) as $c ) {
			$opts[ $c->id ] = $c->name . ' (' . $c->code . ')' . ( $c->debt > 0 ? ' – nợ ' . qldn_money( $c->debt, false ) : '' );
		}
		qldn_field( 'Khách hàng', 'customer_id', qldn_in( 'customer', 'int', 0 ), array( 'type' => 'select', 'options' => $opts, 'help' => 'Loại "Thu nợ khách hàng" + chọn khách: tự trừ vào các đơn còn nợ, cũ trước.' ) );
	} else {
		qldn_field( 'Nhà cung cấp', 'supplier_id', qldn_in( 'supplier', 'int', 0 ), array( 'type' => 'select', 'options' => array( 0 => '—' ) + qldn_supplier_options(), 'help' => 'Loại "Trả nhà cung cấp" + chọn NCC: tự trừ công nợ phải trả.' ) );
	}
	qldn_field( $in ? 'Người nộp tiền' : 'Người nhận tiền', 'partner_name', '', array( 'help' => 'Để trống nếu đã chọn khách hàng / nhà cung cấp.' ) );
	qldn_field( 'Diễn giải', 'note', '', array( 'type' => 'textarea' ) );
	echo '</table>';
	submit_button( $in ? 'Lập phiếu thu' : 'Lập phiếu chi' );
	echo '</form>';
}

/* ---------- Trang Nhà cung cấp ---------- */

/**
 * Trang nhà cung cấp.
 */
function qldn_page_suppliers() {
	global $wpdb;
	if ( ! current_user_can( 'qldn_cash' ) && ! current_user_can( 'qldn_stock' ) ) {
		wp_die( 'Bạn không có quyền xem nhà cung cấp.', 403 );
	}
	$edit = qldn_in( 'edit' );
	$view = qldn_in( 'view', 'int', 0 );
	echo '<div class="wrap qldn">';
	if ( '' !== $edit ) {
		$s = 'new' === $edit ? null : qldn_get( 'suppliers', (int) $edit );
		echo '<h1>' . ( $s ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp' ) . '</h1>';
		qldn_print_notices();
		qldn_form_open( 'supplier' );
		echo '<input type="hidden" name="id" value="' . esc_attr( $s ? $s->id : 0 ) . '"><table class="form-table">';
		qldn_field( 'Tên nhà cung cấp', 'name', $s ? $s->name : '', array( 'required' => true ) );
		qldn_field( 'Điện thoại', 'phone', $s ? $s->phone : '', array( 'type' => 'tel' ) );
		qldn_field( 'Email', 'email', $s ? $s->email : '', array( 'type' => 'email' ) );
		qldn_field( 'Địa chỉ', 'address', $s ? $s->address : '', array( 'class' => 'large-text' ) );
		qldn_field( 'Mã số thuế', 'tax_code', $s ? $s->tax_code : '' );
		qldn_field( 'Tài khoản ngân hàng', 'bank_account', $s ? $s->bank_account : '', array( 'class' => 'large-text' ) );
		qldn_field( 'Ghi chú', 'note', $s ? $s->note : '', array( 'type' => 'textarea' ) );
		echo '</table>';
		submit_button( $s ? 'Cập nhật' : 'Thêm nhà cung cấp' );
		echo '</form></div>';
		return;
	}
	if ( $view ) {
		$s = qldn_get( 'suppliers', $view );
		if ( ! $s ) {
			echo '<p>Không tìm thấy.</p></div>';
			return;
		}
		echo '<h1 class="wp-heading-inline">' . esc_html( $s->name ) . ' <small>' . esc_html( $s->code ) . '</small></h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'suppliers', array( 'edit' => $s->id ) ) ) . '">Sửa</a> <a class="page-title-action" href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'nhap', 'supplier' => $s->id ) ) ) . '">Nhập hàng</a><hr class="wp-header-end">';
		qldn_print_notices();
		echo '<div class="qldn-cards"><div class="qldn-card"><span>Tổng tiền hàng đã mua</span><strong>' . esc_html( qldn_money( $s->total_purchase ) ) . '</strong></div><div class="qldn-card' . ( $s->debt > 0 ? ' qldn-card-warn' : '' ) . '"><span>Còn phải trả</span><strong>' . esc_html( qldn_money( $s->debt ) ) . '</strong></div></div>';
		echo '<p>' . esc_html( trim( $s->phone . ' · ' . $s->address . ( $s->tax_code ? ' · MST ' . $s->tax_code : '' ) . ( $s->bank_account ? ' · TK ' . $s->bank_account : '' ), ' ·' ) ) . '</p>';
		if ( $s->debt > 0 && current_user_can( 'qldn_cash' ) ) {
			echo '<h2>Trả nợ</h2>';
			qldn_form_open( 'supplier_pay', array( 'class' => 'qldn-filter' ) );
			echo '<input type="hidden" name="id" value="' . esc_attr( $s->id ) . '"><input type="text" inputmode="numeric" class="qldn-money" name="amount" value="' . esc_attr( qldn_money( $s->debt, false ) ) . '"> <select name="fund">' . qldn_options_html( qldn_funds(), 'bank' ) . '</select> <input type="text" name="note" placeholder="Ghi chú"> <button class="button button-primary">Lập phiếu chi</button></form>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		$purch = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'purchases' ) . ' WHERE supplier_id = %d ORDER BY id DESC LIMIT 100', $s->id ) );
		$pays  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'cashbook' ) . " WHERE partner_type = 'supplier' AND partner_id = %d ORDER BY id DESC LIMIT 100", $s->id ) );
		echo '<div class="qldn-cols"><div class="qldn-col"><h2>Phiếu nhập</h2><table class="widefat striped qldn-table"><thead><tr><th>Ngày</th><th>Số phiếu</th><th class="num">Tiền hàng</th><th class="num">Trả ngay</th></tr></thead><tbody>';
		if ( ! $purch ) {
			echo '<tr><td colspan="4">Chưa có.</td></tr>';
		}
		foreach ( $purch as $p ) {
			echo '<tr><td>' . esc_html( qldn_date( $p->created_at ) ) . '</td><td><a href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'history', 'ref' => $p->code ) ) ) . '">' . esc_html( $p->code ) . '</a></td><td class="num">' . esc_html( qldn_money( $p->total, false ) ) . '</td><td class="num">' . esc_html( qldn_money( $p->paid, false ) ) . '</td></tr>';
		}
		echo '</tbody></table></div><div class="qldn-col"><h2>Đã trả</h2><table class="widefat striped qldn-table"><thead><tr><th>Ngày</th><th>Phiếu chi</th><th class="num">Số tiền</th><th>Diễn giải</th></tr></thead><tbody>';
		if ( ! $pays ) {
			echo '<tr><td colspan="4">Chưa có.</td></tr>';
		}
		foreach ( $pays as $p ) {
			echo '<tr><td>' . esc_html( qldn_date( $p->entry_date ) ) . '</td><td>' . esc_html( $p->code ) . '</td><td class="num">' . esc_html( qldn_money( $p->amount, false ) ) . '</td><td>' . esc_html( $p->note ) . '</td></tr>';
		}
		echo '</tbody></table></div></div></div>';
		return;
	}
	$s    = qldn_in( 's' );
	$rows = $wpdb->get_results( 'SELECT * FROM ' . qldn_t( 'suppliers' ) . ' WHERE 1=1' . qldn_like( $s, array( 'name', 'phone', 'code', 'tax_code' ) ) . ( 'debt' === qldn_in( 'filter', 'key' ) ? ' AND debt > 0' : '' ) . ' ORDER BY name LIMIT 500' );
	$debt = (int) $wpdb->get_var( 'SELECT COALESCE(SUM(debt),0) FROM ' . qldn_t( 'suppliers' ) . ' WHERE debt > 0' );
	echo '<h1 class="wp-heading-inline">Nhà cung cấp</h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'suppliers', array( 'edit' => 'new' ) ) ) . '">Thêm nhà cung cấp</a><hr class="wp-header-end">';
	qldn_print_notices();
	echo '<div class="qldn-cards"><a class="qldn-card' . ( $debt ? ' qldn-card-warn' : '' ) . '" href="' . esc_url( qldn_url( 'suppliers', array( 'filter' => 'debt' ) ) ) . '"><span>Tổng nợ phải trả nhà cung cấp</span><strong>' . esc_html( qldn_money( $debt ) ) . '</strong></a></div>';
	qldn_search_box( 'suppliers', $s, '', 'Tên / SĐT / MST' );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Mã</th><th>Nhà cung cấp</th><th>Điện thoại</th><th>MST</th><th class="num">Tổng mua</th><th class="num">Còn phải trả</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="6">Chưa có nhà cung cấp.</td></tr>';
	}
	foreach ( $rows as $r ) {
		echo '<tr><td>' . esc_html( $r->code ) . '</td><td><a href="' . esc_url( qldn_url( 'suppliers', array( 'view' => $r->id ) ) ) . '"><strong>' . esc_html( $r->name ) . '</strong></a></td><td>' . qldn_phone_link( $r->phone ) . '</td><td>' . esc_html( $r->tax_code ) . '</td><td class="num">' . esc_html( qldn_money( $r->total_purchase, false ) ) . '</td><td class="num">' . ( $r->debt > 0 ? qldn_badge( qldn_money( $r->debt, false ), 'orange' ) : '0' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table></div>';
}
