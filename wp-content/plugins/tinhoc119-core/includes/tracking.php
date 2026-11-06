<?php
/**
 * Thống kê nhẹ, ẩn danh (không cookie, không lưu IP): lượt xem trang, nguồn truy cập,
 * lượt bấm Gọi / Zalo / Gửi yêu cầu. Gộp theo ngày → 1 dòng/ngày/chỉ số, không làm chậm database.
 * Gửi bằng navigator.sendBeacon sau khi trang tải xong nên vẫn đếm đúng khi trang được cache.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tăng 1 chỉ số trong ngày.
 *
 * @param string $metric Chỉ số.
 * @param string $dim    Chiều (trang, nguồn…).
 */
function th119_stat_hit( $metric, $dim = '' ) {
	global $wpdb;
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			'INSERT INTO ' . th119_table( 'stats' ) . ' (day, metric, dim, hits) VALUES (%s, %s, %s, 1) ON DUPLICATE KEY UPDATE hits = hits + 1', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			current_time( 'Y-m-d' ),
			substr( $metric, 0, 32 ),
			substr( $dim, 0, 191 )
		)
	);
}

/**
 * Phân loại nguồn truy cập theo referrer.
 *
 * @param string $ref URL nguồn.
 * @return string
 */
function th119_traffic_source( $ref ) {
	$host = strtolower( (string) wp_parse_url( (string) $ref, PHP_URL_HOST ) );
	if ( '' === $host ) {
		return 'Trực tiếp';
	}
	if ( wp_parse_url( home_url(), PHP_URL_HOST ) === $host ) {
		return 'Nội bộ';
	}
	$map = array(
		'google.'    => 'Google',
		'bing.'      => 'Bing',
		'coccoc.'    => 'Cốc Cốc',
		'facebook.'  => 'Facebook',
		'fb.'        => 'Facebook',
		'zalo.'      => 'Zalo',
		'youtube.'   => 'YouTube',
		'tiktok.'    => 'TikTok',
		'chatgpt.'   => 'ChatGPT',
		'openai.'    => 'ChatGPT',
		'perplexity' => 'Perplexity',
	);
	foreach ( $map as $needle => $label ) {
		if ( false !== strpos( $host, $needle ) ) {
			return $label;
		}
	}
	return 'Khác: ' . preg_replace( '/^www\./', '', $host );
}

/**
 * Có phải bot không (theo User-Agent).
 *
 * @return bool
 */
function th119_is_bot() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
	return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|preview|lighthouse|headless|curl|wget|python|monitor|facebookexternalhit|zalo.*link/', $ua );
}

/**
 * Đăng ký REST endpoint /wp-json/th119/v1/hit.
 */
function th119_register_tracking_route() {
	register_rest_route(
		'th119/v1',
		'/hit',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'th119_rest_hit',
		)
	);
}
add_action( 'rest_api_init', 'th119_register_tracking_route' );

/**
 * Ghi nhận 1 sự kiện.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function th119_rest_hit( $req ) {
	if ( ! th119_opt( 'track_views' ) || th119_is_bot() || is_user_logged_in() ) {
		return new WP_REST_Response( null, 204 );
	}
	$body = json_decode( (string) $req->get_body(), true );
	if ( ! is_array( $body ) ) {
		$body = $req->get_params();
	}
	$m    = isset( $body['m'] ) ? sanitize_key( $body['m'] ) : '';
	$path = isset( $body['p'] ) ? '/' . ltrim( (string) wp_parse_url( esc_url_raw( home_url( (string) $body['p'] ) ), PHP_URL_PATH ), '/' ) : '/';
	$ref  = isset( $body['r'] ) ? esc_url_raw( (string) $body['r'] ) : '';
	if ( ! in_array( $m, array( 'view', 'call', 'zalo', 'cta', 'maps', 'messenger' ), true ) ) {
		return new WP_REST_Response( null, 400 );
	}
	if ( 'view' === $m ) {
		th119_stat_hit( 'view', '' );
		th119_stat_hit( 'page', $path );
		$src = th119_traffic_source( $ref );
		if ( 'Nội bộ' !== $src ) {
			th119_stat_hit( 'source', $src );
		}
	} else {
		th119_stat_hit( $m, '' );
		th119_stat_hit( $m . '_page', $path );
	}
	return new WP_REST_Response( null, 204 );
}

/**
 * Tổng chỉ số trong khoảng ngày.
 *
 * @param string $metric Chỉ số.
 * @param int    $days   Số ngày gần nhất.
 * @return int
 */
