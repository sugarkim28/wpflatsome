<?php
/**
 * Mục "Dự án" + phân loại + ô thông tin + bộ lọc.
 *
 * URL: /du-an/ten-du-an/ · /khu-vuc/... · /chu-dau-tu/... · /loai-hinh/...
 * Trạng thái và Khoảng giá chỉ dùng để lọc (không có trang riêng → tránh trang mỏng).
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các ô thông tin của dự án: key => [nhãn, kiểu, gợi ý].
 *
 * @return array
 */
function sgp_project_fields() {
	return array(
		'subtitle'   => array( 'Mô tả ngắn dưới tiêu đề', 'text', 'Căn hộ chuẩn sống xanh cạnh ga Metro số 2' ),
		'price'      => array( 'Giá hiển thị', 'text', 'Từ 43,688 triệu/m²' ),
		'address'    => array( 'Địa chỉ dự án', 'text', 'Lô 198 QL13, P. Thuận Giao, TP.HCM' ),
		'scale'      => array( 'Quy mô', 'text', '01 tháp 39 tầng, 688 sản phẩm' ),
		'area'       => array( 'Diện tích căn', 'text', '46 – 166 m²' ),
		'handover'   => array( 'Bàn giao', 'text', 'Quý II/2029' ),
		'legal'      => array( 'Pháp lý', 'text', 'QH 1/500, chủ trương đầu tư' ),
		'policy'     => array( 'Chính sách nổi bật (hiện ở thẻ dự án)', 'text', 'Chiết khấu đến 12%' ),
		'highlights' => array( 'Điểm nổi bật – mỗi dòng: Tiêu đề | Mô tả', 'textarea', "Vị trí | 300m tới ga Metro số 2\nPháp lý | Đã có QH 1/500" ),
		'faq'        => array( 'Câu hỏi thường gặp – mỗi dòng: Câu hỏi | Trả lời', 'textarea', 'Dự án ở đâu? | Lô 198 QL13…' ),
		'gallery'    => array( 'Thư viện ảnh (ID ảnh, cách nhau dấu phẩy)', 'gallery', '' ),
		'map'        => array( 'Google Maps (dán link nhúng hoặc cả thẻ iframe)', 'text', 'https://www.google.com/maps/embed?pb=…' ),
		'landing'    => array( 'Website riêng của dự án (nếu có)', 'url', 'https://thecollection688.online/' ),
		'hotline'    => array( 'Hotline riêng của dự án (để trống = hotline chung)', 'text', '' ),
		'lead_email' => array( 'Email nhận khách của dự án (để trống = email chung)', 'email', '' ),
		'featured'   => array( 'Dự án nổi bật (hiện ở trang chủ)', 'checkbox', '' ),
	);
}

/**
 * Đọc 1 ô thông tin dự án.
 *
 * @param string   $key Khoá.
 * @param int|null $id  ID dự án.
 * @return string
 */
function sgp_meta( $key, $id = null ) {
	return (string) get_post_meta( $id ? $id : get_the_ID(), '_sgp_' . $key, true );
}

/**
 * Các phân loại: slug => [tên số nhiều, tên số ít, công khai (có trang riêng)?, phân cấp?].
 *
 * @return array
 */
function sgp_taxonomies() {
	return array(
		'khu_vuc'    => array( 'Khu vực', 'Khu vực', true, true ),
		'chu_dau_tu' => array( 'Chủ đầu tư', 'Chủ đầu tư', true, false ),
		'loai_hinh'  => array( 'Loại hình', 'Loại hình', true, false ),
		'trang_thai' => array( 'Trạng thái', 'Trạng thái', false, false ),
		'khoang_gia' => array( 'Khoảng giá', 'Khoảng giá', false, false ),
	);
}

/**
 * Tham số lọc trên URL cho từng phân loại.
 *
 * @return array
 */
function sgp_filter_params() {
	return array(
		'khu_vuc'    => 'kv',
		'loai_hinh'  => 'lh',
		'chu_dau_tu' => 'cdt',
		'trang_thai' => 'tt',
		'khoang_gia' => 'gia',
	);
}

/**
 * Đăng ký mục Dự án + phân loại.
 */
