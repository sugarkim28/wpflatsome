<?php
/**
 * SEO: mô tả trang (khi không dùng Rank Math/Yoast) và dữ liệu cấu trúc LearningResource + Quiz cho bài học.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * Có plugin SEO đang quản lý tiêu đề/mô tả không.
 *
 * @return bool
 */
function hv_has_seo_plugin() {
	return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/**
 * Thẻ mô tả cho bài học, lớp, môn.
 */
function hv_meta_description() {
	if ( hv_has_seo_plugin() ) {
		return;
	}
	$desc = '';
	if ( is_singular( 'bai_hoc' ) ) {
		list( $grade, $subject ) = hv_lesson_terms( get_the_ID() );
		$desc                    = ( $grade ? 'Lớp ' . $grade . ' – ' : '' ) . ( $subject ? $subject->name . ': ' : '' ) . get_the_excerpt() . ' Có câu hỏi luyện tập và trò chơi ôn bài.';
	} elseif ( is_tax( 'lop' ) ) {
		$desc = 'Bài học ' . single_term_title( '', false ) . ' theo chương trình sách giáo khoa mới: Toán, Tiếng Việt, Tiếng Anh, Khoa học… kèm luyện tập và trò chơi học tập.';
	} elseif ( is_tax( 'mon_hoc' ) ) {
		$desc = 'Bài học môn ' . single_term_title( '', false ) . ' lớp 1 đến lớp 5 theo chương trình GDPT 2018, có câu hỏi luyện tập và trò chơi.';
	} elseif ( is_front_page() ) {
		$desc = hv_opt( 'hero_lead' );
	}
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_html_excerpt( wp_strip_all_tags( $desc ), 155, '…' ) ) . "\">\n";
	}
}
add_action( 'wp_head', 'hv_meta_description', 1 );

/**
 * JSON-LD cho bài học.
 */
function hv_lesson_schema() {
	if ( ! is_singular( 'bai_hoc' ) ) {
		return;
	}
	$id                      = get_the_ID();
	list( $grade, $subject ) = hv_lesson_terms( $id );
	$data                    = array(
		'@context'             => 'https://schema.org',
		'@type'                => 'LearningResource',
		'name'                 => get_the_title(),
		'description'          => wp_strip_all_tags( get_the_excerpt() ),
		'url'                  => get_permalink(),
		'inLanguage'           => 'vi',
		'isAccessibleForFree'  => true,
		'learningResourceType' => 'Bài học',
		'educationalLevel'     => $grade ? 'Lớp ' . $grade : 'Tiểu học',
		'audience'             => array( '@type' => 'EducationalAudience', 'educationalRole' => 'student' ),
		'dateModified'         => get_the_modified_date( 'c' ),
		'publisher'            => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
	);
	if ( $subject ) {
		$data['about'] = $subject->name;
	}
	$topic = hv_lesson_meta( 'chu_de', $id );
	if ( $topic ) {
		$data['teaches'] = $topic;
	}
	$quiz = hv_quiz_data( $id );
	if ( $quiz ) {
		$parts = array();
		foreach ( $quiz as $q ) {
			$parts[] = array(
				'@type'          => 'Question',
				'name'           => $q['q'],
				'eduQuestionType' => 'Multiple choice',
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q['a'] ),
			);
		}
		$data['hasPart'] = array( '@type' => 'Quiz', 'name' => 'Luyện tập: ' . get_the_title(), 'hasPart' => $parts );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}
add_action( 'wp_head', 'hv_lesson_schema', 20 );
