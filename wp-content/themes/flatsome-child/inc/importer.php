<?php
/**
 * Trình tạo landing theo chuẩn Flatsome:
 *  - Nạp ảnh đóng gói trong theme vào Thư viện (Media).
 *  - Tạo mỗi mục thành 1 UX Block (Flatsome > UX Blocks) dựng bằng phần tử gốc Flatsome
 *    (section, row, col, ux_image, button, tabgroup, ux_gallery, accordion...) → sửa bằng UX Builder.
 *  - Tạo trang chủ ghép các Block, template "Page - Full Width - Transparent Header - Light Text".
 *  - Tạo menu neo + cấu hình Header Builder của Flatsome (menu, nút, màu).
 *
 * Giao diện > Tạo landing 688. Chạy lại được: Block/trang/menu cũ được cập nhật, không nhân bản.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách ảnh cần nạp: khoá => array( đường dẫn trong theme, chú thích ).
 *
 * @return array
 */
function bds_import_media_list() {
	$list = array();
	foreach ( bds_default_images() as $key => $name ) {
		$file = bds_default_image_file( $key );
		if ( $file ) {
			$list[ $key ] = array( 'assets/img/' . $file, bds_opt( 'hero_title' ) );
		}
	}
	foreach ( bds_modelhouse_groups() as $group ) {
		foreach ( $group['images'] as $n => $img ) {
			$rel = 'assets/nha-mau/' . $group['slug'] . '/' . rawurldecode( basename( $img['full'] ) );
			$list[ 'nm-' . $group['slug'] . '-' . ( $n + 1 ) ] = array( $rel, 'Nhà mẫu ' . $group['label'] . ' – ' . bds_opt( 'hero_title' ) );
		}
	}
	return $list;
}

/**
 * Nạp 1 ảnh trong theme vào Thư viện.
 *
 * @param string $rel Đường dẫn tương đối trong theme.
 * @param string $alt Chữ thay thế.
 * @return int Attachment ID hoặc 0.
 */
function bds_import_one_image( $rel, $alt ) {
	$path = BDS_DIR . '/' . $rel;
	if ( ! is_readable( $path ) ) {
		return 0;
	}
	$name   = sanitize_file_name( 'tc688-' . str_replace( '/', '-', preg_replace( '~^assets/(img|nha-mau)/~', '', $rel ) ) );
	$upload = wp_upload_bits( $name, null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$type = wp_check_filetype( $upload['file'] );
	$id   = wp_insert_attachment(
		array(
			'post_mime_type' => $type['type'],
			'post_title'     => $alt,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $id, '_bds_source', $rel );
	return $id;
}

/**
 * Một lượt nạp ảnh (tối đa $batch ảnh) – tránh quá thời gian chạy của hosting.
 *
 * @param int $batch Số ảnh mỗi lượt.
 * @return array( 'done' => int, 'total' => int ).
 */
function bds_import_media_step( $batch = 6 ) {
	$map   = get_option( 'bds_import_media', array() );
	$list  = bds_import_media_list();
	$count = 0;
	$more  = false;
	foreach ( $list as $key => $item ) {
		if ( ! empty( $map[ $key ] ) && get_post( $map[ $key ] ) ) {
			// Ảnh trong theme đã thay (khác dung lượng) → nạp lại, xoá bản cũ.
			$old_file = get_attached_file( $map[ $key ] );
			$src      = BDS_DIR . '/' . $item[0];
			if ( ! $old_file || ! file_exists( $old_file ) || ! is_readable( $src ) || filesize( $old_file ) === filesize( $src ) ) {
				continue;
			}
			if ( $count >= $batch ) {
				$more = true;
				break;
			}
			wp_delete_attachment( $map[ $key ], true );
			$map[ $key ] = 0;
		}
		if ( $count >= $batch ) {
			$more = true;
			break;
		}
		$map[ $key ] = bds_import_one_image( $item[0], $item[1] );
		++$count;
		update_option( 'bds_import_media', $map, false );
	}
	$done = 0;
	foreach ( $list as $key => $item ) {
		if ( ! empty( $map[ $key ] ) ) {
			++$done;
		}
	}
	return array(
		'done'  => $done,
		'total' => count( $list ),
		'more'  => $more,
	);
}

/**
 * ID ảnh đã nạp theo khoá.
 *
 * @param string $key Khoá.
 * @return int
 */
function bds_mid( $key ) {
	$map = get_option( 'bds_import_media', array() );
	return isset( $map[ $key ] ) ? absint( $map[ $key ] ) : 0;
}

/**
 * Làm sạch giá trị thuộc tính shortcode.
 *
 * @param string $value Giá trị.
 * @return string
 */
function bds_sc( $value ) {
	return str_replace( array( '"', '[', ']' ), array( '”', '(', ')' ), wp_strip_all_tags( (string) $value ) );
}

/**
 * Làm sạch chữ đặt trong nội dung (tránh bị hiểu nhầm là shortcode).
 *
 * @param string $value Chữ.
 * @return string
 */
function bds_tx( $value ) {
	return str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), esc_html( (string) $value ) );
}

/**
 * Tiêu đề mục (dùng phần tử Title của Flatsome).
 *
 * @param string $text  Chữ.
 * @param bool   $light Nền tối.
 * @return string
 */
