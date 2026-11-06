<?php
/**
 * Thư viện kiến thức: cây chuyên mục, trường SEO cho chuyên mục, trường bài viết chuẩn,
 * mẫu bài 15 phần, shortcode cảnh báo / lệnh kỹ thuật / bước.
 * Dùng Bài viết + Chuyên mục gốc của WordPress để giữ nguyên URL bài cũ.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cây chuyên mục kiến thức: slug => [tên, icon, mô tả, con[slug => tên]].
 *
 * @return array
 */
function th119_kb_tree() {
	return array(
		'windows'         => array(
			'Windows',
			'windows',
			'Hướng dẫn xử lý lỗi Windows 10, Windows 11: lỗi khởi động, màn hình xanh, lỗi cập nhật, lỗi driver và tối ưu hệ thống.',
			array(
				'windows-10'          => 'Windows 10',
				'windows-11'          => 'Windows 11',
				'loi-khoi-dong'       => 'Lỗi khởi động',
				'loi-man-hinh-xanh'   => 'Lỗi màn hình xanh',
				'loi-cap-nhat-windows' => 'Lỗi cập nhật',
				'loi-driver'          => 'Lỗi driver',
				'loi-he-thong'        => 'Lỗi hệ thống',
				'toi-uu-windows'      => 'Tối ưu Windows',
			),
		),
		'laptop'          => array(
			'Laptop',
			'laptop',
			'Nhận biết và xử lý sự cố laptop: không lên nguồn, không sạc, pin, màn hình, bàn phím, quạt, nóng máy, chạy chậm.',
			array(
				'laptop-khong-len-nguon' => 'Laptop không lên nguồn',
				'laptop-khong-sac'       => 'Laptop không sạc',
				'loi-pin-laptop'         => 'Lỗi pin',
				'loi-man-hinh-laptop'    => 'Lỗi màn hình',
				'loi-ban-phim-laptop'    => 'Lỗi bàn phím',
				'loi-quat-laptop'        => 'Lỗi quạt',
				'laptop-nong'            => 'Laptop nóng',
				'laptop-chay-cham'       => 'Laptop chạy chậm',
			),
		),
		'phan-cung'       => array(
			'Phần cứng',
			'chip',
			'Kiến thức phần cứng máy tính: CPU, RAM, SSD, HDD, mainboard, VGA, nguồn và tản nhiệt – cách kiểm tra, chẩn đoán, nâng cấp.',
			array(
				'cpu'            => 'CPU',
				'ram'            => 'RAM',
				'ssd'            => 'SSD',
				'hdd'            => 'HDD',
				'mainboard'      => 'Mainboard',
				'vga'            => 'VGA',
				'nguon-may-tinh' => 'Nguồn máy tính',
				'tan-nhiet'      => 'Tản nhiệt',
			),
		),
		'mang-he-thong'   => array(
			'Mạng và hệ thống',
			'network',
			'Mạng LAN, Wi-Fi, router, switch, NAS, RAID, chia sẻ dữ liệu và máy chủ cho gia đình, văn phòng.',
			array(
				'mang-lan'        => 'Mạng LAN',
				'wi-fi'           => 'Wi-Fi',
				'router'          => 'Router',
				'switch'          => 'Switch',
				'nas'             => 'NAS',
				'raid'            => 'RAID',
				'chia-se-du-lieu' => 'Chia sẻ dữ liệu',
				'may-chu'         => 'Máy chủ',
			),
		),
		'phan-mem'        => array(
			'Phần mềm',
			'app',
			'Hướng dẫn cài đặt, sửa lỗi phần mềm văn phòng và doanh nghiệp: Microsoft Office, Excel, Word, HTKK, phần mềm kế toán, AutoCAD.',
			array(
				'microsoft-office'    => 'Microsoft Office',
				'excel'               => 'Excel',
				'word'                => 'Word',
				'htkk'                => 'HTKK',
				'phan-mem-ke-toan'    => 'Phần mềm kế toán',
				'autocad'             => 'AutoCAD',
				'phan-mem-doanh-nghiep' => 'Phần mềm doanh nghiệp',
			),
		),
		'thiet-bi'        => array(
			'Thiết bị',
			'printer',
			'Xử lý sự cố máy in, máy scan, màn hình, webcam và thiết bị ngoại vi.',
			array(
				'may-in'              => 'Máy in',
				'may-scan'            => 'Máy scan',
				'man-hinh'            => 'Màn hình',
				'webcam'              => 'Webcam',
				'thiet-bi-ngoai-vi'   => 'Thiết bị ngoại vi',
			),
		),
		'it-doanh-nghiep' => array(
			'IT doanh nghiệp',
			'building',
			'Giải pháp IT cho văn phòng: bảo trì máy tính định kỳ, quản lý mạng nội bộ, sao lưu dữ liệu, bảo mật, quản lý thiết bị.',
			array(
				'bao-tri-may-tinh'      => 'Bảo trì máy tính',
				'quan-ly-mang-noi-bo'   => 'Quản lý mạng nội bộ',
				'sao-luu-du-lieu'       => 'Sao lưu dữ liệu',
				'bao-mat'               => 'Bảo mật',
				'quan-ly-thiet-bi'      => 'Quản lý thiết bị',
				'giai-phap-van-phong'   => 'Giải pháp văn phòng',
			),
		),
	);
}

