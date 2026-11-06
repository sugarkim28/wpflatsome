<?php
/**
 * Bảo hành: quản lý phiếu, nhập CSV, tra cứu công khai (mã phiếu / serial / mã yêu cầu + số điện thoại).
 * Không hiển thị thông tin cá nhân khi chưa xác thực bằng số điện thoại.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lấy phiếu theo ID.
 *
 * @param int $id ID.
 * @return object|null
 */
function th119_get_warranty( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . th119_table( 'warranty' ) . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
}

/**
 * Tính ngày hết bảo hành.
 *
 * @param string $start  Ngày bắt đầu Y-m-d.
 * @param int    $months Số tháng.
 * @return string|null
 */
function th119_warranty_end( $start, $months ) {
	if ( ! $start || $months <= 0 ) {
		return null;
	}
	$d = date_create( $start );
	if ( ! $d ) {
		return null;
	}
	// Cộng tháng không tràn: 31/01 + 1 tháng → hết hạn 28/02 (cuối tháng), 15/01 + 3 tháng → 14/04.
	$day   = (int) $d->format( 'j' );
	$first = date_create( $d->format( 'Y-m-01' ) );
	$first->modify( '+' . (int) $months . ' months' );
	$last = (int) $first->format( 't' );
	if ( $day > $last ) {
		return $first->format( 'Y-m-' ) . sprintf( '%02d', $last );
	}
	$first->setDate( (int) $first->format( 'Y' ), (int) $first->format( 'n' ), $day );
	$first->modify( '-1 day' );
	return $first->format( 'Y-m-d' );
}

/* ---------------------------------------------------------------------------
 * Tra cứu công khai.
 * ------------------------------------------------------------------------- */

/**
 * Tra cứu theo mã + số điện thoại.
 *
 * @param string $key   Mã phiếu / serial / mã yêu cầu.
 * @param string $phone Số điện thoại.
 * @return array
 */
function th119_lookup( $key, $phone ) {
	global $wpdb;
	$key   = strtoupper( preg_replace( '/[^A-Za-z0-9\-_.\/]/', '', (string) $key ) );
	$phone = th119_normalize_phone( $phone );
	if ( strlen( $key ) < 3 || '' === $phone ) {
		return array( 'ok' => false, 'message' => 'Vui lòng nhập mã phiếu / số serial / mã yêu cầu và số điện thoại đã đăng ký.' );
	}
	if ( ! th119_rate_ok( 'lookup', 10, 15 * MINUTE_IN_SECONDS ) ) {
		return array( 'ok' => false, 'message' => 'Bạn đã tra cứu quá nhiều lần. Vui lòng thử lại sau 15 phút hoặc gọi hotline ' . th119_opt( 'hotline' ) . '.' );
	}
	$items = array();

	// Phiếu bảo hành: khớp mã phiếu hoặc serial VÀ số điện thoại.
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . th119_table( 'warranty' ) . ' WHERE (code = %s OR UPPER(serial) = %s) AND phone = %s ORDER BY id DESC LIMIT 10', $key, $key, $phone ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$ws   = th119_warranty_statuses();
	foreach ( $rows as $r ) {
		$status = $r->status;
		if ( $r->warranty_until && in_array( $status, array( 'active', 'returned' ), true ) ) {
			$status = strtotime( $r->warranty_until ) >= strtotime( current_time( 'Y-m-d' ) ) ? 'active' : 'expired';
		}
		$items[] = array(
			'type'     => 'Phiếu bảo hành',
			'code'     => $r->code,
			'customer' => th119_mask_name( $r->customer_name ),
			'device'   => $r->device,
			'serial'   => $r->serial,
			'service'  => $r->service,
			'received' => th119_date( $r->received_at ),
			'returned' => th119_date( $r->returned_at ),
			'until'    => $r->warranty_until ? th119_date( $r->warranty_until ) : '',
			'months'   => (int) $r->warranty_months,
			'status'   => isset( $ws[ $status ] ) ? $ws[ $status ] : $status,
			'state'    => $status,
			'note'     => (string) $r->public_note,
			'history'  => th119_public_history( 'warranty', $r->id, $ws ),
		);
	}

	// Yêu cầu hỗ trợ / lịch hẹn: khớp mã yêu cầu VÀ số điện thoại.
	$t = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . th119_table( 'tickets' ) . ' WHERE code = %s AND phone = %s', $key, $phone ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	if ( $t ) {
		$ts      = th119_ticket_statuses();
		$items[] = array(
			'type'     => 'booking' === $t->request_type ? 'Lịch hẹn' : 'Yêu cầu hỗ trợ',
			'code'     => $t->code,
			'customer' => th119_mask_name( $t->name ),
			'device'   => $t->device,
			'serial'   => '',
			'service'  => '',
			'received' => th119_date( $t->created_at ),
			'returned' => '',
			'until'    => '',
			'months'   => 0,
			'status'   => isset( $ts[ $t->status ] ) ? $ts[ $t->status ] : $t->status,
			'state'    => $t->status,
			'note'     => '',
			'history'  => th119_public_history( 'ticket', $t->id, $ts ),
		);
	}

	th119_stat_hit( 'lookup', '' );
	th119_stat_hit( 'lookup_result', $items ? 'found' : 'notfound' );
	if ( ! $items ) {
		return array( 'ok' => false, 'message' => 'Không tìm thấy thông tin khớp với mã và số điện thoại đã nhập. Vui lòng kiểm tra lại hoặc gọi hotline ' . th119_opt( 'hotline' ) . '.' );
	}
	return array( 'ok' => true, 'items' => $items );
}