function bds_sc_title( $text, $light = false ) {
	return sprintf(
		'<p class="bds-kicker text-center">%1$s</p>' . "\n" . '[title style="center" text="%2$s" tag_name="h2" size="160" class="bds-title%3$s"]',
		bds_tx( bds_opt( 'hero_title' ) ),
		bds_sc( $text ),
		$light ? ' bds-title--light' : ''
	);
}

/**
 * Nội dung shortcode của từng Block.
 *
 * @return array slug => array( tiêu đề Block, nội dung, neo, tên menu ).
 */
function bds_import_blocks() {
	$tel     = bds_tel( bds_opt( 'hotline' ) );
	$hotline = bds_opt( 'hotline' );
	$blocks  = array();

	// 1. Banner.
	$trust = '';
	foreach ( bds_lines( bds_opt( 'trust_badges' ) ) as $t ) {
		$trust .= '<li>' . bds_tx( $t[0] ) . '</li>';
	}
	$trust = $trust ? '<ul class="bds-trust">' . $trust . '</ul>' : '';
	$stats = '';
	foreach ( array_slice( bds_lines( bds_opt( 'highlights' ) ), 0, 4 ) as $s ) {
		$stats .= sprintf( "[col span=\"3\" span__sm=\"6\"]\n<div class=\"bds-stat\"><span>%s</span><strong>%s</strong></div>\n[/col]\n", bds_tx( $s[0] ), bds_tx( $s[1] ) );
	}
	$blocks['688-banner'] = array(
		'688 – 01 Banner',
		sprintf(
			'[section label="Banner" bg="%1$d" bg_size="original" bg_overlay="rgba(8, 18, 45, 0.6)" dark="true" padding="140px" padding__sm="110px" class="bds-fs-hero"]
[row v_align="middle"]
[col span="7" span__sm="12"]
<p class="bds-eyebrow">%2$s</p>
<h1 class="bds-hero__title">%3$s</h1>
<p class="bds-hero__subtitle">%4$s</p>
<p class="bds-hero__price">%5$s</p>
[button text="%6$s" color="secondary" radius="6" size="larger" class="bds-pulse" link="#dang-ky"]
[button text="Khám phá dự án" color="primary" radius="6" link="#tong-quan"]
[button text="%7$s" color="white" style="outline" radius="6" icon="icon-phone" link="tel:%8$s"]
%10$s
[/col]
[col span="5" span__sm="12"]
[bds_lead_form title="Nhận bảng giá gốc chủ đầu tư" button="Nhận bảng giá ngay" source="Hero" card="1" compact="1" perks="1"]
[/col]
[/row]
[gap height="30px"]
[row class="bds-stats-row" col_style="divided"]
%9$s[/row]
[/section]',
			bds_mid( 'hero_image' ),
			bds_tx( bds_opt( 'hero_eyebrow' ) ),
			bds_tx( bds_opt( 'hero_title' ) ),
			bds_tx( bds_opt( 'hero_subtitle' ) ),
			bds_tx( bds_opt( 'hero_price' ) ),
			bds_sc( bds_opt( 'hero_cta' ) ),
			bds_sc( $hotline ),
			$tel,
			$stats,
			$trust
		),
		'trang-chu',
		'',
	);

	// 2. Tổng quan.
	$facts = '';
	foreach ( bds_lines( bds_opt( 'overview_facts' ) ) as $f ) {
		$facts .= sprintf( '<div class="bds-facts__row"><dt>%s</dt><dd>%s</dd></div>', bds_tx( $f[0] ), bds_tx( $f[1] ) ) . "\n";
	}
	$blocks['688-tong-quan'] = array(
		'688 – 02 Tổng quan',
		sprintf(
			'[section label="Tổng quan" bg_color="{{IVORY}}" padding="90px"]
[scroll_to title="Tổng quan" link="#tong-quan" bullet="false"]
%1$s
[row v_align="middle"]
[col span="7" span__sm="12"]
<p class="bds-lead">%2$s</p>
<dl class="bds-facts">
%3$s</dl>
[/col]
[col span="5" span__sm="12"]
[ux_image id="%4$d" lightbox="true" depth="2"]
[/col]
[/row]
[/section]',
			bds_sc_title( bds_opt( 'overview_title' ) ),
			bds_tx( bds_opt( 'overview_text' ) ),
			$facts,
			bds_mid( 'overview_image' )
		),
		'tong-quan',
		'Tổng quan',
	);

	// 3. Vị trí.
	$points = '';
	foreach ( bds_lines( bds_opt( 'location_points' ) ) as $p ) {
		$points .= sprintf( '<li><strong>%s</strong><span>%s</span></li>', bds_tx( $p[0] ), bds_tx( $p[1] ) ) . "\n";
	}
	$blocks['688-vi-tri'] = array(
		'688 – 03 Vị trí',
		sprintf(
			'[section label="Vị trí" bg_color="{{NAVY}}" dark="true" padding="90px" class="bds-navy"]
[scroll_to title="Vị trí" link="#vi-tri" bullet="false"]
%1$s
<p class="bds-lead text-center">%2$s</p>
[row v_align="middle"]
[col span="5" span__sm="12"]
<ul class="bds-connect">
%3$s</ul>
[/col]
[col span="7" span__sm="12"]
[ux_image id="%4$d" lightbox="true" depth="2"]
[/col]
[/row]
[/section]',
			bds_sc_title( bds_opt( 'location_title' ) ),
			bds_tx( bds_opt( 'location_text' ) ),
			$points,
			bds_mid( 'location_image' )
		),
		'vi-tri',
		'Vị trí',
	);

	// 4. Tiện ích.
	$items = '';
	foreach ( bds_lines( bds_opt( 'amenities' ) ) as $i => $a ) {
		$items .= sprintf( "[col span=\"4\" span__sm=\"12\"]\n<div class=\"bds-feature\"><span class=\"bds-feature__num\">%02d</span><h3>%s</h3><p>%s</p></div>\n[/col]\n", $i + 1, bds_tx( $a[0] ), bds_tx( $a[1] ) );
	}
	$blocks['688-tien-ich'] = array(
		'688 – 04 Tiện ích',
		sprintf(
			'[section label="Tiện ích" bg_color="#ffffff" padding="90px"]
[scroll_to title="Tiện ích" link="#tien-ich" bullet="false"]
%1$s
[ux_image id="%2$d" lightbox="true" depth="2"]
[gap height="30px"]
[row]
%3$s[/row]
[/section]',
			bds_sc_title( bds_opt( 'amenities_title' ) ),
			bds_mid( 'amenities_image' ),
			$items
		),
		'tien-ich',
		'Tiện ích',
	);

	// 5. Mặt bằng.
	$plans = '';
	foreach ( array( 1, 2 ) as $n ) {
		$plans .= sprintf(
			"[col span=\"6\" span__sm=\"12\"]\n[ux_image id=\"%d\" lightbox=\"true\"]\n<h3 class=\"bds-plan__title\">%s</h3>\n<p class=\"bds-plan__desc\">%s</p>\n[/col]\n",
			bds_mid( "floorplan_{$n}_image" ),
			bds_tx( bds_opt( "floorplan_{$n}_title" ) ),
			bds_tx( bds_opt( "floorplan_{$n}_desc" ) )
		);
	}
	$blocks['688-mat-bang'] = array(
		'688 – 05 Mặt bằng',
		sprintf(
			'[section label="Mặt bằng" bg_color="{{BEIGE}}" padding="90px"]
[scroll_to title="Mặt bằng" link="#mat-bang" bullet="false"]
%1$s
[row col_bg="rgb(255,255,255)" col_bg_radius="8" padding="18px 18px 8px 18px"]
%2$s[/row]
[/section]',
			bds_sc_title( bds_opt( 'floorplans_title' ) ),
			$plans
		),
		'mat-bang',
		'Mặt bằng',
	);

	// 6. Nhà mẫu.
	$tabs = '';
	foreach ( bds_modelhouse_groups() as $group ) {
		$ids = array();
		foreach ( $group['images'] as $n => $img ) {
			$id = bds_mid( 'nm-' . $group['slug'] . '-' . ( $n + 1 ) );
			if ( $id ) {
				$ids[] = $id;
			}
		}
		if ( $ids ) {
			$tabs .= sprintf(
				"[tab title=\"%s\"]\n[ux_gallery ids=\"%s\" style=\"normal\" columns=\"4\" columns__sm=\"2\" col_spacing=\"xsmall\" image_height=\"75%%\" image_size=\"medium_large\"]\n[/tab]\n",
				bds_sc( $group['label'] ),
				implode( ',', $ids )
			);
		}
	}
	$blocks['688-nha-mau'] = array(
		'688 – 06 Nhà mẫu',
		sprintf(
			'[section label="Nhà mẫu" padding="90px"]
[scroll_to title="Nhà mẫu" link="#nha-mau" bullet="false"]
%1$s
<p class="bds-lead text-center">%2$s</p>
[tabgroup style="pills" align="center" nav_style="normal"]
%3$s[/tabgroup]
<p class="text-center">[button text="Đăng ký tham quan nhà mẫu" color="secondary" radius="6" link="#dang-ky"]</p>
[/section]',
			bds_sc_title( bds_opt( 'modelhouse_title' ) ),
			bds_tx( bds_opt( 'modelhouse_text' ) ),
			$tabs
		),
		'nha-mau',
		'Nhà mẫu',
	);

	// 7. Thư viện ảnh + video.
	$gallery = array();
	for ( $i = 1; $i <= 8; $i++ ) {
		if ( bds_mid( "gallery_{$i}" ) ) {
			$gallery[] = bds_mid( "gallery_{$i}" );
		}
	}
	$yt    = bds_youtube_id( bds_opt( 'video_url' ) );
	$mp4   = bds_video_mp4_url();
	$video = '';
	if ( $yt ) {
		$video = sprintf( '[ux_video url="https://www.youtube.com/watch?v=%s"]', $yt );
	} elseif ( $mp4 ) {
		$video = sprintf( '[video mp4="%s" poster="%s" preload="none"][/video]', esc_url( $mp4 ), esc_url( wp_get_attachment_image_url( bds_mid( 'hero_image' ), 'large' ) ) );
	}
	$blocks['688-hinh-anh'] = array(
		'688 – 07 Hình ảnh & Video',
		sprintf(
			'[section label="Hình ảnh & Video" bg_color="{{NAVY}}" dark="true" padding="90px" class="bds-navy"]
[scroll_to title="Hình ảnh" link="#hinh-anh" bullet="false"]
%1$s
[ux_gallery ids="%2$s" style="normal" columns="4" columns__sm="2" col_spacing="xsmall" image_height="75%%" image_size="medium_large"]
[gap height="40px"]
[row h_align="center"]
[col span="10" span__sm="12"]
%3$s
[/col]
[/row]
[/section]',
			bds_sc_title( bds_opt( 'gallery_title' ) ),
			implode( ',', $gallery ),
			$video
		),
		'hinh-anh',
		'Hình ảnh',
	);

	// 8. Chính sách bán hàng.
	$pol = '';
	foreach ( bds_lines( bds_opt( 'pricing_items' ) ) as $p ) {
		$pol .= sprintf( "[col span=\"4\" span__sm=\"12\"]\n<div class=\"bds-policy\"><i class=\"icon-checkmark\"></i><h3>%s</h3><p>%s</p></div>\n[/col]\n", bds_tx( $p[0] ), bds_tx( $p[1] ) );
	}
	$blocks['688-chinh-sach'] = array(
		'688 – 08 Chính sách bán hàng',
		sprintf(
			'[section label="Chính sách" bg_color="#ffffff" padding="90px"]
[scroll_to title="Giá bán" link="#chinh-sach" bullet="false"]
%1$s
<div class="bds-price-card">
<p class="bds-price-card__label">Giá bán chỉ từ</p>
<p class="bds-price-card__value">%3$s</p>
<p class="bds-price-card__note">%4$s</p>
<p class="bds-price-card__actions">[button text="Nhận bảng giá chi tiết" color="secondary" radius="6" size="large" class="bds-pulse" link="#dang-ky"]</p>
<div class="bds-callbtns bds-callbtns--center">' . bds_call_buttons_html() . '</div>
</div>
[row]
%2$s[/row]
<p class="text-center">[button text="Nhận chính sách chi tiết" color="secondary" radius="6" link="#dang-ky"]</p>
[/section]',
			bds_sc_title( bds_opt( 'pricing_title' ) ),
			$pol,
			bds_tx( bds_opt( 'price_from' ) ),
			bds_tx( bds_opt( 'price_note' ) ),
			bds_sc( $hotline ),
			$tel
		),
		'chinh-sach',
		'Giá bán',
	);

	// 9. Lý do sở hữu.
	$rs = '';
	foreach ( bds_lines( bds_opt( 'reasons' ) ) as $i => $r ) {
		$rs .= sprintf( "[col span=\"4\" span__sm=\"12\"]\n<div class=\"bds-reason\"><span class=\"bds-reason__num\">%d</span><h3>%s</h3><p>%s</p></div>\n[/col]\n", $i + 1, bds_tx( $r[0] ), bds_tx( $r[1] ) );
	}
	$blocks['688-ly-do'] = array(
		'688 – 09 Lý do sở hữu',
		sprintf(
			'[section label="Lý do sở hữu" bg_color="{{NAVY}}" dark="true" padding="90px" class="bds-navy"]
%1$s
[row]
%2$s[/row]
[/section]',
			bds_sc_title( bds_opt( 'reasons_title' ) ),
			$rs
		),
		'ly-do',
		'',
	);

	// 10. Chủ đầu tư.
	$dp = '';
	foreach ( bds_lines( bds_opt( 'developer_points' ) ) as $d ) {
		$dp .= sprintf( "[col span=\"6\" span__sm=\"12\"]\n<div class=\"bds-dev-point\"><strong>%s</strong><span>%s</span></div>\n[/col]\n", bds_tx( $d[0] ), bds_tx( $d[1] ) );
	}
	$fields = '';
	foreach ( bds_lines( bds_opt( 'developer_fields' ) ) as $f ) {
		$fields .= sprintf( "[col span=\"3\" span__sm=\"6\"]\n<div class=\"bds-field\"><h3>%s</h3><p>%s</p></div>\n[/col]\n", bds_tx( $f[0] ), bds_tx( $f[1] ) );
	}
	$blocks['688-chu-dau-tu'] = array(
		'688 – 10 Chủ đầu tư',
		sprintf(
			'[section label="Chủ đầu tư" bg_color="#ffffff" padding="90px"]
[scroll_to title="Chủ đầu tư" link="#chu-dau-tu" bullet="false"]
%1$s
<p class="bds-dev-slogan text-center">%5$s</p>
[row v_align="middle"]
[col span="6" span__sm="12"]
[ux_image id="%4$d" lightbox="true" depth="2"]
[/col]
[col span="6" span__sm="12"]
<p class="bds-lead">%2$s</p>
[row_inner]
%3$s[/row_inner]
[/col]
[/row]
[row class="bds-fields"]
%6$s[/row]
[/section]',
			bds_sc_title( bds_opt( 'developer_title' ) ),
			bds_tx( bds_opt( 'developer_text' ) ),
			str_replace( array( '[col ', '[/col]' ), array( '[col_inner ', '[/col_inner]' ), $dp ),
			bds_mid( 'developer_image' ),
			bds_tx( bds_opt( 'developer_slogan' ) ),
			$fields
		),
		'chu-dau-tu',
		'Chủ đầu tư',
	);

	// 11. FAQ (Accordion của Flatsome, bật dữ liệu FAQ cho Google).
	$faq = '';
	foreach ( bds_lines( bds_opt( 'faq' ) ) as $i => $q ) {
		if ( '' === $q[1] ) {
			continue;
		}
		$faq .= sprintf( "[accordion-item title=\"%s\"]\n<p>%s</p>\n[/accordion-item]\n", bds_sc( $q[0] ), bds_tx( $q[1] ) );
	}
	$blocks['688-faq'] = array(
		'688 – 11 Câu hỏi thường gặp',
		sprintf(
			'[section label="FAQ" bg_color="{{BEIGE}}" padding="90px"]
[scroll_to title="FAQ" link="#faq" bullet="false"]
%1$s
[row h_align="center"]
[col span="9" span__sm="12"]
[accordion auto_open="true" faq_schema="true"]
%2$s[/accordion]
[/col]
[/row]
[/section]',
			bds_sc_title( bds_opt( 'faq_title' ) ),
			$faq
		),
		'faq',
		'FAQ',
	);

	// 12. Đăng ký.
	$agency = '';
	foreach ( array( 'agency_name', 'agency_address' ) as $k ) {
		if ( bds_opt( $k ) ) {
			$agency .= '<p class="bds-agency__line">' . bds_tx( bds_opt( $k ) ) . '</p>' . "\n";
		}
	}
	if ( is_email( bds_opt( 'contact_email' ) ) ) {
		$agency .= sprintf( '<p class="bds-agency__line">Email: <a href="mailto:%1$s">%1$s</a></p>', esc_attr( bds_opt( 'contact_email' ) ) ) . "\n";
	}
	$blocks['688-dang-ky'] = array(
		'688 – 12 Đăng ký',
		sprintf(
			'[section label="Đăng ký" bg="%1$d" bg_overlay="rgba(255, 255, 255, 0.94)" padding="90px" class="bds-register-light"]
[scroll_to title="Liên hệ" link="#dang-ky" bullet="false"]
%2$s
[row v_align="middle"]
[col span="6" span__sm="12"]
<p class="bds-lead">%3$s</p>
<p class="bds-register__hotline">Hotline tư vấn 24/7: <a href="tel:%4$s">%5$s</a></p>
%6$s[/col]
[col span="6" span__sm="12"]
[bds_lead_form title="Đăng ký nhận bảng giá & tư vấn" button="Nhận bảng giá ngay" source="Cuối trang" full="1" card="1" perks="1"]
[/col]
[/row]
[row]
[col span="12"]
<p class="bds-disclaimer">%7$s</p>
[/col]
[/row]
[/section]',
			bds_mid( 'register_image' ),
			bds_sc_title( bds_opt( 'register_title' ) ),
			bds_tx( bds_opt( 'register_text' ) ),
			$tel,
			bds_tx( $hotline ),
			$agency,
			bds_tx( bds_opt( 'disclaimer' ) )
		),
		'dang-ky',
		'',
	);

	// Dải ưu đãi (ngay dưới banner) và khối kêu gọi giữa trang.
	$offers = '';
	foreach ( bds_lines( bds_opt( 'offers' ) ) as $o ) {
		$offers .= sprintf( "[col span=\"3\" span__sm=\"6\"]\n<div class=\"bds-offer\"><span>%s</span><strong>%s</strong></div>\n[/col]\n", bds_tx( $o[0] ), bds_tx( $o[1] ) );
	}
	$offer_block = sprintf(
		'[section label="Ưu đãi" bg_color="{{NAVY}}" dark="true" padding="26px" class="bds-offers"]
[row v_align="middle" col_style="divided"]
%1$s[/row]
[/section]',
		$offers
	);
	$zalo = bds_tel( bds_opt( 'zalo' ) );
	$cta  = sprintf(
		'[section label="Kêu gọi" bg_color="{{NAVY}}" dark="true" padding="50px" class="bds-cta bds-navy"]
[row v_align="middle"]
[col span="7" span__sm="12"]
<h3 class="bds-cta__title">%1$s</h3>
<p class="bds-cta__text">%2$s</p>
[/col]
[col span="5" span__sm="12" class="bds-cta__actions"]
[button text="Nhận bảng giá ngay" color="secondary" radius="6" size="large" class="bds-pulse" link="#dang-ky"]
<div class="bds-callbtns">%5$s</div>
[/col]
[/row]
[/section]',
		bds_tx( bds_opt( 'cta_title' ) ),
		bds_tx( bds_opt( 'cta_text' ) ),
		bds_sc( $hotline ),
		$tel,
		bds_call_buttons_html()
	);
	$ordered = array();
	foreach ( $blocks as $slug => $b ) {
		$ordered[ $slug ] = $b;
		if ( '688-banner' === $slug ) {
			$ordered['688-uu-dai'] = array( '688 – 01b Dải ưu đãi', $offer_block, 'uu-dai', '' );
		}
		if ( '688-tien-ich' === $slug ) {
			$ordered['688-cta-1'] = array( '688 – 04b Kêu gọi (sau Tiện ích)', $cta, 'cta-1', '' );
		}
		if ( '688-nha-mau' === $slug ) {
			$ordered['688-cta-2'] = array( '688 – 06b Kêu gọi (sau Nhà mẫu)', $cta, 'cta-2', '' );
		}
	}
	$blocks = $ordered;

	foreach ( $blocks as $slug => $b ) {
		$blocks[ $slug ][1] = str_replace(
			array( '{{NAVY}}', '{{BEIGE}}', '{{GOLD}}', '{{IVORY}}' ),
			array( bds_opt( 'color_primary' ), bds_opt( 'color_beige' ), bds_opt( 'color_accent' ), bds_opt( 'color_ivory' ) ),
			$b[1]
		);
	}
	return $blocks;
}