/**
 * Tạo các chuyên mục còn thiếu (không sửa, không xoá chuyên mục có sẵn).
 *
 * @return array [created, skipped].
 */
function th119_kb_seed_categories() {
	$created = 0;
	$skipped = 0;
	$order   = 0;
	foreach ( th119_kb_tree() as $slug => $row ) {
		$order++;
		$parent = get_term_by( 'slug', $slug, 'category' );
		if ( ! $parent ) {
			$r = wp_insert_term( $row[0], 'category', array( 'slug' => $slug, 'description' => $row[2] ) );
			if ( is_wp_error( $r ) ) {
				continue;
			}
			$parent_id = (int) $r['term_id'];
			update_term_meta( $parent_id, 'th_icon', $row[1] );
			update_term_meta( $parent_id, 'th_kb_root', 1 );
			update_term_meta( $parent_id, 'th_order', $order );
			$created++;
		} else {
			$parent_id = (int) $parent->term_id;
			if ( ! get_term_meta( $parent_id, 'th_kb_root', true ) ) {
				update_term_meta( $parent_id, 'th_kb_root', 1 );
			}
			$skipped++;
		}
		foreach ( $row[3] as $cslug => $cname ) {
			if ( get_term_by( 'slug', $cslug, 'category' ) ) {
				$skipped++;
				continue;
			}
			$r = wp_insert_term( $cname, 'category', array( 'slug' => $cslug, 'parent' => $parent_id ) );
			if ( ! is_wp_error( $r ) ) {
				$created++;
			}
		}
	}
	th119_log_activity( 'kb_seed', 'category', sprintf( 'Tạo %d chuyên mục, bỏ qua %d đã có', $created, $skipped ) );
	return array( $created, $skipped );
}

/**
 * Chuyên mục gốc của thư viện kiến thức (đã đánh dấu), theo thứ tự.
 *
 * @return WP_Term[]
 */
function th119_kb_roots() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'meta_key'   => 'th_kb_root', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'meta_value' => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort(
		$terms,
		function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, 'th_order', true ) - (int) get_term_meta( $b->term_id, 'th_order', true );
		}
	);
	return $terms;
}

/* ---------------------------------------------------------------------------
 * Trường SEO / nội dung cho chuyên mục.
 * ------------------------------------------------------------------------- */

/**
 * Trường chuyên mục.
 *
 * @return array
 */
function th119_term_fields() {
	return array(
		'th_kb_root'   => array( 'Là nhóm chính của Trung tâm kiến thức', 'checkbox', 'Hiện ở trang Trung tâm kiến thức và trang chủ.' ),
		'th_icon'      => array( 'Icon', 'text', 'windows, laptop, chip, network, app, printer, building, tool, shield, cloud' ),
		'th_order'     => array( 'Thứ tự', 'number', '' ),
		'th_seo_title' => array( 'Tiêu đề SEO', 'text', '50–60 ký tự. Trống = tên chuyên mục.' ),
		'th_seo_desc'  => array( 'Mô tả SEO', 'textarea', '140–155 ký tự. Trống = mô tả chuyên mục.' ),
		'th_intro'     => array( 'Bài giới thiệu chuyên mục (HTML, hiện đầu trang)', 'editor', 'Giới thiệu 150–300 chữ: chuyên mục giúp giải quyết vấn đề gì, nên đọc bài nào trước, khi nào cần kỹ thuật viên.' ),
		'th_featured'  => array( 'Bài nổi bật (ID bài, ngăn bằng dấu phẩy)', 'text', 'Trống = tự lấy bài được đánh dấu "Bài nổi bật" trong chuyên mục.' ),
		'th_services'  => array( 'Dịch vụ liên quan', 'services', '' ),
	);
}

