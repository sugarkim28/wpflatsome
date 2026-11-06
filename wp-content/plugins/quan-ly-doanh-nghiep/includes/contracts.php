<?php
/**
 * Hợp đồng: mua bán hàng hoá, cung cấp dịch vụ (với khách hàng), lao động (với nhân viên).
 * Số hợp đồng tự đánh, giá trị, thanh toán, hiệu lực, nhắc sắp hết hạn, soạn hợp đồng / biên bản thanh lý từ mẫu.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại hợp đồng.
 *
 * @return array
 */
function qldn_contract_types() {
	return array(
		'sale'    => 'Mua bán hàng hoá',
		'service' => 'Cung cấp dịch vụ',
		'labor'   => 'Lao động',
		'other'   => 'Khác',
	);
}

/**
 * Ký hiệu trong số hợp đồng.
 *
 * @param string $type Loại.
 * @return string
 */
function qldn_contract_symbol( $type ) {
	$s = array( 'sale' => 'HĐMB', 'service' => 'HĐDV', 'labor' => 'HĐLĐ', 'other' => 'HĐ' );
	return isset( $s[ $type ] ) ? $s[ $type ] : 'HĐ';
}

/**
 * Trạng thái hợp đồng.
 *
 * @return array
 */
function qldn_contract_statuses() {
	return array(
		'draft'      => 'Đang soạn / chờ ký',
		'active'     => 'Đang hiệu lực',
		'liquidated' => 'Đã thanh lý',
		'cancelled'  => 'Đã huỷ',
	);
}

/**
 * Huy hiệu trạng thái (đang hiệu lực nhưng quá ngày kết thúc → hết hạn).
 *
 * @param object $c Hợp đồng.
 * @return string HTML.
 */
function qldn_contract_badge( $c ) {
	$today = qldn_today();
	if ( 'active' === $c->status && $c->end_date ) {
		if ( $c->end_date < $today ) {
			return qldn_badge( 'Hết hạn ' . qldn_date( $c->end_date ), 'red' );
		}
		if ( $c->end_date <= gmdate( 'Y-m-d', strtotime( $today . ' +' . (int) qldn_opt( 'contract_remind' ) . ' days' ) ) ) {
			return qldn_badge( 'Sắp hết hạn ' . qldn_date( $c->end_date ), 'orange' );
		}
	}
	$colors = array( 'draft' => 'gray', 'active' => 'green', 'liquidated' => 'blue', 'cancelled' => 'gray' );
	return qldn_badge( qldn_contract_statuses()[ $c->status ] ?? $c->status, $colors[ $c->status ] ?? 'gray' );
}

/**
 * Thời hạn hợp đồng bằng chữ.
 *
 * @param object $c Hợp đồng.
 * @return string
 */
function qldn_contract_term( $c ) {
	if ( ! $c->end_date ) {
		return 'labor' === $c->type ? 'Không xác định thời hạn' : '';
	}
	if ( ! $c->start_date ) {
		return 'đến ngày ' . qldn_date( $c->end_date );
	}
	$a = new DateTime( $c->start_date );
	$b = ( new DateTime( $c->end_date ) )->modify( '+1 day' );
	$d = $a->diff( $b );
	if ( 0 === $d->d ) {
		$m = $d->y * 12 + $d->m;
		return $m . ' tháng (từ ngày ' . qldn_date( $c->start_date ) . ' đến ngày ' . qldn_date( $c->end_date ) . ')';
	}
	return 'từ ngày ' . qldn_date( $c->start_date ) . ' đến ngày ' . qldn_date( $c->end_date );
}

/**
 * Số hợp đồng tiếp theo: 01/2026/HĐDV.
 *
 * @param string $type Loại.
 * @return string
 */
function qldn_contract_next_code( $type ) {
	global $wpdb;
	$year = current_time( 'Y' );
	$sym  = qldn_contract_symbol( $type );
	$n    = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . qldn_t( 'contracts' ) . ' WHERE type = %s AND created_at >= %s', $type, $year . '-01-01 00:00:00' ) );
	do {
		++$n;
		$code = sprintf( '%02d/%s/%s', $n, $year, $sym );
	} while ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . qldn_t( 'contracts' ) . ' WHERE code = %s', $code ) ) );
	return $code;
}

/**
 * Lưu hợp đồng.
 *
 * @param array $d  Dữ liệu.
 * @param int   $id ID khi sửa.
 * @return int|WP_Error
 */
