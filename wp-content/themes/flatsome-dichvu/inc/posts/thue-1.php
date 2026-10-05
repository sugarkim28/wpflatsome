<?php
/**
 * Bài viết Kiến thức kế toán (đợt 1): Thuế TNDN, Thuế TNCN, Thuế GTGT, Thuế hộ – cá nhân kinh doanh –
 * mỗi mục 4 bài. Nguồn: văn bản trên Thư viện Pháp luật.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$u = '<p class="sgd-updated"><em>Cập nhật tháng 10/2026 theo văn bản đang có hiệu lực. Chính sách thuế thay đổi thường xuyên – trước khi áp dụng, bạn nên liên hệ chuyên viên để được kiểm tra theo trường hợp cụ thể.</em></p>';

return array(

	// ===================== THUẾ TNDN =====================
	array(
		'slug'    => 'thue-suat-thue-tndn-2026',
		'cat'     => array( 'thue-tndn' ),
		'title'   => 'Thuế suất thuế TNDN năm 2026: 15%, 17%, 20% và trường hợp được miễn thuế',
		'excerpt' => 'Các mức thuế suất thuế thu nhập doanh nghiệp theo Luật Thuế TNDN 2025: 15% (doanh thu đến 3 tỷ), 17% (trên 3 đến 50 tỷ), 20%; doanh nghiệp doanh thu đến 1 tỷ đồng được miễn theo Nghị định 141/2026; cách xác định doanh thu và ví dụ tính thuế.',
		'content' => $u . '
<p>Từ kỳ tính thuế năm 2025, [[tvpl:ltndn]] áp dụng thuế suất ưu đãi theo quy mô doanh thu, giúp doanh nghiệp nhỏ giảm đáng kể số thuế phải nộp. Năm 2026, [[tvpl:nd141]] bổ sung thêm chính sách <strong>miễn thuế TNDN</strong> cho doanh nghiệp có doanh thu năm đến 1 tỷ đồng.</p>

<h2>1. Bảng thuế suất</h2>
<table>
<thead><tr><th>Tổng doanh thu năm</th><th>Thuế suất</th></tr></thead>
<tbody>
<tr><td>Đến 1 tỷ đồng</td><td>Miễn thuế TNDN (Nghị định 141/2026, áp dụng từ 01/01/2026)</td></tr>
<tr><td>Không quá 3 tỷ đồng</td><td>15%</td></tr>
<tr><td>Trên 3 tỷ đến không quá 50 tỷ đồng</td><td>17%</td></tr>
<tr><td>Các trường hợp còn lại</td><td>20%</td></tr>
</tbody>
</table>
<p>Một số lĩnh vực có thuế suất riêng (dầu khí, tài nguyên quý hiếm…) và các dự án thuộc diện ưu đãi đầu tư có thể được áp dụng thuế suất 10%, 15%, 17% trong thời hạn nhất định.</p>

<h2>2. Doanh thu nào làm căn cứ?</h2>
<p>Theo [[tvpl:nd320]] (sửa đổi bởi Nghị định 141/2026), tổng doanh thu năm làm căn cứ gồm: doanh thu bán hàng hoá, cung cấp dịch vụ (sau khi trừ các khoản giảm trừ doanh thu), doanh thu hoạt động tài chính và thu nhập khác – lấy trên báo cáo kết quả kinh doanh kèm tờ khai quyết toán thuế TNDN của <strong>năm trước liền kề</strong>.</p>
<ul>
<li>Doanh nghiệp có quan hệ liên kết với doanh nghiệp không đáp ứng điều kiện thì không được áp dụng mức 15%, 17%.</li>
<li>Doanh nghiệp mới thành lập xác định doanh thu theo hướng dẫn riêng tại Nghị định 320/2025.</li>
</ul>

<h2>3. Ví dụ</h2>
<p><strong>Công ty A</strong> năm 2025 có tổng doanh thu 2,4 tỷ đồng, năm 2026 có thu nhập tính thuế 300 triệu đồng. Do doanh thu năm trước không quá 3 tỷ đồng, thuế TNDN năm 2026 = 300 triệu × 15% = <strong>45 triệu đồng</strong> (theo thuế suất cũ 20% là 60 triệu đồng).</p>
<p><strong>Công ty B</strong> doanh thu năm trước 12 tỷ đồng, thu nhập tính thuế 1 tỷ đồng → thuế = 1 tỷ × 17% = <strong>170 triệu đồng</strong>.</p>

<h2>4. Thu nhập tính thuế</h2>
<p>Thu nhập tính thuế = (Doanh thu − Chi phí được trừ + Thu nhập khác) − Thu nhập được miễn thuế − Lỗ được kết chuyển. Phần lớn chênh lệch giữa lợi nhuận kế toán và thu nhập tính thuế nằm ở các khoản <strong>chi phí không được trừ</strong> – xem bài Chi phí được trừ khi tính thuế TNDN trong chuyên mục này.</p>

<h2>5. Các ưu đãi khác cần biết</h2>
<ul>
<li>Doanh nghiệp nhỏ và vừa đăng ký kinh doanh lần đầu: miễn thuế TNDN 3 năm theo Nghị quyết 198/2025/QH15 – xem bài riêng trong chuyên mục.</li>
<li>Lỗ phát sinh được chuyển sang các năm sau, tối đa 5 năm liên tục.</li>
<li>Ưu đãi theo ngành nghề, địa bàn đầu tư (công nghệ cao, nông nghiệp, địa bàn khó khăn…).</li>
</ul>
<p>Chọn đúng thuế suất và tận dụng ưu đãi có thể giảm đáng kể số thuế. Gói [[ke-toan-tron-goi|kế toán trọn gói]] và [[quyet-toan-thue-cuoi-nam|dịch vụ quyết toán thuế]] sẽ rà soát để bạn không nộp thừa.</p>',
	),

	array(
		'slug'    => 'mien-thue-tndn-3-nam-doanh-nghiep-nho-va-vua',
		'cat'     => array( 'thue-tndn', 'thu-tuc-thanh-lap' ),
		'title'   => 'Miễn thuế TNDN 3 năm cho doanh nghiệp nhỏ và vừa mới thành lập: điều kiện và cách kê khai',
		'excerpt' => 'Chính sách miễn thuế TNDN 3 năm theo Nghị quyết 198/2025/QH15 và Nghị định 20/2026/NĐ-CP: ai được miễn, tiêu chí doanh nghiệp nhỏ và vừa, các trường hợp bị loại trừ, cách tính thời gian miễn và kê khai trên tờ khai quyết toán.',
		'content' => $u . '
<p>Một trong những chính sách hỗ trợ lớn nhất cho doanh nghiệp mới là <strong>miễn thuế TNDN trong 3 năm</strong>, quy định tại Điều 10 [[tvpl:nq198|Nghị quyết 198/2025/QH15]] về phát triển kinh tế tư nhân và được hướng dẫn tại Nghị định 20/2026/NĐ-CP. Chính sách có hiệu lực từ 17/5/2025, áp dụng từ kỳ tính thuế năm 2025.</p>

<h2>1. Ai được miễn?</h2>
<p>Doanh nghiệp <strong>nhỏ và vừa</strong> (gồm cả doanh nghiệp siêu nhỏ) <strong>đăng ký kinh doanh lần đầu</strong>. Thời gian miễn 3 năm tính <strong>liên tục</strong> kể từ năm đầu tiên được cấp Giấy chứng nhận đăng ký doanh nghiệp lần đầu.</p>

<h2>2. Tiêu chí doanh nghiệp nhỏ và vừa</h2>
<table>
<thead><tr><th>Loại</th><th>Nông, lâm, thuỷ sản; công nghiệp, xây dựng</th><th>Thương mại, dịch vụ</th></tr></thead>
<tbody>
<tr><td>Siêu nhỏ</td><td>≤ 10 lao động đóng BHXH; doanh thu ≤ 3 tỷ hoặc nguồn vốn ≤ 3 tỷ đồng</td><td>≤ 10 lao động; doanh thu ≤ 10 tỷ hoặc nguồn vốn ≤ 3 tỷ đồng</td></tr>
<tr><td>Nhỏ</td><td>≤ 100 lao động; doanh thu ≤ 50 tỷ hoặc nguồn vốn ≤ 20 tỷ đồng</td><td>≤ 50 lao động; doanh thu ≤ 100 tỷ hoặc nguồn vốn ≤ 50 tỷ đồng</td></tr>
<tr><td>Vừa</td><td>≤ 200 lao động; doanh thu ≤ 200 tỷ hoặc nguồn vốn ≤ 100 tỷ đồng</td><td>≤ 100 lao động; doanh thu ≤ 300 tỷ hoặc nguồn vốn ≤ 100 tỷ đồng</td></tr>
</tbody>
</table>
<p>Tiêu chí theo pháp luật về hỗ trợ doanh nghiệp nhỏ và vừa; số lao động tính bình quân năm.</p>

<h2>3. Trường hợp không được miễn</h2>
<p>Doanh nghiệp thành lập mới do <strong>sáp nhập, hợp nhất, chia, tách, chuyển đổi chủ sở hữu, chuyển đổi loại hình</strong> không được coi là đăng ký lần đầu. Hộ kinh doanh chuyển lên doanh nghiệp cần đối chiếu điều kiện cụ thể tại Nghị định 20/2026.</p>

<h2>4. Cách tính thời gian miễn</h2>
<p>Ví dụ: công ty được cấp giấy chứng nhận ngày 15/9/2025 → được miễn thuế TNDN cho các năm 2025, 2026, 2027. Năm 2025 dù chỉ hoạt động 3,5 tháng vẫn tính là một năm miễn thuế. Năm 2028 trở đi nộp thuế theo thuế suất thông thường (15% – 17% – 20%, hoặc miễn nếu doanh thu năm đến 1 tỷ đồng theo [[tvpl:nd141]]).</p>

<h2>5. Có phải kê khai không?</h2>
<p>Có. Doanh nghiệp vẫn phải:</p>
<ul>
<li>Lập sổ sách kế toán, báo cáo tài chính năm.</li>
<li>Nộp <strong>tờ khai quyết toán thuế TNDN</strong>, kê khai số thuế được miễn vào phụ lục ưu đãi – cơ quan thuế không tự động miễn.</li>
<li>Không phải tạm nộp thuế TNDN quý đối với số thuế được miễn.</li>
</ul>
<p>Thu nhập không thuộc diện ưu đãi (ví dụ chuyển nhượng bất động sản, thu nhập khác theo quy định) vẫn có thể phải nộp thuế.</p>

<h2>6. Lưu ý</h2>
<ul>
<li>Ưu đãi miễn thuế TNDN <strong>không</strong> miễn thuế GTGT, thuế TNCN của nhân viên.</li>
<li>Chi phí vẫn phải hợp lệ – nếu bị loại chi phí làm phát sinh lãi sau năm miễn thuế, số lỗ chuyển sang sẽ giảm.</li>
<li>Doanh nghiệp đồng thời được hưởng ưu đãi khác thì chọn ưu đãi có lợi nhất theo quy định.</li>
</ul>
<p>Mới thành lập và muốn chắc chắn được hưởng ưu đãi? [[khai-thue-ban-dau|Dịch vụ khai thuế ban đầu]] và [[ke-toan-tron-goi|kế toán trọn gói]] sẽ kê khai đúng ngay từ năm đầu.</p>',
	),

	array(
		'slug'    => 'tam-nop-thue-tndn-theo-quy-quy-tac-80',
		'cat'     => array( 'thue-tndn', 'bao-cao-thue-tai-chinh' ),
		'title'   => 'Tạm nộp thuế TNDN theo quý: cách tính, hạn nộp và quy tắc 80%',
		'excerpt' => 'Doanh nghiệp không nộp tờ khai thuế TNDN quý nhưng phải tạm nộp thuế chậm nhất ngày cuối tháng đầu quý sau; tổng 4 quý không thấp hơn 80% số thuế quyết toán; cách ước tính, tiền chậm nộp và ví dụ.',
		'content' => $u . '
<p>Nhiều kế toán mới nghĩ rằng thuế TNDN chỉ phải nộp khi quyết toán cuối năm. Thực tế, doanh nghiệp phải <strong>tạm nộp</strong> theo quý, và nếu tạm nộp quá ít sẽ bị tính tiền chậm nộp khi quyết toán.</p>

<h2>1. Quy định chung</h2>
<ul>
<li>Doanh nghiệp <strong>không phải nộp tờ khai</strong> thuế TNDN tạm tính quý.</li>
<li>Phải <strong>tạm nộp tiền thuế</strong> chậm nhất ngày cuối cùng của tháng đầu quý tiếp theo (ví dụ quý III/2026 nộp trước 31/10/2026).</li>
<li>Tổng số thuế đã tạm nộp của 4 quý <strong>không được thấp hơn 80%</strong> số thuế phải nộp theo quyết toán năm.</li>
</ul>
<p>Quy định theo [[tvpl:lqlt2025]] và [[tvpl:nd252]].</p>

<h2>2. Cách ước tính số tạm nộp</h2>
<ol>
<li>Lấy lợi nhuận kế toán luỹ kế đến cuối quý.</li>
<li>Cộng các khoản chi phí dự kiến không được trừ; trừ thu nhập được miễn, lỗ được chuyển.</li>
<li>Nhân thuế suất áp dụng (15%, 17% hoặc 20%) theo doanh thu năm trước.</li>
<li>Trừ số đã tạm nộp các quý trước → số phải nộp quý này.</li>
</ol>

<h2>3. Quy tắc 80% hoạt động thế nào?</h2>
<p>Ví dụ: quyết toán năm 2026 xác định thuế TNDN phải nộp 200 triệu đồng. Doanh nghiệp đã tạm nộp 4 quý tổng cộng 140 triệu đồng.</p>
<ul>
<li>80% × 200 triệu = 160 triệu đồng.</li>
<li>Số tạm nộp thiếu so với mức 80% = 160 − 140 = 20 triệu đồng → bị tính <strong>tiền chậm nộp</strong> từ ngày hết hạn tạm nộp quý IV (31/01/2027) đến ngày nộp.</li>
<li>Phần còn lại 40 triệu đồng (200 − 160) nộp đến hạn quyết toán (31/3/2027) không bị tính chậm nộp.</li>
</ul>
<p>Tiền chậm nộp tính theo tỷ lệ phần trăm trên ngày đối với số tiền chậm nộp theo pháp luật quản lý thuế.</p>

<h2>4. Trường hợp không phải tạm nộp</h2>
<ul>
<li>Doanh nghiệp đang trong thời gian miễn thuế TNDN (ví dụ miễn 3 năm cho doanh nghiệp nhỏ và vừa mới thành lập).</li>
<li>Doanh nghiệp có doanh thu năm đến 1 tỷ đồng được miễn thuế theo [[tvpl:nd141]].</li>
<li>Doanh nghiệp lỗ luỹ kế đến quý – số tạm nộp bằng 0.</li>
</ul>

<h2>5. Tạm nộp thừa thì sao?</h2>
<p>Số tạm nộp vượt số phải nộp khi quyết toán được bù trừ vào nghĩa vụ thuế kỳ sau hoặc đề nghị hoàn theo quy định.</p>

<h2>6. Kinh nghiệm</h2>
<ul>
<li>Lập bảng theo dõi lợi nhuận và thuế tạm nộp hằng quý.</li>
<li>Quý IV nên ước tính sát nhất – đây là quý quyết định có đạt 80% hay không.</li>
<li>Rà soát chi phí không được trừ ngay trong năm thay vì dồn đến lúc quyết toán.</li>
</ul>
<p>Lịch nộp đầy đủ xem bài Lịch nộp tờ khai thuế năm 2026 trong chuyên mục Báo cáo thuế – Tài chính. Dịch vụ [[bao-cao-thue-hang-thang-quy|báo cáo thuế hằng tháng, quý]] sẽ tính số tạm nộp và nhắc hạn cho bạn.</p>',
	),

	array(
		'slug'    => 'quyet-toan-thue-tndn-va-chuyen-lo',
		'cat'     => array( 'thue-tndn', 'bao-cao-thue-tai-chinh' ),
		'title'   => 'Quyết toán thuế TNDN năm: hồ sơ, thời hạn và cách chuyển lỗ tối đa 5 năm',
		'excerpt' => 'Hồ sơ quyết toán thuế TNDN (tờ khai, báo cáo tài chính, phụ lục chuyển lỗ, ưu đãi, giao dịch liên kết), hạn nộp ngày cuối tháng thứ 3, nguyên tắc chuyển lỗ tối đa 5 năm và những lỗi thường gặp khi quyết toán.',
		'content' => $u . '
<p>Quyết toán thuế TNDN là lúc doanh nghiệp tổng hợp toàn bộ doanh thu, chi phí cả năm để xác định số thuế chính thức. Đây cũng là hồ sơ cơ quan thuế dùng khi kiểm tra, thanh tra.</p>

<h2>1. Thời hạn</h2>
<p>Chậm nhất <strong>ngày cuối cùng của tháng thứ 3</strong> kể từ ngày kết thúc năm tài chính – với năm tài chính theo năm dương lịch là <strong>31/3</strong> năm sau. Hạn nộp tiền thuế còn thiếu trùng hạn nộp hồ sơ ([[tvpl:nd252]]).</p>

<h2>2. Hồ sơ quyết toán</h2>
<ul>
<li>Tờ khai quyết toán thuế TNDN.</li>
<li><strong>Báo cáo tài chính năm</strong> (hoặc báo cáo tài chính đã kiểm toán với doanh nghiệp bắt buộc kiểm toán).</li>
<li>Phụ lục kết quả hoạt động sản xuất kinh doanh.</li>
<li>Phụ lục chuyển lỗ (nếu có lỗ được chuyển).</li>
<li>Phụ lục ưu đãi thuế (miễn, giảm thuế).</li>
<li>Phụ lục thông tin giao dịch liên kết (nếu có).</li>
</ul>

<h2>3. Chuyển lỗ</h2>
<p>Theo [[tvpl:ltndn]] và [[tvpl:nd320]]:</p>
<ul>
<li>Lỗ phát sinh trong kỳ tính thuế được chuyển <strong>toàn bộ và liên tục</strong> vào thu nhập tính thuế của các năm sau, tối đa <strong>không quá 5 năm</strong> kể từ năm tiếp sau năm phát sinh lỗ.</li>
<li>Doanh nghiệp tự xác định số lỗ được chuyển; lỗ quá 5 năm không được chuyển tiếp.</li>
<li>Lỗ từ chuyển nhượng bất động sản, dự án đầu tư được bù trừ theo quy định riêng.</li>
</ul>
<p><em>Ví dụ:</em> năm 2024 lỗ 400 triệu; năm 2025 lãi 150 triệu; năm 2026 lãi 300 triệu. Năm 2025 chuyển 150 triệu (thu nhập tính thuế = 0); năm 2026 chuyển tiếp 250 triệu, thu nhập tính thuế còn 50 triệu.</p>

<h2>4. Các bước quyết toán</h2>
<ol>
<li>Khoá sổ, kiểm kê tiền, hàng tồn kho, tài sản; đối chiếu công nợ.</li>
<li>Rà soát chi phí: loại các khoản không đủ hoá đơn, chứng từ, khoản từ 5 triệu đồng trả tiền mặt, chi phí không liên quan đến kinh doanh.</li>
<li>Xác định thu nhập tính thuế, lỗ được chuyển, ưu đãi.</li>
<li>Đối chiếu số thuế đã tạm nộp – kiểm tra quy tắc 80%.</li>
<li>Lập báo cáo tài chính, tờ khai quyết toán; ký số và nộp qua cổng thuế điện tử.</li>
<li>Nộp số thuế còn thiếu (hoặc theo dõi số nộp thừa để bù trừ).</li>
</ol>

<h2>5. Lỗi thường gặp</h2>
<ul>
<li>Số liệu tờ khai không khớp báo cáo tài chính.</li>
<li>Quên điều chỉnh tăng chi phí không được trừ (phạt hành chính, khấu hao vượt mức…).</li>
<li>Chuyển lỗ sai năm hoặc vượt thời hạn 5 năm.</li>
<li>Không kê khai phụ lục ưu đãi nên không được hưởng miễn thuế.</li>
<li>Không đối chiếu doanh thu với hoá đơn đầu ra trên hệ thống của cơ quan thuế.</li>
</ul>

<h2>6. Phát hiện sai sau khi nộp</h2>
<p>Doanh nghiệp được khai bổ sung trong 5 năm kể từ hết hạn nộp hồ sơ của kỳ có sai sót theo [[tvpl:lqlt2025]], nếu chưa có quyết định kiểm tra, thanh tra.</p>
<p>Sắp đến kỳ quyết toán? [[quyet-toan-thue-cuoi-nam|Dịch vụ quyết toán thuế cuối năm]] và [[bao-cao-tai-chinh|lập báo cáo tài chính]] giúp rà soát và nộp hồ sơ đúng hạn.</p>',
	),

	// ===================== THUẾ TNCN =====================
	array(
		'slug'    => 'quyet-toan-thue-tncn-tu-tien-luong',
		'cat'     => array( 'thue-tncn' ),
		'title'   => 'Quyết toán thuế TNCN từ tiền lương: ai phải quyết toán, uỷ quyền và tự quyết toán',
		'excerpt' => 'Hướng dẫn quyết toán thuế TNCN năm với thu nhập từ tiền lương, tiền công: trách nhiệm của tổ chức trả thu nhập, điều kiện uỷ quyền quyết toán, cá nhân tự quyết toán, thời hạn 31/3 và 30/4, nộp qua eTax và hoàn thuế nộp thừa.',
		'content' => $u . '
<p>Mỗi năm, doanh nghiệp và người lao động đều phải xác định lại số thuế TNCN cả năm để nộp thêm hoặc được hoàn phần nộp thừa. Từ 01/7/2026, việc quyết toán thực hiện theo [[tvpl:ltncn]], [[tvpl:nd253]], [[tvpl:tt87]] và [[tvpl:lqlt2025]].</p>

<h2>1. Tổ chức trả thu nhập phải quyết toán</h2>
<p>Doanh nghiệp trả tiền lương, tiền công có trách nhiệm khai quyết toán thuế TNCN và <strong>quyết toán thay</strong> cho người lao động đã uỷ quyền – từ 01/7/2026, trách nhiệm này áp dụng <strong>không phân biệt</strong> doanh nghiệp đã khấu trừ thuế hay chưa. Hạn nộp: ngày cuối cùng của tháng thứ 3 sau khi kết thúc năm (31/3).</p>

<h2>2. Khi nào người lao động được uỷ quyền?</h2>
<ul>
<li>Cá nhân cư trú có thu nhập từ tiền lương, tiền công, ký hợp đồng lao động từ <strong>3 tháng trở lên</strong> tại một nơi và <strong>đang làm việc thực tế</strong> tại đó vào thời điểm quyết toán (kể cả chưa làm đủ 12 tháng).</li>
<li>Người lao động điều chuyển từ tổ chức cũ sang tổ chức mới do sáp nhập, chia tách… được uỷ quyền cho tổ chức mới.</li>
</ul>
<p>Người lao động lập giấy uỷ quyền quyết toán thuế TNCN theo mẫu; khi đã uỷ quyền, doanh nghiệp không cấp chứng từ khấu trừ thuế cho người đó.</p>

<h2>3. Khi nào phải tự quyết toán?</h2>
<ul>
<li>Có thu nhập từ hai nơi trở lên và không đủ điều kiện uỷ quyền.</li>
<li>Có số thuế phải nộp thêm sau quyết toán.</li>
<li>Có số thuế nộp thừa muốn được hoàn hoặc bù trừ.</li>
<li>Người nước ngoài kết thúc hợp đồng làm việc tại Việt Nam (quyết toán trước khi xuất cảnh).</li>
</ul>
<p>Hạn tự quyết toán: <strong>ngày cuối cùng của tháng thứ 4</strong> sau khi kết thúc năm (30/4). Nếu ngày này trùng ngày nghỉ, hạn được lùi sang ngày làm việc tiếp theo.</p>

<h2>4. Cách tính khi quyết toán</h2>
<ol>
<li>Tổng thu nhập chịu thuế cả năm (từ tất cả các nơi).</li>
<li>Trừ bảo hiểm bắt buộc, giảm trừ gia cảnh (186 triệu/năm cho bản thân, 6,2 triệu/tháng cho mỗi người phụ thuộc theo [[tvpl:nq110]]), các khoản giảm trừ khác.</li>
<li>Áp biểu thuế luỹ tiến 5 bậc theo năm.</li>
<li>So với số đã khấu trừ → nộp thêm hoặc hoàn.</li>
</ol>
<p>Năm 2026 có giai đoạn chuyển tiếp: thuế đã khấu trừ 6 tháng đầu năm theo quy định cũ không phải khai lại; chênh lệch được xử lý khi quyết toán năm 2026.</p>

<h2>5. Nộp hồ sơ ở đâu?</h2>
<ul>
<li>Trực tuyến qua Cổng thông tin của Cục Thuế hoặc ứng dụng <strong>eTax Mobile</strong> – hệ thống hỗ trợ tờ khai gợi ý số liệu từ dữ liệu các nơi trả thu nhập đã kê khai.</li>
<li>Nơi nộp: cơ quan thuế quản lý tổ chức trả thu nhập (nếu có một nơi đang làm việc) hoặc nơi cư trú.</li>
</ul>

<h2>6. Lưu ý cho doanh nghiệp</h2>
<ul>
<li>Thu thập giấy uỷ quyền trước tháng 3.</li>
<li>Đối chiếu danh sách người phụ thuộc, số định danh cá nhân của nhân viên.</li>
<li>Cấp chứng từ khấu trừ thuế điện tử cho người không uỷ quyền.</li>
</ul>
<p>Doanh nghiệp đông nhân sự có thể giao [[quyet-toan-thue-cuoi-nam|dịch vụ quyết toán thuế]]; người lao động cần hoàn thuế xem [[hoan-thue-tncn|dịch vụ hoàn thuế TNCN]].</p>',
	),

	array(
		'slug'    => 'khau-tru-thue-tncn-10-phan-tram-thu-nhap-vang-lai',
		'cat'     => array( 'thue-tncn' ),
		'title'   => 'Khấu trừ thuế TNCN 10% thu nhập vãng lai: ngưỡng 5 triệu đồng từ 01/7/2026 và bản cam kết',
		'excerpt' => 'Doanh nghiệp chi trả cho người không ký hợp đồng lao động hoặc hợp đồng dưới 3 tháng phải khấu trừ 10% khi chi từ 5 triệu đồng/lần (trước là 2 triệu). Điều kiện làm cam kết không khấu trừ, chứng từ khấu trừ điện tử và cách hạch toán.',
		'content' => $u . '
<p>Thuê cộng tác viên, chuyên gia, người làm thời vụ là chuyện thường ngày ở doanh nghiệp nhỏ. Khoản chi này kéo theo nghĩa vụ <strong>khấu trừ thuế TNCN 10%</strong> – và ngưỡng khấu trừ vừa thay đổi từ 01/7/2026 theo [[tvpl:nd253]].</p>

<h2>1. Áp dụng với ai?</h2>
<ul>
<li>Cá nhân cư trú <strong>không ký hợp đồng lao động</strong>, hoặc ký hợp đồng lao động <strong>dưới 3 tháng</strong>.</li>
<li>Các khoản: tiền công, tiền thù lao, tiền dịch vụ, hoa hồng môi giới, tiền chi khác cho cá nhân.</li>
</ul>
<p>Người ký hợp đồng lao động từ 3 tháng trở lên thì khấu trừ theo biểu luỹ tiến từng phần, không áp dụng 10%.</p>

<h2>2. Ngưỡng khấu trừ mới</h2>
<table>
<thead><tr><th>Thời điểm chi trả</th><th>Ngưỡng phải khấu trừ 10%</th></tr></thead>
<tbody>
<tr><td>Đến 30/6/2026</td><td>Từ 2 triệu đồng/lần trở lên</td></tr>
<tr><td>Từ 01/7/2026</td><td>Từ <strong>5 triệu đồng/lần</strong> trở lên</td></tr>
</tbody>
</table>
<p>Ví dụ: tháng 8/2026 công ty trả 4 triệu đồng thù lao viết bài cho một cộng tác viên → không phải khấu trừ. Trả 12 triệu đồng phí thiết kế cho một cá nhân → khấu trừ 1,2 triệu đồng, chi thực nhận 10,8 triệu đồng.</p>
<p>Lưu ý: không chia nhỏ khoản chi để né ngưỡng – cơ quan thuế xem xét theo bản chất hợp đồng.</p>

<h2>3. Bản cam kết không khấu trừ</h2>
<p>Cá nhân <strong>chỉ có duy nhất</strong> thu nhập thuộc diện khấu trừ 10% nhưng ước tính tổng thu nhập chịu thuế cả năm sau khi trừ gia cảnh chưa đến mức phải nộp thuế, có thể làm <strong>bản cam kết</strong> gửi tổ chức trả thu nhập để tạm thời chưa khấu trừ. Với mức giảm trừ 15,5 triệu đồng/tháng (186 triệu đồng/năm) từ năm 2026 theo [[tvpl:nq110]], nhiều cá nhân chỉ có thu nhập vãng lai đủ điều kiện cam kết.</p>
<ul>
<li>Cá nhân phải có mã số thuế/số định danh cá nhân.</li>
<li>Cam kết sai sự thật bị xử lý theo pháp luật quản lý thuế.</li>
<li>Doanh nghiệp vẫn phải kê khai thu nhập đã trả trên tờ khai khấu trừ và quyết toán.</li>
</ul>

<h2>4. Chứng từ và kê khai</h2>
<ul>
<li>Cấp <strong>chứng từ khấu trừ thuế TNCN điện tử</strong> cho cá nhân khi có yêu cầu, theo [[tvpl:nd70]].</li>
<li>Kê khai số đã khấu trừ trên tờ khai thuế TNCN tháng/quý; quyết toán cuối năm kèm bảng kê thu nhập vãng lai.</li>
<li>Hợp đồng dịch vụ, biên bản nghiệm thu, chứng từ thanh toán (chuyển khoản từ 5 triệu đồng) làm căn cứ tính chi phí được trừ thuế TNDN theo [[tvpl:nd320]].</li>
</ul>

<h2>5. Cá nhân không cư trú</h2>
<p>Cá nhân không cư trú (ví dụ chuyên gia nước ngoài ở Việt Nam dưới 183 ngày) bị khấu trừ thuế TNCN với thuế suất riêng trên toàn bộ thu nhập, không áp dụng ngưỡng và không được cam kết.</p>

<h2>6. Sai lầm hay gặp</h2>
<ul>
<li>Không khấu trừ vì nghĩ “cộng tác viên tự lo thuế”.</li>
<li>Không có hợp đồng, chứng từ – chi phí bị loại khi quyết toán TNDN.</li>
<li>Nhận bản cam kết của người có thu nhập ở nhiều nơi.</li>
</ul>
<p>Muốn xử lý đúng thuế cho cộng tác viên, chuyên gia? Gói [[ke-toan-tron-goi|kế toán trọn gói]] kê khai, cấp chứng từ khấu trừ hằng tháng cho bạn.</p>',
	),

	array(
		'slug'    => 'dang-ky-nguoi-phu-thuoc-giam-tru-gia-canh-2026',
		'cat'     => array( 'thue-tncn' ),
		'title'   => 'Đăng ký người phụ thuộc giảm trừ gia cảnh năm 2026: ai được tính, hồ sơ và thời hạn',
		'excerpt' => 'Mức giảm trừ 6,2 triệu đồng/tháng cho mỗi người phụ thuộc; các nhóm người phụ thuộc (con, vợ/chồng, cha mẹ, người khác), điều kiện thu nhập không quá 3 triệu đồng/tháng từ 01/7/2026, hồ sơ chứng minh và cách đăng ký qua doanh nghiệp hoặc eTax.',
		'content' => $u . '
<p>Mỗi người phụ thuộc giúp giảm <strong>6,2 triệu đồng/tháng</strong> thu nhập tính thuế từ năm 2026 ([[tvpl:nq110]]). Đăng ký đúng, đủ người phụ thuộc là cách hợp pháp và đơn giản nhất để giảm thuế TNCN.</p>

<h2>1. Ai được tính là người phụ thuộc?</h2>
<ul>
<li><strong>Con</strong>: con dưới 18 tuổi; con từ 18 tuổi trở lên bị khuyết tật, không có khả năng lao động; con đang học đại học, cao đẳng, trung cấp, học nghề, phổ thông… không có thu nhập hoặc thu nhập không vượt mức quy định. Gồm con đẻ, con nuôi hợp pháp, con riêng của vợ/chồng.</li>
<li><strong>Vợ hoặc chồng</strong> không có thu nhập hoặc thu nhập không vượt mức quy định.</li>
<li><strong>Cha, mẹ</strong> (đẻ, vợ/chồng, nuôi hợp pháp) ngoài độ tuổi lao động hoặc trong độ tuổi lao động nhưng không có khả năng lao động, không có thu nhập hoặc thu nhập không vượt mức quy định.</li>
<li><strong>Người khác</strong> không nơi nương tựa mà người nộp thuế phải trực tiếp nuôi dưỡng (anh chị em ruột, ông bà, cô dì chú bác, cháu ruột…) đáp ứng điều kiện.</li>
</ul>

<h2>2. Mức thu nhập tối đa của người phụ thuộc</h2>
<p>Từ 01/7/2026, người phụ thuộc phải không có thu nhập hoặc có thu nhập bình quân tháng trong năm từ tất cả các nguồn <strong>không vượt quá 3 triệu đồng</strong> theo [[tvpl:tt87]] (trước đây là 1 triệu đồng). Người nộp thuế tự chịu trách nhiệm về việc xác định và kê khai đúng.</p>

<h2>3. Nguyên tắc giảm trừ</h2>
<ul>
<li>Mỗi người phụ thuộc chỉ được tính giảm trừ <strong>một lần vào một người nộp thuế</strong> trong cùng năm (ví dụ con chỉ đăng ký cho bố hoặc mẹ).</li>
<li>Được tính từ tháng phát sinh nghĩa vụ nuôi dưỡng.</li>
<li>Từ 01/7/2026, người lao động <strong>chuyển nơi làm việc không phải đăng ký lại</strong> người phụ thuộc đã đăng ký.</li>
</ul>

<h2>4. Hồ sơ chứng minh</h2>
<ul>
<li>Con: giấy khai sinh hoặc thông tin trên Cơ sở dữ liệu quốc gia về dân cư; xác nhận của nhà trường với con trên 18 tuổi đang học.</li>
<li>Vợ/chồng: thông tin hôn nhân trên cơ sở dữ liệu dân cư hoặc giấy chứng nhận kết hôn.</li>
<li>Cha mẹ: giấy tờ chứng minh quan hệ (cơ sở dữ liệu dân cư, giấy khai sinh của người nộp thuế…).</li>
<li>Người khuyết tật, không có khả năng lao động: giấy xác nhận khuyết tật hoặc hồ sơ bệnh án.</li>
</ul>
<p>Thông tin đã có trên Cơ sở dữ liệu quốc gia về dân cư thì không phải nộp lại giấy tờ.</p>

<h2>5. Cách đăng ký</h2>
<ol>
<li><strong>Qua doanh nghiệp</strong>: người lao động lập tờ khai đăng ký người phụ thuộc gửi tổ chức trả thu nhập; doanh nghiệp tổng hợp nộp cơ quan thuế.</li>
<li><strong>Tự đăng ký</strong>: qua eTax Mobile hoặc Cổng thông tin của Cục Thuế.</li>
<li>Người phụ thuộc dùng <strong>số định danh cá nhân</strong> thay mã số thuế.</li>
</ol>
<p>Đăng ký muộn trong năm vẫn được tính giảm trừ cho cả các tháng trước (từ tháng phát sinh nghĩa vụ nuôi dưỡng) khi quyết toán năm.</p>

<h2>6. Ví dụ</h2>
<p>Chị B lương tính thuế 30 triệu đồng/tháng sau bảo hiểm, nuôi 2 con nhỏ. Thu nhập tính thuế = 30 − 15,5 − 2 × 6,2 = 2,1 triệu đồng → thuế = 105.000 đồng/tháng. Nếu không đăng ký 2 con, thu nhập tính thuế là 14,5 triệu, thuế 950.000 đồng/tháng.</p>
<p>Cần đăng ký, cập nhật người phụ thuộc cho nhân viên hoặc quyết toán, hoàn thuế? Xem [[dang-ky-ma-so-thue-ca-nhan|dịch vụ đăng ký thuế cá nhân]] và [[hoan-thue-tncn|hoàn thuế TNCN]].</p>',
	),

	array(
		'slug'    => 'hoan-thue-tncn-ai-duoc-hoan-ho-so',
		'cat'     => array( 'thue-tncn' ),
		'title'   => 'Hoàn thuế TNCN: ai được hoàn, hồ sơ và cách đề nghị hoàn qua eTax',
		'excerpt' => 'Các trường hợp nộp thừa thuế TNCN được hoàn (khấu trừ 10% nhiều nơi, chưa tính đủ giảm trừ gia cảnh, nghỉ việc giữa năm), cách đề nghị hoàn ngay trên tờ khai quyết toán, thời hạn giải quyết và lỗi thường khiến hồ sơ hoàn bị chậm.',
		'content' => $u . '
<p>Nhiều người lao động nộp thừa thuế TNCN mà không biết, đặc biệt khi có thu nhập vãng lai bị khấu trừ 10% hoặc nghỉ việc giữa năm. Số tiền này hoàn toàn có thể lấy lại qua thủ tục quyết toán và hoàn thuế.</p>

<h2>1. Ai thường được hoàn thuế?</h2>
<ul>
<li>Bị khấu trừ 10% thu nhập vãng lai ở một hoặc nhiều nơi, trong khi tổng thu nhập cả năm sau giảm trừ gia cảnh chưa đến mức chịu thuế hoặc ở bậc thuế thấp.</li>
<li>Chưa đăng ký đủ người phụ thuộc khi doanh nghiệp khấu trừ hằng tháng.</li>
<li>Nghỉ việc, không có thu nhập vài tháng nhưng các tháng đi làm vẫn bị khấu trừ như cả năm.</li>
<li>Có khoản giảm trừ mới (chi phí y tế, giáo dục, đóng quỹ hưu trí tự nguyện) theo [[tvpl:nd253]] chưa được tính khi khấu trừ.</li>
<li>Năm 2026: chênh lệch do áp dụng biểu thuế, mức giảm trừ mới trong năm.</li>
</ul>

<h2>2. Điều kiện</h2>
<ul>
<li>Số thuế đã khấu trừ/đã nộp <strong>lớn hơn</strong> số thuế phải nộp theo quyết toán năm.</li>
<li>Cá nhân <strong>tự quyết toán</strong> (hoặc uỷ quyền cho doanh nghiệp quyết toán thay và doanh nghiệp đề nghị hoàn).</li>
<li>Có chứng từ khấu trừ thuế hoặc dữ liệu khấu trừ đã được tổ chức trả thu nhập kê khai.</li>
</ul>

<h2>3. Cách đề nghị hoàn</h2>
<ol>
<li>Đăng nhập eTax Mobile hoặc Cổng thông tin của Cục Thuế bằng tài khoản định danh.</li>
<li>Chọn quyết toán thuế TNCN – hệ thống gợi ý số liệu từ các nơi trả thu nhập.</li>
<li>Kiểm tra, bổ sung thu nhập, người phụ thuộc, khoản giảm trừ.</li>
<li>Tại chỉ tiêu số thuế nộp thừa, chọn <strong>đề nghị hoàn</strong> và nhập tài khoản ngân hàng đứng tên mình.</li>
<li>Nộp tờ khai, theo dõi thông báo trên hệ thống.</li>
</ol>
<p>Thủ tục hoàn thuế thực hiện theo [[tvpl:lqlt2025]] và [[tvpl:nd252]]; hồ sơ thuộc diện hoàn trước, kiểm tra sau thường được giải quyết nhanh.</p>

<h2>4. Thời hạn</h2>
<p>Hạn tự quyết toán là ngày cuối cùng của tháng thứ 4 sau khi kết thúc năm. Nộp trễ nhưng chỉ đề nghị hoàn (không phát sinh số phải nộp) thì không bị phạt chậm nộp hồ sơ. Số thuế nộp thừa cũng có thể được để lại bù trừ cho năm sau.</p>

<h2>5. Vì sao hồ sơ hoàn bị chậm?</h2>
<ul>
<li>Tổ chức trả thu nhập chưa kê khai hoặc kê khai sai số định danh của người lao động.</li>
<li>Thông tin cá nhân chưa khớp Cơ sở dữ liệu quốc gia về dân cư.</li>
<li>Kê khai người phụ thuộc trùng với người khác.</li>
<li>Tài khoản nhận hoàn không đứng tên người nộp thuế.</li>
</ul>

<h2>6. Ví dụ</h2>
<p>Anh C làm cộng tác viên cho 3 công ty, mỗi nơi bị khấu trừ 10%, tổng thu nhập năm 2026 là 150 triệu đồng, đã bị khấu trừ 15 triệu đồng. Sau giảm trừ bản thân 186 triệu đồng/năm, thu nhập tính thuế bằng 0 → anh C được hoàn toàn bộ 15 triệu đồng khi tự quyết toán.</p>
<p>Không rành thao tác trên eTax? [[hoan-thue-tncn|Dịch vụ hoàn thuế TNCN]] lập tờ khai, theo dõi hồ sơ đến khi tiền về tài khoản.</p>',
	),

	// ===================== THUẾ GTGT =====================
	array(
		'slug'    => 'thue-suat-thue-gtgt-2026',
		'cat'     => array( 'thue-gtgt' ),
		'title'   => 'Thuế suất thuế GTGT năm 2026: 0%, 5%, 8%, 10% áp dụng cho hàng hoá, dịch vụ nào?',
		'excerpt' => 'Ba mức thuế suất GTGT theo Luật Thuế GTGT 2024 (0%, 5%, 10%), mức giảm còn 8% đến hết 31/12/2026 theo Nghị định 174/2025, các nhóm không được giảm, và cách tra cứu thuế suất đúng khi lập hoá đơn.',
		'content' => $u . '
<p>Chọn sai thuế suất khi lập hoá đơn là lỗi phổ biến, dẫn đến phải lập hoá đơn điều chỉnh, khai bổ sung và có thể bị phạt. Bài viết tổng hợp các mức thuế suất đang áp dụng theo [[tvpl:lgtgt]] (sửa đổi bởi [[tvpl:lgtgt149|Luật số 149/2025/QH15]]) và [[tvpl:nd181]].</p>

<h2>1. Thuế suất 0%</h2>
<p>Áp dụng cho hàng hoá, dịch vụ <strong>xuất khẩu</strong>: hàng hoá xuất khẩu ra nước ngoài, bán vào khu phi thuế quan; dịch vụ cung cấp cho tổ chức, cá nhân ở nước ngoài và tiêu dùng ngoài Việt Nam; vận tải quốc tế; một số dịch vụ hàng không, hàng hải cho vận tải quốc tế. Phải đáp ứng điều kiện về hợp đồng, thanh toán không dùng tiền mặt, tờ khai hải quan (với hàng hoá).</p>

<h2>2. Thuế suất 5%</h2>
<p>Gồm các nhóm thiết yếu, ví dụ:</p>
<ul>
<li>Nước sạch phục vụ sản xuất, sinh hoạt.</li>
<li>Phân bón, quặng sản xuất phân bón, thuốc bảo vệ thực vật, máy móc chuyên dùng cho nông nghiệp, tàu đánh bắt xa bờ (chuyển từ không chịu thuế sang 5% theo Luật 2024).</li>
<li>Thiết bị, dụng cụ y tế; thuốc phòng, chữa bệnh; giáo cụ dùng để giảng dạy và học tập.</li>
<li>Sản phẩm trồng trọt, chăn nuôi, thuỷ sản chưa chế biến ở khâu kinh doanh thương mại (trừ trường hợp không chịu thuế).</li>
<li>Dịch vụ khoa học, công nghệ; nhà ở xã hội; một số hoạt động văn hoá, nghệ thuật, thể dục thể thao.</li>
</ul>

<h2>3. Thuế suất 10%</h2>
<p>Áp dụng cho các hàng hoá, dịch vụ còn lại không thuộc nhóm 0%, 5% và không thuộc đối tượng không chịu thuế.</p>

<h2>4. Giảm còn 8% đến hết năm 2026</h2>
<p>Theo Nghị quyết 204/2025/QH15 và [[tvpl:nd174]], hàng hoá, dịch vụ đang chịu thuế suất 10% được giảm còn <strong>8%</strong> từ 01/7/2025 đến hết <strong>31/12/2026</strong>, <strong>trừ</strong>:</p>
<ul>
<li>Viễn thông, công nghệ thông tin; hoạt động tài chính, ngân hàng, chứng khoán, bảo hiểm; kinh doanh bất động sản.</li>
<li>Kim loại, sản phẩm từ kim loại đúc sẵn; sản phẩm khai khoáng (trừ than); than cốc, dầu mỏ tinh chế, sản phẩm hoá chất (theo phụ lục).</li>
<li>Hàng hoá, dịch vụ chịu thuế tiêu thụ đặc biệt (trừ xăng).</li>
</ul>
<p>Hộ, doanh nghiệp tính thuế theo tỷ lệ % trên doanh thu được giảm 20% tỷ lệ % tương ứng. Cần tra cứu phụ lục của Nghị định 174/2025 theo mã hàng hoá.</p>

<h2>5. Lập hoá đơn đúng thuế suất</h2>
<ul>
<li>Mỗi dòng hàng hoá ghi thuế suất riêng; một hoá đơn có thể có nhiều thuế suất.</li>
<li>Hàng hoá được giảm thuế ghi rõ 8%; nếu lập nhầm 10% thì lập hoá đơn điều chỉnh/thay thế theo [[tvpl:nd70]].</li>
<li>Từ 01/01/2027 (nếu không có chính sách gia hạn), các mặt hàng được giảm sẽ trở lại 10% – cần cập nhật phần mềm hoá đơn.</li>
</ul>

<h2>6. Phế phẩm, phụ phẩm</h2>
<p>Luật số 149/2025/QH15 (hiệu lực 01/01/2026) quy định phế phẩm, phụ phẩm, phế liệu thu hồi trong quá trình sản xuất áp dụng thuế suất của chính mặt hàng phế phẩm, phụ phẩm, phế liệu đó.</p>
<p>Không chắc mặt hàng của mình chịu thuế suất nào? Dịch vụ [[bao-cao-thue-hang-thang-quy|báo cáo thuế hằng tháng, quý]] rà soát danh mục hàng hoá và thiết lập thuế suất trên phần mềm hoá đơn cho bạn.</p>',
	),

	array(
		'slug'    => 'doi-tuong-khong-chiu-thue-gtgt-2026',
		'cat'     => array( 'thue-gtgt' ),
		'title'   => 'Hàng hoá, dịch vụ không chịu thuế GTGT năm 2026: danh sách và lưu ý khi lập hoá đơn',
		'excerpt' => 'Các nhóm hàng hoá, dịch vụ không chịu thuế GTGT theo Luật Thuế GTGT 2024 sửa đổi 2025: nông sản chưa chế biến, dịch vụ tài chính, y tế, giáo dục, chuyển quyền sử dụng đất, hộ kinh doanh dưới ngưỡng…; phân biệt với thuế suất 0% và hệ quả về khấu trừ đầu vào.',
		'content' => $u . '
<p>“Không chịu thuế GTGT” khác với “thuế suất 0%”: hàng hoá không chịu thuế thì <strong>không được khấu trừ</strong> thuế GTGT đầu vào tương ứng, còn hàng hoá thuế suất 0% vẫn được khấu trừ và hoàn thuế. Phân loại đúng ảnh hưởng trực tiếp đến số thuế doanh nghiệp phải nộp.</p>

<h2>1. Các nhóm không chịu thuế thường gặp</h2>
<p>Theo Điều 5 [[tvpl:lgtgt]] (sửa đổi bởi [[tvpl:lgtgt149|Luật số 149/2025/QH15]]):</p>
<ul>
<li><strong>Sản phẩm trồng trọt, chăn nuôi, nuôi trồng thuỷ sản</strong> chưa chế biến hoặc chỉ qua sơ chế thông thường do tổ chức, cá nhân tự sản xuất, đánh bắt bán ra và ở khâu nhập khẩu. Từ 2026, doanh nghiệp, hợp tác xã mua các sản phẩm này để bán cho doanh nghiệp, hợp tác xã khác cũng không phải kê khai, tính nộp thuế GTGT.</li>
<li>Giống vật nuôi, giống cây trồng.</li>
<li><strong>Dịch vụ tài chính</strong>: cấp tín dụng, cho vay; chuyển nhượng vốn; kinh doanh chứng khoán; một số dịch vụ ngân hàng theo luật định.</li>
<li><strong>Bảo hiểm</strong> nhân thọ, bảo hiểm sức khoẻ, bảo hiểm cây trồng, vật nuôi…</li>
<li><strong>Dịch vụ y tế</strong>: khám, chữa bệnh, phòng bệnh; dịch vụ chăm sóc người cao tuổi, người khuyết tật.</li>
<li><strong>Dạy học, dạy nghề</strong> theo quy định của pháp luật.</li>
<li><strong>Chuyển quyền sử dụng đất</strong>.</li>
<li>Vận chuyển hành khách công cộng bằng xe buýt, xe điện.</li>
<li>Chuyển giao công nghệ, chuyển nhượng quyền sở hữu trí tuệ; <strong>phần mềm máy tính</strong> (phạm vi, điều kiện theo Nghị định 181/2025).</li>
<li>Hàng hoá chuyển khẩu, quá cảnh; hàng tạm nhập tái xuất.</li>
<li>Hàng hoá, dịch vụ của <strong>hộ, cá nhân kinh doanh</strong> có doanh thu năm dưới ngưỡng – hiện là 1 tỷ đồng theo [[tvpl:nd141]].</li>
</ul>
<p>Danh sách đầy đủ và điều kiện chi tiết xem Luật và [[tvpl:nd181]].</p>

<h2>2. Không chịu thuế khác thuế suất 0% thế nào?</h2>
<table>
<thead><tr><th>Tiêu chí</th><th>Không chịu thuế</th><th>Thuế suất 0%</th></tr></thead>
<tbody>
<tr><td>Thuế đầu ra</td><td>Không có</td><td>0 đồng</td></tr>
<tr><td>Khấu trừ thuế đầu vào</td><td>Không được khấu trừ – tính vào chi phí/giá vốn</td><td>Được khấu trừ</td></tr>
<tr><td>Hoàn thuế</td><td>Không</td><td>Được hoàn khi đủ điều kiện (từ 300 triệu đồng)</td></tr>
<tr><td>Ghi trên hoá đơn</td><td>Gạch chéo hoặc ghi “KCT”</td><td>Ghi 0%</td></tr>
</tbody>
</table>

<h2>3. Doanh nghiệp vừa có hàng chịu thuế, vừa có hàng không chịu thuế</h2>
<ul>
<li>Thuế đầu vào dùng riêng cho hàng chịu thuế: khấu trừ toàn bộ.</li>
<li>Dùng riêng cho hàng không chịu thuế: không khấu trừ.</li>
<li>Dùng chung: phân bổ theo tỷ lệ doanh thu chịu thuế trên tổng doanh thu theo hướng dẫn của Nghị định 181/2025 (sửa đổi bởi Nghị định 144/2026).</li>
</ul>

<h2>4. Lưu ý khi lập hoá đơn</h2>
<ul>
<li>Vẫn phải lập hoá đơn cho hàng hoá, dịch vụ không chịu thuế (trừ trường hợp được miễn lập theo quy định).</li>
<li>Không ghi thuế suất 0% cho hàng không chịu thuế và ngược lại.</li>
<li>Phần mềm máy tính thường bị nhầm với dịch vụ công nghệ thông tin chịu thuế (thiết kế website, thuê hạ tầng…) – cần xem kỹ bản chất hợp đồng.</li>
</ul>
<p>Doanh nghiệp có cả hàng chịu thuế và không chịu thuế nên để kế toán chuyên nghiệp phân bổ thuế đầu vào hằng kỳ – xem [[ke-toan-tron-goi|dịch vụ kế toán trọn gói]].</p>',
	),

	array(
		'slug'    => 'hoan-thue-gtgt-2026-truong-hop-dieu-kien',
		'cat'     => array( 'thue-gtgt' ),
		'title'   => 'Hoàn thuế GTGT năm 2026: các trường hợp được hoàn, điều kiện và hồ sơ',
		'excerpt' => 'Doanh nghiệp được hoàn thuế GTGT khi xuất khẩu, có dự án đầu tư, chỉ sản xuất hàng thuế suất 5% với số thuế chưa khấu trừ hết từ 300 triệu đồng; điều kiện hoàn thuế được nới từ 01/01/2026 theo Luật số 149/2025/QH15; hồ sơ và kinh nghiệm để được hoàn nhanh.',
		'content' => $u . '
<p>Hoàn thuế GTGT giúp doanh nghiệp thu hồi vốn bị “đọng” ở số thuế đầu vào chưa khấu trừ hết. Các trường hợp và điều kiện hoàn được quy định tại Điều 15 [[tvpl:lgtgt]] và hướng dẫn tại [[tvpl:nd181]].</p>

<h2>1. Các trường hợp được hoàn</h2>
<h3>Xuất khẩu</h3>
<p>Cơ sở kinh doanh trong tháng, quý có hàng hoá, dịch vụ xuất khẩu nếu số thuế GTGT đầu vào chưa được khấu trừ hết <strong>từ 300 triệu đồng</strong> trở lên thì được hoàn theo tháng, quý (trừ hàng nhập khẩu rồi xuất khẩu).</p>
<h3>Dự án đầu tư</h3>
<p>Cơ sở kinh doanh nộp thuế theo phương pháp khấu trừ có dự án đầu tư mới, dự án mở rộng: sau khi bù trừ, số thuế đầu vào của dự án chưa khấu trừ hết từ 300 triệu đồng trở lên được hoàn. Dự án phải đáp ứng điều kiện về vốn góp, giấy phép kinh doanh ngành có điều kiện (nếu có).</p>
<h3>Chỉ sản xuất hàng chịu thuế suất 5%</h3>
<p>Cơ sở kinh doanh chỉ sản xuất hàng hoá, cung cấp dịch vụ chịu thuế suất 5%, có số thuế đầu vào chưa khấu trừ hết từ 300 triệu đồng và thời gian luỹ kế chưa khấu trừ hết từ <strong>12 tháng hoặc 4 quý liên tục</strong>.</p>
<h3>Các trường hợp khác</h3>
<ul>
<li>Chuyển đổi sở hữu, chuyển đổi doanh nghiệp, sáp nhập, giải thể, chấm dứt hoạt động có số thuế nộp thừa hoặc chưa khấu trừ hết.</li>
<li>Người nước ngoài mang hàng hoá mua tại Việt Nam khi xuất cảnh.</li>
<li>Dự án sử dụng vốn ODA không hoàn lại, viện trợ nhân đạo.</li>
</ul>

<h2>2. Điều kiện hoàn – điểm mới từ 01/01/2026</h2>
<p>Hoá đơn đầu vào đề nghị hoàn phải đáp ứng điều kiện khấu trừ: có hoá đơn GTGT hoặc chứng từ nộp thuế khâu nhập khẩu, có chứng từ thanh toán không dùng tiền mặt với khoản từ 5 triệu đồng. [[tvpl:lgtgt149|Luật số 149/2025/QH15]] đã <strong>bãi bỏ</strong> điều kiện “người bán đã kê khai, nộp thuế GTGT đối với hoá đơn đã xuất” – doanh nghiệp không còn bị treo hồ sơ hoàn vì lỗi của nhà cung cấp.</p>

<h2>3. Trường hợp không được hoàn</h2>
<ul>
<li>Số thuế đầu vào chưa khấu trừ hết dưới 300 triệu đồng – chuyển sang khấu trừ kỳ sau.</li>
<li>Doanh nghiệp mới thành lập nhưng chưa có hoạt động xuất khẩu, dự án đầu tư hay trường hợp đặc thù.</li>
<li>Hàng hoá nhập khẩu rồi xuất khẩu, hàng xuất khẩu không thực hiện việc xuất khẩu tại địa bàn hoạt động hải quan theo quy định.</li>
</ul>

<h2>4. Hồ sơ hoàn thuế</h2>
<ul>
<li>Giấy đề nghị hoàn trả khoản thu ngân sách nhà nước.</li>
<li>Bảng kê hoá đơn, chứng từ hàng hoá, dịch vụ mua vào.</li>
<li>Tài liệu liên quan theo từng trường hợp: tờ khai hải quan, hợp đồng xuất khẩu, chứng từ thanh toán qua ngân hàng (xuất khẩu); giấy chứng nhận đăng ký đầu tư, chứng từ góp vốn (dự án đầu tư).</li>
</ul>
<p>Hồ sơ nộp điện tử; thủ tục, thời hạn giải quyết theo [[tvpl:lqlt2025]] và [[tvpl:nd252]].</p>

<h2>5. Kinh nghiệm để hoàn nhanh</h2>
<ul>
<li>Kiểm tra trạng thái hoạt động của nhà cung cấp trước khi nhận hoá đơn giá trị lớn.</li>
<li>Thanh toán qua ngân hàng đúng tài khoản người bán đăng ký.</li>
<li>Hồ sơ xuất khẩu đầy đủ, khớp số liệu giữa tờ khai hải quan, hoá đơn, chứng từ thanh toán.</li>
<li>Khai đúng kỳ đề nghị hoàn – không khai bổ sung tăng số thuế đầu vào sau khi đã đề nghị hoàn.</li>
</ul>
<p>Hồ sơ hoàn thuế thường bị yêu cầu giải trình nhiều lần nếu sổ sách thiếu chặt chẽ. [[hoan-thue-gtgt|Dịch vụ hoàn thuế GTGT]] chuẩn bị và theo dõi hồ sơ đến khi tiền được hoàn.</p>',
	),

	array(
		'slug'    => 'phuong-phap-tinh-thue-gtgt-khau-tru-va-truc-tiep',
		'cat'     => array( 'thue-gtgt' ),
		'title'   => 'Phương pháp khấu trừ và phương pháp trực tiếp tính thuế GTGT: doanh nghiệp nào áp dụng?',
		'excerpt' => 'Đối tượng áp dụng phương pháp khấu trừ và phương pháp tính trực tiếp theo Luật Thuế GTGT 2024: ngưỡng doanh thu 1 tỷ đồng của doanh nghiệp, đăng ký tự nguyện, tỷ lệ % trên doanh thu theo ngành và cách chọn phương pháp có lợi.',
		'content' => $u . '
<p>Thuế GTGT có hai phương pháp tính: <strong>khấu trừ</strong> và <strong>trực tiếp</strong>. Phương pháp quyết định cách lập hoá đơn, cách khai thuế và số thuế phải nộp. Quy định tại [[tvpl:lgtgt]] và [[tvpl:nd181]].</p>

<h2>1. Phương pháp khấu trừ</h2>
<p><strong>Thuế phải nộp = Thuế GTGT đầu ra − Thuế GTGT đầu vào được khấu trừ.</strong></p>
<p>Áp dụng cho:</p>
<ul>
<li>Doanh nghiệp, hợp tác xã có doanh thu năm từ <strong>1 tỷ đồng trở lên</strong>, thực hiện đầy đủ chế độ kế toán, hoá đơn, chứng từ.</li>
<li>Doanh nghiệp doanh thu dưới 1 tỷ đồng nhưng <strong>đăng ký tự nguyện</strong> áp dụng phương pháp khấu trừ.</li>
<li>Doanh nghiệp mới thành lập có dự án đầu tư, tổ chức nước ngoài cung cấp hàng hoá dịch vụ để tiến hành hoạt động tìm kiếm, khai thác dầu khí… theo quy định.</li>
</ul>
<p>Doanh nghiệp lập <strong>hoá đơn GTGT</strong>, khách hàng là doanh nghiệp được khấu trừ thuế ghi trên hoá đơn.</p>

<h2>2. Phương pháp trực tiếp</h2>
<p><strong>Thuế phải nộp = Tỷ lệ % × Doanh thu.</strong></p>
<table>
<thead><tr><th>Hoạt động</th><th>Tỷ lệ %</th></tr></thead>
<tbody>
<tr><td>Phân phối, cung cấp hàng hoá</td><td>1%</td></tr>
<tr><td>Dịch vụ, xây dựng không bao thầu nguyên vật liệu</td><td>5%</td></tr>
<tr><td>Sản xuất, vận tải, dịch vụ gắn với hàng hoá, xây dựng có bao thầu nguyên vật liệu</td><td>3%</td></tr>
<tr><td>Hoạt động kinh doanh khác</td><td>2%</td></tr>
</tbody>
</table>
<p>Áp dụng cho doanh nghiệp, hợp tác xã có doanh thu năm <strong>dưới 1 tỷ đồng</strong> (trừ khi đăng ký tự nguyện khấu trừ), hộ, cá nhân kinh doanh, và tổ chức kinh doanh không đầy đủ sổ sách. Doanh nghiệp lập <strong>hoá đơn bán hàng</strong>; khách hàng không được khấu trừ thuế.</p>
<p>Tỷ lệ % được giảm 20% đến hết 31/12/2026 với hàng hoá, dịch vụ thuộc diện giảm thuế theo [[tvpl:nd174]].</p>

<h2>3. Nên chọn phương pháp nào?</h2>
<ul>
<li><strong>Khách hàng chủ yếu là doanh nghiệp</strong> → nên khấu trừ: hoá đơn GTGT giúp khách hàng được khấu trừ, giá bán cạnh tranh hơn.</li>
<li><strong>Có nhiều đầu vào có hoá đơn GTGT</strong> (nhập hàng, mua tài sản lớn) → khấu trừ có lợi vì được trừ thuế đầu vào.</li>
<li><strong>Bán lẻ cho người tiêu dùng, ít đầu vào có hoá đơn</strong> → trực tiếp đơn giản hơn, nhưng doanh nghiệp vẫn phải giữ sổ sách đầy đủ.</li>
</ul>

<h2>4. Ví dụ so sánh</h2>
<p>Công ty thương mại doanh thu 800 triệu đồng/năm (chưa thuế), giá vốn hàng mua có hoá đơn GTGT 650 triệu đồng.</p>
<ul>
<li><strong>Trực tiếp</strong>: thuế = 1% × 800 triệu = 8 triệu đồng.</li>
<li><strong>Khấu trừ</strong> (thuế suất 10%): đầu ra 80 triệu − đầu vào 65 triệu = 15 triệu đồng, nhưng khách hàng doanh nghiệp được khấu trừ 80 triệu đồng.</li>
</ul>
<p>Lựa chọn còn phụ thuộc khách hàng có cần hoá đơn GTGT không – cần tính toán theo thực tế.</p>

<h2>5. Thủ tục đăng ký, chuyển đổi</h2>
<ul>
<li>Doanh nghiệp mới chọn phương pháp khi khai thuế ban đầu.</li>
<li>Đăng ký tự nguyện khấu trừ: nộp thông báo với cơ quan thuế, áp dụng ổn định theo quy định.</li>
<li>Khi doanh thu vượt hoặc xuống dưới ngưỡng, phương pháp thay đổi từ năm tiếp theo theo hướng dẫn của cơ quan thuế.</li>
</ul>
<p>Cần tư vấn chọn phương pháp ngay từ khi thành lập? [[khai-thue-ban-dau|Dịch vụ khai thuế ban đầu]] tính thử số thuế theo cả hai phương pháp cho bạn.</p>',
	),

	// ===================== THUẾ HỘ, CÁ NHÂN KINH DOANH =====================
	array(
		'slug'    => 'cach-ke-khai-thue-ho-kinh-doanh-2026',
		'cat'     => array( 'thue-ho-kinh-doanh' ),
		'title'   => 'Cách kê khai thuế hộ kinh doanh năm 2026: kỳ khai, hạn nộp và thông báo doanh thu',
		'excerpt' => 'Hướng dẫn hộ kinh doanh tự kê khai thuế sau khi bỏ thuế khoán: hộ doanh thu đến 1 tỷ đồng thông báo doanh thu năm, hộ trên 1 tỷ khai theo quý hoặc tháng, hạn nộp, kê khai trên eTax Mobile và các lỗi thường gặp.',
		'content' => $u . '
<p>Từ 01/01/2026, hộ kinh doanh không còn được cơ quan thuế ấn định mức thuế khoán mà phải <strong>tự kê khai</strong>. Cách khai phụ thuộc doanh thu năm của hộ, theo [[tvpl:nd68]] đã sửa đổi bởi [[tvpl:nd141]].</p>

<h2>1. Hộ có doanh thu năm đến 1 tỷ đồng</h2>
<ul>
<li><strong>Không phải nộp</strong> thuế GTGT, thuế TNCN.</li>
<li>Vẫn phải <strong>thông báo doanh thu thực tế</strong> của năm với cơ quan thuế, chậm nhất ngày <strong>31/01 năm sau</strong>.</li>
<li>Hộ mới kinh doanh trong 6 tháng đầu năm thông báo doanh thu đến 30/6 chậm nhất ngày 31/7.</li>
<li>Ghi sổ doanh thu theo [[tvpl:tt152]].</li>
</ul>

<h2>2. Hộ có doanh thu năm trên 1 tỷ đồng</h2>
<ul>
<li>Khai thuế GTGT và TNCN theo <strong>quý</strong> (hoặc tháng với hộ quy mô lớn theo quy định); hạn nộp chậm nhất ngày cuối cùng của tháng đầu quý sau (khai quý) hoặc ngày 20 tháng sau (khai tháng).</li>
<li>Khi doanh thu luỹ kế trong năm vượt 1 tỷ đồng, hộ bắt đầu khai, nộp thuế từ kỳ phát sinh doanh thu vượt ngưỡng.</li>
<li>Hộ tính thuế theo thu nhập (doanh thu − chi phí) quyết toán thuế TNCN năm chậm nhất ngày 31/3 năm sau.</li>
</ul>

<h2>3. Thuế được tính thế nào?</h2>
<ul>
<li><strong>Thuế GTGT</strong> = tỷ lệ % × <strong>toàn bộ doanh thu</strong> (1% hàng hoá, 5% dịch vụ, 3% sản xuất – vận tải, 2% khác).</li>
<li><strong>Thuế TNCN</strong>: hộ doanh thu trên 1 tỷ đến 3 tỷ đồng tính theo tỷ lệ % trên <strong>phần doanh thu vượt 1 tỷ</strong>, hoặc chọn tính theo thu nhập 15%; hộ trên 3 tỷ đồng bắt buộc tính theo thu nhập (17% hoặc 20%).</li>
</ul>
<p>Chi tiết xem bài Thuế hộ kinh doanh năm 2026 trong chuyên mục này.</p>

<h2>4. Kê khai ở đâu?</h2>
<ol>
<li>Đăng nhập <strong>eTax Mobile</strong> hoặc Cổng thông tin của Cục Thuế bằng tài khoản định danh điện tử (VNeID) hoặc mã số thuế hộ kinh doanh.</li>
<li>Chọn tờ khai dành cho hộ, cá nhân kinh doanh; nhập doanh thu theo nhóm ngành.</li>
<li>Hệ thống tự tính số thuế; kiểm tra, nộp tờ khai.</li>
<li>Nộp tiền qua ngân hàng điện tử, ví điện tử liên kết.</li>
</ol>
<p>Mẫu tờ khai theo hướng dẫn của Bộ Tài chính (Thông tư 18/2026/TT-BTC và [[tvpl:tt89]]).</p>

<h2>5. Hộ có nhiều địa điểm</h2>
<p>Hộ kinh doanh có nhiều địa điểm kinh doanh khai tổng doanh thu của tất cả địa điểm để xác định ngưỡng; báo cáo từng địa điểm với cơ quan thuế theo quy định. Doanh thu bán trên sàn thương mại điện tử đã được sàn khấu trừ vẫn phải tính vào tổng doanh thu.</p>

<h2>6. Lỗi thường gặp</h2>
<ul>
<li>Không thông báo doanh thu vì nghĩ “dưới 1 tỷ không cần làm gì”.</li>
<li>Không cộng doanh thu bán online, chuyển khoản vào tài khoản cá nhân.</li>
<li>Khai sai nhóm ngành (hàng hoá/dịch vụ) dẫn đến sai tỷ lệ thuế.</li>
<li>Không lưu sổ doanh thu, hoá đơn tối thiểu 5 năm.</li>
</ul>
<p>Không có thời gian ghi sổ và kê khai? [[ke-toan-ho-kinh-doanh|Dịch vụ kế toán hộ kinh doanh]] làm thay trọn năm với chi phí cố định.</p>',
	),

	array(
		'slug'    => 'thue-cho-thue-nha-2026',
		'cat'     => array( 'thue-ho-kinh-doanh', 'thue-tncn' ),
		'title'   => 'Thuế cho thuê nhà năm 2026: doanh thu đến 1 tỷ đồng được miễn, cách tính và kê khai',
		'excerpt' => 'Cá nhân cho thuê nhà, mặt bằng, kho bãi có doanh thu năm đến 1 tỷ đồng không phải nộp thuế GTGT, TNCN (Nghị định 141/2026); trên 1 tỷ nộp GTGT 5% trên toàn bộ doanh thu và TNCN 5% trên phần vượt 1 tỷ; kỳ khai, hạn nộp và trường hợp bên thuê khai thay.',
		'content' => $u . '
<p>Cho thuê nhà, mặt bằng, kho xưởng là nguồn thu nhập phổ biến của nhiều gia đình. Từ năm 2026, chính sách thuế với hoạt động này thay đổi theo hướng có lợi: ngưỡng không phải nộp thuế được nâng lên <strong>1 tỷ đồng/năm</strong> theo [[tvpl:nd141]].</p>

<h2>1. Áp dụng với ai?</h2>
<p>Cá nhân cho thuê bất động sản – nhà ở, mặt bằng, văn phòng, kho bãi (mã ngành 6810) – <strong>không phải</strong> kinh doanh dịch vụ lưu trú (khách sạn, nhà nghỉ, homestay theo ngày – mã 5510, 5520, 5530, 5590 tính thuế như hộ kinh doanh dịch vụ lưu trú).</p>

<h2>2. Doanh thu đến 1 tỷ đồng/năm</h2>
<p>Không phải nộp thuế GTGT và thuế TNCN. Doanh thu tính theo tổng tiền thuê của năm dương lịch (kể cả nhiều hợp đồng, nhiều tài sản).</p>

<h2>3. Doanh thu trên 1 tỷ đồng/năm</h2>
<ul>
<li><strong>Thuế GTGT</strong> = 5% × <strong>toàn bộ doanh thu</strong>.</li>
<li><strong>Thuế TNCN</strong> = 5% × (doanh thu − 1 tỷ đồng).</li>
</ul>
<p><em>Ví dụ:</em> ông D cho thuê 2 mặt bằng, tổng tiền thuê năm 2026 là 1,4 tỷ đồng.</p>
<ul>
<li>Thuế GTGT = 1,4 tỷ × 5% = 70 triệu đồng.</li>
<li>Thuế TNCN = (1,4 tỷ − 1 tỷ) × 5% = 20 triệu đồng.</li>
<li>Tổng thuế: 90 triệu đồng.</li>
</ul>

<h2>4. Kỳ khai và hạn nộp</h2>
<p>Theo [[tvpl:nd68]], cá nhân cho thuê được chọn:</p>
<ul>
<li><strong>Khai 2 lần/năm</strong>: hạn 31/7 (6 tháng đầu năm) và 31/01 năm sau (6 tháng cuối năm); hoặc</li>
<li><strong>Khai 1 lần/năm</strong>: hạn 31/01 năm sau.</li>
</ul>
<p>Hợp đồng nhận tiền trước nhiều năm: doanh thu phân bổ theo từng năm của thời gian thuê để xác định ngưỡng và số thuế.</p>

<h2>5. Bên thuê khai thay</h2>
<p>Khi cho doanh nghiệp, tổ chức thuê, hai bên có thể thoả thuận trong hợp đồng để <strong>bên thuê khai, nộp thuế thay</strong> cá nhân cho thuê. Khi đó bên thuê khấu trừ số thuế trước khi trả tiền và nộp vào ngân sách; tiền thuê nhà và chứng từ khai thay là căn cứ để doanh nghiệp tính chi phí được trừ.</p>

<h2>6. Hồ sơ cần chuẩn bị</h2>
<ul>
<li>Tờ khai thuế dành cho cá nhân cho thuê tài sản.</li>
<li>Bản sao hợp đồng thuê (lần khai đầu hoặc khi có thay đổi).</li>
<li>Bản sao giấy uỷ quyền (nếu khai thay).</li>
</ul>

<h2>7. Lưu ý</h2>
<ul>
<li>Doanh thu dưới ngưỡng vẫn nên giữ hợp đồng, chứng từ nhận tiền để chứng minh khi cơ quan thuế đối chiếu.</li>
<li>Không “tách” hợp đồng cho người thân đứng tên để né ngưỡng nếu thực chất là một người cho thuê.</li>
<li>Từ 2026 không còn lệ phí môn bài đối với hoạt động cho thuê.</li>
</ul>
<p>Cần khai thuế cho thuê nhà đúng hạn hoặc tư vấn hợp đồng có điều khoản khai thay? Liên hệ [[ke-toan-ho-kinh-doanh|dịch vụ kế toán hộ kinh doanh]].</p>',
	),

	array(
		'slug'    => 'thue-ban-hang-online-san-thuong-mai-dien-tu-2026',
		'cat'     => array( 'thue-ho-kinh-doanh' ),
		'title'   => 'Thuế bán hàng online năm 2026: sàn thương mại điện tử khấu trừ thay thế nào?',
		'excerpt' => 'Sàn thương mại điện tử có chức năng đặt hàng, thanh toán khấu trừ thuế thay hộ, cá nhân kinh doanh (GTGT 1% hàng hoá, 5% dịch vụ; TNCN 0,5% hàng hoá, 2% dịch vụ); người bán vẫn phải tổng hợp doanh thu, tự khai phần bán ngoài sàn và đề nghị hoàn khi doanh thu năm đến 1 tỷ đồng.',
		'content' => $u . '
<p>Bán hàng trên Shopee, TikTok Shop, Lazada hay qua mạng xã hội đều là hoạt động kinh doanh phải nộp thuế. Từ 01/7/2025, sàn thương mại điện tử có chức năng đặt hàng và thanh toán phải <strong>khấu trừ, nộp thuế thay</strong> cho người bán là hộ, cá nhân; năm 2026 cơ chế này được hướng dẫn tại [[tvpl:nd68]] (sửa đổi bởi [[tvpl:nd141]]).</p>

<h2>1. Sàn nào phải khấu trừ thay?</h2>
<p>Tổ chức quản lý nền tảng thương mại điện tử, nền tảng số (trong nước hoặc nước ngoài) <strong>có chức năng đặt hàng trực tuyến và thanh toán</strong>. Sàn khấu trừ thuế trên từng giao dịch, khai và nộp thay vào ngân sách, cấp chứng từ khấu trừ cho người bán.</p>

<h2>2. Tỷ lệ khấu trừ</h2>
<table>
<thead><tr><th>Loại</th><th>Thuế GTGT</th><th>Thuế TNCN</th></tr></thead>
<tbody>
<tr><td>Hàng hoá</td><td>1%</td><td>0,5%</td></tr>
<tr><td>Dịch vụ</td><td>5%</td><td>2%</td></tr>
</tbody>
</table>
<p>Tỷ lệ tính trên giá trị giao dịch thành công (đã trừ hàng hoàn, huỷ theo quy định). Cá nhân không cư trú có thể bị khấu trừ theo tỷ lệ khác.</p>

<h2>3. Người bán vẫn phải làm gì?</h2>
<ul>
<li><strong>Tổng hợp toàn bộ doanh thu</strong>: trên các sàn, mạng xã hội, website riêng, bán tại cửa hàng – để xác định có vượt ngưỡng 1 tỷ đồng/năm hay không.</li>
<li><strong>Tự khai, nộp thuế</strong> phần doanh thu không qua sàn khấu trừ (bán qua Facebook, Zalo, livestream nhận chuyển khoản trực tiếp; sàn không có chức năng thanh toán).</li>
<li>Thuế đã bị sàn khấu trừ được trừ khi xác định số thuế phải nộp của hộ.</li>
<li>Lưu chứng từ khấu trừ do sàn cấp.</li>
</ul>

<h2>4. Doanh thu cả năm đến 1 tỷ đồng: được hoàn hoặc bù trừ</h2>
<p>Hộ, cá nhân kinh doanh có tổng doanh thu năm đến 1 tỷ đồng không phải nộp thuế GTGT, TNCN. Nếu đã bị sàn khấu trừ, số thuế này là <strong>nộp thừa</strong> – người bán được đề nghị <strong>hoàn hoặc bù trừ</strong> theo Nghị định 141/2026, khi khai thông báo doanh thu năm với cơ quan thuế.</p>

<h2>5. Ví dụ</h2>
<p>Chị E bán mỹ phẩm trên sàn, doanh thu 2026 là 1,3 tỷ đồng (đều qua sàn). Sàn đã khấu trừ GTGT 13 triệu và TNCN 6,5 triệu đồng.</p>
<ul>
<li>Thuế GTGT phải nộp: 1% × 1,3 tỷ = 13 triệu đồng → đã đủ.</li>
<li>Thuế TNCN theo tỷ lệ: 0,5% × (1,3 tỷ − 1 tỷ) = 1,5 triệu đồng → đã bị khấu trừ 6,5 triệu, nộp thừa 5 triệu đồng, được đề nghị hoàn hoặc bù trừ khi khai năm.</li>
</ul>

<h2>6. Hoá đơn</h2>
<p>Hộ bán hàng online có doanh thu năm trên 1 tỷ đồng phải dùng hoá đơn điện tử có mã hoặc hoá đơn khởi tạo từ máy tính tiền theo quy định; một số giao dịch trên sàn đã có chứng từ của sàn có thể không phải lập hoá đơn riêng cho người tiêu dùng – cần kiểm tra theo hướng dẫn cụ thể.</p>
<p>Bán trên nhiều kênh và không biết tổng hợp doanh thu thế nào? [[ke-toan-ho-kinh-doanh|Dịch vụ kế toán hộ kinh doanh]] đối soát doanh thu từng sàn và kê khai đúng ngưỡng.</p>',
	),

	array(
		'slug'    => 'ho-kinh-doanh-nen-tinh-thue-theo-ty-le-hay-theo-thu-nhap',
		'cat'     => array( 'thue-ho-kinh-doanh' ),
		'title'   => 'Hộ kinh doanh nên tính thuế TNCN theo tỷ lệ doanh thu hay theo thu nhập? Ví dụ so sánh',
		'excerpt' => 'Hộ kinh doanh doanh thu trên 1 tỷ đến 3 tỷ đồng được chọn tính thuế TNCN theo tỷ lệ % trên phần vượt 1 tỷ hoặc theo thu nhập 15%; ví dụ tính cho cửa hàng tạp hoá, quán ăn, dịch vụ sửa chữa và điều kiện sổ sách khi chọn tính theo thu nhập.',
		'content' => $u . '
<p>Hộ kinh doanh có doanh thu năm <strong>trên 1 tỷ đến 3 tỷ đồng</strong> được lựa chọn một trong hai cách tính thuế TNCN theo [[tvpl:nd68]] (sửa đổi bởi [[tvpl:nd141]]). Chọn đúng có thể tiết kiệm vài chục triệu đồng mỗi năm.</p>

<h2>1. Hai cách tính</h2>
<ul>
<li><strong>Cách 1 – theo tỷ lệ</strong>: Thuế TNCN = tỷ lệ % theo ngành × (doanh thu − 1 tỷ đồng). Tỷ lệ: phân phối hàng hoá 0,5%; dịch vụ 2%; sản xuất, vận tải, dịch vụ gắn với hàng hoá 1,5%; cho thuê tài sản 5%; khác 1%.</li>
<li><strong>Cách 2 – theo thu nhập</strong>: Thuế TNCN = (doanh thu − chi phí được trừ) × 15%.</li>
</ul>
<p>Thuế GTGT không phụ thuộc lựa chọn này – vẫn tính theo tỷ lệ % trên toàn bộ doanh thu. Hộ có doanh thu trên 3 tỷ đồng bắt buộc tính theo thu nhập (17% – 20%).</p>

<h2>2. Ví dụ 1: Cửa hàng tạp hoá – biên lợi nhuận thấp</h2>
<p>Doanh thu 2,5 tỷ đồng; chi phí có chứng từ (hàng hoá, thuê mặt bằng, lương) 2,3 tỷ đồng.</p>
<ul>
<li>Cách 1: 0,5% × (2,5 − 1) tỷ = <strong>7,5 triệu đồng</strong>.</li>
<li>Cách 2: (2,5 − 2,3) tỷ × 15% = <strong>30 triệu đồng</strong>.</li>
</ul>
<p>→ Tạp hoá nên chọn <strong>cách 1</strong>: tỷ lệ 0,5% rất thấp.</p>

<h2>3. Ví dụ 2: Dịch vụ sửa chữa – chi phí lớn</h2>
<p>Doanh thu 2 tỷ đồng; chi phí linh kiện, lương thợ, thuê xưởng có đủ hoá đơn, chứng từ 1,85 tỷ đồng.</p>
<ul>
<li>Cách 1: 2% × (2 − 1) tỷ = <strong>20 triệu đồng</strong>.</li>
<li>Cách 2: (2 − 1,85) tỷ × 15% = <strong>22,5 triệu đồng</strong>.</li>
</ul>
<p>→ Gần tương đương; cách 1 đơn giản hơn về sổ sách.</p>

<h2>4. Ví dụ 3: Quán ăn – có năm lỗ</h2>
<p>Doanh thu 1,8 tỷ đồng; chi phí thực tế có chứng từ 1,82 tỷ đồng (lỗ do mới mở rộng).</p>
<ul>
<li>Cách 1 (dịch vụ ăn uống gắn với hàng hoá 1,5%): 1,5% × 0,8 tỷ = <strong>12 triệu đồng</strong>.</li>
<li>Cách 2: thu nhập âm → <strong>0 đồng</strong>.</li>
</ul>
<p>→ Năm lỗ hoặc lãi rất mỏng, <strong>cách 2</strong> có lợi – với điều kiện chứng minh được chi phí.</p>

<h2>5. Điều kiện khi chọn tính theo thu nhập</h2>
<ul>
<li>Ghi sổ doanh thu, chi phí, hàng tồn kho, tiền theo [[tvpl:tt152]].</li>
<li>Chi phí có hoá đơn, chứng từ hợp lệ; khoản từ 5 triệu đồng phải chuyển khoản.</li>
<li>Không được trừ tiền lương của chủ hộ và thành viên hộ, chi phí sinh hoạt gia đình.</li>
<li>Áp dụng ổn định tối thiểu <strong>2 năm</strong> liên tục; quyết toán thuế TNCN năm.</li>
</ul>

<h2>6. Lời khuyên</h2>
<p>Ngành có tỷ lệ thấp (phân phối hàng hoá) gần như luôn có lợi khi tính theo tỷ lệ. Ngành dịch vụ chi phí lớn, hoặc năm đầu tư mở rộng, nên tính thử theo thu nhập. Hãy tính trên số liệu thực tế cả năm trước khi đăng ký.</p>
<p>[[ke-toan-ho-kinh-doanh|Dịch vụ kế toán hộ kinh doanh]] tính thử cả hai cách trên sổ sách thật và đăng ký phương pháp có lợi nhất cho bạn.</p>',
	),
);
