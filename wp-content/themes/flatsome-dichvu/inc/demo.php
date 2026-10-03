<?php
/**
 * Giao diện → Tạo site mẫu: dựng sẵn 5 nhóm dịch vụ, 20 dịch vụ (bảng giá, quy trình, hồ sơ, FAQ),
 * 3 bài viết, trang chủ / bảng giá / giới thiệu / liên hệ (UX Builder), menu, footer, header.
 * Chạy lại được (cập nhật theo slug, không tạo trùng). Có nút xoá bài viết mẫu.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu quản trị.
 */
function sgd_demo_menu() {
	add_theme_page( 'Tạo site mẫu', 'Tạo site mẫu', 'manage_options', 'sgd-demo', 'sgd_demo_page' );
}
add_action( 'admin_menu', 'sgd_demo_menu' );

/**
 * Trang quản trị.
 */
function sgd_demo_page() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiện thông báo.
	$msg = isset( $_GET['sgd_demo'] ) ? sanitize_key( wp_unslash( $_GET['sgd_demo'] ) ) : '';
	?>
	<div class="wrap">
		<h1>Tạo site mẫu – dịch vụ doanh nghiệp</h1>
		<?php if ( 'done' === $msg ) : ?>
			<div class="notice notice-success"><p><strong>Đã tạo xong.</strong> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Xem trang chủ</a> · <a href="<?php echo esc_url( get_post_type_archive_link( 'dich_vu' ) ); ?>" target="_blank">Xem danh sách dịch vụ</a></p></div>
		<?php elseif ( 'menu' === $msg ) : ?>
			<div class="notice notice-success"><p><strong>Đã cập nhật menu</strong>, tạo các trang dịch vụ, trang Tra cứu và bài viết Kiến thức – Đào tạo còn thiếu (bài bạn đã sửa giữ nguyên), đổi tên 3 khối dịch vụ chính trên trang chủ. <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Xem menu</a></p></div>
		<?php elseif ( 'cleaned' === $msg ) : ?>
			<div class="notice notice-success"><p>Đã xoá bài viết mẫu.</p></div>
		<?php endif; ?>
		<p><strong>Trước khi bấm:</strong> nhập tên công ty, hotline, Zalo, địa chỉ ở <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=sgd_company' ) ); ?>">Tuỳ biến → Website dịch vụ</a> (trang chủ, footer, header lấy thông tin từ đó).</p>
		<p>Nút này dựng sẵn:</p>
		<ul style="list-style:disc;margin-left:20px">
			<li>6 nhóm dịch vụ: <em>Thành lập doanh nghiệp, Thay đổi giấy phép kinh doanh, Dịch vụ thuế, Dịch vụ kế toán, Dịch vụ khác, Đào tạo kế toán</em>.</li>
			<li>Hơn 30 dịch vụ có sẵn nội dung, chi phí trọn gói, bảng giá (gói / bảng theo số hóa đơn), quy trình, hồ sơ cần chuẩn bị, câu hỏi thường gặp.</li>
			<li>10 bài viết Kiến thức &amp; Đào tạo (cập nhật luật 2026, có link về trang dịch vụ); trang <em>Trang chủ</em>, <em>Bảng giá</em>, <em>Giới thiệu</em>, <em>Liên hệ</em>, <em>Kiến thức</em> (sửa bằng UX Builder).</li>
			<li>Menu chính, footer (UX Block "Footer website"), header Flatsome.</li>
		</ul>
		<p><strong>Lưu ý:</strong> giá trong dữ liệu mẫu là <strong>giá minh hoạ</strong> – sửa theo bảng giá thật (Dịch vụ → sửa từng dịch vụ). Chạy lại sẽ đưa các dịch vụ/trang mẫu về nội dung gốc (dịch vụ bạn tự thêm không bị ảnh hưởng).</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:10px" onsubmit="return confirm('Tạo / cập nhật site mẫu? Các dịch vụ và trang mẫu sẽ được ghi đè về nội dung gốc.');">
			<input type="hidden" name="action" value="sgd_demo_run">
			<?php wp_nonce_field( 'sgd_demo_run' ); ?>
			<?php submit_button( 'Tạo site mẫu', 'primary hero', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:10px" onsubmit="return confirm('Dựng lại Menu chính theo mẫu, tạo các trang dịch vụ và bài viết Kiến thức – Đào tạo còn thiếu? Trang chủ và các dịch vụ đã có giữ nguyên. Mục menu bạn tự thêm sẽ bị thay.');">
			<input type="hidden" name="action" value="sgd_demo_menu_run">
			<?php wp_nonce_field( 'sgd_demo_menu_run' ); ?>
			<?php submit_button( 'Cập nhật menu (giữ trang chủ)', 'secondary', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block" onsubmit="return confirm('Xoá các bài viết mẫu cũ (có chữ “bài mẫu”)? 10 bài Kiến thức – Đào tạo không bị xoá.');">
			<input type="hidden" name="action" value="sgd_demo_clean">
			<?php wp_nonce_field( 'sgd_demo_clean' ); ?>
			<?php submit_button( 'Xoá bài viết mẫu', 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Chạy tạo site mẫu.
 */
function sgd_demo_run() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'sgd_demo_run' );
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
	}
	sgd_demo_build();
	wp_safe_redirect( admin_url( 'themes.php?page=sgd-demo&sgd_demo=done' ) );
	exit;
}
add_action( 'admin_post_sgd_demo_run', 'sgd_demo_run' );

/**
 * Xoá bài viết mẫu (giữ dịch vụ và trang).
 */
function sgd_demo_clean() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'sgd_demo_clean' );
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_sgd_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 'sample', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
	wp_safe_redirect( admin_url( 'themes.php?page=sgd-demo&sgd_demo=cleaned' ) );
	exit;
}
add_action( 'admin_post_sgd_demo_clean', 'sgd_demo_clean' );

/**
 * Tạo / lấy mục phân loại.
 *
 * @param string $tax  Phân loại.
 * @param string $name Tên.
 * @param string $slug Slug.
 * @param string $desc Mô tả.
 * @param array  $meta Term meta (luôn ghi đè).
 * @return int ID.
 */
