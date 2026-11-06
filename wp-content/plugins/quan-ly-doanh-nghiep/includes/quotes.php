<?php
/**
 * Báo giá: hàng hoá trong kho hoặc dịch vụ tự nhập, giảm giá, thuế GTGT, hiệu lực;
 * in / tải Word, chuyển thành đơn bán hàng hoặc hợp đồng.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trạng thái báo giá.
 *
 * @return array
 */
function qldn_quote_statuses() {
	return array(
		'draft'    => 'Nháp',
		'sent'     => 'Đã gửi khách',
		'accepted' => 'Khách đồng ý',
		'rejected' => 'Khách từ chối',
	);
}

/**
 * Huy hiệu trạng thái (báo giá đã gửi quá hạn hiện "Hết hiệu lực").
 *
 * @param object $q Báo giá.
 * @return string HTML.
 */
function qldn_quote_badge( $q ) {
	$colors = array( 'draft' => 'gray', 'sent' => 'blue', 'accepted' => 'green', 'rejected' => 'red' );
	if ( in_array( $q->status, array( 'draft', 'sent' ), true ) && $q->valid_until && $q->valid_until < qldn_today() ) {
		return qldn_badge( 'Hết hiệu lực', 'orange' );
	}
	return qldn_badge( qldn_quote_statuses()[ $q->status ] ?? $q->status, $colors[ $q->status ] ?? 'gray' );
}

/**
 * Lưu báo giá.
 *
 * @param array $d  customer_id | phone + customer_name, title, lines [product_id, name, unit, qty, price],
 *                  discount, vat_rate, valid_until, terms, note, status, staff_id.
 * @param int   $id ID khi sửa.
 * @return int|WP_Error
 */
function qldn_quote_save( $d, $id = 0 ) {
	global $wpdb;
	$t     = qldn_t( 'quotes' );
	$lines = array();
	foreach ( (array) ( $d['lines'] ?? array() ) as $l ) {
		$pid  = isset( $l['product_id'] ) ? (int) $l['product_id'] : 0;
		$p    = $pid ? qldn_get( 'products', $pid ) : null;
		$name = isset( $l['name'] ) ? sanitize_text_field( $l['name'] ) : '';
		$name = '' === $name && $p ? $p->name : $name;
		$qty  = isset( $l['qty'] ) ? round( (float) $l['qty'], 2 ) : 0;
		if ( '' === $name || $qty <= 0 ) {
			continue;
		}
		$price   = isset( $l['price'] ) ? max( 0, (int) $l['price'] ) : ( $p ? (int) $p->sale_price : 0 );
		$lines[] = array(
			'product_id' => $p ? $p->id : 0,
			'name'       => $name,
			'unit'       => isset( $l['unit'] ) && '' !== $l['unit'] ? sanitize_text_field( $l['unit'] ) : ( $p ? $p->unit : '' ),
			'qty'        => $qty,
			'price'      => $price,
			'line_total' => (int) round( $qty * $price ),
		);
	}
	if ( ! $lines ) {
		return new WP_Error( 'qldn_empty', 'Báo giá chưa có dòng hàng hoá / dịch vụ nào.' );
	}
	$cid = (int) ( $d['customer_id'] ?? 0 );
	if ( ! $cid && ! empty( $d['phone'] ) ) {
		$cid = qldn_customer_by_phone( $d['phone'], sanitize_text_field( $d['customer_name'] ?? '' ) );
	}
	if ( ! $cid || ! qldn_get( 'customers', $cid ) ) {
		return new WP_Error( 'qldn_customer', 'Chọn khách hàng nhận báo giá (hoặc nhập số điện thoại khách mới).' );
	}
	$subtotal = array_sum( array_column( $lines, 'line_total' ) );
	$discount = min( max( 0, (int) ( $d['discount'] ?? 0 ) ), $subtotal );
	$rate     = min( 100, max( 0, (float) ( $d['vat_rate'] ?? 0 ) ) );
	$vat      = (int) round( ( $subtotal - $discount ) * $rate / 100 );
	$statuses = qldn_quote_statuses();
	$valid    = $d['valid_until'] ?? '';
	$row      = array(
		'customer_id' => $cid,
		'staff_id'    => (int) ( $d['staff_id'] ?? 0 ),
		'title'       => sanitize_text_field( $d['title'] ?? '' ),
		'subtotal'    => $subtotal,
		'discount'    => $discount,
		'vat_rate'    => $rate,
		'vat'         => $vat,
		'total'       => $subtotal - $discount + $vat,
		'valid_until' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $valid ) ? $valid : gmdate( 'Y-m-d', strtotime( qldn_today() . ' +' . (int) qldn_opt( 'quote_valid_days' ) . ' days' ) ),
		'status'      => isset( $statuses[ $d['status'] ?? '' ] ) ? $d['status'] : 'draft',
		'terms'       => sanitize_textarea_field( $d['terms'] ?? '' ),
		'note'        => sanitize_textarea_field( $d['note'] ?? '' ),
	);
	if ( '' === $row['title'] ) {
		$row['title'] = 'Báo giá ' . $lines[0]['name'] . ( count( $lines ) > 1 ? ' và ' . ( count( $lines ) - 1 ) . ' mục khác' : '' );
	}
	if ( $id ) {
		$wpdb->update( $t, $row, array( 'id' => $id ) );
		$wpdb->delete( qldn_t( 'quote_items' ), array( 'quote_id' => $id ) );
	} else {
		$row['user_id']    = get_current_user_id();
		$row['created_at'] = qldn_now();
		$wpdb->insert( $t, $row );
		$id = (int) $wpdb->insert_id;
		$wpdb->update( $t, array( 'code' => qldn_code( 'BG', $id ) ), array( 'id' => $id ) );
	}
	foreach ( $lines as $l ) {
		$wpdb->insert( qldn_t( 'quote_items' ), array_merge( $l, array( 'quote_id' => $id ) ) );
	}
	return $id;
}

