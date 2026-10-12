<?php
/**
 * Quản lý kho: sản phẩm, phiếu nhập / xuất, kiểm kê, lịch sử kho.
 *
 * Mọi thay đổi tồn kho đi qua qldn_stock_move() để luôn có dòng lịch sử (thẻ kho).
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại chứng từ kho.
 *
 * @return array
 */
function qldn_move_types() {
	return array(
		'dau_ky' => 'Tồn đầu kỳ',
		'nhap'   => 'Nhập hàng',
		'xuat'   => 'Xuất kho',
		'ban'    => 'Bán hàng',
		'tra'    => 'Huỷ đơn / trả hàng',
		'kiemke' => 'Kiểm kê',
	);
}

/**
 * Ghi biến động kho và cập nhật tồn.
 *
 * @param int   $product_id Sản phẩm.
 * @param string $type      Loại (qldn_move_types).
 * @param float $qty        Số lượng có dấu: + nhập, - xuất.
 * @param array $args       unit_cost, ref, partner, note, force (bỏ qua kiểm tra tồn).
 * @return int|WP_Error ID dòng lịch sử.
 */
function qldn_stock_move( $product_id, $type, $qty, $args = array() ) {
	global $wpdb;
	$args = wp_parse_args( $args, array( 'unit_cost' => 0, 'ref' => '', 'partner' => '', 'note' => '', 'force' => false ) );
	$qty  = round( (float) $qty, 2 );
	$t    = qldn_t( 'products' );
	$p    = qldn_get( 'products', $product_id );
	if ( ! $p ) {
		return new WP_Error( 'qldn_product', 'Không tìm thấy sản phẩm.' );
	}
	if ( 0.0 === $qty ) {
		return new WP_Error( 'qldn_qty', 'Số lượng phải khác 0.' );
	}

	// Nhập hàng có giá vốn: tính lại giá vốn bình quân gia quyền.
	if ( $qty > 0 && $args['unit_cost'] > 0 && in_array( $type, array( 'nhap', 'dau_ky' ), true ) ) {
		$old_stock = max( 0, (float) $p->stock );
		$avg       = (int) round( ( $old_stock * $p->cost_price + $qty * $args['unit_cost'] ) / ( $old_stock + $qty ) );
		$wpdb->update( $t, array( 'cost_price' => $avg ), array( 'id' => $product_id ) );
	}

	if ( $qty < 0 && ! $args['force'] && ! qldn_opt( 'allow_negative' ) ) {
		// Trừ có điều kiện trong 1 câu lệnh: 2 người bán cùng lúc cũng không làm tồn âm.
		$ok = $wpdb->query( $wpdb->prepare( "UPDATE $t SET stock = stock + %f WHERE id = %d AND stock + %f >= 0", $qty, $product_id, $qty ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $ok ) {
			return new WP_Error( 'qldn_stock', sprintf( '"%s" chỉ còn %s %s trong kho.', $p->name, qldn_qty( $p->stock ), $p->unit ) );
		}
	} else {
		$wpdb->query( $wpdb->prepare( "UPDATE $t SET stock = stock + %f WHERE id = %d", $qty, $product_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	$after = (float) $wpdb->get_var( $wpdb->prepare( "SELECT stock FROM $t WHERE id = %d", $product_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$wpdb->insert(
		qldn_t( 'stock_moves' ),
		array(
			'product_id'  => $product_id,
			'type'        => $type,
			'qty'         => $qty,
			'stock_after' => $after,
			'unit_cost'   => $args['unit_cost'] ? (int) $args['unit_cost'] : (int) $p->cost_price,
			'ref'         => $args['ref'],
			'partner'     => $args['partner'],
			'note'        => $args['note'],
			'user_id'     => get_current_user_id(),
			'created_at'  => qldn_now(),
		)
	);
	return (int) $wpdb->insert_id;
}

/**
 * Lưu sản phẩm (thêm mới hoặc sửa).
 *
 * @param array $d  sku, name, category, unit, cost_price, sale_price, min_stock, note, status, stock (chỉ khi thêm).
 * @param int   $id ID khi sửa.
 * @return int|WP_Error
 */
function qldn_product_save( $d, $id = 0 ) {
	global $wpdb;
	$t   = qldn_t( 'products' );
	$row = array(
		'sku'        => isset( $d['sku'] ) ? strtoupper( sanitize_text_field( $d['sku'] ) ) : '',
		'name'       => isset( $d['name'] ) ? sanitize_text_field( $d['name'] ) : '',
		'category'   => isset( $d['category'] ) ? sanitize_text_field( $d['category'] ) : '',
		'unit'       => isset( $d['unit'] ) ? sanitize_text_field( $d['unit'] ) : '',
		'cost_price' => isset( $d['cost_price'] ) ? max( 0, (int) $d['cost_price'] ) : 0,
		'sale_price' => isset( $d['sale_price'] ) ? max( 0, (int) $d['sale_price'] ) : 0,
		'min_stock'  => isset( $d['min_stock'] ) ? max( 0, (float) $d['min_stock'] ) : 0,
		'note'       => isset( $d['note'] ) ? sanitize_textarea_field( $d['note'] ) : '',
		'status'     => ( isset( $d['status'] ) && 'inactive' === $d['status'] ) ? 'inactive' : 'active',
	);
	if ( '' === $row['name'] ) {
		return new WP_Error( 'qldn_name', 'Chưa nhập tên sản phẩm.' );
	}
	if ( '' !== $row['sku'] ) {
		$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE sku = %s AND id <> %d", $row['sku'], $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $dup ) {
			return new WP_Error( 'qldn_sku', 'Mã hàng ' . $row['sku'] . ' đã có.' );
		}
	}
	if ( $id ) {
		$wpdb->update( $t, $row, array( 'id' => $id ) );
		return $id;
	}
	$row['created_at'] = qldn_now();
	$wpdb->insert( $t, $row );
	$id = (int) $wpdb->insert_id;
	if ( '' === $row['sku'] ) {
		$wpdb->update( $t, array( 'sku' => qldn_code( 'SP', $id ) ), array( 'id' => $id ) );
	}
	$stock = isset( $d['stock'] ) ? (float) $d['stock'] : 0;
	if ( $stock > 0 ) {
		qldn_stock_move( $id, 'dau_ky', $stock, array( 'unit_cost' => $row['cost_price'], 'note' => 'Tồn khi tạo sản phẩm' ) );
	}
	return $id;
}

/**
 * Xử lý form sản phẩm.
 */
function qldn_handle_product() {
	qldn_check( 'product', 'qldn_stock' );
	$id = qldn_in( 'id', 'int', 0 );
	$r  = qldn_product_save(
		array(
			'sku'        => qldn_in( 'sku' ),
			'name'       => qldn_in( 'name' ),
			'category'   => qldn_in( 'category' ),
			'unit'       => qldn_in( 'unit' ),
			'cost_price' => qldn_in( 'cost_price', 'money', 0 ),
			'sale_price' => qldn_in( 'sale_price', 'money', 0 ),
			'min_stock'  => qldn_in( 'min_stock', 'qty', 0 ),
			'stock'      => qldn_in( 'stock', 'qty', 0 ),
			'note'       => qldn_in( 'note', 'textarea' ),
			'status'     => qldn_in( 'status', 'key', 'active' ),
		),
		$id
	);
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'products', array( 'edit' => $id ? $id : 'new' ) );
	}
	qldn_notice( $id ? 'Đã cập nhật sản phẩm.' : 'Đã thêm sản phẩm.' );
	qldn_go( 'products' );
}
add_action( 'admin_post_qldn_product', 'qldn_handle_product' );

/**
 * Tạo phiếu nhập / xuất nhiều dòng.
 *
 * @param string $type    nhap|xuat.
 * @param array  $lines   [ [product_id, qty, unit_cost] ].
 * @param array  $args    partner, note, ref.
 * @return string|WP_Error Số phiếu.
 */
function qldn_stock_voucher( $type, $lines, $args = array() ) {
	if ( ! in_array( $type, array( 'nhap', 'xuat' ), true ) ) {
		return new WP_Error( 'qldn_type', 'Loại phiếu không hợp lệ.' );
	}
	$ref  = ! empty( $args['ref'] ) ? sanitize_text_field( $args['ref'] ) : ( 'nhap' === $type ? 'PN' : 'PX' ) . current_time( 'ymdHis' );
	$done = array();
	foreach ( $lines as $l ) {
		$qty = round( abs( (float) $l['qty'] ), 2 );
		if ( empty( $l['product_id'] ) || $qty <= 0 ) {
			continue;
		}
		$r = qldn_stock_move(
			(int) $l['product_id'],
			$type,
			'nhap' === $type ? $qty : -$qty,
			array(
				'unit_cost' => isset( $l['unit_cost'] ) ? max( 0, (int) $l['unit_cost'] ) : 0,
				'ref'       => $ref,
				'partner'   => isset( $args['partner'] ) ? sanitize_text_field( $args['partner'] ) : '',
				'note'      => isset( $args['note'] ) ? sanitize_textarea_field( $args['note'] ) : '',
			)
		);
		if ( is_wp_error( $r ) ) {
			// Hoàn lại các dòng đã ghi để phiếu không bị ghi dở dang.
			qldn_undo_moves( $done );
			return $r;
		}
		$done[] = $r;
	}
	if ( ! $done ) {
		return new WP_Error( 'qldn_empty', 'Phiếu chưa có dòng hàng nào.' );
	}
	return $ref;
}

/**
 * Xoá các dòng lịch sử vừa ghi và trả lại tồn (dùng khi phiếu / đơn lỗi giữa chừng).
 *
 * @param int[] $move_ids ID dòng lịch sử.
 */
function qldn_undo_moves( $move_ids ) {
	global $wpdb;
	foreach ( array_reverse( $move_ids ) as $mid ) {
		$m = qldn_get( 'stock_moves', $mid );
		if ( $m ) {
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . qldn_t( 'products' ) . ' SET stock = stock - %f WHERE id = %d', $m->qty, $m->product_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->delete( qldn_t( 'stock_moves' ), array( 'id' => $mid ) );
		}
	}
}

/**
 * Dòng hàng gửi lên từ form (items[i][product_id|qty|price|cost]).
 *
 * @return array
 */
function qldn_posted_lines() {
	$out = array();
	foreach ( qldn_in( 'items', 'array', array() ) as $l ) {
		if ( ! is_array( $l ) ) {
			continue;
		}
		$out[] = array(
			'product_id' => isset( $l['product_id'] ) ? (int) $l['product_id'] : 0,
			'qty'        => isset( $l['qty'] ) ? qldn_num( $l['qty'], true ) : 0,
			'price'      => isset( $l['price'] ) ? qldn_num( $l['price'] ) : 0,
			'unit_cost'  => isset( $l['cost'] ) ? qldn_num( $l['cost'] ) : 0,
		);
	}
	return $out;
}

/**
 * Xử lý phiếu nhập / xuất.
 */
function qldn_handle_voucher() {
	qldn_check( 'voucher', 'qldn_stock' );
	$type = qldn_in( 'type', 'key', 'nhap' );
	$r    = qldn_stock_voucher(
		$type,
		qldn_posted_lines(),
		array(
			'partner' => qldn_in( 'partner' ),
			'note'    => qldn_in( 'note', 'textarea' ),
			'ref'     => qldn_in( 'ref' ),
		)
	);
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'stock', array( 'tab' => $type ) );
	}
	qldn_notice( sprintf( 'Đã lưu phiếu %s <strong>%s</strong>.', 'nhap' === $type ? 'nhập' : 'xuất', esc_html( $r ) ) );
	qldn_go( 'stock', array( 'tab' => 'history', 'ref' => $r ) );
}
add_action( 'admin_post_qldn_voucher', 'qldn_handle_voucher' );

/**
 * Xử lý kiểm kê: đưa tồn hệ thống về số đếm thực tế.
 */
function qldn_handle_stocktake() {
	qldn_check( 'stocktake', 'qldn_stock' );
	$ref     = 'KK' . current_time( 'ymdHis' );
	$note    = qldn_in( 'note', 'textarea' );
	$changed = 0;
	foreach ( qldn_in( 'actual', 'array', array() ) as $pid => $val ) {
		if ( '' === trim( (string) $val ) ) {
			continue;
		}
		$p = qldn_get( 'products', (int) $pid );
		if ( ! $p ) {
			continue;
		}
		$diff = round( qldn_num( $val, true ) - (float) $p->stock, 2 );
		if ( 0.0 !== $diff ) {
			qldn_stock_move( $p->id, 'kiemke', $diff, array( 'ref' => $ref, 'note' => $note, 'force' => true ) );
			++$changed;
		}
	}
	qldn_notice( $changed ? sprintf( 'Đã điều chỉnh %d mặt hàng theo kiểm kê (phiếu %s).', $changed, $ref ) : 'Số đếm khớp với hệ thống, không có điều chỉnh.' );
	qldn_go( 'stock', array( 'tab' => $changed ? 'history' : 'stocktake', 'ref' => $changed ? $ref : '' ) );
}
add_action( 'admin_post_qldn_stocktake', 'qldn_handle_stocktake' );

/**
 * Tìm sản phẩm (AJAX) cho ô chọn hàng ở trang bán hàng / phiếu kho.
 */
function qldn_ajax_products() {
	if ( ! current_user_can( 'qldn_sales' ) && ! current_user_can( 'qldn_stock' ) ) {
		wp_send_json_error( null, 403 );
	}
	check_ajax_referer( 'qldn_ajax', 'nonce' );
	global $wpdb;
	$s    = qldn_in( 'q' );
	$rows = $wpdb->get_results(
		'SELECT id, sku, name, unit, sale_price, cost_price, stock FROM ' . qldn_t( 'products' ) . " WHERE status = 'active'" . qldn_like( $s, array( 'sku', 'name', 'category' ) ) . ' ORDER BY name LIMIT 20'
	);
	foreach ( $rows as $r ) {
		$r->stock = (float) $r->stock;
		$r->sale_price = (int) $r->sale_price;
		$r->cost_price = current_user_can( 'qldn_stock' ) || current_user_can( 'qldn_reports' ) ? (int) $r->cost_price : 0;
	}
	wp_send_json_success( $rows );
}
add_action( 'wp_ajax_qldn_products', 'qldn_ajax_products' );

/**
 * Xuất CSV tồn kho.
 */
function qldn_export_stock() {
	qldn_check( 'export_stock', 'qldn_stock' );
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . qldn_t( 'products' ) . ' ORDER BY category, name' );
	$out  = array();
	foreach ( $rows as $r ) {
		$out[] = array( $r->sku, $r->name, $r->category, $r->unit, $r->cost_price, $r->sale_price, (float) $r->stock, (float) $r->min_stock, round( $r->stock * $r->cost_price ), 'active' === $r->status ? 'Đang bán' : 'Ngừng bán' );
	}
	qldn_csv( 'ton-kho-' . current_time( 'Y-m-d' ) . '.csv', array( 'Mã hàng', 'Tên hàng', 'Nhóm', 'Đơn vị', 'Giá vốn', 'Giá bán', 'Tồn kho', 'Tồn tối thiểu', 'Giá trị tồn', 'Trạng thái' ), $out );
}
add_action( 'admin_post_qldn_export_stock', 'qldn_export_stock' );

