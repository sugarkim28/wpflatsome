<?php
/**
 * Mục "Bài học" + phân loại "Lớp" và "Môn học" + ô thông tin bài (mục tiêu, ghi nhớ, câu hỏi luyện tập, trò chơi).
 *
 * URL: /bai-hoc/ten-bai/ · /chuong-trinh/lop-1/ · /mon/toan/
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * 5 lớp Tiểu học = 5 hòn đảo cần phá đảo: số => [tên, slug, biểu tượng, màu, mô tả ngắn, tên đảo, màu đất, ảnh 3D].
 *
 * @return array
 */
function hv_grades() {
	return array(
		1 => array( 'Lớp 1', 'lop-1', '🌱', '#ff5d73', 'Làm quen chữ cái, đếm số, nhận biết hình', 'Đảo Hạt Mầm', '#7ed957', 'seedling' ),
		2 => array( 'Lớp 2', 'lop-2', '🐠', '#ff9f1c', 'Cộng trừ có nhớ, bảng nhân 2 và 5', 'Đảo San Hô', '#ffb38a', 'fish' ),
		3 => array( 'Lớp 3', 'lop-3', '🌴', '#2ec27e', 'Bảng nhân chia, bắt đầu học tiếng Anh', 'Đảo Rừng Xanh', '#3fbf5f', 'palm' ),
		4 => array( 'Lớp 4', 'lop-4', '🌋', '#2f80ed', 'Phân số, Khoa học, Lịch sử và Địa lí', 'Đảo Núi Lửa', '#c98b5a', 'volcano' ),
		5 => array( 'Lớp 5', 'lop-5', '🚀', '#8b5cf6', 'Số thập phân, phần trăm, lịch sử dân tộc', 'Đảo Ngôi Sao', '#b8a4ff', 'rocket' ),
	);
}

/**
 * Môn học theo Chương trình GDPT 2018: slug => [tên, biểu tượng, màu, các lớp học, ảnh 3D].
 *
 * @return array
 */
function hv_subjects() {
	return array(
		'toan'            => array( 'Toán', '🔢', '#ff6b6b', array( 1, 2, 3, 4, 5 ), 'abacus' ),
		'tieng-viet'      => array( 'Tiếng Việt', '📖', '#ff922b', array( 1, 2, 3, 4, 5 ), 'book' ),
		'tieng-anh'       => array( 'Tiếng Anh', '🔤', '#339af0', array( 3, 4, 5 ), 'abc' ),
		'tu-nhien-xa-hoi' => array( 'Tự nhiên và Xã hội', '🌱', '#40c057', array( 1, 2, 3 ), 'seedling' ),
		'khoa-hoc'        => array( 'Khoa học', '🔬', '#12b886', array( 4, 5 ), 'microscope' ),
		'lich-su-dia-li'  => array( 'Lịch sử và Địa lí', '🗺️', '#f59f00', array( 4, 5 ), 'map' ),
		'dao-duc'         => array( 'Đạo đức', '💖', '#f06595', array( 1, 2, 3, 4, 5 ), 'heart' ),
		'tin-hoc'         => array( 'Tin học và Công nghệ', '💻', '#5c7cfa', array( 3, 4, 5 ), 'laptop' ),
		'may-tinh'        => array( 'Làm quen máy tính', '🖱️', '#00b8d9', array( 1, 2, 3, 4, 5 ), 'desktop' ),
	);
}

/**
 * Trùm cuối của mỗi môn (đánh khi đã học xong các màn): slug môn => [tên trùm, biểu tượng, ảnh 3D].
 *
 * @return array
 */
function hv_bosses() {
	return array(
		'toan'            => array( 'Rồng Số Học', '🐉', 'dragon' ),
		'tieng-viet'      => array( 'Yêu Tinh Chữ Cái', '👹', 'ogre' ),
		'tieng-anh'       => array( 'Robot Alphabet', '🤖', 'robot' ),
		'tu-nhien-xa-hoi' => array( 'Khủng Long Rừng Rậm', '🦖', 'trex' ),
		'khoa-hoc'        => array( 'Giáo Sư Lộn Xộn', '🧪', 'testtube' ),
		'lich-su-dia-li'  => array( 'Thuyền Trưởng Bí Ẩn', '🏴‍☠️', 'pirate' ),
		'dao-duc'         => array( 'Mây Đen Cáu Kỉnh', '⛈️', 'storm' ),
		'tin-hoc'         => array( 'Virus Tí Hon', '🦠', 'microbe' ),
		'may-tinh'        => array( 'Chuột Nghịch Ngợm', '🐭', 'mole' ),
	);
}

