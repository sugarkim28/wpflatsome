<?php
/**
 * Mục "Dịch vụ" + "Nhóm dịch vụ" + ô thông tin (giá, thời gian, gói, quy trình, hồ sơ, FAQ).
 *
 * URL: /dich-vu/ · /dich-vu/ten-dich-vu/ · /nhom-dich-vu/thanh-lap-doanh-nghiep/
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bộ icon SVG (nét, 24x24) – dùng cho dịch vụ, nhóm dịch vụ, lý do chọn.
 *
 * @return array
 */
function sgd_icons() {
	return array(
		'building'   => array( 'Toà nhà', '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10"/><path d="M2 21h20M8 7h4M8 11h4M8 15h4"/>' ),
		'edit'       => array( 'Sửa giấy tờ', '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M18.4 2.6a2 2 0 0 1 2.9 2.9L12 14.8l-4 1 1-4z"/>' ),
		'tax'        => array( 'Thuế', '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 8l6 6M9.5 13.5h0M14.5 8.5h0"/><circle cx="9.5" cy="8.5" r="1"/><circle cx="14.5" cy="13.5" r="1"/>' ),
		'calculator' => array( 'Kế toán', '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15v3M8 18h.01M12 18h.01"/>' ),
		'stamp'      => array( 'Con dấu', '<path d="M9 3h6l-1 7h-4z"/><path d="M5 14h14a1 1 0 0 1 1 1v3H4v-3a1 1 0 0 1 1-1zM4 21h16"/>' ),
		'chart'      => array( 'Báo cáo', '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>' ),
		'shield'     => array( 'An toàn', '<path d="M12 2l8 3v6c0 5-3.5 9-8 11-4.5-2-8-6-8-11V5z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>' ),
		'doc'        => array( 'Hồ sơ', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>' ),
		'clock'      => array( 'Nhanh', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>' ),
		'users'      => array( 'Đội ngũ', '<circle cx="9" cy="8" r="3.5"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 21a7 7 0 0 0-4-6.3"/>' ),
		'wallet'     => array( 'Chi phí', '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h15V9H5a2 2 0 0 1-2-2z"/><circle cx="16" cy="14.5" r="1.2"/>' ),
		'search'     => array( 'Tìm kiếm', '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>' ),
		'pin'        => array( 'Địa chỉ', '<path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.6"/>' ),
		'phone'      => array( 'Điện thoại', '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 5a2 2 0 0 1 2-2z"/>' ),
		'mail'       => array( 'Email', '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>' ),
		'trademark'  => array( 'Nhãn hiệu', '<circle cx="12" cy="12" r="9"/><path d="M8 9h4M10 9v6M13.5 15V9l1.75 3L17 9v6"/>' ),
		'globe'      => array( 'Nước ngoài', '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>' ),
	);
}

/**
 * In icon SVG.
 *
 * @param string $name Tên icon.
 * @return string
 */
function sgd_icon( $name ) {
	$icons = sgd_icons();
	$name  = isset( $icons[ $name ] ) ? $name : 'doc';
	return '<svg class="sgd-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ][1] . '</svg>';
}

/**
 * Các ô thông tin của dịch vụ: key => [nhãn, kiểu, gợi ý].
 *
 * @return array
 */
function sgd_service_fields() {
	return array(
		'short'      => array( 'Tên ngắn trên banner (vd: THÀNH LẬP CÔNG TY TNHH)', 'text', 'Thành lập công ty TNHH' ),
		'subtitle'   => array( 'Mô tả ngắn dưới tiêu đề', 'text', 'Trọn gói từ A–Z, có giấy phép sau 3 ngày làm việc' ),
		'price'      => array( 'Giá hiển thị', 'text', 'Từ 1.000.000đ' ),
		'duration'   => array( 'Thời gian hoàn thành', 'text', '3 – 5 ngày làm việc' ),
		'icon'       => array( 'Icon', 'icon', '' ),
		'costs'      => array( 'Chi phí trọn gói – mỗi dòng: Khoản | Số tiền (dòng bắt đầu bằng "Tổng" được in đậm)', 'textarea', "Phí dịch vụ | 250.000đ\nLệ phí nhà nước | Theo quy định\nTổng chi phí trọn gói | 1.000.000đ" ),
		'price_table' => array( 'Bảng giá dạng bảng – dòng "## Tiêu đề" mở bảng mới, dòng kế tiếp là tiêu đề cột, các dòng sau: Ô 1 | Ô 2 | …; dòng bắt đầu "* " là ghi chú', 'textarea', "## Bảng giá kế toán trọn gói (theo quý)\nSố hóa đơn/quý | Dịch vụ | Thương mại\nKhông có hóa đơn | 1.500.000đ | 1.500.000đ\n* Giá chưa gồm VAT" ),
		'includes'   => array( 'Công việc chúng tôi thực hiện – mỗi dòng 1 ý', 'textarea', "Tư vấn loại hình, vốn, ngành nghề\nSoạn và nộp hồ sơ online" ),
		'documents'  => array( 'Hồ sơ khách hàng cần chuẩn bị – mỗi dòng 1 ý', 'textarea', "CCCD của thành viên/cổ đông\nĐịa chỉ trụ sở" ),
		'process'    => array( 'Quy trình – mỗi dòng: Bước | Mô tả', 'textarea', "Tiếp nhận yêu cầu | Tư vấn miễn phí, báo giá trọn gói\nSoạn hồ sơ | ..." ),
		'packages'   => array( 'Các gói – mỗi dòng: Tên gói | Giá | Bao gồm (ngăn bằng ;) | * (gói nổi bật) | Tiết kiệm (tuỳ chọn) | Giá ưu đãi kèm ghi chú, vd: 1.400.000đ (khi dùng dịch vụ kế toán) (tuỳ chọn)', 'textarea', "Cơ bản | 1.000.000đ | Giấy phép; Con dấu |\nTrọn gói | 2.500.000đ | Giấy phép; Con dấu; Chữ ký số | *" ),
		'faq'        => array( 'Câu hỏi thường gặp – mỗi dòng: Câu hỏi | Trả lời', 'textarea', 'Cần bao nhiêu vốn để thành lập công ty? | Pháp luật không quy định vốn tối thiểu với đa số ngành nghề…' ),
		'lead_email' => array( 'Email nhận khách của dịch vụ (để trống = email chung)', 'email', '' ),
		'featured'   => array( 'Dịch vụ nổi bật (hiện ở trang chủ)', 'checkbox', '' ),
	);
}

/**
 * Đọc 1 ô thông tin dịch vụ.
 *
 * @param string   $key Khoá.
 * @param int|null $id  ID dịch vụ.
 * @return string
 */
function sgd_meta( $key, $id = null ) {
	return (string) get_post_meta( $id ? $id : get_the_ID(), '_sgd_' . $key, true );
}

/**
 * Danh sách ý (mỗi dòng 1 ý).
 *
 * @param string $text Nội dung.
 * @return array
 */
function sgd_list( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
}

/**
 * Bảng giá đã tách: [ ['name','price','items'=>[],'hot'=>bool], ... ].
 *
 * @param int|null $id ID dịch vụ.
 * @return array
 */
function sgd_packages( $id = null ) {
	$out = array();
	foreach ( sgd_lines( sgd_meta( 'packages', $id ), 6 ) as $p ) {
		if ( '' === $p[0] ) {
			continue;
		}
		$out[] = array(
			'name'  => $p[0],
			'price' => $p[1],
			'items' => array_values( array_filter( array_map( 'trim', explode( ';', $p[2] ) ), 'strlen' ) ),
			'hot'   => '*' === trim( $p[3] ),
			'save'  => trim( $p[4] ),
			'combo' => trim( $p[5] ),
		);
	}
	return $out;
}

/**
 * Nhóm dịch vụ đầu tiên.
 *
 * @param int|null $id ID.
 * @return WP_Term|null
 */
function sgd_first_group( $id = null ) {
	$terms = get_the_terms( $id ? $id : get_the_ID(), 'nhom_dich_vu' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * Đăng ký mục Dịch vụ + Nhóm dịch vụ.
 */
function sgd_register_services() {
	register_post_type(
		'dich_vu',
		array(
			'labels'        => array(
				'name'          => 'Dịch vụ',
				'singular_name' => 'Dịch vụ',
				'add_new'       => 'Thêm dịch vụ',
				'add_new_item'  => 'Thêm dịch vụ mới',
				'edit_item'     => 'Sửa dịch vụ',
				'all_items'     => 'Tất cả dịch vụ',
				'search_items'  => 'Tìm dịch vụ',
				'not_found'     => 'Chưa có dịch vụ',
			),
			'public'        => true,
			'has_archive'   => 'dich-vu',
			'rewrite'       => array( 'slug' => 'dich-vu', 'with_front' => false ),
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions' ),
			'show_in_rest'  => true,
		)
	);
	register_taxonomy(
		'nhom_dich_vu',
		'dich_vu',
		array(
			'labels'            => array(
				'name'          => 'Nhóm dịch vụ',
				'singular_name' => 'Nhóm dịch vụ',
				'add_new_item'  => 'Thêm nhóm dịch vụ',
				'edit_item'     => 'Sửa nhóm dịch vụ',
			),
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'nhom-dich-vu', 'with_front' => false ),
		)
	);
}
add_action( 'init', 'sgd_register_services' );

/**
 * Tự làm mới đường dẫn sau khi kích hoạt theme.
 */
function sgd_flush_on_switch() {
	sgd_register_services();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'sgd_flush_on_switch' );

/**
 * Hộp nhập thông tin dịch vụ.
 */
function sgd_service_meta_box() {
	add_meta_box( 'sgd_service', 'Thông tin dịch vụ', 'sgd_service_meta_box_html', 'dich_vu', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'sgd_service_meta_box' );

/**
 * Ô chọn icon.
 *
 * @param string $name  Tên field.
 * @param string $value Giá trị.
 * @param string $id    ID.
 * @return string
 */
function sgd_icon_select( $name, $value, $id = '' ) {
	$html = '<select name="' . esc_attr( $name ) . '"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '><option value="">— Mặc định —</option>';
	foreach ( sgd_icons() as $k => $ic ) {
		$html .= '<option value="' . esc_attr( $k ) . '" ' . selected( $value, $k, false ) . '>' . esc_html( $ic[0] ) . '</option>';
	}
	return $html . '</select>';
}

/**
 * HTML hộp thông tin.
 *
 * @param WP_Post $post Bài.
 */
function sgd_service_meta_box_html( $post ) {
	wp_nonce_field( 'sgd_service', 'sgd_service_nonce' );
	echo '<p style="color:#646970">Nội dung chi tiết viết ở trình soạn thảo phía trên, chia mục bằng <strong>Tiêu đề 2 (H2)</strong>. Bảng giá, công việc, hồ sơ, quy trình và FAQ bên dưới tự hiển thị đẹp trên trang (FAQ có dữ liệu cho Google).</p>';
	echo '<table class="form-table" role="presentation">';
	foreach ( sgd_service_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, '_sgd_' . $key, true );
		$id  = 'sgd-' . $key;
		echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		switch ( $f[1] ) {
			case 'textarea':
				printf( '<textarea id="%1$s" name="sgd[%2$s]" rows="5" class="large-text" placeholder="%4$s">%3$s</textarea>', esc_attr( $id ), esc_attr( $key ), esc_textarea( $val ), esc_attr( $f[2] ) );
				break;
			case 'checkbox':
				printf( '<label><input type="checkbox" id="%1$s" name="sgd[%2$s]" value="1" %3$s> Có</label>', esc_attr( $id ), esc_attr( $key ), checked( $val, '1', false ) );
				break;
			case 'icon':
				echo sgd_icon_select( 'sgd[' . $key . ']', $val, $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm.
				break;
			default:
				printf( '<input type="%5$s" id="%1$s" name="sgd[%2$s]" value="%3$s" class="large-text" placeholder="%4$s">', esc_attr( $id ), esc_attr( $key ), esc_attr( $val ), esc_attr( $f[2] ), 'email' === $f[1] ? 'email' : 'text' );
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

/**
 * Lưu thông tin dịch vụ.
 *
 * @param int $post_id ID.
 */
function sgd_save_service( $post_id ) {
	if ( ! isset( $_POST['sgd_service_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sgd_service_nonce'] ) ), 'sgd_service' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['sgd'] ) && is_array( $_POST['sgd'] ) ? wp_unslash( $_POST['sgd'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng ô bên dưới.
	foreach ( sgd_service_fields() as $key => $f ) {
		$v = isset( $in[ $key ] ) ? $in[ $key ] : '';
		switch ( $f[1] ) {
			case 'textarea':
				$v = sanitize_textarea_field( $v );
				break;
			case 'checkbox':
				$v = $v ? '1' : '';
				break;
			case 'email':
				$v = sanitize_email( $v );
				break;
			case 'icon':
				$v = array_key_exists( $v, sgd_icons() ) ? $v : '';
				break;
			default:
				$v = sanitize_text_field( $v );
		}
		if ( '' === $v ) {
			delete_post_meta( $post_id, '_sgd_' . $key );
		} else {
			update_post_meta( $post_id, '_sgd_' . $key, $v );
		}
	}
}
add_action( 'save_post_dich_vu', 'sgd_save_service' );

/**
 * Ô icon + tiêu đề H1 riêng cho Nhóm dịch vụ (trang sửa nhóm).
 *
 * @param WP_Term $term Nhóm.
 */
function sgd_group_edit_fields( $term ) {
	wp_nonce_field( 'sgd_group', 'sgd_group_nonce' );
	?>
	<tr class="form-field"><th><label for="sgd-group-icon">Icon</label></th>
		<td><?php echo sgd_icon_select( 'sgd_icon', get_term_meta( $term->term_id, '_sgd_icon', true ), 'sgd-group-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
	<tr class="form-field"><th><label for="sgd-group-order">Thứ tự</label></th>
		<td><input id="sgd-group-order" name="sgd_order" type="number" style="width:90px" value="<?php echo esc_attr( (int) get_term_meta( $term->term_id, '_sgd_order', true ) ); ?>">
			<p class="description">Số nhỏ đứng trước (dùng ở khối nhóm dịch vụ trang chủ).</p></td></tr>
	<tr class="form-field"><th><label for="sgd-group-h1">Tiêu đề H1 (tuỳ chọn)</label></th>
		<td><input id="sgd-group-h1" name="sgd_h1" type="text" value="<?php echo esc_attr( get_term_meta( $term->term_id, '_sgd_h1', true ) ); ?>">
			<p class="description">Để trống = "Dịch vụ " + tên nhóm. Mô tả nhóm (150–300 chữ) hiện ở đầu trang nhóm.</p></td></tr>
	<tr class="form-field"><th><label for="sgd-group-image">Ảnh đại diện nhóm (tuỳ chọn)</label></th>
		<td><input id="sgd-group-image" name="sgd_image" type="url" value="<?php echo esc_attr( get_term_meta( $term->term_id, '_sgd_image', true ) ); ?>" placeholder="https://…/anh-nhom.jpg">
			<p class="description">Dán đường dẫn ảnh từ Thư viện Media (ngang, khoảng 1100×700). Hiện đầu bài viết của nhóm; để trống = khung màu thương hiệu.</p></td></tr>
	<tr class="form-field"><th><label for="sgd_article">Bài viết của nhóm</label></th>
		<td><?php wp_editor( (string) get_term_meta( $term->term_id, '_sgd_article', true ), 'sgd_article', array( 'textarea_rows' => 18, 'media_buttons' => true ) ); ?>
			<p class="description">Bài viết chuẩn SEO hiện ở trang nhóm (dưới danh sách dịch vụ), dùng tiêu đề H2/H3 để tự tạo mục lục. Để trống = dùng bài mẫu có sẵn của theme.</p></td></tr>
	<?php
}
add_action( 'nhom_dich_vu_edit_form_fields', 'sgd_group_edit_fields' );

/**
 * Lưu ô của Nhóm dịch vụ.
 *
 * @param int $term_id ID.
 */
function sgd_group_save( $term_id ) {
	if ( ! isset( $_POST['sgd_group_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sgd_group_nonce'] ) ), 'sgd_group' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$icon = isset( $_POST['sgd_icon'] ) ? sanitize_key( wp_unslash( $_POST['sgd_icon'] ) ) : '';
	update_term_meta( $term_id, '_sgd_icon', array_key_exists( $icon, sgd_icons() ) ? $icon : '' );
	update_term_meta( $term_id, '_sgd_order', isset( $_POST['sgd_order'] ) ? (int) $_POST['sgd_order'] : 0 );
	update_term_meta( $term_id, '_sgd_h1', isset( $_POST['sgd_h1'] ) ? sanitize_text_field( wp_unslash( $_POST['sgd_h1'] ) ) : '' );
	update_term_meta( $term_id, '_sgd_image', isset( $_POST['sgd_image'] ) ? esc_url_raw( wp_unslash( $_POST['sgd_image'] ) ) : '' );
	update_term_meta( $term_id, '_sgd_article', isset( $_POST['sgd_article'] ) ? wp_kses_post( wp_unslash( $_POST['sgd_article'] ) ) : '' );
}
add_action( 'edited_nhom_dich_vu', 'sgd_group_save' );

/**
 * Thứ tự hiển thị trong /dich-vu/ và trang nhóm: theo "Thứ tự" rồi mới nhất.
 *
 * @param WP_Query $q Truy vấn.
 */
function sgd_service_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'dich_vu' ) || $q->is_tax( 'nhom_dich_vu' ) ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
		$q->set( 'posts_per_page', 24 );
	}
}
add_action( 'pre_get_posts', 'sgd_service_query' );

/**
 * Đang ở trang danh sách dịch vụ (/dich-vu/ hoặc trang nhóm).
 *
 * @return bool
 */
function sgd_is_listing() {
	return is_post_type_archive( 'dich_vu' ) || is_tax( 'nhom_dich_vu' );
}

/**
 * Tiêu đề H1 của trang danh sách.
 *
 * @return string
 */
function sgd_listing_h1() {
	if ( is_tax( 'nhom_dich_vu' ) ) {
		$term = get_queried_object();
		$h1   = get_term_meta( $term->term_id, '_sgd_h1', true );
		return $h1 ? $h1 : 'Dịch vụ ' . mb_strtolower( $term->name );
	}
	return sgd_opt( 'archive_title' );
}

/**
 * Thêm id cho các H2 trong nội dung (để làm mục lục).
 *
 * @param string $html Nội dung đã qua the_content.
 * @return array [html, [[id, text], ...]]
 */
function sgd_content_headings( $html ) {
	$items = array();
	$used  = array();
	$html  = preg_replace_callback(
		'/<h2([^>]*)>(.*?)<\/h2>/is',
		function ( $m ) use ( &$items, &$used ) {
			$text = trim( wp_strip_all_tags( $m[2] ) );
			if ( preg_match( '/\bid=["\']([^"\']+)["\']/', $m[1], $idm ) ) {
				$id    = $idm[1];
				$attrs = $m[1];
			} else {
				$id   = sanitize_title( remove_accents( $text ) );
				$id   = $id ? $id : 'muc';
				$base = $id;
				$n    = 2;
				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $n++;
				}
				$attrs = $m[1] . ' id="' . esc_attr( $id ) . '"';
			}
			$used[ $id ] = true;
			$items[]     = array( $id, $text );
			return '<h2' . $attrs . '>' . $m[2] . '</h2>';
		},
		(string) $html
	);
	return array( $html, $items );
}

/**
 * Dịch vụ liên quan: cùng nhóm trước, thiếu thì lấy dịch vụ khác.
 *
 * @param int $id    ID.
 * @param int $count Số lượng.
 * @return WP_Post[]
 */
function sgd_related_services( $id, $count = 3 ) {
	$found = array();
	$ids   = wp_get_post_terms( $id, 'nhom_dich_vu', array( 'fields' => 'ids' ) );
	foreach ( array( true, false ) as $same_group ) {
		if ( count( $found ) >= $count || ( $same_group && ( ! $ids || is_wp_error( $ids ) ) ) ) {
			continue;
		}
		$args = array(
			'post_type'      => 'dich_vu',
			'posts_per_page' => $count - count( $found ),
			'post__not_in'   => array_merge( array( $id ), wp_list_pluck( $found, 'ID' ) ),
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'no_found_rows'  => true,
		);
		if ( $same_group ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'nhom_dich_vu', 'terms' => $ids ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		$found = array_merge( $found, get_posts( $args ) );
	}
	return $found;
}

/**
 * Bảng giá dạng bảng: [ ['title'=>, 'head'=>[], 'rows'=>[[]], 'notes'=>[]], ... ].
 *
 * @param int|null $id ID dịch vụ.
 * @return array
 */
function sgd_price_tables( $id = null ) {
	$tables = array();
	$cur    = null;
	foreach ( preg_split( '/\r\n|\r|\n/', sgd_meta( 'price_table', $id ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( 0 === strpos( $line, '##' ) ) {
			if ( $cur ) {
				$tables[] = $cur;
			}
			$cur = array( 'title' => trim( ltrim( $line, '#' ) ), 'head' => array(), 'rows' => array(), 'notes' => array() );
			continue;
		}
		if ( ! $cur ) {
			$cur = array( 'title' => '', 'head' => array(), 'rows' => array(), 'notes' => array() );
		}
		if ( 0 === strpos( $line, '* ' ) ) {
			$cur['notes'][] = trim( substr( $line, 2 ) );
		} elseif ( ! $cur['head'] ) {
			$cur['head'] = array_map( 'trim', explode( '|', $line ) );
		} else {
			$cur['rows'][] = array_map( 'trim', explode( '|', $line ) );
		}
	}
	if ( $cur ) {
		$tables[] = $cur;
	}
	return array_values(
		array_filter(
			$tables,
			function ( $t ) {
				return $t['head'] || $t['rows'];
			}
		)
	);
}

/**
 * Tên ngắn hiển thị trên banner dịch vụ.
 *
 * @param int|null $id ID.
 * @return string
 */
function sgd_short_title( $id = null ) {
	$s = sgd_meta( 'short', $id );
	return $s ? $s : get_the_title( $id ? $id : get_the_ID() );
}

/**
 * Thời gian đọc ước tính (phút) – khoảng 220 chữ/phút.
 *
 * @param int|null $id ID bài.
 * @return int
 */
function sgd_reading_time( $id = null ) {
	$words = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( get_post_field( 'post_content', $id ? $id : get_the_ID() ) ) ) ) );
	return max( 1, (int) round( $words / 220 ) );
}

/**
 * Tiêu đề H1 trang danh sách bài viết.
 *
 * @return string
 */
function sgd_blog_title() {
	if ( is_search() ) {
		return 'Kết quả tìm kiếm: ' . get_search_query();
	}
	if ( is_category() || is_tag() ) {
		return single_term_title( '', false );
	}
	if ( is_author() ) {
		return 'Bài viết của ' . get_the_author();
	}
	if ( is_date() ) {
		return 'Bài viết ' . get_the_date( is_year() ? 'Y' : 'm/Y' );
	}
	$page = get_option( 'page_for_posts' );
	return $page ? get_the_title( $page ) : 'Kiến thức doanh nghiệp';
}

/**
 * Tắt bình luận cho bài viết (giao diện riêng không hiện; tránh spam liên kết).
 *
 * @return bool
 */
function sgd_close_comments() {
	return false;
}
add_filter( 'comments_open', 'sgd_close_comments' );
add_filter( 'pings_open', 'sgd_close_comments' );

/**
 * Bài viết liên quan tới 1 dịch vụ: bài có nhắc tên dịch vụ / nhóm dịch vụ trước, thiếu thì lấy bài mới.
 *
 * @param int $id    ID dịch vụ.
 * @param int $count Số bài.
 * @return WP_Post[]
 */
function sgd_related_posts_for_service( $id, $count = 3 ) {
	$found = array();
	$group = sgd_first_group( $id );
	$terms = array_filter( array( sgd_meta( 'short', $id ), get_the_title( $id ), $group ? $group->name : '' ) );
	foreach ( $terms as $term ) {
		if ( count( $found ) >= $count ) {
			break;
		}
		$found = array_merge(
			$found,
			get_posts(
				array(
					'post_type'           => 'post',
					's'                   => $term,
					'posts_per_page'      => $count - count( $found ),
					'post__not_in'        => wp_list_pluck( $found, 'ID' ),
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			)
		);
	}
	if ( count( $found ) < $count ) {
		$found = array_merge(
			$found,
			get_posts(
				array(
					'post_type'           => 'post',
					'posts_per_page'      => $count - count( $found ),
					'post__not_in'        => wp_list_pluck( $found, 'ID' ),
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			)
		);
	}
	return $found;
}

/**
 * Bài viết của nhóm dịch vụ: bài đã nhập ở trang sửa nhóm, trống thì dùng bài mẫu của theme.
 *
 * @param WP_Term $term Nhóm.
 * @return string HTML.
 */
function sgd_group_article( $term ) {
	$html = (string) get_term_meta( $term->term_id, '_sgd_article', true );
	if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
		static $defaults = null;
		if ( null === $defaults ) {
			$defaults = require SGD_DIR . '/inc/demo-articles.php';
		}
		$html = isset( $defaults[ $term->slug ] ) ? $defaults[ $term->slug ] : '';
	}
	// Bảng giá nằm trong bài: bài tự viết chưa chèn bảng giá thì tự thêm ở cuối.
	if ( $html && false === strpos( $html, '[sgd_price' ) ) {
		$html .= "\n<h2>Bảng giá " . esc_html( mb_strtolower( $term->name ) ) . "</h2>\n[sgd_price_table group=\"" . esc_attr( $term->slug ) . "\"]";
	}
	$html = str_replace( '{company}', esc_html( sgd_opt( 'company' ) ), $html );
	return $html ? do_shortcode( shortcode_unautop( wpautop( $html ) ) ) : '';
}
