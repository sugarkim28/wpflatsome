<?php
/**
 * Menu vui nhộn (biểu tượng 3D, menu xổ dạng lưới) và chân trang đầy đủ chuẩn web:
 * [hv_footer] [hv_contact] [hv_faq]
 *
 * Mục menu gắn biểu tượng 3D bằng class CSS dạng `hv-i-ten` (vd: hv-i-island) – Giao diện → Menu → Tuỳ chọn hiển thị → bật "Lớp CSS".
 * Mô tả của mục menu (ô "Mô tả") hiện thành dòng chữ nhỏ trong menu xổ.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thêm biểu tượng 3D + mô tả vào tiêu đề mục menu.
 *
 * @param string   $title Tiêu đề.
 * @param WP_Post  $item  Mục menu.
 * @param stdClass $args  Tham số menu.
 * @param int      $depth Cấp.
 * @return string
 */
function hv_menu_item_title( $title, $item, $args, $depth ) {
	$icon = '';
	foreach ( (array) $item->classes as $c ) {
		if ( 0 === strpos( $c, 'hv-i-' ) ) {
			$icon = hv_icon3d( substr( $c, 5 ), $depth ? 36 : 26, '', 'hv-mi' );
		}
	}
	$desc = ( $depth && ! empty( $item->description ) ) ? '<small class="hv-md">' . esc_html( $item->description ) . '</small>' : '';
	if ( ! $icon && ! $desc ) {
		return $title;
	}
	return $icon . '<span class="hv-mt">' . $title . $desc . '</span>';
}
add_filter( 'nav_menu_item_title', 'hv_menu_item_title', 10, 4 );

/**
 * Liên kết mạng xã hội (chỉ hiện mục đã nhập link).
 *
 * @return string
 */
function hv_social_links() {
	$icons = array(
		'facebook' => array( 'Facebook', '<path d="M15 3h-3a4 4 0 0 0-4 4v3H6v4h2v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>' ),
		'youtube'  => array( 'YouTube', '<rect x="3" y="6" width="18" height="12" rx="4"/><path d="M10.5 9.5l4 2.5-4 2.5z" fill="currentColor"/>' ),
		'tiktok'   => array( 'TikTok', '<path d="M14 4v10.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 4c.5 2.5 2.5 4 5 4"/>' ),
	);
	$html = '';
	foreach ( $icons as $k => $i ) {
		$url = hv_opt( $k );
		if ( $url ) {
			$html .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $i[0] ) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $i[1] . '</svg></a>';
		}
	}
	$zalo = hv_tel( hv_opt( 'zalo' ) );
	if ( $zalo ) {
		$html .= '<a href="' . esc_url( 'https://zalo.me/' . ltrim( $zalo, '+' ) ) . '" target="_blank" rel="noopener" aria-label="Zalo" class="is-zalo">Zalo</a>';
	}
	return $html ? '<div class="hv-social">' . $html . '</div>' : '';
}

/**
 * Link trang theo slug (để trống nếu chưa có trang).
 *
 * @param string $slug Slug.
 * @return string
 */
function hv_page_url( $slug ) {
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : '';
}

/**
 * [hv_footer] – chân trang: giới thiệu + đơn vị phát triển, lớp học, môn học, khám phá, liên hệ; dải dưới cùng có bản quyền và chính sách.
 *
 * @return string
 */
