<?php
/**
 * Soạn hồ sơ: mẫu văn bản có trường trộn {{...}}, tạo văn bản từ khách hàng / nhân viên / hợp đồng / báo giá,
 * sửa nội dung, in, tải về mở bằng Word.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đối tượng của mẫu.
 *
 * @return array
 */
function qldn_doc_contexts() {
	return array(
		'contract' => 'Hợp đồng',
		'quote'    => 'Báo giá',
		'customer' => 'Khách hàng',
		'employee' => 'Nhân viên',
	);
}

/* ------------------------------------------------------------------ *
 * Đọc số tiền bằng chữ
 * ------------------------------------------------------------------ */

/**
 * Đọc 3 chữ số.
 *
 * @param int  $n    0–999.
 * @param bool $full Đã có nhóm lớn hơn phía trước (đọc cả "không trăm", "linh").
 * @return string
 */
function qldn_words_triple( $n, $full ) {
	$d = array( 'không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín' );
	$h = intdiv( $n, 100 );
	$t = intdiv( $n % 100, 10 );
	$u = $n % 10;
	$w = array();
	if ( $full || $h > 0 ) {
		$w[] = $d[ $h ] . ' trăm';
	}
	if ( 0 === $t ) {
		if ( $u > 0 && ( $full || $h > 0 ) ) {
			$w[] = 'linh';
		}
	} elseif ( 1 === $t ) {
		$w[] = 'mười';
	} else {
		$w[] = $d[ $t ] . ' mươi';
	}
	if ( $u > 0 ) {
		if ( 1 === $u && $t > 1 ) {
			$w[] = 'mốt';
		} elseif ( 5 === $u && $t > 0 ) {
			$w[] = 'lăm';
		} else {
			$w[] = $d[ $u ];
		}
	}
	return implode( ' ', $w );
}

/**
 * Đọc số nguyên không âm.
 *
 * @param int  $n    Số.
 * @param bool $full Đã có nhóm lớn hơn phía trước.
 * @return string
 */
function qldn_words_int( $n, $full = false ) {
	if ( $n >= 1000000000 ) {
		$rest = $n % 1000000000;
		return trim( qldn_words_int( intdiv( $n, 1000000000 ), $full ) . ' tỷ' . ( $rest ? ' ' . qldn_words_int( $rest, true ) : '' ) );
	}
	$units = array( 1000000 => 'triệu', 1000 => 'nghìn', 1 => '' );
	$w     = array();
	foreach ( $units as $div => $name ) {
		$g = intdiv( $n, $div ) % 1000;
		if ( $g > 0 ) {
			$w[]  = trim( qldn_words_triple( $g, $full ) . ' ' . $name );
			$full = true;
		}
	}
	return implode( ' ', $w );
}

/**
 * Số tiền bằng chữ: 1.250.000 → "Một triệu hai trăm năm mươi nghìn đồng".
 *
 * @param int $n Số tiền.
 * @return string
 */
function qldn_money_words( $n ) {
	$n = (int) round( $n );
	if ( 0 === $n ) {
		return 'Không đồng';
	}
	$s = ( $n < 0 ? 'âm ' : '' ) . qldn_words_int( abs( $n ) ) . ' đồng';
	return mb_strtoupper( mb_substr( $s, 0, 1 ) ) . mb_substr( $s, 1 );
}

/* ------------------------------------------------------------------ *
 * Trường trộn
 * ------------------------------------------------------------------ */

/**
 * Danh sách trường trộn theo nhóm (hiện ở trang sửa mẫu).
 *
 * @return array nhóm => [ khoá => mô tả ].
 */