function qldn_contract_save( $d, $id = 0 ) {
	global $wpdb;
	$t     = qldn_t( 'contracts' );
	$type  = isset( qldn_contract_types()[ $d['type'] ?? '' ] ) ? $d['type'] : 'service';
	$ptype = 'labor' === $type ? 'employee' : 'customer';
	$pid   = (int) ( $d['party_id'] ?? 0 );
	$date  = function ( $k ) use ( $d ) {
		return ! empty( $d[ $k ] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d[ $k ] ) ? $d[ $k ] : null;
	};
	$row = array(
		'type'       => $type,
		'party_type' => $ptype,
		'party_id'   => $pid,
		'title'      => sanitize_text_field( $d['title'] ?? '' ),
		'value'      => max( 0, (int) ( $d['value'] ?? 0 ) ),
		'paid'       => max( 0, (int) ( $d['paid'] ?? 0 ) ),
		'sign_date'  => $date( 'sign_date' ),
		'start_date' => $date( 'start_date' ),
		'end_date'   => $date( 'end_date' ),
		'status'     => isset( qldn_contract_statuses()[ $d['status'] ?? '' ] ) ? $d['status'] : 'draft',
		'quote_id'   => (int) ( $d['quote_id'] ?? 0 ),
		'scope'      => sanitize_textarea_field( $d['scope'] ?? '' ),
		'note'       => sanitize_textarea_field( $d['note'] ?? '' ),
	);
	if ( ! $pid || ! qldn_get( 'employee' === $ptype ? 'employees' : 'customers', $pid ) ) {
		return new WP_Error( 'qldn_party', 'labor' === $type ? 'Chọn nhân viên ký hợp đồng lao động.' : 'Chọn khách hàng (bên ký hợp đồng).' );
	}
	if ( $row['start_date'] && $row['end_date'] && $row['end_date'] < $row['start_date'] ) {
		return new WP_Error( 'qldn_date', 'Ngày kết thúc phải sau ngày bắt đầu.' );
	}
	// Bộ luật Lao động 2019, Điều 20: hợp đồng xác định thời hạn không quá 36 tháng.
	if ( 'labor' === $type && $row['start_date'] && $row['end_date'] && strtotime( $row['end_date'] ) > strtotime( $row['start_date'] . ' +36 months' ) ) {
		return new WP_Error( 'qldn_labor', 'Hợp đồng lao động xác định thời hạn không quá 36 tháng (Điều 20 Bộ luật Lao động 2019). Để trống ngày kết thúc nếu là hợp đồng không xác định thời hạn.' );
	}
	if ( 'labor' === $type && ! $row['value'] ) {
		$row['value'] = (int) qldn_get( 'employees', $pid )->base_salary;
	}
	if ( '' === $row['title'] ) {
		$party        = qldn_get( 'employee' === $ptype ? 'employees' : 'customers', $pid );
		$row['title'] = 'Hợp đồng ' . mb_strtolower( qldn_contract_types()[ $type ] ) . ' – ' . $party->name;
	}
	$code = sanitize_text_field( $d['code'] ?? '' );
	if ( '' !== $code && $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE code = %s AND id <> %d", $code, $id ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
		return new WP_Error( 'qldn_code', 'Số hợp đồng ' . $code . ' đã có.' );
	}
	if ( $id ) {
		if ( '' !== $code ) {
			$row['code'] = $code;
		}
		$wpdb->update( $t, $row, array( 'id' => $id ) );
		return $id;
	}
	$row['code']       = '' !== $code ? $code : qldn_contract_next_code( $type );
	$row['user_id']    = get_current_user_id();
	$row['created_at'] = qldn_now();
	$wpdb->insert( $t, $row );
	return (int) $wpdb->insert_id;
}

/**
 * Kiểm tra quyền với hợp đồng (lao động cần quyền nhân sự).
 *
 * @param string $type Loại.
 */
function qldn_contract_cap( $type ) {
	if ( ! current_user_can( 'qldn_docs' ) || ( 'labor' === $type && ! current_user_can( 'qldn_hr' ) ) ) {
		wp_die( 'Bạn không có quyền với hợp đồng này.', 403 );
	}
}

/**
 * Xử lý form hợp đồng.
 */