/**
 * Tạo/cập nhật Blocks, trang chủ, menu và cấu hình header Flatsome.
 *
 * @return array Kết quả.
 */
function bds_import_content() {
	// Bảng màu mặc định cũ → bảng màu hiện tại (navy + vàng kim); màu bạn tự chọn trong Tuỳ biến được giữ nguyên.
	$old_palette = array(
		'color_primary' => array( '#0b1734', '#1f2430' ),
		'color_accent'  => array( '#b8914a', '#e8411c' ),
		'color_beige'   => array( '#f4ede1', '#f6f3ef' ),
		'color_ivory'   => array( '#fbf8f2' ),
	);
	foreach ( $old_palette as $key => $olds ) {
		if ( in_array( strtolower( (string) get_theme_mod( 'bds_' . $key, '' ) ), $olds, true ) ) {
			remove_theme_mod( 'bds_' . $key );
		}
	}

	$ids    = get_option( 'bds_import_ids', array() );
	$blocks = bds_import_blocks();
	$page   = '';

	foreach ( $blocks as $slug => $b ) {
		$existing = get_page_by_path( $slug, OBJECT, 'blocks' );
		$data     = array(
			'post_type'    => 'blocks',
			'post_status'  => 'publish',
			'post_title'   => $b[0],
			'post_name'    => $slug,
			'post_content' => $b[1],
		);
		if ( $existing ) {
			$data['ID'] = $existing->ID;
			wp_update_post( wp_slash( $data ) );
		} else {
			wp_insert_post( wp_slash( $data ) );
		}
		$page .= '[block id="' . $slug . '"]' . "\n\n";
	}

	// Trang chủ.
	$page_id = ! empty( $ids['page'] ) && get_post( $ids['page'] ) ? (int) $ids['page'] : 0;
	$data    = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => bds_opt( 'hero_title' ),
		'post_name'    => 'the-collection-688',
		'post_content' => $page,
	);
	if ( $page_id ) {
		$data['ID'] = $page_id;
		wp_update_post( wp_slash( $data ) );
	} else {
		$page_id = wp_insert_post( wp_slash( $data ) );
	}
	update_post_meta( $page_id, '_wp_page_template', 'page-transparent-header-light.php' );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page_id );
	$ids['page'] = $page_id;

	// Menu neo.
	$menu_name = 'Menu Landing 688';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
	if ( ! is_wp_error( $menu_id ) ) {
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		foreach ( $blocks as $b ) {
			if ( '' === $b[3] ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $b[3],
					'menu-item-url'    => '#' . $b[2],
					'menu-item-type'   => 'custom',
					'menu-item-status' => 'publish',
				)
			);
		}
		$locations                   = get_theme_mod( 'nav_menu_locations', array() );
		$locations['primary']        = $menu_id;
		$locations['primary_mobile'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		$ids['menu'] = $menu_id;
	}

	// Sao lưu cấu hình header Flatsome trước khi đổi (chỉ lần đầu).
	$keys = array( 'header_elements_left', 'header_elements_right', 'header_mobile_elements_left', 'header_mobile_elements_right', 'mobile_sidebar', 'header_button_1', 'header_button_1_link', 'header_button_1_radius', 'header_button_1_color', 'color_primary', 'color_secondary', 'nav_uppercase', 'header_height', 'topbar_show', 'header_color', 'header_bg', 'type_nav_color', 'type_nav_color_hover' );
	if ( ! get_option( 'bds_import_header_backup' ) ) {
		$backup = array();
		foreach ( $keys as $k ) {
			$backup[ $k ] = get_theme_mod( $k, null );
		}
		update_option( 'bds_import_header_backup', $backup, false );
	}

	set_theme_mod( 'header_elements_left', array() );
	set_theme_mod( 'header_elements_right', array( 'nav', 'button-1' ) );
	set_theme_mod( 'header_mobile_elements_left', array( 'menu-icon' ) );
		set_theme_mod( 'mobile_sidebar', array( 'nav', 'button-1' ) );
	set_theme_mod( 'header_button_1', bds_opt( 'nav_cta' ) ? bds_opt( 'nav_cta' ) : 'Nhận bảng giá' );
	set_theme_mod( 'header_button_1_link', '#dang-ky' );
		set_theme_mod( 'header_button_1_color', 'secondary' );
	set_theme_mod( 'color_primary', bds_opt( 'color_primary' ) );
	set_theme_mod( 'color_secondary', bds_opt( 'color_accent' ) );
	set_theme_mod( 'nav_uppercase', 1 );
	set_theme_mod( 'header_height', 80 );
	set_theme_mod( 'topbar_show', 0 );
	// Header đặc (khi cuộn / trang khác): nền màu chủ đạo, chữ sáng.
	// Header navy, chữ vàng kim (như web mẫu, nổi bật hơn).
	set_theme_mod( 'header_color', 'dark' );
	set_theme_mod( 'header_bg', bds_opt( 'color_primary' ) );
	set_theme_mod( 'type_nav_color', '#f0c96a' );
	set_theme_mod( 'type_nav_color_hover', '#ffffff' );
	set_theme_mod( 'type_headings_color', bds_opt( 'color_primary' ) );
	set_theme_mod( 'color_links', bds_opt( 'color_primary' ) );
	set_theme_mod( 'color_links_hover', bds_opt( 'color_accent' ) );
	set_theme_mod( 'header_button_1_radius', '6px' );
	set_theme_mod( 'header_sticky', 1 );
	set_theme_mod( 'logo_width', 90 );
	set_theme_mod( 'header_mobile_elements_right', array() );
	set_theme_mod( 'html_custom_css', '' );
	set_theme_mod( 'topbar_left', '' );
	bds_import_clean_legacy_scripts();

	// Tắt chế độ template/menu riêng của theme con: từ giờ dùng chuẩn Flatsome.
	set_theme_mod( 'bds_landing_front', 0 );
	set_theme_mod( 'bds_own_header', 0 );

	update_option( 'bds_import_ids', $ids, false );
	return array(
		'page' => $page_id,
		'url'  => get_permalink( $page_id ),
	);
}

