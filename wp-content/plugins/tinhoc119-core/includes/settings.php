<?php
/**
 * Trang Cài đặt (Tin Học 119 → Cài đặt) và menu quản trị.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các nhóm ô cài đặt: tab => [ key => [ nhãn, kiểu, ghi chú ] ].
 *
 * @return array
 */
function th119_settings_fields() {
	return array(
		'business' => array(
			'_title'          => 'Doanh nghiệp (NAP)',
			'_intro'          => 'Tên – Địa chỉ – Số điện thoại phải giống hệt Google Business Profile, Google Maps, Facebook. Đây là dữ liệu dùng cho schema LocalBusiness, header, footer, nút Gọi/Zalo.',
			'brand'           => array( 'Tên thương hiệu', 'text', '' ),
			'legal_name'      => array( 'Tên pháp lý (nếu có đăng ký kinh doanh)', 'text', 'Để trống nếu chưa có' ),
			'slogan'          => array( 'Slogan', 'text', '' ),
			'hotline'         => array( 'Hotline', 'text', 'Lấy theo số đang dùng trên website. Chỉ sửa khi doanh nghiệp đổi số.' ),
			'zalo'            => array( 'Zalo (số hoặc link zalo.me)', 'text', '' ),
			'email'           => array( 'Email liên hệ', 'email', '' ),
			'contact_person'  => array( 'Người liên hệ', 'text', '' ),
			'street'          => array( 'Số nhà, đường / hẻm', 'text', '' ),
			'ward'            => array( 'Phường', 'text', 'Theo địa giới hành chính đang dùng trên Google Business Profile' ),
			'district'        => array( 'Quận / khu vực', 'text', '' ),
			'city'            => array( 'Tỉnh / thành phố', 'text', '' ),
			'postal_code'     => array( 'Mã bưu chính', 'text', '' ),
			'lat'             => array( 'Vĩ độ (latitude)', 'text', 'Lấy từ Google Maps: chuột phải vào vị trí cửa hàng → bấm vào toạ độ để sao chép' ),
			'lng'             => array( 'Kinh độ (longitude)', 'text', '' ),
			'maps_url'        => array( 'Link Google Maps của doanh nghiệp', 'url', 'Link "Chia sẻ" trên Google Maps / Google Business Profile' ),
			'maps_embed'      => array( 'Link nhúng bản đồ (src của iframe)', 'url', 'Google Maps → Chia sẻ → Nhúng bản đồ → chỉ dán phần https://www.google.com/maps/embed?...' ),
			'hours'           => array( 'Giờ làm việc (hiển thị)', 'text', 'Ví dụ: 8:00 – 20:00, Thứ 2 – Chủ nhật' ),
			'hours_schema'    => array( 'Giờ làm việc cho Google (mỗi dòng: Ngày | Mở | Đóng)', 'textarea', "Ví dụ:\nMo,Tu,We,Th,Fr,Sa | 08:00 | 20:00\nSu | 08:00 | 12:00\nĐể trống nếu chưa chắc chắn – không khai báo sai giờ." ),
			'area_served'     => array( 'Khu vực phục vụ', 'text', 'Ngăn bằng dấu phẩy' ),
			'founded'         => array( 'Năm thành lập', 'text', 'Để trống nếu không chắc chắn' ),
			'facebook'        => array( 'Facebook', 'url', '' ),
			'youtube'         => array( 'YouTube', 'url', '' ),
			'logo_id'         => array( 'Logo (PNG/JPG cho Google & mạng xã hội)', 'image', 'Google và Facebook không đọc logo SVG' ),
			'notify_emails'   => array( 'Email nhận thông báo yêu cầu hỗ trợ', 'text', 'Nhiều email ngăn bằng dấu phẩy. Trống = email quản trị.' ),
			'warranty_default' => array( 'Thời hạn bảo hành mặc định (tháng)', 'number', '' ),
		),
		'integrations' => array(
			'_title'           => 'Google & chống spam',
			'_intro'           => 'Chỉ dán mã đo lường / xác minh. Không dán mật khẩu hay API key Google ở đây.',
			'ga4_id'           => array( 'Google Analytics 4 – Measurement ID', 'text', 'Dạng G-XXXXXXXXXX. Để trống nếu đã gắn GA4 bằng plugin khác (tránh đếm trùng).' ),
			'gsc_verify'       => array( 'Google Search Console – mã xác minh (thẻ meta)', 'text', 'Chỉ phần content="..."' ),
			'bing_verify'      => array( 'Bing Webmaster – mã xác minh (msvalidate.01)', 'text', '' ),
			'turnstile_site'   => array( 'Cloudflare Turnstile – Site key (tuỳ chọn)', 'text', 'Chống spam form mạnh hơn. Để trống = dùng honeypot + giới hạn tần suất.' ),
			'turnstile_secret' => array( 'Cloudflare Turnstile – Secret key', 'secret', '' ),
			'track_views'      => array( 'Đếm lượt xem trang, nguồn truy cập, lượt bấm Gọi/Zalo (ẩn danh, không cookie)', 'checkbox', '' ),
		),
		'seo' => array(
			'_title'         => 'SEO',
			'_intro'         => 'Nếu đang dùng Rank Math hoặc Yoast, plugin tự nhường tiêu đề, mô tả, Open Graph, sitemap cho plugin đó và chỉ bổ sung schema LocalBusiness / Service / FAQ / TechArticle còn thiếu.',
			'seo_enabled'    => array( 'Xuất meta title, description, canonical, Open Graph (khi không có plugin SEO)', 'checkbox', '' ),
			'schema_enabled' => array( 'Xuất dữ liệu có cấu trúc (schema)', 'checkbox', '' ),
			'home_title'     => array( 'Tiêu đề trang chủ', 'text', '50–60 ký tự' ),
			'home_desc'      => array( 'Mô tả trang chủ', 'textarea', '140–155 ký tự. Trống = tự tạo từ tên, dịch vụ, khu vực và hotline.' ),
			'og_image_id'    => array( 'Ảnh chia sẻ mặc định (1200×630)', 'image', '' ),
			'redirects'      => array( 'Bật chuyển hướng 301 (Tin Học 119 → SEO & chuyển hướng)', 'checkbox', 'Tự tắt nếu đã dùng plugin Redirection / Rank Math Redirections.' ),
			'log_404'        => array( 'Ghi nhận link lỗi 404 để tạo chuyển hướng', 'checkbox', '' ),
			'webp'           => array( 'Tạo ảnh thu nhỏ dạng WebP cho ảnh JPEG tải lên mới', 'checkbox', 'Ảnh gốc giữ nguyên; chỉ áp dụng khi máy chủ hỗ trợ WebP. Ảnh cũ không bị đổi.' ),
		),
		'security' => array(
			'_title'           => 'Bảo mật',
			'_intro'           => 'Các tuỳ chọn nhẹ, không thay thế tường lửa. Nếu đang dùng Wordfence / iThemes / All In One WP Security, giới hạn đăng nhập của plugin này tự tắt để tránh trùng.',
			'disable_xmlrpc'   => array( 'Tắt XML-RPC (chặn dò mật khẩu hàng loạt)', 'checkbox', 'Tắt mục này nếu dùng app WordPress di động hoặc Jetpack.' ),
			'hide_version'     => array( 'Ẩn phiên bản WordPress', 'checkbox', '' ),
			'security_headers' => array( 'Thêm header bảo mật (X-Frame-Options, nosniff, Referrer-Policy)', 'checkbox', '' ),
			'login_limit'      => array( 'Khoá đăng nhập 15 phút sau 5 lần sai', 'checkbox', '' ),
			'block_user_enum'  => array( 'Chặn dò tên đăng nhập (?author=1, REST users)', 'checkbox', '' ),
			'delete_on_uninstall' => array( 'Xoá toàn bộ dữ liệu plugin khi gỡ plugin', 'checkbox', 'Mặc định KHÔNG xoá – yêu cầu hỗ trợ và phiếu bảo hành được giữ lại.' ),
		),
	);
}

