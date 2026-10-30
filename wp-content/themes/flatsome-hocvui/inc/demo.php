<?php
/**
 * Giao diện → Tạo site mẫu: dựng 5 lớp, 8 môn, hơn 60 bài học (inc/curriculum.txt) kèm câu hỏi luyện tập,
 * trang chủ, Trò chơi, Góc của em, Góc phụ huynh, menu chính. Chạy lại được (cập nhật theo slug, không tạo trùng).
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu quản trị.
 */
function hv_demo_menu() {
	add_theme_page( 'Tạo site mẫu Học Vui', 'Tạo site mẫu', 'manage_options', 'hv-demo', 'hv_demo_page' );
}
add_action( 'admin_menu', 'hv_demo_menu' );

/**
 * Trang quản trị.
 */
function hv_demo_page() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiện thông báo.
	$msg = isset( $_GET['hv_demo'] ) ? sanitize_key( wp_unslash( $_GET['hv_demo'] ) ) : '';
	?>
	<div class="wrap">
		<h1>Tạo site mẫu – Học Vui</h1>
		<?php if ( 'done' === $msg ) : ?>
			<div class="notice notice-success"><p><strong>Đã tạo xong.</strong> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Xem trang chủ</a> · <a href="<?php echo esc_url( get_post_type_archive_link( 'bai_hoc' ) ); ?>" target="_blank">Xem bài học</a></p></div>
		<?php elseif ( 'lessons' === $msg ) : ?>
			<div class="notice notice-success"><p><strong>Đã cập nhật bài học mẫu.</strong> Trang và menu giữ nguyên.</p></div>
		<?php endif; ?>
		<p>Nút <strong>Tạo site mẫu</strong> dựng sẵn:</p>
		<ul style="list-style:disc;margin-left:20px">
			<li>5 lớp (Lớp 1 – Lớp 5) và 8 môn theo Chương trình GDPT 2018: Toán, Tiếng Việt, Tiếng Anh, Tự nhiên và Xã hội, Khoa học, Lịch sử và Địa lí, Đạo đức, Tin học và Công nghệ.</li>
			<li><?php echo (int) count( hv_curriculum() ); ?> bài học có mục tiêu, nội dung, ví dụ, ghi nhớ, 3–4 câu hỏi luyện tập và trò chơi gợi ý (bám bộ sách <em>Kết nối tri thức với cuộc sống</em> và <em>Tiếng Anh Global Success</em> – bộ sách dùng chung toàn quốc từ năm học 2026–2027).</li>
			<li>Trang <em>Trang chủ</em> (đặt làm trang chủ), <em>Trò chơi</em>, <em>Góc của em</em> (album nhãn dán), <em>Góc phụ huynh</em>.</li>
			<li>Menu chính (Lớp học, Môn học, Trò chơi…) gán vào header Flatsome.</li>
		</ul>
		<p><strong>Lưu ý:</strong> chạy lại sẽ đưa các bài học và trang mẫu về nội dung gốc; bài học bạn tự thêm không bị ảnh hưởng. Nội dung là bài tự biên soạn để ôn tập, giáo viên nên rà soát lại cho phù hợp với lớp mình.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:10px" onsubmit="return confirm('Tạo / cập nhật site mẫu? Bài học và trang mẫu sẽ được ghi đè về nội dung gốc.');">
			<input type="hidden" name="action" value="hv_demo_run">
			<?php wp_nonce_field( 'hv_demo_run' ); ?>
			<?php submit_button( 'Tạo site mẫu', 'primary hero', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block" onsubmit="return confirm('Cập nhật lại các bài học mẫu? Trang chủ, trang và menu giữ nguyên.');">
			<input type="hidden" name="action" value="hv_demo_lessons">
			<?php wp_nonce_field( 'hv_demo_lessons' ); ?>
			<?php submit_button( 'Chỉ cập nhật bài học mẫu', 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Chạy tạo site mẫu.
 */
function hv_demo_run() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'hv_demo_run' );
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
	}
	hv_demo_lessons_build();
	hv_demo_pages_build();
	flush_rewrite_rules();
	wp_safe_redirect( admin_url( 'themes.php?page=hv-demo&hv_demo=done' ) );
	exit;
}
add_action( 'admin_post_hv_demo_run', 'hv_demo_run' );

/**
 * Chỉ cập nhật bài học.
 */
function hv_demo_lessons_run() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'hv_demo_lessons' );
	hv_demo_lessons_build();
	wp_safe_redirect( admin_url( 'themes.php?page=hv-demo&hv_demo=lessons' ) );
	exit;
}
add_action( 'admin_post_hv_demo_lessons', 'hv_demo_lessons_run' );

/**
 * Tạo / lấy mục phân loại.
 *
 * @param string $tax  Phân loại.
 * @param string $name Tên.
 * @param string $slug Slug.
 * @param string $desc Mô tả.
 * @return int
 */
