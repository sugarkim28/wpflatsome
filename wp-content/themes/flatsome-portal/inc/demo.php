<?php
/**
 * Giao diện → Tạo site mẫu: dựng sẵn phân loại, 8 dự án (The Collection 688 thật + 7 dự án mẫu),
 * 3 bài viết mẫu, trang chủ / giới thiệu / liên hệ (UX Builder), menu, footer, header.
 * Chạy lại được (cập nhật, không tạo trùng). Có nút xoá dữ liệu mẫu.
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu quản trị.
 */
function sgp_demo_menu() {
	add_theme_page( 'Tạo site mẫu', 'Tạo site mẫu', 'manage_options', 'sgp-demo', 'sgp_demo_page' );
}
add_action( 'admin_menu', 'sgp_demo_menu' );

/**
 * Trang quản trị.
 */
function sgp_demo_page() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiện thông báo.
	$msg = isset( $_GET['sgp_demo'] ) ? sanitize_key( wp_unslash( $_GET['sgp_demo'] ) ) : '';
	?>
	<div class="wrap">
		<h1>Tạo site mẫu – tổng hợp dự án</h1>
		<?php if ( 'done' === $msg ) : ?>
			<div class="notice notice-success"><p><strong>Đã tạo xong.</strong> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Xem trang chủ</a> · <a href="<?php echo esc_url( get_post_type_archive_link( 'du_an' ) ); ?>" target="_blank">Xem danh sách dự án</a></p></div>
		<?php elseif ( 'cleaned' === $msg ) : ?>
			<div class="notice notice-success"><p>Đã xoá dự án và bài viết mẫu.</p></div>
		<?php endif; ?>
		<p>Nút này dựng sẵn:</p>
		<ul style="list-style:disc;margin-left:20px">
			<li>Phân loại: Khu vực, Chủ đầu tư, Loại hình, Trạng thái, Khoảng giá.</li>
			<li>8 dự án: <strong>The Collection 688</strong> (dữ liệu thật) + 7 dự án mẫu ghi rõ "(dự án mẫu)" để bạn thay dần bằng dự án thật.</li>
			<li>3 bài viết mẫu, trang <em>Trang chủ</em>, <em>Giới thiệu</em>, <em>Liên hệ</em>, <em>Tin tức</em> (sửa bằng UX Builder).</li>
			<li>Menu chính, footer (UX Block "Footer website"), header Flatsome.</li>
		</ul>
		<p><strong>Lưu ý:</strong> chạy lại sẽ cập nhật các trang/dự án mẫu về nội dung gốc (dự án bạn tự thêm không bị ảnh hưởng).</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:10px">
			<input type="hidden" name="action" value="sgp_demo_run">
			<?php wp_nonce_field( 'sgp_demo_run' ); ?>
			<?php submit_button( 'Tạo site mẫu', 'primary hero', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block" onsubmit="return confirm('Xoá 7 dự án mẫu và 3 bài viết mẫu?');">
			<input type="hidden" name="action" value="sgp_demo_clean">
			<?php wp_nonce_field( 'sgp_demo_clean' ); ?>
			<?php submit_button( 'Xoá dữ liệu mẫu', 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Chạy tạo site mẫu.
 */
function sgp_demo_run() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'sgp_demo_run' );
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- tạo ảnh cần thời gian.
	}
	sgp_demo_build();
	wp_safe_redirect( admin_url( 'themes.php?page=sgp-demo&sgp_demo=done' ) );
	exit;
}
add_action( 'admin_post_sgp_demo_run', 'sgp_demo_run' );

/**
 * Xoá dự án / bài viết mẫu (giữ The Collection 688 và mọi thứ bạn tự tạo).
 */
