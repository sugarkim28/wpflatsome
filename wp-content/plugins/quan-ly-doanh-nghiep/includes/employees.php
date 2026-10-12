<?php
/**
 * Nhân viên: hồ sơ, lương cơ bản, phụ cấp, hoa hồng, người phụ thuộc, liên kết tài khoản.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hình thức trả lương.
 *
 * @return array
 */
function qldn_salary_types() {
	return array(
		'month' => 'Theo tháng (lương / ngày công chuẩn × ngày công)',
		'day'   => 'Theo ngày (đơn giá × ngày công)',
		'hour'  => 'Theo giờ (đơn giá × giờ làm)',
	);
}

/**
 * Lưu nhân viên.
 *
 * @param array $d  Dữ liệu.
 * @param int   $id ID khi sửa.
 * @return int|WP_Error
 */
function qldn_employee_save( $d, $id = 0 ) {
	global $wpdb;
	$t     = qldn_t( 'employees' );
	$types = qldn_salary_types();
	$row   = array(
		'name'             => isset( $d['name'] ) ? sanitize_text_field( $d['name'] ) : '',
		'phone'            => isset( $d['phone'] ) ? qldn_phone( $d['phone'] ) : '',
		'email'            => isset( $d['email'] ) ? sanitize_email( $d['email'] ) : '',
		'position'         => isset( $d['position'] ) ? sanitize_text_field( $d['position'] ) : '',
		'department'       => isset( $d['department'] ) ? sanitize_text_field( $d['department'] ) : '',
		'salary_type'      => isset( $d['salary_type'], $types[ $d['salary_type'] ] ) ? $d['salary_type'] : 'month',
		'base_salary'      => isset( $d['base_salary'] ) ? max( 0, (int) $d['base_salary'] ) : 0,
		'allowance'        => isset( $d['allowance'] ) ? max( 0, (int) $d['allowance'] ) : 0,
		'insurance_salary' => isset( $d['insurance_salary'] ) ? max( 0, (int) $d['insurance_salary'] ) : 0,
		'commission_rate'  => isset( $d['commission_rate'] ) ? min( 100, max( 0, (float) $d['commission_rate'] ) ) : 0,
		'dependents'       => isset( $d['dependents'] ) ? min( 20, max( 0, (int) $d['dependents'] ) ) : 0,
		'id_number'        => isset( $d['id_number'] ) ? sanitize_text_field( $d['id_number'] ) : '',
		'tax_code'         => isset( $d['tax_code'] ) ? sanitize_text_field( $d['tax_code'] ) : '',
		'bank_account'     => isset( $d['bank_account'] ) ? sanitize_text_field( $d['bank_account'] ) : '',
		'start_date'       => ! empty( $d['start_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['start_date'] ) ? $d['start_date'] : null,
		'user_id'          => isset( $d['user_id'] ) ? (int) $d['user_id'] : 0,
		'status'           => ( isset( $d['status'] ) && 'inactive' === $d['status'] ) ? 'inactive' : 'active',
		'note'             => isset( $d['note'] ) ? sanitize_textarea_field( $d['note'] ) : '',
	);
	if ( '' === $row['name'] ) {
		return new WP_Error( 'qldn_name', 'Chưa nhập tên nhân viên.' );
	}
	if ( $row['user_id'] ) {
		$dup = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $t WHERE user_id = %d AND id <> %d", $row['user_id'], $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $dup ) {
			return new WP_Error( 'qldn_user', 'Tài khoản này đã liên kết với nhân viên ' . $dup . '.' );
		}
	}
	if ( $id ) {
		$wpdb->update( $t, $row, array( 'id' => $id ) );
		return $id;
	}
	$row['created_at'] = qldn_now();
	$wpdb->insert( $t, $row );
	$id = (int) $wpdb->insert_id;
	$wpdb->update( $t, array( 'code' => qldn_code( 'NV', $id, 4 ) ), array( 'id' => $id ) );
	return $id;
}

/**
 * Xử lý form nhân viên.
 */
function qldn_handle_employee() {
	qldn_check( 'employee', 'qldn_hr' );
	$id = qldn_in( 'id', 'int', 0 );
	$r  = qldn_employee_save(
		array(
			'name'             => qldn_in( 'name' ),
			'phone'            => qldn_in( 'phone' ),
			'email'            => qldn_in( 'email', 'email' ),
			'position'         => qldn_in( 'position' ),
			'department'       => qldn_in( 'department' ),
			'salary_type'      => qldn_in( 'salary_type', 'key' ),
			'base_salary'      => qldn_in( 'base_salary', 'money', 0 ),
			'allowance'        => qldn_in( 'allowance', 'money', 0 ),
			'insurance_salary' => qldn_in( 'insurance_salary', 'money', 0 ),
			'commission_rate'  => qldn_in( 'commission_rate', 'qty', 0 ),
			'dependents'       => qldn_in( 'dependents', 'int', 0 ),
			'id_number'        => qldn_in( 'id_number' ),
			'tax_code'         => qldn_in( 'tax_code' ),
			'bank_account'     => qldn_in( 'bank_account' ),
			'start_date'       => qldn_in( 'start_date', 'date' ),
			'user_id'          => qldn_in( 'user_id', 'int', 0 ),
			'status'           => qldn_in( 'status', 'key' ),
			'note'             => qldn_in( 'note', 'textarea' ),
		),
		$id
	);
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'employees', array( 'edit' => $id ? $id : 'new' ) );
	}
	qldn_notice( $id ? 'Đã cập nhật nhân viên.' : 'Đã thêm nhân viên.' );
	qldn_go( 'employees' );
}
add_action( 'admin_post_qldn_employee', 'qldn_handle_employee' );