function hv_demo_term( $tax, $name, $slug, $desc = '' ) {
	$t = get_term_by( 'slug', $slug, $tax );
	if ( $t ) {
		if ( $desc && ! $t->description ) {
			wp_update_term( $t->term_id, $tax, array( 'description' => $desc ) );
		}
		return (int) $t->term_id;
	}
	$r = wp_insert_term( $name, $tax, array( 'slug' => $slug, 'description' => $desc ) );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

/**
 * Tạo / cập nhật bài theo slug.
 *
 * @param array $data Dữ liệu wp_insert_post.
 * @param array $meta Meta.
 * @return int
 */
function hv_demo_post( $data, $meta = array() ) {
	$existing = get_page_by_path( $data['post_name'], OBJECT, $data['post_type'] );
	if ( $existing ) {
		$data['ID'] = $existing->ID;
	}
	$data['post_status'] = 'publish';
	$id                  = wp_insert_post( wp_slash( $data ) );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return (int) $id;
}

/**
 * Lớp, môn, bài học.
 */
function hv_demo_lessons_build() {
	hv_register_content();
	$grade_desc = array(
		1 => 'Làm quen với chữ cái, vần và dấu thanh; đếm, cộng trừ trong phạm vi 10, các số đến 100; nhận biết hình và xem giờ đúng.',
		2 => 'Cộng, trừ có nhớ trong phạm vi 100; bảng nhân 2, 5; các số đến 1000; từ chỉ sự vật, hoạt động, đặc điểm.',
		3 => 'Bảng nhân, chia; các số đến 100 000; chu vi, diện tích; so sánh, từ trái nghĩa; bắt đầu Tiếng Anh, Tin học.',
		4 => 'Số có nhiều chữ số, phân số, góc; danh từ, động từ, tính từ; Khoa học, Lịch sử và Địa lí các vùng miền.',
		5 => 'Số thập phân, tỉ số phần trăm, hình tròn, thể tích, vận tốc; câu ghép; Khoa học năng lượng; lịch sử dân tộc.',
	);
	foreach ( hv_grades() as $n => $g ) {
		hv_demo_term( 'lop', $g[0], $g[1], $grade_desc[ $n ] );
	}
	$subject_desc = array(
		'toan'            => 'Số và phép tính, hình học và đo lường, thống kê – xác suất.',
		'tieng-viet'      => 'Đọc, viết, nói và nghe; luyện từ và câu.',
		'tieng-anh'       => 'Tiếng Anh lớp 3–5 theo bộ Global Success: từ vựng, mẫu câu, nghe và nói.',
		'tu-nhien-xa-hoi' => 'Gia đình, trường học, cộng đồng, thực vật và động vật, con người và sức khoẻ, Trái Đất và bầu trời (lớp 1–3).',
		'khoa-hoc'        => 'Chất, năng lượng, thực vật và động vật, nấm, con người và sức khoẻ, sinh vật và môi trường (lớp 4–5).',
		'lich-su-dia-li'  => 'Địa phương em, các vùng miền Việt Nam, lịch sử dựng nước và giữ nước (lớp 4–5).',
		'dao-duc'         => 'Kĩ năng sống, ứng xử với gia đình, thầy cô, bạn bè và cộng đồng.',
		'tin-hoc'         => 'Máy tính và em, sử dụng an toàn, làm quen lập trình (lớp 3–5).',
	);
	foreach ( hv_subjects() as $slug => $s ) {
		hv_demo_term( 'mon_hoc', $s[0], $slug, $subject_desc[ $slug ] );
	}
	$order = array();
	foreach ( hv_curriculum() as $l ) {
		$key           = $l['grade'] . '-' . $l['subject'];
		$order[ $key ] = isset( $order[ $key ] ) ? $order[ $key ] + 1 : 1;
		$id            = hv_demo_post(
			array(
				'post_type'    => 'bai_hoc',
				'post_name'    => $l['slug'],
				'post_title'   => $l['title'],
				'post_excerpt' => $l['excerpt'],
				'post_content' => trim( $l['content'] ),
				'menu_order'   => $order[ $key ],
			),
			array(
				'_hv_chu_de'   => $l['topic'],
				'_hv_icon'     => $l['icon'],
				'_hv_muc_tieu' => implode( "\n", $l['goals'] ),
				'_hv_ghi_nho'  => implode( "\n", $l['notes'] ),
				'_hv_cau_hoi'  => implode( "\n", $l['quiz'] ),
				'_hv_tro_choi' => $l['game'],
				'_hv_bo_sach'  => hv_default_books( $l['subject'] ),
				'_hv_demo'     => 1,
			)
		);
		if ( $id ) {
			$grades = hv_grades();
			wp_set_object_terms( $id, $grades[ $l['grade'] ][1], 'lop' );
			wp_set_object_terms( $id, $l['subject'], 'mon_hoc' );
		}
	}
}

/**
 * Khối shortcode Gutenberg (không bị wpautop chèn thẻ p).
 *
 * @param string $sc Shortcode.
 * @return string
 */
function hv_demo_block( $sc ) {
	return "<!-- wp:shortcode -->\n" . $sc . "\n<!-- /wp:shortcode -->\n\n";
}

/**
 * Nội dung Góc phụ huynh.
 *
 * @return string
 */
function hv_demo_parent_content() {
	$contact = hv_opt( 'contact' );
	$html    = '<div class="hv-box hv-content"><h2>Học Vui bám sát chương trình nào?</h2>'
		. '<p>Bài học trên Học Vui được biên soạn theo <strong>Chương trình giáo dục phổ thông 2018</strong> cấp Tiểu học, sắp xếp theo đúng chủ đề, mạch kiến thức của bộ sách <strong>Kết nối tri thức với cuộc sống</strong> và <strong>Tiếng Anh Global Success</strong> – bộ sách giáo khoa dùng thống nhất trên cả nước từ năm học 2026–2027 (Quyết định 3588/QĐ-BGDĐT ngày 26/12/2025). Nội dung là bài tóm tắt, ví dụ và câu hỏi tự biên soạn để con ôn tập, <strong>không thay thế sách giáo khoa</strong> và bài giảng của thầy cô.</p>'
		. '<h2>Các môn học ở Tiểu học</h2>'
		. '<table><thead><tr><th>Lớp</th><th>Môn học bắt buộc</th></tr></thead><tbody>'
		. '<tr><td>Lớp 1, 2</td><td>Tiếng Việt, Toán, Đạo đức, Tự nhiên và Xã hội, Giáo dục thể chất, Nghệ thuật (Âm nhạc, Mĩ thuật), Hoạt động trải nghiệm. Tiếng Anh là môn tự chọn.</td></tr>'
		. '<tr><td>Lớp 3</td><td>Như lớp 1, 2 và thêm <strong>Tiếng Anh</strong>, <strong>Tin học và Công nghệ</strong>.</td></tr>'
		. '<tr><td>Lớp 4, 5</td><td>Tiếng Việt, Toán, Tiếng Anh, Đạo đức, <strong>Khoa học</strong>, <strong>Lịch sử và Địa lí</strong>, Tin học và Công nghệ, Giáo dục thể chất, Nghệ thuật, Hoạt động trải nghiệm.</td></tr>'
		. '</tbody></table>'
		. '<h2>Gợi ý học 15 phút mỗi ngày</h2><ul>'
		. '<li><strong>5 phút</strong> đọc bài học cùng con (bé lớp 1 có thể bấm 🔈 để nghe đọc câu hỏi).</li>'
		. '<li><strong>5 phút</strong> làm phần Luyện tập cuối bài – sai thì cùng con xem lại phần Ghi nhớ.</li>'
		. '<li><strong>5 phút</strong> chơi 1 trò chơi phù hợp lớp – câu hỏi mới được tạo ngẫu nhiên mỗi lượt.</li>'
		. '<li>Khen ngợi khi con nhận sao, mở nhãn dán mới; không nên cho con chơi quá 30 phút liên tục trên màn hình.</li></ul>'
		. '<h2>Riêng tư và an toàn</h2><p>Website không yêu cầu đăng ký, không thu thập thông tin của trẻ. Tên bé, số sao và nhãn dán chỉ lưu trên trình duyệt của máy đang dùng; xoá dữ liệu trình duyệt là mất. Không có quảng cáo hay liên kết mua hàng trong trò chơi.</p>';
	if ( $contact ) {
		$html .= '<h2>Liên hệ</h2><p>' . esc_html( $contact ) . '</p>';
	}
	return $html . '</div>';
}

/**
 * Trang, menu, trang chủ.
 */
function hv_demo_pages_build() {
	$blank = array( '_wp_page_template' => 'page-blank.php' );
	$home  = hv_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'trang-chu',
			'post_title'   => 'Trang chủ',
			'post_content' => hv_demo_block( '[hv_hero]' )
				. hv_demo_block( '[hv_section title="Em học lớp mấy?" sub="Chọn lớp để xem bài học của từng môn theo chương trình sách giáo khoa mới." id="lop-hoc"][hv_grades][/hv_section]' )
				. hv_demo_block( '[hv_section title="Trò chơi học tập" sub="Câu hỏi tự sinh mới mỗi lượt, theo đúng kiến thức từng lớp. Làm đúng nhận sao, đủ 10 sao mở nhãn dán!" bg="#fff"][hv_games player="0"][/hv_section]' )
				. hv_demo_block( '[hv_section title="Các môn học" sub="Theo Chương trình giáo dục phổ thông 2018 cấp Tiểu học."][hv_subjects][/hv_section]' )
				. hv_demo_block( '[hv_section title="Bài học mới" bg="#fff"][hv_lessons so="6"][/hv_section]' )
				. hv_demo_block( '[hv_section title="Học thế nào cho vui?"][hv_parent_steps][/hv_section]' )
				. hv_demo_block( '[hv_section][hv_band][/hv_section]' ),
		),
		$blank
	);
	$games = hv_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'tro-choi',
			'post_title'   => 'Trò chơi',
			'post_content' => hv_demo_block( '[hv_pagehead title="Trò chơi học tập" lead="7 trò chơi Toán, Tiếng Việt, Tiếng Anh, Khoa học – chọn lớp ngay trong trò chơi."]' )
				. hv_demo_block( '[hv_section][hv_games][/hv_section]' ),
		),
		$blank
	);
	$kid   = hv_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'goc-cua-em',
			'post_title'   => 'Góc của em',
			'post_content' => hv_demo_block( '[hv_pagehead title="Góc của em" lead="Nhập tên của em, xem số sao và bộ sưu tập nhãn dán." color="#f06595"]' )
				. hv_demo_block( '[hv_section][hv_name_form][hv_album][/hv_section]' ),
		),
		$blank
	);
	$parent = hv_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'goc-phu-huynh',
			'post_title'   => 'Góc phụ huynh',
			'post_content' => hv_demo_block( '[hv_pagehead title="Góc phụ huynh" lead="Chương trình học, cách đồng hành cùng con và cam kết an toàn cho trẻ." color="#12b886"]' )
				. "<!-- wp:html -->\n<section class=\"hv-section\"><div class=\"hv-wrap\">" . hv_demo_parent_content() . "</div></section>\n<!-- /wp:html -->\n\n"
				. hv_demo_block( '[hv_section title="Học thế nào cho vui?"][hv_parent_steps][/hv_section]' ),
		),
		$blank
	);
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );

	// Menu chính.
	$menu_name = 'Menu Học Vui';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		wp_delete_post( $item->ID, true );
	}
	$add = function ( $args, $parent = 0 ) use ( $menu_id ) {
		return wp_update_nav_menu_item( $menu_id, 0, array_merge( array( 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent ), $args ) );
	};
	$add( array( 'menu-item-title' => 'Trang chủ', 'menu-item-object' => 'page', 'menu-item-object-id' => $home, 'menu-item-type' => 'post_type' ) );
	$lop = $add( array( 'menu-item-title' => 'Lớp học', 'menu-item-url' => get_post_type_archive_link( 'bai_hoc' ), 'menu-item-type' => 'custom' ) );
	foreach ( hv_grades() as $g ) {
		$t = get_term_by( 'slug', $g[1], 'lop' );
		if ( $t ) {
			$add( array( 'menu-item-title' => $g[2] . ' ' . $g[0], 'menu-item-object' => 'lop', 'menu-item-object-id' => $t->term_id, 'menu-item-type' => 'taxonomy' ), $lop );
		}
	}
	$mon = $add( array( 'menu-item-title' => 'Môn học', 'menu-item-url' => get_post_type_archive_link( 'bai_hoc' ), 'menu-item-type' => 'custom' ) );
	foreach ( hv_subjects() as $slug => $s ) {
		$t = get_term_by( 'slug', $slug, 'mon_hoc' );
		if ( $t ) {
			$add( array( 'menu-item-title' => $s[1] . ' ' . $s[0], 'menu-item-object' => 'mon_hoc', 'menu-item-object-id' => $t->term_id, 'menu-item-type' => 'taxonomy' ), $mon );
		}
	}
	$add( array( 'menu-item-title' => '🎮 Trò chơi', 'menu-item-object' => 'page', 'menu-item-object-id' => $games, 'menu-item-type' => 'post_type' ) );
	$add( array( 'menu-item-title' => '⭐ Góc của em', 'menu-item-object' => 'page', 'menu-item-object-id' => $kid, 'menu-item-type' => 'post_type' ) );
	$add( array( 'menu-item-title' => 'Phụ huynh', 'menu-item-object' => 'page', 'menu-item-object-id' => $parent, 'menu-item-type' => 'post_type' ) );
	$locations                   = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary']        = $menu_id;
	$locations['primary_mobile'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	// Footer Flatsome.
	set_theme_mod( 'footer_left_text', '© ' . gmdate( 'Y' ) . ' ' . get_bloginfo( 'name' ) . ' – Học mà chơi, chơi mà học. Nội dung bám Chương trình GDPT 2018, bộ sách Kết nối tri thức với cuộc sống.' );
}
