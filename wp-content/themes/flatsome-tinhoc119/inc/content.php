<?php
/**
 * Thành phần nội dung: mục lục, FAQ, dịch vụ liên quan, tác giả, bài liên quan.
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chuyển tiêu đề thành id neo (giữ dạng không dấu, đọc được).
 *
 * @param string $text  Tiêu đề.
 * @param array  $used  Id đã dùng.
 * @return string
 */
function th119t_anchor( $text, &$used ) {
	$id = sanitize_title( remove_accents( wp_strip_all_tags( $text ) ) );
	$id = $id ? $id : 'muc';
	$base = $id;
	$i    = 2;
	while ( isset( $used[ $id ] ) ) {
		$id = $base . '-' . $i++;
	}
	$used[ $id ] = true;
	return $id;
}

/**
 * Gắn id cho H2/H3 trong nội dung và trả về danh sách mục lục.
 *
 * @param string $html Nội dung đã qua the_content.
 * @return array [html, heads[[level, id, text]]]
 */
function th119t_toc( $html ) {
	$used  = array();
	$heads = array();
	$html  = preg_replace_callback(
		'#<h([23])([^>]*)>(.*?)</h\1>#is',
		function ( $m ) use ( &$used, &$heads ) {
			$text = trim( wp_strip_all_tags( $m[3] ) );
			if ( '' === $text ) {
				return $m[0];
			}
			if ( preg_match( '/\sid=["\']([^"\']+)["\']/i', $m[2], $idm ) ) {
				$id           = $idm[1];
				$used[ $id ]  = true;
				$attrs        = $m[2];
			} else {
				$id    = th119t_anchor( $text, $used );
				$attrs = $m[2] . ' id="' . esc_attr( $id ) . '"';
			}
			$heads[] = array( (int) $m[1], $id, $text );
			return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
		},
		$html
	);
	return array( $html, $heads );
}

/**
 * HTML mục lục.
 *
 * @param array $heads Danh sách tiêu đề.
 * @return string
 */
function th119t_toc_html( $heads ) {
	if ( count( $heads ) < 3 ) {
		return '';
	}
	$out = '<details class="th-toc" open><summary>Nội dung bài viết</summary><ol>';
	foreach ( $heads as $h ) {
		$out .= '<li class="th-toc__l' . (int) $h[0] . '"><a href="#' . esc_attr( $h[1] ) . '">' . esc_html( $h[2] ) . '</a></li>';
	}
	return $out . '</ol></details>';
}

/**
 * Câu hỏi thường gặp hiển thị trên trang (khớp với FAQPage do plugin xuất).
 *
 * @param int    $post_id ID.
 * @param string $title   Tiêu đề khối.
 * @return string
 */
function th119t_faq_html( $post_id, $title = 'Câu hỏi thường gặp' ) {
	$faq = function_exists( 'th119_get_faq' ) ? th119_get_faq( $post_id ) : array();
	if ( ! $faq ) {
		return '';
	}
	$out = '<section class="th-faq" id="cau-hoi-thuong-gap"><h2>' . esc_html( $title ) . '</h2>';
	foreach ( $faq as $i => $f ) {
		$out .= '<details class="th-faq__item"' . ( 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $f[0] ) . '</summary><div class="th-faq__a">' . wpautop( esc_html( $f[1] ) ) . '</div></details>';
	}
	return $out . '</section>';
}

/**
 * Thẻ dịch vụ nhỏ.
 *
 * @param WP_Post[] $services Dịch vụ.
 * @param string    $title    Tiêu đề.
 * @return string
 */
function th119t_services_box( $services, $title = 'Dịch vụ liên quan' ) {
	if ( ! $services ) {
		return '';
	}
	$out = '<section class="th-related-services"><h2>' . esc_html( $title ) . '</h2><div class="th-svc-list">';
	foreach ( $services as $s ) {
		$icon  = function_exists( 'th119_service_field' ) ? th119_service_field( 'icon', $s->ID ) : '';
		$price = function_exists( 'th119_service_field' ) ? th119_service_field( 'price', $s->ID ) : '';
		$sub   = function_exists( 'th119_service_field' ) ? th119_service_field( 'subtitle', $s->ID ) : '';
		$out  .= '<a class="th-svc" href="' . esc_url( get_permalink( $s ) ) . '"><span class="th-svc__icon">' . th119t_ic( $icon ? $icon : 'tool' ) . '</span><span class="th-svc__body"><strong>' . esc_html( get_the_title( $s ) ) . '</strong>' . ( $sub ? '<small>' . esc_html( $sub ) . '</small>' : '' ) . ( $price ? '<em>' . esc_html( $price ) . '</em>' : '' ) . '</span></a>';
	}
	return $out . '</div></section>';
}

