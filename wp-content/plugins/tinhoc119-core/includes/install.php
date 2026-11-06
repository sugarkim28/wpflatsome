<?php
/**
 * Cài đặt: tạo bảng dữ liệu, quyền, thư mục lưu ảnh riêng tư.
 * Không xoá hay sửa dữ liệu cũ của website.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách quyền riêng của plugin và vai trò được cấp.
 *
 * @return array
 */
function th119_caps_map() {
	return array(
		'th119_manage_tickets'  => array( 'administrator', 'editor', 'th119_technician' ),
		'th119_manage_warranty' => array( 'administrator', 'editor', 'th119_technician' ),
		'th119_view_reports'    => array( 'administrator', 'editor' ),
		'th119_use_ai'          => array( 'administrator', 'editor', 'author' ),
		'th119_approve_ai'      => array( 'administrator', 'editor' ),
		'th119_manage_settings' => array( 'administrator' ),
	);
}

/**
 * Tạo / cập nhật bảng.
 */
function th119_install_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();

	$sql = array();

	$sql[] = 'CREATE TABLE ' . th119_table( 'tickets' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		code varchar(20) NOT NULL,
		name varchar(120) NOT NULL DEFAULT '',
		phone varchar(20) NOT NULL DEFAULT '',
		email varchar(120) NOT NULL DEFAULT '',
		device varchar(120) NOT NULL DEFAULT '',
		issue text NOT NULL,
		area varchar(120) NOT NULL DEFAULT '',
		contact_time varchar(120) NOT NULL DEFAULT '',
		request_type varchar(20) NOT NULL DEFAULT 'support',
		appointment varchar(40) NOT NULL DEFAULT '',
		images text NULL,
		status varchar(20) NOT NULL DEFAULT 'new',
		assigned_to bigint(20) unsigned NOT NULL DEFAULT 0,
		source_url varchar(255) NOT NULL DEFAULT '',
		channel varchar(40) NOT NULL DEFAULT '',
		ip_hash varchar(32) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY code (code),
		KEY status (status),
		KEY phone (phone),
		KEY created_at (created_at)
	) $c;";

	$sql[] = 'CREATE TABLE ' . th119_table( 'warranty' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		code varchar(30) NOT NULL,
		serial varchar(80) NOT NULL DEFAULT '',
		customer_name varchar(120) NOT NULL DEFAULT '',
		phone varchar(20) NOT NULL DEFAULT '',
		device varchar(160) NOT NULL DEFAULT '',
		service varchar(255) NOT NULL DEFAULT '',
		received_at date NULL,
		returned_at date NULL,
		warranty_months smallint(5) unsigned NOT NULL DEFAULT 0,
		warranty_until date NULL,
		status varchar(20) NOT NULL DEFAULT 'received',
		public_note text NULL,
		internal_note text NULL,
		ticket_id bigint(20) unsigned NOT NULL DEFAULT 0,
		created_by bigint(20) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY code (code),
		KEY serial (serial),
		KEY phone (phone)
	) $c;";

	$sql[] = 'CREATE TABLE ' . th119_table( 'logs' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		object_type varchar(20) NOT NULL,
		object_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		status_from varchar(20) NOT NULL DEFAULT '',
		status_to varchar(20) NOT NULL DEFAULT '',
		note text NULL,
		is_public tinyint(1) NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY object (object_type,object_id)
	) $c;";

	$sql[] = 'CREATE TABLE ' . th119_table( 'stats' ) . " (
		day date NOT NULL,
		metric varchar(32) NOT NULL,
		dim varchar(191) NOT NULL DEFAULT '',
		hits int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (day,metric,dim)
	) $c;";

	$sql[] = 'CREATE TABLE ' . th119_table( 'ai_log' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		provider varchar(30) NOT NULL DEFAULT '',
		model varchar(80) NOT NULL DEFAULT '',
		task varchar(40) NOT NULL DEFAULT '',
		prompt text NULL,
		output longtext NULL,
		tokens_in int(10) unsigned NOT NULL DEFAULT 0,
		tokens_out int(10) unsigned NOT NULL DEFAULT 0,
		cost decimal(10,5) NOT NULL DEFAULT 0,
		post_id bigint(20) unsigned NOT NULL DEFAULT 0,
		status varchar(20) NOT NULL DEFAULT 'ok',
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY created_at (created_at),
		KEY post_id (post_id)
	) $c;";

	$sql[] = 'CREATE TABLE ' . th119_table( 'activity' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		action varchar(60) NOT NULL,
		object varchar(191) NOT NULL DEFAULT '',
		detail text NULL,
		ip_hash varchar(32) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY created_at (created_at),
		KEY user_id (user_id)
	) $c;";

	$sql[] = 'CREATE TABLE ' . th119_table( 'redirects' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		source varchar(191) NOT NULL,
		target varchar(255) NOT NULL,
		code smallint(3) unsigned NOT NULL DEFAULT 301,
		hits int(10) unsigned NOT NULL DEFAULT 0,
		last_hit datetime NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY source (source)
	) $c;";

	$sql[] = 'CREATE TABLE ' . th119_table( 'notfound' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		url varchar(191) NOT NULL,
		referrer varchar(255) NOT NULL DEFAULT '',
		hits int(10) unsigned NOT NULL DEFAULT 1,
		last_seen datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY url (url)
	) $c;";

	foreach ( $sql as $q ) {
		dbDelta( $q );
	}
	update_option( 'th119_db_version', TH119_DB_VERSION, false );
}

