<?php
/**
 * Các khối trang chủ (shortcode, đặt được trong UX Builder):
 * [hv_hero] [hv_grades] [hv_subjects lop=""] [hv_lessons lop="" mon="" so="6"] [hv_parent_steps] [hv_band]
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * [hv_hero] – banner đầu trang: lời chào, nút, ô nhập tên, số sao.
 *
 * @return string
 */
function hv_sc_hero() {
	$title = esc_html( hv_opt( 'hero_title' ) );
	$title = preg_replace( '/\*(.+?)\*/u', '<em>$1</em>', $title );
	ob_start();
	?>
	<section class="hv-hero">
		<div class="hv-wrap hv-hero-in">
			<div>
				<h1><?php echo wp_kses( $title, array( 'em' => array() ) ); ?></h1>
				<p class="hv-hero-lead"><?php echo esc_html( hv_opt( 'hero_lead' ) ); ?></p>
				<div class="hv-hero-btns">
					<a class="hv-btn hv-btn-big" href="#chon-lop">📚 Chọn lớp học</a>
					<a class="hv-btn hv-btn-big hv-btn-accent" href="<?php echo esc_url( hv_games_url() ); ?>">🎮 Vào chơi</a>
				</div>
				<?php echo hv_sc_name_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML tĩnh. ?>
				<div class="hv-mystars">⭐ Em đang có <b data-hv-stars>0</b> sao</div>
			</div>
			<div class="hv-hero-art">
				<div class="hv-bubble"><?php echo esc_html( hv_opt( 'hero_bubble' ) ); ?></div>
				<img src="<?php echo esc_url( HV_URI . '/assets/img/mascot.svg' ); ?>" width="320" height="320" alt="Bạn Cú Bông – linh vật Học Vui" fetchpriority="high">
				<div class="hv-floaters" aria-hidden="true"><span style="left:4%;bottom:12%">✏️</span><span style="right:6%;top:30%;animation-delay:.8s">⭐</span><span style="right:16%;bottom:4%;animation-delay:1.4s">🔢</span></div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'hv_hero', 'hv_sc_hero' );

/**
 * [hv_grades] – 5 thẻ lớp.
 *
 * @return string
 */
function hv_sc_grades() {
	$html = '<div class="hv-grade-grid" id="chon-lop">';
	foreach ( hv_grades() as $n => $g ) {
		$html .= '<a class="hv-grade g' . (int) $n . '" href="' . esc_url( hv_grade_link( $n ) ) . '"><i aria-hidden="true">' . esc_html( $g[2] ) . '</i><b>' . (int) $n . '</b><strong>' . esc_html( $g[0] ) . '</strong><small>' . esc_html( $g[4] ) . '</small></a>';
	}
	return $html . '</div>';
}
add_shortcode( 'hv_grades', 'hv_sc_grades' );

/**
 * Link 1 môn (trong 1 lớp nếu có).
 *
 * @param string $slug  Môn.
 * @param int    $grade Lớp.
 * @return string
 */
function hv_subject_link( $slug, $grade = 0 ) {
	if ( $grade ) {
		return hv_grade_link( $grade ) . '#mon-' . $slug;
	}
	$t    = get_term_by( 'slug', $slug, 'mon_hoc' );
	$link = $t ? get_term_link( $t ) : '';
	return $link && ! is_wp_error( $link ) ? $link : get_post_type_archive_link( 'bai_hoc' );
}

/**
 * [hv_subjects lop=""] – thẻ môn học.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function hv_sc_subjects( $atts ) {
	$a     = shortcode_atts( array( 'lop' => 0 ), $atts );
	$grade = (int) $a['lop'];
	$html  = '<div class="hv-subject-grid">';
	foreach ( hv_subjects() as $slug => $s ) {
		if ( $grade && ! in_array( $grade, $s[3], true ) ) {
			continue;
		}
		$range = min( $s[3] ) === max( $s[3] ) ? 'Lớp ' . min( $s[3] ) : 'Lớp ' . min( $s[3] ) . '–' . max( $s[3] );
		$html .= '<a class="hv-subject" style="--c:' . esc_attr( $s[2] ) . '" href="' . esc_url( hv_subject_link( $slug, $grade ) ) . '"><i aria-hidden="true">' . esc_html( $s[1] ) . '</i><span>' . esc_html( $s[0] ) . '<small>' . esc_html( $range ) . '</small></span></a>';
	}
	return $html . '</div>';
}
add_shortcode( 'hv_subjects', 'hv_sc_subjects' );

/**
 * Thẻ 1 bài học.
 *
 * @param WP_Post $p          Bài.
 * @param bool    $show_grade Hiện lớp.
 * @return string
 */
function hv_lesson_card( $p, $show_grade = true ) {
	list( $grade, $subject ) = hv_lesson_terms( $p->ID );
	list( $sname, $sicon, $scolor ) = hv_subject_info( $subject );
	$icon = hv_lesson_meta( 'icon', $p->ID );
	$meta = trim( ( $show_grade && $grade ? 'Lớp ' . $grade . ' · ' : '' ) . $sname );
	return '<a class="hv-lesson-card" href="' . esc_url( get_permalink( $p ) ) . '" style="--c:' . esc_attr( $scolor ) . '"><i aria-hidden="true">' . esc_html( $icon ? $icon : $sicon ) . '</i><span><span class="hv-meta">' . esc_html( $meta ) . '</span><h3>' . esc_html( get_the_title( $p ) ) . '</h3><p>' . esc_html( wp_trim_words( get_the_excerpt( $p ), 18 ) ) . '</p></span></a>';
}