function qldn_merge_fields() {
	return array(
		'Chung'      => array(
			'ngay_hom_nay' => 'Ngày hôm nay (dd/mm/yyyy)',
			'ngay'         => 'Ngày (số)',
			'thang'        => 'Tháng',
			'nam'          => 'Năm',
			'cong_ty'      => 'Tên doanh nghiệp',
			'cong_ty_dia_chi'    => 'Địa chỉ doanh nghiệp',
			'cong_ty_dien_thoai' => 'Điện thoại',
			'cong_ty_email'      => 'Email',
			'cong_ty_mst'        => 'Mã số thuế',
			'cong_ty_dai_dien'   => 'Người đại diện',
			'cong_ty_chuc_vu'    => 'Chức vụ người đại diện',
			'cong_ty_tai_khoan'  => 'Tài khoản ngân hàng',
		),
		'Khách hàng' => array(
			'kh_ma'         => 'Mã khách hàng',
			'kh_ten'        => 'Tên khách hàng / doanh nghiệp',
			'kh_dia_chi'    => 'Địa chỉ',
			'kh_dien_thoai' => 'Điện thoại',
			'kh_email'      => 'Email',
			'kh_mst'        => 'Mã số thuế',
			'kh_dai_dien'   => 'Người đại diện (cá nhân: chính khách hàng)',
			'kh_chuc_vu'    => 'Chức vụ người đại diện',
			'kh_tai_khoan'  => 'Tài khoản ngân hàng',
		),
		'Nhân viên'  => array(
			'nv_ma'          => 'Mã nhân viên',
			'nv_ten'         => 'Họ tên',
			'nv_ngay_sinh'   => 'Ngày sinh',
			'nv_gioi_tinh'   => 'Giới tính',
			'nv_dia_chi'     => 'Nơi cư trú',
			'nv_cccd'        => 'Số CCCD',
			'nv_ngay_cap'    => 'Ngày cấp CCCD',
			'nv_noi_cap'     => 'Nơi cấp CCCD',
			'nv_dien_thoai'  => 'Điện thoại',
			'nv_bo_phan'     => 'Bộ phận',
			'nv_chuc_vu'     => 'Chức vụ',
			'nv_ngay_vao_lam' => 'Ngày vào làm',
			'nv_luong'       => 'Lương cơ bản',
			'nv_luong_chu'   => 'Lương cơ bản bằng chữ',
			'nv_hinh_thuc_luong' => 'Hình thức trả lương (tháng / ngày / giờ)',
			'nv_phu_cap'     => 'Phụ cấp / tháng',
			'nv_luong_bh'    => 'Lương đóng bảo hiểm',
			'nv_mst'         => 'Mã số thuế cá nhân',
			'nv_tai_khoan'   => 'Tài khoản ngân hàng',
		),
		'Hợp đồng'   => array(
			'hd_so'          => 'Số hợp đồng',
			'hd_ten'         => 'Tên / nội dung hợp đồng',
			'hd_loai'        => 'Loại hợp đồng',
			'hd_gia_tri'     => 'Giá trị',
			'hd_gia_tri_chu' => 'Giá trị bằng chữ',
			'hd_da_thanh_toan' => 'Đã thanh toán',
			'hd_con_lai'     => 'Còn phải thanh toán',
			'hd_ngay_ky'     => 'Ngày ký',
			'hd_tu_ngay'     => 'Có hiệu lực từ ngày',
			'hd_den_ngay'    => 'Đến ngày',
			'hd_thoi_han'    => 'Thời hạn (VD: 12 tháng / không xác định thời hạn)',
			'hd_pham_vi'     => 'Phạm vi công việc / hàng hoá (nhập ở hợp đồng)',
		),
		'Báo giá'    => array(
			'bg_so'        => 'Số báo giá',
			'bg_tieu_de'   => 'Tiêu đề',
			'bg_ngay'      => 'Ngày lập',
			'bg_hieu_luc'  => 'Hiệu lực đến',
			'bang_hang'    => 'Bảng hàng hoá / dịch vụ',
			'bg_tien_hang' => 'Tiền hàng',
			'bg_giam_gia'  => 'Giảm giá',
			'bg_thue_suat' => 'Thuế suất GTGT',
			'bg_vat'       => 'Tiền thuế GTGT',
			'bg_tong'      => 'Tổng thanh toán',
			'bg_tong_chu'  => 'Tổng bằng chữ',
			'bg_dieu_khoan' => 'Điều khoản',
			'bg_nhan_vien' => 'Nhân viên phụ trách',
			'bg_nv_dien_thoai' => 'Điện thoại nhân viên phụ trách',
		),
	);
}

/**
 * Giá trị trường trộn cho 1 đối tượng.
 *
 * @param string $context  contract|quote|customer|employee.
 * @param int    $id       ID đối tượng.
 * @return array khoá => giá trị (HTML an toàn).
 */