/**
 * Dòng hàng của báo giá.
 *
 * @param int $id Báo giá.
 * @return array
 */
function qldn_quote_items( $id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'quote_items' ) . ' WHERE quote_id = %d ORDER BY id', $id ) );
}

/**
 * Bảng hàng hoá / dịch vụ (dùng trong văn bản: báo giá, hợp đồng mua bán).
 *
 * @param object $q Báo giá.
 * @return string HTML.
 */
function qldn_quote_table( $q ) {
	$h = '<table class="qldn-items" style="width:100%;border-collapse:collapse" border="1" cellpadding="4"><thead><tr><th>STT</th><th>Tên hàng hoá / dịch vụ</th><th>ĐVT</th><th>Số lượng</th><th>Đơn giá (đồng)</th><th>Thành tiền (đồng)</th></tr></thead><tbody>';
	foreach ( qldn_quote_items( $q->id ) as $i => $it ) {
		$h .= '<tr><td class="c" style="text-align:center">' . ( $i + 1 ) . '</td><td>' . esc_html( $it->name ) . '</td><td class="c" style="text-align:center">' . esc_html( $it->unit ) . '</td><td class="r" style="text-align:right">' . esc_html( qldn_qty( $it->qty ) ) . '</td><td class="r" style="text-align:right">' . esc_html( qldn_money( $it->price, false ) ) . '</td><td class="r" style="text-align:right">' . esc_html( qldn_money( $it->line_total, false ) ) . '</td></tr>';
	}
	$sum = array( array( 'Cộng tiền hàng', $q->subtotal ) );
	if ( $q->discount ) {
		$sum[] = array( 'Giảm giá', -$q->discount );
	}
	if ( (float) $q->vat_rate > 0 ) {
		$sum[] = array( 'Thuế GTGT ' . qldn_qty( $q->vat_rate ) . '%', $q->vat );
	}
	$sum[] = array( '<strong>Tổng cộng thanh toán</strong>', $q->total );
	foreach ( $sum as $s ) {
		$h .= '<tr><td colspan="5" class="r" style="text-align:right">' . $s[0] . '</td><td class="r" style="text-align:right"><strong>' . esc_html( qldn_money( $s[1], false ) ) . '</strong></td></tr>';
	}
	$h .= '</tbody></table><p><em>Bằng chữ: ' . esc_html( qldn_money_words( $q->total ) ) . '.</em></p>';
	return $h;
}

/**
 * Chuyển báo giá thành đơn bán hàng (trừ kho, ghi công nợ khách).
 *
 * @param int $id Báo giá.
 * @return int|WP_Error ID đơn.
 */
