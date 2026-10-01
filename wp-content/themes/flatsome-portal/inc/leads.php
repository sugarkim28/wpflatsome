<?php
/**
 * Khách hàng đăng ký: lưu vào mục "Khách đăng ký", gắn với dự án, gửi email báo
 * (email chung + email riêng của dự án nếu có), xuất CSV, lọc theo dự án.
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đăng ký mục khách hàng.
 */
function sgp_register_lead_cpt() {
	register_post_type(
		'sgp_lead',
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
add_action( 'init', 'sgp_register_lead_cpt' );

/**
 * Chuẩn hoá số điện thoại Việt Nam.
 *
 * @param string $phone Số.
 * @return string Số hợp lệ hoặc rỗng.
 */
function sgp_normalize_phone( $phone ) {
	$phone = preg_replace( '/[\s.\-()]/', '', (string) $phone );
	return preg_match( '/^(\+?84|0)(\d{9,10})$/', $phone, $m ) ? '0' . $m[2] : '';
}

/**
 * Xử lý form (admin-post.php?action=sgp_lead).
 */
function sgp_handle_lead() {
	$redirect = wp_get_referer();
	$redirect = $redirect ? $redirect : home_url( '/' );
	$redirect = preg_replace( '/#.*$/', '', remove_query_arg( array( 'sgp_status', 'sgp_form' ), $redirect ) );
	$source   = isset( $_POST['sgp_source'] ) ? sanitize_text_field( wp_unslash( $_POST['sgp_source'] ) ) : '';
	$form     = sanitize_title( $source );

	$back = function ( $code ) use ( $redirect, $form ) {
		wp_safe_redirect( add_query_arg( array( 'sgp_status' => $code, 'sgp_form' => $form ), $redirect ) . '#sgp-form-' . $form );
		exit;
	};

	if ( ! isset( $_POST['sgp_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sgp_nonce'] ) ), 'sgp_lead' ) ) {
		$back( 'expired' );
	}
	if ( ! empty( $_POST['sgp_website'] ) ) { // Honeypot.
		$back( 'success' );
	}

	$name    = isset( $_POST['sgp_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sgp_name'] ) ) : '';
	$phone   = isset( $_POST['sgp_phone'] ) ? sgp_normalize_phone( sanitize_text_field( wp_unslash( $_POST['sgp_phone'] ) ) ) : '';
	$email   = isset( $_POST['sgp_email'] ) ? sanitize_email( wp_unslash( $_POST['sgp_email'] ) ) : '';
	$project = isset( $_POST['sgp_project'] ) ? absint( $_POST['sgp_project'] ) : 0;
	if ( $project && 'du_an' !== get_post_type( $project ) ) {
		$project = 0;
	}
	if ( '' === $name || '' === $phone ) {
		$back( 'invalid' );
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		$key = 'sgp_lead_' . md5( $ip );
		if ( get_transient( $key ) ) {
			$back( 'wait' );
		}
		set_transient( $key, 1, MINUTE_IN_SECONDS );
	}

	$project_name = $project ? get_the_title( $project ) : '';
	$post_id      = wp_insert_post(
		array(
			'post_type'   => 'sgp_lead',
			'post_status' => 'private',
			'post_title'  => $name . ' – ' . $phone . ( $project_name ? ' – ' . $project_name : '' ),
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
		'project' => $project,
		'source'  => $source,
		'page'    => esc_url_raw( $redirect ),
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $post_id, '_sgp_' . $k, $v );
	}

	$to = array_filter( array_unique( array( sgp_opt( 'lead_email' ), $project ? sgp_meta( 'lead_email', $project ) : '' ) ), 'is_email' );
	if ( $to ) {
		$lines = array(
			'Họ tên: ' . $name,
			'Điện thoại: ' . $phone,
			$email ? 'Email: ' . $email : null,
			$project_name ? 'Dự án quan tâm: ' . $project_name : null,
			'Form: ' . $source,
			'Trang: ' . $redirect,
			'',
			'Xem danh sách: ' . admin_url( 'edit.php?post_type=sgp_lead' ),
		);
		$lines   = array_filter(
			$lines,
			function ( $l ) {
				return null !== $l;
			}
		);
		$headers = is_email( $email ) ? array( 'Reply-To: ' . $name . ' <' . $email . '>' ) : array();
		$subject = '[' . sgp_opt( 'company' ) . '] Khách mới' . ( $project_name ? ' – ' . $project_name : '' ) . ': ' . $name . ' – ' . $phone;
		$sent    = wp_mail( array_values( $to ), $subject, implode( "\n", $lines ), $headers );
		update_post_meta( $post_id, '_sgp_mail', $sent ? 'ok' : 'fail' );
	}

	/**
	 * Hook cho CRM / Google Sheets / Telegram.
	 *
	 * @param int   $post_id ID khách.
	 * @param array $meta    Dữ liệu.
	 */
	do_action( 'sgp_lead_created', $post_id, $meta );
	$back( 'success' );
}
add_action( 'admin_post_nopriv_sgp_lead', 'sgp_handle_lead' );
add_action( 'admin_post_sgp_lead', 'sgp_handle_lead' );

