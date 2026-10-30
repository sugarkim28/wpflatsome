<?php
/**
 * Trang 1 lớp = 1 hòn đảo: mỗi môn là một con đường gồm các màn (bài học) và trùm cuối, kèm khu trò chơi của lớp.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hv_term   = get_queried_object();
$hv_grade  = hv_term_grade( $hv_term );
$hv_grades = hv_grades();
$hv_g      = isset( $hv_grades[ $hv_grade ] ) ? $hv_grades[ $hv_grade ] : $hv_grades[1];
$hv_groups = hv_group_lessons( $GLOBALS['wp_query']->posts, 'subject' );
$hv_bosses = hv_bosses();
$hv_slugs  = wp_list_pluck( $GLOBALS['wp_query']->posts, 'post_name' );

$hv_after = '<div class="hv-island-progress" data-hv-island="' . esc_attr( implode( ',', $hv_slugs ) ) . '"><span class="hv-isl-bar"><i></i></span><span class="hv-isl-count">0/' . count( $hv_slugs ) . ' màn</span></div><div class="hv-tabs">';
foreach ( $hv_groups as $hv_slug => $hv_list ) {
	list( $hv_name, $hv_icon ) = hv_subject_info( get_term_by( 'slug', $hv_slug, 'mon_hoc' ) );
	$hv_after                 .= '<a href="#mon-' . esc_attr( $hv_slug ) . '">' . esc_html( $hv_icon . ' ' . $hv_name ) . '</a>';
}
$hv_after .= '<a href="#tro-choi">🎮 Trò chơi</a></div>';

get_template_part(
	'template-parts/hocvui/pagehead',
	null,
	array(
		'color'  => $hv_g[3],
		'crumbs' => array( array( 'Bản đồ', home_url( '/#ban-do' ) ), array( $hv_g[5], '' ) ),
		'meta'   => $hv_term->name . ' · Chương trình GDPT 2018',
		'title'  => $hv_g[5],
		'lead'   => $hv_term->description ? $hv_term->description : $hv_g[4],
		'art'    => hv_island_svg( $hv_grade ),
		'after'  => $hv_after,
	)
);
?>
<div class="hv-wrap hv-section">
	<?php if ( ! $hv_groups ) : ?>
		<p class="hv-empty">Đảo này đang được xây dựng. Em quay lại sau nhé!</p>
	<?php endif; ?>
	<div class="hv-zones">
	<?php foreach ( $hv_groups as $hv_slug => $hv_list ) : ?>
		<?php
		list( $hv_name, $hv_icon, $hv_color ) = hv_subject_info( get_term_by( 'slug', $hv_slug, 'mon_hoc' ) );
		$hv_boss                               = isset( $hv_bosses[ $hv_slug ] ) ? $hv_bosses[ $hv_slug ] : array( 'Trùm cuối', '👾' );
		$hv_boss_id                            = 'boss-' . $hv_grade . '-' . $hv_slug;
		$hv_quiz                               = array();
		foreach ( $hv_list as $hv_p ) {
			foreach ( hv_quiz_data( $hv_p->ID ) as $hv_q ) {
				$hv_q['t'] = get_the_title( $hv_p );
				$hv_quiz[] = $hv_q;
			}
		}
		?>
		<section class="hv-zone" id="mon-<?php echo esc_attr( $hv_slug ); ?>" style="--c:<?php echo esc_attr( $hv_color ); ?>">
			<header class="hv-zone-head">
				<i aria-hidden="true"><?php echo esc_html( $hv_icon ); ?></i>
				<div><h2><?php echo esc_html( $hv_name ); ?></h2><span><?php echo count( $hv_list ); ?> màn · Trùm cuối: <?php echo esc_html( $hv_boss[0] ); ?></span></div>
			</header>
			<ol class="hv-path" data-hv-path>
				<?php foreach ( $hv_list as $hv_i => $hv_p ) : ?>
					<?php $hv_ic = hv_lesson_meta( 'icon', $hv_p->ID ); ?>
					<li class="hv-node" data-hv-node="<?php echo esc_attr( $hv_p->post_name ); ?>">
						<a href="<?php echo esc_url( get_permalink( $hv_p ) ); ?>">
							<span class="hv-node-btn" aria-hidden="true"><?php echo esc_html( $hv_ic ? $hv_ic : $hv_icon ); ?></span>
							<span class="hv-node-label"><small>Màn <?php echo (int) $hv_i + 1; ?></small><?php echo esc_html( get_the_title( $hv_p ) ); ?></span>
							<span class="hv-node-stars" aria-hidden="true"></span>
						</a>
					</li>
				<?php endforeach; ?>
				<?php if ( $hv_quiz ) : ?>
					<li class="hv-node hv-node-boss" data-hv-node="<?php echo esc_attr( $hv_boss_id ); ?>">
						<a href="#trum-<?php echo esc_attr( $hv_slug ); ?>" data-hv-boss-link>
							<span class="hv-node-btn" aria-hidden="true"><?php echo esc_html( $hv_boss[1] ); ?></span>
							<span class="hv-node-label"><small>Trùm cuối</small><?php echo esc_html( $hv_boss[0] ); ?></span>
							<span class="hv-node-stars" aria-hidden="true"></span>
						</a>
					</li>
				<?php endif; ?>
			</ol>
			<?php if ( $hv_quiz ) : ?>
				<details class="hv-boss-arena" id="trum-<?php echo esc_attr( $hv_slug ); ?>">
					<summary><span aria-hidden="true"><?php echo esc_html( $hv_boss[1] ); ?></span> Đánh trùm <?php echo esc_html( $hv_boss[0] ); ?> <small>10 câu hỏi trộn từ mọi màn · 3 tim</small></summary>
					<?php
					echo hv_game_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hàm tự escape.
						'quiz',
						$hv_grade,
						array(
							'data-quiz'   => wp_json_encode( $hv_quiz ),
							'data-title'  => $hv_boss[1] . ' Trùm ' . $hv_boss[0],
							'data-level'  => $hv_boss_id,
							'data-hearts' => 3,
							'data-limit'  => 10,
							'style'       => '--c:' . $hv_color,
						)
					);
					?>
				</details>
			<?php endif; ?>
		</section>
	<?php endforeach; ?>
	</div>
	<section class="hv-zone hv-zone-arcade" id="tro-choi">
		<header class="hv-zone-head"><i aria-hidden="true">🎮</i><div><h2>Khu trò chơi <?php echo esc_html( $hv_g[5] ); ?></h2><span>Luyện thêm để kiếm sao và XP</span></div></header>
		<?php echo do_shortcode( '[hv_games lop="' . (int) $hv_grade . '" player="0"]' ); ?>
	</section>
</div>
<?php
get_footer();