function qldn_quote_to_order( $id ) {
	global $wpdb;
	$q = qldn_get( 'quotes', $id );
	if ( ! $q ) {
		return new WP_Error( 'qldn_quote', 'Không tìm thấy báo giá.' );
	}
	if ( $q->order_id ) {
		return new WP_Error( 'qldn_quote', 'Báo giá đã chuyển thành đơn hàng trước đó.' );
	}
	$lines = array();
	$free  = array();
	foreach ( qldn_quote_items( $id ) as $it ) {
		if ( $it->product_id && qldn_get( 'products', $it->product_id ) ) {
			$lines[] = array( 'product_id' => $it->product_id, 'qty' => $it->qty, 'price' => $it->price );
		} else {
			$free[] = $it->name;
		}
	}
	if ( $free ) {
		return new WP_Error( 'qldn_quote', 'Các dòng không phải hàng trong kho (' . implode( ', ', $free ) . ') không tạo được đơn bán hàng – hãy lập hợp đồng dịch vụ.' );
	}
	$order = qldn_order_create(
		array(
			'customer_id' => $q->customer_id,
			'staff_id'    => $q->staff_id,
			'lines'       => $lines,
			'discount'    => $q->discount,
			'paid'        => 0,
			'payment'     => 'transfer',
			'note'        => 'Từ báo giá ' . $q->code,
		)
	);
	if ( ! is_wp_error( $order ) ) {
		$wpdb->update( qldn_t( 'quotes' ), array( 'order_id' => $order, 'status' => 'accepted' ), array( 'id' => $id ) );
	}
	return $order;
}

/**
 * Xử lý form báo giá.
 */
function qldn_handle_quote() {
	qldn_check( 'quote', 'qldn_docs' );
	$id    = qldn_in( 'id', 'int', 0 );
	$lines = array();
	foreach ( qldn_in( 'items', 'array', array() ) as $l ) {
		if ( is_array( $l ) ) {
			$lines[] = array(
				'product_id' => (int) ( $l['product_id'] ?? 0 ),
				'name'       => (string) ( $l['name'] ?? '' ),
				'unit'       => (string) ( $l['unit'] ?? '' ),
				'qty'        => qldn_num( $l['qty'] ?? 0, true ),
				'price'      => qldn_num( $l['price'] ?? 0 ),
			);
		}
	}
	$r = qldn_quote_save(
		array(
			'customer_id'   => qldn_in( 'customer_id', 'int', 0 ),
			'phone'         => qldn_in( 'phone' ),
			'customer_name' => qldn_in( 'customer_name' ),
			'staff_id'      => qldn_in( 'staff_id', 'int', 0 ),
			'title'         => qldn_in( 'title' ),
			'lines'         => $lines,
			'discount'      => qldn_in( 'discount', 'money', 0 ),
			'vat_rate'      => qldn_in( 'vat_rate', 'qty', 0 ),
			'valid_until'   => qldn_in( 'valid_until', 'date' ),
			'terms'         => qldn_in( 'terms', 'textarea' ),
			'note'          => qldn_in( 'note', 'textarea' ),
			'status'        => qldn_in( 'status', 'key', 'draft' ),
		),
		$id
	);
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'quotes', array( 'edit' => $id ? $id : 'new' ) );
	}
	qldn_notice( $id ? 'Đã cập nhật báo giá.' : 'Đã lập báo giá.' );
	qldn_go( 'quotes', array( 'view' => $r ) );
}
add_action( 'admin_post_qldn_quote', 'qldn_handle_quote' );

/**
 * Thao tác: đổi trạng thái, chuyển đơn hàng, xoá.
 */
