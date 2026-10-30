<?php
/**
 * Dải tiêu đề màu đầu trang lớp / môn / bài học.
 *
 * Tham số ($args): color, crumbs (mảng [tên, link]), meta, title, lead, after (HTML đã an toàn).
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

$hv_a = wp_parse_args( $args, array( 'color' => '#7b4fd6', 'crumbs' => array(), 'meta' => '', 'title' => '', 'lead' => '', 'after' => '' ) );
?>
<header class="hv-pagehead" style="--c:<?php echo esc_attr( $hv_a['color'] ); ?>">
	<div class="hv-wrap">
		<nav class="hv-crumbs" aria-label="Đường dẫn">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
			<?php foreach ( $hv_a['crumbs'] as $hv_c ) : ?>
				› <?php echo $hv_c[1] ? '<a href="' . esc_url( $hv_c[1] ) . '">' . esc_html( $hv_c[0] ) . '</a>' : esc_html( $hv_c[0] ); ?>
			<?php endforeach; ?>
		</nav>
		<?php if ( $hv_a['meta'] ) : ?>
			<div class="hv-crumbs"><strong><?php echo esc_html( $hv_a['meta'] ); ?></strong></div>
		<?php endif; ?>
		<h1><?php echo esc_html( $hv_a['title'] ); ?></h1>
		<?php if ( $hv_a['lead'] ) : ?>
			<p><?php echo esc_html( $hv_a['lead'] ); ?></p>
		<?php endif; ?>
		<?php echo $hv_a['after']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML dựng sẵn đã escape. ?>
	</div>
</header>
