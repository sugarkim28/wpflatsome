<?php
/**
 * Công cụ dựng website: tạo cây chuyên mục kiến thức, các trang chuẩn, dịch vụ chính, menu.
 * Nguyên tắc: chỉ TẠO MỚI cái còn thiếu (so theo slug), không sửa / xoá nội dung có sẵn.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trang chuẩn: slug => [tiêu đề, nội dung, trạng thái mặc định, ghi chú].
 *
 * @return array
 */
function th119_standard_pages() {
	$brand = th119_opt( 'brand' );
	return array(
		'trang-chu'          => array( 'Trang chủ', '[th119_home]', 'publish', 'Trang chủ dựng sẵn các khối. Chỉ đặt làm trang chủ khi bạn tích ô bên dưới.' ),
		'kien-thuc'          => array( 'Trung tâm kiến thức', '[th119_kb_hub]', 'publish', '' ),
		'gui-yeu-cau-ho-tro' => array( 'Gửi yêu cầu hỗ trợ', "<p>Mô tả sự cố máy tính, laptop, máy in hoặc mạng. Kỹ thuật viên $brand sẽ liên hệ lại để tư vấn cách kiểm tra và báo giá trước khi sửa.</p>\n[th119_support_form]\n<h2>Cần gấp?</h2>\n[th119_cta show=\"call,zalo\"]", 'publish', '' ),
		'dat-lich'           => array( 'Đặt lịch kỹ thuật viên', "<p>Chọn thời gian thuận tiện, $brand sẽ gọi xác nhận lịch hẹn trước khi đến hoặc trước khi bạn mang máy tới.</p>\n[th119_support_form type=\"booking\"]", 'publish', '' ),
		'tra-cuu-bao-hanh'   => array( 'Tra cứu bảo hành', "[th119_lookup]\n<p>Không nhớ mã phiếu? Gọi hotline hoặc nhắn Zalo kèm số điện thoại đã đăng ký để được hỗ trợ.</p>\n[th119_cta show=\"call,zalo\"]", 'publish', '' ),
		'lien-he'            => array( 'Liên hệ', "<p>$brand – " . th119_opt( 'slogan' ) . ".</p>\n[th119_contact]\n[th119_cta show=\"call,zalo,maps\"]\n[th119_map]\n<h2>Gửi yêu cầu hỗ trợ</h2>\n[th119_support_form compact=\"1\"]", 'publish', '' ),
		'gioi-thieu'         => array( 'Giới thiệu', th119_about_template(), 'draft', 'Bản nháp: bổ sung câu chuyện, năm thành lập, ảnh cửa hàng/kỹ thuật viên thật rồi mới đăng.' ),
		'chinh-sach-bao-mat' => array( 'Chính sách bảo mật', th119_privacy_template(), 'draft', 'Bản nháp: rà soát với thực tế vận hành trước khi đăng.' ),
		'dieu-khoan-su-dung' => array( 'Điều khoản sử dụng', th119_terms_template(), 'draft', 'Bản nháp: rà soát trước khi đăng.' ),
	);
}

/**
 * Mẫu trang Giới thiệu (không bịa số liệu – phần cần bổ sung ghi rõ).
 *
 * @return string
 */
function th119_about_template() {
	$b = th119_opt( 'brand' );
	return "<p><strong>$b</strong> – " . th119_opt( 'slogan' ) . '. Chúng tôi sửa chữa laptop, máy tính, máy in, mạng và hỗ trợ IT cho gia đình, văn phòng tại ' . th119_opt( 'district' ) . ', ' . th119_opt( 'city' ) . ".</p>
<h2>Chúng tôi làm gì</h2>
[th119_services]
<h2>Cách chúng tôi làm việc</h2>
[th119_process]
<h2>Cam kết</h2>
[th119_why]
<h2>Câu chuyện $b</h2>
<p>[BỔ SUNG TRƯỚC KHI ĐĂNG: năm bắt đầu hoạt động, người sáng lập/kỹ thuật viên chính, kinh nghiệm thực tế, ảnh cửa hàng và đội ngũ.]</p>
<h2>Liên hệ</h2>
[th119_contact]
[th119_map]";
}

