<?php
/**
 * Form đăng ký cuối trang.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_bg = bds_img_url( bds_opt( 'register_image' ), 'full' );
?>
<section id="dang-ky" class="bds-section bds-register"<?php echo $bds_bg ? ' style="background-image:url(' . esc_url( $bds_bg ) . ')"' : ''; ?>>
	<div class="bds-hero__overlay"></div>
	<div class="container bds-register__inner">
		<div class="row row-large align-middle">
			<div class="col large-6 medium-12 small-12">
				<h2 class="bds-heading bds-heading--light"><?php echo esc_html( bds_opt( 'register_title' ) ); ?></h2>
				<p class="bds-lead"><?php echo nl2br( esc_html( bds_opt( 'register_text' ) ) ); ?></p>
				<?php if ( bds_opt( 'hotline' ) ) : ?>
					<p class="bds-register__hotline">Hotline tư vấn 24/7:
						<a href="tel:<?php echo esc_attr( bds_tel( bds_opt( 'hotline' ) ) ); ?>"><?php echo esc_html( bds_opt( 'hotline' ) ); ?></a>
					</p>
				<?php endif; ?>
			</div>
			<div class="col large-6 medium-12 small-12">
				<div class="bds-card">
					<?php
					get_template_part(
						'template-parts/bds/lead-form',
						null,
						array(
							'title'  => '',
							'button' => 'Đăng ký tư vấn',
							'source' => 'Cuối trang',
							'full'   => true,
						)
					);
					?>
				</div>
			</div>
		</div>
	</div>
</section>
