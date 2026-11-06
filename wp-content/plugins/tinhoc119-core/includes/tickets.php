<?php
/**
 * Yêu cầu hỗ trợ kỹ thuật / đặt lịch: form ngoài website, sinh mã, lưu database,
 * báo quản trị viên, cập nhật trạng thái, lịch sử xử lý.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Chống spam: token thời gian ký HMAC (chạy được cả khi trang bị cache),
 * honeypot, giới hạn tần suất theo IP, kiểm tra nguồn gửi, Turnstile tuỳ chọn.
 * ------------------------------------------------------------------------- */

/**
 * Tạo token form.
 *
 * @param string $form Tên form.
 * @return string
 */
function th119_form_token( $form ) {
	$t = time();
	return $t . '.' . substr( hash_hmac( 'sha256', $form . '|' . $t, wp_salt( 'nonce' ) ), 0, 20 );
}

/**
 * Kiểm tra token: đúng chữ ký, gửi sau ít nhất 3 giây và trong vòng 3 ngày.
 *
 * @param string $form  Tên form.
 * @param string $token Token.
 * @return bool
 */
function th119_check_form_token( $form, $token ) {
	$p = explode( '.', (string) $token );
	if ( 2 !== count( $p ) || ! ctype_digit( $p[0] ) ) {
		return false;
	}
	$age = time() - (int) $p[0];
	if ( $age < 3 || $age > 3 * DAY_IN_SECONDS ) {
		return false;
	}
	return hash_equals( substr( hash_hmac( 'sha256', $form . '|' . $p[0], wp_salt( 'nonce' ) ), 0, 20 ), $p[1] );
}

/**
 * Yêu cầu gửi từ chính website (chặn gửi chéo trang – CSRF).
 *
 * @return bool
 */
function th119_same_origin() {
	$src = '';
	if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		$src = esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
	} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$src = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
	}
	if ( '' === $src ) {
		return true; // Một số trình duyệt/ứng dụng không gửi Origin/Referer – các lớp khác vẫn bảo vệ.
	}
	return wp_parse_url( $src, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST );
}

/**
 * Xác minh Cloudflare Turnstile (nếu đã cấu hình).
 *
 * @param string $response Token từ trình duyệt.
 * @return bool
 */
function th119_turnstile_ok( $response ) {
	$secret = th119_decrypt( th119_opt( 'turnstile_secret' ) );
	if ( ! th119_opt( 'turnstile_site' ) || ! $secret ) {
		return true;
	}
	if ( '' === (string) $response ) {
		return false;
	}
	$r = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => 10,
			'body'    => array(
				'secret'   => $secret,
				'response' => $response,
				'remoteip' => th119_ip(),
			),
		)
	);
	if ( is_wp_error( $r ) ) {
		return true; // Cloudflare lỗi mạng: không chặn khách thật, các lớp khác vẫn bảo vệ.
	}
	$body = json_decode( wp_remote_retrieve_body( $r ), true );
	return ! empty( $body['success'] );
}

/**
 * Kiểm tra chống spam chung cho form công khai.
 *
 * @param string $form Tên form.
 * @param array  $in   Dữ liệu gửi.
 * @return string Lỗi hoặc ''.
 */
function th119_spam_check( $form, $in ) {
	if ( ! empty( $in['website'] ) ) {
		return 'spam';
	}
	if ( ! th119_same_origin() ) {
		return 'Yêu cầu không hợp lệ. Vui lòng tải lại trang và gửi lại.';
	}
	if ( ! th119_check_form_token( $form, isset( $in['th_token'] ) ? $in['th_token'] : '' ) ) {
		return 'Phiên làm việc đã hết hạn hoặc gửi quá nhanh. Vui lòng đợi vài giây rồi gửi lại.';
	}
	if ( ! th119_turnstile_ok( isset( $in['cf-turnstile-response'] ) ? $in['cf-turnstile-response'] : '' ) ) {
		return 'Vui lòng xác nhận bạn không phải robot.';
	}
	return '';
}

/* ---------------------------------------------------------------------------
 * Xử lý gửi yêu cầu.
 * ------------------------------------------------------------------------- */

/**
 * Sinh mã yêu cầu duy nhất: YC261106-7K3Q.
 *
 * @param string $prefix Tiền tố.
 * @param string $table  Bảng.
 * @return string
 */
function th119_new_code( $prefix = 'YC', $table = 'tickets' ) {
	global $wpdb;
	$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	for ( $i = 0; $i < 10; $i++ ) {
		$rand = '';
		for ( $j = 0; $j < 4; $j++ ) {
			$rand .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
		}
		$code = $prefix . current_time( 'ymd' ) . '-' . $rand;
		$hit  = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . th119_table( $table ) . ' WHERE code = %s', $code ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		if ( ! $hit ) {
			return $code;
		}
	}
	return $prefix . current_time( 'ymdHis' ) . '-' . wp_rand( 100, 999 );
}

