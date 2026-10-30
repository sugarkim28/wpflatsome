<?php
/**
 * Trò chơi học tập (chạy bằng assets/js/hocvui.js) và các shortcode:
 * [hv_game id="tinh-nham" lop="2"] · [hv_games lop="3"] · [hv_album] · [hv_name_form]
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách trò chơi: id => [tên, biểu tượng, mô tả, các lớp, màu, môn, ảnh 3D].
 *
 * @return array
 */
function hv_games() {
	return array(
		'tinh-nham'   => array( 'Tính nhẩm siêu tốc', '🧮', 'Cộng, trừ, nhân, chia, phân số, số thập phân – câu hỏi tự sinh theo đúng kiến thức từng lớp.', array( 1, 2, 3, 4, 5 ), '#ff6b6b', 'Toán', 'numbers' ),
		'dem-hinh'    => array( 'Đếm hình & nhận biết hình', '🔢', 'Đếm đồ vật, so sánh số, gọi tên hình; lớp 2 đếm theo nhóm để hiểu phép nhân.', array( 1, 2 ), '#ff922b', 'Toán', 'crayon' ),
		'xem-gio'     => array( 'Bé xem đồng hồ', '⏰', 'Đọc giờ đúng (lớp 1), giờ rưỡi, 15 phút (lớp 2), giờ và phút (lớp 3).', array( 1, 2, 3 ), '#f59f00', 'Toán', 'clock' ),
		'ghep-chu'    => array( 'Ghép chữ – Đánh vần', '🔤', 'Nhìn hình chọn âm đầu (lớp 1), chọn vần (lớp 2), chọn từ viết đúng chính tả (lớp 3).', array( 1, 2, 3 ), '#40c057', 'Tiếng Việt', 'letters' ),
		'sap-xep-cau' => array( 'Sắp xếp câu', '🧩', 'Chạm vào các từ theo đúng thứ tự để tạo thành câu hoàn chỉnh.', array( 1, 2, 3, 4, 5 ), '#12b886', 'Tiếng Việt', 'puzzle' ),
		'lat-the'     => array( 'Lật thẻ tiếng Anh', '🃏', 'Lật thẻ tìm cặp hình – từ tiếng Anh, nghe phát âm chuẩn. Chủ đề thay đổi theo lớp.', array( 1, 2, 3, 4, 5 ), '#339af0', 'Tiếng Anh', 'joker' ),
		'do-vui'      => array( 'Đố vui khoa học – xã hội', '🔬', 'Câu đố Tự nhiên và Xã hội, Khoa học, Lịch sử và Địa lí, Đạo đức theo từng lớp.', array( 1, 2, 3, 4, 5 ), '#9775fa', 'TN&XH · Khoa học · LS&ĐL', 'bulb' ),
	);
}

/**
 * Link trang Trò chơi.
 *
 * @return string
 */