/**
 * Xuất CSV lịch sử kho theo bộ lọc.
 */
function qldn_export_moves() {
	qldn_check( 'export_moves', 'qldn_stock' );
	global $wpdb;
	$types = qldn_move_types();
	$rows  = $wpdb->get_results( 'SELECT m.*, p.sku, p.name, p.unit FROM ' . qldn_t( 'stock_moves' ) . ' m LEFT JOIN ' . qldn_t( 'products' ) . ' p ON p.id = m.product_id WHERE 1=1' . qldn_moves_where() . ' ORDER BY m.id DESC' ); // phpcs:ignore WordPress.DB.PreparedSQL
	$out   = array();
	foreach ( $rows as $r ) {
		$out[] = array( qldn_date( $r->created_at, true ), $r->ref, isset( $types[ $r->type ] ) ? $types[ $r->type ] : $r->type, $r->sku, $r->name, (float) $r->qty, $r->unit, $r->unit_cost, (float) $r->stock_after, $r->partner, $r->note );
	}
	qldn_csv( 'lich-su-kho-' . current_time( 'Y-m-d' ) . '.csv', array( 'Thời gian', 'Số phiếu', 'Loại', 'Mã hàng', 'Tên hàng', 'Số lượng', 'Đơn vị', 'Đơn giá vốn', 'Tồn sau', 'Đối tác', 'Ghi chú' ), $out );
}
add_action( 'admin_post_qldn_export_moves', 'qldn_export_moves' );