/**
 * Mẫu chính sách bảo mật theo đúng dữ liệu plugin thu thập.
 *
 * @return string
 */
function th119_privacy_template() {
	$b = th119_opt( 'brand' );
	return "<p>Chính sách này giải thích $b thu thập, sử dụng và bảo vệ thông tin cá nhân của bạn khi dùng website " . esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) . ".</p>
<h2>1. Thông tin chúng tôi thu thập</h2>
<ul><li>Khi gửi yêu cầu hỗ trợ / đặt lịch: họ tên, số điện thoại, thiết bị, khu vực, thời gian liên hệ, mô tả sự cố và hình ảnh lỗi bạn tải lên (ảnh được xoá dữ liệu vị trí GPS và lưu ở khu vực không truy cập công khai).</li><li>Khi tra cứu bảo hành: mã phiếu/serial và số điện thoại bạn nhập (chỉ dùng để đối chiếu, không lưu lại).</li><li>Thống kê truy cập ẩn danh: số lượt xem trang, nguồn truy cập, lượt bấm Gọi/Zalo – không dùng cookie theo dõi, không lưu địa chỉ IP.</li><li>Nếu website dùng Google Analytics: Google có thể đặt cookie theo chính sách của Google.</li></ul>
<h2>2. Mục đích sử dụng</h2>
<p>Liên hệ hỗ trợ kỹ thuật, xác nhận lịch hẹn, quản lý bảo hành và cải thiện website. Chúng tôi không bán hoặc chia sẻ thông tin của bạn cho bên thứ ba vì mục đích quảng cáo.</p>
<h2>3. Thời gian lưu trữ</h2>
<p>[BỔ SUNG: thời gian lưu yêu cầu hỗ trợ và phiếu bảo hành, ví dụ đến hết thời hạn bảo hành + 12 tháng.]</p>
<h2>4. Quyền của bạn</h2>
<p>Bạn có thể yêu cầu xem, sửa hoặc xoá thông tin cá nhân bằng cách liên hệ hotline " . esc_html( th119_opt( 'hotline' ) ) . ( th119_opt( 'email' ) ? ' hoặc email ' . esc_html( th119_opt( 'email' ) ) : '' ) . ".</p>
<h2>5. Dữ liệu trên thiết bị gửi sửa</h2>
<p>Kỹ thuật viên chỉ truy cập dữ liệu khi cần thiết cho việc sửa chữa và khi được bạn đồng ý. Bạn nên sao lưu dữ liệu quan trọng trước khi gửi máy.</p>
<h2>6. Liên hệ</h2>
[th119_contact]";
}

/**
 * Mẫu điều khoản sử dụng.
 *
 * @return string
 */
function th119_terms_template() {
	$b = th119_opt( 'brand' );
	return "<h2>1. Nội dung hướng dẫn</h2>
<p>Bài viết trong Trung tâm kiến thức mang tính tham khảo. Bạn tự chịu trách nhiệm khi tự thực hiện; hãy sao lưu dữ liệu và ngừng thao tác khi không chắc chắn. Với các trường hợp được ghi \"không nên tự sửa\", vui lòng liên hệ kỹ thuật viên.</p>
<h2>2. Báo giá và sửa chữa</h2>
<p>Giá dịch vụ được báo sau khi kiểm tra thực tế. $b chỉ tiến hành sửa chữa, thay linh kiện khi khách hàng đồng ý.</p>
<h2>3. Bảo hành</h2>
<p>Thời hạn và điều kiện bảo hành ghi trên phiếu giao khách và tra cứu được tại trang Tra cứu bảo hành. [BỔ SUNG: các trường hợp không được bảo hành.]</p>
<h2>4. Bản quyền nội dung</h2>
<p>Nội dung, hình ảnh trên website thuộc $b. Vui lòng ghi nguồn khi trích dẫn.</p>
<h2>5. Liên hệ</h2>
[th119_contact]";
}