function qldn_merge_values( $context, $id ) {
	$o   = qldn_opt();
	$now = current_time( 'timestamp' );
	$v   = array(
		'ngay_hom_nay'       => gmdate( 'd/m/Y', $now ),
		'ngay'               => gmdate( 'd', $now ),
		'thang'              => gmdate( 'm', $now ),
		'nam'                => gmdate( 'Y', $now ),
		'cong_ty'            => $o['company_name'],
		'cong_ty_dia_chi'    => $o['company_address'],
		'cong_ty_dien_thoai' => $o['company_phone'],
		'cong_ty_email'      => $o['company_email'],
		'cong_ty_mst'        => $o['company_tax_code'],
		'cong_ty_dai_dien'   => $o['company_rep'],
		'cong_ty_chuc_vu'    => $o['company_rep_title'],
		'cong_ty_tai_khoan'  => $o['company_bank'],
	);
	$customer = null;
	$employee = null;
	$quote    = null;
	if ( 'contract' === $context ) {
		$c = qldn_get( 'contracts', $id );
		if ( $c ) {
			$types = qldn_contract_types();
			$v    += array(
				'hd_so'            => $c->code,
				'hd_ten'           => $c->title,
				'hd_loai'          => isset( $types[ $c->type ] ) ? $types[ $c->type ] : $c->type,
				'hd_gia_tri'       => qldn_money( $c->value, false ),
				'hd_gia_tri_chu'   => qldn_money_words( $c->value ),
				'hd_da_thanh_toan' => qldn_money( $c->paid, false ),
				'hd_con_lai'       => qldn_money( max( 0, $c->value - $c->paid ), false ),
				'hd_ngay_ky'       => qldn_date( $c->sign_date ),
				'hd_tu_ngay'       => qldn_date( $c->start_date ),
				'hd_den_ngay'      => qldn_date( $c->end_date ),
				'hd_thoi_han'      => qldn_contract_term( $c ),
				'hd_pham_vi'       => $c->scope,
			);
			if ( 'employee' === $c->party_type ) {
				$employee = qldn_get( 'employees', $c->party_id );
			} else {
				$customer = qldn_get( 'customers', $c->party_id );
			}
			$quote = $c->quote_id ? qldn_get( 'quotes', $c->quote_id ) : null;
		}
	} elseif ( 'quote' === $context ) {
		$quote = qldn_get( 'quotes', $id );
	} elseif ( 'customer' === $context ) {
		$customer = qldn_get( 'customers', $id );
	} elseif ( 'employee' === $context ) {
		$employee = qldn_get( 'employees', $id );
	}
	if ( $quote ) {
		$staff    = $quote->staff_id ? qldn_get( 'employees', $quote->staff_id ) : null;
		$customer = $customer ? $customer : qldn_get( 'customers', $quote->customer_id );
		$v       += array(
			'bg_so'            => $quote->code,
			'bg_tieu_de'       => $quote->title,
			'bg_ngay'          => qldn_date( $quote->created_at ),
			'bg_hieu_luc'      => qldn_date( $quote->valid_until ),
			'bang_hang'        => qldn_quote_table( $quote ),
			'bg_tien_hang'     => qldn_money( $quote->subtotal, false ),
			'bg_giam_gia'      => qldn_money( $quote->discount, false ),
			'bg_thue_suat'     => qldn_qty( $quote->vat_rate ) . '%',
			'bg_vat'           => qldn_money( $quote->vat, false ),
			'bg_tong'          => qldn_money( $quote->total, false ),
			'bg_tong_chu'      => qldn_money_words( $quote->total ),
			'bg_dieu_khoan'    => $quote->terms,
			'bg_nhan_vien'     => $staff ? $staff->name : '',
			'bg_nv_dien_thoai' => $staff ? $staff->phone : $o['company_phone'],
		);
	}
	if ( $customer ) {
		$company = 'company' === $customer->ctype;
		$v      += array(
			'kh_ma'         => $customer->code,
			'kh_ten'        => $customer->name,
			'kh_dia_chi'    => $customer->address,
			'kh_dien_thoai' => $customer->phone,
			'kh_email'      => $customer->email,
			'kh_mst'        => $customer->tax_code,
			'kh_dai_dien'   => $company ? $customer->representative : ( $customer->representative ? $customer->representative : $customer->name ),
			'kh_chuc_vu'    => $customer->rep_title,
			'kh_tai_khoan'  => $customer->bank_account,
		);
	}
	if ( $employee ) {
		$types = array( 'month' => 'Theo tháng', 'day' => 'Theo ngày công', 'hour' => 'Theo giờ làm việc' );
		$v    += array(
			'nv_ma'              => $employee->code,
			'nv_ten'             => $employee->name,
			'nv_ngay_sinh'       => qldn_date( $employee->birthday ),
			'nv_gioi_tinh'       => $employee->gender,
			'nv_dia_chi'         => $employee->address,
			'nv_cccd'            => $employee->id_number,
			'nv_ngay_cap'        => qldn_date( $employee->id_issue_date ),
			'nv_noi_cap'         => $employee->id_issue_place,
			'nv_dien_thoai'      => $employee->phone,
			'nv_bo_phan'         => $employee->department,
			'nv_chuc_vu'         => $employee->position,
			'nv_ngay_vao_lam'    => qldn_date( $employee->start_date ),
			'nv_luong'           => qldn_money( $employee->base_salary, false ),
			'nv_luong_chu'       => qldn_money_words( $employee->base_salary ),
			'nv_hinh_thuc_luong' => isset( $types[ $employee->salary_type ] ) ? $types[ $employee->salary_type ] : '',
			'nv_phu_cap'         => qldn_money( $employee->allowance, false ),
			'nv_luong_bh'        => qldn_money( $employee->insurance_salary, false ),
			'nv_mst'             => $employee->tax_code,
			'nv_tai_khoan'       => $employee->bank_account,
		);
	}
	// Chữ thường thì escape, xuống dòng → <br>; bảng hàng đã là HTML.
	foreach ( $v as $k => $val ) {
		if ( 'bang_hang' !== $k ) {
			$v[ $k ] = nl2br( esc_html( (string) $val ) );
		}
	}
	return $v;
}

/**
 * Trộn mẫu với dữ liệu. Trường thiếu dữ liệu để chấm "……" cho điền tay.
 *
 * @param string $content Nội dung mẫu.
 * @param array  $values  Giá trị.
 * @return string HTML.
 */
function qldn_merge( $content, $values ) {
	return preg_replace_callback(
		'/\{\{\s*([a-z_]+)\s*\}\}/',
		function ( $m ) use ( $values ) {
			return isset( $values[ $m[1] ] ) && '' !== trim( wp_strip_all_tags( $values[ $m[1] ] ) ) ? $values[ $m[1] ] : '……………………';
		},
		(string) $content
	);
}

/* ------------------------------------------------------------------ *
 * Mẫu & văn bản
 * ------------------------------------------------------------------ */

/**
 * Mẫu theo đối tượng (và loại hợp đồng).
 *
 * @param string $context Đối tượng.
 * @param string $kind    Loại hợp đồng (sale, service, labor) – mẫu kind rỗng dùng cho mọi loại.
 * @return array
 */
function qldn_templates_for( $context, $kind = '' ) {
	global $wpdb;
	$sql = $wpdb->prepare( 'SELECT id, name, kind FROM ' . qldn_t( 'templates' ) . ' WHERE context = %s', $context );
	if ( $kind ) {
		$sql .= $wpdb->prepare( " AND (kind = %s OR kind = '')", $kind );
	}
	return $wpdb->get_results( $sql . ' ORDER BY kind DESC, name' ); // phpcs:ignore WordPress.DB.PreparedSQL
}

/**
 * Tạo văn bản từ mẫu.
 *
 * @param int    $template_id Mẫu.
 * @param string $context     Đối tượng.
 * @param int    $object_id   ID đối tượng.
 * @param string $title       Tiêu đề (rỗng = tên mẫu + mã đối tượng).
 * @return int|WP_Error
 */