/**
 * Xuất CSV nhân viên.
 */
function qldn_export_employees() {
	qldn_check( 'export_employees', 'qldn_hr' );
	global $wpdb;
	$rows  = $wpdb->get_results( 'SELECT * FROM ' . qldn_t( 'employees' ) . ' ORDER BY id' );
	$types = array( 'month' => 'Tháng', 'day' => 'Ngày', 'hour' => 'Giờ' );
	$out   = array();
	foreach ( $rows as $r ) {
		$out[] = array( $r->code, $r->name, $r->phone, $r->email, $r->department, $r->position, $types[ $r->salary_type ] ?? $r->salary_type, $r->base_salary, $r->allowance, $r->insurance_salary, (float) $r->commission_rate, $r->dependents, $r->id_number, $r->tax_code, $r->bank_account, qldn_date( $r->start_date ), 'active' === $r->status ? 'Đang làm' : 'Đã nghỉ' );
	}
	qldn_csv( 'nhan-vien-' . current_time( 'Y-m-d' ) . '.csv', array( 'Mã NV', 'Họ tên', 'Điện thoại', 'Email', 'Bộ phận', 'Chức vụ', 'Trả lương theo', 'Lương / đơn giá', 'Phụ cấp', 'Lương đóng BH', 'Hoa hồng %', 'Người phụ thuộc', 'CCCD', 'MST cá nhân', 'Tài khoản ngân hàng', 'Ngày vào làm', 'Trạng thái' ), $out );
}
add_action( 'admin_post_qldn_export_employees', 'qldn_export_employees' );

/**
 * Trang nhân viên.
 */