/**
 * Dịch vụ chính (bản nháp để chủ doanh nghiệp xác nhận chi tiết, giá).
 *
 * @return array slug => [tiêu đề, icon, mô tả ngắn, nội dung, hạng mục, faq].
 */
function th119_seed_services() {
	$area = th119_opt( 'district' ) . ', ' . th119_opt( 'city' );
	return array(
		'sua-chua-laptop'          => array( 'Sửa chữa laptop', 'laptop', 'Kiểm tra miễn phí nguyên nhân, báo giá trước khi sửa', "<p>Dịch vụ sửa laptop tại $area cho các lỗi thường gặp: không lên nguồn, không nhận sạc, chai pin, vỡ/sọc màn hình, liệt phím, quạt kêu, máy nóng và chạy chậm.</p><h2>Lỗi laptop thường gặp</h2><ul><li>Laptop không lên nguồn, lên nguồn nhưng không lên hình.</li><li>Không nhận sạc, sạc không vào pin, pin tụt nhanh.</li><li>Màn hình sọc, chớp, tối, vỡ; bản lề gãy.</li><li>Bàn phím liệt phím, touchpad không hoạt động.</li><li>Quạt kêu to, máy nóng, tự tắt.</li><li>Máy chạy chậm, treo, lỗi Windows.</li></ul><h2>Khi nào nên mang máy đi kiểm tra?</h2><p>Khi máy có mùi khét, pin phồng, máy bị vào nước hoặc đã thử các bước cơ bản trong Trung tâm kiến thức mà không hết lỗi, hãy ngừng sử dụng và mang máy đi kiểm tra để tránh hỏng lan sang linh kiện khác.</p>", "Kiểm tra, xác định nguyên nhân lỗi\nBáo giá trước khi sửa\nThay thế linh kiện khi khách đồng ý\nVệ sinh, tra keo tản nhiệt\nCài đặt, tối ưu Windows và phần mềm\nKiểm tra lại toàn bộ chức năng trước khi bàn giao", "Kiểm tra laptop có mất phí không? | [XÁC NHẬN chính sách phí kiểm tra của cửa hàng.]\nSửa laptop có mất dữ liệu không? | Phần lớn lỗi phần cứng không ảnh hưởng dữ liệu. Nếu cần can thiệp ổ cứng hoặc cài lại hệ điều hành, kỹ thuật viên sẽ báo trước và hỗ trợ sao lưu." ),
		'sua-chua-may-tinh-de-ban' => array( 'Sửa chữa máy tính để bàn', 'pc', 'Sửa PC văn phòng, PC gaming tại cửa hàng hoặc tận nơi', "<p>Kiểm tra và sửa máy tính để bàn tại $area: máy không khởi động, tự khởi động lại, màn hình xanh, không nhận ổ cứng, lỗi nguồn, mainboard, RAM, card màn hình.</p><h2>Quy trình kiểm tra</h2><p>Kỹ thuật viên kiểm tra lần lượt nguồn, RAM, ổ cứng, mainboard, card màn hình và phần mềm để xác định đúng linh kiện lỗi, tránh thay thế không cần thiết.</p>", "Chẩn đoán lỗi phần cứng, phần mềm\nThay nguồn, RAM, ổ cứng, card màn hình\nCài đặt hệ điều hành, driver, phần mềm\nVệ sinh máy, sắp xếp lại dây cáp\nHỗ trợ tận nơi cho văn phòng", '' ),
		'nang-cap-may-tinh'        => array( 'Nâng cấp máy tính, laptop', 'upgrade', 'Tư vấn nâng cấp SSD, RAM, card màn hình đúng nhu cầu', "<p>Nâng cấp là cách tiết kiệm để máy chạy nhanh hơn: thay HDD bằng SSD, thêm RAM, nâng cấp card màn hình hoặc nguồn. Kỹ thuật viên kiểm tra khả năng tương thích của máy trước khi tư vấn.</p><h2>Nâng cấp nào hiệu quả nhất?</h2><p>Với máy còn dùng ổ cứng HDD, chuyển sang SSD thường cải thiện tốc độ khởi động và mở ứng dụng rõ rệt nhất. RAM chỉ nên nâng khi máy thường xuyên dùng hết bộ nhớ.</p>", "Kiểm tra cấu hình và khả năng nâng cấp\nTư vấn linh kiện phù hợp ngân sách\nChuyển dữ liệu, hệ điều hành sang SSD mới\nKiểm tra nhiệt độ, độ ổn định sau nâng cấp", '' ),
		'bao-tri-may-tinh'         => array( 'Bảo trì máy tính', 'shield', 'Vệ sinh, tra keo, kiểm tra sức khoẻ ổ cứng, sao lưu dữ liệu', "<p>Bảo trì định kỳ giúp máy mát hơn, ổn định hơn và phát hiện sớm ổ cứng sắp hỏng. Phù hợp cho cá nhân và văn phòng.</p><h2>Nội dung bảo trì</h2><p>Vệ sinh bụi, thay keo tản nhiệt, kiểm tra quạt, kiểm tra tình trạng ổ cứng/SSD, cập nhật hệ điều hành, gỡ phần mềm không cần thiết, kiểm tra sao lưu dữ liệu.</p>", "Vệ sinh bụi, thay keo tản nhiệt\nKiểm tra sức khoẻ ổ cứng, SSD, pin\nCập nhật Windows, driver\nKiểm tra virus, phần mềm lạ\nKiểm tra và thiết lập sao lưu dữ liệu", '' ),
		'dich-vu-it-doanh-nghiep'  => array( 'Dịch vụ IT doanh nghiệp', 'building', 'Bảo trì định kỳ, mạng nội bộ, máy in, sao lưu cho văn phòng', "<p>Hỗ trợ IT cho văn phòng, cửa hàng, doanh nghiệp nhỏ tại $area: bảo trì máy tính định kỳ, quản lý mạng LAN/Wi-Fi, máy in, phân quyền chia sẻ dữ liệu, sao lưu và bảo mật cơ bản.</p><h2>Phù hợp với</h2><p>Văn phòng chưa có nhân sự IT riêng, cần một đơn vị hỗ trợ nhanh khi sự cố và bảo trì định kỳ để hạn chế gián đoạn công việc.</p>", "Khảo sát hiện trạng hệ thống\nBảo trì máy tính, máy in định kỳ\nThiết lập, quản lý mạng LAN, Wi-Fi, router\nChia sẻ dữ liệu, NAS, phân quyền\nSao lưu dữ liệu, bảo mật cơ bản\nHỗ trợ từ xa và tận nơi khi có sự cố", '' ),
		'ho-tro-ky-thuat'          => array( 'Hỗ trợ kỹ thuật từ xa & tận nơi', 'support', 'Cài phần mềm, sửa lỗi Office, máy in, Wi-Fi nhanh chóng', "<p>Nhiều lỗi phần mềm có thể xử lý từ xa qua phần mềm điều khiển máy tính (UltraViewer, AnyDesk, TeamViewer) mà không cần mang máy đi. Lỗi phần cứng sẽ được hẹn kiểm tra tận nơi hoặc tại cửa hàng.</p><h2>An toàn khi hỗ trợ từ xa</h2><p>Chỉ cấp quyền điều khiển cho kỹ thuật viên bạn đã liên hệ qua hotline chính thức, và tắt phần mềm điều khiển sau khi hoàn tất.</p>", "Cài đặt, sửa lỗi Microsoft Office, phần mềm văn phòng\nCài driver, kết nối máy in, máy scan\nSửa lỗi Wi-Fi, mạng\nHỗ trợ phần mềm kế toán, HTKK\nHướng dẫn sử dụng máy tính", '' ),
	);
}