function sgp_demo_clean() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'sgp_demo_clean' );
	$ids = get_posts(
		array(
			'post_type'      => array( 'du_an', 'post', 'attachment' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_sgp_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 'sample', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	foreach ( $ids as $id ) {
		if ( 'attachment' === get_post_type( $id ) ) {
			wp_delete_attachment( $id, true );
		} else {
			wp_delete_post( $id, true );
		}
	}
	$map = get_option( 'sgp_demo_media', array() );
	foreach ( $map as $k => $id ) {
		if ( ! get_post( $id ) ) {
			unset( $map[ $k ] );
		}
	}
	update_option( 'sgp_demo_media', $map, false );
	wp_safe_redirect( admin_url( 'themes.php?page=sgp-demo&sgp_demo=cleaned' ) );
	exit;
}
add_action( 'admin_post_sgp_demo_clean', 'sgp_demo_clean' );

/**
 * Đưa 1 file ảnh vào Thư viện (dùng lại nếu đã có).
 *
 * @param string $key    Khoá nhận diện.
 * @param string $path   Đường dẫn file.
 * @param string $title  Tiêu đề / alt.
 * @param bool   $sample Đánh dấu là dữ liệu mẫu.
 * @return int ID ảnh.
 */
function sgp_demo_attach( $key, $path, $title, $sample = false ) {
	$map = get_option( 'sgp_demo_media', array() );
	if ( ! empty( $map[ $key ] ) && get_post( $map[ $key ] ) ) {
		return (int) $map[ $key ];
	}
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	$upload = wp_upload_bits( 'sgp-' . $key . '.' . pathinfo( $path, PATHINFO_EXTENSION ), null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$type = wp_check_filetype( $upload['file'] );
	$id   = wp_insert_attachment(
		array(
			'post_title'     => $title,
			'post_mime_type' => $type['type'],
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	if ( $sample ) {
		update_post_meta( $id, '_sgp_demo', 'sample' );
	}
	$map[ $key ] = $id;
	update_option( 'sgp_demo_media', $map, false );
	return (int) $id;
}

/**
 * Vẽ ảnh minh hoạ (bầu trời + toà nhà) bằng GD – không chứa chữ, không dùng ảnh của bên thứ ba.
 *
 * @param string $key   Khoá (cũng là hạt giống ngẫu nhiên → ảnh cố định).
 * @param string $title Tiêu đề / alt.
 * @param array  $sky   [màu trên, màu dưới] dạng hex.
 * @param int    $w     Rộng.
 * @param int    $h     Cao.
 * @return int ID ảnh (0 nếu host không có GD).
 */
function sgp_demo_placeholder( $key, $title, $sky, $w = 1200, $h = 800 ) {
	$map = get_option( 'sgp_demo_media', array() );
	if ( ! empty( $map[ $key ] ) && get_post( $map[ $key ] ) ) {
		return (int) $map[ $key ];
	}
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}
	$hex = function ( $c ) {
		$c = ltrim( $c, '#' );
		return array( hexdec( substr( $c, 0, 2 ) ), hexdec( substr( $c, 2, 2 ) ), hexdec( substr( $c, 4, 2 ) ) );
	};
	$im = imagecreatetruecolor( $w, $h );
	$a  = $hex( $sky[0] );
	$b  = $hex( $sky[1] );
	for ( $y = 0; $y < $h; $y++ ) {
		$t = $y / $h;
		imageline( $im, 0, $y, $w, $y, imagecolorallocate( $im, (int) ( $a[0] + ( $b[0] - $a[0] ) * $t ), (int) ( $a[1] + ( $b[1] - $a[1] ) * $t ), (int) ( $a[2] + ( $b[2] - $a[2] ) * $t ) ) );
	}
	mt_srand( crc32( $key ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- ảnh cố định theo khoá.
	imagefilledellipse( $im, (int) ( $w * 0.78 ), (int) ( $h * 0.28 ), (int) ( $h * 0.22 ), (int) ( $h * 0.22 ), imagecolorallocatealpha( $im, 255, 214, 107, 40 ) );
	$ground = (int) ( $h * 0.86 );
	for ( $layer = 0; $layer < 2; $layer++ ) {
		$x = -20;
		while ( $x < $w ) {
			$bw   = mt_rand( 60, 150 );
			$bh   = mt_rand( (int) ( $h * ( 0.2 + 0.15 * $layer ) ), (int) ( $h * ( 0.45 + 0.2 * $layer ) ) );
			$tone = 0 === $layer ? imagecolorallocate( $im, 40, 58, 104 ) : imagecolorallocate( $im, 13, 27, 62 );
			imagefilledrectangle( $im, $x, $ground - $bh, $x + $bw, $ground, $tone );
			if ( 1 === $layer ) {
				$win = imagecolorallocatealpha( $im, 255, 214, 107, mt_rand( 30, 80 ) );
				for ( $wy = $ground - $bh + 14; $wy < $ground - 12; $wy += 22 ) {
					for ( $wx = $x + 10; $wx < $x + $bw - 14; $wx += 20 ) {
						if ( mt_rand( 0, 3 ) ) {
							imagefilledrectangle( $im, $wx, $wy, $wx + 8, $wy + 10, $win );
						}
					}
				}
			}
			$x += $bw + mt_rand( 4, 30 );
		}
	}
	imagefilledrectangle( $im, 0, $ground, $w, $h, imagecolorallocate( $im, 9, 18, 42 ) );
	imagefilledrectangle( $im, 0, $ground, $w, $ground + 3, imagecolorallocate( $im, 212, 164, 55 ) );
	$tmp = wp_tempnam( 'sgp-' . $key );
	imagejpeg( $im, $tmp, 82 );
	imagedestroy( $im );
	$file = $tmp . '.jpg';
	rename( $tmp, $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
	$id = sgp_demo_attach( $key, $file, $title, true );
	wp_delete_file( $file );
	return $id;
}

/**
 * Tạo / lấy mục phân loại.
 *
 * @param string $tax    Phân loại.
 * @param string $name   Tên.
 * @param string $slug   Slug.
 * @param string $desc   Mô tả.
 * @param array  $meta   Term meta.
 * @return int ID.
 */
function sgp_demo_term( $tax, $name, $slug, $desc = '', $meta = array() ) {
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
		if ( $id && $v && ! get_term_meta( $id, $k, true ) ) {
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
function sgp_demo_post( $data, $meta = array() ) {
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
		update_post_meta( $id, $k, $v );
	}
	return (int) $id;
}

/**
 * Danh sách HTML từ ô "A | B".
 *
 * @param string $text Nội dung.
 * @return string
 */
function sgp_demo_ul( $text ) {
	$out = '<ul>';
	foreach ( sgp_lines( $text ) as $l ) {
		$out .= '<li><strong>' . esc_html( $l[0] ) . '</strong>' . ( $l[1] ? ': ' . esc_html( $l[1] ) : '' ) . '</li>';
	}
	return $out . '</ul>';
}

/**
 * Nội dung chi tiết cho dự án mẫu (chỉ để minh hoạ bố cục H2).
 *
 * @param string $name Tên.
 * @param string $type Loại hình.
 * @param string $area Khu vực.
 * @return string
 */
function sgp_demo_sample_content( $name, $type, $area ) {
	$note = '<p><em>Đây là nội dung mẫu để minh hoạ bố cục trang dự án. Hãy thay bằng thông tin thật trước khi quảng bá.</em></p>';
	$s    = array(
		'Tổng quan dự án ' . $name => 'Giới thiệu ngắn gọn: tên thương mại, tên pháp lý, chủ đầu tư, quy mô, loại hình ' . mb_strtolower( $type ) . ', thời gian bàn giao và định vị sản phẩm. Viết 150–250 chữ, đưa từ khoá chính vào câu đầu tiên.',
		'Vị trí & kết nối'         => 'Mô tả vị trí tại ' . $area . ', các tuyến đường chính, khoảng cách tới trung tâm, trường học, bệnh viện, trung tâm thương mại. Nên kèm bảng khoảng cách và ảnh bản đồ liên kết vùng.',
		'Tiện ích nội khu'         => 'Liệt kê tiện ích theo tầng / khu: hồ bơi, phòng gym, công viên, khu vui chơi trẻ em, an ninh… Mỗi tiện ích một dòng ngắn, kèm ảnh phối cảnh.',
		'Mặt bằng & sản phẩm'      => 'Giới thiệu các loại sản phẩm, diện tích, số phòng ngủ, ảnh mặt bằng tầng điển hình và mặt bằng căn hộ.',
		'Giá bán & phương thức thanh toán' => 'Giá tham khảo, tiến độ thanh toán, chiết khấu, hỗ trợ vay ngân hàng. Luôn ghi ngày cập nhật và lưu ý giá có thể thay đổi theo chính sách chủ đầu tư.',
		'Pháp lý dự án'            => 'Tình trạng pháp lý: quy hoạch 1/500, giấy phép xây dựng, văn bản đủ điều kiện bán nhà ở hình thành trong tương lai, ngân hàng bảo lãnh.',
	);
	$out = $note;
	foreach ( $s as $h => $p ) {
		$out .= "\n<h2>" . esc_html( $h ) . "</h2>\n<p>" . esc_html( $p ) . '</p>';
	}
	return $out;
}

/**
 * Dựng toàn bộ site mẫu.
 */
function sgp_demo_build() {
	if ( '' === get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	$dir = SGP_DIR . '/assets/demo/';
	$d   = require SGP_DIR . '/inc/demo-688.php';

	// 1. Ảnh.
	$img = array(
		'688_hero'   => sgp_demo_attach( '688-hero', $dir . 'hero.jpg', 'The Collection 688 – sảnh căn hộ' ),
		'688_tower'  => sgp_demo_attach( '688-tong-quan', $dir . 'tong-quan.jpg', 'Phối cảnh tháp The Collection 688' ),
		'688_map'    => sgp_demo_attach( '688-vi-tri', $dir . 'vi-tri.jpg', 'Vị trí The Collection 688' ),
		'688_mb1'    => sgp_demo_attach( '688-mat-bang-11-20', $dir . 'mat-bang-tang-11-20.jpg', 'Mặt bằng tầng 11–20 The Collection 688' ),
		'688_mb2'    => sgp_demo_attach( '688-mat-bang-5a-8', $dir . 'mat-bang-tang-5a-8.jpg', 'Mặt bằng tầng 5A–8 The Collection 688' ),
		'688_dev'    => sgp_demo_attach( '688-dicera', $dir . 'chu-dau-tu.jpg', 'DICERA Holdings' ),
		'hero'       => sgp_demo_placeholder( 'home-hero', 'Dự án bất động sản', array( '#0b1838', '#c98a14' ), 1920, 900 ),
	);
	$gallery_688 = array();
	foreach ( array( 'thu-vien-5', 'thu-vien-6', 'thu-vien-7', 'thu-vien-8' ) as $i => $f ) {
		$gallery_688[] = sgp_demo_attach( '688-' . $f, $dir . $f . '.jpg', 'Nhà mẫu The Collection 688 – ảnh ' . ( $i + 1 ) );
	}
	$gallery_688 = array_merge( array( $img['688_tower'], $img['688_map'] ), $gallery_688 );

	// 2. Phân loại.
	$skies = array( array( '#14306a', '#f2c14e' ), array( '#0b1838', '#4f7cc4' ), array( '#123d3a', '#e9b44c' ), array( '#2b1a3f', '#e48a5a' ) );
	$areas = array(
		'tp-ho-chi-minh' => array( 'TP. Hồ Chí Minh', 'Dự án căn hộ, nhà phố tại TP. Hồ Chí Minh: khu Đông (Thủ Đức), khu Nam (Quận 7, Nhà Bè) và trung tâm. (Mô tả mẫu – viết lại 150–300 chữ về thị trường khu vực.)' ),
		'binh-duong'     => array( 'Bình Dương', 'Dự án tại khu vực Bình Dương (cũ) – Thuận An, Dĩ An, Thủ Dầu Một – hưởng lợi từ Metro số 2, Vành đai 3 và các khu công nghiệp VSIP. (Mô tả mẫu.)' ),
		'dong-nai'       => array( 'Đồng Nai', 'Dự án tại Đồng Nai – hưởng lợi từ sân bay Long Thành và cao tốc. (Mô tả mẫu.)' ),
		'long-an'        => array( 'Long An', 'Dự án khu vực Long An – cửa ngõ phía Tây Nam TP.HCM. (Mô tả mẫu.)' ),
	);
	$t_area = array();
	$i      = 0;
	foreach ( $areas as $slug => $a ) {
		$t_area[ $slug ] = sgp_demo_term( 'khu_vuc', $a[0], $slug, $a[1], array( '_sgp_image' => sgp_demo_placeholder( 'area-' . $slug, $a[0], $skies[ $i++ % 4 ], 900, 675 ) ) );
	}
	$t_type = array();
	foreach ( array( 'can-ho' => 'Căn hộ', 'nha-pho-shophouse' => 'Nhà phố – Shophouse', 'biet-thu' => 'Biệt thự', 'dat-nen' => 'Đất nền' ) as $slug => $n ) {
		$t_type[ $slug ] = sgp_demo_term( 'loai_hinh', $n, $slug, $n . ' đang mở bán – thông tin giá, pháp lý, chính sách cập nhật mới nhất. (Mô tả mẫu.)' );
	}
	$t_status = array();
	foreach ( array( 'dang-mo-ban' => 'Đang mở bán', 'sap-mo-ban' => 'Sắp mở bán', 'da-ban-giao' => 'Đã bàn giao' ) as $slug => $n ) {
		$t_status[ $slug ] = sgp_demo_term( 'trang_thai', $n, $slug );
	}
	$t_price = array();
	foreach ( array( 'duoi-2-ty' => 'Dưới 2 tỷ', '2-4-ty' => '2 – 4 tỷ', '4-7-ty' => '4 – 7 tỷ', 'tren-7-ty' => 'Trên 7 tỷ' ) as $slug => $n ) {
		$t_price[ $slug ] = sgp_demo_term( 'khoang_gia', $n, $slug );
	}
	$t_dev = array(
		'dicera-holdings'   => sgp_demo_term( 'chu_dau_tu', 'DICERA Holdings', 'dicera-holdings', $d['developer_text'], array( '_sgp_image' => $img['688_dev'] ) ),
		'chu-dau-tu-mau-a'  => sgp_demo_term( 'chu_dau_tu', 'Chủ đầu tư mẫu A', 'chu-dau-tu-mau-a', 'Mô tả mẫu – thay bằng giới thiệu chủ đầu tư thật.', array( '_sgp_image' => sgp_demo_placeholder( 'dev-a', 'Chủ đầu tư mẫu A', array( '#0b1838', '#24407e' ), 600, 400 ) ) ),
		'chu-dau-tu-mau-b'  => sgp_demo_term( 'chu_dau_tu', 'Chủ đầu tư mẫu B', 'chu-dau-tu-mau-b', 'Mô tả mẫu – thay bằng giới thiệu chủ đầu tư thật.', array( '_sgp_image' => sgp_demo_placeholder( 'dev-b', 'Chủ đầu tư mẫu B', array( '#2b1a3f', '#a8740f' ), 600, 400 ) ) ),
	);

	// 3. The Collection 688 (dữ liệu thật).
	$mb = function ( $id, $cap ) {
		return $id ? '<figure class="wp-block-image">' . wp_get_attachment_image( $id, 'large' ) . '<figcaption>' . esc_html( $cap ) . '</figcaption></figure>' : '';
	};
	$content_688 = '<p>' . esc_html( $d['overview_text'] ) . '</p>'
		. "\n<h2>Vị trí The Collection 688</h2>\n<p>" . esc_html( $d['location_text'] ) . '</p>' . sgp_demo_ul( $d['location_points'] )
		. "\n<h2>Tiện ích The Collection 688</h2>\n" . sgp_demo_ul( $d['amenities'] )
		. "\n<h2>Mặt bằng The Collection 688</h2>\n" . $mb( $img['688_mb1'], $d['floorplan_1_title'] . ' – ' . $d['floorplan_1_desc'] ) . $mb( $img['688_mb2'], $d['floorplan_2_title'] . ' – ' . $d['floorplan_2_desc'] )
		. "\n<h2>Giá bán & chính sách thanh toán</h2>\n<p>Giá bán chỉ từ <strong>" . esc_html( $d['price_from'] ) . '</strong>. ' . esc_html( $d['price_note'] ) . '.</p>' . sgp_demo_ul( $d['pricing_items'] )
		. "\n<h2>Chủ đầu tư DICERA Holdings</h2>\n<p>" . esc_html( $d['developer_text'] ) . '</p>' . sgp_demo_ul( $d['developer_points'] );
	$p688 = sgp_demo_post(
		array(
			'post_type'    => 'du_an',
			'post_name'    => 'the-collection-688',
			'post_title'   => 'The Collection 688',
			'post_excerpt' => $d['hero_subtitle'],
			'post_content' => $content_688,
			'menu_order'   => 1,
		),
		array(
			'_sgp_demo'       => 'real',
			'_sgp_subtitle'   => 'Căn hộ chuẩn sống xanh mặt tiền Đại lộ Bình Dương, 300m tới ga Metro số 2',
			'_sgp_price'      => 'Từ ' . $d['price_from'],
			'_sgp_policy'     => 'Chiết khấu đến 12%',
			'_sgp_address'    => 'Lô 198 Quốc lộ 13, khu phố 1, P. Thuận Giao, TP.HCM',
			'_sgp_scale'      => '01 tháp 39 tầng nổi, 688 sản phẩm',
			'_sgp_area'       => '46 – 166 m²',
			'_sgp_handover'   => 'Quý II/2029',
			'_sgp_legal'      => 'QH 1/500, chủ trương đầu tư',
			'_sgp_highlights' => $d['reasons'],
			'_sgp_faq'        => $d['faq'],
			'_sgp_gallery'    => implode( ',', array_filter( $gallery_688 ) ),
			'_sgp_map'        => 'https://www.google.com/maps?q=' . rawurlencode( 'Lô 198 Quốc lộ 13, Thuận Giao, Thuận An' ) . '&output=embed',
			'_sgp_landing'    => 'https://thecollection688.online/',
			'_sgp_featured'   => '1',
		)
	);
	if ( $p688 ) {
		if ( $img['688_hero'] ) {
			set_post_thumbnail( $p688, $img['688_hero'] );
		}
		wp_set_object_terms( $p688, array( $t_area['binh-duong'] ), 'khu_vuc' );
		wp_set_object_terms( $p688, array( $t_type['can-ho'] ), 'loai_hinh' );
		wp_set_object_terms( $p688, array( $t_status['dang-mo-ban'] ), 'trang_thai' );
		wp_set_object_terms( $p688, array( $t_price['2-4-ty'] ), 'khoang_gia' );
		wp_set_object_terms( $p688, array( $t_dev['dicera-holdings'] ), 'chu_dau_tu' );
	}

	// 4. 7 dự án mẫu.
	$samples = array(
		array( '02', 'Căn hộ ven sông', 'tp-ho-chi-minh', 'can-ho', 'dang-mo-ban', '4-7-ty', 'chu-dau-tu-mau-a', 'Từ 65 triệu/m²', 'Thanh toán 1%/tháng', array( '#14306a', '#f2c14e' ) ),
		array( '03', 'Căn hộ trung tâm', 'tp-ho-chi-minh', 'can-ho', 'sap-mo-ban', '2-4-ty', 'chu-dau-tu-mau-a', 'Từ 52 triệu/m²', 'Booking có hoàn lại', array( '#0b1838', '#4f7cc4' ) ),
		array( '04', 'Căn hộ cạnh khu công nghiệp', 'binh-duong', 'can-ho', 'dang-mo-ban', 'duoi-2-ty', 'chu-dau-tu-mau-b', 'Từ 1,6 tỷ/căn', 'Vay 70%, ân hạn gốc', array( '#123d3a', '#e9b44c' ) ),
		array( '05', 'Biệt thự ven hồ', 'dong-nai', 'biet-thu', 'dang-mo-ban', 'tren-7-ty', 'chu-dau-tu-mau-b', 'Từ 12 tỷ/căn', 'Tặng gói nội thất', array( '#2b1a3f', '#e48a5a' ) ),
		array( '06', 'Nhà phố thương mại', 'binh-duong', 'nha-pho-shophouse', 'sap-mo-ban', '4-7-ty', 'chu-dau-tu-mau-a', 'Từ 5,2 tỷ/căn', 'Chiết khấu 5%', array( '#1b2b52', '#d49a1f' ) ),
		array( '07', 'Đất nền khu đô thị', 'long-an', 'dat-nen', 'dang-mo-ban', 'duoi-2-ty', 'chu-dau-tu-mau-b', 'Từ 15 triệu/m²', 'Sổ riêng từng nền', array( '#0f2a3f', '#8fc1e3' ) ),
		array( '08', 'Căn hộ đã bàn giao', 'tp-ho-chi-minh', 'can-ho', 'da-ban-giao', '4-7-ty', 'chu-dau-tu-mau-a', 'Từ 4,5 tỷ/căn', 'Nhận nhà ở ngay', array( '#2a2140', '#c98a14' ) ),
	);
	foreach ( $samples as $s ) {
		$name    = 'Dự án mẫu ' . $s[0] . ' – ' . $s[1];
		$area    = $areas[ $s[2] ][0];
		$type    = get_term( $t_type[ $s[3] ] )->name;
		$thumb   = sgp_demo_placeholder( 'p' . $s[0], $name, $s[9] );
		$gallery = array( $thumb );
		for ( $g = 1; $g <= 3; $g++ ) {
			$gallery[] = sgp_demo_placeholder( 'p' . $s[0] . '-g' . $g, $name . ' – ảnh ' . $g, array( $s[9][1], $s[9][0] ) );
		}
		$id = sgp_demo_post(
			array(
				'post_type'    => 'du_an',
				'post_name'    => 'du-an-mau-' . $s[0],
				'post_title'   => $name . ' (dự án mẫu)',
				'post_excerpt' => 'Dự án mẫu – ' . mb_strtolower( $type ) . ' tại ' . $area . '. Thay bằng mô tả thật 1–2 câu (hiện ở kết quả Google).',
				'post_content' => sgp_demo_sample_content( $name, $type, $area ),
				'menu_order'   => (int) $s[0],
			),
			array(
				'_sgp_demo'       => 'sample',
				'_sgp_subtitle'   => 'Mô tả ngắn mẫu – ' . mb_strtolower( $type ) . ' tại ' . $area,
				'_sgp_price'      => $s[7],
				'_sgp_policy'     => $s[8],
				'_sgp_address'    => 'Địa chỉ mẫu, ' . $area,
				'_sgp_scale'      => 'Quy mô mẫu',
				'_sgp_area'       => 'Diện tích mẫu',
				'_sgp_handover'   => 'Thời gian bàn giao mẫu',
				'_sgp_legal'      => 'Tình trạng pháp lý mẫu',
				'_sgp_highlights' => "Vị trí | Điểm nổi bật mẫu về vị trí\nTiện ích | Điểm nổi bật mẫu về tiện ích\nPháp lý | Điểm nổi bật mẫu về pháp lý\nThanh toán | Điểm nổi bật mẫu về chính sách",
				'_sgp_faq'        => "Dự án nằm ở đâu? | Câu trả lời mẫu – thay bằng địa chỉ thật.\nGiá bán bao nhiêu? | Câu trả lời mẫu – thay bằng giá thật và ngày cập nhật.\nPháp lý thế nào? | Câu trả lời mẫu – thay bằng tình trạng pháp lý thật.",
				'_sgp_gallery'    => implode( ',', array_filter( $gallery ) ),
				'_sgp_featured'   => in_array( $s[0], array( '02', '03', '04', '05', '06' ), true ) ? '1' : '',
			)
		);
		if ( $id ) {
			if ( $thumb ) {
				set_post_thumbnail( $id, $thumb );
			}
			wp_set_object_terms( $id, array( $t_area[ $s[2] ] ), 'khu_vuc' );
			wp_set_object_terms( $id, array( $t_type[ $s[3] ] ), 'loai_hinh' );
			wp_set_object_terms( $id, array( $t_status[ $s[4] ] ), 'trang_thai' );
			wp_set_object_terms( $id, array( $t_price[ $s[5] ] ), 'khoang_gia' );
			wp_set_object_terms( $id, array( $t_dev[ $s[6] ] ), 'chu_dau_tu' );
		}
	}

	// 5. Bài viết mẫu.
	$cats = array(
		'kinh-nghiem-mua-nha' => sgp_demo_term( 'category', 'Kinh nghiệm mua nhà', 'kinh-nghiem-mua-nha' ),
		'thi-truong'          => sgp_demo_term( 'category', 'Thị trường', 'thi-truong' ),
	);
	$link688 = $p688 ? get_permalink( $p688 ) : home_url( '/du-an/' );
	$posts   = array(
		array( 'kinh-nghiem-chon-can-ho-gan-ga-metro', 'Kinh nghiệm chọn căn hộ gần ga Metro (bài mẫu)', 'kinh-nghiem-mua-nha', '<p><em>Bài viết mẫu – minh hoạ cách viết bài chuẩn SEO và liên kết về trang dự án.</em></p><h2>Khoảng cách đi bộ tới nhà ga</h2><p>Ưu tiên dự án trong bán kính 500m tới ga, có lối đi bộ an toàn. Ví dụ <a href="' . esc_url( $link688 ) . '">The Collection 688</a> cách ga C6 Metro số 2 khoảng 300m.</p><h2>Tiến độ tuyến Metro</h2><p>Kiểm tra quyết định đầu tư và tiến độ thi công tuyến để đánh giá thời điểm hưởng lợi.</p><h2>Pháp lý và tiến độ dự án</h2><p>Yêu cầu xem quy hoạch 1/500, giấy phép xây dựng và văn bản đủ điều kiện bán.</p>' ),
		array( 'luu-y-phap-ly-truoc-khi-dat-coc', 'Lưu ý pháp lý trước khi đặt cọc mua nhà (bài mẫu)', 'kinh-nghiem-mua-nha', '<p><em>Bài viết mẫu.</em></p><h2>Kiểm tra chủ đầu tư</h2><p>Tra cứu doanh nghiệp, các dự án đã bàn giao.</p><h2>Văn bản cần có</h2><p>Quy hoạch, giấy phép xây dựng, bảo lãnh ngân hàng, văn bản đủ điều kiện bán nhà ở hình thành trong tương lai.</p><h2>Điều khoản thỏa thuận đặt cọc</h2><p>Đọc kỹ điều kiện hoàn cọc và thời hạn ký hợp đồng mua bán. Xem thêm <a href="' . esc_url( home_url( '/du-an/' ) ) . '">các dự án đang phân phối</a>.</p>' ),
		array( 'thi-truong-can-ho-vung-ven-tphcm', 'Thị trường căn hộ vùng ven TP.HCM: điểm cần lưu ý (bài mẫu)', 'thi-truong', '<p><em>Bài viết mẫu.</em></p><h2>Hạ tầng dẫn dắt</h2><p>Vành đai 3, Metro số 2, các tuyến cao tốc mở ra cơ hội cho khu vực vùng ven như <a href="' . esc_url( get_term_link( $t_area['binh-duong'] ) ) . '">Bình Dương</a>.</p><h2>Mặt bằng giá</h2><p>So sánh giá theo m² và tổng giá căn, chính sách thanh toán.</p>' ),
	);
	foreach ( $posts as $i => $p ) {
		$pid = sgp_demo_post(
			array(
				'post_type'     => 'post',
				'post_name'     => $p[0],
				'post_title'    => $p[1],
				'post_content'  => $p[3],
				'post_category' => array( $cats[ $p[2] ] ),
			),
			array( '_sgp_demo' => 'sample' )
		);
		$th = sgp_demo_placeholder( 'post-' . $i, $p[1], $skies[ $i % 4 ] );
		if ( $pid && $th ) {
			set_post_thumbnail( $pid, $th );
		}
	}

	// 6. Trang.
	$hero_bg = $img['hero'] ? $img['hero'] : $img['688_hero'];
	$home    = sgp_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'trang-chu',
			'post_title'   => 'Trang chủ',
			'post_content' => sgp_demo_home_content( $hero_bg ),
		),
		array( '_wp_page_template' => 'page-blank.php' )
	);
	$about = sgp_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'gioi-thieu',
			'post_title'   => 'Giới thiệu',
			'post_content' => sgp_demo_about_content(),
		),
		array( '_wp_page_template' => 'page-blank.php' )
	);
	$contact = sgp_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'lien-he',
			'post_title'   => 'Liên hệ',
			'post_content' => sgp_demo_contact_content(),
		),
		array( '_wp_page_template' => 'page-blank.php' )
	);
	$news = sgp_demo_post(
		array(
			'post_type'    => 'page',
			'post_name'    => 'tin-tuc',
			'post_title'   => 'Tin tức',
			'post_content' => '',
		)
	);
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $news );

	// 7. Footer (UX Block).
	$existing = get_page_by_path( 'footer-website', OBJECT, 'blocks' );
	$block    = wp_insert_post(
		array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'blocks',
			'post_status'  => 'publish',
			'post_name'    => 'footer-website',
			'post_title'   => 'Footer website',
			'post_content' => wp_slash( sgp_demo_footer_content( $t_area, $t_type ) ),
		)
	);
	if ( $block && ! is_wp_error( $block ) ) {
		set_theme_mod( 'footer_block', 'footer-website' );
	}
	set_theme_mod( 'footer_left_text', 'Copyright [ux_current_year] © <strong>' . esc_html( sgp_opt( 'company_full' ) ) . '</strong>' );
	set_theme_mod( 'footer_right_text', '' );

	// 8. Menu.
	$menu_name = 'Menu chính';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) {
		wp_delete_post( $it->ID, true );
	}
	$add = function ( $title, $url, $parent = 0, $obj = array() ) use ( $menu_id ) {
		$data = array(
			'menu-item-title'     => $title,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
		);
		if ( $obj ) {
			$data = array_merge( $data, $obj );
		} else {
			$data['menu-item-url']  = $url;
			$data['menu-item-type'] = 'custom';
		}
		return wp_update_nav_menu_item( $menu_id, 0, $data );
	};
	$add( 'Trang chủ', home_url( '/' ) );
	$m_proj = $add( 'Dự án', get_post_type_archive_link( 'du_an' ) );
	foreach ( $t_type as $tid ) {
		$add( get_term( $tid )->name, '', $m_proj, array( 'menu-item-object' => 'loai_hinh', 'menu-item-object-id' => $tid, 'menu-item-type' => 'taxonomy' ) );
	}
	$m_area = $add( 'Khu vực', get_post_type_archive_link( 'du_an' ) );
	foreach ( $t_area as $tid ) {
		$add( get_term( $tid )->name, '', $m_area, array( 'menu-item-object' => 'khu_vuc', 'menu-item-object-id' => $tid, 'menu-item-type' => 'taxonomy' ) );
	}
	$add( 'Tin tức', get_permalink( $news ) );
	$add( 'Giới thiệu', get_permalink( $about ) );
	$add( 'Liên hệ', get_permalink( $contact ) );
	$loc                   = get_theme_mod( 'nav_menu_locations', array() );
	$loc['primary']        = $menu_id;
	$loc['primary_mobile'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $loc );

	// 9. Header Flatsome.
	$hot = sgp_opt( 'hotline' );
	set_theme_mod( 'header_elements_left', array() );
	set_theme_mod( 'header_elements_right', array( 'nav', 'button-1' ) );
	set_theme_mod( 'header_mobile_elements_left', array( 'menu-icon' ) );
	set_theme_mod( 'header_mobile_elements_right', array() );
	set_theme_mod( 'mobile_sidebar', array( 'nav', 'button-1' ) );
	set_theme_mod( 'header_button_1', 'Hotline ' . $hot );
	set_theme_mod( 'header_button_1_link', 'tel:' . sgp_tel( $hot ) );
	set_theme_mod( 'header_button_1_color', 'secondary' );
	set_theme_mod( 'header_button_1_radius', '8px' );
	set_theme_mod( 'color_primary', sgp_opt( 'color_primary' ) );
	set_theme_mod( 'color_secondary', sgp_opt( 'color_accent' ) );
	set_theme_mod( 'header_bg', '#ffffff' );
	set_theme_mod( 'header_color', 'light' );
	set_theme_mod( 'type_nav_color', sgp_opt( 'color_primary' ) );
	set_theme_mod( 'type_nav_color_hover', '#a8740f' );
	set_theme_mod( 'nav_uppercase', 1 );
	set_theme_mod( 'header_height', 80 );
	set_theme_mod( 'header_sticky', 1 );
	set_theme_mod( 'topbar_show', 0 );
	set_theme_mod( 'default_title', 0 );
	if ( ! get_theme_mod( 'site_logo' ) || false !== strpos( (string) get_theme_mod( 'site_logo' ), '/flatsome/assets/img/logo.png' ) ) {
		set_theme_mod( 'site_logo', '' ); // Chưa có logo → hiện tên công ty dạng chữ.
	}
	update_option( 'blogname', sgp_opt( 'company' ) );
	update_option( 'blogdescription', sgp_opt( 'tagline' ) );

	sgp_register_projects();
	flush_rewrite_rules();
}

