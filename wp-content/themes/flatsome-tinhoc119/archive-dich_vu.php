<?php
/**
 * Danh sách dịch vụ (/dich-vu/) và nhóm dịch vụ (/nhom-dich-vu/…).
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
$th_is_tax = is_tax( 'nhom_dich_vu' );
$th_title  = $th_is_tax ? single_term_title( '', false ) : 'Dịch vụ sửa chữa máy tính & IT';
$th_desc   = $th_is_tax ? term_description() : '<p>Kiểm tra, báo giá trước khi sửa. Hỗ trợ tại cửa hàng, tận nơi và từ xa.</p>';
?>
<div id="content" class="th-archive th-services">
	<?php th119t_pagehead( $th_title, $th_desc ); ?>
	<div class="th-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="th-cards th-cols-3">
				<?php
				while ( have_posts() ) :
					the_post();
					$th_icon  = function_exists( 'th119_service_field' ) ? th119_service_field( 'icon' ) : '';
					$th_price = function_exists( 'th119_service_field' ) ? th119_service_field( 'price' ) : '';
					$th_sub   = function_exists( 'th119_service_field' ) ? th119_service_field( 'subtitle' ) : '';
					?>
					<a class="th-card" href="<?php the_permalink(); ?>">
						<span class="th-card__icon"><?php echo th119t_ic( $th_icon ? $th_icon : 'tool' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<h2 class="th-card__head"><?php the_title(); ?></h2>
						<p><?php echo esc_html( $th_sub ? $th_sub : wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
						<?php if ( $th_price ) : ?>
							<p class="th-card__price"><?php echo esc_html( $th_price ); ?></p>
						<?php endif; ?>
						<span class="th-card__more">Xem chi tiết →</span>
					</a>
				<?php endwhile; ?>
			</div>
			<?php th119t_pagination(); ?>
		<?php else : ?>
			<p class="th-empty">Danh sách dịch vụ đang được cập nhật. Vui lòng gọi hotline để được tư vấn.</p>
		<?php endif; ?>
		<?php echo th119t_help_box( 'Chưa biết chọn dịch vụ nào?' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
<?php
get_footer();