function qldn_handle_contract() {
	qldn_check( 'contract', 'qldn_docs' );
	$id   = qldn_in( 'id', 'int', 0 );
	$type = qldn_in( 'type', 'key' );
	qldn_contract_cap( $type );
	if ( $id ) {
		$old = qldn_get( 'contracts', $id );
		qldn_contract_cap( $old ? $old->type : '' );
	}
	$r = qldn_contract_save(
		array(
			'code'       => qldn_in( 'code' ),
			'type'       => $type,
			'party_id'   => 'labor' === $type ? qldn_in( 'employee_id', 'int', 0 ) : qldn_in( 'customer_id', 'int', 0 ),
			'title'      => qldn_in( 'title' ),
			'value'      => qldn_in( 'value', 'money', 0 ),
			'paid'       => qldn_in( 'paid', 'money', 0 ),
			'sign_date'  => qldn_in( 'sign_date', 'date' ),
			'start_date' => qldn_in( 'start_date', 'date' ),
			'end_date'   => qldn_in( 'end_date', 'date' ),
			'status'     => qldn_in( 'status', 'key' ),
			'quote_id'   => qldn_in( 'quote_id', 'int', 0 ),
			'scope'      => qldn_in( 'scope', 'textarea' ),
			'note'       => qldn_in( 'note', 'textarea' ),
		),
		$id
	);
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'contracts', array( 'edit' => $id ? $id : 'new', 'type' => $type ) );
	}
	if ( ! $id ) {
		// Hợp đồng mới: soạn luôn văn bản từ mẫu phù hợp đầu tiên.
		$tpls = array_values(
			array_filter(
				qldn_templates_for( 'contract', $type ),
				function ( $t ) {
					return '' !== $t->kind;
				}
			)
		);
		if ( $tpls ) {
			qldn_document_create( $tpls[0]->id, 'contract', $r );
		}
		$q = qldn_in( 'quote_id', 'int', 0 );
		if ( $q ) {
			global $wpdb;
			$wpdb->update( qldn_t( 'quotes' ), array( 'status' => 'accepted' ), array( 'id' => $q ) );
		}
	}
	qldn_notice( $id ? 'Đã cập nhật hợp đồng.' : 'Đã lập hợp đồng và soạn sẵn văn bản từ mẫu – mở văn bản bên dưới để kiểm tra.' );
	qldn_go( 'contracts', array( 'view' => $r ) );
}
add_action( 'admin_post_qldn_contract', 'qldn_handle_contract' );

/**
 * Thao tác: hiệu lực, thanh lý, huỷ, ghi nhận thanh toán, xoá.
 */
