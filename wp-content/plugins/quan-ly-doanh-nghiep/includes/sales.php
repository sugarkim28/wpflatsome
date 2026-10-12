<?php
/**
 * Bán hàng: tạo đơn (trừ kho, cộng doanh số khách), danh sách đơn, in hoá đơn, huỷ đơn, thu nợ.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hình thức thanh toán.
 *
 * @return array
 */
function qldn_payments() {
	return array(
		'cash'     => 'Tiền mặt',
		'transfer' => 'Chuyển khoản',
		'card'     => 'Thẻ / ví điện tử',
	);
}

/**
 * Huy hiệu trạng thái đơn.
 *
 * @param string $status done|cancelled.
 * @return string HTML.
 */
function qldn_order_status_badge( $status ) {
	return 'cancelled' === $status ? qldn_badge( 'Đã huỷ', 'gray' ) : qldn_badge( 'Hoàn thành', 'green' );
}

/**
 * Tạo đơn bán hàng.
 *
 * @param array $d customer_id | (phone, customer_name), staff_id, lines [product_id, qty, price],
 *                 discount, paid (null = trả đủ), payment, note.
 * @return int|WP_Error ID đơn.
 */
function qldn_order_create( $d ) {
	global $wpdb;
	$d = wp_parse_args(
		$d,
		array(
			'customer_id'   => 0,
			'phone'         => '',
			'customer_name' => '',
			'staff_id'      => 0,
			'lines'         => array(),
			'discount'      => 0,
			'paid'          => null,
			'payment'       => 'cash',
			'note'          => '',
		)
	);

	// Gộp dòng trùng sản phẩm + kiểm tra.
	$lines = array();
	foreach ( (array) $d['lines'] as $l ) {
		$pid = isset( $l['product_id'] ) ? (int) $l['product_id'] : 0;
		$qty = isset( $l['qty'] ) ? round( (float) $l['qty'], 2 ) : 0;
		if ( ! $pid || $qty <= 0 ) {
			continue;
		}
		$p = qldn_get( 'products', $pid );
		if ( ! $p ) {
			return new WP_Error( 'qldn_product', 'Sản phẩm không tồn tại.' );
		}
		$price = isset( $l['price'] ) && '' !== $l['price'] && null !== $l['price'] ? max( 0, (int) $l['price'] ) : (int) $p->sale_price;
		$key   = $pid . ':' . $price;
		if ( isset( $lines[ $key ] ) ) {
			$lines[ $key ]['qty'] += $qty;
		} else {
			$lines[ $key ] = array( 'p' => $p, 'qty' => $qty, 'price' => $price );
		}
	}
	if ( ! $lines ) {
		return new WP_Error( 'qldn_empty', 'Đơn hàng chưa có sản phẩm.' );
	}

	$subtotal = 0;
	$cost     = 0;
	foreach ( $lines as $l ) {
		$subtotal += (int) round( $l['qty'] * $l['price'] );
		$cost     += (int) round( $l['qty'] * $l['p']->cost_price );
	}
	$discount = min( max( 0, (int) $d['discount'] ), $subtotal );
	$total    = $subtotal - $discount;
	$paid     = null === $d['paid'] ? $total : min( max( 0, (int) $d['paid'] ), $total );

	$cid = (int) $d['customer_id'];
	if ( ! $cid && '' !== trim( (string) $d['phone'] ) ) {
		$cid = qldn_customer_by_phone( $d['phone'], sanitize_text_field( $d['customer_name'] ) );
	}
	if ( $total > $paid && ! $cid ) {
		return new WP_Error( 'qldn_debt', 'Khách nợ tiền thì phải chọn khách hàng (hoặc nhập số điện thoại).' );
	}

	$payments = qldn_payments();
	$wpdb->insert(
		qldn_t( 'orders' ),
		array(
			'customer_id' => $cid,
			'staff_id'    => (int) $d['staff_id'],
			'subtotal'    => $subtotal,
			'discount'    => $discount,
			'total'       => $total,
			'cost_total'  => $cost,
			'paid'        => $paid,
			'payment'     => isset( $payments[ $d['payment'] ] ) ? $d['payment'] : 'cash',
			'status'      => 'done',
			'note'        => sanitize_textarea_field( $d['note'] ),
			'user_id'     => get_current_user_id(),
			'created_at'  => qldn_now(),
		)
	);
	$oid  = (int) $wpdb->insert_id;
	if ( ! $oid ) {
		return new WP_Error( 'qldn_db', 'Không lưu được đơn hàng.' );
	}
	$code = qldn_code( qldn_opt( 'order_prefix' ), $oid );
	$wpdb->update( qldn_t( 'orders' ), array( 'code' => $code ), array( 'id' => $oid ) );

	$moves = array();
	foreach ( $lines as $l ) {
		$m = qldn_stock_move( $l['p']->id, 'ban', -$l['qty'], array( 'ref' => $code, 'unit_cost' => (int) $l['p']->cost_price ) );
		if ( is_wp_error( $m ) ) {
			qldn_undo_moves( $moves );
			$wpdb->delete( qldn_t( 'order_items' ), array( 'order_id' => $oid ) );
			$wpdb->delete( qldn_t( 'orders' ), array( 'id' => $oid ) );
			return $m;
		}
		$moves[] = $m;
		$wpdb->insert(
			qldn_t( 'order_items' ),
			array(
				'order_id'   => $oid,
				'product_id' => $l['p']->id,
				'name'       => $l['p']->name,
				'qty'        => $l['qty'],
				'price'      => $l['price'],
				'cost'       => (int) $l['p']->cost_price,
				'line_total' => (int) round( $l['qty'] * $l['price'] ),
			)
		);
	}

	qldn_customer_add_sale( $cid, $total, $total - $paid, 1 );
	do_action( 'qldn_order_created', $oid );
	return $oid;
}