/**
 * Nội dung trang chủ (UX Builder).
 *
 * @param int $bg ID ảnh nền.
 * @return string
 */
function sgp_demo_home_content( $bg ) {
	$company = sgp_opt( 'company' );
	return '[section label="Banner" bg="' . absint( $bg ) . '" bg_overlay="rgba(8, 18, 45, 0.62)" dark="true" padding="110px" padding__sm="60px" class="sgp-home-hero"]
[row h_align="center"]
[col span="10" span__sm="12" align="center"]
<p class="sgp-kicker" style="color:#ffd66b">' . esc_html( $company ) . ' – ' . esc_html( sgp_opt( 'tagline' ) ) . '</p>
<h1>Dự án căn hộ, nhà phố, đất nền tại TP.HCM &amp; vùng ven</h1>
<p class="lead">Thông tin pháp lý, giá bán và chính sách được cập nhật trực tiếp từ chủ đầu tư. Tư vấn miễn phí, hỗ trợ tham quan nhà mẫu.</p>
[gap height="10px"]
[sgp_search]
[/col]
[/row]
[gap height="30px"]
[row h_align="center" class="sgp-stats"]
[col span="3" span__sm="6" align="center"]<div class="sgp-stat"><strong>[sgp_count what="projects"]</strong><span>dự án đang phân phối</span></div>[/col]
[col span="3" span__sm="6" align="center"]<div class="sgp-stat"><strong>[sgp_count what="areas"]</strong><span>khu vực</span></div>[/col]
[col span="3" span__sm="6" align="center"]<div class="sgp-stat"><strong>[sgp_count what="developers"]</strong><span>chủ đầu tư đối tác</span></div>[/col]
[col span="3" span__sm="6" align="center"]<div class="sgp-stat"><strong>24/7</strong><span>hỗ trợ tư vấn</span></div>[/col]
[/row]
[/section]
[section label="Dự án nổi bật" bg_color="#ffffff" padding="70px"]
[row]
[col span="12"]
<p class="sgp-kicker text-center">Đang mở bán</p>
[title style="center" text="Dự án nổi bật" tag_name="h2" size="160"]
[sgp_projects number="6" featured="1"]
[gap height="24px"]
<p class="text-center">[button text="Xem tất cả dự án" link="/du-an/" color="secondary" radius="8" size="large"]</p>
[/col]
[/row]
[/section]
[section label="Khu vực" bg_color="#f7f3ea" padding="70px"]
[row]
[col span="12"]
<p class="sgp-kicker text-center">Tìm theo vị trí</p>
[title style="center" text="Dự án theo khu vực" tag_name="h2" size="160"]
[sgp_terms taxonomy="khu_vuc" columns="4"]
[/col]
[/row]
[/section]
[section label="Vì sao chọn" bg_color="#ffffff" padding="70px"]
[row][col span="12"][title style="center" text="Vì sao chọn ' . esc_attr( $company ) . '" tag_name="h2" size="160"][/col][/row]
[row]
[col span="3" span__sm="12"]<div class="sgp-why"><b>1</b><h3>Phân phối chính thức</h3><p>Làm việc trực tiếp với chủ đầu tư, giá và chính sách minh bạch. (Nội dung mẫu)</p></div>[/col]
[col span="3" span__sm="12"]<div class="sgp-why"><b>2</b><h3>Pháp lý rõ ràng</h3><p>Cung cấp đầy đủ hồ sơ pháp lý trước khi khách đặt cọc. (Nội dung mẫu)</p></div>[/col]
[col span="3" span__sm="12"]<div class="sgp-why"><b>3</b><h3>Hỗ trợ tài chính</h3><p>Tư vấn phương án vay, kết nối ngân hàng đối tác. (Nội dung mẫu)</p></div>[/col]
[col span="3" span__sm="12"]<div class="sgp-why"><b>4</b><h3>Đồng hành sau bán</h3><p>Hỗ trợ thủ tục ký hợp đồng, nhận nhà, cho thuê lại. (Nội dung mẫu)</p></div>[/col]
[/row]
[/section]
[section label="Chủ đầu tư" bg_color="#f7f3ea" padding="70px"]
[row]
[col span="12"]
[title style="center" text="Chủ đầu tư đối tác" tag_name="h2" size="160"]
[sgp_terms taxonomy="chu_dau_tu" style="logos" columns="4"]
[/col]
[/row]
[/section]
[section label="Tin tức" bg_color="#ffffff" padding="70px"]
[row]
[col span="12"]
[title style="center" text="Tin tức &amp; kinh nghiệm" tag_name="h2" size="160"]
[blog_posts style="normal" columns="3" columns__sm="1" posts="3" image_height="60%" show_date="text" excerpt_length="20"]
[/col]
[/row]
[/section]
[section label="Đăng ký" bg_color="#0b1838" dark="true" padding="60px" class="sgp-cta"]
<div id="dang-ky"></div>
[row v_align="middle"]
[col span="7" span__sm="12"]
<h2 style="color:#ffd66b">Nhận bảng giá &amp; tư vấn chọn dự án</h2>
<p>Để lại thông tin, chuyên viên gửi bảng giá, chính sách mới nhất và so sánh các dự án phù hợp ngân sách của bạn.</p>
[sgp_call_buttons]
[/col]
[col span="5" span__sm="12"]
[sgp_lead_form source="Trang chủ" perks="0"]
[/col]
[/row]
[/section]';
}