/**
 * Dọn đoạn mã cũ dán trong Flatsome → Advanced → Global Settings (Header/Footer Scripts) nếu nó
 * nạp thêm jQuery từ CDN hoặc chứa menu nổi/nút liên hệ kiểu cũ (box_fixRight, float-contact):
 * đoạn này làm hỏng JS của Flatsome và chèn nút chết (tel: trống, m.me/demo). Bản gốc được sao lưu.
 *
 * @return string[] Các ô đã dọn.
 */
function bds_import_clean_legacy_scripts() {
	$keys    = array( 'html_scripts_header', 'html_scripts_footer', 'html_scripts_after_body', 'html_scripts_before_body' );
	$backup  = get_option( 'bds_import_scripts_backup', array() );
	$cleaned = array();
	foreach ( $keys as $k ) {
		remove_filter( 'theme_mod_' . $k, 'bds_strip_extra_jquery' );
		$v = get_theme_mod( $k, '' );
		if ( ! is_string( $v ) || '' === trim( $v ) ) {
			continue;
		}
		if ( preg_match( '#cdnjs\.cloudflare\.com/ajax/libs/jquery|code\.jquery\.com|box_fixRight|float-contact#i', $v ) ) {
			if ( ! isset( $backup[ $k ] ) ) {
				$backup[ $k ] = $v;
			}
			set_theme_mod( $k, '' );
			$cleaned[] = $k;
		}
		add_filter( 'theme_mod_' . $k, 'bds_strip_extra_jquery' );
	}
	if ( $cleaned ) {
		update_option( 'bds_import_scripts_backup', $backup, false );
	}
	return $cleaned;
}