function sgd_demo_term( $tax, $name, $slug, $desc = '', $meta = array() ) {
	$t = get_term_by( 'slug', $slug, $tax );
	if ( $t ) {
		$id = $t->term_id;
		if ( $desc && ! $t->description ) {
			wp_update_term( $id, $tax, array( 'description' => $desc ) );
		}
	} else {
		$r  = wp_insert_term( $name, $tax, array( 'slug' => $slug, 'description' => $desc ) );
		$id = is_wp_error( $r ) ? 0 : $r['term_id'];
	}
	foreach ( $meta as $k => $v ) {
		if ( $id ) {
			update_term_meta( $id, $k, $v );
		}
	}
	return (int) $id;
}

/**
 * Tạo / cập nhật bài theo slug.
 *
 * @param array $data Dữ liệu wp_insert_post.
 * @param array $meta Meta.
 * @return int ID.
 */
function sgd_demo_post( $data, $meta = array() ) {
	$existing = get_page_by_path( $data['post_name'], OBJECT, $data['post_type'] );
	if ( $existing ) {
		$data['ID'] = $existing->ID;
	}
	$data['post_status']  = 'publish';
	$data['post_content'] = wp_slash( $data['post_content'] );
	$id                   = wp_insert_post( $data, true );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( $meta as $k => $v ) {
		if ( '' === $v ) {
			delete_post_meta( $id, $k );
		} else {
			update_post_meta( $id, $k, $v );
		}
	}
	return (int) $id;
}

/**
 * Dựng toàn bộ site mẫu.
 */
function sgd_demo_build() {
	if ( '' === get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	sgd_register_services();
	$data = sgd_demo_data();

	list( $groups, $services ) = sgd_demo_services( $data );
	// 3. Bài viết Kiến thức & Đào tạo (10 bài, link nội bộ về trang dịch vụ).
	sgd_demo_import_posts();

	// Xoá nội dung mặc định của WordPress (bài "Hello world!", "Sample Page", bình luận mẫu) nếu chưa bị sửa.
	foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ) ) as $d ) {
		$old = get_page_by_path( $d[0], OBJECT, $d[1] );
		if ( $old && $old->post_modified_gmt === $old->post_date_gmt ) {
			wp_delete_post( $old->ID, true );
		}
	}
	update_option( 'posts_per_page', 9 );

	// 4. Trang.
	$blank   = array( '_wp_page_template' => 'page-blank.php' );
	$pricing = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'bang-gia', 'post_title' => 'Bảng giá dịch vụ', 'post_content' => sgd_demo_pricing_content( $data['groups'] ) ), $blank );
	$about   = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'gioi-thieu', 'post_title' => 'Giới thiệu', 'post_content' => sgd_demo_about_content() ), $blank );
	$contact = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'lien-he', 'post_title' => 'Liên hệ', 'post_content' => sgd_demo_contact_content() ), $blank );
	$news    = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'tin-tuc', 'post_title' => 'Kiến thức', 'post_content' => '' ) );
	sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'tra-cuu', 'post_title' => 'Tra cứu', 'post_content' => sgd_demo_lookup_content() ), $blank );
	// Trang chủ tạo sau cùng để liên kết tới các trang khác dùng đúng đường dẫn của site
	// (máy chủ nginx chưa cấu hình rewrite sẽ có dạng /index.php/bang-gia/).
	$home    = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'trang-chu', 'post_title' => 'Trang chủ', 'post_content' => sgd_demo_home_content() ), $blank );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $news );

	// 5. Footer (UX Block).
	$existing = get_page_by_path( 'footer-website', OBJECT, 'blocks' );
	$block    = wp_insert_post(
		array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'blocks',
			'post_status'  => 'publish',
			'post_name'    => 'footer-website',
			'post_title'   => 'Footer website',
			'post_content' => wp_slash( sgd_demo_footer_content( $groups, compact( 'pricing', 'about', 'contact', 'news' ) ) ),
		)
	);
	if ( $block && ! is_wp_error( $block ) ) {
		set_theme_mod( 'footer_block', 'footer-website' );
	}
	set_theme_mod( 'footer_left_text', 'Copyright [ux_current_year] © <strong>' . esc_html( sgd_opt( 'company_full' ) ) . '</strong>. All rights reserved.' );
	set_theme_mod( 'footer_right_text', '' );

	// 6. Menu.
	sgd_demo_build_menu( $groups, $services );

	// 7. Header Flatsome 2 tầng (kiểu công ty kế toán – đại lý thuế): thanh trên cùng (khẩu hiệu – email)
	// → hàng chính: logo trái, hotline/Zalo/giờ làm việc phải → thanh menu xanh chủ đạo toàn chiều ngang.
	$hot = sgd_opt( 'hotline' );
	set_theme_mod( 'header_elements_left', array() );
	set_theme_mod( 'header_elements_right', array( 'html-3' ) );
	set_theme_mod( 'header_elements_bottom_left', array( 'nav' ) );
	set_theme_mod( 'header_elements_bottom_center', array() );
	set_theme_mod( 'header_elements_bottom_right', array() );
	set_theme_mod( 'header_mobile_elements_left', array() );
	set_theme_mod( 'header_mobile_elements_right', array( 'html-3', 'menu-icon' ) );
	set_theme_mod( 'header_mobile_elements_bottom', array() );
	set_theme_mod( 'top_right_text', '[sgd_header_info]' );
	set_theme_mod( 'mobile_sidebar', array( 'nav' ) );
	set_theme_mod( 'header_height', 92 );
	set_theme_mod( 'header_height_mobile', 64 );
	set_theme_mod( 'header_bottom_height', 52 );
	set_theme_mod( 'logo_width', 240 );
	set_theme_mod( 'header_bg', '#ffffff' );
	set_theme_mod( 'header_color', 'light' );
	set_theme_mod( 'nav_position_bg', '#ffffff' );
	set_theme_mod( 'nav_position_color', 'light' );
	set_theme_mod( 'nav_style_bottom', '' );
	set_theme_mod( 'nav_uppercase', 0 );
	set_theme_mod( 'nav_uppercase_bottom', 1 );
	set_theme_mod( 'type_nav_bottom_color', '#1d2939' );
	set_theme_mod( 'type_nav_bottom_color_hover', sgd_opt( 'color_primary' ) );
	set_theme_mod( 'type_nav_color', '#1d2939' );
	set_theme_mod( 'type_nav_color_hover', sgd_opt( 'color_primary' ) );
	set_theme_mod( 'header_sticky', 1 );
	set_theme_mod( 'sticky_style', 'jump' );
	set_theme_mod( 'topbar_show', 1 );
	set_theme_mod( 'topbar_bg', '#0b2a5b' );
	set_theme_mod( 'topbar_color', 'dark' );
	set_theme_mod( 'topbar_elements_left', array( 'html' ) );
	set_theme_mod( 'topbar_elements_center', array() );
	set_theme_mod( 'topbar_elements_right', array( 'html-2' ) );
	set_theme_mod( 'topbar_left', '[sgd_topbar side="left"]' );
	set_theme_mod( 'topbar_right', '[sgd_topbar side="right"]' );
	set_theme_mod( 'header_button_1', 'Hotline ' . $hot );
	set_theme_mod( 'header_button_1_link', 'tel:' . sgd_tel( $hot ) );
	set_theme_mod( 'color_primary', sgd_opt( 'color_primary' ) );
	set_theme_mod( 'color_secondary', sgd_opt( 'color_accent' ) );
	set_theme_mod( 'color_links', sgd_opt( 'color_primary' ) );
	set_theme_mod( 'back_to_top', 1 );
	set_theme_mod( 'back_to_top_shape', 'square' );
	set_theme_mod( 'back_to_top_position', 'right' );
	set_theme_mod( 'footer_1_columns', 0 );
	set_theme_mod( 'footer_2_columns', 0 );
	set_theme_mod( 'default_title', 0 );
	if ( ! get_theme_mod( 'site_logo' ) || false !== strpos( (string) get_theme_mod( 'site_logo' ), '/flatsome/assets/img/logo.png' ) ) {
		set_theme_mod( 'site_logo', '' ); // Chưa tải logo → dùng logo SVG Tin Học 119 có sẵn trong theme.
	}
	update_option( 'blogname', sgd_opt( 'company' ) );
	update_option( 'blogdescription', sgd_opt( 'tagline' ) );

	flush_rewrite_rules();
}

