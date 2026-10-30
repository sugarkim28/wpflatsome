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

/**
 * Thẻ chia sẻ Facebook/Zalo (khi không dùng plugin SEO): ảnh mặc định là ảnh chia sẻ Học Vui 1200×630.
 */
function hv_open_graph() {
	if ( hv_has_seo_plugin() ) {
		return;
	}
	$title = wp_get_document_title();
	$desc  = is_singular() ? wp_strip_all_tags( get_the_excerpt() ) : hv_opt( 'hero_lead' );
	$image = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : HV_URI . '/assets/img/brand/og-1200x630.jpg';
	$url   = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );
	$tags  = array(
		'og:type'        => is_singular( 'bai_hoc' ) ? 'article' : 'website',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:locale'      => 'vi_VN',
		'og:title'       => $title,
		'og:description' => wp_html_excerpt( $desc, 200, '…' ),
		'og:url'         => $url,
		'og:image'       => $image,
	);
	foreach ( $tags as $k => $v ) {
		echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . "\">\n";
	}
	if ( ! has_post_thumbnail() || ! is_singular() ) {
		echo '<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}
add_action( 'wp_head', 'hv_open_graph', 5 );

/**
 * Dữ liệu tổ chức (EducationalOrganization) + WebSite trên trang chủ – giúp Google hiện logo, số điện thoại.
 */
function hv_org_schema() {
	if ( ! is_front_page() ) {
		return;
	}
	$same = array_values( array_filter( array( hv_opt( 'facebook' ), hv_opt( 'youtube' ), hv_opt( 'tiktok' ) ) ) );
	$org  = array(
		'@type'        => 'EducationalOrganization',
		'@id'          => home_url( '/#org' ),
		'name'         => get_bloginfo( 'name' ),
		'alternateName' => hv_opt( 'org_name' ),
		'url'          => home_url( '/' ),
		'logo'         => HV_URI . '/assets/img/brand/hocvui-icon-512.png',
		'image'        => HV_URI . '/assets/img/brand/og-1200x630.jpg',
		'slogan'       => hv_opt( 'org_slogan' ),
		'contactPoint' => array(
			'@type'             => 'ContactPoint',
			'telephone'         => hv_tel( hv_opt( 'hotline' ) ),
			'contactType'       => 'customer support',
			'areaServed'        => 'VN',
			'availableLanguage' => 'vi',
		),
	);
	if ( $same ) {
		$org['sameAs'] = $same;
	}
	if ( hv_opt( 'email' ) ) {
		$org['email'] = hv_opt( 'email' );
	}
	if ( hv_opt( 'address' ) ) {
		$org['address'] = hv_opt( 'address' );
	}
	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			$org,
			array(
				'@type'     => 'WebSite',
				'url'       => home_url( '/' ),
				'name'      => get_bloginfo( 'name' ),
				'inLanguage' => 'vi',
				'publisher' => array( '@id' => home_url( '/#org' ) ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => home_url( '/?s={search_term_string}' ),
					'query-input' => 'required name=search_term_string',
				),
			),
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}
add_action( 'wp_head', 'hv_org_schema', 21 );
