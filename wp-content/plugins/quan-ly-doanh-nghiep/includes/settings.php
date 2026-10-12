<?php
/**
 * Cài đặt: thông tin công ty, bán hàng, tham số tính lương (bảo hiểm, thuế TNCN).
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giá trị mặc định.
 *
 * Tham số lương theo quy định áp dụng từ kỳ tính thuế 2026 – cần kiểm tra lại khi Nhà nước thay đổi:
 * - Giảm trừ gia cảnh: 15,5 triệu/tháng bản thân, 6,2 triệu/người phụ thuộc (Nghị quyết 110/2025/UBTVQH15).
 * - Biểu thuế lũy tiến 5 bậc cho thu nhập từ tiền lương (Luật Thuế TNCN 2025).
 * - Bảo hiểm phần người lao động đóng: BHXH 8% + BHYT 1,5% + BHTN 1% = 10,5%,
 *   mức lương đóng tối đa 20 lần lương cơ sở.
 *
 * @return array
 */
function qldn_default_settings() {
	return array(
		'company_name'        => get_bloginfo( 'name' ),
		'company_address'     => '',
		'company_phone'       => '',
		'company_tax_code'    => '',
		'order_prefix'        => 'HD',
		'invoice_footer'      => 'Cảm ơn quý khách. Hẹn gặp lại!',
		'allow_negative'      => 0,
		'point_value'         => 100000,
		'customer_groups'     => "Khách lẻ\nKhách quen\nVIP\nĐại lý / Khách sỉ",
		'customer_sources'    => "Cửa hàng\nFacebook\nZalo\nWebsite\nGiới thiệu",
		'vip_threshold'       => 20000000,
		'inactive_days'       => 60,
		'standard_days'       => 26,
		'work_hours'          => 8,
		'ot_rate'             => 150,
		'insurance_rate'      => 10.5,
		'base_wage'           => 2340000,
		'insurance_cap'       => 20,
		'personal_deduction'  => 15500000,
		'dependent_deduction' => 6200000,
		'tax_brackets'        => "10000000 | 5\n30000000 | 10\n60000000 | 20\n100000000 | 30\n0 | 35",
	);
}

/**
 * Lấy cài đặt.
 *
 * @param string $key Khoá (rỗng = cả mảng).
 * @return mixed
 */
function qldn_opt( $key = '' ) {
	$all = wp_parse_args( (array) get_option( 'qldn_settings', array() ), qldn_default_settings() );
	if ( '' === $key ) {
		return $all;
	}
	return isset( $all[ $key ] ) ? $all[ $key ] : null;
}

/**
 * Danh sách dòng của một ô nhiều dòng.
 *
 * @param string $key Khoá cài đặt.
 * @return array giá trị => nhãn
 */
function qldn_opt_list( $key ) {
	$lines = array_filter( array_map( 'trim', explode( "\n", (string) qldn_opt( $key ) ) ) );
	return array_combine( $lines, $lines ) ?: array();
}

/**
 * Biểu thuế: mảng [ [ngưỡng trên (0 = không giới hạn), thuế suất %] ].
 *
 * @return array
 */
