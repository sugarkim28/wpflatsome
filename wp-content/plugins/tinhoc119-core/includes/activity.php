<?php
/**
 * Nhật ký hoạt động quản trị + trang xem lỗi PHP.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ghi 1 dòng nhật ký.
 *
 * @param string   $action  Hành động.
 * @param string   $object  Đối tượng.
 * @param string   $detail  Chi tiết.
 * @param int|null $user_id Người dùng (null = hiện tại).
 */
function th119_log_activity( $action, $object = '', $detail = '', $user_id = null ) {
	global $wpdb;
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		th119_table( 'activity' ),
		array(
			'user_id'    => null === $user_id ? get_current_user_id() : (int) $user_id,
			'action'     => substr( $action, 0, 60 ),
			'object'     => substr( (string) $object, 0, 191 ),
			'detail'     => (string) $detail,
			'ip_hash'    => th119_ip_hash(),
			'created_at' => th119_now(),
		)
	);
}

add_action(
	'wp_login',
	function ( $login, $user ) {
		th119_log_activity( 'login', $login, '', $user->ID );
	},
	10,
	2
);
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( $new === $old || wp_is_post_revision( $post ) || in_array( $post->post_type, array( 'nav_menu_item', 'customize_changeset', 'oembed_cache', 'wp_global_styles' ), true ) || 'auto-draft' === $new ) {
			return;
		}
		if ( in_array( $new, array( 'publish', 'trash', 'future', 'pending' ), true ) || 'publish' === $old ) {
			th119_log_activity( 'post_' . $new, $post->post_type . ':' . $post->ID, $post->post_title . ' (' . $old . ' → ' . $new . ')' );
		}
	},
	10,
	3
);
add_action(
	'before_delete_post',
	function ( $id ) {
		$p = get_post( $id );
		if ( $p && ! in_array( $p->post_type, array( 'revision', 'nav_menu_item', 'customize_changeset', 'oembed_cache' ), true ) ) {
			th119_log_activity( 'post_delete', $p->post_type . ':' . $id, $p->post_title );
		}
	}
);
add_action(
	'activated_plugin',
	function ( $plugin ) {
		th119_log_activity( 'plugin_activate', $plugin );
	}
);
add_action(
	'deactivated_plugin',
	function ( $plugin ) {
		th119_log_activity( 'plugin_deactivate', $plugin );
	}
);
add_action(
	'switch_theme',
	function ( $name ) {
		th119_log_activity( 'theme_switch', $name );
	}
);
add_action(
	'upgrader_process_complete',
	function ( $upgrader, $data ) {
		th119_log_activity( 'update', isset( $data['type'] ) ? $data['type'] : '', wp_json_encode( array_intersect_key( (array) $data, array_flip( array( 'action', 'plugins', 'themes' ) ) ) ) );
	},
	10,
	2
);
add_action(
	'user_register',
	function ( $id ) {
		th119_log_activity( 'user_create', 'user:' . $id, implode( ',', (array) get_userdata( $id )->roles ) );
	}
);
add_action(
	'set_user_role',
	function ( $id, $role, $old ) {
		th119_log_activity( 'user_role', 'user:' . $id, implode( ',', (array) $old ) . ' → ' . $role );
	},
	10,
	3
);
add_action(
	'delete_user',
	function ( $id ) {
		th119_log_activity( 'user_delete', 'user:' . $id );
	}
);
foreach ( array( 'siteurl', 'home', 'blog_public', 'users_can_register', 'default_role', 'admin_email', 'permalink_structure' ) as $th119_opt_name ) {
	add_action(
		'update_option_' . $th119_opt_name,
		function ( $old, $new, $name ) {
			th119_log_activity( 'option_change', $name, wp_json_encode( $old ) . ' → ' . wp_json_encode( $new ) );
		},
		10,
		3
	);
}
unset( $th119_opt_name );

/**
 * Trang Nhật ký & lỗi.
 */
