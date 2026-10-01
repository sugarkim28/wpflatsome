<?php
/**
 * Giao diện → Tạo site mẫu: dựng sẵn 4 nhóm dịch vụ, 16 dịch vụ (bảng giá, quy trình, hồ sơ, FAQ),
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
			<li>4 nhóm dịch vụ: <em>Thành lập doanh nghiệp, Thay đổi giấy phép kinh doanh, Dịch vụ thuế, Dịch vụ kế toán</em>.</li>
			<li>16 dịch vụ có sẵn nội dung, bảng giá theo gói, quy trình, hồ sơ cần chuẩn bị, câu hỏi thường gặp.</li>
			<li>3 bài viết mẫu; trang <em>Trang chủ</em>, <em>Bảng giá</em>, <em>Giới thiệu</em>, <em>Liên hệ</em>, <em>Tin tức</em> (sửa bằng UX Builder).</li>
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
			$content .= '<h2>' . esc_html( $h ) . "</h2>\n<p>" . esc_html( $p ) . "</p>\n";
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

	// 4. Trang.
	$blank   = array( '_wp_page_template' => 'page-blank.php' );
	$home    = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'trang-chu', 'post_title' => 'Trang chủ', 'post_content' => sgd_demo_home_content() ), $blank );
	$pricing = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'bang-gia', 'post_title' => 'Bảng giá dịch vụ', 'post_content' => sgd_demo_pricing_content( $data['groups'] ) ), $blank );
	$about   = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'gioi-thieu', 'post_title' => 'Giới thiệu', 'post_content' => sgd_demo_about_content() ), $blank );
	$contact = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'lien-he', 'post_title' => 'Liên hệ', 'post_content' => sgd_demo_contact_content() ), $blank );
	$news    = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => 'tin-tuc', 'post_title' => 'Tin tức', 'post_content' => '' ) );
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
	set_theme_mod( 'footer_left_text', 'Copyright [ux_current_year] © <strong>' . esc_html( sgd_opt( 'company_full' ) ) . '</strong>' );
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
	$short = array(
		'thanh-lap-doanh-nghiep' => 'Thành lập công ty',
		'thay-doi-giay-phep'     => 'Thay đổi GPKD',
		'dich-vu-thue'           => 'Thuế',
		'ke-toan'                => 'Kế toán',
	);
	$add( 'Trang chủ', home_url( '/' ) );
	foreach ( $groups as $slug => $tid ) {
		$parent = $add( isset( $short[ $slug ] ) ? $short[ $slug ] : get_term( $tid )->name, '', 0, array( 'menu-item-object' => 'nhom_dich_vu', 'menu-item-object-id' => $tid, 'menu-item-type' => 'taxonomy' ) );
		foreach ( $data['services'] as $s ) {
			if ( $s['group'] === $slug && isset( $services[ $s['slug'] ] ) ) {
				$add( $s['title'], '', $parent, array( 'menu-item-object' => 'dich_vu', 'menu-item-object-id' => $services[ $s['slug'] ], 'menu-item-type' => 'post_type' ) );
			}
		}
	}
	$add( 'Bảng giá', get_permalink( $pricing ) );
	$add( 'Tin tức', get_permalink( $news ) );
	$add( 'Liên hệ', get_permalink( $contact ) );
	$loc                   = get_theme_mod( 'nav_menu_locations', array() );
	$loc['primary']        = $menu_id;
	$loc['primary_mobile'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $loc );

	// 7. Header Flatsome.
	$hot = sgd_opt( 'hotline' );
	set_theme_mod( 'header_elements_left', array() );
	set_theme_mod( 'header_elements_right', array( 'nav', 'button-1' ) );
	set_theme_mod( 'header_mobile_elements_left', array( 'menu-icon' ) );
	set_theme_mod( 'header_mobile_elements_right', array() );
	set_theme_mod( 'mobile_sidebar', array( 'nav', 'button-1' ) );
	set_theme_mod( 'header_button_1', 'Hotline ' . $hot );
	set_theme_mod( 'header_button_1_link', 'tel:' . sgd_tel( $hot ) );
	set_theme_mod( 'header_button_1_color', 'secondary' );
	set_theme_mod( 'header_button_1_radius', '8px' );
	set_theme_mod( 'color_primary', sgd_opt( 'color_primary' ) );
	set_theme_mod( 'color_secondary', sgd_opt( 'color_accent' ) );
	set_theme_mod( 'header_bg', '#ffffff' );
	set_theme_mod( 'header_color', 'light' );
	set_theme_mod( 'type_nav_color', sgd_opt( 'color_primary' ) );
	set_theme_mod( 'type_nav_color_hover', '#d9480f' );
	set_theme_mod( 'nav_uppercase', 0 );
	set_theme_mod( 'header_height', 80 );
	set_theme_mod( 'header_sticky', 1 );
	set_theme_mod( 'topbar_show', 1 );
	set_theme_mod( 'topbar_bg', sgd_opt( 'color_primary' ) );
	set_theme_mod( 'topbar_color', 'dark' );
	set_theme_mod( 'topbar_elements_left', array( 'html' ) );
	set_theme_mod( 'topbar_elements_right', array() );
	set_theme_mod( 'topbar_left', esc_html( sgd_opt( 'working_hours' ) ) . ' &nbsp;·&nbsp; Email: ' . esc_html( sgd_opt( 'email' ) ) . ' &nbsp;·&nbsp; Tư vấn miễn phí: <strong>' . esc_html( $hot ) . '</strong>' );
	set_theme_mod( 'default_title', 0 );
	if ( ! get_theme_mod( 'site_logo' ) || false !== strpos( (string) get_theme_mod( 'site_logo' ), '/flatsome/assets/img/logo.png' ) ) {
		set_theme_mod( 'site_logo', '' ); // Chưa có logo → hiện tên công ty dạng chữ.
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
	return '[section label="Banner" bg_color="#0b3d6e" dark="true" padding="70px" padding__sm="40px" class="sgd-home-hero"]
[row v_align="middle"]
[col span="7" span__sm="12"]
<p class="sgd-kicker">' . $company . ' – tư vấn doanh nghiệp trọn gói</p>
<h1>Thành lập công ty, thay đổi giấy phép, thuế &amp; kế toán trọn gói</h1>
<p class="lead">Một đầu mối lo toàn bộ thủ tục pháp lý và thuế cho doanh nghiệp của bạn: báo giá rõ ràng, làm hồ sơ online, giao kết quả tận nơi.</p>
<ul class="sgd-check">
<li>Có giấy phép kinh doanh sau 3 – 5 ngày làm việc</li>
<li>Kế toán, báo cáo thuế trọn gói chỉ từ 500.000đ/tháng</li>
<li>Cam kết không phát sinh chi phí ngoài báo giá</li>
</ul>
[gap height="10px"]
[button text="Xem bảng giá" link="/bang-gia/" color="secondary" radius="8" size="large"] [button text="Gọi tư vấn" link="tel:' . esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ) . '" style="outline" color="white" radius="8" size="large"]
<div class="sgd-stats"><div class="sgd-stat"><strong>3–5</strong><span>ngày có giấy phép</span></div><div class="sgd-stat"><strong>0đ</strong><span>phí tư vấn</span></div><div class="sgd-stat"><strong>100%</strong><span>nộp hồ sơ online</span></div><div class="sgd-stat"><strong>1:1</strong><span>chuyên viên riêng</span></div></div>
[/col]
[col span="5" span__sm="12"]
[sgd_lead_form source="Trang chủ – banner" perks="0" note="0" title="Nhận tư vấn &amp; báo giá miễn phí"]
[/col]
[/row]
[/section]
[section label="Nhóm dịch vụ" bg_color="#ffffff" padding="70px"]
[row]
[col span="12"]
<p class="sgd-kicker text-center">Dịch vụ của chúng tôi</p>
[title style="center" text="Giải pháp pháp lý &amp; thuế cho mọi giai đoạn của doanh nghiệp" tag_name="h2" size="140"]
[sgd_groups columns="4" services="4"]
[/col]
[/row]
[/section]
[section label="Dịch vụ nổi bật" bg_color="#f2f6fb" padding="70px"]
[row]
[col span="12"]
<p class="sgd-kicker text-center">Được chọn nhiều nhất</p>
[title style="center" text="Dịch vụ nổi bật" tag_name="h2" size="140"]
[sgd_services number="6" featured="1"]
[gap height="24px"]
<p class="text-center">[button text="Xem tất cả dịch vụ" link="/dich-vu/" color="primary" radius="8" size="large"]</p>
[/col]
[/row]
[/section]
[section label="Bảng giá" bg_color="#ffffff" padding="70px"]
[row]
[col span="12"]
<p class="sgd-kicker text-center">Minh bạch chi phí</p>
[title style="center" text="Bảng giá trọn gói" tag_name="h2" size="140"]
[tabgroup style="pills" align="center"]
[tab title="Thành lập công ty TNHH"]
[sgd_pricing service="thanh-lap-cong-ty-tnhh"]
[/tab]
[tab title="Kế toán trọn gói"]
[sgd_pricing service="ke-toan-tron-goi"]
[/tab]
[tab title="Báo cáo thuế"]
[sgd_pricing service="bao-cao-thue-hang-thang-quy"]
[/tab]
[/tabgroup]
<p class="text-center"><a href="/bang-gia/">Xem bảng giá tất cả dịch vụ →</a></p>
[/col]
[/row]
[/section]
[section label="Quy trình" bg_color="#0b3d6e" dark="true" padding="70px" class="sgd-section-dark"]
[row]
[col span="12"]
<p class="sgd-kicker text-center">Đơn giản – nhanh chóng</p>
[title style="center" text="Quy trình làm việc 4 bước" tag_name="h2" size="140"]
[sgd_steps layout="row"]
Tư vấn miễn phí | Gọi hotline hoặc để lại thông tin, chuyên viên tư vấn và báo giá trọn gói
Soạn hồ sơ | Chúng tôi soạn toàn bộ hồ sơ, gửi bạn ký tại nhà hoặc ký số
Nộp &amp; theo dõi | Nộp online tới cơ quan nhà nước, cập nhật tiến độ qua Zalo
Bàn giao kết quả | Giao giấy phép, con dấu, hồ sơ tận nơi và hướng dẫn việc tiếp theo
[/sgd_steps]
[/col]
[/row]
[/section]
[section label="Vì sao chọn" bg_color="#ffffff" padding="70px"]
[row][col span="12"][title style="center" text="Vì sao chọn ' . esc_attr( sgd_opt( 'company' ) ) . '?" tag_name="h2" size="140"][/col][/row]
[row]
[col span="4" span__sm="12"]<div class="sgd-why">[sgd_icon name="wallet"]<h3>Báo giá trọn gói</h3><p>Giá công khai, ghi rõ trong hợp đồng. Không phát sinh chi phí ngoài báo giá.</p></div>[/col]
[col span="4" span__sm="12"]<div class="sgd-why">[sgd_icon name="clock"]<h3>Nhanh – đúng hạn</h3><p>Hồ sơ được kiểm tra kỹ trước khi nộp, hạn chế tối đa việc bị yêu cầu bổ sung.</p></div>[/col]
[col span="4" span__sm="12"]<div class="sgd-why">[sgd_icon name="users"]<h3>Chuyên viên riêng</h3><p>Mỗi khách hàng có một chuyên viên phụ trách, hỗ trợ qua điện thoại và Zalo.</p></div>[/col]
[/row]
[row]
[col span="4" span__sm="12"]<div class="sgd-why">[sgd_icon name="shield"]<h3>Bảo mật thông tin</h3><p>Giấy tờ, số liệu của doanh nghiệp được bảo mật theo hợp đồng.</p></div>[/col]
[col span="4" span__sm="12"]<div class="sgd-why">[sgd_icon name="globe"]<h3>Không cần đi lại</h3><p>Làm việc online, ký hồ sơ tại nhà, nhận kết quả tận nơi.</p></div>[/col]
[col span="4" span__sm="12"]<div class="sgd-why">[sgd_icon name="calculator"]<h3>Đồng hành lâu dài</h3><p>Sau thành lập, tiếp tục hỗ trợ thuế, kế toán, thay đổi giấy phép khi doanh nghiệp phát triển.</p></div>[/col]
[/row]
[/section]
[section label="Câu hỏi thường gặp" bg_color="#f2f6fb" padding="70px"]
[row h_align="center"]
[col span="9" span__sm="12"]
[title style="center" text="Câu hỏi thường gặp" tag_name="h2" size="140"]
[accordion]
[accordion-item title="Thành lập công ty mất bao lâu?"]Cơ quan đăng ký kinh doanh giải quyết trong 3 ngày làm việc kể từ khi nhận hồ sơ hợp lệ. Tính cả thời gian soạn hồ sơ, khắc dấu và giao nhận, thường mất 3 – 5 ngày làm việc.[/accordion-item]
[accordion-item title="Tôi có phải đến cơ quan nhà nước không?"]Không. Hồ sơ được nộp online; bạn chỉ cần cung cấp thông tin, ký hồ sơ tại nhà và nhận kết quả tận nơi.[/accordion-item]
[accordion-item title="Phí dịch vụ đã bao gồm lệ phí nhà nước chưa?"]Báo giá ghi rõ phí dịch vụ và lệ phí nhà nước (nếu có). Chúng tôi cam kết không thu thêm ngoài báo giá đã thống nhất.[/accordion-item]
[accordion-item title="Doanh nghiệp mới thành lập chưa có doanh thu có cần kế toán không?"]Có. Doanh nghiệp vẫn phải nộp tờ khai thuế định kỳ và báo cáo tài chính năm dù chưa phát sinh doanh thu. Gói kế toán Khởi nghiệp phù hợp cho giai đoạn này.[/accordion-item]
[accordion-item title="Làm sao gửi chứng từ kế toán hằng tháng?"]Bạn có thể gửi ảnh chụp/bản điện tử qua Zalo, email; hóa đơn điện tử được tra cứu trực tiếp trên hệ thống. Với chứng từ giấy, chúng tôi nhận tận nơi theo lịch.[/accordion-item]
[/accordion]
[/col]
[/row]
[/section]
[section label="Tin tức" bg_color="#ffffff" padding="70px"]
[row]
[col span="12"]
[title style="center" text="Kiến thức doanh nghiệp" tag_name="h2" size="140"]
[blog_posts style="normal" columns="3" columns__sm="1" posts="3" image_height="56%" show_date="text" excerpt_length="20"]
[/col]
[/row]
[/section]
[section label="Đăng ký" bg_color="#072a4d" dark="true" padding="60px" class="sgd-section-dark"]
<div id="dang-ky"></div>
[row v_align="middle"]
[col span="7" span__sm="12"]
<h2>Bắt đầu ngay hôm nay – tư vấn miễn phí</h2>
<p>Để lại thông tin, chuyên viên gọi lại trong ít phút (giờ hành chính) để tư vấn phương án và báo giá trọn gói cho doanh nghiệp của bạn.</p>
[sgd_call_buttons]
[/col]
[col span="5" span__sm="12"]
[sgd_lead_form source="Trang chủ" perks="0"]
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
	$out = '[section bg_color="#f2f6fb" padding="50px"]
[row][col span="12"]
<h1>Bảng giá dịch vụ</h1>
<p class="lead">Phí dịch vụ thành lập công ty, thay đổi giấy phép kinh doanh, thuế và kế toán. Bấm vào từng dịch vụ để xem chi tiết các gói. ' . esc_html( sgd_opt( 'disclaimer' ) ) . '</p>
[/col][/row]
[/section]
[section bg_color="#ffffff" padding="50px"]';
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
[section bg_color="#072a4d" dark="true" padding="50px" class="sgd-section-dark"]
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
	return '[section bg_color="#f2f6fb" padding="50px"]
[row][col span="12"]
<h1>Giới thiệu ' . esc_html( sgd_opt( 'company' ) ) . '</h1>
<p class="lead">' . esc_html( sgd_opt( 'company_full' ) ) . ' – ' . esc_html( sgd_opt( 'tagline' ) ) . '.</p>
[/col][/row]
[/section]
[section bg_color="#ffffff" padding="50px"]
[row][col span="8" span__sm="12"]
<p><em>Nội dung mẫu – thay bằng giới thiệu thật của công ty.</em></p>
<h2>Chúng tôi là ai</h2><p>Giới thiệu năm thành lập, lĩnh vực hoạt động, số doanh nghiệp đã hỗ trợ thành lập, số khách hàng kế toán đang phục vụ.</p>
<h2>Đội ngũ</h2><p>Giới thiệu luật sư, chuyên viên pháp lý, kế toán viên, chứng chỉ hành nghề (đại lý thuế, kế toán…) kèm ảnh thật – giúp khách hàng tin tưởng và tốt cho SEO.</p>
<h2>Cam kết</h2><p>Báo giá trọn gói, không phát sinh; đúng hạn; bảo mật thông tin; chịu trách nhiệm với kết quả công việc theo hợp đồng.</p>
<h2>Thông tin pháp nhân</h2><p>' . esc_html( sgd_opt( 'company_full' ) ) . '<br>Trụ sở: [sgd_company field="address"]<br>Mã số thuế: [sgd_company field="tax_code"]<br>Hotline: [sgd_company field="hotline"]</p>
[/col]
[col span="4" span__sm="12"]
<div id="dang-ky"></div>
[sgd_lead_form source="Trang giới thiệu"]
[/col][/row]
[/section]';
}

/**
 * Nội dung trang Liên hệ.
 *
 * @return string
 */