/**
 * Bộ sách giáo khoa. Từ năm học 2026–2027 cả nước dùng chung bộ "Kết nối tri thức với cuộc sống"
 * (Tiếng Anh: "Global Success") theo Quyết định 3588/QĐ-BGDĐT; 2 bộ còn lại giữ để đánh dấu bài dùng chung.
 *
 * @return array
 */
function hv_books() {
	return array(
		'kntt'      => 'Kết nối tri thức với cuộc sống',
		'global'    => 'Tiếng Anh Global Success',
		'ctst'      => 'Chân trời sáng tạo',
		'canh-dieu' => 'Cánh Diều',
	);
}

/**
 * Bộ sách mặc định của bài theo môn.
 *
 * @param string $subject Slug môn.
 * @return array
 */
function hv_default_books( $subject ) {
	return array( 'tieng-anh' === $subject ? 'global' : 'kntt' );
}

/**
 * Ảnh biểu tượng 3D (Microsoft Fluent Emoji, giấy phép MIT – assets/img/3d/).
 *
 * @param string $key   Tên tệp (không đuôi).
 * @param int    $size  Kích thước hiển thị (px).
 * @param string $alt   Chữ thay thế (để trống = ảnh trang trí).
 * @param string $class Class.
 * @return string
 */
function hv_icon3d( $key, $size = 64, $alt = '', $class = 'hv-3d' ) {
	if ( ! $key || ! file_exists( HV_DIR . '/assets/img/3d/' . $key . '.webp' ) ) {
		return '';
	}
	return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( HV_URI . '/assets/img/3d/' . $key . '.webp' ) . '" width="' . (int) $size . '" height="' . (int) $size . '" alt="' . esc_attr( $alt ) . '"' . ( $alt ? '' : ' aria-hidden="true"' ) . ' loading="lazy" decoding="async">';
}

/**
 * Đăng ký mục Bài học, Lớp, Môn học.
 */