function qldn_document_create( $template_id, $context, $object_id, $title = '' ) {
	global $wpdb;
	$tpl = qldn_get( 'templates', $template_id );
	if ( ! $tpl ) {
		return new WP_Error( 'qldn_tpl', 'Chưa chọn mẫu văn bản.' );
	}
	if ( $tpl->context !== $context ) {
		return new WP_Error( 'qldn_tpl', 'Mẫu "' . $tpl->name . '" không dùng cho ' . strtolower( qldn_doc_contexts()[ $context ] ?? $context ) . '.' );
	}
	$values = qldn_merge_values( $context, $object_id );
	if ( '' === $title ) {
		$ref   = $values['hd_so'] ?? ( $values['bg_so'] ?? ( $values['kh_ten'] ?? ( $values['nv_ten'] ?? '' ) ) );
		$title = $tpl->name . ( $ref ? ' – ' . wp_strip_all_tags( $ref ) : '' );
	}
	$now = qldn_now();
	$wpdb->insert(
		qldn_t( 'documents' ),
		array(
			'title'       => sanitize_text_field( $title ),
			'template_id' => $tpl->id,
			'context'     => $context,
			'object_id'   => (int) $object_id,
			'content'     => wp_kses_post( qldn_merge( $tpl->content, $values ) ),
			'user_id'     => get_current_user_id(),
			'created_at'  => $now,
			'updated_at'  => $now,
		)
	);
	$id = (int) $wpdb->insert_id;
	$wpdb->update( qldn_t( 'documents' ), array( 'code' => qldn_code( 'VB', $id ) ), array( 'id' => $id ) );
	return $id;
}

/**
 * Quyền xem đối tượng: hợp đồng lao động / hồ sơ nhân viên có lương → cần quyền nhân sự.
 *
 * @param string $context Đối tượng.
 * @param int    $id      ID.
 * @return bool
 */
function qldn_can_object( $context, $id ) {
	if ( ! current_user_can( 'qldn_docs' ) ) {
		return false;
	}
	if ( 'employee' === $context ) {
		return current_user_can( 'qldn_hr' );
	}
	if ( 'contract' === $context ) {
		$c = qldn_get( 'contracts', $id );
		return ! $c || 'employee' !== $c->party_type || current_user_can( 'qldn_hr' );
	}
	return true;
}

/**
 * Văn bản của 1 đối tượng.
 *
 * @param string $context Đối tượng.
 * @param int    $id      ID.
 * @return array
 */
function qldn_documents_of( $context, $id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT id, code, title, created_at, updated_at FROM ' . qldn_t( 'documents' ) . ' WHERE context = %s AND object_id = %d ORDER BY id DESC', $context, $id ) );
}

/**
 * Bảng văn bản + form tạo nhanh (dùng ở trang hợp đồng, báo giá).
 *
 * @param string $context Đối tượng.
 * @param int    $id      ID.
 * @param string $kind    Loại hợp đồng để lọc mẫu.
 */
function qldn_documents_box( $context, $id, $kind = '' ) {
	$docs = qldn_documents_of( $context, $id );
	echo '<h2>Văn bản</h2><table class="widefat striped qldn-table qldn-narrow"><tbody>';
	if ( ! $docs ) {
		echo '<tr><td>Chưa có văn bản. Chọn mẫu bên dưới để soạn.</td></tr>';
	}
	foreach ( $docs as $d ) {
		echo '<tr><td><a href="' . esc_url( qldn_url( 'docs', array( 'doc' => $d->id ) ) ) . '"><strong>' . esc_html( $d->title ) . '</strong></a> <small>' . esc_html( $d->code . ' · ' . qldn_date( $d->updated_at, true ) ) . '</small></td>';
		echo '<td class="qldn-actions"><a target="_blank" href="' . esc_url( qldn_action_url( 'doc_print', array( 'id' => $d->id ) ) ) . '">In</a> · <a href="' . esc_url( qldn_action_url( 'doc_word', array( 'id' => $d->id ) ) ) . '">Tải Word</a></td></tr>';
	}
	echo '</tbody></table>';
	$tpls = qldn_templates_for( $context, $kind );
	if ( $tpls ) {
		qldn_form_open( 'doc_create', array( 'class' => 'qldn-filter' ) );
		echo '<input type="hidden" name="context" value="' . esc_attr( $context ) . '"><input type="hidden" name="object_id" value="' . esc_attr( $id ) . '"><select name="template_id">';
		foreach ( $tpls as $t ) {
			echo '<option value="' . esc_attr( $t->id ) . '">' . esc_html( $t->name ) . '</option>';
		}
		echo '</select> <button class="button button-primary">Soạn văn bản</button></form>';
	} else {
		echo '<p>Chưa có mẫu cho mục này. <a href="' . esc_url( qldn_url( 'docs', array( 'tab' => 'templates' ) ) ) . '">Thêm mẫu</a>.</p>';
	}
}

/**
 * Xử lý tạo văn bản.
 */
function qldn_handle_doc_create() {
	qldn_check( 'doc_create', 'qldn_docs' );
	$context = qldn_in( 'context', 'key' );
	$oid     = qldn_in( 'object_id', 'int', 0 );
	if ( ! isset( qldn_doc_contexts()[ $context ] ) || ! qldn_can_object( $context, $oid ) ) {
		wp_die( 'Bạn không có quyền soạn văn bản cho mục này.', 403 );
	}
	$r = qldn_document_create( qldn_in( 'template_id', 'int', 0 ), $context, $oid );
	if ( is_wp_error( $r ) ) {
		qldn_notice( $r->get_error_message(), 'error' );
		qldn_go( 'docs', array( 'tab' => 'new' ) );
	}
	qldn_notice( 'Đã soạn văn bản. Kiểm tra, sửa các chỗ "……" rồi lưu, in hoặc tải về Word.' );
	qldn_go( 'docs', array( 'doc' => $r ) );
}
add_action( 'admin_post_qldn_doc_create', 'qldn_handle_doc_create' );

