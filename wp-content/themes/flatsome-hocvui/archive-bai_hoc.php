<?php
/**
 * Trang /bai-hoc/: chọn lớp, chọn môn, bài học mới.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part(
	'template-parts/hocvui/pagehead',
	null,
	array(
		'crumbs' => array( array( 'Bài học', '' ) ),
		'meta'   => '📚 Chương trình GDPT 2018 · Bộ sách Kết nối tri thức với cuộc sống (dùng chung toàn quốc từ năm học 2026–2027)',
		'title'  => 'Bài học lớp 1 – lớp 5',
		'lead'   => 'Chọn lớp của em, sau đó chọn môn và bài muốn học. Mỗi bài đều có phần luyện tập và trò chơi ôn bài.',
	)
);
?>
<div class="hv-wrap hv-section">
	<h2 class="hv-section-title">Em học lớp mấy?</h2>
	<?php echo do_shortcode( '[hv_grades]' ); ?>
	<h2 class="hv-section-title" style="margin-top:48px">Các môn học</h2>
	<p class="hv-section-sub">Theo Chương trình giáo dục phổ thông 2018 cấp Tiểu học.</p>
	<?php echo do_shortcode( '[hv_subjects]' ); ?>
	<h2 class="hv-section-title" style="margin-top:48px">Bài học mới</h2>
	<?php echo do_shortcode( '[hv_lessons so="9"]' ); ?>
</div>
<?php
get_footer();
