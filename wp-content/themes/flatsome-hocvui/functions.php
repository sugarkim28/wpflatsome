<?php
/**
 * Flatsome Học Vui – website giáo dục vui nhộn cho học sinh Tiểu học (lớp 1–5).
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

define( 'HV_VERSION', '0.6.0' );
define( 'HV_DIR', get_stylesheet_directory() );
define( 'HV_URI', get_stylesheet_directory_uri() );

require_once HV_DIR . '/inc/options.php';
require_once HV_DIR . '/inc/content.php';
require_once HV_DIR . '/inc/games.php';
require_once HV_DIR . '/inc/home.php';
require_once HV_DIR . '/inc/layout.php';
require_once HV_DIR . '/inc/seo.php';
require_once HV_DIR . '/inc/demo.php';

/**
 * CSS/JS giao diện + trò chơi.
 */
function hv_enqueue_assets() {
	// Baloo 2 (tiêu đề tròn, vui) + Nunito (chữ thường dễ đọc) – cả hai có bộ ký tự tiếng Việt.
	wp_enqueue_style( 'hv-fonts', 'https://fonts.googleapis.com/css2?family=Baloo+2:wght@700;800&family=Nunito:wght@500;700;800;900&display=swap', array(), null );
	wp_enqueue_style( 'hv-main', HV_URI . '/assets/css/hocvui.css', array( 'flatsome-main' ), HV_VERSION . '.' . filemtime( HV_DIR . '/assets/css/hocvui.css' ) );
	wp_add_inline_style(
		'hv-main',
		sprintf(
			':root{--hv-primary:%s;--hv-accent:%s;--hv-mascot:url("%s");}',
			esc_html( hv_opt( 'color_primary' ) ),
			esc_html( hv_opt( 'color_accent' ) ),
			esc_url( HV_URI . '/assets/img/mascot.svg' )
		)
	);
	wp_enqueue_script( 'hv-main', HV_URI . '/assets/js/hocvui.js', array(), HV_VERSION . '.' . filemtime( HV_DIR . '/assets/js/hocvui.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'hv-main', 'window.hvConfig=' . wp_json_encode( array( 'img' => HV_URI . '/assets/img/3d/', 'gamesUrl' => hv_games_url(), 'profileUrl' => get_page_by_path( 'goc-cua-em' ) ? get_permalink( get_page_by_path( 'goc-cua-em' ) ) : '' ) ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'hv_enqueue_assets', 120 );

/**
 * Kết nối sớm tới máy chủ phông chữ.
 *
 * @param array  $urls          URL.
 * @param string $relation_type Loại.
 * @return array
 */
function hv_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'hv_resource_hints', 10, 2 );

/**
 * Class cho body.
 *
 * @param array $classes Class.
 * @return array
 */
function hv_body_class( $classes ) {
	$classes[] = 'hv';
	return $classes;
}
add_filter( 'body_class', 'hv_body_class' );

/**
 * Ảnh logo theo kiểu chọn ở Tuỳ biến → Học Vui → Logo trên header.
 *
 * @param string $style cobrand | hocvui | tinhoc119 | white.
 * @return string URL.
 */
function hv_logo_url( $style = '' ) {
	$style = $style ? $style : hv_opt( 'logo_style' );
	$files = array(
		'cobrand'   => 'hocvui-ngang-tinhoc119.png',
		'hocvui'    => 'hocvui-ngang.png',
		'tinhoc119' => 'tinhoc119-ngang.webp',
		'white'     => 'hocvui-ngang-trang.png',
	);
	return HV_URI . '/assets/img/brand/' . ( isset( $files[ $style ] ) ? $files[ $style ] : $files['cobrand'] );
}

/**
 * Logo mặc định khi chưa tải logo ở Tuỳ biến → Header → Logo.
 *
 * @param mixed $logo Logo đã lưu.
 * @return mixed
 */
function hv_default_logo( $logo ) {
	// Flatsome trả về logo mặc định của nó khi chưa tải logo → thay bằng logo Học Vui.
	if ( ! $logo || false !== strpos( (string) $logo, '/flatsome/assets/img/logo' ) ) {
		return hv_logo_url();
	}
	return $logo;
}
add_filter( 'theme_mod_site_logo', 'hv_default_logo' );

/**
 * Favicon, biểu tượng iPhone/Android mặc định (khi chưa đặt Biểu tượng trang web trong WordPress).
 */
function hv_default_favicon() {
	if ( ! has_site_icon() ) {
		$img = HV_URI . '/assets/img/brand/';
		echo '<link rel="icon" type="image/png" sizes="64x64" href="' . esc_url( $img . 'favicon-64.png' ) . "\">\n";
		echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url( $img . 'hocvui-icon-192.png' ) . "\">\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( $img . 'hocvui-icon-192.png' ) . "\">\n";
	}
	echo '<meta name="theme-color" content="#2ea3f2">' . "\n";
}
add_action( 'wp_head', 'hv_default_favicon', 2 );

/**
 * Kích hoạt theme: đăng ký đường dẫn bài học, lớp, môn.
 */
function hv_after_switch() {
	hv_register_content();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'hv_after_switch' );

/**
 * Tối ưu tốc độ: tắt script đổi emoji thành ảnh của WordPress (tải thêm JS và ảnh từ máy chủ ngoài,
 * làm vỡ biểu tượng khi mạng chậm) – trình duyệt hiện đại tự hiển thị emoji.
 */
function hv_disable_wp_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'hv_disable_wp_emoji' );

/**
 * Tải trước ảnh Cú Bông ở banner trang chủ (cải thiện LCP).
 */
function hv_preload_hero() {
	if ( is_front_page() ) {
		echo '<link rel="preload" as="image" href="' . esc_url( HV_URI . '/assets/img/mascot.svg' ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hv_preload_hero', 3 );

/**
 * Nhắc bật đường dẫn đẹp: dạng ?page_id=… / ?lop=lop-1 không tốt cho Google và khó chia sẻ.
 */
function hv_permalink_notice() {
	if ( get_option( 'permalink_structure' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Học Vui:</strong> website đang dùng đường dẫn dạng <code>?page_id=69</code>, <code>?lop=lop-1</code> – không tốt cho Google và khó nhớ. Vào <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">Cài đặt → Đường dẫn tĩnh</a>, chọn <strong>Tên bài viết</strong> rồi Lưu để có đường dẫn đẹp như <code>/chuong-trinh/lop-1/</code>, <code>/bai-hoc/cac-so-tu-0-den-10/</code>. Máy chủ nginx cần có quy tắc <code>try_files $uri $uri/ /index.php?$args;</code>.</p></div>';
}
add_action( 'admin_notices', 'hv_permalink_notice' );
