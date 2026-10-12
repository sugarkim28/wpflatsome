<?php
/**
 * Khách hàng & chăm sóc khách hàng: hồ sơ, lịch sử mua, nhật ký chăm sóc, lịch hẹn gọi lại,
 * sinh nhật, khách lâu không quay lại.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kênh chăm sóc.
 *
 * @return array
 */
function qldn_care_channels() {
	return array(
		'call'     => 'Gọi điện',
		'zalo'     => 'Zalo / tin nhắn',
		'meet'     => 'Gặp trực tiếp',
		'email'    => 'Email',
		'complain' => 'Khiếu nại / bảo hành',
		'other'    => 'Khác',
	);
}

/**
 * Kết quả chăm sóc.
 *
 * @return array
 */
function qldn_care_results() {
	return array(
		''           => '—',
		'interested' => 'Quan tâm',
		'bought'     => 'Đã mua',
		'later'      => 'Hẹn lại',
		'no_answer'  => 'Không nghe máy',
		'resolved'   => 'Đã xử lý xong',
		'refused'    => 'Không có nhu cầu',
	);
}

/**
 * Lưu khách hàng.
 *
 * @param array $d  name, phone, email, address, grp, birthday, source, staff_id, note.
 * @param int   $id ID khi sửa.
 * @return int|WP_Error
 */
function qldn_customer_save( $d, $id = 0 ) {
	global $wpdb;
	$t   = qldn_t( 'customers' );
	$row = array(
		'name'     => isset( $d['name'] ) ? sanitize_text_field( $d['name'] ) : '',
		'phone'    => isset( $d['phone'] ) ? qldn_phone( $d['phone'] ) : '',
		'email'    => isset( $d['email'] ) ? sanitize_email( $d['email'] ) : '',
		'address'  => isset( $d['address'] ) ? sanitize_text_field( $d['address'] ) : '',
		'grp'      => isset( $d['grp'] ) ? sanitize_text_field( $d['grp'] ) : '',
		'birthday' => ! empty( $d['birthday'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['birthday'] ) ? $d['birthday'] : null,
		'source'   => isset( $d['source'] ) ? sanitize_text_field( $d['source'] ) : '',
		'staff_id' => isset( $d['staff_id'] ) ? (int) $d['staff_id'] : 0,
		'note'     => isset( $d['note'] ) ? sanitize_textarea_field( $d['note'] ) : '',
	);
	if ( '' === $row['name'] ) {
		return new WP_Error( 'qldn_name', 'Chưa nhập tên khách hàng.' );
	}
	if ( '' !== $row['phone'] ) {
		$dup = $wpdb->get_row( $wpdb->prepare( "SELECT id, name FROM $t WHERE phone = %s AND id <> %d", $row['phone'], $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $dup ) {
			return new WP_Error( 'qldn_phone', sprintf( 'Số điện thoại %s đã thuộc khách "%s".', $row['phone'], $dup->name ) );
		}
	}
	if ( $id ) {
		$wpdb->update( $t, $row, array( 'id' => $id ) );
		return $id;
	}
	$row['created_at'] = qldn_now();
	if ( '' === $row['grp'] ) {
		$groups     = array_keys( qldn_opt_list( 'customer_groups' ) );
		$row['grp'] = $groups ? $groups[0] : '';
	}
	$wpdb->insert( $t, $row );
	$id = (int) $wpdb->insert_id;
	$wpdb->update( $t, array( 'code' => qldn_code( 'KH', $id ) ), array( 'id' => $id ) );
	return $id;
}

/**
 * Tìm khách theo số điện thoại, chưa có thì tạo (dùng khi bán hàng).
 *
 * @param string $phone Số điện thoại.
 * @param string $name  Tên (khi tạo mới).
 * @return int ID khách, 0 nếu không có số.
 */
function qldn_customer_by_phone( $phone, $name = '' ) {
	global $wpdb;
	$phone = qldn_phone( $phone );
	if ( '' === $phone ) {
		return 0;
	}
	$id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . qldn_t( 'customers' ) . ' WHERE phone = %s', $phone ) );
	if ( $id ) {
		return $id;
	}
	$id = qldn_customer_save(
		array(
			'name'   => $name ? $name : 'Khách ' . $phone,
			'phone'  => $phone,
			'source' => 'Cửa hàng',
		)
	);
	return is_wp_error( $id ) ? 0 : $id;
}