/**
 * Menu quản trị.
 */
function th119_admin_menu() {
	add_menu_page( 'Tin Học 119', 'Tin Học 119', 'th119_manage_tickets', 'th119', 'th119_dashboard_page', 'dashicons-desktop', 3 );
	add_submenu_page( 'th119', 'Tổng quan', 'Tổng quan', 'th119_manage_tickets', 'th119', 'th119_dashboard_page' );
	add_submenu_page( 'th119', 'Yêu cầu hỗ trợ', 'Yêu cầu hỗ trợ' . th119_new_ticket_bubble(), 'th119_manage_tickets', 'th119-tickets', 'th119_tickets_page' );
	add_submenu_page( 'th119', 'Bảo hành', 'Bảo hành', 'th119_manage_warranty', 'th119-warranty', 'th119_warranty_page' );
	add_submenu_page( 'th119', 'AI nội dung', 'AI nội dung', 'th119_use_ai', 'th119-ai', 'th119_ai_page' );
	add_submenu_page( 'th119', 'SEO & chuyển hướng', 'SEO & chuyển hướng', 'th119_manage_settings', 'th119-seo', 'th119_seo_page' );
	add_submenu_page( 'th119', 'Nhật ký & lỗi', 'Nhật ký & lỗi', 'th119_manage_settings', 'th119-activity', 'th119_activity_page' );
	add_submenu_page( 'th119', 'Công cụ', 'Công cụ', 'th119_manage_settings', 'th119-tools', 'th119_tools_page' );
	add_submenu_page( 'th119', 'Cài đặt', 'Cài đặt', 'th119_manage_settings', 'th119-settings', 'th119_settings_page' );
}
add_action( 'admin_menu', 'th119_admin_menu' );