/**
 * Lưu văn bản đã sửa.
 */
function qldn_handle_doc_save() {
	qldn_check( 'doc_save', 'qldn_docs' );
	global $wpdb;
	$d = qldn_get( 'documents', qldn_in( 'id', 'int', 0 ) );
	if ( ! $d || ! qldn_can_object( $d->context, $d->object_id ) ) {
		wp_die( 'Không tìm thấy văn bản.', 404 );
	}
	// phpcs:ignore WordPress.Security.NonceVerification -- đã kiểm tra.
	// Trình soạn thảo WordPress bỏ thẻ <p>, <br> khi gửi: dựng lại đoạn văn trước khi lưu.
	$content = isset( $_POST['content'] ) ? wpautop( wp_kses_post( wp_unslash( $_POST['content'] ) ) ) : '';
	$wpdb->update( qldn_t( 'documents' ), array( 'title' => qldn_in( 'title' ), 'content' => $content, 'updated_at' => qldn_now() ), array( 'id' => $d->id ) );
	qldn_notice( 'Đã lưu văn bản.' );
	qldn_go( 'docs', array( 'doc' => $d->id ) );
}
add_action( 'admin_post_qldn_doc_save', 'qldn_handle_doc_save' );

/**
 * Xoá văn bản.
 */
function qldn_handle_doc_delete() {
	qldn_check( 'doc_delete', 'qldn_docs' );
	global $wpdb;
	$d = qldn_get( 'documents', qldn_in( 'id', 'int', 0 ) );
	if ( $d && qldn_can_object( $d->context, $d->object_id ) ) {
		$wpdb->delete( qldn_t( 'documents' ), array( 'id' => $d->id ) );
		qldn_notice( 'Đã xoá văn bản ' . esc_html( $d->code ) . '.' );
	}
	qldn_go( 'docs' );
}
add_action( 'admin_post_qldn_doc_delete', 'qldn_handle_doc_delete' );

/**
 * CSS trang in A4 (dùng chung cho in và Word).
 *
 * @return string
 */
function qldn_doc_css() {
	return 'body{font-family:"Times New Roman",Times,serif;font-size:13pt;line-height:1.45;color:#000}'
		. '.qldn-doc{max-width:170mm;margin:0 auto}h1,h2,h3{font-family:"Times New Roman",Times,serif}'
		. 'h1{font-size:15pt;text-align:center;margin:12pt 0 4pt}h2{font-size:13pt;margin:10pt 0 4pt}'
		. 'p{margin:3pt 0;text-align:justify}table{border-collapse:collapse;width:100%}'
		. 'table.qldn-items td,table.qldn-items th{border:1px solid #000;padding:3pt 5pt;font-size:12pt}'
		. 'table.qldn-items th{background:#eee}.r{text-align:right}.c{text-align:center}'
		. 'table.qldn-head td,table.qldn-sign td{border:0;vertical-align:top;text-align:center;width:50%}'
		. '@page{size:A4;margin:20mm 20mm 20mm 30mm}@media print{.noprint{display:none}}';
}

/**
 * Lấy văn bản được phép xem từ tham số id.
 *
 * @return object
 */
function qldn_doc_for_output() {
	$d = qldn_get( 'documents', qldn_in( 'id', 'int', 0 ) );
	if ( ! $d || ! qldn_can_object( $d->context, $d->object_id ) ) {
		wp_die( 'Không tìm thấy văn bản.', 404 );
	}
	return $d;
}

/**
 * Xuất văn bản: trang in (trình duyệt → In / Lưu PDF) hoặc file Word (.doc – Word, WPS, LibreOffice mở và sửa được).
 *
 * @param string $title   Tiêu đề / tên file.
 * @param string $content HTML.
 * @param string $format  print|word.
 */