/**
 * [hv_lessons lop="" mon="" so="6"] – lưới bài học (mới nhất hoặc theo lớp/môn).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function hv_sc_lessons( $atts ) {
	$a    = shortcode_atts( array( 'lop' => 0, 'mon' => '', 'so' => 6 ), $atts );
	$args = array(
		'post_type'      => 'bai_hoc',
		'posts_per_page' => max( 1, (int) $a['so'] ),
		'no_found_rows'  => true,
		'tax_query'      => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	);
	if ( (int) $a['lop'] ) {
		$g                   = hv_grades();
		$args['tax_query'][] = array( 'taxonomy' => 'lop', 'field' => 'slug', 'terms' => isset( $g[ (int) $a['lop'] ] ) ? $g[ (int) $a['lop'] ][1] : '' );
	}
	if ( $a['mon'] ) {
		$args['tax_query'][] = array( 'taxonomy' => 'mon_hoc', 'field' => 'slug', 'terms' => sanitize_title( $a['mon'] ) );
	}
	$posts = get_posts( $args );
	if ( ! $posts ) {
		return '<p class="hv-empty">Chưa có bài học. Vào <em>Giao diện → Tạo site mẫu</em> để thêm bài học mẫu.</p>';
	}
	$html = '<div class="hv-lesson-grid">';
	foreach ( $posts as $p ) {
		$html .= hv_lesson_card( $p );
	}
	return $html . '</div>';
}
add_shortcode( 'hv_lessons', 'hv_sc_lessons' );

/**
 * [hv_parent_steps] – cách học hiệu quả cùng Học Vui.
 *
 * @return string
 */
function hv_sc_parent_steps() {
	$steps = array(
		array( 'Chọn lớp, chọn môn', 'Bài học sắp xếp theo chủ đề của chương trình mới, bám bộ sách Kết nối tri thức với cuộc sống.' ),
		array( 'Đọc bài 5–10 phút', 'Mỗi bài ngắn gọn: mục tiêu, giải thích dễ hiểu, ví dụ, mẹo nhớ nhanh.' ),
		array( 'Luyện tập ngay', 'Câu hỏi trắc nghiệm cuối bài, có đọc to câu hỏi cho bé lớp 1.' ),
		array( 'Chơi để nhớ lâu', 'Trò chơi tự sinh câu hỏi mới mỗi lượt, nhận sao và sưu tập nhãn dán.' ),
	);
	$html  = '<div class="hv-steps">';
	foreach ( $steps as $s ) {
		$html .= '<div class="hv-step"><h3>' . esc_html( $s[0] ) . '</h3><p>' . esc_html( $s[1] ) . '</p></div>';
	}
	return $html . '</div>';
}
add_shortcode( 'hv_parent_steps', 'hv_sc_parent_steps' );

/**
 * [hv_band title="" text="" button="" link=""] – dải kêu gọi.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function hv_sc_band( $atts ) {
	$a = shortcode_atts(
		array(
			'title'  => 'Bố mẹ đồng hành cùng con',
			'text'   => 'Xem cách Học Vui bám sát chương trình mới và gợi ý học 15 phút mỗi ngày cùng con.',
			'button' => 'Góc phụ huynh',
			'link'   => '',
		),
		$atts
	);
	$link = $a['link'] ? $a['link'] : ( get_page_by_path( 'goc-phu-huynh' ) ? get_permalink( get_page_by_path( 'goc-phu-huynh' ) ) : home_url( '/' ) );
	return '<div class="hv-band"><div><h2>' . esc_html( $a['title'] ) . '</h2><p>' . esc_html( $a['text'] ) . '</p></div><a class="hv-btn hv-btn-accent hv-btn-big" href="' . esc_url( $link ) . '">' . esc_html( $a['button'] ) . '</a></div>';
}
add_shortcode( 'hv_band', 'hv_sc_band' );

/**
 * [hv_section title="" sub="" bg=""]…[/hv_section] – khung mục có tiêu đề.
 *
 * @param array  $atts    Thuộc tính.
 * @param string $content Nội dung.
 * @return string
 */
function hv_sc_section( $atts, $content = '' ) {
	$a = shortcode_atts( array( 'title' => '', 'sub' => '', 'bg' => '', 'id' => '' ), $atts );
	$h = '<section class="hv-section"' . ( $a['id'] ? ' id="' . esc_attr( $a['id'] ) . '"' : '' ) . ( $a['bg'] ? ' style="background:' . esc_attr( $a['bg'] ) . '"' : '' ) . '><div class="hv-wrap">';
	if ( $a['title'] ) {
		$h .= '<h2 class="hv-section-title">' . esc_html( $a['title'] ) . '</h2>';
	}
	if ( $a['sub'] ) {
		$h .= '<p class="hv-section-sub">' . esc_html( $a['sub'] ) . '</p>';
	}
	return $h . do_shortcode( shortcode_unautop( $content ) ) . '</div></section>';
}
add_shortcode( 'hv_section', 'hv_sc_section' );

/**
 * [hv_pagehead title="" lead="" color=""] – dải tiêu đề màu cho trang thường (Trò chơi, Góc phụ huynh…).
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function hv_sc_pagehead( $atts ) {
	$a = shortcode_atts( array( 'title' => get_the_title(), 'lead' => '', 'color' => hv_opt( 'color_primary' ) ), $atts );
	ob_start();
	get_template_part(
		'template-parts/hocvui/pagehead',
		null,
		array( 'color' => $a['color'], 'crumbs' => array( array( $a['title'], '' ) ), 'title' => $a['title'], 'lead' => $a['lead'] )
	);
	return ob_get_clean();
}
add_shortcode( 'hv_pagehead', 'hv_sc_pagehead' );