function qldn_handle_quote_action() {
	qldn_check( 'quote_action', 'qldn_docs' );
	global $wpdb;
	$id = qldn_in( 'id', 'int', 0 );
	$do = qldn_in( 'do', 'key' );
	if ( isset( qldn_quote_statuses()[ $do ] ) ) {
		$wpdb->update( qldn_t( 'quotes' ), array( 'status' => $do ), array( 'id' => $id ) );
		qldn_notice( 'Đã chuyển trạng thái: ' . qldn_quote_statuses()[ $do ] . '.' );
	} elseif ( 'order' === $do ) {
		if ( ! current_user_can( 'qldn_sales' ) ) {
			wp_die( 'Cần quyền bán hàng.', 403 );
		}
		$r = qldn_quote_to_order( $id );
		if ( is_wp_error( $r ) ) {
			qldn_notice( $r->get_error_message(), 'error' );
		} else {
			$q = qldn_get( 'quotes', $id );
			qldn_notice( 'Đã tạo đơn hàng, hàng đã trừ kho, số tiền ghi công nợ khách.' . ( $q->vat ? ' Lưu ý: đơn bán hàng không gồm thuế GTGT ' . esc_html( qldn_money( $q->vat ) ) . ' của báo giá.' : '' ) );
			qldn_go( 'orders', array( 'view' => $r ) );
		}
	} elseif ( 'delete' === $do ) {
		$wpdb->delete( qldn_t( 'quote_items' ), array( 'quote_id' => $id ) );
		$wpdb->delete( qldn_t( 'quotes' ), array( 'id' => $id ) );
		qldn_notice( 'Đã xoá báo giá.' );
		qldn_go( 'quotes' );
	}
	qldn_go( 'quotes', array( 'view' => $id ) );
}
add_action( 'admin_post_qldn_quote_action', 'qldn_handle_quote_action' );

/**
 * In nhanh / tải Word báo giá theo mẫu báo giá đầu tiên (không cần lưu văn bản).
 */
function qldn_handle_quote_output() {
	qldn_check( 'quote_output', 'qldn_docs' );
	$q    = qldn_get( 'quotes', qldn_in( 'id', 'int', 0 ) );
	$tpls = qldn_templates_for( 'quote' );
	if ( ! $q || ! $tpls ) {
		wp_die( 'Không có báo giá hoặc chưa có mẫu báo giá (Soạn hồ sơ → Mẫu văn bản).' );
	}
	qldn_doc_output( 'Bao gia ' . $q->code, wp_kses_post( qldn_merge( qldn_get( 'templates', $tpls[0]->id )->content, qldn_merge_values( 'quote', $q->id ) ) ), qldn_in( 'format', 'key' ) );
}
add_action( 'admin_post_qldn_quote_output', 'qldn_handle_quote_output' );

/**
 * Trang Báo giá.
 */
