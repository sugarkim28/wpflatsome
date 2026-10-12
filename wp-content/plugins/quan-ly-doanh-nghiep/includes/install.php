<?php
/**
 * Cài đặt: tạo bảng dữ liệu, quyền và vai trò người dùng.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quyền của plugin => tên hiển thị.
 *
 * @return array
 */
function qldn_caps() {
	return array(
		'qldn_access'   => 'Vào phần mềm (xem tổng quan, tự chấm công)',
		'qldn_sales'    => 'Bán hàng, khách hàng, chăm sóc khách hàng',
		'qldn_stock'    => 'Sản phẩm, nhập/xuất/kiểm kho',
		'qldn_hr'       => 'Nhân viên, chấm công, tính lương',
		'qldn_reports'  => 'Báo cáo doanh thu, lợi nhuận',
		'qldn_settings' => 'Cài đặt phần mềm',
	);
}

/**
 * Vai trò mặc định => (tên, quyền).
 *
 * @return array
 */
function qldn_roles() {
	return array(
		'qldn_manager'    => array( 'Quản lý cửa hàng', array_keys( qldn_caps() ) ),
		'qldn_cashier'    => array( 'Nhân viên bán hàng', array( 'qldn_access', 'qldn_sales' ) ),
		'qldn_storekeeper' => array( 'Thủ kho', array( 'qldn_access', 'qldn_stock' ) ),
		'qldn_accountant' => array( 'Kế toán - nhân sự', array( 'qldn_access', 'qldn_hr', 'qldn_reports' ) ),
		'qldn_staff'      => array( 'Nhân viên (chỉ chấm công)', array( 'qldn_access' ) ),
	);
}

/**
 * Tạo / cập nhật bảng (dbDelta chỉ thêm cột, không xoá dữ liệu).
 */
