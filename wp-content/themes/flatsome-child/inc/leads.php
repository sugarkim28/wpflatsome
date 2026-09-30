<?php
/**
 * Lưu khách hàng đăng ký (lead): CPT riêng, email thông báo, xuất CSV.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nhu cầu khách hàng.
 *
 * @return array
 */
function bds_lead_needs() {
	return array(
		'o'      => 'Mua để ở',
		'dau-tu' => 'Mua đầu tư',
		'thue'   => 'Thuê',
		'khac'   => 'Khác',
	);
}

/**
 * Đăng ký post type "bds_lead" (chỉ hiện trong admin).
 */
function bds_register_lead_cpt() {
	register_post_type(
		'bds_lead',
		array(
			'labels'          => array(
				'name'          => 'Khách hàng đăng ký',
				'singular_name' => 'Khách hàng',
				'menu_name'     => 'Khách đăng ký',
				'all_items'     => 'Tất cả khách hàng',
				'edit_item'     => 'Chi tiết khách hàng',
				'search_items'  => 'Tìm khách hàng',
				'not_found'     => 'Chưa có khách hàng nào',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-id-alt',
			'menu_position'   => 25,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'bds_register_lead_cpt' );

/**
 * Chuẩn hoá & kiểm tra số điện thoại Việt Nam.
 *
 * @param string $phone Số nhập vào.
 * @return string Số hợp lệ hoặc chuỗi rỗng.
 */
function bds_normalize_phone( $phone ) {
	$phone = preg_replace( '/[\s.\-()]/', '', (string) $phone );
	if ( preg_match( '/^(\+?84|0)(\d{9,10})$/', $phone, $m ) ) {
		return '0' . $m[2];
	}
	return '';
}

/**
 * IP người gửi (chỉ dùng để chống spam).
 *
 * @return string
 */
function bds_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Xử lý form (admin-post.php?action=bds_lead).
 */
function bds_handle_lead_submit() {
	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = home_url( '/' );
	}
	$redirect = remove_query_arg( array( 'bds_status', 'bds_form' ), $redirect );
	$redirect = preg_replace( '/#.*$/', '', $redirect );

	$source = isset( $_POST['bds_source'] ) ? sanitize_text_field( wp_unslash( $_POST['bds_source'] ) ) : '';
	$form   = sanitize_title( $source );

	// Quay lại đúng form đã gửi, kèm trạng thái để hiển thị thông báo.
	$back = function ( $code ) use ( $redirect, $form ) {
		$url = add_query_arg(
			array(
				'bds_status' => $code,
				'bds_form'   => $form,
			),
			$redirect
		);
		wp_safe_redirect( $url . '#bds-form-' . $form );
		exit;
	};

	if ( ! isset( $_POST['bds_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bds_nonce'] ) ), 'bds_lead' ) ) {
		$back( 'expired' );
	}

	// Honeypot: bot thường điền mọi ô.
	if ( ! empty( $_POST['bds_website'] ) ) {
		$back( 'success' );
	}

	$name    = isset( $_POST['bds_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bds_name'] ) ) : '';
	$phone   = isset( $_POST['bds_phone'] ) ? bds_normalize_phone( sanitize_text_field( wp_unslash( $_POST['bds_phone'] ) ) ) : '';
	$email   = isset( $_POST['bds_email'] ) ? sanitize_email( wp_unslash( $_POST['bds_email'] ) ) : '';
	$need    = isset( $_POST['bds_need'] ) ? sanitize_key( wp_unslash( $_POST['bds_need'] ) ) : '';
	$message = isset( $_POST['bds_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bds_message'] ) ) : '';

	$needs = bds_lead_needs();
	if ( ! isset( $needs[ $need ] ) ) {
		$need = '';
	}

	if ( '' === $name || '' === $phone ) {
		$back( 'invalid' );
	}

	// Chống gửi liên tục: 1 lần / 60 giây / IP.
	$ip = bds_client_ip();
	if ( $ip ) {
		$key = 'bds_lead_' . md5( $ip );
		if ( get_transient( $key ) ) {
			$back( 'wait' );
		}
		set_transient( $key, 1, MINUTE_IN_SECONDS );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'bds_lead',
			'post_status' => 'private',
			'post_title'  => $name . ' – ' . $phone,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		$back( 'error' );
	}

	update_post_meta( $post_id, '_bds_name', $name );
	update_post_meta( $post_id, '_bds_phone', $phone );
	update_post_meta( $post_id, '_bds_email', $email );
	update_post_meta( $post_id, '_bds_need', $need );
	update_post_meta( $post_id, '_bds_message', $message );
	update_post_meta( $post_id, '_bds_source', $source );
	update_post_meta( $post_id, '_bds_page', esc_url_raw( $redirect ) );

	$to = bds_opt( 'lead_email' );
	if ( is_email( $to ) ) {
		$lines = array(
			'Họ tên: ' . $name,
			'Điện thoại: ' . $phone,
			'Email: ' . $email,
			'Nhu cầu: ' . ( $need ? $needs[ $need ] : '' ),
			'Ghi chú: ' . $message,
			'Form: ' . $source,
			'Trang: ' . $redirect,
			'',
			'Xem danh sách: ' . admin_url( 'edit.php?post_type=bds_lead' ),
		);
		$headers = array();
		if ( is_email( $email ) ) {
			$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
		}
		$lines = array_filter(
			$lines,
			function ( $l ) {
				return '' === $l || ! preg_match( '/: $/', $l );
			}
		);
		$sent  = wp_mail( $to, '[' . get_bloginfo( 'name' ) . '] Khách hàng mới: ' . $name . ' – ' . $phone, implode( "\n", $lines ), $headers );
		update_post_meta( $post_id, '_bds_mail', $sent ? 'ok' : 'fail' );
	}

	/**
	 * Hook cho tích hợp CRM / Google Sheets / Telegram...
	 *
	 * @param int   $post_id ID lead.
	 * @param array $data    Dữ liệu.
	 */
	do_action( 'bds_lead_created', $post_id, compact( 'name', 'phone', 'email', 'need', 'message', 'source' ) );

	$back( 'success' );
}
add_action( 'admin_post_nopriv_bds_lead', 'bds_handle_lead_submit' );
add_action( 'admin_post_bds_lead', 'bds_handle_lead_submit' );

