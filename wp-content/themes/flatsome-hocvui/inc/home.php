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
 * Hòn đảo bay của 1 lớp (cỏ xanh, đá nâu, cờ số lớp, ảnh 3D đặc trưng).
 *
 * @param int $grade Lớp.
 * @return string
 */
function hv_island_svg( $grade ) {
	$g = hv_grades();
	$g = isset( $g[ $grade ] ) ? $g[ $grade ] : $g[1];
	$u = 'hvi' . (int) $grade;
	return '<span class="hv-fly" style="--c:' . esc_attr( $g[3] ) . '">'
		. '<svg class="hv-isl-svg" viewBox="0 0 200 170" aria-hidden="true"><defs>'
		. '<linearGradient id="' . $u . 'g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#a6ec6a"/><stop offset="1" stop-color="#4cb648"/></linearGradient>'
		. '<linearGradient id="' . $u . 'r" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c08a57"/><stop offset="1" stop-color="#6b4227"/></linearGradient></defs>'
		. '<path d="M16 62 C40 104 62 128 100 166 C138 128 160 104 184 62 Z" fill="url(#' . $u . 'r)"/>'
		. '<path d="M60 92 L80 120 M120 98 L104 132 M140 84 L150 104" stroke="#5a3620" stroke-width="3" stroke-linecap="round" opacity=".45"/>'
		. '<ellipse cx="100" cy="60" rx="88" ry="26" fill="#8a5a33"/>'
		. '<ellipse cx="100" cy="54" rx="90" ry="26" fill="url(#' . $u . 'g)"/>'
		. '<ellipse cx="78" cy="46" rx="44" ry="9" fill="#fff" opacity=".22"/>'
		. '<path d="M150 50 V12" stroke="#5a3620" stroke-width="3.5" stroke-linecap="round"/>'
		. '<path d="M150 12 L180 21 L150 30 Z" fill="' . esc_attr( $g[3] ) . '"/>'
		. '<text x="161" y="25.5" font-size="11" font-weight="900" fill="#fff" text-anchor="middle" font-family="Nunito,Arial">' . (int) $grade . '</text>'
		. '</svg>' . hv_icon3d( $g[7], 96, '', 'hv-fly-icon' ) . '</span>';
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
 * [hv_hero] – banner trời xanh mây trắng: tiêu đề, nút bắt đầu, Cú Bông và đồ vật 3D bay quanh;
 * thẻ nhân vật + số liệu nổi lên mép dưới banner.
 *
 * @return string
 */
function hv_sc_hero() {
	$title = esc_html( hv_opt( 'hero_title' ) );
	$title = preg_replace( '/\*(.+?)\*/u', '<em>$1</em>', $title );
	$count = (int) array_sum( array_map( 'count', hv_lessons_by_grade() ) );
	ob_start();
	?>
	<section class="hv-hero">
		<div class="hv-wrap hv-hero-in">
			<div class="hv-hero-copy">
				<span class="hv-kicker">🏝️ Hành trình phá đảo tri thức</span>
				<h1><?php echo wp_kses( $title, array( 'em' => array() ) ); ?></h1>
				<p class="hv-hero-lead"><?php echo esc_html( hv_opt( 'hero_lead' ) ); ?></p>
				<div class="hv-hero-btns">
					<a class="hv-btn hv-btn-big" href="#ban-do">Bắt đầu phá đảo <span aria-hidden="true">›</span></a>
					<a class="hv-play-link" href="<?php echo esc_url( hv_games_url() ); ?>"><span aria-hidden="true">▶</span> Vào khu trò chơi</a>
				</div>
			</div>
			<div class="hv-hero-art">
				<div class="hv-bubble"><?php echo esc_html( hv_opt( 'hero_bubble' ) ); ?></div>
				<img class="hv-hero-owl hv-owl-img" src="<?php echo esc_url( HV_URI . '/assets/img/mascot.svg' ); ?>" width="340" height="340" alt="Bạn Cú Bông – linh vật Học Vui" fetchpriority="high">
				<?php
				foreach ( array( 'book' => 'f1', 'star' => 'f2', 'abacus' => 'f3', 'cap' => 'f4', 'rocket' => 'f5' ) as $hv_k => $hv_c ) {
					echo hv_icon3d( $hv_k, 72, '', 'hv-float ' . $hv_c ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hàm tự escape.
				}
				?>
			</div>
		</div>
		<div class="hv-clouds" aria-hidden="true"></div>
	</section>
	<div class="hv-wrap hv-hero-panel">
		<div class="hv-panel-player"><?php echo hv_hud_html( 'is-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML tĩnh. ?><?php echo hv_sc_name_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML tĩnh. ?></div>
		<ul class="hv-panel-stats">
			<li><?php echo hv_icon3d( 'island', 52 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><b>5 hòn đảo</b><span>5 lớp học, từ lớp 1 đến lớp 5</span></li>
			<li><?php echo hv_icon3d( 'book', 52 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><b><?php echo (int) $count; ?> màn chơi</b><span>Bám sách Kết nối tri thức</span></li>
			<li><?php echo hv_icon3d( 'game', 52 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><b>7 trò chơi</b><span>Câu hỏi mới mỗi lượt chơi</span></li>
			<li><?php echo hv_icon3d( 'heart', 52 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><b>Miễn phí</b><span>Không quảng cáo, không đăng ký</span></li>
		</ul>
	</div>
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
			. '<span class="hv-isl-card"><span class="hv-isl-tag">' . esc_html( $g[0] ) . '</span>'
			. '<strong>' . esc_html( $g[5] ) . '</strong>'
			. '<small>' . esc_html( $g[4] ) . '</small>'
			. '<span class="hv-isl-bar"><i></i></span><span class="hv-isl-count">0/' . count( $slugs ) . ' màn</span>'
			. '<span class="hv-isl-go">Lên đảo ›</span></span></a></li>';
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
	return '<div class="hv-quests"><h3>' . hv_icon3d( 'target', 32 ) . ' Nhiệm vụ hôm nay</h3><ul data-hv-quests></ul><p>Mỗi ngày học một chút để giữ ngọn lửa 🔥 luôn cháy nhé!</p></div>';
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
		$html .= '<a class="hv-subject" style="--c:' . esc_attr( $s[2] ) . '" href="' . esc_url( hv_subject_link( $slug, $grade ) ) . '"><strong>' . esc_html( $s[0] ) . '</strong>' . hv_icon3d( $s[4], 84 ) . '<span class="hv-pill">' . esc_html( $range ) . '</span></a>';
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
		array( 'speech', 'Mở màn', 'Cú Bông kể một tình huống gần gũi, khơi gợi tò mò, được đọc to bằng giọng tiếng Việt.' ),
		array( 'search', 'Khám phá', 'Kiến thức chia thành từng thẻ ngắn, có ví dụ, bảng, mẹo nhớ nhanh – học từng chút một.' ),
		array( 'scroll', 'Bí kíp', 'Tóm tắt điều cần ghi nhớ, giúp con ôn lại trước khi “đánh trùm”.' ),
		array( 'swords', 'Đánh trùm', 'Câu hỏi luyện tập: đúng nhiều được 3 sao, XP, lên cấp. Cuối mỗi môn có trùm cuối với 3 tim.' ),
		array( 'house', 'Nhiệm vụ ngoài đời', 'Một việc nhỏ con làm cùng gia đình để dùng ngay điều vừa học.' ),
	);
	$html  = '<ol class="hv-steps">';
	foreach ( $steps as $i => $s ) {
		$html .= '<li class="hv-step-card"><b class="hv-hex">' . ( $i + 1 ) . '</b><h3>' . esc_html( $s[1] ) . '</h3><span class="hv-arch">' . hv_icon3d( $s[0], 72 ) . '<span>' . esc_html( $s[2] ) . '</span></span></li>';
	}
	return $html . '</ol>';
}
add_shortcode( 'hv_parent_steps', 'hv_sc_parent_steps' );

/**
 * [hv_why] – Vì sao chọn Học Vui (tab Học sinh / Phụ huynh / Giáo viên).
 *
 * @return string
 */
function hv_sc_why() {
	$tabs = array(
		'Học sinh'  => array(
			array( 'party', 'Học như chơi game', 'Mỗi bài là một màn chơi có chuyện kể, sao, XP, trùm cuối.' ),
			array( 'speech', 'Cú Bông đọc cho nghe', 'Giọng tiếng Việt ấm áp, chậm rãi – bé lớp 1 chưa đọc thạo vẫn tự học được.' ),
			array( 'trophy', 'Huy hiệu, nhãn dán', 'Sưu tập 20 nhãn dán, 8 huy hiệu, giữ chuỗi ngày học 🔥.' ),
			array( 'game', '7 trò chơi tự sinh câu hỏi', 'Không bao giờ hết câu hỏi – mỗi lượt chơi là một đề mới.' ),
		),
		'Phụ huynh' => array(
			array( 'book', 'Đúng chương trình', 'Theo Chương trình GDPT 2018, bộ sách Kết nối tri thức với cuộc sống dùng chung toàn quốc.' ),
			array( 'clock', '15 phút mỗi ngày', 'Màn chơi ngắn, vừa sức, có nhiệm vụ nhỏ làm cùng gia đình.' ),
			array( 'heart', 'An toàn cho trẻ', 'Không quảng cáo, không đăng ký, không thu thập dữ liệu của con.' ),
			array( 'gem', 'Hoàn toàn miễn phí', 'Học và chơi không giới hạn trên máy tính, điện thoại, máy tính bảng.' ),
		),
		'Giáo viên' => array(
			array( 'scroll', 'Tự soạn bài dễ dàng', 'Thêm bài, câu hỏi luyện tập ngay trong trang quản trị, mỗi dòng một câu.' ),
			array( 'target', 'Bám mục tiêu bài học', 'Mỗi màn ghi rõ mục tiêu, bí kíp ghi nhớ và câu hỏi kiểm tra.' ),
			array( 'cap', 'Dùng trên lớp', 'Chiếu trò chơi lên máy chiếu, cả lớp cùng thi tính nhẩm, đố vui.' ),
			array( 'map', 'Theo chủ đề chương trình', 'Bài sắp xếp theo lớp, môn và mạch kiến thức, dễ tra cứu.' ),
		),
	);
	$id   = 'hv-why-' . wp_rand( 100, 999 );
	$html = '<div class="hv-why" data-hv-tabs><div class="hv-tabbar" role="tablist">';
	$i    = 0;
	foreach ( array_keys( $tabs ) as $name ) {
		$html .= '<button type="button" role="tab" id="' . esc_attr( $id . '-t' . $i ) . '" aria-controls="' . esc_attr( $id . '-p' . $i ) . '" aria-selected="' . ( 0 === $i ? 'true' : 'false' ) . '"' . ( $i ? ' tabindex="-1"' : '' ) . '>' . esc_html( $name ) . '</button>';
		$i++;
	}
	$html .= '</div><div class="hv-why-body"><div class="hv-why-panels">';
	$i     = 0;
	foreach ( $tabs as $items ) {
		$html .= '<div class="hv-why-grid" role="tabpanel" id="' . esc_attr( $id . '-p' . $i ) . '" aria-labelledby="' . esc_attr( $id . '-t' . $i ) . '"' . ( $i ? ' hidden' : '' ) . '>';
		foreach ( $items as $it ) {
			$html .= '<div class="hv-why-item">' . hv_icon3d( $it[0], 48 ) . '<h3>' . esc_html( $it[1] ) . '</h3><p>' . esc_html( $it[2] ) . '</p></div>';
		}
		$html .= '</div>';
		$i++;
	}
	return $html . '</div><img class="hv-why-owl hv-owl-img" src="' . esc_url( HV_URI . '/assets/img/mascot.svg' ) . '" width="260" height="260" alt="" loading="lazy"></div></div>';
}
add_shortcode( 'hv_why', 'hv_sc_why' );

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
	return '<div class="hv-band">' . hv_icon3d( 'house', 96, '', 'hv-band-ico' ) . '<div><h2>' . esc_html( $a['title'] ) . '</h2><p>' . esc_html( $a['text'] ) . '</p></div><a class="hv-btn hv-btn-white hv-btn-big" href="' . esc_url( $link ) . '">' . esc_html( $a['button'] ) . ' ›</a></div>';
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
		. '<div class="hv-box hv-profile-main"><img class="hv-owl-img" src="' . esc_url( HV_URI . '/assets/img/mascot.svg' ) . '" width="120" height="120" alt="">' . hv_hud_html( 'is-card' ) . hv_sc_name_form()
		. '<h3>Chọn nhân vật của em</h3><div class="hv-avatars" data-hv-avatars></div></div>'
		. '<div class="hv-box"><h2>' . hv_icon3d( 'medal', 36 ) . ' Huy hiệu</h2><div class="hv-badges" data-hv-badges></div></div>'
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

/**
 * Cú Bông trợ giúp nổi góc phải: chào bé, đọc to, mở cài đặt giọng đọc.
 */
function hv_render_helper() {
	echo '<div class="hv-helper" data-hv-helper><div class="hv-helper-pop" hidden><p data-hv-helper-text>Chào em! Mình là Cú Bông.</p><div class="hv-helper-acts"><button type="button" class="hv-btn hv-btn-sm" data-hv-helper-say>🔈 Nghe</button><button type="button" class="hv-btn hv-btn-sm hv-btn-white" data-hv-settings>⚙️ Giọng đọc</button></div></div>'
		. '<button type="button" class="hv-helper-btn" aria-label="Hỏi Cú Bông" data-hv-helper-toggle><img src="' . esc_url( HV_URI . '/assets/img/3d/owl.webp' ) . '" width="64" height="64" alt=""></button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape.
}
add_action( 'wp_footer', 'hv_render_helper' );