/**
 * Hộp tác giả + người kiểm duyệt (E-E-A-T, chỉ dùng thông tin thật trong hồ sơ người dùng).
 *
 * @param int $post_id ID.
 * @return string
 */
function th119t_author_box( $post_id ) {
	$post = get_post( $post_id );
	$uid  = (int) $post->post_author;
	$job  = get_user_meta( $uid, 'th_job_title', true );
	$bio  = get_the_author_meta( 'description', $uid );
	$out  = '<aside class="th-author"><div class="th-author__avatar">' . get_avatar( $uid, 72, '', '', array( 'loading' => 'lazy' ) ) . '</div><div class="th-author__body"><p class="th-author__label">Người viết</p><p class="th-author__name"><a href="' . esc_url( get_author_posts_url( $uid ) ) . '">' . esc_html( get_the_author_meta( 'display_name', $uid ) ) . '</a>' . ( $job ? ' <span>· ' . esc_html( $job ) . '</span>' : '' ) . '</p>';
	if ( $bio ) {
		$out .= '<p class="th-author__bio">' . esc_html( $bio ) . '</p>';
	}
	$rev = (int) get_post_meta( $post_id, '_th_reviewer', true );
	if ( $rev && get_userdata( $rev ) ) {
		$rjob = get_user_meta( $rev, 'th_job_title', true );
		$date = get_post_meta( $post_id, '_th_reviewed', true );
		$out .= '<p class="th-author__review">' . th119t_ic( 'shield' ) . ' Kiểm duyệt kỹ thuật: <strong>' . esc_html( get_the_author_meta( 'display_name', $rev ) ) . '</strong>' . ( $rjob ? ' – ' . esc_html( $rjob ) : '' ) . ( $date ? ' · ' . esc_html( mysql2date( 'd/m/Y', $date ) ) : '' ) . '</p>';
	}
	return $out . '</div></aside>';
}

/**
 * Dòng thông tin dưới H1 bài viết.
 *
 * @param int $post_id ID.
 * @return string
 */
function th119t_post_meta( $post_id ) {
	$items   = array();
	$items[] = th119t_ic( 'clock' ) . ' Cập nhật <time datetime="' . esc_attr( get_the_modified_date( 'c', $post_id ) ) . '">' . esc_html( get_the_modified_date( 'd/m/Y', $post_id ) ) . '</time>';
	$items[] = 'Đọc ' . th119t_reading_time( $post_id ) . ' phút';
	$diff    = get_post_meta( $post_id, '_th_difficulty', true );
	if ( $diff ) {
		$items[] = 'Độ khó: <strong>' . esc_html( $diff ) . '</strong>';
	}
	$time = get_post_meta( $post_id, '_th_time', true );
	if ( $time ) {
		$items[] = 'Thực hiện: <strong>' . esc_html( $time ) . '</strong>';
	}
	$rev = (int) get_post_meta( $post_id, '_th_reviewer', true );
	if ( $rev && get_userdata( $rev ) ) {
		$items[] = th119t_ic( 'shield' ) . ' Đã kiểm duyệt bởi kỹ thuật viên';
	}
	return '<p class="th-meta">' . implode( '<span class="th-meta__sep" aria-hidden="true">·</span>', $items ) . '</p>';
}

/**
 * Hộp "Cần hỗ trợ?" cuối bài / thanh bên.
 *
 * @param string $title Tiêu đề.
 * @return string
 */
function th119t_help_box( $title = 'Làm theo hướng dẫn mà vẫn chưa được?' ) {
	return '<aside class="th-helpbox"><h2 class="th-helpbox__title">' . esc_html( $title ) . '</h2><p>Kỹ thuật viên ' . esc_html( th119t_opt( 'brand' ) ) . ' có thể kiểm tra trực tiếp hoặc hỗ trợ từ xa. Báo rõ tình trạng máy để được tư vấn đúng cách xử lý.</p>' . th119t_sc( '[th119_cta show="call,zalo,request,booking"]' ) . '</aside>';
}

/**
 * Danh sách thẻ bài.
 *
 * @param WP_Post[] $posts Bài.
 * @param string    $title Tiêu đề.
 * @return string
 */
function th119t_posts_grid( $posts, $title = '' ) {
	if ( ! $posts ) {
		return '';
	}
	$out = $title ? '<h2 class="th-section-title">' . esc_html( $title ) . '</h2>' : '';
	$out .= '<div class="th-posts">';
	foreach ( $posts as $p ) {
		$out .= th119t_card( $p );
	}
	return $out . '</div>';
}