/**
 * Điều kiện lọc lịch sử kho từ tham số GET.
 *
 * @return string SQL.
 */
function qldn_moves_where() {
	global $wpdb;
	$w    = '';
	$pid  = qldn_in( 'product', 'int', 0 );
	$type = qldn_in( 'type', 'key' );
	$ref  = qldn_in( 'ref' );
	$from = qldn_in( 'from', 'date' );
	$to   = qldn_in( 'to', 'date' );
	if ( $pid ) {
		$w .= $wpdb->prepare( ' AND m.product_id = %d', $pid );
	}
	if ( $type && isset( qldn_move_types()[ $type ] ) ) {
		$w .= $wpdb->prepare( ' AND m.type = %s', $type );
	}
	if ( $ref ) {
		$w .= $wpdb->prepare( ' AND m.ref = %s', $ref );
	}
	if ( $from ) {
		$w .= $wpdb->prepare( ' AND m.created_at >= %s', $from . ' 00:00:00' );
	}
	if ( $to ) {
		$w .= $wpdb->prepare( ' AND m.created_at <= %s', $to . ' 23:59:59' );
	}
	return $w;
}

/**
 * Trang Sản phẩm & tồn kho.
 */
function qldn_page_products() {
	global $wpdb;
	$edit = qldn_in( 'edit' );
	if ( '' !== $edit ) {
		qldn_product_form( 'new' === $edit ? null : qldn_get( 'products', (int) $edit ) );
		return;
	}
	$t      = qldn_t( 'products' );
	$s      = qldn_in( 's' );
	$cat    = qldn_in( 'cat' );
	$filter = qldn_in( 'filter', 'key' );
	$where  = ' WHERE 1=1' . qldn_like( $s, array( 'sku', 'name' ) );
	if ( $cat ) {
		$where .= $wpdb->prepare( ' AND category = %s', $cat );
	}
	if ( 'low' === $filter ) {
		$where .= " AND status = 'active' AND stock <= min_stock";
	} elseif ( 'inactive' === $filter ) {
		$where .= " AND status = 'inactive'";
	} else {
		$where .= " AND status = 'active'";
	}
	$per             = 50;
	list( $paged, $off ) = qldn_paged( $per );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t $where ORDER BY name LIMIT %d OFFSET %d", $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$sum   = $wpdb->get_row( "SELECT SUM(stock * cost_price) AS v, SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END) AS low FROM $t WHERE status = 'active'" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$cats  = $wpdb->get_col( "SELECT DISTINCT category FROM $t WHERE category <> '' ORDER BY category" ); // phpcs:ignore WordPress.DB.PreparedSQL

	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Sản phẩm & tồn kho</h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'products', array( 'edit' => 'new' ) ) ) . '">Thêm sản phẩm</a> <a class="page-title-action" href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'nhap' ) ) ) . '">Nhập hàng</a> <a class="page-title-action" href="' . esc_url( qldn_action_url( 'export_stock' ) ) . '">Xuất Excel (CSV)</a><hr class="wp-header-end">';
	qldn_print_notices();
	echo '<div class="qldn-cards"><div class="qldn-card"><span>Giá trị tồn kho (giá vốn)</span><strong>' . esc_html( qldn_money( $sum ? $sum->v : 0 ) ) . '</strong></div>';
	echo '<a class="qldn-card' . ( $sum && $sum->low ? ' qldn-card-warn' : '' ) . '" href="' . esc_url( qldn_url( 'products', array( 'filter' => 'low' ) ) ) . '"><span>Sắp hết / hết hàng</span><strong>' . esc_html( $sum ? (int) $sum->low : 0 ) . ' mặt hàng</strong></a></div>';

	$extra  = '<select name="filter">' . qldn_options_html( array( '' => 'Đang bán', 'low' => 'Sắp hết hàng', 'inactive' => 'Ngừng bán' ), $filter ) . '</select> ';
	$extra .= '<select name="cat"><option value="">Mọi nhóm hàng</option>' . qldn_options_html( array_combine( $cats, $cats ) ?: array(), $cat ) . '</select> ';
	qldn_search_box( 'products', $s, $extra, 'Mã / tên hàng' );

	echo '<table class="widefat striped qldn-table"><thead><tr><th>Mã hàng</th><th>Tên hàng</th><th>Nhóm</th><th>ĐVT</th><th class="num">Giá vốn</th><th class="num">Giá bán</th><th class="num">Tồn kho</th><th class="num">Giá trị tồn</th><th></th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="9">Chưa có sản phẩm.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$low = (float) $r->stock <= (float) $r->min_stock;
		echo '<tr><td>' . esc_html( $r->sku ) . '</td><td><a href="' . esc_url( qldn_url( 'products', array( 'edit' => $r->id ) ) ) . '"><strong>' . esc_html( $r->name ) . '</strong></a></td><td>' . esc_html( $r->category ) . '</td><td>' . esc_html( $r->unit ) . '</td>';
		echo '<td class="num">' . esc_html( qldn_money( $r->cost_price, false ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->sale_price, false ) ) . '</td>';
		echo '<td class="num">' . ( $low ? qldn_badge( qldn_qty( $r->stock ), 'red' ) : esc_html( qldn_qty( $r->stock ) ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="num">' . esc_html( qldn_money( $r->stock * $r->cost_price, false ) ) . '</td>';
		echo '<td class="qldn-actions"><a href="' . esc_url( qldn_url( 'products', array( 'edit' => $r->id ) ) ) . '">Sửa</a> · <a href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'history', 'product' => $r->id ) ) ) . '">Thẻ kho</a></td></tr>';
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
	echo '</div>';
}

/**
 * Chuỗi <option> (trả về thay vì in).
 *
 * @param array  $options  Giá trị => nhãn.
 * @param string $selected Đang chọn.
 * @return string
 */
function qldn_options_html( $options, $selected ) {
	ob_start();
	qldn_options( $options, $selected );
	return ob_get_clean();
}

/**
 * Form thêm / sửa sản phẩm.
 *
 * @param object|null $p Sản phẩm.
 */
function qldn_product_form( $p ) {
	global $wpdb;
	$cats = $wpdb->get_col( 'SELECT DISTINCT category FROM ' . qldn_t( 'products' ) . " WHERE category <> '' ORDER BY category" );
	echo '<div class="wrap qldn"><h1>' . ( $p ? 'Sửa sản phẩm' : 'Thêm sản phẩm' ) . '</h1>';
	qldn_print_notices();
	qldn_form_open( 'product' );
	echo '<input type="hidden" name="id" value="' . esc_attr( $p ? $p->id : 0 ) . '"><table class="form-table">';
	qldn_field( 'Tên hàng', 'name', $p ? $p->name : '', array( 'required' => true ) );
	qldn_field( 'Mã hàng', 'sku', $p ? $p->sku : '', array( 'help' => 'Để trống để tự sinh (SP000001…). Có thể nhập mã vạch.' ) );
	qldn_field( 'Nhóm hàng', 'category', $p ? $p->category : '', array( 'attrs' => array( 'list' => 'qldn-cats' ) ) );
	echo '<datalist id="qldn-cats">';
	foreach ( $cats as $c ) {
		echo '<option value="' . esc_attr( $c ) . '">';
	}
	echo '</datalist>';
	qldn_field( 'Đơn vị tính', 'unit', $p ? $p->unit : 'cái', array( 'class' => 'small-text' ) );
	qldn_field( 'Giá bán', 'sale_price', $p ? $p->sale_price : '', array( 'type' => 'money' ) );
	qldn_field( 'Giá vốn', 'cost_price', $p ? $p->cost_price : '', array( 'type' => 'money', 'help' => $p ? 'Tự tính lại theo bình quân gia quyền mỗi lần nhập hàng có đơn giá.' : '' ) );
	if ( $p ) {
		echo '<tr><th>Tồn kho</th><td><strong>' . esc_html( qldn_qty( $p->stock ) . ' ' . $p->unit ) . '</strong> <span class="description">– thay đổi bằng <a href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'nhap' ) ) ) . '">phiếu nhập/xuất</a> hoặc <a href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'stocktake' ) ) ) . '">kiểm kê</a>.</span></td></tr>';
	} else {
		qldn_field( 'Tồn kho ban đầu', 'stock', '', array( 'type' => 'text', 'class' => 'small-text', 'attrs' => array( 'inputmode' => 'decimal' ) ) );
	}
	qldn_field( 'Tồn tối thiểu', 'min_stock', $p ? qldn_qty( $p->min_stock ) : '', array( 'class' => 'small-text', 'help' => 'Tồn kho bằng hoặc dưới mức này sẽ báo sắp hết hàng.' ) );
	qldn_field( 'Trạng thái', 'status', $p ? $p->status : 'active', array( 'type' => 'select', 'options' => array( 'active' => 'Đang bán', 'inactive' => 'Ngừng bán' ) ) );
	qldn_field( 'Ghi chú', 'note', $p ? $p->note : '', array( 'type' => 'textarea' ) );
	echo '</table>';
	submit_button( $p ? 'Cập nhật' : 'Thêm sản phẩm' );
	echo '</form></div>';
}

/**
 * Trang Kho: phiếu nhập, phiếu xuất, kiểm kê, lịch sử.
 */
function qldn_page_stock() {
	$tab  = qldn_in( 'tab', 'key', 'nhap' );
	$tabs = array(
		'nhap'      => 'Phiếu nhập',
		'xuat'      => 'Phiếu xuất',
		'stocktake' => 'Kiểm kê',
		'history'   => 'Lịch sử kho',
	);
	$tab = isset( $tabs[ $tab ] ) ? $tab : 'nhap';
	echo '<div class="wrap qldn"><h1>Kho hàng</h1>';
	qldn_print_notices();
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $v ) {
		echo '<a class="nav-tab' . ( $k === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( qldn_url( 'stock', array( 'tab' => $k ) ) ) . '">' . esc_html( $v ) . '</a>';
	}
	echo '</nav>';
	if ( 'stocktake' === $tab ) {
		qldn_stocktake_form();
	} elseif ( 'history' === $tab ) {
		qldn_moves_list();
	} else {
		qldn_voucher_form( $tab );
	}
	echo '</div>';
}

/**
 * Form phiếu nhập / xuất.
 *
 * @param string $type nhap|xuat.
 */
function qldn_voucher_form( $type ) {
	$in = 'nhap' === $type;
	qldn_form_open( 'voucher', array( 'class' => 'qldn-lines-form' ) );
	echo '<input type="hidden" name="type" value="' . esc_attr( $type ) . '">';
	echo '<table class="form-table">';
	qldn_field( $in ? 'Nhà cung cấp' : 'Người nhận / lý do', 'partner', '' );
	qldn_field( 'Số chứng từ', 'ref', '', array( 'class' => 'regular-text', 'help' => 'Để trống để tự đánh số (' . ( $in ? 'PN' : 'PX' ) . ' + ngày giờ).' ) );
	qldn_field( 'Ghi chú', 'note', '', array( 'type' => 'textarea' ) );
	echo '</table>';
	echo '<h3>Hàng hoá</h3><div class="qldn-picker"><input type="search" class="regular-text qldn-product-search" placeholder="Gõ mã hoặc tên hàng để thêm…" autocomplete="off"><div class="qldn-results"></div></div>';
	echo '<table class="widefat qldn-lines" data-mode="' . ( $in ? 'in' : 'out' ) . '"><thead><tr><th>Hàng hoá</th><th class="num">Tồn hiện tại</th><th class="num">Số lượng</th>' . ( $in ? '<th class="num">Đơn giá nhập</th><th class="num">Thành tiền</th>' : '' ) . '<th></th></tr></thead><tbody></tbody>';
	if ( $in ) {
		echo '<tfoot><tr><th colspan="4" class="num">Tổng tiền hàng</th><th class="num qldn-sum">0</th><th></th></tr></tfoot>';
	}
	echo '</table>';
	submit_button( $in ? 'Lưu phiếu nhập' : 'Lưu phiếu xuất' );
	echo '</form>';
}

/**
 * Form kiểm kê: nhập số đếm thực tế, để trống = không kiểm.
 */
function qldn_stocktake_form() {
	global $wpdb;
	$cat  = qldn_in( 'cat' );
	$t    = qldn_t( 'products' );
	$cats = $wpdb->get_col( "SELECT DISTINCT category FROM $t WHERE category <> '' ORDER BY category" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$sql  = "SELECT * FROM $t WHERE status = 'active'" . ( $cat ? $wpdb->prepare( ' AND category = %s', $cat ) : '' ) . ' ORDER BY category, name';
	$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL

	echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-stock"><input type="hidden" name="tab" value="stocktake"><select name="cat"><option value="">Mọi nhóm hàng</option>';
	qldn_options( array_combine( $cats, $cats ) ?: array(), $cat );
	echo '</select> <button class="button">Lọc</button></form>';
	echo '<p class="description">Nhập số lượng đếm được thực tế. Ô để trống = không kiểm mặt hàng đó. Lưu xong, tồn kho được đưa về đúng số đếm và chênh lệch ghi vào lịch sử kho.</p>';
	qldn_form_open( 'stocktake' );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Mã</th><th>Tên hàng</th><th>ĐVT</th><th class="num">Tồn hệ thống</th><th class="num">Thực tế</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		echo '<tr><td>' . esc_html( $r->sku ) . '</td><td>' . esc_html( $r->name ) . '</td><td>' . esc_html( $r->unit ) . '</td><td class="num">' . esc_html( qldn_qty( $r->stock ) ) . '</td><td class="num"><input type="text" inputmode="decimal" class="small-text" name="actual[' . esc_attr( $r->id ) . ']"></td></tr>';
	}
	echo '</tbody></table><table class="form-table">';
	qldn_field( 'Ghi chú', 'note', 'Kiểm kê ' . current_time( 'd/m/Y' ) );
	echo '</table>';
	submit_button( 'Lưu kết quả kiểm kê' );
	echo '</form>';
}

/**
 * Lịch sử kho (thẻ kho).
 */
function qldn_moves_list() {
	global $wpdb;
	$types = qldn_move_types();
	$pid   = qldn_in( 'product', 'int', 0 );
	$where = qldn_moves_where();
	$per   = 50;
	list( $paged, $off ) = qldn_paged( $per );
	$from  = qldn_t( 'stock_moves' ) . ' m LEFT JOIN ' . qldn_t( 'products' ) . ' p ON p.id = m.product_id';
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $from WHERE 1=1 $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT m.*, p.sku, p.name, p.unit FROM $from WHERE 1=1 $where ORDER BY m.id DESC LIMIT %d OFFSET %d", $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$prod  = $pid ? qldn_get( 'products', $pid ) : null;

	echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-stock"><input type="hidden" name="tab" value="history">';
	if ( $pid ) {
		echo '<input type="hidden" name="product" value="' . esc_attr( $pid ) . '">';
	}
	echo '<select name="type"><option value="">Mọi loại</option>';
	qldn_options( $types, qldn_in( 'type', 'key' ) );
	echo '</select> Từ <input type="date" name="from" value="' . esc_attr( qldn_in( 'from', 'date' ) ) . '"> đến <input type="date" name="to" value="' . esc_attr( qldn_in( 'to', 'date' ) ) . '"> <input type="text" name="ref" placeholder="Số phiếu" value="' . esc_attr( qldn_in( 'ref' ) ) . '"> <button class="button">Lọc</button> ';
	$export = array_filter( array( 'product' => $pid, 'type' => qldn_in( 'type', 'key' ), 'from' => qldn_in( 'from', 'date' ), 'to' => qldn_in( 'to', 'date' ), 'ref' => qldn_in( 'ref' ) ) );
	echo '<a class="button" href="' . esc_url( qldn_action_url( 'export_moves', $export ) ) . '">Xuất CSV</a></form>';
	if ( $prod ) {
		echo '<p>Thẻ kho: <strong>' . esc_html( $prod->sku . ' – ' . $prod->name ) . '</strong>, tồn hiện tại <strong>' . esc_html( qldn_qty( $prod->stock ) . ' ' . $prod->unit ) . '</strong>. <a href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'history' ) ) ) . '">Xem tất cả</a></p>';
	}
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Thời gian</th><th>Số phiếu</th><th>Loại</th><th>Hàng hoá</th><th class="num">Số lượng</th><th class="num">Đơn giá</th><th class="num">Tồn sau</th><th>Đối tác / ghi chú</th><th>Người làm</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="9">Chưa có dữ liệu.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$u = $r->user_id ? get_userdata( $r->user_id ) : null;
		echo '<tr><td>' . esc_html( qldn_date( $r->created_at, true ) ) . '</td><td>' . esc_html( $r->ref ) . '</td><td>' . esc_html( isset( $types[ $r->type ] ) ? $types[ $r->type ] : $r->type ) . '</td>';
		echo '<td><a href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'history', 'product' => $r->product_id ) ) ) . '">' . esc_html( $r->sku . ' – ' . $r->name ) . '</a></td>';
		echo '<td class="num ' . ( $r->qty < 0 ? 'qldn-neg' : 'qldn-pos' ) . '">' . esc_html( ( $r->qty > 0 ? '+' : '' ) . qldn_qty( $r->qty ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->unit_cost, false ) ) . '</td><td class="num">' . esc_html( qldn_qty( $r->stock_after ) ) . '</td>';
		echo '<td>' . esc_html( trim( $r->partner . ( $r->partner && $r->note ? ' – ' : '' ) . $r->note ) ) . '</td><td>' . esc_html( $u ? $u->display_name : '' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
}
