<?php
/**
 * 10 bài viết cho mục Kiến thức (5 bài) và Đào tạo (5 bài) – nội dung tự biên soạn,
 * cập nhật theo văn bản đang có hiệu lực đến tháng 10/2026.
 *
 * Link nội bộ: [[slug-dich-vu|chữ neo]] → trang dịch vụ, [[nhom:slug-nhom|chữ neo]] → trang nhóm dịch vụ.
 * Dịch vụ chưa có trên web → chỉ hiện chữ, không tạo link hỏng.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_upd = '<p class="sgd-updated"><em>Cập nhật tháng 10/2026 theo quy định đang có hiệu lực. Chính sách thuế, đăng ký doanh nghiệp thay đổi thường xuyên – trước khi thực hiện, bạn nên liên hệ chuyên viên để được kiểm tra theo trường hợp cụ thể.</em></p>';

return array(
	// Cây chuyên mục kiểu trang Kiến thức của ketoananpha.vn: [tên, mô tả, chuyên mục cha, icon, thứ tự].
	'categories' => array(
		'kien-thuc-ke-toan'            => array( 'Kiến thức kế toán', 'Giải đáp các vướng mắc về kế toán và thuế: hạch toán, hoá đơn, kê khai, quyết toán, các loại thuế doanh nghiệp và hộ kinh doanh phải nộp – cập nhật theo quy định mới nhất.', '', 'calculator', 1 ),
		'thue-tndn'                    => array( 'Thuế thu nhập doanh nghiệp', 'Thuế suất, chi phí được trừ, tạm nộp và quyết toán thuế TNDN theo Luật Thuế TNDN 2025.', 'kien-thuc-ke-toan', 'tax', 1 ),
		'thue-tncn'                    => array( 'Thuế thu nhập cá nhân', 'Cách tính thuế TNCN từ tiền lương, giảm trừ gia cảnh, khấu trừ, quyết toán và hoàn thuế.', 'kien-thuc-ke-toan', 'users', 2 ),
		'thue-gtgt'                    => array( 'Thuế giá trị gia tăng', 'Kê khai, khấu trừ, hoàn thuế GTGT và các mức thuế suất đang áp dụng.', 'kien-thuc-ke-toan', 'wallet', 3 ),
		'thue-ho-kinh-doanh'           => array( 'Thuế hộ, cá nhân kinh doanh', 'Ngưỡng doanh thu chịu thuế, cách tính thuế, khai thuế và sổ sách của hộ kinh doanh.', 'kien-thuc-ke-toan', 'building', 4 ),
		'hoa-don-chung-tu'             => array( 'Hoá đơn, chứng từ', 'Hoá đơn điện tử, xử lý hoá đơn sai sót, chứng từ thanh toán không dùng tiền mặt.', 'kien-thuc-ke-toan', 'doc', 5 ),
		'bao-cao-thue-tai-chinh'       => array( 'Báo cáo thuế – Tài chính', 'Lịch nộp tờ khai, báo cáo tài chính, quyết toán năm và các mốc thời hạn cần nhớ.', 'kien-thuc-ke-toan', 'chart', 6 ),
		'so-sach-ke-toan'              => array( 'Sổ sách kế toán', 'Chế độ kế toán, hệ thống tài khoản, ghi sổ và tổ chức công tác kế toán.', 'kien-thuc-ke-toan', 'edit', 7 ),
		'bao-hiem-xa-hoi'              => array( 'Bảo hiểm xã hội', 'Đăng ký, đóng và báo tăng giảm bảo hiểm xã hội, y tế, thất nghiệp cho người lao động.', 'kien-thuc-ke-toan', 'shield', 8 ),
		'kien-thuc-phap-ly'            => array( 'Kiến thức pháp lý', 'Tổng hợp các thủ tục pháp lý doanh nghiệp thường gặp: thành lập, thay đổi đăng ký kinh doanh, hộ kinh doanh, tạm ngừng, giải thể, sở hữu trí tuệ – cập nhật theo Luật Doanh nghiệp và nghị định mới.', '', 'stamp', 2 ),
		'thu-tuc-thanh-lap'            => array( 'Thành lập doanh nghiệp', 'Hồ sơ, trình tự thành lập công ty TNHH, cổ phần và những việc cần làm sau khi có giấy phép.', 'kien-thuc-phap-ly', 'stamp', 1 ),
		'ho-kinh-doanh-ca-the'         => array( 'Hộ kinh doanh cá thể', 'Đăng ký, quản lý và chuyển đổi hộ kinh doanh lên doanh nghiệp.', 'kien-thuc-phap-ly', 'pin', 2 ),
		'cong-ty-von-nuoc-ngoai'       => array( 'Công ty vốn nước ngoài', 'Thủ tục đầu tư, thành lập và vận hành doanh nghiệp có vốn đầu tư nước ngoài.', 'kien-thuc-phap-ly', 'globe', 3 ),
		'thay-doi-dang-ky-kinh-doanh'  => array( 'Thay đổi GPKD', 'Các trường hợp phải đăng ký thay đổi nội dung đăng ký doanh nghiệp và thời hạn thực hiện.', 'kien-thuc-phap-ly', 'edit', 4 ),
		'chi-nhanh-van-phong-dai-dien' => array( 'Chi nhánh – Văn phòng đại diện', 'Thành lập, thay đổi, chấm dứt hoạt động chi nhánh, văn phòng đại diện, địa điểm kinh doanh.', 'kien-thuc-phap-ly', 'building', 5 ),
		'so-huu-tri-tue'               => array( 'Nhãn hiệu – Sở hữu trí tuệ', 'Đăng ký bảo hộ nhãn hiệu, logo và các quyền sở hữu trí tuệ của doanh nghiệp.', 'kien-thuc-phap-ly', 'trademark', 6 ),
		'tam-ngung-giai-the'           => array( 'Tạm ngừng – Giải thể', 'Thủ tục tạm ngừng kinh doanh, hoạt động trở lại, giải thể doanh nghiệp.', 'kien-thuc-phap-ly', 'clock', 7 ),
		'thu-tuc-phap-ly-khac'         => array( 'Thủ tục pháp lý khác', 'Hợp đồng, giấy phép con và các thủ tục pháp lý khác của doanh nghiệp.', 'kien-thuc-phap-ly', 'search', 8 ),
		'bai-hoc-ke-toan'              => array( 'Bài học kế toán', 'Bài học kế toán tổng hợp, kế toán thuế, sổ sách theo chế độ kế toán mới – dành cho người mới và chủ doanh nghiệp nhỏ.', '', 'calculator', 9 ),
	),
	'posts'      => array(

		// ===================== KIẾN THỨC =====================
		array(
			'slug'    => 'thu-tuc-thanh-lap-cong-ty',
			'cat'     => array( 'thu-tuc-thanh-lap' ),
			'title'   => 'Thủ tục thành lập công ty năm 2026: hồ sơ, các bước và chi phí',
			'excerpt' => 'Hướng dẫn thành lập công ty theo Luật Doanh nghiệp sửa đổi 2025, Nghị định 168/2025 và Nghị định 296/2026: chọn loại hình, hồ sơ, xác thực điện tử, chủ sở hữu hưởng lợi và việc cần làm sau khi có giấy phép.',
			'content' => $sgd_upd . '
<p>Năm 2026, thủ tục thành lập công ty đã gần như hoàn toàn trực tuyến, nhưng cũng có thêm một số yêu cầu mới mà nhiều người chưa để ý: kê khai <strong>chủ sở hữu hưởng lợi</strong>, <strong>xác thực điện tử</strong> khi uỷ quyền nộp hồ sơ, và việc <strong>không còn lệ phí môn bài</strong>. Bài viết dưới đây đi lần lượt từ khâu chuẩn bị đến lúc công ty bắt đầu hoạt động.</p>

<h2>Căn cứ pháp lý đang áp dụng</h2>
<ul>
<li>Luật Doanh nghiệp 2020, được sửa đổi, bổ sung bởi Luật số 76/2025/QH15 (hiệu lực từ 01/7/2025).</li>
<li>Nghị định 168/2025/NĐ-CP về đăng ký doanh nghiệp (hiệu lực từ 01/7/2025), được sửa đổi bởi Nghị định 296/2026/NĐ-CP (hiệu lực từ 23/7/2026).</li>
<li>Nghị quyết 198/2025/QH15: chấm dứt thu, nộp lệ phí môn bài từ 01/01/2026.</li>
</ul>

<h2>Bước 1: Quyết định những nội dung cơ bản</h2>
<h3>Loại hình doanh nghiệp</h3>
<p>Phần lớn doanh nghiệp nhỏ chọn một trong hai loại hình:</p>
<ul>
<li><strong>Công ty TNHH</strong> (một thành viên hoặc từ hai đến 50 thành viên): chủ sở hữu chỉ chịu trách nhiệm trong phạm vi vốn góp, bộ máy gọn, phù hợp với công ty gia đình hoặc vài người cùng góp vốn. Xem chi tiết tại [[thanh-lap-cong-ty-tnhh|dịch vụ thành lập công ty TNHH]].</li>
<li><strong>Công ty cổ phần</strong>: tối thiểu 3 cổ đông, được phát hành cổ phần để gọi vốn, chuyển nhượng linh hoạt – phù hợp khi có kế hoạch mở rộng. Tham khảo [[thanh-lap-cong-ty-co-phan|thủ tục thành lập công ty cổ phần]].</li>
</ul>
<p>Nếu chỉ buôn bán nhỏ, chưa cần xuất hoá đơn cho doanh nghiệp, bạn có thể cân nhắc [[dang-ky-ho-kinh-doanh|đăng ký hộ kinh doanh]] trước rồi chuyển lên công ty khi quy mô lớn hơn.</p>
<h3>Tên công ty, trụ sở, ngành nghề, vốn điều lệ</h3>
<ul>
<li><strong>Tên</strong> không được trùng hoặc gây nhầm lẫn với doanh nghiệp đã đăng ký – nên tra trước trên Cổng thông tin quốc gia về đăng ký doanh nghiệp và chuẩn bị 2–3 phương án.</li>
<li><strong>Trụ sở</strong> phải có địa chỉ rõ ràng; không đặt tại căn hộ chung cư chỉ có chức năng để ở. Sau đợt sắp xếp đơn vị hành chính năm 2025, hãy ghi địa chỉ theo tên xã, phường, tỉnh mới.</li>
<li><strong>Ngành nghề</strong> ghi theo mã ngành kinh tế Việt Nam. Với ngành kinh doanh có điều kiện, cần kiểm tra điều kiện (vốn, chứng chỉ, giấy phép con) trước khi nộp.</li>
<li><strong>Vốn điều lệ</strong>: đa số ngành không yêu cầu vốn tối thiểu, nhưng thành viên, cổ đông phải góp đủ trong 90 ngày kể từ ngày được cấp giấy chứng nhận đăng ký doanh nghiệp.</li>
</ul>

<h2>Bước 2: Xác định chủ sở hữu hưởng lợi</h2>
<p>Từ Luật Doanh nghiệp sửa đổi 2025, người thành lập doanh nghiệp phải tự xác định và kê khai <strong>chủ sở hữu hưởng lợi</strong>. Theo Nghị định 296/2026, đó là cá nhân (một hoặc nhiều người) xác định theo thứ tự:</p>
<ol>
<li>Cá nhân sở hữu trực tiếp, gián tiếp hoặc kết hợp cả hai <strong>từ 25% vốn điều lệ</strong> (hoặc 25% tổng số cổ phần có quyền biểu quyết) trở lên. Nhóm người có quan hệ gia đình hoặc cùng ký hợp đồng sở hữu chung từ 25% trở lên cũng được xác định là chủ sở hữu hưởng lợi – tránh việc chia nhỏ tỷ lệ.</li>
<li>Nếu không có ai đạt tiêu chí trên: cá nhân có quyền chi phối thực tế (bổ nhiệm, miễn nhiệm giám đốc, hội đồng thành viên; sửa điều lệ; quyết định tài chính, đầu tư…).</li>
<li>Nếu vẫn không xác định được: kê khai người quản lý có quyền hạn lớn nhất của doanh nghiệp.</li>
</ol>
<p>Nghị định 296/2026 cũng quy định rõ thành viên, cổ đông phải góp vốn thật và <strong>không được đứng tên thay người khác</strong>.</p>

<h2>Bước 3: Chuẩn bị hồ sơ</h2>
<ol>
<li>Giấy đề nghị đăng ký doanh nghiệp.</li>
<li>Điều lệ công ty.</li>
<li>Danh sách thành viên (công ty TNHH hai thành viên trở lên) hoặc danh sách cổ đông sáng lập (công ty cổ phần).</li>
<li>Thông tin về chủ sở hữu hưởng lợi.</li>
<li>Giấy tờ pháp lý của cá nhân (thẻ căn cước) hoặc của tổ chức góp vốn.</li>
<li>Văn bản uỷ quyền cho người nộp hồ sơ (nếu không tự nộp).</li>
</ol>

<h2>Bước 4: Nộp hồ sơ trực tuyến và xác thực điện tử</h2>
<p>Hồ sơ nộp qua Cổng thông tin quốc gia về đăng ký doanh nghiệp; từ Nghị định 296/2026 có thể đăng nhập bằng tài khoản Cổng Dịch vụ công quốc gia hoặc ứng dụng định danh quốc gia (VNeID). Một số điểm mới giúp hồ sơ gọn hơn:</p>
<ul>
<li>Giấy đề nghị chỉ cần một người ký và đã kê khai trực tuyến thì không phải ký số, ký tay rồi tải lên nữa.</li>
<li>Cơ quan đăng ký kinh doanh tự khai thác giấy tờ đã có trong cơ sở dữ liệu quốc gia (giấy chứng nhận đăng ký doanh nghiệp của tổ chức góp vốn, giấy chứng nhận đầu tư…), không yêu cầu nộp bản sao.</li>
<li>Khi uỷ quyền cho người khác nộp hồ sơ, <strong>cả người uỷ quyền và người được uỷ quyền phải xác thực điện tử</strong>. Vì vậy, dù dùng dịch vụ, bạn vẫn cần thao tác xác nhận trên tài khoản định danh của mình – chỉ mất vài phút.</li>
</ul>
<p>Cơ quan đăng ký kinh doanh giải quyết trong <strong>3 ngày làm việc</strong> kể từ khi nhận hồ sơ hợp lệ. Lệ phí đăng ký doanh nghiệp hiện đang được giảm 50% (còn 25.000 đồng/lần) đến hết ngày 31/12/2026 theo Thông tư 64/2025/TT-BTC.</p>

<h2>Bước 5: Việc cần làm ngay sau khi có giấy phép</h2>
<ul>
<li>Khắc con dấu (nếu cần), treo biển hiệu tại trụ sở.</li>
<li>Mở tài khoản ngân hàng đứng tên công ty – bắt buộc để thanh toán không dùng tiền mặt các khoản từ 5 triệu đồng và được khấu trừ thuế.</li>
<li>Mua chữ ký số, đăng ký hoá đơn điện tử – có thể làm trọn gói qua [[chu-ky-so-hoa-don-dien-tu|dịch vụ chữ ký số và hoá đơn điện tử]].</li>
<li>Kê khai thuế ban đầu, chọn phương pháp tính thuế GTGT, kỳ khai thuế. Từ năm 2026 doanh nghiệp mới <strong>không phải khai và nộp lệ phí môn bài</strong>. Chi tiết xem [[khai-thue-ban-dau|dịch vụ khai thuế ban đầu]].</li>
<li>Góp đủ vốn trong 90 ngày và lưu chứng từ góp vốn.</li>
<li>Tổ chức sổ sách kế toán, nộp tờ khai đúng hạn ngay từ tháng/quý đầu tiên, kể cả khi chưa phát sinh doanh thu.</li>
</ul>
<p>Nhiều doanh nghiệp mới bị phạt chỉ vì quên nộp tờ khai trong những tháng đầu. Nếu chưa có kế toán, gói [[ke-toan-tron-goi|kế toán trọn gói]] sẽ lo phần này ngay từ khi công ty vừa thành lập.</p>

<h2>Câu hỏi thường gặp</h2>
<h3>Thành lập công ty mất bao lâu?</h3>
<p>Hồ sơ hợp lệ được giải quyết trong 3 ngày làm việc. Tính cả thời gian soạn hồ sơ, xác thực và khắc dấu, thông thường mất khoảng 3–5 ngày làm việc.</p>
<h3>Có cần vốn tối thiểu không?</h3>
<p>Không, trừ ngành nghề có quy định vốn pháp định hoặc ký quỹ. Tuy nhiên vốn điều lệ là con số bạn cam kết góp thật, nên chọn mức phù hợp với hoạt động thực tế.</p>
<p>Bạn muốn được tư vấn loại hình và báo giá trọn gói? Xem các gói [[nhom:thanh-lap-doanh-nghiep|dịch vụ thành lập công ty]] hoặc để lại số điện thoại, chuyên viên sẽ gọi lại trong 15 phút.</p>',
		),

		array(
			'slug'    => 'lich-nop-to-khai-thue',
			'cat'     => array( 'bao-cao-thue-tai-chinh' ),
			'title'   => 'Lịch nộp tờ khai thuế năm 2026 cho doanh nghiệp theo Luật Quản lý thuế mới',
			'excerpt' => 'Thời hạn nộp tờ khai thuế tháng, quý, quyết toán năm theo Luật Quản lý thuế 2025 và Nghị định 252/2026; ai được khai theo quý, lệ phí môn bài đã bãi bỏ và lưu ý tạm nộp thuế TNDN.',
			'content' => $sgd_upd . '
<p>Luật Quản lý thuế số 108/2025/QH15 có hiệu lực từ 01/7/2026, đi kèm Nghị định 252/2026/NĐ-CP và Thông tư 89/2026/TT-BTC. Khung thời hạn nộp hồ sơ khai thuế về cơ bản vẫn giữ như trước, nhưng có vài thay đổi đáng chú ý – đặc biệt là việc <strong>bãi bỏ lệ phí môn bài</strong> từ năm 2026. Dưới đây là lịch để kế toán và chủ doanh nghiệp đánh dấu.</p>

<h2>Thời hạn nộp hồ sơ khai thuế</h2>
<table>
<thead><tr><th>Loại tờ khai</th><th>Hạn nộp</th></tr></thead>
<tbody>
<tr><td>Khai theo tháng (GTGT, TNCN khấu trừ…)</td><td>Chậm nhất ngày 20 của tháng tiếp theo</td></tr>
<tr><td>Khai theo quý</td><td>Chậm nhất ngày cuối cùng của tháng đầu quý tiếp theo</td></tr>
<tr><td>Quyết toán thuế TNDN, quyết toán TNCN (tổ chức trả thu nhập), báo cáo tài chính năm</td><td>Chậm nhất ngày cuối cùng của tháng thứ 3 kể từ khi kết thúc năm tài chính</td></tr>
<tr><td>Cá nhân tự quyết toán thuế TNCN từ tiền lương, tiền công</td><td>Chậm nhất ngày cuối cùng của tháng thứ 4 kể từ khi kết thúc năm dương lịch</td></tr>
<tr><td>Khai theo từng lần phát sinh</td><td>Chậm nhất ngày thứ 10 kể từ ngày phát sinh nghĩa vụ thuế</td></tr>
<tr><td>Chấm dứt hoạt động, giải thể, tổ chức lại doanh nghiệp</td><td>Chậm nhất ngày thứ 45 kể từ ngày có quyết định</td></tr>
</tbody>
</table>
<p>Hạn nộp tiền thuế trùng với hạn nộp tờ khai. Khai theo năm (với loại thuế khai năm): chậm nhất ngày cuối cùng của tháng đầu tiên của năm tiếp theo. Bảng trên theo Điều 10 Nghị định 252/2026/NĐ-CP.</p>

<h2>Lịch cụ thể cho doanh nghiệp khai theo quý (năm tài chính theo năm dương lịch)</h2>
<ul>
<li><strong>Quý I/2026</strong>: hạn 30/4/2026.</li>
<li><strong>Quý II/2026</strong>: hạn 31/7/2026.</li>
<li><strong>Quý III/2026</strong>: hạn 31/10/2026 (thứ Bảy) – nên nộp trong ngày làm việc trước đó.</li>
<li><strong>Quý IV/2026</strong>: hạn 31/01/2027 (Chủ nhật) – nên nộp trong ngày làm việc trước đó.</li>
<li><strong>Quyết toán năm 2026</strong> và báo cáo tài chính: hạn 31/3/2027.</li>
</ul>

<h2>Ai được khai thuế GTGT theo quý?</h2>
<p>Doanh nghiệp có tổng doanh thu bán hàng hoá, cung cấp dịch vụ của năm trước liền kề <strong>từ 50 tỷ đồng trở xuống</strong> được lựa chọn khai theo quý. Doanh nghiệp mới bắt đầu hoạt động cũng được chọn khai theo quý. Khai theo quý giúp giảm số lần nộp tờ khai, phù hợp với doanh nghiệp nhỏ; khi doanh thu vượt ngưỡng, doanh nghiệp chuyển sang khai theo tháng từ năm tiếp theo. Nếu bạn chưa chắc nên chọn kỳ nào, bộ phận [[bao-cao-thue-hang-thang-quy|báo cáo thuế hằng tháng, quý]] có thể rà soát giúp.</p>

<h2>Thuế TNDN: không khai tạm tính nhưng phải tạm nộp</h2>
<p>Doanh nghiệp không nộp tờ khai thuế TNDN hằng quý, nhưng phải <strong>tạm nộp</strong> thuế theo quý, chậm nhất ngày cuối cùng của tháng đầu quý sau (ví dụ tạm nộp quý III/2026 trước 31/10/2026). Tổng số đã tạm nộp của 4 quý không được thấp hơn <strong>80%</strong> số thuế phải nộp theo quyết toán năm; nếu thấp hơn, phần chênh lệch bị tính tiền chậm nộp. Từ kỳ tính thuế 2025, thuế suất TNDN là 15% (doanh thu năm đến 3 tỷ đồng), 17% (trên 3 tỷ đến 50 tỷ đồng) và 20% với các doanh nghiệp còn lại, theo Luật Thuế TNDN số 67/2025/QH15.</p>

<h2>Lệ phí môn bài: không còn phải khai, nộp từ năm 2026</h2>
<p>Nghị quyết 198/2025/QH15 chấm dứt việc thu, nộp lệ phí môn bài từ 01/01/2026; Nghị định 362/2025/NĐ-CP đã bãi bỏ các nghị định về lệ phí môn bài. Doanh nghiệp, hộ kinh doanh không phải nộp tờ khai và tiền lệ phí môn bài cho năm 2026 trở đi. Riêng nghĩa vụ lệ phí môn bài của năm 2025 trở về trước (nếu còn nợ) vẫn phải hoàn thành.</p>

<h2>Khai sai thì được sửa trong bao lâu?</h2>
<p>Luật Quản lý thuế 2025 cho phép khai bổ sung trong <strong>5 năm</strong> kể từ ngày hết hạn nộp hồ sơ của kỳ có sai sót (trước đây là 10 năm), với điều kiện chưa có quyết định thanh tra, kiểm tra kỳ đó. Phát hiện sai càng sớm, tiền chậm nộp càng ít.</p>

<h2>Những lỗi hay gặp khiến doanh nghiệp bị phạt</h2>
<ul>
<li>Không nộp tờ khai vì nghĩ “chưa có doanh thu thì không cần khai” – thực tế vẫn phải nộp tờ khai, ghi số 0.</li>
<li>Nộp đúng hạn tờ khai nhưng quên nộp tiền thuế, phát sinh tiền chậm nộp theo ngày.</li>
<li>Quyết toán năm lệch với số tạm nộp quá 20%.</li>
<li>Không nộp báo cáo tài chính cùng quyết toán.</li>
</ul>
<p>Muốn không phải nhớ từng mốc? Gói [[ke-toan-tron-goi|kế toán trọn gói]] theo dõi toàn bộ lịch nộp và gửi nhắc trước hạn; cuối năm có [[quyet-toan-thue-cuoi-nam|dịch vụ quyết toán thuế]] và [[bao-cao-tai-chinh|lập báo cáo tài chính]] riêng nếu bạn chỉ cần hỗ trợ một lần.</p>',
		),

		array(
			'slug'    => 'khi-nao-phai-thay-doi-giay-phep-kinh-doanh',
			'cat'     => array( 'thay-doi-dang-ky-kinh-doanh', 'tam-ngung-giai-the' ),
			'title'   => 'Khi nào doanh nghiệp phải đăng ký thay đổi giấy phép kinh doanh?',
			'excerpt' => 'Những thay đổi bắt buộc phải đăng ký trong 10 ngày, trường hợp địa chỉ thay đổi do sắp xếp đơn vị hành chính, quy định mới về tạm ngừng kinh doanh và xác thực điện tử theo Nghị định 296/2026.',
			'content' => $sgd_upd . '
<p>“Giấy phép kinh doanh” mà mọi người hay gọi thực chất là <strong>Giấy chứng nhận đăng ký doanh nghiệp</strong>. Mỗi khi thông tin trên giấy này – hoặc một số thông tin chỉ có trên hệ thống đăng ký – thay đổi, doanh nghiệp phải đăng ký hoặc thông báo với cơ quan đăng ký kinh doanh. Làm chậm có thể bị xử phạt hành chính và gây vướng mắc khi ký hợp đồng, xuất hoá đơn, giao dịch ngân hàng.</p>

<h2>Thời hạn: 10 ngày kể từ ngày có thay đổi</h2>
<p>Theo Luật Doanh nghiệp, doanh nghiệp phải đăng ký thay đổi nội dung Giấy chứng nhận đăng ký doanh nghiệp trong <strong>10 ngày</strong> kể từ ngày có thay đổi (ngày ra quyết định của chủ sở hữu, hội đồng thành viên hoặc đại hội đồng cổ đông). Hồ sơ hợp lệ được giải quyết trong 3 ngày làm việc.</p>

<h2>Các thay đổi phải đăng ký</h2>
<h3>Thay đổi nội dung trên giấy chứng nhận</h3>
<ul>
<li><strong>Tên doanh nghiệp</strong> – kể cả tên tiếng nước ngoài, tên viết tắt. Đổi tên kéo theo việc cập nhật con dấu, hoá đơn, hợp đồng; xem [[doi-ten-cong-ty|dịch vụ đổi tên công ty]].</li>
<li><strong>Địa chỉ trụ sở chính</strong> – chuyển sang địa chỉ mới, kể cả chuyển sang tỉnh khác. Lưu ý chuyển khác tỉnh còn phải chốt thuế ở cơ quan thuế cũ. Tham khảo [[thay-doi-dia-chi-cong-ty|dịch vụ thay đổi địa chỉ công ty]].</li>
<li><strong>Người đại diện theo pháp luật</strong> – thay người, thêm người hoặc thay đổi thông tin cá nhân. Xem [[doi-dai-dien-phap-luat|thủ tục đổi người đại diện theo pháp luật]].</li>
<li><strong>Vốn điều lệ</strong> – tăng vốn hoặc giảm vốn; với giảm vốn cần đáp ứng điều kiện về thanh toán nợ. Chi tiết tại [[tang-giam-von-dieu-le|dịch vụ tăng, giảm vốn điều lệ]].</li>
<li><strong>Thành viên công ty TNHH, chủ sở hữu</strong> – chuyển nhượng, tặng cho, thừa kế phần vốn góp, thêm hoặc bớt thành viên. Xem [[them-giam-thanh-vien-co-dong|thêm, giảm thành viên – cổ đông]].</li>
</ul>
<h3>Thay đổi phải thông báo (không cấp lại giấy chứng nhận)</h3>
<ul>
<li><strong>Ngành, nghề kinh doanh</strong> – bổ sung hoặc rút bớt ngành; nên làm trước khi xuất hoá đơn cho hoạt động mới. Xem [[bo-sung-nganh-nghe-kinh-doanh|dịch vụ bổ sung ngành nghề]].</li>
<li>Cổ đông sáng lập chưa góp vốn, cổ đông là nhà đầu tư nước ngoài.</li>
<li>Thông tin chủ sở hữu hưởng lợi (cá nhân sở hữu từ 25% vốn điều lệ trở lên hoặc có quyền chi phối thực tế) – theo Luật Doanh nghiệp sửa đổi 2025 và Nghị định 296/2026, doanh nghiệp phải cập nhật khi có thay đổi.</li>
<li>Thông tin đăng ký thuế, thông tin liên lạc, thay đổi giấy tờ pháp lý cá nhân của người quản lý (ví dụ chuyển từ CMND/căn cước công dân sang thẻ căn cước) – có thể làm kèm [[cap-nhat-cccd-dang-ky-kinh-doanh|cập nhật căn cước trên đăng ký kinh doanh]].</li>
</ul>
<h3>Chuyển đổi loại hình</h3>
<p>Ví dụ công ty TNHH một thành viên nhận thêm người góp vốn thành công ty TNHH hai thành viên, hoặc chuyển thành công ty cổ phần để gọi vốn. Đây là thủ tục riêng, cần chuẩn bị điều lệ mới – xem [[chuyen-doi-loai-hinh-cong-ty|chuyển đổi loại hình công ty]].</p>

<h2>Địa chỉ đổi do sáp nhập tỉnh, xã: có bắt buộc làm thủ tục?</h2>
<p><strong>Không bắt buộc.</strong> Sau đợt sắp xếp đơn vị hành chính năm 2025, doanh nghiệp được tiếp tục sử dụng giấy chứng nhận đã cấp; cơ quan đăng ký kinh doanh không được yêu cầu doanh nghiệp đăng ký thay đổi địa chỉ vì lý do này. Doanh nghiệp có thể cập nhật khi có nhu cầu hoặc làm cùng lúc với một thay đổi khác, và <strong>không phải nộp lệ phí</strong> cho việc cập nhật địa chỉ do thay đổi địa giới hành chính. Tuy vậy, nên ghi địa chỉ mới trên hoá đơn, hợp đồng để thống nhất với dữ liệu thuế.</p>

<h2>Điểm mới theo Nghị định 296/2026/NĐ-CP</h2>
<ul>
<li><strong>Xác thực điện tử</strong>: khi uỷ quyền đăng ký thay đổi người đại diện theo pháp luật, chủ sở hữu, thành viên công ty TNHH, cổ đông sáng lập, cổ đông là nhà đầu tư nước ngoài (công ty cổ phần chưa niêm yết), chủ doanh nghiệp tư nhân, thành viên hợp danh – cả người uỷ quyền và người được uỷ quyền phải xác thực điện tử.</li>
<li><strong>Không phải nộp lại giấy tờ</strong> mà cơ quan đăng ký kinh doanh đã có trong cơ sở dữ liệu quốc gia.</li>
<li><strong>Tạm ngừng kinh doanh</strong>: tổng thời gian tạm ngừng liên tiếp không quá 24 tháng; thông báo tạm ngừng phải có số điện thoại, email của người đại diện theo pháp luật. Trong 5 ngày làm việc sau khi hết thời hạn tạm ngừng, người đại diện phải xác nhận trên hệ thống việc kinh doanh trở lại – không xác nhận có thể bị yêu cầu báo cáo và tiến tới thu hồi giấy chứng nhận. Trong thời gian tạm ngừng, nếu có thay đổi thông tin vẫn phải đăng ký thay đổi. Xem [[tam-ngung-kinh-doanh|dịch vụ tạm ngừng kinh doanh]].</li>
<li>Siết việc đứng tên góp vốn thay người khác – thông tin thành viên, cổ đông, chủ sở hữu hưởng lợi phải phản ánh đúng thực tế.</li>
</ul>

<h2>Sau khi đăng ký thay đổi cần làm gì?</h2>
<ul>
<li>Cập nhật thông tin trên hoá đơn điện tử, chữ ký số, tài khoản ngân hàng.</li>
<li>Thông báo cho khách hàng, đối tác; điều chỉnh hợp đồng đang thực hiện nếu cần.</li>
<li>Với thay đổi địa chỉ khác tỉnh: hoàn tất thủ tục thuế ở nơi đi trước khi đăng ký ở nơi đến.</li>
</ul>
<p>Bạn có nhiều thay đổi cùng lúc? Có thể gộp trong một bộ hồ sơ để tiết kiệm thời gian. Xem toàn bộ [[nhom:thay-doi-giay-phep|dịch vụ thay đổi giấy phép kinh doanh]] hoặc gọi hotline để được rà soát miễn phí.</p>',
		),

		array(
			'slug'    => 'thue-ho-kinh-doanh-2026-bo-thue-khoan',
			'cat'     => array( 'thue-ho-kinh-doanh', 'ho-kinh-doanh-ca-the' ),
			'title'   => 'Thuế hộ kinh doanh năm 2026: bỏ thuế khoán, ngưỡng 1 tỷ đồng và cách tính mới',
			'excerpt' => 'Từ 2026 hộ kinh doanh không còn nộp thuế khoán, doanh thu đến 1 tỷ đồng/năm không phải nộp thuế GTGT, TNCN (Nghị định 141/2026). Cách tính thuế theo tỷ lệ hoặc theo thu nhập, hoá đơn điện tử và sổ sách cần có.',
			'content' => $sgd_upd . '
<p>Năm 2026 là năm thay đổi lớn nhất về thuế đối với hộ kinh doanh trong nhiều năm: <strong>thuế khoán bị bãi bỏ</strong>, <strong>lệ phí môn bài không còn</strong>, và mức doanh thu không phải nộp thuế được nâng lên <strong>1 tỷ đồng/năm</strong> theo Nghị định 141/2026/NĐ-CP (ban hành 29/4/2026, áp dụng từ 01/01/2026). Bài viết tóm tắt những gì hộ kinh doanh cần biết theo Luật Thuế TNCN số 109/2025/QH15, Nghị định 68/2026/NĐ-CP (hiệu lực từ 05/3/2026) đã sửa đổi bởi Nghị định 141/2026/NĐ-CP, Thông tư 152/2025/TT-BTC về kế toán hộ kinh doanh và Luật Quản lý thuế 2025.</p>

<h2>1. Bỏ thuế khoán – hộ kinh doanh tự khai, tự nộp</h2>
<p>Từ 01/01/2026, cơ quan thuế không còn ấn định mức thuế khoán. Mọi hộ, cá nhân kinh doanh chuyển sang <strong>tự kê khai doanh thu</strong> và tự tính, nộp thuế. Điều này đồng nghĩa hộ kinh doanh phải ghi chép doanh thu (và chi phí, nếu tính thuế theo thu nhập) một cách có hệ thống thay vì chỉ nộp một khoản cố định như trước.</p>

<h2>2. Doanh thu đến 1 tỷ đồng/năm: không phải nộp thuế</h2>
<p>Hộ, cá nhân kinh doanh có doanh thu năm <strong>từ 1 tỷ đồng trở xuống</strong> không phải nộp thuế GTGT và thuế TNCN – gấp 10 lần mức 100 triệu đồng/năm áp dụng đến hết 2025 (mức 500 triệu đồng tại Nghị định 68/2026 đã được Nghị định 141/2026 nâng lên 1 tỷ đồng, áp dụng ngay từ 01/01/2026). Tuy vậy, hộ vẫn phải <strong>thông báo doanh thu thực tế</strong> của năm với cơ quan thuế, chậm nhất ngày 31/01 năm sau. Hộ mới ra kinh doanh trong 6 tháng đầu năm thông báo doanh thu đến 30/6 chậm nhất ngày 31/7.</p>
<p>Doanh thu tính cả tiền thưởng, hỗ trợ đạt doanh số, khuyến mại, chiết khấu thanh toán, tiền bồi thường liên quan đến kinh doanh; không gồm chiết khấu thương mại, giảm giá và hàng bán bị trả lại. Khi doanh thu vượt 1 tỷ đồng, hộ phải khai, nộp thuế kể từ quý phát sinh doanh thu vượt ngưỡng.</p>

<h2>3. Doanh thu trên 1 tỷ đến 3 tỷ đồng: được chọn cách tính</h2>
<ul>
<li><strong>Cách 1 – theo tỷ lệ trên doanh thu</strong>: thuế TNCN = tỷ lệ theo ngành × (doanh thu − 1 tỷ đồng).</li>
<li><strong>Cách 2 – theo thu nhập</strong>: thuế TNCN = (doanh thu − chi phí được trừ) × <strong>15%</strong>. Cách này có lợi khi chi phí lớn (biên lợi nhuận thấp) nhưng đòi hỏi hoá đơn, chứng từ chi phí đầy đủ; đã chọn thì phải áp dụng ổn định <strong>2 năm liên tục</strong>.</li>
</ul>
<table>
<thead><tr><th>Ngành nghề</th><th>Tỷ lệ thuế TNCN (cách 1)</th></tr></thead>
<tbody>
<tr><td>Phân phối, cung cấp hàng hoá</td><td>0,5%</td></tr>
<tr><td>Dịch vụ, xây dựng không bao thầu nguyên vật liệu</td><td>2%</td></tr>
<tr><td>Cho thuê tài sản, đại lý bảo hiểm, đại lý xổ số, bán hàng đa cấp</td><td>5%</td></tr>
<tr><td>Sản xuất, vận tải, dịch vụ gắn với hàng hoá, xây dựng có bao thầu nguyên vật liệu</td><td>1,5%</td></tr>
<tr><td>Nội dung số (trò chơi điện tử, phim, ảnh, nhạc, quảng cáo số…)</td><td>5%</td></tr>
<tr><td>Hoạt động kinh doanh khác</td><td>1%</td></tr>
</tbody>
</table>
<p><em>Ví dụ:</em> cửa hàng tạp hoá doanh thu 1,6 tỷ đồng/năm chọn cách 1: thuế TNCN = (1.600 − 1.000) triệu × 0,5% = 3 triệu đồng/năm. Thuế GTGT của hộ có doanh thu trên 1 tỷ đồng tính trên <strong>toàn bộ doanh thu</strong> bằng tỷ lệ % theo ngành nghề (phân phối hàng hoá 1%, dịch vụ 5%, sản xuất – vận tải 3%, khác 2%).</p>
<p>Cá nhân cho thuê bất động sản (trừ kinh doanh lưu trú) nộp thuế TNCN 5% trên phần doanh thu vượt 1 tỷ đồng/năm (thuế GTGT 5% tính trên toàn bộ doanh thu khi vượt ngưỡng); có thể khai 2 lần/năm (hạn 31/7 và 31/01 năm sau) hoặc 1 lần (hạn 31/01 năm sau).</p>

<h2>4. Doanh thu trên 3 tỷ đồng: bắt buộc tính theo thu nhập</h2>
<p>Hộ kinh doanh có doanh thu năm trên 3 tỷ đồng phải tính thuế TNCN theo thu nhập: thuế suất <strong>17%</strong> (trên 3 tỷ đến 50 tỷ đồng) hoặc <strong>20%</strong> (trên 50 tỷ đồng) – tương đương thuế suất TNDN của doanh nghiệp. Hộ phải khai thuế theo tháng hoặc quý và quyết toán thuế TNCN năm chậm nhất ngày 31/3 năm sau.</p>
<h3>Chi phí nào được trừ?</h3>
<p>Chi phí thực tế phát sinh, có hoá đơn, chứng từ; khoản thanh toán từng lần từ 5 triệu đồng phải chuyển khoản. Được trừ: nguyên vật liệu, hàng hoá; lương, bảo hiểm của người lao động; khấu hao tài sản cố định; điện, nước, internet, thuê mặt bằng; lãi vay… <strong>Không được trừ</strong>: tiền lương của chính chủ hộ và các thành viên trong hộ, chi phí sinh hoạt gia đình, tiền phạt, khoản chi không có chứng từ, nhà ở và xe đứng tên cá nhân không dùng để kinh doanh vận tải, du lịch.</p>

<h2>5. Hoá đơn điện tử</h2>
<p>Theo Nghị định 68/2026/NĐ-CP, hộ, cá nhân kinh doanh có doanh thu năm <strong>trên 1 tỷ đồng</strong> phải dùng hoá đơn điện tử có mã của cơ quan thuế hoặc <strong>hoá đơn điện tử khởi tạo từ máy tính tiền</strong> kết nối dữ liệu với cơ quan thuế; đăng ký khi doanh thu trong năm vượt 1 tỷ đồng. Có nhiều cửa hàng thì dùng chung mã số thuế, ghi rõ địa chỉ từng điểm trên hoá đơn. Hộ có doanh thu đến 1 tỷ đồng không bắt buộc nhưng được đăng ký dùng nếu khách hàng cần hoá đơn. Xem [[hoa-don-dien-tu|dịch vụ hoá đơn điện tử]].</p>

<h2>6. Sổ sách hộ kinh doanh cần có</h2>
<p>Thông tư 152/2025/TT-BTC (hiệu lực 01/01/2026) hướng dẫn kế toán cho hộ kinh doanh:</p>
<ul>
<li>Chủ hộ tự ghi sổ, hoặc giao cho người thân (cha mẹ, vợ chồng, con, anh chị em ruột), thủ kho, thủ quỹ kiêm nhiệm, hoặc thuê dịch vụ kế toán.</li>
<li>Sổ doanh thu với mọi hộ; thêm sổ chi phí, hàng tồn kho, tiền với hộ tính thuế theo thu nhập. Được tự bổ sung, sửa mẫu sổ cho phù hợp.</li>
<li>Lưu tài liệu kế toán (bản giấy hoặc điện tử) tối thiểu 5 năm.</li>
</ul>
<p>Nếu bạn chưa quen ghi chép, gói [[ke-toan-ho-kinh-doanh|kế toán hộ kinh doanh]] sẽ lập sổ, khai doanh thu và tính thuế theo phương pháp có lợi nhất cho bạn.</p>

<h2>Có nên chuyển từ hộ kinh doanh lên công ty?</h2>
<p>Khi doanh thu vượt 3 tỷ đồng, hộ kinh doanh đã phải làm sổ sách gần như doanh nghiệp, trong khi vẫn chịu trách nhiệm vô hạn bằng toàn bộ tài sản cá nhân. Lúc này, thành lập công ty TNHH thường hợp lý hơn: chịu trách nhiệm trong phạm vi vốn góp, dễ ký hợp đồng với doanh nghiệp lớn, được khấu trừ thuế GTGT đầu vào. Tham khảo [[thanh-lap-cong-ty-tnhh|dịch vụ thành lập công ty TNHH]], hoặc nếu mới bắt đầu, xem [[dang-ky-ho-kinh-doanh|thủ tục đăng ký hộ kinh doanh]].</p>',
		),

		array(
			'slug'    => 'hoa-don-dien-tu-2026-quy-dinh-moi',
			'cat'     => array( 'hoa-don-chung-tu', 'thue-gtgt' ),
			'title'   => 'Hoá đơn điện tử năm 2026: những quy định doanh nghiệp cần nắm',
			'excerpt' => 'Quy định hoá đơn điện tử theo Nghị định 70/2025 (sửa Nghị định 123/2020): thời điểm lập, xử lý hoá đơn sai sót, hoá đơn từ máy tính tiền, điều kiện khấu trừ khi thanh toán từ 5 triệu đồng và thuế suất 8%.',
			'content' => $sgd_upd . '
<p>Hoá đơn điện tử là chứng từ quan trọng nhất của doanh nghiệp: sai một hoá đơn có thể làm mất quyền khấu trừ thuế của khách hàng, bị loại chi phí hoặc bị xử phạt. Từ ngày 01/6/2025, Nghị định 70/2025/NĐ-CP sửa đổi nhiều điểm của Nghị định 123/2020/NĐ-CP. Dưới đây là những quy định đang áp dụng mà kế toán và chủ doanh nghiệp nên nắm.</p>

<h2>1. Ai phải dùng hoá đơn điện tử?</h2>
<ul>
<li><strong>Doanh nghiệp, tổ chức kinh tế</strong>: dùng hoá đơn điện tử khi bán hàng hoá, cung cấp dịch vụ, kể cả bán cho người tiêu dùng không lấy hoá đơn.</li>
<li><strong>Hộ, cá nhân kinh doanh</strong> có doanh thu năm trên 1 tỷ đồng: dùng hoá đơn điện tử có mã của cơ quan thuế hoặc hoá đơn khởi tạo từ máy tính tiền (Nghị định 68/2026, sửa đổi bởi Nghị định 141/2026). Từ 01/7/2026, hộ đăng ký dùng hoá đơn điện tử lần đầu sẽ được cơ quan thuế kiểm tra, xác thực thông tin trước khi chấp nhận.</li>
</ul>
<p>Để xuất hoá đơn, doanh nghiệp cần có chữ ký số và đăng ký với cơ quan thuế qua nhà cung cấp hoá đơn. Có thể làm trọn bộ qua [[chu-ky-so-hoa-don-dien-tu|dịch vụ chữ ký số và hoá đơn điện tử]].</p>

<h2>2. Thời điểm lập hoá đơn</h2>
<ul>
<li><strong>Bán hàng hoá</strong>: thời điểm chuyển giao quyền sở hữu hoặc quyền sử dụng cho người mua, không phân biệt đã thu tiền hay chưa.</li>
<li><strong>Cung cấp dịch vụ</strong>: thời điểm hoàn thành việc cung cấp dịch vụ; nếu thu tiền trước hoặc trong khi cung cấp dịch vụ thì lập hoá đơn tại thời điểm thu tiền (trừ một số trường hợp thu tiền đặt cọc, tạm ứng để bảo đảm thực hiện hợp đồng).</li>
<li>Hoạt động cung cấp dịch vụ số lượng lớn, phát sinh thường xuyên (điện, nước, viễn thông…) có quy định riêng về lập hoá đơn định kỳ.</li>
</ul>

<h2>3. Xử lý hoá đơn có sai sót</h2>
<p>Nghị định 70/2025 thống nhất cách xử lý hoá đơn đã gửi cho người mua mà phát hiện sai:</p>
<ul>
<li><strong>Sai tên, địa chỉ người mua</strong> nhưng đúng mã số thuế và các nội dung khác: thông báo cho người mua, không phải lập lại hoá đơn.</li>
<li><strong>Sai mã số thuế, số tiền, thuế suất, hàng hoá</strong>: lập hoá đơn <strong>điều chỉnh</strong> hoặc hoá đơn <strong>thay thế</strong>. Trường hợp người mua là doanh nghiệp, hai bên cần có văn bản thoả thuận ghi rõ nội dung sai trước khi lập hoá đơn điều chỉnh/thay thế.</li>
</ul>
<p>Nghị định 70/2025 đã <strong>bỏ việc huỷ hoá đơn điện tử</strong> khi có sai sót – thay vào đó dùng hoá đơn điều chỉnh hoặc thay thế (ghi rõ “Điều chỉnh/Thay thế cho hoá đơn mẫu số… ký hiệu… số… ngày…”). Trường hợp sai tên, địa chỉ người mua, người bán thông báo với cơ quan thuế theo Mẫu 04/SS-HĐĐT.</p>

<h2>4. Hoá đơn đầu vào và điều kiện khấu trừ</h2>
<p>Theo Luật Thuế GTGT số 48/2024/QH15 (hiệu lực từ 01/7/2025) và Nghị định 181/2025/NĐ-CP (sửa đổi bởi Nghị định 144/2026/NĐ-CP), hàng hoá, dịch vụ mua vào <strong>từ 5 triệu đồng trở lên</strong> (đã gồm thuế GTGT) phải có chứng từ thanh toán không dùng tiền mặt mới được khấu trừ thuế GTGT. Ngưỡng cũ là 20 triệu đồng. Mua nhiều lần trong ngày của cùng một người bán mà tổng từ 5 triệu đồng trở lên cũng phải chuyển khoản.</p>
<p>Nghị định 320/2025/NĐ-CP áp dụng ngưỡng 5 triệu đồng tương tự cho chi phí được trừ khi tính thuế TNDN, kể cả khoản chi lương từ 15/12/2025 (Công văn 218/CST-TN năm 2026). Lưu ý: nộp tiền mặt trực tiếp vào tài khoản của người bán <strong>không</strong> được coi là thanh toán không dùng tiền mặt.</p>

<h2>5. Thuế suất 8% còn áp dụng đến hết năm 2026</h2>
<p>Theo Nghị quyết 204/2025/QH15 và Nghị định 174/2025/NĐ-CP, hàng hoá, dịch vụ đang chịu thuế suất 10% được giảm còn <strong>8%</strong> từ 01/7/2025 đến hết <strong>31/12/2026</strong>, trừ các nhóm: viễn thông, tài chính, ngân hàng, chứng khoán, bảo hiểm, kinh doanh bất động sản, sản phẩm kim loại, khai khoáng (trừ than), hàng hoá dịch vụ chịu thuế tiêu thụ đặc biệt (trừ xăng). Khi lập hoá đơn, cần chọn đúng thuế suất cho từng dòng hàng.</p>

<h2>6. Lưu trữ và rủi ro thường gặp</h2>
<ul>
<li>Hoá đơn điện tử phải được lưu trữ đúng thời hạn theo pháp luật kế toán; nên sao lưu định kỳ, không chỉ phụ thuộc vào nhà cung cấp.</li>
<li>Kiểm tra trạng thái hoạt động của người bán trước khi nhận hoá đơn lớn, tránh nhận hoá đơn của doanh nghiệp đã ngừng hoạt động.</li>
<li>Đối chiếu hoá đơn đầu ra, đầu vào với tờ khai GTGT hằng kỳ.</li>
</ul>
<p>Doanh nghiệp xuất nhiều hoá đơn mỗi tháng nên giao việc đối chiếu cho kế toán chuyên nghiệp – xem [[ke-toan-tron-goi|dịch vụ kế toán trọn gói]] và [[bao-cao-thue-hang-thang-quy|báo cáo thuế hằng tháng, quý]].</p>',
		),

		// ===================== ĐÀO TẠO =====================
		array(
			'slug'    => 'ke-toan-tong-hop-cho-nguoi-moi',
			'cat'     => array( 'so-sach-ke-toan', 'bai-hoc-ke-toan' ),
			'title'   => 'Kế toán tổng hợp là làm gì? Quy trình công việc cho người mới bắt đầu',
			'excerpt' => 'Công việc của kế toán tổng hợp theo tháng, quý, năm; chọn chế độ kế toán (Thông tư 99/2025, Thông tư 133/2016, Thông tư 58/2026) và những kỹ năng cần có để làm được việc ngay.',
			'content' => $sgd_upd . '
<p>Kế toán tổng hợp là người nắm toàn bộ số liệu của doanh nghiệp: từ chứng từ hằng ngày đến tờ khai thuế, báo cáo tài chính cuối năm. Ở doanh nghiệp nhỏ, một kế toán tổng hợp thường kiêm luôn kế toán thuế. Bài học này giúp người mới hình dung công việc theo từng kỳ và chọn đúng chế độ kế toán đang áp dụng năm 2026.</p>

<h2>1. Chọn chế độ kế toán năm 2026</h2>
<table>
<thead><tr><th>Văn bản</th><th>Đối tượng</th></tr></thead>
<tbody>
<tr><td>Thông tư 99/2025/TT-BTC (hiệu lực 01/01/2026, thay Thông tư 200/2014)</td><td>Doanh nghiệp mọi lĩnh vực, thành phần kinh tế; doanh nghiệp nhỏ và vừa được tự nguyện áp dụng</td></tr>
<tr><td>Thông tư 133/2016/TT-BTC (vẫn còn hiệu lực)</td><td>Doanh nghiệp nhỏ và vừa</td></tr>
<tr><td>Thông tư 58/2026/TT-BTC (thay Thông tư 132/2018)</td><td>Doanh nghiệp siêu nhỏ</td></tr>
</tbody>
</table>
<p>Phần lớn công ty nhỏ hiện tiếp tục dùng Thông tư 133 vì gọn nhẹ. Nếu chuyển sang Thông tư 99, cần áp dụng từ đầu năm tài chính và thông báo cách làm trong quy chế kế toán nội bộ. Xem thêm bài <em>Điểm mới Thông tư 99/2025</em> trong chuyên mục này.</p>

<h2>2. Công việc hằng ngày, hằng tháng</h2>
<ul>
<li>Thu thập, kiểm tra chứng từ: hoá đơn đầu vào, đầu ra, phiếu thu chi, sao kê ngân hàng, phiếu nhập xuất kho.</li>
<li>Hạch toán vào phần mềm kế toán; đối chiếu số dư tiền mặt, tiền gửi ngân hàng.</li>
<li>Theo dõi công nợ phải thu, phải trả; nhắc khách hàng thanh toán.</li>
<li>Tính lương, các khoản trích bảo hiểm, thuế TNCN khấu trừ.</li>
<li>Kiểm tra điều kiện khấu trừ, chi phí được trừ: hoá đơn từ 5 triệu đồng phải có chứng từ chuyển khoản.</li>
</ul>

<h2>3. Công việc theo kỳ khai thuế (tháng hoặc quý)</h2>
<ul>
<li>Lập tờ khai thuế GTGT, tờ khai thuế TNCN khấu trừ (nếu phát sinh).</li>
<li>Tạm tính và tạm nộp thuế TNDN theo quý.</li>
<li>Đối chiếu hoá đơn trên hệ thống của cơ quan thuế với sổ sách.</li>
</ul>
<p>Cách lập tờ khai GTGT được hướng dẫn chi tiết trong bài <em>Hướng dẫn kê khai thuế GTGT theo phương pháp khấu trừ</em>.</p>

<h2>4. Công việc cuối năm</h2>
<ol>
<li>Kiểm kê tiền, hàng tồn kho, tài sản cố định; đối chiếu công nợ.</li>
<li>Trích khấu hao, phân bổ chi phí trả trước, trích lập dự phòng (nếu có).</li>
<li>Kết chuyển doanh thu, chi phí, xác định kết quả kinh doanh.</li>
<li>Lập báo cáo tài chính, quyết toán thuế TNDN, quyết toán thuế TNCN.</li>
<li>In, ký và lưu trữ sổ kế toán.</li>
</ol>

<h2>5. Kỹ năng người mới cần luyện</h2>
<ul>
<li>Đọc hiểu hệ thống tài khoản và định khoản các nghiệp vụ cơ bản.</li>
<li>Thành thạo một phần mềm kế toán và Excel (hàm tra cứu, tổng hợp, bảng tính lương).</li>
<li>Theo dõi văn bản thuế mới – năm 2025–2026 có rất nhiều thay đổi về thuế TNDN, GTGT, TNCN và quản lý thuế.</li>
</ul>
<p>Muốn thực hành trên chứng từ thật thay vì chỉ đọc lý thuyết? [[khoa-hoc-ke-toan-tong-hop|Khoá học kế toán tổng hợp]] đi qua trọn một năm sổ sách của doanh nghiệp, từ chứng từ đến báo cáo tài chính. Còn nếu bạn là chủ doanh nghiệp chưa có kế toán, dùng [[ke-toan-tron-goi|dịch vụ kế toán trọn gói]] sẽ tiết kiệm hơn tuyển một nhân sự toàn thời gian.</p>',
		),

		array(
			'slug'    => 'huong-dan-ke-khai-thue-gtgt-phuong-phap-khau-tru',
			'cat'     => array( 'thue-gtgt', 'bai-hoc-ke-toan' ),
			'title'   => 'Hướng dẫn kê khai thuế GTGT theo phương pháp khấu trừ (cập nhật 2026)',
			'excerpt' => 'Các bước kê khai thuế GTGT theo phương pháp khấu trừ: tổng hợp hoá đơn, kiểm tra điều kiện khấu trừ theo Luật Thuế GTGT 2024, thuế suất 8%/10%, khai bổ sung và những lỗi kế toán mới hay mắc.',
			'content' => $sgd_upd . '
<p>Kê khai thuế GTGT là việc kế toán làm đều đặn mỗi tháng hoặc mỗi quý. Công thức rất đơn giản – <strong>thuế phải nộp = thuế GTGT đầu ra − thuế GTGT đầu vào được khấu trừ</strong> – nhưng sai sót thường nằm ở khâu xác định hoá đơn nào được khấu trừ. Bài học dưới đây áp dụng cho doanh nghiệp nộp thuế theo phương pháp khấu trừ, theo Luật Thuế GTGT số 48/2024/QH15, Nghị định 181/2025/NĐ-CP (được sửa đổi bởi Nghị định 144/2026/NĐ-CP, hiệu lực từ 20/6/2026) và Luật Quản lý thuế 2025.</p>

<h2>Bước 1: Xác định kỳ khai và hạn nộp</h2>
<p>Doanh nghiệp có doanh thu năm trước từ 50 tỷ đồng trở xuống, hoặc mới thành lập, được chọn khai theo quý; còn lại khai theo tháng. Hạn nộp: ngày 20 tháng sau (khai tháng) hoặc ngày cuối cùng của tháng đầu quý sau (khai quý). Bảng đầy đủ xem bài <em>Lịch nộp tờ khai thuế năm 2026</em> trong chuyên mục Kiến thức.</p>

<h2>Bước 2: Tổng hợp hoá đơn đầu ra</h2>
<ul>
<li>Tải danh sách hoá đơn đã lập trong kỳ từ phần mềm hoá đơn, đối chiếu với hệ thống hoá đơn điện tử của cơ quan thuế.</li>
<li>Phân loại theo thuế suất: 0%, 5%, 8%, 10%, hàng hoá không chịu thuế. Lưu ý thuế suất 8% (giảm 2%) áp dụng đến hết 31/12/2026 cho phần lớn hàng hoá, dịch vụ đang chịu 10%, trừ các nhóm bị loại trừ theo Nghị định 174/2025.</li>
<li>Hoá đơn điều chỉnh, thay thế: khai vào kỳ lập hoá đơn điều chỉnh/thay thế.</li>
</ul>

<h2>Bước 3: Lọc hoá đơn đầu vào đủ điều kiện khấu trừ</h2>
<p>Một hoá đơn đầu vào chỉ được khấu trừ khi đáp ứng đủ:</p>
<ol>
<li>Có hoá đơn GTGT hợp pháp, phục vụ hoạt động chịu thuế GTGT.</li>
<li>Có <strong>chứng từ thanh toán không dùng tiền mặt</strong> với hàng hoá, dịch vụ mua vào từ <strong>5 triệu đồng trở lên</strong> (đã gồm thuế) – ngưỡng mới từ 01/7/2025, trước đây là 20 triệu đồng.</li>
<li>Người bán đang hoạt động bình thường tại thời điểm lập hoá đơn – nên kiểm tra trạng thái mã số thuế của nhà cung cấp trước khi nhận hoá đơn giá trị lớn; hoá đơn của doanh nghiệp bỏ địa chỉ kinh doanh có thể bị loại khi kiểm tra.</li>
</ol>
<p>Hàng hoá, dịch vụ mua vào dùng chung cho hoạt động chịu thuế và không chịu thuế: hạch toán riêng, hoặc phân bổ thuế đầu vào theo tỷ lệ doanh thu theo hướng dẫn tại Nghị định 144/2026.</p>
<p>Hoá đơn mua chưa thanh toán đến hạn (mua trả chậm) vẫn được kê khai, nhưng khi đến hạn mà không có chứng từ chuyển khoản thì phải điều chỉnh giảm số thuế đã khấu trừ.</p>

<h2>Bước 4: Lập tờ khai</h2>
<ul>
<li>Nhập (hoặc kết xuất từ phần mềm kế toán) doanh thu và thuế đầu ra theo từng thuế suất; giá trị và thuế đầu vào đủ điều kiện khấu trừ.</li>
<li>Chuyển số thuế còn được khấu trừ kỳ trước sang.</li>
<li>Kiểm tra lại: thuế đầu ra trên tờ khai phải khớp tổng hoá đơn đã lập trong kỳ.</li>
<li>Ký số và nộp qua cổng thuế điện tử; lưu thông báo chấp nhận tờ khai.</li>
</ul>

<h2>Bước 5: Nộp thuế hoặc theo dõi số được khấu trừ chuyển kỳ sau</h2>
<p>Nếu thuế đầu ra lớn hơn đầu vào, nộp tiền trong cùng hạn nộp tờ khai. Nếu đầu vào lớn hơn, số chênh lệch được chuyển sang kỳ sau; trong một số trường hợp (dự án đầu tư, xuất khẩu…) có thể đề nghị hoàn thuế – xem [[hoan-thue-gtgt|dịch vụ hoàn thuế GTGT]].</p>

<h2>Khai bổ sung khi phát hiện sai</h2>
<p>Phát hiện sai sót sau khi đã nộp, doanh nghiệp khai bổ sung cho kỳ có sai sót. Luật Quản lý thuế 2025 rút ngắn thời hạn được khai bổ sung còn <strong>5 năm</strong> kể từ ngày hết hạn nộp hồ sơ khai thuế của kỳ có sai sót (trước đây là 10 năm). Khai bổ sung làm tăng số thuế phải nộp thì phải nộp thêm tiền chậm nộp.</p>

<h2>5 lỗi người mới hay mắc</h2>
<ul>
<li>Khấu trừ hoá đơn từ 5 triệu đồng trả bằng tiền mặt.</li>
<li>Chọn nhầm thuế suất 8%/10% khi lập hoá đơn đầu ra.</li>
<li>Kê khai hoá đơn đầu vào của chi phí không phục vụ sản xuất kinh doanh.</li>
<li>Bỏ sót hoá đơn đầu ra lập cuối kỳ.</li>
<li>Không đối chiếu với dữ liệu hoá đơn trên hệ thống thuế trước khi nộp.</li>
</ul>
<p>Bạn muốn luyện kê khai trên bộ hoá đơn thực tế cùng giảng viên? Tham gia [[khoa-hoc-ke-toan-thue|khoá học kế toán thuế]]. Doanh nghiệp không có thời gian tự làm có thể giao cho [[bao-cao-thue-hang-thang-quy|dịch vụ báo cáo thuế hằng tháng, quý]].</p>',
		),

		array(
			'slug'    => 'cach-tinh-thue-tncn-tu-tien-luong-2026',
			'cat'     => array( 'thue-tncn', 'bai-hoc-ke-toan' ),
			'title'   => 'Cách tính thuế TNCN từ tiền lương năm 2026: biểu thuế 5 bậc, giảm trừ 15,5 triệu',
			'excerpt' => 'Công thức tính thuế thu nhập cá nhân từ tiền lương, tiền công năm 2026 theo Luật Thuế TNCN 2025 và Nghị quyết 110/2025: mức giảm trừ gia cảnh mới, biểu thuế lũy tiến 5 bậc và ví dụ cụ thể.',
			'content' => $sgd_upd . '
<p>Từ kỳ tính thuế năm 2026, thuế thu nhập cá nhân (TNCN) đối với tiền lương, tiền công thay đổi theo hai văn bản: <strong>Nghị quyết 110/2025/UBTVQH15</strong> nâng mức giảm trừ gia cảnh (hiệu lực 01/01/2026) và <strong>Luật Thuế TNCN số 109/2025/QH15</strong> (hiệu lực 01/7/2026, phần thu nhập từ tiền lương áp dụng từ kỳ tính thuế 2026, hướng dẫn tại Nghị định 253/2026/NĐ-CP) với biểu thuế lũy tiến rút từ 7 bậc xuống 5 bậc. Bài học này hướng dẫn kế toán tính đúng số thuế khấu trừ hằng tháng.</p>

<h2>1. Công thức</h2>
<p><strong>Thu nhập tính thuế</strong> = Tổng thu nhập chịu thuế − Các khoản bảo hiểm bắt buộc người lao động đóng − Giảm trừ gia cảnh − Các khoản giảm trừ khác (từ thiện, quỹ hưu trí tự nguyện… nếu có).</p>
<p><strong>Thuế TNCN</strong> = Thu nhập tính thuế × thuế suất theo biểu lũy tiến từng phần.</p>
<p>Thu nhập chịu thuế là tiền lương, thưởng và các khoản có tính chất tiền lương, <em>không gồm</em> các khoản được miễn hoặc không tính thuế như tiền ăn giữa ca trong mức quy định, tiền làm thêm giờ phần cao hơn lương ngày thường… (xem mục 5).</p>

<h2>2. Mức giảm trừ gia cảnh từ 2026</h2>
<ul>
<li>Bản thân người nộp thuế: <strong>15,5 triệu đồng/tháng</strong> (186 triệu đồng/năm) – trước đây 11 triệu.</li>
<li>Mỗi người phụ thuộc: <strong>6,2 triệu đồng/tháng</strong> – trước đây 4,4 triệu.</li>
</ul>
<p>Người phụ thuộc phải được đăng ký với cơ quan thuế; chỉ tính giảm trừ từ tháng phát sinh nghĩa vụ nuôi dưỡng.</p>

<h2>3. Biểu thuế lũy tiến 5 bậc</h2>
<table>
<thead><tr><th>Bậc</th><th>Thu nhập tính thuế/tháng</th><th>Thuế suất</th></tr></thead>
<tbody>
<tr><td>1</td><td>Đến 10 triệu đồng</td><td>5%</td></tr>
<tr><td>2</td><td>Trên 10 đến 30 triệu đồng</td><td>10%</td></tr>
<tr><td>3</td><td>Trên 30 đến 60 triệu đồng</td><td>20%</td></tr>
<tr><td>4</td><td>Trên 60 đến 100 triệu đồng</td><td>30%</td></tr>
<tr><td>5</td><td>Trên 100 triệu đồng</td><td>35%</td></tr>
</tbody>
</table>

<h2>4. Ví dụ tính thuế</h2>
<h3>Ví dụ 1: Lương 25 triệu đồng, 1 người phụ thuộc</h3>
<p>Giả sử lương đóng bảo hiểm 25 triệu đồng, người lao động đóng 10,5% = 2.625.000 đồng.</p>
<p>Thu nhập tính thuế = 25.000.000 − 2.625.000 − 15.500.000 − 6.200.000 = <strong>675.000 đồng</strong>.</p>
<p>Thuế TNCN = 675.000 × 5% = <strong>33.750 đồng/tháng</strong>. Theo mức giảm trừ cũ (11 triệu và 4,4 triệu), người này phải nộp khoảng 350.000 đồng/tháng.</p>
<h3>Ví dụ 2: Thu nhập chịu thuế 50 triệu đồng, không có người phụ thuộc</h3>
<p>Giả sử bảo hiểm bắt buộc người lao động đóng là 3.000.000 đồng.</p>
<p>Thu nhập tính thuế = 50.000.000 − 3.000.000 − 15.500.000 = <strong>31.500.000 đồng</strong>.</p>
<ul>
<li>Bậc 1: 10.000.000 × 5% = 500.000</li>
<li>Bậc 2: 20.000.000 × 10% = 2.000.000</li>
<li>Bậc 3: 1.500.000 × 20% = 300.000</li>
</ul>
<p>Tổng thuế = <strong>2.800.000 đồng/tháng</strong>.</p>

<h2>5. Khoản không tính thuế, giảm trừ mới theo Nghị định 253/2026</h2>
<p>Nghị định 253/2026/NĐ-CP (hiệu lực 01/7/2026) hướng dẫn Luật Thuế TNCN 2025, có nhiều điểm có lợi cho người lao động:</p>
<ul>
<li><strong>Tiền ăn giữa ca</strong>: chỉ phần vượt <strong>1,2 triệu đồng/người/tháng</strong> mới tính thuế (trước là 730.000 đồng); nếu công ty tự nấu, mua suất ăn, cấp phiếu ăn thì không tính thuế.</li>
<li><strong>Chi y tế, giáo dục</strong> của người nộp thuế và người phụ thuộc được giảm trừ: khám chữa bệnh trong danh mục bảo hiểm y tế tối đa 23 triệu đồng/năm, học phí tối đa 24 triệu đồng/năm – cần hoá đơn, chứng từ ghi tên người nộp thuế hoặc người phụ thuộc.</li>
<li><strong>Quỹ hưu trí tự nguyện, bảo hiểm hưu trí</strong>: được trừ tối đa 3 triệu đồng/tháng (trước là 1 triệu).</li>
<li>Tiền lương trả cho <strong>ngày phép không nghỉ</strong> theo Bộ luật Lao động được miễn thuế.</li>
<li>Trợ cấp thôi việc, mất việc cao hơn luật nhưng có trong quy chế, hợp đồng, thoả ước: phần vượt không tính thuế.</li>
<li>Nhà ở do công ty xây cho người lao động: không tính thuế, không phân biệt địa bàn. Tiền thuê nhà công ty trả thay tính thuế tối đa 15% tổng thu nhập chịu thuế.</li>
</ul>
<p>Thuế đã khấu trừ từ tháng 1 đến tháng 6/2026 theo quy định cũ không phải khai lại; chênh lệch được điều chỉnh khi quyết toán năm 2026.</p>

<h2>6. Lưu ý cho kế toán</h2>
<ul>
<li>Lao động không ký hợp đồng hoặc hợp đồng dưới 3 tháng: khấu trừ 10% khi chi trả <strong>từ 5 triệu đồng/lần</strong> trở lên (từ 01/7/2026; trước là 2 triệu). Người chỉ có thu nhập này và ước tính chưa đến mức chịu thuế có thể làm cam kết để tạm chưa khấu trừ.</li>
<li>Chi lương từ 5 triệu đồng/lần trở lên cho một người phải chuyển khoản để được tính vào chi phí được trừ khi tính thuế TNDN (Nghị định 320/2025).</li>
<li>Cá nhân tự quyết toán: hạn chậm nhất ngày cuối cùng của tháng thứ 4 sau khi kết thúc năm.</li>
</ul>
<p>Người lao động nộp thừa thuế trong năm có thể đề nghị hoàn – xem [[hoan-thue-tncn|dịch vụ hoàn thuế TNCN]]. Doanh nghiệp cần quyết toán TNCN cho nhân viên cuối năm có thể dùng [[quyet-toan-thue-cuoi-nam|dịch vụ quyết toán thuế]]; người học muốn nắm chắc phần lương – bảo hiểm – thuế TNCN nên tham khảo [[khoa-hoc-ke-toan-thue|khoá học kế toán thuế]].</p>',
		),

		array(
			'slug'    => 'diem-moi-thong-tu-99-2025-che-do-ke-toan',
			'cat'     => array( 'so-sach-ke-toan', 'bai-hoc-ke-toan' ),
			'title'   => 'Điểm mới Thông tư 99/2025/TT-BTC về chế độ kế toán doanh nghiệp từ 2026',
			'excerpt' => 'Thông tư 99/2025/TT-BTC thay Thông tư 200/2014 từ 01/01/2026: phạm vi áp dụng, hệ thống tài khoản mới (thêm TK 215, đổi tên nhiều tài khoản), quy chế hạch toán, chứng từ và sổ sách tự thiết kế và việc doanh nghiệp nhỏ có nên chuyển đổi.',
			'content' => $sgd_upd . '
<p>Ngày 27/10/2025, Bộ Tài chính ban hành Thông tư 99/2025/TT-BTC hướng dẫn chế độ kế toán doanh nghiệp, có hiệu lực từ <strong>01/01/2026</strong> và áp dụng cho năm tài chính bắt đầu từ ngày này. Thông tư thay thế Thông tư 200/2014/TT-BTC cùng các thông tư sửa đổi (75/2015, 53/2016). Đây là thay đổi lớn nhất về chế độ kế toán doanh nghiệp sau hơn 10 năm.</p>

<h2>1. Ai áp dụng?</h2>
<ul>
<li>Doanh nghiệp thuộc mọi lĩnh vực, mọi thành phần kinh tế đang dùng Thông tư 200 chuyển sang Thông tư 99.</li>
<li>Doanh nghiệp nhỏ và vừa: được chọn tiếp tục Thông tư 133/2016 hoặc chuyển sang Thông tư 99.</li>
<li>Doanh nghiệp siêu nhỏ: có chế độ riêng tại Thông tư 58/2026/TT-BTC (thay Thông tư 132/2018).</li>
</ul>

<h2>2. Hệ thống tài khoản gọn hơn</h2>
<ul>
<li>Còn <strong>71 tài khoản cấp 1</strong>, ít hơn 5 tài khoản so với Thông tư 200.</li>
<li>Bỏ quy định cứng về tài khoản cấp 2 của nhiều tài khoản như 111, 112, 113, 153, 155, 156, 211, 212, 213, 334, 413, 511, 521 – doanh nghiệp tự mở chi tiết theo nhu cầu quản lý.</li>
<li>Bổ sung <strong>TK 215 – Tài sản sinh học</strong> (súc vật nuôi, cây trồng) và tài khoản chi tiết <strong>82112</strong> – chi phí thuế TNDN bổ sung theo quy định thuế tối thiểu toàn cầu.</li>
<li>Đổi tên một số tài khoản: 112 “Tiền gửi không kỳ hạn” (trước là Tiền gửi ngân hàng), 155 “Sản phẩm” (trước là Thành phẩm), 242 “Chi phí chờ phân bổ” (trước là Chi phí trả trước), 244 “Ký quỹ, ký cược”, 419 “Cổ phiếu mua lại của chính mình” (trước là Cổ phiếu quỹ).</li>
</ul>
<p>Khi chuyển đổi, kế toán cần lập bảng chuyển số dư từ tài khoản cũ sang tài khoản mới tại ngày 01/01/2026 và cập nhật danh mục tài khoản trên phần mềm.</p>

<h2>3. Chứng từ, sổ kế toán linh hoạt hơn</h2>
<p>Trước đây, muốn sửa mẫu chứng từ, tài khoản hay chỉ tiêu báo cáo phải xin chấp thuận của Bộ Tài chính. Thông tư 99 cho phép doanh nghiệp tự làm, nhưng phải ban hành <strong>Quy chế hạch toán kế toán</strong> trong 4 trường hợp: thiết kế thêm hoặc sửa mẫu chứng từ; sửa tên, số hiệu, kết cấu tài khoản; thiết kế thêm hoặc sửa mẫu sổ; bổ sung chỉ tiêu báo cáo tài chính. Ngoài ra, doanh nghiệp phải tự xây dựng <strong>quy chế quản trị nội bộ và tổ chức kiểm soát nội bộ</strong>, phân định rõ trách nhiệm của từng bộ phận trong việc tạo lập, thực hiện, kiểm soát giao dịch.</p>

<h2>4. Báo cáo tài chính</h2>
<p>“Bảng cân đối kế toán” đổi tên thành <strong>Báo cáo tình hình tài chính</strong>; bổ sung một số chỉ tiêu đầu tư ngắn hạn (mã số 124, 125, 126) và hướng dẫn chuyển đổi báo cáo lập bằng ngoại tệ sang Đồng Việt Nam. Báo cáo năm 2026 (nộp năm 2027) là kỳ đầu tiên lập theo mẫu mới, cần trình bày lại số liệu so sánh năm 2025 theo cách phân loại mới. Doanh nghiệp nhỏ và vừa chuyển từ Thông tư 133 sang cũng phải trình bày lại số liệu so sánh và giải trình trong thuyết minh; đã chọn thì áp dụng nhất quán tối thiểu một năm tài chính.</p>

<h2>5. Doanh nghiệp nhỏ có nên chuyển sang Thông tư 99?</h2>
<ul>
<li><strong>Nên giữ Thông tư 133</strong> nếu hoạt động đơn giản (thương mại, dịch vụ), ít nghiệp vụ phức tạp, muốn sổ sách gọn.</li>
<li><strong>Cân nhắc Thông tư 99</strong> nếu chuẩn bị gọi vốn, vay vốn lớn, có công ty mẹ – con, hoặc cần báo cáo chi tiết cho nhà đầu tư.</li>
</ul>
<p>Dù chọn cách nào, cần áp dụng nhất quán trong cả năm tài chính và ghi rõ trong thuyết minh báo cáo tài chính.</p>

<h2>Checklist chuyển đổi</h2>
<ol>
<li>Chốt số dư 31/12/2025, đối chiếu đầy đủ.</li>
<li>Lập bảng ánh xạ tài khoản cũ – mới.</li>
<li>Cập nhật phần mềm kế toán lên phiên bản hỗ trợ Thông tư 99.</li>
<li>Ban hành quy chế hạch toán kế toán, quy chế quản trị và kiểm soát nội bộ.</li>
<li>Đào tạo lại kế toán viên về cách hạch toán mới.</li>
</ol>
<p>Sổ sách năm cũ còn sai lệch sẽ kéo theo số dư đầu kỳ sai khi chuyển đổi – nên [[ra-soat-lam-lai-so-sach-ke-toan|rà soát, làm lại sổ sách]] trước. Người học muốn thực hành ghi sổ theo chế độ mới có thể đăng ký [[khoa-hoc-so-sach-ke-toan|khoá học sổ sách kế toán]].</p>',
		),

		array(
			'slug'    => 'chi-phi-duoc-tru-thue-tndn-2026',
			'cat'     => array( 'thue-tndn', 'bai-hoc-ke-toan' ),
			'title'   => 'Chi phí được trừ khi tính thuế TNDN theo Luật Thuế TNDN 2025',
			'excerpt' => 'Điều kiện chi phí được trừ theo Luật Thuế TNDN số 67/2025/QH15 và Nghị định 320/2025: chứng từ, thanh toán không dùng tiền mặt từ 5 triệu đồng, các khoản chi lương, chi phí hay bị loại và thuế suất 15% – 17% – 20%.',
			'content' => $sgd_upd . '
<p>Thuế TNDN phải nộp phụ thuộc trực tiếp vào việc chi phí nào được trừ. Một khoản chi bị loại khi quyết toán sẽ làm tăng thuế phải nộp, kèm tiền chậm nộp và có thể bị phạt. Bài học này tổng hợp nguyên tắc theo <strong>Luật Thuế TNDN số 67/2025/QH15</strong> (hiệu lực từ 01/10/2025, áp dụng từ kỳ tính thuế năm 2025), <strong>Nghị định 320/2025/NĐ-CP</strong> (hiệu lực từ 15/12/2025) và <strong>Thông tư 20/2026/TT-BTC</strong>.</p>

<h2>1. Thuế suất mới cho doanh nghiệp nhỏ</h2>
<table>
<thead><tr><th>Tổng doanh thu năm</th><th>Thuế suất</th></tr></thead>
<tbody>
<tr><td>Không quá 3 tỷ đồng</td><td>15%</td></tr>
<tr><td>Trên 3 tỷ đến không quá 50 tỷ đồng</td><td>17%</td></tr>
<tr><td>Các trường hợp còn lại</td><td>20%</td></tr>
</tbody>
</table>
<p>Doanh thu làm căn cứ là tổng doanh thu bán hàng, cung cấp dịch vụ, doanh thu tài chính và thu nhập khác của <strong>năm trước liền kề</strong>. Doanh nghiệp có quan hệ liên kết với doanh nghiệp không đáp ứng điều kiện thì không được áp dụng mức 15%, 17%.</p>

<h2>2. Ba điều kiện để chi phí được trừ</h2>
<ol>
<li><strong>Thực tế phát sinh</strong> và liên quan đến hoạt động sản xuất, kinh doanh của doanh nghiệp.</li>
<li>Có <strong>hoá đơn, chứng từ hợp pháp</strong> theo quy định.</li>
<li>Khoản chi mua hàng hoá, dịch vụ và các khoản thanh toán khác <strong>từ 5 triệu đồng trở lên</strong> mỗi lần phải có <strong>chứng từ thanh toán không dùng tiền mặt</strong>.</li>
</ol>

<h3>Các tình huống hay gặp (Thông tư 20/2026/TT-BTC)</h3>
<ul>
<li><strong>Chưa thanh toán</strong> khi ghi nhận chi phí: vẫn được tính vào chi phí nếu có hợp đồng, biên bản bàn giao; khi trả tiền phải chuyển khoản. Nếu sau đó trả bằng tiền mặt thì phải điều chỉnh giảm chi phí ở kỳ thanh toán.</li>
<li><strong>Nhân viên mua hộ</strong> từ 5 triệu đồng: được trừ nếu nhân viên trả bằng thẻ/chuyển khoản, có quy chế hoặc quyết định uỷ quyền, và công ty chuyển khoản hoàn tiền lại cho nhân viên.</li>
<li><strong>Mua của người dân, hộ kinh doanh dưới ngưỡng chịu thuế</strong> (nông sản do người sản xuất bán, phế liệu, đồ dùng của hộ gia đình…): lập bảng kê Mẫu 02/TNDN; mua trong ngày từ 5 triệu đồng của cùng một người vẫn phải chuyển khoản.</li>
<li>Nộp tiền mặt vào tài khoản người bán <strong>không</strong> được coi là thanh toán không dùng tiền mặt.</li>
</ul>

<h2>3. Chi lương – điểm cần chú ý nhất từ 2026</h2>
<p>Theo Nghị định 320/2025 và Công văn 218/CST-TN năm 2026, từ 15/12/2025 khoản chi tiền lương, tiền công từng lần từ 5 triệu đồng trở lên phải chuyển khoản mới được tính vào chi phí được trừ. Ngoài ra, chi lương phải có:</p>
<ul>
<li>Hợp đồng lao động, quy chế lương thưởng hoặc thoả ước lao động.</li>
<li>Bảng lương, bảng chấm công có chữ ký người nhận hoặc chứng từ ngân hàng.</li>
<li>Khấu trừ, kê khai thuế TNCN đầy đủ (kể cả người có thu nhập dưới mức chịu thuế).</li>
</ul>

<h2>4. Những khoản chi hay bị loại khi quyết toán</h2>
<ul>
<li>Chi phí không có hoá đơn, hoặc hoá đơn của người bán đã ngừng hoạt động.</li>
<li>Khoản từ 5 triệu đồng trả bằng tiền mặt.</li>
<li>Khấu hao tài sản không dùng cho sản xuất kinh doanh, hoặc vượt mức khung quy định.</li>
<li>Tiền phạt vi phạm hành chính, tiền chậm nộp thuế.</li>
<li>Chi phí của năm trước hạch toán vào năm nay.</li>
</ul>

<h2>5. Tạm nộp và quyết toán</h2>
<p>Doanh nghiệp tạm nộp thuế TNDN theo quý (chậm nhất ngày cuối cùng của tháng đầu quý sau); tổng 4 quý không thấp hơn 80% số phải nộp khi quyết toán. Quyết toán và báo cáo tài chính nộp chậm nhất ngày cuối cùng của tháng thứ 3 sau khi kết thúc năm tài chính.</p>

<h2>Kinh nghiệm thực tế</h2>
<ul>
<li>Thiết lập quy định nội bộ: mọi khoản chi từ 5 triệu đồng đều chuyển khoản từ tài khoản công ty.</li>
<li>Rà soát hoá đơn đầu vào hằng tháng thay vì dồn đến cuối năm.</li>
<li>Lưu hợp đồng, biên bản nghiệm thu đi kèm hoá đơn dịch vụ lớn.</li>
</ul>
<p>Sắp đến kỳ quyết toán mà sổ sách còn nhiều khoản chi chưa đủ chứng từ? [[quyet-toan-thue-cuoi-nam|Dịch vụ quyết toán thuế cuối năm]] sẽ rà soát và xử lý trước khi nộp. Kế toán muốn hiểu sâu cách xác định chi phí được trừ có thể học [[khoa-hoc-ke-toan-thue|khoá kế toán thuế]] hoặc xem các khoá trong mục [[nhom:dao-tao|Đào tạo kế toán]].</p>',
		),
	),
);