function qldn_tax_brackets() {
	$out = array();
	foreach ( explode( "\n", (string) qldn_opt( 'tax_brackets' ) ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( 2 === count( $p ) && '' !== $p[1] ) {
			$out[] = array( qldn_num( $p[0] ), (float) str_replace( ',', '.', $p[1] ) );
		}
	}
	// Ngưỡng 0 (không giới hạn) luôn ở cuối.
	usort(
		$out,
		function ( $a, $b ) {
			if ( 0 === $a[0] ) {
				return 1;
			}
			if ( 0 === $b[0] ) {
				return -1;
			}
			return $a[0] <=> $b[0];
		}
	);
	return $out;
}

/**
 * Lưu cài đặt.
 */
function qldn_save_settings() {
	qldn_check( 'save_settings', 'qldn_settings' );
	$d   = qldn_default_settings();
	$new = array();
	foreach ( $d as $k => $def ) {
		if ( in_array( $k, array( 'customer_groups', 'customer_sources', 'tax_brackets', 'company_address', 'invoice_footer' ), true ) ) {
			$new[ $k ] = qldn_in( $k, 'textarea', $def );
		} elseif ( in_array( $k, array( 'point_value', 'vip_threshold', 'base_wage', 'personal_deduction', 'dependent_deduction' ), true ) ) {
			$new[ $k ] = max( 0, qldn_in( $k, 'money', $def ) );
		} elseif ( in_array( $k, array( 'insurance_rate', 'standard_days', 'work_hours', 'ot_rate', 'insurance_cap', 'inactive_days' ), true ) ) {
			$new[ $k ] = max( 0, qldn_in( $k, 'qty', $def ) );
		} elseif ( 'allow_negative' === $k ) {
			$new[ $k ] = qldn_in( $k, 'int', 0 ) ? 1 : 0;
		} else {
			$new[ $k ] = qldn_in( $k, 'text', $def );
		}
	}
	$new['standard_days'] = $new['standard_days'] > 0 ? $new['standard_days'] : 26;
	$new['work_hours']    = $new['work_hours'] > 0 ? $new['work_hours'] : 8;
	update_option( 'qldn_settings', $new, false );
	qldn_notice( 'Đã lưu cài đặt.' );
	qldn_go( 'settings' );
}
add_action( 'admin_post_qldn_save_settings', 'qldn_save_settings' );

/**
 * Trang cài đặt.
 */
function qldn_page_settings() {
	$o = qldn_opt();
	echo '<div class="wrap qldn"><h1>Cài đặt</h1>';
	qldn_print_notices();
	qldn_form_open( 'save_settings' );

	echo '<h2>Thông tin doanh nghiệp (in trên hoá đơn, phiếu lương)</h2><table class="form-table">';
	qldn_field( 'Tên doanh nghiệp', 'company_name', $o['company_name'] );
	qldn_field( 'Địa chỉ', 'company_address', $o['company_address'] );
	qldn_field( 'Điện thoại', 'company_phone', $o['company_phone'] );
	qldn_field( 'Mã số thuế', 'company_tax_code', $o['company_tax_code'] );
	echo '</table>';

	echo '<h2>Bán hàng & kho</h2><table class="form-table">';
	qldn_field( 'Tiền tố mã đơn', 'order_prefix', $o['order_prefix'], array( 'class' => 'small-text' ) );
	qldn_field( 'Lời chào cuối hoá đơn', 'invoice_footer', $o['invoice_footer'] );
	qldn_field( 'Bán khi hết hàng', 'allow_negative', $o['allow_negative'], array( 'type' => 'select', 'options' => array( 0 => 'Không – chặn bán vượt tồn kho', 1 => 'Có – cho phép tồn kho âm' ) ) );
	echo '</table>';

	echo '<h2>Khách hàng & chăm sóc</h2><table class="form-table">';
	qldn_field( 'Tích điểm', 'point_value', $o['point_value'], array( 'type' => 'money', 'class' => 'small-text', 'help' => 'Mỗi số tiền này trong đơn hoàn thành = 1 điểm. Đặt 0 để tắt tích điểm.' ) );
	qldn_field( 'Tự lên nhóm VIP khi tổng mua đạt', 'vip_threshold', $o['vip_threshold'], array( 'type' => 'money', 'help' => 'Đặt 0 để tắt. Khách được chuyển sang nhóm "VIP" (nếu có nhóm này).' ) );
	qldn_field( 'Nhắc khách lâu không mua sau', 'inactive_days', $o['inactive_days'], array( 'type' => 'number', 'class' => 'small-text', 'help' => 'ngày' ) );
	qldn_field( 'Nhóm khách hàng', 'customer_groups', $o['customer_groups'], array( 'type' => 'textarea', 'help' => 'Mỗi dòng 1 nhóm.' ) );
	qldn_field( 'Nguồn khách hàng', 'customer_sources', $o['customer_sources'], array( 'type' => 'textarea', 'help' => 'Mỗi dòng 1 nguồn.' ) );
	echo '</table>';

	echo '<h2>Chấm công & tính lương</h2><table class="form-table">';
	qldn_field( 'Ngày công chuẩn / tháng', 'standard_days', $o['standard_days'], array( 'type' => 'number', 'class' => 'small-text', 'attrs' => array( 'step' => '0.5' ) ) );
	qldn_field( 'Số giờ / ngày công', 'work_hours', $o['work_hours'], array( 'type' => 'number', 'class' => 'small-text', 'attrs' => array( 'step' => '0.5' ) ) );
	qldn_field( 'Hệ số làm thêm giờ', 'ot_rate', $o['ot_rate'], array( 'type' => 'number', 'class' => 'small-text', 'help' => '% lương giờ (ngày thường tối thiểu 150%, ngày nghỉ tuần 200%, lễ tết 300% – Điều 98 Bộ luật Lao động 2019).' ) );
	qldn_field( 'Tỷ lệ bảo hiểm người lao động đóng', 'insurance_rate', $o['insurance_rate'], array( 'type' => 'text', 'class' => 'small-text', 'help' => '% (BHXH 8% + BHYT 1,5% + BHTN 1% = 10,5%).' ) );
	qldn_field( 'Lương cơ sở', 'base_wage', $o['base_wage'], array( 'type' => 'money', 'help' => 'Dùng tính mức trần lương đóng bảo hiểm. Cập nhật khi Nhà nước điều chỉnh.' ) );
	qldn_field( 'Trần lương đóng bảo hiểm', 'insurance_cap', $o['insurance_cap'], array( 'type' => 'number', 'class' => 'small-text', 'help' => 'lần lương cơ sở.' ) );
	qldn_field( 'Giảm trừ bản thân', 'personal_deduction', $o['personal_deduction'], array( 'type' => 'money' ) );
	qldn_field( 'Giảm trừ mỗi người phụ thuộc', 'dependent_deduction', $o['dependent_deduction'], array( 'type' => 'money' ) );
	qldn_field( 'Biểu thuế TNCN lũy tiến', 'tax_brackets', $o['tax_brackets'], array( 'type' => 'textarea', 'help' => 'Mỗi dòng: <code>thu nhập tính thuế đến | thuế suất %</code>, bậc cuối ghi <code>0</code> = phần còn lại.' ) );
	echo '</table>';
	echo '<p class="description">Tham số lương mặc định theo quy định áp dụng năm 2026. Hãy đối chiếu văn bản mới nhất trước khi chốt lương.</p>';

	submit_button( 'Lưu cài đặt' );
	echo '</form>';

	echo '<h2>Phân quyền</h2><p>Plugin tạo sẵn các vai trò (vào <a href="' . esc_url( admin_url( 'users.php' ) ) . '">Người dùng</a> → sửa người dùng → <em>Vai trò</em>):</p><table class="widefat striped qldn-narrow"><thead><tr><th>Vai trò</th><th>Được dùng</th></tr></thead><tbody>';
	$caps = qldn_caps();
	foreach ( qldn_roles() as $def ) {
		echo '<tr><td><strong>' . esc_html( $def[0] ) . '</strong></td><td>' . esc_html( implode( '; ', array_intersect_key( $caps, array_flip( $def[1] ) ) ) ) . '</td></tr>';
	}
	echo '</tbody></table><p class="description">Quản trị viên luôn có toàn quyền. Liên kết nhân viên với tài khoản ở trang <em>Nhân viên</em> để họ tự chấm công vào/ra ca.</p>';
	echo '</div>';
}