/**
 * Lịch sử công khai (chỉ dòng được đánh dấu khách xem được).
 *
 * @param string $type     Loại.
 * @param int    $id       ID.
 * @param array  $statuses Tên trạng thái.
 * @return array
 */
function th119_public_history( $type, $id, $statuses ) {
	$out = array();
	foreach ( th119_get_logs( $type, $id, true ) as $l ) {
		$out[] = array(
			'date'   => th119_date( $l->created_at, true ),
			'status' => $l->status_to && isset( $statuses[ $l->status_to ] ) ? $statuses[ $l->status_to ] : '',
			'note'   => (string) $l->note,
		);
	}
	return $out;
}

/**
 * HTML kết quả tra cứu.
 *
 * @param array $res Kết quả.
 * @return string
 */
function th119_lookup_html( $res ) {
	if ( empty( $res['ok'] ) ) {
		return '<div class="th-alert th-alert--err">' . esc_html( $res['message'] ) . '</div>';
	}
	$html = '';
	foreach ( $res['items'] as $it ) {
		$html .= '<div class="th-lookup-card"><div class="th-lookup-card__head"><span>' . esc_html( $it['type'] ) . ' <strong>' . esc_html( $it['code'] ) . '</strong></span><span class="th-badge th-badge--' . esc_attr( $it['state'] ) . '">' . esc_html( $it['status'] ) . '</span></div><dl>';
		$rows  = array(
			'Khách hàng'        => $it['customer'],
			'Thiết bị'          => $it['device'],
			'Serial'            => $it['serial'],
			'Dịch vụ / linh kiện' => $it['service'],
			'Ngày tiếp nhận'    => $it['received'],
			'Ngày trả máy'      => '—' === $it['returned'] ? '' : $it['returned'],
			'Thời hạn bảo hành' => $it['months'] ? $it['months'] . ' tháng' . ( $it['until'] ? ' – đến hết ngày ' . $it['until'] : '' ) : '',
			'Ghi chú'           => $it['note'],
		);
		foreach ( $rows as $k => $v ) {
			if ( '' !== (string) $v ) {
				$html .= '<dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd>';
			}
		}
		$html .= '</dl>';
		if ( $it['history'] ) {
			$html .= '<h4>Lịch sử xử lý</h4><ol class="th-timeline">';
			foreach ( $it['history'] as $h ) {
				$html .= '<li><time>' . esc_html( $h['date'] ) . '</time> ' . ( $h['status'] ? '<strong>' . esc_html( $h['status'] ) . '</strong>' : '' ) . ( $h['note'] ? '<br>' . esc_html( $h['note'] ) : '' ) . '</li>';
			}
			$html .= '</ol>';
		}
		$html .= '</div>';
	}
	return $html;
}

