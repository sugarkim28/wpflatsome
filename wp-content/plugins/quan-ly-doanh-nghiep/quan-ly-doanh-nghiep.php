<?php
/**
 * Plugin Name: Quản lý doanh nghiệp
 * Description: Bán hàng, quản lý kho, khách hàng & chăm sóc khách hàng, nhân viên, chấm công, tính lương – ngay trong trang quản trị WordPress.
 * Version:     1.0.0
 * Author:      Tin Học 119
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: qldn
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

define( 'QLDN_VERSION', '1.0.0' );
define( 'QLDN_DB_VERSION', '1' );
define( 'QLDN_FILE', __FILE__ );
define( 'QLDN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QLDN_URL', plugin_dir_url( __FILE__ ) );

require_once QLDN_DIR . 'includes/install.php';
require_once QLDN_DIR . 'includes/helpers.php';
require_once QLDN_DIR . 'includes/settings.php';
require_once QLDN_DIR . 'includes/inventory.php';
require_once QLDN_DIR . 'includes/customers.php';
require_once QLDN_DIR . 'includes/sales.php';
require_once QLDN_DIR . 'includes/employees.php';
require_once QLDN_DIR . 'includes/attendance.php';
require_once QLDN_DIR . 'includes/payroll.php';
require_once QLDN_DIR . 'includes/reports.php';
require_once QLDN_DIR . 'includes/admin.php';

register_activation_hook( __FILE__, 'qldn_activate' );
