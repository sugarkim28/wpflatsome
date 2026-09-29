<?php
/**
 * Hero.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_bg = bds_img_url( bds_opt( 'hero_image' ), 'full' );
?>
<section id="trang-chu" class="bds-hero"<?php echo $bds_bg ? ' style="background-image:url(' . esc_url( $bds_bg ) . ')"' : ''; ?>>
	<div class="bds-hero__overlay"></div>
	<div class="container bds-hero__inner">
		<div class="row row-large align-middle">
			<div class="col large-7 medium-12 small-12">
				<?php if ( bds_opt( 'hero_eyebrow' ) ) : ?>
					<span class="bds-eyebrow"><?php echo esc_html( bds_opt( 'hero_eyebrow' ) ); ?></span>
				<?php endif; ?>
				<h1 class="bds-hero__title"><?php echo esc_html( bds_opt( 'hero_title' ) ); ?></h1>
				<?php if ( bds_opt( 'hero_subtitle' ) ) : ?>
					<p class="bds-hero__subtitle"><?php echo esc_html( bds_opt( 'hero_subtitle' ) ); ?></p>
				<?php endif; ?>
				<?php if ( bds_opt( 'hero_price' ) ) : ?>
					<p class="bds-hero__price"><?php echo esc_html( bds_opt( 'hero_price' ) ); ?></p>
				<?php endif; ?>
				<div class="bds-hero__actions">
					<a href="#dang-ky" class="button bds-btn bds-btn--accent"><?php echo esc_html( bds_opt( 'hero_cta' ) ); ?></a>
					<?php if ( bds_opt( 'hotline' ) ) : ?>
						<a href="tel:<?php echo esc_attr( bds_tel( bds_opt( 'hotline' ) ) ); ?>" class="button bds-btn bds-btn--ghost">
							<i class="icon-phone" aria-hidden="true"></i> <?php echo esc_html( bds_opt( 'hotline' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
			<div class="col large-5 medium-12 small-12">
				<div class="bds-card bds-hero__form">
					<?php
					get_template_part(
						'template-parts/bds/lead-form',
						null,
						array(
							'title'  => 'Nhận bảng giá mới nhất',
							'button' => 'Gửi thông tin',
							'source' => 'Hero',
						)
					);
					?>
				</div>
			</div>
		</div>
	</div>
</section>