/**
 * Lưu ảnh lỗi khách gửi vào thư mục riêng tư (đã kiểm tra loại file, dung lượng, xoá EXIF/GPS).
 *
 * @param array $files Mảng $_FILES['images'].
 * @return array [ đường dẫn tương đối[], lỗi ].
 */
function th119_store_ticket_images( $files ) {
	$saved = array();
	if ( empty( $files ) || ! isset( $files['name'] ) ) {
		return array( $saved, '' );
	}
	$names = (array) $files['name'];
	$count = 0;
	foreach ( $names as $i => $name ) {
		if ( '' === $name || UPLOAD_ERR_NO_FILE === (int) $files['error'][ $i ] ) {
			continue;
		}
		$count++;
		if ( $count > 3 ) {
			return array( $saved, 'Chỉ gửi tối đa 3 ảnh.' );
		}
		if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] || ! is_uploaded_file( $files['tmp_name'][ $i ] ) ) {
			return array( $saved, 'Tải ảnh không thành công, vui lòng thử lại.' );
		}
		if ( (int) $files['size'][ $i ] > 5 * MB_IN_BYTES ) {
			return array( $saved, 'Mỗi ảnh tối đa 5 MB.' );
		}
		$allowed = array(
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'webp'     => 'image/webp',
		);
		$check   = wp_check_filetype_and_ext( $files['tmp_name'][ $i ], $name, $allowed );
		$info    = @getimagesize( $files['tmp_name'][ $i ] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( empty( $check['ext'] ) || ! $info || ! in_array( $info['mime'], $allowed, true ) ) {
			return array( $saved, 'Chỉ nhận ảnh JPG, PNG hoặc WEBP.' );
		}
		$dir  = th119_private_dir() . '/' . current_time( 'Y/m' );
		wp_mkdir_p( $dir );
		$file = $dir . '/' . bin2hex( random_bytes( 16 ) ) . '.' . ( 'jpeg' === $check['ext'] ? 'jpg' : $check['ext'] );
		// Lưu lại qua thư viện ảnh để bỏ dữ liệu ẩn (EXIF, vị trí GPS) và mã độc chèn trong ảnh.
		$editor = wp_get_image_editor( $files['tmp_name'][ $i ] );
		if ( ! is_wp_error( $editor ) ) {
			$editor->resize( 2000, 2000, false );
			$res = $editor->save( $file );
			if ( is_wp_error( $res ) ) {
				return array( $saved, 'Không lưu được ảnh.' );
			}
			$file = $res['path'];
		} elseif ( ! move_uploaded_file( $files['tmp_name'][ $i ], $file ) ) {
			return array( $saved, 'Không lưu được ảnh.' );
		}
		$saved[] = ltrim( str_replace( th119_private_dir(), '', $file ), '/' );
	}
	return array( $saved, '' );
}

/**
 * Xử lý dữ liệu form yêu cầu hỗ trợ.
 *
 * @param array $in    Dữ liệu (đã wp_unslash).
 * @param array $files $_FILES.
 * @return array [ 'ok' => bool, 'code' => string, 'message' => string, 'errors' => [] ].
 */
