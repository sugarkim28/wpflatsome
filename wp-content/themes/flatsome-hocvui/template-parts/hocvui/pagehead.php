<?php
/**
 * Dải tiêu đề đầu trang (đảo, môn, màn chơi, trang thường).
 *
 * Tham số ($args): color, crumbs (mảng [tên, link]), meta, title, lead, art (emoji hoặc HTML đã an toàn), after (HTML đã an toàn).
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

$hv_a = wp_parse_args( $args, array( 'color' => '#7c4dff', 'crumbs' => array(), 'meta' => '', 'title' => '', 'lead' => '', 'art' => '', 'after' => '' ) );
?>
<header class="hv-pagehead" style="--c:<?php echo esc_attr( $hv_a['color'] ); ?>">
	<div class="hv-wrap hv-pagehead-in">
		<div class="hv-pagehead-copy">
			<nav class="hv-crumbs" aria-label="Đường dẫn">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">🏠 Trang chủ</a>
				<?php foreach ( $hv_a['crumbs'] as $hv_c ) : ?>
					<span aria-hidden="true">›</span> <?php echo $hv_c[1] ? '<a href="' . esc_url( $hv_c[1] ) . '">' . esc_html( $hv_c[0] ) . '</a>' : '<span>' . esc_html( $hv_c[0] ) . '</span>'; ?>
				<?php endforeach; ?>
			</nav>
			<?php if ( $hv_a['meta'] ) : ?>
				<span class="hv-kicker"><?php echo esc_html( $hv_a['meta'] ); ?></span>
			<?php endif; ?>
			<h1><?php echo esc_html( $hv_a['title'] ); ?></h1>
			<?php if ( $hv_a['lead'] ) : ?>
				<p><?php echo esc_html( $hv_a['lead'] ); ?></p>
			<?php endif; ?>
			<?php echo $hv_a['after']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML dựng sẵn đã escape. ?>
		</div>
		<?php if ( $hv_a['art'] ) : ?>
			<div class="hv-pagehead-art" aria-hidden="true"><?php echo false === strpos( $hv_a['art'], '<' ) ? '<span>' . esc_html( $hv_a['art'] ) . '</span>' : $hv_a['art']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- emoji đã escape hoặc SVG dựng sẵn. ?></div>
		<?php endif; ?>
	</div>
</header>
