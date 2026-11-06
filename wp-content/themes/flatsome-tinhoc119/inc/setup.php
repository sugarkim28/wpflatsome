<?php
/**
 * Giao diện → Thiết lập Tin Học 119: áp dụng header/footer/màu cho Flatsome bằng một nút bấm.
 * Trước khi ghi, toàn bộ theme_mods hiện tại được sao lưu (option th119t_mods_backup) và có nút khôi phục.
 * Không xoá bài, trang, menu; footer UX Block chỉ tạo khi chưa có.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các theme mod của Flatsome được áp dụng.
 *
 * @return array
 */
function th119t_flatsome_mods() {
	return array(
		// Màu & chữ.
		'color_primary'                => '#0057D9',
		'color_secondary'              => '#E31E24',
		'color_alert'                  => '#E31E24',
		'color_links'                  => '#0057D9',
		'color_links_hover'            => '#003f9e',
		'type_texts'                   => '#1f2937',
		'type_headings_color'          => '#0D1B2A',
		'disable_fonts'                => 1, // Dùng Be Vietnam Pro của theme con, không tải phông Google mặc định của Flatsome.
		// Thanh trên cùng.
		'topbar_show'                  => 1,
		'topbar_bg'                    => '#0D1B2A',
		'topbar_color'                 => 'dark',
		'topbar_elements_left'         => array( 'html' ),
		'topbar_elements_center'       => array(),
		'topbar_elements_right'        => array( 'html-2' ),
		'topbar_left'                  => '[th119t_topbar side="left"]',
		'topbar_right'                 => '[th119t_topbar side="right"]',
		// Hàng chính: logo trái, cụm hành động phải.
		'header_elements_left'         => array(),
		'header_elements_right'        => array( 'html-3' ),
		'top_right_text'               => '[th119t_header_actions]',
		'header_height'                => 84,
		'header_bg'                    => '#ffffff',
		'header_color'                 => 'light',
		'logo_width'                   => 200,
		'header_sticky'                => 1,
		'sticky_style'                 => 'jump',
		// Thanh menu.
		'header_elements_bottom_left'  => array( 'nav' ),
		'header_elements_bottom_center' => array(),
		'header_elements_bottom_right' => array(),
		'header_bottom_height'         => 50,
		'nav_position_bg'              => '#0057D9',
		'nav_position_color'           => 'dark',
		'nav_uppercase_bottom'         => 0,
		'type_nav_bottom_color'        => '#ffffff',
		'type_nav_bottom_color_hover'  => '#ffffff',
		// Di động.
		'header_height_mobile'         => 64,
		'header_mobile_elements_left'  => array( 'menu-icon' ),
		'header_mobile_elements_right' => array( 'html-3' ),
		'header_mobile_elements_bottom' => array(),
		'mobile_sidebar'               => array( 'search', 'nav' ),
		// Footer.
		'footer_1_columns'             => 0,
		'footer_2_columns'             => 0,
		'footer_left_text'             => '',
		'footer_right_text'            => '',
		'back_to_top'                  => 1,
		'back_to_top_position'         => 'left',
		'default_title'                => 0,
		'blog_style'                   => 'normal',
	);
}

/**
 * Menu trang thiết lập.
 */
function th119t_setup_menu() {
	add_theme_page( 'Thiết lập Tin Học 119', 'Thiết lập Tin Học 119', 'edit_theme_options', 'th119t-setup', 'th119t_setup_page' );
}
add_action( 'admin_menu', 'th119t_setup_menu' );

/**
 * Trang thiết lập.
 */
