<?php
/**
 * Tài khoản phụ huynh: đăng ký, đăng nhập, một phụ huynh quản lý nhiều bé.
 * Tiến độ học (sao, XP, bài đã học) của từng bé lưu trên máy chủ để học trên nhiều thiết bị.
 * Khách chưa đăng nhập vẫn học bình thường, tiến độ lưu trong trình duyệt như trước.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

const HV_KIDS_META = 'hv_kids';
const HV_KIDS_MAX  = 8;

/**
 * Nhân vật bé có thể chọn (trùng danh sách trong hocvui.js).
 *
 * @return array
 */
function hv_avatars() {
	return array( '🦉', '🐯', '🐼', '🦊', '🐸', '🐵', '🦄', '🐙', '🐶', '🐱', '🐰', '🐲' );
}

/**
 * Vai trò "Phụ huynh Học Vui": chỉ có quyền đọc, không vào trang quản trị.
 */
function hv_parent_role() {
	if ( ! get_role( 'hv_parent' ) ) {
		add_role( 'hv_parent', 'Phụ huynh Học Vui', array( 'read' => true ) );
	}
}
add_action( 'init', 'hv_parent_role' );

/**
 * Người dùng hiện tại chỉ là phụ huynh (không phải quản trị/biên tập).
 *
 * @return bool
 */
function hv_is_parent_only() {
	return is_user_logged_in() && ! current_user_can( 'edit_posts' );
}

/**
 * URL trang Tài khoản phụ huynh.
 *
 * @param array $args Tham số thêm.
 * @return string
 */