/**
 * AJAX tra cứu.
 */
function th119_ajax_lookup() {
	$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- token HMAC + làm sạch trong th119_lookup.
	if ( ! empty( $in['website'] ) || ! th119_check_form_token( 'lookup', isset( $in['th_token'] ) ? $in['th_token'] : '' ) || ! th119_same_origin() ) {
		wp_send_json( array( 'ok' => false, 'html' => th119_lookup_html( array( 'ok' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang.' ) ) ), 400 );
	}
	$res = th119_lookup( isset( $in['key'] ) ? $in['key'] : '', isset( $in['phone'] ) ? $in['phone'] : '' );
	wp_send_json( array( 'ok' => ! empty( $res['ok'] ), 'html' => th119_lookup_html( $res ) ) );
}
add_action( 'wp_ajax_th119_lookup', 'th119_ajax_lookup' );
add_action( 'wp_ajax_nopriv_th119_lookup', 'th119_ajax_lookup' );

/**
 * Form tra cứu bảo hành / tiến độ sửa chữa.
 *
 * @return string
 */
function th119_lookup_shortcode() {
	th119_enqueue_front();
	$result = '';
	// Trình duyệt tắt JavaScript: form gửi POST về chính trang này.
	if ( isset( $_POST['th_lookup'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $in['website'] ) && th119_check_form_token( 'lookup', isset( $in['th_token'] ) ? $in['th_token'] : '' ) ) {
			$result = th119_lookup_html( th119_lookup( isset( $in['key'] ) ? $in['key'] : '', isset( $in['phone'] ) ? $in['phone'] : '' ) );
		}
	}
	ob_start();
	?>
	<form class="th-form th-lookup-form" method="post" novalidate>
		<h3 class="th-form__title">Tra cứu bảo hành &amp; tiến độ sửa chữa</h3>
		<p class="th-form__sub">Nhập <strong>mã phiếu</strong>, <strong>số serial</strong> hoặc <strong>mã yêu cầu</strong> kèm <strong>số điện thoại đã đăng ký</strong>. Thông tin chỉ hiển thị khi cả hai khớp.</p>
		<input type="hidden" name="th_lookup" value="1">
		<input type="hidden" name="th_token" value="<?php echo esc_attr( th119_form_token( 'lookup' ) ); ?>">
		<div class="th-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
		<div class="th-grid">
			<p class="th-field"><label for="th-lk-key">Mã phiếu / Serial / Mã yêu cầu</label><input id="th-lk-key" name="key" type="text" required maxlength="80" autocapitalize="characters" placeholder="Ví dụ: BH261106-7K3Q"></p>
			<p class="th-field"><label for="th-lk-phone">Số điện thoại</label><input id="th-lk-phone" name="phone" type="tel" required inputmode="tel" autocomplete="tel"></p>
		</div>
		<button type="submit" class="th-btn th-btn--primary">Tra cứu</button>
		<div class="th-lookup-result" aria-live="polite"><?php echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong th119_lookup_html. ?></div>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode( 'th119_lookup', 'th119_lookup_shortcode' );

/* ---------------------------------------------------------------------------
 * Quản trị.
 * ------------------------------------------------------------------------- */

/**
 * Trang Bảo hành.
 */
function th119_warranty_page() {
	if ( ! current_user_can( 'th119_manage_warranty' ) ) {
		wp_die( 'Không có quyền.' );
	}
	if ( isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		th119_warranty_form( absint( $_GET['edit'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	global $wpdb;
	$t      = th119_table( 'warranty' );
	$ws     = th119_warranty_statuses();
	$s      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$paged  = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per    = 30;
	if ( '' !== $s ) {
		$like  = '%' . $wpdb->esc_like( $s ) . '%';
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE code LIKE %s OR serial LIKE %s OR phone LIKE %s OR customer_name LIKE %s OR device LIKE %s", $like, $like, $like, $like, $like ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE code LIKE %s OR serial LIKE %s OR phone LIKE %s OR customer_name LIKE %s OR device LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d", $like, $like, $like, $like, $like, $per, ( $paged - 1 ) * $per ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	} else {
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t ORDER BY id DESC LIMIT %d OFFSET %d", $per, ( $paged - 1 ) * $per ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}
	$base = admin_url( 'admin.php?page=th119-warranty' );
	echo '<div class="wrap th119-wrap"><h1 class="wp-heading-inline">Phiếu bảo hành</h1>';
	printf( ' <a class="page-title-action" href="%s">Thêm phiếu</a>', esc_url( add_query_arg( 'edit', 0, $base ) ) );
	printf( ' <a class="page-title-action" href="%s">Xuất CSV</a>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=th119_export&what=warranty' ), 'th119_export' ) ) );
	if ( isset( $_GET['imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf( '<div class="notice notice-success"><p>Đã nhập %d phiếu, bỏ qua %d dòng (trùng mã hoặc thiếu dữ liệu).</p></div>', absint( $_GET['imported'] ), isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	echo '<p class="description">Khách tra cứu ở trang có shortcode <code>[th119_lookup]</code> bằng mã phiếu hoặc serial + số điện thoại.</p>';
	echo '<form method="get" class="th119-search"><input type="hidden" name="page" value="th119-warranty"><input type="search" name="s" value="' . esc_attr( $s ) . '" placeholder="Mã phiếu, serial, SĐT, tên, thiết bị"> <button class="button">Tìm</button></form>';
	echo '<table class="widefat striped th119-table"><thead><tr><th>Mã phiếu</th><th>Khách hàng</th><th>Thiết bị / Serial</th><th>Tiếp nhận</th><th>Bảo hành đến</th><th>Trạng thái</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="6">Chưa có phiếu bảo hành.</td></tr>';
	}
	foreach ( $rows as $r ) {
		printf(
			'<tr><td><a href="%1$s"><strong>%2$s</strong></a></td><td>%3$s<br>%4$s</td><td>%5$s<br><code>%6$s</code></td><td>%7$s</td><td>%8$s</td><td><span class="th119-badge th119-badge--%9$s">%10$s</span></td></tr>',
			esc_url( add_query_arg( 'edit', $r->id, $base ) ),
			esc_html( $r->code ),
			esc_html( $r->customer_name ),
			esc_html( $r->phone ),
			esc_html( $r->device ),
			esc_html( $r->serial ),
			esc_html( th119_date( $r->received_at ) ),
			esc_html( th119_date( $r->warranty_until ) ),
			esc_attr( $r->status ),
			esc_html( isset( $ws[ $r->status ] ) ? $ws[ $r->status ] : $r->status )
		);
	}
	echo '</tbody></table>';
	$pages = (int) ceil( $total / $per );
	if ( $pages > 1 ) {
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ) ) ) . '</div></div>';
	}

	echo '<div class="th119-card" style="margin-top:24px"><h2>Nhập phiếu từ file CSV</h2><p>Cột (dòng đầu là tiêu đề): <code>code, serial, customer_name, phone, device, service, received_at, warranty_months, status, public_note</code>. Ngày dạng <code>2026-11-06</code> hoặc <code>06/11/2026</code>. Mã trống = tự sinh. Mã đã có sẽ bỏ qua (không ghi đè).</p>';
	echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'th119_warranty_import' );
	echo '<input type="hidden" name="action" value="th119_warranty_import"><input type="file" name="csv" accept=".csv,text/csv" required> ';
	submit_button( 'Nhập CSV', 'secondary', 'submit', false );
	echo '</form></div></div>';
}

/**
 * Form thêm / sửa phiếu.
 *
 * @param int $id ID (0 = thêm mới).
 */
function th119_warranty_form( $id ) {
	$r  = $id ? th119_get_warranty( $id ) : null;
	$ws = th119_warranty_statuses();
	$v  = array(
		'code'            => '',
		'serial'          => '',
		'customer_name'   => '',
		'phone'           => '',
		'device'          => '',
		'service'         => '',
		'received_at'     => current_time( 'Y-m-d' ),
		'returned_at'     => '',
		'warranty_months' => (int) th119_opt( 'warranty_default' ),
		'status'          => 'received',
		'public_note'     => '',
		'internal_note'   => '',
		'ticket_id'       => 0,
	);
	if ( $r ) {
		foreach ( $v as $k => $_ ) {
			$v[ $k ] = isset( $r->$k ) ? $r->$k : $v[ $k ];
		}
	} elseif ( ! empty( $_GET['from_ticket'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$t = th119_get_ticket( absint( $_GET['from_ticket'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $t ) {
			$v['customer_name'] = $t->name;
			$v['phone']         = $t->phone;
			$v['device']        = $t->device;
			$v['ticket_id']     = (int) $t->id;
			$v['internal_note'] = 'Tạo từ yêu cầu ' . $t->code;
		}
	}
	echo '<div class="wrap th119-wrap"><h1>' . ( $r ? 'Phiếu ' . esc_html( $r->code ) : 'Thêm phiếu bảo hành' ) . '</h1><p><a href="' . esc_url( admin_url( 'admin.php?page=th119-warranty' ) ) . '">← Danh sách</a></p>';
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success"><p>Đã lưu phiếu.</p></div>';
	}
	if ( isset( $_GET['err'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['err'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	echo '<div class="th119-cols"><div class="th119-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'th119_warranty_save' );
	echo '<input type="hidden" name="action" value="th119_warranty_save"><input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="w[ticket_id]" value="' . (int) $v['ticket_id'] . '"><table class="form-table">';
	$fields = array(
		'code'            => array( 'Mã phiếu', 'text', 'Trống = tự sinh (BH + ngày + mã ngẫu nhiên). In mã này lên phiếu giao khách.' ),
		'serial'          => array( 'Số serial / Service Tag', 'text', '' ),
		'customer_name'   => array( 'Tên khách hàng *', 'text', '' ),
		'phone'           => array( 'Số điện thoại khách *', 'text', 'Khách dùng số này để tra cứu.' ),
		'device'          => array( 'Thiết bị *', 'text', 'Ví dụ: Laptop Dell Inspiron 15 3520' ),
		'service'         => array( 'Dịch vụ / linh kiện bảo hành', 'text', 'Ví dụ: Thay SSD 512GB, vệ sinh tra keo' ),
		'received_at'     => array( 'Ngày tiếp nhận', 'date', '' ),
		'returned_at'     => array( 'Ngày trả máy', 'date', 'Thời hạn bảo hành tính từ ngày trả máy (trống = từ ngày tiếp nhận).' ),
		'warranty_months' => array( 'Thời hạn bảo hành (tháng)', 'number', '' ),
		'public_note'     => array( 'Ghi chú cho khách (hiện khi tra cứu)', 'textarea', '' ),
		'internal_note'   => array( 'Ghi chú nội bộ', 'textarea', 'Không hiển thị cho khách.' ),
	);
	foreach ( $fields as $k => $f ) {
		echo '<tr><th><label for="w-' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( 'textarea' === $f[1] ) {
			printf( '<textarea id="w-%1$s" name="w[%1$s]" rows="3" class="large-text">%2$s</textarea>', esc_attr( $k ), esc_textarea( $v[ $k ] ) );
		} else {
			printf( '<input type="%3$s" id="w-%1$s" name="w[%1$s]" value="%2$s" class="%4$s">', esc_attr( $k ), esc_attr( $v[ $k ] ), esc_attr( $f[1] ), 'number' === $f[1] ? 'small-text' : 'regular-text' );
		}
		echo $f[2] ? '<p class="description">' . esc_html( $f[2] ) . '</p>' : '';
		echo '</td></tr>';
	}
	echo '<tr><th>Trạng thái</th><td><select name="w[status]">';
	foreach ( $ws as $k => $label ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $v['status'], $k, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></td></tr><tr><th>Ghi chú lịch sử</th><td><textarea name="log_note" rows="2" class="large-text" placeholder="Ví dụ: Đã thay SSD, chờ khách nhận máy"></textarea><label><input type="checkbox" name="log_public" value="1" checked> Khách xem được</label></td></tr></table>';
	submit_button( $r ? 'Lưu phiếu' : 'Tạo phiếu' );
	echo '</form></div>';
	if ( $r ) {
		echo '<div class="th119-card"><h2>Lịch sử</h2>';
		th119_render_logs( th119_get_logs( 'warranty', $r->id ), $ws );
		echo '</div>';
	}
	echo '</div></div>';
}

/**
 * Chuẩn hoá ngày (Y-m-d hoặc d/m/Y).
 *
 * @param string $d Ngày.
 * @return string|null
 */
function th119_parse_date( $d ) {
	$d = trim( (string) $d );
	if ( preg_match( '#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $d, $m ) ) {
		return checkdate( (int) $m[2], (int) $m[1], (int) $m[3] ) ? sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] ) : null;
	}
	if ( preg_match( '#^(\d{4})-(\d{2})-(\d{2})$#', $d, $m ) ) {
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $d : null;
	}
	return null;
}

/**
 * Làm sạch dữ liệu phiếu.
 *
 * @param array $w Dữ liệu thô.
 * @return array
 */
function th119_clean_warranty( $w ) {
	$ws   = th119_warranty_statuses();
	$data = array(
		'code'            => strtoupper( preg_replace( '/[^A-Za-z0-9\-_]/', '', (string) ( $w['code'] ?? '' ) ) ),
		'serial'          => sanitize_text_field( $w['serial'] ?? '' ),
		'customer_name'   => sanitize_text_field( $w['customer_name'] ?? '' ),
		'phone'           => th119_normalize_phone( $w['phone'] ?? '' ),
		'device'          => sanitize_text_field( $w['device'] ?? '' ),
		'service'         => sanitize_text_field( $w['service'] ?? '' ),
		'received_at'     => th119_parse_date( $w['received_at'] ?? '' ),
		'returned_at'     => th119_parse_date( $w['returned_at'] ?? '' ),
		'warranty_months' => min( 120, absint( $w['warranty_months'] ?? 0 ) ),
		'status'          => isset( $ws[ $w['status'] ?? '' ] ) ? $w['status'] : 'received',
		'public_note'     => sanitize_textarea_field( $w['public_note'] ?? '' ),
		'internal_note'   => sanitize_textarea_field( $w['internal_note'] ?? '' ),
		'ticket_id'       => absint( $w['ticket_id'] ?? 0 ),
	);
	$start                  = $data['returned_at'] ? $data['returned_at'] : $data['received_at'];
	$data['warranty_until'] = th119_warranty_end( $start, $data['warranty_months'] );
	return $data;
}

/**
 * Lưu phiếu.
 */
function th119_warranty_save() {
	check_admin_referer( 'th119_warranty_save' );
	if ( ! current_user_can( 'th119_manage_warranty' ) ) {
		wp_die( 'Không có quyền.' );
	}
	global $wpdb;
	$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$w    = isset( $_POST['w'] ) && is_array( $_POST['w'] ) ? wp_unslash( $_POST['w'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch trong th119_clean_warranty.
	$data = th119_clean_warranty( $w );
	$back = admin_url( 'admin.php?page=th119-warranty&edit=' . $id );
	if ( '' === $data['customer_name'] || '' === $data['phone'] || '' === $data['device'] ) {
		wp_safe_redirect( add_query_arg( 'err', rawurlencode( 'Cần nhập tên khách, số điện thoại hợp lệ và thiết bị.' ), $back ) );
		exit;
	}
	if ( '' === $data['code'] ) {
		$data['code'] = th119_new_code( 'BH', 'warranty' );
	}
	$dup = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . th119_table( 'warranty' ) . ' WHERE code = %s AND id != %d', $data['code'], $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	if ( $dup ) {
		wp_safe_redirect( add_query_arg( 'err', rawurlencode( 'Mã phiếu đã tồn tại.' ), $back ) );
		exit;
	}
	$note   = isset( $_POST['log_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['log_note'] ) ) : '';
	$public = ! empty( $_POST['log_public'] );
	$data['updated_at'] = th119_now();
	if ( $id ) {
		$old = th119_get_warranty( $id );
		if ( ! $old ) {
			wp_die( 'Không tìm thấy phiếu.' );
		}
		$wpdb->update( th119_table( 'warranty' ), $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $old->status !== $data['status'] || '' !== $note ) {
			th119_add_log( 'warranty', $id, $data['status'], $old->status, $note, $public );
		}
		th119_log_activity( 'warranty_update', $data['code'], $old->status . ' → ' . $data['status'] );
	} else {
		$data['created_by'] = get_current_user_id();
		$data['created_at'] = th119_now();
		$wpdb->insert( th119_table( 'warranty' ), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$id = (int) $wpdb->insert_id;
		th119_add_log( 'warranty', $id, $data['status'], '', $note ? $note : 'Tạo phiếu.', true );
		th119_log_activity( 'warranty_create', $data['code'], '' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=th119-warranty&edit=' . $id . '&saved=1' ) );
	exit;
}
add_action( 'admin_post_th119_warranty_save', 'th119_warranty_save' );

/**
 * Nhập CSV (không ghi đè phiếu có sẵn).
 */
function th119_warranty_import() {
	check_admin_referer( 'th119_warranty_import' );
	if ( ! current_user_can( 'th119_manage_warranty' ) ) {
		wp_die( 'Không có quyền.' );
	}
	if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) || (int) $_FILES['csv']['size'] > 5 * MB_IN_BYTES ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		wp_die( 'File CSV không hợp lệ (tối đa 5 MB).' );
	}
	$ext = strtolower( pathinfo( sanitize_file_name( wp_unslash( $_FILES['csv']['name'] ) ), PATHINFO_EXTENSION ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( 'csv' !== $ext ) {
		wp_die( 'Chỉ nhận file .csv' );
	}
	global $wpdb;
	$fh   = fopen( $_FILES['csv']['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$head = fgetcsv( $fh );
	if ( ! $head ) {
		wp_die( 'File trống.' );
	}
	$head = array_map(
		function ( $h ) {
			return strtolower( trim( preg_replace( '/^\xEF\xBB\xBF/', '', $h ) ) );
		},
		$head
	);
	$ok   = 0;
	$skip = 0;
	$n    = 0;
	while ( ( $row = fgetcsv( $fh ) ) !== false && $n < 5000 ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		$n++;
		if ( count( $row ) !== count( $head ) ) {
			$skip++;
			continue;
		}
		$data = th119_clean_warranty( array_combine( $head, $row ) );
		if ( '' === $data['customer_name'] || '' === $data['phone'] || '' === $data['device'] ) {
			$skip++;
			continue;
		}
		if ( '' === $data['code'] ) {
			$data['code'] = th119_new_code( 'BH', 'warranty' );
		} elseif ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . th119_table( 'warranty' ) . ' WHERE code = %s', $data['code'] ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
			$skip++;
			continue;
		}
		$data['created_by'] = get_current_user_id();
		$data['created_at'] = th119_now();
		$data['updated_at'] = th119_now();
		if ( $wpdb->insert( th119_table( 'warranty' ), $data ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			th119_add_log( 'warranty', (int) $wpdb->insert_id, $data['status'], '', 'Nhập từ CSV.', false );
			$ok++;
		} else {
			$skip++;
		}
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	th119_log_activity( 'warranty_import', 'csv', "Nhập $ok, bỏ qua $skip" );
	wp_safe_redirect( admin_url( 'admin.php?page=th119-warranty&imported=' . $ok . '&skipped=' . $skip ) );
	exit;
}
add_action( 'admin_post_th119_warranty_import', 'th119_warranty_import' );