function hv_sc_footer() {
	$col = function ( $title, $links ) {
		$h = '<nav class="hv-fcol" aria-label="' . esc_attr( $title ) . '"><h3>' . esc_html( $title ) . '</h3><ul>';
		foreach ( $links as $l ) {
			if ( $l[1] ) {
				$h .= '<li><a href="' . esc_url( $l[1] ) . '">' . ( isset( $l[2] ) ? hv_icon3d( $l[2], 22 ) : '' ) . esc_html( $l[0] ) . '</a></li>';
			}
		}
		return $h . '</ul></nav>';
	};
	$grades = array();
	foreach ( hv_grades() as $n => $g ) {
		$grades[] = array( $g[0] . ' – ' . $g[5], hv_grade_link( $n ), $g[7] );
	}
	$subjects = array();
	foreach ( hv_subjects() as $slug => $s ) {
		$subjects[] = array( $s[0], hv_subject_link( $slug ), $s[4] );
	}
	$explore = array(
		array( 'Khu trò chơi', hv_games_url(), 'game' ),
		array( 'Góc của em', hv_page_url( 'goc-cua-em' ), 'trophy' ),
		array( 'Góc phụ huynh', hv_page_url( 'goc-phu-huynh' ), 'heart' ),
		array( 'Tất cả bài học', get_post_type_archive_link( 'bai_hoc' ), 'book' ),
		array( 'Câu hỏi thường gặp', hv_page_url( 'cau-hoi-thuong-gap' ), 'bulb' ),
		array( 'Giới thiệu', hv_page_url( 'gioi-thieu' ), 'owl' ),
	);
	$hot  = hv_opt( 'hotline' );
	$zalo = hv_opt( 'zalo' );
	ob_start();
	?>
	<div class="hv-footer">
		<div class="hv-wrap hv-fgrid">
			<div class="hv-fbrand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hv-flogo"><img src="<?php echo esc_url( hv_logo_url( 'white' ) ); ?>" width="300" height="82" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" loading="lazy"></a>
				<p>Học mà chơi – chơi mà học. Bài học lớp 1–5 theo Chương trình GDPT 2018, bám sách Kết nối tri thức với cuộc sống và Tiếng Anh Global Success, kèm 11 trò chơi học tập miễn phí.</p>
				<div class="hv-fmaker">
					<img src="<?php echo esc_url( HV_URI . '/assets/img/brand/tinhoc119-tron.webp' ); ?>" width="64" height="64" alt="" loading="lazy">
					<span><small>Đơn vị phát triển</small><strong><?php echo esc_html( hv_opt( 'org_name' ) ); ?></strong><em><?php echo esc_html( hv_opt( 'org_slogan' ) ); ?></em></span>
				</div>
				<?php echo hv_social_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape. ?>
			</div>
			<?php
			echo $col( 'Học theo lớp', $grades ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape.
			echo $col( 'Môn học', $subjects ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape.
			echo $col( 'Khám phá', $explore ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape.
			?>
			<div class="hv-fcol hv-fcontact">
				<h3>Liên hệ</h3>
				<ul>
					<?php if ( $hot ) : ?>
						<li><a href="tel:<?php echo esc_attr( hv_tel( $hot ) ); ?>"><span aria-hidden="true">📞</span> Hotline: <b><?php echo esc_html( $hot ); ?></b></a></li>
					<?php endif; ?>
					<?php if ( $zalo ) : ?>
						<li><a href="<?php echo esc_url( 'https://zalo.me/' . ltrim( hv_tel( $zalo ), '+' ) ); ?>" target="_blank" rel="noopener"><span aria-hidden="true">💬</span> Zalo: <b><?php echo esc_html( $zalo ); ?></b></a></li>
					<?php endif; ?>
					<?php if ( hv_opt( 'email' ) ) : ?>
						<li><a href="mailto:<?php echo esc_attr( antispambot( hv_opt( 'email' ) ) ); ?>"><span aria-hidden="true">✉️</span> <?php echo esc_html( antispambot( hv_opt( 'email' ) ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( hv_opt( 'address' ) ) : ?>
						<li><span aria-hidden="true">📍</span> <?php echo esc_html( hv_opt( 'address' ) ); ?></li>
					<?php endif; ?>
					<?php if ( hv_opt( 'hours' ) ) : ?>
						<li><span aria-hidden="true">🕗</span> <?php echo esc_html( hv_opt( 'hours' ) ); ?></li>
					<?php endif; ?>
				</ul>
				<?php if ( hv_page_url( 'lien-he' ) ) : ?>
					<a class="hv-btn hv-btn-white hv-btn-sm" href="<?php echo esc_url( hv_page_url( 'lien-he' ) ); ?>">Gửi góp ý cho Cú Bông ›</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="hv-fbar">
			<div class="hv-wrap">
				<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> · <?php echo esc_html( hv_opt( 'org_name' ) ); ?>. Nội dung bài học tự biên soạn theo Chương trình GDPT 2018, không thay thế sách giáo khoa.</span>
				<nav aria-label="Chính sách">
					<?php
					foreach ( array( 'chinh-sach-bao-mat' => 'Chính sách bảo mật', 'dieu-khoan-su-dung' => 'Điều khoản sử dụng', 'lien-he' => 'Liên hệ' ) as $hv_s => $hv_t ) {
						if ( hv_page_url( $hv_s ) ) {
							echo '<a href="' . esc_url( hv_page_url( $hv_s ) ) . '">' . esc_html( $hv_t ) . '</a>';
						}
					}
					?>
					<a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>">Sơ đồ trang</a>
				</nav>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'hv_footer', 'hv_sc_footer' );

/**
 * [hv_contact] – thẻ thông tin liên hệ (trang Liên hệ).
 *
 * @return string
 */
function hv_sc_contact() {
	$rows = array(
		array( 'speech', 'Hotline', hv_opt( 'hotline' ), 'tel:' . hv_tel( hv_opt( 'hotline' ) ) ),
		array( 'speech', 'Zalo', hv_opt( 'zalo' ), 'https://zalo.me/' . ltrim( hv_tel( hv_opt( 'zalo' ) ), '+' ) ),
		array( 'scroll', 'Email', antispambot( hv_opt( 'email' ) ), 'mailto:' . antispambot( hv_opt( 'email' ) ) ),
		array( 'map', 'Địa chỉ', hv_opt( 'address' ), '' ),
		array( 'clock', 'Giờ hỗ trợ', hv_opt( 'hours' ), '' ),
	);
	$html = '<div class="hv-contact-grid">';
	foreach ( $rows as $r ) {
		if ( ! $r[2] ) {
			continue;
		}
		$val   = $r[3] ? '<a href="' . esc_url( $r[3] ) . '">' . esc_html( $r[2] ) . '</a>' : esc_html( $r[2] );
		$html .= '<div class="hv-why-item">' . hv_icon3d( $r[0], 44 ) . '<h3>' . esc_html( $r[1] ) . '</h3><p>' . $val . '</p></div>';
	}
	return $html . '</div>';
}
add_shortcode( 'hv_contact', 'hv_sc_contact' );

/**
 * Câu hỏi thường gặp (dùng cho trang FAQ + dữ liệu FAQPage cho Google).
 *
 * @return array
 */
function hv_faq_items() {
	return array(
		array( 'Học Vui có mất phí không?', 'Hoàn toàn miễn phí. Bé học và chơi không giới hạn, không cần tạo tài khoản.' ),
		array( 'Bài học theo bộ sách nào?', 'Theo Chương trình GDPT 2018, sắp xếp theo chủ đề của bộ sách Kết nối tri thức với cuộc sống (Tiếng Anh: Global Success) – bộ sách dùng chung toàn quốc từ năm học 2026–2027. Nội dung là bài tóm tắt, ví dụ, câu hỏi tự biên soạn để ôn tập, không thay thế sách giáo khoa.' ),
		array( 'Bé lớp 1 chưa đọc thạo có dùng được không?', 'Được. Cú Bông đọc to bài học và câu hỏi bằng giọng tiếng Việt. Bố mẹ vào nút ⚙️ để chọn giọng đọc và tốc độ chậm rãi.' ),
		array( 'Sao và nhãn dán của bé được lưu ở đâu?', 'Lưu ngay trên trình duyệt của máy đang dùng, không gửi đi đâu. Đổi máy hoặc xoá dữ liệu trình duyệt thì tiến độ sẽ bắt đầu lại.' ),
		array( 'Mỗi ngày bé nên học bao lâu?', 'Khoảng 15 phút: 1 màn bài học (mở màn, khám phá, bí kíp, đánh trùm) và 1 lượt trò chơi. Không nên để bé dùng màn hình quá 30 phút liên tục.' ),
		array( 'Máy không có tiếng đọc tiếng Việt thì làm sao?', 'Dùng trình duyệt Microsoft Edge (giọng HoaiMy rất ấm) hoặc Google Chrome. Trên điện thoại: Cài đặt → Trợ năng → Nội dung được đọc → Giọng nói → thêm Tiếng Việt.' ),
		array( 'Thầy cô có dùng trên lớp được không?', 'Được. Chiếu trò chơi lên máy chiếu để cả lớp thi tính nhẩm, đố vui. Nhà trường có thể tự thêm bài, câu hỏi trong trang quản trị.' ),
	);
}

/**
 * [hv_faq] – hỏi đáp dạng mở/đóng.
 *
 * @return string
 */
function hv_sc_faq() {
	$html = '<div class="hv-faq">';
	foreach ( hv_faq_items() as $i => $f ) {
		$html .= '<details' . ( 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $f[0] ) . '</summary><p>' . esc_html( $f[1] ) . '</p></details>';
	}
	return $html . '</div>';
}
add_shortcode( 'hv_faq', 'hv_sc_faq' );

/**
 * Dữ liệu FAQPage cho trang Câu hỏi thường gặp.
 */
function hv_faq_schema() {
	if ( ! is_page( 'cau-hoi-thuong-gap' ) ) {
		return;
	}
	$items = array();
	foreach ( hv_faq_items() as $f ) {
		$items[] = array( '@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ) );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}
add_action( 'wp_head', 'hv_faq_schema', 22 );