/**
 * Ô nhập ở trang sửa chuyên mục.
 *
 * @param WP_Term $term Chuyên mục.
 */
function th119_category_edit_fields( $term ) {
	wp_nonce_field( 'th119_term', 'th119_term_nonce' );
	foreach ( th119_term_fields() as $key => $f ) {
		$val = get_term_meta( $term->term_id, $key, true );
		echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		switch ( $f[1] ) {
			case 'checkbox':
				printf( '<label><input type="checkbox" id="%1$s" name="th119t[%1$s]" value="1" %2$s> Có</label>', esc_attr( $key ), checked( (int) $val, 1, false ) );
				break;
			case 'textarea':
				printf( '<textarea id="%1$s" name="th119t[%1$s]" rows="3">%2$s</textarea>', esc_attr( $key ), esc_textarea( $val ) );
				break;
			case 'editor':
				wp_editor(
					(string) $val,
					'th119t_' . $key,
					array(
						'textarea_name' => 'th119t[' . $key . ']',
						'textarea_rows' => 8,
						'media_buttons' => true,
					)
				);
				break;
			case 'services':
				$sel = array_map( 'intval', (array) $val );
				$all = get_posts( array( 'post_type' => 'dich_vu', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
				if ( ! $all ) {
					echo '<em>Chưa có dịch vụ.</em>';
				}
				echo '<div style="max-height:200px;overflow:auto">';
				foreach ( $all as $s ) {
					printf( '<label style="display:block"><input type="checkbox" name="th119t[%1$s][]" value="%2$d" %3$s> %4$s</label>', esc_attr( $key ), (int) $s->ID, checked( in_array( (int) $s->ID, $sel, true ), true, false ), esc_html( $s->post_title ) );
				}
				echo '</div>';
				break;
			default:
				printf( '<input type="%3$s" id="%1$s" name="th119t[%1$s]" value="%2$s">', esc_attr( $key ), esc_attr( is_array( $val ) ? '' : $val ), 'number' === $f[1] ? 'number' : 'text' );
		}
		if ( $f[2] ) {
			echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
		}
		echo '</td></tr>';
	}
}
add_action( 'category_edit_form_fields', 'th119_category_edit_fields' );

/**
 * Lưu trường chuyên mục.
 *
 * @param int $term_id ID.
 */
function th119_save_category_fields( $term_id ) {
	if ( ! isset( $_POST['th119_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['th119_term_nonce'] ) ), 'th119_term' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$in = isset( $_POST['th119t'] ) && is_array( $_POST['th119t'] ) ? wp_unslash( $_POST['th119t'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng ô.
	foreach ( th119_term_fields() as $key => $f ) {
		$v = isset( $in[ $key ] ) ? $in[ $key ] : '';
		switch ( $f[1] ) {
			case 'checkbox':
				$v = $v ? 1 : '';
				break;
			case 'number':
				$v = '' === $v ? '' : (int) $v;
				break;
			case 'textarea':
				$v = sanitize_textarea_field( $v );
				break;
			case 'editor':
				$v = wp_kses_post( $v );
				break;
			case 'services':
				$v = array_values( array_filter( array_map( 'absint', (array) $v ) ) );
				break;
			default:
				$v = sanitize_text_field( $v );
		}
		if ( '' === $v || array() === $v ) {
			delete_term_meta( $term_id, $key );
		} else {
			update_term_meta( $term_id, $key, $v );
		}
	}
}
add_action( 'edited_category', 'th119_save_category_fields' );

/* ---------------------------------------------------------------------------
 * Trường bài kiến thức.
 * ------------------------------------------------------------------------- */

/**
 * Trường bài viết.
 *
 * @return array
 */
function th119_article_fields() {
	return array(
		'th_summary'    => array( 'Tóm tắt nhanh (hiện đầu bài)', 'textarea', '2–3 câu: vấn đề là gì, cách xử lý nhanh nhất, khi nào cần kỹ thuật viên.' ),
		'th_difficulty' => array( 'Độ khó tự xử lý', 'select', 'Dễ|Trung bình|Khó – nên nhờ kỹ thuật viên' ),
		'th_time'       => array( 'Thời gian thực hiện', 'text', 'Ví dụ: 10 – 15 phút' ),
		'th_faq'        => array( 'Câu hỏi thường gặp – mỗi dòng: Câu hỏi | Trả lời', 'textarea', 'Hiện cuối bài và xuất FAQPage. Không lặp lại câu hỏi đã có trong thân bài.' ),
		'th_services'   => array( 'Dịch vụ liên quan', 'services', 'Trống = lấy theo chuyên mục.' ),
		'th_reviewer'   => array( 'Người kiểm duyệt kỹ thuật', 'user', 'Kỹ thuật viên đã đọc và xác nhận nội dung.' ),
		'th_reviewed'   => array( 'Ngày kiểm duyệt gần nhất', 'date', '' ),
		'th_featured'   => array( 'Bài nổi bật', 'checkbox', 'Hiện ở trang chủ và đầu chuyên mục.' ),
	);
}

/**
 * Hộp nhập bài kiến thức.
 */
function th119_article_meta_box() {
	add_meta_box( 'th119_article', 'Bài kiến thức – thông tin chuẩn', 'th119_article_meta_box_html', 'post', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'th119_article_meta_box' );

/**
 * HTML hộp nhập.
 *
 * @param WP_Post $post Bài.
 */
function th119_article_meta_box_html( $post ) {
	wp_nonce_field( 'th119_article', 'th119_article_nonce' );
	$ai = get_post_meta( $post->ID, '_th_ai_generated', true );
	if ( $ai ) {
		echo '<p class="th119-warn"><strong>Bài do AI soạn nháp.</strong> Cần kỹ thuật viên kiểm tra từng bước, lệnh, thông số trước khi duyệt. Plugin không cho xuất bản khi chưa có người kiểm duyệt.</p>';
	}
	echo '<table class="form-table" role="presentation">';
	foreach ( th119_article_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, '_' . $key, true );
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		switch ( $f[1] ) {
			case 'textarea':
				printf( '<textarea id="%1$s" name="th119a[%1$s]" rows="4" class="large-text">%2$s</textarea>', esc_attr( $key ), esc_textarea( $val ) );
				break;
			case 'select':
				echo '<select id="' . esc_attr( $key ) . '" name="th119a[' . esc_attr( $key ) . ']"><option value="">—</option>';
				foreach ( explode( '|', $f[2] ) as $o ) {
					echo '<option ' . selected( $val, $o, false ) . '>' . esc_html( $o ) . '</option>';
				}
				echo '</select>';
				break;
			case 'checkbox':
				printf( '<label><input type="checkbox" id="%1$s" name="th119a[%1$s]" value="1" %2$s> Có</label>', esc_attr( $key ), checked( (int) $val, 1, false ) );
				break;
			case 'date':
				printf( '<input type="date" id="%1$s" name="th119a[%1$s]" value="%2$s">', esc_attr( $key ), esc_attr( $val ) );
				break;
			case 'user':
				wp_dropdown_users(
					array(
						'name'             => 'th119a[' . $key . ']',
						'id'               => $key,
						'selected'         => (int) $val,
						'show_option_none' => '— Chưa kiểm duyệt —',
						'option_none_value' => 0,
						'role__in'         => array( 'administrator', 'editor', 'author', 'th119_technician' ),
					)
				);
				break;
			case 'services':
				$sel = array_map( 'intval', (array) $val );
				$all = get_posts( array( 'post_type' => 'dich_vu', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
				echo '<div style="max-height:160px;overflow:auto;columns:2">';
				foreach ( $all as $s ) {
					printf( '<label style="display:block"><input type="checkbox" name="th119a[%1$s][]" value="%2$d" %3$s> %4$s</label>', esc_attr( $key ), (int) $s->ID, checked( in_array( (int) $s->ID, $sel, true ), true, false ), esc_html( $s->post_title ) );
				}
				echo $all ? '' : '<em>Chưa có dịch vụ.</em>';
				echo '</div>';
				break;
			default:
				printf( '<input type="text" id="%1$s" name="th119a[%1$s]" value="%2$s" class="regular-text">', esc_attr( $key ), esc_attr( $val ) );
		}
		if ( $f[2] && 'select' !== $f[1] ) {
			echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	echo '<p class="description">Mẹo trình bày: <code>[th_canh_bao]...[/th_canh_bao]</code> khung cảnh báo đỏ · <code>[th_luu_y]...[/th_luu_y]</code> khung lưu ý xanh · <code>[th_lenh]sfc /scannow[/th_lenh]</code> khối lệnh có nút sao chép · <code>[th_ky_thuat_vien]</code> khối "Khi nào cần kỹ thuật viên" kèm nút gọi.</p>';
}

/**
 * Lưu trường bài viết.
 *
 * @param int $post_id ID.
 */
function th119_save_article_fields( $post_id ) {
	if ( ! isset( $_POST['th119_article_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['th119_article_nonce'] ) ), 'th119_article' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['th119a'] ) && is_array( $_POST['th119a'] ) ? wp_unslash( $_POST['th119a'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng ô.
	foreach ( th119_article_fields() as $key => $f ) {
		$v = isset( $in[ $key ] ) ? $in[ $key ] : '';
		switch ( $f[1] ) {
			case 'textarea':
				$v = sanitize_textarea_field( $v );
				break;
			case 'select':
				$v = in_array( $v, explode( '|', $f[2] ), true ) ? $v : '';
				break;
			case 'checkbox':
				$v = $v ? 1 : '';
				break;
			case 'date':
				$v = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? $v : '';
				break;
			case 'user':
				$v = absint( $v ) ? absint( $v ) : '';
				break;
			case 'services':
				$v = array_values( array_filter( array_map( 'absint', (array) $v ) ) );
				break;
			default:
				$v = sanitize_text_field( $v );
		}
		if ( '' === $v || array() === $v ) {
			delete_post_meta( $post_id, '_' . $key );
		} else {
			update_post_meta( $post_id, '_' . $key, $v );
		}
		if ( 'services' === $f[1] ) {
			// Một dòng meta cho mỗi dịch vụ để trang dịch vụ truy vấn chính xác "bài nào nhắc tới dịch vụ này".
			delete_post_meta( $post_id, '_th_service' );
			foreach ( (array) $v as $sid ) {
				add_post_meta( $post_id, '_th_service', (int) $sid );
			}
		}
	}
}
add_action( 'save_post_post', 'th119_save_article_fields' );

/**
 * Mẫu bài 15 phần cho bài viết mới (chỉ khi soạn bài mới, bài cũ không bị đổi).
 *
 * @param string  $content Nội dung.
 * @param WP_Post $post    Bài.
 * @return string
 */
function th119_default_article_content( $content, $post ) {
	if ( 'post' !== $post->post_type || '' !== $content ) {
		return $content;
	}
	$sections = array(
		'Mô tả vấn đề'                     => 'Mô tả ngắn hiện tượng người dùng gặp phải, máy/hệ điều hành áp dụng.',
		'Dấu hiệu nhận biết'               => 'Liệt kê các dấu hiệu cụ thể (thông báo lỗi, đèn, âm thanh…).',
		'Nguyên nhân thường gặp'           => 'Sắp xếp từ phổ biến đến hiếm. Không khẳng định khi chưa kiểm tra.',
		'Cách kiểm tra'                    => 'Các bước kiểm tra an toàn để xác định nguyên nhân.',
		'Hướng dẫn xử lý từng bước'        => "Bước 1: …\nBước 2: …\nChèn ảnh chụp màn hình thật cho từng bước (có alt mô tả).",
		'Lưu ý an toàn'                    => '[th_canh_bao]Sao lưu dữ liệu trước khi thao tác. Rút nguồn trước khi mở máy.[/th_canh_bao]',
		'Trường hợp không nên tự sửa'      => 'Ví dụ: máy vào nước, có mùi khét, phồng pin, cần hàn chip…',
		'Khi nào cần kỹ thuật viên'        => '[th_ky_thuat_vien]',
	);
	$out = '';
	foreach ( $sections as $h => $p ) {
		$out .= '<h2>' . $h . "</h2>\n<p>" . nl2br( $p ) . "</p>\n\n";
	}
	return $out . "<!-- Câu hỏi thường gặp, bài viết liên quan, dịch vụ liên quan, người biên soạn và ngày cập nhật được theme tự hiển thị từ ô 'Bài kiến thức – thông tin chuẩn'. -->\n";
}
add_filter( 'default_content', 'th119_default_article_content', 10, 2 );

/**
 * Chức danh người biên soạn (hồ sơ người dùng).
 *
 * @param WP_User $user Người dùng.
 */
function th119_user_fields( $user ) {
	echo '<h2>Tin Học 119 – hồ sơ tác giả</h2><table class="form-table"><tr><th><label for="th_job_title">Chức danh / kinh nghiệm</label></th><td>';
	printf( '<input type="text" id="th_job_title" name="th_job_title" value="%s" class="regular-text">', esc_attr( get_user_meta( $user->ID, 'th_job_title', true ) ) );
	echo '<p class="description">Ví dụ: Kỹ thuật viên phần cứng. Chỉ ghi kinh nghiệm thật. Hiện ở khung tác giả cuối bài (E-E-A-T). Giới thiệu chi tiết ghi ở ô "Thông tin tiểu sử".</p></td></tr></table>';
	wp_nonce_field( 'th119_user', 'th119_user_nonce' );
}
add_action( 'show_user_profile', 'th119_user_fields' );
add_action( 'edit_user_profile', 'th119_user_fields' );

/**
 * Lưu chức danh.
 *
 * @param int $user_id ID.
 */
function th119_save_user_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['th119_user_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['th119_user_nonce'] ) ), 'th119_user' ) ) {
		return;
	}
	update_user_meta( $user_id, 'th_job_title', isset( $_POST['th_job_title'] ) ? sanitize_text_field( wp_unslash( $_POST['th_job_title'] ) ) : '' );
}
add_action( 'personal_options_update', 'th119_save_user_fields' );
add_action( 'edit_user_profile_update', 'th119_save_user_fields' );

/**
 * Dịch vụ liên quan của 1 bài (bài chọn riêng → theo chuyên mục).
 *
 * @param int $post_id ID.
 * @param int $limit   Số lượng.
 * @return WP_Post[]
 */
function th119_related_services( $post_id, $limit = 3 ) {
	$ids = array_map( 'intval', (array) get_post_meta( $post_id, '_th_services', true ) );
	if ( ! $ids ) {
		foreach ( wp_get_post_categories( $post_id ) as $cid ) {
			$ids = array_merge( $ids, th119_category_services( $cid ) );
		}
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );
	if ( ! $ids ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'   => 'dich_vu',
			'post__in'    => array_slice( $ids, 0, $limit ),
			'orderby'     => 'post__in',
			'numberposts' => $limit,
		)
	);
}

/**
 * Dịch vụ gắn với chuyên mục (chọn ở chuyên mục, hoặc dịch vụ chọn chuyên mục này, hoặc theo chuyên mục cha).
 *
 * @param int $cat_id ID chuyên mục.
 * @return int[]
 */
function th119_category_services( $cat_id ) {
	$ids = array_map( 'intval', (array) get_term_meta( $cat_id, 'th_services', true ) );
	if ( ! $ids ) {
		$q   = get_posts(
			array(
				'post_type'   => 'dich_vu',
				'numberposts' => 6,
				'fields'      => 'ids',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query
					array(
						'key'   => '_th_kb_cat',
						'value' => (int) $cat_id,
					),
				),
			)
		);
		$ids = array_map( 'intval', $q );
	}
	if ( ! $ids ) {
		$term = get_term( $cat_id, 'category' );
		if ( $term && ! is_wp_error( $term ) && $term->parent ) {
			return th119_category_services( $term->parent );
		}
	}
	return array_values( array_filter( $ids ) );
}

/**
 * Bài liên quan: cùng chuyên mục con trước, rồi cùng chuyên mục cha.
 *
 * @param int $post_id ID.
 * @param int $limit   Số lượng.
 * @return WP_Post[]
 */
function th119_related_posts( $post_id, $limit = 4 ) {
	$cats = wp_get_post_categories( $post_id );
	if ( ! $cats ) {
		return array();
	}
	$posts = get_posts(
		array(
			'category__in'        => $cats,
			'post__not_in'        => array( $post_id ),
			'numberposts'         => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( count( $posts ) < $limit ) {
		$parents = array();
		foreach ( $cats as $c ) {
			$t = get_term( $c, 'category' );
			if ( $t && ! is_wp_error( $t ) && $t->parent ) {
				$parents[] = $t->parent;
			}
		}
		if ( $parents ) {
			$exclude = array_merge( array( $post_id ), wp_list_pluck( $posts, 'ID' ) );
			$more    = get_posts(
				array(
					'category__in' => $parents,
					'post__not_in' => $exclude,
					'numberposts'  => $limit - count( $posts ),
					'no_found_rows' => true,
				)
			);
			$posts   = array_merge( $posts, $more );
		}
	}
	return $posts;
}

/**
 * Danh sách FAQ của bài / dịch vụ dạng [[q, a], …].
 *
 * @param int $post_id ID.
 * @return array
 */
function th119_get_faq( $post_id ) {
	$raw = 'dich_vu' === get_post_type( $post_id ) ? get_post_meta( $post_id, '_sgd_faq', true ) : get_post_meta( $post_id, '_th_faq', true );
	return th119_pipe_rows( $raw, 2 );
}
