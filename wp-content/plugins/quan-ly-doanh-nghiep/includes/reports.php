<?php
/**
 * Tổng quan và báo cáo: doanh thu, lãi gộp, hàng bán chạy, doanh số nhân viên.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Số liệu bán hàng trong khoảng thời gian (chỉ đơn hoàn thành).
 *
 * @param string $from Y-m-d.
 * @param string $to   Y-m-d.
 * @return object orders, revenue, cost, discount, debt.
 */
function qldn_sales_stats( $from, $to ) {
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( 'SELECT COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cost, COALESCE(SUM(discount),0) AS discount, COALESCE(SUM(total - paid),0) AS debt FROM ' . qldn_t( 'orders' ) . " WHERE status = 'done' AND created_at BETWEEN %s AND %s", $from . ' 00:00:00', $to . ' 23:59:59' ) );
	foreach ( array( 'orders', 'revenue', 'cost', 'discount', 'debt' ) as $k ) {
		$r->$k = (int) $r->$k;
	}
	$r->profit = $r->revenue - $r->cost;
	return $r;
}

/**
 * Doanh thu theo ngày.
 *
 * @param string $from Y-m-d.
 * @param string $to   Y-m-d.
 * @return array ngày => [revenue, profit, orders].
 */
function qldn_sales_by_day( $from, $to ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT DATE(created_at) AS d, SUM(total) AS revenue, SUM(total - cost_total) AS profit, COUNT(*) AS orders FROM ' . qldn_t( 'orders' ) . " WHERE status = 'done' AND created_at BETWEEN %s AND %s GROUP BY DATE(created_at)", $from . ' 00:00:00', $to . ' 23:59:59' ) );
	$out  = array();
	for ( $ts = strtotime( $from ); $ts <= strtotime( $to ); $ts = strtotime( '+1 day', $ts ) ) {
		$out[ gmdate( 'Y-m-d', $ts ) ] = array( 0, 0, 0 );
	}
	foreach ( $rows as $r ) {
		$out[ $r->d ] = array( (int) $r->revenue, (int) $r->profit, (int) $r->orders );
	}
	return $out;
}

/**
 * Biểu đồ cột doanh thu (HTML/CSS, không cần thư viện).
 *
 * @param array $days ngày => [revenue, profit, orders].
 */
function qldn_bar_chart( $days ) {
	$max = max( 1, max( array_map( function ( $d ) { return $d[0]; }, $days ) ) );
	echo '<div class="qldn-chart" role="img" aria-label="Biểu đồ doanh thu theo ngày">';
	foreach ( $days as $d => $v ) {
		$h = round( $v[0] / $max * 100, 1 );
		echo '<div class="qldn-bar" title="' . esc_attr( qldn_date( $d ) . ': ' . qldn_money( $v[0] ) . ' – ' . $v[2] . ' đơn' ) . '"><span style="height:' . esc_attr( $h ) . '%"></span><small>' . esc_html( gmdate( 'd', strtotime( $d ) ) ) . '</small></div>';
	}
	echo '</div>';
}

/**
 * Trang Tổng quan.
 */