/**
 * Số yêu cầu mới (bong bóng đỏ trên menu).
 *
 * @return string
 */
function th119_new_ticket_bubble() {
	global $wpdb;
	$n = (int) wp_cache_get( 'th119_new_tickets' );
	if ( ! $n ) {
		$n = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . th119_table( 'tickets' ) . " WHERE status = 'new'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		wp_cache_set( 'th119_new_tickets', $n, '', 60 );
	}
	return $n ? ' <span class="awaiting-mod">' . $n . '</span>' : '';
}

/**
 * CSS/JS trang quản trị.
 *
 * @param string $hook Trang.
 */
function th119_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'th119' ) && ! in_array( $hook, array( 'post.php', 'post-new.php', 'term.php', 'edit-tags.php' ), true ) ) {
		return;
	}
	wp_enqueue_style( 'th119-admin', TH119_URL . 'assets/css/admin.css', array(), TH119_VERSION );
	wp_enqueue_script( 'th119-admin', TH119_URL . 'assets/js/admin.js', array( 'jquery' ), TH119_VERSION, true );
	wp_localize_script(
		'th119-admin',
		'TH119Admin',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'th119_admin' ),
		)
	);
	if ( false !== strpos( $hook, 'th119-settings' ) ) {
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'th119_admin_assets' );

/**
 * Trang cài đặt.
 */
function th119_settings_page() {
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$fields = th119_settings_fields();
	$tab    = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'business'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $fields[ $tab ] ) && 'ai' !== $tab ) {
		$tab = 'business';
	}
	echo '<div class="wrap th119-wrap"><h1>Cài đặt Tin Học 119</h1>';
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cài đặt.</p></div>';
	}
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $fields as $k => $group ) {
		printf( '<a class="nav-tab %s" href="%s">%s</a>', $k === $tab ? 'nav-tab-active' : '', esc_url( admin_url( 'admin.php?page=th119-settings&tab=' . $k ) ), esc_html( $group['_title'] ) );
	}
	printf( '<a class="nav-tab %s" href="%s">AI</a>', 'ai' === $tab ? 'nav-tab-active' : '', esc_url( admin_url( 'admin.php?page=th119-settings&tab=ai' ) ) );
	echo '</nav>';

	if ( 'ai' === $tab ) {
		th119_ai_settings_form();
		echo '</div>';
		return;
	}

	$group = $fields[ $tab ];
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'th119_settings' );
	echo '<input type="hidden" name="action" value="th119_save_settings"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
	echo '<p class="description">' . esc_html( $group['_intro'] ) . '</p><table class="form-table" role="presentation">';
	foreach ( $group as $key => $f ) {
		if ( '_' === $key[0] ) {
			continue;
		}
		th119_render_field( 'th119[' . $key . ']', 'th119-' . $key, $f, 'secret' === $f[1] ? th119_decrypt( th119_opt( $key ) ) : th119_opt( $key ) );
	}
	echo '</table>';
	submit_button( 'Lưu cài đặt' );
	echo '</form></div>';
}

/**
 * In một ô nhập.
 *
 * @param string $name  Name.
 * @param string $id    ID.
 * @param array  $f     [nhãn, kiểu, ghi chú].
 * @param mixed  $value Giá trị.
 */