function hv_account_url( $args = array() ) {
	$url = hv_page_url( 'tai-khoan' );
	$url = $url ? $url : home_url( '/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

/* ---------- Dữ liệu các bé ---------- */

/**
 * Danh sách bé của một phụ huynh.
 *
 * @param int $uid Người dùng (0 = hiện tại).
 * @return array
 */
function hv_get_kids( $uid = 0 ) {
	$uid  = $uid ? $uid : get_current_user_id();
	$kids = $uid ? get_user_meta( $uid, HV_KIDS_META, true ) : array();
	return is_array( $kids ) ? array_values( $kids ) : array();
}

/**
 * Lưu danh sách bé.
 *
 * @param array $kids Danh sách.
 * @param int   $uid  Người dùng (0 = hiện tại).
 */
function hv_save_kids( $kids, $uid = 0 ) {
	update_user_meta( $uid ? $uid : get_current_user_id(), HV_KIDS_META, array_values( $kids ) );
}

/**
 * Vị trí bé trong danh sách theo mã.
 *
 * @param array  $kids Danh sách.
 * @param string $id   Mã bé.
 * @return int -1 nếu không có.
 */
function hv_kid_index( $kids, $id ) {
	foreach ( $kids as $i => $k ) {
		if ( isset( $k['id'] ) && $k['id'] === $id ) {
			return $i;
		}
	}
	return -1;
}

/**
 * Làm sạch tên, lớp, nhân vật.
 *
 * @param array $in  Dữ liệu gửi lên.
 * @param array $kid Bé hiện có.
 * @return array
 */
function hv_kid_clean( $in, $kid = array() ) {
	if ( isset( $in['name'] ) ) {
		$name = trim( mb_substr( sanitize_text_field( wp_unslash( $in['name'] ) ), 0, 24 ) );
		if ( '' !== $name ) {
			$kid['name'] = $name;
		}
	}
	if ( isset( $in['grade'] ) ) {
		$kid['grade'] = max( 1, min( 5, (int) $in['grade'] ) );
	}
	if ( isset( $in['avatar'] ) && in_array( $in['avatar'], hv_avatars(), true ) ) {
		$kid['avatar'] = $in['avatar'];
	}
	return $kid;
}

/**
 * Làm sạch tiến độ học gửi từ trình duyệt.
 *
 * @param mixed $p Dữ liệu.
 * @return array
 */
function hv_progress_clean( $p ) {
	$p   = is_array( $p ) ? $p : array();
	$num = function ( $v, $max ) {
		return max( 0, min( $max, (int) $v ) );
	};
	$day = function ( $v ) {
		return is_string( $v ) && preg_match( '/^\d{4}-\d{1,2}-\d{1,2}$/', $v ) ? $v : '';
	};
	$out = array(
		'stars'   => $num( isset( $p['stars'] ) ? $p['stars'] : 0, 1000000 ),
		'played'  => $num( isset( $p['played'] ) ? $p['played'] : 0, 1000000 ),
		'xp'      => $num( isset( $p['xp'] ) ? $p['xp'] : 0, 10000000 ),
		'streak'  => $num( isset( $p['streak'] ) ? $p['streak'] : 0, 10000 ),
		'lastDay' => $day( isset( $p['lastDay'] ) ? $p['lastDay'] : '' ),
		'today'   => array(),
		'done'    => array(),
		'exam'    => array(),
	);
	$t = isset( $p['today'] ) && is_array( $p['today'] ) ? $p['today'] : array();
	$out['today'] = array(
		'date'    => $day( isset( $t['date'] ) ? $t['date'] : '' ),
		'plays'   => $num( isset( $t['plays'] ) ? $t['plays'] : 0, 10000 ),
		'lessons' => $num( isset( $t['lessons'] ) ? $t['lessons'] : 0, 10000 ),
		'perfect' => $num( isset( $t['perfect'] ) ? $t['perfect'] : 0, 10000 ),
	);
	if ( isset( $p['done'] ) && is_array( $p['done'] ) ) {
		foreach ( array_slice( $p['done'], 0, 800, true ) as $k => $v ) {
			$k = sanitize_title( (string) $k );
			if ( '' !== $k ) {
				$out['done'][ $k ] = $num( $v, 3 );
			}
		}
	}
	// Luyện thi: điểm cao nhất (%) của từng vòng, khoá dạng g3-toan-r2.
	if ( isset( $p['exam'] ) && is_array( $p['exam'] ) ) {
		foreach ( array_slice( $p['exam'], 0, 150, true ) as $k => $v ) {
			$k = sanitize_key( (string) $k );
			if ( '' !== $k ) {
				$out['exam'][ $k ] = $num( $v, 100 );
			}
		}
	}
	return $out;
}

/**
 * Gộp tiến độ máy chủ với tiến độ gửi lên – không bao giờ làm mất sao, XP, bài đã học
 * (khi bé học trên 2 thiết bị cùng lúc).
 *
 * @param array $old Đã lưu.
 * @param array $new Mới gửi.
 * @return array
 */
function hv_progress_merge( $old, $new ) {
	$old = hv_progress_clean( $old );
	$new = hv_progress_clean( $new );
	$out = $new;
	foreach ( array( 'stars', 'played', 'xp' ) as $k ) {
		$out[ $k ] = max( $old[ $k ], $new[ $k ] );
	}
	foreach ( $old['done'] as $k => $v ) {
		$out['done'][ $k ] = max( $v, isset( $new['done'][ $k ] ) ? $new['done'][ $k ] : 0 );
	}
	foreach ( $old['exam'] as $k => $v ) {
		$out['exam'][ $k ] = max( $v, isset( $new['exam'][ $k ] ) ? $new['exam'][ $k ] : 0 );
	}
	// Ngày học gần nhất và chuỗi ngày: lấy bên mới hơn.
	if ( strtotime( $old['lastDay'] ) > strtotime( $new['lastDay'] ) ) {
		$out['lastDay'] = $old['lastDay'];
		$out['streak']  = $old['streak'];
	}
	if ( strtotime( $old['today']['date'] ) > strtotime( $new['today']['date'] ) ) {
		$out['today'] = $old['today'];
	} elseif ( $old['today']['date'] === $new['today']['date'] ) {
		foreach ( array( 'plays', 'lessons', 'perfect' ) as $k ) {
			$out['today'][ $k ] = max( $old['today'][ $k ], $new['today'][ $k ] );
		}
	}
	return $out;
}

/**
 * Dữ liệu bé gửi cho trình duyệt.
 *
 * @param array $kid Bé.
 * @return array
 */
function hv_kid_public( $kid ) {
	$p         = hv_progress_clean( isset( $kid['p'] ) ? $kid['p'] : array() );
	$p['done'] = (object) $p['done'];
	$p['exam'] = (object) $p['exam'];
	return array(
		'id'     => $kid['id'],
		'name'   => $kid['name'],
		'grade'  => (int) $kid['grade'],
		'avatar' => $kid['avatar'],
		'p'      => $p,
		't'      => isset( $kid['t'] ) ? (int) $kid['t'] : 0,
		'r'      => isset( $kid['r'] ) ? (int) $kid['r'] : 0,
	);
}

/**
 * Thêm bé mới.
 *
 * @param array $in       Tên, lớp, nhân vật.
 * @param array $progress Tiến độ chuyển từ máy đang dùng (nếu có).
 * @return array|WP_Error Bé vừa thêm.
 */
function hv_kid_add( $in, $progress = array() ) {
	$kids = hv_get_kids();
	if ( count( $kids ) >= HV_KIDS_MAX ) {
		return new WP_Error( 'hv_max', 'Mỗi tài khoản có tối đa ' . HV_KIDS_MAX . ' bé.' );
	}
	$kid = hv_kid_clean( $in, array( 'grade' => 1, 'avatar' => hv_avatars()[ count( $kids ) % 12 ] ) );
	if ( empty( $kid['name'] ) ) {
		return new WP_Error( 'hv_name', 'Bố mẹ nhập tên (hoặc tên ở nhà) của bé nhé.' );
	}
	$kid['id'] = strtolower( wp_generate_password( 8, false, false ) );
	$kid['p']  = hv_progress_clean( $progress );
	$kid['t']  = time();
	$kids[]    = $kid;
	hv_save_kids( $kids );
	return $kid;
}

/* ---------- REST API: trình duyệt đồng bộ tiến độ của bé đang học ---------- */

/**
 * Đăng ký đường dẫn REST hocvui/v1/kids.
 */
function hv_kids_rest() {
	$auth = function () {
		return is_user_logged_in();
	};
	register_rest_route(
		'hocvui/v1',
		'/kids',
		array(
			array(
				'methods'             => 'GET',
				'permission_callback' => $auth,
				'callback'            => function () {
					return array_map( 'hv_kid_public', hv_get_kids() );
				},
			),
			array(
				'methods'             => 'POST',
				'permission_callback' => $auth,
				'callback'            => function ( WP_REST_Request $r ) {
					$kid = hv_kid_add( $r->get_params(), $r->get_param( 'progress' ) );
					return is_wp_error( $kid ) ? new WP_Error( $kid->get_error_code(), $kid->get_error_message(), array( 'status' => 400 ) ) : hv_kid_public( $kid );
				},
			),
		)
	);
	register_rest_route(
		'hocvui/v1',
		'/kids/(?P<id>[a-z0-9]{4,20})',
		array(
			array(
				'methods'             => 'POST',
				'permission_callback' => $auth,
				'callback'            => function ( WP_REST_Request $r ) {
					$kids = hv_get_kids();
					$i    = hv_kid_index( $kids, $r['id'] );
					if ( $i < 0 ) {
						return new WP_Error( 'hv_nokid', 'Không tìm thấy bé.', array( 'status' => 404 ) );
					}
					$kids[ $i ] = hv_kid_clean( $r->get_params(), $kids[ $i ] );
					if ( is_array( $r->get_param( 'progress' ) ) ) {
						$kids[ $i ]['p'] = hv_progress_merge( isset( $kids[ $i ]['p'] ) ? $kids[ $i ]['p'] : array(), $r->get_param( 'progress' ) );
						$kids[ $i ]['t'] = time();
					}
					hv_save_kids( $kids );
					return hv_kid_public( $kids[ $i ] );
				},
			),
		)
	);
}
add_action( 'rest_api_init', 'hv_kids_rest' );

/**
 * Thông tin tài khoản gửi cho hocvui.js (window.hvConfig.user).
 *
 * @return array
 */
function hv_user_config() {
	$cfg = array(
		'in'      => is_user_logged_in(),
		'account' => hv_account_url(),
	);
	if ( $cfg['in'] ) {
		$cfg['rest']  = esc_url_raw( rest_url( 'hocvui/v1/kids' ) );
		$cfg['nonce'] = wp_create_nonce( 'wp_rest' );
		$cfg['kids']  = array_map( 'hv_kid_public', hv_get_kids() );
	}
	return $cfg;
}

/* ---------- Biểu mẫu: đăng ký, đăng nhập, thêm/sửa/xoá bé ---------- */

/**
 * Chuyển hướng về trang tài khoản kèm thông báo.
 *
 * @param array $args Tham số.
 */
function hv_account_go( $args = array() ) {
	wp_safe_redirect( hv_account_url( $args ) );
	exit;
}

/**
 * Xử lý biểu mẫu trên trang Tài khoản phụ huynh (POST → chuyển hướng → GET).
 */
function hv_parent_forms() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['hv_action'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- chỉ so sánh.
		return;
	}
	$action = sanitize_key( wp_unslash( $_POST['hv_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- kiểm tra nonce bên dưới với từng thao tác của người đã đăng nhập.
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- đăng nhập/đăng ký của khách không dùng nonce (giống wp-login.php) để không hỏng khi trang bị lưu cache.

	if ( 'login' === $action ) {
		$user = wp_signon(
			array(
				'user_login'    => isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '',
				'user_password' => isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- mật khẩu giữ nguyên.
				'remember'      => true,
			),
			is_ssl()
		);
		hv_account_go( is_wp_error( $user ) ? array( 'hv_err' => 'login' ) : array( 'hv_ok' => 'hello' ) );
	}

	if ( 'register' === $action ) {
		if ( ! hv_opt( 'parent_signup' ) ) {
			hv_account_go( array( 'hv_err' => 'closed' ) );
		}
		if ( ! empty( $_POST['hv_website'] ) ) { // Ô bẫy chống máy tự đăng ký.
			hv_account_go();
		}
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$lock  = 'hv_reg_' . md5( $ip );
		$tries = (int) get_transient( $lock );
		if ( $tries >= 5 ) {
			hv_account_go( array( 'hv_err' => 'many' ) );
		}
		$name  = isset( $_POST['name'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$pass  = isset( $_POST['pass'] ) ? (string) wp_unslash( $_POST['pass'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- mật khẩu giữ nguyên.
		if ( '' === $name || ! is_email( $email ) ) {
			hv_account_go( array( 'hv_err' => 'fields', 'tab' => 'dang-ky' ) );
		}
		if ( strlen( $pass ) < 6 ) {
			hv_account_go( array( 'hv_err' => 'pass', 'tab' => 'dang-ky' ) );
		}
		if ( email_exists( $email ) ) {
			hv_account_go( array( 'hv_err' => 'exists' ) );
		}
		$login = sanitize_user( strstr( $email, '@', true ), true );
		$login = $login ? $login : 'phuhuynh';
		$base  = $login;
		for ( $n = 2; username_exists( $login ); $n++ ) {
			$login = $base . $n;
		}
		$uid = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $pass,
				'display_name' => $name,
				'first_name'   => $name,
				'role'         => 'hv_parent',
			)
		);
		set_transient( $lock, $tries + 1, HOUR_IN_SECONDS );
		if ( is_wp_error( $uid ) ) {
			hv_account_go( array( 'hv_err' => 'fields', 'tab' => 'dang-ky' ) );
		}
		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid, true, is_ssl() );
		hv_account_go( array( 'hv_ok' => 'welcome' ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	// Các thao tác dưới đây cần đã đăng nhập + nonce.
	if ( ! is_user_logged_in() || ! isset( $_POST['_hvnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_hvnonce'] ) ), 'hv_parent' ) ) {
		return;
	}
	$kids = hv_get_kids();
	$id   = isset( $_POST['kid'] ) ? sanitize_key( wp_unslash( $_POST['kid'] ) ) : '';
	$i    = hv_kid_index( $kids, $id );

	if ( 'kid_add' === $action ) {
		$progress = array();
		if ( ! empty( $_POST['adopt'] ) && ! empty( $_POST['adopt_data'] ) ) {
			$progress = json_decode( wp_unslash( $_POST['adopt_data'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch trong hv_progress_clean().
		}
		$kid = hv_kid_add( wp_unslash( $_POST ), $progress );
		if ( is_wp_error( $kid ) ) {
			hv_account_go( array( 'hv_err' => $kid->get_error_code() ) );
		}
		hv_account_go( array( 'hv_ok' => 'added', 'hv_pick' => $kid['id'] ) );
	}
	if ( 'kid_edit' === $action && $i >= 0 ) {
		$kids[ $i ] = hv_kid_clean( wp_unslash( $_POST ), $kids[ $i ] );
		hv_save_kids( $kids );
		hv_account_go( array( 'hv_ok' => 'saved' ) );
	}
	if ( 'kid_reset' === $action && $i >= 0 ) {
		$kids[ $i ]['p'] = hv_progress_clean( array() );
		$kids[ $i ]['t'] = time();
		$kids[ $i ]['r'] = time(); // Báo các thiết bị khác bỏ tiến độ cũ.
		hv_save_kids( $kids );
		hv_account_go( array( 'hv_ok' => 'reset', 'hv_pick' => $id ) );
	}
	if ( 'kid_delete' === $action && $i >= 0 ) {
		array_splice( $kids, $i, 1 );
		hv_save_kids( $kids );
		hv_account_go( array( 'hv_ok' => 'deleted' ) );
	}
	if ( 'account_delete' === $action && hv_is_parent_only() ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$uid = get_current_user_id();
		wp_logout();
		wp_delete_user( $uid );
		wp_safe_redirect( add_query_arg( 'hv_ok', 'bye', hv_account_url() ) );
		exit;
	}
}
add_action( 'template_redirect', 'hv_parent_forms', 1 );

/**
 * Phụ huynh: ẩn thanh quản trị, không vào trang quản trị, đăng nhập/đăng xuất quay về trang tài khoản.
 */
function hv_parent_no_admin() {
	if ( hv_is_parent_only() && is_admin() && ! wp_doing_ajax() && ! ( isset( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'] ) ) {
		wp_safe_redirect( hv_account_url() );
		exit;
	}
}
add_action( 'admin_init', 'hv_parent_no_admin' );

/**
 * Ẩn thanh quản trị với phụ huynh.
 *
 * @param bool $show Hiện.
 * @return bool
 */
function hv_parent_admin_bar( $show ) {
	return hv_is_parent_only() ? false : $show;
}
add_filter( 'show_admin_bar', 'hv_parent_admin_bar' );

/**
 * Phụ huynh đăng nhập ở wp-login.php cũng về trang tài khoản.
 *
 * @param string           $to   URL.
 * @param string           $req  URL yêu cầu.
 * @param WP_User|WP_Error $user Người dùng.
 * @return string
 */
function hv_parent_login_redirect( $to, $req, $user ) {
	return ( $user instanceof WP_User && ! $user->has_cap( 'edit_posts' ) ) ? hv_account_url() : $to;
}
add_filter( 'login_redirect', 'hv_parent_login_redirect', 10, 3 );

/* ---------- Báo cáo học tập của bé ---------- */

/**
 * Tổng hợp tiến độ của bé theo lớp đang học: từng môn đã học bao nhiêu bài, bài cần ôn lại.
 *
 * @param array $kid Bé.
 * @return array
 */
function hv_kid_report( $kid ) {
	$p      = hv_progress_clean( isset( $kid['p'] ) ? $kid['p'] : array() );
	$done   = $p['done'];
	$lv     = 1 + (int) floor( sqrt( $p['xp'] / 40 ) );
	$titles = array( 'Tân binh', 'Nhà thám hiểm', 'Chiến binh nhí', 'Hiệp sĩ tri thức', 'Siêu sao học tập', 'Huyền thoại phá đảo' );
	$subj   = array();
	$review = array();
	$next   = null;
	$total  = 0;
	$ok     = 0;
	foreach ( hv_grade_lessons( (int) $kid['grade'] ) as $post ) {
		list( , $term ) = hv_lesson_terms( $post->ID );
		$key            = $term ? $term->slug : 'khac';
		if ( ! isset( $subj[ $key ] ) ) {
			$subj[ $key ] = array( 'term' => $term, 'done' => 0, 'total' => 0, 'stars' => 0 );
		}
		$st = isset( $done[ $post->post_name ] ) ? $done[ $post->post_name ] : 0;
		++$subj[ $key ]['total'];
		++$total;
		if ( $st > 0 ) {
			++$subj[ $key ]['done'];
			++$ok;
			$subj[ $key ]['stars'] += $st;
			if ( $st < 2 ) {
				$review[] = $post;
			}
		} elseif ( ! $next ) {
			$next = $post;
		}
	}
	$cleared = 0;
	$bosses  = 0;
	foreach ( $done as $k => $v ) {
		if ( $v > 0 ) {
			if ( 0 === strpos( $k, 'boss-' ) ) {
				++$bosses;
			} else {
				++$cleared;
			}
		}
	}
	$yesterday = gmdate( 'Y-n-j', current_time( 'timestamp' ) - DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- so ngày theo giờ địa phương như trình duyệt.
	$today     = gmdate( 'Y-n-j', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	return array(
		'p'       => $p,
		'lv'      => $lv,
		'title'   => $titles[ min( $lv - 1, count( $titles ) - 1 ) ],
		'subj'    => $subj,
		'review'  => $review,
		'next'    => $next,
		'total'   => $total,
		'ok'      => $ok,
		'cleared' => $cleared,
		'bosses'  => $bosses,
		'streak'  => in_array( $p['lastDay'], array( $today, $yesterday ), true ) ? $p['streak'] : 0,
		'today'   => $p['today']['date'] === $today ? $p['today'] : array( 'plays' => 0, 'lessons' => 0 ),
	);
}

/* ---------- [hv_parent] – trang Tài khoản phụ huynh ---------- */

/**
 * Thông báo sau khi gửi biểu mẫu.
 *
 * @return string
 */
function hv_parent_notice() {
	$msgs = array(
		'hv_ok'  => array(
			'hello'   => 'Chào mừng bố mẹ quay lại! Chọn bé để bắt đầu học nhé.',
			'welcome' => 'Tạo tài khoản thành công! Bước tiếp theo: thêm bé đầu tiên ở bên dưới.',
			'added'   => 'Đã thêm bé. Máy này đang học với tài khoản của bé.',
			'saved'   => 'Đã lưu thông tin của bé.',
			'reset'   => 'Đã xoá tiến độ để bé học lại từ đầu.',
			'deleted' => 'Đã xoá bé và toàn bộ tiến độ học của bé.',
			'bye'     => 'Đã xoá tài khoản và toàn bộ dữ liệu. Cảm ơn bố mẹ đã đồng hành cùng Học Vui!',
		),
		'hv_err' => array(
			'login'   => 'Email/tên đăng nhập hoặc mật khẩu chưa đúng. Bố mẹ thử lại hoặc bấm "Quên mật khẩu?".',
			'fields'  => 'Bố mẹ điền đủ họ tên và email hợp lệ nhé.',
			'pass'    => 'Mật khẩu cần ít nhất 6 ký tự.',
			'exists'  => 'Email này đã có tài khoản – bố mẹ đăng nhập nhé.',
			'many'    => 'Đăng ký quá nhiều lần, vui lòng thử lại sau 1 giờ.',
			'closed'  => 'Website tạm ngừng nhận đăng ký mới.',
			'hv_max'  => 'Mỗi tài khoản có tối đa ' . HV_KIDS_MAX . ' bé.',
			'hv_name' => 'Bố mẹ nhập tên (hoặc tên ở nhà) của bé nhé.',
		),
	);
	foreach ( $msgs as $type => $list ) {
		$code = isset( $_GET[ $type ] ) ? sanitize_key( wp_unslash( $_GET[ $type ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ hiển thị thông báo.
		if ( isset( $list[ $code ] ) ) {
			return '<p class="hv-alert ' . ( 'hv_ok' === $type ? 'is-ok' : 'is-err' ) . '" role="status">' . esc_html( $list[ $code ] ) . '</p>';
		}
	}
	return '';
}

/**
 * Chọn nhân vật (radio).
 *
 * @param string $cur  Đang chọn.
 * @param string $name Tên ô.
 * @return string
 */
function hv_avatar_radios( $cur, $name = 'avatar' ) {
	$h = '<fieldset class="hv-ava-pick"><legend>Nhân vật</legend>';
	foreach ( hv_avatars() as $a ) {
		$h .= '<label><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $a ) . '"' . checked( $cur, $a, false ) . '><span>' . esc_html( $a ) . '</span></label>';
	}
	return $h . '</fieldset>';
}

/**
 * Chọn lớp.
 *
 * @param int $cur Đang chọn.
 * @return string
 */
function hv_grade_select( $cur ) {
	$h = '<select name="grade">';
	foreach ( hv_grades() as $n => $g ) {
		$h .= '<option value="' . (int) $n . '"' . selected( (int) $cur, (int) $n, false ) . '>' . esc_html( $g[0] ) . '</option>';
	}
	return $h . '</select>';
}

/**
 * Trang đăng nhập/đăng ký cho khách.
 *
 * @return string
 */
function hv_parent_guest() {
	$tab = isset( $_GET['tab'] ) && 'dang-ky' === $_GET['tab'] ? 'dang-ky' : 'dang-nhap'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn tab.
	$h   = '<div class="hv-acc-guest">';
	$h  .= '<div class="hv-box hv-acc-why"><img class="hv-owl-img" src="' . esc_url( HV_URI . '/assets/img/mascot.svg' ) . '" width="110" height="110" alt="">'
		. '<h2>Tài khoản phụ huynh để làm gì?</h2><ul class="hv-checks">'
		. '<li><b>Một tài khoản, nhiều bé:</b> anh chị em mỗi bé một hồ sơ riêng, đúng lớp của mình.</li>'
		. '<li><b>Học trên mọi thiết bị:</b> sao, XP, bài đã học được lưu lại – học tiếp trên điện thoại, máy tính bảng hay máy tính.</li>'
		. '<li><b>Theo dõi việc học:</b> bé đã học bao nhiêu bài mỗi môn, bài nào nên ôn lại, học bao nhiêu ngày liền.</li>'
		. '<li><b>Miễn phí, không quảng cáo.</b> Không cần tài khoản bé vẫn học được, tiến độ chỉ lưu trên máy đang dùng.</li>'
		. '</ul></div>';
	$h .= '<div class="hv-box hv-acc-forms" data-hv-tabs>';
	$h .= '<div class="hv-tabbar" role="tablist">'
		. '<button type="button" role="tab" id="hv-acc-t1" aria-controls="hv-acc-p1" aria-selected="' . ( 'dang-nhap' === $tab ? 'true' : 'false' ) . '">Đăng nhập</button>'
		. ( hv_opt( 'parent_signup' ) ? '<button type="button" role="tab" id="hv-acc-t2" aria-controls="hv-acc-p2" aria-selected="' . ( 'dang-ky' === $tab ? 'true' : 'false' ) . '">Tạo tài khoản</button>' : '' )
		. '</div>';
	$h .= '<form class="hv-form" method="post" id="hv-acc-p1" role="tabpanel" aria-labelledby="hv-acc-t1"' . ( 'dang-nhap' === $tab ? '' : ' hidden' ) . '>'
		. '<input type="hidden" name="hv_action" value="login">'
		. '<label>Email hoặc tên đăng nhập<input type="text" name="log" autocomplete="username" required></label>'
		. '<label>Mật khẩu<input type="password" name="pwd" autocomplete="current-password" required></label>'
		. '<button class="hv-btn hv-btn-big" type="submit">Đăng nhập</button>'
		. '<p class="hv-form-note"><a href="' . esc_url( wp_lostpassword_url( hv_account_url() ) ) . '">Quên mật khẩu?</a></p></form>';
	if ( hv_opt( 'parent_signup' ) ) {
		$h .= '<form class="hv-form" method="post" id="hv-acc-p2" role="tabpanel" aria-labelledby="hv-acc-t2"' . ( 'dang-ky' === $tab ? '' : ' hidden' ) . '>'
			. '<input type="hidden" name="hv_action" value="register">'
			. '<label class="hv-hp" aria-hidden="true">Website<input type="text" name="hv_website" tabindex="-1" autocomplete="off"></label>'
			. '<label>Họ tên phụ huynh<input type="text" name="name" autocomplete="name" required maxlength="60"></label>'
			. '<label>Email<input type="email" name="email" autocomplete="email" required></label>'
			. '<label>Mật khẩu (ít nhất 6 ký tự)<input type="password" name="pass" autocomplete="new-password" minlength="6" required></label>'
			. '<label class="hv-check"><input type="checkbox" required> Tôi là phụ huynh/người giám hộ và đồng ý với <a href="' . esc_url( hv_page_url( 'dieu-khoan-su-dung' ) ) . '" target="_blank">điều khoản</a>, <a href="' . esc_url( hv_page_url( 'chinh-sach-bao-mat' ) ) . '" target="_blank">chính sách bảo mật</a>.</label>'
			. '<button class="hv-btn hv-btn-big hv-btn-accent" type="submit">Tạo tài khoản miễn phí</button></form>';
	}
	return $h . '</div></div>';
}

/**
 * Thẻ một bé: số liệu, tiến độ theo môn, bài nên ôn, sửa/xoá.
 *
 * @param array $kid Bé.
 * @return string
 */
function hv_parent_kid_card( $kid ) {
	$r     = hv_kid_report( $kid );
	$g     = hv_grades();
	$grade = isset( $g[ $kid['grade'] ] ) ? $g[ $kid['grade'] ] : reset( $g );
	$nonce = wp_nonce_field( 'hv_parent', '_hvnonce', false, false );
	$pct   = $r['total'] ? round( $r['ok'] / $r['total'] * 100 ) : 0;
	$h     = '<article class="hv-box hv-kid" data-hv-kid="' . esc_attr( $kid['id'] ) . '" style="--c:' . esc_attr( $grade[3] ) . '">';
	$h    .= '<header class="hv-kid-head"><span class="hv-kid-ava">' . esc_html( $kid['avatar'] ) . '<b>' . (int) $r['lv'] . '</b></span>'
		. '<div><h3>' . esc_html( $kid['name'] ) . '</h3><span>' . esc_html( $grade[0] . ' · Cấp ' . $r['lv'] . ' · ' . $r['title'] ) . '</span></div>'
		. '<button type="button" class="hv-btn hv-btn-accent" data-hv-pick="' . esc_attr( $kid['id'] ) . '" data-go="' . esc_url( hv_grade_link( $kid['grade'] ) ) . '">▶ Cho bé học</button></header>';
	$h    .= '<ul class="hv-kid-stats">'
		. '<li>' . hv_icon3d( 'book', 30 ) . '<b>' . (int) $r['cleared'] . '</b><span>bài đã học</span></li>'
		. '<li>' . hv_icon3d( 'star', 30 ) . '<b>' . (int) $r['p']['stars'] . '</b><span>sao</span></li>'
		. '<li>' . hv_icon3d( 'fire', 30 ) . '<b>' . (int) $r['streak'] . '</b><span>ngày liên tiếp</span></li>'
		. '<li>' . hv_icon3d( 'trophy', 30 ) . '<b>' . (int) $r['today']['lessons'] . '</b><span>bài hôm nay</span></li>'
		. '</ul>';
	$h .= '<div class="hv-kid-grade"><div class="hv-kid-bar"><span>' . esc_html( $grade[0] ) . ': ' . (int) $r['ok'] . '/' . (int) $r['total'] . ' bài</span><b style="width:' . (int) $pct . '%"></b></div>';
	if ( $r['next'] ) {
		$h .= '<p class="hv-kid-next">Bài tiếp theo: <a href="' . esc_url( get_permalink( $r['next'] ) ) . '" data-hv-pick="' . esc_attr( $kid['id'] ) . '">' . esc_html( get_the_title( $r['next'] ) ) . ' ›</a></p>';
	}
	$h .= '</div><details class="hv-kid-more"><summary>Xem tiến độ từng môn</summary><ul class="hv-kid-subj">';
	foreach ( $r['subj'] as $s ) {
		$info = hv_subject_info( $s['term'] );
		$sp   = $s['total'] ? round( $s['done'] / $s['total'] * 100 ) : 0;
		$h   .= '<li style="--c:' . esc_attr( $info[2] ) . '">' . hv_icon3d( $info[3], 28 ) . '<span>' . esc_html( $info[0] ) . '<small><b style="width:' . (int) $sp . '%"></b></small></span><em>' . (int) $s['done'] . '/' . (int) $s['total'] . '</em></li>';
	}
	$h .= '</ul>';
	if ( $r['review'] ) {
		$h .= '<p class="hv-kid-review"><b>Nên ôn lại</b> (mới đạt 1 sao): ';
		$links = array();
		foreach ( array_slice( $r['review'], 0, 6 ) as $post ) {
			$links[] = '<a href="' . esc_url( get_permalink( $post ) ) . '" data-hv-pick="' . esc_attr( $kid['id'] ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
		}
		$h .= implode( ', ', $links ) . '</p>';
	}
	$h .= '</details>';
	if ( $r['p']['exam'] ) {
		$subj   = array( 'toan' => 'Toán', 'tieng-viet' => 'Tiếng Việt', 'tieng-anh' => 'Tiếng Anh', 'tong-hop' => 'Tổng hợp' );
		$rounds = array( 'Khởi động', 'Cấp trường', 'Cấp huyện', 'Cấp tỉnh', 'Cấp quốc gia' );
		$h     .= '<details class="hv-kid-more"><summary>Kết quả luyện thi</summary><ul class="hv-kid-exam">';
		$list   = $r['p']['exam'];
		ksort( $list );
		foreach ( $list as $k => $pct ) {
			if ( ! preg_match( '/^g(\d)-([a-z-]+)-r(\d)$/', $k, $m ) || ! isset( $subj[ $m[2] ], $rounds[ $m[3] ] ) ) {
				continue;
			}
			$medal = $pct >= 90 ? '🥇' : ( $pct >= 75 ? '🥈' : ( $pct >= 60 ? '🥉' : '' ) );
			$h    .= '<li><span>' . esc_html( $subj[ $m[2] ] . ' ' . $m[1] . ' · ' . $rounds[ $m[3] ] ) . '</span><b>' . esc_html( $medal . ' ' . $pct . '%' ) . '</b></li>';
		}
		$h .= '</ul></details>';
	}
	$h .= '<details class="hv-kid-edit"><summary>Sửa thông tin</summary><form class="hv-form" method="post">' . $nonce
		. '<input type="hidden" name="hv_action" value="kid_edit"><input type="hidden" name="kid" value="' . esc_attr( $kid['id'] ) . '">'
		. '<div class="hv-form-row"><label>Tên bé<input type="text" name="name" value="' . esc_attr( $kid['name'] ) . '" maxlength="24" required></label><label>Lớp' . hv_grade_select( $kid['grade'] ) . '</label></div>'
		. hv_avatar_radios( $kid['avatar'] )
		. '<button class="hv-btn" type="submit">Lưu</button></form>'
		. '<div class="hv-kid-danger"><form method="post" data-hv-confirm="Xoá toàn bộ sao, XP và bài đã học của ' . esc_attr( $kid['name'] ) . '?">' . $nonce . '<input type="hidden" name="hv_action" value="kid_reset"><input type="hidden" name="kid" value="' . esc_attr( $kid['id'] ) . '"><button type="submit" class="hv-link-btn">Học lại từ đầu</button></form>'
		. '<form method="post" data-hv-confirm="Xoá bé ' . esc_attr( $kid['name'] ) . ' và toàn bộ tiến độ học? Không thể hoàn tác.">' . $nonce . '<input type="hidden" name="hv_action" value="kid_delete"><input type="hidden" name="kid" value="' . esc_attr( $kid['id'] ) . '"><button type="submit" class="hv-link-btn is-danger">Xoá bé này</button></form></div>'
		. '</details></article>';
	return $h;
}

/**
 * [hv_parent] – đăng nhập/đăng ký; sau khi đăng nhập: danh sách bé, báo cáo học tập, thêm bé.
 *
 * @return string
 */
function hv_sc_parent() {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // Không lưu cache trang có thông tin riêng.
	}
	$h = '<div class="hv-account">' . hv_parent_notice();
	if ( ! is_user_logged_in() ) {
		return $h . hv_parent_guest() . '</div>';
	}
	$user = wp_get_current_user();
	$kids = hv_get_kids();
	$h   .= '<div class="hv-acc-top"><div><h2>Chào ' . esc_html( $user->display_name ) . '!</h2><p>' . ( $kids ? 'Bấm <b>Cho bé học</b> để chọn bé đang dùng máy này. Tiến độ của bé tự lưu vào tài khoản.' : 'Thêm bé đầu tiên để bắt đầu nhé.' ) . '</p></div>'
		. '<a class="hv-btn hv-btn-white" href="' . esc_url( wp_logout_url( hv_account_url() ) ) . '">Đăng xuất</a></div>';
	$h   .= '<div class="hv-kids">';
	foreach ( $kids as $kid ) {
		$h .= hv_parent_kid_card( $kid );
	}
	if ( count( $kids ) < HV_KIDS_MAX ) {
		$h .= '<form class="hv-box hv-kid-add hv-form" method="post" id="them-be">' . wp_nonce_field( 'hv_parent', '_hvnonce', false, false )
			. '<input type="hidden" name="hv_action" value="kid_add"><input type="hidden" name="adopt_data" value="">'
			. '<h3>' . hv_icon3d( 'seedling', 36 ) . ' Thêm bé' . ( $kids ? '' : ' đầu tiên' ) . '</h3>'
			. '<div class="hv-form-row"><label>Tên bé (tên ở nhà cũng được)<input type="text" name="name" maxlength="24" required placeholder="VD: Bống"></label><label>Lớp' . hv_grade_select( 1 ) . '</label></div>'
			. hv_avatar_radios( hv_avatars()[ count( $kids ) % 12 ] )
			. '<label class="hv-check" data-hv-adopt hidden><input type="checkbox" name="adopt" value="1" checked> <span></span></label>'
			. '<button class="hv-btn hv-btn-big" type="submit">+ Thêm bé</button></form>';
	}
	$h .= '</div>';
	if ( hv_is_parent_only() ) {
		$h .= '<details class="hv-acc-delete"><summary>Xoá tài khoản</summary><p>Xoá tài khoản phụ huynh cùng toàn bộ hồ sơ và tiến độ học của các bé. Không thể hoàn tác.</p>'
			. '<form method="post" data-hv-confirm="Xoá vĩnh viễn tài khoản và dữ liệu của tất cả các bé?">' . wp_nonce_field( 'hv_parent', '_hvnonce', false, false )
			. '<input type="hidden" name="hv_action" value="account_delete"><button type="submit" class="hv-link-btn is-danger">Xoá tài khoản của tôi</button></form></details>';
	}
	return $h . '</div>';
}
add_shortcode( 'hv_parent', 'hv_sc_parent' );
