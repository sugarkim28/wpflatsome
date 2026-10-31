<?php
/**
 * Giao diện → Tạo site mẫu: dựng 5 lớp, 9 môn, hơn 80 bài học (inc/curriculum.txt) kèm câu hỏi luyện tập,
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
			<li>5 lớp (Lớp 1 – Lớp 5), 8 môn theo Chương trình GDPT 2018 (Toán, Tiếng Việt, Tiếng Anh, Tự nhiên và Xã hội, Khoa học, Lịch sử và Địa lí, Đạo đức, Tin học và Công nghệ) và môn ngoại khoá <em>Làm quen máy tính</em>.</li>
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
		'may-tinh'        => 'Môn ngoại khoá cho bé từ lớp 1: các bộ phận máy tính, cầm chuột, nháy đúp, kéo thả, gõ phím 10 ngón, gõ tiếng Việt, dùng Internet an toàn – học qua trò chơi.',
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
				'_hv_mo_man'   => $l['hook'],
				'_hv_em_co_biet' => $l['fact'],
				'_hv_nhiem_vu' => $l['mission'],
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
		. '<li><strong>5 phút</strong> cùng con đi qua phần Mở màn và Khám phá. Bấm 🔈 để Cú Bông đọc to; vào ⚙️ để chọn giọng đọc tiếng Việt và tốc độ chậm rãi.</li>'
		. '<li><strong>5 phút</strong> đánh trùm màn (câu hỏi luyện tập) – sai thì cùng con xem lại Bí kíp ghi nhớ.</li>'
		. '<li><strong>5 phút</strong> chơi màn thưởng, rồi giao con làm Nhiệm vụ ngoài đời cùng gia đình.</li>'
		. '<li>Khen ngợi khi con nhận sao, mở nhãn dán mới; không nên cho con chơi quá 30 phút liên tục trên màn hình.</li></ul>'
		. '<h2>Giọng đọc tiếng Việt</h2><p>Giọng đọc dùng sẵn trong máy (không cần tải thêm). Giọng ấm, tự nhiên nhất hiện nay là <strong>HoaiMy</strong> trên trình duyệt Microsoft Edge; Chrome có giọng <strong>Google Tiếng Việt</strong>; iPhone, iPad có giọng <strong>Linh</strong>. Nếu máy chưa có giọng tiếng Việt, vào Cài đặt điện thoại → Trợ năng → Nội dung được đọc → Giọng nói để thêm.</p>'
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
				. hv_demo_block( '[hv_section title="Chọn lớp của em" sub="Mỗi lớp là một hòn đảo kiến thức. Học lần lượt từng bài theo đúng thứ tự trong sách, cuối mỗi môn có bài ôn tập tổng hợp." class="hv-sea"][hv_world][/hv_section]' )
				. hv_demo_block( '[hv_section title="Bài học theo lớp" sub="Bài học sắp xếp theo đúng chủ đề của sách Kết nối tri thức với cuộc sống."][hv_lesson_tabs so="8"][/hv_section]' )
				. hv_demo_block( '[hv_section title="Các môn học" sub="8 môn theo Chương trình giáo dục phổ thông 2018 và môn ngoại khoá Làm quen máy tính."][hv_subjects][/hv_section]' )
				. hv_demo_block( '[hv_section title="Một bài học có gì?" sub="Mỗi bài 10–15 phút, đi từ dễ đến khó, giúp bé hiểu bài chứ không học vẹt."][hv_parent_steps][/hv_section]' )
				. hv_demo_block( '[hv_section title="Học xong, chơi một chút!" sub="Trò chơi giúp bé luyện tập lại kiến thức vừa học – câu hỏi mới mỗi lượt, đúng chương trình từng lớp."][hv_games player="0" show="tinh-nham,cau-ca,hai-tao,leo-nui"][hv_button text="Xem tất cả 16 trò chơi ›" page="games" style="white"][/hv_section]' )
				. hv_demo_block( '[hv_section title="Vì sao chọn Học Vui?"][hv_why][/hv_section]' )
				. hv_demo_block( '[hv_section][hv_band][/hv_section]' ),
		),
		$blank
	);
	$games = hv_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'tro-choi',
			'post_title'   => 'Trò chơi',
			'post_content' => hv_demo_block( '[hv_pagehead title="Khu trò chơi" lead="16 trò chơi Toán, Tiếng Việt, Tiếng Anh, Khoa học – chọn lớp ngay trong trò chơi. Mỗi lượt được sao và XP!" icon="🕹️" color="#ff5d73"]' )
				. hv_demo_block( '[hv_section][hv_games][/hv_section]' ),
		),
		$blank
	);
	$kid   = hv_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'goc-cua-em',
			'post_title'   => 'Góc của em',
			'post_content' => hv_demo_block( '[hv_pagehead title="Góc của em" lead="Hồ sơ nhà thám hiểm: cấp độ, nhân vật, huy hiệu và bộ sưu tập nhãn dán." icon="🏅" color="#ff9f1c"]' )
				. hv_demo_block( '[hv_section][hv_profile][/hv_section]' ),
		),
		$blank
	);
	$parent = hv_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'goc-phu-huynh',
			'post_title'   => 'Góc phụ huynh',
			'post_content' => hv_demo_block( '[hv_pagehead title="Góc phụ huynh" lead="Chương trình học, cách đồng hành cùng con và cam kết an toàn cho trẻ." icon="👨‍👩‍👧" color="#2ec27e"]' )
				. "<!-- wp:html -->\n<section class=\"hv-section\"><div class=\"hv-wrap\">" . hv_demo_parent_content() . "</div></section>\n<!-- /wp:html -->\n\n"
				. hv_demo_block( '[hv_section title="Một màn chơi có gì?"][hv_parent_steps][/hv_section]' ),
		),
		$blank
	);
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );

	// Trang thông tin chuẩn web (giới thiệu, liên hệ, hỏi đáp, chính sách).
	$info  = array();
	$heads = array(
		'gioi-thieu'          => array( 'Giới thiệu', 'Học Vui là sân chơi học tập miễn phí cho học sinh tiểu học, do Tin Học 119 phát triển.', 'owl', '#7c4dff' ),
		'lien-he'             => array( 'Liên hệ', 'Bố mẹ, thầy cô có góp ý hay cần hỗ trợ? Cú Bông luôn sẵn sàng lắng nghe!', 'speech', '#2ea3f2' ),
		'tai-khoan'           => array( 'Tài khoản phụ huynh', 'Một tài khoản cho cả nhà: mỗi bé một hồ sơ riêng, học tiếp trên mọi thiết bị, bố mẹ xem bé học đến đâu.', 'house', '#2ec27e' ),
		'so-tay-kien-thuc'    => array( 'Sổ tay kiến thức', 'Tất cả phần Ghi nhớ của các bài học, theo lớp và môn – xem nhanh, ôn tập, in ra giấy.', 'scroll', '#ff9f1c' ),
		'cau-hoi-thuong-gap'  => array( 'Câu hỏi thường gặp', 'Những điều bố mẹ và thầy cô hay hỏi về Học Vui.', 'bulb', '#ff9f1c' ),
		'chinh-sach-bao-mat'  => array( 'Chính sách bảo mật', 'Học Vui tôn trọng và bảo vệ quyền riêng tư của trẻ em.', 'heart', '#2ec27e' ),
		'dieu-khoan-su-dung'  => array( 'Điều khoản sử dụng', 'Quy định khi sử dụng nội dung và trò chơi trên Học Vui.', 'scroll', '#5d6a8a' ),
	);
	foreach ( $heads as $slug => $h ) {
		$info[ $slug ] = hv_demo_post(
			array(
				'post_type'    => 'page',
				'post_name'    => $slug,
				'post_title'   => $h[0],
				'post_content' => hv_demo_block( '[hv_pagehead title="' . $h[0] . '" lead="' . $h[1] . '" color="' . $h[3] . '"]' )
					. "<!-- wp:html -->\n<section class=\"hv-section\"><div class=\"hv-wrap\">" . hv_demo_info_content( $slug ) . "</div></section>\n<!-- /wp:html -->\n",
			),
			$blank
		);
	}

	// Menu chính: biểu tượng 3D (class hv-i-…), mô tả hiện trong menu xổ, nút "Chơi ngay" nổi bật.
	$menu_name = 'Menu Học Vui';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		wp_delete_post( $item->ID, true );
	}
	$add = function ( $args, $parent = 0, $icon = '', $desc = '', $extra = '' ) use ( $menu_id ) {
		return wp_update_nav_menu_item(
			$menu_id,
			0,
			array_merge(
				array(
					'menu-item-status'      => 'publish',
					'menu-item-parent-id'   => $parent,
					'menu-item-classes'     => trim( ( $icon ? 'hv-i-' . $icon : '' ) . ' ' . $extra ),
					'menu-item-description' => $desc,
				),
				$args
			)
		);
	};
	$page = function ( $title, $id ) {
		return array( 'menu-item-title' => $title, 'menu-item-object' => 'page', 'menu-item-object-id' => $id, 'menu-item-type' => 'post_type' );
	};
	$by  = hv_lessons_by_grade();
	$lop = $add( array( 'menu-item-title' => 'Lớp học', 'menu-item-url' => home_url( '/#ban-do' ), 'menu-item-type' => 'custom' ), 0, 'island', '', 'hv-mega' );
	foreach ( hv_grades() as $n => $g ) {
		$t = get_term_by( 'slug', $g[1], 'lop' );
		if ( $t ) {
			$add( array( 'menu-item-title' => $g[0] . ' · ' . $g[5], 'menu-item-object' => 'lop', 'menu-item-object-id' => $t->term_id, 'menu-item-type' => 'taxonomy' ), $lop, $g[7], ( isset( $by[ $n ] ) ? count( $by[ $n ] ) . ' màn · ' : '' ) . $g[4] );
		}
	}
	$mon = $add( array( 'menu-item-title' => 'Môn học', 'menu-item-url' => get_post_type_archive_link( 'bai_hoc' ), 'menu-item-type' => 'custom' ), 0, 'book', '', 'hv-mega' );
	foreach ( hv_subjects() as $slug => $s ) {
		$t = get_term_by( 'slug', $slug, 'mon_hoc' );
		if ( $t ) {
			$add( array( 'menu-item-title' => $s[0], 'menu-item-object' => 'mon_hoc', 'menu-item-object-id' => $t->term_id, 'menu-item-type' => 'taxonomy' ), $mon, $s[4], 'Lớp ' . min( $s[3] ) . ( min( $s[3] ) === max( $s[3] ) ? '' : '–' . max( $s[3] ) ) );
		}
	}
	$add( $page( 'Sổ tay', $info['so-tay-kien-thuc'] ), 0, 'scroll' );
	$add( $page( 'Góc của em', $kid ), 0, 'trophy' );
	$ph = $add( $page( 'Phụ huynh', $parent ), 0, 'heart' );
	$add( $page( 'Tài khoản phụ huynh', $info['tai-khoan'] ), $ph, 'house', 'Đăng nhập, quản lý các bé, xem tiến độ' );
	$add( $page( 'Góc phụ huynh', $parent ), $ph, 'heart', 'Chương trình học, cách đồng hành cùng con' );
	$add( $page( 'Câu hỏi thường gặp', $info['cau-hoi-thuong-gap'] ), $ph, 'bulb', 'Giải đáp nhanh cho bố mẹ, thầy cô' );
	$add( $page( 'Giới thiệu', $info['gioi-thieu'] ), $ph, 'owl', 'Học Vui và Tin Học 119' );
	$add( $page( 'Liên hệ', $info['lien-he'] ), $ph, 'speech', 'Hotline, Zalo, góp ý' );
	$add( $page( 'Chơi ngay', $games ), 0, 'game', '', 'hv-menu-cta' );
	$locations                   = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary']        = $menu_id;
	$locations['primary_mobile'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	// Header Flatsome: logo trái, menu phải, dính khi cuộn; điện thoại: nút menu + logo.
	set_theme_mod( 'header_elements_left', array() );
	set_theme_mod( 'header_elements_right', array( 'nav' ) );
	set_theme_mod( 'header_mobile_elements_left', array( 'menu-icon' ) );
	set_theme_mod( 'header_mobile_elements_right', array() );
	set_theme_mod( 'mobile_sidebar', array( 'nav' ) );
	set_theme_mod( 'header_height', 88 );
	set_theme_mod( 'header_height_mobile', 64 );
	set_theme_mod( 'logo_width', 260 );
	set_theme_mod( 'logo_max_width_mobile', 170 );
	set_theme_mod( 'header_bg', '#ffffff' );
	set_theme_mod( 'header_color', 'light' );
	set_theme_mod( 'header_sticky', 1 );
	set_theme_mod( 'topbar_show', 0 );
	set_theme_mod( 'nav_uppercase', 0 );
	set_theme_mod( 'type_nav_color', '#1d2b53' );
	set_theme_mod( 'type_nav_color_hover', '#3f952f' );
	set_theme_mod( 'color_primary', '#2ea3f2' );
	set_theme_mod( 'color_secondary', '#58b847' );
	set_theme_mod( 'back_to_top', 1 );
	set_theme_mod( 'site_width', 1240 );
	set_theme_mod( 'header_width', 'container' );
	set_theme_mod( 'nav_spacing', 'xsmall' );
	set_theme_mod( 'disable_fonts', 1 ); // Theme đã tải Nunito + Baloo 2, không cần phông mặc định của Flatsome.
	if ( false !== strpos( get_bloginfo( 'name' ), '.' ) || '' === get_bloginfo( 'name' ) ) {
		update_option( 'blogname', 'Học Vui' ); // Tên site đang là tên miền → đặt tên thương hiệu.
		update_option( 'blogdescription', 'Học mà chơi – chơi mà học, lớp 1 đến lớp 5' );
	}

	// Chân trang = UX Block "Chân trang Học Vui" (sửa được bằng UX Builder), dải bản quyền của Flatsome để trống.
	$existing = get_page_by_path( 'chan-trang-hoc-vui', OBJECT, 'blocks' );
	$block    = post_type_exists( 'blocks' ) ? wp_insert_post(
		array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'blocks',
			'post_status'  => 'publish',
			'post_name'    => 'chan-trang-hoc-vui',
			'post_title'   => 'Chân trang Học Vui',
			'post_content' => '[hv_footer]',
		)
	) : 0;
	if ( $block && ! is_wp_error( $block ) ) {
		set_theme_mod( 'footer_block', 'chan-trang-hoc-vui' );
		set_theme_mod( 'footer_left_text', '' );
		set_theme_mod( 'footer_right_text', '' );
	} else {
		set_theme_mod( 'footer_left_text', '© ' . gmdate( 'Y' ) . ' ' . get_bloginfo( 'name' ) . ' · ' . hv_opt( 'org_name' ) );
	}
}

/**
 * Nội dung các trang thông tin (giới thiệu, liên hệ, hỏi đáp, chính sách).
 *
 * @param string $slug Trang.
 * @return string
 */
