<?php
/**
 * Trang 1 bài học = 1 màn chơi: Mở màn (Cú Bông kể chuyện) → Khám phá (từng thẻ) → Bí kíp → Nhiệm vụ ngoài đời → Đánh trùm màn.
 * Không có JavaScript thì các bước hiện nối tiếp nhau như bài viết thường.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hv_id                                 = get_the_ID();
	list( $hv_grade, $hv_subject )         = hv_lesson_terms( $hv_id );
	list( $hv_sname, $hv_sicon, $hv_scol, $hv_s3d ) = hv_subject_info( $hv_subject );
	$hv_grades                             = hv_grades();
	$hv_g                                  = isset( $hv_grades[ $hv_grade ] ) ? $hv_grades[ $hv_grade ] : null;
	$hv_icon                               = hv_lesson_meta( 'icon' ) ? hv_lesson_meta( 'icon' ) : $hv_sicon;
	$hv_topic                              = hv_lesson_meta( 'chu_de' );
	$hv_island                             = $hv_grade ? hv_grade_link( $hv_grade ) : get_post_type_archive_link( 'bai_hoc' );

	// Các màn cùng môn, cùng lớp (để biết số màn, màn trước, màn sau).
	$hv_siblings = array();
	if ( $hv_g && $hv_subject ) {
		$hv_siblings = get_posts(
			array(
				'post_type'      => 'bai_hoc',
				'posts_per_page' => 100,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array( 'taxonomy' => 'lop', 'field' => 'slug', 'terms' => $hv_g[1] ),
					array( 'taxonomy' => 'mon_hoc', 'field' => 'term_id', 'terms' => $hv_subject->term_id ),
				),
			)
		);
	}
	$hv_pos   = array_search( $hv_id, wp_list_pluck( $hv_siblings, 'ID' ), true );
	$hv_next  = ( false !== $hv_pos && isset( $hv_siblings[ $hv_pos + 1 ] ) ) ? $hv_siblings[ $hv_pos + 1 ] : null;
	$hv_bosses = hv_bosses();
	if ( $hv_next ) {
		$hv_next_url   = get_permalink( $hv_next );
		$hv_next_label = 'Màn tiếp theo';
	} elseif ( $hv_subject && $hv_grade ) {
		$hv_next_url   = $hv_island . '#trum-' . $hv_subject->slug;
		$hv_next_label = 'Đánh trùm ' . ( isset( $hv_bosses[ $hv_subject->slug ] ) ? $hv_bosses[ $hv_subject->slug ][0] : 'cuối' );
	} else {
		$hv_next_url   = '';
		$hv_next_label = '';
	}

	$hv_books = '';
	foreach ( hv_lesson_meta( 'bo_sach' ) as $hv_b ) {
		$hv_all    = hv_books();
		$hv_books .= isset( $hv_all[ $hv_b ] ) ? '<span>📗 ' . esc_html( $hv_all[ $hv_b ] ) . '</span>' : '';
	}
	$hv_crumbs = array();
	if ( $hv_g ) {
		$hv_crumbs[] = array( $hv_g[5], $hv_island );
	}
	if ( $hv_subject ) {
		$hv_crumbs[] = array( $hv_sname, $hv_g ? $hv_island . '#mon-' . $hv_subject->slug : get_term_link( $hv_subject ) );
	}
	get_template_part(
		'template-parts/hocvui/pagehead',
		null,
		array(
			'color'  => $hv_g ? $hv_g[3] : $hv_scol,
			'crumbs' => $hv_crumbs,
			'meta'   => ( false !== $hv_pos ? 'Màn ' . ( $hv_pos + 1 ) . '/' . count( $hv_siblings ) . ' · ' : '' ) . trim( ( $hv_grade ? 'Lớp ' . $hv_grade . ' · ' : '' ) . $hv_sname ),
			'title'  => get_the_title(),
			'lead'   => $hv_topic ? 'Chủ đề: ' . $hv_topic : '',
			'art'    => hv_icon3d( $hv_s3d, 150 ) ? hv_icon3d( $hv_s3d, 150 ) : $hv_icon,
			'after'  => $hv_books ? '<div class="hv-books">' . $hv_books . '</div>' : '',
		)
	);
	$hv_hook    = hv_lesson_meta( 'mo_man' );
	$hv_mission = hv_lesson_meta( 'nhiem_vu' );
	$hv_goals   = hv_lines( hv_lesson_meta( 'muc_tieu' ) );
	$hv_notes   = hv_lines( hv_lesson_meta( 'ghi_nho' ) );
	$hv_quiz    = hv_quiz_data();
	$hv_game    = hv_lesson_meta( 'tro_choi' );
	$hv_video   = hv_lesson_meta( 'video' );
	$hv_embed   = $hv_video ? wp_oembed_get( $hv_video ) : '';
	?>
	<div class="hv-wrap hv-lesson">
		<article class="hv-level" data-hv-stepper data-grade="<?php echo (int) $hv_grade; ?>" style="--c:<?php echo esc_attr( $hv_g ? $hv_g[3] : $hv_scol ); ?>">

			<section class="hv-step hv-step-intro" data-title="Mở màn">
				<div class="hv-story">
					<img class="hv-owl-img" src="<?php echo esc_url( HV_URI . '/assets/img/mascot.svg' ); ?>" width="130" height="130" alt="">
					<div class="hv-speech" data-read><?php echo esc_html( $hv_hook ? $hv_hook : get_the_excerpt() ); ?></div>
				</div>
				<?php if ( $hv_goals ) : ?>
					<div class="hv-goals">
						<h2><?php echo hv_icon3d( 'target', 32 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Qua màn này, em sẽ:</h2>
						<ul><?php foreach ( $hv_goals as $hv_l ) : ?><li><?php echo wp_kses_post( $hv_l ); ?></li><?php endforeach; ?></ul>
					</div>
				<?php endif; ?>
				<div class="hv-actions">
					<button type="button" class="hv-btn hv-btn-white" data-hv-hear>🔈 Nghe Cú Bông kể</button>
					<button type="button" class="hv-btn hv-btn-big" data-hv-start>Vào màn chơi ›</button>
				</div>
			</section>

			<section class="hv-step" data-split data-title="Khám phá">
				<div class="hv-content"><?php the_content(); ?></div>
			</section>

			<?php if ( $hv_embed ) : ?>
				<section class="hv-step" data-title="Xem video">
					<div class="hv-card-xl"><h2>🎬 Xem video bài giảng</h2><div class="hv-video"><?php echo $hv_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- mã nhúng oEmbed của WordPress. ?></div></div>
				</section>
			<?php endif; ?>

			<?php if ( $hv_notes ) : ?>
				<section class="hv-step" data-title="Bí kíp ghi nhớ">
					<div class="hv-card-xl hv-remember" data-read>
						<h2><?php echo hv_icon3d( 'scroll', 40 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Bí kíp ghi nhớ</h2>
						<ul><?php foreach ( $hv_notes as $hv_l ) : ?><li><?php echo wp_kses_post( $hv_l ); ?></li><?php endforeach; ?></ul>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $hv_mission ) : ?>
				<section class="hv-step" data-title="Nhiệm vụ ngoài đời">
					<div class="hv-card-xl hv-mission" data-read>
						<h2><?php echo hv_icon3d( 'house', 40 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Nhiệm vụ ngoài đời</h2>
						<p><?php echo esc_html( $hv_mission ); ?></p>
						<small>Làm cùng bố mẹ sau giờ học nhé. Còn bây giờ – chuẩn bị đánh trùm màn!</small>
					</div>
				</section>
			<?php endif; ?>

			<section class="hv-step hv-step-boss" data-title="Đánh trùm màn">
				<?php if ( $hv_quiz ) : ?>
					<?php
					echo hv_game_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hàm tự escape.
						'quiz',
						$hv_grade,
						array(
							'data-quiz'       => wp_json_encode( $hv_quiz ),
							'data-title'      => 'Trùm màn: ' . get_the_title(),
							'data-level'      => get_post_field( 'post_name', $hv_id ),
							'data-next-url'   => $hv_next_url,
							'data-next-label' => $hv_next_label,
							'data-home-url'   => $hv_island,
							'style'           => '--c:' . ( $hv_g ? $hv_g[3] : $hv_scol ),
						)
					);
					?>
				<?php endif; ?>
				<?php if ( $hv_game && array_key_exists( $hv_game, hv_games() ) ) : ?>
					<h2 class="hv-bonus-title"><?php echo hv_icon3d( 'gem', 36 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Màn thưởng: chơi thêm để kiếm XP</h2>
					<?php echo hv_game_html( $hv_game, $hv_grade ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hàm tự escape. ?>
				<?php endif; ?>
			</section>
		</article>

		<aside class="hv-side">
			<?php if ( count( $hv_siblings ) > 1 ) : ?>
				<div class="hv-box hv-mini-path">
					<h3><?php echo esc_html( $hv_sicon . ' ' . $hv_sname . ' · ' . ( $hv_g ? $hv_g[5] : '' ) ); ?></h3>
					<ol data-hv-path>
						<?php foreach ( $hv_siblings as $hv_k => $hv_s ) : ?>
							<li class="hv-node<?php echo $hv_s->ID === $hv_id ? ' is-here' : ''; ?>" data-hv-node="<?php echo esc_attr( $hv_s->post_name ); ?>"><a href="<?php echo esc_url( get_permalink( $hv_s ) ); ?>"><b><?php echo (int) $hv_k + 1; ?></b><span><?php echo esc_html( get_the_title( $hv_s ) ); ?></span><span class="hv-node-stars" aria-hidden="true"></span></a></li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>
			<div class="hv-box">
				<?php echo hv_sc_quests(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML tĩnh. ?>
				<p><a class="hv-btn hv-btn-ghost" href="<?php echo esc_url( $hv_island ); ?>">🏝️ Về <?php echo esc_html( $hv_g ? $hv_g[5] : 'bản đồ' ); ?></a></p>
			</div>
		</aside>
	</div>
	<?php
endwhile;

get_footer();
