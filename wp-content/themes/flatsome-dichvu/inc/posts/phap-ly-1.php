<?php
/**
 * Bài viết Kiến thức pháp lý (đợt 1): Thành lập doanh nghiệp, Hộ kinh doanh cá thể,
 * Công ty vốn nước ngoài, Thay đổi GPKD – mỗi mục 4 bài. Nguồn: văn bản trên Thư viện Pháp luật.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$u = '<p class="sgd-updated"><em>Cập nhật tháng 10/2026 theo văn bản đang có hiệu lực. Quy định về đăng ký doanh nghiệp, đầu tư thay đổi thường xuyên – trước khi thực hiện, bạn nên liên hệ chuyên viên để được kiểm tra theo trường hợp cụ thể.</em></p>';

return array(

	// ===================== THÀNH LẬP DOANH NGHIỆP =====================
	array(
		'slug'    => 'nen-thanh-lap-cong-ty-tnhh-hay-cong-ty-co-phan',
		'cat'     => array( 'thu-tuc-thanh-lap' ),
		'title'   => 'Nên thành lập công ty TNHH hay công ty cổ phần? So sánh để chọn đúng loại hình',
		'excerpt' => 'So sánh công ty TNHH một thành viên, hai thành viên và công ty cổ phần về số thành viên, trách nhiệm, cơ cấu quản lý, chuyển nhượng vốn, huy động vốn và thuế – kèm gợi ý chọn loại hình cho từng trường hợp.',
		'content' => $u . '
<p>Chọn loại hình là quyết định đầu tiên khi thành lập công ty và ảnh hưởng lâu dài: cách góp vốn, cách ra quyết định, việc nhận thêm người góp vốn hay gọi nhà đầu tư. Hai loại hình được chọn nhiều nhất là <strong>công ty trách nhiệm hữu hạn (TNHH)</strong> và <strong>công ty cổ phần</strong>, đều được quy định tại [[tvpl:ldn2020]] (đã sửa đổi bởi [[tvpl:ldn2025|Luật số 76/2025/QH15]]).</p>

<h2>Điểm giống nhau</h2>
<ul>
<li>Đều có tư cách pháp nhân, chủ sở hữu chỉ chịu trách nhiệm trong phạm vi số vốn đã góp hoặc cam kết góp – tài sản cá nhân được tách bạch với tài sản công ty.</li>
<li>Thuế như nhau: thuế TNDN, GTGT, TNCN không phụ thuộc loại hình mà phụ thuộc doanh thu, ngành nghề.</li>
<li>Thủ tục thành lập, thời hạn giải quyết (3 ngày làm việc) và chi phí gần như tương đương.</li>
</ul>

<h2>Bảng so sánh nhanh</h2>
<table>
<thead><tr><th>Tiêu chí</th><th>Công ty TNHH</th><th>Công ty cổ phần</th></tr></thead>
<tbody>
<tr><td>Số thành viên</td><td>1 chủ sở hữu (TNHH một thành viên) hoặc 2 – 50 thành viên</td><td>Tối thiểu 3 cổ đông, không giới hạn tối đa</td></tr>
<tr><td>Vốn</td><td>Chia theo tỷ lệ phần vốn góp</td><td>Chia thành cổ phần bằng nhau</td></tr>
<tr><td>Huy động vốn</td><td>Không phát hành cổ phần (trừ khi chuyển thành công ty cổ phần); được phát hành trái phiếu</td><td>Được phát hành cổ phần, trái phiếu – thuận lợi gọi vốn</td></tr>
<tr><td>Chuyển nhượng vốn</td><td>TNHH hai thành viên trở lên phải chào bán cho các thành viên còn lại trước</td><td>Tự do chuyển nhượng; riêng cổ đông sáng lập bị hạn chế trong 3 năm đầu</td></tr>
<tr><td>Bộ máy</td><td>Gọn: chủ sở hữu/Hội đồng thành viên và Giám đốc</td><td>Đại hội đồng cổ đông, Hội đồng quản trị, Giám đốc; có thể phải có Ban kiểm soát</td></tr>
</tbody>
</table>

<h2>Khi nào nên chọn công ty TNHH một thành viên?</h2>
<p>Khi chỉ có một người bỏ vốn và muốn toàn quyền quyết định. Đây là lựa chọn phổ biến nhất của doanh nghiệp nhỏ, cửa hàng, công ty dịch vụ. Sau này muốn nhận thêm người góp vốn, công ty chuyển đổi lên TNHH hai thành viên hoặc cổ phần. Tham khảo [[thanh-lap-cong-ty-tnhh|dịch vụ thành lập công ty TNHH]].</p>

<h2>Khi nào nên chọn công ty TNHH hai thành viên trở lên?</h2>
<p>Khi vài người quen biết cùng góp vốn và muốn kiểm soát việc ai được vào công ty. Quy định ưu tiên chào bán cho thành viên hiện hữu giúp tránh người lạ mua lại phần vốn góp. Nên thoả thuận trước trong điều lệ: tỷ lệ biểu quyết, cách chia lợi nhuận, cách định giá khi một người rút vốn.</p>

<h2>Khi nào nên chọn công ty cổ phần?</h2>
<ul>
<li>Có kế hoạch gọi vốn từ nhà đầu tư, phát hành thêm cổ phần, hoặc hướng tới niêm yết.</li>
<li>Có nhiều người góp vốn, mỗi người một ít, cần cơ chế chuyển nhượng linh hoạt.</li>
<li>Ngành nghề mà pháp luật chuyên ngành yêu cầu loại hình cổ phần.</li>
</ul>
<p>Đổi lại, công ty cổ phần có bộ máy phức tạp hơn: họp Đại hội đồng cổ đông thường niên, lập sổ đăng ký cổ đông, thông báo cổ đông sáng lập. Xem [[thanh-lap-cong-ty-co-phan|thủ tục thành lập công ty cổ phần]].</p>

<h2>Đừng quên kê khai chủ sở hữu hưởng lợi</h2>
<p>Dù chọn loại hình nào, từ năm 2025 người thành lập phải xác định và kê khai <strong>chủ sở hữu hưởng lợi</strong> – cá nhân sở hữu từ 25% vốn điều lệ trở lên hoặc có quyền chi phối thực tế. [[tvpl:nd296]] còn quy định rõ thành viên, cổ đông phải góp vốn thật, không được đứng tên thay người khác.</p>

<h2>Gợi ý nhanh</h2>
<ul>
<li>Một chủ, kinh doanh nhỏ → TNHH một thành viên.</li>
<li>Vài người thân, bạn bè góp vốn → TNHH hai thành viên.</li>
<li>Cần gọi vốn, nhiều cổ đông → Cổ phần.</li>
</ul>
<p>Chưa chắc loại hình nào hợp với kế hoạch của bạn? Chuyên viên [[nhom:thanh-lap-doanh-nghiep|thành lập doanh nghiệp]] sẽ tư vấn miễn phí và soạn điều lệ phù hợp.</p>',
	),

	array(
		'slug'    => 'cach-dat-ten-cong-ty-dung-luat',
		'cat'     => array( 'thu-tuc-thanh-lap' ),
		'title'   => 'Cách đặt tên công ty đúng luật: cấu trúc tên, tên trùng, tên gây nhầm lẫn',
		'excerpt' => 'Quy định đặt tên doanh nghiệp theo Luật Doanh nghiệp: cấu trúc loại hình + tên riêng, những điều cấm, thế nào là tên trùng, tên gây nhầm lẫn, tên tiếng nước ngoài, tên viết tắt và cách tra cứu trước khi nộp hồ sơ.',
		'content' => $u . '
<p>Tên công ty bị từ chối là lý do phổ biến nhất khiến hồ sơ thành lập phải sửa đi sửa lại. Nắm vài nguyên tắc dưới đây (theo các điều 37 – 41 của [[tvpl:ldn2020]]) sẽ giúp bạn chọn tên đúng ngay từ đầu.</p>

<h2>1. Cấu trúc tên tiếng Việt</h2>
<p>Tên doanh nghiệp gồm hai thành tố theo thứ tự: <strong>loại hình doanh nghiệp + tên riêng</strong>.</p>
<ul>
<li>Loại hình: “Công ty trách nhiệm hữu hạn” (hoặc “Công ty TNHH”), “Công ty cổ phần” (hoặc “Công ty CP”), “Công ty hợp danh”, “Doanh nghiệp tư nhân” (hoặc “DNTN”).</li>
<li>Tên riêng: viết bằng chữ cái tiếng Việt, có thể kèm chữ F, J, Z, W, chữ số và ký hiệu.</li>
</ul>
<p>Ví dụ: Công ty TNHH Thương mại Dịch vụ Minh An. Cụm “Thương mại Dịch vụ” thường được thêm vào để tên riêng dễ phân biệt, không bắt buộc.</p>

<h2>2. Những điều cấm khi đặt tên</h2>
<ul>
<li>Đặt tên trùng hoặc gây nhầm lẫn với tên doanh nghiệp đã đăng ký (xem mục 3).</li>
<li>Dùng tên cơ quan nhà nước, đơn vị lực lượng vũ trang, tổ chức chính trị – xã hội… làm toàn bộ hoặc một phần tên riêng, trừ khi được cơ quan, tổ chức đó chấp thuận.</li>
<li>Dùng từ ngữ, ký hiệu vi phạm truyền thống lịch sử, văn hoá, đạo đức và thuần phong mỹ tục.</li>
</ul>

<h2>3. Thế nào là tên trùng, tên gây nhầm lẫn?</h2>
<p><strong>Tên trùng</strong> là tên tiếng Việt được viết hoàn toàn giống tên doanh nghiệp đã đăng ký. <strong>Tên gây nhầm lẫn</strong> gồm nhiều trường hợp, thường gặp nhất:</p>
<ul>
<li>Đọc giống tên đã đăng ký (khác dấu, khác cách viết).</li>
<li>Tên riêng chỉ khác tên đã đăng ký bởi ký hiệu “&”, “-”, “.”, “+”, hoặc khác chữ “và”.</li>
<li>Tên riêng chỉ khác bởi một số tự nhiên, số thứ tự, chữ cái tiếng Việt ngay sau tên riêng; hoặc khác bởi từ “tân” ngay trước, “mới” ngay sau tên riêng; hoặc các từ “miền Bắc”, “miền Nam”, “miền Trung”, “miền Tây”, “miền Đông”.</li>
<li>Tên viết tắt, tên tiếng nước ngoài trùng với tên viết tắt, tên tiếng nước ngoài của doanh nghiệp đã đăng ký.</li>
</ul>
<p>Một số trường hợp gây nhầm lẫn (khác số, khác chữ “tân”, “mới”, “miền…”) không áp dụng với công ty con của công ty đã đăng ký.</p>

<h2>4. Tên tiếng nước ngoài và tên viết tắt</h2>
<ul>
<li><strong>Tên tiếng nước ngoài</strong>: dịch từ tên tiếng Việt sang một ngôn ngữ dùng hệ chữ La-tinh; tên riêng có thể giữ nguyên hoặc dịch nghĩa. Chữ trên biển hiệu, giấy tờ giao dịch phải nhỏ hơn tên tiếng Việt.</li>
<li><strong>Tên viết tắt</strong>: viết tắt từ tên tiếng Việt hoặc tên tiếng nước ngoài.</li>
</ul>

<h2>5. Tên và quyền sở hữu trí tuệ</h2>
<p>Không được dùng tên riêng xâm phạm nhãn hiệu đã được bảo hộ của người khác. Ngược lại, có giấy chứng nhận đăng ký doanh nghiệp <strong>không có nghĩa là thương hiệu của bạn được bảo hộ</strong> – nếu tên riêng là thương hiệu bạn đầu tư lâu dài, nên [[dang-ky-nhan-hieu|đăng ký nhãn hiệu]] song song để tránh bị người khác đăng ký trước.</p>

<h2>6. Tra cứu tên trước khi nộp</h2>
<ol>
<li>Tra trên Cổng thông tin quốc gia về đăng ký doanh nghiệp (dangkykinhdoanh.gov.vn) – phần tra cứu tên doanh nghiệp.</li>
<li>Thử cả các biến thể: có dấu/không dấu, thêm số, thêm “tân”, “mới”.</li>
<li>Tra nhãn hiệu trên cơ sở dữ liệu của Cục Sở hữu trí tuệ nếu định dùng tên làm thương hiệu.</li>
<li>Chuẩn bị 2 – 3 phương án dự phòng.</li>
</ol>
<p>Cơ quan đăng ký kinh doanh có quyền từ chối tên vi phạm và là nơi quyết định cuối cùng theo [[tvpl:nd168]]. Nếu muốn chắc chắn tên được chấp nhận ngay lần đầu, chuyên viên [[thanh-lap-cong-ty-tnhh|thành lập công ty]] sẽ tra cứu và đề xuất phương án miễn phí.</p>',
	),

	array(
		'slug'    => 'von-dieu-le-thoi-han-gop-von',
		'cat'     => array( 'thu-tuc-thanh-lap' ),
		'title'   => 'Vốn điều lệ là gì? Nên đăng ký bao nhiêu và thời hạn góp vốn 90 ngày',
		'excerpt' => 'Vốn điều lệ, thời hạn góp đủ vốn 90 ngày, cách góp vốn bằng tiền và tài sản, hậu quả khi không góp đủ, mức phạt và kinh nghiệm chọn mức vốn phù hợp cho doanh nghiệp nhỏ.',
		'content' => $u . '
<p>“Đăng ký vốn bao nhiêu thì vừa?” là câu hỏi gần như ai thành lập công ty cũng hỏi. Vốn điều lệ không chỉ là con số trên giấy chứng nhận – đó là cam kết góp vốn thật và là giới hạn trách nhiệm của bạn với khách hàng, đối tác.</p>

<h2>1. Vốn điều lệ là gì?</h2>
<p>Theo [[tvpl:ldn2020]], vốn điều lệ của công ty TNHH là tổng giá trị tài sản các thành viên đã góp hoặc cam kết góp khi thành lập; của công ty cổ phần là tổng mệnh giá cổ phần đã bán hoặc được đăng ký mua. Tài sản góp vốn có thể là tiền (Đồng Việt Nam, ngoại tệ tự do chuyển đổi), vàng, quyền sử dụng đất, quyền sở hữu trí tuệ, công nghệ, bí quyết kỹ thuật hoặc tài sản khác định giá được bằng Đồng Việt Nam.</p>

<h2>2. Thời hạn góp đủ vốn: 90 ngày</h2>
<p>Thành viên, chủ sở hữu, cổ đông phải góp đủ và đúng loại tài sản đã cam kết trong <strong>90 ngày kể từ ngày được cấp Giấy chứng nhận đăng ký doanh nghiệp</strong>, không kể thời gian vận chuyển, nhập khẩu tài sản góp vốn và thực hiện thủ tục chuyển quyền sở hữu tài sản.</p>
<ul>
<li>Góp bằng tiền: nên chuyển khoản vào tài khoản công ty, ghi rõ nội dung “góp vốn điều lệ”.</li>
<li>Góp bằng tài sản: phải định giá (các thành viên tự thoả thuận hoặc thuê tổ chức thẩm định giá) và làm thủ tục chuyển quyền sở hữu sang công ty.</li>
<li>Công ty cấp giấy chứng nhận phần vốn góp (TNHH) hoặc ghi sổ đăng ký cổ đông (cổ phần).</li>
</ul>

<h2>3. Không góp đủ thì sao?</h2>
<p>Hết 90 ngày mà chưa góp đủ, công ty phải <strong>đăng ký điều chỉnh vốn điều lệ</strong> và tỷ lệ phần vốn góp bằng số vốn thực góp trong 30 ngày tiếp theo. Thành viên chưa góp phải chịu trách nhiệm tương ứng với phần vốn đã cam kết đối với các nghĩa vụ phát sinh trước ngày đăng ký điều chỉnh.</p>
<p>Không đăng ký điều chỉnh vốn khi chưa góp đủ là hành vi bị xử phạt theo [[tvpl:nd122]]. Việc góp vốn không thật, đứng tên góp vốn thay người khác cũng đã bị nghiêm cấm rõ ràng tại [[tvpl:nd296]].</p>

<h2>4. Nên đăng ký vốn bao nhiêu?</h2>
<p>Đa số ngành nghề không có vốn tối thiểu. Một số gợi ý thực tế:</p>
<ul>
<li><strong>Đủ để vận hành</strong> trong 3 – 6 tháng đầu (thuê mặt bằng, lương, hàng hoá) – vốn quá thấp khiến đối tác, ngân hàng e ngại.</li>
<li><strong>Không đăng ký quá khả năng góp</strong> – bạn phải góp thật trong 90 ngày, và vốn càng cao thì trách nhiệm cam kết càng lớn.</li>
<li><strong>Kiểm tra ngành có điều kiện</strong>: một số ngành yêu cầu vốn pháp định hoặc ký quỹ (ví dụ kinh doanh bất động sản, dịch vụ lữ hành, dịch vụ việc làm…).</li>
<li><strong>Nhà đầu tư nước ngoài</strong>: vốn phải tương xứng với dự án đăng ký và được chuyển qua tài khoản vốn đầu tư trực tiếp.</li>
</ul>
<p>Từ năm 2026 lệ phí môn bài đã bãi bỏ theo [[tvpl:nq198|Nghị quyết 198/2025/QH15]], nên mức vốn không còn ảnh hưởng đến khoản lệ phí này như trước.</p>

<h2>5. Sau này muốn thay đổi vốn?</h2>
<p>Công ty có thể tăng vốn khi mở rộng hoặc giảm vốn khi đáp ứng điều kiện luật định. Thủ tục chi tiết xem [[tang-giam-von-dieu-le|dịch vụ tăng, giảm vốn điều lệ]].</p>

<h2>Lưu chứng từ góp vốn</h2>
<p>Hãy lưu sao kê ngân hàng, biên bản góp vốn, giấy chứng nhận phần vốn góp – đây là căn cứ khi kiểm tra thuế, khi chuyển nhượng vốn hoặc khi tranh chấp giữa các thành viên. Gói [[ke-toan-tron-goi|kế toán trọn gói]] sẽ hạch toán và theo dõi việc góp vốn ngay từ tháng đầu tiên.</p>',
	),

	array(
		'slug'    => 'chu-so-huu-huong-loi-cua-doanh-nghiep',
		'cat'     => array( 'thu-tuc-thanh-lap', 'thay-doi-dang-ky-kinh-doanh' ),
		'title'   => 'Chủ sở hữu hưởng lợi của doanh nghiệp là ai? Cách xác định và kê khai năm 2026',
		'excerpt' => 'Khái niệm chủ sở hữu hưởng lợi theo Luật Doanh nghiệp sửa đổi 2025 và Nghị định 296/2026: tiêu chí 25% vốn điều lệ, quyền chi phối thực tế, cách kê khai khi thành lập, thời hạn thông báo khi thay đổi và nghĩa vụ lưu giữ thông tin.',
		'content' => $u . '
<p>Từ ngày 01/7/2025, [[tvpl:ldn2025]] đưa vào khái niệm <strong>chủ sở hữu hưởng lợi</strong> – nhằm minh bạch ai là người thực sự sở hữu, kiểm soát doanh nghiệp, phục vụ phòng, chống rửa tiền. [[tvpl:nd296]] (hiệu lực từ 23/7/2026) tiếp tục làm rõ tiêu chí và cách kê khai.</p>

<h2>1. Chủ sở hữu hưởng lợi là ai?</h2>
<p>Là <strong>cá nhân</strong> (không phải tổ chức) có quyền sở hữu thực tế vốn điều lệ hoặc quyền chi phối doanh nghiệp. Theo Nghị định 296/2026, việc xác định đi theo thứ tự:</p>
<ol>
<li>Cá nhân sở hữu trực tiếp, gián tiếp hoặc vừa trực tiếp vừa gián tiếp <strong>từ 25% vốn điều lệ</strong> hoặc từ 25% tổng số cổ phần có quyền biểu quyết trở lên.</li>
<li>Nếu không có ai đạt tiêu chí trên: cá nhân có <strong>quyền chi phối</strong> – quyết định bổ nhiệm, miễn nhiệm người quản lý, sửa đổi điều lệ, thay đổi cơ cấu tổ chức, tổ chức lại, giải thể…</li>
<li>Nếu vẫn không xác định được: kê khai người giữ chức vụ quản lý cao nhất của doanh nghiệp.</li>
</ol>
<p>Nhóm người có quan hệ gia đình hoặc cùng thoả thuận sở hữu chung, cộng lại từ 25% trở lên, cũng được xác định là chủ sở hữu hưởng lợi – để tránh việc chia nhỏ tỷ lệ cho người thân đứng tên.</p>

<h2>2. Sở hữu gián tiếp là gì?</h2>
<p>Là sở hữu thông qua một tổ chức khác. Ví dụ: ông A sở hữu 60% Công ty X; Công ty X góp 50% vốn vào Công ty Y. Khi đó ông A gián tiếp sở hữu 60% × 50% = 30% Công ty Y, nên là chủ sở hữu hưởng lợi của Công ty Y dù không trực tiếp góp vốn.</p>

<h2>3. Khi nào phải kê khai?</h2>
<ul>
<li><strong>Khi thành lập</strong>: kê khai trong hồ sơ đăng ký doanh nghiệp.</li>
<li><strong>Khi có thay đổi</strong>: thông báo với Cơ quan đăng ký kinh doanh trong <strong>10 ngày</strong> kể từ ngày thay đổi thông tin hoặc tỷ lệ sở hữu của chủ sở hữu hưởng lợi.</li>
<li><strong>Khi phát hiện kê khai thiếu, sai</strong>: kịp thời sửa đổi, bổ sung.</li>
<li><strong>Doanh nghiệp thành lập trước 01/7/2025</strong>: bổ sung thông tin cùng lần đăng ký thay đổi gần nhất, hoặc khi cơ quan nhà nước có yêu cầu.</li>
</ul>
<p>Doanh nghiệp niêm yết và đăng ký giao dịch chứng khoán không phải kê khai theo cách này vì đã công bố thông tin theo pháp luật chứng khoán.</p>

<h2>4. Thông tin cần kê khai</h2>
<p>Họ tên, ngày sinh, quốc tịch, dân tộc, giới tính, địa chỉ liên lạc, số giấy tờ pháp lý cá nhân, tỷ lệ sở hữu hoặc thông tin về quyền chi phối. Doanh nghiệp phải <strong>lưu giữ</strong> thông tin này tối thiểu 5 năm kể từ ngày giải thể hoặc phá sản và cung cấp khi cơ quan có thẩm quyền yêu cầu.</p>

<h2>5. Rủi ro khi kê khai không trung thực</h2>
<p>Kê khai sai, đứng tên hộ, góp vốn không thật có thể bị xử phạt hành chính, bị yêu cầu đăng ký lại, và trong trường hợp liên quan rửa tiền có thể bị xử lý hình sự. Đây cũng là thông tin ngân hàng thường đối chiếu khi mở tài khoản, cấp tín dụng.</p>

<h2>Mẹo thực tế</h2>
<ul>
<li>Vẽ sơ đồ sở hữu nếu có tổ chức góp vốn – tính tỷ lệ gián tiếp trước khi nộp hồ sơ.</li>
<li>Thống nhất nội dung kê khai với thoả thuận góp vốn, điều lệ.</li>
<li>Gộp việc bổ sung chủ sở hữu hưởng lợi vào lần thay đổi đăng ký kinh doanh gần nhất để tiết kiệm thủ tục.</li>
</ul>
<p>Cần kê khai bổ sung hoặc đang có thay đổi thành viên, cổ đông? Xem [[them-giam-thanh-vien-co-dong|dịch vụ thay đổi thành viên, cổ đông]] hoặc toàn bộ [[nhom:thay-doi-giay-phep|dịch vụ thay đổi giấy phép kinh doanh]].</p>',
	),

	// ===================== HỘ KINH DOANH CÁ THỂ =====================
	array(
		'slug'    => 'thu-tuc-dang-ky-ho-kinh-doanh-2026',
		'cat'     => array( 'ho-kinh-doanh-ca-the' ),
		'title'   => 'Thủ tục đăng ký hộ kinh doanh năm 2026: hồ sơ, nơi nộp và thời gian',
		'excerpt' => 'Ai được đăng ký hộ kinh doanh, nộp hồ sơ ở đâu sau khi bỏ cấp huyện, giấy tờ cần chuẩn bị, thời hạn 3 ngày làm việc, nộp trực tuyến và những việc phải làm sau khi có giấy chứng nhận theo Nghị định 168/2025.',
		'content' => $u . '
<p>Hộ kinh doanh là hình thức phù hợp với cửa hàng, quán ăn, cơ sở dịch vụ quy mô nhỏ. Từ 01/7/2025, thủ tục đăng ký hộ kinh doanh thực hiện theo [[tvpl:nd168]] và đã chuyển về <strong>cấp xã</strong> do không còn cấp huyện.</p>

<h2>1. Ai được đăng ký hộ kinh doanh?</h2>
<ul>
<li>Cá nhân hoặc các thành viên hộ gia đình là công dân Việt Nam, có đầy đủ năng lực hành vi dân sự.</li>
<li>Mỗi cá nhân, thành viên hộ gia đình chỉ được đăng ký <strong>một hộ kinh doanh</strong> trên phạm vi toàn quốc.</li>
<li>Chủ hộ chịu trách nhiệm bằng <strong>toàn bộ tài sản</strong> của mình đối với hoạt động kinh doanh của hộ.</li>
</ul>
<p>Người buôn bán rong, kinh doanh thời vụ, làm dịch vụ có thu nhập thấp nhìn chung không phải đăng ký, trừ ngành nghề kinh doanh có điều kiện.</p>

<h2>2. Nộp hồ sơ ở đâu?</h2>
<p>Tại <strong>Cơ quan đăng ký kinh doanh cấp xã</strong> nơi hộ kinh doanh đặt trụ sở. Điểm mới: bạn có thể nộp và nhận kết quả tại cơ quan đăng ký kinh doanh cấp xã <strong>bất kỳ</strong> trong cùng tỉnh, thành phố. Hồ sơ cũng có thể nộp trực tuyến qua Cổng Dịch vụ công quốc gia.</p>

<h2>3. Hồ sơ gồm những gì?</h2>
<ol>
<li>Giấy đề nghị đăng ký hộ kinh doanh (mẫu ban hành kèm Thông tư 68/2025/TT-BTC).</li>
<li>Văn bản uỷ quyền và giấy tờ của người được uỷ quyền (nếu không tự đi nộp).</li>
<li>Biên bản họp thành viên hộ gia đình về việc thành lập hộ kinh doanh (nếu các thành viên hộ gia đình cùng đăng ký).</li>
</ol>
<p>Không cần nộp bản sao căn cước – cơ quan đăng ký tự khai thác từ cơ sở dữ liệu quốc gia về dân cư.</p>

<h2>4. Những thông tin cần quyết định trước</h2>
<ul>
<li><strong>Tên hộ kinh doanh</strong>: gồm cụm “Hộ kinh doanh” và tên riêng; không trùng tên hộ kinh doanh khác trong phạm vi cấp xã.</li>
<li><strong>Địa chỉ trụ sở</strong>: ghi theo tên xã, phường, tỉnh mới sau sắp xếp đơn vị hành chính.</li>
<li><strong>Ngành nghề</strong>: hộ kinh doanh được kinh doanh nhiều ngành, trừ ngành pháp luật cấm và một số ngành chỉ dành cho doanh nghiệp.</li>
<li><strong>Vốn kinh doanh</strong>: tự kê khai, không có mức tối thiểu.</li>
</ul>

<h2>5. Thời gian giải quyết</h2>
<p>Trong <strong>3 ngày làm việc</strong> kể từ ngày nhận hồ sơ hợp lệ, cơ quan đăng ký kinh doanh cấp xã cấp Giấy chứng nhận đăng ký hộ kinh doanh và gửi thông tin sang cơ quan thuế quản lý. Mã số hộ kinh doanh đồng thời là mã số thuế.</p>

<h2>6. Sau khi có giấy chứng nhận cần làm gì?</h2>
<ul>
<li>Treo biển hiệu tại trụ sở.</li>
<li>Tự kê khai doanh thu, nộp thuế: từ 2026 không còn thuế khoán và không còn lệ phí môn bài; doanh thu đến 500 triệu đồng/năm không phải nộp thuế GTGT, TNCN nhưng vẫn phải thông báo doanh thu với cơ quan thuế. Chi tiết xem bài Thuế hộ kinh doanh năm 2026 trong chuyên mục Thuế hộ, cá nhân kinh doanh.</li>
<li>Mở sổ doanh thu theo [[tvpl:tt152]].</li>
<li>Đăng ký hoá đơn điện tử nếu doanh thu từ 1 tỷ đồng/năm trở lên hoặc khi khách hàng cần hoá đơn.</li>
</ul>

<h2>Lưu ý về địa điểm kinh doanh</h2>
<p>Hộ kinh doanh được mở thêm địa điểm kinh doanh trên phạm vi cả nước; mỗi địa điểm phải được thông báo với cơ quan thuế theo [[tvpl:nd68]].</p>
<p>Không muốn tự làm? [[dang-ky-ho-kinh-doanh|Dịch vụ đăng ký hộ kinh doanh]] lo trọn từ hồ sơ đến khai thuế ban đầu; [[ke-toan-ho-kinh-doanh|kế toán hộ kinh doanh]] giúp ghi sổ và khai doanh thu hằng quý.</p>',
	),

	array(
		'slug'    => 'ho-kinh-doanh-va-cong-ty-khac-nhau-the-nao',
		'cat'     => array( 'ho-kinh-doanh-ca-the', 'thu-tuc-thanh-lap' ),
		'title'   => 'Hộ kinh doanh và công ty khác nhau thế nào? Nên chọn mô hình nào năm 2026',
		'excerpt' => 'So sánh hộ kinh doanh và công ty TNHH về trách nhiệm tài sản, thuế, hoá đơn, sổ sách, lao động, khả năng ký hợp đồng với doanh nghiệp – cập nhật chính sách bỏ thuế khoán, ngưỡng 500 triệu và miễn thuế TNDN 3 năm cho doanh nghiệp mới.',
		'content' => $u . '
<p>Năm 2026, khoảng cách giữa hộ kinh doanh và doanh nghiệp nhỏ đã thu hẹp đáng kể: hộ kinh doanh không còn thuế khoán, phải tự kê khai và ghi sổ; trong khi doanh nghiệp nhỏ được hưởng nhiều ưu đãi mới. Bảng so sánh dưới đây giúp bạn chọn mô hình phù hợp.</p>

<h2>Bảng so sánh</h2>
<table>
<thead><tr><th>Tiêu chí</th><th>Hộ kinh doanh</th><th>Công ty TNHH</th></tr></thead>
<tbody>
<tr><td>Tư cách pháp nhân</td><td>Không</td><td>Có</td></tr>
<tr><td>Trách nhiệm</td><td>Vô hạn – bằng toàn bộ tài sản của chủ hộ</td><td>Hữu hạn – trong phạm vi vốn góp</td></tr>
<tr><td>Nơi đăng ký</td><td>Cơ quan đăng ký kinh doanh cấp xã</td><td>Phòng Đăng ký kinh doanh cấp tỉnh</td></tr>
<tr><td>Thuế khi doanh thu ≤ 500 triệu/năm</td><td>Không phải nộp thuế GTGT, TNCN</td><td>Vẫn khai thuế GTGT, TNDN theo quy định</td></tr>
<tr><td>Thuế khi doanh thu lớn</td><td>Thuế GTGT theo tỷ lệ; thuế TNCN theo tỷ lệ hoặc theo thu nhập</td><td>Thuế GTGT khấu trừ hoặc trực tiếp; thuế TNDN 15% – 17% – 20%</td></tr>
<tr><td>Hoá đơn</td><td>Hoá đơn có mã/từ máy tính tiền khi doanh thu từ 1 tỷ đồng/năm</td><td>Hoá đơn điện tử ngay từ đầu</td></tr>
<tr><td>Sổ sách</td><td>Sổ đơn giản theo Thông tư 152/2025</td><td>Chế độ kế toán doanh nghiệp, báo cáo tài chính năm</td></tr>
<tr><td>Ưu đãi khi mới thành lập</td><td>–</td><td>Doanh nghiệp nhỏ và vừa đăng ký lần đầu được miễn thuế TNDN 3 năm</td></tr>
</tbody>
</table>

<h2>Ưu điểm của hộ kinh doanh</h2>
<ul>
<li>Thủ tục đơn giản, đăng ký ở cấp xã, chi phí thấp.</li>
<li>Doanh thu đến 500 triệu đồng/năm không phải nộp thuế GTGT, TNCN theo [[tvpl:nd68]].</li>
<li>Sổ sách gọn, chủ hộ có thể tự ghi theo [[tvpl:tt152]].</li>
</ul>

<h2>Hạn chế của hộ kinh doanh</h2>
<ul>
<li>Chịu trách nhiệm vô hạn: nợ của hộ có thể phải trả bằng nhà, đất của chủ hộ.</li>
<li>Khó ký hợp đồng với doanh nghiệp lớn, khó tham gia đấu thầu, khó vay vốn quy mô lớn.</li>
<li>Không được khấu trừ thuế GTGT đầu vào, nên giá bán cho doanh nghiệp kém cạnh tranh.</li>
<li>Doanh thu trên 3 tỷ đồng phải tính thuế TNCN theo thu nhập (17% – 20%) – sổ sách đã gần như doanh nghiệp.</li>
</ul>

<h2>Ưu điểm của công ty</h2>
<ul>
<li>Tài sản cá nhân được bảo vệ, dễ gọi thêm người góp vốn.</li>
<li>Uy tín với khách hàng doanh nghiệp, được khấu trừ thuế GTGT.</li>
<li>Được miễn thuế TNDN 3 năm nếu là doanh nghiệp nhỏ và vừa đăng ký lần đầu, theo [[tvpl:nq198|Nghị quyết 198/2025/QH15]]; thuế suất TNDN chỉ 15% với doanh thu đến 3 tỷ đồng/năm theo [[tvpl:ltndn]].</li>
</ul>

<h2>Vậy nên chọn mô hình nào?</h2>
<ul>
<li><strong>Bán lẻ, dịch vụ cho người tiêu dùng, doanh thu dưới 1 tỷ đồng/năm</strong> → hộ kinh doanh là đủ.</li>
<li><strong>Khách hàng chủ yếu là doanh nghiệp, cần hoá đơn GTGT</strong> → nên lập công ty.</li>
<li><strong>Doanh thu từ 3 tỷ đồng/năm</strong> hoặc có tài sản cá nhân lớn cần bảo vệ → nên chuyển lên công ty.</li>
<li><strong>Có người cùng góp vốn</strong> → công ty TNHH hai thành viên trở lên hoặc cổ phần.</li>
</ul>
<p>Bạn đang là hộ kinh doanh và muốn chuyển lên? Xem bài Chuyển hộ kinh doanh lên doanh nghiệp trong chuyên mục này, hoặc liên hệ [[thanh-lap-cong-ty-tnhh|dịch vụ thành lập công ty TNHH]] để được tính thử số thuế theo hai mô hình.</p>',
	),

	array(
		'slug'    => 'chuyen-ho-kinh-doanh-len-doanh-nghiep',
		'cat'     => array( 'ho-kinh-doanh-ca-the', 'thu-tuc-thanh-lap' ),
		'title'   => 'Chuyển hộ kinh doanh lên doanh nghiệp: hồ sơ, các bước và ưu đãi thuế',
		'excerpt' => 'Thủ tục chuyển đổi hộ kinh doanh thành công ty theo Điều 27 Nghị định 168/2025: hồ sơ, nơi nộp, kế thừa quyền và nghĩa vụ, chốt thuế hộ kinh doanh, hoá đơn và ưu đãi miễn thuế TNDN cho doanh nghiệp nhỏ và vừa.',
		'content' => $u . '
<p>Khi doanh thu tăng, khách hàng yêu cầu hoá đơn GTGT hoặc cần bảo vệ tài sản cá nhân, chuyển hộ kinh doanh lên doanh nghiệp là bước đi hợp lý. Thủ tục được quy định tại Điều 27 [[tvpl:nd168]] và không phức tạp hơn thành lập công ty mới.</p>

<h2>1. Được chuyển sang loại hình nào?</h2>
<p>Hộ kinh doanh có thể đăng ký thành lập doanh nghiệp tư nhân, công ty TNHH, công ty cổ phần hoặc công ty hợp danh. Phổ biến nhất là <strong>công ty TNHH một thành viên</strong> do chủ hộ làm chủ sở hữu.</p>

<h2>2. Hồ sơ</h2>
<ul>
<li>Hồ sơ đăng ký thành lập doanh nghiệp theo loại hình đã chọn (giấy đề nghị, điều lệ, danh sách thành viên/cổ đông, thông tin chủ sở hữu hưởng lợi…).</li>
<li>Bản sao Giấy chứng nhận đăng ký hộ kinh doanh.</li>
<li>Giấy đề nghị ghi rõ thông tin hộ kinh doanh được chuyển đổi (tên, mã số hộ kinh doanh, mã số thuế).</li>
</ul>
<p>Hồ sơ nộp tại Phòng Đăng ký kinh doanh cấp tỉnh nơi dự kiến đặt trụ sở chính; thời hạn giải quyết 3 ngày làm việc như thành lập mới.</p>

<h2>3. Doanh nghiệp kế thừa gì từ hộ kinh doanh?</h2>
<p>Doanh nghiệp được chuyển đổi <strong>kế thừa toàn bộ quyền và nghĩa vụ</strong> của hộ kinh doanh: hợp đồng đang thực hiện, khoản nợ, nghĩa vụ thuế. Chủ hộ kinh doanh cam kết chịu trách nhiệm cá nhân bằng toàn bộ tài sản với các khoản nợ phát sinh trước khi chuyển đổi. Kể từ ngày được cấp giấy chứng nhận đăng ký doanh nghiệp, hộ kinh doanh <strong>chấm dứt hoạt động</strong>.</p>

<h2>4. Các bước thực tế</h2>
<ol>
<li>Chốt doanh thu, kê khai và nộp đủ thuế của hộ kinh doanh đến thời điểm chuyển đổi.</li>
<li>Chọn tên, trụ sở, ngành nghề, vốn điều lệ cho doanh nghiệp (có thể giữ tên riêng cũ).</li>
<li>Nộp hồ sơ, nhận giấy chứng nhận đăng ký doanh nghiệp.</li>
<li>Khắc dấu (nếu cần), mở tài khoản ngân hàng, mua chữ ký số, đăng ký hoá đơn điện tử.</li>
<li>Chuyển tài sản, hàng tồn kho của hộ sang doanh nghiệp (làm biên bản góp vốn bằng tài sản).</li>
<li>Huỷ/ngừng sử dụng hoá đơn của hộ kinh doanh; thông báo cho khách hàng, nhà cung cấp.</li>
</ol>

<h2>5. Ưu đãi khi chuyển lên doanh nghiệp</h2>
<ul>
<li>Doanh nghiệp nhỏ và vừa đăng ký kinh doanh lần đầu được <strong>miễn thuế TNDN 3 năm</strong> liên tục kể từ ngày được cấp giấy chứng nhận đăng ký doanh nghiệp, theo [[tvpl:nq198|Nghị quyết 198/2025/QH15]] và Nghị định 20/2026/NĐ-CP.</li>
<li>Không phải nộp lệ phí môn bài (đã bãi bỏ từ 2026).</li>
<li>Được hỗ trợ tư vấn, thủ tục, đào tạo kế toán theo chính sách hỗ trợ doanh nghiệp nhỏ và vừa.</li>
</ul>
<p>Ưu đãi miễn thuế áp dụng khi doanh nghiệp đáp ứng tiêu chí doanh nghiệp nhỏ và vừa và kê khai đúng; vẫn phải nộp tờ khai, quyết toán thuế TNDN hằng năm (ghi số thuế được miễn).</p>

<h2>6. Sổ sách sau chuyển đổi</h2>
<p>Doanh nghiệp phải tổ chức kế toán theo [[tvpl:lkt]], chọn chế độ kế toán (thường là Thông tư 133/2016 cho doanh nghiệp nhỏ), lập báo cáo tài chính năm. Đây là phần nhiều chủ hộ lo ngại nhất – gói [[ke-toan-tron-goi|kế toán trọn gói]] giải quyết toàn bộ với chi phí cố định hằng tháng.</p>
<p>Cần làm hồ sơ chuyển đổi nhanh? [[thanh-lap-cong-ty-tnhh|Dịch vụ thành lập công ty TNHH]] hỗ trợ trọn gói từ chốt thuế hộ kinh doanh đến phát hành hoá đơn đầu tiên.</p>',
	),

	array(
		'slug'    => 'tam-ngung-cham-dut-ho-kinh-doanh',
		'cat'     => array( 'ho-kinh-doanh-ca-the', 'tam-ngung-giai-the' ),
		'title'   => 'Tạm ngừng, thay đổi và chấm dứt hoạt động hộ kinh doanh: thủ tục cần biết',
		'excerpt' => 'Hướng dẫn hộ kinh doanh đăng ký thay đổi nội dung, tạm ngừng kinh doanh và chấm dứt hoạt động theo Nghị định 168/2025: hồ sơ, thời hạn, nghĩa vụ thuế cần hoàn thành và mức phạt khi không thông báo.',
		'content' => $u . '
<p>Nghỉ bán một thời gian, chuyển chỗ, đổi ngành hay dừng hẳn – mỗi trường hợp hộ kinh doanh đều phải thông báo với cơ quan đăng ký kinh doanh cấp xã và cơ quan thuế. Làm đúng giúp tránh bị tính thuế cho thời gian không kinh doanh và tránh bị phạt.</p>

<h2>1. Thay đổi nội dung đăng ký hộ kinh doanh</h2>
<p>Khi thay đổi tên, địa chỉ trụ sở, ngành nghề, vốn kinh doanh, chủ hộ hoặc thành viên hộ gia đình, hộ kinh doanh gửi thông báo thay đổi đến cơ quan đăng ký kinh doanh cấp xã nơi đã đăng ký. Hồ sơ gồm:</p>
<ul>
<li>Thông báo thay đổi nội dung đăng ký hộ kinh doanh.</li>
<li>Biên bản họp thành viên hộ gia đình (nếu hộ do các thành viên hộ gia đình đăng ký).</li>
<li>Văn bản uỷ quyền (nếu có).</li>
</ul>
<p>Chuyển trụ sở sang xã khác: nộp hồ sơ tại cơ quan đăng ký kinh doanh nơi đặt trụ sở mới. Thời hạn giải quyết 3 ngày làm việc theo [[tvpl:nd168]].</p>

<h2>2. Tạm ngừng kinh doanh</h2>
<ul>
<li>Hộ kinh doanh thông báo tạm ngừng với cơ quan đăng ký kinh doanh cấp xã <strong>trước thời điểm tạm ngừng</strong>; thông tin được chuyển sang cơ quan thuế.</li>
<li>Trong thời gian tạm ngừng, hộ không phải khai thuế cho kỳ tạm ngừng trọn vẹn, nhưng phải nộp đủ số thuế còn nợ.</li>
<li>Muốn kinh doanh trở lại sớm hơn thời hạn đã thông báo, hộ gửi thông báo tiếp tục kinh doanh.</li>
</ul>
<p>Cách tạm ngừng và quản lý thuế trong thời gian tạm ngừng được hướng dẫn thêm tại [[tvpl:nd68]].</p>

<h2>3. Chấm dứt hoạt động hộ kinh doanh</h2>
<ol>
<li><strong>Hoàn thành nghĩa vụ thuế</strong>: khai doanh thu, nộp thuế đến ngày chấm dứt; thông báo chấm dứt các địa điểm kinh doanh với cơ quan thuế trong 10 ngày làm việc kể từ ngày chấm dứt địa điểm.</li>
<li><strong>Thanh toán hết các khoản nợ</strong>, kể cả nợ lương người lao động.</li>
<li><strong>Nộp thông báo chấm dứt hoạt động</strong> đến cơ quan đăng ký kinh doanh cấp xã trong 5 ngày làm việc kể từ ngày thanh toán hết nợ.</li>
<li>Cơ quan đăng ký kinh doanh giải quyết trong 5 ngày làm việc và cập nhật tình trạng hộ kinh doanh trên hệ thống.</li>
</ol>
<p>Trường hợp hộ kinh doanh chuyển lên doanh nghiệp, không cần làm thủ tục chấm dứt riêng – hộ tự chấm dứt khi doanh nghiệp được cấp giấy chứng nhận.</p>

<h2>4. Không thông báo thì sao?</h2>
<ul>
<li>Ngừng kinh doanh mà không thông báo: cơ quan thuế vẫn ghi nhận đang hoạt động, hộ có thể bị phạt vì không khai thuế, không thông báo doanh thu.</li>
<li>Kinh doanh không đúng địa chỉ, ngành nghề đã đăng ký; không đăng ký thay đổi khi thay đổi nội dung: bị xử phạt theo [[tvpl:nd122]].</li>
</ul>

<h2>Lời khuyên</h2>
<ul>
<li>Nghỉ ngắn ngày (vài tuần) không nhất thiết phải tạm ngừng – chỉ cần khai doanh thu đúng thực tế.</li>
<li>Nghỉ cả quý trở lên: nên thông báo tạm ngừng để không phải khai thuế các kỳ đó.</li>
<li>Lưu sổ sách, hoá đơn tối thiểu 5 năm kể cả sau khi chấm dứt.</li>
</ul>
<p>Cần làm thủ tục nhanh? Liên hệ [[dang-ky-ho-kinh-doanh|dịch vụ đăng ký hộ kinh doanh]] – chuyên viên xử lý cả phần đăng ký và chốt thuế.</p>',
	),

	// ===================== CÔNG TY VỐN NƯỚC NGOÀI =====================
	array(
		'slug'    => 'thu-tuc-thanh-lap-cong-ty-von-nuoc-ngoai-2026',
		'cat'     => array( 'cong-ty-von-nuoc-ngoai' ),
		'title'   => 'Thủ tục thành lập công ty vốn nước ngoài năm 2026 theo Luật Đầu tư mới',
		'excerpt' => 'Thành lập công ty có vốn đầu tư nước ngoài theo Luật Đầu tư 2025 và Nghị định 96/2026: hai cách làm mới, hồ sơ cấp Giấy chứng nhận đăng ký đầu tư trong 10 ngày làm việc, đăng ký doanh nghiệp và các việc sau cấp phép.',
		'content' => $u . '
<p>[[tvpl:ldt2025]] có hiệu lực từ 01/3/2026, thay thế Luật Đầu tư 2020, cùng với [[tvpl:nd96]] (hiệu lực từ 31/3/2026) đã thay đổi đáng kể cách nhà đầu tư nước ngoài thành lập công ty tại Việt Nam. Điểm mới lớn nhất: nhà đầu tư nước ngoài được <strong>thành lập công ty trước, làm thủ tục dự án đầu tư sau</strong>.</p>

<h2>1. Hai cách thành lập</h2>
<h3>Cách 1: Thành lập tổ chức kinh tế trước</h3>
<p>Nhà đầu tư nước ngoài làm thủ tục đăng ký doanh nghiệp trước, chưa cần có dự án đầu tư. Điều kiện: phải đáp ứng <strong>điều kiện tiếp cận thị trường</strong> đối với nhà đầu tư nước ngoài (Điều 8 Luật Đầu tư 2025) ngay khi thành lập. Khi triển khai dự án cụ thể, công ty làm thủ tục cấp Giấy chứng nhận đăng ký đầu tư (IRC).</p>
<h3>Cách 2: Cấp IRC trước, đăng ký doanh nghiệp sau</h3>
<p>Như trước đây: nhà đầu tư xin IRC cho dự án, sau đó đăng ký thành lập công ty để thực hiện dự án. Cách này vẫn phù hợp với dự án cần đất, nhà xưởng hoặc ưu đãi đầu tư.</p>

<h2>2. Hồ sơ cấp Giấy chứng nhận đăng ký đầu tư</h2>
<ul>
<li>Văn bản đề nghị thực hiện dự án đầu tư.</li>
<li>Tài liệu về tư cách pháp lý của nhà đầu tư (hộ chiếu với cá nhân; giấy chứng nhận thành lập với tổ chức – hợp pháp hoá lãnh sự, dịch công chứng).</li>
<li>Tài liệu chứng minh năng lực tài chính: báo cáo tài chính 2 năm gần nhất, cam kết hỗ trợ tài chính của công ty mẹ, sao kê số dư ngân hàng…</li>
<li>Đề xuất dự án: mục tiêu, quy mô, vốn, tiến độ, nhu cầu lao động.</li>
<li>Bản sao giấy tờ về địa điểm (hợp đồng thuê văn phòng, nhà xưởng).</li>
</ul>
<p>Với dự án không thuộc diện chấp thuận chủ trương đầu tư, cơ quan đăng ký đầu tư cấp IRC trong <strong>10 ngày làm việc</strong> kể từ ngày nhận hồ sơ hợp lệ (Điều 39 Nghị định 96/2026). Hồ sơ có thể nộp trực tuyến trên Hệ thống thông tin quốc gia về đầu tư.</p>

<h2>3. Đăng ký doanh nghiệp</h2>
<p>Hồ sơ đăng ký doanh nghiệp tương tự công ty trong nước theo [[tvpl:nd168]], kèm thông tin nhà đầu tư nước ngoài và chủ sở hữu hưởng lợi. Thời hạn giải quyết 3 ngày làm việc. Lưu ý doanh nghiệp phải luôn có ít nhất một người đại diện theo pháp luật cư trú tại Việt Nam.</p>

<h2>4. Việc cần làm sau khi có giấy phép</h2>
<ol>
<li>Mở <strong>tài khoản vốn đầu tư trực tiếp</strong> tại ngân hàng được phép; mọi khoản góp vốn, chuyển lợi nhuận ra nước ngoài đi qua tài khoản này.</li>
<li>Góp đủ vốn trong thời hạn cam kết tại IRC và điều lệ.</li>
<li>Khắc dấu, chữ ký số, hoá đơn điện tử, khai thuế ban đầu.</li>
<li>Xin giấy phép con nếu ngành nghề yêu cầu (ví dụ giấy phép kinh doanh bán lẻ).</li>
<li>Đăng ký lao động, giấy phép lao động cho người nước ngoài làm việc.</li>
<li>Thực hiện chế độ báo cáo đầu tư định kỳ trên Hệ thống thông tin quốc gia về đầu tư.</li>
</ol>

<h2>5. Kinh nghiệm để hồ sơ không bị trả lại</h2>
<ul>
<li>Kiểm tra cam kết WTO, hiệp định thương mại cho ngành định làm trước khi chọn mục tiêu dự án.</li>
<li>Năng lực tài chính phải tương xứng với vốn đăng ký.</li>
<li>Giấy tờ nước ngoài phải hợp pháp hoá lãnh sự (trừ nước được miễn) và còn hiệu lực.</li>
</ul>
<p>Chúng tôi hỗ trợ trọn gói từ tư vấn ngành nghề, xin IRC, đăng ký doanh nghiệp đến mở tài khoản vốn: xem [[thanh-lap-cong-ty-von-nuoc-ngoai|dịch vụ thành lập công ty vốn nước ngoài]].</p>',
	),

	array(
		'slug'    => 'nha-dau-tu-nuoc-ngoai-gop-von-mua-phan-von-gop',
		'cat'     => array( 'cong-ty-von-nuoc-ngoai', 'thay-doi-dang-ky-kinh-doanh' ),
		'title'   => 'Nhà đầu tư nước ngoài góp vốn, mua phần vốn góp công ty Việt Nam: khi nào phải đăng ký?',
		'excerpt' => 'Các trường hợp nhà đầu tư nước ngoài phải đăng ký góp vốn, mua cổ phần, mua phần vốn góp theo Luật Đầu tư 2025 (vượt 50% vốn điều lệ, ngành có điều kiện tiếp cận thị trường, đất khu vực quốc phòng), hồ sơ và các bước tiếp theo.',
		'content' => $u . '
<p>Ngoài việc thành lập công ty mới, nhà đầu tư nước ngoài có thể <strong>góp vốn, mua cổ phần, mua phần vốn góp</strong> của một công ty Việt Nam đang hoạt động. Cách này nhanh hơn vì công ty đã có pháp nhân, khách hàng, giấy phép. Tuy nhiên, một số trường hợp phải đăng ký với cơ quan đăng ký đầu tư trước khi thay đổi thành viên, cổ đông theo [[tvpl:ldt2025]].</p>

<h2>1. Khi nào phải đăng ký góp vốn?</h2>
<p>Nhà đầu tư nước ngoài phải làm thủ tục đăng ký góp vốn, mua cổ phần, mua phần vốn góp <strong>trước khi</strong> thay đổi thành viên, cổ đông nếu thuộc một trong các trường hợp:</p>
<ul>
<li>Giao dịch làm <strong>tăng tỷ lệ sở hữu của nhà đầu tư nước ngoài lên trên 50%</strong> vốn điều lệ (từ ≤ 50% lên trên 50%), hoặc tiếp tục tăng tỷ lệ khi đã sở hữu trên 50%.</li>
<li>Công ty kinh doanh ngành, nghề <strong>tiếp cận thị trường có điều kiện</strong> đối với nhà đầu tư nước ngoài, và giao dịch làm tăng tỷ lệ sở hữu nước ngoài.</li>
<li>Công ty có quyền sử dụng đất tại đảo, xã biên giới, xã ven biển hoặc khu vực khác có ảnh hưởng đến quốc phòng, an ninh.</li>
</ul>
<p>Các trường hợp còn lại: không cần đăng ký góp vốn, chỉ làm thủ tục thay đổi thành viên, cổ đông tại Phòng Đăng ký kinh doanh theo [[tvpl:nd168]].</p>

<h2>2. Điều kiện nhà đầu tư nước ngoài phải đáp ứng</h2>
<ul>
<li>Điều kiện tiếp cận thị trường (tỷ lệ sở hữu tối đa, hình thức đầu tư, phạm vi hoạt động…) theo Điều 8 Luật Đầu tư 2025 và cam kết quốc tế.</li>
<li>Bảo đảm quốc phòng, an ninh.</li>
<li>Quy định về đất đai nếu công ty có quyền sử dụng đất.</li>
</ul>

<h2>3. Hồ sơ đăng ký góp vốn</h2>
<ul>
<li>Văn bản đăng ký góp vốn, mua cổ phần, mua phần vốn góp (thông tin công ty, ngành nghề, tỷ lệ sở hữu trước và sau giao dịch).</li>
<li>Tài liệu pháp lý của nhà đầu tư nước ngoài (hợp pháp hoá lãnh sự, dịch công chứng).</li>
<li>Thoả thuận nguyên tắc về việc góp vốn, mua cổ phần, mua phần vốn góp.</li>
<li>Giấy tờ về quyền sử dụng đất (nếu thuộc trường hợp đất khu vực quốc phòng, an ninh).</li>
</ul>
<p>Thủ tục, thời hạn chi tiết hướng dẫn tại [[tvpl:nd96]].</p>

<h2>4. Các bước sau khi được chấp thuận</h2>
<ol>
<li>Mở tài khoản vốn đầu tư trực tiếp (công ty có trên 50% vốn nước ngoài, hoặc thuộc trường hợp bắt buộc) hoặc dùng tài khoản đầu tư gián tiếp theo quy định ngoại hối.</li>
<li>Thanh toán tiền mua phần vốn góp, cổ phần qua tài khoản – không dùng tiền mặt.</li>
<li>Đăng ký thay đổi thành viên, cổ đông, người đại diện, chủ sở hữu hưởng lợi tại Phòng Đăng ký kinh doanh.</li>
<li>Bên chuyển nhượng kê khai, nộp thuế TNCN (cá nhân) hoặc thuế TNDN (tổ chức) từ chuyển nhượng vốn.</li>
<li>Cập nhật thông tin trên hoá đơn, tài khoản ngân hàng, hợp đồng.</li>
</ol>

<h2>5. Sau khi trở thành doanh nghiệp có vốn nước ngoài</h2>
<p>Khi nhà đầu tư nước ngoài nắm trên 50% vốn điều lệ, công ty được xác định là tổ chức kinh tế có vốn đầu tư nước ngoài và phải tuân thủ thủ tục đầu tư như nhà đầu tư nước ngoài khi đầu tư dự án mới, góp vốn vào công ty khác. Báo cáo tài chính năm phải được kiểm toán.</p>
<p>Giao dịch góp vốn nước ngoài có nhiều bước liên quan đến cả đầu tư, doanh nghiệp, ngoại hối và thuế. Liên hệ [[thanh-lap-cong-ty-von-nuoc-ngoai|dịch vụ đầu tư nước ngoài]] để được rà soát cấu trúc giao dịch trước khi ký.</p>',
	),

	array(
		'slug'    => 'dieu-kien-tiep-can-thi-truong-nha-dau-tu-nuoc-ngoai',
		'cat'     => array( 'cong-ty-von-nuoc-ngoai' ),
		'title'   => 'Điều kiện tiếp cận thị trường cho nhà đầu tư nước ngoài: ngành nào bị hạn chế?',
		'excerpt' => 'Điều kiện tiếp cận thị trường đối với nhà đầu tư nước ngoài theo Luật Đầu tư 2025: ngành chưa được tiếp cận, ngành tiếp cận có điều kiện, tỷ lệ sở hữu tối đa, cam kết WTO và cách tra cứu trước khi đầu tư.',
		'content' => $u . '
<p>Không phải ngành nào nhà đầu tư nước ngoài cũng được kinh doanh như nhà đầu tư trong nước. Trước khi chọn ngành nghề, cần kiểm tra <strong>điều kiện tiếp cận thị trường</strong> theo Điều 8 [[tvpl:ldt2025]] – đặc biệt quan trọng từ năm 2026, khi nhà đầu tư nước ngoài được thành lập công ty trước khi có dự án và phải đáp ứng điều kiện này ngay từ lúc đăng ký doanh nghiệp.</p>

<h2>1. Ba nhóm ngành</h2>
<ol>
<li><strong>Ngành chưa được tiếp cận thị trường</strong>: nhà đầu tư nước ngoài không được đầu tư (ví dụ một số dịch vụ liên quan đến báo chí, điều tra, an ninh…).</li>
<li><strong>Ngành tiếp cận thị trường có điều kiện</strong>: được đầu tư nhưng phải đáp ứng điều kiện về tỷ lệ sở hữu, hình thức đầu tư (ví dụ bắt buộc liên doanh), phạm vi hoạt động, năng lực nhà đầu tư, đối tác Việt Nam…</li>
<li><strong>Các ngành còn lại</strong>: được tiếp cận thị trường như nhà đầu tư trong nước.</li>
</ol>
<p>Danh mục cụ thể do Chính phủ công bố tại [[tvpl:nd96]] và được cập nhật trên Cổng thông tin quốc gia về đầu tư.</p>

<h2>2. Các loại điều kiện thường gặp</h2>
<ul>
<li><strong>Tỷ lệ sở hữu</strong>: ví dụ một số dịch vụ vận tải, viễn thông, quảng cáo chỉ cho phép sở hữu tối đa một tỷ lệ nhất định hoặc phải liên doanh với đối tác Việt Nam.</li>
<li><strong>Hình thức đầu tư</strong>: chỉ được thành lập liên doanh, hoặc chỉ được hợp tác kinh doanh.</li>
<li><strong>Phạm vi hoạt động</strong>: ví dụ chỉ được phục vụ khách hàng là doanh nghiệp có vốn nước ngoài, chỉ được hoạt động tại một số địa bàn.</li>
<li><strong>Năng lực nhà đầu tư, đối tác</strong>: kinh nghiệm, quy mô, chứng chỉ hành nghề.</li>
</ul>

<h2>3. Cam kết quốc tế áp dụng thế nào?</h2>
<p>Điều kiện tiếp cận thị trường căn cứ vào luật Việt Nam và các điều ước quốc tế mà Việt Nam là thành viên (WTO, CPTPP, EVFTA…). Nhà đầu tư từ nước có hiệp định ưu đãi hơn có thể được áp dụng mức mở cửa cao hơn – cần xác định quốc tịch nhà đầu tư và hiệp định áp dụng ngay từ đầu.</p>

<h2>4. Phân biệt với ngành nghề kinh doanh có điều kiện</h2>
<p>Đây là hai lớp điều kiện khác nhau:</p>
<ul>
<li><strong>Điều kiện tiếp cận thị trường</strong>: chỉ áp dụng cho nhà đầu tư nước ngoài.</li>
<li><strong>Điều kiện đầu tư kinh doanh</strong> (giấy phép con, chứng chỉ, vốn pháp định…): áp dụng cho cả nhà đầu tư trong nước và nước ngoài.</li>
</ul>
<p>Một dự án có thể phải đáp ứng cả hai lớp, ví dụ kinh doanh bán lẻ hàng hoá vừa phải có giấy phép kinh doanh theo pháp luật thương mại, vừa phải đáp ứng cam kết mở cửa thị trường phân phối.</p>

<h2>5. Cách tra cứu trước khi đầu tư</h2>
<ol>
<li>Xác định mã ngành kinh tế Việt Nam và mã CPC (phân loại dịch vụ trong cam kết WTO) của hoạt động định làm.</li>
<li>Tra danh mục ngành chưa được tiếp cận và tiếp cận có điều kiện.</li>
<li>Tra biểu cam kết của hiệp định áp dụng cho quốc tịch nhà đầu tư.</li>
<li>Kiểm tra điều kiện đầu tư kinh doanh theo pháp luật chuyên ngành.</li>
<li>Kiểm tra quy định về đất đai, quốc phòng nếu dự án cần đất.</li>
</ol>

<h2>6. Không đáp ứng thì sao?</h2>
<p>Hồ sơ đăng ký doanh nghiệp, đăng ký đầu tư hoặc đăng ký góp vốn sẽ bị từ chối. Nếu doanh nghiệp đã hoạt động mà vi phạm điều kiện tiếp cận thị trường, cơ quan nhà đầu tư có thể yêu cầu khắc phục, điều chỉnh hoặc chấm dứt hoạt động.</p>
<p>Việc chọn ngành, mã CPC và cấu trúc sở hữu quyết định hồ sơ có được chấp thuận hay không. Hãy để [[thanh-lap-cong-ty-von-nuoc-ngoai|chuyên viên đầu tư nước ngoài]] rà soát trước khi bạn ký hợp đồng thuê văn phòng hay chuyển vốn.</p>',
	),

	array(
		'slug'    => 'nghia-vu-doanh-nghiep-fdi-sau-khi-thanh-lap',
		'cat'     => array( 'cong-ty-von-nuoc-ngoai', 'bao-cao-thue-tai-chinh' ),
		'title'   => 'Doanh nghiệp vốn nước ngoài cần làm gì sau khi thành lập? Báo cáo, thuế, kiểm toán',
		'excerpt' => 'Danh sách nghĩa vụ định kỳ của doanh nghiệp FDI: góp vốn qua tài khoản vốn đầu tư trực tiếp, báo cáo đầu tư quý, năm, khai thuế, báo cáo tài chính kiểm toán, giao dịch liên kết, chuyển lợi nhuận ra nước ngoài và giấy phép lao động.',
		'content' => $u . '
<p>Có Giấy chứng nhận đăng ký đầu tư và đăng ký doanh nghiệp mới là bước đầu. Doanh nghiệp có vốn đầu tư nước ngoài (FDI) có nhiều nghĩa vụ báo cáo hơn doanh nghiệp trong nước, và vi phạm thường bị phát hiện khi chuyển lợi nhuận về nước hoặc khi thanh tra thuế. Dưới đây là danh sách cần theo dõi.</p>

<h2>1. Góp vốn đúng hạn, đúng kênh</h2>
<ul>
<li>Mở <strong>tài khoản vốn đầu tư trực tiếp</strong> tại ngân hàng được phép; vốn góp chuyển từ nước ngoài vào tài khoản này.</li>
<li>Góp đủ vốn theo tiến độ tại Giấy chứng nhận đăng ký đầu tư và trong 90 ngày theo [[tvpl:ldn2020]] (với vốn điều lệ). Chậm góp vốn phải điều chỉnh vốn và có thể bị xử phạt.</li>
<li>Lưu chứng từ ngân hàng – đây là căn cứ để sau này chuyển lợi nhuận, chuyển nhượng vốn.</li>
</ul>

<h2>2. Báo cáo đầu tư định kỳ</h2>
<p>Tổ chức kinh tế thực hiện dự án đầu tư phải báo cáo tình hình thực hiện dự án theo quý và năm trên Hệ thống thông tin quốc gia về đầu tư, theo [[tvpl:ldt2025]] và [[tvpl:nd96]]. Báo cáo gồm vốn đã góp, doanh thu, lao động, thuế đã nộp… Không báo cáo bị xử phạt theo [[tvpl:nd122]].</p>

<h2>3. Nghĩa vụ thuế</h2>
<ul>
<li><strong>Thuế GTGT</strong>: khai tháng hoặc quý; doanh nghiệp mới được chọn khai theo quý.</li>
<li><strong>Thuế TNCN</strong>: khấu trừ cho nhân viên Việt Nam và nước ngoài; người nước ngoài cư trú được giảm trừ gia cảnh như người Việt.</li>
<li><strong>Thuế TNDN</strong>: tạm nộp quý, quyết toán năm; kiểm tra điều kiện ưu đãi đầu tư (ngành, địa bàn).</li>
<li><strong>Thuế nhà thầu nước ngoài</strong>: khi trả tiền dịch vụ, bản quyền, lãi vay cho tổ chức, cá nhân nước ngoài – doanh nghiệp khấu trừ và nộp thay.</li>
</ul>
<p>Hạn nộp hồ sơ khai thuế theo [[tvpl:lqlt2025]] và [[tvpl:nd252]].</p>

<h2>4. Báo cáo tài chính phải kiểm toán</h2>
<p>Doanh nghiệp có vốn đầu tư nước ngoài thuộc đối tượng bắt buộc kiểm toán báo cáo tài chính năm theo pháp luật về kiểm toán độc lập. Báo cáo tài chính đã kiểm toán nộp cho cơ quan thuế, cơ quan thống kê và cơ quan đăng ký đầu tư trong thời hạn quy định (thông thường chậm nhất 90 ngày kể từ ngày kết thúc năm tài chính). Chế độ kế toán thường áp dụng là [[tvpl:tt99]].</p>

<h2>5. Giao dịch liên kết</h2>
<p>Doanh nghiệp FDI thường mua bán hàng hoá, dịch vụ, vay vốn với công ty mẹ hoặc công ty cùng tập đoàn – đây là <strong>giao dịch liên kết</strong>. Doanh nghiệp phải kê khai thông tin giao dịch liên kết cùng quyết toán thuế TNDN, chuẩn bị hồ sơ xác định giá giao dịch liên kết khi đến ngưỡng, và chi phí lãi vay được trừ bị khống chế theo tỷ lệ trên lợi nhuận.</p>

<h2>6. Chuyển lợi nhuận ra nước ngoài</h2>
<p>Nhà đầu tư được chuyển lợi nhuận về nước sau khi doanh nghiệp đã hoàn thành nghĩa vụ tài chính, quyết toán thuế TNDN năm và không còn lỗ luỹ kế. Doanh nghiệp thông báo với cơ quan thuế trước khi chuyển; tiền đi qua tài khoản vốn đầu tư trực tiếp.</p>

<h2>7. Lao động nước ngoài</h2>
<ul>
<li>Người nước ngoài làm việc tại Việt Nam cần giấy phép lao động hoặc xác nhận không thuộc diện cấp giấy phép.</li>
<li>Thẻ tạm trú cho nhà đầu tư, người lao động.</li>
<li>Đóng bảo hiểm xã hội bắt buộc cho người nước ngoài có hợp đồng lao động đủ điều kiện theo [[tvpl:lbhxh]].</li>
</ul>

<h2>Lịch nhắc việc tối thiểu</h2>
<table>
<thead><tr><th>Thời điểm</th><th>Việc cần làm</th></tr></thead>
<tbody>
<tr><td>Hằng tháng/quý</td><td>Tờ khai GTGT, TNCN; tạm nộp TNDN; báo cáo đầu tư quý</td></tr>
<tr><td>Đầu năm</td><td>Báo cáo đầu tư năm</td></tr>
<tr><td>Trong 90 ngày sau năm tài chính</td><td>Báo cáo tài chính kiểm toán, quyết toán TNDN, TNCN, hồ sơ giao dịch liên kết</td></tr>
</tbody>
</table>
<p>Gói [[tax-and-accounting-service|kế toán – thuế cho doanh nghiệp FDI]] (báo cáo song ngữ Việt – Anh) theo dõi toàn bộ lịch trên. Xem thêm [[ke-toan-tron-goi|kế toán trọn gói]] và [[bao-cao-tai-chinh|lập báo cáo tài chính]].</p>',
	),

	// ===================== THAY ĐỔI GPKD =====================
	array(
		'slug'    => 'thu-tuc-thay-doi-nguoi-dai-dien-theo-phap-luat',
		'cat'     => array( 'thay-doi-dang-ky-kinh-doanh' ),
		'title'   => 'Thủ tục thay đổi người đại diện theo pháp luật của công ty năm 2026',
		'excerpt' => 'Khi nào phải đổi người đại diện theo pháp luật, hồ sơ với công ty TNHH và cổ phần, ai ký giấy đề nghị, xác thực điện tử theo Nghị định 296/2026, thời hạn 10 ngày và những việc cần cập nhật sau khi đổi.',
		'content' => $u . '
<p>Người đại diện theo pháp luật là người ký hợp đồng, đại diện công ty trước cơ quan nhà nước, toà án và chịu trách nhiệm cá nhân trong nhiều trường hợp. Khi thay người, công ty phải đăng ký thay đổi trong <strong>10 ngày</strong> kể từ ngày có quyết định.</p>

<h2>1. Quy định chung về người đại diện</h2>
<ul>
<li>Công ty TNHH, công ty cổ phần có thể có một hoặc nhiều người đại diện theo pháp luật; điều lệ quy định số lượng, chức danh, quyền hạn.</li>
<li>Doanh nghiệp phải bảo đảm <strong>luôn có ít nhất một người đại diện cư trú tại Việt Nam</strong> ([[tvpl:ldn2020]], sửa đổi bởi [[tvpl:ldn2025|Luật số 76/2025/QH15]]). Nếu người đó xuất cảnh, phải uỷ quyền bằng văn bản cho người khác cư trú tại Việt Nam.</li>
<li>Người đại diện không được thuộc trường hợp cấm quản lý doanh nghiệp (cán bộ, công chức, người đang bị truy cứu trách nhiệm hình sự…).</li>
</ul>

<h2>2. Hồ sơ</h2>
<ol>
<li>Giấy đề nghị đăng ký thay đổi người đại diện theo pháp luật (theo mẫu tại Thông tư 68/2025/TT-BTC, đã sửa đổi bởi Thông tư 121/2026/TT-BTC từ 21/8/2026).</li>
<li>Nghị quyết/quyết định của chủ sở hữu, Hội đồng thành viên hoặc Hội đồng quản trị về việc thay đổi; kèm biên bản họp (với công ty TNHH hai thành viên, công ty cổ phần).</li>
<li>Văn bản uỷ quyền cho người nộp hồ sơ (nếu có).</li>
</ol>
<p>Không phải nộp bản sao căn cước của người đại diện mới – cơ quan đăng ký kinh doanh khai thác từ cơ sở dữ liệu dân cư theo [[tvpl:nd168]].</p>

<h2>3. Ai ký giấy đề nghị?</h2>
<p>Chủ tịch công ty/Chủ tịch Hội đồng thành viên (công ty TNHH) hoặc Chủ tịch Hội đồng quản trị (công ty cổ phần). Nếu chính người này là người đại diện bị thay thế, người ký là người được bổ nhiệm, bầu mới.</p>

<h2>4. Xác thực điện tử khi uỷ quyền nộp hồ sơ</h2>
<p>Theo [[tvpl:nd296]], khi uỷ quyền cho người khác đăng ký thay đổi người đại diện theo pháp luật, <strong>cả người uỷ quyền và người được uỷ quyền phải xác thực điện tử</strong> trên tài khoản định danh. Giấy đề nghị chỉ cần một người ký và đã kê khai trực tuyến thì không phải ký số, ký tay tải lên nữa.</p>

<h2>5. Thời hạn và mức phạt</h2>
<p>Hồ sơ hợp lệ được giải quyết trong 3 ngày làm việc. Chậm đăng ký so với thời hạn 10 ngày bị xử phạt theo Điều 44 [[tvpl:nd122]]:</p>
<table>
<thead><tr><th>Thời gian chậm</th><th>Mức phạt (tổ chức)</th></tr></thead>
<tbody>
<tr><td>1 – 10 ngày</td><td>Cảnh cáo</td></tr>
<tr><td>11 – 30 ngày</td><td>3 – 5 triệu đồng</td></tr>
<tr><td>31 – 90 ngày</td><td>5 – 10 triệu đồng</td></tr>
<tr><td>Từ 91 ngày</td><td>10 – 20 triệu đồng</td></tr>
</tbody>
</table>

<h2>6. Việc cần làm sau khi đổi</h2>
<ul>
<li>Cập nhật chữ ký số (token) theo người đại diện mới.</li>
<li>Thay đổi chữ ký mẫu, người đại diện chủ tài khoản tại ngân hàng.</li>
<li>Cập nhật thông tin với nhà cung cấp hoá đơn điện tử, cơ quan bảo hiểm xã hội.</li>
<li>Thông báo cho đối tác; rà soát hợp đồng, giấy uỷ quyền do người cũ ký còn hiệu lực.</li>
</ul>
<p>Đổi người đại diện thường đi kèm đổi chủ sở hữu, thành viên hoặc chức danh. Gộp vào một hồ sơ để tiết kiệm thời gian – xem [[doi-dai-dien-phap-luat|dịch vụ đổi người đại diện theo pháp luật]].</p>',
	),

	array(
		'slug'    => 'thu-tuc-tang-giam-von-dieu-le',
		'cat'     => array( 'thay-doi-dang-ky-kinh-doanh' ),
		'title'   => 'Thủ tục tăng, giảm vốn điều lệ công ty TNHH và cổ phần: hồ sơ và điều kiện',
		'excerpt' => 'Các cách tăng vốn điều lệ, điều kiện giảm vốn (hoạt động từ 2 năm, bảo đảm thanh toán đủ nợ), hồ sơ đăng ký thay đổi vốn, thời hạn 10 ngày, việc góp vốn thật theo Nghị định 296/2026 và lưu ý về thuế.',
		'content' => $u . '
<p>Vốn điều lệ thay đổi khi công ty mở rộng, nhận thêm người góp vốn, hoặc khi thành viên rút bớt vốn. Mọi thay đổi vốn điều lệ phải đăng ký với Phòng Đăng ký kinh doanh trong <strong>10 ngày</strong> kể từ ngày hoàn thành việc thay đổi.</p>

<h2>1. Các cách tăng vốn</h2>
<ul>
<li><strong>Công ty TNHH một thành viên</strong>: chủ sở hữu góp thêm, hoặc huy động thêm người góp vốn (khi đó phải chuyển đổi lên TNHH hai thành viên hoặc cổ phần).</li>
<li><strong>Công ty TNHH hai thành viên trở lên</strong>: thành viên góp thêm theo tỷ lệ, hoặc tiếp nhận thành viên mới.</li>
<li><strong>Công ty cổ phần</strong>: chào bán cổ phần cho cổ đông hiện hữu, chào bán riêng lẻ, phát hành cổ phần trả cổ tức, chuyển đổi trái phiếu…</li>
</ul>
<p>Việc tăng vốn phải là góp vốn thật. [[tvpl:nd296]] bổ sung nguyên tắc: chủ sở hữu, thành viên, cổ đông phải góp vốn theo quy định và <strong>không được góp vốn hộ, đứng tên thay</strong>.</p>

<h2>2. Điều kiện giảm vốn</h2>
<p>Theo [[tvpl:ldn2020]], công ty TNHH được giảm vốn điều lệ khi:</p>
<ul>
<li>Hoàn trả một phần vốn góp cho chủ sở hữu/thành viên, nếu công ty đã <strong>hoạt động kinh doanh liên tục từ 2 năm trở lên</strong> kể từ ngày đăng ký doanh nghiệp và <strong>bảo đảm thanh toán đủ các khoản nợ</strong>, nghĩa vụ tài sản khác sau khi hoàn trả.</li>
<li>Thành viên, chủ sở hữu không góp đủ vốn đúng hạn – công ty đăng ký điều chỉnh vốn bằng số vốn thực góp.</li>
<li>Công ty mua lại phần vốn góp của thành viên (TNHH hai thành viên trở lên).</li>
</ul>
<p>Công ty cổ phần giảm vốn theo quyết định của Đại hội đồng cổ đông (hoàn trả vốn khi đã hoạt động từ 2 năm), công ty mua lại cổ phần, hoặc cổ đông không thanh toán đủ cổ phần đăng ký mua.</p>

<h2>3. Hồ sơ đăng ký thay đổi vốn</h2>
<ol>
<li>Thông báo thay đổi nội dung đăng ký doanh nghiệp (vốn điều lệ).</li>
<li>Nghị quyết/quyết định và biên bản họp về việc thay đổi vốn.</li>
<li>Danh sách thành viên mới (nếu tỷ lệ góp vốn thay đổi) hoặc giấy tờ về việc chuyển nhượng, tiếp nhận thành viên.</li>
<li>Với trường hợp giảm vốn do hoàn trả: báo cáo tài chính gần nhất chứng minh khả năng thanh toán nợ.</li>
<li>Văn bản uỷ quyền (nếu có).</li>
</ol>
<p>Nộp qua Cổng thông tin quốc gia về đăng ký doanh nghiệp; giải quyết trong 3 ngày làm việc theo [[tvpl:nd168]].</p>

<h2>4. Lưu ý về thuế và kế toán</h2>
<ul>
<li>Tăng vốn bằng tiền: chuyển khoản vào tài khoản công ty, hạch toán tăng vốn chủ sở hữu.</li>
<li>Tăng vốn bằng tài sản: định giá, chuyển quyền sở hữu; tài sản góp vốn có thể phát sinh nghĩa vụ thuế với người góp.</li>
<li>Chuyển nhượng phần vốn góp, cổ phần: người chuyển nhượng nộp thuế TNCN (cá nhân) hoặc thuế TNDN (tổ chức).</li>
<li>Giảm vốn hoàn trả cho thành viên: lưu chứng từ thanh toán qua ngân hàng.</li>
<li>Từ 2026 không còn lệ phí môn bài, nên tăng vốn không làm tăng khoản lệ phí này như trước.</li>
</ul>

<h2>5. Chậm đăng ký bị phạt bao nhiêu?</h2>
<p>Từ cảnh cáo (chậm 1 – 10 ngày) đến 10 – 20 triệu đồng (chậm từ 91 ngày) với tổ chức, theo Điều 44 [[tvpl:nd122]].</p>
<p>Thay đổi vốn liên quan đến cả đăng ký kinh doanh, kế toán và thuế. [[tang-giam-von-dieu-le|Dịch vụ tăng, giảm vốn điều lệ]] xử lý trọn gói, kể cả hạch toán và kê khai thuế chuyển nhượng.</p>',
	),

	array(
		'slug'    => 'bo-sung-nganh-nghe-kinh-doanh-cach-ghi-ma-nganh',
		'cat'     => array( 'thay-doi-dang-ky-kinh-doanh' ),
		'title'   => 'Bổ sung ngành nghề kinh doanh: cách ghi mã ngành và những lưu ý quan trọng',
		'excerpt' => 'Khi nào phải bổ sung ngành nghề, cách chọn mã ngành cấp 4 theo hệ thống ngành kinh tế Việt Nam, ghi chi tiết ngành, ngành có điều kiện, hồ sơ thông báo thay đổi và rủi ro khi xuất hoá đơn cho ngành chưa đăng ký.',
		'content' => $u . '
<p>Công ty mở rộng sang hoạt động mới – từ bán hàng sang lắp đặt, từ dịch vụ sang sản xuất – cần bổ sung ngành nghề trước khi ký hợp đồng và xuất hoá đơn. Đây là thủ tục <strong>thông báo</strong> (không cấp lại Giấy chứng nhận đăng ký doanh nghiệp), thực hiện trong 10 ngày kể từ ngày có quyết định thay đổi.</p>

<h2>1. Có bắt buộc bổ sung ngành nghề không?</h2>
<p>Doanh nghiệp được tự do kinh doanh ngành nghề pháp luật không cấm, nhưng phải <strong>thông báo</strong> ngành nghề với Cơ quan đăng ký kinh doanh theo [[tvpl:ldn2025|Luật Doanh nghiệp sửa đổi 2025]]. Kinh doanh ngành chưa thông báo có thể bị xử phạt theo [[tvpl:nd122]], đồng thời gây rắc rối khi:</p>
<ul>
<li>Đối tác, ngân hàng kiểm tra ngành nghề trước khi ký hợp đồng, giải ngân.</li>
<li>Tham gia đấu thầu (hồ sơ yêu cầu ngành nghề phù hợp).</li>
<li>Xin giấy phép con cho ngành có điều kiện.</li>
</ul>

<h2>2. Cách chọn mã ngành</h2>
<ul>
<li>Ngành nghề ghi theo <strong>mã ngành cấp 4</strong> trong Hệ thống ngành kinh tế Việt Nam (ví dụ 4659 – Bán buôn máy móc, thiết bị và phụ tùng máy khác).</li>
<li>Có thể ghi <strong>chi tiết</strong> dưới mã ngành để làm rõ hoạt động (ví dụ: “Chi tiết: Bán buôn máy móc, thiết bị văn phòng”), nhưng nội dung chi tiết phải thuộc phạm vi mã ngành cấp 4.</li>
<li>Ngành chuyên ngành chưa có trong hệ thống: ghi theo tên ngành tại văn bản pháp luật chuyên ngành.</li>
<li>Không cần đăng ký thật nhiều ngành “cho chắc” – đăng ký ngành có điều kiện mà không đáp ứng điều kiện khi hoạt động thực tế vẫn bị xử lý.</li>
</ul>

<h2>3. Ngành nghề kinh doanh có điều kiện</h2>
<p>Với ngành có điều kiện (dịch vụ kế toán, lữ hành, vận tải, xây dựng…), khi đăng ký ngành chưa phải chứng minh điều kiện; nhưng <strong>phải đáp ứng đủ điều kiện</strong> (giấy phép, chứng chỉ hành nghề, vốn pháp định, ký quỹ) trong suốt quá trình hoạt động. Danh mục ngành nghề đầu tư kinh doanh có điều kiện ban hành kèm [[tvpl:ldt2025]].</p>
<p>Doanh nghiệp có vốn nước ngoài bổ sung ngành nghề còn phải kiểm tra điều kiện tiếp cận thị trường và có thể phải điều chỉnh Giấy chứng nhận đăng ký đầu tư.</p>

<h2>4. Hồ sơ thông báo thay đổi ngành nghề</h2>
<ol>
<li>Thông báo thay đổi nội dung đăng ký doanh nghiệp (phần ngành, nghề kinh doanh).</li>
<li>Nghị quyết/quyết định của chủ sở hữu, Hội đồng thành viên hoặc Đại hội đồng cổ đông; biên bản họp (nếu có).</li>
<li>Văn bản uỷ quyền (nếu có).</li>
</ol>
<p>Nộp trực tuyến qua Cổng thông tin quốc gia về đăng ký doanh nghiệp theo [[tvpl:nd168]]; giải quyết trong 3 ngày làm việc. Kết quả là Giấy xác nhận về việc thay đổi nội dung đăng ký doanh nghiệp.</p>

<h2>5. Sau khi bổ sung ngành</h2>
<ul>
<li>Cập nhật danh mục hàng hoá, dịch vụ trên phần mềm hoá đơn; chọn đúng thuế suất GTGT cho hoạt động mới.</li>
<li>Kiểm tra ưu đãi thuế (nếu ngành mới thuộc lĩnh vực ưu đãi) – thu nhập từ ngành mới có thể phải hạch toán riêng.</li>
<li>Xin giấy phép con (nếu có) trước khi hoạt động.</li>
</ul>
<p>Muốn ghi mã ngành chuẩn, không bị trả hồ sơ? [[bo-sung-nganh-nghe-kinh-doanh|Dịch vụ bổ sung ngành nghề kinh doanh]] tra mã và soạn hồ sơ trong ngày.</p>',
	),

	array(
		'slug'    => 'chuyen-dia-chi-tru-so-cong-ty-sang-tinh-khac',
		'cat'     => array( 'thay-doi-dang-ky-kinh-doanh' ),
		'title'   => 'Chuyển địa chỉ trụ sở công ty: cùng tỉnh và khác tỉnh làm thế nào?',
		'excerpt' => 'Thủ tục thay đổi địa chỉ trụ sở chính: trường hợp không đổi cơ quan thuế và trường hợp chuyển sang tỉnh khác phải làm thủ tục thuế nơi đi trước theo Thông tư 90/2026/TT-BTC, hồ sơ đăng ký và việc cần cập nhật.',
		'content' => $u . '
<p>Chuyển văn phòng là việc bình thường khi công ty phát triển. Tuy nhiên, thủ tục khác nhau đáng kể giữa chuyển trong cùng địa bàn cơ quan thuế quản lý và chuyển sang tỉnh khác. Làm sai thứ tự có thể khiến hồ sơ bị treo nhiều tuần.</p>

<h2>1. Địa chỉ thay đổi do sắp xếp đơn vị hành chính</h2>
<p>Nếu chỉ tên phường, xã, tỉnh thay đổi do sắp xếp đơn vị hành chính năm 2025 (trụ sở vẫn ở chỗ cũ), doanh nghiệp <strong>không bắt buộc</strong> làm thủ tục thay đổi địa chỉ. Có thể cập nhật khi có nhu cầu hoặc gộp với lần thay đổi khác.</p>

<h2>2. Chuyển trụ sở – không thay đổi cơ quan thuế quản lý</h2>
<ol>
<li>Chủ sở hữu/Hội đồng thành viên/Hội đồng quản trị ra quyết định chuyển trụ sở.</li>
<li>Nộp thông báo thay đổi địa chỉ trụ sở chính tại Phòng Đăng ký kinh doanh trong 10 ngày kể từ ngày quyết định.</li>
<li>Nhận Giấy chứng nhận đăng ký doanh nghiệp mới (3 ngày làm việc theo [[tvpl:nd168]]); thông tin tự động chuyển sang cơ quan thuế.</li>
</ol>

<h2>3. Chuyển trụ sở sang tỉnh khác – làm thuế trước</h2>
<p>Khi trụ sở mới thuộc cơ quan thuế quản lý khác, doanh nghiệp phải <strong>hoàn tất thủ tục với cơ quan thuế nơi đi trước</strong>, sau đó mới đăng ký thay đổi với Phòng Đăng ký kinh doanh nơi đến:</p>
<ol>
<li><strong>Tại cơ quan thuế nơi đi</strong>: nộp tờ khai điều chỉnh thông tin đăng ký thuế (mẫu 08 theo [[tvpl:tt90]], thay Thông tư 86/2024/TT-BTC từ 01/7/2026); nộp đủ tờ khai, tiền thuế đến thời điểm chuyển. Cơ quan thuế nơi đi ban hành thông báo về việc người nộp thuế chuyển địa điểm.</li>
<li><strong>Tại Phòng Đăng ký kinh doanh nơi đến</strong>: nộp hồ sơ thay đổi địa chỉ trụ sở chính.</li>
<li><strong>Tại cơ quan thuế nơi đến</strong>: trong 10 ngày làm việc kể từ ngày cơ quan thuế nơi đi ra thông báo, nộp văn bản đăng ký chuyển địa điểm (mẫu 30/ĐKT) nếu hệ thống chưa tự cập nhật.</li>
</ol>
<p>Các thủ tục thuế được quản lý theo [[tvpl:lqlt2025]] và [[tvpl:nd252]].</p>

<h2>4. Việc cần cập nhật sau khi chuyển</h2>
<ul>
<li>Hoá đơn điện tử: cập nhật địa chỉ mới với nhà cung cấp; hoá đơn lập sau ngày thay đổi phải ghi địa chỉ mới.</li>
<li>Biển hiệu tại trụ sở mới.</li>
<li>Con dấu (nếu trên dấu có địa danh cũ).</li>
<li>Ngân hàng, bảo hiểm xã hội, hợp đồng với khách hàng, nhà cung cấp.</li>
<li>Giấy phép con có ghi địa chỉ (giấy phép kinh doanh có điều kiện, giấy chứng nhận đủ điều kiện…).</li>
</ul>

<h2>5. Lưu ý chọn trụ sở mới</h2>
<ul>
<li>Không đặt tại căn hộ chung cư chỉ có chức năng để ở, nhà tập thể.</li>
<li>Có hợp đồng thuê hoặc giấy tờ sử dụng hợp pháp; địa chỉ đủ số nhà, tên đường/thôn, xã/phường, tỉnh/thành phố.</li>
<li>Cơ quan thuế có thể kiểm tra thực tế địa chỉ – trụ sở không có thật dễ bị đưa vào diện “không hoạt động tại địa chỉ đăng ký”.</li>
</ul>
<p>Chậm đăng ký thay đổi địa chỉ bị phạt theo Điều 44 [[tvpl:nd122]]. Để chuyển trụ sở trọn gói cả phần thuế và đăng ký kinh doanh, xem [[thay-doi-dia-chi-cong-ty|dịch vụ thay đổi địa chỉ công ty]].</p>',
	),
);
