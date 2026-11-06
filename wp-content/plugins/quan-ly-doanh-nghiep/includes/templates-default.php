<?php
/**
 * Mẫu văn bản gốc (bản tham khảo – cần rà soát pháp lý cho từng giao dịch trước khi ký).
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tiêu ngữ + số / ngày tháng.
 *
 * @param string $left Dòng bên trái dưới tên công ty (số văn bản).
 * @return string HTML.
 */
function qldn_tpl_head( $left = '' ) {
	return '<table class="qldn-head" style="width:100%"><tbody><tr>'
		. '<td style="width:45%;text-align:center"><strong>{{cong_ty}}</strong><br>' . $left . '</td>'
		. '<td style="text-align:center"><strong>CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><br><strong><u>Độc lập – Tự do – Hạnh phúc</u></strong><br><em>……………, ngày {{ngay}} tháng {{thang}} năm {{nam}}</em></td>'
		. '</tr></tbody></table>';
}

/**
 * Khối chữ ký 2 bên.
 *
 * @param string $a Tên bên trái.
 * @param string $b Tên bên phải.
 * @return string HTML.
 */
function qldn_tpl_sign( $a, $b ) {
	return '<table class="qldn-sign" style="width:100%;margin-top:18pt"><tbody><tr>'
		. '<td style="text-align:center;width:50%"><strong>' . $a . '</strong><br><em>(Ký, ghi rõ họ tên, đóng dấu nếu có)</em><br><br><br><br><br></td>'
		. '<td style="text-align:center"><strong>' . $b . '</strong><br><em>(Ký, ghi rõ họ tên, đóng dấu nếu có)</em><br><br><br><br><br></td>'
		. '</tr></tbody></table>';
}

/**
 * Thông tin 1 bên là doanh nghiệp của mình.
 *
 * @param string $label "BÊN A (BÊN BÁN)".
 * @return string HTML.
 */
function qldn_tpl_party_us( $label ) {
	return '<p><strong>' . $label . ': {{cong_ty}}</strong></p>'
		. '<p>Địa chỉ trụ sở: {{cong_ty_dia_chi}}</p>'
		. '<p>Mã số doanh nghiệp / mã số thuế: {{cong_ty_mst}}</p>'
		. '<p>Điện thoại: {{cong_ty_dien_thoai}} &nbsp; Email: {{cong_ty_email}}</p>'
		. '<p>Tài khoản: {{cong_ty_tai_khoan}}</p>'
		. '<p>Người đại diện: Ông/Bà <strong>{{cong_ty_dai_dien}}</strong> &nbsp; Chức vụ: {{cong_ty_chuc_vu}}</p>';
}

/**
 * Thông tin bên là khách hàng.
 *
 * @param string $label "BÊN B (BÊN MUA)".
 * @return string HTML.
 */
function qldn_tpl_party_customer( $label ) {
	return '<p><strong>' . $label . ': {{kh_ten}}</strong></p>'
		. '<p>Địa chỉ: {{kh_dia_chi}}</p>'
		. '<p>Mã số thuế / số định danh: {{kh_mst}}</p>'
		. '<p>Điện thoại: {{kh_dien_thoai}} &nbsp; Email: {{kh_email}}</p>'
		. '<p>Tài khoản: {{kh_tai_khoan}}</p>'
		. '<p>Người đại diện: Ông/Bà <strong>{{kh_dai_dien}}</strong> &nbsp; Chức vụ: {{kh_chuc_vu}}</p>';
}

/**
 * Các mẫu gốc.
 *
 * @return array slug => [name, context, kind, content].
 */