function th119_activity_page() {
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	global $wpdb;
	$filter = isset( $_GET['act'] ) ? sanitize_key( $_GET['act'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$rows   = $filter
		? $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . th119_table( 'activity' ) . ' WHERE action LIKE %s ORDER BY id DESC LIMIT 300', $wpdb->esc_like( $filter ) . '%' ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		: $wpdb->get_results( 'SELECT * FROM ' . th119_table( 'activity' ) . ' ORDER BY id DESC LIMIT 300' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	echo '<div class="wrap th119-wrap"><h1>Nhật ký & lỗi</h1>';

	$errors = array_reverse( (array) get_option( 'th119_php_errors', array() ) );
	echo '<div class="th119-card"><h2>Lỗi PHP nghiêm trọng gần đây</h2>';
	if ( ! $errors ) {
		echo '<p>Chưa ghi nhận lỗi nghiêm trọng.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>Thời gian</th><th>Lỗi</th><th>File</th><th>Trang</th></tr></thead><tbody>';
		foreach ( array_slice( $errors, 0, 20 ) as $e ) {
			printf( '<tr><td>%s</td><td>%s</td><td><code>%s</code></td><td>%s</td></tr>', esc_html( $e['time'] ), esc_html( $e['msg'] ), esc_html( $e['file'] ), esc_html( $e['url'] ) );
		}
		echo '</tbody></table>';
		printf( '<p><a class="button" href="%s">Xoá danh sách lỗi</a></p>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=th119_clear_errors' ), 'th119_clear_errors' ) ) );
	}
	$log = WP_CONTENT_DIR . '/debug.log';
	if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ) {
		$log = WP_DEBUG_LOG;
	}
	if ( is_readable( $log ) && filesize( $log ) > 0 ) {
		$fh   = fopen( $log, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$size = filesize( $log );
		fseek( $fh, max( 0, $size - 20000 ) );
		$tail = fread( $fh, 20000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		echo '<h3>Cuối file debug.log (' . esc_html( size_format( $size ) ) . ')</h3><pre class="th119-pre">' . esc_html( str_replace( ABSPATH, '', (string) $tail ) ) . '</pre>';
		if ( 0 === strpos( realpath( $log ), realpath( WP_CONTENT_DIR ) ) ) {
			echo '<p class="th119-warn">File debug.log nằm trong wp-content và có thể bị tải về công khai. Nên đặt <code>define( \'WP_DEBUG_LOG\', \'/đường-dẫn-ngoài-public/debug.log\' );</code> hoặc chặn truy cập file .log trên máy chủ.</p>';
		}
	} else {
		echo '<p class="description">Muốn ghi mọi cảnh báo PHP: thêm vào wp-config.php <code>define( \'WP_DEBUG\', true ); define( \'WP_DEBUG_LOG\', \'/đường-dẫn-ngoài-public/debug.log\' ); define( \'WP_DEBUG_DISPLAY\', false );</code></p>';
	}
	echo '</div>';

	echo '<div class="th119-card"><h2>Nhật ký hoạt động (giữ 180 ngày)</h2><p>Lọc: ';
	foreach ( array( '' => 'Tất cả', 'login' => 'Đăng nhập', 'post' => 'Nội dung', 'plugin' => 'Plugin', 'user' => 'Người dùng', 'option' => 'Cài đặt WP', 'settings' => 'Cài đặt 119', 'ticket' => 'Yêu cầu', 'warranty' => 'Bảo hành', 'ai' => 'AI' ) as $k => $label ) {
		printf( '<a href="%s" class="%s">%s</a> ', esc_url( admin_url( 'admin.php?page=th119-activity' . ( $k ? '&act=' . $k : '' ) ) ), $filter === $k ? 'button button-primary' : 'button', esc_html( $label ) );
	}
	echo '</p><table class="widefat striped"><thead><tr><th>Thời gian</th><th>Người dùng</th><th>Hành động</th><th>Đối tượng</th><th>Chi tiết</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="5">Chưa có dữ liệu.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$u = $r->user_id ? get_userdata( $r->user_id ) : null;
		printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', esc_html( th119_date( $r->created_at, true ) ), esc_html( $u ? $u->user_login : '—' ), esc_html( $r->action ), esc_html( $r->object ), esc_html( mb_substr( (string) $r->detail, 0, 300 ) ) );
	}
	echo '</tbody></table></div></div>';
}

/**
 * Xoá danh sách lỗi PHP.
 */
function th119_clear_errors() {
	check_admin_referer( 'th119_clear_errors' );
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	delete_option( 'th119_php_errors' );
	wp_safe_redirect( admin_url( 'admin.php?page=th119-activity' ) );
	exit;
}
add_action( 'admin_post_th119_clear_errors', 'th119_clear_errors' );
