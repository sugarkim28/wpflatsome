<?php
/**
 * Gỡ plugin: chỉ xoá dữ liệu khi người dùng chọn "Xoá toàn bộ dữ liệu phần mềm" trong Cài đặt.
 *
 * @package QLDN
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$qldn_settings = (array) get_option( 'qldn_settings', array() );
if ( empty( $qldn_settings['delete_on_uninstall'] ) ) {
	return; // Giữ nguyên dữ liệu: cài lại là dùng tiếp.
}

global $wpdb;
$qldn_tables = array( 'products', 'stock_moves', 'customers', 'care', 'orders', 'order_items', 'employees', 'attendance', 'payroll', 'punches', 'quotes', 'quote_items', 'contracts', 'templates', 'documents', 'cashbook', 'suppliers', 'purchases', 'shifts', 'requests', 'logs' );
foreach ( $qldn_tables as $qldn_t ) {
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'qldn_' . $qldn_t ); // phpcs:ignore WordPress.DB.PreparedSQL
}
foreach ( array( 'qldn_settings', 'qldn_db_version', 'qldn_devices', 'qldn_api_key', 'qldn_templates_seeded', 'qldn_flush_rewrite' ) as $qldn_o ) {
	delete_option( $qldn_o );
}
foreach ( array( 'qldn_manager', 'qldn_cashier', 'qldn_storekeeper', 'qldn_accountant', 'qldn_staff' ) as $qldn_r ) {
	remove_role( $qldn_r );
}
$qldn_admin = get_role( 'administrator' );
if ( $qldn_admin ) {
	foreach ( array( 'qldn_access', 'qldn_sales', 'qldn_cash', 'qldn_docs', 'qldn_stock', 'qldn_hr', 'qldn_reports', 'qldn_settings' ) as $qldn_c ) {
		$qldn_admin->remove_cap( $qldn_c );
	}
}