function qldn_doc_output( $title, $content, $format = 'print' ) {
	nocache_headers();
	$content = wpautop( $content ); // Văn bản cũ đã lưu qua trình soạn thảo có thể thiếu thẻ đoạn.

	if ( 'word' === $format ) {
		// Tên file chỉ dùng chữ không dấu (dấu gạch ngang dài, ngoặc… làm hỏng tên file trên một số trình duyệt).
		$name = trim( preg_replace( '/[^A-Za-z0-9._-]+/', '-', remove_accents( $title ) ), '-.' );
		$name = ( $name ? substr( $name, 0, 120 ) : 'van-ban' ) . '.doc';
		header( 'Content-Type: application/msword; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode( $name ) );
		echo "\xEF\xBB\xBF";
		echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><title>' . esc_html( $title ) . '</title>';
		echo '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->';
		echo '<style>' . qldn_doc_css() . '@page WordSection1{size:595.3pt 841.9pt;margin:56.7pt 56.7pt 56.7pt 85pt}div.WordSection1{page:WordSection1}</style></head>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<body><div class="WordSection1">' . wp_kses_post( $content ) . '</div></body></html>';
		exit;
	}
	echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>' . esc_html( $title ) . '</title><style>' . qldn_doc_css() . '</style></head><body onload="window.print()"><div class="qldn-doc">' . wp_kses_post( $content ) . '</div><p class="noprint c"><button onclick="window.print()">In / Lưu PDF</button></p></body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}

/**
 * In văn bản đã lưu.
 */
function qldn_handle_doc_print() {
	qldn_check( 'doc_print', 'qldn_docs' );
	$d = qldn_doc_for_output();
	qldn_doc_output( $d->title, $d->content, 'print' );
}
add_action( 'admin_post_qldn_doc_print', 'qldn_handle_doc_print' );

/**
 * Tải văn bản đã lưu về Word.
 */
function qldn_handle_doc_word() {
	qldn_check( 'doc_word', 'qldn_docs' );
	$d = qldn_doc_for_output();
	qldn_doc_output( $d->title, $d->content, 'word' );
}
add_action( 'admin_post_qldn_doc_word', 'qldn_handle_doc_word' );

/**
 * Lưu mẫu.
 */
function qldn_handle_template_save() {
	qldn_check( 'template_save', 'qldn_settings' );
	global $wpdb;
	$id      = qldn_in( 'id', 'int', 0 );
	$context = qldn_in( 'context', 'key' );
	$kind    = qldn_in( 'kind', 'key' );
	$row     = array(
		'name'    => qldn_in( 'name' ),
		'context' => isset( qldn_doc_contexts()[ $context ] ) ? $context : 'customer',
		'kind'    => 'contract' === $context && isset( qldn_contract_types()[ $kind ] ) ? $kind : '',
		// phpcs:ignore WordPress.Security.NonceVerification -- đã kiểm tra.
		'content' => isset( $_POST['content'] ) ? wpautop( wp_kses_post( wp_unslash( $_POST['content'] ) ) ) : '',
	);
	if ( '' === $row['name'] ) {
		qldn_notice( 'Chưa nhập tên mẫu.', 'error' );
		qldn_go( 'docs', array( 'tab' => 'templates', 'tpl' => $id ? $id : 'new' ) );
	}
	if ( $id ) {
		$wpdb->update( qldn_t( 'templates' ), $row, array( 'id' => $id ) );
	} else {
		$row['created_at'] = qldn_now();
		$wpdb->insert( qldn_t( 'templates' ), $row );
		$id = (int) $wpdb->insert_id;
	}
	qldn_notice( 'Đã lưu mẫu "' . esc_html( $row['name'] ) . '".' );
	qldn_go( 'docs', array( 'tab' => 'templates', 'tpl' => $id ) );
}
add_action( 'admin_post_qldn_template_save', 'qldn_handle_template_save' );

/**
 * Xoá mẫu / khôi phục mẫu gốc.
 */
function qldn_handle_template_action() {
	qldn_check( 'template_action', 'qldn_settings' );
	global $wpdb;
	if ( 'restore' === qldn_in( 'do', 'key' ) ) {
		$n = qldn_seed_templates( true );
		qldn_notice( sprintf( 'Đã thêm lại %d mẫu gốc còn thiếu.', $n ) );
	} else {
		$wpdb->delete( qldn_t( 'templates' ), array( 'id' => qldn_in( 'id', 'int', 0 ) ) );
		qldn_notice( 'Đã xoá mẫu (văn bản đã soạn từ mẫu này vẫn giữ).' );
	}
	qldn_go( 'docs', array( 'tab' => 'templates' ) );
}
add_action( 'admin_post_qldn_template_action', 'qldn_handle_template_action' );

/* ------------------------------------------------------------------ *
 * Trang "Soạn hồ sơ"
 * ------------------------------------------------------------------ */

/**
 * Trang Soạn hồ sơ.
 */
function qldn_page_docs() {
	$doc = qldn_in( 'doc', 'int', 0 );
	if ( $doc ) {
		qldn_doc_edit( qldn_get( 'documents', $doc ) );
		return;
	}
	$tabs = array( 'list' => 'Văn bản đã soạn', 'new' => 'Soạn văn bản mới' );
	if ( current_user_can( 'qldn_settings' ) ) {
		$tabs['templates'] = 'Mẫu văn bản';
	}
	$tab = qldn_in( 'tab', 'key', 'list' );
	$tab = isset( $tabs[ $tab ] ) ? $tab : 'list';
	echo '<div class="wrap qldn"><h1>Soạn hồ sơ</h1>';
	qldn_print_notices();
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $v ) {
		echo '<a class="nav-tab' . ( $k === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( qldn_url( 'docs', array( 'tab' => $k ) ) ) . '">' . esc_html( $v ) . '</a>';
	}
	echo '</nav>';
	call_user_func( 'qldn_docs_tab_' . $tab );
	echo '</div>';
}

/**
 * Danh sách văn bản.
 */
function qldn_docs_tab_list() {
	global $wpdb;
	$t     = qldn_t( 'documents' );
	$s     = qldn_in( 's' );
	$ctx   = qldn_in( 'context', 'key' );
	$where = ' WHERE 1=1' . qldn_like( $s, array( 'title', 'code' ) );
	if ( isset( qldn_doc_contexts()[ $ctx ] ) ) {
		$where .= $wpdb->prepare( ' AND context = %s', $ctx );
	}
	if ( ! current_user_can( 'qldn_hr' ) ) {
		// Không có quyền nhân sự: ẩn văn bản của nhân viên và hợp đồng lao động.
		$where .= " AND context <> 'employee' AND NOT (context = 'contract' AND object_id IN (SELECT id FROM " . qldn_t( 'contracts' ) . " WHERE party_type = 'employee'))";
	}
	$per             = 50;
	list( $paged, $off ) = qldn_paged( $per );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT id, code, title, context, object_id, user_id, updated_at FROM $t $where ORDER BY id DESC LIMIT %d OFFSET %d", $per, $off ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	qldn_search_box( 'docs', $s, '<select name="context"><option value="">Mọi loại</option>' . qldn_options_html( qldn_doc_contexts(), $ctx ) . '</select> ', 'Tên / số văn bản' );
	echo '<table class="widefat striped qldn-table"><thead><tr><th>Số</th><th>Văn bản</th><th>Thuộc</th><th>Người soạn</th><th>Cập nhật</th><th></th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="6">Chưa có văn bản.</td></tr>';
	}
	$ctxs = qldn_doc_contexts();
	foreach ( $rows as $r ) {
		$u = $r->user_id ? get_userdata( $r->user_id ) : null;
		echo '<tr><td>' . esc_html( $r->code ) . '</td><td><a href="' . esc_url( qldn_url( 'docs', array( 'doc' => $r->id ) ) ) . '"><strong>' . esc_html( $r->title ) . '</strong></a></td><td>' . esc_html( $ctxs[ $r->context ] ?? $r->context ) . '</td><td>' . esc_html( $u ? $u->display_name : '' ) . '</td><td>' . esc_html( qldn_date( $r->updated_at, true ) ) . '</td>';
		echo '<td class="qldn-actions"><a target="_blank" href="' . esc_url( qldn_action_url( 'doc_print', array( 'id' => $r->id ) ) ) . '">In</a> · <a href="' . esc_url( qldn_action_url( 'doc_word', array( 'id' => $r->id ) ) ) . '">Word</a></td></tr>';
	}
	echo '</tbody></table>';
	qldn_pagination( $total, $per, $paged );
}

