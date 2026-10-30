<?php
/**
 * Trang 1 lớp: các môn và bài học theo thứ tự chương trình, trò chơi dành cho lớp.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hv_term   = get_queried_object();
$hv_grade  = hv_term_grade( $hv_term );
$hv_grades = hv_grades();
$hv_groups = hv_group_lessons( $GLOBALS['wp_query']->posts, 'subject' );

$hv_tabs = '<div class="hv-tabs">';
foreach ( $hv_groups as $hv_slug => $hv_list ) {
	list( $hv_name, $hv_icon ) = hv_subject_info( get_term_by( 'slug', $hv_slug, 'mon_hoc' ) );
	$hv_tabs                  .= '<a href="#mon-' . esc_attr( $hv_slug ) . '">' . esc_html( $hv_icon . ' ' . $hv_name ) . '</a>';
}
$hv_tabs .= '<a href="#tro-choi">🎮 Trò chơi</a></div>';

get_template_part(
	'template-parts/hocvui/pagehead',
	null,
	array(
		'color'  => hv_grade_color( $hv_grade ),
		'crumbs' => array( array( $hv_term->name, '' ) ),
		'meta'   => isset( $hv_grades[ $hv_grade ] ) ? $hv_grades[ $hv_grade ][2] . ' Chương trình GDPT 2018' : '',
		'title'  => $hv_term->name,
		'lead'   => $hv_term->description ? $hv_term->description : ( isset( $hv_grades[ $hv_grade ] ) ? $hv_grades[ $hv_grade ][4] : '' ),
		'after'  => $hv_tabs,
	)
);
?>
<div class="hv-wrap hv-section">
	<?php if ( ! $hv_groups ) : ?>
		<p class="hv-empty">Lớp này chưa có bài học. Em quay lại sau nhé!</p>
	<?php endif; ?>
	<?php foreach ( $hv_groups as $hv_slug => $hv_list ) : ?>
		<?php list( $hv_name, $hv_icon, $hv_color ) = hv_subject_info( get_term_by( 'slug', $hv_slug, 'mon_hoc' ) ); ?>
		<section class="hv-subject-block" id="mon-<?php echo esc_attr( $hv_slug ); ?>">
			<h2><i aria-hidden="true"><?php echo esc_html( $hv_icon ); ?></i> <?php echo esc_html( $hv_name ); ?> <span class="hv-pill" style="background:<?php echo esc_attr( $hv_color ); ?>;color:#fff"><?php echo count( $hv_list ); ?> bài</span></h2>
			<div class="hv-lesson-grid">
				<?php
				foreach ( $hv_list as $hv_p ) {
					echo hv_lesson_card( $hv_p, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hàm tự escape.
				}
				?>
			</div>
		</section>
	<?php endforeach; ?>
	<section class="hv-subject-block" id="tro-choi">
		<h2><i aria-hidden="true">🎮</i> Trò chơi cho <?php echo esc_html( $hv_term->name ); ?></h2>
		<?php echo do_shortcode( '[hv_games lop="' . (int) $hv_grade . '" player="0"]' ); ?>
	</section>
</div>
<?php
get_footer();
