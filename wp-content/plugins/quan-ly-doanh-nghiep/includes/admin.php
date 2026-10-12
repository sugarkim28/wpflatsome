<?php
/**
 * Menu quản trị và CSS/JS.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các trang: slug => [tiêu đề menu, quyền, hàm hiển thị].
 *
 * @return array
 */
function qldn_pages() {
	return array(
		''           => array( 'Tổng quan', 'qldn_access', 'qldn_page_dashboard' ),
		'pos'        => array( 'Bán hàng', 'qldn_sales', 'qldn_page_pos' ),
		'orders'     => array( 'Đơn hàng', 'qldn_sales', 'qldn_page_orders' ),
		'customers'  => array( 'Khách hàng', 'qldn_sales', 'qldn_page_customers' ),
		'care'       => array( 'Chăm sóc KH', 'qldn_sales', 'qldn_page_care' ),
		'products'   => array( 'Sản phẩm & tồn kho', 'qldn_stock', 'qldn_page_products' ),
		'stock'      => array( 'Nhập / xuất kho', 'qldn_stock', 'qldn_page_stock' ),
		'employees'  => array( 'Nhân viên', 'qldn_hr', 'qldn_page_employees' ),
		'attendance' => array( 'Chấm công', 'qldn_hr', 'qldn_page_attendance' ),
		'payroll'    => array( 'Tính lương', 'qldn_hr', 'qldn_page_payroll' ),
		'reports'    => array( 'Báo cáo', 'qldn_reports', 'qldn_page_reports' ),
		'settings'   => array( 'Cài đặt', 'qldn_settings', 'qldn_page_settings' ),
	);
}

/**
 * Đăng ký menu.
 */
function qldn_admin_menu() {
	add_menu_page( 'Quản lý doanh nghiệp', 'Quản lý DN', 'qldn_access', 'qldn', 'qldn_page_dashboard', 'dashicons-store', 3 );
	foreach ( qldn_pages() as $slug => $p ) {
		add_submenu_page( 'qldn', $p[0], $p[0], $p[1], $slug ? 'qldn-' . $slug : 'qldn', $p[2] );
	}
}
add_action( 'admin_menu', 'qldn_admin_menu' );

/**
 * Người chỉ có quyền của plugin (thu ngân, thủ kho…) đăng nhập xong vào thẳng phần mềm.
 *
 * @param string  $url  URL mặc định.
 * @param string  $req  URL yêu cầu.
 * @param WP_User $user Người dùng.
 * @return string
 */
function qldn_login_redirect( $url, $req, $user ) {
	if ( $user instanceof WP_User && $user->has_cap( 'qldn_access' ) && ! $user->has_cap( 'edit_posts' ) && ( ! $req || admin_url() === $req || false !== strpos( $req, 'wp-admin/profile.php' ) ) ) {
		return qldn_url();
	}
	return $url;
}
add_filter( 'login_redirect', 'qldn_login_redirect', 10, 3 );

/**
 * Nạp CSS/JS trên các trang của plugin.
 *
 * @param string $hook Trang hiện tại.
 */
function qldn_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'qldn' ) ) {
		return;
	}
	$css = QLDN_DIR . 'assets/admin.css';
	$js  = QLDN_DIR . 'assets/admin.js';
	wp_enqueue_style( 'qldn-admin', QLDN_URL . 'assets/admin.css', array(), QLDN_VERSION . '.' . filemtime( $css ) );
	wp_enqueue_script( 'qldn-admin', QLDN_URL . 'assets/admin.js', array(), QLDN_VERSION . '.' . filemtime( $js ), true );
	wp_localize_script(
		'qldn-admin',
		'QLDN',
		array(
			'ajax'          => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'qldn_ajax' ),
			'allowNegative' => (bool) qldn_opt( 'allow_negative' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'qldn_admin_assets' );

/**
 * Lối tắt "Bán hàng" trên thanh quản trị.
 *
 * @param WP_Admin_Bar $bar Thanh quản trị.
 */
function qldn_admin_bar( $bar ) {
	if ( ! current_user_can( 'qldn_sales' ) ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'qldn-pos',
			'title' => '<span class="ab-icon dashicons dashicons-cart" style="top:2px"></span>Bán hàng',
			'href'  => qldn_url( 'pos' ),
		)
	);
}
add_action( 'admin_bar_menu', 'qldn_admin_bar', 80 );