/**
 * Huỷ đơn: trả hàng về kho, trừ doanh số / công nợ của khách.
 *
 * @param int    $id     Đơn.
 * @param string $reason Lý do.
 * @return true|WP_Error
 */
function qldn_order_cancel( $id, $reason = '' ) {
	global $wpdb;
	$o = qldn_get( 'orders', $id );
	if ( ! $o || 'done' !== $o->status ) {
		return new WP_Error( 'qldn_order', 'Đơn không tồn tại hoặc đã huỷ.' );
	}
	// Đổi trạng thái có điều kiện: bấm huỷ 2 lần cũng chỉ trả hàng 1 lần.
	$ok = $wpdb->query( $wpdb->prepare( 'UPDATE ' . qldn_t( 'orders' ) . " SET status = 'cancelled', note = %s WHERE id = %d AND status = 'done'", trim( $o->note . "\nHuỷ: " . $reason ), $id ) );
	if ( ! $ok ) {
		return new WP_Error( 'qldn_order', 'Đơn đã được huỷ.' );
	}
	$items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'order_items' ) . ' WHERE order_id = %d', $id ) );
	foreach ( $items as $it ) {
		if ( qldn_get( 'products', $it->product_id ) ) {
			qldn_stock_move( $it->product_id, 'tra', (float) $it->qty, array( 'ref' => $o->code, 'unit_cost' => (int) $it->cost, 'note' => $reason, 'force' => true ) );
		}
	}
	qldn_customer_add_sale( (int) $o->customer_id, -(int) $o->total, -max( 0, $o->total - $o->paid ), -1 );
	do_action( 'qldn_order_cancelled', $id );
	return true;
}

/**
 * Thu thêm tiền của đơn còn nợ.
 *
 * @param int $id     Đơn.
 * @param int $amount Số tiền.
 * @return int|WP_Error Số tiền thực thu.
 */
function qldn_order_pay( $id, $amount ) {
	global $wpdb;
	$o = qldn_get( 'orders', $id );
	if ( ! $o || 'done' !== $o->status ) {
		return new WP_Error( 'qldn_order', 'Đơn không hợp lệ.' );
	}
	$amount = min( max( 0, (int) $amount ), $o->total - $o->paid );
	if ( $amount <= 0 ) {
		return new WP_Error( 'qldn_amount', 'Đơn đã thanh toán đủ.' );
	}
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . qldn_t( 'orders' ) . ' SET paid = paid + %d WHERE id = %d', $amount, $id ) );
	if ( $o->customer_id ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . qldn_t( 'customers' ) . ' SET debt = debt - %d WHERE id = %d', $amount, $o->customer_id ) );
	}
	return $amount;
}

