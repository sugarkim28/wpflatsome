<?php
/**
 * Kết quả tìm kiếm: bài kiến thức trước, gợi ý dịch vụ và hỗ trợ khi không có kết quả.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
global $wp_query;
$th_q = get_search_query();
?>
<div id="content" class="th-archive th-search-page">
	<?php th119t_pagehead( $th_q ? 'Kết quả tìm kiếm: “' . $th_q . '”' : 'Tìm kiếm', $th_q ? '<p>Tìm thấy ' . (int) $wp_query->found_posts . ' kết quả.</p>' : '', '<div class="th-pagehead__search">' . th119t_sc( '[th119_search]' ) . '</div>' ); ?>
	<div class="th-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="th-posts">
				<?php
				while ( have_posts() ) :
					the_post();
					echo th119t_card( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endwhile;
				?>
			</div>
			<?php th119t_pagination(); ?>
		<?php else : ?>
			<div class="th-empty-state">
				<h2>Chưa có bài viết phù hợp</h2>
				<p>Thử từ khoá ngắn hơn (ví dụ: “màn hình xanh”, “laptop không lên nguồn”, “máy in”), hoặc xem các chủ đề bên dưới.</p>
			</div>
			<?php echo th119t_sc( '[th119_kb_categories]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<?php echo th119t_help_box( 'Không tìm thấy cách xử lý?' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
<?php
get_footer();
