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
		<?php elseif ( 'cleaned' === $msg ) : ?>
			<div class="notice notice-success"><p>Đã xoá bài viết mẫu.</p></div>
		<?php endif; ?>
		<p><strong>Trước khi bấm:</strong> nhập tên công ty, hotline, Zalo, địa chỉ ở <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=sgd_company' ) ); ?>">Tuỳ biến → Website dịch vụ</a> (trang chủ, footer, header lấy thông tin từ đó).</p>
		<p>Nút này dựng sẵn:</p>
		<ul style="list-style:disc;margin-left:20px">
			<li>5 nhóm dịch vụ: <em>Thành lập doanh nghiệp, Thay đổi giấy phép kinh doanh, Dịch vụ thuế, Dịch vụ kế toán, Dịch vụ khác</em>.</li>
			<li>20 dịch vụ có sẵn nội dung, chi phí trọn gói, bảng giá (gói / bảng theo số hóa đơn), quy trình, hồ sơ cần chuẩn bị, câu hỏi thường gặp.</li>
			<li>3 bài viết mẫu; trang <em>Trang chủ</em>, <em>Bảng giá</em>, <em>Giới thiệu</em>, <em>Liên hệ</em>, <em>Kiến thức</em> (sửa bằng UX Builder).</li>
			<li>Menu chính, footer (UX Block "Footer website"), header Flatsome.</li>
		</ul>
		<p><strong>Lưu ý:</strong> giá trong dữ liệu mẫu là <strong>giá minh hoạ</strong> – sửa theo bảng giá thật (Dịch vụ → sửa từng dịch vụ). Chạy lại sẽ đưa các dịch vụ/trang mẫu về nội dung gốc (dịch vụ bạn tự thêm không bị ảnh hưởng).</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:10px" onsubmit="return confirm('Tạo / cập nhật site mẫu? Các dịch vụ và trang mẫu sẽ được ghi đè về nội dung gốc.');">
			<input type="hidden" name="action" value="sgd_demo_run">
			<?php wp_nonce_field( 'sgd_demo_run' ); ?>
			<?php submit_button( 'Tạo site mẫu', 'primary hero', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block" onsubmit="return confirm('Xoá 3 bài viết mẫu?');">
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
	$data = require SGD_DIR . '/inc/demo-data.php';

	// 1. Nhóm dịch vụ.
	$groups = array();
	foreach ( $data['groups'] as $slug => $g ) {
		$groups[ $slug ] = sgd_demo_term( 'nhom_dich_vu', $g[0], $slug, $g[3], array( '_sgd_icon' => $g[1], '_sgd_order' => $g[2] ) );
	}

	// 2. Dịch vụ.
	$services = array();
	foreach ( $data['services'] as $i => $s ) {
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
	$link = function ( $slug ) use ( $services ) {
		return isset( $services[ $slug ] ) ? get_permalink( $services[ $slug ] ) : get_post_type_archive_link( 'dich_vu' );
	};

	// 3. Bài viết mẫu.
	$cat   = sgd_demo_term( 'category', 'Kiến thức doanh nghiệp', 'kien-thuc-doanh-nghiep' );
	$posts = array(
		array(
			'thu-tuc-thanh-lap-cong-ty',
			'Thủ tục thành lập công ty: hồ sơ, các bước và lưu ý (bài mẫu)',
			'<p><em>Bài viết mẫu – minh hoạ cách viết bài chuẩn SEO và liên kết về trang dịch vụ. Kiểm tra lại quy định mới nhất trước khi đăng.</em></p>'
			. '<h2>Chọn loại hình doanh nghiệp</h2><p>Công ty TNHH phù hợp với ít thành viên, quản lý gọn; công ty cổ phần phù hợp khi có từ 3 cổ đông và cần huy động vốn. Xem chi tiết: <a href="' . esc_url( $link( 'thanh-lap-cong-ty-tnhh' ) ) . '">dịch vụ thành lập công ty TNHH</a>, <a href="' . esc_url( $link( 'thanh-lap-cong-ty-co-phan' ) ) . '">thành lập công ty cổ phần</a>.</p>'
			. '<h2>Chuẩn bị thông tin</h2><p>Tên công ty, địa chỉ trụ sở, vốn điều lệ, ngành nghề, người đại diện theo pháp luật và giấy tờ pháp lý của các thành viên.</p>'
			. '<h2>Nộp hồ sơ và nhận kết quả</h2><p>Hồ sơ nộp trực tuyến qua Cổng thông tin quốc gia về đăng ký doanh nghiệp; thời hạn giải quyết 3 ngày làm việc kể từ khi nhận hồ sơ hợp lệ.</p>'
			. '<h2>Việc cần làm sau khi thành lập</h2><p>Mở tài khoản ngân hàng, chữ ký số, hóa đơn điện tử, kê khai thuế ban đầu, góp vốn trong 90 ngày. Tham khảo <a href="' . esc_url( $link( 'khai-thue-ban-dau' ) ) . '">dịch vụ khai thuế ban đầu</a>.</p>',
		),
		array(
			'lich-nop-to-khai-thue',
			'Lịch nộp tờ khai thuế cho doanh nghiệp nhỏ (bài mẫu)',
			'<p><em>Bài viết mẫu – kiểm tra lại quy định mới nhất trước khi đăng.</em></p>'
			. '<h2>Kê khai theo tháng</h2><p>Nộp tờ khai chậm nhất ngày 20 của tháng tiếp theo.</p>'
			. '<h2>Kê khai theo quý</h2><p>Nộp tờ khai chậm nhất ngày cuối cùng của tháng đầu quý tiếp theo.</p>'
			. '<h2>Quyết toán năm</h2><p>Báo cáo tài chính, quyết toán thuế TNDN, TNCN nộp chậm nhất ngày cuối cùng của tháng thứ 3 sau khi kết thúc năm tài chính. Xem <a href="' . esc_url( $link( 'quyet-toan-thue-cuoi-nam' ) ) . '">dịch vụ quyết toán thuế</a> và <a href="' . esc_url( $link( 'ke-toan-tron-goi' ) ) . '">kế toán trọn gói</a>.</p>',
		),
		array(
			'khi-nao-phai-thay-doi-giay-phep-kinh-doanh',
			'Khi nào doanh nghiệp phải đăng ký thay đổi giấy phép kinh doanh? (bài mẫu)',
			'<p><em>Bài viết mẫu – kiểm tra lại quy định mới nhất trước khi đăng.</em></p>'
			. '<h2>Các nội dung phải đăng ký thay đổi</h2><p>Tên, địa chỉ trụ sở, người đại diện theo pháp luật, vốn điều lệ, thành viên/cổ đông, ngành nghề kinh doanh…</p>'
			. '<h2>Thời hạn đăng ký</h2><p>Trong 10 ngày kể từ ngày có thay đổi. Chậm đăng ký có thể bị xử phạt vi phạm hành chính.</p>'
			. '<h2>Dịch vụ hỗ trợ</h2><p><a href="' . esc_url( $link( 'thay-doi-dia-chi-cong-ty' ) ) . '">Thay đổi địa chỉ công ty</a>, <a href="' . esc_url( $link( 'bo-sung-nganh-nghe-kinh-doanh' ) ) . '">bổ sung ngành nghề</a>, <a href="' . esc_url( $link( 'tang-giam-von-dieu-le' ) ) . '">tăng giảm vốn điều lệ</a>.</p>',
		),
	);
	foreach ( $posts as $p ) {
		sgd_demo_post(
			array(
				'post_type'     => 'post',
				'post_name'     => $p[0],
				'post_title'    => $p[1],
				'post_content'  => $p[2],
				'post_category' => array( $cat ),
			),
			array( '_sgd_demo' => 'sample' )
		);
	}

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
	$service_links = function ( $slugs, $parent ) use ( $data, $services, $add ) {
		foreach ( $data['services'] as $s ) {
			if ( in_array( $s['group'], (array) $slugs, true ) && isset( $services[ $s['slug'] ] ) ) {
				$add( $s['title'], '', $parent, array( 'menu-item-object' => 'dich_vu', 'menu-item-object-id' => $services[ $s['slug'] ], 'menu-item-type' => 'post_type' ) );
			}
		}
	};
	$tax_item = function ( $slug, $label, $parent = 0 ) use ( $groups, $add ) {
		return $add( $label, '', $parent, array( 'menu-item-object' => 'nhom_dich_vu', 'menu-item-object-id' => $groups[ $slug ], 'menu-item-type' => 'taxonomy' ) );
	};
	$top = function ( $slug, $label, $overview, $service_groups ) use ( $tax_item, $service_links ) {
		$parent = $tax_item( $slug, $label );
		$tax_item( $slug, $overview, $parent );
		$service_links( $service_groups, $parent );
		return $parent;
	};
	$add( 'Giới thiệu', get_permalink( $about ) );
	$top( 'thanh-lap-doanh-nghiep', 'Dịch vụ thành lập', 'Tổng quan thành lập công ty', 'thanh-lap-doanh-nghiep' );
	$acc = $top( 'ke-toan', 'Dịch vụ kế toán', 'Tổng quan kế toán – thuế', 'ke-toan' );
	$tax_item( 'dich-vu-thue', 'Dịch vụ thuế', $acc );
	$service_links( 'dich-vu-thue', $acc );
	$top( 'thay-doi-giay-phep', 'Thay đổi GPKD', 'Tổng quan thay đổi giấy phép', 'thay-doi-giay-phep' );
	$top( 'dich-vu-khac', 'Dịch vụ khác', 'Tổng quan dịch vụ khác', 'dich-vu-khac' );
	$add( 'Kiến thức', get_permalink( $news ) );
	$add( 'Liên hệ', get_permalink( $contact ) );
	$loc                   = get_theme_mod( 'nav_menu_locations', array() );
	$loc['primary']        = $menu_id;
	$loc['primary_mobile'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $loc );

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
[sgd_group_section group="thanh-lap-doanh-nghiep" title="Tư vấn thành lập công ty" price="0"]
[/col]
[/row]
[/section]
[section label="5. Dịch vụ kế toán – thuế" bg_color="#ffffff" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="ke-toan,dich-vu-thue" title="Dịch vụ kế toán – thuế" flip="1" price="0"]
[/col]
[/row]
[/section]
[section label="6. Thay đổi GPKD" bg_color="#f4f7fc" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="thay-doi-giay-phep" title="Thay đổi đăng ký kinh doanh" price="0"]
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
Tiếp nhận thông tin | Lắng nghe nhu cầu, tư vấn quy định và báo giá trọn gói miễn phí
Soạn hồ sơ | Soạn hồ sơ theo quy trình chuẩn, gửi khách ký tại nhà hoặc ký số
Nộp &amp; theo dõi | Nộp hồ sơ, theo dõi và báo tiến độ qua Zalo
Bàn giao kết quả | Giao giấy phép, con dấu, hồ sơ tận nơi và hướng dẫn việc tiếp theo
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
<p>Tiếp nhận nhu cầu và tư vấn miễn phí → báo giá trọn gói → soạn hồ sơ, khách ký tại nhà hoặc ký số → nộp hồ sơ trực tuyến, theo dõi kết quả → bàn giao tận nơi và hướng dẫn các việc tiếp theo.</p>
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
