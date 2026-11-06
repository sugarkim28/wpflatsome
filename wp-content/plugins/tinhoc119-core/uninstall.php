<?php
/**
 * Gỡ plugin: mặc định GIỮ dữ liệu (yêu cầu hỗ trợ, bảo hành, lịch sử).
 * Chỉ xoá khi quản trị viên bật "Xoá toàn bộ dữ liệu plugin khi gỡ plugin" trong Cài đặt → Bảo mật.
 *
 * @package Tinhoc119_Core
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$th119_settings = (array) get_option( 'th119_settings', array() );
if ( empty( $th119_settings['delete_on_uninstall'] ) ) {
	return;
}
global $wpdb;
foreach ( array( 'tickets', 'warranty', 'logs', 'stats', 'ai_log', 'activity', 'redirects', 'notfound' ) as $th119_t ) {
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'th119_' . $th119_t ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
}
foreach ( array( 'th119_settings', 'th119_ai', 'th119_db_version', 'th119_rewrite_version', 'th119_php_errors' ) as $th119_o ) {
	delete_option( $th119_o );
}
remove_role( 'th119_technician' );
