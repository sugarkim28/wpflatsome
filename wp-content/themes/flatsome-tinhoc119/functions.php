<?php
/**
 * Flatsome Tin Học 119 – theme con.
 * Giao diện + template. Dữ liệu và chức năng (form, bảo hành, AI, SEO) nằm trong plugin tinhoc119-core
 * nên đổi theme không mất dữ liệu.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

define( 'TH119T_VERSION', '1.0.0' );
define( 'TH119T_DIR', get_stylesheet_directory() );
define( 'TH119T_URI', get_stylesheet_directory_uri() );

require_once TH119T_DIR . '/inc/helpers.php';
require_once TH119T_DIR . '/inc/header-footer.php';
require_once TH119T_DIR . '/inc/content.php';
require_once TH119T_DIR . '/inc/home.php';
require_once TH119T_DIR . '/inc/setup.php';

/**
 * Hỗ trợ của theme.
 */
function th119t_setup() {
	// Báo cho plugin biết theme có hiển thị FAQ trên trang → plugin được phép xuất FAQPage.
	add_theme_support( 'th119-faq' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'th119-card', 640, 360, true );
}
add_action( 'after_setup_theme', 'th119t_setup', 20 );

/**
 * CSS/JS.
 */
function th119t_assets() {
	wp_enqueue_style( 'th119t-font', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	if ( defined( 'TH119_URL' ) ) {
		wp_enqueue_style( 'th119-front', TH119_URL . 'assets/css/front.css', array(), defined( 'TH119_VERSION' ) ? TH119_VERSION : TH119T_VERSION );
	}
	$css = TH119T_DIR . '/assets/css/theme.css';
	wp_enqueue_style( 'th119t-main', TH119T_URI . '/assets/css/theme.css', array( 'flatsome-main' ), TH119T_VERSION . '.' . filemtime( $css ) );
	$js = TH119T_DIR . '/assets/js/theme.js';
	wp_enqueue_script( 'th119t-main', TH119T_URI . '/assets/js/theme.js', array(), TH119T_VERSION . '.' . filemtime( $js ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	if ( function_exists( 'th119_enqueue_front' ) && ( is_singular() || is_front_page() ) ) {
		th119_enqueue_front();
	}
}
add_action( 'wp_enqueue_scripts', 'th119t_assets', 120 );

/**
 * Kết nối sớm tới máy chủ phông chữ.
 *
 * @param array  $urls URL.
 * @param string $type Loại.
 * @return array
 */
function th119t_resource_hints( $urls, $type ) {
	if ( 'preconnect' === $type ) {
		$urls[] = array(
			'href' => 'https://fonts.gstatic.com',
			'crossorigin',
		);
		$urls[] = 'https://fonts.googleapis.com';
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'th119t_resource_hints', 10, 2 );

/**
 * Class body.
 *
 * @param array $c Class.
 * @return array
 */
function th119t_body_class( $c ) {
	$c[] = 'th119';
	return $c;
}
add_filter( 'body_class', 'th119t_body_class' );

/**
 * Logo SVG mặc định khi chưa tải logo trong Flatsome.
 *
 * @param mixed $logo Logo.
 * @return mixed
 */
function th119t_default_logo( $logo ) {
	return $logo ? $logo : TH119T_URI . '/assets/img/logo-119.svg';
}
add_filter( 'theme_mod_site_logo', 'th119t_default_logo' );

/**
 * Favicon mặc định.
 */
function th119t_favicon() {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( TH119T_URI . '/assets/img/icon-119.svg' ) . "\">\n";
	}
	echo '<meta name="theme-color" content="#0D1B2A">' . "\n";
}
add_action( 'wp_head', 'th119t_favicon', 2 );

/**
 * Nhắc kích hoạt plugin lõi.
 */
function th119t_plugin_notice() {
	if ( ! function_exists( 'th119_opt' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-error"><p><strong>Flatsome Tin Học 119:</strong> cần kích hoạt plugin <em>Tin Học 119 – Lõi hệ thống</em> (tinhoc119-core) để có form hỗ trợ, tra cứu bảo hành, SEO và các khối trang chủ.</p></div>';
	}
}
add_action( 'admin_notices', 'th119t_plugin_notice' );
