<?php
/**
 * Trang 1 bài học: mục tiêu, nội dung, ghi nhớ, luyện tập, trò chơi, bài cùng môn.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hv_id                         = get_the_ID();
	list( $hv_grade, $hv_subject ) = hv_lesson_terms( $hv_id );
	list( $hv_sname, $hv_sicon )   = hv_subject_info( $hv_subject );
	$hv_icon                       = hv_lesson_meta( 'icon' ) ? hv_lesson_meta( 'icon' ) : $hv_sicon;
	$hv_topic                      = hv_lesson_meta( 'chu_de' );
	$hv_books                      = '';
	foreach ( hv_lesson_meta( 'bo_sach' ) as $hv_b ) {
		$hv_all    = hv_books();
		$hv_books .= isset( $hv_all[ $hv_b ] ) ? '<span>📗 ' . esc_html( $hv_all[ $hv_b ] ) . '</span>' : '';
	}
	$hv_crumbs = array();
	if ( $hv_grade ) {
		$hv_crumbs[] = array( 'Lớp ' . $hv_grade, hv_grade_link( $hv_grade ) );
	}
	if ( $hv_subject ) {
		$hv_crumbs[] = array( $hv_sname, $hv_grade ? hv_grade_link( $hv_grade ) . '#mon-' . $hv_subject->slug : get_term_link( $hv_subject ) );
	}
	get_template_part(
		'template-parts/hocvui/pagehead',
		null,
		array(
			'color'  => hv_grade_color( $hv_grade ),
			'crumbs' => $hv_crumbs,
			'meta'   => $hv_icon . ' ' . trim( ( $hv_grade ? 'Lớp ' . $hv_grade . ' · ' : '' ) . $hv_sname . ( $hv_topic ? ' · ' . $hv_topic : '' ) ),
			'title'  => get_the_title(),
			'lead'   => get_the_excerpt(),
			'after'  => $hv_books ? '<div class="hv-books" title="Bộ sách giáo khoa">' . $hv_books . '</div>' : '',
		)
	);
	$hv_goals = hv_lines( hv_lesson_meta( 'muc_tieu' ) );
	$hv_notes = hv_lines( hv_lesson_meta( 'ghi_nho' ) );
	$hv_quiz  = hv_quiz_data();
	$hv_game  = hv_lesson_meta( 'tro_choi' );
	$hv_video = hv_lesson_meta( 'video' );
	?>
	<div class="hv-wrap hv-lesson">
		<article class="hv-lesson-main">
			<?php if ( $hv_goals ) : ?>
				<section class="hv-box hv-goals">
					<h2>🎯 Em sẽ học được</h2>
					<ul><?php foreach ( $hv_goals as $hv_l ) : ?><li><?php echo wp_kses_post( $hv_l ); ?></li><?php endforeach; ?></ul>
				</section>
			<?php endif; ?>
			<?php if ( $hv_video && wp_oembed_get( $hv_video ) ) : ?>
				<div class="hv-video"><?php echo wp_oembed_get( $hv_video ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- mã nhúng oEmbed của WordPress. ?></div>
			<?php endif; ?>
			<section class="hv-box hv-content">
				<?php the_content(); ?>
			</section>
			<?php if ( $hv_notes ) : ?>
				<section class="hv-box hv-remember">
					<h2>⭐ Ghi nhớ</h2>
					<ul><?php foreach ( $hv_notes as $hv_l ) : ?><li><?php echo wp_kses_post( $hv_l ); ?></li><?php endforeach; ?></ul>
				</section>
			<?php endif; ?>
			<?php if ( $hv_quiz ) : ?>
				<h2 id="luyen-tap">✏️ Luyện tập</h2>
				<?php
				echo hv_game_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hàm tự escape.
					'quiz',
					$hv_grade,
					array(
						'data-quiz'  => wp_json_encode( $hv_quiz ),
						'data-title' => 'Luyện tập: ' . get_the_title(),
						'style'      => '--c:' . hv_grade_color( $hv_grade ),
					)
				);
				?>
			<?php endif; ?>
			<?php if ( $hv_game && array_key_exists( $hv_game, hv_games() ) ) : ?>
				<h2>🎮 Chơi mà học</h2>
				<?php echo hv_game_html( $hv_game, $hv_grade ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hàm tự escape. ?>
			<?php endif; ?>
			<nav class="hv-nav" aria-label="Bài trước, bài sau">
				<?php
				$hv_siblings = array();
				if ( $hv_grade && $hv_subject ) {
					$hv_grades   = hv_grades();
					$hv_siblings = get_posts(
						array(
							'post_type'      => 'bai_hoc',
							'posts_per_page' => 100,
							'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
							'no_found_rows'  => true,
							'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
								array( 'taxonomy' => 'lop', 'field' => 'slug', 'terms' => $hv_grades[ $hv_grade ][1] ),
								array( 'taxonomy' => 'mon_hoc', 'field' => 'term_id', 'terms' => $hv_subject->term_id ),
							),
						)
					);
				}
				$hv_pos = array_search( $hv_id, wp_list_pluck( $hv_siblings, 'ID' ), true );
				if ( false !== $hv_pos && $hv_pos > 0 ) {
					echo '<a class="prev" href="' . esc_url( get_permalink( $hv_siblings[ $hv_pos - 1 ] ) ) . '"><small>← Bài trước</small>' . esc_html( get_the_title( $hv_siblings[ $hv_pos - 1 ] ) ) . '</a>';
				}
				if ( false !== $hv_pos && isset( $hv_siblings[ $hv_pos + 1 ] ) ) {
					echo '<a class="next" href="' . esc_url( get_permalink( $hv_siblings[ $hv_pos + 1 ] ) ) . '"><small>Bài tiếp theo →</small>' . esc_html( get_the_title( $hv_siblings[ $hv_pos + 1 ] ) ) . '</a>';
				}
				?>
			</nav>
		</article>
		<aside class="hv-side">
			<?php if ( count( $hv_siblings ) > 1 ) : ?>
				<div class="hv-box">
					<h3><?php echo esc_html( $hv_sicon . ' ' . $hv_sname . ' lớp ' . $hv_grade ); ?></h3>
					<ol>
						<?php foreach ( $hv_siblings as $hv_s ) : ?>
							<li<?php echo $hv_s->ID === $hv_id ? ' class="is-cur"' : ''; ?>><a href="<?php echo esc_url( get_permalink( $hv_s ) ); ?>"><?php echo esc_html( get_the_title( $hv_s ) ); ?></a></li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>
			<div class="hv-box">
				<h3>⭐ Sao của <span data-hv-name="em">em</span>: <b data-hv-stars>0</b></h3>
				<p>Làm bài luyện tập và chơi trò chơi để nhận thêm sao nhé!</p>
				<?php if ( $hv_grade ) : ?>
					<p><a class="hv-btn" href="<?php echo esc_url( hv_grade_link( $hv_grade ) ); ?>">📚 Các bài lớp <?php echo (int) $hv_grade; ?></a></p>
				<?php endif; ?>
				<p><a class="hv-btn hv-btn-accent" href="<?php echo esc_url( hv_games_url() ); ?>">🎮 Tất cả trò chơi</a></p>
			</div>
		</aside>
	</div>
	<?php
endwhile;

get_footer();