function hv_demo_info_content( $slug ) {
	$org = esc_html( hv_opt( 'org_name' ) );
	switch ( $slug ) {
		case 'gioi-thieu':
			return '<div class="hv-box hv-content"><img class="alignright" src="' . esc_url( HV_URI . '/assets/img/brand/hocvui-huy-hieu.png' ) . '" width="220" height="220" alt="Huy hiệu Học Vui">'
				. '<h2>Học Vui là gì?</h2><p>Học Vui là sân chơi học tập trực tuyến miễn phí cho học sinh lớp 1 đến lớp 5. Mỗi lớp là một hòn đảo, mỗi bài học là một màn chơi ngắn: nghe bạn Cú Bông kể chuyện, khám phá kiến thức từng chút một, ôn bí kíp rồi đánh trùm để giành sao. Bé còn có 16 trò chơi học tập tự sinh câu hỏi mới mỗi lượt.</p>'
				. '<h2>Bám sát chương trình mới</h2><p>Bài học sắp xếp theo Chương trình giáo dục phổ thông 2018 và chủ đề của bộ sách <strong>Kết nối tri thức với cuộc sống</strong>, Tiếng Anh <strong>Global Success</strong> – bộ sách dùng chung toàn quốc từ năm học 2026–2027. Nội dung do đội ngũ tự biên soạn để ôn tập, không sao chép và không thay thế sách giáo khoa.</p>'
				. '<h2>Đơn vị phát triển</h2><p><img src="' . esc_url( HV_URI . '/assets/img/brand/tinhoc119-ngang.webp' ) . '" width="360" height="97" alt="' . $org . '" loading="lazy"></p><p>Học Vui do <strong>' . $org . '</strong> xây dựng và vận hành với tinh thần <em>' . esc_html( hv_opt( 'org_slogan' ) ) . '</em>.</p></div>';
		case 'lien-he':
			return do_shortcode( '[hv_contact]' ) . '<div class="hv-box hv-content" style="margin-top:24px"><h2>Góp ý cho Học Vui</h2><p>Bố mẹ, thầy cô phát hiện câu hỏi chưa chính xác, muốn đề xuất bài học hay trò chơi mới? Hãy nhắn qua Zalo hoặc gọi hotline – Cú Bông cảm ơn rất nhiều!</p></div>';
		case 'cau-hoi-thuong-gap':
			return do_shortcode( '[hv_faq]' );
		case 'so-tay-kien-thuc':
			return '[hv_handbook]';
		case 'tai-khoan':
			return '[hv_parent]';
		case 'chinh-sach-bao-mat':
			return '<div class="hv-box hv-content"><h2>1. Thông tin chúng tôi thu thập</h2><p>Bé có thể học mà không cần tài khoản: khi đó tên bé tự nhập, số sao, cấp độ, nhãn dán chỉ lưu trong bộ nhớ trình duyệt (localStorage) trên thiết bị đang dùng và không gửi về máy chủ.</p><p>Nếu bố mẹ tạo <strong>tài khoản phụ huynh</strong>, chúng tôi lưu: họ tên và email của phụ huynh, mật khẩu (đã mã hoá); với mỗi bé: tên gọi do bố mẹ đặt (có thể là tên ở nhà), lớp, nhân vật và tiến độ học (sao, XP, bài đã học). Chúng tôi không thu thập ngày sinh, địa chỉ, ảnh hay giọng nói của trẻ, và không chia sẻ dữ liệu cho bên thứ ba.</p><h2>2. Cookie và thống kê</h2><p>Website có thể dùng cookie kỹ thuật cần thiết để vận hành. Nếu bật công cụ thống kê truy cập, dữ liệu được tổng hợp ẩn danh, không dùng để quảng cáo nhắm vào trẻ em.</p>'
				. '<h2>3. Quảng cáo</h2><p>Học Vui không hiển thị quảng cáo và không có liên kết mua hàng trong trò chơi.</p>'
				. '<h2>4. Giọng đọc</h2><p>Tính năng đọc to dùng bộ chuyển văn bản thành giọng nói có sẵn của trình duyệt/thiết bị; nội dung đọc không được ghi âm hay lưu lại.</p>'
				. '<h2>5. Xoá dữ liệu</h2><p>Bố mẹ có thể xoá tiến độ trên máy ở trang Góc của em (nút “Bắt đầu lại từ đầu”) hoặc xoá dữ liệu trình duyệt. Với tài khoản phụ huynh, bố mẹ tự xoá từng bé hoặc xoá hẳn tài khoản cùng toàn bộ dữ liệu ở trang Tài khoản phụ huynh.</p>'
				. '<h2>6. Liên hệ</h2><p>Mọi thắc mắc về quyền riêng tư, vui lòng liên hệ ' . $org . ' qua hotline ' . esc_html( hv_opt( 'hotline' ) ) . '.</p></div>';
		case 'dieu-khoan-su-dung':
			return '<div class="hv-box hv-content"><h2>1. Mục đích</h2><p>Học Vui cung cấp bài ôn tập và trò chơi học tập miễn phí nhằm hỗ trợ học sinh tiểu học, không thay thế sách giáo khoa và bài giảng của thầy cô.</p>'
				. '<h2>2. Bản quyền nội dung</h2><p>Bài học, câu hỏi, hình minh hoạ linh vật Cú Bông thuộc ' . $org . '. Nhà trường, thầy cô được dùng miễn phí cho mục đích giảng dạy phi thương mại, vui lòng ghi nguồn Học Vui. Biểu tượng 3D sử dụng bộ Microsoft Fluent Emoji (giấy phép MIT).</p>'
				. '<h2>3. Trách nhiệm của người dùng</h2><p>Bố mẹ, thầy cô hướng dẫn trẻ sử dụng thiết bị an toàn, hợp lý về thời gian.</p>'
				. '<h2>4. Thay đổi</h2><p>Nội dung và điều khoản có thể được cập nhật để phù hợp chương trình, sách giáo khoa mới.</p></div>';
	}
	return '';
}
