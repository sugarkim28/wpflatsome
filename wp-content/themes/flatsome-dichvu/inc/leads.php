<?php
/**
 * Khách hàng đăng ký: lưu vào mục "Khách đăng ký", gắn với dịch vụ, gửi email báo
 * (email chung + email riêng của dịch vụ nếu có), xuất CSV, lọc theo dịch vụ.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đăng ký mục khách hàng.
 */
function sgd_register_lead_cpt() {
	register_post_type(
		'sgd_lead',
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
			'menu_icon'       => 'dashicons-id-alt',
			'menu_position'   => 6,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'sgd_register_lead_cpt' );

/**
 * Chuẩn hoá số điện thoại Việt Nam.
 *
 * @param string $phone Số.
 * @return string Số hợp lệ hoặc rỗng.
 */
function sgd_normalize_phone( $phone ) {
	$phone = preg_replace( '/[\s.\-()]/', '', (string) $phone );
	return preg_match( '/^(\+?84|0)(\d{9,10})$/', $phone, $m ) ? '0' . $m[2] : '';
}

/**
 * Xử lý form (admin-post.php?action=sgd_lead).
 */
function sgd_handle_lead() {
	$redirect = wp_get_referer();
	$redirect = $redirect ? $redirect : home_url( '/' );
	$redirect = preg_replace( '/#.*$/', '', remove_query_arg( array( 'sgd_status', 'sgd_form' ), $redirect ) );
	$source   = isset( $_POST['sgd_source'] ) ? sanitize_text_field( wp_unslash( $_POST['sgd_source'] ) ) : '';
	$form     = sanitize_title( $source );

	$back = function ( $code ) use ( $redirect, $form ) {
		wp_safe_redirect( add_query_arg( array( 'sgd_status' => $code, 'sgd_form' => $form ), $redirect ) . '#sgd-form-' . $form );
		exit;
	};

	if ( ! isset( $_POST['sgd_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sgd_nonce'] ) ), 'sgd_lead' ) ) {
		$back( 'expired' );
	}
	if ( ! empty( $_POST['sgd_website'] ) ) { // Honeypot.
		$back( 'success' );
	}

	$name    = isset( $_POST['sgd_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sgd_name'] ) ) : '';
	$phone   = isset( $_POST['sgd_phone'] ) ? sgd_normalize_phone( sanitize_text_field( wp_unslash( $_POST['sgd_phone'] ) ) ) : '';
	$email   = isset( $_POST['sgd_email'] ) ? sanitize_email( wp_unslash( $_POST['sgd_email'] ) ) : '';
	$note    = isset( $_POST['sgd_note'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['sgd_note'] ) ), 0, 1000 ) : '';
	$raw     = isset( $_POST['sgd_service'] ) ? sanitize_text_field( wp_unslash( $_POST['sgd_service'] ) ) : '';
	$group   = '';
	$service = 0;
	if ( preg_match( '/^g(\d+)$/', $raw, $m ) ) {
		$term  = get_term( (int) $m[1], 'nhom_dich_vu' );
		$group = ( $term && ! is_wp_error( $term ) ) ? $term->name : '';
	} elseif ( '0' === $raw ) {
		$group = 'Khác';
	} else {
		$service = absint( $raw );
		if ( $service && 'dich_vu' !== get_post_type( $service ) ) {
			$service = 0;
		}
	}
	if ( '' === $name || '' === $phone ) {
		$back( 'invalid' );
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		$key = 'sgd_lead_' . md5( $ip );
		if ( get_transient( $key ) ) {
			$back( 'wait' );
		}
		set_transient( $key, 1, MINUTE_IN_SECONDS );
	}

	$service_name = $service ? get_the_title( $service ) : $group;
	$post_id      = wp_insert_post(
		array(
			'post_type'   => 'sgd_lead',
			'post_status' => 'private',
			'post_title'  => $name . ' – ' . $phone . ( $service_name ? ' – ' . $service_name : '' ),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		$back( 'error' );
	}
	$meta = array(
		'name'    => $name,
		'phone'   => $phone,
		'email'   => $email,
		'service' => $service,
		'group'   => $group,
		'note'    => $note,
		'source'  => $source,
		'page'    => esc_url_raw( $redirect ),
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $post_id, '_sgd_' . $k, $v );
	}

	$to = array_filter( array_unique( array( sgd_opt( 'lead_email' ), $service ? sgd_meta( 'lead_email', $service ) : '' ) ), 'is_email' );
	if ( $to ) {
		$lines = array(
			'Họ tên: ' . $name,
			'Điện thoại: ' . $phone,
			$email ? 'Email: ' . $email : null,
			$service_name ? 'Dịch vụ quan tâm: ' . $service_name : null,
			$note ? 'Nội dung: ' . $note : null,
			'Form: ' . $source,
			'Trang: ' . $redirect,
			'',
			'Xem danh sách: ' . admin_url( 'edit.php?post_type=sgd_lead' ),
		);
		$lines   = array_filter(
			$lines,
			function ( $l ) {
				return null !== $l;
			}
		);
		$headers = is_email( $email ) ? array( 'Reply-To: ' . $name . ' <' . $email . '>' ) : array();
		$subject = '[' . sgd_opt( 'company' ) . '] Khách mới' . ( $service_name ? ' – ' . $service_name : '' ) . ': ' . $name . ' – ' . $phone;
		$sent    = wp_mail( array_values( $to ), $subject, implode( "\n", $lines ), $headers );
		update_post_meta( $post_id, '_sgd_mail', $sent ? 'ok' : 'fail' );
	}

	/**
	 * Hook cho CRM / Google Sheets / Telegram.
	 *
	 * @param int   $post_id ID khách.
	 * @param array $meta    Dữ liệu.
	 */
	do_action( 'sgd_lead_created', $post_id, $meta );
	$back( 'success' );
}
add_action( 'admin_post_nopriv_sgd_lead', 'sgd_handle_lead' );
add_action( 'admin_post_sgd_lead', 'sgd_handle_lead' );

