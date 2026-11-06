<?php
/**
 * Trang 404: tìm kiếm, chủ đề kiến thức, liên hệ (lỗi được ghi vào Tin Học 119 → SEO & chuyển hướng để tạo chuyển hướng).
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div id="content" class="th-archive th-404">
	<?php th119t_pagehead( 'Không tìm thấy trang', '<p>Trang bạn tìm có thể đã đổi địa chỉ. Hãy tìm lại hoặc chọn chủ đề bên dưới.</p>', '<div class="th-pagehead__search">' . th119t_sc( '[th119_search]' ) . '</div>' ); ?>
	<div class="th-wrap">
		<?php echo th119t_sc( '[th119_kb_categories]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p class="th-center"><a class="th-btn th-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Về trang chủ</a></p>
		<?php echo th119t_help_box( 'Cần hỗ trợ ngay?' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
<?php
get_footer();