/**
 * Nội dung trang chủ (UX Builder).
 *
 * @return string
 */
function sgd_demo_home_content() {
	$company = esc_html( sgd_opt( 'company' ) );
	// Bố cục 0.7 – tổng hợp ketoananpha.vn, timsen.vn, tanthanhthinh.com theo hành trình ra quyết định của khách:
	// Nhu cầu → Giá → Tin cậy → Gói → Giải đáp → Hành động. Mỗi khối 1 việc, không lặp nội dung, 1 H1 duy nhất.
	// 1 Banner + form → 2 Cam kết → 3 Về công ty → 4–7 Giới thiệu từng dịch vụ chính kiểu tanthanhthinh.com (danh sách dịch vụ + bài viết của nhóm,
	// không hiện giá – bảng giá nằm trong bài viết lớn của từng nhóm) → 8 Quy trình → 9 Chuyên viên + đối tác (nếu có) → 10 Hỏi đáp + form → 11 Gọi ngay.
	return '[section label="1. Banner (H1) + form" bg_color="#0b2a5b" padding="56px" padding__sm="28px" class="sgd-heroband"]
[row]
[col span="12"]
[sgd_hero title="Dịch vụ thành lập công ty, thuế &amp; kế toán" highlight="trọn gói – không phát sinh" popular="0"]
[/col]
[/row]
[/section]
[section label="2. Cam kết" bg_color="#ffffff" padding="0px" class="sgd-gnav-sec"]
[row]
[col span="12"]
[sgd_commit style="float"]
[/col]
[/row]
[/section]
[section label="3. Về công ty + số liệu" bg_color="#ffffff" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_about]
[/col]
[/row]
[/section]
[section label="4. Dịch vụ thành lập" bg_color="#f4f7fc" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="thanh-lap-doanh-nghiep" title="Dịch vụ thành lập công ty" price="0"]
[/col]
[/row]
[/section]
[section label="5. Dịch vụ kế toán – thuế" bg_color="#ffffff" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="ke-toan,dich-vu-thue" title="Dịch vụ kế toán" flip="1" price="0"]
[/col]
[/row]
[/section]
[section label="6. Thay đổi GPKD" bg_color="#f4f7fc" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="thay-doi-giay-phep" title="Thay đổi giấy phép kinh doanh" price="0"]
[/col]
[/row]
[/section]
[section label="7. Dịch vụ khác" bg_color="#ffffff" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="dich-vu-khac" title="Dịch vụ khác cho doanh nghiệp" flip="1" price="0"]
[/col]
[/row]
[/section]
[section label="8. Quy trình làm việc" bg_color="#0b2a5b" dark="true" padding="72px" padding__sm="44px" class="sgd-navyband"]
[row]
[col span="12"]
[sgd_title text="Quy trình làm việc 4 bước" sub="Minh bạch từng khâu – khách hàng nắm được tiến độ hồ sơ mọi lúc qua Zalo." class="is-light"]
[sgd_steps layout="row"]
Tiếp nhận thông tin | Lắng nghe nhu cầu, tư vấn quy định và báo giá trọn gói miễn phí | Trong 15 phút
Soạn hồ sơ | Soạn hồ sơ theo quy trình chuẩn, gửi khách ký tại nhà hoặc ký số | Trong ngày
Nộp &amp; theo dõi | Nộp hồ sơ trực tuyến, theo dõi và báo tiến độ qua Zalo | 3 – 5 ngày làm việc
Bàn giao kết quả | Giao giấy phép, con dấu, hồ sơ tận nơi và hướng dẫn việc tiếp theo | Tận nơi
[/sgd_steps]
[/col]
[/row]
[/section]
[section label="9. Chuyên viên tư vấn (Tuỳ biến → Thông tin công ty; trống = ẩn)" bg_color="#ffffff" padding="64px" padding__sm="40px" class="sgd-team-sec"]
[row]
[col span="12"]
[sgd_team]
[sgd_partners]
[/col]
[/row]
[/section]
[section label="10. Hỏi đáp + tư vấn" bg_color="#f4f7fc" padding="72px" padding__sm="44px"]
[row]
[col span="7" span__sm="12"]
[sgd_title text="Câu hỏi thường gặp" class="is-left"]
[sgd_faq]
Thành lập công ty mất bao lâu? | Cơ quan đăng ký kinh doanh giải quyết trong 3 ngày làm việc kể từ khi nhận hồ sơ hợp lệ. Tính cả soạn hồ sơ, khắc dấu và giao nhận, thường mất 3 – 5 ngày làm việc.
Thành lập công ty cần vốn tối thiểu bao nhiêu? | Với đa số ngành nghề, pháp luật không quy định vốn tối thiểu. Vốn điều lệ nên phù hợp quy mô kinh doanh và phải góp đủ trong 90 ngày kể từ ngày được cấp giấy chứng nhận.
Tôi có phải đến cơ quan nhà nước không? | Không. Hồ sơ được nộp online, bạn ký hồ sơ tại nhà và nhận giấy phép, con dấu tận nơi.
Doanh nghiệp chưa có doanh thu có cần làm báo cáo thuế không? | Có. Doanh nghiệp vẫn phải nộp tờ khai thuế định kỳ và báo cáo tài chính năm dù chưa phát sinh doanh thu; chậm nộp sẽ bị phạt.
Phí dịch vụ đã gồm lệ phí nhà nước chưa? | Báo giá ghi rõ phí dịch vụ và lệ phí nhà nước (nếu có). Chúng tôi không thu thêm ngoài báo giá đã thống nhất.
[/sgd_faq]
[/col]
[col span="5" span__sm="12"]
<div class="sgd-sticky-form">[sgd_lead_form source="Trang chủ – hỏi đáp" perks="1" title="Chưa thấy câu trả lời? Hỏi chuyên viên"]</div>
[/col]
[/row]
[/section]
[section label="11. Gọi ngay" bg_color="#ffffff" padding="0px"]
[row]
[col span="12"]
[sgd_cta_strip]
[gap height="56px"]
[/col]
[/row]
[/section]';
}