/**
 * Quyền và vai trò Kỹ thuật viên.
 */
function th119_install_roles() {
	if ( ! get_role( 'th119_technician' ) ) {
		add_role(
			'th119_technician',
			'Kỹ thuật viên',
			array(
				'read'         => true,
				'upload_files' => true,
			)
		);
	}
	foreach ( th119_caps_map() as $cap => $roles ) {
		foreach ( $roles as $r ) {
			$role = get_role( $r );
			if ( $role && ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}
}

/**
 * Thư mục lưu ảnh lỗi khách gửi – chặn truy cập trực tiếp.
 *
 * @return string Đường dẫn thư mục.
 */
function th119_private_dir() {
	$up  = wp_upload_dir( null, false );
	$dir = trailingslashit( $up['basedir'] ) . 'th119-private';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		// Apache. Với nginx xem tài liệu: location ~* /uploads/th119-private/ { deny all; }.
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php\n// Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	return $dir;
}

/**
 * Kích hoạt plugin.
 */
function th119_activate() {
	th119_install_tables();
	th119_install_roles();
	th119_private_dir();
	if ( false === get_option( 'th119_settings' ) ) {
		add_option( 'th119_settings', array(), '', true );
	}
	if ( function_exists( 'th119_register_content_types' ) ) {
		th119_register_content_types();
	}
	flush_rewrite_rules();
	if ( ! wp_next_scheduled( 'th119_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'th119_daily' );
	}
}

/**
 * Tắt plugin (giữ nguyên dữ liệu).
 */
function th119_deactivate() {
	wp_clear_scheduled_hook( 'th119_daily' );
	flush_rewrite_rules();
}

/**
 * Tự nâng cấp bảng khi cập nhật plugin (không cần tắt/bật lại).
 */
function th119_maybe_upgrade() {
	if ( get_option( 'th119_db_version' ) !== TH119_DB_VERSION ) {
		th119_install_tables();
		th119_install_roles();
		th119_private_dir();
	}
	if ( ! wp_next_scheduled( 'th119_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'th119_daily' );
	}
}
add_action( 'admin_init', 'th119_maybe_upgrade' );

/**
 * Dọn dẹp hằng ngày: nhật ký 404 cũ, nhật ký hoạt động > 180 ngày, cập nhật trạng thái hết bảo hành.
 */
function th119_daily_cleanup() {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . th119_table( 'notfound' ) . ' WHERE last_seen < %s AND hits < 3', gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . th119_table( 'activity' ) . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', time() - 180 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . th119_table( 'warranty' ) . " SET status = 'expired', updated_at = %s WHERE status = 'active' AND warranty_until IS NOT NULL AND warranty_until < %s", th119_now(), current_time( 'Y-m-d' ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
}
add_action( 'th119_daily', 'th119_daily_cleanup' );