/**
 * Soạn văn bản mới: chọn mẫu + đối tượng.
 */
function qldn_docs_tab_new() {
	global $wpdb;
	$ctxs = qldn_doc_contexts();
	if ( ! current_user_can( 'qldn_hr' ) ) {
		unset( $ctxs['employee'] );
	}
	echo '<p>Chọn mẫu và đối tượng – dữ liệu (tên, địa chỉ, mã số thuế, số tiền, số tiền bằng chữ…) được điền tự động, sau đó sửa tiếp trước khi in.</p>';
	foreach ( $ctxs as $ctx => $label ) {
		$tpls = qldn_templates_for( $ctx );
		if ( ! $tpls ) {
			continue;
		}
		switch ( $ctx ) {
			case 'contract':
				$sql = 'SELECT id, code, title AS name FROM ' . qldn_t( 'contracts' ) . ( current_user_can( 'qldn_hr' ) ? '' : " WHERE party_type <> 'employee'" ) . ' ORDER BY id DESC LIMIT 300';
				break;
			case 'quote':
				$sql = 'SELECT id, code, title AS name FROM ' . qldn_t( 'quotes' ) . ' ORDER BY id DESC LIMIT 300';
				break;
			case 'customer':
				$sql = 'SELECT id, code, name FROM ' . qldn_t( 'customers' ) . ' ORDER BY id DESC LIMIT 500';
				break;
			default:
				$sql = 'SELECT id, code, name FROM ' . qldn_t( 'employees' ) . " WHERE status = 'active' ORDER BY name";
		}
		$objs = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL
		echo '<h2>' . esc_html( $label ) . '</h2>';
		if ( ! $objs ) {
			echo '<p class="description">Chưa có ' . esc_html( mb_strtolower( $label ) ) . ' nào.</p>';
			continue;
		}
		qldn_form_open( 'doc_create', array( 'class' => 'qldn-filter' ) );
		echo '<input type="hidden" name="context" value="' . esc_attr( $ctx ) . '"><select name="template_id">';
		foreach ( $tpls as $t ) {
			echo '<option value="' . esc_attr( $t->id ) . '">' . esc_html( $t->name ) . '</option>';
		}
		echo '</select> cho <select name="object_id">';
		foreach ( $objs as $ob ) {
			echo '<option value="' . esc_attr( $ob->id ) . '">' . esc_html( $ob->code . ' – ' . $ob->name ) . '</option>';
		}
		echo '</select> <button class="button button-primary">Soạn</button></form>';
	}
}

/**
 * Sửa văn bản.
 *
 * @param object|null $d Văn bản.
 */
