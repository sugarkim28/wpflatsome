<?php
/**
 * Hàm dùng chung: tên bảng, định dạng tiền, form, phân trang, thông báo, CSV.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tên bảng đầy đủ.
 *
 * @param string $name products, orders...
 * @return string
 */
function qldn_t( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'qldn_' . $name;
}

/**
 * Lấy 1 dòng theo id.
 *
 * @param string $table Tên bảng ngắn.
 * @param int    $id    ID.
 * @return object|null
 */
function qldn_get( $table, $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( $table ) . ' WHERE id = %d', $id ) );
}

/**
 * Thời gian hiện tại theo múi giờ của site (Y-m-d H:i:s).
 *
 * @return string
 */
function qldn_now() {
	return current_time( 'mysql' );
}

/**
 * Ngày hôm nay theo múi giờ của site.
 *
 * @return string
 */
function qldn_today() {
	return current_time( 'Y-m-d' );
}

/**
 * 1.500.000 ₫
 *
 * @param int|float $n Số tiền.
 * @param bool      $symbol Kèm ký hiệu ₫.
 * @return string
 */
function qldn_money( $n, $symbol = true ) {
	return number_format( (float) $n, 0, ',', '.' ) . ( $symbol ? ' ₫' : '' );
}

/**
 * Số lượng: bỏ phần thập phân thừa (2,00 → 2; 1,50 → 1,5).
 *
 * @param float $n Số.
 * @return string
 */
function qldn_qty( $n ) {
	$n = round( (float) $n, 2 );
	return number_format( $n, floor( $n ) == $n ? 0 : ( round( $n, 1 ) == $n ? 1 : 2 ), ',', '.' );
}

/**
 * Ngày dd/mm/yyyy.
 *
 * @param string $date   Y-m-d hoặc Y-m-d H:i:s.
 * @param bool   $time   Kèm giờ.
 * @return string
 */
function qldn_date( $date, $time = false ) {
	if ( ! $date || 0 === strpos( $date, '0000' ) ) {
		return '';
	}
	$ts = strtotime( $date );
	return $ts ? gmdate( $time ? 'd/m/Y H:i' : 'd/m/Y', $ts ) : '';
}

/**
 * Đọc số tiền / số lượng người dùng nhập: chấp nhận "1.500.000", "1 500 000", "1500000", "2,5".
 *
 * @param mixed $v     Giá trị.
 * @param bool  $float Cho phép số lẻ (số lượng).
 * @return int|float
 */
function qldn_num( $v, $float = false ) {
	$v = trim( (string) $v );
	$v = str_replace( array( ' ', '₫', 'đ', 'VND', 'vnd' ), '', $v );
	if ( $float ) {
		// Số lượng: "1.234,5" hoặc "1234.5" hoặc "2,5".
		if ( false !== strpos( $v, ',' ) ) {
			$v = str_replace( '.', '', $v );
			$v = str_replace( ',', '.', $v );
		}
		return round( (float) $v, 2 );
	}
	// Tiền đồng không có số lẻ: bỏ mọi dấu phân cách.
	$neg = 0 === strpos( $v, '-' );
	$v   = preg_replace( '/[^\d]/', '', $v );
	return ( $neg ? -1 : 1 ) * (int) $v;
}

/**
 * Lấy giá trị từ $_POST / $_GET đã làm sạch.
 *
 * @param string $key     Khoá.
 * @param string $type    text|textarea|int|money|qty|email|date|key|array.
 * @param mixed  $default Mặc định.
 * @param array  $src     Nguồn (mặc định $_REQUEST).
 * @return mixed
 */
function qldn_in( $key, $type = 'text', $default = '', $src = null ) {
	// phpcs:ignore WordPress.Security.NonceVerification -- nonce kiểm tra ở nơi gọi.
	$src = null === $src ? $_REQUEST : $src;
	if ( ! isset( $src[ $key ] ) ) {
		return $default;
	}
	$v = wp_unslash( $src[ $key ] );
	switch ( $type ) {
		case 'array':
			return is_array( $v ) ? $v : array();
		case 'int':
			return (int) $v;
		case 'money':
			return qldn_num( $v );
		case 'qty':
			return qldn_num( $v, true );
		case 'email':
			return sanitize_email( $v );
		case 'textarea':
			return sanitize_textarea_field( $v );
		case 'key':
			return sanitize_key( $v );
		case 'date':
			$v = sanitize_text_field( $v );
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : $default;
		default:
			return sanitize_text_field( $v );
	}
}

