<?php
/**
 * Các khối giao diện "phá đảo" (shortcode, đặt được trong UX Builder):
 * [hv_hero] [hv_world] [hv_quests] [hv_subjects lop=""] [hv_lessons lop="" mon="" so="6"]
 * [hv_parent_steps] [hv_band] [hv_section]…[/hv_section] [hv_pagehead] [hv_profile]
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hình hòn đảo (SVG) của 1 lớp.
 *
 * @param int $grade Lớp.
 * @return string
 */
function hv_island_svg( $grade ) {
	$g    = hv_grades();
	$g    = isset( $g[ $grade ] ) ? $g[ $grade ] : $g[1];
	$land = $g[6];
	return '<svg class="hv-isl-svg" viewBox="0 0 200 150" aria-hidden="true">'
		. '<ellipse cx="100" cy="122" rx="94" ry="20" fill="#fff" opacity=".35"/>'
		. '<path d="M14 110 C16 92 52 84 100 84 C150 84 186 92 186 110 C186 126 150 134 100 134 C50 134 14 126 14 110 Z" fill="#ffe1a1" stroke="#1d1442" stroke-width="4"/>'
		. '<path d="M34 108 C30 76 64 46 102 46 C142 46 172 76 166 108 C150 116 54 116 34 108 Z" fill="' . esc_attr( $land ) . '" stroke="#1d1442" stroke-width="4"/>'
		. '<path d="M52 92 q10 -6 20 0 M120 98 q10 -6 20 0" stroke="#1d1442" stroke-width="3" fill="none" stroke-linecap="round" opacity=".35"/>'
		. '<path d="M150 70 V20" stroke="#1d1442" stroke-width="4" stroke-linecap="round"/>'
		. '<path d="M150 20 L184 30 L150 40 Z" fill="' . esc_attr( $g[3] ) . '" stroke="#1d1442" stroke-width="3" stroke-linejoin="round"/>'
		. '<text x="163" y="35" font-size="13" font-weight="800" fill="#fff" text-anchor="middle" font-family="Baloo 2,Arial">' . (int) $grade . '</text>'
		. '<text x="98" y="92" font-size="46" text-anchor="middle">' . esc_html( $g[2] ) . '</text>'
		. '</svg>';
}

/**
 * Slug các bài học theo lớp (để tính tiến độ phá đảo).
 *
 * @return array lớp => [slug…]
 */
function hv_lessons_by_grade() {
	static $out = null;
	if ( null !== $out ) {
		return $out;
	}
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'bai_hoc', 'posts_per_page' => 500, 'no_found_rows' => true ) ) as $p ) {
		list( $grade ) = hv_lesson_terms( $p->ID );
		if ( $grade ) {
			$out[ $grade ][] = $p->post_name;
		}
	}
	return $out;
}

/**
 * Thanh nhân vật (cấp độ, XP, sao, chuỗi ngày) – JS điền dữ liệu lưu trên máy.
 *
 * @param string $extra Class thêm.
 * @return string
 */
function hv_hud_html( $extra = '' ) {
	return '<div class="hv-hud ' . esc_attr( $extra ) . '" data-hv-hud><span class="hv-hud-ava">🦉<b>1</b></span><div class="hv-hud-who"><strong>Bạn nhỏ</strong><span>Cấp 1 · Tân binh</span><div class="hv-xpbar"><i style="width:0"></i><em>0 XP</em></div></div></div>';
}

/**
 * [hv_hero] – màn hình chào kiểu trò chơi: tiêu đề, nút bắt đầu, thẻ nhân vật.
 *
 * @return string
 */