/**
 * Cộng / trừ doanh số, điểm, công nợ của khách khi có đơn hoàn thành / huỷ đơn.
 *
 * @param int $customer_id Khách.
 * @param int $total       Tổng tiền đơn (âm khi huỷ).
 * @param int $debt        Số nợ phát sinh (âm khi huỷ).
 * @param int $sign        1 = thêm đơn, -1 = huỷ đơn.
 */
function qldn_customer_add_sale( $customer_id, $total, $debt, $sign = 1 ) {
	global $wpdb;
	if ( ! $customer_id ) {
		return;
	}
	$t      = qldn_t( 'customers' );
	$pv     = (int) qldn_opt( 'point_value' );
	$points = $pv > 0 ? (int) floor( abs( $total ) / $pv ) * $sign : 0;
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE $t SET total_spent = total_spent + %d, debt = debt + %d, points = GREATEST(points + %d, 0), order_count = GREATEST(order_count + %d, 0) WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL
			$total,
			$debt,
			$points,
			$sign,
			$customer_id
		)
	);
	if ( $sign > 0 ) {
		$wpdb->update( $t, array( 'last_order_at' => qldn_now() ), array( 'id' => $customer_id ) );
		// Tự lên VIP.
		$vip = (int) qldn_opt( 'vip_threshold' );
		if ( $vip > 0 && isset( qldn_opt_list( 'customer_groups' )['VIP'] ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE $t SET grp = 'VIP' WHERE id = %d AND total_spent >= %d", $customer_id, $vip ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
	} else {
		$last = $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(created_at) FROM ' . qldn_t( 'orders' ) . " WHERE customer_id = %d AND status = 'done'", $customer_id ) );
		$wpdb->update( $t, array( 'last_order_at' => $last ), array( 'id' => $customer_id ) );
	}
}

/**
 * Ghi nhật ký chăm sóc.
 *
 * @param array $d customer_id, channel, content, result, next_date, next_task, staff_id.
 * @return int|WP_Error
 */
function qldn_care_add( $d ) {
	global $wpdb;
	$cid = isset( $d['customer_id'] ) ? (int) $d['customer_id'] : 0;
	if ( ! $cid || ! qldn_get( 'customers', $cid ) ) {
		return new WP_Error( 'qldn_customer', 'Chưa chọn khách hàng.' );
	}
	$channels = qldn_care_channels();
	$results  = qldn_care_results();
	$row      = array(
		'customer_id' => $cid,
		'staff_id'    => isset( $d['staff_id'] ) ? (int) $d['staff_id'] : 0,
		'channel'     => isset( $d['channel'], $channels[ $d['channel'] ] ) ? $d['channel'] : 'other',
		'content'     => isset( $d['content'] ) ? sanitize_textarea_field( $d['content'] ) : '',
		'result'      => isset( $d['result'], $results[ $d['result'] ] ) ? $d['result'] : '',
		'next_date'   => ! empty( $d['next_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['next_date'] ) ? $d['next_date'] : null,
		'next_task'   => isset( $d['next_task'] ) ? sanitize_text_field( $d['next_task'] ) : '',
		'done'        => 0,
		'user_id'     => get_current_user_id(),
		'created_at'  => qldn_now(),
	);
	if ( '' === $row['content'] && ! $row['next_date'] ) {
		return new WP_Error( 'qldn_content', 'Nhập nội dung chăm sóc hoặc ngày hẹn.' );
	}
	// Đã chăm sóc thì các lịch hẹn cũ của khách coi như xong.
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . qldn_t( 'care' ) . ' SET done = 1 WHERE customer_id = %d AND done = 0 AND next_date IS NOT NULL AND next_date <= %s', $cid, qldn_today() ) );
	$wpdb->insert( qldn_t( 'care' ), $row );
	return (int) $wpdb->insert_id;
}

/**
 * Xử lý form khách hàng.
 */
function qldn_handle_customer() {
	qldn_check( 'customer', 'qldn_sales' );
	$id = qldn_in( 'id', 'int', 0 );
	$r  = qldn_customer_save(
		array(
			'name'     => qldn_in( 'name' ),
			'phone'    => qldn_in( 'phone' ),
			'email'    => qldn_in( 'email', 'email' ),
			'address'  => qldn_in( 'address' ),
			'grp'      => qldn_in( 'grp' ),
			'birthday' => qldn_in( 'birthday', 'date' ),
			'source'   => qldn_in( 'source' ),
			'staff_id' => qldn_in( 'staff_id', 'int', 0 ),
			'note'     => qldn_in( 'note', 'textarea' ),
		),
		$id
	);
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'customers', array( 'edit' => $id ? $id : 'new' ) );
	}
	qldn_notice( $id ? 'Đã cập nhật khách hàng.' : 'Đã thêm khách hàng.' );
	qldn_go( 'customers', array( 'view' => $r ) );
}
add_action( 'admin_post_qldn_customer', 'qldn_handle_customer' );

