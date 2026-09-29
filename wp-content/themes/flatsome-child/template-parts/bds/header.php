<?php
/**
 * Thanh menu của landing: logo, menu neo, nút nhận bảng giá, hotline; menu ☰ trên điện thoại.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_logo    = bds_logo_url();
$bds_items   = bds_lines( bds_opt( 'nav_items' ) );
$bds_hotline = bds_opt( 'hotline' );
$bds_cta     = bds_opt( 'nav_cta' );
?>
<header class="bds-nav" id="bds-nav">
	<div class="container bds-nav__inner">
		<a class="bds-nav__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( $bds_logo ) : ?>
				<img src="<?php echo esc_url( $bds_logo ); ?>" alt="<?php echo esc_attr( bds_opt( 'hero_title' ) ); ?>">
			<?php else : ?>
				<span><?php echo esc_html( bds_opt( 'hero_title' ) ); ?></span>
			<?php endif; ?>
		</a>

		<nav class="bds-nav__menu" id="bds-nav-menu" aria-label="Menu dự án">
			<?php foreach ( $bds_items as $bds_item ) : ?>
				<a href="<?php echo esc_url( '' !== $bds_item[1] ? $bds_item[1] : '#' ); ?>"><?php echo esc_html( $bds_item[0] ); ?></a>
			<?php endforeach; ?>
			<?php if ( $bds_cta ) : ?>
				<a href="#dang-ky" class="bds-nav__cta"><?php echo esc_html( $bds_cta ); ?></a>
			<?php endif; ?>
			<?php if ( $bds_hotline ) : ?>
				<a href="tel:<?php echo esc_attr( bds_tel( $bds_hotline ) ); ?>" class="bds-nav__phone">
					<i class="icon-phone" aria-hidden="true"></i> <?php echo esc_html( $bds_hotline ); ?>
				</a>
			<?php endif; ?>
		</nav>

		<button type="button" class="bds-nav__toggle" aria-controls="bds-nav-menu" aria-expanded="false" aria-label="Mở menu">
			<span></span><span></span><span></span>
		</button>
	</div>
</header>