function sgp_register_projects() {
	register_post_type(
		'du_an',
		array(
			'labels'        => array(
				'name'          => 'Dự án',
				'singular_name' => 'Dự án',
				'add_new'       => 'Thêm dự án',
				'add_new_item'  => 'Thêm dự án mới',
				'edit_item'     => 'Sửa dự án',
				'all_items'     => 'Tất cả dự án',
				'search_items'  => 'Tìm dự án',
				'not_found'     => 'Chưa có dự án',
			),
			'public'        => true,
			'has_archive'   => 'du-an',
			'rewrite'       => array( 'slug' => 'du-an', 'with_front' => false ),
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	foreach ( sgp_taxonomies() as $tax => $t ) {
		register_taxonomy(
			$tax,
			'du_an',
			array(
				'labels'             => array(
					'name'          => $t[0],
					'singular_name' => $t[1],
					'add_new_item'  => 'Thêm ' . mb_strtolower( $t[1] ),
					'edit_item'     => 'Sửa ' . mb_strtolower( $t[1] ),
				),
				'public'             => $t[2],
				'publicly_queryable' => $t[2],
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_rest'       => true,
				'hierarchical'       => $t[3] || ! $t[2],
				'rewrite'            => $t[2] ? array( 'slug' => str_replace( '_', '-', $tax ), 'with_front' => false, 'hierarchical' => $t[3] ) : false,
				'query_var'          => $t[2] ? $tax : false,
				'meta_box_cb'        => $t[2] ? null : 'post_categories_meta_box',
			)
		);
	}
}
add_action( 'init', 'sgp_register_projects' );

/**
 * Tự làm mới đường dẫn sau khi kích hoạt theme.
 */
function sgp_flush_on_switch() {
	sgp_register_projects();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'sgp_flush_on_switch' );

/**
 * Hộp nhập thông tin dự án.
 */
function sgp_project_meta_box() {
	add_meta_box( 'sgp_project', 'Thông tin dự án', 'sgp_project_meta_box_html', 'du_an', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'sgp_project_meta_box' );

/**
 * HTML hộp thông tin.
 *
 * @param WP_Post $post Bài.
 */
function sgp_project_meta_box_html( $post ) {
	wp_nonce_field( 'sgp_project', 'sgp_project_nonce' );
	echo '<p style="color:#646970">Nội dung chi tiết viết ở trình soạn thảo phía trên, chia mục bằng <strong>Tiêu đề 2 (H2)</strong>: Tổng quan, Vị trí, Tiện ích, Mặt bằng, Giá & chính sách… Mục lục tạo tự động.</p>';
	echo '<table class="form-table" role="presentation">';
	foreach ( sgp_project_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, '_sgp_' . $key, true );
		$id  = 'sgp-' . $key;
		echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		switch ( $f[1] ) {
			case 'textarea':
				printf( '<textarea id="%1$s" name="sgp[%2$s]" rows="5" class="large-text" placeholder="%4$s">%3$s</textarea>', esc_attr( $id ), esc_attr( $key ), esc_textarea( $val ), esc_attr( $f[2] ) );
				break;
			case 'checkbox':
				printf( '<label><input type="checkbox" id="%1$s" name="sgp[%2$s]" value="1" %3$s> Có</label>', esc_attr( $id ), esc_attr( $key ), checked( $val, '1', false ) );
				break;
			case 'gallery':
				printf( '<input type="text" id="%1$s" name="sgp[%2$s]" value="%3$s" class="regular-text"> <button type="button" class="button sgp-pick" data-target="%1$s" data-multiple="1">Chọn ảnh</button>', esc_attr( $id ), esc_attr( $key ), esc_attr( $val ) );
				break;
			default:
				printf( '<input type="%5$s" id="%1$s" name="sgp[%2$s]" value="%3$s" class="large-text" placeholder="%4$s">', esc_attr( $id ), esc_attr( $key ), esc_attr( $val ), esc_attr( $f[2] ), 'url' === $f[1] ? 'url' : ( 'email' === $f[1] ? 'email' : 'text' ) );
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

/**
 * Lưu thông tin dự án.
 *
 * @param int $post_id ID.
 */
function sgp_save_project( $post_id ) {
	if ( ! isset( $_POST['sgp_project_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sgp_project_nonce'] ) ), 'sgp_project' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['sgp'] ) && is_array( $_POST['sgp'] ) ? wp_unslash( $_POST['sgp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng ô bên dưới.
	foreach ( sgp_project_fields() as $key => $f ) {
		$v = isset( $in[ $key ] ) ? $in[ $key ] : '';
		switch ( $f[1] ) {
			case 'textarea':
				$v = sanitize_textarea_field( $v );
				break;
			case 'checkbox':
				$v = $v ? '1' : '';
				break;
			case 'gallery':
				$v = implode( ',', array_filter( array_map( 'absint', explode( ',', (string) $v ) ) ) );
				break;
			case 'url':
				$v = esc_url_raw( $v );
				break;
			case 'email':
				$v = sanitize_email( $v );
				break;
			default:
				$v = 'map' === $key ? sgp_sanitize_map( $v ) : sanitize_text_field( $v );
		}
		if ( '' === $v ) {
			delete_post_meta( $post_id, '_sgp_' . $key );
		} else {
			update_post_meta( $post_id, '_sgp_' . $key, $v );
		}
	}
}
add_action( 'save_post_du_an', 'sgp_save_project' );

/**
 * Chỉ nhận link nhúng Google Maps (hoặc thẻ iframe – lấy src).
 *
 * @param string $value Giá trị.
 * @return string
 */
function sgp_sanitize_map( $value ) {
	if ( preg_match( '/src=["\']([^"\']+)["\']/i', (string) $value, $m ) ) {
		$value = $m[1];
	}
	$value = esc_url_raw( trim( (string) $value ) );
	$host  = wp_parse_url( $value, PHP_URL_HOST );
	return ( $host && preg_match( '/(^|\.)google\.[a-z.]+$/i', $host ) ) ? $value : '';
}

/**
 * Nút "Chọn ảnh" dùng thư viện Media.
 *
 * @param string $hook Trang admin.
 */
function sgp_admin_media( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->base, array( 'post', 'term', 'edit-tags' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'media-editor',
		"jQuery(function($){\$(document).on('click','.sgp-pick',function(e){e.preventDefault();var b=\$(this),t=\$('#'+b.data('target')),multi=!!b.data('multiple');var f=wp.media({title:'Chọn ảnh',multiple:multi,library:{type:'image'}});f.on('select',function(){var ids=f.state().get('selection').map(function(a){return a.id;});t.val(ids.join(','));});f.open();});});"
	);
}
add_action( 'admin_enqueue_scripts', 'sgp_admin_media' );

/**
 * Ảnh đại diện + nội dung SEO cuối trang cho Khu vực / Chủ đầu tư / Loại hình.
 */
function sgp_term_fields_init() {
	foreach ( array( 'khu_vuc', 'chu_dau_tu', 'loai_hinh' ) as $tax ) {
		add_action( $tax . '_edit_form_fields', 'sgp_term_fields_html' );
		add_action( 'edited_' . $tax, 'sgp_term_fields_save' );
	}
}
add_action( 'init', 'sgp_term_fields_init' );

/**
 * HTML ô của phân loại.
 *
 * @param WP_Term $term Mục.
 */
function sgp_term_fields_html( $term ) {
	wp_nonce_field( 'sgp_term', 'sgp_term_nonce' );
	$img    = get_term_meta( $term->term_id, '_sgp_image', true );
	$bottom = get_term_meta( $term->term_id, '_sgp_bottom', true );
	$h1     = get_term_meta( $term->term_id, '_sgp_h1', true );
	?>
	<tr class="form-field"><th><label for="sgp-term-h1">Tiêu đề H1 của trang</label></th>
		<td><input type="text" id="sgp-term-h1" name="sgp_term[h1]" value="<?php echo esc_attr( $h1 ); ?>" placeholder="VD: Dự án căn hộ Bình Dương 2026">
			<p class="description">Để trống = dùng tên mục. Mô tả (ô phía trên) hiện ở đầu trang – nên viết 150–300 chữ.</p></td></tr>
	<tr class="form-field"><th><label for="sgp-term-image">Ảnh / logo (ID)</label></th>
		<td><input type="text" id="sgp-term-image" name="sgp_term[image]" value="<?php echo esc_attr( $img ); ?>" style="width:120px"> <button type="button" class="button sgp-pick" data-target="sgp-term-image">Chọn ảnh</button></td></tr>
	<tr class="form-field"><th><label for="sgp-term-bottom">Nội dung SEO cuối trang</label></th>
		<td><?php wp_editor( $bottom, 'sgp-term-bottom', array( 'textarea_name' => 'sgp_term[bottom]', 'textarea_rows' => 8, 'media_buttons' => false ) ); ?>
			<p class="description">Phân tích thị trường, hạ tầng, giá khu vực… (300–800 chữ, có H2). Hiện bên dưới danh sách dự án.</p></td></tr>
	<?php
}

/**
 * Lưu ô của phân loại.
 *
 * @param int $term_id ID.
 */
function sgp_term_fields_save( $term_id ) {
	if ( ! isset( $_POST['sgp_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sgp_term_nonce'] ) ), 'sgp_term' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$in = isset( $_POST['sgp_term'] ) && is_array( $_POST['sgp_term'] ) ? wp_unslash( $_POST['sgp_term'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch bên dưới.
	update_term_meta( $term_id, '_sgp_image', absint( isset( $in['image'] ) ? $in['image'] : 0 ) );
	update_term_meta( $term_id, '_sgp_h1', sanitize_text_field( isset( $in['h1'] ) ? $in['h1'] : '' ) );
	update_term_meta( $term_id, '_sgp_bottom', wp_kses_post( isset( $in['bottom'] ) ? $in['bottom'] : '' ) );
}

/**
 * Giá trị lọc đang chọn trên URL.
 *
 * @return array tax => slug
 */
function sgp_active_filters() {
	$out = array();
	foreach ( sgp_filter_params() as $tax => $param ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- bộ lọc công khai, chỉ đọc.
		if ( ! empty( $_GET[ $param ] ) ) {
			$out[ $tax ] = sanitize_title( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}
	return $out;
}

/**
 * Đang ở trang danh sách dự án (lưu trữ / khu vực / chủ đầu tư / loại hình)?
 *
 * @return bool
 */
function sgp_is_listing() {
	return is_post_type_archive( 'du_an' ) || is_tax( array( 'khu_vuc', 'chu_dau_tu', 'loai_hinh' ) );
}

/**
 * Áp bộ lọc + sắp xếp cho trang danh sách.
 *
 * @param WP_Query $q Truy vấn.
 */
function sgp_listing_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( ! ( $q->is_post_type_archive( 'du_an' ) || $q->is_tax( array( 'khu_vuc', 'chu_dau_tu', 'loai_hinh' ) ) ) ) {
		return;
	}
	$q->set( 'post_type', 'du_an' );
	$q->set( 'posts_per_page', 12 );
	$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	$tax_query = array();
	foreach ( sgp_active_filters() as $tax => $slug ) {
		$tax_query[] = array(
			'taxonomy' => $tax,
			'field'    => 'slug',
			'terms'    => $slug,
		);
	}
	if ( $tax_query ) {
		$existing = (array) $q->get( 'tax_query' );
		$q->set( 'tax_query', array_merge( array( 'relation' => 'AND' ), array_filter( $existing ), $tax_query ) );
	}
}
add_action( 'pre_get_posts', 'sgp_listing_query' );

/**
 * Tên mục đầu tiên của dự án theo phân loại.
 *
 * @param string   $tax Phân loại.
 * @param int|null $id  ID dự án.
 * @return WP_Term|null
 */
function sgp_first_term( $tax, $id = null ) {
	$terms = get_the_terms( $id ? $id : get_the_ID(), $tax );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * Hotline của dự án (riêng hoặc chung).
 *
 * @param int|null $id ID dự án.
 * @return string
 */
function sgp_project_hotline( $id = null ) {
	$h = $id ? sgp_meta( 'hotline', $id ) : '';
	return $h ? $h : sgp_opt( 'hotline' );
}

/**
 * Dự án liên quan: cùng khu vực, sau đó cùng loại hình.
 *
 * @param int $id    ID.
 * @param int $count Số lượng.
 * @return WP_Post[]
 */
function sgp_related_projects( $id, $count = 3 ) {
	$found = array();
	foreach ( array( 'khu_vuc', 'loai_hinh', '' ) as $tax ) {
		if ( count( $found ) >= $count ) {
			break;
		}
		$args = array(
			'post_type'      => 'du_an',
			'posts_per_page' => $count - count( $found ),
			'post__not_in'   => array_merge( array( $id ), wp_list_pluck( $found, 'ID' ) ),
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'no_found_rows'  => true,
		);
		if ( $tax ) {
			$ids = wp_get_post_terms( $id, $tax, array( 'fields' => 'ids' ) );
			if ( ! $ids || is_wp_error( $ids ) ) {
				continue;
			}
			$args['tax_query'] = array( array( 'taxonomy' => $tax, 'terms' => $ids ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		$found = array_merge( $found, get_posts( $args ) );
	}
	return $found;
}

/**
 * Thêm id cho các H2 trong nội dung dự án + chèn mục lục khi có từ 3 mục.
 *
 * @param string $content Nội dung.
 * @return string
 */
function sgp_project_toc( $content ) {
	if ( ! is_singular( 'du_an' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$items = array();
	$used  = array();
	$content = preg_replace_callback(
		'/<h2([^>]*)>(.*?)<\/h2>/is',
		function ( $m ) use ( &$items, &$used ) {
			$text = trim( wp_strip_all_tags( $m[2] ) );
			if ( preg_match( '/\bid=["\']([^"\']+)["\']/', $m[1], $idm ) ) {
				$id = $idm[1];
				$attrs = $m[1];
			} else {
				$id = sanitize_title( remove_accents( $text ) );
				$id = $id ? $id : 'muc';
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
		$content
	);
	if ( count( $items ) < 3 ) {
		return $content;
	}
	$toc = '<nav class="sgp-toc" aria-label="Mục lục"><p class="sgp-toc__title">Nội dung chính</p><ol>';
	foreach ( $items as $it ) {
		$toc .= '<li><a href="#' . esc_attr( $it[0] ) . '">' . esc_html( $it[1] ) . '</a></li>';
	}
	return $toc . '</ol></nav>' . $content;
}
add_filter( 'the_content', 'sgp_project_toc', 20 );