/**
 * Xử lý ghi nhật ký chăm sóc.
 */
function qldn_handle_care() {
	qldn_check( 'care', 'qldn_sales' );
	$cid = qldn_in( 'customer_id', 'int', 0 );
	$r   = qldn_care_add(
		array(
			'customer_id' => $cid,
			'channel'     => qldn_in( 'channel', 'key' ),
			'content'     => qldn_in( 'content', 'textarea' ),
			'result'      => qldn_in( 'result', 'key' ),
			'next_date'   => qldn_in( 'next_date', 'date' ),
			'next_task'   => qldn_in( 'next_task' ),
			'staff_id'    => qldn_in( 'staff_id', 'int', 0 ),
		)
	);
	qldn_notice( is_wp_error( $r ) ? $r->get_error_message() : 'Đã lưu nhật ký chăm sóc.', is_wp_error( $r ) ? 'error' : 'success' );
	if ( 'care' === qldn_in( 'back', 'key' ) ) {
		qldn_go( 'care' );
	}
	qldn_go( 'customers', array( 'view' => $cid ) );
}
add_action( 'admin_post_qldn_care', 'qldn_handle_care' );

/**
 * Đánh dấu lịch hẹn đã xong.
 */
function qldn_handle_care_done() {
	qldn_check( 'care_done', 'qldn_sales' );
	global $wpdb;
	$wpdb->update( qldn_t( 'care' ), array( 'done' => 1 ), array( 'id' => qldn_in( 'id', 'int', 0 ) ) );
	qldn_notice( 'Đã đánh dấu hoàn thành.' );
	qldn_go( 'care' );
}
add_action( 'admin_post_qldn_care_done', 'qldn_handle_care_done' );

/**
 * Xuất CSV khách hàng.
 */
function qldn_export_customers() {
	qldn_check( 'export_customers', 'qldn_sales' );
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . qldn_t( 'customers' ) . ' ORDER BY id' );
	$out  = array();
	foreach ( $rows as $r ) {
		$out[] = array( $r->code, $r->name, $r->phone, $r->email, $r->address, $r->grp, qldn_date( $r->birthday ), $r->source, $r->order_count, $r->total_spent, $r->debt, $r->points, qldn_date( $r->last_order_at ), $r->note );
	}
	qldn_csv( 'khach-hang-' . current_time( 'Y-m-d' ) . '.csv', array( 'Mã KH', 'Tên', 'Điện thoại', 'Email', 'Địa chỉ', 'Nhóm', 'Sinh nhật', 'Nguồn', 'Số đơn', 'Tổng mua', 'Công nợ', 'Điểm', 'Mua gần nhất', 'Ghi chú' ), $out );
}
add_action( 'admin_post_qldn_export_customers', 'qldn_export_customers' );