function qldn_handle_contract_action() {
	qldn_check( 'contract_action', 'qldn_docs' );
	global $wpdb;
	$c = qldn_get( 'contracts', qldn_in( 'id', 'int', 0 ) );
	if ( ! $c ) {
		qldn_go( 'contracts' );
	}
	qldn_contract_cap( $c->type );
	$t  = qldn_t( 'contracts' );
	$do = qldn_in( 'do', 'key' );
	if ( 'pay' === $do ) {
		$amount = qldn_in( 'amount', 'money', 0 );
		if ( $amount > 0 ) {
			$note = trim( $c->note . "\n" . current_time( 'd/m/Y' ) . ': thanh toán ' . qldn_money( $amount ) . ( qldn_in( 'memo' ) ? ' – ' . qldn_in( 'memo' ) : '' ) );
			$wpdb->query( $wpdb->prepare( "UPDATE $t SET paid = paid + %d, note = %s WHERE id = %d", $amount, $note, $c->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			qldn_notice( 'Đã ghi nhận thanh toán ' . qldn_money( $amount ) . '.' );
		}
	} elseif ( 'delete' === $do ) {
		$wpdb->delete( qldn_t( 'documents' ), array( 'context' => 'contract', 'object_id' => $c->id ) );
		$wpdb->delete( $t, array( 'id' => $c->id ) );
		qldn_notice( 'Đã xoá hợp đồng ' . esc_html( $c->code ) . '.' );
		qldn_go( 'contracts' );
	} elseif ( isset( qldn_contract_statuses()[ $do ] ) ) {
		$data = array( 'status' => $do );
		if ( 'active' === $do && ! $c->sign_date ) {
			$data['sign_date'] = qldn_today();
		}
		$wpdb->update( $t, $data, array( 'id' => $c->id ) );
		qldn_notice( 'Đã chuyển trạng thái: ' . qldn_contract_statuses()[ $do ] . '.' . ( 'liquidated' === $do ? ' Soạn biên bản thanh lý ở mục Văn bản bên dưới.' : '' ) );
	}
	qldn_go( 'contracts', array( 'view' => $c->id ) );
}
add_action( 'admin_post_qldn_contract_action', 'qldn_handle_contract_action' );

/**
 * Hợp đồng còn hiệu lực sắp hết hạn (và đã quá hạn mà chưa thanh lý).
 *
 * @param int $limit Số dòng.
 * @return array
 */
function qldn_contracts_expiring( $limit = 20 ) {
	global $wpdb;
	$until = gmdate( 'Y-m-d', strtotime( qldn_today() . ' +' . (int) qldn_opt( 'contract_remind' ) . ' days' ) );
	$where = current_user_can( 'qldn_hr' ) ? '' : " AND type <> 'labor'";
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'contracts' ) . " WHERE status = 'active' AND end_date IS NOT NULL AND end_date <= %s $where ORDER BY end_date LIMIT %d", $until, $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL
}

/**
 * Tên bên ký.
 *
 * @param object $c Hợp đồng.
 * @return string
 */
function qldn_contract_party( $c ) {
	$p = qldn_get( 'employee' === $c->party_type ? 'employees' : 'customers', $c->party_id );
	return $p ? $p->name : '';
}

/**
 * Trang Hợp đồng.
 */
function qldn_page_contracts() {
	global $wpdb;
	$edit = qldn_in( 'edit' );
	$view = qldn_in( 'view', 'int', 0 );
	if ( '' !== $edit ) {
		qldn_contract_form( 'new' === $edit ? null : qldn_get( 'contracts', (int) $edit ) );
		return;
	}
	if ( $view ) {
		qldn_contract_view( qldn_get( 'contracts', $view ) );
		return;
	}
	$t      = qldn_t( 'contracts' );
	$type   = qldn_in( 'type', 'key' );
	$status = qldn_in( 'status', 'key' );
	$hr     = current_user_can( 'qldn_hr' );
	$from   = "$t h LEFT JOIN " . qldn_t( 'customers' ) . " c ON h.party_type = 'customer' AND c.id = h.party_id LEFT JOIN " . qldn_t( 'employees' ) . " e ON h.party_type = 'employee' AND e.id = h.party_id";
	$where  = ( $hr ? '' : " AND h.type <> 'labor'" ) . qldn_like( qldn_in( 's' ), array( 'h.code', 'h.title', 'c.name', 'e.name' ) );
	if ( isset( qldn_contract_types()[ $type ] ) ) {
		$where .= $wpdb->prepare( ' AND h.type = %s', $type );
	}
	if ( 'expiring' === $status ) {
		$where .= $wpdb->prepare( " AND h.status = 'active' AND h.end_date IS NOT NULL AND h.end_date <= %s", gmdate( 'Y-m-d', strtotime( qldn_today() . ' +' . (int) qldn_opt( 'contract_remind' ) . ' days' ) ) );
	} elseif ( 'unpaid' === $status ) {
		$where .= " AND h.status IN ('active','liquidated') AND h.type <> 'labor' AND h.paid < h.value";
	} elseif ( isset( qldn_contract_statuses()[ $status ] ) ) {
		$where .= $wpdb->prepare( ' AND h.status = %s', $status );
	}
	$per             = 50;
	list( $paged, $off ) = qldn_paged( $per );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $from WHERE 1=1 $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT h.*, c.name AS cname, e.name AS ename FROM $from WHERE 1=1 $where ORDER BY h.id DESC LIMIT %d OFFSET %d", $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$sum   = $wpdb->get_row( "SELECT SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN status = 'active' AND type <> 'labor' THEN value ELSE 0 END) AS value, SUM(CASE WHEN status IN ('active','liquidated') AND type <> 'labor' AND paid < value THEN value - paid ELSE 0 END) AS unpaid FROM $t" . ( $hr ? '' : " WHERE type <> 'labor'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$exp   = count( qldn_contracts_expiring( 500 ) );
	$types = qldn_contract_types();
	if ( ! $hr ) {
		unset( $types['labor'] );
	}

	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Hợp đồng</h1>';
	foreach ( $types as $k => $v ) {
		if ( 'other' !== $k ) {
			echo ' <a class="page-title-action" href="' . esc_url( qldn_url( 'contracts', array( 'edit' => 'new', 'type' => $k ) ) ) . '">+ HĐ ' . esc_html( mb_strtolower( $v ) ) . '</a>';
		}
	}
	echo '<hr class="wp-header-end">';
	qldn_print_notices();
	echo '<div class="qldn-cards"><div class="qldn-card"><span>Đang hiệu lực</span><strong>' . esc_html( (int) $sum->active ) . ' hợp đồng</strong><em>Giá trị (trừ HĐ lao động): ' . esc_html( qldn_money( $sum->value ) ) . '</em></div>';
	echo '<a class="qldn-card' . ( $sum->unpaid > 0 ? ' qldn-card-warn' : '' ) . '" href="' . esc_url( qldn_url( 'contracts', array( 'status' => 'unpaid' ) ) ) . '"><span>Khách còn phải thanh toán</span><strong>' . esc_html( qldn_money( $sum->unpaid ) ) . '</strong></a>';
	echo '<a class="qldn-card' . ( $exp ? ' qldn-card-warn' : '' ) . '" href="' . esc_url( qldn_url( 'contracts', array( 'status' => 'expiring' ) ) ) . '"><span>Hết hạn / sắp hết hạn (' . esc_html( (int) qldn_opt( 'contract_remind' ) ) . ' ngày)</span><strong>' . esc_html( $exp ) . ' hợp đồng</strong></a></div>';
	$extra  = '<select name="type"><option value="">Mọi loại</option>' . qldn_options_html( $types, $type ) . '</select> ';
	$extra .= '<select name="status"><option value="">Mọi trạng thái</option>' . qldn_options_html( qldn_contract_statuses() + array( 'expiring' => 'Hết hạn / sắp hết hạn', 'unpaid' => 'Còn phải thu' ), $status ) . '</select> ';
	qldn_search_box( 'contracts', qldn_in( 's' ), $extra, 'Số HĐ / tên / bên ký' );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Số HĐ</th><th>Tên hợp đồng</th><th>Loại</th><th>Bên ký</th><th>Hiệu lực</th><th class="num">Giá trị</th><th class="num">Còn phải thu</th><th>Trạng thái</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="8">Chưa có hợp đồng.</td></tr>';
	}
	$all = qldn_contract_types();
	foreach ( $rows as $r ) {
		$left = 'labor' === $r->type ? 0 : max( 0, $r->value - $r->paid );
		echo '<tr><td><a href="' . esc_url( qldn_url( 'contracts', array( 'view' => $r->id ) ) ) . '"><strong>' . esc_html( $r->code ) . '</strong></a></td><td>' . esc_html( $r->title ) . '</td><td>' . esc_html( $all[ $r->type ] ?? $r->type ) . '</td><td>' . esc_html( $r->cname ? $r->cname : $r->ename ) . '</td>';
		echo '<td>' . esc_html( trim( qldn_date( $r->start_date ) . ( $r->end_date ? ' – ' . qldn_date( $r->end_date ) : ( 'labor' === $r->type && $r->start_date ? ' – không thời hạn' : '' ) ) ) ) . '</td>';
		echo '<td class="num">' . esc_html( qldn_money( $r->value, false ) . ( 'labor' === $r->type ? '/tháng' : '' ) ) . '</td><td class="num">' . ( $left ? qldn_badge( qldn_money( $left, false ), 'orange' ) : '' ) . '</td><td>' . qldn_contract_badge( $r ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
	echo '</div>';
}

/**
 * Form hợp đồng.
 *
 * @param object|null $c Hợp đồng.
 */
function qldn_contract_form( $c ) {
	global $wpdb;
	$type = $c ? $c->type : qldn_in( 'type', 'key', 'service' );
	$type = isset( qldn_contract_types()[ $type ] ) ? $type : 'service';
	qldn_contract_cap( $type );
	$labor = 'labor' === $type;
	// Lập từ báo giá.
	$quote = $c ? ( $c->quote_id ? qldn_get( 'quotes', $c->quote_id ) : null ) : ( qldn_in( 'quote', 'int', 0 ) ? qldn_get( 'quotes', qldn_in( 'quote', 'int', 0 ) ) : null );
	if ( $quote && ! $c ) {
		$has_free = (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . qldn_t( 'quote_items' ) . ' WHERE quote_id = %d AND product_id = 0', $quote->id ) );
		$type     = qldn_in( 'type', 'key' ) ? $type : ( $has_free ? 'service' : 'sale' );
	}
	$party = $c ? $c->party_id : ( $quote ? $quote->customer_id : qldn_in( 'party', 'int', 0 ) );
	$emp   = $labor && $party ? qldn_get( 'employees', $party ) : null;
	$scope = '';
	if ( $quote && ! $c ) {
		foreach ( qldn_quote_items( $quote->id ) as $it ) {
			$scope .= '- ' . $it->name . ': ' . qldn_qty( $it->qty ) . ' ' . $it->unit . "\n";
		}
	}

	echo '<div class="wrap qldn"><h1>' . ( $c ? 'Sửa hợp đồng ' . esc_html( $c->code ) : 'Lập hợp đồng ' . esc_html( mb_strtolower( qldn_contract_types()[ $type ] ) ) ) . '</h1>';
	qldn_print_notices();
	if ( $quote ) {
		echo '<p>Từ báo giá <a href="' . esc_url( qldn_url( 'quotes', array( 'view' => $quote->id ) ) ) . '">' . esc_html( $quote->code ) . '</a> – giá trị, bên ký và bảng hàng hoá được lấy theo báo giá.</p>';
	}
	qldn_form_open( 'contract' );
	echo '<input type="hidden" name="id" value="' . esc_attr( $c ? $c->id : 0 ) . '"><input type="hidden" name="quote_id" value="' . esc_attr( $quote ? $quote->id : 0 ) . '"><table class="form-table">';
	$types = qldn_contract_types();
	if ( ! current_user_can( 'qldn_hr' ) ) {
		unset( $types['labor'] );
	}
	qldn_field( 'Loại hợp đồng', 'type', $type, array( 'type' => 'select', 'options' => $types, 'attrs' => array( 'onchange' => "location.href='" . esc_js( qldn_url( 'contracts', array( 'edit' => $c ? $c->id : 'new', 'quote' => $quote ? $quote->id : 0, 'party' => $party ) ) ) . "&type='+this.value" ) ) );
	qldn_field( 'Số hợp đồng', 'code', $c ? $c->code : '', array( 'help' => $c ? '' : 'Để trống để tự đánh số: ' . esc_html( qldn_contract_next_code( $type ) ) ) );
	if ( $labor ) {
		qldn_field( 'Người lao động', 'employee_id', $party, array( 'type' => 'select', 'options' => array( 0 => 'Chọn nhân viên…' ) + qldn_employee_options(), 'required' => true, 'help' => 'Ngày sinh, CCCD, nơi cư trú, lương, phụ cấp lấy từ hồ sơ nhân viên – nhập đủ ở trang Nhân viên trước khi soạn.' ) );
	} else {
		$opts = array( 0 => 'Chọn khách hàng…' );
		foreach ( $wpdb->get_results( 'SELECT id, code, name FROM ' . qldn_t( 'customers' ) . ' ORDER BY name LIMIT 2000' ) as $r ) {
			$opts[ $r->id ] = $r->name . ' (' . $r->code . ')';
		}
		qldn_field( 'Khách hàng (bên ký)', 'customer_id', $party, array( 'type' => 'select', 'options' => $opts, 'required' => true, 'help' => 'Doanh nghiệp: nhập mã số thuế, người đại diện, chức vụ, tài khoản ở hồ sơ khách hàng để điền vào hợp đồng.' ) );
	}
	qldn_field( 'Tên hợp đồng', 'title', $c ? $c->title : ( $quote ? $quote->title : '' ), array( 'class' => 'large-text', 'help' => 'Để trống: "Hợp đồng … – tên bên ký".' ) );
	qldn_field( $labor ? 'Mức lương / tháng' : 'Giá trị hợp đồng', 'value', $c ? $c->value : ( $quote ? $quote->total : ( $emp ? $emp->base_salary : '' ) ), array( 'type' => 'money', 'help' => $labor ? 'Để trống: lấy lương cơ bản trong hồ sơ nhân viên.' : 'Đã gồm thuế GTGT (nếu có).' ) );
	if ( ! $labor ) {
		qldn_field( 'Đã thanh toán', 'paid', $c ? $c->paid : 0, array( 'type' => 'money' ) );
	}
	qldn_field( 'Ngày ký', 'sign_date', $c ? $c->sign_date : '', array( 'type' => 'date', 'class' => '' ) );
	qldn_field( 'Hiệu lực từ ngày', 'start_date', $c ? $c->start_date : '', array( 'type' => 'date', 'class' => '' ) );
	qldn_field( 'Đến ngày', 'end_date', $c ? $c->end_date : '', array( 'type' => 'date', 'class' => '', 'help' => $labor ? 'Để trống = hợp đồng không xác định thời hạn. Có thời hạn: tối đa 36 tháng.' : 'Để trống nếu hợp đồng kết thúc khi hoàn thành nghĩa vụ.' ) );
	qldn_field( 'Trạng thái', 'status', $c ? $c->status : 'draft', array( 'type' => 'select', 'options' => qldn_contract_statuses() ) );
	qldn_field( $labor ? 'Công việc, địa điểm làm việc' : 'Phạm vi công việc / hàng hoá', 'scope', $c ? $c->scope : $scope, array( 'type' => 'textarea', 'help' => 'Điền vào trường {{hd_pham_vi}} của mẫu.' ) );
	qldn_field( 'Ghi chú nội bộ', 'note', $c ? $c->note : '', array( 'type' => 'textarea' ) );
	echo '</table>';
	submit_button( $c ? 'Lưu hợp đồng' : 'Lập hợp đồng và soạn văn bản' );
	echo '</form></div>';
}