/**
 * Cột danh sách trong admin.
 *
 * @param array $cols Cột.
 * @return array
 */
function bds_lead_columns( $cols ) {
	return array(
		'cb'          => $cols['cb'],
		'title'       => 'Khách hàng',
		'bds_phone'   => 'Điện thoại',
		'bds_email'   => 'Email',
		'bds_source'  => 'Đăng ký từ',
		'bds_mail'    => 'Email báo',
		'date'        => 'Thời gian',
	);
}
add_filter( 'manage_bds_lead_posts_columns', 'bds_lead_columns' );

/**
 * Nội dung cột.
 *
 * @param string $col     Cột.
 * @param int    $post_id ID.
 */
function bds_lead_column_content( $col, $post_id ) {
	switch ( $col ) {
		case 'bds_phone':
			$phone = get_post_meta( $post_id, '_bds_phone', true );
			printf( '<a href="tel:%1$s">%2$s</a>', esc_attr( bds_tel( $phone ) ), esc_html( $phone ) );
			break;
		case 'bds_email':
			echo esc_html( get_post_meta( $post_id, '_bds_email', true ) );
			break;
		case 'bds_need':
			$needs = bds_lead_needs();
			$need  = get_post_meta( $post_id, '_bds_need', true );
			echo esc_html( isset( $needs[ $need ] ) ? $needs[ $need ] : '' );
			break;
		case 'bds_message':
			echo esc_html( wp_trim_words( get_post_meta( $post_id, '_bds_message', true ), 15 ) );
			break;
		case 'bds_source':
			echo esc_html( get_post_meta( $post_id, '_bds_source', true ) );
			break;
		case 'bds_mail':
			$mail = get_post_meta( $post_id, '_bds_mail', true );
			if ( 'ok' === $mail ) {
				echo '<span style="color:#00a32a">✔ Đã gửi</span>';
			} elseif ( 'fail' === $mail ) {
				echo '<a style="color:#b32d2e" href="' . esc_url( admin_url( 'edit.php?post_type=bds_lead&page=bds-mail' ) ) . '">✖ Lỗi gửi</a>';
			} else {
				echo '—';
			}
			break;
	}
}
add_action( 'manage_bds_lead_posts_custom_column', 'bds_lead_column_content', 10, 2 );

