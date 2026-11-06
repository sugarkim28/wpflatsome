<?php
/**
 * Plugin Name: Tin Học 119 – Lõi hệ thống
 * Description: Yêu cầu hỗ trợ kỹ thuật, tra cứu bảo hành, thư viện kiến thức, AI soạn bản nháp (có duyệt), SEO & dữ liệu có cấu trúc, thống kê nút Gọi/Zalo, nhật ký quản trị, chuyển hướng 301. Dữ liệu nằm trong plugin nên đổi theme không mất.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Tin Học 119
 * Text Domain: tinhoc119-core
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'TH119_VERSION', '1.0.0' );
define( 'TH119_DB_VERSION', '1' );
define( 'TH119_FILE', __FILE__ );
define( 'TH119_DIR', plugin_dir_path( __FILE__ ) );
define( 'TH119_URL', plugin_dir_url( __FILE__ ) );

$th119_modules = array(
	'helpers',
	'install',
	'settings',
	'content-types',
	'knowledge',
	'tickets',
	'warranty',
	'tracking',
	'ai',
	'ai-admin',
	'seo',
	'redirects',
	'security',
	'activity',
	'pages',
	'dashboard',
	'shortcodes',
);
foreach ( $th119_modules as $th119_module ) {
	require_once TH119_DIR . 'includes/' . $th119_module . '.php';
}
unset( $th119_modules, $th119_module );

register_activation_hook( __FILE__, 'th119_activate' );
register_deactivation_hook( __FILE__, 'th119_deactivate' );
