<?php
/**
 * Giáo án (kế hoạch bài dạy) cho thầy cô, soạn tự động từ dữ liệu từng bài học theo khung
 * Công văn 2345/BGDĐT-GDTH: I. Yêu cầu cần đạt – II. Đồ dùng dạy học – III. Các hoạt động dạy học chủ yếu
 * (Khởi động, Khám phá, Luyện tập – thực hành, Vận dụng – trải nghiệm) – IV. Điều chỉnh sau bài dạy.
 *
 * Xem: <link bài học>?giao-an=1 · Tải Word: <link bài học>?giao-an=1&tai=word · Danh sách: [hv_lesson_plans].
 * Học liệu điện tử chính thức của NXB Giáo dục: hoclieu.vn (mỗi bài có thể gắn đường dẫn riêng ở ô "Học liệu điện tử").
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

const HV_HOCLIEU_URL = 'https://hoclieu.vn/';

/**
 * Link giáo án của bài.
 *
 * @param WP_Post|int $post Bài.
 * @param bool        $word Tải file Word.
 * @return string
 */
function hv_lesson_plan_url( $post, $word = false ) {
	return add_query_arg( $word ? array( 'giao-an' => 1, 'tai' => 'word' ) : array( 'giao-an' => 1 ), get_permalink( $post ) );
}

/**
 * Phẩm chất, năng lực chung gợi ý theo môn.
 *
 * @param string $subject Slug môn.
 * @return array [ phẩm chất, đồ dùng học sinh ]
 */
function hv_lesson_plan_subject( $subject ) {
	$map = array(
		'toan'                => array( 'Chăm chỉ, trung thực; cẩn thận, chính xác khi tính toán.', 'SGK, vở bài tập Toán, bảng con, bộ đồ dùng học Toán.' ),
		'tieng-viet'          => array( 'Yêu thích đọc, viết; nhân ái, biết chia sẻ.', 'SGK, vở bài tập Tiếng Việt, bảng con, bút chì.' ),
		'tieng-anh'           => array( 'Chăm chỉ, tự tin giao tiếp; tôn trọng bạn bè.', 'SGK Tiếng Anh, vở bài tập, thẻ từ (flashcards).' ),
		'tu-nhien-xa-hoi'     => array( 'Yêu thiên nhiên; có ý thức giữ gìn sức khoẻ, bảo vệ môi trường.', 'SGK, vở bài tập Tự nhiên và Xã hội, tranh ảnh sưu tầm.' ),
		'khoa-hoc'            => array( 'Ham tìm hiểu, trung thực khi quan sát; có ý thức bảo vệ môi trường.', 'SGK, vở bài tập Khoa học, đồ dùng thí nghiệm đơn giản.' ),
		'lich-su-dia-li'      => array( 'Yêu nước, tự hào về lịch sử và văn hoá dân tộc.', 'SGK, vở bài tập Lịch sử và Địa lí, tranh ảnh, bản đồ.' ),
		'dao-duc'             => array( 'Nhân ái, trách nhiệm; biết cư xử đúng mực.', 'SGK Đạo đức, vở bài tập, thẻ mặt cười – mặt mếu.' ),
		'tin-hoc-cong-nghe'   => array( 'Trách nhiệm; sử dụng máy tính an toàn, đúng cách.', 'SGK Tin học và Công nghệ, máy tính (nếu có).' ),
		'may-tinh'            => array( 'Trách nhiệm; sử dụng máy tính an toàn, giữ gìn đồ dùng chung.', 'Máy tính hoặc máy tính bảng, chuột, bàn phím.' ),
	);
	return isset( $map[ $subject ] ) ? $map[ $subject ] : array( 'Chăm chỉ, trung thực, trách nhiệm.', 'SGK, vở bài tập, bút, bảng con.' );
}

/**
 * Tách nội dung bài theo tiêu đề H2 để thành các bước Khám phá.
 *
 * @param string $html Nội dung bài.
 * @return array [ [ tiêu đề, HTML ] ]
 */