/**
 * Khôi phục cấu hình header Flatsome như trước khi chạy trình tạo.
 */
function bds_import_restore_header() {
	$scripts = get_option( 'bds_import_scripts_backup' );
	if ( is_array( $scripts ) ) {
		foreach ( $scripts as $k => $v ) {
			set_theme_mod( $k, $v );
		}
		delete_option( 'bds_import_scripts_backup' );
	}

	$backup = get_option( 'bds_import_header_backup' );
	if ( ! is_array( $backup ) ) {
		return;
	}
	foreach ( $backup as $k => $v ) {
		if ( null === $v ) {
			remove_theme_mod( $k );
		} else {
			set_theme_mod( $k, $v );
		}
	}
	delete_option( 'bds_import_header_backup' );
}

/**
 * Trang quản trị: Giao diện > Tạo landing 688.
 */
function bds_import_admin_menu() {
	add_theme_page( 'Tạo landing 688', 'Tạo landing 688', 'manage_options', 'bds-import', 'bds_import_admin_page' );
}
add_action( 'admin_menu', 'bds_import_admin_menu' );

/**
 * Nội dung trang quản trị.
 */
function bds_import_admin_page() {
	$ids   = get_option( 'bds_import_ids', array() );
	$nonce = wp_create_nonce( 'bds_import' );
	?>
	<div class="wrap">
		<h1>Tạo landing The Collection 688 (chuẩn Flatsome)</h1>
		<p>Bấm nút bên dưới để tự động:</p>
		<ol>
			<li>Nạp ảnh dự án và ảnh nhà mẫu (đóng gói trong theme) vào <strong>Thư viện</strong>.</li>
			<li>Tạo 15 <strong>UX Block</strong> (Flatsome → UX Blocks), mỗi mục một Block, sửa bằng <strong>UX Builder</strong>.</li>
			<li>Tạo trang chủ ghép các Block (template <em>Page - Full Width - Transparent Header - Light Text</em>) và đặt làm trang chủ.</li>
			<li>Tạo <strong>Menu Landing 688</strong> và gán vào header; cấu hình Header Builder (menu + nút "Nhận bảng giá"), màu chủ đạo.</li>
		</ol>
		<p>Chạy lại được bất cứ lúc nào: Block, trang và menu được <strong>cập nhật</strong> theo nội dung trong <em>Tuỳ biến → Landing Bất động sản</em>, không tạo trùng.
			<br><strong>Lưu ý:</strong> chạy lại sẽ ghi đè các chỉnh sửa bạn đã làm trong UX Builder của các Block "688 – …".</p>

		<p><button type="button" class="button button-primary button-hero" id="bds-import-run">Tạo landing 688</button></p>
		<div id="bds-import-log" style="max-width:720px"></div>

		<?php if ( ! empty( $ids['page'] ) && get_post( $ids['page'] ) ) : ?>
			<hr>
			<p>Đã tạo: <a href="<?php echo esc_url( get_permalink( $ids['page'] ) ); ?>" target="_blank">Xem trang</a> ·
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=blocks' ) ); ?>">Danh sách UX Blocks</a> ·
				<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Sửa menu</a></p>
			<?php if ( get_option( 'bds_import_scripts_backup' ) ) : ?>
				<p><em>Đoạn mã cũ trong Flatsome → Advanced → Global Settings (nạp jQuery CDN / menu nổi kiểu cũ) đã được gỡ vì làm lỗi trang và đã sao lưu.
					Nếu trong đó có mã Google Analytics / Facebook Pixel, hãy dán lại riêng các mã đó.</em></p>
			<?php endif; ?>
			<?php if ( get_option( 'bds_import_header_backup' ) || get_option( 'bds_import_scripts_backup' ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="bds_import_restore">
					<?php wp_nonce_field( 'bds_import_restore' ); ?>
					<p><button class="button">Khôi phục header Flatsome như trước khi tạo</button></p>
				</form>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<script>
	( function () {
		var btn = document.getElementById( 'bds-import-run' ), log = document.getElementById( 'bds-import-log' );
		function say( msg ) { var p = document.createElement( 'p' ); p.innerHTML = msg; log.appendChild( p ); return p; }
		function call( step ) {
			var body = new FormData();
			body.append( 'action', 'bds_import_step' );
			body.append( 'step', step );
			body.append( '_ajax_nonce', '<?php echo esc_js( $nonce ); ?>' );
			return fetch( ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } );
		}
		btn.addEventListener( 'click', function () {
			btn.disabled = true; log.innerHTML = '';
			var line = say( 'Đang nạp ảnh…' );
			( function media() {
				call( 'media' ).then( function ( res ) {
					if ( ! res.success ) { throw new Error( res.data || 'Lỗi nạp ảnh' ); }
					line.textContent = 'Đang nạp ảnh: ' + res.data.done + '/' + res.data.total;
					if ( ( res.data.done < res.data.total || res.data.more ) && res.data.progress ) { return media(); }
					say( 'Đang tạo Blocks, trang chủ, menu, header…' );
					return call( 'content' ).then( function ( r2 ) {
						if ( ! r2.success ) { throw new Error( r2.data || 'Lỗi tạo nội dung' ); }
						say( '<strong>Xong!</strong> <a href="' + r2.data.url + '" target="_blank">Xem trang chủ</a>' );
					} );
				} ).catch( function ( e ) { say( '<span style="color:#b32d2e">' + e.message + ' – bấm lại nút để chạy tiếp.</span>' ); btn.disabled = false; } );
			} )();
		} );
	} )();
	</script>
	<?php
}

/**
 * AJAX: từng bước của trình tạo.
 */
function bds_import_ajax() {
	check_ajax_referer( 'bds_import' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Không có quyền.' );
	}
	if ( ! post_type_exists( 'blocks' ) ) {
		wp_send_json_error( 'Chưa kích hoạt theme Flatsome (không có UX Blocks).' );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
	$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
	if ( 'media' === $step ) {
		$before = bds_import_media_step( 0 );
		$res    = bds_import_media_step( 6 );
		$res['progress'] = $res['done'] > $before['done'] || ! empty( $res['more'] );
		wp_send_json_success( $res );
	}
	if ( 'content' === $step ) {
		wp_send_json_success( bds_import_content() );
	}
	wp_send_json_error( 'Bước không hợp lệ.' );
}
add_action( 'wp_ajax_bds_import_step', 'bds_import_ajax' );

/**
 * Khôi phục header.
 */
function bds_import_restore_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'bds_import_restore' );
	bds_import_restore_header();
	wp_safe_redirect( admin_url( 'themes.php?page=bds-import' ) );
	exit;
}
add_action( 'admin_post_bds_import_restore', 'bds_import_restore_action' );

/**
 * Nhắc chạy trình tạo sau khi cập nhật theme.
 */
function bds_import_notice() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'bds_import_ids' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'appearance_page_bds-import' === $screen->id ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p><strong>Landing The Collection 688:</strong> dựng trang theo chuẩn Flatsome (UX Blocks + Header Builder) chỉ với 1 nút. <a class="button button-primary" href="%s">Mở trình tạo</a></p></div>',
		esc_url( admin_url( 'themes.php?page=bds-import' ) )
	);
}
add_action( 'admin_notices', 'bds_import_notice' );