function th119_render_field( $name, $id, $f, $value ) {
	list( $label, $type, $help ) = $f;
	echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
	switch ( $type ) {
		case 'textarea':
			printf( '<textarea id="%1$s" name="%2$s" rows="4" class="large-text">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
			break;
		case 'checkbox':
			printf( '<input type="hidden" name="%2$s" value="0"><label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> Bật</label>', esc_attr( $id ), esc_attr( $name ), checked( (int) $value, 1, false ) );
			break;
		case 'secret':
			printf( '<input type="password" id="%1$s" name="%2$s" value="" class="regular-text" autocomplete="new-password" placeholder="%3$s">', esc_attr( $id ), esc_attr( $name ), esc_attr( th119_mask_secret( $value ) ) );
			echo '<p class="description">Để trống = giữ giá trị đã lưu. Nhập dấu - để xoá.</p>';
			break;
		case 'image':
			$url = $value ? wp_get_attachment_image_url( (int) $value, 'medium' ) : '';
			printf( '<input type="hidden" id="%1$s" name="%2$s" value="%3$s"><img class="th119-img-preview" src="%4$s" style="max-width:220px;%5$s"><br><button type="button" class="button th119-pick-image" data-target="%1$s">Chọn ảnh</button> <button type="button" class="button-link th119-clear-image" data-target="%1$s">Bỏ ảnh</button>', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_url( $url ), $url ? '' : 'display:none' );
			break;
		case 'number':
			printf( '<input type="number" min="0" id="%1$s" name="%2$s" value="%3$s" class="small-text">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
			break;
		default:
			printf( '<input type="%4$s" id="%1$s" name="%2$s" value="%3$s" class="regular-text">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_attr( in_array( $type, array( 'email', 'url' ), true ) ? $type : 'text' ) );
	}
	if ( $help && 'secret' !== $type ) {
		echo '<p class="description">' . esc_html( $help ) . '</p>';
	}
	echo '</td></tr>';
}

/**
 * Làm sạch 1 giá trị theo kiểu.
 *
 * @param string $type Kiểu.
 * @param mixed  $v    Giá trị.
 * @return mixed
 */
function th119_sanitize_by_type( $type, $v ) {
	switch ( $type ) {
		case 'textarea':
			return sanitize_textarea_field( $v );
		case 'checkbox':
			return $v ? 1 : 0;
		case 'email':
			return sanitize_email( $v );
		case 'url':
			return esc_url_raw( trim( $v ) );
		case 'image':
		case 'number':
			return absint( $v );
		default:
			return sanitize_text_field( $v );
	}
}

/**
 * Lưu cài đặt.
 */
function th119_save_settings() {
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'th119_settings' );
	$fields = th119_settings_fields();
	$tab    = isset( $_POST['tab'] ) ? sanitize_key( $_POST['tab'] ) : '';
	if ( ! isset( $fields[ $tab ] ) ) {
		wp_die( 'Tab không hợp lệ.' );
	}
	$in    = isset( $_POST['th119'] ) && is_array( $_POST['th119'] ) ? wp_unslash( $_POST['th119'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch theo kiểu ô.
	$saved = (array) get_option( 'th119_settings', array() );
	$old   = $saved;
	foreach ( $fields[ $tab ] as $key => $f ) {
		if ( '_' === $key[0] || ! array_key_exists( $key, $in ) ) {
			continue;
		}
		$v = is_string( $in[ $key ] ) ? $in[ $key ] : '';
		if ( 'secret' === $f[1] ) {
			if ( '' === $v ) {
				continue;
			}
			$saved[ $key ] = '-' === $v ? '' : th119_encrypt( sanitize_text_field( $v ) );
			continue;
		}
		if ( 'hours_schema' === $key ) {
			$saved[ $key ] = th119_sanitize_hours( $v );
			continue;
		}
		$saved[ $key ] = th119_sanitize_by_type( $f[1], $v );
	}
	update_option( 'th119_settings', $saved );
	if ( isset( $old['hotline'] ) && isset( $saved['hotline'] ) && $old['hotline'] !== $saved['hotline'] ) {
		th119_log_activity( 'settings_hotline', 'hotline', 'Đổi hotline từ ' . $old['hotline'] . ' sang ' . $saved['hotline'] );
	}
	th119_log_activity( 'settings_saved', 'tab:' . $tab, '' );
	wp_safe_redirect( admin_url( 'admin.php?page=th119-settings&tab=' . $tab . '&saved=1' ) );
	exit;
}
add_action( 'admin_post_th119_save_settings', 'th119_save_settings' );

/**
 * Chuẩn hoá giờ làm việc dạng "Mo,Tu | 08:00 | 20:00".
 *
 * @param string $text Văn bản.
 * @return string
 */
function th119_sanitize_hours( $text ) {
	$ok   = array( 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su' );
	$out  = array();
	$rows = preg_split( '/\r\n|\r|\n/', (string) $text );
	foreach ( $rows as $row ) {
		$p = array_map( 'trim', explode( '|', $row ) );
		if ( 3 !== count( $p ) ) {
			continue;
		}
		$days = array_values( array_intersect( array_map( 'trim', explode( ',', $p[0] ) ), $ok ) );
		if ( ! $days || ! preg_match( '/^\d{2}:\d{2}$/', $p[1] ) || ! preg_match( '/^\d{2}:\d{2}$/', $p[2] ) ) {
			continue;
		}
		$out[] = implode( ',', $days ) . ' | ' . $p[1] . ' | ' . $p[2];
	}
	return implode( "\n", $out );
}
