<?php
/**
 * Plugin Name: Quản lý doanh nghiệp
 * Description: Bán hàng, sổ quỹ, nhà cung cấp, báo giá, hợp đồng, soạn hồ sơ, quản lý kho, khách hàng & chăm sóc khách hàng, nhân viên, đơn từ & nghỉ phép, ca làm, chấm công, tính lương, cổng nhân viên – ngay trong trang quản trị WordPress.
 * Version:     2.0.0
 * Author:      Tin Học 119
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: qldn
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

define( 'QLDN_VERSION', '2.0.0' );
define( 'QLDN_DB_VERSION', '4' );
define( 'QLDN_FILE', __FILE__ );
define( 'QLDN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QLDN_URL', plugin_dir_url( __FILE__ ) );

require_once QLDN_DIR . 'includes/install.php';
require_once QLDN_DIR . 'includes/helpers.php';
require_once QLDN_DIR . 'includes/logs.php';
require_once QLDN_DIR . 'includes/settings.php';
require_once QLDN_DIR . 'includes/inventory.php';
require_once QLDN_DIR . 'includes/customers.php';
require_once QLDN_DIR . 'includes/sales.php';
require_once QLDN_DIR . 'includes/employees.php';
require_once QLDN_DIR . 'includes/attendance.php';
require_once QLDN_DIR . 'includes/shifts.php';
require_once QLDN_DIR . 'includes/devices.php';
require_once QLDN_DIR . 'includes/requests.php';
require_once QLDN_DIR . 'includes/payroll.php';
require_once QLDN_DIR . 'includes/documents.php';
require_once QLDN_DIR . 'includes/templates-default.php';
require_once QLDN_DIR . 'includes/quotes.php';
require_once QLDN_DIR . 'includes/contracts.php';
require_once QLDN_DIR . 'includes/cashbook.php';
require_once QLDN_DIR . 'includes/portal.php';
require_once QLDN_DIR . 'includes/reports.php';
require_once QLDN_DIR . 'includes/demo.php';
require_once QLDN_DIR . 'includes/admin.php';

register_activation_hook( __FILE__, 'qldn_activate' );