function qldn_page_employees() {
	global $wpdb;
	$edit = qldn_in( 'edit' );
	if ( '' !== $edit ) {
		qldn_employee_form( 'new' === $edit ? null : qldn_get( 'employees', (int) $edit ) );
		return;
	}
	$t      = qldn_t( 'employees' );
	$s      = qldn_in( 's' );
	$status = qldn_in( 'status', 'key', 'active' );
	$where  = $wpdb->prepare( ' WHERE status = %s', 'inactive' === $status ? 'inactive' : 'active' ) . qldn_like( $s, array( 'name', 'phone', 'code', 'department', 'position' ) );
	$rows   = $wpdb->get_results( "SELECT * FROM $t $where ORDER BY department, name" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$types  = array( 'month' => '/tháng', 'day' => '/ngày', 'hour' => '/giờ' );

	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Nhân viên</h1> <a class="page-title-action" href="' . esc_url( qldn_url( 'employees', array( 'edit' => 'new' ) ) ) . '">Thêm nhân viên</a> <a class="page-title-action" href="' . esc_url( qldn_action_url( 'export_employees' ) ) . '">Xuất Excel (CSV)</a><hr class="wp-header-end">';
	qldn_print_notices();
	qldn_search_box( 'employees', $s, '<select name="status">' . qldn_options_html( array( 'active' => 'Đang làm', 'inactive' => 'Đã nghỉ' ), $status ) . '</select> ', 'Tên / SĐT / bộ phận' );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Mã</th><th>Họ tên</th><th>Bộ phận</th><th>Chức vụ</th><th>Điện thoại</th><th class="num">Lương</th><th class="num">Phụ cấp</th><th class="num">Hoa hồng</th><th>Tài khoản</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="9">Chưa có nhân viên.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$u = $r->user_id ? get_userdata( $r->user_id ) : null;
		echo '<tr><td>' . esc_html( $r->code ) . '</td><td><a href="' . esc_url( qldn_url( 'employees', array( 'edit' => $r->id ) ) ) . '"><strong>' . esc_html( $r->name ) . '</strong></a></td><td>' . esc_html( $r->department ) . '</td><td>' . esc_html( $r->position ) . '</td><td>' . qldn_phone_link( $r->phone ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="num">' . esc_html( qldn_money( $r->base_salary, false ) . ( $types[ $r->salary_type ] ?? '' ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->allowance, false ) ) . '</td><td class="num">' . esc_html( (float) $r->commission_rate ) . '%</td><td>' . esc_html( $u ? $u->user_login : '' ) . '</td></tr>';
	}
	echo '</tbody></table><p class="qldn-count">' . esc_html( count( $rows ) ) . ' nhân viên</p></div>';
}

/**
 * Form nhân viên.
 *
 * @param object|null $e Nhân viên.
 */
function qldn_employee_form( $e ) {
	$users = array( 0 => '— Không liên kết —' );
	foreach ( get_users( array( 'fields' => array( 'ID', 'user_login', 'display_name' ), 'number' => 500 ) ) as $u ) {
		$users[ $u->ID ] = $u->display_name . ' (' . $u->user_login . ')';
	}
	echo '<div class="wrap qldn"><h1>' . ( $e ? 'Sửa nhân viên ' . esc_html( $e->code ) : 'Thêm nhân viên' ) . '</h1>';
	qldn_print_notices();
	qldn_form_open( 'employee' );
	echo '<input type="hidden" name="id" value="' . esc_attr( $e ? $e->id : 0 ) . '">';
	echo '<h2>Thông tin</h2><table class="form-table">';
	qldn_field( 'Họ tên', 'name', $e ? $e->name : '', array( 'required' => true ) );
	qldn_field( 'Điện thoại', 'phone', $e ? $e->phone : '', array( 'type' => 'tel' ) );
	qldn_field( 'Email', 'email', $e ? $e->email : '', array( 'type' => 'email' ) );
	qldn_field( 'Bộ phận', 'department', $e ? $e->department : '' );
	qldn_field( 'Chức vụ', 'position', $e ? $e->position : '' );
	qldn_field( 'Ngày vào làm', 'start_date', $e ? $e->start_date : '', array( 'type' => 'date', 'class' => '' ) );
	qldn_field( 'Số CCCD', 'id_number', $e ? $e->id_number : '' );
	qldn_field( 'Mã số thuế cá nhân', 'tax_code', $e ? $e->tax_code : '' );
	qldn_field( 'Tài khoản ngân hàng', 'bank_account', $e ? $e->bank_account : '', array( 'help' => 'Số tài khoản – ngân hàng (dùng khi chuyển lương).' ) );
	qldn_field( 'Tài khoản đăng nhập', 'user_id', $e ? $e->user_id : 0, array( 'type' => 'select', 'options' => $users, 'help' => 'Liên kết để nhân viên tự chấm công vào/ra ca ở trang Tổng quan và tự điền tên khi bán hàng.' ) );
	qldn_field( 'Trạng thái', 'status', $e ? $e->status : 'active', array( 'type' => 'select', 'options' => array( 'active' => 'Đang làm', 'inactive' => 'Đã nghỉ việc' ) ) );
	echo '</table><h2>Lương</h2><table class="form-table">';
	qldn_field( 'Trả lương theo', 'salary_type', $e ? $e->salary_type : 'month', array( 'type' => 'select', 'options' => qldn_salary_types() ) );
	qldn_field( 'Lương cơ bản / đơn giá', 'base_salary', $e ? $e->base_salary : '', array( 'type' => 'money', 'help' => 'Lương tháng, hoặc đơn giá 1 ngày / 1 giờ tuỳ hình thức trả lương.' ) );
	qldn_field( 'Phụ cấp / tháng', 'allowance', $e ? $e->allowance : '', array( 'type' => 'money', 'help' => 'Ăn trưa, xăng xe, điện thoại… (tính đủ, không chia theo ngày công).' ) );
	qldn_field( 'Lương đóng bảo hiểm', 'insurance_salary', $e ? $e->insurance_salary : '', array( 'type' => 'money', 'help' => 'Để 0 nếu không đóng BHXH (thử việc, cộng tác viên…).' ) );
	qldn_field( 'Hoa hồng bán hàng', 'commission_rate', $e ? (float) $e->commission_rate : '', array( 'class' => 'small-text', 'help' => '% trên doanh thu các đơn hoàn thành do nhân viên này bán trong tháng.' ) );
	qldn_field( 'Số người phụ thuộc', 'dependents', $e ? $e->dependents : 0, array( 'type' => 'number', 'class' => 'small-text', 'help' => 'Đã đăng ký giảm trừ gia cảnh (tính thuế TNCN).' ) );
	qldn_field( 'Ghi chú', 'note', $e ? $e->note : '', array( 'type' => 'textarea' ) );
	echo '</table>';
	submit_button( $e ? 'Cập nhật' : 'Thêm nhân viên' );
	echo '</form></div>';
}