function hv_register_content() {
	register_post_type(
		'bai_hoc',
		array(
			'labels'        => array(
				'name'          => 'Bài học',
				'singular_name' => 'Bài học',
				'add_new'       => 'Thêm bài học',
				'add_new_item'  => 'Thêm bài học mới',
				'edit_item'     => 'Sửa bài học',
				'all_items'     => 'Tất cả bài học',
				'search_items'  => 'Tìm bài học',
				'menu_name'     => 'Bài học',
			),
			'public'        => true,
			'has_archive'   => 'bai-hoc',
			'rewrite'       => array( 'slug' => 'bai-hoc', 'with_front' => false ),
			'menu_icon'     => 'dashicons-welcome-learn-more',
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);
	register_taxonomy(
		'lop',
		'bai_hoc',
		array(
			'labels'            => array( 'name' => 'Lớp', 'singular_name' => 'Lớp', 'menu_name' => 'Lớp', 'add_new_item' => 'Thêm lớp' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'chuong-trinh', 'with_front' => false ),
		)
	);
	register_taxonomy(
		'mon_hoc',
		'bai_hoc',
		array(
			'labels'            => array( 'name' => 'Môn học', 'singular_name' => 'Môn học', 'menu_name' => 'Môn học', 'add_new_item' => 'Thêm môn học' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'mon', 'with_front' => false ),
		)
	);
}
add_action( 'init', 'hv_register_content' );

/**
 * Số lớp (1–5) của 1 mục Lớp, theo slug lop-N.
 *
 * @param WP_Term|null $term Mục.
 * @return int
 */
function hv_term_grade( $term ) {
	return ( $term && preg_match( '/(\d)/', $term->slug, $m ) ) ? (int) $m[1] : 0;
}

/**
 * Lớp và môn của bài học.
 *
 * @param int $post_id ID.
 * @return array [ grade int, subject WP_Term|null ]
 */
function hv_lesson_terms( $post_id ) {
	$lop   = get_the_terms( $post_id, 'lop' );
	$mon   = get_the_terms( $post_id, 'mon_hoc' );
	$grade = ( $lop && ! is_wp_error( $lop ) ) ? hv_term_grade( $lop[0] ) : 0;
	return array( $grade, ( $mon && ! is_wp_error( $mon ) ) ? $mon[0] : null );
}

/**
 * Thông tin hiển thị của môn (biểu tượng, màu) – theo slug, mặc định cho môn tự thêm.
 *
 * @param WP_Term|string|null $subject Môn.
 * @return array [ tên, biểu tượng, màu, ảnh 3D ]
 */
function hv_subject_info( $subject ) {
	$all  = hv_subjects();
	$slug = $subject instanceof WP_Term ? $subject->slug : (string) $subject;
	if ( isset( $all[ $slug ] ) ) {
		return array( $subject instanceof WP_Term ? $subject->name : $all[ $slug ][0], $all[ $slug ][1], $all[ $slug ][2], $all[ $slug ][4] );
	}
	return array( $subject instanceof WP_Term ? $subject->name : $slug, '📘', '#7c4dff', 'book' );
}

/**
 * Màu của lớp.
 *
 * @param int $grade Lớp.
 * @return string
 */
function hv_grade_color( $grade ) {
	$g = hv_grades();
	return isset( $g[ $grade ] ) ? $g[ $grade ][3] : '#7b4fd6';
}

/**
 * Link trang 1 lớp.
 *
 * @param int $grade Lớp.
 * @return string
 */
function hv_grade_link( $grade ) {
	$g    = hv_grades();
	$term = isset( $g[ $grade ] ) ? get_term_by( 'slug', $g[ $grade ][1], 'lop' ) : null;
	$link = $term ? get_term_link( $term ) : '';
	return $link && ! is_wp_error( $link ) ? $link : get_post_type_archive_link( 'bai_hoc' );
}

/* ---------- Ô thông tin bài học ---------- */

/**
 * Các ô: key => [nhãn, kiểu, gợi ý].
 *
 * @return array
 */
function hv_lesson_fields() {
	return array(
		'chu_de'   => array( 'Chủ đề / mạch kiến thức trong chương trình', 'text', 'Phép cộng, phép trừ trong phạm vi 100' ),
		'icon'     => array( 'Biểu tượng (emoji, để trống = theo môn)', 'text', '➕' ),
		'mo_man'   => array( 'Mở màn – Cú Bông kể tình huống gợi tò mò (được đọc to)', 'textarea', 'Đội bóng của Cú Bông có 38 quả bóng, vừa được tặng thêm 25 quả…' ),
		'nhiem_vu' => array( 'Nhiệm vụ ngoài đời – việc nhỏ em làm cùng gia đình', 'text', 'Đặt tính và cộng tuổi của bố với tuổi của mẹ.' ),
		'muc_tieu' => array( 'Em sẽ học được – mỗi dòng 1 ý', 'textarea', "Đặt tính thẳng cột\nThực hiện phép cộng có nhớ" ),
		'ghi_nho'  => array( 'Ghi nhớ – mỗi dòng 1 ý', 'textarea', 'Cộng hàng đơn vị được từ 10 trở lên thì nhớ 1 sang hàng chục.' ),
		'em_co_biet' => array( 'Em có biết? – điều thú vị mở rộng kiến thức (được đọc to)', 'textarea', 'Số 0 do người Ấn Độ cổ phát minh ra…' ),
		'cau_hoi'  => array( 'Câu hỏi luyện tập – mỗi dòng: Câu hỏi | Đáp án đúng | Đáp án sai | Đáp án sai => Lời giải thích (thứ tự đáp án tự xáo trộn; phần sau => hiện ra sau khi bé trả lời)', 'textarea', '47 + 35 = ? | 82 | 72 | 712 => 7 + 5 = 12, viết 2 nhớ 1; 4 + 3 = 7, thêm 1 là 8. Vậy 82.' ),
		'tro_choi' => array( 'Trò chơi luyện thêm', 'game', '' ),
		'hoclieu'  => array( 'Học liệu điện tử hoclieu.vn của bài (tuỳ chọn – mở bài trong sách điện tử trên hoclieu.vn rồi chép đường dẫn vào đây)', 'url', 'https://hoclieu.vn/…' ),
		'video'    => array( 'Video bài giảng YouTube (tuỳ chọn)', 'url', 'https://www.youtube.com/watch?v=…' ),
		'bo_sach'  => array( 'Dùng được với bộ sách', 'books', '' ),
	);
}

/**
 * Đọc 1 ô của bài học.
 *
 * @param string   $key Khoá.
 * @param int|null $id  ID.
 * @return mixed
 */
function hv_lesson_meta( $key, $id = null ) {
	$id = $id ? $id : get_the_ID();
	$v  = get_post_meta( $id, '_hv_' . $key, true );
	if ( 'bo_sach' === $key ) {
		if ( is_array( $v ) && $v ) {
			return $v;
		}
		$terms = get_the_terms( $id, 'mon_hoc' );
		return hv_default_books( $terms && ! is_wp_error( $terms ) ? $terms[0]->slug : '' );
	}
	return is_string( $v ) ? $v : '';
}

/**
 * Tách ô nhiều dòng.
 *
 * @param string $text Nội dung.
 * @return array
 */
function hv_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) ) );
}

