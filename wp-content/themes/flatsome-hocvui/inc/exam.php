<?php
/**
 * Phòng luyện thi: bé luyện đề nhiều câu có đồng hồ tổng giống các cuộc thi trực tuyến
 * (5 vòng: Khởi động → Cấp trường → Cấp huyện → Cấp tỉnh → Cấp quốc gia), nộp bài, xem lại lời giải,
 * in giấy chứng nhận. Đề trộn câu hỏi tự sinh với câu hỏi luyện tập của các bài học.
 *
 * Shortcode: [hv_exam] (trang Luyện thi) và [hv_exam_teaser] (khối giới thiệu trên trang chủ).
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ngân hàng câu hỏi từ các bài học, gọn để nhúng vào trang:
 * { L: [ [tên bài, link] ], B: { lớp: { môn: [ [câu, đáp án, [sai], giải thích, số bài] ] } } }.
 *
 * @return array
 */
function hv_exam_banks() {
	$lessons = array();
	$banks   = array();
	foreach ( get_posts( array( 'post_type' => 'bai_hoc', 'posts_per_page' => 500, 'no_found_rows' => true ) ) as $p ) {
		list( $g, $sub ) = hv_lesson_terms( $p->ID );
		if ( ! $g || ! $sub ) {
			continue;
		}
		$quiz = hv_quiz_data( $p->ID );
		if ( ! $quiz ) {
			continue;
		}
		$li        = count( $lessons );
		$lessons[] = array( get_the_title( $p ), get_permalink( $p ) );
		foreach ( $quiz as $q ) {
			$banks[ $g ][ $sub->slug ][] = array( $q['q'], $q['a'], $q['w'], isset( $q['e'] ) ? $q['e'] : '', $li );
		}
	}
	return array( 'L' => $lessons, 'B' => $banks );
}

/**
 * [hv_exam] – phòng luyện thi.
 *
 * @return string
 */
function hv_sc_exam() {
	return '<div class="hv-exam" data-hv-exam><noscript><p class="hv-box">Phòng luyện thi cần bật JavaScript.</p></noscript>'
		. '<script type="application/json" class="hv-exam-data">' . wp_json_encode( hv_exam_banks(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script></div>';
}
add_shortcode( 'hv_exam', 'hv_sc_exam' );

/**
 * [hv_exam_teaser] – giới thiệu phòng luyện thi (trang chủ).
 *
 * @return string
 */
function hv_sc_exam_teaser() {
	$url    = hv_page_url( 'luyen-thi' );
	$rounds = array( array( 'flag', 'Khởi động', '10 câu' ), array( 'house', 'Cấp trường', '15 câu' ), array( 'map', 'Cấp huyện', '20 câu' ), array( 'medal', 'Cấp tỉnh', '25 câu' ), array( 'crown', 'Cấp quốc gia', '30 câu' ) );
	$h      = '<div class="hv-exam-teaser"><div class="hv-et-copy"><span class="hv-kicker">🏆 Phòng luyện thi</span><h3>Luyện đề như thi thật – tự tin trước ngày thi</h3>'
		. '<p>Đề Toán, Tiếng Việt, Tiếng Anh, Kiến thức tổng hợp từ lớp 1 đến lớp 5. Có đồng hồ đếm ngược, chuyển câu, đánh dấu câu, nộp bài, chấm điểm ngay và xem lời giải. Vượt qua mỗi vòng để mở vòng tiếp theo!</p>'
		. ( $url ? '<a class="hv-btn hv-btn-accent hv-btn-big" href="' . esc_url( $url ) . '">Vào phòng thi ▶</a>' : '' ) . '</div><ol class="hv-et-rounds">';
	foreach ( $rounds as $i => $r ) {
		$h .= '<li style="--i:' . (int) $i . '">' . hv_icon3d( $r[0], 40 ) . '<b>' . esc_html( $r[1] ) . '</b><span>' . esc_html( $r[2] ) . '</span></li>';
	}
	return $h . '</ol></div>';
}
add_shortcode( 'hv_exam_teaser', 'hv_sc_exam_teaser' );
