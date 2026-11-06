<?php
/**
 * Trang AI nội dung: đề xuất chủ đề, phân tích từ khoá, cụm chủ đề, tiêu đề, dàn ý, soạn bài,
 * meta, FAQ, liên kết nội bộ, kiểm tra trùng lặp & chất lượng, tạo bản nháp, duyệt & lên lịch, theo dõi.
 * Quy tắc: AI chỉ tạo BẢN NHÁP. Bài do AI soạn không thể xuất bản khi chưa được duyệt.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Cài đặt AI (tab trong Cài đặt).
 * ------------------------------------------------------------------------- */

/**
 * Form cài đặt AI.
 */
function th119_ai_settings_form() {
	$s    = th119_ai_settings();
	$p    = th119_ai_providers();
	$cost = th119_ai_month_cost();
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'th119_ai_settings' );
	echo '<input type="hidden" name="action" value="th119_ai_settings">';
	echo '<p class="description">AI chỉ soạn <strong>bản nháp</strong>. Bài AI soạn phải được biên tập viên/quản trị viên duyệt mới xuất bản được. Khoá API được mã hoá trong database; an toàn hơn nữa: thêm <code>define( \'TH119_AI_KEY\', \'...\' );</code> vào <code>wp-config.php</code> (không nằm trong mã nguồn Git).</p>';
	echo '<table class="form-table">';
	printf( '<tr><th>Bật AI</th><td><input type="hidden" name="ai[enabled]" value="0"><label><input type="checkbox" name="ai[enabled]" value="1" %s> Bật</label></td></tr>', checked( (int) $s['enabled'], 1, false ) );
	echo '<tr><th>Nhà cung cấp</th><td><select name="ai[provider]">';
	foreach ( $p as $k => $row ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $s['provider'], $k, false ) . '>' . esc_html( $row[0] ) . '</option>';
	}
	echo '</select><p class="description">Đổi nhà cung cấp bất cứ lúc nào; lịch sử và bản nháp giữ nguyên.</p></td></tr>';
	printf( '<tr><th>Model</th><td><input type="text" name="ai[model]" value="%s" class="regular-text"><p class="description">Trống = mặc định của nhà cung cấp (Anthropic: claude-opus-5-5).</p></td></tr>', esc_attr( $s['model'] ) );
	printf( '<tr><th>Base URL (tương thích OpenAI)</th><td><input type="url" name="ai[base_url]" value="%s" class="regular-text" placeholder="https://openrouter.ai/api/v1"></td></tr>', esc_attr( $s['base_url'] ) );
	if ( defined( 'TH119_AI_KEY' ) && TH119_AI_KEY ) {
		echo '<tr><th>API key</th><td><strong>Đang dùng khoá trong wp-config.php (TH119_AI_KEY).</strong></td></tr>';
	} else {
		printf( '<tr><th>API key</th><td><input type="password" name="ai[api_key]" value="" class="regular-text" autocomplete="new-password" placeholder="%s"><p class="description">Để trống = giữ khoá đã lưu. Nhập dấu - để xoá.</p></td></tr>', esc_attr( th119_mask_secret( th119_decrypt( $s['api_key'] ) ) ) );
	}
	printf( '<tr><th>Mức suy nghĩ (Claude)</th><td><select name="ai[effort]"><option value="low" %1$s>Thấp – nhanh, rẻ</option><option value="medium" %2$s>Vừa (khuyên dùng)</option><option value="high" %3$s>Cao – bài kỹ thuật khó</option></select></td></tr>', selected( $s['effort'], 'low', false ), selected( $s['effort'], 'medium', false ), selected( $s['effort'], 'high', false ) );
	printf( '<tr><th>Giá ước tính (USD / 1 triệu token)</th><td>Vào <input type="number" step="0.001" min="0" name="ai[price_in]" value="%s" class="small-text"> · Ra <input type="number" step="0.001" min="0" name="ai[price_out]" value="%s" class="small-text"><p class="description">Dùng để tính chi phí và chặn khi vượt ngân sách. Cập nhật theo bảng giá của nhà cung cấp.</p></td></tr>', esc_attr( $s['price_in'] ), esc_attr( $s['price_out'] ) );
	printf( '<tr><th>Ngân sách mỗi tháng (USD)</th><td><input type="number" step="0.5" min="0" name="ai[budget_month]" value="%s" class="small-text"> Đã dùng tháng này: <strong>%s USD</strong></td></tr>', esc_attr( $s['budget_month'] ), esc_html( number_format( $cost, 3 ) ) );
	printf( '<tr><th>Số lần gọi tối đa / ngày</th><td><input type="number" min="1" name="ai[daily_limit]" value="%s" class="small-text"></td></tr>', esc_attr( $s['daily_limit'] ) );
	printf( '<tr><th>Token tối đa mỗi lần</th><td><input type="number" min="500" max="32000" name="ai[max_tokens]" value="%s" class="small-text"></td></tr>', esc_attr( $s['max_tokens'] ) );
	printf( '<tr><th>Giọng văn thương hiệu</th><td><textarea name="ai[brand_voice]" rows="3" class="large-text">%s</textarea></td></tr>', esc_textarea( $s['brand_voice'] ) );
	echo '</table>';
	submit_button( 'Lưu cài đặt AI' );
	echo '</form>';
	if ( th119_ai_ready() ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'th119_ai_test' );
		echo '<input type="hidden" name="action" value="th119_ai_test">';
		submit_button( 'Kiểm tra kết nối AI', 'secondary', 'submit', false );
		echo '</form>';
	}
	if ( isset( $_GET['ai_test'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-info"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['ai_test'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}

/**
 * Lưu cài đặt AI.
 */
function th119_ai_save_settings() {
	check_admin_referer( 'th119_ai_settings' );
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$in  = isset( $_POST['ai'] ) && is_array( $_POST['ai'] ) ? wp_unslash( $_POST['ai'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng ô.
	$old = (array) get_option( 'th119_ai', array() );
	$new = array(
		'enabled'      => empty( $in['enabled'] ) ? 0 : 1,
		'provider'     => isset( th119_ai_providers()[ $in['provider'] ?? '' ] ) ? $in['provider'] : 'anthropic',
		'model'        => sanitize_text_field( $in['model'] ?? '' ),
		'base_url'     => esc_url_raw( $in['base_url'] ?? '' ),
		'api_key'      => $old['api_key'] ?? '',
		'price_in'     => '' === ( $in['price_in'] ?? '' ) ? '' : (float) $in['price_in'],
		'price_out'    => '' === ( $in['price_out'] ?? '' ) ? '' : (float) $in['price_out'],
		'budget_month' => max( 0, (float) ( $in['budget_month'] ?? 10 ) ),
		'daily_limit'  => max( 1, absint( $in['daily_limit'] ?? 50 ) ),
		'max_tokens'   => min( 32000, max( 500, absint( $in['max_tokens'] ?? 8000 ) ) ),
		'effort'       => in_array( $in['effort'] ?? '', array( 'low', 'medium', 'high' ), true ) ? $in['effort'] : 'medium',
		'brand_voice'  => sanitize_textarea_field( $in['brand_voice'] ?? '' ),
	);
	// Đổi nhà cung cấp → model/giá về mặc định của nhà cung cấp mới nếu người dùng không tự nhập.
	if ( ( $old['provider'] ?? 'anthropic' ) !== $new['provider'] && ( $old['model'] ?? '' ) === $new['model'] ) {
		$new['model']     = '';
		$new['price_in']  = '';
		$new['price_out'] = '';
	}
	$key = isset( $in['api_key'] ) ? trim( (string) $in['api_key'] ) : '';
	if ( '-' === $key ) {
		$new['api_key'] = '';
	} elseif ( '' !== $key ) {
		$new['api_key'] = th119_encrypt( sanitize_text_field( $key ) );
	}
	update_option( 'th119_ai', $new, false );
	th119_log_activity( 'ai_settings', $new['provider'] . ' / ' . ( $new['model'] ? $new['model'] : 'mặc định' ), '' !== $key ? 'Đổi API key' : '' );
	wp_safe_redirect( admin_url( 'admin.php?page=th119-settings&tab=ai&saved=1' ) );
	exit;
}
add_action( 'admin_post_th119_ai_settings', 'th119_ai_save_settings' );

/**
 * Kiểm tra kết nối.
 */
function th119_ai_test() {
	check_admin_referer( 'th119_ai_test' );
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$r   = th119_ai_request( 'test', 'Trả lời đúng một từ.', 'Trả lời: OK', array( 'max_tokens' => 500 ) );
	$msg = is_wp_error( $r ) ? 'Lỗi: ' . $r->get_error_message() : 'Kết nối thành công. AI trả lời: ' . wp_trim_words( $r['text'], 10 );
	wp_safe_redirect( admin_url( 'admin.php?page=th119-settings&tab=ai&ai_test=' . rawurlencode( $msg ) ) );
	exit;
}
add_action( 'admin_post_th119_ai_test', 'th119_ai_test' );

/* ---------------------------------------------------------------------------
 * Tác vụ AI.
 * ------------------------------------------------------------------------- */

/**
 * Chỉ dẫn chung cho mọi tác vụ.
 *
 * @return string
 */
function th119_ai_base_rules() {
	return 'Bạn là biên tập viên kỹ thuật của Tin Học 119 – dịch vụ sửa chữa máy tính, laptop và IT doanh nghiệp tại ' . th119_opt( 'district' ) . ', ' . th119_opt( 'city' ) . '. Viết tiếng Việt có dấu, chuẩn chính tả.
Quy tắc bắt buộc:
- Không bịa số liệu, kết quả kiểm tra, thông số kỹ thuật, giá, đánh giá khách hàng hay trích dẫn. Chỗ nào cần số liệu/thông số mà bạn không chắc chắn, ghi [CẦN KIỂM TRA: ...] để kỹ thuật viên điền.
- Không sao chép nội dung website khác; viết bằng lời của bạn dựa trên kiến thức chung đã được kiểm chứng.
- Không nhồi nhét từ khoá; từ khoá xuất hiện tự nhiên.
- Hướng dẫn phải an toàn: nhắc sao lưu dữ liệu, rút nguồn khi thao tác phần cứng, nêu rõ trường hợp không nên tự sửa.
- Không hứa hẹn kết quả tuyệt đối, không chê đối thủ.';
}

/**
 * Bài hiện có (để gợi ý liên kết nội bộ và tránh trùng chủ đề).
 *
 * @param int $limit Số bài.
 * @return array [ [title, url], … ]
 */
function th119_ai_existing_posts( $limit = 120 ) {
	$out = array();
	foreach ( get_posts( array( 'numberposts' => $limit, 'post_status' => array( 'publish' ), 'post_type' => array( 'post', 'dich_vu' ) ) ) as $p ) {
		$out[] = array( $p->post_title, get_permalink( $p ) );
	}
	return $out;
}

/**
 * Chạy 1 tác vụ AI.
 *
 * @param string $task Tác vụ.
 * @param array  $in   Dữ liệu nhập.
 * @return array|WP_Error
 */
function th119_ai_run_task( $task, $in ) {
	$topic   = sanitize_text_field( $in['topic'] ?? '' );
	$keyword = sanitize_text_field( $in['keyword'] ?? '' );
	$cat     = absint( $in['cat'] ?? 0 );
	$notes   = sanitize_textarea_field( $in['notes'] ?? '' );
	$outline = sanitize_textarea_field( $in['outline'] ?? '' );
	$content = wp_kses_post( $in['content'] ?? '' );
	$catname = $cat ? get_cat_name( $cat ) : '';
	$sys     = th119_ai_base_rules();
	$titles  = implode( "\n", array_map( function ( $p ) { return '- ' . $p[0]; }, th119_ai_existing_posts( 80 ) ) ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment

	switch ( $task ) {
		case 'topics':
			$prompt = "Đề xuất 15 chủ đề bài hướng dẫn mới cho chuyên mục \"$catname\" (chủ đề gợi ý: \"$topic\"). Ưu tiên lỗi/sự cố người dùng hay tìm trên Google, có ý định tìm kiếm rõ, phù hợp khách hàng tại TP.HCM. Không trùng các bài đã có:\n$titles\n\nTrả về JSON: [{\"title\":\"...\",\"keyword\":\"từ khoá chính\",\"intent\":\"thông tin|giao dịch|địa phương\",\"why\":\"lý do ngắn\"}]";
			break;
		case 'keywords':
			$prompt = "Phân tích từ khoá \"$keyword\" cho website dịch vụ IT/sửa máy tính. Bạn KHÔNG có dữ liệu lượt tìm kiếm thực tế, không được đoán số lượt tìm kiếm. Hãy phân tích: ý định tìm kiếm, nhóm người tìm, 10–15 từ khoá liên quan/biến thể dài, câu hỏi người dùng hay hỏi, loại nội dung nên viết (hướng dẫn/dịch vụ/so sánh), có nên gắn yếu tố địa phương (Tân Phú, TP.HCM) không.\nTrả về JSON: {\"intent\":\"...\",\"audience\":\"...\",\"related\":[\"...\"],\"questions\":[\"...\"],\"content_type\":\"...\",\"local\":\"...\"}";
			break;
		case 'cluster':
			$prompt = "Xây dựng cụm chủ đề (topic cluster) quanh chủ đề trụ cột \"$topic\". Gồm 1 bài trụ cột và 8–12 bài vệ tinh, mỗi bài có từ khoá chính và liên kết nội bộ nên đặt (bài nào trỏ tới bài nào, bài nào trỏ tới trang dịch vụ). Bài đã có trên website:\n$titles\n\nTrả về JSON: {\"pillar\":{\"title\":\"...\",\"keyword\":\"...\"},\"articles\":[{\"title\":\"...\",\"keyword\":\"...\",\"exists\":false,\"links_to\":[\"...\"]}]}";
			break;
		case 'titles':
			$prompt = "Đề xuất 8 tiêu đề bài viết cho từ khoá \"$keyword\" (chủ đề: \"$topic\"). Tiêu đề 50–65 ký tự, rõ lợi ích, không giật tít, không dùng chữ in hoa toàn bộ, có từ khoá tự nhiên. Trả về JSON: [\"...\"]";
			break;
		case 'outline':
			$prompt = "Lập dàn ý bài hướng dẫn \"$topic\" (từ khoá chính: \"$keyword\", chuyên mục: $catname). Ghi chú của kỹ thuật viên: $notes\nDàn ý PHẢI theo đúng thứ tự các mục H2: Mô tả vấn đề; Dấu hiệu nhận biết; Nguyên nhân thường gặp; Cách kiểm tra; Hướng dẫn xử lý từng bước; Lưu ý an toàn; Trường hợp không nên tự sửa; Khi nào cần kỹ thuật viên. Dưới mỗi H2 ghi 2–5 ý chính (H3 nếu cần) và gợi ý ảnh minh hoạ cần chụp. Trả về văn bản thuần, mỗi dòng 1 ý, H2 bắt đầu bằng '## ', H3 bằng '### '.";
			break;
		case 'draft':
			$prompt = "Viết bài hướng dẫn hoàn chỉnh \"$topic\" (từ khoá chính: \"$keyword\", chuyên mục: $catname), 1.200–2.000 chữ, theo dàn ý:\n$outline\n\nGhi chú của kỹ thuật viên (ưu tiên làm theo): $notes\n\nYêu cầu định dạng: chỉ trả về HTML phần thân bài (không có <html>, <h1>). Dùng <h2>, <h3>, <p>, <ul>, <ol>, <table> khi cần. Đoạn ngắn 2–4 câu. Mở bài 2–3 câu nêu ngay vấn đề và cách xử lý nhanh nhất.\n- Lệnh kỹ thuật đặt trong [th_lenh]lệnh[/th_lenh].\n- Cảnh báo quan trọng đặt trong [th_canh_bao]...[/th_canh_bao]; mẹo/lưu ý trong [th_luu_y]...[/th_luu_y].\n- Ở mục 'Khi nào cần kỹ thuật viên' kết thúc bằng [th_ky_thuat_vien].\n- Vị trí cần ảnh minh hoạ: <p class=\"th-img-placeholder\">[ẢNH: mô tả cụ thể ảnh cần chụp]</p>.\n- Không viết mục FAQ, bài liên quan, tác giả (hệ thống tự thêm).";
			return th119_ai_request( $task, $sys, $prompt, array( 'max_tokens' => max( 6000, (int) th119_ai_settings()['max_tokens'] ) ) );
		case 'meta':
			$prompt = "Viết meta cho bài \"$topic\" (từ khoá: \"$keyword\"). Nội dung tóm tắt: " . wp_trim_words( wp_strip_all_tags( $content ), 250 ) . "\nTrả về JSON: {\"title\":\"50–60 ký tự, có từ khoá\",\"description\":\"140–155 ký tự, nêu lợi ích, có lời kêu gọi nhẹ\",\"slug\":\"duong-dan-khong-dau-ngan-gon\",\"excerpt\":\"2 câu tóm tắt\"}";
			break;
		case 'faq':
			$prompt = "Đề xuất 4–6 câu hỏi thường gặp cho bài \"$topic\" mà thân bài CHƯA trả lời trực tiếp, mỗi câu trả lời 1–3 câu, chính xác, không bịa thông số. Thân bài: " . wp_trim_words( wp_strip_all_tags( $content ), 400 ) . "\nTrả về JSON: [{\"q\":\"...\",\"a\":\"...\"}]";
			break;
		case 'links':
			$list   = implode( "\n", array_map( function ( $p ) { return '- ' . $p[0] . ' | ' . $p[1]; }, th119_ai_existing_posts( 150 ) ) ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment
			$prompt = "Chọn 3–6 trang dưới đây phù hợp để liên kết nội bộ từ bài \"$topic\", kèm chữ neo tự nhiên và đoạn nên đặt link. CHỈ chọn trong danh sách (không tạo URL mới):\n$list\n\nThân bài: " . wp_trim_words( wp_strip_all_tags( $content ), 300 ) . "\nTrả về JSON: [{\"url\":\"...\",\"anchor\":\"...\",\"where\":\"...\"}]";
			$res    = th119_ai_request( $task, $sys, $prompt );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			// Loại link AI tự bịa (không có trong website).
			$valid = wp_list_pluck( th119_ai_existing_posts( 150 ), 1 );
			$data  = th119_ai_json( $res['text'] );
			if ( is_array( $data ) ) {
				$data        = array_values( array_filter( $data, function ( $l ) use ( $valid ) { return isset( $l['url'] ) && in_array( $l['url'], $valid, true ); } ) ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment
				$res['text'] = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			}
			return $res;
		default:
			return new WP_Error( 'th119_ai_task', 'Tác vụ không hợp lệ.' );
	}
	return th119_ai_request( $task, $sys, $prompt );
}

/**
 * AJAX chạy tác vụ.
 */
function th119_ajax_ai_run() {
	check_ajax_referer( 'th119_admin', 'nonce' );
	if ( ! current_user_can( 'th119_use_ai' ) ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	$task = isset( $_POST['task'] ) ? sanitize_key( $_POST['task'] ) : '';
	$in   = isset( $_POST['data'] ) && is_array( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch trong th119_ai_run_task.
	$res  = th119_ai_run_task( $task, $in );
	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'message' => $res->get_error_message() ) );
	}
	$text = 'draft' === $task ? wp_kses_post( $res['text'] ) : $res['text'];
	if ( 'draft' === $task ) {
		$text = preg_replace( '/^```(?:html)?\s*|```\s*$/m', '', $text );
	}
	wp_send_json_success(
		array(
			'text' => $text,
			'json' => th119_ai_json( $res['text'] ),
			'cost' => round( $res['cost'], 4 ),
			'log'  => isset( $res['log_id'] ) ? (int) $res['log_id'] : 0,
		)
	);
}
add_action( 'wp_ajax_th119_ai_run', 'th119_ajax_ai_run' );

/* ---------------------------------------------------------------------------
 * Kiểm tra trùng lặp & chất lượng (chạy trên máy chủ, không tốn phí AI).
 * ------------------------------------------------------------------------- */

/**
 * Tập "shingle" 5 từ của văn bản.
 *
 * @param string $text Văn bản.
 * @return array
 */
function th119_shingles( $text ) {
	$words = preg_split( '/\s+/u', mb_strtolower( trim( preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', wp_strip_all_tags( strip_shortcodes( $text ) ) ) ) ) );
	$words = array_values( array_filter( $words ) );
	$set   = array();
	for ( $i = 0; $i + 5 <= count( $words ); $i++ ) {
		$set[ md5( implode( ' ', array_slice( $words, $i, 5 ) ) ) ] = 1;
	}
	return $set;
}

/**
 * Bài trùng lặp nội dung nhiều nhất với văn bản.
 *
 * @param string $title   Tiêu đề.
 * @param string $content Nội dung.
 * @param int    $exclude ID loại trừ.
 * @return array [ [id, title, url, score%], … ]
 */
function th119_duplicate_check( $title, $content, $exclude = 0 ) {
	$mine = th119_shingles( $content );
	$out  = array();
	$tw   = array_unique( preg_split( '/\s+/u', mb_strtolower( $title ) ) );
	$q    = new WP_Query(
		array(
			'post_type'      => array( 'post', 'dich_vu', 'page' ),
			'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
			'posts_per_page' => 400,
			'post__not_in'   => $exclude ? array( $exclude ) : array(),
			'no_found_rows'  => true,
		)
	);
	foreach ( $q->posts as $p ) {
		$score = 0;
		if ( $mine ) {
			$other = th119_shingles( $p->post_content );
			if ( $other ) {
				$inter = count( array_intersect_key( $mine, $other ) );
				$score = $inter / max( 1, min( count( $mine ), count( $other ) ) );
			}
		}
		$pw     = array_unique( preg_split( '/\s+/u', mb_strtolower( $p->post_title ) ) );
		$tscore = count( array_intersect( $tw, $pw ) ) / max( 1, count( array_unique( array_merge( $tw, $pw ) ) ) );
		if ( $score >= 0.08 || $tscore >= 0.6 ) {
			$out[] = array( $p->ID, $p->post_title, get_permalink( $p ), round( $score * 100 ), round( $tscore * 100 ) );
		}
	}
	usort( $out, function ( $a, $b ) { return ( $b[3] + $b[4] ) - ( $a[3] + $a[4] ); } ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment
	return array_slice( $out, 0, 8 );
}

/**
 * Chấm chất lượng bài kiến thức theo tiêu chuẩn 15 phần.
 *
 * @param WP_Post $post Bài.
 * @return array [ score 0–100, [ [ok bool, message], … ] ].
 */
function th119_quality_check( $post ) {
	$c      = (string) $post->post_content;
	$plain  = wp_strip_all_tags( strip_shortcodes( $c ) );
	$words  = count( preg_split( '/\s+/u', trim( $plain ), -1, PREG_SPLIT_NO_EMPTY ) );
	$h2     = preg_match_all( '/<h2[\s>]/i', $c );
	$imgs   = preg_match_all( '/<img\s[^>]*>/i', $c, $im );
	$noalt  = 0;
	foreach ( $im[0] as $tag ) {
		if ( ! preg_match( '/alt="[^"]{3,}"/i', $tag ) ) {
			$noalt++;
		}
	}
	$host   = wp_parse_url( home_url(), PHP_URL_HOST );
	$links  = preg_match_all( '#href="(https?://' . preg_quote( $host, '#' ) . '|/)[^"]*"#i', $c );
	$need   = array( 'dấu hiệu', 'nguyên nhân', 'kiểm tra', 'xử lý', 'lưu ý', 'không nên tự', 'kỹ thuật viên' );
	$lower  = mb_strtolower( $c );
	$miss   = array();
	foreach ( $need as $n ) {
		if ( false === mb_strpos( $lower, $n ) ) {
			$miss[] = $n;
		}
	}
	$seo_t  = (string) get_post_meta( $post->ID, '_th_seo_title', true );
	$seo_d  = (string) get_post_meta( $post->ID, '_th_seo_desc', true );
	$faq    = th119_get_faq( $post->ID );
	$cats   = wp_get_post_categories( $post->ID );
	$checks = array(
		array( $words >= 700, sprintf( 'Độ dài %d chữ (nên ≥ 700 với bài hướng dẫn).', $words ) ),
		array( $h2 >= 5, sprintf( '%d tiêu đề H2 (nên ≥ 5 để có mục lục rõ ràng).', $h2 ) ),
		array( ! $miss, $miss ? 'Thiếu mục chuẩn: ' . implode( ', ', $miss ) . '.' : 'Đủ các mục chuẩn (dấu hiệu, nguyên nhân, kiểm tra, xử lý, lưu ý, không nên tự sửa, kỹ thuật viên).' ),
		array( $imgs >= 1, sprintf( '%d ảnh minh hoạ trong bài.', $imgs ) ),
		array( 0 === $noalt, $noalt ? "$noalt ảnh chưa có mô tả (alt)." : 'Ảnh đều có mô tả alt.' ),
		array( false === strpos( $c, '[ẢNH:' ) && false === strpos( $c, '[CẦN KIỂM TRA' ), 'Không còn chỗ đánh dấu [ẢNH: …] / [CẦN KIỂM TRA …] chưa xử lý.' ),
		array( $links >= 2, sprintf( '%d liên kết nội bộ (nên ≥ 2).', $links ) ),
		array( has_post_thumbnail( $post ), 'Ảnh đại diện.' ),
		array( count( $faq ) >= 2, sprintf( '%d câu hỏi thường gặp.', count( $faq ) ) ),
		array( $cats && ! in_array( (int) get_option( 'default_category' ), array_map( 'intval', $cats ), true ), 'Đã chọn chuyên mục kiến thức.' ),
		array( mb_strlen( $post->post_title ) >= 30 && mb_strlen( $post->post_title ) <= 70, sprintf( 'Tiêu đề %d ký tự (nên 30–70).', mb_strlen( $post->post_title ) ) ),
		array( '' === $seo_d || ( mb_strlen( $seo_d ) >= 110 && mb_strlen( $seo_d ) <= 160 ), $seo_d ? sprintf( 'Mô tả SEO %d ký tự (nên 110–160).', mb_strlen( $seo_d ) ) : 'Chưa có mô tả SEO riêng (sẽ tự lấy tóm tắt).' ),
		array( '' === $seo_t || mb_strlen( $seo_t ) <= 65, $seo_t ? sprintf( 'Tiêu đề SEO %d ký tự.', mb_strlen( $seo_t ) ) : 'Tiêu đề SEO: dùng tiêu đề bài.' ),
		array( (bool) get_post_meta( $post->ID, '_th_reviewer', true ), 'Có người kiểm duyệt kỹ thuật.' ),
	);
	$ok = count( array_filter( wp_list_pluck( $checks, 0 ) ) );
	return array( (int) round( $ok / count( $checks ) * 100 ), $checks );
}

/**
 * AJAX kiểm tra trùng lặp + chất lượng cho 1 bài.
 */
function th119_ajax_check_post() {
	check_ajax_referer( 'th119_admin', 'nonce' );
	$id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$post = get_post( $id );
	if ( ! $post || ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	list( $score, $checks ) = th119_quality_check( $post );
	$html  = '<p><strong>Điểm chất lượng: ' . (int) $score . '/100</strong></p><ul class="th119-checks">';
	foreach ( $checks as $c ) {
		$html .= '<li class="' . ( $c[0] ? 'ok' : 'bad' ) . '">' . ( $c[0] ? '✔ ' : '✘ ' ) . esc_html( $c[1] ) . '</li>';
	}
	$html .= '</ul><p><strong>Kiểm tra trùng lặp với nội dung đã có trên website</strong></p>';
	$dups  = th119_duplicate_check( $post->post_title, $post->post_content, $id );
	if ( ! $dups ) {
		$html .= '<p>Không thấy bài trùng lặp đáng kể.</p>';
	} else {
		$html .= '<ul>';
		foreach ( $dups as $d ) {
			$html .= sprintf( '<li><a href="%s" target="_blank">%s</a> – trùng nội dung %d%%, tiêu đề giống %d%%</li>', esc_url( $d[2] ), esc_html( $d[1] ), (int) $d[3], (int) $d[4] );
		}
		$html .= '</ul>';
	}
	update_post_meta( $id, '_th_quality', $score );
	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_th119_check_post', 'th119_ajax_check_post' );

/**
 * Hộp "Kiểm tra chất lượng" ở trang soạn bài.
 */
function th119_quality_meta_box() {
	add_meta_box( 'th119_quality', 'Kiểm tra chất lượng & trùng lặp', 'th119_quality_meta_box_html', 'post', 'side' );
}
add_action( 'add_meta_boxes', 'th119_quality_meta_box' );

/**
 * HTML hộp kiểm tra.
 *
 * @param WP_Post $post Bài.
 */
function th119_quality_meta_box_html( $post ) {
	echo '<p class="description">Lưu nháp trước, rồi bấm kiểm tra.</p><button type="button" class="button th119-check-post" data-id="' . (int) $post->ID . '">Kiểm tra bài</button><div class="th119-check-result"></div>';
	if ( get_post_meta( $post->ID, '_th_ai_generated', true ) ) {
		$approved = get_post_meta( $post->ID, '_th_ai_approved', true );
		echo '<hr><p><strong>Bài AI soạn:</strong> ' . ( $approved ? 'đã duyệt ✔' : 'chưa duyệt – chưa xuất bản được' ) . '</p>';
		if ( ! $approved && current_user_can( 'th119_approve_ai' ) ) {
			printf( '<a class="button button-primary" href="%s">Duyệt bài này</a>', esc_url( admin_url( 'admin.php?page=th119-ai&tab=review' ) ) );
		}
	}
}

/* ---------------------------------------------------------------------------
 * Tạo bản nháp, duyệt, lên lịch.
 * ------------------------------------------------------------------------- */

/**
 * AJAX tạo bản nháp WordPress từ kết quả AI.
 */
function th119_ajax_ai_create_draft() {
	check_ajax_referer( 'th119_admin', 'nonce' );
	if ( ! current_user_can( 'th119_use_ai' ) || ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	$d       = isset( $_POST['data'] ) && is_array( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch bên dưới.
	$title   = sanitize_text_field( $d['title'] ?? '' );
	$content = wp_kses_post( $d['content'] ?? '' );
	if ( '' === $title || mb_strlen( wp_strip_all_tags( $content ) ) < 200 ) {
		wp_send_json_error( array( 'message' => 'Cần có tiêu đề và nội dung bài trước khi tạo bản nháp.' ) );
	}
	$dups = th119_duplicate_check( $title, $content );
	$id   = wp_insert_post(
		array(
			'post_title'    => $title,
			'post_content'  => $content,
			'post_excerpt'  => sanitize_textarea_field( $d['excerpt'] ?? '' ),
			'post_name'     => sanitize_title( $d['slug'] ?? '' ),
			'post_status'   => 'draft',
			'post_type'     => 'post',
			'post_author'   => get_current_user_id(),
			'post_category' => array_filter( array( absint( $d['cat'] ?? 0 ) ) ),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		wp_send_json_error( array( 'message' => $id->get_error_message() ) );
	}
	update_post_meta( $id, '_th_ai_generated', 1 );
	update_post_meta( $id, '_th_ai_keyword', sanitize_text_field( $d['keyword'] ?? '' ) );
	$faq_lines = array();
	foreach ( (array) ( $d['faq'] ?? array() ) as $f ) {
		if ( ! empty( $f['q'] ) && ! empty( $f['a'] ) ) {
			$faq_lines[] = str_replace( '|', '/', sanitize_text_field( $f['q'] ) ) . ' | ' . sanitize_text_field( $f['a'] );
		}
	}
	if ( $faq_lines ) {
		update_post_meta( $id, '_th_faq', implode( "\n", $faq_lines ) );
	}
	th119_set_seo_meta( $id, sanitize_text_field( $d['meta_title'] ?? '' ), sanitize_text_field( $d['meta_desc'] ?? '' ) );
	global $wpdb;
	if ( ! empty( $d['log_ids'] ) ) {
		foreach ( array_map( 'absint', (array) $d['log_ids'] ) as $lid ) {
			$wpdb->update( th119_table( 'ai_log' ), array( 'post_id' => $id ), array( 'id' => $lid, 'user_id' => get_current_user_id() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}
	th119_log_activity( 'ai_draft', 'post:' . $id, $title );
	wp_send_json_success(
		array(
			'edit' => get_edit_post_link( $id, 'raw' ),
			'dups' => $dups,
		)
	);
}
add_action( 'wp_ajax_th119_ai_create_draft', 'th119_ajax_ai_create_draft' );

/**
 * Lưu tiêu đề / mô tả SEO (của plugin; đồng bộ sang Rank Math / Yoast nếu đang dùng).
 *
 * @param int    $id    ID.
 * @param string $title Tiêu đề.
 * @param string $desc  Mô tả.
 */
function th119_set_seo_meta( $id, $title, $desc ) {
	if ( $title ) {
		update_post_meta( $id, '_th_seo_title', $title );
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			update_post_meta( $id, 'rank_math_title', $title );
		}
		if ( defined( 'WPSEO_VERSION' ) ) {
			update_post_meta( $id, '_yoast_wpseo_title', $title );
		}
	}
	if ( $desc ) {
		update_post_meta( $id, '_th_seo_desc', $desc );
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			update_post_meta( $id, 'rank_math_description', $desc );
		}
		if ( defined( 'WPSEO_VERSION' ) ) {
			update_post_meta( $id, '_yoast_wpseo_metadesc', $desc );
		}
	}
}

/**
 * Chặn xuất bản bài AI chưa được duyệt (kể cả xuất bản từ trình soạn thảo, REST, app).
 *
 * @param array $data    Dữ liệu bài.
 * @param array $postarr Dữ liệu gốc.
 * @return array
 */
function th119_block_unapproved_ai( $data, $postarr ) {
	$id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
	if ( ! $id || 'post' !== $data['post_type'] || ! in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) {
		return $data;
	}
	if ( get_post_meta( $id, '_th_ai_generated', true ) && ! get_post_meta( $id, '_th_ai_approved', true ) ) {
		$data['post_status'] = 'pending';
		set_transient( 'th119_ai_blocked_' . get_current_user_id(), $id, 120 );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'th119_block_unapproved_ai', 99, 2 );

/**
 * Thông báo khi bài AI bị chuyển về "Chờ duyệt".
 */
function th119_ai_blocked_notice() {
	$id = get_transient( 'th119_ai_blocked_' . get_current_user_id() );
	if ( $id ) {
		delete_transient( 'th119_ai_blocked_' . get_current_user_id() );
		echo '<div class="notice notice-warning"><p>Bài do AI soạn chưa được duyệt nên đã chuyển sang <strong>Chờ duyệt</strong>. Biên tập viên duyệt tại <a href="' . esc_url( admin_url( 'admin.php?page=th119-ai&tab=review' ) ) . '">AI nội dung → Duyệt bài</a>.</p></div>';
	}
}
add_action( 'admin_notices', 'th119_ai_blocked_notice' );

/**
 * Duyệt bài AI: đánh dấu đã duyệt + người kiểm duyệt, rồi xuất bản ngay / lên lịch / để nháp.
 */
function th119_ai_approve() {
	$id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	check_admin_referer( 'th119_ai_approve_' . $id );
	if ( ! current_user_can( 'th119_approve_ai' ) || ! current_user_can( 'publish_post', $id ) ) {
		wp_die( 'Không có quyền duyệt bài.' );
	}
	$post = get_post( $id );
	if ( ! $post || ! get_post_meta( $id, '_th_ai_generated', true ) ) {
		wp_die( 'Không tìm thấy bài.' );
	}
	if ( empty( $_POST['confirm'] ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=th119-ai&tab=review&err=confirm' ) );
		exit;
	}
	update_post_meta( $id, '_th_ai_approved', get_current_user_id() );
	update_post_meta( $id, '_th_reviewer', get_current_user_id() );
	update_post_meta( $id, '_th_reviewed', current_time( 'Y-m-d' ) );
	$when = isset( $_POST['when'] ) ? sanitize_text_field( wp_unslash( $_POST['when'] ) ) : '';
	$mode = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'draft';
	if ( 'publish' === $mode ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	} elseif ( 'schedule' === $mode && $when && strtotime( $when ) > current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$local = gmdate( 'Y-m-d H:i:s', strtotime( $when ) );
		wp_update_post(
			array(
				'ID'            => $id,
				'post_status'   => 'future',
				'post_date'     => $local,
				'post_date_gmt' => get_gmt_from_date( $local ),
				'edit_date'     => true,
			)
		);
	}
	th119_log_activity( 'ai_approve', 'post:' . $id, $mode . ( 'schedule' === $mode ? ' ' . $when : '' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=th119-ai&tab=review&ok=1' ) );
	exit;
}
add_action( 'admin_post_th119_ai_approve', 'th119_ai_approve' );

/* ---------------------------------------------------------------------------
 * Trang quản trị AI.
 * ------------------------------------------------------------------------- */

/**
 * Trang AI nội dung.
 */
function th119_ai_page() {
	if ( ! current_user_can( 'th119_use_ai' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'write'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$tabs = array(
		'plan'    => 'Lập kế hoạch',
		'write'   => 'Soạn bài',
		'review'  => 'Bản nháp & duyệt',
		'track'   => 'Theo dõi bài đã đăng',
		'history' => 'Lịch sử & chi phí',
	);
	if ( ! isset( $tabs[ $tab ] ) ) {
		$tab = 'write';
	}
	echo '<div class="wrap th119-wrap"><h1>AI nội dung</h1>';
	if ( ! th119_ai_ready() && in_array( $tab, array( 'plan', 'write' ), true ) ) {
		echo '<div class="notice notice-warning"><p>AI chưa được cấu hình. ' . ( current_user_can( 'th119_manage_settings' ) ? '<a href="' . esc_url( admin_url( 'admin.php?page=th119-settings&tab=ai' ) ) . '">Nhập API key</a>.' : 'Liên hệ quản trị viên.' ) . ' Các tab duyệt bài, theo dõi, lịch sử và nút kiểm tra chất lượng vẫn dùng được.</p></div>';
	}
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $label ) {
		printf( '<a class="nav-tab %s" href="%s">%s</a>', $k === $tab ? 'nav-tab-active' : '', esc_url( admin_url( 'admin.php?page=th119-ai&tab=' . $k ) ), esc_html( $label ) );
	}
	echo '</nav>';
	call_user_func( 'th119_ai_tab_' . $tab );
	echo '</div>';
}

/**
 * Ô chọn chuyên mục.
 *
 * @param string $name Tên.
 * @return string
 */
function th119_cat_dropdown( $name ) {
	return wp_dropdown_categories(
		array(
			'name'            => $name,
			'echo'            => 0,
			'hide_empty'      => 0,
			'hierarchical'    => 1,
			'show_option_none' => '— Chọn chuyên mục —',
			'option_none_value' => 0,
		)
	);
}

/**
 * Tab Lập kế hoạch.
 */
function th119_ai_tab_plan() {
	echo '<div class="th119-card th119-ai" data-mode="plan"><p class="description">Phân tích từ khoá dựa trên hiểu biết của AI về ý định tìm kiếm – <strong>không có số liệu lượt tìm kiếm thật</strong>. Số liệu thật xem trong Google Search Console → Hiệu suất.</p>';
	echo '<table class="form-table"><tr><th>Chủ đề / chủ đề trụ cột</th><td><input type="text" class="regular-text" data-field="topic" placeholder="Ví dụ: Laptop không lên nguồn"></td></tr>';
	echo '<tr><th>Từ khoá</th><td><input type="text" class="regular-text" data-field="keyword" placeholder="Ví dụ: laptop không lên nguồn"></td></tr>';
	echo '<tr><th>Chuyên mục</th><td>' . th119_cat_dropdown( 'plan_cat' ) . '</td></tr></table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p><button class="button" data-task="topics">Đề xuất chủ đề</button> <button class="button" data-task="keywords">Phân tích từ khoá</button> <button class="button" data-task="cluster">Xây cụm chủ đề</button> <button class="button" data-task="titles">Đề xuất tiêu đề</button></p>';
	echo '<div class="th119-ai-out"></div></div>';
}

/**
 * Tab Soạn bài.
 */
function th119_ai_tab_write() {
	$prefill = isset( $_GET['topic'] ) ? sanitize_text_field( wp_unslash( $_GET['topic'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="th119-card th119-ai" data-mode="write">';
	echo '<ol class="th119-steps"><li>Nhập chủ đề, từ khoá, ghi chú thực tế của kỹ thuật viên</li><li>Tạo dàn ý → sửa nếu cần</li><li>Soạn bài → sửa trong ô nội dung</li><li>Tạo meta, FAQ, gợi ý liên kết nội bộ</li><li>Tạo bản nháp WordPress → chèn ảnh thật → gửi duyệt</li></ol>';
	echo '<table class="form-table">';
	printf( '<tr><th>Tiêu đề / chủ đề *</th><td><input type="text" class="large-text" data-field="topic" value="%s"></td></tr>', esc_attr( $prefill ) );
	echo '<tr><th>Từ khoá chính</th><td><input type="text" class="regular-text" data-field="keyword"></td></tr>';
	echo '<tr><th>Chuyên mục</th><td>' . th119_cat_dropdown( 'write_cat' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<tr><th>Ghi chú của kỹ thuật viên</th><td><textarea class="large-text" rows="3" data-field="notes" placeholder="Kinh nghiệm thực tế: lỗi hay gặp ở dòng máy nào, cách xử lý cửa hàng đang làm, điều cần cảnh báo…"></textarea><p class="description">Nội dung có trải nghiệm thật giúp bài khác biệt và đáng tin hơn (E-E-A-T).</p></td></tr>';
	echo '<tr><th>Dàn ý</th><td><textarea class="large-text code" rows="10" data-field="outline"></textarea><p><button class="button" data-task="outline">1. Tạo dàn ý</button></p></td></tr>';
	echo '<tr><th>Nội dung (HTML)</th><td><textarea class="large-text code" rows="18" data-field="content"></textarea><p><button class="button" data-task="draft">2. Soạn bài theo dàn ý</button> <button class="button th119-preview">Xem trước</button></p><div class="th119-preview-box" hidden></div></td></tr>';
	echo '<tr><th>Meta title</th><td><input type="text" class="large-text" data-field="meta_title"> <span class="th119-count" data-for="meta_title"></span></td></tr>';
	echo '<tr><th>Meta description</th><td><textarea class="large-text" rows="2" data-field="meta_desc"></textarea> <span class="th119-count" data-for="meta_desc"></span></td></tr>';
	echo '<tr><th>Đường dẫn (slug)</th><td><input type="text" class="regular-text" data-field="slug"></td></tr>';
	echo '<tr><th>Tóm tắt</th><td><textarea class="large-text" rows="2" data-field="excerpt"></textarea><p><button class="button" data-task="meta">3. Tạo meta</button></p></td></tr>';
	echo '<tr><th>Câu hỏi thường gặp</th><td><div class="th119-faq-list"></div><p><button class="button" data-task="faq">4. Đề xuất FAQ</button></p></td></tr>';
	echo '<tr><th>Liên kết nội bộ</th><td><div class="th119-links-list"></div><p><button class="button" data-task="links">5. Gợi ý liên kết nội bộ</button> <span class="description">Chỉ gợi ý trang có thật trên website; bạn tự chèn link vào chỗ phù hợp.</span></p></td></tr>';
	echo '</table><p><button class="button button-primary th119-create-draft">Tạo bản nháp WordPress</button> <span class="description">Bài được lưu ở trạng thái <strong>Nháp</strong>, chưa hiển thị ra ngoài.</span></p>';
	echo '<div class="th119-ai-out"></div></div>';
}

/**
 * Tab Duyệt bài.
 */
function th119_ai_tab_review() {
	if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success"><p>Đã duyệt bài.</p></div>';
	}
	if ( isset( $_GET['err'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-error"><p>Cần tích xác nhận đã kiểm tra nội dung trước khi duyệt.</p></div>';
	}
	$q = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'draft', 'pending', 'future' ),
			'posts_per_page' => 100,
			'meta_key'       => '_th_ai_generated', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'no_found_rows'  => true,
		)
	);
	echo '<p class="description">Quy trình: AI soạn → <strong>Nháp</strong> → người viết chèn ảnh thật, sửa nội dung, bấm "Gửi để duyệt" → <strong>Chờ duyệt</strong> → biên tập viên kiểm tra kỹ thuật và duyệt (xuất bản ngay hoặc lên lịch).</p>';
	echo '<table class="widefat striped"><thead><tr><th>Bài</th><th>Trạng thái</th><th>Chất lượng</th><th>Tác giả</th><th>Duyệt</th></tr></thead><tbody>';
	if ( ! $q->posts ) {
		echo '<tr><td colspan="5">Chưa có bản nháp AI nào chờ xử lý.</td></tr>';
	}
	$labels = array(
		'draft'   => 'Nháp',
		'pending' => 'Chờ duyệt',
		'future'  => 'Đã lên lịch',
	);
	foreach ( $q->posts as $p ) {
		$score    = get_post_meta( $p->ID, '_th_quality', true );
		$approved = get_post_meta( $p->ID, '_th_ai_approved', true );
		echo '<tr><td><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '"><strong>' . esc_html( $p->post_title ) . '</strong></a><br><a href="' . esc_url( get_preview_post_link( $p ) ) . '" target="_blank">Xem trước</a></td>';
		echo '<td>' . esc_html( $labels[ $p->post_status ] ?? $p->post_status ) . ( 'future' === $p->post_status ? '<br>' . esc_html( get_the_date( 'd/m/Y H:i', $p ) ) : '' ) . '</td>';
		echo '<td>' . ( '' === $score ? '<em>chưa kiểm tra</em>' : (int) $score . '/100' ) . '</td><td>' . esc_html( get_the_author_meta( 'display_name', $p->post_author ) ) . '</td><td>';
		if ( $approved ) {
			echo 'Đã duyệt bởi ' . esc_html( get_the_author_meta( 'display_name', (int) $approved ) );
		} elseif ( current_user_can( 'th119_approve_ai' ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'th119_ai_approve_' . $p->ID );
			echo '<input type="hidden" name="action" value="th119_ai_approve"><input type="hidden" name="post_id" value="' . (int) $p->ID . '">';
			echo '<label><input type="checkbox" name="confirm" value="1"> Tôi đã kiểm tra các bước, lệnh, thông số và ảnh</label><br>';
			echo '<select name="mode"><option value="draft">Duyệt, chưa đăng</option><option value="publish">Duyệt & đăng ngay</option><option value="schedule">Duyệt & lên lịch</option></select> <input type="datetime-local" name="when"> ';
			submit_button( 'Duyệt', 'primary small', 'submit', false );
			echo '</form>';
		} else {
			echo '<em>Chờ biên tập viên duyệt</em>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * Tab Theo dõi bài đã đăng (lượt xem từ thống kê nội bộ, 30 ngày).
 */
function th119_ai_tab_track() {
	$q = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'meta_key'       => '_th_ai_generated', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'no_found_rows'  => true,
		)
	);
	$views = array();
	foreach ( th119_stat_top( 'page', 30, 2000 ) as $row ) {
		$views[ untrailingslashit( $row->dim ) ] = (int) $row->hits;
	}
	echo '<p class="description">Lượt xem 30 ngày từ bộ đếm của plugin (không tính người đăng nhập và bot). Thứ hạng và lượt hiển thị Google: xem Search Console.</p>';
	echo '<table class="widefat striped"><thead><tr><th>Bài</th><th>Ngày đăng</th><th>Cập nhật</th><th>Lượt xem 30 ngày</th><th>Chất lượng</th></tr></thead><tbody>';
	if ( ! $q->posts ) {
		echo '<tr><td colspan="5">Chưa có bài AI nào được đăng.</td></tr>';
	}
	foreach ( $q->posts as $p ) {
		$path = untrailingslashit( (string) wp_parse_url( get_permalink( $p ), PHP_URL_PATH ) );
		printf(
			'<tr><td><a href="%s" target="_blank">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_url( get_permalink( $p ) ),
			esc_html( $p->post_title ),
			esc_html( get_the_date( 'd/m/Y', $p ) ),
			esc_html( get_the_modified_date( 'd/m/Y', $p ) ),
			isset( $views[ $path ] ) ? (int) $views[ $path ] : '<em>chưa có dữ liệu</em>',
			esc_html( (string) get_post_meta( $p->ID, '_th_quality', true ) )
		);
	}
	echo '</tbody></table>';
}

/**
 * Tab Lịch sử.
 */
function th119_ai_tab_history() {
	global $wpdb;
	$s    = th119_ai_settings();
	$rows = $wpdb->get_results( 'SELECT * FROM ' . th119_table( 'ai_log' ) . ' ORDER BY id DESC LIMIT 100' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	printf( '<p>Chi phí ước tính tháng này: <strong>%s / %s USD</strong> · Số lần gọi hôm nay: <strong>%d / %d</strong></p>', esc_html( number_format( th119_ai_month_cost(), 3 ) ), esc_html( $s['budget_month'] ), (int) th119_ai_today_calls(), (int) $s['daily_limit'] );
	echo '<table class="widefat striped"><thead><tr><th>Thời gian</th><th>Người dùng</th><th>Tác vụ</th><th>Model</th><th>Token vào/ra</th><th>Chi phí (USD)</th><th>Bài</th><th>Kết quả</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="8">Chưa có lịch sử.</td></tr>';
	}
	foreach ( $rows as $r ) {
		printf(
			'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%d / %d</td><td>%s</td><td>%s</td><td><details><summary>%s</summary><pre class="th119-pre">%s</pre></details></td></tr>',
			esc_html( th119_date( $r->created_at, true ) ),
			esc_html( get_the_author_meta( 'display_name', $r->user_id ) ),
			esc_html( $r->task ),
			esc_html( $r->model ),
			(int) $r->tokens_in,
			(int) $r->tokens_out,
			esc_html( number_format( (float) $r->cost, 4 ) ),
			$r->post_id ? '<a href="' . esc_url( get_edit_post_link( $r->post_id ) ) . '">#' . (int) $r->post_id . '</a>' : '',
			'ok' === $r->status ? 'Xem' : 'Lỗi',
			esc_html( mb_substr( (string) $r->output, 0, 5000 ) )
		);
	}
	echo '</tbody></table>';
}