/**
 * Nội dung trang Bảng giá.
 *
 * @param array $groups Nhóm dịch vụ (slug => [tên, icon, thứ tự, mô tả]).
 * @return string
 */
function sgd_demo_pricing_content( $groups ) {
	$out = '[sgd_pagehead title="Bảng giá dịch vụ" sub="Phí dịch vụ thành lập công ty, thay đổi giấy phép kinh doanh, thuế và kế toán – bấm vào từng dịch vụ để xem chi tiết các gói."]
[section bg_color="#ffffff" padding="50px"]
[row][col span="12"]
<p class="sgd-note">' . esc_html( sgd_opt( 'disclaimer' ) ) . '</p>
[/col][/row]';
	foreach ( $groups as $slug => $g ) {
		$out .= '
[row][col span="12"]
<h2>' . esc_html( $g[0] ) . '</h2>
[sgd_price_table group="' . esc_attr( $slug ) . '"]
[gap height="30px"]
[/col][/row]';
	}
	return $out . '
[/section]
[section bg_color="#0b2a5b" dark="true" padding="56px" class="sgd-section-dark sgd-navyband"]
<div id="dang-ky"></div>
[row v_align="middle"]
[col span="7" span__sm="12"]
<h2>Cần báo giá cho trường hợp cụ thể?</h2>
<p>Gửi yêu cầu, chuyên viên sẽ báo giá trọn gói trong ít phút (giờ hành chính).</p>
[sgd_call_buttons]
[/col]
[col span="5" span__sm="12"]
[sgd_lead_form source="Trang bảng giá" perks="0"]
[/col]
[/row]
[/section]';
}

/**
 * Nội dung trang Giới thiệu.
 *
 * @return string
 */
function sgd_demo_about_content() {
	$c = esc_html( sgd_opt( 'company' ) );
	return '[sgd_pagehead title="Giới thiệu ' . esc_attr( sgd_opt( 'company' ) ) . '" sub="' . esc_attr( sgd_opt( 'company_full' ) ) . ' – ' . esc_attr( sgd_opt( 'tagline' ) ) . '."]
[section bg_color="#ffffff" padding="60px" padding__sm="36px"]
[row]
[col span="12"]
[sgd_about]
[/col]
[/row]
[/section]
[section bg_color="#0b2a5b" dark="true" padding="60px" padding__sm="36px" class="sgd-navyband"]
[row]
[col span="12"]
<p class="sgd-commit__head">Cam kết của ' . esc_html( sgd_opt( 'company' ) ) . '</p>
[sgd_commit]
[/col]
[/row]
[/section]
[section bg_color="#ffffff" padding="60px" padding__sm="36px" class="sgd-team-sec"]
[row]
[col span="12"]
[sgd_team title="Đội ngũ chuyên viên"]
[/col]
[/row]
[/section]
[section bg_color="#ffffff" padding="60px" padding__sm="36px"]
[row]
[col span="7" span__sm="12" class="sgd-prose"]
<h2>Về ' . $c . '</h2>
<p>' . $c . ' là đơn vị cung cấp dịch vụ pháp lý doanh nghiệp, thuế và kế toán cho doanh nghiệp vừa và nhỏ, hộ kinh doanh và người mới khởi nghiệp. Chúng tôi đồng hành từ khi khách hàng có ý tưởng kinh doanh: tư vấn loại hình, thành lập công ty, khai thuế ban đầu, đến kế toán – báo cáo thuế hằng tháng và các thủ tục thay đổi trong suốt quá trình hoạt động.</p>
<h2>Lĩnh vực hoạt động</h2>
<ul>
<li><strong>Thành lập doanh nghiệp:</strong> công ty TNHH, cổ phần, hộ kinh doanh, chi nhánh, văn phòng đại diện, công ty có vốn nước ngoài.</li>
<li><strong>Thay đổi giấy phép kinh doanh:</strong> địa chỉ, tên, người đại diện, vốn điều lệ, thành viên, ngành nghề; tạm ngừng, giải thể.</li>
<li><strong>Dịch vụ thuế – kế toán:</strong> khai thuế ban đầu, báo cáo thuế tháng/quý, quyết toán, báo cáo tài chính, kế toán trọn gói, làm lại sổ sách.</li>
<li><strong>Dịch vụ khác:</strong> bảo hiểm xã hội, chữ ký số, hóa đơn điện tử, đăng ký nhãn hiệu.</li>
</ul>
<h2>Giá trị chúng tôi theo đuổi</h2>
<ul>
<li><strong>Uy tín:</strong> làm đúng những gì đã cam kết trong báo giá và hợp đồng.</li>
<li><strong>Minh bạch:</strong> phí dịch vụ, lệ phí nhà nước ghi rõ từ đầu, không phát sinh.</li>
<li><strong>Tận tâm:</strong> một chuyên viên phụ trách, cập nhật tiến độ thường xuyên qua Zalo.</li>
<li><strong>Bảo mật:</strong> giữ kín giấy tờ, số liệu của khách hàng.</li>
</ul>
<h2>Cách chúng tôi làm việc</h2>
[sgd_steps]
Tiếp nhận và tư vấn | Lắng nghe nhu cầu, tư vấn miễn phí quy định liên quan | Trong 15 phút
Báo giá trọn gói | Ghi rõ phí dịch vụ và lệ phí nhà nước, không phát sinh | Trong ngày
Thực hiện hồ sơ | Soạn hồ sơ, khách ký tại nhà hoặc ký số; nộp trực tuyến, báo tiến độ qua Zalo | Theo từng thủ tục
Bàn giao và hỗ trợ | Giao kết quả tận nơi, hướng dẫn các việc tiếp theo | Sau khi có kết quả
[/sgd_steps]
<h2>Thông tin pháp nhân</h2>
<p><strong>' . esc_html( sgd_opt( 'company_full' ) ) . '</strong></p>
[sgd_contact_list]
[/col]
[col span="5" span__sm="12"]
<div id="dang-ky"></div>
[sgd_lead_form source="Trang giới thiệu"]
[/col]
[/row]
[/section]';
}

