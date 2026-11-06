<?php
/**
 * Chuyển hướng 301 (giữ giá trị SEO của URL cũ) + ghi nhận link lỗi 404.
 * Chỉ kiểm tra khi trang trả về 404 nên không làm chậm trang bình thường.
 * Trang SEO & chuyển hướng: quản lý chuyển hướng, 404, kiểm tra sức khoẻ SEO.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Có plugin chuyển hướng khác không.
 *
 * @return string
 */
function th119_other_redirect_plugin() {
	if ( defined( 'REDIRECTION_VERSION' ) ) {
		return 'Redirection';
	}
	if ( defined( 'RANK_MATH_VERSION' ) && class_exists( '\\RankMath\\Helper' ) && method_exists( '\\RankMath\\Helper', 'is_module_active' ) && \RankMath\Helper::is_module_active( 'redirections' ) ) {
		return 'Rank Math Redirections';
	}
	return '';
}

/**
 * Chuẩn hoá đường dẫn: bỏ domain, giữ query, có dấu / đầu.
 *
 * @param string $url URL hoặc path.
 * @return string
 */
function th119_normalize_path( $url ) {
	$url  = trim( (string) $url );
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$q    = (string) wp_parse_url( $url, PHP_URL_QUERY );
	$path = '/' . ltrim( rawurldecode( $path ), '/' );
	if ( '/' !== $path ) {
		$path = untrailingslashit( $path );
	}
	return $path . ( $q ? '?' . $q : '' );
}

/**
 * Xử lý chuyển hướng & ghi 404.
 */