/**
 * Bảng nhập liệu lớn (bảng công, bảng lương): trình duyệt gom các ô thành 1 trường JSON "{key}_json"
 * để không bị PHP cắt bớt khi vượt max_input_vars (mặc định 1000 ô – chỉ ~33 nhân viên × 30 ngày).
 * Trình duyệt tắt JavaScript thì đọc mảng thường.
 *
 * @param string $key Tên mảng (att, pr).
 * @return array Mảng 2 cấp [id][cột] = giá trị (chưa unslash ở cấp dưới khi đọc từ mảng thường).
 */
function qldn_in_packed( $key ) {
	// phpcs:ignore WordPress.Security.NonceVerification -- nonce kiểm tra ở nơi gọi.
	if ( isset( $_POST[ $key . '_json' ] ) ) {
		$data = json_decode( wp_unslash( $_POST[ $key . '_json' ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
		if ( is_array( $data ) ) {
			// Giá trị JSON đã không có dấu gạch chéo: thêm lại để nơi gọi wp_unslash() như mảng thường.
			return wp_slash( $data );
		}
	}
	return qldn_in( $key, 'array', array() );
}

/**
 * Chuẩn hoá số điện thoại Việt Nam (0xxxxxxxxx). Không hợp lệ thì giữ nguyên chữ số.
 *
 * @param string $phone Số.
 * @return string
 */
function qldn_phone( $phone ) {
	$phone = preg_replace( '/[^\d+]/', '', (string) $phone );
	if ( preg_match( '/^(\+?84|0)(\d{9,10})$/', $phone, $m ) ) {
		return '0' . $m[2];
	}
	return preg_replace( '/\D/', '', $phone );
}

/**
 * Mã tự sinh: tiền tố + số thứ tự 6 chữ số.
 *
 * @param string $prefix Tiền tố.
 * @param int    $id     ID.
 * @param int    $pad    Số chữ số.
 * @return string
 */
function qldn_code( $prefix, $id, $pad = 6 ) {
	return $prefix . str_pad( (string) $id, $pad, '0', STR_PAD_LEFT );
}

/**
 * Đường dẫn trang quản trị của plugin.
 *
 * @param string $page Slug trang (không có tiền tố qldn-), '' = tổng quan.
 * @param array  $args Tham số.
 * @return string
 */
function qldn_url( $page = '', $args = array() ) {
	$args = array_merge( array( 'page' => $page ? 'qldn-' . $page : 'qldn' ), $args );
	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

/**
 * URL admin-post có nonce.
 *
 * @param string $action Hành động (không tiền tố).
 * @param array  $args   Tham số.
 * @return string
 */
function qldn_action_url( $action, $args = array() ) {
	$args = array_merge( array( 'action' => 'qldn_' . $action ), $args );
	return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'qldn_' . $action );
}

/**
 * Mở form gửi tới admin-post (kèm nonce).
 *
 * @param string $action Hành động.
 * @param array  $attrs  Thuộc tính thêm (class, id...).
 */
function qldn_form_open( $action, $attrs = array() ) {
	$html = '';
	foreach ( $attrs as $k => $v ) {
		$html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . $html . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<input type="hidden" name="action" value="qldn_' . esc_attr( $action ) . '">';
	wp_nonce_field( 'qldn_' . $action );
}

/**
 * Kiểm tra quyền + nonce cho hành động admin-post; sai thì dừng.
 *
 * @param string $action Hành động.
 * @param string $cap    Quyền cần có.
 */
function qldn_check( $action, $cap ) {
	if ( ! current_user_can( $cap ) ) {
		wp_die( 'Bạn không có quyền thực hiện thao tác này.', 403 );
	}
	check_admin_referer( 'qldn_' . $action );
}

/**
 * Ghi thông báo để hiện sau khi chuyển trang.
 *
 * @param string $msg  Nội dung.
 * @param string $type success|error|warning|info.
 */
function qldn_notice( $msg, $type = 'success' ) {
	$key   = 'qldn_notice_' . get_current_user_id();
	$list  = get_transient( $key );
	$list  = is_array( $list ) ? $list : array();
	$list[] = array( $type, $msg );
	set_transient( $key, $list, 120 );
}

/**
 * In thông báo đang chờ.
 */
function qldn_print_notices() {
	$key  = 'qldn_notice_' . get_current_user_id();
	$list = get_transient( $key );
	if ( ! is_array( $list ) ) {
		return;
	}
	delete_transient( $key );
	foreach ( $list as $n ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $n[0] ), wp_kses_post( $n[1] ) );
	}
}

/**
 * Chuyển về trang của plugin rồi dừng.
 *
 * @param string $page Slug.
 * @param array  $args Tham số.
 */
function qldn_go( $page = '', $args = array() ) {
	wp_safe_redirect( qldn_url( $page, $args ) );
	exit;
}

/**
 * Ô nhập trong bảng form-table.
 *
 * @param string $label Nhãn.
 * @param string $name  Tên trường.
 * @param mixed  $value Giá trị.
 * @param array  $o     type, options (select), required, help, attrs, class.
 */
function qldn_field( $label, $name, $value = '', $o = array() ) {
	$o    = wp_parse_args( $o, array( 'type' => 'text', 'options' => array(), 'required' => false, 'help' => '', 'attrs' => array(), 'class' => 'regular-text' ) );
	$id   = 'qldn-' . sanitize_key( $name );
	$attr = $o['required'] ? ' required' : '';
	foreach ( $o['attrs'] as $k => $v ) {
		$attr .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ( $o['required'] ? ' <span class="qldn-req">*</span>' : '' ) . '</label></th><td>';
	switch ( $o['type'] ) {
		case 'select':
			echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '"' . $attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
			foreach ( $o['options'] as $k => $v ) {
				echo '<option value="' . esc_attr( $k ) . '"' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
			}
			echo '</select>';
			break;
		case 'textarea':
			echo '<textarea name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" rows="4" class="large-text"' . $attr . '>' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'money':
			echo '<input type="text" inputmode="numeric" class="qldn-money ' . esc_attr( $o['class'] ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( '' === $value ? '' : qldn_money( $value, false ) ) . '"' . $attr . '> ₫'; // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		default:
			echo '<input type="' . esc_attr( $o['type'] ) . '" class="' . esc_attr( $o['class'] ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '"' . $attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	if ( $o['help'] ) {
		echo '<p class="description">' . wp_kses_post( $o['help'] ) . '</p>';
	}
	echo '</td></tr>';
}

/**
 * Thẻ <option> cho select.
 *
 * @param array  $options  Giá trị => nhãn.
 * @param string $selected Đang chọn.
 */
function qldn_options( $options, $selected ) {
	foreach ( $options as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( (string) $selected, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
}

/**
 * Phân trang đơn giản.
 *
 * @param int $total Tổng số dòng.
 * @param int $per   Số dòng / trang.
 * @param int $paged Trang hiện tại.
 */
function qldn_pagination( $total, $per, $paged ) {
	$pages = (int) ceil( $total / $per );
	if ( $pages < 2 ) {
		echo '<p class="qldn-count">Tổng: ' . esc_html( number_format( $total, 0, ',', '.' ) ) . '</p>';
		return;
	}
	$links = paginate_links(
		array(
			'base'      => add_query_arg( 'paged', '%#%' ),
			'format'    => '',
			'current'   => max( 1, $paged ),
			'total'     => $pages,
			'prev_text' => '‹',
			'next_text' => '›',
		)
	);
	echo '<div class="tablenav"><div class="tablenav-pages"><span class="displaying-num">Tổng: ' . esc_html( number_format( $total, 0, ',', '.' ) ) . '</span> ' . wp_kses_post( $links ) . '</div></div>';
}

/**
 * Trang hiện tại (paged) và offset.
 *
 * @param int $per Số dòng / trang.
 * @return array [paged, offset]
 */
function qldn_paged( $per ) {
	$paged = max( 1, qldn_in( 'paged', 'int', 1 ) );
	return array( $paged, ( $paged - 1 ) * $per );
}

/**
 * Xuất CSV (UTF-8 có BOM để Excel đọc đúng tiếng Việt) rồi dừng.
 *
 * @param string $filename Tên file.
 * @param array  $header   Dòng tiêu đề.
 * @param array  $rows     Các dòng.
 */
function qldn_csv( $filename, $header, $rows ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $out, $header );
	foreach ( $rows as $r ) {
		// Chặn công thức Excel (CSV injection).
		$r = array_map(
			function ( $c ) {
				$c = (string) $c;
				return ( '' !== $c && in_array( $c[0], array( '=', '+', '-', '@' ), true ) && ! is_numeric( $c ) ) ? "'" . $c : $c;
			},
			(array) $r
		);
		fputcsv( $out, $r );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

/**
 * Nhãn trạng thái dạng huy hiệu.
 *
 * @param string $text  Chữ.
 * @param string $color green|red|orange|gray|blue.
 * @return string HTML.
 */
function qldn_badge( $text, $color = 'gray' ) {
	return '<span class="qldn-badge qldn-badge-' . esc_attr( $color ) . '">' . esc_html( $text ) . '</span>';
}

/**
 * Ô tìm kiếm + bộ lọc GET giữ nguyên trang hiện tại.
 *
 * @param string $page   Slug (không tiền tố).
 * @param string $search Từ khoá hiện tại.
 * @param string $extra  HTML bộ lọc thêm (đã escape).
 * @param string $placeholder Gợi ý.
 */
function qldn_search_box( $page, $search, $extra = '', $placeholder = 'Tìm…' ) {
	echo '<form method="get" class="qldn-filter"><input type="hidden" name="page" value="qldn-' . esc_attr( $page ) . '">';
	echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape ở nơi tạo.
	echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr( $placeholder ) . '"> ';
	echo '<button class="button">Lọc</button></form>';
}

/**
 * Điều kiện LIKE cho nhiều cột.
 *
 * @param string $s    Từ khoá.
 * @param array  $cols Cột.
 * @return string SQL đã prepare (bắt đầu bằng " AND ") hoặc rỗng.
 */
function qldn_like( $s, $cols ) {
	global $wpdb;
	if ( '' === $s ) {
		return '';
	}
	$like  = '%' . $wpdb->esc_like( $s ) . '%';
	$parts = array();
	foreach ( $cols as $c ) {
		$parts[] = $wpdb->prepare( "$c LIKE %s", $like ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
	return ' AND (' . implode( ' OR ', $parts ) . ')';
}

/**
 * Nhân viên liên kết với người dùng đang đăng nhập.
 *
 * @param int $user_id ID người dùng (0 = hiện tại).
 * @return object|null
 */
function qldn_current_employee( $user_id = 0 ) {
	global $wpdb;
	$user_id = $user_id ? $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return null;
	}
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qldn_t( 'employees' ) . " WHERE user_id = %d AND status = 'active' LIMIT 1", $user_id ) );
}

/**
 * Danh sách nhân viên đang làm: id => "Mã – Tên".
 *
 * @param bool $all Gồm cả người đã nghỉ.
 * @return array
 */
function qldn_employee_options( $all = false ) {
	global $wpdb;
	$where = $all ? '' : " WHERE status = 'active'";
	$rows  = $wpdb->get_results( 'SELECT id, code, name FROM ' . qldn_t( 'employees' ) . $where . ' ORDER BY name' );
	$out   = array();
	foreach ( $rows as $r ) {
		$out[ $r->id ] = $r->name . ' (' . $r->code . ')';
	}
	return $out;
}