/**
 * Xem hợp đồng.
 *
 * @param object|null $c Hợp đồng.
 */
function qldn_contract_view( $c ) {
	if ( ! $c ) {
		echo '<div class="wrap qldn"><p>Không tìm thấy hợp đồng.</p></div>';
		return;
	}
	qldn_contract_cap( $c->type );
	$labor = 'labor' === $c->type;
	$link  = $labor ? qldn_url( 'employees', array( 'edit' => $c->party_id ) ) : qldn_url( 'customers', array( 'view' => $c->party_id ) );
	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">Hợp đồng ' . esc_html( $c->code ) . '</h1> ' . qldn_contract_badge( $c ) . ' <a class="page-title-action" href="' . esc_url( qldn_url( 'contracts', array( 'edit' => $c->id ) ) ) . '">Sửa</a><hr class="wp-header-end">'; // phpcs:ignore WordPress.Security.EscapeOutput
	qldn_print_notices();
	echo '<div class="qldn-cards">';
	echo '<div class="qldn-card"><span>' . ( $labor ? 'Mức lương' : 'Giá trị' ) . '</span><strong>' . esc_html( qldn_money( $c->value ) ) . '</strong><em>' . esc_html( qldn_money_words( $c->value ) ) . '</em></div>';
	if ( ! $labor ) {
		$left = max( 0, $c->value - $c->paid );
		echo '<div class="qldn-card"><span>Đã thanh toán</span><strong>' . esc_html( qldn_money( $c->paid ) ) . '</strong></div><div class="qldn-card' . ( $left ? ' qldn-card-warn' : '' ) . '"><span>Còn phải thu</span><strong>' . esc_html( qldn_money( $left ) ) . '</strong></div>';
	}
	echo '<div class="qldn-card"><span>Thời hạn</span><strong style="font-size:15px">' . esc_html( qldn_contract_term( $c ) ? qldn_contract_term( $c ) : '—' ) . '</strong></div></div>';
	echo '<table class="widefat qldn-info qldn-narrow"><tbody>';
	$info = array(
		'Tên hợp đồng' => esc_html( $c->title ),
		'Loại'         => esc_html( qldn_contract_types()[ $c->type ] ?? $c->type ),
		'Bên ký'       => '<a href="' . esc_url( $link ) . '">' . esc_html( qldn_contract_party( $c ) ) . '</a>',
		'Ngày ký'      => esc_html( qldn_date( $c->sign_date ) ),
		'Báo giá'      => $c->quote_id && ( $q = qldn_get( 'quotes', $c->quote_id ) ) ? '<a href="' . esc_url( qldn_url( 'quotes', array( 'view' => $q->id ) ) ) . '">' . esc_html( $q->code ) . '</a>' : '',
		'Phạm vi'      => nl2br( esc_html( $c->scope ) ),
		'Ghi chú'      => nl2br( esc_html( $c->note ) ),
	);
	foreach ( $info as $k => $v ) {
		if ( '' !== $v ) {
			echo '<tr><th>' . esc_html( $k ) . '</th><td>' . wp_kses_post( $v ) . '</td></tr>';
		}
	}
	echo '</tbody></table>';

	$btn = function ( $do, $label, $class = 'button', $confirm = '' ) use ( $c ) {
		qldn_form_open( 'contract_action', array( 'class' => 'qldn-inline' ) + ( $confirm ? array( 'onsubmit' => 'return confirm(' . wp_json_encode( $confirm ) . ')' ) : array() ) );
		echo '<input type="hidden" name="id" value="' . esc_attr( $c->id ) . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '"><button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
	};
	echo '<p>';
	if ( 'draft' === $c->status ) {
		$btn( 'active', 'Đã ký – bắt đầu hiệu lực', 'button button-primary' );
	}
	if ( 'active' === $c->status ) {
		$btn( 'liquidated', 'Thanh lý hợp đồng', 'button', 'Chuyển hợp đồng sang "Đã thanh lý"?' );
		if ( $labor || ( $c->end_date && $c->end_date <= gmdate( 'Y-m-d', strtotime( qldn_today() . ' +' . (int) qldn_opt( 'contract_remind' ) . ' days' ) ) ) ) {
			echo '<a class="button" href="' . esc_url( qldn_url( 'contracts', array( 'edit' => 'new', 'type' => $c->type, 'party' => $c->party_id ) ) ) . '">Lập hợp đồng mới / gia hạn</a> ';
		}
	}
	if ( in_array( $c->status, array( 'draft', 'active' ), true ) ) {
		$btn( 'cancelled', 'Huỷ hợp đồng', 'button', 'Huỷ hợp đồng ' . $c->code . '?' );
	}
	echo '</p>';
	if ( ! $labor && $c->paid < $c->value && 'cancelled' !== $c->status ) {
		echo '<h2>Ghi nhận thanh toán</h2>';
		qldn_form_open( 'contract_action', array( 'class' => 'qldn-filter' ) );
		echo '<input type="hidden" name="id" value="' . esc_attr( $c->id ) . '"><input type="hidden" name="do" value="pay"><input type="text" inputmode="numeric" class="qldn-money" name="amount" value="' . esc_attr( qldn_money( $c->value - $c->paid, false ) ) . '"> <input type="text" name="memo" placeholder="Đợt / hình thức (VD: Đợt 1 – chuyển khoản)"> <button class="button button-primary">Ghi nhận</button></form>';
	}
	qldn_documents_box( 'contract', $c->id, $c->type );
	if ( in_array( $c->status, array( 'draft', 'cancelled' ), true ) ) {
		echo '<p>';
		$btn( 'delete', 'Xoá hợp đồng', 'button-link qldn-neg', 'Xoá hợp đồng ' . $c->code . ' và các văn bản kèm theo?' );
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Hợp đồng & báo giá của 1 khách / nhân viên (hiện ở hồ sơ).
 *
 * @param string $party_type customer|employee.
 * @param int    $id         ID.
 */
function qldn_party_contracts_box( $party_type, $id ) {
	global $wpdb;
	if ( ! current_user_can( 'qldn_docs' ) || ( 'employee' === $party_type && ! current_user_can( 'qldn_hr' ) ) ) {
		return;
	}
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'contracts' ) . ' WHERE party_type = %s AND party_id = %d ORDER BY id DESC', $party_type, $id ) );
	echo '<h2>Hợp đồng</h2><table class="widefat striped qldn-table"><tbody>';
	if ( ! $rows ) {
		echo '<tr><td>Chưa có hợp đồng.</td></tr>';
	}
	foreach ( $rows as $r ) {
		echo '<tr><td><a href="' . esc_url( qldn_url( 'contracts', array( 'view' => $r->id ) ) ) . '">' . esc_html( $r->code ) . '</a> ' . esc_html( $r->title ) . '</td><td>' . esc_html( qldn_date( $r->start_date ) . ( $r->end_date ? ' – ' . qldn_date( $r->end_date ) : '' ) ) . '</td><td>' . qldn_contract_badge( $r ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table><p>';
	if ( 'employee' === $party_type ) {
		echo '<a class="button" href="' . esc_url( qldn_url( 'contracts', array( 'edit' => 'new', 'type' => 'labor', 'party' => $id ) ) ) . '">Lập hợp đồng lao động</a> ';
		echo '<a class="button" href="' . esc_url( qldn_url( 'docs', array( 'tab' => 'new' ) ) ) . '">Soạn văn bản khác</a>';
	} else {
		$quotes = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'quotes' ) . ' WHERE customer_id = %d ORDER BY id DESC LIMIT 20', $id ) );
		echo '<a class="button" href="' . esc_url( qldn_url( 'quotes', array( 'edit' => 'new', 'customer' => $id ) ) ) . '">Lập báo giá</a> <a class="button" href="' . esc_url( qldn_url( 'contracts', array( 'edit' => 'new', 'type' => 'service', 'party' => $id ) ) ) . '">Lập hợp đồng</a></p>';
		echo '<h2>Báo giá</h2><table class="widefat striped qldn-table"><tbody>';
		if ( ! $quotes ) {
			echo '<tr><td>Chưa có báo giá.</td></tr>';
		}
		foreach ( $quotes as $q ) {
			echo '<tr><td><a href="' . esc_url( qldn_url( 'quotes', array( 'view' => $q->id ) ) ) . '">' . esc_html( $q->code ) . '</a> ' . esc_html( $q->title ) . '</td><td class="num">' . esc_html( qldn_money( $q->total, false ) ) . '</td><td>' . qldn_quote_badge( $q ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</tbody></table><p>';
	}
	echo '</p>';
}
