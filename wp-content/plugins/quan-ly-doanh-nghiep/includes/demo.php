<?php
/**
 * Dữ liệu mẫu để dùng thử: nhân viên, ca, hàng hoá, nhà cung cấp, khách hàng, đơn hàng, chấm công,
 * đơn từ, báo giá, hợp đồng, sổ quỹ. Chỉ chạy khi phần mềm chưa có dữ liệu.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Phần mềm còn trống (chưa có sản phẩm, nhân viên, đơn hàng).
 *
 * @return bool
 */
function qldn_is_empty() {
	global $wpdb;
	foreach ( array( 'products', 'employees', 'orders', 'customers' ) as $t ) {
		if ( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . qldn_t( $t ) ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Tạo dữ liệu mẫu.
 *
 * @return true|WP_Error
 */
function qldn_demo_create() {
	global $wpdb;
	if ( ! qldn_is_empty() ) {
		return new WP_Error( 'qldn_demo', 'Đã có dữ liệu – chỉ tạo dữ liệu mẫu khi phần mềm còn trống.' );
	}
	mt_srand( 119 );
	add_filter( 'pre_wp_mail', '__return_false' ); // Không gửi email thông báo đơn từ khi tạo dữ liệu mẫu.
	$today = qldn_today();
	$year  = current_time( 'Y' );

	$o = (array) get_option( 'qldn_settings', array() );
	$o = array_merge(
		$o,
		array(
			'company_name'      => 'Công ty TNHH Mẫu Việt',
			'company_address'   => '12 Lê Lợi, phường Bến Nghé, TP. Hồ Chí Minh',
			'company_phone'     => '028 3822 0000',
			'company_tax_code'  => '0312345678',
			'company_rep'       => 'Nguyễn Văn An',
			'company_rep_title' => 'Giám đốc',
			'company_bank'      => '0071000123456 tại Vietcombank – CN TP.HCM',
			'holidays'          => "$year-01-01 | Tết Dương lịch\n$year-04-30 | Ngày Chiến thắng\n$year-05-01 | Quốc tế Lao động\n$year-09-02 | Quốc khánh\n$year-09-01 | Quốc khánh (ngày liền kề)",
		)
	);
	update_option( 'qldn_settings', $o, false );

	$wpdb->insert( qldn_t( 'shifts' ), array( 'name' => 'Ca chiều', 'start_time' => '13:00:00', 'end_time' => '21:30:00', 'break_min' => 30 ) );
	$evening = (int) $wpdb->insert_id;
	$wpdb->insert( qldn_t( 'shifts' ), array( 'name' => 'Ca đêm (bảo vệ)', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'break_min' => 0 ) );
	$night = (int) $wpdb->insert_id;

	// Nhân viên.
	$people = array(
		array( 'Nguyễn Văn An', 'Ban giám đốc', 'Giám đốc', 30000000, 0, 0 ),
		array( 'Trần Thị Bình', 'Kế toán – Nhân sự', 'Kế toán trưởng', 18000000, 0, 0 ),
		array( 'Lê Minh Châu', 'Kinh doanh', 'Trưởng nhóm kinh doanh', 15000000, 2, 0 ),
		array( 'Phạm Thu Dung', 'Kinh doanh', 'Nhân viên bán hàng', 8000000, 2, 0 ),
		array( 'Hoàng Văn Em', 'Kinh doanh', 'Nhân viên bán hàng', 8000000, 2, $evening ),
		array( 'Vũ Thị Giang', 'Kho', 'Thủ kho', 9000000, 0, 0 ),
		array( 'Đỗ Văn Hùng', 'Kho', 'Nhân viên giao hàng', 7500000, 0, 0 ),
		array( 'Bùi Văn Khoa', 'Hành chính', 'Bảo vệ', 7000000, 0, $night ),
	);
	$emp = array();
	foreach ( $people as $i => $p ) {
		$emp[ $i ] = qldn_employee_save(
			array(
				'name'             => $p[0],
				'department'       => $p[1],
				'position'         => $p[2],
				'base_salary'      => $p[3],
				'allowance'        => 730000,
				'insurance_salary' => $p[3],
				'commission_rate'  => $p[4],
				'shift_id'         => $p[5],
				'dependents'       => $i % 3,
				'device_pin'       => (string) ( $i + 1 ),
				'phone'            => '09' . str_pad( (string) ( 1234500 + $i ), 8, '0', STR_PAD_LEFT ),
				'birthday'         => ( 1980 + $i * 2 ) . '-0' . ( $i % 9 + 1 ) . '-15',
				'gender'           => $i % 2 ? 'Nữ' : 'Nam',
				'address'          => 'TP. Hồ Chí Minh',
				'id_number'        => '0790' . str_pad( (string) ( 90000000 + $i ), 8, '0', STR_PAD_LEFT ),
				'start_date'       => ( (int) $year - ( 7 - $i ) ) . '-03-01',
				'user_id'          => 0 === $i ? get_current_user_id() : 0,
			)
		);
	}
	// Quản lý trực tiếp (người duyệt đơn).
	foreach ( array( 3 => 2, 4 => 2, 6 => 5, 7 => 1, 2 => 0, 5 => 0, 1 => 0 ) as $i => $m ) {
		$wpdb->update( qldn_t( 'employees' ), array( 'manager_id' => $emp[ $m ] ), array( 'id' => $emp[ $i ] ) );
	}

	// Nhà cung cấp, hàng hoá, phiếu nhập.
	$sup1     = qldn_supplier_save( array( 'name' => 'Công ty CP Thiết bị Văn phòng Sài Gòn', 'phone' => '0283811111', 'tax_code' => '0301111111' ) );
	$sup2     = qldn_supplier_save( array( 'name' => 'Công ty TNHH Giấy Bình Dương', 'phone' => '0274381111', 'tax_code' => '3701111111' ) );
	$products = array(
		array( 'Giấy A4 Double A 80gsm', 'Giấy', 'ram', 62000, 78000, $sup2 ),
		array( 'Giấy A3 IK Plus 70gsm', 'Giấy', 'ram', 115000, 145000, $sup2 ),
		array( 'Bút bi Thiên Long TL-027', 'Văn phòng phẩm', 'hộp', 52000, 70000, $sup1 ),
		array( 'Bìa còng 7cm Kingjim', 'Văn phòng phẩm', 'cái', 38000, 55000, $sup1 ),
		array( 'Mực in HP 12A', 'Mực in', 'hộp', 180000, 260000, $sup1 ),
		array( 'Mực in Canon 337', 'Mực in', 'hộp', 210000, 290000, $sup1 ),
		array( 'Máy tính Casio FX-580', 'Thiết bị', 'cái', 520000, 690000, $sup1 ),
		array( 'Máy huỷ tài liệu Silicon PS-812C', 'Thiết bị', 'cái', 2800000, 3590000, $sup1 ),
		array( 'Kẹp giấy 32mm', 'Văn phòng phẩm', 'hộp', 8000, 12000, $sup1 ),
		array( 'Băng keo trong 5cm', 'Văn phòng phẩm', 'cuộn', 11000, 16000, $sup1 ),
	);
	$pids     = array();
	foreach ( $products as $p ) {
		$pids[] = qldn_product_save( array( 'name' => $p[0], 'category' => $p[1], 'unit' => $p[2], 'cost_price' => $p[3], 'sale_price' => $p[4], 'min_stock' => 5 ) );
	}
	foreach ( array( $sup1, $sup2 ) as $k => $sid ) {
		$lines = array();
		foreach ( $products as $i => $p ) {
			if ( $p[5] === $sid ) {
				$lines[] = array( 'product_id' => $pids[ $i ], 'qty' => 'Thiết bị' === $p[1] ? 40 : 200, 'unit_cost' => $p[3] );
			}
		}
		$ref = qldn_stock_voucher( 'nhap', $lines, array( 'partner' => qldn_get( 'suppliers', $sid )->name, 'ref' => 'PN-MAU-' . ( $k + 1 ) ) );
		qldn_purchase_record( $ref, $sid, $lines, $k ? 0 : 5000000, 'bank' );
	}

	// Sổ quỹ đầu kỳ.
	qldn_cash_add( array( 'direction' => 'in', 'fund' => 'cash', 'category' => 'Số dư đầu kỳ', 'amount' => 20000000, 'entry_date' => current_time( 'Y-m-01' ), 'partner_name' => 'Tồn quỹ' ) );
	qldn_cash_add( array( 'direction' => 'in', 'fund' => 'bank', 'category' => 'Số dư đầu kỳ', 'amount' => 150000000, 'entry_date' => current_time( 'Y-m-01' ), 'partner_name' => 'Số dư tài khoản' ) );
	qldn_cash_add( array( 'direction' => 'out', 'fund' => 'bank', 'category' => 'Thuê mặt bằng', 'amount' => 25000000, 'entry_date' => current_time( 'Y-m-05' ) <= $today ? current_time( 'Y-m-05' ) : $today, 'partner_name' => 'Chủ nhà', 'note' => 'Tiền thuê tháng ' . current_time( 'm/Y' ) ) );

	// Khách hàng, đơn hàng, chăm sóc.
	$cust = array();
	$names = array( 'Nguyễn Thị Lan', 'Trần Văn Minh', 'Lê Thị Ngọc', 'Phạm Quốc Phong', 'Võ Thị Quỳnh', 'Đặng Văn Sơn' );
	foreach ( $names as $i => $n ) {
		$cust[] = qldn_customer_save( array( 'name' => $n, 'phone' => '0908' . str_pad( (string) ( 100000 + $i ), 6, '0', STR_PAD_LEFT ), 'birthday' => '1990-' . current_time( 'm' ) . '-' . str_pad( (string) ( 10 + $i ), 2, '0', STR_PAD_LEFT ), 'source' => 'Cửa hàng' ) );
	}
	$cust[] = qldn_customer_save( array( 'name' => 'Công ty TNHH Luật ABC', 'ctype' => 'company', 'phone' => '0283930000', 'tax_code' => '0315555555', 'representative' => 'Lê Văn Bảo', 'rep_title' => 'Giám đốc', 'address' => '45 Nguyễn Huệ, Q.1, TP.HCM', 'grp' => 'Đại lý / Khách sỉ' ) );
	$sellers = array( $emp[2], $emp[3], $emp[4] );
	for ( $k = 0; $k < 30; $k++ ) {
		$lines = array();
		for ( $j = 0, $n = mt_rand( 1, 3 ); $j < $n; $j++ ) {
			$lines[] = array( 'product_id' => $pids[ mt_rand( 0, 6 ) ], 'qty' => mt_rand( 1, 4 ) );
		}
		$c = mt_rand( 0, 3 ) ? $cust[ mt_rand( 0, count( $cust ) - 1 ) ] : 0;
		$o = qldn_order_create( array( 'customer_id' => $c, 'staff_id' => $sellers[ $k % 3 ], 'lines' => $lines, 'payment' => $k % 3 ? 'cash' : 'transfer', 'paid' => $c && 0 === $k % 7 ? 0 : null ) );
		if ( ! is_wp_error( $o ) ) {
			// Rải đơn trong 30 ngày gần nhất.
			$when = gmdate( 'Y-m-d H:i:s', strtotime( $today . ' 09:00:00 -' . ( 29 - $k ) . ' days' ) + mt_rand( 0, 9 * 3600 ) );
			$wpdb->update( qldn_t( 'orders' ), array( 'created_at' => $when ), array( 'id' => $o ) );
			$wpdb->update( qldn_t( 'cashbook' ), array( 'entry_date' => substr( $when, 0, 10 ) ), array( 'ref_type' => 'order', 'ref_id' => $o ) );
		}
	}
	qldn_care_add( array( 'customer_id' => $cust[0], 'channel' => 'call', 'content' => 'Khách hỏi giá mực in số lượng lớn cho văn phòng.', 'result' => 'interested', 'next_date' => $today, 'next_task' => 'Gửi báo giá mực in' ) );
	qldn_care_add( array( 'customer_id' => end( $cust ), 'channel' => 'meet', 'content' => 'Gặp anh Bảo, nhu cầu cung cấp văn phòng phẩm hằng tháng.', 'result' => 'later', 'next_date' => gmdate( 'Y-m-d', strtotime( $today . ' +3 days' ) ), 'next_task' => 'Gửi hợp đồng nguyên tắc' ) );

	// Báo giá + hợp đồng.
	$q = qldn_quote_save( array( 'customer_id' => end( $cust ), 'title' => 'Báo giá văn phòng phẩm định kỳ', 'staff_id' => $emp[2], 'vat_rate' => 8, 'terms' => qldn_opt( 'quote_terms' ), 'lines' => array( array( 'product_id' => $pids[0], 'qty' => 50 ), array( 'product_id' => $pids[4], 'qty' => 10 ), array( 'name' => 'Giao hàng tận nơi hằng tháng', 'unit' => 'tháng', 'qty' => 12, 'price' => 0 ) ) ) );
	$qq = qldn_get( 'quotes', $q );
	$k1 = qldn_contract_save( array( 'type' => 'sale', 'party_id' => end( $cust ), 'quote_id' => $q, 'value' => $qq->total, 'sign_date' => $today, 'start_date' => $today, 'end_date' => gmdate( 'Y-m-d', strtotime( $today . ' +12 months -1 day' ) ), 'status' => 'active', 'scope' => 'Cung cấp văn phòng phẩm theo đơn đặt hàng hằng tháng.' ) );
	$k2 = qldn_contract_save( array( 'type' => 'labor', 'party_id' => $emp[3], 'start_date' => gmdate( 'Y-m-d', strtotime( $today . ' -11 months' ) ), 'end_date' => gmdate( 'Y-m-d', strtotime( $today . ' +20 days' ) ), 'sign_date' => gmdate( 'Y-m-d', strtotime( $today . ' -11 months' ) ), 'status' => 'active', 'scope' => 'Nhân viên bán hàng tại cửa hàng.' ) );
	foreach ( array( array( $k1, 'hd-mua-ban' ), array( $k2, 'hd-lao-dong' ) ) as $x ) {
		$tpl = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . qldn_t( 'templates' ) . ' WHERE slug = %s', $x[1] ) );
		if ( $tpl ) {
			qldn_document_create( $tpl, 'contract', $x[0] );
		}
	}

	// Chấm công tháng này từ "máy chấm công" (đến hôm qua).
	$punches = array();
	for ( $t = strtotime( current_time( 'Y-m-01' ) ); $t < strtotime( $today ); $t += DAY_IN_SECONDS ) {
		$d = gmdate( 'Y-m-d', $t );
		if ( ! qldn_is_workday( $d ) ) {
			continue;
		}
		foreach ( $emp as $i => $eid ) {
			$s = qldn_employee_shift( $eid );
			list( $a, $b ) = qldn_shift_bounds( $s, $d );
			$in  = $a - mt_rand( 0, 15 ) * 60 + ( 0 === mt_rand( 0, 9 ) ? mt_rand( 10, 40 ) * 60 : 0 );
			$out = $b + mt_rand( 0, 20 ) * 60 + ( 0 === mt_rand( 0, 6 ) ? mt_rand( 60, 120 ) * 60 : 0 );
			$punches[] = array( 'pin' => (string) ( $i + 1 ), 'time' => gmdate( 'Y-m-d H:i:s', $in ) );
			$punches[] = array( 'pin' => (string) ( $i + 1 ), 'time' => gmdate( 'Y-m-d H:i:s', $out ) );
		}
	}
	qldn_punches_save( $punches, 'file', 'MAU' );

	// Đơn từ.
	$next = gmdate( 'Y-m-d', strtotime( $today . ' +7 days' ) );
	qldn_request_create( array( 'employee_id' => $emp[3], 'type' => 'annual', 'from_date' => $next, 'to_date' => $next, 'reason' => 'Việc gia đình' ) );
	qldn_request_create( array( 'employee_id' => $emp[6], 'type' => 'ot', 'from_date' => $today, 'hours' => 2, 'reason' => 'Giao hàng gấp cho khách sỉ' ) );

	remove_filter( 'pre_wp_mail', '__return_false' );
	qldn_log( 'demo_data', 'settings', 0, 'Tạo dữ liệu mẫu' );
	return true;
}

/**
 * Nút tạo dữ liệu mẫu.
 */
function qldn_handle_demo() {
	qldn_check( 'demo', 'qldn_settings' );
	$r = qldn_demo_create();
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'settings' );
	}
	qldn_notice( 'Đã tạo dữ liệu mẫu: 8 nhân viên (3 ca, có ca đêm), 10 mặt hàng, 2 nhà cung cấp, 7 khách hàng, 30 đơn hàng, chấm công tháng này, đơn từ, báo giá, hợp đồng, sổ quỹ. Tài khoản của bạn được liên kết với nhân viên "Nguyễn Văn An" để thử trang Của tôi.' );
	qldn_go();
}
add_action( 'admin_post_qldn_demo', 'qldn_handle_demo' );