function th119_process_ticket( $in, $files ) {
	$spam = th119_spam_check( 'ticket', $in );
	if ( 'spam' === $spam ) {
		// Trả về như thành công để bot không dò được, nhưng không lưu.
		return array( 'ok' => true, 'code' => '', 'message' => 'Đã gửi yêu cầu.' );
	}
	if ( $spam ) {
		return array( 'ok' => false, 'message' => $spam );
	}
	if ( ! th119_rate_ok( 'ticket', 3, 10 * MINUTE_IN_SECONDS, false ) ) {
		return array( 'ok' => false, 'message' => 'Bạn đã gửi nhiều yêu cầu trong thời gian ngắn. Vui lòng gọi hotline ' . th119_opt( 'hotline' ) . ' để được hỗ trợ ngay.' );
	}

	$type   = isset( $in['request_type'] ) && 'booking' === $in['request_type'] ? 'booking' : 'support';
	$data   = array(
		'name'         => sanitize_text_field( isset( $in['name'] ) ? $in['name'] : '' ),
		'phone'        => th119_normalize_phone( isset( $in['phone'] ) ? $in['phone'] : '' ),
		'email'        => sanitize_email( isset( $in['email'] ) ? $in['email'] : '' ),
		'device'       => sanitize_text_field( isset( $in['device'] ) ? $in['device'] : '' ),
		'issue'        => sanitize_textarea_field( isset( $in['issue'] ) ? $in['issue'] : '' ),
		'area'         => sanitize_text_field( isset( $in['area'] ) ? $in['area'] : '' ),
		'contact_time' => sanitize_text_field( isset( $in['contact_time'] ) ? $in['contact_time'] : '' ),
		'appointment'  => '',
		'request_type' => $type,
	);
	if ( ! empty( $in['device_other'] ) && ( 'Khác' === $data['device'] || '' === $data['device'] ) ) {
		$data['device'] = sanitize_text_field( $in['device_other'] );
	}
	if ( 'booking' === $type && ! empty( $in['appointment'] ) ) {
		$ts = strtotime( sanitize_text_field( $in['appointment'] ) );
		if ( $ts ) {
			$data['appointment'] = gmdate( 'Y-m-d H:i', $ts );
		}
	}

	$errors = array();
	if ( mb_strlen( $data['name'] ) < 2 || mb_strlen( $data['name'] ) > 100 ) {
		$errors['name'] = 'Vui lòng nhập họ tên.';
	}
	if ( '' === $data['phone'] ) {
		$errors['phone'] = 'Số điện thoại chưa đúng (10 số, bắt đầu bằng 0).';
	}
	if ( mb_strlen( $data['issue'] ) < 10 ) {
		$errors['issue'] = 'Vui lòng mô tả sự cố (ít nhất 10 ký tự).';
	}
	if ( mb_strlen( $data['issue'] ) > 3000 ) {
		$errors['issue'] = 'Mô tả tối đa 3000 ký tự.';
	}
	if ( 'booking' === $type && '' === $data['appointment'] ) {
		$errors['appointment'] = 'Vui lòng chọn thời gian hẹn.';
	}
	if ( $data['appointment'] && strtotime( $data['appointment'] ) < current_time( 'timestamp' ) - HOUR_IN_SECONDS ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$errors['appointment'] = 'Thời gian hẹn đã qua.';
	}
	if ( preg_match_all( '#https?://#i', $data['issue'] ) > 2 ) {
		$errors['issue'] = 'Nội dung chứa quá nhiều đường link.';
	}
	if ( $errors ) {
		return array( 'ok' => false, 'message' => 'Vui lòng kiểm tra lại thông tin.', 'errors' => $errors );
	}

	list( $images, $img_err ) = th119_store_ticket_images( isset( $files['images'] ) ? $files['images'] : array() );
	if ( $img_err ) {
		foreach ( $images as $rel ) {
			wp_delete_file( th119_private_dir() . '/' . $rel );
		}
		return array( 'ok' => false, 'message' => $img_err, 'errors' => array( 'images' => $img_err ) );
	}

	th119_rate_ok( 'ticket', 3, 10 * MINUTE_IN_SECONDS, true );

	global $wpdb;
	$code = th119_new_code( 'booking' === $type ? 'LH' : 'YC' );
	$now  = th119_now();
	$src  = isset( $in['source_url'] ) ? esc_url_raw( $in['source_url'] ) : '';
	$ok   = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		th119_table( 'tickets' ),
		array_merge(
			$data,
			array(
				'code'       => $code,
				'images'     => wp_json_encode( $images ),
				'status'     => 'new',
				'source_url' => substr( $src, 0, 255 ),
				'channel'    => th119_traffic_source( isset( $in['ref'] ) ? esc_url_raw( $in['ref'] ) : '' ),
				'ip_hash'    => th119_ip_hash(),
				'created_at' => $now,
				'updated_at' => $now,
			)
		)
	);
	if ( ! $ok ) {
		return array( 'ok' => false, 'message' => 'Hệ thống đang bận, vui lòng gọi hotline ' . th119_opt( 'hotline' ) . '.' );
	}
	$id = (int) $wpdb->insert_id;
	th119_add_log( 'ticket', $id, 'new', '', 'Khách gửi yêu cầu qua website.', true );
	th119_stat_hit( 'booking' === $type ? 'booking' : 'ticket', '' );
	wp_cache_delete( 'th119_new_tickets' );

	$body  = ( 'booking' === $type ? 'Lịch hẹn mới' : 'Yêu cầu hỗ trợ mới' ) . " $code\n\n";
	$body .= "Họ tên: {$data['name']}\nĐiện thoại: {$data['phone']}\nThiết bị: {$data['device']}\nKhu vực: {$data['area']}\nThời gian liên hệ: {$data['contact_time']}\n";
	$body .= $data['appointment'] ? "Hẹn lúc: {$data['appointment']}\n" : '';
	$body .= "\nSự cố:\n{$data['issue']}\n\nẢnh đính kèm: " . count( $images ) . "\nTrang gửi: $src\n\nXử lý: " . admin_url( 'admin.php?page=th119-tickets&view=' . $id );
	th119_notify_admins( ( 'booking' === $type ? 'Lịch hẹn ' : 'Yêu cầu hỗ trợ ' ) . $code . ' – ' . $data['name'], $body );

	do_action( 'th119_ticket_created', $id, $code, $data );

	return array(
		'ok'      => true,
		'code'    => $code,
		'message' => sprintf( 'Đã nhận yêu cầu. Mã yêu cầu của bạn: %s. Kỹ thuật viên sẽ liên hệ số %s sớm nhất. Bạn có thể tra cứu tiến độ bằng mã này và số điện thoại.', $code, th119_mask_phone( $data['phone'] ) ),
	);
}