function hv_sc_hero() {
	$title = esc_html( hv_opt( 'hero_title' ) );
	$title = preg_replace( '/\*(.+?)\*/u', '<em>$1</em>', $title );
	ob_start();
	?>
	<section class="hv-hero">
		<div class="hv-hero-sea" aria-hidden="true"></div>
		<div class="hv-wrap hv-hero-in">
			<div class="hv-hero-copy">
				<span class="hv-kicker">🏝️ Hành trình phá đảo tri thức</span>
				<h1><?php echo wp_kses( $title, array( 'em' => array() ) ); ?></h1>
				<p class="hv-hero-lead"><?php echo esc_html( hv_opt( 'hero_lead' ) ); ?></p>
				<div class="hv-hero-btns">
					<a class="hv-btn hv-btn-accent hv-btn-big" href="#ban-do">▶ Bắt đầu phá đảo</a>
					<a class="hv-btn hv-btn-big hv-btn-dark" href="<?php echo esc_url( hv_games_url() ); ?>">🎮 Khu trò chơi</a>
				</div>
				<ul class="hv-hero-facts">
					<li><b>5</b> hòn đảo</li>
					<li><b><?php echo (int) array_sum( array_map( 'count', hv_lessons_by_grade() ) ); ?></b> màn chơi</li>
					<li><b>7</b> trò chơi</li>
				</ul>
			</div>
			<div class="hv-hero-side">
				<div class="hv-player-card">
					<div class="hv-pc-top">
						<div class="hv-owl" aria-hidden="true"></div>
						<div class="hv-bubble"><?php echo esc_html( hv_opt( 'hero_bubble' ) ); ?></div>
					</div>
					<?php echo hv_hud_html( 'is-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML tĩnh. ?>
					<?php echo hv_sc_name_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML tĩnh. ?>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'hv_hero', 'hv_sc_hero' );

/**
 * [hv_world] – bản đồ 5 hòn đảo (5 lớp), có tiến độ phá đảo.
 *
 * @return string
 */
function hv_sc_world() {
	$by   = hv_lessons_by_grade();
	$html = '<div class="hv-world" id="ban-do"><ol class="hv-islands">';
	foreach ( hv_grades() as $n => $g ) {
		$slugs = isset( $by[ $n ] ) ? $by[ $n ] : array();
		$html .= '<li><a class="hv-island g' . (int) $n . '" style="--c:' . esc_attr( $g[3] ) . '" href="' . esc_url( hv_grade_link( $n ) ) . '" data-hv-island="' . esc_attr( implode( ',', $slugs ) ) . '">'
			. '<span class="hv-isl-art">' . hv_island_svg( $n ) . '</span>'
			. '<span class="hv-isl-tag">' . esc_html( $g[0] ) . '</span>'
			. '<strong>' . esc_html( $g[5] ) . '</strong>'
			. '<small>' . esc_html( $g[4] ) . '</small>'
			. '<span class="hv-isl-bar"><i></i></span><span class="hv-isl-count">0/' . count( $slugs ) . ' màn</span>'
			. '<span class="hv-isl-go">Lên đảo ▶</span></a></li>';
	}
	return $html . '</ol></div>';
}
add_shortcode( 'hv_world', 'hv_sc_world' );
add_shortcode( 'hv_grades', 'hv_sc_world' );

/**
 * [hv_quests] – nhiệm vụ hôm nay.
 *
 * @return string
 */
function hv_sc_quests() {
	return '<div class="hv-quests"><h3>📜 Nhiệm vụ hôm nay</h3><ul data-hv-quests></ul><p>Mỗi ngày học một chút để giữ ngọn lửa 🔥 luôn cháy nhé!</p></div>';
}
add_shortcode( 'hv_quests', 'hv_sc_quests' );

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
	list( $grade, $subject )        = hv_lesson_terms( $p->ID );
	list( $sname, $sicon, $scolor ) = hv_subject_info( $subject );
	$icon                           = hv_lesson_meta( 'icon', $p->ID );
	$meta                           = trim( ( $show_grade && $grade ? 'Lớp ' . $grade . ' · ' : '' ) . $sname );
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
 * [hv_parent_steps] – mỗi màn chơi gồm những bước nào.
 *
 * @return string
 */
function hv_sc_parent_steps() {
	$steps = array(
		array( '🦉', 'Mở màn', 'Cú Bông kể một tình huống gần gũi, khơi gợi tò mò, được đọc to bằng giọng tiếng Việt.' ),
		array( '🔍', 'Khám phá', 'Kiến thức chia thành từng thẻ ngắn, có ví dụ, bảng, mẹo nhớ nhanh – học từng chút một.' ),
		array( '📜', 'Bí kíp', 'Tóm tắt điều cần ghi nhớ, giúp con ôn lại trước khi “đánh trùm”.' ),
		array( '⚔️', 'Đánh trùm', 'Câu hỏi luyện tập: đúng nhiều được 3 sao, XP, lên cấp. Cuối mỗi môn có trùm cuối với 3 tim.' ),
		array( '🏡', 'Nhiệm vụ ngoài đời', 'Một việc nhỏ con làm cùng gia đình để dùng ngay điều vừa học.' ),
	);
	$html  = '<div class="hv-steps">';
	foreach ( $steps as $s ) {
		$html .= '<div class="hv-step-card"><i aria-hidden="true">' . esc_html( $s[0] ) . '</i><h3>' . esc_html( $s[1] ) . '</h3><p>' . esc_html( $s[2] ) . '</p></div>';
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
	$page = get_page_by_path( 'goc-phu-huynh' );
	$link = $a['link'] ? $a['link'] : ( $page ? get_permalink( $page ) : home_url( '/' ) );
	return '<div class="hv-band"><div><h2>' . esc_html( $a['title'] ) . '</h2><p>' . esc_html( $a['text'] ) . '</p></div><a class="hv-btn hv-btn-accent hv-btn-big" href="' . esc_url( $link ) . '">' . esc_html( $a['button'] ) . '</a></div>';
}
add_shortcode( 'hv_band', 'hv_sc_band' );

/**
 * [hv_section title="" sub="" bg="" id=""]…[/hv_section] – khung mục có tiêu đề.
 *
 * @param array  $atts    Thuộc tính.
 * @param string $content Nội dung.
 * @return string
 */
function hv_sc_section( $atts, $content = '' ) {
	$a = shortcode_atts( array( 'title' => '', 'sub' => '', 'bg' => '', 'id' => '', 'class' => '' ), $atts );
	$h = '<section class="hv-section ' . esc_attr( $a['class'] ) . '"' . ( $a['id'] ? ' id="' . esc_attr( $a['id'] ) . '"' : '' ) . ( $a['bg'] ? ' style="background:' . esc_attr( $a['bg'] ) . '"' : '' ) . '><div class="hv-wrap">';
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
 * [hv_pagehead title="" lead="" color=""] – dải tiêu đề màu cho trang thường.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function hv_sc_pagehead( $atts ) {
	$a = shortcode_atts( array( 'title' => get_the_title(), 'lead' => '', 'color' => hv_opt( 'color_primary' ), 'icon' => '' ), $atts );
	ob_start();
	get_template_part(
		'template-parts/hocvui/pagehead',
		null,
		array( 'color' => $a['color'], 'crumbs' => array( array( $a['title'], '' ) ), 'title' => $a['title'], 'lead' => $a['lead'], 'art' => $a['icon'] )
	);
	return ob_get_clean();
}
add_shortcode( 'hv_pagehead', 'hv_sc_pagehead' );

/**
 * [hv_profile] – hồ sơ nhân vật: cấp độ, ảnh đại diện, huy hiệu, album nhãn dán.
 *
 * @return string
 */
function hv_sc_profile() {
	return '<div class="hv-profile">'
		. '<div class="hv-box hv-profile-main"><div class="hv-owl" aria-hidden="true"></div>' . hv_hud_html( 'is-card' ) . hv_sc_name_form()
		. '<h3>Chọn nhân vật của em</h3><div class="hv-avatars" data-hv-avatars></div></div>'
		. '<div class="hv-box"><h2>🏅 Huy hiệu</h2><div class="hv-badges" data-hv-badges></div></div>'
		. hv_sc_quests()
		. hv_sc_album()
		. '</div>';
}
add_shortcode( 'hv_profile', 'hv_sc_profile' );

/**
 * Thanh nhân vật dưới header (trừ trang chủ – đã có thẻ nhân vật ở banner).
 */
function hv_render_hudbar() {
	if ( is_front_page() ) {
		return;
	}
	echo '<div class="hv-hudbar"><div class="hv-wrap">' . hv_hud_html() . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML tĩnh.
}
add_action( 'flatsome_after_header', 'hv_render_hudbar' );