function sgd_demo_contact_content() {
	$map = 'https://www.google.com/maps?q=' . rawurlencode( sgd_opt( 'address' ) ) . '&output=embed';
	return '[section bg_color="#f2f6fb" padding="50px"]
[row][col span="12"]
<h1>Liên hệ ' . esc_html( sgd_opt( 'company' ) ) . '</h1>
<p class="lead">Gọi hotline, nhắn Zalo hoặc gửi yêu cầu – chuyên viên sẽ liên hệ lại trong thời gian sớm nhất.</p>
[/col][/row]
[/section]
[section bg_color="#ffffff" padding="50px"]
[row]
[col span="6" span__sm="12"]
<h2>Thông tin liên hệ</h2>
<p><strong>' . esc_html( sgd_opt( 'company_full' ) ) . '</strong></p>
<p>Trụ sở: [sgd_company field="address"]<br>Hotline: [sgd_company field="hotline"]<br>Email: [sgd_company field="email"]<br>Giờ làm việc: [sgd_company field="working_hours"]</p>
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
	foreach ( array( 'pricing' => 'Bảng giá', 'about' => 'Giới thiệu', 'news' => 'Tin tức', 'contact' => 'Liên hệ' ) as $k => $label ) {
		if ( ! empty( $pages[ $k ] ) ) {
			$p .= '<li><a href="' . esc_url( get_permalink( $pages[ $k ] ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	return '[section bg_color="#06233f" dark="true" padding="50px" class="sgd-footer"]
[row]
[col span="4" span__sm="12"]
<h3>[sgd_company field="company"]</h3>
<p><strong>[sgd_company field="company_full"]</strong></p>
<p>Trụ sở: [sgd_company field="address"]<br>MST: [sgd_company field="tax_code"]<br>Hotline: [sgd_company field="hotline"]<br>Email: [sgd_company field="email"]<br>Giờ làm việc: [sgd_company field="working_hours"]</p>
[/col]
[col span="3" span__sm="6"]
<h3>Dịch vụ</h3>
<ul><li><a href="/dich-vu/">Tất cả dịch vụ</a></li>' . $g . '</ul>
[/col]
[col span="2" span__sm="6"]
<h3>Thông tin</h3>
<ul>' . $p . '</ul>
[/col]
[col span="3" span__sm="12"]
<h3>Tư vấn miễn phí</h3>
<p>Gọi hoặc nhắn Zalo để được tư vấn và báo giá trọn gói.</p>
[sgd_call_buttons]
[/col]
[/row]
[/section]';
}