function qldn_page_quotes() {
	global $wpdb;
	$edit = qldn_in( 'edit' );
	$view = qldn_in( 'view', 'int', 0 );
	if ( '' !== $edit ) {
		qldn_quote_form( 'new' === $edit ? null : qldn_get( 'quotes', (int) $edit ) );
		return;
	}
	if ( $view ) {
		qldn_quote_view( qldn_get( 'quotes', $view ) );
		return;
	}
	$from   = qldn_t( 'quotes' ) . ' q LEFT JOIN ' . qldn_t( 'customers' ) . ' c ON c.id = q.customer_id';
	$status = qldn_in( 'status', 'key' );
	$where  = qldn_like( qldn_in( 's' ), array( 'q.code', 'q.title', 'c.name', 'c.phone' ) );
	if ( 'expired' === $status ) {
		$where .= $wpdb->prepare( " AND q.status IN ('draft','sent') AND q.valid_until < %s", qldn_today() );
	} elseif ( isset( qldn_quote_statuses()[ $status ] ) ) {
		$where .= $wpdb->prepare( ' AND q.status = %s', $status );
	}
	$per             = 50;
	list( $paged, $off ) = qldn_paged( $per );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $from WHERE 1=1 $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT q.*, c.name AS cname FROM $from WHERE 1=1 $where ORDER BY q.id DESC LIMIT %d OFFSET %d", $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$stat  = $wpdb->get_row( $wpdb->prepare( 'SELECT SUM(CASE WHEN status IN (\'draft\',\'sent\') AND valid_until >= %s THEN total ELSE 0 END) AS open_total, SUM(CASE WHEN status = \'accepted\' THEN 1 ELSE 0 END) AS won, COUNT(*) AS n FROM ' . qldn_t( 'quotes' ) . ' WHERE created_at >= %s', qldn_today(), current_time( 'Y-m-01' ) . ' 00:00:00' ) );

	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Báo giá</h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'quotes', array( 'edit' => 'new' ) ) ) . '">Lập báo giá</a><hr class="wp-header-end">';
	qldn_print_notices();
	echo '<div class="qldn-cards"><div class="qldn-card"><span>Báo giá tháng này</span><strong>' . esc_html( (int) $stat->n ) . '</strong><em>' . esc_html( (int) $stat->won ) . ' được khách đồng ý</em></div><div class="qldn-card"><span>Đang chờ khách (còn hiệu lực)</span><strong>' . esc_html( qldn_money( $stat->open_total ) ) . '</strong></div></div>';
	qldn_search_box( 'quotes', qldn_in( 's' ), '<select name="status"><option value="">Mọi trạng thái</option>' . qldn_options_html( qldn_quote_statuses() + array( 'expired' => 'Hết hiệu lực' ), $status ) . '</select> ', 'Số / tiêu đề / khách hàng' );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Số</th><th>Tiêu đề</th><th>Khách hàng</th><th>Ngày lập</th><th>Hiệu lực đến</th><th class="num">Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="7">Chưa có báo giá.</td></tr>';
	}
	foreach ( $rows as $r ) {
		echo '<tr><td><a href="' . esc_url( qldn_url( 'quotes', array( 'view' => $r->id ) ) ) . '"><strong>' . esc_html( $r->code ) . '</strong></a></td><td>' . esc_html( $r->title ) . '</td><td>' . esc_html( $r->cname ) . '</td><td>' . esc_html( qldn_date( $r->created_at ) ) . '</td><td>' . esc_html( qldn_date( $r->valid_until ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->total, false ) ) . '</td><td>' . qldn_quote_badge( $r ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
	echo '</div>';
}

/**
 * Form báo giá.
 *
 * @param object|null $q Báo giá.
 */
function qldn_quote_form( $q ) {
	$me    = qldn_current_employee();
	$cid   = $q ? $q->customer_id : qldn_in( 'customer', 'int', 0 );
	$cust  = $cid ? qldn_get( 'customers', $cid ) : null;
	$items = $q ? qldn_quote_items( $q->id ) : array();
	echo '<div class="wrap qldn"><h1>' . ( $q ? 'Sửa báo giá ' . esc_html( $q->code ) : 'Lập báo giá' ) . '</h1>';
	qldn_print_notices();
	qldn_form_open( 'quote', array( 'class' => 'qldn-lines-form qldn-quote' ) );
	echo '<input type="hidden" name="id" value="' . esc_attr( $q ? $q->id : 0 ) . '">';
	echo '<div class="qldn-pos-grid"><div class="qldn-pos-main">';
	echo '<p><input type="text" name="title" class="large-text" placeholder="Tiêu đề báo giá (VD: Báo giá dịch vụ kế toán trọn gói năm 2027)" value="' . esc_attr( $q ? $q->title : '' ) . '"></p>';
	echo '<div class="qldn-picker"><input type="search" class="large-text qldn-product-search" placeholder="Thêm hàng trong kho: gõ mã / tên hàng…" autocomplete="off"><div class="qldn-results"></div></div>';
	echo '<table class="widefat qldn-lines" data-mode="quote"><thead><tr><th>Hàng hoá / dịch vụ</th><th>ĐVT</th><th class="num">Số lượng</th><th class="num">Đơn giá</th><th class="num">Thành tiền</th><th></th></tr></thead><tbody>';
	foreach ( $items as $i => $it ) {
		echo '<tr data-id="' . esc_attr( $it->product_id ? $it->product_id : 'f' . $i ) . '"><td><input type="hidden" name="items[' . esc_attr( $i ) . '][product_id]" value="' . esc_attr( $it->product_id ) . '"><input type="text" class="regular-text" name="items[' . esc_attr( $i ) . '][name]" value="' . esc_attr( $it->name ) . '"></td>';
		echo '<td><input type="text" class="small-text" name="items[' . esc_attr( $i ) . '][unit]" value="' . esc_attr( $it->unit ) . '"></td>';
		echo '<td class="num"><input type="text" inputmode="decimal" class="small-text qldn-q" name="items[' . esc_attr( $i ) . '][qty]" value="' . esc_attr( qldn_qty( $it->qty ) ) . '"></td>';
		echo '<td class="num"><input type="text" inputmode="numeric" class="qldn-money qldn-p" name="items[' . esc_attr( $i ) . '][price]" value="' . esc_attr( qldn_money( $it->price, false ) ) . '"></td><td class="num qldn-lt"></td><td><button type="button" class="button-link qldn-del" aria-label="Xoá dòng">✕</button></td></tr>';
	}
	echo '</tbody></table><p><button type="button" class="button qldn-add-line">+ Thêm dòng dịch vụ / hàng tự nhập</button></p>';
	echo '<p><label>Điều khoản<br><textarea name="terms" rows="4" class="large-text">' . esc_textarea( $q ? $q->terms : qldn_opt( 'quote_terms' ) ) . '</textarea></label></p>';
	echo '<p><label>Ghi chú nội bộ (không in)<br><textarea name="note" rows="2" class="large-text">' . esc_textarea( $q ? $q->note : '' ) . '</textarea></label></p>';
	echo '</div><div class="qldn-pos-side">';

	echo '<h3>Khách hàng</h3><div class="qldn-customer-pick"><input type="hidden" name="customer_id" value="' . esc_attr( $cust ? $cust->id : 0 ) . '">';
	echo '<div class="qldn-picker"><input type="search" class="widefat qldn-customer-search" placeholder="Tìm tên / SĐT khách" autocomplete="off"' . ( $cust ? ' style="display:none"' : '' ) . '><div class="qldn-results"></div></div>';
	echo '<div class="qldn-customer-chosen"' . ( $cust ? '' : ' style="display:none"' ) . '><strong>' . esc_html( $cust ? $cust->name : '' ) . '</strong> <span>' . esc_html( $cust ? $cust->phone : '' ) . '</span> <a href="#" class="qldn-customer-clear">Đổi</a></div>';
	echo '<div class="qldn-customer-new"' . ( $cust ? ' style="display:none"' : '' ) . '><input type="tel" name="phone" class="widefat" placeholder="SĐT khách mới"><input type="text" name="customer_name" class="widefat" placeholder="Tên khách mới"></div></div>';

	echo '<h3>Giá trị</h3><table class="qldn-pay"><tbody>';
	echo '<tr><th>Tiền hàng</th><td class="num qldn-subtotal">0</td></tr>';
	echo '<tr><th>Giảm giá</th><td class="num"><input type="text" inputmode="numeric" name="discount" class="qldn-money qldn-discount" value="' . esc_attr( qldn_money( $q ? $q->discount : 0, false ) ) . '"></td></tr>';
	echo '<tr><th>Thuế GTGT</th><td class="num"><select name="vat_rate" class="qldn-vat-rate">';
	$rate = $q ? qldn_qty( $q->vat_rate ) : '0';
	foreach ( array( '0' => 'Không tính', '5' => '5%', '8' => '8%', '10' => '10%' ) as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $rate, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select> <span class="qldn-vat">0</span></td></tr>';
	echo '<tr class="qldn-total-row"><th>Tổng cộng</th><td class="num qldn-total">0</td></tr></tbody></table>';
	echo '<p><label>Hiệu lực đến<br><input type="date" name="valid_until" value="' . esc_attr( $q ? $q->valid_until : gmdate( 'Y-m-d', strtotime( qldn_today() . ' +' . (int) qldn_opt( 'quote_valid_days' ) . ' days' ) ) ) . '"></label></p>';
	echo '<p><label>Trạng thái<br><select name="status" class="widefat">';
	qldn_options( qldn_quote_statuses(), $q ? $q->status : 'draft' );
	echo '</select></label></p><p><label>Nhân viên phụ trách<br><select name="staff_id" class="widefat"><option value="0">—</option>';
	qldn_options( qldn_employee_options(), $q ? $q->staff_id : ( $me ? $me->id : 0 ) );
	echo '</select></label></p>';
	echo '<p><button type="submit" class="button button-primary button-hero">' . ( $q ? 'Lưu báo giá' : 'Lập báo giá' ) . '</button></p></div></div></form></div>';
}

/**
 * Xem báo giá.
 *
 * @param object|null $q Báo giá.
 */
function qldn_quote_view( $q ) {
	if ( ! $q ) {
		echo '<div class="wrap qldn"><p>Không tìm thấy báo giá.</p></div>';
		return;
	}
	$c     = qldn_get( 'customers', $q->customer_id );
	$staff = $q->staff_id ? qldn_get( 'employees', $q->staff_id ) : null;
	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Báo giá ' . esc_html( $q->code ) . '</h1> ' . qldn_quote_badge( $q ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo ' <a class="page-title-action" href="' . esc_url( qldn_url( 'quotes', array( 'edit' => $q->id ) ) ) . '">Sửa</a>';
	echo ' <a class="page-title-action" target="_blank" href="' . esc_url( qldn_action_url( 'quote_output', array( 'id' => $q->id ) ) ) . '">In báo giá</a>';
	echo ' <a class="page-title-action" href="' . esc_url( qldn_action_url( 'quote_output', array( 'id' => $q->id, 'format' => 'word' ) ) ) . '">Tải Word</a><hr class="wp-header-end">';
	qldn_print_notices();
	echo '<p><strong>' . esc_html( $q->title ) . '</strong><br>Khách: ' . ( $c ? '<a href="' . esc_url( qldn_url( 'customers', array( 'view' => $c->id ) ) ) . '">' . esc_html( $c->name ) . '</a> ' . esc_html( $c->phone ) : '—' ) . ' · Lập ngày ' . esc_html( qldn_date( $q->created_at ) ) . ' · Hiệu lực đến ' . esc_html( qldn_date( $q->valid_until ) ) . ( $staff ? ' · Phụ trách: ' . esc_html( $staff->name ) : '' ) . '</p>';
	echo '<div class="qldn-doc-preview">' . wp_kses_post( qldn_quote_table( $q ) ) . '</div>';
	if ( $q->terms ) {
		echo '<h3>Điều khoản</h3><p>' . nl2br( esc_html( $q->terms ) ) . '</p>';
	}
	if ( $q->note ) {
		echo '<p class="description">Ghi chú nội bộ: ' . nl2br( esc_html( $q->note ) ) . '</p>';
	}

	echo '<h2>Bước tiếp theo</h2><p>';
	$btn = function ( $do, $label, $class = 'button', $confirm = '' ) use ( $q ) {
		qldn_form_open( 'quote_action', array( 'class' => 'qldn-inline' ) + ( $confirm ? array( 'onsubmit' => 'return confirm(' . wp_json_encode( $confirm ) . ')' ) : array() ) );
		echo '<input type="hidden" name="id" value="' . esc_attr( $q->id ) . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '"><button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
	};
	if ( 'draft' === $q->status ) {
		$btn( 'sent', 'Đánh dấu đã gửi khách' );
	}
	if ( in_array( $q->status, array( 'draft', 'sent' ), true ) ) {
		$btn( 'accepted', 'Khách đồng ý', 'button button-primary' );
		$btn( 'rejected', 'Khách từ chối' );
	}
	echo '<a class="button" href="' . esc_url( qldn_url( 'contracts', array( 'edit' => 'new', 'quote' => $q->id ) ) ) . '">Lập hợp đồng từ báo giá</a> ';
	if ( $q->order_id ) {
		echo '<a class="button" href="' . esc_url( qldn_url( 'orders', array( 'view' => $q->order_id ) ) ) . '">Xem đơn hàng đã tạo</a> ';
	} elseif ( current_user_can( 'qldn_sales' ) ) {
		$btn( 'order', 'Tạo đơn bán hàng (trừ kho)', 'button', 'Tạo đơn bán hàng từ báo giá: trừ kho các mặt hàng, ghi công nợ khách. Tiếp tục?' );
	}
	echo '</p>';
	global $wpdb;
	$contracts = $wpdb->get_results( $wpdb->prepare( 'SELECT id, code, title, status FROM ' . qldn_t( 'contracts' ) . ' WHERE quote_id = %d', $q->id ) );
	foreach ( $contracts as $ct ) {
		echo '<p>Hợp đồng: <a href="' . esc_url( qldn_url( 'contracts', array( 'view' => $ct->id ) ) ) . '">' . esc_html( $ct->code . ' – ' . $ct->title ) . '</a></p>';
	}
	qldn_documents_box( 'quote', $q->id );
	echo '<p>';
	$btn( 'delete', 'Xoá báo giá', 'button-link qldn-neg', 'Xoá báo giá ' . $q->code . '?' );
	echo '</p></div>';
}