/**
 * Xử lý form bán hàng.
 */
function qldn_handle_order() {
	qldn_check( 'order', 'qldn_sales' );
	$lines = qldn_posted_lines();
	$paid  = qldn_in( 'paid' );
	$r     = qldn_order_create(
		array(
			'customer_id'   => qldn_in( 'customer_id', 'int', 0 ),
			'phone'         => qldn_in( 'phone' ),
			'customer_name' => qldn_in( 'customer_name' ),
			'staff_id'      => qldn_in( 'staff_id', 'int', 0 ),
			'lines'         => $lines,
			'discount'      => qldn_in( 'discount', 'money', 0 ),
			'paid'          => '' === trim( $paid ) ? null : qldn_num( $paid ),
			'payment'       => qldn_in( 'payment', 'key', 'cash' ),
			'note'          => qldn_in( 'note', 'textarea' ),
		)
	);
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'pos' );
	}
	$o = qldn_get( 'orders', $r );
	qldn_notice( sprintf( 'Đã tạo đơn <strong>%s</strong> – %s. <a href="%s" target="_blank">In hoá đơn</a>', esc_html( $o->code ), esc_html( qldn_money( $o->total ) ), esc_url( qldn_action_url( 'print_order', array( 'id' => $r ) ) ) ) );
	qldn_go( 'pos', array( 'last' => $r ) );
}
add_action( 'admin_post_qldn_order', 'qldn_handle_order' );

/**
 * Xử lý huỷ đơn.
 */
function qldn_handle_cancel_order() {
	qldn_check( 'cancel_order', 'qldn_sales' );
	$id = qldn_in( 'id', 'int', 0 );
	$r  = qldn_order_cancel( $id, qldn_in( 'reason' ) );
	qldn_notice( is_wp_error( $r ) ? $r->get_error_message() : 'Đã huỷ đơn, hàng đã nhập lại kho.', is_wp_error( $r ) ? 'error' : 'success' );
	qldn_go( 'orders', array( 'view' => $id ) );
}
add_action( 'admin_post_qldn_cancel_order', 'qldn_handle_cancel_order' );

/**
 * Xử lý thu nợ.
 */
function qldn_handle_pay_order() {
	qldn_check( 'pay_order', 'qldn_sales' );
	$id = qldn_in( 'id', 'int', 0 );
	$r  = qldn_order_pay( $id, qldn_in( 'amount', 'money', 0 ) );
	qldn_notice( is_wp_error( $r ) ? $r->get_error_message() : 'Đã thu ' . qldn_money( $r ) . '.', is_wp_error( $r ) ? 'error' : 'success' );
	qldn_go( 'orders', array( 'view' => $id ) );
}
add_action( 'admin_post_qldn_pay_order', 'qldn_handle_pay_order' );

/**
 * In hoá đơn (khổ 80mm, cũng in được A4).
 */
function qldn_print_order() {
	qldn_check( 'print_order', 'qldn_sales' );
	global $wpdb;
	$o = qldn_get( 'orders', qldn_in( 'id', 'int', 0 ) );
	if ( ! $o ) {
		wp_die( 'Không tìm thấy đơn hàng.' );
	}
	$items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'order_items' ) . ' WHERE order_id = %d', $o->id ) );
	$c     = $o->customer_id ? qldn_get( 'customers', $o->customer_id ) : null;
	$s     = $o->staff_id ? qldn_get( 'employees', $o->staff_id ) : null;
	$opt   = qldn_opt();
	?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>Hoá đơn <?php echo esc_html( $o->code ); ?></title>