/**
 * Nội dung trang Liên hệ.
 *
 * @return string
 */
function sgd_demo_contact_content() {
	$map = 'https://www.google.com/maps?q=' . rawurlencode( sgd_opt( 'address' ) ) . '&output=embed';
	return '[sgd_pagehead title="Liên hệ ' . esc_attr( sgd_opt( 'company' ) ) . '" sub="Gọi hotline, nhắn Zalo hoặc gửi yêu cầu – chuyên viên sẽ liên hệ lại trong thời gian sớm nhất."]
[section bg_color="#ffffff" padding="50px"]
[row]
[col span="6" span__sm="12"]
<h2>Thông tin liên hệ</h2>
<p><strong>' . esc_html( sgd_opt( 'company_full' ) ) . '</strong></p>
[sgd_contact_list]
[sgd_call_buttons]
[gap height="20px"]
<div class="sgd-map__frame"><iframe src="' . esc_url( $map ) . '" title="Bản đồ văn phòng" loading="lazy" allowfullscreen></iframe></div>
[/col]
[col span="6" span__sm="12"]
<div id="dang-ky"></div>
[sgd_lead_form title="Gửi yêu cầu tư vấn" source="Trang liên hệ"]
[/col]
[/row]
[/section]';
}

/**
 * Nội dung footer (UX Block).
 *
 * @param array $groups Nhóm dịch vụ (slug => term_id).
 * @param array $pages  ID trang: pricing, about, contact, news.
 * @return string
 */