/**
 * Nội dung trang Giới thiệu.
 *
 * @return string
 */
function sgp_demo_about_content() {
	return '[section bg_color="#f7f3ea" padding="50px"]
[row][col span="12"]
<h1>Giới thiệu ' . esc_html( sgp_opt( 'company' ) ) . '</h1>
<p class="lead">' . esc_html( sgp_opt( 'company_full' ) ) . ' – ' . esc_html( sgp_opt( 'tagline' ) ) . '.</p>
[/col][/row]
[/section]
[section bg_color="#ffffff" padding="50px"]
[row][col span="8" span__sm="12"]
<p><em>Nội dung mẫu – thay bằng giới thiệu thật của công ty.</em></p>
<h2>Tầm nhìn &amp; sứ mệnh</h2><p>Mô tả tầm nhìn, sứ mệnh, giá trị cốt lõi.</p>
<h2>Đội ngũ</h2><p>Giới thiệu ban lãnh đạo, số lượng chuyên viên, ảnh đội ngũ (tăng độ tin cậy – E-E-A-T).</p>
<h2>Năng lực phân phối</h2><p>Các dự án đã phân phối, chủ đầu tư đối tác, giải thưởng, giấy phép kinh doanh.</p>
<h2>Thông tin pháp nhân</h2><p>' . esc_html( sgp_opt( 'company_full' ) ) . '<br>Trụ sở: ' . esc_html( sgp_opt( 'address' ) ) . '<br>Mã số thuế: [sgp_company field="tax_code"]</p>
[/col]
[col span="4" span__sm="12"]
<div id="dang-ky"></div>
[sgp_lead_form source="Trang giới thiệu"]
[/col][/row]
[/section]';
}