function qldn_page_dashboard() {
	global $wpdb;
	$today = qldn_today();
	$month = current_time( 'Y-m-01' );
	echo '<div class="wrap qldn"><h1>Tổng quan – ' . esc_html( qldn_opt( 'company_name' ) ) . '</h1>';
	qldn_print_notices();

	// Chấm công của chính mình.
	$me = qldn_current_employee();
	if ( $me ) {
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'attendance' ) . ' WHERE employee_id = %d AND work_date = %s', $me->id, $today ) );
		echo '<div class="qldn-checkin"><div><strong>Xin chào ' . esc_html( $me->name ) . '</strong><br>';
		if ( $row && $row->check_in ) {
			echo 'Vào ca: ' . esc_html( substr( $row->check_in, 0, 5 ) ) . ( $row->check_out ? ' · Ra ca: ' . esc_html( substr( $row->check_out, 0, 5 ) ) : '' );
		} else {
			echo 'Hôm nay bạn chưa chấm công.';
		}
		echo '</div>';
		if ( ! $row || ! $row->check_in || ! $row->check_out ) {
			qldn_form_open( 'checkin' );
			echo '<button class="button button-primary button-hero">' . ( $row && $row->check_in ? 'Chấm công ra ca' : 'Chấm công vào ca' ) . '</button></form>';
		}
		echo '</div>';
	}

	if ( current_user_can( 'qldn_sales' ) || current_user_can( 'qldn_reports' ) ) {
		$t = qldn_sales_stats( $today, $today );
		$m = qldn_sales_stats( $month, $today );
		$see_profit = current_user_can( 'qldn_reports' );
		echo '<div class="qldn-cards">';
		echo '<div class="qldn-card"><span>Doanh thu hôm nay</span><strong>' . esc_html( qldn_money( $t->revenue ) ) . '</strong><em>' . esc_html( $t->orders ) . ' đơn</em></div>';
		echo '<div class="qldn-card"><span>Doanh thu tháng này</span><strong>' . esc_html( qldn_money( $m->revenue ) ) . '</strong><em>' . esc_html( $m->orders ) . ' đơn</em></div>';
		if ( $see_profit ) {
			echo '<div class="qldn-card"><span>Lãi gộp tháng này</span><strong>' . esc_html( qldn_money( $m->profit ) ) . '</strong><em>' . esc_html( $m->revenue ? round( $m->profit / $m->revenue * 100, 1 ) : 0 ) . '% doanh thu</em></div>';
		}
		$debt = (int) $wpdb->get_var( 'SELECT COALESCE(SUM(debt),0) FROM ' . qldn_t( 'customers' ) . ' WHERE debt > 0' );
		echo '<a class="qldn-card' . ( $debt ? ' qldn-card-warn' : '' ) . '" href="' . esc_url( qldn_url( 'customers', array( 'filter' => 'debt' ) ) ) . '"><span>Khách đang nợ</span><strong>' . esc_html( qldn_money( $debt ) ) . '</strong></a>';
		echo '</div>';
		echo '<h2>Doanh thu 30 ngày gần nhất</h2>';
		qldn_bar_chart( qldn_sales_by_day( gmdate( 'Y-m-d', strtotime( $today . ' -29 days' ) ), $today ) );
	}

	echo '<div class="qldn-cols">';
	if ( current_user_can( 'qldn_sales' ) ) {
		$tc    = qldn_t( 'care' );
		$tasks = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tc WHERE done = 0 AND next_date IS NOT NULL AND next_date <= %s", $today ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$bdays = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . qldn_t( 'customers' ) . ' WHERE birthday IS NOT NULL AND MONTH(birthday) = %d AND DAY(birthday) = %d', (int) current_time( 'n' ), (int) current_time( 'j' ) ) );
		echo '<div class="qldn-col"><h2>Chăm sóc khách hàng</h2><ul class="qldn-todo">';
		echo '<li><a href="' . esc_url( qldn_url( 'care' ) ) . '">' . ( $tasks ? qldn_badge( $tasks, 'orange' ) : qldn_badge( 0, 'gray' ) ) . ' lịch hẹn cần làm hôm nay / quá hạn</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<li><a href="' . esc_url( qldn_url( 'care' ) ) . '">' . ( $bdays ? qldn_badge( $bdays, 'orange' ) : qldn_badge( 0, 'gray' ) ) . ' khách có sinh nhật hôm nay</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</ul><p><a class="button button-primary" href="' . esc_url( qldn_url( 'pos' ) ) . '">Bán hàng</a> <a class="button" href="' . esc_url( qldn_url( 'customers', array( 'edit' => 'new' ) ) ) . '">Thêm khách hàng</a></p></div>';
	}
	if ( current_user_can( 'qldn_stock' ) ) {
		$low = $wpdb->get_results( 'SELECT * FROM ' . qldn_t( 'products' ) . " WHERE status = 'active' AND stock <= min_stock ORDER BY stock LIMIT 10" );
		echo '<div class="qldn-col"><h2>Hàng sắp hết</h2>';
		if ( ! $low ) {
			echo '<p>Không có mặt hàng nào dưới mức tồn tối thiểu.</p>';
		} else {
			echo '<table class="widefat striped"><tbody>';
			foreach ( $low as $p ) {
				echo '<tr><td>' . esc_html( $p->name ) . '</td><td class="num">' . qldn_badge( qldn_qty( $p->stock ) . ' ' . $p->unit, 'red' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</tbody></table><p><a href="' . esc_url( qldn_url( 'products', array( 'filter' => 'low' ) ) ) . '">Xem tất cả</a> · <a href="' . esc_url( qldn_url( 'stock', array( 'tab' => 'nhap' ) ) ) . '">Nhập hàng</a></p>';
		}
		echo '</div>';
	}
	if ( current_user_can( 'qldn_hr' ) ) {
		$te      = qldn_t( 'employees' );
		$total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $te WHERE status = 'active'" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$present = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . qldn_t( 'attendance' ) . " a JOIN $te e ON e.id = a.employee_id WHERE e.status = 'active' AND a.work_date = %s AND a.mark IN ('X','N')", $today ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		echo '<div class="qldn-col"><h2>Nhân sự hôm nay</h2><p><strong>' . esc_html( $present ) . '</strong> / ' . esc_html( $total ) . ' nhân viên đã chấm công.</p><p><a class="button" href="' . esc_url( qldn_url( 'attendance' ) ) . '">Bảng chấm công</a> <a class="button" href="' . esc_url( qldn_url( 'payroll' ) ) . '">Bảng lương</a></p></div>';
	}
	echo '</div></div>';
}

/**
 * Trang Báo cáo.
 */
function qldn_page_reports() {
	global $wpdb;
	$from = qldn_in( 'from', 'date', current_time( 'Y-m-01' ) );
	$to   = qldn_in( 'to', 'date', qldn_today() );
	if ( $from > $to ) {
		list( $from, $to ) = array( $to, $from );
	}
	// Biểu đồ tối đa 366 ngày.
	if ( strtotime( $to ) - strtotime( $from ) > 366 * DAY_IN_SECONDS ) {
		$from = gmdate( 'Y-m-d', strtotime( $to . ' -366 days' ) );
	}
	$s    = qldn_sales_stats( $from, $to );
	$args = array( $from . ' 00:00:00', $to . ' 23:59:59' );
	$to_  = qldn_t( 'orders' );
	$top  = $wpdb->get_results( $wpdb->prepare( 'SELECT i.product_id, i.name, SUM(i.qty) AS qty, SUM(i.line_total) AS revenue, SUM(i.line_total - i.cost * i.qty) AS profit FROM ' . qldn_t( 'order_items' ) . " i JOIN $to_ o ON o.id = i.order_id WHERE o.status = 'done' AND o.created_at BETWEEN %s AND %s GROUP BY i.product_id, i.name ORDER BY revenue DESC LIMIT 20", $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$staff = $wpdb->get_results( $wpdb->prepare( "SELECT o.staff_id, e.name, COUNT(*) AS orders, SUM(o.total) AS revenue, SUM(o.total - o.cost_total) AS profit FROM $to_ o LEFT JOIN " . qldn_t( 'employees' ) . " e ON e.id = o.staff_id WHERE o.status = 'done' AND o.created_at BETWEEN %s AND %s GROUP BY o.staff_id, e.name ORDER BY revenue DESC", $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$pays  = $wpdb->get_results( $wpdb->prepare( "SELECT payment, COUNT(*) AS orders, SUM(paid) AS paid FROM $to_ WHERE status = 'done' AND created_at BETWEEN %s AND %s GROUP BY payment", $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$groups = $wpdb->get_results( $wpdb->prepare( "SELECT c.grp, COUNT(DISTINCT o.customer_id) AS customers, SUM(o.total) AS revenue FROM $to_ o JOIN " . qldn_t( 'customers' ) . " c ON c.id = o.customer_id WHERE o.status = 'done' AND o.created_at BETWEEN %s AND %s GROUP BY c.grp ORDER BY revenue DESC", $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$cancel = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $to_ WHERE status = 'cancelled' AND created_at BETWEEN %s AND %s", $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$payroll = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(gross),0) FROM ' . qldn_t( 'payroll' ) . ' WHERE period BETWEEN %s AND %s', substr( $from, 0, 7 ), substr( $to, 0, 7 ) ) );
	$imports = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(qty * unit_cost),0) FROM ' . qldn_t( 'stock_moves' ) . " WHERE type = 'nhap' AND created_at BETWEEN %s AND %s", $args ) );

	echo '<div class="wrap qldn"><h1>Báo cáo</h1>';
	echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-reports">Từ <input type="date" name="from" value="' . esc_attr( $from ) . '"> đến <input type="date" name="to" value="' . esc_attr( $to ) . '"> <button class="button">Xem</button> ';
	$quick = array(
		'Hôm nay'     => array( qldn_today(), qldn_today() ),
		'Tháng này'   => array( current_time( 'Y-m-01' ), qldn_today() ),
		'Tháng trước' => array( gmdate( 'Y-m-01', strtotime( current_time( 'Y-m-01' ) . ' -1 month' ) ), gmdate( 'Y-m-t', strtotime( current_time( 'Y-m-01' ) . ' -1 month' ) ) ),
		'Năm nay'     => array( current_time( 'Y-01-01' ), qldn_today() ),
	);
	foreach ( $quick as $label => $r ) {
		echo '<a class="button" href="' . esc_url( qldn_url( 'reports', array( 'from' => $r[0], 'to' => $r[1] ) ) ) . '">' . esc_html( $label ) . '</a> ';
	}
	echo '</form>';

	echo '<div class="qldn-cards">';
	echo '<div class="qldn-card"><span>Doanh thu</span><strong>' . esc_html( qldn_money( $s->revenue ) ) . '</strong><em>' . esc_html( $s->orders ) . ' đơn · ' . esc_html( $cancel ) . ' đơn huỷ</em></div>';
	echo '<div class="qldn-card"><span>Giá vốn hàng bán</span><strong>' . esc_html( qldn_money( $s->cost ) ) . '</strong></div>';
	echo '<div class="qldn-card"><span>Lãi gộp</span><strong>' . esc_html( qldn_money( $s->profit ) ) . '</strong><em>' . esc_html( $s->revenue ? round( $s->profit / $s->revenue * 100, 1 ) : 0 ) . '% doanh thu</em></div>';
	echo '<div class="qldn-card"><span>Giá trị đơn trung bình</span><strong>' . esc_html( qldn_money( $s->orders ? $s->revenue / $s->orders : 0 ) ) . '</strong><em>Giảm giá: ' . esc_html( qldn_money( $s->discount ) ) . '</em></div>';
	echo '<div class="qldn-card"><span>Chi phí lương (các tháng trong kỳ)</span><strong>' . esc_html( qldn_money( $payroll ) ) . '</strong><em>Lãi gộp trừ lương: ' . esc_html( qldn_money( $s->profit - $payroll ) ) . '</em></div>';
	echo '<div class="qldn-card"><span>Tiền nhập hàng</span><strong>' . esc_html( qldn_money( $imports ) ) . '</strong></div>';
	echo '</div>';

	echo '<h2>Doanh thu theo ngày</h2>';
	$days = qldn_sales_by_day( $from, $to );
	qldn_bar_chart( $days );

	echo '<div class="qldn-cols"><div class="qldn-col"><h2>Hàng bán chạy</h2><table class="widefat striped qldn-table"><thead><tr><th>Hàng hoá</th><th class="num">SL</th><th class="num">Doanh thu</th><th class="num">Lãi gộp</th></tr></thead><tbody>';
	if ( ! $top ) {
		echo '<tr><td colspan="4">Chưa có dữ liệu.</td></tr>';
	}
	foreach ( $top as $r ) {
		echo '<tr><td>' . esc_html( $r->name ) . '</td><td class="num">' . esc_html( qldn_qty( $r->qty ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->revenue, false ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->profit, false ) ) . '</td></tr>';
	}
	echo '</tbody></table></div><div class="qldn-col"><h2>Doanh số nhân viên</h2><table class="widefat striped qldn-table"><thead><tr><th>Nhân viên</th><th class="num">Đơn</th><th class="num">Doanh thu</th><th class="num">Lãi gộp</th></tr></thead><tbody>';
	if ( ! $staff ) {
		echo '<tr><td colspan="4">Chưa có dữ liệu.</td></tr>';
	}
	foreach ( $staff as $r ) {
		echo '<tr><td>' . esc_html( $r->name ? $r->name : '(không ghi nhân viên)' ) . '</td><td class="num">' . esc_html( $r->orders ) . '</td><td class="num">' . esc_html( qldn_money( $r->revenue, false ) ) . '</td><td class="num">' . esc_html( qldn_money( $r->profit, false ) ) . '</td></tr>';
	}
	echo '</tbody></table>';

	echo '<h2>Thu tiền theo hình thức</h2><table class="widefat striped qldn-table"><thead><tr><th>Hình thức</th><th class="num">Đơn</th><th class="num">Đã thu</th></tr></thead><tbody>';
	$pl = qldn_payments();
	foreach ( $pays as $r ) {
		echo '<tr><td>' . esc_html( $pl[ $r->payment ] ?? $r->payment ) . '</td><td class="num">' . esc_html( $r->orders ) . '</td><td class="num">' . esc_html( qldn_money( $r->paid, false ) ) . '</td></tr>';
	}
	echo '</tbody></table>';

	echo '<h2>Doanh thu theo nhóm khách</h2><table class="widefat striped qldn-table"><thead><tr><th>Nhóm</th><th class="num">Số khách</th><th class="num">Doanh thu</th></tr></thead><tbody>';
	foreach ( $groups as $r ) {
		echo '<tr><td>' . esc_html( $r->grp ? $r->grp : '—' ) . '</td><td class="num">' . esc_html( $r->customers ) . '</td><td class="num">' . esc_html( qldn_money( $r->revenue, false ) ) . '</td></tr>';
	}
	echo '</tbody></table></div></div>';

	echo '<h2>Chi tiết theo ngày</h2><table class="widefat striped qldn-table qldn-narrow"><thead><tr><th>Ngày</th><th class="num">Đơn</th><th class="num">Doanh thu</th><th class="num">Lãi gộp</th></tr></thead><tbody>';
	foreach ( array_reverse( $days, true ) as $d => $v ) {
		if ( $v[2] ) {
			echo '<tr><td><a href="' . esc_url( qldn_url( 'orders', array( 'from' => $d, 'to' => $d ) ) ) . '">' . esc_html( qldn_date( $d ) ) . '</a></td><td class="num">' . esc_html( $v[2] ) . '</td><td class="num">' . esc_html( qldn_money( $v[0], false ) ) . '</td><td class="num">' . esc_html( qldn_money( $v[1], false ) ) . '</td></tr>';
		}
	}
	echo '</tbody></table><p class="description">Doanh thu, lãi gộp tính trên đơn hoàn thành (không gồm đơn huỷ), giá vốn lấy theo giá vốn tại thời điểm bán.</p></div>';
}
