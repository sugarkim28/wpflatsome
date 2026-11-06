<?php
/**
 * Môi trường giả lập tối thiểu của WordPress để kiểm thử các hàm thuần (không cần cơ sở dữ liệu).
 * Chạy: php tests/run.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'MONTH_IN_SECONDS', 30 * 86400 );
define( 'WP_CONTENT_DIR', sys_get_temp_dir() );

$GLOBALS['th_hooks']   = array();
$GLOBALS['th_options'] = array();

function add_action( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['th_hooks'][ $h ][] = $cb; return true; }
function add_filter( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['th_hooks'][ $h ][] = $cb; return true; }
function remove_action() { return true; }
function remove_filter() { return true; }
function add_shortcode( $t, $cb ) { $GLOBALS['th_hooks'][ 'shortcode:' . $t ] = $cb; }
function register_activation_hook( $f, $cb ) {}
function register_deactivation_hook( $f, $cb ) {}
function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }
function plugin_dir_url( $f ) { return 'https://example.test/wp-content/plugins/' . basename( dirname( $f ) ) . '/'; }
function get_stylesheet_directory() { return dirname( __DIR__ ) . '/wp-content/themes/flatsome-tinhoc119'; }
function get_stylesheet_directory_uri() { return 'https://example.test/wp-content/themes/flatsome-tinhoc119'; }
function apply_filters( $h, $v ) { return $v; }
function do_action() {}
function wp_salt( $s = 'auth' ) { return 'test-salt-' . $s; }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['th_options'] ) ? $GLOBALS['th_options'][ $k ] : $d; }
function update_option( $k, $v ) { $GLOBALS['th_options'][ $k ] = $v; return true; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function untrailingslashit( $s ) { return rtrim( $s, '/\\' ); }
function trailingslashit( $s ) { return untrailingslashit( $s ) . '/'; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $s ) ) ); }
function strip_shortcodes( $s ) { return preg_replace( '/\[\/?[a-z0-9_]+[^\]]*\]/i', '', (string) $s ); }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( (string) $s ) ) ); }
function sanitize_textarea_field( $s ) { return trim( wp_strip_all_tags( (string) $s ) ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return esc_html( $s ); }
function esc_url( $s ) { return (string) $s; }
function esc_url_raw( $s ) { return (string) $s; }
function absint( $n ) { return abs( (int) $n ); }
function wp_unslash( $v ) { return $v; }
function remove_accents( $s ) {
	$s = Normalizer::normalize( $s, Normalizer::FORM_D );
	$s = preg_replace( '/\p{Mn}/u', '', $s );
	return str_replace( array( 'đ', 'Đ' ), array( 'd', 'D' ), $s );
}
function sanitize_title( $s ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( remove_accents( $s ) ) ), '-' ); }
function is_admin() { return false; }
function __( $s ) { return $s; }

function th_assert( $cond, $msg ) {
	$GLOBALS['th_tests'] = ( $GLOBALS['th_tests'] ?? 0 ) + 1;
	if ( ! $cond ) {
		$GLOBALS['th_fail'][] = $msg;
		echo "  ✗ $msg\n";
	}
}
function th_eq( $actual, $expected, $msg ) {
	th_assert( $actual === $expected, $msg . ' – nhận ' . var_export( $actual, true ) . ', mong đợi ' . var_export( $expected, true ) );
}
function wp_parse_args( $a, $d = array() ) { return array_merge( $d, is_array( $a ) ? $a : array() ); }