function sgd_demo_footer_content( $groups, $pages ) {
	$g = '';
	foreach ( $groups as $tid ) {
		$t = get_term( $tid, 'nhom_dich_vu' );
		if ( $t && ! is_wp_error( $t ) ) {
			$g .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
		}
	}
	$p = '';
	foreach ( array( 'pricing' => 'Bảng giá dịch vụ', 'about' => 'Giới thiệu', 'news' => 'Kiến thức', 'contact' => 'Liên hệ' ) as $k => $label ) {
		if ( ! empty( $pages[ $k ] ) ) {
			$p .= '<li><a href="' . esc_url( get_permalink( $pages[ $k ] ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	$branches = count( sgd_branches() ) > 1 || sgd_opt( 'branches' );
	return '[section bg_color="#0b2a5b" dark="true" padding="56px" padding__sm="36px" class="sgd-footer--dark"]
[row]
[col span="4" span__sm="12"]
<img class="sgd-footer__brandlogo" src="' . esc_url( SGD_URI . '/assets/img/logo-119-white.svg' ) . '" alt="' . esc_attr( sgd_opt( 'company' ) ) . '" width="260" height="52" loading="lazy">
<p class="sgd-footer__about">' . esc_html( sgd_opt( 'company_full' ) ) . ' – dịch vụ thành lập công ty, thay đổi giấy phép kinh doanh, thuế và kế toán trọn gói.</p>
[sgd_contact_list]
[/col]
[col span="3" span__sm="6"]
<p class="sgd-footer__h">Dịch vụ</p>
<ul class="sgd-flinks"><li><a href="' . esc_url( get_post_type_archive_link( 'dich_vu' ) ) . '">Tất cả dịch vụ</a></li>' . $g . '</ul>
[/col]
[col span="2" span__sm="6"]
<p class="sgd-footer__h">Thông tin</p>
<ul class="sgd-flinks">' . $p . '</ul>
[/col]
[col span="3" span__sm="12"]
<p class="sgd-footer__h">Tư vấn miễn phí</p>
<p class="sgd-footer__hot"><a href="tel:' . esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ) . '">' . esc_html( sgd_opt( 'hotline' ) ) . '</a></p>
<p class="sgd-footer__small">Gọi hoặc nhắn Zalo – ' . esc_html( sgd_opt( 'working_hours' ) ) . '</p>
[sgd_call_buttons]
[/col]
[/row]' . ( $branches ? '
[row]
[col span="12"]
[sgd_branches]
[/col]
[/row]' : '' ) . '
[/section]';
}

/**
 * Đường dẫn trang theo slug, đúng cấu trúc đường dẫn hiện tại của site (kể cả dạng /index.php/…).
 *
 * @param string $slug Slug trang.
 * @return string
 */
function sgd_demo_url( $slug ) {
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : home_url( '/' );
}

/**
 * Dữ liệu mẫu: demo-data.php + demo-data-more.php (menu mới).
 *
 * @return array
 */
function sgd_demo_data() {
	$data = require SGD_DIR . '/inc/demo-data.php';
	$more = require SGD_DIR . '/inc/demo-data-more.php';
	$data['groups']   = array_merge( $data['groups'], $more['groups'] );
	$data['services'] = array_merge( $data['services'], $more['services'] );
	return $data;
}

/**
 * Tạo / cập nhật nhóm và dịch vụ mẫu.
 *
 * @param array $data         Dữ liệu.
 * @param bool  $only_missing true = chỉ tạo dịch vụ chưa có (không ghi đè dịch vụ bạn đã sửa).
 * @return array [ nhóm slug => ID, dịch vụ slug => ID ]
 */
function sgd_demo_services( $data, $only_missing = false ) {
	$defaults = array(
		'subtitle'  => '',
		'price'     => '',
		'duration'  => '',
		'icon'      => 'doc',
		'includes'  => '',
		'documents' => '',
		'process'   => '',
		'packages'  => '',
		'faq'       => '',
		'featured'  => false,
		'content'   => array(),
		'excerpt'   => '',
	);
	$services = array();
	$groups = array();
	foreach ( $data['groups'] as $slug => $g ) {
		$groups[ $slug ] = sgd_demo_term( 'nhom_dich_vu', $g[0], $slug, $g[3], array( '_sgd_icon' => $g[1], '_sgd_order' => $g[2] ) );
	}

	// Dịch vụ.
	foreach ( $data['services'] as $i => $s ) {
		$s = array_merge( $defaults, $s );
		if ( $only_missing ) {
			$have = get_page_by_path( $s['slug'], OBJECT, 'dich_vu' );
			if ( $have ) {
				$services[ $s['slug'] ] = $have->ID;
				continue;
			}
		}
		$content = '';
		foreach ( $s['content'] as $h => $p ) {
			// Đoạn bắt đầu bằng "<" là HTML soạn sẵn (bảng so sánh…), giữ nguyên.
			$content .= '<h2>' . esc_html( $h ) . "</h2>\n" . ( 0 === strpos( $p, '<' ) ? $p : '<p>' . esc_html( $p ) . '</p>' ) . "\n";
		}
		$id = sgd_demo_post(
			array(
				'post_type'    => 'dich_vu',
				'post_name'    => $s['slug'],
				'post_title'   => $s['title'],
				'post_excerpt' => $s['excerpt'],
				'post_content' => $content,
				'menu_order'   => $i + 1,
			),
			array(
				'_sgd_demo'      => 'service',
				'_sgd_short'       => isset( $s['short'] ) ? $s['short'] : '',
				'_sgd_costs'       => isset( $s['costs'] ) ? $s['costs'] : '',
				'_sgd_price_table' => isset( $s['price_table'] ) ? $s['price_table'] : '',
				'_sgd_subtitle'  => $s['subtitle'],
				'_sgd_price'     => $s['price'],
				'_sgd_duration'  => $s['duration'],
				'_sgd_icon'      => $s['icon'],
				'_sgd_includes'  => $s['includes'],
				'_sgd_documents' => $s['documents'],
				'_sgd_process'   => $s['process'],
				'_sgd_packages'  => $s['packages'],
				'_sgd_faq'       => $s['faq'],
				'_sgd_featured'  => $s['featured'] ? '1' : '',
			)
		);
		if ( $id ) {
			wp_set_object_terms( $id, array( $groups[ $s['group'] ] ), 'nhom_dich_vu' );
			$services[ $s['slug'] ] = $id;
		}
	}
	return array( $groups, $services );
}

/**
 * Menu chính theo bố cục ketoananpha.vn: Giới thiệu · Dịch vụ thành lập · Dịch vụ kế toán · Thay đổi GPKD
 * · Dịch vụ khác · Đào tạo · Kiến thức · Liên hệ.
 *
 * @param array $groups   Nhóm (slug => ID).
 * @param array $services Dịch vụ (slug => ID).
 */
function sgd_demo_build_menu( $groups, $services ) {
	$page = function ( $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'page' );
		return $p ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
	};
	$news_id = (int) get_option( 'page_for_posts' );
	$menu_name = 'Menu chính';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) {
		wp_delete_post( $it->ID, true );
	}
	$add = function ( $title, $url, $parent = 0, $obj = array() ) use ( $menu_id ) {
		$item = array(
			'menu-item-title'     => $title,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
		);
		if ( $obj ) {
			$item = array_merge( $item, $obj );
		} else {
			$item['menu-item-url']  = $url;
			$item['menu-item-type'] = 'custom';
		}
		return wp_update_nav_menu_item( $menu_id, 0, $item );
	};
	// Menu kiểu ketoananpha.vn: chữ in hoa, mỗi mục là 1 trang có bài giới thiệu; menu con mở đầu bằng trang tổng quan của nhóm.
	// Không đặt "Bảng giá" trên menu – bảng giá nằm trong bài viết từng nhóm và từng dịch vụ.
	$tax_item = function ( $slug, $label, $parent = 0 ) use ( $groups, $add ) {
		return $add( $label, '', $parent, array( 'menu-item-object' => 'nhom_dich_vu', 'menu-item-object-id' => $groups[ $slug ], 'menu-item-type' => 'taxonomy' ) );
	};
	// Menu con theo danh sách cố định (thứ tự giống ketoananpha.vn): [nhãn, slug dịch vụ].
	$svc_item = function ( $label, $slug, $parent ) use ( $services, $add ) {
		if ( isset( $services[ $slug ] ) ) {
			$add( $label, '', $parent, array( 'menu-item-object' => 'dich_vu', 'menu-item-object-id' => $services[ $slug ], 'menu-item-type' => 'post_type' ) );
		}
	};
	// $overview = '' → không có mục tổng quan ở đầu menu con.
	$top = function ( $slug, $label, $overview, $items ) use ( $tax_item, $svc_item ) {
		$parent = $tax_item( $slug, $label );
		if ( $overview ) {
			$tax_item( $slug, $overview, $parent );
		}
		foreach ( $items as $it ) {
			$svc_item( $it[0], $it[1], $parent );
		}
		return $parent;
	};
	$add( 'Giới thiệu', $page( 'gioi-thieu' ) );
	$top(
		'thanh-lap-doanh-nghiep',
		'Thành lập công ty',
		'Dịch vụ thành lập công ty',
		array(
			array( 'Công ty TNHH', 'thanh-lap-cong-ty-tnhh' ),
			array( 'Công ty cổ phần', 'thanh-lap-cong-ty-co-phan' ),
			array( 'Công ty vốn nước ngoài', 'thanh-lap-cong-ty-von-nuoc-ngoai' ),
			array( 'FDI company establishment', 'fdi-company-establishment' ),
			array( 'Chi nhánh công ty', 'thanh-lap-chi-nhanh-van-phong-dai-dien' ),
			array( 'Hộ kinh doanh cá thể', 'dang-ky-ho-kinh-doanh' ),
		)
	);
	$top(
		'ke-toan',
		'Dịch vụ kế toán',
		'',
		array(
			array( 'Kế toán trọn gói', 'ke-toan-tron-goi' ),
			array( 'Kế toán nội bộ', 'ke-toan-noi-bo' ),
			array( 'Kế toán hộ kinh doanh', 'ke-toan-ho-kinh-doanh' ),
			array( 'Tax and accounting service', 'tax-and-accounting-service' ),
			array( 'Khai thuế ban đầu', 'khai-thue-ban-dau' ),
			array( 'Báo cáo tài chính', 'bao-cao-tai-chinh' ),
			array( 'Quyết toán thuế cuối năm', 'quyet-toan-thue-cuoi-nam' ),
			array( 'Làm sổ sách kế toán', 'ra-soat-lam-lai-so-sach-ke-toan' ),
			array( 'Hoàn thuế GTGT', 'hoan-thue-gtgt' ),
			array( 'Hoàn thuế TNCN', 'hoan-thue-tncn' ),
		)
	);
	$top(
		'thay-doi-giay-phep',
		'Thay đổi GPKD',
		'',
		array(
			array( 'Thay đổi tên', 'doi-ten-cong-ty' ),
			array( 'Đổi địa chỉ', 'thay-doi-dia-chi-cong-ty' ),
			array( 'Thêm ngành nghề', 'bo-sung-nganh-nghe-kinh-doanh' ),
			array( 'Tăng vốn điều lệ', 'tang-giam-von-dieu-le' ),
			array( 'Thêm cổ đông', 'them-giam-thanh-vien-co-dong' ),
			array( 'Đổi đại diện pháp luật', 'doi-dai-dien-phap-luat' ),
			array( 'Đổi loại hình công ty', 'chuyen-doi-loai-hinh-cong-ty' ),
			array( 'Cập nhật CCCD', 'cap-nhat-cccd-dang-ky-kinh-doanh' ),
		)
	);
	$top(
		'dich-vu-khac',
		'Dịch vụ khác',
		'',
		array(
			array( 'Hóa đơn điện tử', 'hoa-don-dien-tu' ),
			array( 'Bảo hiểm xã hội', 'dang-ky-bao-hiem-xa-hoi' ),
			array( 'Tạm ngừng kinh doanh', 'tam-ngung-kinh-doanh' ),
			array( 'Giải thể doanh nghiệp', 'giai-the-doanh-nghiep' ),
			array( 'Đăng ký kinh doanh', 'dang-ky-kinh-doanh' ),
			array( 'VPĐD nước ngoài', 'thanh-lap-van-phong-dai-dien-nuoc-ngoai' ),
			array( 'Đăng ký nhãn hiệu, logo', 'dang-ky-nhan-hieu' ),
			array( 'Chữ ký số', 'chu-ky-so' ),
			array( 'Chữ ký số và hoá đơn điện tử', 'chu-ky-so-hoa-don-dien-tu' ),
			array( 'Đăng ký MST cá nhân', 'dang-ky-ma-so-thue-ca-nhan' ),
			array( 'Soạn thảo hợp đồng', 'soan-thao-hop-dong' ),
		)
	);
	$training = $top(
		'dao-tao',
		'Đào tạo',
		'',
		array(
			array( 'Kế toán tổng hợp', 'khoa-hoc-ke-toan-tong-hop' ),
			array( 'Kế toán thuế', 'khoa-hoc-ke-toan-thue' ),
			array( 'Sổ sách kế toán', 'khoa-hoc-so-sach-ke-toan' ),
		)
	);
	// Chuyên mục bài viết: menu con dẫn tới trang chuyên mục (chỉ thêm khi đã có).
	$cat_item = function ( $label, $slug, $parent ) use ( $add ) {
		$c = get_term_by( 'slug', $slug, 'category' );
		if ( $c ) {
			$add( $label, '', $parent, array( 'menu-item-object' => 'category', 'menu-item-object-id' => $c->term_id, 'menu-item-type' => 'taxonomy' ) );
		}
	};
	$cat_item( 'Bài học kế toán', 'bai-hoc-ke-toan', $training );
	$news = $add( 'Kiến thức', ( $news_id ? get_permalink( $news_id ) : $page( 'tin-tuc' ) ) );
	$cat_item( 'Kiến thức kế toán', 'kien-thuc-ke-toan', $news );
	$cat_item( 'Kiến thức pháp lý', 'kien-thuc-phap-ly', $news );
	$add( 'Liên hệ', $page( 'lien-he' ) );
	$loc                   = get_theme_mod( 'nav_menu_locations', array() );
	$loc['primary']        = $menu_id;
	$loc['primary_mobile'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $loc );

}

/**
 * Chỉ dựng lại menu + tạo dịch vụ còn thiếu (không đụng trang chủ, dịch vụ đã sửa, header, footer).
 */
function sgd_demo_menu_run() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'sgd_demo_menu_run' );
	sgd_register_services();
	list( $groups, $services ) = sgd_demo_services( sgd_demo_data(), true );
	if ( ! get_page_by_path( 'tra-cuu', OBJECT, 'page' ) ) {
		sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'tra-cuu', 'post_title' => 'Tra cứu', 'post_content' => sgd_demo_lookup_content() ), array( '_wp_page_template' => 'page-blank.php' ) );
	}
	sgd_demo_rename_home_sections();
	sgd_demo_import_posts();
	sgd_demo_build_menu( $groups, $services );
	flush_rewrite_rules();
	wp_safe_redirect( admin_url( 'themes.php?page=sgd-demo&sgd_demo=menu' ) );
	exit;
}
add_action( 'admin_post_sgd_demo_menu_run', 'sgd_demo_menu_run' );