/**
 * Câu hỏi luyện tập dạng mảng cho trò chơi.
 *
 * @param int|null $id ID bài.
 * @return array
 */
function hv_quiz_data( $id = null ) {
	$out = array();
	foreach ( hv_lines( hv_lesson_meta( 'cau_hoi', $id ) ) as $line ) {
		// Phần sau "=>" là lời giải thích hiện ra sau khi bé trả lời.
		$why  = '';
		$cut  = strpos( $line, '=>' );
		$cut2 = strpos( $line, '=&gt;' );
		if ( false !== $cut2 && ( false === $cut || $cut2 < $cut ) ) {
			$why  = substr( $line, $cut2 + 5 );
			$line = substr( $line, 0, $cut2 );
		} elseif ( false !== $cut ) {
			$why  = substr( $line, $cut + 2 );
			$line = substr( $line, 0, $cut );
		}
		$p = array_values( array_filter( array_map( 'trim', explode( '|', $line ) ), 'strlen' ) );
		if ( count( $p ) >= 3 ) {
			$p     = array_map(
				function ( $s ) {
					return html_entity_decode( wp_strip_all_tags( $s ), ENT_QUOTES, 'UTF-8' );
				},
				$p
			);
			$item = array( 'q' => $p[0], 'a' => $p[1], 'w' => array_slice( $p, 2, 3 ) );
			if ( trim( $why ) ) {
				$item['e'] = html_entity_decode( wp_strip_all_tags( trim( $why ) ), ENT_QUOTES, 'UTF-8' );
			}
			$out[] = $item;
		}
	}
	return $out;
}

/**
 * Hộp nhập liệu trong trang sửa bài học.
 */
