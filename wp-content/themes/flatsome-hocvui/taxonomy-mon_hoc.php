<?php
/**
 * Trang 1 môn học: bài học chia theo lớp.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hv_term                            = get_queried_object();
list( $hv_name, $hv_icon, $hv_color ) = hv_subject_info( $hv_term );
$hv_groups                          = hv_group_lessons( $GLOBALS['wp_query']->posts, 'grade' );

$hv_tabs = '<div class="hv-tabs">';
foreach ( array_keys( $hv_groups ) as $hv_g ) {
	$hv_tabs .= $hv_g ? '<a href="#lop-' . (int) $hv_g . '">Lớp ' . (int) $hv_g . '</a>' : '';
}
$hv_tabs .= '</div>';

get_template_part(
	'template-parts/hocvui/pagehead',
	null,
	array(
		'color'  => $hv_color,
		'crumbs' => array( array( 'Bài học', get_post_type_archive_link( 'bai_hoc' ) ), array( $hv_name, '' ) ),
		'meta'   => $hv_icon . ' Môn học',
		'title'  => $hv_name,
		'lead'   => $hv_term->description,
		'after'  => $hv_tabs,
	)
);
?>
<div class="hv-wrap hv-section">
	<?php if ( ! $hv_groups ) : ?>
		<p class="hv-empty">Môn này chưa có bài học.</p>
	<?php endif; ?>
	<?php foreach ( $hv_groups as $hv_g => $hv_list ) : ?>
		<section class="hv-subject-block" id="lop-<?php echo (int) $hv_g; ?>">
			<h2><?php echo $hv_g ? esc_html( 'Lớp ' . $hv_g ) : 'Chưa xếp lớp'; ?></h2>
			<div class="hv-lesson-grid">
				<?php
				foreach ( $hv_list as $hv_p ) {
					echo hv_lesson_card( $hv_p, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hàm tự escape.
				}
				?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
<?php
get_footer();
