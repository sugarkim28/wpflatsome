<?php
/**
 * Học theo chủ đề – cách tổ chức kiến thức theo lứa tuổi như các hệ thống tự học trực tuyến nhà trường đang dùng:
 * Môn → Lớp → Học kỳ → Chủ đề → Chủ điểm (mỗi bài học là một chủ điểm, mã kiểu I.1.2).
 * Mỗi chủ điểm: Xem lý thuyết (trang bài học) + Thực hành tích điểm 0 → 100 để "thành thạo".
 * Mỗi chủ đề có bài kiểm tra; mỗi học kỳ có bài kiểm tra cuối kỳ; có Đánh giá năng lực đầu vào.
 *
 * Shortcode: [hv_learn] (trang "Học tập", đường dẫn hoc-tap).
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * Học kỳ mặc định của bài Toán theo sách Kết nối tri thức với cuộc sống (theo tên bài).
 *
 * @return array [ lớp, biểu thức, học kỳ ]
 */
function hv_math_semesters() {
	return array(
		array( 2, '/có nhớ|ki-lô-gam|lít|đồng hồ|giờ/iu', 1 ),
		array( 2, '/nhân|chia|1000/iu', 2 ),
		array( 3, '/bảng nhân|bảng chia|chia có dư|chia hết/iu', 1 ),
		array( 3, '/10 000|100 000|la mã|chu vi|diện tích/iu', 2 ),
		array( 4, '/nhiều chữ số|chẵn|lẻ|tổng và hiệu|góc|trung bình/iu', 1 ),
		array( 4, '/phân số|biểu đồ|thống kê/iu', 2 ),
		array( 5, '/thập phân|tam giác|hình thang|hình tròn/iu', 1 ),
		array( 5, '/phần trăm|tỉ số|thể tích|vận tốc|quãng đường|chuyển động/iu', 2 ),
	);
}

/**
 * Học kỳ của một bài: ô "Học kỳ" trong trang sửa bài → quy tắc môn Toán → nửa đầu/nửa sau các bài cùng môn.
 *
 * @param WP_Post $post  Bài.
 * @param int     $grade Lớp.
 * @param string  $sub   Slug môn.
 * @param int     $idx   Thứ tự trong môn (0…).
 * @param int     $count Số bài cùng môn, cùng lớp.
 * @return int 1|2
 */
function hv_lesson_semester( $post, $grade, $sub, $idx, $count ) {
	$set = hv_lesson_meta( 'hoc_ky', $post->ID );
	if ( '1' === $set || '2' === $set ) {
		return (int) $set;
	}
	$topic = hv_lesson_meta( 'chu_de', $post->ID );
	if ( 'toan' === $sub ) {
		if ( 1 === $grade && preg_match( '/Chủ đề (\d+)/u', $topic, $m ) ) {
			return (int) $m[1] <= 5 ? 1 : 2;
		}
		foreach ( hv_math_semesters() as $r ) {
			if ( $r[0] === $grade && preg_match( $r[1], $post->post_title ) ) {
				return $r[2];
			}
		}
	}
	return $idx < ceil( $count / 2 ) ? 1 : 2;
}

/**
 * Dữ liệu trang Học tập: môn, và từng chủ điểm (bài học) với chủ đề, học kỳ, câu hỏi.
 *
 * @return array
 */
function hv_learn_data() {
	$posts = get_posts(
		array(
			'post_type'      => 'bai_hoc',
			'posts_per_page' => 500,
			'no_found_rows'  => true,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
	$group = array();
	foreach ( $posts as $p ) {
		list( $g, $sub ) = hv_lesson_terms( $p->ID );
		if ( $g && $sub ) {
			$group[ $g . '|' . $sub->slug ][] = $p;
		}
	}
	$lessons = array();
	$subs    = array();
	foreach ( $group as $key => $list ) {
		list( $g, $slug ) = explode( '|', $key );
		$g                = (int) $g;
		$info             = hv_subject_info( get_term_by( 'slug', $slug, 'mon_hoc' ) );
		$subs[ $slug ]    = array( 'name' => $info[0], 'ico' => $info[3], 'c' => $info[2] );
		foreach ( $list as $i => $p ) {
			$icon      = hv_lesson_meta( 'icon', $p->ID );
			$lessons[] = array(
				'id'    => $p->post_name,
				'g'     => $g,
				's'     => $slug,
				't'     => get_the_title( $p ),
				'u'     => get_permalink( $p ),
				'topic' => hv_lesson_meta( 'chu_de', $p->ID ) ? hv_lesson_meta( 'chu_de', $p->ID ) : get_the_title( $p ),
				'sem'   => hv_lesson_semester( $p, $g, $slug, $i, count( $list ) ),
				'o'     => $i,
				'ico'   => $icon ? $icon : $info[1],
				'q'     => array_map(
					function ( $q ) {
						return array( $q['q'], $q['a'], $q['w'], isset( $q['e'] ) ? $q['e'] : '' );
					},
					hv_quiz_data( $p->ID )
				),
			);
		}
	}
	// Thứ tự môn như danh sách môn của theme.
	$order = array_keys( hv_subjects() );
	uksort(
		$subs,
		function ( $a, $b ) use ( $order ) {
			return (int) array_search( $a, $order, true ) - (int) array_search( $b, $order, true );
		}
	);
	return array( 'subjects' => $subs, 'lessons' => $lessons );
}

/**
 * [hv_learn] – trang Học tập: chọn môn, lớp; học kỳ, chủ đề, chủ điểm; gợi ý; báo cáo; thực hành; kiểm tra.
 *
 * @return string
 */
function hv_sc_learn() {
	return '<div class="hv-learn" data-hv-learn><noscript><p class="hv-box">Trang Học tập cần bật JavaScript.</p></noscript>'
		. '<script type="application/json" class="hv-learn-data">' . wp_json_encode( hv_learn_data(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script></div>';
}
add_shortcode( 'hv_learn', 'hv_sc_learn' );

/**
 * Link trang Học tập (mở thẳng phần thực hành một chủ điểm nếu truyền slug).
 *
 * @param string $practice Slug bài cần thực hành.
 * @return string
 */
function hv_learn_url( $practice = '' ) {
	$url = hv_page_url( 'hoc-tap' );
	if ( ! $url ) {
		return '';
	}
	return $practice ? add_query_arg( 'thuc-hanh', $practice, $url ) : $url;
}