function hv_lesson_plan_sections( $html ) {
	$allowed = array(
		'p'      => array(),
		'br'     => array(),
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'ul'     => array(),
		'ol'     => array(),
		'li'     => array(),
		'table'  => array(),
		'thead'  => array(),
		'tbody'  => array(),
		'tr'     => array(),
		'th'     => array(),
		'td'     => array(),
		'div'    => array( 'class' => true ),
		'span'   => array( 'class' => true ),
		'h3'     => array(),
	);
	$html  = preg_replace( '/<!--.*?-->/s', '', (string) $html );
	$parts = preg_split( '/<h2[^>]*>(.*?)<\/h2>/si', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$out   = array();
	if ( trim( wp_strip_all_tags( $parts[0] ) ) ) {
		$out[] = array( 'Giới thiệu kiến thức mới', wp_kses( $parts[0], $allowed ) );
	}
	for ( $i = 1; $i < count( $parts ); $i += 2 ) {
		$out[] = array( wp_strip_all_tags( $parts[ $i ] ), wp_kses( isset( $parts[ $i + 1 ] ) ? $parts[ $i + 1 ] : '', $allowed ) );
	}
	return $out;
}

/**
 * Nội dung giáo án của một bài.
 *
 * @param WP_Post $post Bài học.
 * @return string HTML.
 */
function hv_lesson_plan_html( $post ) {
	$id                     = $post->ID;
	list( $grade, $subject ) = hv_lesson_terms( $id );
	$sinfo                  = hv_subject_info( $subject );
	$sslug                  = $subject ? $subject->slug : '';
	list( $quality, $kit )  = hv_lesson_plan_subject( $sslug );
	$books                  = array();
	foreach ( hv_lesson_meta( 'bo_sach', $id ) as $b ) {
		$all = hv_books();
		if ( isset( $all[ $b ] ) ) {
			$books[] = $all[ $b ];
		}
	}
	$book    = $books ? $books[0] : 'Kết nối tri thức với cuộc sống';
	$goals   = hv_lines( hv_lesson_meta( 'muc_tieu', $id ) );
	$notes   = hv_lines( hv_lesson_meta( 'ghi_nho', $id ) );
	$hook    = hv_lesson_meta( 'mo_man', $id );
	$fact    = hv_lesson_meta( 'em_co_biet', $id );
	$mission = hv_lesson_meta( 'nhiem_vu', $id );
	$topic   = hv_lesson_meta( 'chu_de', $id );
	$quiz    = hv_quiz_data( $id );
	$game    = hv_lesson_meta( 'tro_choi', $id );
	$games   = hv_games();
	$hoclieu = hv_lesson_meta( 'hoclieu', $id );
	$title   = get_the_title( $post );
	$li      = function ( $items ) {
		return $items ? '<ul>' . implode( '', array_map( function ( $x ) { return '<li>' . esc_html( $x ) . '</li>'; }, $items ) ) . '</ul>' : '';
	};
	$row     = function ( $time, $gv, $hs ) {
		return '<tr><td class="t">' . $time . '</td><td>' . $gv . '</td><td>' . $hs . '</td></tr>';
	};

	$h  = '<header class="gp-head"><p class="gp-top"><span>Trường: ……………………………</span><span>Lớp: ' . (int) $grade . '……</span></p>';
	$h .= '<p class="gp-top"><span>Giáo viên: ……………………………</span><span>Ngày dạy: …… / …… / ………</span></p>';
	$h .= '<h1>KẾ HOẠCH BÀI DẠY</h1><p class="gp-sub">Môn: <b>' . esc_html( $sinfo[0] ) . '</b> – Lớp ' . (int) $grade . ( $topic ? ' · ' . esc_html( $topic ) : '' ) . '</p>';
	$h .= '<p class="gp-title">' . esc_html( $title ) . '</p><p class="gp-sub">Thời lượng: 1 tiết (35 phút) · Bộ sách: ' . esc_html( $book ) . '</p></header>';

	// I. Yêu cầu cần đạt.
	$h .= '<h2>I. YÊU CẦU CẦN ĐẠT</h2><h3>1. Năng lực đặc thù</h3>' . ( $goals ? $li( $goals ) : '<p>……</p>' );
	$h .= '<h3>2. Năng lực chung</h3><ul><li>Tự chủ và tự học: tự hoàn thành các nhiệm vụ học tập, tự luyện tập thêm trên Học Vui.</li><li>Giao tiếp và hợp tác: trao đổi, chia sẻ cách làm với bạn trong nhóm đôi, nhóm bàn.</li><li>Giải quyết vấn đề và sáng tạo: vận dụng kiến thức vào tình huống thực tế' . ( $mission ? ' (' . esc_html( rtrim( $mission, '.' ) ) . ')' : '' ) . '.</li></ul>';
	$h .= '<h3>3. Phẩm chất</h3><ul><li>' . esc_html( $quality ) . '</li></ul>';

	// II. Đồ dùng dạy học.
	$h .= '<h2>II. ĐỒ DÙNG DẠY HỌC</h2><h3>1. Giáo viên</h3><ul>';
	$h .= '<li>SGK ' . esc_html( $sinfo[0] ) . ' ' . (int) $grade . ' (' . esc_html( $book ) . ').</li>';
	$h .= '<li>Học liệu điện tử của NXB Giáo dục Việt Nam trên <a href="' . esc_url( $hoclieu ? $hoclieu : HV_HOCLIEU_URL ) . '">hoclieu.vn</a>' . ( $hoclieu ? ' (bài này)' : ' – sách điện tử, video, bài tập tương tác' ) . '.</li>';
	$h .= '<li>Máy chiếu / ti vi; bài giảng Học Vui: <a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_permalink( $post ) ) . '</a></li>';
	$h .= '<li>Phiếu bài tập (các câu hỏi ở phần Luyện tập).</li></ul>';
	$h .= '<h3>2. Học sinh</h3><ul><li>' . esc_html( $kit ) . '</li></ul>';

	// III. Các hoạt động dạy học chủ yếu.
	$h .= '<h2>III. CÁC HOẠT ĐỘNG DẠY HỌC CHỦ YẾU</h2><table class="gp-table"><thead><tr><th class="t">Thời gian</th><th>Hoạt động của giáo viên</th><th>Hoạt động của học sinh</th></tr></thead><tbody>';

	// 1. Khởi động.
	$gv  = '<p class="gp-act">1. Khởi động</p><p><i>Mục tiêu: tạo hứng thú, kết nối kinh nghiệm của HS với bài mới.</i></p>';
	$gv .= $hook ? '<p>– GV kể tình huống: “' . esc_html( $hook ) . '”</p><p>– GV hỏi: Theo em, ta làm thế nào để giải quyết tình huống trên?</p>' : '<p>– GV tổ chức trò chơi khởi động, hỏi đáp nhanh kiến thức cũ.</p>';
	if ( $game && isset( $games[ $game ] ) ) {
		$gv .= '<p>– Hoặc: tổ chức trò chơi “' . esc_html( $games[ $game ][0] ) . '” trên Học Vui (2–3 phút).</p>';
	}
	$gv .= '<p>– GV giới thiệu bài: <b>' . esc_html( $title ) . '</b>.</p>';
	$h  .= $row( '3–5 phút', $gv, '<p>– Lắng nghe tình huống.</p><p>– Suy nghĩ, nêu ý kiến, dự đoán.</p><p>– Tham gia trò chơi.</p><p>– Ghi tên bài.</p>' );

	// 2. Khám phá.
	$gv = '<p class="gp-act">2. Khám phá – Hình thành kiến thức mới</p><p><i>Mục tiêu: ' . esc_html( $goals ? implode( '; ', array_map( function ( $g ) { return rtrim( hv_lcfirst( $g ), '. ' ); }, array_slice( $goals, 0, 2 ) ) ) : 'HS nắm được kiến thức trọng tâm của bài' ) . '.</i></p>';
	$n  = 0;
	foreach ( hv_lesson_plan_sections( $post->post_content ) as $sec ) {
		++$n;
		$gv .= '<p class="gp-step">' . $n . ') ' . esc_html( $sec[0] ) . '</p>' . $sec[1];
	}
	if ( $notes ) {
		$gv .= '<p class="gp-step">Chốt kiến thức (Ghi nhớ):</p>' . $li( $notes );
	}
	if ( $fact ) {
		$gv .= '<p class="gp-step">Mở rộng – Em có biết?</p><p>' . esc_html( $fact ) . '</p>';
	}
	$h .= $row( '12–15 phút', $gv, '<p>– Quan sát tranh, ví dụ trên màn hình/SGK.</p><p>– Thực hiện theo hướng dẫn của GV, nêu nhận xét.</p><p>– Thảo luận nhóm đôi, chia sẻ cách làm.</p><p>– Đọc lại phần Ghi nhớ.</p>' );

	// 3. Luyện tập, thực hành.
	$gv = '<p class="gp-act">3. Luyện tập, thực hành</p><p><i>Mục tiêu: củng cố, vận dụng kiến thức vừa học.</i></p>';
	if ( $quiz ) {
		$gv .= '<ol class="gp-quiz">';
		foreach ( $quiz as $q ) {
			$opts = array_merge( array( $q['a'] ), $q['w'] );
			sort( $opts );
			$gv .= '<li><p>' . esc_html( $q['q'] ) . '</p><p class="gp-opts">' . implode( ' &nbsp; ', array_map( function ( $o, $k ) { return chr( 97 + $k ) . ') ' . esc_html( $o ); }, $opts, array_keys( $opts ) ) ) . '</p>'
				. '<p class="gp-key">Đáp án: <b>' . esc_html( $q['a'] ) . '</b>' . ( ! empty( $q['e'] ) ? ' – ' . esc_html( $q['e'] ) : '' ) . '</p></li>';
		}
		$gv .= '</ol>';
	}
	$gv .= '<p>– GV chữa bài, nhận xét; có thể cho HS luyện tập trên Học Vui (phần “Đánh trùm màn” của bài).</p>';
	$h  .= $row( '10–12 phút', $gv, '<p>– Làm bài cá nhân vào bảng con/phiếu bài tập.</p><p>– Trình bày, giải thích cách làm.</p><p>– Nhận xét bài của bạn, chữa bài.</p>' );

	// 4. Vận dụng, trải nghiệm.
	$gv = '<p class="gp-act">4. Vận dụng, trải nghiệm</p>';
	$gv .= $mission ? '<p>– GV giao nhiệm vụ: ' . esc_html( $mission ) . '</p>' : '<p>– GV nêu tình huống thực tế để HS vận dụng.</p>';
	$gv .= '<p>– Nhận xét tiết học, tuyên dương HS tích cực.</p><p>– Dặn dò: ôn lại bài, tự luyện thêm trên Học Vui; chuẩn bị bài sau.</p>';
	$h  .= $row( '3–5 phút', $gv, '<p>– Nêu cách thực hiện nhiệm vụ.</p><p>– Lắng nghe, ghi nhớ nhiệm vụ về nhà.</p>' );
	$h  .= '</tbody></table>';

	// IV. Điều chỉnh sau bài dạy.
	$h .= '<h2>IV. ĐIỀU CHỈNH SAU BÀI DẠY (nếu có)</h2><p class="gp-lines">' . str_repeat( '…………………………………………………………………………………………………………<br>', 3 ) . '</p>';
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$h   .= '<p class="gp-src">Giáo án soạn từ bài học trên Học Vui' . ( $host ? ' (' . esc_html( $host ) . ')' : '' ) . ', theo khung kế hoạch bài dạy của Công văn 2345/BGDĐT-GDTH. Thầy cô tham khảo thêm SGK và học liệu điện tử tại hoclieu.vn, điều chỉnh cho phù hợp với lớp mình.</p>';
	return $h;
}

/**
 * Viết thường chữ đầu (giữ dấu tiếng Việt).
 *
 * @param string $s Chuỗi.
 * @return string
 */
function hv_lcfirst( $s ) {
	return mb_strtolower( mb_substr( $s, 0, 1 ) ) . mb_substr( $s, 1 );
}

/**
 * Trang giáo án riêng (in, lưu PDF, tải Word) khi mở ?giao-an=1 trên trang bài học.
 */
function hv_lesson_plan_render() {
	if ( ! isset( $_GET['giao-an'] ) || ! is_singular( 'bai_hoc' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn cách hiển thị.
		return;
	}
	$post = get_queried_object();
	$body = hv_lesson_plan_html( $post );
	$word = isset( $_GET['tai'] ) && 'word' === $_GET['tai']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$css  = 'body{font-family:"Times New Roman",Times,serif;font-size:13pt;line-height:1.45;color:#000;max-width:820px;margin:0 auto;padding:24px}'
		. 'h1{text-align:center;font-size:17pt;margin:16px 0 4px}h2{font-size:13.5pt;margin:20px 0 6px}h3{font-size:13pt;margin:10px 0 4px;font-style:italic}'
		. '.gp-top{display:flex;justify-content:space-between;margin:0}.gp-sub{text-align:center;margin:2px 0}.gp-title{text-align:center;font-size:15pt;font-weight:bold;margin:6px 0 2px;text-transform:uppercase}'
		. 'ul{margin:4px 0 4px 22px;padding:0}li{margin:2px 0}p{margin:4px 0}a{color:#0b57d0}'
		. '.gp-table{width:100%;border-collapse:collapse;margin-top:6px}.gp-table th,.gp-table td{border:1px solid #000;padding:6px 8px;vertical-align:top;text-align:left}.gp-table th{background:#eef2f8;text-align:center}'
		. '.gp-table td.t,.gp-table th.t{width:70px;text-align:center}.gp-table td:nth-child(3){width:30%}.gp-act{font-weight:bold;text-transform:uppercase}.gp-step{font-weight:bold;margin-top:8px}'
		. '.gp-table table{border-collapse:collapse;margin:4px 0}.gp-table table td,.gp-table table th{border:1px solid #777;padding:3px 6px}'
		. '.hv-example,.hv-tip{border:1px dashed #777;padding:4px 8px;margin:4px 0}.gp-quiz{margin:4px 0 4px 20px;padding:0}.gp-opts{margin-left:6px}.gp-key{color:#1a5e20;font-style:italic}'
		. '.gp-lines{line-height:2}.gp-src{margin-top:18px;font-size:11pt;font-style:italic;color:#444}'
		. '.gp-bar{position:sticky;top:0;display:flex;flex-wrap:wrap;gap:8px;padding:10px;margin:-24px -24px 16px;background:#f3f6fb;border-bottom:1px solid #dde4f0;font-family:system-ui,sans-serif;font-size:14px}'
		. '.gp-bar a,.gp-bar button{padding:8px 14px;border:0;border-radius:999px;background:#58b847;color:#fff;font-weight:700;text-decoration:none;cursor:pointer;font-size:14px}.gp-bar .w{background:#2b579a}.gp-bar .b{background:#fff;color:#1d2b53;border:1px solid #cfd8e6}'
		. '@media print{.gp-bar{display:none}body{padding:0;max-width:none}@page{size:A4;margin:2cm 2cm 2cm 3cm}}';
	$doc  = '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">'
		. '<title>' . esc_html( 'Giáo án – ' . get_the_title( $post ) ) . '</title><style>' . $css . '</style></head><body>';
	if ( $word ) {
		nocache_headers();
		header( 'Content-Type: application/msword; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="giao-an-' . sanitize_file_name( $post->post_name ) . '.doc"' );
		echo $doc . $body . '</body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML đã escape trong hv_lesson_plan_html().
		exit;
	}
	echo $doc . '<nav class="gp-bar"><button type="button" onclick="window.print()">🖨️ In / Lưu PDF</button><a class="w" href="' . esc_url( hv_lesson_plan_url( $post, true ) ) . '">⬇️ Tải file Word</a><a class="b" href="' . esc_url( get_permalink( $post ) ) . '">← Về bài học</a><a class="b" href="' . esc_url( hv_page_url( 'giao-an' ) ? hv_page_url( 'giao-an' ) : home_url( '/' ) ) . '">Tất cả giáo án</a></nav>'
		. $body . '</body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML đã escape trong hv_lesson_plan_html().
	exit;
}
add_action( 'template_redirect', 'hv_lesson_plan_render', 5 );

/**
 * [hv_lesson_plans] – danh sách giáo án theo lớp (tab) và môn.
 *
 * @return string
 */
function hv_sc_lesson_plans() {
	$grades = hv_grades();
	$by     = hv_group_lessons( get_posts( array( 'post_type' => 'bai_hoc', 'posts_per_page' => 500, 'no_found_rows' => true, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ), 'grade' );
	$h      = '<div class="hv-plans" data-hv-tabs><div class="hv-plans-intro hv-box"><p>' . hv_icon3d( 'scroll', 44 ) . '<span>Mỗi bài học trên Học Vui có sẵn <b>kế hoạch bài dạy</b> theo khung Công văn 2345/BGDĐT-GDTH: yêu cầu cần đạt, đồ dùng, 4 hoạt động (khởi động – khám phá – luyện tập – vận dụng) kèm đáp án và lời giải. Thầy cô xem, in, lưu PDF hoặc tải file Word để chỉnh sửa. Học liệu điện tử chính thức của NXB Giáo dục: <a href="' . esc_url( HV_HOCLIEU_URL ) . '" target="_blank" rel="noopener">hoclieu.vn</a>.</span></p></div>';
	$h     .= '<div class="hv-tabbar" role="tablist" aria-label="Chọn lớp">';
	$first  = true;
	foreach ( $grades as $n => $g ) {
		if ( empty( $by[ $n ] ) ) {
			continue;
		}
		$h    .= '<button type="button" role="tab" id="hv-gp-t' . (int) $n . '" aria-controls="hv-gp-p' . (int) $n . '" aria-selected="' . ( $first ? 'true' : 'false' ) . '">' . esc_html( $g[0] ) . '</button>';
		$first = false;
	}
	$h    .= '</div>';
	$first = true;
	foreach ( $grades as $n => $g ) {
		if ( empty( $by[ $n ] ) ) {
			continue;
		}
		$h .= '<div class="hv-plans-panel" role="tabpanel" id="hv-gp-p' . (int) $n . '" aria-labelledby="hv-gp-t' . (int) $n . '"' . ( $first ? '' : ' hidden' ) . '>';
		foreach ( hv_group_lessons( $by[ $n ], 'subject' ) as $list ) {
			list( , $subject ) = hv_lesson_terms( $list[0]->ID );
			$info             = hv_subject_info( $subject );
			$h               .= '<section class="hv-box hv-plans-subj" style="--c:' . esc_attr( $info[2] ) . '"><h3>' . hv_icon3d( $info[3], 36 ) . ' ' . esc_html( $info[0] . ' ' . $n ) . '</h3><ol>';
			foreach ( $list as $p ) {
				$h .= '<li><span>' . esc_html( get_the_title( $p ) ) . '</span><a href="' . esc_url( hv_lesson_plan_url( $p ) ) . '" target="_blank" rel="noopener">Xem / In</a><a class="is-word" href="' . esc_url( hv_lesson_plan_url( $p, true ) ) . '">Word</a></li>';
			}
			$h .= '</ol></section>';
		}
		$h    .= '</div>';
		$first = false;
	}
	return $h . '</div>';
}
add_shortcode( 'hv_lesson_plans', 'hv_sc_lesson_plans' );
