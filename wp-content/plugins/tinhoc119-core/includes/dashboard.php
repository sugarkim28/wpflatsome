<?php
/**
 * Tổng quan quản trị: số liệu THẬT từ database (không hiển thị số giả khi chưa có dữ liệu).
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ô số liệu.
 *
 * @param string $label Nhãn.
 * @param int    $value Giá trị.
 * @param string $note  Ghi chú.
 * @param string $url   Link.
 * @return string
 */
function th119_stat_tile( $label, $value, $note = '', $url = '' ) {
	$v = null === $value ? '<span class="th119-nodata">Chưa có dữ liệu</span>' : esc_html( number_format_i18n( (int) $value ) );
	$h = '<div class="th119-tile"><span class="th119-tile__label">' . esc_html( $label ) . '</span><strong class="th119-tile__value">' . $v . '</strong>' . ( $note ? '<span class="th119-tile__note">' . esc_html( $note ) . '</span>' : '' ) . '</div>';
	return $url ? '<a href="' . esc_url( $url ) . '" class="th119-tile-link">' . $h . '</a>' : $h;
}

/**
 * Trang Tổng quan.
 */
function th119_dashboard_page() {
	if ( ! current_user_can( 'th119_manage_tickets' ) ) {
		wp_die( 'Không có quyền.' );
	}
	global $wpdb;
	$t       = th119_table( 'tickets' );
	$since   = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 30 * DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	$counts  = $wpdb->get_results( "SELECT status, COUNT(*) n FROM $t GROUP BY status", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$new30   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE created_at >= %s", $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$open    = 0;
	foreach ( array( 'new', 'checking', 'processing', 'waiting' ) as $s ) {
		$open += isset( $counts[ $s ] ) ? (int) $counts[ $s ]->n : 0;
	}
	$has_stats = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . th119_table( 'stats' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$val       = function ( $metric ) use ( $has_stats ) {
		return $has_stats ? th119_stat_total( $metric, 30 ) : null;
	};
	$warranty_active = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . th119_table( 'warranty' ) . ' WHERE warranty_until >= %s', current_time( 'Y-m-d' ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery

	echo '<div class="wrap th119-wrap"><h1>Tin Học 119 – Tổng quan</h1>';
	if ( ! th119_opt( 'track_views' ) ) {
		echo '<div class="notice notice-info"><p>Bộ đếm lượt xem/nhấn nút đang tắt (Cài đặt → Google & chống spam).</p></div>';
	}
	echo '<h2>Yêu cầu hỗ trợ</h2><div class="th119-tiles">';
	echo th119_stat_tile( 'Mới tiếp nhận', isset( $counts['new'] ) ? (int) $counts['new']->n : 0, 'cần liên hệ khách', admin_url( 'admin.php?page=th119-tickets&status=new' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo th119_stat_tile( 'Đang xử lý', $open, 'chưa hoàn thành', admin_url( 'admin.php?page=th119-tickets' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo th119_stat_tile( 'Yêu cầu 30 ngày', $new30, '', admin_url( 'admin.php?page=th119-tickets' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo th119_stat_tile( 'Phiếu còn bảo hành', $warranty_active, '', admin_url( 'admin.php?page=th119-warranty' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';

	if ( current_user_can( 'th119_view_reports' ) ) {
		echo '<h2>Truy cập & tương tác (30 ngày)</h2><p class="description">Bộ đếm ẩn danh của website (không tính người đăng nhập, bot). Số liệu đầy đủ hơn xem trong Google Analytics 4 / Search Console.</p><div class="th119-tiles">';
		echo th119_stat_tile( 'Lượt xem trang', $val( 'view' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo th119_stat_tile( 'Lượt bấm Gọi', $val( 'call' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo th119_stat_tile( 'Lượt bấm Zalo', $val( 'zalo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo th119_stat_tile( 'Bấm Gửi yêu cầu / Đặt lịch', $val( 'cta' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo th119_stat_tile( 'Bấm Chỉ đường', $val( 'maps' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo th119_stat_tile( 'Lượt tra cứu bảo hành', $val( 'lookup' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div><div class="th119-cols">';

		echo '<div class="th119-card"><h3>Nguồn khách truy cập</h3>';
		th119_rank_list( th119_stat_top( 'source', 30, 10 ) );
		echo '<h3>Nguồn của yêu cầu hỗ trợ</h3>';
		th119_rank_list( $wpdb->get_results( $wpdb->prepare( "SELECT channel dim, COUNT(*) hits FROM $t WHERE created_at >= %s AND channel != '' GROUP BY channel ORDER BY hits DESC LIMIT 10", $since ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		echo '</div><div class="th119-card"><h3>Trang được xem nhiều</h3>';
		th119_rank_list( th119_stat_top( 'page', 30, 10 ), true );
		echo '<h3>Trang có nhiều lượt bấm Gọi / Zalo</h3>';
		$cz = array_merge( th119_stat_top( 'call_page', 30, 5 ), th119_stat_top( 'zalo_page', 30, 5 ) );
		th119_rank_list( $cz, true );
		echo '</div></div>';
	}

	$pc  = wp_count_posts( 'post' );
	$ai  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_th_ai_generated' AND p.post_status IN ('draft','pending')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$old = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_modified < %s", gmdate( 'Y-m-d', current_time( 'timestamp' ) - 365 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DateTime.CurrentTimeTimestamp.Requested
	echo '<h2>Tình trạng nội dung</h2><div class="th119-tiles">';
	echo th119_stat_tile( 'Bài đã đăng', (int) $pc->publish, '', admin_url( 'edit.php' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo th119_stat_tile( 'Bản nháp', (int) $pc->draft, '', admin_url( 'edit.php?post_status=draft' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo th119_stat_tile( 'Chờ duyệt', (int) $pc->pending, '', admin_url( 'edit.php?post_status=pending' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo th119_stat_tile( 'Đã lên lịch', (int) $pc->future, '', admin_url( 'edit.php?post_status=future' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo th119_stat_tile( 'Bản nháp AI chờ xử lý', $ai, '', admin_url( 'admin.php?page=th119-ai&tab=review' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo th119_stat_tile( 'Bài > 12 tháng chưa cập nhật', $old, 'nên rà soát lại', admin_url( 'edit.php?orderby=modified&order=asc' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';

	echo '<h2>Lối tắt</h2><p>';
	$links = array(
		'post-new.php'                     => 'Viết bài kiến thức',
		'admin.php?page=th119-ai&tab=write' => 'Soạn bài với AI',
		'admin.php?page=th119-warranty&edit=0' => 'Tạo phiếu bảo hành',
		'edit.php?post_type=dich_vu'       => 'Dịch vụ',
		'edit.php?post_type=th_project'    => 'Dự án',
		'edit.php?post_type=th_review'     => 'Đánh giá khách hàng',
		'admin.php?page=th119-seo'         => 'Kiểm tra SEO',
		'admin.php?page=th119-settings'    => 'Cài đặt',
	);
	foreach ( $links as $u => $l ) {
		echo '<a class="button" href="' . esc_url( admin_url( $u ) ) . '">' . esc_html( $l ) . '</a> ';
	}
	echo '</p></div>';
}

/**
 * Danh sách xếp hạng.
 *
 * @param array $rows  Dòng (dim, hits).
 * @param bool  $paths Là đường dẫn.
 */
function th119_rank_list( $rows, $paths = false ) {
	if ( ! $rows ) {
		echo '<p class="th119-nodata">Chưa có dữ liệu.</p>';
		return;
	}
	echo '<ol class="th119-rank">';
	foreach ( $rows as $r ) {
		$label = '' === (string) $r->dim ? '(không rõ)' : $r->dim;
		echo '<li><span>' . ( $paths ? '<a href="' . esc_url( home_url( $label ) ) . '" target="_blank">' . esc_html( $label ) . '</a>' : esc_html( $label ) ) . '</span><strong>' . (int) $r->hits . '</strong></li>';
	}
	echo '</ol>';
}

/**
 * Widget trên Bảng tin WordPress.
 */
function th119_wp_dashboard_widget() {
	if ( current_user_can( 'th119_manage_tickets' ) ) {
		wp_add_dashboard_widget(
			'th119_widget',
			'Tin Học 119 – Yêu cầu hỗ trợ mới',
			function () {
				global $wpdb;
				$rows = $wpdb->get_results( 'SELECT id, code, name, device, created_at FROM ' . th119_table( 'tickets' ) . " WHERE status = 'new' ORDER BY id DESC LIMIT 5" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
				if ( ! $rows ) {
					echo '<p>Không có yêu cầu mới.</p>';
				}
				echo '<ul>';
				foreach ( $rows as $r ) {
					printf( '<li><a href="%s"><strong>%s</strong></a> – %s – %s <em>%s</em></li>', esc_url( admin_url( 'admin.php?page=th119-tickets&view=' . $r->id ) ), esc_html( $r->code ), esc_html( $r->name ), esc_html( $r->device ), esc_html( th119_date( $r->created_at, true ) ) );
				}
				echo '</ul><p><a href="' . esc_url( admin_url( 'admin.php?page=th119' ) ) . '">Xem tổng quan →</a></p>';
			}
		);
	}
}
add_action( 'wp_dashboard_setup', 'th119_wp_dashboard_widget' );
