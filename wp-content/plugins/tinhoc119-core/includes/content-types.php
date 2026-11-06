<?php
/**
 * Loại nội dung: Dịch vụ (giữ nguyên URL /dich-vu/ của theme cũ), Dự án, Đánh giá khách hàng.
 * Theme cũ (Flatsome Dịch vụ) đã đăng ký Dịch vụ thì plugin không đăng ký lại – dữ liệu dùng chung.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đăng ký loại nội dung.
 */
function th119_register_content_types() {
	if ( ! post_type_exists( 'dich_vu' ) ) {
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
				'menu_icon'     => 'dashicons-admin-tools',
				'menu_position' => 5,
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions' ),
				'show_in_rest'  => true,
			)
		);
	}
	if ( ! taxonomy_exists( 'nhom_dich_vu' ) ) {
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

	register_post_type(
		'th_project',
		array(
			'labels'        => array(
				'name'          => 'Dự án',
				'singular_name' => 'Dự án',
				'add_new'       => 'Thêm dự án',
				'add_new_item'  => 'Thêm dự án đã thực hiện',
				'edit_item'     => 'Sửa dự án',
				'all_items'     => 'Tất cả dự án',
				'not_found'     => 'Chưa có dự án',
			),
			'description'   => 'Dự án, công trình IT đã thực hiện thực tế.',
			'public'        => true,
			'has_archive'   => 'du-an',
			'rewrite'       => array( 'slug' => 'du-an', 'with_front' => false ),
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'th_review',
		array(
			'labels'          => array(
				'name'          => 'Đánh giá khách hàng',
				'singular_name' => 'Đánh giá',
				'add_new'       => 'Thêm đánh giá',
				'add_new_item'  => 'Thêm đánh giá thật của khách',
				'edit_item'     => 'Sửa đánh giá',
				'all_items'     => 'Đánh giá khách hàng',
				'not_found'     => 'Chưa có đánh giá – khối đánh giá trên trang chủ sẽ tự ẩn',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'edit.php?post_type=th_project',
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'capability_type' => 'post',
		)
	);

	// Khách đăng ký của theme cũ: vẫn xem được sau khi đổi theme (dữ liệu không mất).
	if ( ! post_type_exists( 'sgd_lead' ) ) {
		global $wpdb;
		$has = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sgd_lead'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $has ) {
			register_post_type(
				'sgd_lead',
				array(
					'labels'       => array(
						'name'      => 'Khách đăng ký (cũ)',
						'menu_name' => 'Khách đăng ký (cũ)',
					),
					'public'       => false,
					'show_ui'      => true,
					'show_in_menu' => 'th119',
					'supports'     => array( 'title', 'editor', 'custom-fields' ),
					'capabilities' => array( 'create_posts' => 'do_not_allow' ),
					'map_meta_cap' => true,
				)
			);
		}
	}
}
add_action( 'init', 'th119_register_content_types', 20 );

/**
 * Ô thông tin dịch vụ (cùng khoá _sgd_* với theme cũ để dùng lại dữ liệu).
 *
 * @return array
 */
function th119_service_fields() {
	return array(
		'subtitle' => array( 'Mô tả ngắn dưới tiêu đề', 'text', 'Ví dụ: Kiểm tra miễn phí, báo giá trước khi sửa' ),
		'price'    => array( 'Giá tham khảo hiển thị', 'text', 'Ví dụ: Từ 150.000đ hoặc Liên hệ. Chỉ ghi giá thật đang áp dụng.' ),
		'duration' => array( 'Thời gian xử lý', 'text', 'Ví dụ: 30 phút – 2 giờ' ),
		'icon'     => array( 'Icon', 'select', 'laptop,pc,upgrade,tool,building,support,network,printer,shield,cloud,windows,chip' ),
		'includes' => array( 'Hạng mục thực hiện – mỗi dòng 1 ý', 'textarea', '' ),
		'process'  => array( 'Quy trình – mỗi dòng: Bước | Mô tả', 'textarea', '' ),
		'faq'      => array( 'Câu hỏi thường gặp – mỗi dòng: Câu hỏi | Trả lời', 'textarea', 'Chỉ đưa câu hỏi có trả lời hiển thị trên trang (Google yêu cầu khớp nội dung).' ),
		'featured' => array( 'Hiện ở trang chủ', 'checkbox', '' ),
	);
}

/**
 * Ô thông tin dự án.
 *
 * @return array
 */
function th119_project_fields() {
	return array(
		'client'   => array( 'Khách hàng / loại khách hàng', 'text', 'Ví dụ: Văn phòng kế toán 15 máy. Chỉ ghi tên khi khách đồng ý.' ),
		'location' => array( 'Khu vực', 'text', 'Ví dụ: Tân Phú, TP.HCM' ),
		'date'     => array( 'Thời gian thực hiện', 'text', 'Ví dụ: 10/2026' ),
		'scope'    => array( 'Hạng mục – mỗi dòng 1 ý', 'textarea', '' ),
		'result'   => array( 'Kết quả thực tế', 'textarea', 'Chỉ ghi kết quả đã đo được / khách xác nhận.' ),
	);
}

/**
 * Ô thông tin đánh giá.
 *
 * @return array
 */
function th119_review_fields() {
	return array(
		'rating' => array( 'Số sao (1–5)', 'number', '' ),
		'source' => array( 'Nguồn đánh giá', 'text', 'Ví dụ: Google Maps, Facebook, Zalo' ),
		'link'   => array( 'Link tới đánh giá gốc', 'url', 'Bắt buộc với đánh giá từ Google/Facebook để người đọc kiểm chứng' ),
		'date'   => array( 'Ngày đánh giá', 'text', 'dd/mm/yyyy' ),
	);
}

/**
 * Hộp nhập liệu.
 */
function th119_content_meta_boxes() {
	if ( ! function_exists( 'sgd_service_fields' ) ) {
		add_meta_box( 'th119_service', 'Thông tin dịch vụ', 'th119_meta_box_html', 'dich_vu', 'normal', 'high', array( 'fields' => 'service' ) );
	}
	add_meta_box( 'th119_service_kb', 'Liên kết thư viện kiến thức', 'th119_service_kb_box', 'dich_vu', 'side' );
	add_meta_box( 'th119_project', 'Thông tin dự án', 'th119_meta_box_html', 'th_project', 'normal', 'high', array( 'fields' => 'project' ) );
	add_meta_box( 'th119_review', 'Thông tin đánh giá', 'th119_meta_box_html', 'th_review', 'normal', 'high', array( 'fields' => 'review' ) );
}
add_action( 'add_meta_boxes', 'th119_content_meta_boxes' );

/**
 * Lấy bộ ô theo tên.
 *
 * @param string $set Tên bộ.
 * @return array
 */
function th119_field_set( $set ) {
	if ( 'service' === $set ) {
		return th119_service_fields();
	}
	if ( 'project' === $set ) {
		return th119_project_fields();
	}
	return th119_review_fields();
}

/**
 * Tiền tố meta theo bộ.
 *
 * @param string $set Bộ.
 * @return string
 */
function th119_meta_prefix( $set ) {
	return 'service' === $set ? '_sgd_' : '_th_';
}

/**
 * HTML hộp nhập.
 *
 * @param WP_Post $post Bài.
 * @param array   $box  Hộp.
 */
function th119_meta_box_html( $post, $box ) {
	$set = $box['args']['fields'];
	wp_nonce_field( 'th119_meta_' . $set, 'th119_meta_nonce' );
	echo '<input type="hidden" name="th119_meta_set" value="' . esc_attr( $set ) . '">';
	if ( 'review' === $set ) {
		echo '<p class="th119-warn"><strong>Chỉ nhập đánh giá thật</strong> của khách hàng, chép nguyên văn từ nguồn (Google Maps, Facebook, Zalo) và dán link gốc. Tiêu đề = tên khách (hoặc tên viết tắt nếu khách không muốn công khai). Nội dung = lời đánh giá.</p>';
	}
	echo '<table class="form-table" role="presentation">';
	$prefix = th119_meta_prefix( $set );
	foreach ( th119_field_set( $set ) as $key => $f ) {
		$val = get_post_meta( $post->ID, $prefix . $key, true );
		$id  = 'th119-m-' . $key;
		echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( 'textarea' === $f[1] ) {
			printf( '<textarea id="%1$s" name="th119m[%2$s]" rows="4" class="large-text">%3$s</textarea>', esc_attr( $id ), esc_attr( $key ), esc_textarea( $val ) );
		} elseif ( 'checkbox' === $f[1] ) {
			printf( '<label><input type="checkbox" id="%1$s" name="th119m[%2$s]" value="1" %3$s> Có</label>', esc_attr( $id ), esc_attr( $key ), checked( $val, '1', false ) );
		} elseif ( 'select' === $f[1] ) {
			echo '<select id="' . esc_attr( $id ) . '" name="th119m[' . esc_attr( $key ) . ']"><option value="">— Mặc định —</option>';
			foreach ( explode( ',', $f[2] ) as $opt ) {
				echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $val, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
			}
			echo '</select>';
		} else {
			printf( '<input type="%4$s" id="%1$s" name="th119m[%2$s]" value="%3$s" class="large-text">', esc_attr( $id ), esc_attr( $key ), esc_attr( $val ), esc_attr( in_array( $f[1], array( 'url', 'number' ), true ) ? $f[1] : 'text' ) );
		}
		if ( $f[2] && 'select' !== $f[1] ) {
			echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

/**
 * Hộp chọn chuyên mục kiến thức liên quan cho dịch vụ.
 *
 * @param WP_Post $post Bài.
 */
function th119_service_kb_box( $post ) {
	wp_nonce_field( 'th119_kb_' . $post->ID, 'th119_kb_nonce' );
	$sel  = array_map( 'intval', (array) get_post_meta( $post->ID, '_th_kb_cats', true ) );
	$cats = get_categories( array( 'hide_empty' => false ) );
	echo '<p class="description">Chọn chuyên mục kiến thức liên quan: trang dịch vụ hiện bài hướng dẫn của các mục này, và bài trong mục hiện dịch vụ này ở khối "Dịch vụ liên quan".</p><div style="max-height:220px;overflow:auto">';
	foreach ( $cats as $c ) {
		printf( '<label style="display:block"><input type="checkbox" name="th119_kb[]" value="%d" %s> %s%s</label>', (int) $c->term_id, checked( in_array( (int) $c->term_id, $sel, true ), true, false ), $c->parent ? '— ' : '', esc_html( $c->name ) );
	}
	echo '</div>';
}

/**
 * Lưu hộp nhập.
 *
 * @param int $post_id ID.
 */
function th119_save_content_meta( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['th119_kb_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['th119_kb_nonce'] ) ), 'th119_kb_' . $post_id ) ) {
		$ids = isset( $_POST['th119_kb'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['th119_kb'] ) ) : array();
		$ids = array_values( array_filter( $ids ) );
		update_post_meta( $post_id, '_th_kb_cats', $ids );
		// Mỗi chuyên mục 1 dòng meta để truy vấn chính xác.
		delete_post_meta( $post_id, '_th_kb_cat' );
		foreach ( $ids as $cid ) {
			add_post_meta( $post_id, '_th_kb_cat', $cid );
		}
	}
	if ( empty( $_POST['th119_meta_set'] ) || empty( $_POST['th119_meta_nonce'] ) ) {
		return;
	}
	$set = sanitize_key( wp_unslash( $_POST['th119_meta_set'] ) );
	if ( ! in_array( $set, array( 'service', 'project', 'review' ), true ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['th119_meta_nonce'] ) ), 'th119_meta_' . $set ) ) {
		return;
	}
	$in     = isset( $_POST['th119m'] ) && is_array( $_POST['th119m'] ) ? wp_unslash( $_POST['th119m'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng ô.
	$prefix = th119_meta_prefix( $set );
	foreach ( th119_field_set( $set ) as $key => $f ) {
		$v = isset( $in[ $key ] ) && is_string( $in[ $key ] ) ? $in[ $key ] : '';
		if ( 'select' === $f[1] ) {
			$v = in_array( $v, explode( ',', $f[2] ), true ) ? $v : '';
		} elseif ( 'number' === $f[1] ) {
			$v = '' === $v ? '' : (string) max( 1, min( 5, absint( $v ) ) );
		} else {
			$v = th119_sanitize_by_type( $f[1], $v );
		}
		if ( '' === $v || 0 === $v ) {
			delete_post_meta( $post_id, $prefix . $key );
		} else {
			update_post_meta( $post_id, $prefix . $key, (string) $v );
		}
	}
}
add_action( 'save_post', 'th119_save_content_meta' );

/**
 * Đọc trường dịch vụ.
 *
 * @param string $key Khoá.
 * @param int    $id  ID.
 * @return string
 */
function th119_service_field( $key, $id = 0 ) {
	return (string) get_post_meta( $id ? $id : get_the_ID(), '_sgd_' . $key, true );
}

/**
 * Đọc trường dự án / đánh giá.
 *
 * @param string $key Khoá.
 * @param int    $id  ID.
 * @return string
 */
function th119_field( $key, $id = 0 ) {
	return (string) get_post_meta( $id ? $id : get_the_ID(), '_th_' . $key, true );
}

/**
 * Tách danh sách "A | B" mỗi dòng thành mảng.
 *
 * @param string $text Văn bản.
 * @param int    $cols Số cột tối thiểu.
 * @return array
 */
function th119_pipe_rows( $text, $cols = 2 ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$p = array_map( 'trim', explode( '|', $line, $cols ) );
		if ( count( $p ) >= $cols && '' !== $p[0] && '' !== $p[ $cols - 1 ] ) {
			$out[] = $p;
		}
	}
	return $out;
}

/**
 * Làm mới đường dẫn khi loại nội dung thay đổi lần đầu.
 */
function th119_maybe_flush_rewrite() {
	if ( get_option( 'th119_rewrite_version' ) !== TH119_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'th119_rewrite_version', TH119_VERSION, false );
	}
}
add_action( 'init', 'th119_maybe_flush_rewrite', 99 );