function qldn_doc_edit( $d ) {
	if ( ! $d || ! qldn_can_object( $d->context, $d->object_id ) ) {
		echo '<div class="wrap qldn"><p>Không tìm thấy văn bản.</p></div>';
		return;
	}
	$back = array(
		'contract' => array( 'contracts', array( 'view' => $d->object_id ) ),
		'quote'    => array( 'quotes', array( 'view' => $d->object_id ) ),
		'customer' => array( 'customers', array( 'view' => $d->object_id ) ),
		'employee' => array( 'employees', array( 'edit' => $d->object_id ) ),
	);
	echo '<div class="wrap qldn"><h1 class="wp-heading-inline">' . esc_html( $d->title ) . ' <small>' . esc_html( $d->code ) . '</small></h1> ';
	echo '<a class="page-title-action" target="_blank" href="' . esc_url( qldn_action_url( 'doc_print', array( 'id' => $d->id ) ) ) . '">In / Lưu PDF</a> <a class="page-title-action" href="' . esc_url( qldn_action_url( 'doc_word', array( 'id' => $d->id ) ) ) . '">Tải về Word</a>';
	if ( isset( $back[ $d->context ] ) ) {
		echo ' <a class="page-title-action" href="' . esc_url( qldn_url( $back[ $d->context ][0], $back[ $d->context ][1] ) ) . '">Về ' . esc_html( mb_strtolower( qldn_doc_contexts()[ $d->context ] ) ) . '</a>';
	}
	echo '<hr class="wp-header-end">';
	qldn_print_notices();
	echo '<p class="description">Chỗ "……………………" là dữ liệu còn thiếu – điền ở đây hoặc bổ sung vào hồ sơ khách hàng / nhân viên / hợp đồng rồi soạn lại.</p>';
	qldn_form_open( 'doc_save' );
	echo '<input type="hidden" name="id" value="' . esc_attr( $d->id ) . '"><p><input type="text" name="title" class="large-text" value="' . esc_attr( $d->title ) . '"></p>';
	wp_editor( $d->content, 'qldn_doc_content', array( 'textarea_name' => 'content', 'textarea_rows' => 30, 'media_buttons' => false ) );
	submit_button( 'Lưu văn bản' );
	echo '</form>';
	qldn_form_open( 'doc_delete', array( 'onsubmit' => "return confirm('Xoá văn bản này?')" ) );
	echo '<input type="hidden" name="id" value="' . esc_attr( $d->id ) . '"><button class="button-link qldn-neg">Xoá văn bản</button></form></div>';
}

/**
 * Mẫu văn bản: danh sách + sửa.
 */
function qldn_docs_tab_templates() {
	global $wpdb;
	$tpl = qldn_in( 'tpl' );
	if ( '' !== $tpl ) {
		qldn_template_form( 'new' === $tpl ? null : qldn_get( 'templates', (int) $tpl ) );
		return;
	}
	$rows  = $wpdb->get_results( 'SELECT id, name, context, kind FROM ' . qldn_t( 'templates' ) . ' ORDER BY context, kind, name' );
	$ctxs  = qldn_doc_contexts();
	$types = qldn_contract_types();
	echo '<p><a class="button button-primary" href="' . esc_url( qldn_url( 'docs', array( 'tab' => 'templates', 'tpl' => 'new' ) ) ) . '">Thêm mẫu</a> ';
	qldn_form_open( 'template_action', array( 'class' => 'qldn-inline' ) );
	echo '<input type="hidden" name="do" value="restore"><button class="button">Thêm lại các mẫu gốc đã xoá</button></form></p>';
	echo '<table class="widefat striped qldn-table qldn-narrow"><thead><tr><th>Mẫu</th><th>Dùng cho</th><th></th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		echo '<tr><td><a href="' . esc_url( qldn_url( 'docs', array( 'tab' => 'templates', 'tpl' => $r->id ) ) ) . '"><strong>' . esc_html( $r->name ) . '</strong></a></td><td>' . esc_html( ( $ctxs[ $r->context ] ?? $r->context ) . ( $r->kind ? ' – ' . ( $types[ $r->kind ] ?? $r->kind ) : '' ) ) . '</td><td>';
		qldn_form_open( 'template_action', array( 'class' => 'qldn-inline', 'onsubmit' => "return confirm('Xoá mẫu này?')" ) );
		echo '<input type="hidden" name="id" value="' . esc_attr( $r->id ) . '"><button class="button-link qldn-neg">Xoá</button></form></td></tr>';
	}
	echo '</tbody></table><p class="description">Mẫu gốc là bản tham khảo, cần luật sư / pháp chế rà soát cho phù hợp từng giao dịch trước khi ký.</p>';
}

/**
 * Form sửa mẫu.
 *
 * @param object|null $t Mẫu.
 */
function qldn_template_form( $t ) {
	qldn_form_open( 'template_save' );
	echo '<input type="hidden" name="id" value="' . esc_attr( $t ? $t->id : 0 ) . '"><table class="form-table">';
	qldn_field( 'Tên mẫu', 'name', $t ? $t->name : '', array( 'required' => true, 'class' => 'large-text' ) );
	qldn_field( 'Dùng cho', 'context', $t ? $t->context : 'contract', array( 'type' => 'select', 'options' => qldn_doc_contexts() ) );
	qldn_field( 'Loại hợp đồng', 'kind', $t ? $t->kind : '', array( 'type' => 'select', 'options' => array( '' => 'Mọi loại' ) + qldn_contract_types(), 'help' => 'Chỉ áp dụng khi "Dùng cho" là Hợp đồng.' ) );
	echo '</table>';
	echo '<div class="qldn-tpl-grid"><div>';
	wp_editor( $t ? $t->content : '', 'qldn_tpl_content', array( 'textarea_name' => 'content', 'textarea_rows' => 34, 'media_buttons' => false ) );
	submit_button( 'Lưu mẫu' );
	echo '</div><aside class="qldn-fields"><h3>Trường trộn</h3><p class="description">Bấm để chép, dán vào mẫu. Trường của hợp đồng / báo giá tự kèm thông tin khách hàng hoặc nhân viên liên quan.</p>';
	foreach ( qldn_merge_fields() as $group => $fields ) {
		echo '<h4>' . esc_html( $group ) . '</h4><ul>';
		foreach ( $fields as $k => $label ) {
			echo '<li><code class="qldn-copy" title="' . esc_attr( $label ) . '">{{' . esc_html( $k ) . '}}</code> ' . esc_html( $label ) . '</li>';
		}
		echo '</ul>';
	}
	echo '</aside></div></form>';
}