/**
 * Nội dung trang Liên hệ.
 *
 * @return string
 */
function sgp_demo_contact_content() {
	$map = 'https://www.google.com/maps?q=' . rawurlencode( sgp_opt( 'address' ) ) . '&output=embed';
	return '[section bg_color="#f7f3ea" padding="50px"]
[row][col span="12"]
<h1>Liên hệ ' . esc_html( sgp_opt( 'company' ) ) . '</h1>
<p class="lead">Gọi hotline hoặc để lại thông tin, chuyên viên sẽ liên hệ trong thời gian sớm nhất.</p>
[/col][/row]
[/section]
[section bg_color="#ffffff" padding="50px"]
[row]
[col span="6" span__sm="12"]
<h2>Thông tin liên hệ</h2>
<p><strong>' . esc_html( sgp_opt( 'company_full' ) ) . '</strong></p>
<p>Trụ sở: [sgp_company field="address"]<br>Hotline: [sgp_company field="hotline"]<br>Email: [sgp_company field="email"]<br>Giờ làm việc: [sgp_company field="working_hours"]</p>
[sgp_call_buttons]
[gap height="20px"]
<div class="sgp-map__frame"><iframe src="' . esc_url( $map ) . '" title="Bản đồ văn phòng" loading="lazy" allowfullscreen></iframe></div>
[/col]
[col span="6" span__sm="12"]
<div id="dang-ky"></div>
[sgp_lead_form title="Gửi yêu cầu tư vấn" source="Trang liên hệ"]
[/col]
[/row]
[/section]';
}