function th119_stat_total( $metric, $days = 30 ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(hits) FROM ' . th119_table( 'stats' ) . " WHERE metric = %s AND dim = '' AND day >= %s", $metric, gmdate( 'Y-m-d', current_time( 'timestamp' ) - ( $days - 1 ) * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DateTime.CurrentTimeTimestamp.Requested
}

/**
 * Top theo chiều.
 *
 * @param string $metric Chỉ số.
 * @param int    $days   Số ngày.
 * @param int    $limit  Số dòng.
 * @return array
 */
function th119_stat_top( $metric, $days = 30, $limit = 10 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT dim, SUM(hits) hits FROM ' . th119_table( 'stats' ) . ' WHERE metric = %s AND day >= %s GROUP BY dim ORDER BY hits DESC LIMIT %d', $metric, gmdate( 'Y-m-d', current_time( 'timestamp' ) - ( $days - 1 ) * DAY_IN_SECONDS ), $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DateTime.CurrentTimeTimestamp.Requested
}

/**
 * Chuỗi theo ngày.
 *
 * @param string $metric Chỉ số.
 * @param int    $days   Số ngày.
 * @return array [Y-m-d => hits].
 */
function th119_stat_series( $metric, $days = 30 ) {
	global $wpdb;
	$from = current_time( 'timestamp' ) - ( $days - 1 ) * DAY_IN_SECONDS; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT day, hits FROM ' . th119_table( 'stats' ) . " WHERE metric = %s AND dim = '' AND day >= %s", $metric, gmdate( 'Y-m-d', $from ) ), OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$out  = array();
	for ( $i = 0; $i < $days; $i++ ) {
		$d         = gmdate( 'Y-m-d', $from + $i * DAY_IN_SECONDS );
		$out[ $d ] = isset( $rows[ $d ] ) ? (int) $rows[ $d ]->hits : 0;
	}
	return $out;
}

/**
 * Nạp script đo lường + GA4 ở mọi trang.
 */
function th119_front_tracking() {
	if ( is_admin() ) {
		return;
	}
	wp_enqueue_script( 'th119-track', TH119_URL . 'assets/js/track.js', array(), TH119_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script(
		'th119-track',
		'TH119Track',
		array(
			'url'   => esc_url_raw( rest_url( 'th119/v1/hit' ) ),
			'views' => th119_opt( 'track_views' ) && ! is_user_logged_in() && ! is_404() ? 1 : 0,
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'ga'    => th119_opt( 'ga4_id' ) ? 1 : 0,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'th119_front_tracking' );

/**
 * Mã GA4 (nếu khai báo) + thẻ xác minh Search Console / Bing.
 */
function th119_head_integrations() {
	$gsc  = trim( (string) th119_opt( 'gsc_verify' ) );
	$bing = trim( (string) th119_opt( 'bing_verify' ) );
	if ( $gsc && is_front_page() ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">' . "\n";
	}
	if ( $bing && is_front_page() ) {
		echo '<meta name="msvalidate.01" content="' . esc_attr( $bing ) . '">' . "\n";
	}
	$ga = strtoupper( trim( (string) th119_opt( 'ga4_id' ) ) );
	if ( $ga && preg_match( '/^G-[A-Z0-9]{4,15}$/', $ga ) && ! is_user_logged_in() ) {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		printf(
			"<script async src=\"https://www.googletagmanager.com/gtag/js?id=%1\$s\"></script>\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','%1\$s');</script>\n",
			esc_attr( $ga )
		);
		// phpcs:enable
	}
}
add_action( 'wp_head', 'th119_head_integrations', 3 );