function hv_games_url() {
	$page = get_page_by_path( 'tro-choi' );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

/**
 * HTML khung trò chơi.
 *
 * @param string $id    Mã trò chơi.
 * @param int    $grade Lớp (0 = lớp đã chọn lần trước).
 * @param array  $extra Thuộc tính thêm.
 * @return string
 */
function hv_game_html( $id, $grade = 0, $extra = array() ) {
	$games = hv_games();
	$color = isset( $games[ $id ] ) ? $games[ $id ][4] : hv_opt( 'color_primary' );
	$attrs = array(
		'class'        => 'hv-game',
		'data-hv-game' => $id,
		'style'        => '--c:' . $color,
	);
	if ( $grade ) {
		$attrs['data-grade'] = (int) $grade;
	}
	$attrs = array_merge( $attrs, $extra );
	$html  = '<div';
	foreach ( $attrs as $k => $v ) {
		$html .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	return $html . '><p class="hv-empty">Đang tải trò chơi… <noscript>(cần bật JavaScript để chơi)</noscript></p></div>';
}

/**
 * [hv_game id="tinh-nham" lop="2"]
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function hv_sc_game( $atts ) {
	$a = shortcode_atts( array( 'id' => 'tinh-nham', 'lop' => 0 ), $atts );
	if ( ! array_key_exists( $a['id'], hv_games() ) ) {
		return '';
	}
	return hv_game_html( $a['id'], (int) $a['lop'] );
}
add_shortcode( 'hv_game', 'hv_sc_game' );

/**
 * Thẻ 1 trò chơi.
 *
 * @param string $id    Mã.
 * @param int    $grade Lớp (thêm vào link).
 * @return string
 */
function hv_game_card( $id, $grade = 0 ) {
	$g    = hv_games()[ $id ];
	$args = array( 'choi' => $id );
	if ( $grade ) {
		$args['lop'] = $grade;
	}
	$url   = add_query_arg( $args, hv_games_url() ) . '#choi';
	$pills = '';
	foreach ( $g[3] as $n ) {
		$pills .= '<span class="hv-pill">Lớp ' . (int) $n . '</span>';
	}
	return '<a class="hv-game-card" href="' . esc_url( $url ) . '" style="--c:' . esc_attr( $g[4] ) . '"><span class="hv-gc-screen">' . hv_icon3d( $g[6], 96 ) . '</span><span class="hv-meta">' . esc_html( $g[5] ) . '</span><h3>' . esc_html( $g[0] ) . '</h3><p>' . esc_html( $g[2] ) . '</p><span class="hv-pills">' . $pills . '</span><span class="hv-play">▶ Chơi ngay</span></a>';
}

/**
 * [hv_games lop="" show=""] – lưới trò chơi; khi URL có ?choi=… thì mở trò chơi đó ở đầu.
 *
 * @param array $atts Thuộc tính: lop (lọc theo lớp), show (danh sách id, ngăn bằng dấu phẩy), player (1 = mở trò chơi theo ?choi).
 * @return string
 */
function hv_sc_games( $atts ) {
	$a     = shortcode_atts( array( 'lop' => 0, 'show' => '', 'player' => 1 ), $atts );
	$games = hv_games();
	$grade = (int) $a['lop'];
	$ids   = $a['show'] ? array_intersect( array_map( 'trim', explode( ',', $a['show'] ) ), array_keys( $games ) ) : array_keys( $games );
	$html  = '';
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- chỉ đọc để chọn trò chơi hiển thị.
	if ( $a['player'] && isset( $_GET['choi'] ) ) {
		$pick = sanitize_key( wp_unslash( $_GET['choi'] ) );
		$pg   = isset( $_GET['lop'] ) ? absint( $_GET['lop'] ) : 0;
		if ( isset( $games[ $pick ] ) ) {
			$html .= '<div id="choi" class="hv-now-playing">' . hv_game_html( $pick, $pg ) . '</div><h2 class="hv-section-title">Chọn máy chơi khác</h2>';
		}
	}
	// phpcs:enable
	$html .= '<div class="hv-game-grid">';
	foreach ( $ids as $id ) {
		if ( $grade && ! in_array( $grade, $games[ $id ][3], true ) ) {
			continue;
		}
		$html .= hv_game_card( $id, $grade );
	}
	return $html . '</div>';
}
add_shortcode( 'hv_games', 'hv_sc_games' );

/**
 * [hv_album] – album nhãn dán (đổi bằng sao).
 *
 * @return string
 */
function hv_sc_album() {
	return '<div class="hv-box hv-album"><h2>🏆 Album nhãn dán của <span data-hv-name="em">em</span></h2><p>Mỗi trò chơi được 1–3 ⭐ (làm siêu tốc được thêm 1 ⭐). Cứ đủ 10 ⭐ là mở được 1 nhãn dán mới!</p><div data-hv-album><p class="hv-empty">Đang tải…</p></div><p style="text-align:center;margin-top:12px"><button type="button" class="hv-btn hv-btn-ghost" data-hv-reset>Bắt đầu lại từ đầu</button></p></div>';
}
add_shortcode( 'hv_album', 'hv_sc_album' );

/**
 * [hv_name_form] – ô nhập tên bé (lưu trên trình duyệt, không gửi đi đâu).
 *
 * @return string
 */
function hv_sc_name_form() {
	return '<form class="hv-name" data-hv-name-form><label for="hv-name-input">Tên em là</label><input id="hv-name-input" type="text" maxlength="24" placeholder="Nhập tên của em" autocomplete="off"><button type="submit" class="hv-btn">Lưu</button><span class="hv-saved" hidden>Đã lưu! 🎉</span></form>';
}
add_shortcode( 'hv_name_form', 'hv_sc_name_form' );