<style>
body{font-family:Arial,sans-serif;font-size:13px;color:#000;margin:0 auto;max-width:80mm;padding:8px}
h1{font-size:16px;text-align:center;margin:4px 0}.c{text-align:center}.r{text-align:right}
table{width:100%;border-collapse:collapse}td,th{padding:3px 0;vertical-align:top}thead th{border-bottom:1px dashed #000;text-align:left}
.sum td{border-top:1px dashed #000}.big{font-size:15px;font-weight:bold}.muted{color:#444;font-size:12px}
@media print{.noprint{display:none}}
</style></head><body onload="window.print()">
<div class="c"><strong><?php echo esc_html( $opt['company_name'] ); ?></strong><br>
<span class="muted"><?php echo esc_html( $opt['company_address'] ); ?><?php echo $opt['company_phone'] ? '<br>ĐT: ' . esc_html( $opt['company_phone'] ) : ''; ?></span></div>
<h1>HOÁ ĐƠN BÁN HÀNG</h1>
<div class="c muted">Số: <?php echo esc_html( $o->code ); ?> · <?php echo esc_html( qldn_date( $o->created_at, true ) ); ?></div>
<?php if ( 'cancelled' === $o->status ) : ?><p class="c big">ĐƠN ĐÃ HUỶ</p><?php endif; ?>
<p><?php echo $c ? 'Khách: ' . esc_html( $c->name ) . ( $c->phone ? ' – ' . esc_html( $c->phone ) : '' ) . '<br>' : ''; ?>
<?php echo $s ? 'Nhân viên: ' . esc_html( $s->name ) : ''; ?></p>
<table><thead><tr><th>Hàng</th><th class="r">SL</th><th class="r">Đ.giá</th><th class="r">T.tiền</th></tr></thead><tbody>
<?php foreach ( $items as $it ) : ?>
<tr><td colspan="4"><?php echo esc_html( $it->name ); ?></td></tr>
<tr><td></td><td class="r"><?php echo esc_html( qldn_qty( $it->qty ) ); ?></td><td class="r"><?php echo esc_html( qldn_money( $it->price, false ) ); ?></td><td class="r"><?php echo esc_html( qldn_money( $it->line_total, false ) ); ?></td></tr>
<?php endforeach; ?>
</tbody><tbody class="sum">
<tr><td colspan="3">Tiền hàng</td><td class="r"><?php echo esc_html( qldn_money( $o->subtotal, false ) ); ?></td></tr>
<?php if ( $o->discount ) : ?><tr><td colspan="3">Giảm giá</td><td class="r">-<?php echo esc_html( qldn_money( $o->discount, false ) ); ?></td></tr><?php endif; ?>
<tr class="big"><td colspan="3">TỔNG CỘNG</td><td class="r"><?php echo esc_html( qldn_money( $o->total, false ) ); ?></td></tr>
<tr><td colspan="3">Khách trả (<?php echo esc_html( qldn_payments()[ $o->payment ] ?? $o->payment ); ?>)</td><td class="r"><?php echo esc_html( qldn_money( $o->paid, false ) ); ?></td></tr>
<?php if ( $o->total > $o->paid ) : ?><tr><td colspan="3"><strong>Còn nợ</strong></td><td class="r"><strong><?php echo esc_html( qldn_money( $o->total - $o->paid, false ) ); ?></strong></td></tr><?php endif; ?>
</tbody></table>
<?php if ( $c && $c->points ) : ?><p class="muted">Điểm tích luỹ: <?php echo esc_html( $c->points ); ?></p><?php endif; ?>
<p class="c"><?php echo esc_html( $opt['invoice_footer'] ); ?></p>
<p class="c noprint"><button onclick="window.print()">In lại</button></p>
</body></html>
	<?php
	exit;
}
add_action( 'admin_post_qldn_print_order', 'qldn_print_order' );

/**
 * Xuất CSV đơn hàng theo bộ lọc.
 */
function qldn_export_orders() {
	qldn_check( 'export_orders', 'qldn_sales' );
	global $wpdb;
	$sql  = 'SELECT o.*, c.name AS cname, c.phone AS cphone, e.name AS ename FROM ' . qldn_t( 'orders' ) . ' o LEFT JOIN ' . qldn_t( 'customers' ) . ' c ON c.id = o.customer_id LEFT JOIN ' . qldn_t( 'employees' ) . ' e ON e.id = o.staff_id WHERE 1=1' . qldn_orders_where() . ' ORDER BY o.id DESC';
	$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL
	$pay  = qldn_payments();
	$out  = array();
	foreach ( $rows as $r ) {
		$out[] = array( $r->code, qldn_date( $r->created_at, true ), $r->cname, $r->cphone, $r->ename, $r->subtotal, $r->discount, $r->total, $r->paid, max( 0, $r->total - $r->paid ), $pay[ $r->payment ] ?? $r->payment, 'cancelled' === $r->status ? 'Đã huỷ' : 'Hoàn thành', $r->note );
	}
	qldn_csv( 'don-hang-' . current_time( 'Y-m-d' ) . '.csv', array( 'Mã đơn', 'Thời gian', 'Khách hàng', 'Điện thoại', 'Nhân viên bán', 'Tiền hàng', 'Giảm giá', 'Tổng tiền', 'Đã trả', 'Còn nợ', 'Thanh toán', 'Trạng thái', 'Ghi chú' ), $out );
}
add_action( 'admin_post_qldn_export_orders', 'qldn_export_orders' );

/**
 * Điều kiện lọc đơn từ GET.
 *
 * @return string SQL.
 */
function qldn_orders_where() {
	global $wpdb;
	$w      = '';
	$s      = qldn_in( 's' );
	$from   = qldn_in( 'from', 'date' );
	$to     = qldn_in( 'to', 'date' );
	$status = qldn_in( 'status', 'key' );
	$staff  = qldn_in( 'staff', 'int', 0 );
	if ( '' !== $s ) {
		$w .= qldn_like( $s, array( 'o.code', 'c.name', 'c.phone' ) );
	}
	if ( $from ) {
		$w .= $wpdb->prepare( ' AND o.created_at >= %s', $from . ' 00:00:00' );
	}
	if ( $to ) {
		$w .= $wpdb->prepare( ' AND o.created_at <= %s', $to . ' 23:59:59' );
	}
	if ( in_array( $status, array( 'done', 'cancelled' ), true ) ) {
		$w .= $wpdb->prepare( ' AND o.status = %s', $status );
	} elseif ( 'debt' === $status ) {
		$w .= " AND o.status = 'done' AND o.paid < o.total";
	}
	if ( $staff ) {
		$w .= $wpdb->prepare( ' AND o.staff_id = %d', $staff );
	}
	return $w;
}

/**
 * Trang Bán hàng (tạo đơn).
 */
function qldn_page_pos() {
	$me   = qldn_current_employee();
	$cust = qldn_in( 'customer', 'int', 0 ) ? qldn_get( 'customers', qldn_in( 'customer', 'int', 0 ) ) : null;
	echo '<div class="wrap qldn"><h1>Bán hàng</h1>';
	qldn_print_notices();
	qldn_form_open( 'order', array( 'class' => 'qldn-lines-form qldn-pos' ) );
	echo '<div class="qldn-pos-grid"><div class="qldn-pos-main">';
	echo '<div class="qldn-picker"><input type="search" class="large-text qldn-product-search" placeholder="Quét mã vạch hoặc gõ mã / tên hàng… (Enter để thêm)" autocomplete="off" autofocus><div class="qldn-results"></div></div>';
	echo '<table class="widefat qldn-lines" data-mode="sale"><thead><tr><th>Hàng hoá</th><th class="num">Tồn</th><th class="num">Số lượng</th><th class="num">Đơn giá</th><th class="num">Thành tiền</th><th></th></tr></thead><tbody></tbody></table>';
	echo '<p><textarea name="note" rows="2" class="large-text" placeholder="Ghi chú đơn hàng"></textarea></p>';
	echo '</div><div class="qldn-pos-side">';

	echo '<h3>Khách hàng</h3><div class="qldn-customer-pick">';
	echo '<input type="hidden" name="customer_id" value="' . esc_attr( $cust ? $cust->id : 0 ) . '">';
	echo '<div class="qldn-picker"><input type="search" class="widefat qldn-customer-search" placeholder="Tìm tên / SĐT khách" autocomplete="off"' . ( $cust ? ' style="display:none"' : '' ) . '><div class="qldn-results"></div></div>';
	echo '<div class="qldn-customer-chosen"' . ( $cust ? '' : ' style="display:none"' ) . '><strong>' . esc_html( $cust ? $cust->name : '' ) . '</strong> <span>' . esc_html( $cust ? $cust->phone : '' ) . '</span> <a href="#" class="qldn-customer-clear">Đổi</a></div>';
	echo '<div class="qldn-customer-new"' . ( $cust ? ' style="display:none"' : '' ) . '><p class="description">Khách mới: nhập SĐT (và tên) để tự lưu khách hàng. Bỏ trống = khách lẻ.</p><input type="tel" name="phone" class="widefat" placeholder="Số điện thoại"><input type="text" name="customer_name" class="widefat" placeholder="Tên khách"></div>';
	echo '</div>';

	echo '<h3>Thanh toán</h3><table class="qldn-pay"><tbody>';
	echo '<tr><th>Tiền hàng</th><td class="num qldn-subtotal">0</td></tr>';
	echo '<tr><th>Giảm giá</th><td class="num"><input type="text" inputmode="numeric" name="discount" class="qldn-money qldn-discount" value="0"></td></tr>';
	echo '<tr class="qldn-total-row"><th>Khách cần trả</th><td class="num qldn-total">0</td></tr>';
	echo '<tr><th>Khách trả</th><td class="num"><input type="text" inputmode="numeric" name="paid" class="qldn-money qldn-paid" placeholder="= khách cần trả"></td></tr>';
	echo '<tr><th class="qldn-change-label">Tiền thừa</th><td class="num qldn-change">0</td></tr>';
	echo '</tbody></table>';
	echo '<p><select name="payment" class="widefat">';
	qldn_options( qldn_payments(), 'cash' );
	echo '</select></p><p><label>Nhân viên bán<br><select name="staff_id" class="widefat"><option value="0">—</option>';
	qldn_options( qldn_employee_options(), $me ? $me->id : 0 );
	echo '</select></label></p>';
	echo '<p class="description">Khách trả ít hơn tổng tiền: phần còn lại ghi công nợ của khách. Khách trả nhiều hơn: hiện tiền thừa trả lại.</p>';
	echo '<p><button type="submit" class="button button-primary button-hero qldn-submit">Thanh toán (F9)</button></p>';
	$last = qldn_in( 'last', 'int', 0 );
	if ( $last ) {
		echo '<p><a class="button" target="_blank" href="' . esc_url( qldn_action_url( 'print_order', array( 'id' => $last ) ) ) . '">In hoá đơn vừa tạo</a></p>';
	}
	echo '</div></div></form></div>';
}

/**
 * Trang danh sách đơn hàng / chi tiết đơn.
 */
function qldn_page_orders() {
	global $wpdb;
	$view = qldn_in( 'view', 'int', 0 );
	if ( $view ) {
		qldn_order_view( qldn_get( 'orders', $view ) );
		return;
	}
	$from  = qldn_t( 'orders' ) . ' o LEFT JOIN ' . qldn_t( 'customers' ) . ' c ON c.id = o.customer_id LEFT JOIN ' . qldn_t( 'employees' ) . ' e ON e.id = o.staff_id';
	$where = qldn_orders_where();
	$per   = 50;
	list( $paged, $off ) = qldn_paged( $per );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $from WHERE 1=1 $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$sum   = $wpdb->get_row( "SELECT SUM(o.total) AS total, SUM(o.total - o.paid) AS debt FROM $from WHERE o.status = 'done' $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT o.*, c.name AS cname, c.phone AS cphone, e.name AS ename FROM $from WHERE 1=1 $where ORDER BY o.id DESC LIMIT %d OFFSET %d", $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL

	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Đơn hàng</h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'pos' ) ) . '">Tạo đơn</a>';
	$filters = array_filter( array( 's' => qldn_in( 's' ), 'from' => qldn_in( 'from', 'date' ), 'to' => qldn_in( 'to', 'date' ), 'status' => qldn_in( 'status', 'key' ), 'staff' => qldn_in( 'staff', 'int', 0 ) ) );
	echo ' <a class="page-title-action" href="' . esc_url( qldn_action_url( 'export_orders', $filters ) ) . '">Xuất Excel (CSV)</a><hr class="wp-header-end">';
	qldn_print_notices();
	$extra  = 'Từ <input type="date" name="from" value="' . esc_attr( qldn_in( 'from', 'date' ) ) . '"> đến <input type="date" name="to" value="' . esc_attr( qldn_in( 'to', 'date' ) ) . '"> ';
	$extra .= '<select name="status">' . qldn_options_html( array( '' => 'Mọi trạng thái', 'done' => 'Hoàn thành', 'debt' => 'Còn nợ', 'cancelled' => 'Đã huỷ' ), qldn_in( 'status', 'key' ) ) . '</select> ';
	$extra .= '<select name="staff"><option value="">Mọi nhân viên</option>' . qldn_options_html( qldn_employee_options( true ), qldn_in( 'staff', 'int', 0 ) ) . '</select> ';
	qldn_search_box( 'orders', qldn_in( 's' ), $extra, 'Mã đơn / khách / SĐT' );
	echo '<div class="qldn-cards"><div class="qldn-card"><span>Doanh thu (đơn hoàn thành theo bộ lọc)</span><strong>' . esc_html( qldn_money( $sum ? $sum->total : 0 ) ) . '</strong></div><div class="qldn-card"><span>Khách còn nợ</span><strong>' . esc_html( qldn_money( $sum ? $sum->debt : 0 ) ) . '</strong></div></div>';
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Mã đơn</th><th>Thời gian</th><th>Khách hàng</th><th>Nhân viên</th><th class="num">Tổng tiền</th><th class="num">Còn nợ</th><th>Thanh toán</th><th>Trạng thái</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="8">Chưa có đơn hàng.</td></tr>';
	}
	$pay = qldn_payments();
	foreach ( $rows as $r ) {
		$debt = 'done' === $r->status ? max( 0, $r->total - $r->paid ) : 0;
		echo '<tr><td><a href="' . esc_url( qldn_url( 'orders', array( 'view' => $r->id ) ) ) . '"><strong>' . esc_html( $r->code ) . '</strong></a></td><td>' . esc_html( qldn_date( $r->created_at, true ) ) . '</td><td>' . esc_html( $r->cname ? $r->cname : 'Khách lẻ' ) . '</td><td>' . esc_html( $r->ename ) . '</td>';
		echo '<td class="num">' . esc_html( qldn_money( $r->total, false ) ) . '</td><td class="num">' . ( $debt ? qldn_badge( qldn_money( $debt, false ), 'red' ) : '' ) . '</td><td>' . esc_html( $pay[ $r->payment ] ?? $r->payment ) . '</td><td>' . qldn_order_status_badge( $r->status ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
	echo '</div>';
}

/**
 * Chi tiết đơn.
 *
 * @param object|null $o Đơn.
 */
function qldn_order_view( $o ) {
	global $wpdb;
	if ( ! $o ) {
		echo '<div class="wrap qldn"><p>Không tìm thấy đơn hàng.</p></div>';
		return;
	}
	$items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'order_items' ) . ' WHERE order_id = %d', $o->id ) );
	$c     = $o->customer_id ? qldn_get( 'customers', $o->customer_id ) : null;
	$s     = $o->staff_id ? qldn_get( 'employees', $o->staff_id ) : null;
	$u     = $o->user_id ? get_userdata( $o->user_id ) : null;
	$debt  = 'done' === $o->status ? max( 0, $o->total - $o->paid ) : 0;
	$see_cost = current_user_can( 'qldn_reports' );

	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Đơn ' . esc_html( $o->code ) . '</h1> ' . qldn_order_status_badge( $o->status ) . ' <a class="page-title-action" target="_blank" href="' . esc_url( qldn_action_url( 'print_order', array( 'id' => $o->id ) ) ) . '">In hoá đơn</a><hr class="wp-header-end">'; // phpcs:ignore WordPress.Security.EscapeOutput
	qldn_print_notices();
	echo '<p>' . esc_html( qldn_date( $o->created_at, true ) ) . ' · Khách: ' . ( $c ? '<a href="' . esc_url( qldn_url( 'customers', array( 'view' => $c->id ) ) ) . '">' . esc_html( $c->name ) . '</a> ' . esc_html( $c->phone ) : 'Khách lẻ' ) . ' · Nhân viên bán: ' . esc_html( $s ? $s->name : '—' ) . ' · Người tạo: ' . esc_html( $u ? $u->display_name : '—' ) . '</p>';
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Hàng hoá</th><th class="num">Số lượng</th><th class="num">Đơn giá</th><th class="num">Thành tiền</th>' . ( $see_cost ? '<th class="num">Giá vốn</th>' : '' ) . '</tr></thead><tbody>';
	foreach ( $items as $it ) {
		echo '<tr><td>' . esc_html( $it->name ) . '</td><td class="num">' . esc_html( qldn_qty( $it->qty ) ) . '</td><td class="num">' . esc_html( qldn_money( $it->price, false ) ) . '</td><td class="num">' . esc_html( qldn_money( $it->line_total, false ) ) . '</td>' . ( $see_cost ? '<td class="num">' . esc_html( qldn_money( $it->cost * $it->qty, false ) ) . '</td>' : '' ) . '</tr>';
	}
	echo '</tbody><tfoot>';
	echo '<tr><th colspan="3" class="num">Tiền hàng</th><th class="num">' . esc_html( qldn_money( $o->subtotal, false ) ) . '</th>' . ( $see_cost ? '<th class="num">' . esc_html( qldn_money( $o->cost_total, false ) ) . '</th>' : '' ) . '</tr>';
	echo '<tr><th colspan="3" class="num">Giảm giá</th><th class="num">' . esc_html( qldn_money( $o->discount, false ) ) . '</th>' . ( $see_cost ? '<th></th>' : '' ) . '</tr>';
	echo '<tr><th colspan="3" class="num">Tổng cộng</th><th class="num"><strong>' . esc_html( qldn_money( $o->total, false ) ) . '</strong></th>' . ( $see_cost ? '<th class="num">Lãi gộp: ' . esc_html( qldn_money( $o->total - $o->cost_total, false ) ) . '</th>' : '' ) . '</tr>';
	echo '<tr><th colspan="3" class="num">Đã trả (' . esc_html( qldn_payments()[ $o->payment ] ?? $o->payment ) . ')</th><th class="num">' . esc_html( qldn_money( $o->paid, false ) ) . '</th>' . ( $see_cost ? '<th></th>' : '' ) . '</tr>';
	echo '</tfoot></table>';
	if ( $o->note ) {
		echo '<p><strong>Ghi chú:</strong> ' . nl2br( esc_html( $o->note ) ) . '</p>';
	}
	if ( 'done' === $o->status ) {
		echo '<div class="qldn-cols">';
		if ( $debt ) {
			echo '<div class="qldn-col"><h2>Thu nợ – còn ' . esc_html( qldn_money( $debt ) ) . '</h2>';
			qldn_form_open( 'pay_order' );
			echo '<input type="hidden" name="id" value="' . esc_attr( $o->id ) . '"><input type="text" inputmode="numeric" class="qldn-money" name="amount" value="' . esc_attr( qldn_money( $debt, false ) ) . '"> <button class="button button-primary">Thu tiền</button></form></div>';
		}
		echo '<div class="qldn-col"><h2>Huỷ đơn</h2><p class="description">Hàng được nhập lại kho; doanh số, điểm và công nợ của khách được trừ lại.</p>';
		qldn_form_open( 'cancel_order', array( 'onsubmit' => "return confirm('Huỷ đơn " . esc_js( $o->code ) . "?')" ) );
		echo '<input type="hidden" name="id" value="' . esc_attr( $o->id ) . '"><input type="text" name="reason" class="regular-text" placeholder="Lý do huỷ / trả hàng" required> <button class="button">Huỷ đơn</button></form></div>';
		echo '</div>';
	}
	echo '</div>';
}