function qldn_install_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	$p = $wpdb->prefix . 'qldn_';

	$sql = array();

	$sql[] = "CREATE TABLE {$p}products (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		sku varchar(64) NOT NULL DEFAULT '',
		name varchar(255) NOT NULL DEFAULT '',
		category varchar(100) NOT NULL DEFAULT '',
		unit varchar(30) NOT NULL DEFAULT '',
		cost_price bigint(20) NOT NULL DEFAULT 0,
		sale_price bigint(20) NOT NULL DEFAULT 0,
		stock decimal(14,2) NOT NULL DEFAULT 0,
		min_stock decimal(14,2) NOT NULL DEFAULT 0,
		status varchar(20) NOT NULL DEFAULT 'active',
		note text NULL,
		created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY sku (sku),
		KEY status (status)
	) $c;";

	$sql[] = "CREATE TABLE {$p}stock_moves (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		product_id bigint(20) unsigned NOT NULL DEFAULT 0,
		type varchar(20) NOT NULL DEFAULT '',
		qty decimal(14,2) NOT NULL DEFAULT 0,
		stock_after decimal(14,2) NOT NULL DEFAULT 0,
		unit_cost bigint(20) NOT NULL DEFAULT 0,
		ref varchar(64) NOT NULL DEFAULT '',
		partner varchar(255) NOT NULL DEFAULT '',
		note text NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY product_id (product_id),
		KEY type (type),
		KEY created_at (created_at)
	) $c;";

	$sql[] = "CREATE TABLE {$p}customers (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		code varchar(32) NOT NULL DEFAULT '',
		name varchar(255) NOT NULL DEFAULT '',
		phone varchar(20) NOT NULL DEFAULT '',
		email varchar(100) NOT NULL DEFAULT '',
		address varchar(255) NOT NULL DEFAULT '',
		grp varchar(30) NOT NULL DEFAULT '',
		birthday date NULL,
		source varchar(100) NOT NULL DEFAULT '',
		staff_id bigint(20) unsigned NOT NULL DEFAULT 0,
		note text NULL,
		total_spent bigint(20) NOT NULL DEFAULT 0,
		debt bigint(20) NOT NULL DEFAULT 0,
		points int(11) NOT NULL DEFAULT 0,
		order_count int(11) NOT NULL DEFAULT 0,
		last_order_at datetime NULL,
		created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY phone (phone),
		KEY grp (grp)
	) $c;";

	$sql[] = "CREATE TABLE {$p}care (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
		staff_id bigint(20) unsigned NOT NULL DEFAULT 0,
		channel varchar(20) NOT NULL DEFAULT '',
		content text NULL,
		result varchar(30) NOT NULL DEFAULT '',
		next_date date NULL,
		next_task varchar(255) NOT NULL DEFAULT '',
		done tinyint(1) NOT NULL DEFAULT 0,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY customer_id (customer_id),
		KEY next_date (next_date)
	) $c;";

	$sql[] = "CREATE TABLE {$p}orders (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		code varchar(32) NOT NULL DEFAULT '',
		customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
		staff_id bigint(20) unsigned NOT NULL DEFAULT 0,
		subtotal bigint(20) NOT NULL DEFAULT 0,
		discount bigint(20) NOT NULL DEFAULT 0,
		total bigint(20) NOT NULL DEFAULT 0,
		cost_total bigint(20) NOT NULL DEFAULT 0,
		paid bigint(20) NOT NULL DEFAULT 0,
		payment varchar(20) NOT NULL DEFAULT '',
		status varchar(20) NOT NULL DEFAULT 'done',
		note text NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY code (code),
		KEY customer_id (customer_id),
		KEY staff_id (staff_id),
		KEY created_at (created_at)
	) $c;";

	$sql[] = "CREATE TABLE {$p}order_items (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		order_id bigint(20) unsigned NOT NULL DEFAULT 0,
		product_id bigint(20) unsigned NOT NULL DEFAULT 0,
		name varchar(255) NOT NULL DEFAULT '',
		qty decimal(14,2) NOT NULL DEFAULT 0,
		price bigint(20) NOT NULL DEFAULT 0,
		cost bigint(20) NOT NULL DEFAULT 0,
		line_total bigint(20) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY order_id (order_id),
		KEY product_id (product_id)
	) $c;";

	$sql[] = "CREATE TABLE {$p}employees (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		code varchar(32) NOT NULL DEFAULT '',
		name varchar(255) NOT NULL DEFAULT '',
		phone varchar(20) NOT NULL DEFAULT '',
		email varchar(100) NOT NULL DEFAULT '',
		position varchar(100) NOT NULL DEFAULT '',
		department varchar(100) NOT NULL DEFAULT '',
		salary_type varchar(10) NOT NULL DEFAULT 'month',
		base_salary bigint(20) NOT NULL DEFAULT 0,
		allowance bigint(20) NOT NULL DEFAULT 0,
		insurance_salary bigint(20) NOT NULL DEFAULT 0,
		commission_rate decimal(5,2) NOT NULL DEFAULT 0,
		dependents tinyint(3) unsigned NOT NULL DEFAULT 0,
		id_number varchar(20) NOT NULL DEFAULT '',
		tax_code varchar(20) NOT NULL DEFAULT '',
		bank_account varchar(100) NOT NULL DEFAULT '',
		start_date date NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		device_pin varchar(20) NOT NULL DEFAULT '',
		status varchar(20) NOT NULL DEFAULT 'active',
		note text NULL,
		created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY device_pin (device_pin),
		KEY status (status)
	) $c;";

	$sql[] = "CREATE TABLE {$p}attendance (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		employee_id bigint(20) unsigned NOT NULL DEFAULT 0,
		work_date date NOT NULL DEFAULT '0000-00-00',
		mark varchar(4) NOT NULL DEFAULT '',
		check_in time NULL,
		check_out time NULL,
		ot_hours decimal(5,2) NOT NULL DEFAULT 0,
		note varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		UNIQUE KEY emp_date (employee_id,work_date),
		KEY work_date (work_date)
	) $c;";

	$sql[] = "CREATE TABLE {$p}payroll (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		period char(7) NOT NULL DEFAULT '',
		employee_id bigint(20) unsigned NOT NULL DEFAULT 0,
		standard_days decimal(5,2) NOT NULL DEFAULT 0,
		work_days decimal(5,2) NOT NULL DEFAULT 0,
		paid_days decimal(5,2) NOT NULL DEFAULT 0,
		ot_hours decimal(6,2) NOT NULL DEFAULT 0,
		base_salary bigint(20) NOT NULL DEFAULT 0,
		time_pay bigint(20) NOT NULL DEFAULT 0,
		ot_pay bigint(20) NOT NULL DEFAULT 0,
		allowance bigint(20) NOT NULL DEFAULT 0,
		sales bigint(20) NOT NULL DEFAULT 0,
		commission bigint(20) NOT NULL DEFAULT 0,
		bonus bigint(20) NOT NULL DEFAULT 0,
		gross bigint(20) NOT NULL DEFAULT 0,
		insurance bigint(20) NOT NULL DEFAULT 0,
		taxable bigint(20) NOT NULL DEFAULT 0,
		pit bigint(20) NOT NULL DEFAULT 0,
		advance bigint(20) NOT NULL DEFAULT 0,
		deduction bigint(20) NOT NULL DEFAULT 0,
		net bigint(20) NOT NULL DEFAULT 0,
		status varchar(20) NOT NULL DEFAULT 'draft',
		note varchar(255) NOT NULL DEFAULT '',
		updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		UNIQUE KEY period_emp (period,employee_id)
	) $c;";

	// Dữ liệu thô từ máy chấm công (mỗi lần quẹt vân tay / thẻ / khuôn mặt là 1 dòng).
	$sql[] = "CREATE TABLE {$p}punches (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		pin varchar(20) NOT NULL DEFAULT '',
		punch_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		employee_id bigint(20) unsigned NOT NULL DEFAULT 0,
		device_sn varchar(64) NOT NULL DEFAULT '',
		state varchar(4) NOT NULL DEFAULT '',
		verify varchar(4) NOT NULL DEFAULT '',
		source varchar(10) NOT NULL DEFAULT '',
		created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		UNIQUE KEY pin_time (pin,punch_time),
		KEY employee_time (employee_id,punch_time),
		KEY punch_time (punch_time)
	) $c;";

	foreach ( $sql as $q ) {
		dbDelta( $q );
	}
}

/**
 * Thêm vai trò + gán toàn bộ quyền cho quản trị viên.
 */
function qldn_install_roles() {
	foreach ( qldn_roles() as $role => $def ) {
		remove_role( $role );
		add_role( $role, $def[0], array_fill_keys( array_merge( array( 'read' ), $def[1] ), true ) );
	}
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( array_keys( qldn_caps() ) as $cap ) {
			$admin->add_cap( $cap );
		}
	}
}

/**
 * Kích hoạt plugin.
 */
function qldn_activate() {
	qldn_install_tables();
	qldn_install_roles();
	update_option( 'qldn_db_version', QLDN_DB_VERSION );
	update_option( 'qldn_flush_rewrite', 1 ); // Đường dẫn /iclock/ cho máy chấm công.
}

/**
 * Cập nhật plugin bằng cách chép đè file (không qua kích hoạt lại) vẫn tạo đủ bảng.
 */
function qldn_maybe_upgrade() {
	if ( get_option( 'qldn_db_version' ) !== QLDN_DB_VERSION ) {
		qldn_activate();
	}
}
add_action( 'plugins_loaded', 'qldn_maybe_upgrade' );
