<?php
/**
 * Bài viết Kiến thức kế toán (đợt 2): Hoá đơn – chứng từ, Báo cáo thuế – Tài chính, Sổ sách kế toán,
 * Bảo hiểm xã hội – mỗi mục 4 bài.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$u = '<p class="sgd-updated"><em>Cập nhật tháng 10/2026 theo văn bản đang có hiệu lực. Chính sách thuế, kế toán, bảo hiểm thay đổi thường xuyên – trước khi áp dụng, bạn nên liên hệ chuyên viên để được kiểm tra theo trường hợp cụ thể.</em></p>';

return array(

	// ===================== HOÁ ĐƠN, CHỨNG TỪ =====================
	array(
		'slug'    => 'xu-ly-hoa-don-dien-tu-sai-sot-dieu-chinh-thay-the',
		'cat'     => array( 'hoa-don-chung-tu' ),
		'title'   => 'Xử lý hoá đơn điện tử sai sót: khi nào điều chỉnh, khi nào thay thế, khi nào chỉ cần thông báo?',
		'excerpt' => 'Hướng dẫn xử lý hoá đơn điện tử đã lập có sai sót theo Nghị định 123/2020 sửa đổi bởi Nghị định 70/2025: sai tên, địa chỉ người mua; sai mã số thuế, số tiền, thuế suất; văn bản thoả thuận với người mua; mẫu 04/SS-HĐĐT và cách kê khai thuế.',
		'content' => $u . '
<p>Lập sai hoá đơn là chuyện khó tránh: gõ nhầm tên công ty, chọn nhầm thuế suất 8% thành 10%, ghi sai số lượng. Điều quan trọng là xử lý đúng cách để người mua vẫn được khấu trừ và doanh nghiệp không bị phạt. Quy định áp dụng theo [[tvpl:nd123]] đã sửa đổi bởi [[tvpl:nd70]] (từ 01/6/2025).</p>

<h2>1. Hoá đơn chưa gửi cho người mua</h2>
<p>Hoá đơn có mã của cơ quan thuế phát hiện sai trước khi gửi người mua: thông báo với cơ quan thuế theo mẫu 04/SS-HĐĐT về việc huỷ hoá đơn có mã đã cấp, lập hoá đơn mới gửi cơ quan thuế cấp mã. Hoá đơn không có mã chưa gửi: lập hoá đơn mới thay thế theo quy định.</p>

<h2>2. Sai tên, địa chỉ người mua (mã số thuế đúng)</h2>
<ul>
<li>Người bán <strong>thông báo cho người mua</strong> về việc hoá đơn có sai sót.</li>
<li><strong>Không phải lập lại hoá đơn</strong>.</li>
<li>Người bán thông báo với cơ quan thuế theo mẫu 04/SS-HĐĐT (trừ trường hợp hoá đơn không có mã chưa gửi dữ liệu).</li>
</ul>

<h2>3. Sai mã số thuế, số tiền, thuế suất, tên hàng, số lượng</h2>
<p>Người bán chọn <strong>một trong hai</strong> cách:</p>
<h3>Lập hoá đơn điều chỉnh</h3>
<ul>
<li>Ghi rõ “Điều chỉnh cho hoá đơn Mẫu số… Ký hiệu… Số… ngày… tháng… năm…”.</li>
<li>Điều chỉnh tăng ghi số dương, điều chỉnh giảm ghi số âm phần chênh lệch.</li>
</ul>
<h3>Lập hoá đơn thay thế</h3>
<ul>
<li>Ghi rõ “Thay thế cho hoá đơn Mẫu số… Ký hiệu… Số… ngày… tháng… năm…”.</li>
<li>Hoá đơn thay thế ghi đầy đủ, đúng toàn bộ nội dung.</li>
</ul>
<p>Nếu người mua là doanh nghiệp, tổ chức kinh doanh: hai bên <strong>lập văn bản thoả thuận</strong> ghi rõ nội dung sai trước khi lập hoá đơn điều chỉnh/thay thế. Người mua là cá nhân: người bán thông báo cho người mua hoặc thông báo trên website (nếu có).</p>

<h2>4. Không còn “huỷ hoá đơn” như trước</h2>
<p>Nghị định 70/2025 đã bãi bỏ quy định về huỷ hoá đơn điện tử khi phát hiện sai sót sau khi đã gửi – mọi trường hợp xử lý bằng điều chỉnh hoặc thay thế.</p>

<h2>5. Kê khai thuế</h2>
<ul>
<li>Hoá đơn điều chỉnh, thay thế được khai vào <strong>kỳ lập hoá đơn điều chỉnh/thay thế</strong>, không phải khai bổ sung kỳ cũ.</li>
<li>Riêng trường hợp sai sót làm thay đổi nghĩa vụ thuế của kỳ trước mà đã bị phát hiện qua kiểm tra, xử lý theo yêu cầu của cơ quan thuế.</li>
</ul>

<h2>6. Cơ quan thuế phát hiện sai</h2>
<p>Khi cơ quan thuế thông báo hoá đơn có sai sót (mẫu 01/TB-RSĐT), người bán phải kiểm tra và thông báo lại với cơ quan thuế trong thời hạn ghi trên thông báo.</p>

<h2>7. Mẹo hạn chế sai sót</h2>
<ul>
<li>Lấy thông tin người mua bằng cách tra mã số thuế trên phần mềm thay vì gõ tay.</li>
<li>Cài đặt sẵn thuế suất theo từng mặt hàng (8%/10%) trên phần mềm hoá đơn.</li>
<li>Kiểm tra hoá đơn nháp trước khi ký số.</li>
</ul>
<p>Doanh nghiệp xuất nhiều hoá đơn mỗi tháng có thể giao việc rà soát cho [[ke-toan-tron-goi|dịch vụ kế toán trọn gói]]; cần thiết lập phần mềm hoá đơn chuẩn ngay từ đầu xem [[chu-ky-so-hoa-don-dien-tu|dịch vụ chữ ký số và hoá đơn điện tử]].</p>',
	),

	array(
		'slug'    => 'hoa-don-dien-tu-khoi-tao-tu-may-tinh-tien',
		'cat'     => array( 'hoa-don-chung-tu', 'thue-ho-kinh-doanh' ),
		'title'   => 'Hoá đơn điện tử khởi tạo từ máy tính tiền: ai phải dùng, cách đăng ký và lợi ích',
		'excerpt' => 'Hoá đơn điện tử khởi tạo từ máy tính tiền kết nối dữ liệu với cơ quan thuế: đối tượng bắt buộc (hộ kinh doanh doanh thu trên 1 tỷ đồng, doanh nghiệp bán lẻ, ăn uống…), nội dung hoá đơn, cách đăng ký và lưu ý khi sử dụng.',
		'content' => $u . '
<p>Hoá đơn khởi tạo từ máy tính tiền (thường gọi là “hoá đơn máy tính tiền”) cho phép cửa hàng in hoá đơn ngay tại quầy, dữ liệu tự động chuyển đến cơ quan thuế. Từ năm 2026, đây là lựa chọn phổ biến của hộ kinh doanh bán lẻ sau khi bỏ thuế khoán.</p>

<h2>1. Ai phải dùng?</h2>
<ul>
<li><strong>Hộ, cá nhân kinh doanh</strong> có doanh thu năm <strong>trên 1 tỷ đồng</strong> phải dùng hoá đơn điện tử có mã của cơ quan thuế hoặc hoá đơn khởi tạo từ máy tính tiền ([[tvpl:nd68]], sửa đổi bởi [[tvpl:nd141]]). Hộ có doanh thu năm trước chưa đến 1 tỷ nhưng trong năm vượt 1 tỷ đồng cũng phải áp dụng.</li>
<li><strong>Doanh nghiệp, hộ kinh doanh</strong> bán hàng trực tiếp đến người tiêu dùng: trung tâm thương mại, siêu thị, bán lẻ, ăn uống, nhà hàng, khách sạn, dịch vụ vận tải hành khách, vui chơi giải trí… được khuyến khích hoặc thuộc diện phải áp dụng theo [[tvpl:nd123]] (sửa đổi bởi [[tvpl:nd70]]).</li>
</ul>

<h2>2. Đặc điểm</h2>
<ul>
<li>Hoá đơn có <strong>mã của cơ quan thuế</strong> được tạo từ máy tính tiền theo cấu trúc riêng; ký hiệu hoá đơn có chữ cái “M”.</li>
<li>Không bắt buộc có chữ ký số của người bán trên từng hoá đơn.</li>
<li>Không bắt buộc có tên, địa chỉ, mã số thuế người mua – trừ khi người mua yêu cầu (doanh nghiệp, cá nhân cần hoá đơn).</li>
<li>Dữ liệu chuyển đến cơ quan thuế <strong>trong ngày</strong> (chậm nhất ngày làm việc tiếp theo trong một số trường hợp).</li>
</ul>

<h2>3. Thiết bị cần có</h2>
<p>Không cần máy tính tiền chuyên dụng đắt tiền: có thể dùng máy tính, máy bán hàng POS, máy tính bảng hoặc điện thoại cài phần mềm bán hàng có kết nối hoá đơn, kèm máy in hoá đơn. Phần mềm phải do nhà cung cấp giải pháp hoá đơn đáp ứng chuẩn dữ liệu của cơ quan thuế.</p>

<h2>4. Các bước đăng ký</h2>
<ol>
<li>Chọn nhà cung cấp phần mềm bán hàng – hoá đơn máy tính tiền.</li>
<li>Đăng ký sử dụng hoá đơn điện tử (chọn loại khởi tạo từ máy tính tiền) qua cổng hoá đơn điện tử của cơ quan thuế hoặc qua nhà cung cấp.</li>
<li>Nhận thông báo chấp nhận của cơ quan thuế.</li>
<li>Khai báo danh mục hàng hoá, thuế suất (8%/10% hoặc tỷ lệ % với người nộp thuế theo phương pháp trực tiếp).</li>
<li>Bắt đầu xuất hoá đơn cho từng lần bán.</li>
</ol>
<p>Hộ kinh doanh nhiều cửa hàng dùng chung mã số thuế, ghi rõ địa chỉ từng điểm bán trên hoá đơn.</p>

<h2>5. Lợi ích</h2>
<ul>
<li>Doanh thu được ghi nhận tự động – thuận tiện khi kê khai thuế, giảm rủi ro bị ấn định doanh thu.</li>
<li>Khách hàng doanh nghiệp được hoá đơn hợp lệ ngay tại quầy.</li>
<li>Không phải ký số từng hoá đơn, xuất hoá đơn nhanh.</li>
</ul>

<h2>6. Lưu ý</h2>
<ul>
<li>Phải lập hoá đơn cho <strong>mọi lần bán</strong>, kể cả khi khách không lấy hoá đơn.</li>
<li>Hoá đơn sai xử lý theo quy định về hoá đơn điện tử (điều chỉnh, thay thế).</li>
<li>Không lập hoá đơn đúng thời điểm bị phạt theo số lượng hoá đơn theo [[tvpl:nd310]].</li>
</ul>
<p>Cần chọn và cài đặt hoá đơn máy tính tiền cho cửa hàng? Xem [[hoa-don-dien-tu|dịch vụ hoá đơn điện tử]] và [[ke-toan-ho-kinh-doanh|kế toán hộ kinh doanh]].</p>',
	),

	array(
		'slug'    => 'muc-phat-vi-pham-hoa-don-2026',
		'cat'     => array( 'hoa-don-chung-tu' ),
		'title'   => 'Mức phạt vi phạm hoá đơn năm 2026 theo Nghị định 310/2025: lập sai thời điểm, không lập hoá đơn',
		'excerpt' => 'Từ 16/01/2026, mức phạt lập hoá đơn không đúng thời điểm tính theo số lượng hoá đơn vi phạm (từ 0,5 – 1,5 triệu đến 50 – 70 triệu đồng); phạt không lập hoá đơn; nguyên tắc xử phạt mới, thời hiệu 2 năm và cách phòng tránh.',
		'content' => $u . '
<p>[[tvpl:nd310]] (hiệu lực từ 16/01/2026) sửa đổi nhiều quy định xử phạt về hoá đơn tại Nghị định 125/2020. Thay đổi lớn nhất: phạt <strong>theo số lượng hoá đơn vi phạm</strong>, nên doanh nghiệp lập sai thời điểm hàng loạt có thể bị phạt rất nặng.</p>

<h2>1. Lập hoá đơn không đúng thời điểm</h2>
<p>Mức phạt với hành vi lập hoá đơn không đúng thời điểm khi bán hàng hoá, cung cấp dịch vụ (đối với tổ chức):</p>
<table>
<thead><tr><th>Số hoá đơn vi phạm</th><th>Mức phạt</th></tr></thead>
<tbody>
<tr><td>1 hoá đơn</td><td>0,5 – 1,5 triệu đồng</td></tr>
<tr><td>2 – 9 hoá đơn</td><td>2 – 5 triệu đồng</td></tr>
<tr><td>10 – 19 hoá đơn</td><td>5 – 15 triệu đồng</td></tr>
<tr><td>20 – 49 hoá đơn</td><td>15 – 30 triệu đồng</td></tr>
<tr><td>50 – 99 hoá đơn</td><td>30 – 50 triệu đồng</td></tr>
<tr><td>Từ 100 hoá đơn</td><td>50 – 70 triệu đồng</td></tr>
</tbody>
</table>
<p>Mức phạt với cá nhân bằng 1/2 mức phạt với tổ chức.</p>

<h2>2. Không lập hoá đơn khi bán hàng</h2>
<p>Không lập hoá đơn tổng hợp, không lập hoá đơn cho từng lần bán… bị phạt nặng hơn, có thể lên tới <strong>60 – 80 triệu đồng</strong> khi không lập từ 50 số hoá đơn trở lên. Ngoài phạt tiền, doanh nghiệp bị buộc lập hoá đơn và có thể bị truy thu thuế nếu hành vi làm thiếu số thuế phải nộp.</p>

<h2>3. Nguyên tắc xử phạt mới</h2>
<ul>
<li>Bổ sung định nghĩa <strong>bất khả kháng</strong> (thiên tai, dịch bệnh, hoả hoạn, sự cố bất ngờ…) – vi phạm do bất khả kháng không bị xử phạt.</li>
<li>Có từ 2 tình tiết tăng nặng trở lên thì áp dụng mức tối đa của khung phạt.</li>
<li>Thời hiệu xử phạt vi phạm về hoá đơn: <strong>2 năm</strong>.</li>
</ul>

<h2>4. Thời điểm lập hoá đơn đúng là khi nào?</h2>
<ul>
<li>Bán hàng hoá: thời điểm chuyển giao quyền sở hữu, quyền sử dụng, không phân biệt đã thu tiền hay chưa.</li>
<li>Cung cấp dịch vụ: thời điểm hoàn thành việc cung cấp dịch vụ (kể cả dịch vụ cho tổ chức, cá nhân nước ngoài), không phân biệt đã thu tiền hay chưa; thu tiền trước hoặc trong khi cung cấp dịch vụ thì lập hoá đơn khi thu tiền (trừ tiền đặt cọc, tạm ứng bảo đảm thực hiện hợp đồng).</li>
<li>Xây dựng, lắp đặt: thời điểm nghiệm thu, bàn giao từng hạng mục, khối lượng.</li>
</ul>
<p>Quy định chi tiết tại [[tvpl:nd123]] (sửa đổi bởi [[tvpl:nd70]]).</p>

<h2>5. Các vi phạm khác thường gặp</h2>
<ul>
<li>Lập hoá đơn không đầy đủ nội dung bắt buộc.</li>
<li>Không chuyển dữ liệu hoá đơn đúng hạn (với hoá đơn không mã, hoá đơn máy tính tiền).</li>
<li>Sử dụng hoá đơn không hợp pháp, hoá đơn của doanh nghiệp đã ngừng hoạt động.</li>
<li>Cho, bán hoá đơn.</li>
</ul>

<h2>6. Cách phòng tránh</h2>
<ul>
<li>Xây dựng quy trình: giao hàng/nghiệm thu xong là lập hoá đơn trong ngày.</li>
<li>Với dịch vụ dài hạn, thống nhất lịch nghiệm thu, xuất hoá đơn theo từng giai đoạn trong hợp đồng.</li>
<li>Đối chiếu hằng tháng giữa phiếu xuất kho, biên bản nghiệm thu và hoá đơn đã lập.</li>
</ul>
<p>Muốn rà soát hoá đơn trước khi bị kiểm tra? [[ra-soat-lam-lai-so-sach-ke-toan|Dịch vụ rà soát sổ sách]] kiểm tra thời điểm lập hoá đơn và xử lý sai sót còn trong thời hạn.</p>',
	),

	array(
		'slug'    => 'thanh-toan-khong-dung-tien-mat-tu-5-trieu-dong',
		'cat'     => array( 'hoa-don-chung-tu', 'thue-gtgt', 'thue-tndn' ),
		'title'   => 'Thanh toán không dùng tiền mặt từ 5 triệu đồng: điều kiện khấu trừ thuế và tính chi phí',
		'excerpt' => 'Ngưỡng 5 triệu đồng (thay 20 triệu) để được khấu trừ thuế GTGT và tính chi phí được trừ thuế TNDN, kể cả chi lương: chứng từ nào được chấp nhận, mua trả chậm, bù trừ công nợ, nhân viên mua hộ và những trường hợp không được chấp nhận.',
		'content' => $u . '
<p>Từ 01/7/2025, ngưỡng thanh toán không dùng tiền mặt để được khấu trừ thuế GTGT giảm từ 20 triệu xuống <strong>5 triệu đồng</strong> ([[tvpl:nd181]]). Ngưỡng tương tự áp dụng cho chi phí được trừ thuế TNDN theo [[tvpl:nd320]], kể cả <strong>chi lương</strong>. Đây là thay đổi ảnh hưởng đến gần như mọi doanh nghiệp nhỏ.</p>

<h2>1. Quy định chung</h2>
<ul>
<li>Hàng hoá, dịch vụ mua vào (kể cả nhập khẩu) có giá trị <strong>từ 5 triệu đồng trở lên</strong> (đã gồm thuế GTGT) phải có <strong>chứng từ thanh toán không dùng tiền mặt</strong> mới được khấu trừ thuế GTGT đầu vào.</li>
<li>Khoản chi từ 5 triệu đồng trở lên mỗi lần phải thanh toán không dùng tiền mặt mới được tính vào chi phí được trừ khi tính thuế TNDN.</li>
<li>Từ 15/12/2025, khoản chi tiền lương, tiền công từng lần từ 5 triệu đồng trở lên cho một người cũng phải chuyển khoản.</li>
</ul>

<h2>2. Chứng từ được chấp nhận</h2>
<ul>
<li>Chuyển khoản từ tài khoản của người mua sang tài khoản của người bán mở tại ngân hàng, tổ chức tín dụng.</li>
<li>Thanh toán qua thẻ, ví điện tử, các hình thức thanh toán điện tử khác theo quy định.</li>
<li>Bù trừ công nợ giữa giá trị hàng mua và hàng bán, vay mượn bằng hàng (có hợp đồng, biên bản đối chiếu).</li>
<li>Thanh toán uỷ quyền qua bên thứ ba bằng chuyển khoản (có văn bản uỷ quyền).</li>
</ul>

<h2>3. Không được chấp nhận</h2>
<ul>
<li>Trả tiền mặt cho khoản từ 5 triệu đồng.</li>
<li><strong>Nộp tiền mặt vào tài khoản của người bán</strong> – không được coi là thanh toán không dùng tiền mặt.</li>
<li>Chia nhỏ hoá đơn, chia nhỏ lần thanh toán để né ngưỡng với cùng một giao dịch.</li>
</ul>

<h2>4. Mua trả chậm, trả góp</h2>
<p>Hoá đơn mua chưa thanh toán vẫn được kê khai khấu trừ và ghi nhận chi phí nếu có hợp đồng ghi rõ thời hạn thanh toán. Khi đến hạn, phải có chứng từ chuyển khoản; nếu thanh toán bằng tiền mặt thì <strong>điều chỉnh giảm</strong> số thuế đã khấu trừ và chi phí đã ghi nhận ở kỳ thanh toán.</p>

<h2>5. Nhân viên mua hộ</h2>
<p>Nhân viên dùng thẻ cá nhân, chuyển khoản cá nhân mua hàng từ 5 triệu đồng cho công ty: được chấp nhận khi có quy chế tài chính hoặc quyết định uỷ quyền của công ty, hoá đơn ghi tên công ty, và công ty <strong>chuyển khoản hoàn tiền</strong> lại cho nhân viên.</p>

<h2>6. Chi lương</h2>
<ul>
<li>Lương, thưởng từng lần từ 5 triệu đồng/người phải chuyển khoản vào tài khoản của người lao động.</li>
<li>Lao động thời vụ không có tài khoản: nên yêu cầu mở tài khoản trước khi chi trả lớn.</li>
<li>Lưu bảng lương, hợp đồng lao động, chứng từ ngân hàng làm hồ sơ chứng minh.</li>
</ul>

<h2>7. Hướng xử lý nội bộ</h2>
<ul>
<li>Ban hành quy chế: mọi khoản chi từ 5 triệu đồng đều qua tài khoản công ty.</li>
<li>Kiểm tra tài khoản người bán trên hoá đơn/hợp đồng trước khi chuyển tiền.</li>
<li>Theo dõi hoá đơn mua trả chậm đến hạn thanh toán.</li>
</ul>
<p>Không chắc khoản chi nào đủ điều kiện? Bài Chi phí được trừ khi tính thuế TNDN trong chuyên mục Thuế TNDN phân tích thêm. Gói [[ke-toan-tron-goi|kế toán trọn gói]] kiểm tra chứng từ thanh toán hằng tháng để không bị loại khi quyết toán.</p>',
	),

	// ===================== BÁO CÁO THUẾ – TÀI CHÍNH =====================
	array(
		'slug'    => 'bao-cao-tai-chinh-nam-gom-nhung-gi-nop-o-dau',
		'cat'     => array( 'bao-cao-thue-tai-chinh' ),
		'title'   => 'Báo cáo tài chính năm gồm những gì, nộp ở đâu, hạn nộp năm 2026?',
		'excerpt' => 'Bộ báo cáo tài chính năm theo Thông tư 99/2025 và Thông tư 133/2016; doanh nghiệp siêu nhỏ theo Thông tư 58/2026; hạn nộp chậm nhất 90 ngày sau khi kết thúc năm tài chính, nơi nộp và cách nộp trên Cổng dịch vụ công.',
		'content' => $u . '
<p>Báo cáo tài chính (BCTC) năm là “bức tranh” tài chính của doanh nghiệp, đồng thời là hồ sơ bắt buộc nộp cùng quyết toán thuế TNDN. Từ năm tài chính 2026, chế độ báo cáo có nhiều thay đổi theo [[tvpl:tt99]] và [[tvpl:tt58]].</p>

<h2>1. Bộ BCTC năm gồm những gì?</h2>
<h3>Doanh nghiệp áp dụng Thông tư 99/2025</h3>
<ul>
<li>Báo cáo tình hình tài chính (trước đây gọi là Bảng cân đối kế toán) – mẫu B01-DN.</li>
<li>Báo cáo kết quả hoạt động kinh doanh – B02-DN.</li>
<li>Báo cáo lưu chuyển tiền tệ – B03-DN.</li>
<li>Thuyết minh báo cáo tài chính – B09-DN.</li>
</ul>
<h3>Doanh nghiệp nhỏ và vừa áp dụng Thông tư 133/2016</h3>
<ul>
<li>Báo cáo tình hình tài chính, Báo cáo kết quả hoạt động kinh doanh, Thuyết minh báo cáo tài chính (bắt buộc).</li>
<li>Báo cáo lưu chuyển tiền tệ (khuyến khích lập) – theo [[tvpl:tt133]].</li>
</ul>
<h3>Doanh nghiệp siêu nhỏ</h3>
<p>Áp dụng [[tvpl:tt58]] từ 01/7/2026 (thay Thông tư 132/2018). Doanh nghiệp siêu nhỏ nộp thuế TNDN theo tỷ lệ % trên doanh thu không bắt buộc lập BCTC để nộp cơ quan nhà nước.</p>

<h2>2. Hạn nộp</h2>
<p>Chậm nhất <strong>90 ngày</strong> kể từ ngày kết thúc năm tài chính – với năm tài chính theo năm dương lịch là <strong>31/3</strong> năm sau, trùng hạn quyết toán thuế TNDN. Doanh nghiệp nhà nước, công ty đại chúng, doanh nghiệp có vốn nước ngoài phải kiểm toán có thời hạn và nơi nộp theo quy định riêng.</p>

<h2>3. Nộp ở đâu?</h2>
<ul>
<li><strong>Cơ quan thuế</strong>: nộp kèm hồ sơ quyết toán thuế TNDN qua cổng thuế điện tử.</li>
<li><strong>Cơ quan thống kê</strong>: theo quy định về báo cáo thống kê; nhiều địa phương đã liên thông qua cổng thuế.</li>
<li><strong>Cơ quan đăng ký kinh doanh</strong>: với một số loại hình theo quy định.</li>
<li>Doanh nghiệp FDI: nộp BCTC đã kiểm toán cho cơ quan thuế, thống kê và cơ quan quản lý đầu tư.</li>
</ul>

<h2>4. Cách nộp</h2>
<ol>
<li>Lập BCTC trên phần mềm kế toán hoặc phần mềm hỗ trợ kê khai, kết xuất tệp XML.</li>
<li>Đăng nhập Cổng thông tin của Cục Thuế/Cổng dịch vụ công, chọn nộp BCTC.</li>
<li>Nộp đồng thời báo cáo và thuyết minh (không nộp tách rời).</li>
<li>Ký số, nộp và lưu thông báo chấp nhận.</li>
</ol>

<h2>5. Chậm nộp, nộp sai</h2>
<ul>
<li>Chậm nộp BCTC kèm quyết toán thuế bị phạt như chậm nộp hồ sơ khai thuế theo [[tvpl:nd310]].</li>
<li>Phát hiện sai sau khi nộp: nộp bổ sung/nộp lại BCTC, khai bổ sung quyết toán thuế nếu ảnh hưởng số thuế.</li>
</ul>

<h2>6. Chuẩn bị trước khi lập BCTC</h2>
<ul>
<li>Kiểm kê, đối chiếu số dư tiền, công nợ, hàng tồn kho, tài sản cố định.</li>
<li>Trích khấu hao, phân bổ chi phí, kết chuyển doanh thu – chi phí.</li>
<li>Đối chiếu số liệu với tờ khai thuế GTGT, TNCN trong năm.</li>
<li>Năm 2026 là năm đầu áp dụng Thông tư 99: trình bày lại số liệu so sánh năm 2025 theo mẫu mới.</li>
</ul>
<p>Cần lập BCTC đúng chế độ mới? [[bao-cao-tai-chinh|Dịch vụ lập báo cáo tài chính]] và [[quyet-toan-thue-cuoi-nam|quyết toán thuế cuối năm]] làm trọn gói, kể cả chuyển đổi sang Thông tư 99.</p>',
	),

	array(
		'slug'    => 'muc-phat-cham-nop-to-khai-thue-2026',
		'cat'     => array( 'bao-cao-thue-tai-chinh' ),
		'title'   => 'Mức phạt chậm nộp tờ khai thuế năm 2026 và cách tính tiền chậm nộp thuế',
		'excerpt' => 'Khung phạt chậm nộp hồ sơ khai thuế theo Điều 13 Nghị định 125/2020 sửa đổi bởi Nghị định 310/2025 (từ cảnh cáo đến 25 triệu đồng), mức phạt với cá nhân bằng 1/2, tiền chậm nộp thuế 0,03%/ngày và cách giảm thiểu khi đã lỡ chậm.',
		'content' => $u . '
<p>Chậm nộp tờ khai thuế là vi phạm phổ biến nhất của doanh nghiệp nhỏ, đặc biệt trong những tháng đầu sau khi thành lập. Mức phạt hiện hành theo Điều 13 Nghị định 125/2020/NĐ-CP, được sửa đổi bởi [[tvpl:nd310]] từ 16/01/2026.</p>

<h2>1. Khung phạt chậm nộp hồ sơ khai thuế (tổ chức)</h2>
<table>
<thead><tr><th>Thời gian chậm</th><th>Mức phạt</th></tr></thead>
<tbody>
<tr><td>1 – 5 ngày, có tình tiết giảm nhẹ</td><td>Cảnh cáo</td></tr>
<tr><td>1 – 30 ngày</td><td>2 – 5 triệu đồng</td></tr>
<tr><td>31 – 60 ngày</td><td>5 – 8 triệu đồng</td></tr>
<tr><td>61 – 90 ngày</td><td>8 – 15 triệu đồng</td></tr>
<tr><td>Trên 90 ngày, có phát sinh số thuế phải nộp (và các trường hợp theo quy định)</td><td>15 – 25 triệu đồng</td></tr>
</tbody>
</table>
<p>Mức phạt với <strong>cá nhân</strong> (hộ kinh doanh, cá nhân kinh doanh) bằng <strong>1/2</strong> mức phạt với tổ chức.</p>

<h2>2. Tiền chậm nộp thuế</h2>
<p>Ngoài tiền phạt hành chính, nếu nộp tiền thuế sau hạn, doanh nghiệp phải trả <strong>tiền chậm nộp</strong> theo tỷ lệ <strong>0,03%/ngày</strong> trên số tiền thuế chậm nộp, tính từ ngày tiếp theo ngày hết hạn đến ngày nộp vào ngân sách, theo [[tvpl:lqlt2025]].</p>
<p><em>Ví dụ:</em> số thuế GTGT quý 100 triệu đồng nộp chậm 20 ngày → tiền chậm nộp = 100.000.000 × 0,03% × 20 = 600.000 đồng.</p>

<h2>3. Trường hợp không bị phạt</h2>
<ul>
<li>Chậm nộp do <strong>bất khả kháng</strong> (thiên tai, hoả hoạn, dịch bệnh, sự cố bất ngờ…) và được gia hạn nộp hồ sơ.</li>
<li>Tờ khai không phát sinh nghĩa vụ thuế trong một số trường hợp theo quy định.</li>
<li>Không phát sinh trả thu nhập thì không phải nộp tờ khai quyết toán thuế TNCN.</li>
</ul>

<h2>4. Lỡ chậm thì làm gì?</h2>
<ol>
<li>Nộp tờ khai càng sớm càng tốt – mức phạt tăng theo số ngày chậm.</li>
<li>Nộp ngay tiền thuế (nếu có) để dừng tính tiền chậm nộp.</li>
<li>Khi nhận biên bản, kiểm tra tình tiết giảm nhẹ (vi phạm lần đầu, tự nguyện khắc phục…).</li>
<li>Theo dõi quyết định xử phạt và nộp phạt đúng hạn.</li>
</ol>

<h2>5. Phòng tránh</h2>
<ul>
<li>Lập lịch nộp tờ khai cả năm, đặt nhắc trước 5 ngày.</li>
<li>Doanh nghiệp mới thành lập: nộp tờ khai ngay từ kỳ đầu tiên, kể cả không có doanh thu.</li>
<li>Kiểm tra thông báo chấp nhận tờ khai sau khi nộp.</li>
</ul>
<p>Lịch nộp chi tiết xem bài Lịch nộp tờ khai thuế năm 2026 trong chuyên mục này. Không muốn lo hạn nộp? [[bao-cao-thue-hang-thang-quy|Dịch vụ báo cáo thuế hằng tháng, quý]] theo dõi và nộp thay.</p>',
	),

	array(
		'slug'    => 'khai-bo-sung-ho-so-khai-thue',
		'cat'     => array( 'bao-cao-thue-tai-chinh' ),
		'title'   => 'Khai bổ sung hồ sơ khai thuế: khi nào được khai, thời hạn 5 năm và cách tính tiền chậm nộp',
		'excerpt' => 'Quy định khai bổ sung hồ sơ khai thuế theo Luật Quản lý thuế 2025: thời hạn 5 năm, trường hợp khai bổ sung làm tăng, giảm số thuế, khai bổ sung sau khi có quyết định kiểm tra, hồ sơ và ví dụ tính tiền chậm nộp.',
		'content' => $u . '
<p>Phát hiện sai sót trên tờ khai đã nộp – quên một hoá đơn đầu ra, kê khai trùng hoá đơn đầu vào, áp sai thuế suất – doanh nghiệp được <strong>khai bổ sung</strong> để sửa. Tự phát hiện và khai bổ sung luôn có lợi hơn để cơ quan thuế phát hiện.</p>

<h2>1. Thời hạn khai bổ sung</h2>
<p>Theo [[tvpl:lqlt2025]], người nộp thuế được khai bổ sung trong <strong>5 năm</strong> kể từ ngày hết thời hạn nộp hồ sơ khai thuế của kỳ có sai sót (trước đây là 10 năm), nhưng <strong>trước khi</strong> cơ quan thuế công bố quyết định thanh tra, kiểm tra tại trụ sở.</p>

<h2>2. Sau khi có quyết định kiểm tra, thanh tra</h2>
<ul>
<li>Vẫn được khai bổ sung đối với sai sót <strong>làm tăng</strong> số thuế phải nộp, giảm số thuế được khấu trừ/hoàn – và bị xử phạt theo quy định.</li>
<li>Sai sót làm giảm số thuế phải nộp: được điều chỉnh qua kết luận kiểm tra, thanh tra.</li>
</ul>

<h2>3. Khai bổ sung làm tăng số thuế phải nộp</h2>
<ul>
<li>Nộp số thuế tăng thêm và <strong>tiền chậm nộp</strong> 0,03%/ngày tính từ ngày hết hạn nộp thuế của kỳ sai sót.</li>
<li>Tự phát hiện và khai bổ sung trước khi có quyết định kiểm tra: không bị phạt về hành vi khai sai (nhưng vẫn tính tiền chậm nộp).</li>
</ul>
<p><em>Ví dụ:</em> tờ khai GTGT quý I/2026 (hạn 30/4/2026) bỏ sót hoá đơn đầu ra, thuế tăng thêm 8 triệu đồng. Khai bổ sung và nộp tiền ngày 30/6/2026 (61 ngày chậm) → tiền chậm nộp = 8.000.000 × 0,03% × 61 = 146.400 đồng.</p>

<h2>4. Khai bổ sung làm giảm số thuế</h2>
<ul>
<li>Số thuế nộp thừa được bù trừ vào kỳ sau hoặc đề nghị hoàn.</li>
<li>Với thuế GTGT: khai bổ sung tăng thuế đầu vào được khấu trừ khi đủ điều kiện.</li>
</ul>

<h2>5. Hồ sơ khai bổ sung</h2>
<ul>
<li>Tờ khai bổ sung (chọn “khai bổ sung” cho kỳ có sai sót trên phần mềm).</li>
<li>Bản giải trình khai bổ sung, điều chỉnh (với một số loại tờ khai).</li>
<li>Tài liệu liên quan (hoá đơn, chứng từ).</li>
</ul>
<p>Thủ tục hướng dẫn tại [[tvpl:nd252]] và [[tvpl:tt89]].</p>

<h2>6. Phân biệt với hoá đơn điều chỉnh</h2>
<p>Hoá đơn điều chỉnh, thay thế lập ở kỳ sau được khai vào <strong>kỳ lập hoá đơn điều chỉnh</strong>, không phải khai bổ sung kỳ cũ. Khai bổ sung dùng khi tờ khai sai do kế toán (bỏ sót, kê trùng, áp sai thuế suất trên tờ khai…).</p>

<h2>7. Lưu ý</h2>
<ul>
<li>Khai bổ sung tờ khai quyết toán năm thì không cần khai bổ sung tờ khai tạm tính các quý trong năm đó (trừ trường hợp theo quy định).</li>
<li>Khai bổ sung nhiều lần, số lớn có thể khiến cơ quan thuế đưa vào diện rủi ro – nên rà soát kỹ trước khi nộp lần đầu.</li>
</ul>
<p>Phát hiện sai sót nhiều kỳ? [[ra-soat-lam-lai-so-sach-ke-toan|Dịch vụ rà soát, làm lại sổ sách]] tổng hợp và khai bổ sung một lần cho gọn.</p>',
	),

	array(
		'slug'    => 'doanh-nghiep-chua-phat-sinh-doanh-thu-can-nop-gi',
		'cat'     => array( 'bao-cao-thue-tai-chinh', 'thu-tuc-thanh-lap' ),
		'title'   => 'Doanh nghiệp chưa phát sinh doanh thu có phải nộp tờ khai thuế, báo cáo tài chính không?',
		'excerpt' => 'Doanh nghiệp mới thành lập hoặc không có hoạt động mua bán vẫn phải nộp tờ khai thuế GTGT (tờ khai không phát sinh), báo cáo tài chính và quyết toán thuế TNDN; các trường hợp không phải nộp tờ khai TNCN; rủi ro khi bỏ trống kê khai.',
		'content' => $u . '
<p>“Chưa có doanh thu thì chưa cần khai thuế” là hiểu lầm khiến rất nhiều doanh nghiệp mới bị phạt ngay năm đầu. Thực tế, nghĩa vụ kê khai phát sinh từ khi được cấp mã số thuế, không phụ thuộc có doanh thu hay không.</p>

<h2>1. Tờ khai thuế GTGT: vẫn phải nộp</h2>
<ul>
<li>Doanh nghiệp nộp thuế GTGT theo phương pháp khấu trừ vẫn phải nộp tờ khai tháng/quý dù <strong>không phát sinh</strong> hoá đơn đầu ra, đầu vào – đánh dấu chỉ tiêu “không phát sinh hoạt động mua, bán trong kỳ”.</li>
<li>Không nộp bị phạt chậm nộp hồ sơ khai thuế theo [[tvpl:nd310]].</li>
<li>Doanh nghiệp chỉ có hoạt động không chịu thuế GTGT, doanh nghiệp chế xuất chỉ có hoạt động chế xuất: không phải nộp tờ khai GTGT theo [[tvpl:nd252]].</li>
</ul>

<h2>2. Thuế TNCN</h2>
<ul>
<li>Không phát sinh khấu trừ thuế TNCN trong kỳ thì <strong>không phải nộp</strong> tờ khai khấu trừ TNCN tháng/quý.</li>
<li>Không phát sinh trả thu nhập từ tiền lương, tiền công trong năm thì không phải nộp tờ khai quyết toán TNCN.</li>
<li>Có trả lương (kể cả lương thấp chưa đến mức khấu trừ) thì vẫn nộp quyết toán TNCN năm.</li>
</ul>

<h2>3. Thuế TNDN và báo cáo tài chính</h2>
<ul>
<li>Không tạm nộp TNDN nếu không có lãi.</li>
<li>Cuối năm vẫn phải lập <strong>báo cáo tài chính</strong> và <strong>tờ khai quyết toán thuế TNDN</strong> – kể cả khi lỗ hoặc chưa có doanh thu (chi phí thành lập, thuê văn phòng, lương… vẫn phải hạch toán).</li>
<li>Khoản lỗ năm đầu được chuyển sang các năm sau (tối đa 5 năm) – chỉ khi đã quyết toán đầy đủ.</li>
</ul>

<h2>4. Lệ phí môn bài</h2>
<p>Từ năm 2026 không còn phải khai, nộp lệ phí môn bài theo [[tvpl:nq198|Nghị quyết 198/2025/QH15]].</p>

<h2>5. Vẫn phải giữ sổ sách</h2>
<p>Doanh nghiệp phải tổ chức kế toán từ ngày thành lập theo [[tvpl:lkt]]: ghi nhận vốn góp, chi phí thành lập, mua sắm ban đầu. Đây là căn cứ để khấu trừ thuế GTGT đầu vào và tính chi phí khi bắt đầu có doanh thu.</p>

<h2>6. Rủi ro khi bỏ trống</h2>
<ul>
<li>Bị phạt chậm nộp từng tờ khai – nhiều kỳ cộng lại thành khoản đáng kể.</li>
<li>Bị cơ quan thuế xác minh, có thể chuyển trạng thái “không hoạt động tại địa chỉ đăng ký”.</li>
<li>Mất quyền khấu trừ thuế đầu vào, mất số lỗ được chuyển do không có sổ sách.</li>
</ul>

<h2>Lịch tối thiểu cho doanh nghiệp chưa có doanh thu</h2>
<table>
<thead><tr><th>Thời điểm</th><th>Việc cần làm</th></tr></thead>
<tbody>
<tr><td>Cuối tháng đầu quý sau (khai quý)</td><td>Tờ khai GTGT không phát sinh</td></tr>
<tr><td>31/3 năm sau</td><td>Báo cáo tài chính, quyết toán TNDN (và TNCN nếu có trả lương)</td></tr>
</tbody>
</table>
<p>Mới thành lập và chưa có kế toán? Gói [[ke-toan-tron-goi|kế toán trọn gói]] có mức phí riêng cho doanh nghiệp chưa phát sinh doanh thu.</p>',
	),

	// ===================== SỔ SÁCH KẾ TOÁN =====================
	array(
		'slug'    => 'so-ke-toan-doanh-nghiep-nho-gom-nhung-gi',
		'cat'     => array( 'so-sach-ke-toan' ),
		'title'   => 'Doanh nghiệp nhỏ cần những sổ kế toán nào? Hình thức ghi sổ và cách in, ký, lưu sổ',
		'excerpt' => 'Hệ thống sổ kế toán cần có theo Luật Kế toán và chế độ kế toán (Thông tư 133/2016, Thông tư 99/2025): sổ tổng hợp, sổ chi tiết, hình thức Nhật ký chung, sổ kế toán trên phần mềm, khoá sổ, in và ký sổ cuối năm.',
		'content' => $u . '
<p>Sổ kế toán là nơi ghi chép, hệ thống hoá toàn bộ nghiệp vụ kinh tế của doanh nghiệp. Thiếu sổ hoặc ghi sổ sai là lý do khiến chi phí bị loại, số liệu báo cáo không giải trình được khi kiểm tra thuế.</p>

<h2>1. Quy định chung</h2>
<p>Theo [[tvpl:lkt]], mỗi đơn vị kế toán chỉ có <strong>một hệ thống sổ kế toán</strong> cho một kỳ kế toán năm, gồm sổ tổng hợp và sổ chi tiết. Sổ phải được mở từ đầu kỳ kế toán (hoặc ngày thành lập), ghi chép đầy đủ, kịp thời, khoá sổ cuối kỳ.</p>

<h2>2. Sổ tổng hợp</h2>
<ul>
<li><strong>Sổ Nhật ký chung</strong>: ghi tất cả nghiệp vụ theo thứ tự thời gian.</li>
<li><strong>Sổ Cái</strong>: tổng hợp theo từng tài khoản kế toán.</li>
</ul>
<p>Hình thức Nhật ký chung là hình thức phổ biến nhất ở doanh nghiệp nhỏ vì phù hợp với phần mềm kế toán.</p>

<h2>3. Sổ chi tiết thường dùng</h2>
<ul>
<li>Sổ quỹ tiền mặt, sổ tiền gửi ngân hàng.</li>
<li>Sổ chi tiết công nợ phải thu, phải trả theo từng khách hàng, nhà cung cấp.</li>
<li>Sổ chi tiết vật tư, hàng hoá; thẻ kho; bảng tổng hợp nhập – xuất – tồn.</li>
<li>Sổ tài sản cố định, bảng tính khấu hao; sổ theo dõi công cụ dụng cụ, chi phí trả trước.</li>
<li>Sổ chi tiết doanh thu, giá vốn, chi phí theo hoạt động.</li>
<li>Sổ theo dõi thuế GTGT, sổ chi tiết lương và các khoản trích theo lương.</li>
</ul>

<h2>4. Theo chế độ kế toán nào?</h2>
<ul>
<li><strong>Thông tư 133/2016</strong> (doanh nghiệp nhỏ và vừa): có danh mục mẫu sổ gợi ý; doanh nghiệp được bổ sung, sửa đổi phù hợp – [[tvpl:tt133]].</li>
<li><strong>Thông tư 99/2025</strong>: doanh nghiệp tự thiết kế mẫu sổ, phải ban hành quy chế hạch toán kế toán khi thiết kế, sửa đổi – [[tvpl:tt99]].</li>
<li><strong>Doanh nghiệp siêu nhỏ</strong>: chế độ đơn giản theo [[tvpl:tt58]].</li>
</ul>

<h2>5. Sổ kế toán trên phần mềm</h2>
<ul>
<li>Phần mềm kế toán phải đáp ứng tiêu chuẩn, điều kiện theo quy định; sổ in từ phần mềm có giá trị như sổ ghi tay.</li>
<li>Cuối kỳ kế toán năm, doanh nghiệp <strong>in sổ, ký</strong> (người ghi sổ, kế toán trưởng, người đại diện theo pháp luật) và đóng thành quyển – hoặc lưu sổ điện tử có chữ ký số theo quy định về lưu trữ điện tử.</li>
<li>Sao lưu dữ liệu định kỳ, tách bản sao lưu khỏi máy làm việc.</li>
</ul>

<h2>6. Sửa chữa sổ</h2>
<p>Sổ trên phần mềm: sửa bằng bút toán điều chỉnh, ghi chú; không xoá nghiệp vụ đã khoá sổ. Sổ ghi tay: sửa theo phương pháp cải chính, ghi số âm hoặc ghi bổ sung, có chữ ký người sửa.</p>

<h2>7. Lỗi thường gặp</h2>
<ul>
<li>Không có sổ chi tiết công nợ, kho – không đối chiếu được khi kiểm tra.</li>
<li>Số dư tiền mặt âm.</li>
<li>Sổ không khớp tờ khai thuế, báo cáo tài chính.</li>
<li>Không in, ký sổ cuối năm.</li>
</ul>
<p>Sổ sách năm cũ thiếu, sai? [[ra-soat-lam-lai-so-sach-ke-toan|Dịch vụ rà soát, làm lại sổ sách]] hoàn thiện trước kỳ quyết toán. Người học muốn thực hành ghi sổ trên phần mềm có thể tham gia [[khoa-hoc-so-sach-ke-toan|khoá học sổ sách kế toán]].</p>',
	),

	array(
		'slug'    => 'thoi-han-luu-tru-chung-tu-so-sach-ke-toan',
		'cat'     => array( 'so-sach-ke-toan', 'hoa-don-chung-tu' ),
		'title'   => 'Thời hạn lưu trữ chứng từ, sổ sách kế toán: 5 năm, 10 năm hay vĩnh viễn?',
		'excerpt' => 'Thời hạn lưu trữ tài liệu kế toán theo Luật Kế toán và Nghị định 174/2016: tối thiểu 5 năm, 10 năm, vĩnh viễn; thời điểm tính thời hạn, lưu trữ hoá đơn, chứng từ điện tử và hộ kinh doanh lưu sổ tối thiểu 5 năm.',
		'content' => $u . '
<p>Cơ quan thuế được khai bổ sung, kiểm tra trong nhiều năm; ngân hàng, đối tác, toà án có thể yêu cầu chứng từ cũ. Lưu trữ tài liệu kế toán đúng thời hạn vừa là nghĩa vụ, vừa bảo vệ doanh nghiệp khi có tranh chấp.</p>

<h2>1. Thời hạn lưu trữ</h2>
<p>Theo [[tvpl:lkt]] và Nghị định 174/2016/NĐ-CP:</p>
<table>
<thead><tr><th>Thời hạn tối thiểu</th><th>Tài liệu</th></tr></thead>
<tbody>
<tr><td>5 năm</td><td>Chứng từ kế toán không dùng trực tiếp để ghi sổ và lập báo cáo tài chính: phiếu thu, phiếu chi, phiếu nhập kho, phiếu xuất kho không lưu trong tập tài liệu kế toán của bộ phận kế toán; tài liệu dùng cho quản lý, điều hành</td></tr>
<tr><td>10 năm</td><td>Chứng từ kế toán dùng trực tiếp để ghi sổ, lập báo cáo tài chính; sổ kế toán; báo cáo tài chính năm; hồ sơ, báo cáo quyết toán, kiểm toán; tài liệu liên quan đến thanh lý, nhượng bán tài sản cố định; tài liệu về giải thể, phá sản, chuyển đổi hình thức sở hữu</td></tr>
<tr><td>Vĩnh viễn</td><td>Tài liệu kế toán có tính sử liệu, có ý nghĩa quan trọng về kinh tế, an ninh, quốc phòng</td></tr>
</tbody>
</table>

<h2>2. Khi nào bắt đầu tính?</h2>
<ul>
<li>Thời hạn lưu trữ tính từ <strong>ngày kết thúc kỳ kế toán năm</strong>.</li>
<li>Tài liệu kế toán phải được đưa vào lưu trữ trong <strong>12 tháng</strong> kể từ ngày kết thúc kỳ kế toán năm hoặc kết thúc công việc kế toán.</li>
</ul>

<h2>3. Hoá đơn, chứng từ điện tử</h2>
<ul>
<li>Hoá đơn điện tử, chứng từ điện tử lưu trữ bằng phương tiện điện tử, bảo đảm toàn vẹn, truy xuất được, theo đúng thời hạn như tài liệu giấy – [[tvpl:nd123]].</li>
<li>Không nên chỉ phụ thuộc vào kho lưu trữ của nhà cung cấp hoá đơn – định kỳ tải về tệp XML và bản thể hiện PDF.</li>
<li>Báo cáo tài chính, tờ khai thuế điện tử: lưu cùng thông báo chấp nhận của cơ quan thuế.</li>
</ul>

<h2>4. Hộ kinh doanh</h2>
<p>Hộ, cá nhân kinh doanh được chọn lưu tài liệu kế toán bản giấy hoặc điện tử, thời hạn tối thiểu <strong>5 năm</strong> theo [[tvpl:tt152]].</p>

<h2>5. Tổ chức lưu trữ</h2>
<ul>
<li>Sắp xếp theo năm, theo loại (chứng từ thu – chi, ngân hàng, mua – bán, lương, tài sản).</li>
<li>Đóng tập chứng từ kèm bảng kê; ghi nhãn rõ ràng.</li>
<li>Bảo quản nơi khô ráo, có biện pháp phòng cháy; dữ liệu điện tử sao lưu ở hai nơi.</li>
<li>Lập danh mục tài liệu lưu trữ; ghi sổ theo dõi khi cho mượn, khai thác.</li>
</ul>

<h2>6. Mất, huỷ tài liệu</h2>
<ul>
<li>Tài liệu bị mất, hư hỏng: lập biên bản, kiểm kê, phục hồi (sao lại từ đối tác, ngân hàng, cơ quan thuế).</li>
<li>Huỷ tài liệu hết thời hạn lưu trữ: thành lập hội đồng, lập biên bản huỷ theo quy định – không tự ý vứt bỏ.</li>
</ul>
<p>Cần sắp xếp lại chứng từ nhiều năm trước kỳ kiểm tra thuế? [[ra-soat-lam-lai-so-sach-ke-toan|Dịch vụ rà soát, làm lại sổ sách]] hỗ trợ hệ thống hoá và lập danh mục lưu trữ.</p>',
	),

	array(
		'slug'    => 'ke-toan-truong-doanh-nghiep-nho-va-sieu-nho',
		'cat'     => array( 'so-sach-ke-toan' ),
		'title'   => 'Doanh nghiệp có bắt buộc có kế toán trưởng? Quy định mới cho doanh nghiệp siêu nhỏ từ 01/7/2026',
		'excerpt' => 'Ai được làm kế toán trưởng, tiêu chuẩn, người không được làm kế toán; doanh nghiệp siêu nhỏ không bắt buộc bố trí kế toán trưởng và được giao người thân làm kế toán theo Thông tư 58/2026; thuê dịch vụ kế toán trưởng.',
		'content' => $u . '
<p>Chủ doanh nghiệp nhỏ thường băn khoăn: có bắt buộc phải tuyển kế toán trưởng không, có được để người nhà làm kế toán không? Từ 01/7/2026, [[tvpl:tt58]] đã nới lỏng đáng kể cho doanh nghiệp siêu nhỏ.</p>

<h2>1. Quy định chung về kế toán trưởng</h2>
<p>Theo [[tvpl:lkt]], đơn vị kế toán phải bố trí kế toán trưởng (hoặc người phụ trách kế toán) để tổ chức thực hiện công tác kế toán. Kế toán trưởng phải có:</p>
<ul>
<li>Phẩm chất đạo đức, hiểu biết pháp luật, trình độ chuyên môn về kế toán (trình độ trung cấp, cao đẳng, đại học tuỳ loại đơn vị).</li>
<li><strong>Chứng chỉ bồi dưỡng kế toán trưởng</strong>.</li>
<li>Thời gian công tác thực tế về kế toán theo quy định (ví dụ tối thiểu 2 năm với trình độ đại học, 3 năm với trình độ trung cấp, cao đẳng).</li>
</ul>

<h2>2. Ai không được làm kế toán?</h2>
<ul>
<li>Người chưa thành niên, người bị hạn chế hoặc mất năng lực hành vi dân sự.</li>
<li>Người đang bị truy cứu trách nhiệm hình sự, đang chấp hành án, bị cấm hành nghề kế toán.</li>
<li>Bố mẹ, vợ chồng, con, anh chị em ruột của người đại diện theo pháp luật, giám đốc, kế toán trưởng, thủ quỹ, thủ kho trong cùng đơn vị (trừ trường hợp được phép như doanh nghiệp siêu nhỏ dưới đây).</li>
</ul>

<h2>3. Doanh nghiệp siêu nhỏ: quy định mới</h2>
<p>Theo Thông tư 58/2026/TT-BTC (thay Thông tư 132/2018) từ 01/7/2026:</p>
<ul>
<li><strong>Không bắt buộc bố trí kế toán trưởng</strong>. Nếu bố trí người phụ trách kế toán, người này được ký thay kế toán trưởng trên chứng từ, sổ, báo cáo tài chính.</li>
<li>Được bố trí <strong>người thân</strong> (cha mẹ đẻ, cha mẹ nuôi, vợ chồng, con đẻ, con nuôi, anh chị em ruột của người đại diện theo pháp luật, người đứng đầu, giám đốc…) làm kế toán.</li>
<li>Được <strong>thuê dịch vụ làm kế toán</strong> hoặc dịch vụ làm kế toán trưởng.</li>
</ul>
<p>Doanh nghiệp siêu nhỏ: không quá 10 lao động đóng bảo hiểm xã hội bình quân năm và doanh thu năm không quá 3 tỷ đồng (nông, lâm, thuỷ sản, công nghiệp, xây dựng) hoặc 10 tỷ đồng (thương mại, dịch vụ), hoặc tổng nguồn vốn không quá 3 tỷ đồng.</p>

<h2>4. Doanh nghiệp nhỏ và vừa</h2>
<ul>
<li>Có thể bố trí kế toán trưởng là nhân viên, hoặc <strong>thuê dịch vụ kế toán trưởng</strong> từ doanh nghiệp dịch vụ kế toán đủ điều kiện, hoặc cá nhân có chứng chỉ hành nghề kế toán đăng ký hành nghề.</li>
<li>Người làm dịch vụ kế toán trưởng phải đáp ứng tiêu chuẩn kế toán trưởng.</li>
</ul>

<h2>5. Nên tuyển hay thuê dịch vụ?</h2>
<table>
<thead><tr><th>Tiêu chí</th><th>Tuyển kế toán trưởng</th><th>Thuê dịch vụ</th></tr></thead>
<tbody>
<tr><td>Chi phí</td><td>Lương, bảo hiểm, phúc lợi cố định</td><td>Phí dịch vụ theo khối lượng công việc</td></tr>
<tr><td>Chuyên môn</td><td>Phụ thuộc một người</td><td>Đội ngũ, cập nhật quy định liên tục</td></tr>
<tr><td>Phù hợp</td><td>Doanh nghiệp nhiều nghiệp vụ, cần người tại chỗ</td><td>Doanh nghiệp nhỏ, siêu nhỏ, mới thành lập</td></tr>
</tbody>
</table>
<p>Doanh nghiệp nhỏ có thể kết hợp: nhân viên nội bộ thu thập chứng từ, dịch vụ bên ngoài lo hạch toán, báo cáo. Xem [[ke-toan-tron-goi|dịch vụ kế toán trọn gói]] hoặc [[ke-toan-noi-bo|dịch vụ kế toán nội bộ]].</p>',
	),

	array(
		'slug'    => 'hach-toan-tien-luong-va-cac-khoan-trich-theo-luong',
		'cat'     => array( 'so-sach-ke-toan', 'bao-hiem-xa-hoi' ),
		'title'   => 'Hạch toán tiền lương và các khoản trích theo lương năm 2026 (Thông tư 133)',
		'excerpt' => 'Cách hạch toán tiền lương, BHXH, BHYT, BHTN, kinh phí công đoàn, thuế TNCN theo Thông tư 133/2016: tỷ lệ trích 2026, các bút toán từ tính lương đến chi trả, nộp bảo hiểm và ví dụ bảng lương một nhân viên.',
		'content' => $u . '
<p>Tiền lương là khoản chi phí lớn và được cơ quan thuế kiểm tra kỹ nhất. Hạch toán đúng giúp chi phí lương được trừ khi tính thuế TNDN và số liệu khớp với tờ khai bảo hiểm, thuế TNCN.</p>

<h2>1. Tài khoản sử dụng (Thông tư 133/2016)</h2>
<ul>
<li><strong>334</strong> – Phải trả người lao động.</li>
<li><strong>338</strong> – Phải trả, phải nộp khác: 3382 (kinh phí công đoàn), 3383 (BHXH), 3384 (BHYT), 3385 (BHTN).</li>
<li><strong>3335</strong> – Thuế thu nhập cá nhân.</li>
<li>Tài khoản chi phí: 154 (sản xuất), 6421 (chi phí bán hàng), 6422 (chi phí quản lý doanh nghiệp).</li>
</ul>
<p>Danh mục tài khoản theo [[tvpl:tt133]]; doanh nghiệp áp dụng Thông tư 99/2025 dùng tài khoản tương ứng theo chế độ mới.</p>

<h2>2. Tỷ lệ trích năm 2026</h2>
<table>
<thead><tr><th>Khoản</th><th>Doanh nghiệp</th><th>Người lao động</th></tr></thead>
<tbody>
<tr><td>BHXH (hưu trí – tử tuất, ốm đau – thai sản, tai nạn lao động – bệnh nghề nghiệp)</td><td>17,5%</td><td>8%</td></tr>
<tr><td>BHYT</td><td>3%</td><td>1,5%</td></tr>
<tr><td>BHTN</td><td>1%</td><td>1%</td></tr>
<tr><td>Kinh phí công đoàn</td><td>2%</td><td>–</td></tr>
<tr><td><strong>Cộng</strong></td><td><strong>23,5%</strong></td><td><strong>10,5%</strong></td></tr>
</tbody>
</table>
<p>Tỷ lệ bảo hiểm theo [[tvpl:lbhxh]]; mức lương đóng không thấp hơn lương tối thiểu vùng ([[tvpl:nd293]]) và không cao hơn mức trần (50,6 triệu đồng/tháng từ 01/7/2026).</p>

<h2>3. Ví dụ</h2>
<p>Nhân viên văn phòng lương 15 triệu đồng/tháng (cũng là mức đóng bảo hiểm), không có người phụ thuộc.</p>
<ul>
<li>Doanh nghiệp trích: 15 × 23,5% = 3,525 triệu đồng.</li>
<li>Người lao động đóng: 15 × 10,5% = 1,575 triệu đồng.</li>
<li>Thuế TNCN: 15 − 1,575 − 15,5 &lt; 0 → không phát sinh.</li>
<li>Thực lĩnh: 15 − 1,575 = 13,425 triệu đồng.</li>
</ul>

<h2>4. Các bút toán</h2>
<ol>
<li><strong>Tính lương</strong>: Nợ 6422 / Có 334: 15.000.000.</li>
<li><strong>Trích bảo hiểm, KPCĐ phần doanh nghiệp</strong>: Nợ 6422 / Có 3383: 2.625.000; Có 3384: 450.000; Có 3385: 150.000; Có 3382: 300.000.</li>
<li><strong>Khấu trừ bảo hiểm phần người lao động</strong>: Nợ 334 / Có 3383: 1.200.000; Có 3384: 225.000; Có 3385: 150.000.</li>
<li><strong>Khấu trừ thuế TNCN</strong> (nếu có): Nợ 334 / Có 3335.</li>
<li><strong>Trả lương</strong>: Nợ 334 / Có 112: 13.425.000 (chuyển khoản – bắt buộc với khoản từ 5 triệu đồng để được tính chi phí theo [[tvpl:nd320]]).</li>
<li><strong>Nộp bảo hiểm</strong>: Nợ 3383, 3384, 3385 / Có 112.</li>
<li><strong>Nộp thuế TNCN</strong>: Nợ 3335 / Có 112.</li>
</ol>

<h2>5. Hồ sơ chứng minh chi phí lương</h2>
<ul>
<li>Hợp đồng lao động; quy chế lương, thưởng.</li>
<li>Bảng chấm công, bảng lương có chữ ký hoặc chứng từ ngân hàng.</li>
<li>Tờ khai khấu trừ thuế TNCN, hồ sơ đóng bảo hiểm.</li>
</ul>

<h2>6. Lưu ý</h2>
<ul>
<li>Lương đóng bảo hiểm gồm mức lương và các phụ cấp, khoản bổ sung xác định được mức tiền cụ thể trả thường xuyên theo [[tvpl:nd158]].</li>
<li>Thuế TNCN tính trên thu nhập chịu thuế, có thể khác lương đóng bảo hiểm.</li>
<li>Đối chiếu sổ 334, 338 với tờ khai thuế, bảo hiểm hằng tháng.</li>
</ul>
<p>Học thực hành bảng lương – bảo hiểm – thuế TNCN trên phần mềm: [[khoa-hoc-ke-toan-tong-hop|khoá học kế toán tổng hợp]]. Doanh nghiệp cần làm lương trọn gói: [[ke-toan-tron-goi|dịch vụ kế toán trọn gói]].</p>',
	),

	// ===================== BẢO HIỂM XÃ HỘI =====================
	array(
		'slug'    => 'muc-dong-bhxh-bhyt-bhtn-2026',
		'cat'     => array( 'bao-hiem-xa-hoi' ),
		'title'   => 'Mức đóng BHXH, BHYT, BHTN năm 2026: tỷ lệ 32%, mức lương tối thiểu và mức trần',
		'excerpt' => 'Tỷ lệ đóng bảo hiểm bắt buộc năm 2026 (doanh nghiệp 21,5%, người lao động 10,5%), lương làm căn cứ đóng, mức thấp nhất theo lương tối thiểu vùng (Nghị định 293/2025) và mức trần 46,8 triệu đồng (đến 30/6/2026), 50,6 triệu đồng (từ 01/7/2026).',
		'content' => $u . '
<p>Bảo hiểm bắt buộc là khoản chi lớn thứ hai sau tiền lương. Nắm rõ tỷ lệ, mức tối thiểu và mức trần giúp doanh nghiệp tính đúng chi phí nhân sự và tránh truy thu. Quy định theo [[tvpl:lbhxh]] (hiệu lực từ 01/7/2025) và [[tvpl:nd158]].</p>

<h2>1. Tỷ lệ đóng: tổng 32%</h2>
<table>
<thead><tr><th>Quỹ</th><th>Doanh nghiệp</th><th>Người lao động</th><th>Cộng</th></tr></thead>
<tbody>
<tr><td>Hưu trí, tử tuất</td><td>14%</td><td>8%</td><td>22%</td></tr>
<tr><td>Ốm đau, thai sản</td><td>3%</td><td>–</td><td>3%</td></tr>
<tr><td>Tai nạn lao động, bệnh nghề nghiệp</td><td>0,5%</td><td>–</td><td>0,5%</td></tr>
<tr><td>Bảo hiểm y tế</td><td>3%</td><td>1,5%</td><td>4,5%</td></tr>
<tr><td>Bảo hiểm thất nghiệp</td><td>1%</td><td>1%</td><td>2%</td></tr>
<tr><td><strong>Tổng</strong></td><td><strong>21,5%</strong></td><td><strong>10,5%</strong></td><td><strong>32%</strong></td></tr>
</tbody>
</table>
<p>Ngoài ra doanh nghiệp đóng kinh phí công đoàn 2% quỹ lương đóng bảo hiểm (không thuộc quỹ bảo hiểm).</p>

<h2>2. Tiền lương làm căn cứ đóng</h2>
<p>Gồm mức lương theo công việc, chức danh; phụ cấp lương; các khoản bổ sung xác định được mức tiền cụ thể cùng với mức lương, trả thường xuyên trong mỗi kỳ trả lương – theo Nghị định 158/2025. Các khoản như tiền thưởng theo kết quả kinh doanh, tiền ăn giữa ca, hỗ trợ xăng xe, điện thoại không cố định… thường không tính đóng (cần ghi rõ trong quy chế, hợp đồng).</p>

<h2>3. Mức thấp nhất: lương tối thiểu vùng 2026</h2>
<p>Theo [[tvpl:nd293]], từ 01/01/2026:</p>
<table>
<thead><tr><th>Vùng</th><th>Mức lương tối thiểu tháng</th></tr></thead>
<tbody>
<tr><td>Vùng I</td><td>5.310.000 đồng</td></tr>
<tr><td>Vùng II</td><td>4.730.000 đồng</td></tr>
<tr><td>Vùng III</td><td>4.140.000 đồng</td></tr>
<tr><td>Vùng IV</td><td>3.700.000 đồng</td></tr>
</tbody>
</table>
<p>Lương đóng bảo hiểm không được thấp hơn mức lương tối thiểu vùng nơi người lao động làm việc.</p>

<h2>4. Mức trần</h2>
<ul>
<li>Mức tối đa bằng <strong>20 lần mức tham chiếu</strong>.</li>
<li>Đến 30/6/2026: 20 × 2.340.000 = <strong>46,8 triệu đồng/tháng</strong>.</li>
<li>Từ 01/7/2026: <strong>50,6 triệu đồng/tháng</strong> (theo mức tham chiếu mới).</li>
<li>Bảo hiểm thất nghiệp có mức trần riêng tính theo lương tối thiểu vùng.</li>
</ul>

<h2>5. Ví dụ</h2>
<p>Nhân viên lương đóng bảo hiểm 12 triệu đồng/tháng:</p>
<ul>
<li>Doanh nghiệp đóng: 12 × 21,5% = 2,58 triệu đồng (+ 240.000 đồng kinh phí công đoàn).</li>
<li>Người lao động đóng: 12 × 10,5% = 1,26 triệu đồng (doanh nghiệp khấu trừ từ lương).</li>
</ul>

<h2>6. Hạn đóng</h2>
<p>Doanh nghiệp đóng hằng tháng, chậm nhất ngày cuối cùng của tháng tiếp theo (hoặc theo phương thức 3 tháng, 6 tháng một lần với một số ngành như nông nghiệp, lâm nghiệp, ngư nghiệp, diêm nghiệp trả lương theo sản phẩm, khoán). Chậm đóng bị tính lãi và xử phạt – xem bài Mức phạt chậm đóng BHXH năm 2026 trong chuyên mục này.</p>
<p>Cần tính và kê khai bảo hiểm hằng tháng? [[dang-ky-bao-hiem-xa-hoi|Dịch vụ đăng ký, kê khai bảo hiểm xã hội]] lo trọn từ báo tăng, giảm đến chốt sổ.</p>',
	),

	array(
		'slug'    => 'doi-tuong-tham-gia-bhxh-bat-buoc-2026',
		'cat'     => array( 'bao-hiem-xa-hoi' ),
		'title'   => 'Ai phải tham gia BHXH bắt buộc năm 2026? Đối tượng mới theo Luật BHXH 2024',
		'excerpt' => 'Đối tượng tham gia BHXH bắt buộc theo Luật BHXH 2024: người có hợp đồng lao động từ đủ 1 tháng, lao động không trọn thời gian, người quản lý doanh nghiệp không hưởng lương, chủ hộ kinh doanh, người nước ngoài; trường hợp không phải đóng.',
		'content' => $u . '
<p>[[tvpl:lbhxh]] có hiệu lực từ 01/7/2025 đã mở rộng đáng kể đối tượng tham gia BHXH bắt buộc. Nhiều doanh nghiệp nhỏ trước đây không đóng cho một số nhóm lao động nay phải đóng.</p>

<h2>1. Người lao động Việt Nam</h2>
<ul>
<li>Làm việc theo <strong>hợp đồng lao động từ đủ 1 tháng</strong> trở lên – kể cả hợp đồng có tên gọi khác nhưng có nội dung về việc làm có trả công, tiền lương và sự quản lý, điều hành, giám sát.</li>
<li><strong>Lao động không trọn thời gian</strong> (bán thời gian) có tiền lương trong tháng bằng hoặc cao hơn tiền lương làm căn cứ đóng thấp nhất.</li>
<li>Cán bộ, công chức, viên chức; người hoạt động không chuyên trách ở xã, thôn, tổ dân phố…</li>
</ul>

<h2>2. Người quản lý doanh nghiệp</h2>
<p>Từ 01/7/2025, <strong>người quản lý doanh nghiệp, kiểm soát viên, người đại diện phần vốn nhà nước</strong>, thành viên Hội đồng quản trị, Tổng giám đốc, Giám đốc, thành viên Ban kiểm soát… và người quản lý điều hành hợp tác xã <strong>không hưởng tiền lương</strong> cũng thuộc diện đóng BHXH bắt buộc. Chủ sở hữu công ty TNHH một thành viên kiêm giám đốc cần đặc biệt lưu ý – mức đóng do người tham gia lựa chọn nhưng không thấp hơn mức tham chiếu theo [[tvpl:nd158]].</p>

<h2>3. Chủ hộ kinh doanh</h2>
<p>Theo Nghị định 158/2025, <strong>chủ hộ kinh doanh có đăng ký kinh doanh nộp thuế theo phương pháp kê khai</strong> thuộc đối tượng tham gia BHXH bắt buộc. Từ năm 2026, khi thuế khoán bị bãi bỏ và hộ kinh doanh chuyển sang tự kê khai, nhiều chủ hộ cần kiểm tra nghĩa vụ này với cơ quan bảo hiểm xã hội nơi đăng ký.</p>

<h2>4. Người nước ngoài</h2>
<p>Người lao động nước ngoài làm việc tại Việt Nam có giấy phép lao động (hoặc giấy xác nhận không thuộc diện cấp giấy phép) và hợp đồng lao động theo quy định thuộc diện đóng BHXH bắt buộc, trừ các trường hợp di chuyển nội bộ doanh nghiệp, đã đủ tuổi nghỉ hưu, hoặc điều ước quốc tế có quy định khác.</p>

<h2>5. Ai không phải đóng?</h2>
<ul>
<li>Người đang hưởng lương hưu, trợ cấp hằng tháng theo quy định.</li>
<li>Người giúp việc gia đình.</li>
<li>Người lao động đã đủ tuổi nghỉ hưu (trừ một số trường hợp).</li>
<li>Hợp đồng dưới 1 tháng (vẫn có thể phải đóng bảo hiểm tai nạn lao động theo quy định riêng).</li>
</ul>

<h2>6. BHYT và BHTN</h2>
<ul>
<li><strong>BHYT</strong>: người lao động có hợp đồng từ đủ 1 tháng tham gia BHYT bắt buộc do doanh nghiệp và người lao động cùng đóng.</li>
<li><strong>BHTN</strong>: người làm việc theo hợp đồng lao động từ đủ 1 tháng trở lên theo pháp luật về việc làm (trừ một số trường hợp).</li>
</ul>

<h2>7. Không đóng thì sao?</h2>
<p>Không đóng, đóng không đủ số người thuộc diện bắt buộc bị xử phạt theo [[tvpl:nd283]] (hiệu lực từ 10/9/2026), buộc đóng đủ số tiền và tiền lãi; trường hợp trốn đóng có thể bị truy cứu trách nhiệm hình sự.</p>
<p>Rà soát danh sách người phải đóng và đăng ký bổ sung: [[dang-ky-bao-hiem-xa-hoi|dịch vụ đăng ký bảo hiểm xã hội]].</p>',
	),

	array(
		'slug'    => 'thu-tuc-dang-ky-bhxh-lan-dau-cho-doanh-nghiep',
		'cat'     => array( 'bao-hiem-xa-hoi' ),
		'title'   => 'Thủ tục đăng ký BHXH lần đầu cho doanh nghiệp mới: hồ sơ, thời hạn 30 ngày và cách nộp',
		'excerpt' => 'Doanh nghiệp phải đăng ký tham gia BHXH, BHYT, BHTN cho người lao động trong 30 ngày kể từ ngày hợp đồng có hiệu lực: hồ sơ (tờ khai đơn vị TK3-TS, danh sách lao động D02), nộp điện tử, cấp mã đơn vị, sổ BHXH, thẻ BHYT và báo tăng, giảm hằng tháng.',
		'content' => $u . '
<p>Doanh nghiệp mới tuyển nhân viên đầu tiên cần làm thủ tục đăng ký tham gia bảo hiểm bắt buộc. Thủ tục hiện thực hiện hoàn toàn điện tử và khá nhanh nếu chuẩn bị đúng.</p>

<h2>1. Thời hạn</h2>
<p>Trong <strong>30 ngày</strong> kể từ ngày hợp đồng lao động có hiệu lực (hoặc ngày tuyển dụng), doanh nghiệp phải nộp hồ sơ đăng ký tham gia BHXH bắt buộc cho người lao động theo [[tvpl:lbhxh]]. Đăng ký chậm vẫn phải truy đóng từ thời điểm phát sinh nghĩa vụ và có thể bị xử phạt.</p>

<h2>2. Hồ sơ đăng ký lần đầu</h2>
<h3>Phần đơn vị</h3>
<ul>
<li>Tờ khai đơn vị tham gia, điều chỉnh thông tin BHXH, BHYT (mẫu TK3-TS theo biểu mẫu hiện hành của BHXH Việt Nam).</li>
<li>Thông tin đăng ký doanh nghiệp được cơ quan BHXH khai thác từ cơ sở dữ liệu – không phải nộp bản sao giấy chứng nhận.</li>
</ul>
<h3>Phần người lao động</h3>
<ul>
<li>Danh sách lao động tham gia BHXH, BHYT, BHTN, BHTNLĐ-BNN (mẫu D02).</li>
<li>Thông tin người lao động: số định danh cá nhân, mức lương đóng, phụ cấp, chức danh, ngày bắt đầu.</li>
<li>Tờ khai tham gia, điều chỉnh thông tin BHXH, BHYT của người lao động (mẫu TK1-TS) với người chưa có mã số BHXH hoặc cần điều chỉnh thông tin.</li>
</ul>

<h2>3. Cách nộp</h2>
<ol>
<li>Đăng ký <strong>giao dịch điện tử</strong> với cơ quan BHXH qua Cổng dịch vụ công hoặc nhà cung cấp dịch vụ I-VAN (phần mềm kê khai bảo hiểm), dùng chữ ký số của doanh nghiệp.</li>
<li>Kê khai hồ sơ đăng ký đơn vị và danh sách lao động trên phần mềm.</li>
<li>Ký số và nộp; theo dõi kết quả trên hệ thống.</li>
<li>Nhận <strong>mã đơn vị</strong> tham gia BHXH và thông báo mức đóng.</li>
</ol>
<p>Thời gian giải quyết thường trong vài ngày làm việc kể từ khi nhận đủ hồ sơ.</p>

<h2>4. Sau khi đăng ký</h2>
<ul>
<li>Đóng tiền bảo hiểm theo thông báo, chậm nhất ngày cuối cùng của tháng tiếp theo.</li>
<li>Người lao động tra cứu quá trình đóng, thẻ BHYT điện tử trên ứng dụng <strong>VssID</strong> hoặc VNeID.</li>
<li>Thẻ BHYT có thể dùng bằng thẻ căn cước gắn chip khi khám chữa bệnh.</li>
</ul>

<h2>5. Báo tăng, giảm hằng tháng</h2>
<ul>
<li>Nhân viên mới: báo tăng trong tháng phát sinh.</li>
<li>Nhân viên nghỉ việc, nghỉ không lương từ 14 ngày làm việc trở lên trong tháng: báo giảm để không phải đóng tháng đó.</li>
<li>Thay đổi mức lương: điều chỉnh mức đóng.</li>
<li>Khi người lao động nghỉ việc: chốt quá trình đóng để người lao động hưởng trợ cấp thất nghiệp hoặc chuyển nơi làm việc.</li>
</ul>

<h2>6. Lưu ý thường gặp</h2>
<ul>
<li>Mức lương đóng không thấp hơn lương tối thiểu vùng theo [[tvpl:nd293]].</li>
<li>Thông tin người lao động phải khớp cơ sở dữ liệu dân cư.</li>
<li>Không “ghi lùi” ngày vào làm để né đóng tháng đầu – dễ bị phát hiện khi đối chiếu dữ liệu thuế TNCN.</li>
</ul>
<p>Cần đăng ký nhanh, kê khai bảo hiểm hằng tháng không sai sót? [[dang-ky-bao-hiem-xa-hoi|Dịch vụ đăng ký bảo hiểm xã hội]] thực hiện trọn gói.</p>',
	),

	array(
		'slug'    => 'muc-phat-cham-dong-bhxh-2026',
		'cat'     => array( 'bao-hiem-xa-hoi' ),
		'title'   => 'Chậm đóng, trốn đóng BHXH năm 2026 bị xử lý thế nào? Mức phạt theo Nghị định 283/2026',
		'excerpt' => 'Phân biệt chậm đóng và trốn đóng BHXH theo Luật BHXH 2024; mức phạt 12% – 15% số tiền chậm đóng (tối đa 75 triệu đồng với cá nhân, gấp đôi với tổ chức) theo Nghị định 283/2026 từ 10/9/2026; tiền lãi 0,03%/ngày và biện pháp khắc phục.',
		'content' => $u . '
<p>Từ ngày 10/9/2026, [[tvpl:nd283]] thay thế Nghị định 12/2022 về xử phạt vi phạm hành chính trong lĩnh vực lao động, bảo hiểm xã hội. Các hành vi về BHXH, BHTN được tách thành điều khoản riêng, mức phạt rõ ràng hơn.</p>

<h2>1. Chậm đóng và trốn đóng khác nhau thế nào?</h2>
<p>Theo [[tvpl:lbhxh]]:</p>
<ul>
<li><strong>Chậm đóng</strong>: doanh nghiệp đã đăng ký tham gia nhưng không đóng hoặc đóng chưa đủ số tiền sau thời hạn đóng (chậm nhất ngày cuối cùng của tháng tiếp theo, hoặc theo phương thức đóng đã đăng ký); hoặc chưa đăng ký nhưng chưa quá 60 ngày kể từ hết thời hạn đăng ký.</li>
<li><strong>Trốn đóng</strong>: không đăng ký hoặc đăng ký không đủ số người phải tham gia sau 60 ngày kể từ hết thời hạn đăng ký; đăng ký mức lương đóng thấp hơn quy định; không đóng số tiền đã thu của người lao động… mà không thuộc trường hợp được miễn trách nhiệm.</li>
</ul>

<h2>2. Mức phạt chậm đóng</h2>
<ul>
<li>Phạt tiền từ <strong>12% đến 15%</strong> tổng số tiền BHXH bắt buộc chậm đóng tại thời điểm lập biên bản, tối đa <strong>75 triệu đồng</strong> (mức với cá nhân).</li>
<li>Tổ chức vi phạm bị phạt <strong>gấp đôi</strong> mức với cá nhân.</li>
<li>Bảo hiểm thất nghiệp chậm đóng có điều khoản xử phạt riêng tương tự.</li>
</ul>

<h2>3. Mức phạt trốn đóng</h2>
<p>Hành vi trốn đóng bị phạt nặng hơn chậm đóng (tỷ lệ phạt cao hơn trên số tiền trốn đóng), kèm biện pháp buộc đóng đủ. Trốn đóng với số tiền lớn, thời gian dài hoặc nhiều người lao động có thể bị truy cứu trách nhiệm hình sự theo Bộ luật Hình sự.</p>

<h2>4. Biện pháp khắc phục</h2>
<ul>
<li>Buộc đóng đủ số tiền BHXH, BHTN chậm đóng, trốn đóng.</li>
<li>Buộc nộp <strong>tiền lãi 0,03%/ngày</strong> tính trên số tiền chậm đóng, trốn đóng và số ngày chậm.</li>
<li>Buộc đăng ký tham gia cho người lao động chưa được đăng ký.</li>
</ul>

<h2>5. Hậu quả với người lao động và doanh nghiệp</h2>
<ul>
<li>Người lao động có thể bị gián đoạn quyền lợi BHYT, chế độ ốm đau, thai sản; doanh nghiệp phải tự chi trả các quyền lợi tương ứng nếu do lỗi của mình.</li>
<li>Doanh nghiệp bị công khai thông tin chậm đóng, trốn đóng.</li>
<li>Ảnh hưởng hồ sơ tham gia đấu thầu, vay vốn, xếp hạng tín nhiệm.</li>
</ul>

<h2>6. Các vi phạm khác cần tránh</h2>
<ul>
<li>Không thông báo kết quả đóng BHXH cho người lao động theo quy định.</li>
<li>Không chốt sổ, không phối hợp trả sổ BHXH khi người lao động nghỉ việc.</li>
<li>Trả lương không đúng hạn – Nghị định 283/2026 cũng tăng mức phạt hành vi này.</li>
</ul>

<h2>7. Lỡ chậm đóng thì làm gì?</h2>
<ol>
<li>Đóng ngay số tiền còn thiếu để dừng tính lãi.</li>
<li>Rà soát danh sách lao động, mức lương đóng; đăng ký bổ sung người còn thiếu.</li>
<li>Liên hệ cơ quan BHXH để xác nhận số phải nộp, số lãi.</li>
<li>Thiết lập lịch đóng tự động hằng tháng.</li>
</ol>
<p>Muốn rà soát nghĩa vụ bảo hiểm trước khi bị thanh tra? [[dang-ky-bao-hiem-xa-hoi|Dịch vụ bảo hiểm xã hội]] kiểm tra danh sách, mức lương đóng và xử lý truy đóng giúp bạn.</p>',
	),
);