/**
 * Cột danh sách.
 *
 * @param array $cols Cột.
 * @return array
 */
function sgd_lead_columns( $cols ) {
	return array(
		'cb'          => $cols['cb'],
		'title'       => 'Khách hàng',
		'sgd_phone'   => 'Điện thoại',
		'sgd_email'   => 'Email',
		'sgd_service' => 'Dịch vụ quan tâm',
		'sgd_note'    => 'Nội dung',
		'sgd_source'  => 'Đăng ký từ',
		'sgd_mail'    => 'Email báo',
		'date'        => 'Thời gian',
	);
}
add_filter( 'manage_sgd_lead_posts_columns', 'sgd_lead_columns' );

/**
 * Nội dung cột.
 *
 * @param string $col Cột.
 * @param int    $id  ID.
 */
function sgd_lead_column( $col, $id ) {
	switch ( $col ) {
		case 'sgd_phone':
			$p = get_post_meta( $id, '_sgd_phone', true );
			printf( '<a href="tel:%1$s">%2$s</a>', esc_attr( $p ), esc_html( $p ) );
			break;
		case 'sgd_email':
			echo esc_html( get_post_meta( $id, '_sgd_email', true ) );
			break;
		case 'sgd_service':
			$p = absint( get_post_meta( $id, '_sgd_service', true ) );
			echo $p ? '<a href="' . esc_url( add_query_arg( 'sgd_service', $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a>' : esc_html( get_post_meta( $id, '_sgd_group', true ) ? get_post_meta( $id, '_sgd_group', true ) : '—' );
			break;
		case 'sgd_note':
			echo esc_html( wp_trim_words( get_post_meta( $id, '_sgd_note', true ), 15, '…' ) );
			break;
		case 'sgd_source':
			echo esc_html( get_post_meta( $id, '_sgd_source', true ) );
			break;
		case 'sgd_mail':
			$m = get_post_meta( $id, '_sgd_mail', true );
			if ( 'ok' === $m ) {
				echo '<span style="color:#00a32a">✔ Đã gửi</span>';
			} elseif ( 'fail' === $m ) {
				echo '<a style="color:#b32d2e" href="' . esc_url( admin_url( 'edit.php?post_type=sgd_lead&page=sgd-mail' ) ) . '">✖ Lỗi gửi</a>';
			} else {
				echo '—';
			}
			break;
	}
}
add_action( 'manage_sgd_lead_posts_custom_column', 'sgd_lead_column', 10, 2 );

/**
 * Bộ lọc theo dịch vụ + nút xuất CSV.
 *
 * @param string $post_type Loại bài.
 */
function sgd_lead_filters( $post_type ) {
	if ( 'sgd_lead' !== $post_type ) {
		return;
	}
	$cur = isset( $_GET['sgd_service'] ) ? absint( $_GET['sgd_service'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<select name="sgd_service"><option value="">Tất cả dịch vụ</option>';
	foreach ( get_posts( array( 'post_type' => 'dich_vu', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		printf( '<option value="%d" %s>%s</option>', (int) $p->ID, selected( $cur, $p->ID, false ), esc_html( $p->post_title ) );
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'sgd_lead_filters' );

/**
 * Áp bộ lọc dịch vụ.
 *
 * @param WP_Query $q Truy vấn.
 */
function sgd_lead_filter_query( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || 'sgd_lead' !== $q->get( 'post_type' ) ) {
		return;
	}
	$cur = isset( $_GET['sgd_service'] ) ? absint( $_GET['sgd_service'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $cur ) {
		$q->set( 'meta_query', array( array( 'key' => '_sgd_service', 'value' => $cur ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
}
add_action( 'pre_get_posts', 'sgd_lead_filter_query' );

/**
 * Nút xuất CSV.
 *
 * @param string $which top|bottom.
 */
function sgd_lead_export_button( $which ) {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'sgd_lead' !== $screen->post_type || ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$cur = isset( $_GET['sgd_service'] ) ? absint( $_GET['sgd_service'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=sgd_lead_export&sgd_service=' . $cur ), 'sgd_lead_export' );
	printf( '<div class="alignleft actions"><a class="button" href="%s">Xuất Excel (CSV)</a></div>', esc_url( $url ) );
}
add_action( 'manage_posts_extra_tablenav', 'sgd_lead_export_button' );

/**
 * Xuất CSV (UTF-8 BOM, chặn CSV injection).
 */
function sgd_lead_export() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'sgd_lead_export' );
	$cur  = isset( $_GET['sgd_service'] ) ? absint( $_GET['sgd_service'] ) : 0;
	$args = array( 'post_type' => 'sgd_lead', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' );
	if ( $cur ) {
		$args['meta_query'] = array( array( 'key' => '_sgd_service', 'value' => $cur ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=khach-hang-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out  = fopen( 'php://output', 'w' );
	$safe = function ( $v ) {
		$v = (string) $v;
		return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v;
	};
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'Thời gian', 'Họ tên', 'Điện thoại', 'Email', 'Dịch vụ', 'Nội dung', 'Form', 'Trang' ), ',', '"', '\\' );
	foreach ( get_posts( $args ) as $id ) {
		$p = absint( get_post_meta( $id, '_sgd_service', true ) );
		fputcsv(
			$out,
			array_map(
				$safe,
				array(
					get_the_date( 'd/m/Y H:i', $id ),
					get_post_meta( $id, '_sgd_name', true ),
					get_post_meta( $id, '_sgd_phone', true ),
					get_post_meta( $id, '_sgd_email', true ),
					$p ? get_the_title( $p ) : get_post_meta( $id, '_sgd_group', true ),
					get_post_meta( $id, '_sgd_note', true ),
					get_post_meta( $id, '_sgd_source', true ),
					get_post_meta( $id, '_sgd_page', true ),
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
add_action( 'admin_post_sgd_lead_export', 'sgd_lead_export' );

/**
 * Hộp chi tiết khách.
 */
function sgd_lead_meta_box() {
	add_meta_box(
		'sgd_lead_detail',
		'Thông tin đăng ký',
		function ( $post ) {
			$p    = absint( get_post_meta( $post->ID, '_sgd_service', true ) );
			$rows = array(
				'Họ tên'     => get_post_meta( $post->ID, '_sgd_name', true ),
				'Điện thoại' => get_post_meta( $post->ID, '_sgd_phone', true ),
				'Email'      => get_post_meta( $post->ID, '_sgd_email', true ),
				'Dịch vụ'    => $p ? get_the_title( $p ) : get_post_meta( $post->ID, '_sgd_group', true ),
				'Nội dung'   => get_post_meta( $post->ID, '_sgd_note', true ),
				'Form'       => get_post_meta( $post->ID, '_sgd_source', true ),
				'Trang'      => get_post_meta( $post->ID, '_sgd_page', true ),
				'Thời gian'  => get_the_date( 'd/m/Y H:i', $post ),
			);
			echo '<table class="widefat striped"><tbody>';
			foreach ( $rows as $label => $value ) {
				printf( '<tr><th style="width:140px">%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( $value ) );
			}
			echo '</tbody></table>';
		},
		'sgd_lead',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_sgd_lead', 'sgd_lead_meta_box' );
