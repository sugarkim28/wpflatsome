<?php
/**
 * Nhật ký hoạt động: ai làm gì, lúc nào (bán hàng, huỷ đơn, kho, lương, hợp đồng, sổ quỹ, cài đặt…).
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ghi 1 dòng nhật ký.
 *
 * @param string $action      Mã hành động (order_create, payroll_lock…).
 * @param string $object_type Loại đối tượng (order, payroll, contract…).
 * @param int    $object_id   ID đối tượng.
 * @param string $detail      Mô tả ngắn.
 */
function qldn_log( $action, $object_type = '', $object_id = 0, $detail = '' ) {
	global $wpdb;
	$wpdb->insert(
		qldn_t( 'logs' ),
		array(
			'user_id'     => get_current_user_id(),
			'action'      => substr( sanitize_key( $action ), 0, 50 ),
			'object_type' => substr( sanitize_key( $object_type ), 0, 20 ),
			'object_id'   => (int) $object_id,
			'detail'      => wp_strip_all_tags( (string) $detail ),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), 0, 45 ) : '',
			'created_at'  => qldn_now(),
		)
	);
}

/**
 * Tên hành động.
 *
 * @return array
 */
function qldn_log_actions() {
	return array(
		'order_create'    => 'Tạo đơn hàng',
		'order_cancel'    => 'Huỷ đơn hàng',
		'order_pay'       => 'Thu nợ đơn hàng',
		'stock_voucher'   => 'Phiếu nhập / xuất kho',
		'stocktake'       => 'Kiểm kê kho',
		'product_save'    => 'Sửa sản phẩm / giá',
		'cash_entry'      => 'Phiếu thu / chi',
		'cash_delete'     => 'Xoá phiếu thu / chi',
		'supplier_pay'    => 'Trả nợ nhà cung cấp',
		'employee_save'   => 'Sửa hồ sơ / lương nhân viên',
		'attendance_save' => 'Sửa bảng công',
		'payroll_build'   => 'Tính lương',
		'payroll_lock'    => 'Chốt bảng lương',
		'payroll_unlock'  => 'Mở khoá bảng lương',
		'payroll_paid'    => 'Trả lương',
		'request_decide'  => 'Duyệt / từ chối đơn',
		'contract_save'   => 'Lập / sửa hợp đồng',
		'contract_status' => 'Đổi trạng thái hợp đồng',
		'contract_pay'    => 'Thu tiền hợp đồng',
		'settings_save'   => 'Lưu cài đặt',
		'demo_data'       => 'Tạo dữ liệu mẫu',
	);
}

// Ghi nhật ký qua hook: không phải sửa từng nơi xử lý.
add_action(
	'qldn_order_created',
	function ( $id ) {
		$o = qldn_get( 'orders', $id );
		qldn_log( 'order_create', 'order', $id, $o ? $o->code . ' – ' . qldn_money( $o->total ) : '' );
	}
);
add_action(
	'qldn_order_cancelled',
	function ( $id ) {
		$o = qldn_get( 'orders', $id );
		qldn_log( 'order_cancel', 'order', $id, $o ? $o->code . ' – ' . qldn_money( $o->total ) : '' );
	}
);

/**
 * Trang Nhật ký hoạt động.
 */
function qldn_page_logs() {
	global $wpdb;
	$t       = qldn_t( 'logs' );
	$actions = qldn_log_actions();
	$act     = qldn_in( 'act', 'key' );
	$uid     = qldn_in( 'user', 'int', 0 );
	$from    = qldn_in( 'from', 'date' );
	$to      = qldn_in( 'to', 'date' );
	$where   = ' WHERE 1=1' . qldn_like( qldn_in( 's' ), array( 'detail' ) );
	if ( isset( $actions[ $act ] ) ) {
		$where .= $wpdb->prepare( ' AND action = %s', $act );
	}
	if ( $uid ) {
		$where .= $wpdb->prepare( ' AND user_id = %d', $uid );
	}
	if ( $from ) {
		$where .= $wpdb->prepare( ' AND created_at >= %s', $from . ' 00:00:00' );
	}
	if ( $to ) {
		$where .= $wpdb->prepare( ' AND created_at <= %s', $to . ' 23:59:59' );
	}
	$per             = 100;
	list( $paged, $off ) = qldn_paged( $per );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t $where ORDER BY id DESC LIMIT %d OFFSET %d", $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$users = array();
	foreach ( $wpdb->get_col( "SELECT DISTINCT user_id FROM $t" ) as $u ) { // phpcs:ignore WordPress.DB.PreparedSQL
		$ud = get_userdata( $u );
		$users[ $u ] = $ud ? $ud->display_name : '#' . $u;
	}
	echo '<div class="wrap qldn"><h1>Nhật ký hoạt động</h1><p class="description">Ghi lại các thao tác quan trọng: bán hàng, huỷ đơn, kho, thu chi, lương, hợp đồng, cài đặt. Không sửa / xoá được từ phần mềm.</p>';
	$extra  = '<select name="act"><option value="">Mọi thao tác</option>' . qldn_options_html( $actions, $act ) . '</select> ';
	$extra .= '<select name="user"><option value="">Mọi người</option>' . qldn_options_html( $users, $uid ) . '</select> ';
	$extra .= 'Từ <input type="date" name="from" value="' . esc_attr( $from ) . '"> đến <input type="date" name="to" value="' . esc_attr( $to ) . '"> ';
	qldn_search_box( 'logs', qldn_in( 's' ), $extra, 'Nội dung' );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Thời gian</th><th>Người làm</th><th>Thao tác</th><th>Nội dung</th><th>IP</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="5">Chưa có dữ liệu.</td></tr>';
	}
	foreach ( $rows as $r ) {
		echo '<tr><td>' . esc_html( qldn_date( $r->created_at, true ) ) . '</td><td>' . esc_html( $users[ $r->user_id ] ?? '' ) . '</td><td>' . esc_html( $actions[ $r->action ] ?? $r->action ) . '</td><td>' . esc_html( $r->detail ) . '</td><td>' . esc_html( $r->ip ) . '</td></tr>';
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
	echo '</div>';
}