function qldn_default_templates() {
	$t = array();

	$t['bao-gia'] = array(
		'Thư báo giá',
		'quote',
		'',
		qldn_tpl_head( 'Số: {{bg_so}}' )
		. '<h1>BẢNG BÁO GIÁ</h1><p style="text-align:center"><em>{{bg_tieu_de}}</em></p>'
		. '<p>Kính gửi: <strong>{{kh_ten}}</strong></p><p>Địa chỉ: {{kh_dia_chi}} &nbsp; Điện thoại: {{kh_dien_thoai}}</p>'
		. '<p>{{cong_ty}} trân trọng cảm ơn Quý khách đã quan tâm đến sản phẩm, dịch vụ của chúng tôi. Chúng tôi xin gửi đến Quý khách bảng báo giá như sau:</p>'
		. '{{bang_hang}}'
		. '<h2>Điều khoản</h2><p>{{bg_dieu_khoan}}</p>'
		. '<p>Báo giá có hiệu lực đến ngày <strong>{{bg_hieu_luc}}</strong>.</p>'
		. '<p>Mọi chi tiết xin liên hệ: {{bg_nhan_vien}} – điện thoại {{bg_nv_dien_thoai}}.</p>'
		. '<p>Rất mong được hợp tác cùng Quý khách!</p>'
		. '<table class="qldn-sign" style="width:100%;margin-top:18pt"><tbody><tr><td style="width:50%"></td><td style="text-align:center"><strong>{{cong_ty}}</strong><br>{{cong_ty_chuc_vu}}<br><br><br><br><strong>{{cong_ty_dai_dien}}</strong></td></tr></tbody></table>',
	);

	$t['hd-mua-ban'] = array(
		'Hợp đồng mua bán hàng hoá',
		'contract',
		'sale',
		qldn_tpl_head( 'Số: {{hd_so}}' )
		. '<h1>HỢP ĐỒNG MUA BÁN HÀNG HOÁ</h1>'
		. '<p><em>– Căn cứ Bộ luật Dân sự số 91/2015/QH13 ngày 24/11/2015;</em></p>'
		. '<p><em>– Căn cứ Luật Thương mại số 36/2005/QH11 ngày 14/6/2005 và các văn bản sửa đổi, bổ sung;</em></p>'
		. '<p><em>– Căn cứ nhu cầu và khả năng của các bên.</em></p>'
		. '<p>Hôm nay, ngày {{hd_ngay_ky}}, chúng tôi gồm:</p>'
		. qldn_tpl_party_us( 'BÊN A (BÊN BÁN)' ) . qldn_tpl_party_customer( 'BÊN B (BÊN MUA)' )
		. '<p>Hai bên thống nhất ký kết hợp đồng mua bán hàng hoá với các điều khoản sau:</p>'
		. '<h2>Điều 1. Hàng hoá, số lượng, giá</h2>{{bang_hang}}<p>{{hd_pham_vi}}</p>'
		. '<h2>Điều 2. Giá trị hợp đồng và thanh toán</h2>'
		. '<p>2.1. Tổng giá trị hợp đồng: <strong>{{hd_gia_tri}} đồng</strong> (Bằng chữ: <em>{{hd_gia_tri_chu}}</em>), đã bao gồm thuế giá trị gia tăng (nếu có).</p>'
		. '<p>2.2. Phương thức thanh toán: chuyển khoản vào tài khoản của Bên A nêu trên hoặc tiền mặt.</p>'
		. '<p>2.3. Thời hạn thanh toán: ……………………………………………………</p>'
		. '<h2>Điều 3. Giao nhận hàng hoá</h2>'
		. '<p>3.1. Thời gian giao hàng: ……………………; địa điểm giao hàng: ……………………</p>'
		. '<p>3.2. Khi giao nhận, hai bên kiểm tra số lượng, chủng loại, quy cách và ký biên bản giao nhận. Quyền sở hữu và rủi ro đối với hàng hoá chuyển sang Bên B kể từ thời điểm hai bên ký biên bản giao nhận.</p>'
		. '<h2>Điều 4. Chất lượng và bảo hành</h2>'
		. '<p>4.1. Hàng hoá mới 100%, đúng chủng loại, quy cách, chất lượng theo tiêu chuẩn của nhà sản xuất.</p>'
		. '<p>4.2. Thời hạn bảo hành: …… tháng kể từ ngày giao hàng, theo chính sách bảo hành của nhà sản xuất / Bên A.</p>'
		. '<h2>Điều 5. Quyền và nghĩa vụ của các bên</h2>'
		. '<p>5.1. Bên A: giao hàng đúng số lượng, chất lượng, thời hạn; cung cấp hoá đơn, chứng từ hợp lệ; thực hiện bảo hành theo Điều 4.</p>'
		. '<p>5.2. Bên B: nhận hàng và thanh toán đầy đủ, đúng hạn theo Điều 2; thông báo ngay cho Bên A khi phát hiện hàng hoá không đúng hợp đồng.</p>'
		. '<h2>Điều 6. Phạt vi phạm và bồi thường thiệt hại</h2>'
		. '<p>6.1. Bên vi phạm nghĩa vụ hợp đồng phải chịu phạt vi phạm bằng …% giá trị phần nghĩa vụ hợp đồng bị vi phạm (không quá 8% theo Điều 301 Luật Thương mại) và bồi thường thiệt hại thực tế phát sinh.</p>'
		. '<p>6.2. Bên B chậm thanh toán phải trả lãi trên số tiền chậm trả theo Điều 306 Luật Thương mại.</p>'
		. '<h2>Điều 7. Bất khả kháng</h2>'
		. '<p>Bên gặp sự kiện bất khả kháng theo Điều 156 Bộ luật Dân sự phải thông báo bằng văn bản cho bên kia trong vòng 05 ngày làm việc và được miễn trách nhiệm đối với phần nghĩa vụ không thực hiện được do sự kiện đó.</p>'
		. '<h2>Điều 8. Giải quyết tranh chấp</h2>'
		. '<p>Tranh chấp phát sinh được giải quyết trước hết bằng thương lượng. Không thương lượng được trong 30 ngày, một bên có quyền yêu cầu Toà án nhân dân có thẩm quyền giải quyết.</p>'
		. '<h2>Điều 9. Điều khoản chung</h2>'
		. '<p>Hợp đồng có hiệu lực kể từ ngày ký đến khi các bên hoàn thành nghĩa vụ và thanh lý hợp đồng. Mọi sửa đổi, bổ sung phải lập thành văn bản có chữ ký của hai bên. Hợp đồng lập thành 02 bản có giá trị pháp lý như nhau, mỗi bên giữ 01 bản.</p>'
		. qldn_tpl_sign( 'ĐẠI DIỆN BÊN A', 'ĐẠI DIỆN BÊN B' ),
	);

	$t['hd-dich-vu'] = array(
		'Hợp đồng cung cấp dịch vụ',
		'contract',
		'service',
		qldn_tpl_head( 'Số: {{hd_so}}' )
		. '<h1>HỢP ĐỒNG DỊCH VỤ</h1><p style="text-align:center"><em>(V/v: {{hd_ten}})</em></p>'
		. '<p><em>– Căn cứ Bộ luật Dân sự số 91/2015/QH13 ngày 24/11/2015;</em></p>'
		. '<p><em>– Căn cứ Luật Thương mại số 36/2005/QH11 ngày 14/6/2005 và các văn bản sửa đổi, bổ sung;</em></p>'
		. '<p><em>– Căn cứ nhu cầu và khả năng của các bên.</em></p>'
		. '<p>Hôm nay, ngày {{hd_ngay_ky}}, chúng tôi gồm:</p>'
		. qldn_tpl_party_customer( 'BÊN A (BÊN SỬ DỤNG DỊCH VỤ)' ) . qldn_tpl_party_us( 'BÊN B (BÊN CUNG ỨNG DỊCH VỤ)' )
		. '<p>Hai bên thống nhất ký kết hợp đồng dịch vụ với các điều khoản sau:</p>'
		. '<h2>Điều 1. Nội dung dịch vụ</h2><p>Bên B thực hiện cho Bên A các công việc sau:</p><p>{{hd_pham_vi}}</p>'
		. '<h2>Điều 2. Thời hạn thực hiện</h2><p>Thời hạn thực hiện hợp đồng: {{hd_thoi_han}}.</p>'
		. '<h2>Điều 3. Phí dịch vụ và thanh toán</h2>'
		. '<p>3.1. Phí dịch vụ: <strong>{{hd_gia_tri}} đồng</strong> (Bằng chữ: <em>{{hd_gia_tri_chu}}</em>), đã bao gồm thuế giá trị gia tăng (nếu có).</p>'
		. '<p>3.2. Thanh toán bằng chuyển khoản vào tài khoản của Bên B, chia thành các đợt: Đợt 1: …% ngay sau khi ký hợp đồng; Đợt 2: …% sau khi Bên B hoàn thành công việc và hai bên ký biên bản nghiệm thu.</p>'
		. '<h2>Điều 4. Quyền và nghĩa vụ của Bên A</h2>'
		. '<p>Cung cấp đầy đủ, kịp thời thông tin, tài liệu, phương tiện cần thiết để Bên B thực hiện công việc (Điều 515 Bộ luật Dân sự); thanh toán phí dịch vụ đúng hạn; được yêu cầu Bên B thực hiện công việc đúng chất lượng, số lượng, thời hạn đã thoả thuận.</p>'
		. '<h2>Điều 5. Quyền và nghĩa vụ của Bên B</h2>'
		. '<p>Thực hiện công việc đúng chất lượng, số lượng, thời hạn, địa điểm đã thoả thuận; không giao cho người khác thực hiện thay khi không có sự đồng ý của Bên A; bảo quản và giao lại tài liệu, phương tiện được giao; giữ bí mật thông tin biết được trong thời gian thực hiện công việc (Điều 517 Bộ luật Dân sự); bồi thường thiệt hại nếu làm mất, hư hỏng tài liệu hoặc tiết lộ bí mật thông tin.</p>'
		. '<h2>Điều 6. Phạt vi phạm và bồi thường thiệt hại</h2>'
		. '<p>Bên vi phạm nghĩa vụ hợp đồng phải chịu phạt vi phạm bằng …% giá trị phần nghĩa vụ bị vi phạm (không quá 8% nếu là hoạt động thương mại – Điều 301 Luật Thương mại) và bồi thường thiệt hại thực tế phát sinh.</p>'
		. '<h2>Điều 7. Chấm dứt hợp đồng</h2>'
		. '<p>Hợp đồng chấm dứt khi các bên hoàn thành nghĩa vụ; theo thoả thuận của các bên; hoặc khi một bên đơn phương chấm dứt theo Điều 520 Bộ luật Dân sự và phải báo trước cho bên kia một thời gian hợp lý.</p>'
		. '<h2>Điều 8. Giải quyết tranh chấp</h2>'
		. '<p>Tranh chấp được giải quyết bằng thương lượng; không thương lượng được trong 30 ngày, một bên có quyền yêu cầu Toà án nhân dân có thẩm quyền giải quyết.</p>'
		. '<h2>Điều 9. Điều khoản chung</h2>'
		. '<p>Hợp đồng có hiệu lực kể từ ngày ký. Mọi sửa đổi, bổ sung phải lập thành văn bản có chữ ký của hai bên. Hợp đồng lập thành 02 bản có giá trị pháp lý như nhau, mỗi bên giữ 01 bản.</p>'
		. qldn_tpl_sign( 'ĐẠI DIỆN BÊN A', 'ĐẠI DIỆN BÊN B' ),
	);

	$t['hd-lao-dong'] = array(
		'Hợp đồng lao động',
		'contract',
		'labor',
		qldn_tpl_head( 'Số: {{hd_so}}' )
		. '<h1>HỢP ĐỒNG LAO ĐỘNG</h1>'
		. '<p><em>– Căn cứ Bộ luật Lao động số 45/2019/QH14 ngày 20/11/2019;</em></p>'
		. '<p><em>– Căn cứ Nghị định số 145/2020/NĐ-CP ngày 14/12/2020 của Chính phủ quy định chi tiết và hướng dẫn thi hành một số điều của Bộ luật Lao động về điều kiện lao động và quan hệ lao động;</em></p>'
		. '<p><em>– Căn cứ nhu cầu và năng lực của hai bên.</em></p>'
		. '<p>Hôm nay, ngày {{hd_ngay_ky}}, chúng tôi gồm:</p>'
		. '<p><strong>NGƯỜI SỬ DỤNG LAO ĐỘNG: {{cong_ty}}</strong></p><p>Địa chỉ: {{cong_ty_dia_chi}}</p><p>Mã số doanh nghiệp: {{cong_ty_mst}} &nbsp; Điện thoại: {{cong_ty_dien_thoai}}</p>'
		. '<p>Người đại diện: Ông/Bà <strong>{{cong_ty_dai_dien}}</strong> &nbsp; Chức vụ: {{cong_ty_chuc_vu}}</p>'
		. '<p><strong>NGƯỜI LAO ĐỘNG: Ông/Bà {{nv_ten}}</strong></p>'
		. '<p>Ngày sinh: {{nv_ngay_sinh}} &nbsp; Giới tính: {{nv_gioi_tinh}}</p>'
		. '<p>Số CCCD: {{nv_cccd}} &nbsp; cấp ngày {{nv_ngay_cap}} tại {{nv_noi_cap}}</p>'
		. '<p>Nơi cư trú: {{nv_dia_chi}}</p><p>Điện thoại: {{nv_dien_thoai}} &nbsp; Mã số thuế cá nhân: {{nv_mst}}</p>'
		. '<p>Hai bên thoả thuận ký kết hợp đồng lao động và cam kết thực hiện các điều khoản sau:</p>'
		. '<h2>Điều 1. Thời hạn và công việc hợp đồng</h2>'
		. '<p>1.1. Loại hợp đồng: {{hd_thoi_han}}; bắt đầu làm việc từ ngày {{hd_tu_ngay}}.</p>'
		. '<p>1.2. Chức danh chuyên môn: {{nv_chuc_vu}} – Bộ phận: {{nv_bo_phan}}.</p>'
		. '<p>1.3. Công việc phải làm và địa điểm làm việc: {{hd_pham_vi}}</p>'
		. '<h2>Điều 2. Chế độ làm việc</h2>'
		. '<p>2.1. Thời giờ làm việc bình thường: 08 giờ/ngày, không quá 48 giờ/tuần, theo nội quy lao động của công ty.</p>'
		. '<p>2.2. Làm thêm giờ theo thoả thuận và trong giới hạn của Điều 107 Bộ luật Lao động; được trả lương làm thêm giờ theo Điều 98 Bộ luật Lao động.</p>'
		. '<p>2.3. Nghỉ hằng năm 12 ngày làm việc đối với người làm việc đủ 12 tháng (Điều 113 Bộ luật Lao động); nghỉ lễ, tết, việc riêng theo quy định.</p>'
		. '<p>2.4. Được cấp phát trang thiết bị bảo hộ lao động theo yêu cầu công việc.</p>'
		. '<h2>Điều 3. Tiền lương và các khoản bổ sung</h2>'
		. '<p>3.1. Mức lương theo công việc, chức danh: <strong>{{hd_gia_tri}} đồng/tháng</strong> (Bằng chữ: <em>{{hd_gia_tri_chu}}</em>). Hình thức trả lương: {{nv_hinh_thuc_luong}}.</p>'
		. '<p>3.2. Phụ cấp lương và các khoản bổ sung khác: {{nv_phu_cap}} đồng/tháng.</p>'
		. '<p>3.3. Lương được trả bằng chuyển khoản vào tài khoản {{nv_tai_khoan}}, chậm nhất vào ngày …… hằng tháng.</p>'
		. '<p>3.4. Chế độ nâng bậc, nâng lương: xem xét …… lần/năm theo quy chế lương, thưởng của công ty.</p>'
		. '<h2>Điều 4. Bảo hiểm và đào tạo</h2>'
		. '<p>4.1. Hai bên tham gia bảo hiểm xã hội bắt buộc, bảo hiểm y tế, bảo hiểm thất nghiệp theo quy định; mức lương đóng bảo hiểm: {{nv_luong_bh}} đồng/tháng. Người lao động đóng 8% BHXH, 1,5% BHYT, 1% BHTN; người sử dụng lao động đóng phần của mình theo quy định.</p>'
		. '<p>4.2. Người lao động được đào tạo, bồi dưỡng, nâng cao trình độ, kỹ năng nghề theo kế hoạch của công ty.</p>'
		. '<h2>Điều 5. Quyền và nghĩa vụ của người lao động</h2>'
		. '<p>Được hưởng các quyền theo Điều 5 Bộ luật Lao động; thực hiện công việc theo hợp đồng, chấp hành nội quy lao động, quy chế của công ty và sự điều hành hợp pháp của người sử dụng lao động; giữ bí mật kinh doanh, bí mật công nghệ theo thoả thuận; bồi thường vi phạm và vật chất theo quy định.</p>'
		. '<h2>Điều 6. Quyền và nghĩa vụ của người sử dụng lao động</h2>'
		. '<p>Thực hiện đầy đủ, đúng hạn các nghĩa vụ đã cam kết trong hợp đồng; trả lương, đóng bảo hiểm đúng quy định; bảo đảm điều kiện làm việc an toàn, vệ sinh lao động; có quyền điều hành, khen thưởng, xử lý kỷ luật lao động theo quy định của pháp luật và nội quy lao động.</p>'
		. '<h2>Điều 7. Điều khoản thi hành</h2>'
		. '<p>Những vấn đề về lao động không ghi trong hợp đồng này thì áp dụng theo thoả ước lao động tập thể (nếu có), nội quy lao động và quy định của pháp luật lao động. Hợp đồng lập thành 02 bản có giá trị như nhau, mỗi bên giữ 01 bản và có hiệu lực từ ngày {{hd_tu_ngay}}.</p>'
		. qldn_tpl_sign( 'NGƯỜI LAO ĐỘNG', 'NGƯỜI SỬ DỤNG LAO ĐỘNG' ),
	);

	$t['bb-thanh-ly'] = array(
		'Biên bản thanh lý hợp đồng',
		'contract',
		'',
		qldn_tpl_head()
		. '<h1>BIÊN BẢN THANH LÝ HỢP ĐỒNG</h1><p style="text-align:center"><em>(Hợp đồng số {{hd_so}} ký ngày {{hd_ngay_ky}})</em></p>'
		. '<p><em>– Căn cứ Hợp đồng số {{hd_so}} về {{hd_ten}};</em></p><p><em>– Căn cứ kết quả thực hiện hợp đồng của các bên.</em></p>'
		. '<p>Hôm nay, ngày {{ngay_hom_nay}}, chúng tôi gồm:</p>'
		. qldn_tpl_party_us( 'BÊN THỨ NHẤT' ) . qldn_tpl_party_customer( 'BÊN THỨ HAI' )
		. '<p>Cùng thống nhất thanh lý hợp đồng nêu trên với nội dung sau:</p>'
		. '<h2>Điều 1. Kết quả thực hiện</h2><p>Hai bên đã thực hiện đầy đủ các nội dung của hợp đồng: {{hd_pham_vi}}</p>'
		. '<h2>Điều 2. Thanh toán</h2>'
		. '<p>Giá trị hợp đồng: <strong>{{hd_gia_tri}} đồng</strong> (Bằng chữ: <em>{{hd_gia_tri_chu}}</em>).</p>'
		. '<p>Số tiền đã thanh toán: {{hd_da_thanh_toan}} đồng. Số tiền còn phải thanh toán: {{hd_con_lai}} đồng, thanh toán trong vòng …… ngày kể từ ngày ký biên bản này.</p>'
		. '<h2>Điều 3. Cam kết</h2>'
		. '<p>Kể từ ngày ký biên bản này và sau khi thanh toán đủ số tiền tại Điều 2, hợp đồng số {{hd_so}} chấm dứt, hai bên không còn nghĩa vụ nào đối với nhau (trừ nghĩa vụ bảo hành, bảo mật nếu có). Biên bản lập thành 02 bản có giá trị như nhau, mỗi bên giữ 01 bản.</p>'
		. qldn_tpl_sign( 'ĐẠI DIỆN BÊN THỨ NHẤT', 'ĐẠI DIỆN BÊN THỨ HAI' ),
	);

	return $t;
}

/**
 * Nạp mẫu gốc lần đầu (hoặc thêm lại mẫu gốc đã xoá).
 *
 * @param bool $restore Thêm lại mẫu còn thiếu kể cả khi đã nạp trước đó.
 * @return int Số mẫu đã thêm.
 */
function qldn_seed_templates( $restore = false ) {
	global $wpdb;
	if ( ! $restore && get_option( 'qldn_templates_seeded' ) ) {
		return 0;
	}
	$have = $wpdb->get_col( 'SELECT slug FROM ' . qldn_t( 'templates' ) . " WHERE slug <> ''" );
	$n    = 0;
	foreach ( qldn_default_templates() as $slug => $tpl ) {
		if ( in_array( $slug, $have, true ) ) {
			continue;
		}
		$wpdb->insert(
			qldn_t( 'templates' ),
			array(
				'slug'       => $slug,
				'name'       => $tpl[0],
				'context'    => $tpl[1],
				'kind'       => $tpl[2],
				'content'    => $tpl[3],
				'created_at' => qldn_now(),
			)
		);
		++$n;
	}
	update_option( 'qldn_templates_seeded', 1, false );
	return $n;
}