/**
 * Nội dung footer (UX Block).
 *
 * @param array $areas Khu vực (slug => id).
 * @param array $types Loại hình (slug => id).
 * @return string
 */
function sgp_demo_footer_content( $areas, $types ) {
	$li = function ( $ids, $tax ) {
		$o = '';
		foreach ( $ids as $id ) {
			$t = get_term( $id, $tax );
			if ( $t && ! is_wp_error( $t ) ) {
				$o .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
			}
		}
		return $o;
	};
	return '[section bg_color="#0a1633" dark="true" padding="50px" class="sgp-footer"]
[row]
[col span="4" span__sm="12"]
<h3>[sgp_company field="company"]</h3>
<p><strong>[sgp_company field="company_full"]</strong></p>
<p>Trụ sở: [sgp_company field="address"]<br>Hotline: [sgp_company field="hotline"]<br>Email: [sgp_company field="email"]<br>Giờ làm việc: [sgp_company field="working_hours"]</p>
[/col]
[col span="2" span__sm="6"]
<h3>Dự án</h3>
<ul><li><a href="/du-an/">Tất cả dự án</a></li>' . $li( $types, 'loai_hinh' ) . '</ul>
[/col]
[col span="2" span__sm="6"]
<h3>Khu vực</h3>
<ul>' . $li( $areas, 'khu_vuc' ) . '</ul>
[/col]
[col span="4" span__sm="12"]
<h3>Tư vấn miễn phí</h3>
<p>Gọi hoặc nhắn Zalo để nhận bảng giá và chính sách mới nhất.</p>
[sgp_call_buttons]
[/col]
[/row]
[/section]';
}

/**
 * [sgp_count what="projects|areas|developers"] – số liệu thật lấy từ dữ liệu website.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgp_sc_count( $atts ) {
	$a = shortcode_atts( array( 'what' => 'projects' ), $atts, 'sgp_count' );
	switch ( $a['what'] ) {
		case 'areas':
			return (string) wp_count_terms( array( 'taxonomy' => 'khu_vuc', 'hide_empty' => true ) );
		case 'developers':
			return (string) wp_count_terms( array( 'taxonomy' => 'chu_dau_tu', 'hide_empty' => true ) );
		default:
			return (string) (int) wp_count_posts( 'du_an' )->publish;
	}
}
add_shortcode( 'sgp_count', 'sgp_sc_count' );