/**
 * Cột danh sách.
 *
 * @param array $cols Cột.
 * @return array
 */
function sgp_lead_columns( $cols ) {
	return array(
		'cb'          => $cols['cb'],
		'title'       => 'Khách hàng',
		'sgp_phone'   => 'Điện thoại',
		'sgp_email'   => 'Email',
		'sgp_project' => 'Dự án quan tâm',
		'sgp_source'  => 'Đăng ký từ',
		'sgp_mail'    => 'Email báo',
		'date'        => 'Thời gian',
	);
}
add_filter( 'manage_sgp_lead_posts_columns', 'sgp_lead_columns' );

/**
 * Nội dung cột.
 *
 * @param string $col Cột.
 * @param int    $id  ID.
 */
function sgp_lead_column( $col, $id ) {
	switch ( $col ) {
		case 'sgp_phone':
			$p = get_post_meta( $id, '_sgp_phone', true );
			printf( '<a href="tel:%1$s">%2$s</a>', esc_attr( $p ), esc_html( $p ) );
			break;
		case 'sgp_email':
			echo esc_html( get_post_meta( $id, '_sgp_email', true ) );
			break;
		case 'sgp_project':
			$p = absint( get_post_meta( $id, '_sgp_project', true ) );
			echo $p ? '<a href="' . esc_url( add_query_arg( 'sgp_project', $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a>' : '—';
			break;
		case 'sgp_source':
			echo esc_html( get_post_meta( $id, '_sgp_source', true ) );
			break;
		case 'sgp_mail':
			$m = get_post_meta( $id, '_sgp_mail', true );
			if ( 'ok' === $m ) {
				echo '<span style="color:#00a32a">✔ Đã gửi</span>';
			} elseif ( 'fail' === $m ) {
				echo '<a style="color:#b32d2e" href="' . esc_url( admin_url( 'edit.php?post_type=sgp_lead&page=sgp-mail' ) ) . '">✖ Lỗi gửi</a>';
			} else {
				echo '—';
			}
			break;
	}
}
add_action( 'manage_sgp_lead_posts_custom_column', 'sgp_lead_column', 10, 2 );

/**
 * Bộ lọc theo dự án + nút xuất CSV.
 *
 * @param string $post_type Loại bài.
 */
function sgp_lead_filters( $post_type ) {
	if ( 'sgp_lead' !== $post_type ) {
		return;
	}
	$cur = isset( $_GET['sgp_project'] ) ? absint( $_GET['sgp_project'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<select name="sgp_project"><option value="">Tất cả dự án</option>';
	foreach ( get_posts( array( 'post_type' => 'du_an', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		printf( '<option value="%d" %s>%s</option>', (int) $p->ID, selected( $cur, $p->ID, false ), esc_html( $p->post_title ) );
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'sgp_lead_filters' );

/**
 * Áp bộ lọc dự án.
 *
 * @param WP_Query $q Truy vấn.
 */
function sgp_lead_filter_query( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || 'sgp_lead' !== $q->get( 'post_type' ) ) {
		return;
	}
	$cur = isset( $_GET['sgp_project'] ) ? absint( $_GET['sgp_project'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $cur ) {
		$q->set( 'meta_query', array( array( 'key' => '_sgp_project', 'value' => $cur ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
}
add_action( 'pre_get_posts', 'sgp_lead_filter_query' );

/**
 * Nút xuất CSV.
 *
 * @param string $which top|bottom.
 */
function sgp_lead_export_button( $which ) {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'sgp_lead' !== $screen->post_type || ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$cur = isset( $_GET['sgp_project'] ) ? absint( $_GET['sgp_project'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=sgp_lead_export&sgp_project=' . $cur ), 'sgp_lead_export' );
	printf( '<div class="alignleft actions"><a class="button" href="%s">Xuất Excel (CSV)</a></div>', esc_url( $url ) );
}
add_action( 'manage_posts_extra_tablenav', 'sgp_lead_export_button' );

/**
 * Xuất CSV (UTF-8 BOM, chặn CSV injection).
 */
function sgp_lead_export() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'sgp_lead_export' );
	$cur  = isset( $_GET['sgp_project'] ) ? absint( $_GET['sgp_project'] ) : 0;
	$args = array( 'post_type' => 'sgp_lead', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' );
	if ( $cur ) {
		$args['meta_query'] = array( array( 'key' => '_sgp_project', 'value' => $cur ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
	fputcsv( $out, array( 'Thời gian', 'Họ tên', 'Điện thoại', 'Email', 'Dự án', 'Form', 'Trang' ), ',', '"', '\\' );
	foreach ( get_posts( $args ) as $id ) {
		$p = absint( get_post_meta( $id, '_sgp_project', true ) );
		fputcsv(
			$out,
			array_map(
				$safe,
				array(
					get_the_date( 'd/m/Y H:i', $id ),
					get_post_meta( $id, '_sgp_name', true ),
					get_post_meta( $id, '_sgp_phone', true ),
					get_post_meta( $id, '_sgp_email', true ),
					$p ? get_the_title( $p ) : '',
					get_post_meta( $id, '_sgp_source', true ),
					get_post_meta( $id, '_sgp_page', true ),
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
add_action( 'admin_post_sgp_lead_export', 'sgp_lead_export' );

/**
 * Hộp chi tiết khách.
 */
function sgp_lead_meta_box() {
	add_meta_box(
		'sgp_lead_detail',
		'Thông tin đăng ký',
		function ( $post ) {
			$p    = absint( get_post_meta( $post->ID, '_sgp_project', true ) );
			$rows = array(
				'Họ tên'     => get_post_meta( $post->ID, '_sgp_name', true ),
				'Điện thoại' => get_post_meta( $post->ID, '_sgp_phone', true ),
				'Email'      => get_post_meta( $post->ID, '_sgp_email', true ),
				'Dự án'      => $p ? get_the_title( $p ) : '',
				'Form'       => get_post_meta( $post->ID, '_sgp_source', true ),
				'Trang'      => get_post_meta( $post->ID, '_sgp_page', true ),
				'Thời gian'  => get_the_date( 'd/m/Y H:i', $post ),
			);
			echo '<table class="widefat striped"><tbody>';
			foreach ( $rows as $label => $value ) {
				printf( '<tr><th style="width:140px">%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( $value ) );
			}
			echo '</tbody></table>';
		},
		'sgp_lead',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_sgp_lead', 'sgp_lead_meta_box' );