function th119t_setup_page() {
	$backup = get_option( 'th119t_mods_backup' );
	$msg    = isset( $_GET['th119t'] ) ? sanitize_key( $_GET['th119t'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap">
		<h1>Thiết lập giao diện Tin Học 119</h1>
		<?php if ( 'applied' === $msg ) : ?>
			<div class="notice notice-success"><p>Đã áp dụng. Mở trang chủ để kiểm tra; có thể tinh chỉnh tiếp trong Giao diện → Tuỳ biến (Header Builder).</p></div>
		<?php elseif ( 'restored' === $msg ) : ?>
			<div class="notice notice-success"><p>Đã khôi phục cấu hình giao diện trước đó.</p></div>
		<?php endif; ?>
		<?php if ( ! function_exists( 'th119_opt' ) ) : ?>
			<div class="notice notice-error"><p>Chưa kích hoạt plugin <strong>Tin Học 119 – Lõi hệ thống</strong>. Header, footer và trang chủ cần plugin này.</p></div>
		<?php endif; ?>
		<p>Nút dưới đây cấu hình Flatsome: thanh trên cùng (slogan, địa chỉ, giờ làm việc), header (logo, tìm kiếm, hotline, Zalo, nút Gửi yêu cầu), thanh menu màu Tech Blue, footer 5 cột, màu thương hiệu.</p>
		<ul style="list-style:disc;margin-left:20px">
			<li>Không xoá hay sửa bài viết, trang, menu.</li>
			<li>Footer dùng UX Block <code>footer-tin-hoc-119</code> (chỉ tạo nếu chưa có).</li>
			<li>Cấu hình hiện tại được sao lưu trước, có thể khôi phục bằng một nút.</li>
			<li>Logo: nếu chưa tải logo trong Tuỳ biến → Header → Logo, theme dùng logo SVG Tin Học 119 có sẵn.</li>
		</ul>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px">
			<?php wp_nonce_field( 'th119t_setup' ); ?>
			<input type="hidden" name="action" value="th119t_apply">
			<?php submit_button( 'Áp dụng giao diện Tin Học 119', 'primary', 'submit', false ); ?>
		</form>
		<?php if ( $backup ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
				<?php wp_nonce_field( 'th119t_setup' ); ?>
				<input type="hidden" name="action" value="th119t_restore">
				<?php submit_button( 'Khôi phục cấu hình trước (' . esc_html( mysql2date( 'd/m/Y H:i', $backup['time'] ) ) . ')', 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
		<h2>Shortcode dùng trong UX Builder / Header Builder</h2>
		<table class="widefat striped" style="max-width:900px">
			<tbody>
				<tr><td><code>[th119t_topbar side="left|right"]</code></td><td>Nội dung thanh trên cùng</td></tr>
				<tr><td><code>[th119t_header_actions]</code></td><td>Tìm kiếm, hotline, Zalo, nút Gửi yêu cầu hỗ trợ</td></tr>
				<tr><td><code>[th119t_footer]</code></td><td>Footer đầy đủ</td></tr>
				<tr><td><code>[th119_home]</code></td><td>Toàn bộ trang chủ</td></tr>
				<tr><td><code>[th119_cta show="call,zalo,request,booking"]</code></td><td>Nút Gọi / Zalo / Gửi yêu cầu / Đặt lịch</td></tr>
				<tr><td><code>[th119_support_form]</code>, <code>[th119_lookup]</code>, <code>[th119_contact]</code>, <code>[th119_map]</code></td><td>Form, tra cứu bảo hành, thông tin liên hệ, bản đồ</td></tr>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Áp dụng cấu hình.
 */
function th119t_apply() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'th119t_setup' );
	update_option(
		'th119t_mods_backup',
		array(
			'time' => current_time( 'mysql' ),
			'mods' => get_theme_mods(),
		),
		false
	);

	foreach ( th119t_flatsome_mods() as $k => $v ) {
		set_theme_mod( $k, $v );
	}

	// Footer UX Block (post type "blocks" của Flatsome).
	if ( post_type_exists( 'blocks' ) ) {
		$block = get_page_by_path( 'footer-tin-hoc-119', OBJECT, 'blocks' );
		if ( ! $block ) {
			$id = wp_insert_post(
				array(
					'post_type'    => 'blocks',
					'post_status'  => 'publish',
					'post_name'    => 'footer-tin-hoc-119',
					'post_title'   => 'Footer Tin Học 119',
					'post_content' => '[th119t_footer]',
				)
			);
			$block = $id && ! is_wp_error( $id ) ? get_post( $id ) : null;
		}
		if ( $block ) {
			set_theme_mod( 'footer_block', $block->post_name );
		}
	}

	// Menu: dùng menu đã gán ở theme trước nếu theme con chưa có (WordPress tự chuyển khi đổi theme, đây là dự phòng).
	$locs = get_theme_mod( 'nav_menu_locations' );
	if ( empty( $locs['primary'] ) ) {
		$old = get_option( 'theme_mods_flatsome-dichvu' );
		if ( ! empty( $old['nav_menu_locations'] ) ) {
			set_theme_mod( 'nav_menu_locations', array_merge( (array) $locs, $old['nav_menu_locations'] ) );
		}
	}
	if ( function_exists( 'th119_log_activity' ) ) {
		th119_log_activity( 'theme_setup', 'flatsome-tinhoc119', 'Áp dụng giao diện, đã sao lưu cấu hình cũ' );
	}
	wp_safe_redirect( admin_url( 'themes.php?page=th119t-setup&th119t=applied' ) );
	exit;
}
add_action( 'admin_post_th119t_apply', 'th119t_apply' );

/**
 * Khôi phục cấu hình đã sao lưu.
 */
function th119t_restore() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'th119t_setup' );
	$backup = get_option( 'th119t_mods_backup' );
	if ( $backup && isset( $backup['mods'] ) && is_array( $backup['mods'] ) ) {
		update_option( 'theme_mods_' . get_option( 'stylesheet' ), $backup['mods'] );
		if ( function_exists( 'th119_log_activity' ) ) {
			th119_log_activity( 'theme_restore', 'flatsome-tinhoc119', 'Khôi phục cấu hình ' . $backup['time'] );
		}
	}
	wp_safe_redirect( admin_url( 'themes.php?page=th119t-setup&th119t=restored' ) );
	exit;
}
add_action( 'admin_post_th119t_restore', 'th119t_restore' );

/**
 * Lần đầu kích hoạt theme: nhắc vào trang thiết lập.
 */
function th119t_after_switch() {
	set_transient( 'th119t_setup_notice', 1, DAY_IN_SECONDS );
}
add_action( 'after_switch_theme', 'th119t_after_switch' );

/**
 * Thông báo nhắc thiết lập.
 */
function th119t_setup_notice() {
	if ( get_transient( 'th119t_setup_notice' ) && current_user_can( 'edit_theme_options' ) && ! get_option( 'th119t_mods_backup' ) ) {
		echo '<div class="notice notice-info"><p>Theme Tin Học 119 đã bật. <a href="' . esc_url( admin_url( 'themes.php?page=th119t-setup' ) ) . '">Áp dụng header/footer/màu thương hiệu</a> (có sao lưu và khôi phục).</p></div>';
	}
}
add_action( 'admin_notices', 'th119t_setup_notice' );