/**
 * Hộp chi tiết trong màn hình sửa lead.
 */
function bds_lead_meta_box() {
	add_meta_box(
		'bds_lead_detail',
		'Thông tin đăng ký',
		function ( $post ) {
			$needs = bds_lead_needs();
			$need  = get_post_meta( $post->ID, '_bds_need', true );
			$rows  = array(
				'Họ tên'     => get_post_meta( $post->ID, '_bds_name', true ),
				'Điện thoại' => get_post_meta( $post->ID, '_bds_phone', true ),
				'Email'      => get_post_meta( $post->ID, '_bds_email', true ),
				'Nhu cầu'    => isset( $needs[ $need ] ) ? $needs[ $need ] : '',
				'Ghi chú'    => get_post_meta( $post->ID, '_bds_message', true ),
				'Form'       => get_post_meta( $post->ID, '_bds_source', true ),
				'Trang'      => get_post_meta( $post->ID, '_bds_page', true ),
				'Thời gian'  => get_the_date( 'd/m/Y H:i', $post ),
			);
			echo '<table class="widefat striped"><tbody>';
			foreach ( $rows as $label => $value ) {
				printf( '<tr><th style="width:140px">%s</th><td>%s</td></tr>', esc_html( $label ), nl2br( esc_html( $value ) ) );
			}
			echo '</tbody></table>';
		},
		'bds_lead',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_bds_lead', 'bds_lead_meta_box' );

/**
 * Nút "Xuất CSV" trên trang danh sách.
 *
 * @param string $which top|bottom.
 */
function bds_lead_export_button( $which ) {
	if ( 'top' !== $which || 'bds_lead' !== get_current_screen()->post_type || ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=bds_lead_export' ), 'bds_lead_export' );
	printf( '<div class="alignleft actions"><a class="button" href="%s">Xuất Excel (CSV)</a></div>', esc_url( $url ) );
}
add_action( 'manage_posts_extra_tablenav', 'bds_lead_export_button' );

/**
 * Xuất CSV (UTF-8 BOM để Excel đọc đúng tiếng Việt).
 */
function bds_lead_export() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'bds_lead_export' );

	$needs = bds_lead_needs();
	$ids   = get_posts(
		array(
			'post_type'      => 'bds_lead',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=khach-hang-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'Thời gian', 'Họ tên', 'Điện thoại', 'Email', 'Nhu cầu', 'Ghi chú', 'Form', 'Trang' ), ',', '"', '\\' );

	// Chặn CSV injection khi mở bằng Excel.
	$safe = function ( $v ) {
		$v = (string) $v;
		return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v;
	};

	foreach ( $ids as $id ) {
		$need = get_post_meta( $id, '_bds_need', true );
		fputcsv(
			$out,
			array_map(
				$safe,
				array(
					get_the_date( 'd/m/Y H:i', $id ),
					get_post_meta( $id, '_bds_name', true ),
					get_post_meta( $id, '_bds_phone', true ),
					get_post_meta( $id, '_bds_email', true ),
					isset( $needs[ $need ] ) ? $needs[ $need ] : '',
					get_post_meta( $id, '_bds_message', true ),
					get_post_meta( $id, '_bds_source', true ),
					get_post_meta( $id, '_bds_page', true ),
				)
			),
			',',
			'"',
			'\\'
		);
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_bds_lead_export', 'bds_lead_export' );