/**
 * AJAX gửi yêu cầu.
 */
function th119_ajax_submit_ticket() {
	$in  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- token HMAC + làm sạch trong th119_process_ticket.
	$res = th119_process_ticket( $in, $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	wp_send_json( $res, $res['ok'] ? 200 : 400 );
}
add_action( 'wp_ajax_th119_ticket', 'th119_ajax_submit_ticket' );
add_action( 'wp_ajax_nopriv_th119_ticket', 'th119_ajax_submit_ticket' );

/**
 * Gửi form khi trình duyệt tắt JavaScript.
 */
function th119_post_submit_ticket() {
	$in   = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- token HMAC + làm sạch trong th119_process_ticket.
	$res  = th119_process_ticket( $in, $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$back = ! empty( $in['source_url'] ) ? esc_url_raw( $in['source_url'] ) : home_url( '/' );
	$back = wp_validate_redirect( $back, home_url( '/' ) );
	$args = $res['ok'] ? array( 'th_ok' => rawurlencode( $res['code'] ) ) : array( 'th_err' => rawurlencode( $res['message'] ) );
	wp_safe_redirect( add_query_arg( $args, $back ) . '#th-ticket-form' );
	exit;
}
add_action( 'admin_post_th119_ticket', 'th119_post_submit_ticket' );
add_action( 'admin_post_nopriv_th119_ticket', 'th119_post_submit_ticket' );

/**
 * Thêm 1 dòng lịch sử xử lý.
 *
 * @param string $type   ticket|warranty.
 * @param int    $id     ID.
 * @param string $to     Trạng thái mới.
 * @param string $from   Trạng thái cũ.
 * @param string $note   Ghi chú.
 * @param bool   $public Khách xem được.
 */
function th119_add_log( $type, $id, $to, $from = '', $note = '', $public = false ) {
	global $wpdb;
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		th119_table( 'logs' ),
		array(
			'object_type' => $type,
			'object_id'   => (int) $id,
			'user_id'     => get_current_user_id(),
			'status_from' => $from,
			'status_to'   => $to,
			'note'        => $note,
			'is_public'   => $public ? 1 : 0,
			'created_at'  => th119_now(),
		)
	);
}

/**
 * Lịch sử của 1 đối tượng.
 *
 * @param string $type        Loại.
 * @param int    $id          ID.
 * @param bool   $public_only Chỉ dòng khách xem được.
 * @return array
 */
function th119_get_logs( $type, $id, $public_only = false ) {
	global $wpdb;
	$sql = 'SELECT * FROM ' . th119_table( 'logs' ) . ' WHERE object_type = %s AND object_id = %d' . ( $public_only ? ' AND is_public = 1' : '' ) . ' ORDER BY id DESC';
	return $wpdb->get_results( $wpdb->prepare( $sql, $type, $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
}

/* ---------------------------------------------------------------------------
 * Form ngoài website.
 * ------------------------------------------------------------------------- */

/**
 * Form gửi yêu cầu hỗ trợ / đặt lịch.
 *
 * @param array $atts type="support|booking", title, compact="1".
 * @return string
 */
function th119_ticket_form_shortcode( $atts ) {
	$a = shortcode_atts(
		array(
			'type'    => 'support',
			'title'   => '',
			'compact' => '0',
		),
		$atts,
		'th119_support_form'
	);
	$booking = 'booking' === $a['type'];
	$title   = $a['title'] ? $a['title'] : ( $booking ? 'Đặt lịch kỹ thuật viên' : 'Gửi yêu cầu hỗ trợ kỹ thuật' );
	$devices = array( 'Laptop', 'Máy tính để bàn (PC)', 'Máy in / máy scan', 'Mạng / Wi-Fi / router', 'Phần mềm', 'Màn hình', 'Thiết bị văn phòng', 'Khác' );
	$times   = array( 'Càng sớm càng tốt', 'Buổi sáng (8h – 12h)', 'Buổi chiều (13h – 17h)', 'Buổi tối (18h – 20h)' );
	$ok      = isset( $_GET['th_ok'] ) ? sanitize_text_field( wp_unslash( $_GET['th_ok'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$err     = isset( $_GET['th_err'] ) ? sanitize_text_field( wp_unslash( $_GET['th_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	th119_enqueue_front();

	ob_start();
	?>
	<form id="th-ticket-form" class="th-form th-ticket-form<?php echo '1' === $a['compact'] ? ' is-compact' : ''; ?>" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<h3 class="th-form__title"><?php echo esc_html( $title ); ?></h3>
		<div class="th-form__msg" role="status" aria-live="polite"<?php echo $ok || $err ? '' : ' hidden'; ?>>
			<?php
			if ( $ok ) {
				echo '<div class="th-alert th-alert--ok">Đã nhận yêu cầu. Mã yêu cầu: <strong>' . esc_html( $ok ) . '</strong>. Kỹ thuật viên sẽ liên hệ bạn sớm nhất.</div>';
			} elseif ( $err ) {
				echo '<div class="th-alert th-alert--err">' . esc_html( $err ) . '</div>';
			}
			?>
		</div>
		<input type="hidden" name="action" value="th119_ticket">
		<input type="hidden" name="request_type" value="<?php echo esc_attr( $booking ? 'booking' : 'support' ); ?>">
		<input type="hidden" name="th_token" value="<?php echo esc_attr( th119_form_token( 'ticket' ) ); ?>">
		<input type="hidden" name="source_url" value="">
		<input type="hidden" name="ref" value="">
		<div class="th-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
		<div class="th-grid">
			<p class="th-field"><label for="th-name">Họ tên <span>*</span></label><input id="th-name" name="name" type="text" required maxlength="100" autocomplete="name"></p>
			<p class="th-field"><label for="th-phone">Số điện thoại <span>*</span></label><input id="th-phone" name="phone" type="tel" required inputmode="tel" pattern="[0-9 +.]{9,15}" autocomplete="tel"></p>
			<p class="th-field"><label for="th-device">Thiết bị</label>
				<select id="th-device" name="device"><option value="">— Chọn thiết bị —</option>
				<?php foreach ( $devices as $d ) : ?>
					<option><?php echo esc_html( $d ); ?></option>
				<?php endforeach; ?>
				</select>
			</p>
			<p class="th-field"><label for="th-device-other">Hãng / model (nếu biết)</label><input id="th-device-other" name="device_other" type="text" maxlength="120" placeholder="Ví dụ: Dell Latitude 5420"></p>
			<p class="th-field"><label for="th-area">Khu vực</label><input id="th-area" name="area" type="text" maxlength="120" placeholder="Ví dụ: Tân Phú, Tân Bình…"></p>
			<?php if ( $booking ) : ?>
				<p class="th-field"><label for="th-appointment">Thời gian hẹn <span>*</span></label><input id="th-appointment" name="appointment" type="datetime-local" required></p>
			<?php else : ?>
				<p class="th-field"><label for="th-time">Thời gian thuận tiện liên hệ</label>
					<select id="th-time" name="contact_time">
					<?php foreach ( $times as $t ) : ?>
						<option><?php echo esc_html( $t ); ?></option>
					<?php endforeach; ?>
					</select>
				</p>
			<?php endif; ?>
		</div>
		<p class="th-field"><label for="th-issue">Nội dung sự cố <span>*</span></label><textarea id="th-issue" name="issue" rows="4" required maxlength="3000" placeholder="Máy bị gì, từ khi nào, đã thử cách nào, thông báo lỗi hiện trên màn hình…"></textarea></p>
		<p class="th-field"><label for="th-images">Hình ảnh lỗi (tối đa 3 ảnh, mỗi ảnh ≤ 5 MB)</label><input id="th-images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></p>
		<?php if ( th119_opt( 'turnstile_site' ) ) : ?>
			<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( th119_opt( 'turnstile_site' ) ); ?>"></div>
		<?php endif; ?>
		<p class="th-form__privacy">Thông tin chỉ dùng để liên hệ hỗ trợ, không công khai. <?php echo th119_privacy_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<button type="submit" class="th-btn th-btn--primary"><?php echo esc_html( $booking ? 'Đặt lịch' : 'Gửi yêu cầu' ); ?></button>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode( 'th119_support_form', 'th119_ticket_form_shortcode' );

/**
 * Link chính sách bảo mật.
 *
 * @return string
 */
function th119_privacy_link() {
	$url = get_privacy_policy_url();
	if ( ! $url ) {
		$p   = get_page_by_path( 'chinh-sach-bao-mat' );
		$url = $p ? get_permalink( $p ) : '';
	}
	return $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Chính sách bảo mật</a>' : '';
}

/**
 * CSS/JS ngoài website (chỉ nạp khi trang có form/nút).
 */
function th119_enqueue_front() {
	wp_enqueue_style( 'th119-front', TH119_URL . 'assets/css/front.css', array(), TH119_VERSION );
	wp_enqueue_script( 'th119-front', TH119_URL . 'assets/js/front.js', array(), TH119_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	if ( th119_opt( 'turnstile_site' ) ) {
		wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
}

/* ---------------------------------------------------------------------------
 * Quản trị.
 * ------------------------------------------------------------------------- */

/**
 * Lấy 1 yêu cầu.
 *
 * @param int $id ID.
 * @return object|null
 */
function th119_get_ticket( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . th119_table( 'tickets' ) . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
}

/**
 * Trang Yêu cầu hỗ trợ.
 */
function th119_tickets_page() {
	if ( ! current_user_can( 'th119_manage_tickets' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$view = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $view ) {
		th119_ticket_detail( $view );
		return;
	}
	global $wpdb;
	$t        = th119_table( 'tickets' );
	$statuses = th119_ticket_statuses();
	$status   = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$s        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$paged    = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per      = 30;

	$where = array( '1=1' );
	$args  = array();
	if ( isset( $statuses[ $status ] ) ) {
		$where[] = 'status = %s';
		$args[]  = $status;
	}
	if ( '' !== $s ) {
		$like    = '%' . $wpdb->esc_like( $s ) . '%';
		$where[] = '(code LIKE %s OR name LIKE %s OR phone LIKE %s OR device LIKE %s)';
		array_push( $args, $like, $like, $like, $like );
	}
	$w     = implode( ' AND ', $where );
	$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE $w", $args ) : "SELECT COUNT(*) FROM $t WHERE $w" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE $w ORDER BY id DESC LIMIT %d OFFSET %d", array_merge( $args, array( $per, ( $paged - 1 ) * $per ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	$counts = $wpdb->get_results( "SELECT status, COUNT(*) n FROM $t GROUP BY status", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery

	$base = admin_url( 'admin.php?page=th119-tickets' );
	echo '<div class="wrap th119-wrap"><h1 class="wp-heading-inline">Yêu cầu hỗ trợ</h1>';
	printf( ' <a class="page-title-action" href="%s">Xuất CSV</a>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=th119_export&what=tickets' ), 'th119_export' ) ) );
	echo '<p class="description">Đặt form lên trang bằng shortcode <code>[th119_support_form]</code> (đặt lịch: <code>[th119_support_form type="booking"]</code>).</p>';
	echo '<ul class="subsubsub"><li><a href="' . esc_url( $base ) . '"' . ( '' === $status ? ' class="current"' : '' ) . '>Tất cả</a></li>';
	foreach ( $statuses as $k => $label ) {
		$n = isset( $counts[ $k ] ) ? (int) $counts[ $k ]->n : 0;
		printf( ' | <li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( add_query_arg( 'status', $k, $base ) ), $status === $k ? ' class="current"' : '', esc_html( $label ), (int) $n );
	}
	echo '</ul>';
	echo '<form method="get" class="th119-search"><input type="hidden" name="page" value="th119-tickets"><input type="search" name="s" value="' . esc_attr( $s ) . '" placeholder="Mã, tên, SĐT, thiết bị"> <button class="button">Tìm</button></form>';
	echo '<table class="widefat striped th119-table"><thead><tr><th>Mã</th><th>Khách hàng</th><th>Thiết bị</th><th>Sự cố</th><th>Loại</th><th>Trạng thái</th><th>Nguồn</th><th>Ngày gửi</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="8">Chưa có yêu cầu nào.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$url = add_query_arg( 'view', $r->id, $base );
		printf(
			'<tr><td><a href="%1$s"><strong>%2$s</strong></a></td><td>%3$s<br><a href="tel:%4$s">%4$s</a></td><td>%5$s</td><td>%6$s</td><td>%7$s</td><td><span class="th119-badge th119-badge--%8$s">%9$s</span></td><td>%10$s</td><td>%11$s</td></tr>',
			esc_url( $url ),
			esc_html( $r->code ),
			esc_html( $r->name ),
			esc_html( $r->phone ),
			esc_html( $r->device ),
			esc_html( wp_trim_words( $r->issue, 14 ) ),
			'booking' === $r->request_type ? 'Đặt lịch<br>' . esc_html( $r->appointment ) : 'Hỗ trợ',
			esc_attr( $r->status ),
			esc_html( isset( $statuses[ $r->status ] ) ? $statuses[ $r->status ] : $r->status ),
			esc_html( $r->channel ),
			esc_html( th119_date( $r->created_at, true ) )
		);
	}
	echo '</tbody></table>';
	$pages = (int) ceil( $total / $per );
	if ( $pages > 1 ) {
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ) ) ) . '</div></div>';
	}
	echo '</div>';
}

/**
 * Chi tiết 1 yêu cầu.
 *
 * @param int $id ID.
 */
function th119_ticket_detail( $id ) {
	$r = th119_get_ticket( $id );
	if ( ! $r ) {
		echo '<div class="wrap"><p>Không tìm thấy yêu cầu.</p></div>';
		return;
	}
	$statuses = th119_ticket_statuses();
	$images   = (array) json_decode( (string) $r->images, true );
	echo '<div class="wrap th119-wrap"><h1>Yêu cầu ' . esc_html( $r->code ) . ' <span class="th119-badge th119-badge--' . esc_attr( $r->status ) . '">' . esc_html( $statuses[ $r->status ] ?? $r->status ) . '</span></h1>';
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=th119-tickets' ) ) . '">← Danh sách</a></p>';
	if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success"><p>Đã cập nhật.</p></div>';
	}
	echo '<div class="th119-cols"><div class="th119-card"><h2>Thông tin khách hàng</h2><table class="form-table">';
	$rows = array(
		'Loại'              => 'booking' === $r->request_type ? 'Đặt lịch' : 'Yêu cầu hỗ trợ',
		'Họ tên'            => $r->name,
		'Điện thoại'        => $r->phone,
		'Email'             => $r->email,
		'Thiết bị'          => $r->device,
		'Khu vực'           => $r->area,
		'Liên hệ lúc'       => $r->contact_time,
		'Hẹn lúc'           => $r->appointment,
		'Trang gửi'         => $r->source_url,
		'Nguồn truy cập'    => $r->channel,
		'Ngày gửi'          => th119_date( $r->created_at, true ),
	);
	foreach ( $rows as $k => $v ) {
		if ( '' === (string) $v ) {
			continue;
		}
		echo '<tr><th>' . esc_html( $k ) . '</th><td>' . esc_html( $v ) . '</td></tr>';
	}
	echo '</table>';
	printf( '<p><a class="button" href="tel:%1$s">Gọi %1$s</a> <a class="button" target="_blank" rel="noopener" href="https://zalo.me/%1$s">Zalo</a></p>', esc_attr( $r->phone ) );
	echo '<h3>Nội dung sự cố</h3><div class="th119-issue">' . nl2br( esc_html( $r->issue ) ) . '</div>';
	if ( $images ) {
		echo '<h3>Hình ảnh</h3><div class="th119-thumbs">';
		foreach ( array_keys( $images ) as $i ) {
			$src = wp_nonce_url( admin_url( 'admin-post.php?action=th119_ticket_image&id=' . $r->id . '&i=' . $i ), 'th119_img_' . $r->id );
			printf( '<a href="%1$s" target="_blank"><img src="%1$s" alt="Ảnh lỗi %2$d" loading="lazy"></a>', esc_url( $src ), (int) $i + 1 );
		}
		echo '</div>';
	}
	echo '</div><div class="th119-card"><h2>Cập nhật xử lý</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'th119_ticket_update_' . $r->id );
	echo '<input type="hidden" name="action" value="th119_ticket_update"><input type="hidden" name="id" value="' . (int) $r->id . '">';
	echo '<p><label>Trạng thái<br><select name="status">';
	foreach ( $statuses as $k => $label ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $r->status, $k, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></label></p><p><label>Kỹ thuật viên phụ trách<br>';
	wp_dropdown_users(
		array(
			'name'              => 'assigned_to',
			'selected'          => (int) $r->assigned_to,
			'show_option_none'  => '— Chưa giao —',
			'option_none_value' => 0,
			'capability'        => array( 'th119_manage_tickets' ),
		)
	);
	echo '</label></p><p><label>Ghi chú xử lý<br><textarea name="note" rows="4" class="large-text"></textarea></label></p>';
	echo '<p><label><input type="checkbox" name="is_public" value="1"> Khách xem được ghi chú này khi tra cứu</label></p>';
	submit_button( 'Lưu cập nhật', 'primary', 'submit', false );
	echo '</form>';
	printf( '<p style="margin-top:16px"><a class="button" href="%s">Tạo phiếu bảo hành từ yêu cầu này</a></p>', esc_url( admin_url( 'admin.php?page=th119-warranty&edit=0&from_ticket=' . $r->id ) ) );
	echo '<h2>Lịch sử xử lý</h2>';
	th119_render_logs( th119_get_logs( 'ticket', $r->id ), $statuses );
	if ( current_user_can( 'manage_options' ) ) {
		printf( '<p style="margin-top:24px"><a class="button-link-delete" href="%s" onclick="return confirm(\'Xoá vĩnh viễn yêu cầu này (chỉ dùng cho spam)?\')">Xoá yêu cầu (spam)</a></p>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=th119_ticket_delete&id=' . $r->id ), 'th119_ticket_delete_' . $r->id ) ) );
	}
	echo '</div></div></div>';
}

/**
 * In bảng lịch sử.
 *
 * @param array $logs     Dòng.
 * @param array $statuses Tên trạng thái.
 */
function th119_render_logs( $logs, $statuses ) {
	if ( ! $logs ) {
		echo '<p>Chưa có lịch sử.</p>';
		return;
	}
	echo '<ol class="th119-timeline">';
	foreach ( $logs as $l ) {
		$u    = $l->user_id ? get_userdata( $l->user_id ) : null;
		$who  = $u ? $u->display_name : 'Khách hàng / hệ thống';
		$st   = $l->status_to && $l->status_to !== $l->status_from ? ( $statuses[ $l->status_to ] ?? $l->status_to ) : '';
		printf(
			'<li><strong>%1$s</strong> · %2$s%3$s%4$s%5$s</li>',
			esc_html( th119_date( $l->created_at, true ) ),
			esc_html( $who ),
			$st ? ' · <em>' . esc_html( $st ) . '</em>' : '',
			$l->note ? '<br>' . nl2br( esc_html( $l->note ) ) : '',
			$l->is_public ? ' <span class="th119-badge">khách xem được</span>' : ''
		);
	}
	echo '</ol>';
}

/**
 * Lưu cập nhật trạng thái.
 */
function th119_ticket_update() {
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	check_admin_referer( 'th119_ticket_update_' . $id );
	if ( ! current_user_can( 'th119_manage_tickets' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$r = th119_get_ticket( $id );
	if ( ! $r ) {
		wp_die( 'Không tìm thấy.' );
	}
	$statuses = th119_ticket_statuses();
	$status   = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : $r->status;
	$status   = isset( $statuses[ $status ] ) ? $status : $r->status;
	$assigned = isset( $_POST['assigned_to'] ) ? absint( $_POST['assigned_to'] ) : 0;
	$note     = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$public   = ! empty( $_POST['is_public'] );
	if ( $assigned && ! user_can( $assigned, 'th119_manage_tickets' ) ) {
		$assigned = 0;
	}
	global $wpdb;
	$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		th119_table( 'tickets' ),
		array(
			'status'      => $status,
			'assigned_to' => $assigned,
			'updated_at'  => th119_now(),
		),
		array( 'id' => $id )
	);
	if ( (int) $assigned !== (int) $r->assigned_to && $assigned ) {
		$u     = get_userdata( $assigned );
		$note .= ( $note ? "\n" : '' ) . 'Giao cho: ' . ( $u ? $u->display_name : '#' . $assigned );
	}
	if ( $status !== $r->status || '' !== $note ) {
		th119_add_log( 'ticket', $id, $status, $r->status, $note, $public || $status !== $r->status );
	}
	wp_cache_delete( 'th119_new_tickets' );
	th119_log_activity( 'ticket_update', $r->code, $r->status . ' → ' . $status );
	wp_safe_redirect( admin_url( 'admin.php?page=th119-tickets&view=' . $id . '&updated=1' ) );
	exit;
}
add_action( 'admin_post_th119_ticket_update', 'th119_ticket_update' );

/**
 * Xoá yêu cầu spam (chỉ quản trị viên).
 */
function th119_ticket_delete() {
	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	check_admin_referer( 'th119_ticket_delete_' . $id );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$r = th119_get_ticket( $id );
	if ( $r ) {
		foreach ( (array) json_decode( (string) $r->images, true ) as $rel ) {
			$path = th119_private_dir() . '/' . $rel;
			if ( false === strpos( $rel, '..' ) && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		global $wpdb;
		$wpdb->delete( th119_table( 'tickets' ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( th119_table( 'logs' ), array( 'object_type' => 'ticket', 'object_id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		th119_log_activity( 'ticket_delete', $r->code, 'Xoá yêu cầu (spam)' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=th119-tickets' ) );
	exit;
}
add_action( 'admin_post_th119_ticket_delete', 'th119_ticket_delete' );

/**
 * Xem ảnh lỗi (chỉ người có quyền; ảnh không truy cập trực tiếp được).
 */
function th119_ticket_image() {
	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$i  = isset( $_GET['i'] ) ? absint( $_GET['i'] ) : 0;
	check_admin_referer( 'th119_img_' . $id );
	if ( ! current_user_can( 'th119_manage_tickets' ) ) {
		wp_die( 'Không có quyền.' );
	}
	$r = th119_get_ticket( $id );
	$images = $r ? (array) json_decode( (string) $r->images, true ) : array();
	if ( ! isset( $images[ $i ] ) || false !== strpos( $images[ $i ], '..' ) ) {
		wp_die( 'Không tìm thấy ảnh.' );
	}
	$path = th119_private_dir() . '/' . $images[ $i ];
	$type = wp_check_filetype( $path );
	if ( ! file_exists( $path ) || 0 !== strpos( (string) $type['type'], 'image/' ) ) {
		wp_die( 'Không tìm thấy ảnh.' );
	}
	nocache_headers();
	header( 'Content-Type: ' . $type['type'] );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}
add_action( 'admin_post_th119_ticket_image', 'th119_ticket_image' );

/**
 * Xuất CSV (yêu cầu hỗ trợ / bảo hành).
 */
function th119_export_csv() {
	check_admin_referer( 'th119_export' );
	$what = isset( $_GET['what'] ) ? sanitize_key( $_GET['what'] ) : '';
	$cap  = 'warranty' === $what ? 'th119_manage_warranty' : 'th119_manage_tickets';
	if ( ! current_user_can( $cap ) || ! in_array( $what, array( 'tickets', 'warranty' ), true ) ) {
		wp_die( 'Không có quyền.' );
	}
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . th119_table( $what ) . ' ORDER BY id DESC', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	th119_log_activity( 'export', $what, count( $rows ) . ' dòng' );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=th119-' . $what . '-' . gmdate( 'Ymd' ) . '.csv' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- BOM để Excel đọc đúng tiếng Việt.
	if ( $rows ) {
		fputcsv( $out, array_keys( $rows[0] ) );
		foreach ( $rows as $row ) {
			// Chặn công thức Excel (CSV injection).
			$row = array_map(
				function ( $v ) {
					$v = (string) $v;
					return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v;
				},
				$row
			);
			fputcsv( $out, $row );
		}
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_th119_export', 'th119_export_csv' );