/**
 * Nội dung trang Tra cứu.
 *
 * @return string
 */
function sgd_demo_lookup_content() {
	return '[sgd_pagehead title="Tra cứu thông tin doanh nghiệp, thuế, hoá đơn" sub="Liên kết nhanh tới các cổng tra cứu chính thức của cơ quan nhà nước."]
[section bg_color="#ffffff" padding="50px"]
[row]
[col span="8" span__sm="12"]
[sgd_lookup]
[/col]
[col span="4" span__sm="12"]
<div id="dang-ky"></div>
[sgd_lead_form title="Cần hỗ trợ? Gửi yêu cầu" source="Trang tra cứu"]
[/col]
[/row]
[/section]';
}

/**
 * Đổi tên 3 khối dịch vụ chính trên trang chủ đang dùng (không đụng nội dung khác).
 */
function sgd_demo_rename_home_sections() {
	$id = (int) get_option( 'page_on_front' );
	if ( ! $id ) {
		return;
	}
	$c   = (string) get_post_field( 'post_content', $id );
	$new = strtr(
		$c,
		array(
			'title="Tư vấn thành lập công ty"'    => 'title="Dịch vụ thành lập công ty"',
			'title="Dịch vụ kế toán – thuế"'       => 'title="Dịch vụ kế toán"',
			'title="Thay đổi đăng ký kinh doanh"' => 'title="Thay đổi giấy phép kinh doanh"',
		)
	);
	if ( $new !== $c ) {
		wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $new ) ) );
	}
}