function hv_lesson_metabox() {
	add_meta_box( 'hv_lesson', 'Thông tin bài học', 'hv_lesson_metabox_html', 'bai_hoc', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'hv_lesson_metabox' );

/**
 * In hộp nhập liệu.
 *
 * @param WP_Post $post Bài.
 */
function hv_lesson_metabox_html( $post ) {
	wp_nonce_field( 'hv_lesson_save', 'hv_lesson_nonce' );
	echo '<p style="color:#555">Chọn <strong>Lớp</strong> và <strong>Môn học</strong> ở cột bên phải. Thứ tự bài trong môn: ô <em>Thứ tự</em> ở khung <em>Thuộc tính</em>. Nội dung bài viết ở khung soạn thảo phía trên (có thể dùng khối <code>&lt;div class="hv-example"&gt;</code> cho ví dụ, <code>&lt;div class="hv-tip"&gt;</code> cho mẹo).</p>';
	echo '<table class="form-table" role="presentation">';
	foreach ( hv_lesson_fields() as $key => $f ) {
		$name = 'hv_' . $key;
		echo '<tr><th><label for="' . esc_attr( $name ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( 'textarea' === $f[1] ) {
			echo '<textarea class="large-text" rows="' . ( 'cau_hoi' === $key ? 7 : 3 ) . '" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" placeholder="' . esc_attr( $f[2] ) . '">' . esc_textarea( hv_lesson_meta( $key, $post->ID ) ) . '</textarea>';
		} elseif ( 'game' === $f[1] ) {
			$cur = hv_lesson_meta( $key, $post->ID );
			echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"><option value="">— Không —</option>';
			foreach ( hv_games() as $gid => $g ) {
				echo '<option value="' . esc_attr( $gid ) . '"' . selected( $cur, $gid, false ) . '>' . esc_html( $g[1] . ' ' . $g[0] ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'books' === $f[1] ) {
			$cur = hv_lesson_meta( $key, $post->ID );
			foreach ( hv_books() as $bid => $bname ) {
				echo '<label style="margin-right:16px"><input type="checkbox" name="hv_bo_sach[]" value="' . esc_attr( $bid ) . '"' . checked( in_array( $bid, $cur, true ), true, false ) . '> ' . esc_html( $bname ) . '</label>';
			}
		} else {
			echo '<input type="' . ( 'url' === $f[1] ? 'url' : 'text' ) . '" class="regular-text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( hv_lesson_meta( $key, $post->ID ) ) . '" placeholder="' . esc_attr( $f[2] ) . '">';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

/**
 * Lưu ô thông tin.
 *
 * @param int $post_id ID.
 */
function hv_lesson_save( $post_id ) {
	if ( ! isset( $_POST['hv_lesson_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hv_lesson_nonce'] ) ), 'hv_lesson_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( hv_lesson_fields() as $key => $f ) {
		if ( 'books' === $f[1] ) {
			$raw = isset( $_POST['hv_bo_sach'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['hv_bo_sach'] ) ) : array();
			update_post_meta( $post_id, '_hv_bo_sach', array_values( array_intersect( $raw, array_keys( hv_books() ) ) ) );
			continue;
		}
		$raw = isset( $_POST[ 'hv_' . $key ] ) ? wp_unslash( $_POST[ 'hv_' . $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch theo kiểu ô ngay dưới.
		if ( 'textarea' === $f[1] ) {
			$val = sanitize_textarea_field( $raw );
		} elseif ( 'url' === $f[1] ) {
			$val = esc_url_raw( $raw );
		} elseif ( 'game' === $f[1] ) {
			$val = array_key_exists( $raw, hv_games() ) ? $raw : '';
		} else {
			$val = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, '_hv_' . $key, $val );
	}
}
add_action( 'save_post_bai_hoc', 'hv_lesson_save' );

/**
 * Trang lớp / môn / danh sách bài: lấy tất cả bài, theo thứ tự trong môn.
 *
 * @param WP_Query $q Truy vấn.
 */
function hv_pre_get_posts( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_tax( array( 'lop', 'mon_hoc' ) ) || $q->is_post_type_archive( 'bai_hoc' ) ) {
		$q->set( 'posts_per_page', 300 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
}
add_action( 'pre_get_posts', 'hv_pre_get_posts' );

/**
 * Nhóm các bài theo khoá (môn hoặc lớp).
 *
 * @param WP_Post[] $posts Bài.
 * @param string    $by    'subject' | 'grade'.
 * @return array khoá => WP_Post[]
 */
function hv_group_lessons( $posts, $by ) {
	$out = array();
	foreach ( $posts as $p ) {
		list( $grade, $subject ) = hv_lesson_terms( $p->ID );
		$key                     = 'grade' === $by ? $grade : ( $subject ? $subject->slug : '' );
		$out[ $key ][]           = $p;
	}
	if ( 'grade' === $by ) {
		ksort( $out );
	} else {
		$order = array_flip( array_keys( hv_subjects() ) );
		uksort(
			$out,
			function ( $a, $b ) use ( $order ) {
				return ( isset( $order[ $a ] ) ? $order[ $a ] : 99 ) - ( isset( $order[ $b ] ) ? $order[ $b ] : 99 );
			}
		);
	}
	return $out;
}

/**
 * Đọc ngân hàng bài học mẫu (inc/curriculum.txt).
 *
 * @return array
 */
function hv_curriculum() {
	$file = HV_DIR . '/inc/curriculum.txt';
	if ( ! is_readable( $file ) ) {
		return array();
	}
	$lessons = array();
	$cur     = null;
	foreach ( preg_split( '/\r\n|\r|\n/', (string) file_get_contents( $file ) ) as $line ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- tệp trong theme.
		if ( '' === trim( $line ) || 0 === strpos( $line, '#' ) ) {
			continue;
		}
		if ( 0 === strpos( $line, '=== ' ) ) {
			if ( $cur ) {
				$lessons[] = $cur;
			}
			$p   = array_map( 'trim', explode( '|', substr( $line, 4 ) ) );
			$cur = array(
				'slug'    => $p[0],
				'grade'   => (int) $p[1],
				'subject' => $p[2],
				'title'   => $p[3],
				'topic'   => isset( $p[4] ) ? $p[4] : '',
				'icon'    => isset( $p[5] ) ? $p[5] : '',
				'game'    => isset( $p[6] ) ? $p[6] : '',
				'excerpt' => '',
				'hook'    => '',
				'fact'    => '',
				'mission' => '',
				'goals'   => array(),
				'content' => '',
				'notes'   => array(),
				'quiz'    => array(),
			);
			continue;
		}
		if ( ! $cur ) {
			continue;
		}
		$tag  = substr( $line, 0, 2 );
		$rest = trim( substr( $line, 2 ) );
		if ( '> ' === $tag ) {
			$cur['excerpt'] = $rest;
		} elseif ( '~ ' === $tag ) {
			$cur['hook'] = $rest;
		} elseif ( '@ ' === $tag ) {
			$cur['fact'] = $rest;
		} elseif ( '! ' === $tag ) {
			$cur['mission'] = $rest;
		} elseif ( '+ ' === $tag ) {
			$cur['goals'][] = $rest;
		} elseif ( '* ' === $tag ) {
			$cur['notes'][] = $rest;
		} elseif ( '? ' === $tag ) {
			$cur['quiz'][] = $rest;
		} else {
			$cur['content'] .= $line . "\n";
		}
	}
	if ( $cur ) {
		$lessons[] = $cur;
	}
	return $lessons;
}