/**
 * Tìm khách (AJAX) cho trang bán hàng.
 */
function qldn_ajax_customers() {
	if ( ! current_user_can( 'qldn_sales' ) ) {
		wp_send_json_error( null, 403 );
	}
	check_ajax_referer( 'qldn_ajax', 'nonce' );
	global $wpdb;
	$s    = qldn_in( 'q' );
	$rows = $wpdb->get_results( 'SELECT id, code, name, phone, grp, points, debt FROM ' . qldn_t( 'customers' ) . ' WHERE 1=1' . qldn_like( $s, array( 'name', 'phone', 'code' ) ) . ' ORDER BY last_order_at DESC, id DESC LIMIT 15' );
	wp_send_json_success( $rows );
}
add_action( 'wp_ajax_qldn_customers', 'qldn_ajax_customers' );

/**
 * Trang khách hàng.
 */
function qldn_page_customers() {
	global $wpdb;
	$edit = qldn_in( 'edit' );
	$view = qldn_in( 'view', 'int', 0 );
	if ( '' !== $edit ) {
		qldn_customer_form( 'new' === $edit ? null : qldn_get( 'customers', (int) $edit ) );
		return;
	}
	if ( $view ) {
		qldn_customer_view( qldn_get( 'customers', $view ) );
		return;
	}
	$t     = qldn_t( 'customers' );
	$s     = qldn_in( 's' );
	$grp   = qldn_in( 'grp' );
	$where = ' WHERE 1=1' . qldn_like( $s, array( 'name', 'phone', 'code', 'email' ) );
	if ( $grp ) {
		$where .= $wpdb->prepare( ' AND grp = %s', $grp );
	}
	if ( 'debt' === qldn_in( 'filter', 'key' ) ) {
		$where .= ' AND debt > 0';
	}
	$per             = 50;
	list( $paged, $off ) = qldn_paged( $per );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t $where ORDER BY id DESC LIMIT %d OFFSET %d", $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL

	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Khách hàng</h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'customers', array( 'edit' => 'new' ) ) ) . '">Thêm khách hàng</a> <a class="page-title-action" href="' . esc_url( qldn_action_url( 'export_customers' ) ) . '">Xuất Excel (CSV)</a><hr class="wp-header-end">';
	qldn_print_notices();
	$extra  = '<select name="grp"><option value="">Mọi nhóm</option>' . qldn_options_html( qldn_opt_list( 'customer_groups' ), $grp ) . '</select> ';
	$extra .= '<select name="filter">' . qldn_options_html( array( '' => 'Tất cả', 'debt' => 'Đang nợ' ), qldn_in( 'filter', 'key' ) ) . '</select> ';
	qldn_search_box( 'customers', $s, $extra, 'Tên / SĐT / mã KH' );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Mã</th><th>Khách hàng</th><th>Điện thoại</th><th>Nhóm</th><th class="num">Số đơn</th><th class="num">Tổng mua</th><th class="num">Công nợ</th><th class="num">Điểm</th><th>Mua gần nhất</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="9">Chưa có khách hàng.</td></tr>';
	}
	foreach ( $rows as $r ) {
		echo '<tr><td>' . esc_html( $r->code ) . '</td><td><a href="' . esc_url( qldn_url( 'customers', array( 'view' => $r->id ) ) ) . '"><strong>' . esc_html( $r->name ) . '</strong></a></td><td>' . esc_html( $r->phone ) . '</td><td>' . esc_html( $r->grp ) . '</td>';
		echo '<td class="num">' . esc_html( $r->order_count ) . '</td><td class="num">' . esc_html( qldn_money( $r->total_spent, false ) ) . '</td><td class="num">' . ( $r->debt > 0 ? qldn_badge( qldn_money( $r->debt, false ), 'red' ) : '0' ) . '</td><td class="num">' . esc_html( $r->points ) . '</td><td>' . esc_html( qldn_date( $r->last_order_at ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
	echo '</div>';
}

/**
 * Form khách hàng.
 *
 * @param object|null $c Khách.
 */
function qldn_customer_form( $c ) {
	$groups  = qldn_opt_list( 'customer_groups' );
	$sources = array( '' => '—' ) + qldn_opt_list( 'customer_sources' );
	if ( $c && $c->grp && ! isset( $groups[ $c->grp ] ) ) {
		$groups[ $c->grp ] = $c->grp;
	}
	echo '<div class="wrap qldn"><h1>' . ( $c ? 'Sửa khách hàng ' . esc_html( $c->code ) : 'Thêm khách hàng' ) . '</h1>';
	qldn_print_notices();
	qldn_form_open( 'customer' );
	echo '<input type="hidden" name="id" value="' . esc_attr( $c ? $c->id : 0 ) . '"><table class="form-table">';
	qldn_field( 'Tên khách hàng', 'name', $c ? $c->name : '', array( 'required' => true ) );
	qldn_field( 'Điện thoại', 'phone', $c ? $c->phone : '', array( 'type' => 'tel' ) );
	qldn_field( 'Email', 'email', $c ? $c->email : '', array( 'type' => 'email' ) );
	qldn_field( 'Địa chỉ', 'address', $c ? $c->address : '', array( 'class' => 'large-text' ) );
	qldn_field( 'Nhóm', 'grp', $c ? $c->grp : '', array( 'type' => 'select', 'options' => $groups ) );
	qldn_field( 'Ngày sinh', 'birthday', $c ? $c->birthday : '', array( 'type' => 'date', 'class' => '' ) );
	qldn_field( 'Nguồn', 'source', $c ? $c->source : '', array( 'type' => 'select', 'options' => $sources ) );
	qldn_field( 'Nhân viên phụ trách', 'staff_id', $c ? $c->staff_id : 0, array( 'type' => 'select', 'options' => array( 0 => '—' ) + qldn_employee_options() ) );
	qldn_field( 'Ghi chú', 'note', $c ? $c->note : '', array( 'type' => 'textarea' ) );
	echo '</table>';
	submit_button( $c ? 'Cập nhật' : 'Thêm khách hàng' );
	echo '</form></div>';
}

/**
 * Hồ sơ khách: thông tin, lịch sử mua, nhật ký chăm sóc.
 *
 * @param object|null $c Khách.
 */
function qldn_customer_view( $c ) {
	global $wpdb;
	if ( ! $c ) {
		echo '<div class="wrap qldn"><p>Không tìm thấy khách hàng.</p></div>';
		return;
	}
	$orders   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'orders' ) . ' WHERE customer_id = %d ORDER BY id DESC LIMIT 50', $c->id ) );
	$care     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'care' ) . ' WHERE customer_id = %d ORDER BY id DESC LIMIT 100', $c->id ) );
	$emps     = qldn_employee_options( true );
	$channels = qldn_care_channels();
	$results  = qldn_care_results();

	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">' . esc_html( $c->name ) . ' <small>' . esc_html( $c->code ) . '</small></h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'customers', array( 'edit' => $c->id ) ) ) . '">Sửa</a> <a class="page-title-action" href="' . esc_url( qldn_url( 'pos', array( 'customer' => $c->id ) ) ) . '">Tạo đơn bán</a><hr class="wp-header-end">';
	qldn_print_notices();
	echo '<div class="qldn-cards">';
	echo '<div class="qldn-card"><span>Tổng mua</span><strong>' . esc_html( qldn_money( $c->total_spent ) ) . '</strong><em>' . esc_html( $c->order_count ) . ' đơn</em></div>';
	echo '<div class="qldn-card' . ( $c->debt > 0 ? ' qldn-card-warn' : '' ) . '"><span>Công nợ</span><strong>' . esc_html( qldn_money( $c->debt ) ) . '</strong></div>';
	echo '<div class="qldn-card"><span>Điểm tích luỹ</span><strong>' . esc_html( $c->points ) . '</strong></div>';
	echo '<div class="qldn-card"><span>Mua gần nhất</span><strong>' . esc_html( $c->last_order_at ? qldn_date( $c->last_order_at ) : '—' ) . '</strong></div>';
	echo '</div>';

	echo '<div class="qldn-cols"><div class="qldn-col">';
	echo '<h2>Thông tin</h2><table class="widefat qldn-info"><tbody>';
	$info = array(
		'Điện thoại'  => $c->phone,
		'Email'       => $c->email,
		'Địa chỉ'     => $c->address,
		'Nhóm'        => $c->grp,
		'Ngày sinh'   => qldn_date( $c->birthday ),
		'Nguồn'       => $c->source,
		'Phụ trách'   => isset( $emps[ $c->staff_id ] ) ? $emps[ $c->staff_id ] : '',
		'Ngày tạo'    => qldn_date( $c->created_at ),
		'Ghi chú'     => $c->note,
	);
	foreach ( $info as $k => $v ) {
		echo '<tr><th>' . esc_html( $k ) . '</th><td>' . nl2br( esc_html( $v ) ) . '</td></tr>';
	}
	echo '</tbody></table>';

	echo '<h2>Lịch sử mua hàng</h2><table class="widefat striped qldn-table"><thead><tr><th>Mã đơn</th><th>Ngày</th><th class="num">Tổng tiền</th><th class="num">Còn nợ</th><th>Trạng thái</th></tr></thead><tbody>';
	if ( ! $orders ) {
		echo '<tr><td colspan="5">Chưa mua hàng.</td></tr>';
	}
	foreach ( $orders as $o ) {
		echo '<tr><td><a href="' . esc_url( qldn_url( 'orders', array( 'view' => $o->id ) ) ) . '">' . esc_html( $o->code ) . '</a></td><td>' . esc_html( qldn_date( $o->created_at, true ) ) . '</td><td class="num">' . esc_html( qldn_money( $o->total, false ) ) . '</td><td class="num">' . esc_html( qldn_money( max( 0, $o->total - $o->paid ), false ) ) . '</td><td>' . qldn_order_status_badge( $o->status ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table></div>';

	echo '<div class="qldn-col"><h2 id="qldn-care">Ghi nhận chăm sóc</h2>';
	qldn_care_form( $c->id );
	echo '<h2>Nhật ký chăm sóc</h2><ul class="qldn-timeline">';
	if ( ! $care ) {
		echo '<li>Chưa có.</li>';
	}
	foreach ( $care as $r ) {
		$u = $r->user_id ? get_userdata( $r->user_id ) : null;
		echo '<li><div class="qldn-tl-head"><strong>' . esc_html( isset( $channels[ $r->channel ] ) ? $channels[ $r->channel ] : $r->channel ) . '</strong> · ' . esc_html( qldn_date( $r->created_at, true ) ) . ( $u ? ' · ' . esc_html( $u->display_name ) : '' ) . ( $r->result ? ' ' . qldn_badge( $results[ $r->result ] ?? $r->result, 'blue' ) : '' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( $r->content ) {
			echo '<div>' . nl2br( esc_html( $r->content ) ) . '</div>';
		}
		if ( $r->next_date ) {
			echo '<div class="qldn-tl-next">Hẹn ' . esc_html( qldn_date( $r->next_date ) ) . ( $r->next_task ? ': ' . esc_html( $r->next_task ) : '' ) . ' ' . ( $r->done ? qldn_badge( 'Xong', 'green' ) : qldn_badge( 'Chưa làm', 'orange' ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</li>';
	}
	echo '</ul></div></div></div>';
}

/**
 * Form ghi nhận chăm sóc.
 *
 * @param int    $customer_id Khách (0 = chọn trong danh sách).
 * @param string $back        Trang quay về.
 */
function qldn_care_form( $customer_id, $back = '' ) {
	$me = qldn_current_employee();
	qldn_form_open( 'care', array( 'class' => 'qldn-care-form' ) );
	echo '<input type="hidden" name="customer_id" value="' . esc_attr( $customer_id ) . '"><input type="hidden" name="back" value="' . esc_attr( $back ) . '">';
	echo '<p><select name="channel">';
	qldn_options( qldn_care_channels(), 'call' );
	echo '</select> <select name="result">';
	qldn_options( qldn_care_results(), '' );
	echo '</select> <select name="staff_id"><option value="0">Nhân viên…</option>';
	qldn_options( qldn_employee_options(), $me ? $me->id : 0 );
	echo '</select></p><p><textarea name="content" rows="3" class="large-text" placeholder="Nội dung trao đổi, nhu cầu, phản hồi của khách…"></textarea></p>';
	echo '<p>Hẹn chăm sóc tiếp: <input type="date" name="next_date"> <input type="text" name="next_task" class="regular-text" placeholder="Việc cần làm (gọi lại báo giá, giao hàng…)"></p>';
	echo '<p><button class="button button-primary">Lưu</button></p></form>';
}

/**
 * Trang Chăm sóc khách hàng: việc hôm nay, sinh nhật, khách lâu không mua.
 */
function qldn_page_care() {
	global $wpdb;
	$tc    = qldn_t( 'care' );
	$tk    = qldn_t( 'customers' );
	$today = qldn_today();
	$tasks = $wpdb->get_results( $wpdb->prepare( "SELECT r.*, c.name, c.phone, c.code FROM $tc r JOIN $tk c ON c.id = r.customer_id WHERE r.done = 0 AND r.next_date IS NOT NULL AND r.next_date <= %s ORDER BY r.next_date LIMIT 200", gmdate( 'Y-m-d', strtotime( $today . ' +7 days' ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$month = (int) current_time( 'n' );
	$bdays = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tk WHERE birthday IS NOT NULL AND MONTH(birthday) = %d ORDER BY DAY(birthday) LIMIT 200", $month ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$days  = max( 1, (int) qldn_opt( 'inactive_days' ) );
	$since = gmdate( 'Y-m-d H:i:s', strtotime( qldn_now() . ' -' . $days . ' days' ) );
	$idle  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tk WHERE last_order_at IS NOT NULL AND last_order_at < %s ORDER BY total_spent DESC LIMIT 100", $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$debts = $wpdb->get_results( "SELECT * FROM $tk WHERE debt > 0 ORDER BY debt DESC LIMIT 100" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$recent = $wpdb->get_results( "SELECT r.*, c.name, c.code FROM $tc r JOIN $tk c ON c.id = r.customer_id ORDER BY r.id DESC LIMIT 30" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$channels = qldn_care_channels();

	echo '<div class="wrap qldn"><h1>Chăm sóc khách hàng</h1>';
	qldn_print_notices();

	echo '<h2>Lịch hẹn chăm sóc (quá hạn, hôm nay và 7 ngày tới)</h2><table class="widefat striped qldn-table"><thead><tr><th>Ngày hẹn</th><th>Khách hàng</th><th>Điện thoại</th><th>Việc cần làm</th><th></th></tr></thead><tbody>';
	if ( ! $tasks ) {
		echo '<tr><td colspan="5">Không có lịch hẹn.</td></tr>';
	}
	foreach ( $tasks as $r ) {
		$late = $r->next_date < $today;
		$is   = $r->next_date === $today;
		echo '<tr><td>' . ( $late ? qldn_badge( qldn_date( $r->next_date ) . ' – quá hạn', 'red' ) : ( $is ? qldn_badge( 'Hôm nay', 'orange' ) : esc_html( qldn_date( $r->next_date ) ) ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td><a href="' . esc_url( qldn_url( 'customers', array( 'view' => $r->customer_id ) ) ) . '">' . esc_html( $r->name ) . '</a></td><td>' . qldn_phone_link( $r->phone ) . '</td><td>' . esc_html( $r->next_task ? $r->next_task : $r->content ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="qldn-actions"><a href="' . esc_url( qldn_url( 'customers', array( 'view' => $r->customer_id ) ) ) . '#qldn-care">Ghi nhận</a> · <a href="' . esc_url( qldn_action_url( 'care_done', array( 'id' => $r->id ) ) ) . '">Xong</a></td></tr>';
	}
	echo '</tbody></table>';

	echo '<div class="qldn-cols"><div class="qldn-col"><h2>Sinh nhật trong tháng ' . esc_html( $month ) . '</h2><table class="widefat striped qldn-table"><thead><tr><th>Ngày</th><th>Khách hàng</th><th>Điện thoại</th><th>Nhóm</th></tr></thead><tbody>';
	if ( ! $bdays ) {
		echo '<tr><td colspan="4">Không có.</td></tr>';
	}
	foreach ( $bdays as $r ) {
		$is_today = gmdate( 'm-d', strtotime( $r->birthday ) ) === current_time( 'm-d' );
		echo '<tr><td>' . ( $is_today ? qldn_badge( 'Hôm nay', 'orange' ) : esc_html( gmdate( 'd/m', strtotime( $r->birthday ) ) ) ) . '</td><td><a href="' . esc_url( qldn_url( 'customers', array( 'view' => $r->id ) ) ) . '">' . esc_html( $r->name ) . '</a></td><td>' . qldn_phone_link( $r->phone ) . '</td><td>' . esc_html( $r->grp ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';

	echo '<h2>Khách đang nợ</h2><table class="widefat striped qldn-table"><thead><tr><th>Khách hàng</th><th>Điện thoại</th><th class="num">Công nợ</th></tr></thead><tbody>';
	if ( ! $debts ) {
		echo '<tr><td colspan="3">Không có.</td></tr>';
	}
	foreach ( $debts as $r ) {
		echo '<tr><td><a href="' . esc_url( qldn_url( 'customers', array( 'view' => $r->id ) ) ) . '">' . esc_html( $r->name ) . '</a></td><td>' . qldn_phone_link( $r->phone ) . '</td><td class="num">' . esc_html( qldn_money( $r->debt, false ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table></div>';

	echo '<div class="qldn-col"><h2>Khách lâu không quay lại (trên ' . esc_html( $days ) . ' ngày)</h2><table class="widefat striped qldn-table"><thead><tr><th>Khách hàng</th><th>Điện thoại</th><th>Mua gần nhất</th><th class="num">Tổng mua</th></tr></thead><tbody>';
	if ( ! $idle ) {
		echo '<tr><td colspan="4">Không có.</td></tr>';
	}
	foreach ( $idle as $r ) {
		echo '<tr><td><a href="' . esc_url( qldn_url( 'customers', array( 'view' => $r->id ) ) ) . '">' . esc_html( $r->name ) . '</a></td><td>' . qldn_phone_link( $r->phone ) . '</td><td>' . esc_html( qldn_date( $r->last_order_at ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->total_spent, false ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table></div></div>';

	echo '<h2>Hoạt động chăm sóc gần đây</h2><table class="widefat striped qldn-table"><thead><tr><th>Thời gian</th><th>Khách hàng</th><th>Kênh</th><th>Nội dung</th></tr></thead><tbody>';
	if ( ! $recent ) {
		echo '<tr><td colspan="4">Chưa có.</td></tr>';
	}
	foreach ( $recent as $r ) {
		echo '<tr><td>' . esc_html( qldn_date( $r->created_at, true ) ) . '</td><td><a href="' . esc_url( qldn_url( 'customers', array( 'view' => $r->customer_id ) ) ) . '">' . esc_html( $r->name ) . '</a></td><td>' . esc_html( $channels[ $r->channel ] ?? $r->channel ) . '</td><td>' . esc_html( wp_trim_words( $r->content, 25 ) ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * Số điện thoại bấm gọi được trên điện thoại.
 *
 * @param string $phone Số.
 * @return string HTML.
 */
function qldn_phone_link( $phone ) {
	return $phone ? '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>' : '';
}