/**
 * Nhập 10 bài Kiến thức & Đào tạo (inc/demo-posts.php).
 * Bài chưa có → tạo mới; bài mẫu cũ cùng đường dẫn (… (bài mẫu)) → thay nội dung, giữ URL;
 * bài đã nhập mà chưa sửa tay → lên bản mới; bài đã sửa hoặc bạn tự viết → giữ nguyên.
 *
 * @return int Số bài tạo / cập nhật.
 */
function sgd_demo_import_posts() {
	$data = require SGD_DIR . '/inc/demo-posts.php';
	$cats = array();
	foreach ( $data['categories'] as $slug => $c ) {
		$cats[ $slug ] = sgd_demo_term( 'category', $c[0], $slug, $c[1], array( '_sgd_icon' => $c[3], '_sgd_order' => $c[4] ) );
		$parent        = $c[2] && isset( $cats[ $c[2] ] ) ? $cats[ $c[2] ] : 0;
		$term          = get_term( $cats[ $slug ], 'category' );
		if ( $term && ! is_wp_error( $term ) && (int) $term->parent !== $parent ) {
			wp_update_term( $term->term_id, 'category', array( 'parent' => $parent ) );
		}
	}
	$cat_ids = function ( $list ) use ( $cats ) {
		return array_values( array_filter( array_map( function ( $c ) use ( $cats ) {
			return isset( $cats[ $c ] ) ? $cats[ $c ] : 0;
		}, (array) $list ) ) );
	};
	$done = 0;
	$now  = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	foreach ( $data['posts'] as $i => $p ) {
		$old     = get_page_by_path( $p['slug'], OBJECT, 'post' );
		$content = sgd_demo_resolve_links( $p['content'] );
		if ( $old ) {
			$kind = get_post_meta( $old->ID, '_sgd_demo', true );
			// Bài đã nhập: chỉ cập nhật khi nội dung chưa bị sửa tay (so mã băm lúc nhập).
			$hash      = get_post_meta( $old->ID, '_sgd_hash', true );
			$untouched = 'article' === $kind && ( $hash ? md5( $old->post_content ) === $hash : in_array( sgd_demo_text_hash( $old->post_content ), sgd_demo_legacy_hashes(), true ) );
			if ( in_array( $kind, array( 'sample', 'article' ), true ) ) {
				// Gắn thêm chuyên mục theo cây mới (giữ chuyên mục bạn tự thêm), bỏ chuyên mục cũ của bản 0.10.2.
				wp_set_post_categories( $old->ID, $cat_ids( $p['cat'] ), true );
				wp_remove_object_terms( $old->ID, 'kien-thuc-thue', 'category' );
			}
			if ( 'sample' !== $kind && ! $untouched ) {
				continue;
			}
			if ( $untouched && md5( $content ) === $hash ) {
				continue; // Đã là bản mới nhất.
			}
		}
		$args = array(
			'post_type'     => 'post',
			'post_name'     => $p['slug'],
			'post_title'    => $p['title'],
			'post_excerpt'  => $p['excerpt'],
			'post_content'  => $content,
			'post_category' => $cat_ids( $p['cat'] ),
		);
		// Bài đầu danh sách mới nhất, cách nhau 1 ngày.
		$args['post_date']     = gmdate( 'Y-m-d H:i:s', $now - $i * DAY_IN_SECONDS );
		$args['post_date_gmt'] = get_gmt_from_date( $args['post_date'] );
		$id = sgd_demo_post( $args, array( '_sgd_demo' => 'article' ) );
		if ( $id ) {
			update_post_meta( $id, '_sgd_hash', md5( get_post_field( 'post_content', $id ) ) );
			++$done;
		}
	}
	// Chuyên mục cũ (bài mẫu, bản 0.10.2) – xoá nếu không còn bài nào.
	foreach ( array( 'kien-thuc-doanh-nghiep', 'kien-thuc-thue' ) as $old_slug ) {
		$legacy = get_term_by( 'slug', $old_slug, 'category' );
		if ( $legacy ) {
			$left = get_posts( array( 'category' => $legacy->term_id, 'posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'any' ) );
			if ( ! $left && (int) get_option( 'default_category' ) !== (int) $legacy->term_id ) {
				wp_delete_term( $legacy->term_id, 'category' );
			}
		}
	}
	return $done;
}

/**
 * Đổi [[slug|chữ]] → link trang dịch vụ, [[nhom:slug|chữ]] → link trang nhóm dịch vụ.
 * Không tìm thấy trang đích → giữ chữ, không tạo link.
 *
 * @param string $html Nội dung.
 * @return string
 */
function sgd_demo_resolve_links( $html ) {
	return preg_replace_callback(
		'/\[\[(?:(nhom):)?([a-z0-9-]+)\|([^\]]+)\]\]/u',
		function ( $m ) {
			$url = '';
			if ( 'nhom' === $m[1] ) {
				$t = get_term_by( 'slug', $m[2], 'nhom_dich_vu' );
				$url = $t ? get_term_link( $t ) : '';
			} else {
				$post = get_page_by_path( $m[2], OBJECT, 'dich_vu' );
				$url  = $post && 'publish' === $post->post_status ? get_permalink( $post ) : '';
			}
			return ( $url && ! is_wp_error( $url ) ) ? '<a href="' . esc_url( $url ) . '">' . $m[3] . '</a>' : $m[3];
		},
		$html
	);
}

/**
 * Mã băm phần chữ của bài (bỏ thẻ HTML, gộp khoảng trắng) – nhận diện bài chưa sửa tay.
 *
 * @param string $html Nội dung.
 * @return string
 */
function sgd_demo_text_hash( $html ) {
	return md5( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) ) );
}

/**
 * Mã băm 10 bài nhập ở bản 0.10.2 (khi đó chưa lưu _sgd_hash) – bài còn nguyên thì được lên bản đính chính.
 *
 * @return array
 */
function sgd_demo_legacy_hashes() {
	return array(
		'75125afe30228bdfd83f216e96dcac8f',
		'9715bb3247ebaebcf144ecc791c28f5b',
		'ac50779fd79d8d8b27db8b0c041eaaf9',
		'59795bd11eede6195f9486f7dccefb13',
		'6204f672eca615df6bba3f71d489450a',
		'7e3034d6b278daec841cd2d0c65bc636',
		'1494c3311301542606dac99879b41f32',
		'139a4033ffa8aaaf5f2ce9abc607e1a2',
		'8aced97170c2410d7a59360cd8b07f5f',
		'3270de03ba39ee228bb21537306900c5',
	);
}