function th119_handle_404() {
	if ( ! is_404() || is_admin() ) {
		return;
	}
	global $wpdb;
	$req  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$path = th119_normalize_path( $req );
	if ( th119_opt( 'redirects' ) && ! th119_other_redirect_plugin() ) {
		$bare = strtok( $path, '?' );
		$row  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . th119_table( 'redirects' ) . ' WHERE source IN (%s, %s) LIMIT 1', $path, $bare ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		if ( $row ) {
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . th119_table( 'redirects' ) . ' SET hits = hits + 1, last_hit = %s WHERE id = %d', th119_now(), $row->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
			$target = 0 === strpos( $row->target, '/' ) ? home_url( $row->target ) : $row->target;
			wp_redirect( $target, in_array( (int) $row->code, array( 301, 302, 307, 308 ), true ) ? (int) $row->code : 301, 'Tin Hoc 119' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- đích do quản trị viên khai báo.
			exit;
		}
	}
	if ( th119_opt( 'log_404' ) && ! th119_is_bot() && ! preg_match( '#(wp-login|xmlrpc|\.env|\.git|wp-config|phpmyadmin|\.sql|\.zip|/cgi-bin|\.asp)#i', $path ) && strlen( $path ) < 191 ) {
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$wpdb->query( $wpdb->prepare( 'INSERT INTO ' . th119_table( 'notfound' ) . ' (url, referrer, hits, last_seen) VALUES (%s, %s, 1, %s) ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen), referrer = IF(VALUES(referrer) != \'\', VALUES(referrer), referrer)', $path, substr( $ref, 0, 255 ), th119_now() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}
}
add_action( 'template_redirect', 'th119_handle_404', 1 );

/**
 * Thêm chuyển hướng.
 */
function th119_redirect_add() {
	check_admin_referer( 'th119_redirect_add' );
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$src    = th119_normalize_path( isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '' );
	$target = isset( $_POST['target'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['target'] ) ) ) : '';
	$code   = isset( $_POST['code'] ) && in_array( (int) $_POST['code'], array( 301, 302 ), true ) ? (int) $_POST['code'] : 301;
	$target = 0 === strpos( $target, '/' ) ? $target : esc_url_raw( $target );
	$err    = '';
	if ( '/' === $src || '' === $target ) {
		$err = 'Cần nhập đường dẫn cũ và đích.';
	} elseif ( th119_normalize_path( $target ) === $src ) {
		$err = 'Đích trùng nguồn (vòng lặp).';
	}
	if ( ! $err ) {
		global $wpdb;
		$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			th119_table( 'redirects' ),
			array(
				'source'     => $src,
				'target'     => $target,
				'code'       => $code,
				'created_at' => th119_now(),
			)
		);
		$wpdb->delete( th119_table( 'notfound' ), array( 'url' => $src ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		th119_log_activity( 'redirect_add', $src, '→ ' . $target );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=th119-seo' . ( $err ? '&err=' . rawurlencode( $err ) : '&added=1' ) ) );
	exit;
}
add_action( 'admin_post_th119_redirect_add', 'th119_redirect_add' );

/**
 * Xoá chuyển hướng / dòng 404.
 */
function th119_redirect_delete() {
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$what = isset( $_GET['what'] ) && 'notfound' === $_GET['what'] ? 'notfound' : 'redirects';
	check_admin_referer( 'th119_rd_' . $what . $id );
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	global $wpdb;
	$wpdb->delete( th119_table( $what ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	th119_log_activity( 'redirect_delete', $what . '#' . $id, '' );
	wp_safe_redirect( admin_url( 'admin.php?page=th119-seo' ) );
	exit;
}
add_action( 'admin_post_th119_redirect_delete', 'th119_redirect_delete' );

/**
 * Danh sách kiểm tra sức khoẻ SEO.
 *
 * @return array [ [ok|warn|bad, nội dung], … ]
 */
function th119_seo_health() {
	global $wpdb;
	$out = array();
	$out[] = get_option( 'blog_public' ) ? array( 'ok', 'Website cho phép Google lập chỉ mục.' ) : array( 'bad', 'Đang BẬT "Ngăn chặn các công cụ tìm kiếm đánh chỉ mục" (Cài đặt → Đọc). Tắt khi website chạy chính thức.' );
	$perm  = (string) get_option( 'permalink_structure' );
	if ( '' === $perm ) {
		$out[] = array( 'bad', 'Đường dẫn đang dạng ?p=123. Không tự đổi để giữ URL cũ – nếu website mới, chọn "Tên bài viết" ở Cài đặt → Đường dẫn tĩnh.' );
	} elseif ( 0 === strpos( $perm, '/index.php' ) ) {
		$out[] = array( 'warn', 'Đường dẫn có /index.php/ (máy chủ nginx thiếu rewrite). Sửa cấu hình nginx trước, sau đó mới đổi đường dẫn và tạo chuyển hướng 301 cho URL cũ.' );
	} else {
		$out[] = array( 'ok', 'Cấu trúc đường dẫn: ' . $perm );
	}
	$out[] = is_ssl() || 0 === strpos( home_url(), 'https://' ) ? array( 'ok', 'Website dùng HTTPS.' ) : array( 'bad', 'Website chưa dùng HTTPS – cài chứng chỉ SSL ở hosting và đổi địa chỉ website sang https://.' );
	$seo   = th119_seo_plugin();
	$out[] = $seo ? array( 'ok', "Đang dùng $seo: tiêu đề, mô tả, sitemap do $seo quản lý; plugin này bổ sung LocalBusiness, Service, FAQ." ) : array( 'ok', 'Không có plugin SEO khác: plugin này xuất tiêu đề, mô tả, canonical, Open Graph, schema. Sitemap: ' . home_url( '/wp-sitemap.xml' ) );
	$out[] = th119_opt( 'logo_id' ) ? array( 'ok', 'Đã có logo PNG/JPG cho Google.' ) : array( 'warn', 'Chưa tải logo PNG/JPG (Cài đặt → Doanh nghiệp).' );
	$out[] = th119_opt( 'maps_url' ) ? array( 'ok', 'Đã liên kết Google Maps.' ) : array( 'warn', 'Chưa nhập link Google Maps / Google Business Profile.' );
	$out[] = th119_opt( 'hours_schema' ) ? array( 'ok', 'Đã khai báo giờ làm việc cho Google.' ) : array( 'warn', 'Chưa khai báo giờ làm việc cho Google (phải trùng Google Business Profile).' );
	$out[] = th119_opt( 'ward' ) ? array( 'ok', 'Địa chỉ có phường.' ) : array( 'warn', 'Địa chỉ chưa có phường – điền đúng như trên Google Business Profile.' );
	$out[] = th119_opt( 'gsc_verify' ) || $seo ? array( 'ok', 'Đã/đang xác minh Google Search Console (hoặc qua plugin SEO).' ) : array( 'warn', 'Chưa có mã xác minh Google Search Console (có thể xác minh bằng DNS thay thế).' );
	$out[] = th119_opt( 'ga4_id' ) ? array( 'ok', 'Đã gắn Google Analytics 4.' ) : array( 'warn', 'Chưa gắn Google Analytics 4 (bộ đếm nội bộ vẫn chạy).' );
	$no_thumb = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id' WHERE p.post_type = 'post' AND p.post_status = 'publish' AND m.meta_id IS NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$out[]    = $no_thumb ? array( 'warn', "$no_thumb bài đã đăng chưa có ảnh đại diện." ) : array( 'ok', 'Bài viết đều có ảnh đại diện.' );
	$empty_cat = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'category' AND description = ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$out[]     = $empty_cat ? array( 'warn', "$empty_cat chuyên mục chưa có mô tả." ) : array( 'ok', 'Chuyên mục đều có mô tả.' );
	$nf        = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . th119_table( 'notfound' ) . ' WHERE hits >= 3' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$out[]     = $nf ? array( 'warn', "$nf link lỗi 404 có ≥ 3 lượt truy cập – nên tạo chuyển hướng 301 bên dưới." ) : array( 'ok', 'Không có link lỗi 404 đáng kể.' );
	return $out;
}

/**
 * Trang SEO & chuyển hướng.
 */
function th119_seo_page() {
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	global $wpdb;
	echo '<div class="wrap th119-wrap"><h1>SEO & chuyển hướng</h1>';
	if ( isset( $_GET['err'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['err'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	echo '<div class="th119-card"><h2>Kiểm tra nhanh</h2><ul class="th119-checks">';
	foreach ( th119_seo_health() as $h ) {
		echo '<li class="' . esc_attr( $h[0] ) . '">' . ( 'ok' === $h[0] ? '✔ ' : ( 'warn' === $h[0] ? '⚠ ' : '✘ ' ) ) . esc_html( $h[1] ) . '</li>';
	}
	echo '</ul></div>';

	$other = th119_other_redirect_plugin();
	echo '<div class="th119-card"><h2>Chuyển hướng 301</h2>';
	if ( $other ) {
		echo '<p class="th119-warn">Đang dùng <strong>' . esc_html( $other ) . '</strong> – chuyển hướng của plugin này tạm tắt để tránh trùng. Quản lý chuyển hướng trong ' . esc_html( $other ) . '.</p>';
	}
	echo '<p class="description">Chỉ áp dụng khi đường dẫn cũ không còn tồn tại (trang 404). Đổi slug bài viết thì WordPress đã tự chuyển hướng.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="th119-inline">';
	wp_nonce_field( 'th119_redirect_add' );
	$prefill = isset( $_GET['src'] ) ? sanitize_text_field( wp_unslash( $_GET['src'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<input type="hidden" name="action" value="th119_redirect_add"><input type="text" name="source" placeholder="/duong-dan-cu" value="' . esc_attr( $prefill ) . '" class="regular-text" required> → <input type="text" name="target" placeholder="/duong-dan-moi/ hoặc https://…" class="regular-text" required> <select name="code"><option value="301">301 (vĩnh viễn)</option><option value="302">302 (tạm thời)</option></select> ';
	submit_button( 'Thêm', 'primary', 'submit', false );
	echo '</form>';
	$rows = $wpdb->get_results( 'SELECT * FROM ' . th119_table( 'redirects' ) . ' ORDER BY id DESC LIMIT 300' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	echo '<table class="widefat striped"><thead><tr><th>Đường dẫn cũ</th><th>Đích</th><th>Mã</th><th>Lượt</th><th>Lần cuối</th><th></th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="6">Chưa có chuyển hướng.</td></tr>';
	}
	foreach ( $rows as $r ) {
		printf( '<tr><td><code>%s</code></td><td><code>%s</code></td><td>%d</td><td>%d</td><td>%s</td><td><a class="button-link-delete" href="%s">Xoá</a></td></tr>', esc_html( $r->source ), esc_html( $r->target ), (int) $r->code, (int) $r->hits, esc_html( th119_date( $r->last_hit, true ) ), esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=th119_redirect_delete&what=redirects&id=' . $r->id ), 'th119_rd_redirects' . $r->id ) ) );
	}
	echo '</tbody></table></div>';

	$nf = $wpdb->get_results( 'SELECT * FROM ' . th119_table( 'notfound' ) . ' ORDER BY hits DESC LIMIT 100' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	echo '<div class="th119-card"><h2>Link lỗi 404 gần đây</h2><table class="widefat striped"><thead><tr><th>Đường dẫn</th><th>Lượt</th><th>Nguồn gần nhất</th><th>Lần cuối</th><th></th></tr></thead><tbody>';
	if ( ! $nf ) {
		echo '<tr><td colspan="5">Chưa ghi nhận link lỗi.</td></tr>';
	}
	foreach ( $nf as $r ) {
		printf(
			'<tr><td><code>%1$s</code></td><td>%2$d</td><td>%3$s</td><td>%4$s</td><td><a href="%5$s">Tạo chuyển hướng</a> · <a class="button-link-delete" href="%6$s">Bỏ qua</a></td></tr>',
			esc_html( $r->url ),
			(int) $r->hits,
			esc_html( $r->referrer ),
			esc_html( th119_date( $r->last_seen, true ) ),
			esc_url( admin_url( 'admin.php?page=th119-seo&src=' . rawurlencode( $r->url ) ) ),
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=th119_redirect_delete&what=notfound&id=' . $r->id ), 'th119_rd_notfound' . $r->id ) )
		);
	}
	echo '</tbody></table></div></div>';
}