/**
 * Trang Công cụ.
 */
function th119_tools_page() {
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	echo '<div class="wrap th119-wrap"><h1>Công cụ dựng website</h1>';
	if ( isset( $_GET['msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['msg'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	echo '<div class="th119-warn"><strong>Sao lưu trước khi chạy</strong> (hosting hoặc plugin sao lưu). Các công cụ dưới đây chỉ tạo mới phần còn thiếu, không sửa/xoá nội dung có sẵn; mọi thao tác được ghi vào Nhật ký.</div>';

	$tools = array(
		'kb'       => array( '1. Cây chuyên mục kiến thức', 'Tạo 7 nhóm chính (Windows, Laptop, Phần cứng, Mạng và hệ thống, Phần mềm, Thiết bị, IT doanh nghiệp) và ' . array_sum( array_map( function ( $r ) { return count( $r[3] ); }, th119_kb_tree() ) ) . ' chuyên mục con. Chuyên mục trùng slug được giữ nguyên.', '' ), // phpcs:ignore Generic.Formatting.MultipleStatementAlignment
		'pages'    => array( '2. Các trang chuẩn', 'Tạo trang còn thiếu: ' . implode( ', ', wp_list_pluck( th119_standard_pages(), 0 ) ) . '. Giới thiệu, Chính sách bảo mật, Điều khoản tạo ở dạng Nháp để bạn rà soát.', '<label><input type="checkbox" name="set_home" value="1"> Đặt "Trang chủ" mới làm trang chủ website (trang chủ cũ vẫn giữ nguyên, có thể đổi lại ở Cài đặt → Đọc)</label>' ),
		'services' => array( '3. Sáu dịch vụ chính', 'Tạo dạng Nháp: ' . implode( ', ', wp_list_pluck( th119_seed_services(), 0 ) ) . '. Bổ sung giá, ảnh thật rồi Đăng.', '' ),
		'menu'     => array( '4. Menu chính', 'Tạo menu "Menu chính – Tin Học 119": Trang chủ, Dịch vụ, Kiến thức (7 nhóm), Dự án, Tra cứu bảo hành, Liên hệ.', '<label><input type="checkbox" name="assign" value="1"> Gán vào vị trí menu chính và menu điện thoại (menu cũ không bị xoá)</label>' ),
	);
	foreach ( $tools as $k => $t ) {
		echo '<div class="th119-card"><h2>' . esc_html( $t[0] ) . '</h2><p>' . esc_html( $t[1] ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'th119_tool_' . $k );
		echo '<input type="hidden" name="action" value="th119_tool"><input type="hidden" name="tool" value="' . esc_attr( $k ) . '">';
		if ( $t[2] ) {
			echo '<p>' . wp_kses( $t[2], array( 'label' => array(), 'input' => array( 'type' => true, 'name' => true, 'value' => true ) ) ) . '</p>';
		}
		submit_button( 'Chạy', 'secondary', 'submit', false );
		echo '</form></div>';
	}
	echo '<div class="th119-card"><h2>Shortcode</h2><table class="widefat striped"><tbody>';
	$codes = array(
		'[th119_support_form]'                 => 'Form gửi yêu cầu hỗ trợ (type="booking" = đặt lịch, compact="1" = gọn)',
		'[th119_lookup]'                       => 'Tra cứu bảo hành / tiến độ',
		'[th119_cta show="call,zalo,request,booking,maps"]' => 'Nút liên hệ',
		'[th119_contact]'                      => 'Thông tin liên hệ (NAP)',
		'[th119_map]'                          => 'Bản đồ Google',
		'[th119_search]'                       => 'Ô tìm kiếm kiến thức',
		'[th119_services number="6" columns="3" group=""]' => 'Lưới dịch vụ',
		'[th119_kb_categories]'                => 'Lưới chủ đề kiến thức',
		'[th119_posts number="6" cat="" featured="1"]' => 'Bài viết',
		'[th119_kb_hub]'                       => 'Trung tâm kiến thức',
		'[th119_projects number="3"]'          => 'Dự án (ẩn khi chưa có)',
		'[th119_reviews number="6"]'           => 'Đánh giá khách hàng thật (ẩn khi chưa có)',
		'[th119_process]Bước | Mô tả[/th119_process]' => 'Quy trình làm việc',
		'[th119_why]Tiêu đề | Mô tả[/th119_why]' => 'Lý do chọn',
		'[th119_breadcrumb]'                   => 'Breadcrumb',
		'[th_canh_bao] [th_luu_y] [th_lenh] [th_ky_thuat_vien]' => 'Khối trình bày trong bài kiến thức',
	);
	foreach ( $codes as $c => $d ) {
		echo '<tr><td><code>' . esc_html( $c ) . '</code></td><td>' . esc_html( $d ) . '</td></tr>';
	}
	echo '</tbody></table></div></div>';
}

/**
 * Chạy công cụ.
 */
function th119_run_tool() {
	$tool = isset( $_POST['tool'] ) ? sanitize_key( $_POST['tool'] ) : '';
	check_admin_referer( 'th119_tool_' . $tool );
	if ( ! current_user_can( 'th119_manage_settings' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$msg = '';
	switch ( $tool ) {
		case 'kb':
			list( $c, $s ) = th119_kb_seed_categories();
			$msg           = "Đã tạo $c chuyên mục, giữ nguyên $s chuyên mục đã có.";
			break;
		case 'pages':
			$made = array();
			$skip = array();
			foreach ( th119_standard_pages() as $slug => $p ) {
				$exist = get_page_by_path( $slug, OBJECT, 'page' );
				if ( $exist ) {
					$skip[] = $p[0];
					continue;
				}
				$id = wp_insert_post(
					array(
						'post_type'    => 'page',
						'post_title'   => $p[0],
						'post_name'    => $slug,
						'post_content' => $p[1],
						'post_status'  => $p[2],
					)
				);
				if ( $id && ! is_wp_error( $id ) ) {
					$made[] = $p[0];
					if ( 'chinh-sach-bao-mat' === $slug && ! (int) get_option( 'wp_page_for_privacy_policy' ) ) {
						update_option( 'wp_page_for_privacy_policy', $id );
					}
				}
			}
			if ( ! empty( $_POST['set_home'] ) ) {
				$home = get_page_by_path( 'trang-chu', OBJECT, 'page' );
				if ( $home && 'publish' === $home->post_status ) {
					th119_log_activity( 'front_page', 'page:' . $home->ID, 'Trước đó: ' . get_option( 'show_on_front' ) . ' / ' . get_option( 'page_on_front' ) );
					update_option( 'show_on_front', 'page' );
					update_option( 'page_on_front', $home->ID );
					$made[] = '(đặt Trang chủ)';
				}
			}
			$msg = 'Đã tạo: ' . ( $made ? implode( ', ', $made ) : 'không có' ) . '. Đã có sẵn (giữ nguyên): ' . ( $skip ? implode( ', ', $skip ) : 'không có' ) . '.';
			th119_log_activity( 'tool_pages', '', $msg );
			break;
		case 'services':
			$made = 0;
			foreach ( th119_seed_services() as $slug => $s ) {
				if ( get_page_by_path( $slug, OBJECT, 'dich_vu' ) ) {
					continue;
				}
				$id = wp_insert_post(
					array(
						'post_type'    => 'dich_vu',
						'post_title'   => $s[0],
						'post_name'    => $slug,
						'post_content' => $s[3],
						'post_excerpt' => $s[2],
						'post_status'  => 'draft',
						'menu_order'   => $made,
					)
				);
				if ( $id && ! is_wp_error( $id ) ) {
					update_post_meta( $id, '_sgd_icon', $s[1] );
					update_post_meta( $id, '_sgd_subtitle', $s[2] );
					update_post_meta( $id, '_sgd_price', 'Báo giá sau kiểm tra' );
					update_post_meta( $id, '_sgd_includes', $s[4] );
					update_post_meta( $id, '_sgd_featured', '1' );
					if ( $s[5] ) {
						update_post_meta( $id, '_sgd_faq', $s[5] );
					}
					$made++;
				}
			}
			$msg = "Đã tạo $made dịch vụ dạng Nháp (Dịch vụ → Tất cả dịch vụ). Kiểm tra nội dung, xoá các ô [XÁC NHẬN…], thêm ảnh rồi Đăng.";
			th119_log_activity( 'tool_services', '', $msg );
			break;
		case 'menu':
			$msg = th119_build_menu( ! empty( $_POST['assign'] ) );
			break;
	}
	wp_safe_redirect( admin_url( 'admin.php?page=th119-tools&msg=' . rawurlencode( $msg ) ) );
	exit;
}
add_action( 'admin_post_th119_tool', 'th119_run_tool' );

/**
 * Tạo menu chính.
 *
 * @param bool $assign Gán vị trí.
 * @return string
 */
function th119_build_menu( $assign ) {
	$name = 'Menu chính – Tin Học 119';
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		$menu_id = (int) $menu->term_id;
		$msg     = 'Menu đã tồn tại, giữ nguyên nội dung.';
	} else {
		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return $menu_id->get_error_message();
		}
		$add = function ( $title, $url, $parent = 0, $obj = array() ) use ( $menu_id ) {
			$args = array(
				'menu-item-title'     => $title,
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
			);
			$args = $obj ? array_merge( $args, $obj ) : array_merge( $args, array( 'menu-item-url' => $url, 'menu-item-type' => 'custom' ) );
			return wp_update_nav_menu_item( $menu_id, 0, $args );
		};
		$add( 'Trang chủ', home_url( '/' ) );
		$svc = $add( 'Dịch vụ', get_post_type_archive_link( 'dich_vu' ) );
		foreach ( get_posts( array( 'post_type' => 'dich_vu', 'numberposts' => 10, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $p ) {
			$add( $p->post_title, '', $svc, array( 'menu-item-object' => 'dich_vu', 'menu-item-object-id' => $p->ID, 'menu-item-type' => 'post_type' ) );
		}
		$kb_page = get_page_by_path( 'kien-thuc' );
		$kb      = $kb_page ? $add( 'Kiến thức', '', 0, array( 'menu-item-object' => 'page', 'menu-item-object-id' => $kb_page->ID, 'menu-item-type' => 'post_type' ) ) : $add( 'Kiến thức', home_url( '/kien-thuc/' ) );
		foreach ( th119_kb_roots() as $t ) {
			$add( $t->name, '', $kb, array( 'menu-item-object' => 'category', 'menu-item-object-id' => $t->term_id, 'menu-item-type' => 'taxonomy' ) );
		}
		$add( 'Dự án', get_post_type_archive_link( 'th_project' ) );
		foreach ( array( 'tra-cuu-bao-hanh' => 'Tra cứu bảo hành', 'lien-he' => 'Liên hệ' ) as $slug => $title ) {
			$pg = get_page_by_path( $slug );
			if ( $pg ) {
				$add( $title, '', 0, array( 'menu-item-object' => 'page', 'menu-item-object-id' => $pg->ID, 'menu-item-type' => 'post_type' ) );
			}
		}
		$msg = 'Đã tạo menu.';
	}
	if ( $assign ) {
		$loc = (array) get_theme_mod( 'nav_menu_locations', array() );
		th119_log_activity( 'menu_assign', 'menu:' . $menu_id, 'Trước đó: ' . wp_json_encode( $loc ) );
		foreach ( array( 'primary', 'primary_mobile' ) as $l ) {
			$loc[ $l ] = $menu_id;
		}
		set_theme_mod( 'nav_menu_locations', $loc );
		$msg .= ' Đã gán vào menu chính và menu điện thoại.';
	}
	th119_log_activity( 'tool_menu', 'menu:' . $menu_id, $msg );
	return $msg;
}
