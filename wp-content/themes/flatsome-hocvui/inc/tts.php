<?php
/**
 * Giọng đọc AI tự nhiên cho Học Vui: máy chủ gọi dịch vụ chuyển văn bản thành giọng nói chính thức
 * (Microsoft Azure Speech – giọng HoaiMy/NamMinh, hoặc Google Cloud Text-to-Speech), lưu file MP3 vào
 * thư mục uploads để lần sau phát ngay, không tốn phí lại. Chưa cấu hình thì trang dùng giọng có sẵn của trình duyệt.
 *
 * Cài đặt: Giao diện → Giọng đọc AI.
 *
 * @package Flatsome_Hocvui
 */

defined( 'ABSPATH' ) || exit;

const HV_TTS_MAX_CHARS = 400;   // Một lần đọc (một câu) tối đa.
const HV_TTS_IP_HOUR   = 300;   // Số câu MỚI (chưa có sẵn) mỗi IP được tạo trong 1 giờ.

/**
 * Cài đặt giọng đọc (gộp mặc định).
 *
 * @return array
 */
function hv_tts_settings() {
	$s = get_option( 'hv_tts', array() );
	return wp_parse_args(
		is_array( $s ) ? $s : array(),
		array(
			'provider'  => '',
			'key'       => '',
			'region'    => 'southeastasia',
			'vi_female' => '',
			'vi_male'   => '',
			'en_female' => '',
			'en_male'   => '',
			'budget'    => 200000,
		)
	);
}

/**
 * Giọng mặc định của từng dịch vụ.
 *
 * @param string $provider azure | google.
 * @return array
 */
function hv_tts_default_voices( $provider ) {
	if ( 'google' === $provider ) {
		return array( 'vi_female' => 'vi-VN-Neural2-A', 'vi_male' => 'vi-VN-Neural2-D', 'en_female' => 'en-US-Neural2-F', 'en_male' => 'en-US-Neural2-D' );
	}
	return array( 'vi_female' => 'vi-VN-HoaiMyNeural', 'vi_male' => 'vi-VN-NamMinhNeural', 'en_female' => 'en-US-JennyNeural', 'en_male' => 'en-US-GuyNeural' );
}

/**
 * Đã bật giọng đọc AI chưa.
 *
 * @return bool
 */
function hv_tts_enabled() {
	$s = hv_tts_settings();
	return in_array( $s['provider'], array( 'azure', 'google' ), true ) && '' !== $s['key'];
}

/**
 * Cấu hình gửi cho hocvui.js (window.hvConfig.tts).
 *
 * @return array|false
 */
function hv_tts_config() {
	return hv_tts_enabled() ? array( 'url' => esc_url_raw( rest_url( 'hocvui/v1/tts' ) ) ) : false;
}

/**
 * Thư mục lưu file âm thanh.
 *
 * @return array [ đường dẫn, URL ]
 */
function hv_tts_dir() {
	$up = wp_upload_dir( null, false );
	return array( trailingslashit( $up['basedir'] ) . 'hocvui-tts', trailingslashit( $up['baseurl'] ) . 'hocvui-tts' );
}

/**
 * Thông số đọc theo người đọc, ngôn ngữ, tốc độ.
 *
 * @param string $who  female | male | owl.
 * @param string $lang vi | en.
 * @param string $rate slow | normal | fast.
 * @return array [ tên giọng, tốc độ (azure %), tốc độ (google), cao độ (azure %), cao độ (google bán cung) ]
 */
function hv_tts_voice( $who, $lang, $rate ) {
	$s     = hv_tts_settings();
	$def   = hv_tts_default_voices( $s['provider'] );
	$slot  = $lang . '_' . ( 'male' === $who ? 'male' : 'female' );
	$name  = '' !== $s[ $slot ] ? $s[ $slot ] : $def[ $slot ];
	$rates = array(
		'slow'   => array( '-22%', 0.78 ),
		'normal' => array( '-10%', 0.9 ),
		'fast'   => array( '+5%', 1.05 ),
	);
	$r     = isset( $rates[ $rate ] ) ? $rates[ $rate ] : $rates['normal'];
	// Cú Bông: giọng cô giáo cao hơn một chút, kể chuyện cho bé lớp 1–2.
	$pitch = 'owl' === $who ? array( '+8%', 2.0 ) : array( '+0%', 0.0 );
	if ( 'owl' === $who ) { // Kể chuyện: tươi, nhanh hơn một chút.
		$r = array( sprintf( '%+d%%', (int) $r[0] + 4 ), $r[1] + 0.04 );
	}
	return array( $name, $r[0], $r[1], $pitch[0], $pitch[1] );
}

/**
 * Gọi dịch vụ tạo âm thanh MP3.
 *
 * @param string $text  Câu cần đọc.
 * @param string $lang  vi | en.
 * @param array  $voice Kết quả hv_tts_voice().
 * @return string|WP_Error Dữ liệu MP3.
 */
function hv_tts_synthesize( $text, $lang, $voice ) {
	$s = hv_tts_settings();
	if ( 'google' === $s['provider'] ) {
		$res = wp_remote_post(
			'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . rawurlencode( $s['key'] ),
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode(
					array(
						'input'       => array( 'text' => $text ),
						'voice'       => array( 'languageCode' => 'en' === $lang ? 'en-US' : 'vi-VN', 'name' => $voice[0] ),
						// Giọng Chirp 3 HD (tự nhiên nhất của Google) không nhận chỉnh cao độ.
						'audioConfig' => false !== stripos( $voice[0], 'Chirp' ) ? array( 'audioEncoding' => 'MP3', 'speakingRate' => $voice[2] ) : array( 'audioEncoding' => 'MP3', 'speakingRate' => $voice[2], 'pitch' => $voice[4] ),
					)
				),
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( 200 !== wp_remote_retrieve_response_code( $res ) || empty( $body['audioContent'] ) ) {
			return new WP_Error( 'hv_tts', 'Google TTS: ' . ( isset( $body['error']['message'] ) ? $body['error']['message'] : wp_remote_retrieve_response_code( $res ) ) );
		}
		return base64_decode( $body['audioContent'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- dữ liệu âm thanh từ Google.
	}
	$region = preg_replace( '/[^a-z0-9]/', '', strtolower( $s['region'] ) );
	// Ngắt nghỉ giữa các câu dài hơn một chút (350 ms) cho bé kịp nghe, giống cô giáo đọc bài.
	$ssml   = '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xmlns:mstts="https://www.w3.org/2001/mstts" xml:lang="' . ( 'en' === $lang ? 'en-US' : 'vi-VN' ) . '">'
		. '<voice name="' . esc_attr( $voice[0] ) . '"><mstts:silence type="Sentenceboundary" value="350ms"/><prosody rate="' . $voice[1] . '" pitch="' . $voice[3] . '">'
		. htmlspecialchars( $text, ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</prosody></voice></speak>';
	$res    = wp_remote_post(
		'https://' . $region . '.tts.speech.microsoft.com/cognitiveservices/v1',
		array(
			'timeout' => 15,
			'headers' => array(
				'Ocp-Apim-Subscription-Key' => $s['key'],
				'Content-Type'              => 'application/ssml+xml',
				'X-Microsoft-OutputFormat'  => 'audio-24khz-48kbitrate-mono-mp3',
				'User-Agent'                => 'HocVui',
			),
			'body'    => $ssml,
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = wp_remote_retrieve_response_code( $res );
	$mp3  = wp_remote_retrieve_body( $res );
	if ( 200 !== $code || strlen( $mp3 ) < 200 ) {
		return new WP_Error( 'hv_tts', 'Azure TTS: lỗi ' . $code . ( 401 === $code ? ' (sai khoá hoặc vùng)' : '' ) );
	}
	return $mp3;
}

/**
 * Lấy (hoặc tạo rồi lưu) file MP3 của một câu.
 *
 * @param string $text Câu.
 * @param string $who  Người đọc.
 * @param string $lang Ngôn ngữ.
 * @param string $rate Tốc độ.
 * @param bool   $limit Áp giới hạn chống lạm dụng (khách truy cập).
 * @return string|WP_Error URL file MP3.
 */
function hv_tts_file( $text, $who, $lang, $rate, $limit = true ) {
	$s     = hv_tts_settings();
	$voice = hv_tts_voice( $who, $lang, $rate );
	$hash  = md5( $s['provider'] . '|' . implode( '|', $voice ) . '|' . $lang . '|' . $text );
	list( $dir, $url ) = hv_tts_dir();
	$sub  = substr( $hash, 0, 2 );
	$file = $dir . '/' . $sub . '/' . $hash . '.mp3';
	if ( file_exists( $file ) ) {
		return $url . '/' . $sub . '/' . $hash . '.mp3';
	}
	if ( $limit ) {
		// Chống lạm dụng: mỗi IP tạo tối đa HV_TTS_IP_HOUR câu mới/giờ; cả site tối đa "budget" ký tự/ngày.
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$lock = 'hv_tts_ip_' . md5( $ip );
		$n    = (int) get_transient( $lock );
		if ( $n >= HV_TTS_IP_HOUR ) {
			return new WP_Error( 'hv_tts_limit', 'Quá nhiều yêu cầu, thử lại sau.' );
		}
		set_transient( $lock, $n + 1, HOUR_IN_SECONDS );
		$day  = 'hv_tts_used_' . gmdate( 'Ymd' );
		$used = (int) get_option( $day, 0 );
		if ( $used + mb_strlen( $text ) > (int) $s['budget'] ) {
			return new WP_Error( 'hv_tts_budget', 'Đã dùng hết lượng ký tự trong ngày.' );
		}
		update_option( $day, $used + mb_strlen( $text ), false );
	}
	$mp3 = hv_tts_synthesize( $text, $lang, $voice );
	if ( is_wp_error( $mp3 ) ) {
		return $mp3;
	}
	if ( ! wp_mkdir_p( dirname( $file ) ) || false === file_put_contents( $file, $mp3 ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- ghi file âm thanh vào uploads.
		return new WP_Error( 'hv_tts_write', 'Không ghi được file vào thư mục uploads.' );
	}
	return $url . '/' . $sub . '/' . $hash . '.mp3';
}

/**
 * REST: GET hocvui/v1/tts?t=câu&v=female|male|owl&l=vi|en&r=slow|normal|fast → chuyển hướng tới file MP3.
 */
function hv_tts_rest() {
	register_rest_route(
		'hocvui/v1',
		'/tts',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function ( WP_REST_Request $r ) {
				if ( ! hv_tts_enabled() ) {
					return new WP_Error( 'hv_tts_off', 'Chưa bật giọng đọc AI.', array( 'status' => 404 ) );
				}
				$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $r->get_param( 't' ) ) ) );
				$text = mb_substr( $text, 0, HV_TTS_MAX_CHARS );
				if ( '' === $text || ! preg_match( '/[\p{L}\p{N}]/u', $text ) ) {
					return new WP_Error( 'hv_tts_empty', 'Thiếu nội dung.', array( 'status' => 400 ) );
				}
				$who  = in_array( $r->get_param( 'v' ), array( 'female', 'male', 'owl' ), true ) ? $r->get_param( 'v' ) : 'female';
				$lang = 'en' === $r->get_param( 'l' ) ? 'en' : 'vi';
				$rate = in_array( $r->get_param( 'r' ), array( 'slow', 'normal', 'fast' ), true ) ? $r->get_param( 'r' ) : 'normal';
				$url  = hv_tts_file( $text, $who, $lang, $rate );
				if ( is_wp_error( $url ) ) {
					$status = 'hv_tts_limit' === $url->get_error_code() || 'hv_tts_budget' === $url->get_error_code() ? 429 : 502;
					return new WP_Error( $url->get_error_code(), $url->get_error_message(), array( 'status' => $status ) );
				}
				$res = new WP_REST_Response( null, 302 );
				$res->header( 'Location', $url );
				$res->header( 'Cache-Control', 'public, max-age=604800' );
				return $res;
			},
		)
	);
}
add_action( 'rest_api_init', 'hv_tts_rest' );

/* ---------- Trang cài đặt: Giao diện → Giọng đọc AI ---------- */

/**
 * Thêm trang cài đặt.
 */
function hv_tts_menu() {
	add_theme_page( 'Giọng đọc AI', 'Giọng đọc AI', 'manage_options', 'hv-tts', 'hv_tts_page' );
}
add_action( 'admin_menu', 'hv_tts_menu' );

/**
 * Đăng ký tuỳ chọn.
 */
function hv_tts_register() {
	register_setting(
		'hv_tts',
		'hv_tts',
		array(
			'type'              => 'array',
			'sanitize_callback' => function ( $in ) {
				$old = hv_tts_settings();
				$in  = is_array( $in ) ? $in : array();
				$out = array(
					'provider' => isset( $in['provider'] ) && in_array( $in['provider'], array( '', 'azure', 'google' ), true ) ? $in['provider'] : '',
					// Ô khoá để trống = giữ khoá cũ (không in khoá ra trang).
					'key'      => isset( $in['key'] ) && '' !== trim( $in['key'] ) ? sanitize_text_field( $in['key'] ) : $old['key'],
					'region'   => isset( $in['region'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( $in['region'] ) ) : 'southeastasia',
					'budget'   => isset( $in['budget'] ) ? max( 1000, (int) $in['budget'] ) : 200000,
				);
				foreach ( array( 'vi_female', 'vi_male', 'en_female', 'en_male' ) as $k ) {
					$out[ $k ] = isset( $in[ $k ] ) ? preg_replace( '/[^A-Za-z0-9:_-]/', '', $in[ $k ] ) : '';
				}
				if ( ! empty( $in['forget_key'] ) ) {
					$out['key'] = '';
				}
				return $out;
			},
		)
	);
}
add_action( 'admin_init', 'hv_tts_register' );

/**
 * Xoá bộ nhớ đệm âm thanh (sau khi đổi giọng).
 */
function hv_tts_clear_cache() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'hv_tts_clear' ) ) {
		wp_die( 'Không có quyền.' );
	}
	list( $dir ) = hv_tts_dir();
	foreach ( (array) glob( $dir . '/*/*.mp3' ) as $f ) {
		wp_delete_file( $f );
	}
	wp_safe_redirect( admin_url( 'themes.php?page=hv-tts&cleared=1' ) );
	exit;
}
add_action( 'admin_post_hv_tts_clear', 'hv_tts_clear_cache' );

/**
 * Nghe thử trong trang cài đặt (không tính giới hạn).
 */
function hv_tts_admin_test() {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'hv_tts_test', 'nonce', false ) ) {
		wp_send_json_error( 'Không có quyền.' );
	}
	$who  = isset( $_POST['v'] ) && in_array( $_POST['v'], array( 'female', 'male', 'owl' ), true ) ? sanitize_key( $_POST['v'] ) : 'female';
	$lang = isset( $_POST['l'] ) && 'en' === $_POST['l'] ? 'en' : 'vi';
	$text = 'en' === $lang ? 'Hello! What is your name? My name is Mai. Nice to meet you!' : ( 'owl' === $who ? 'Chào bạn nhỏ! Mình là Cú Bông. Hôm nay mình cùng học bài: Các số từ 0 đến 10 nhé!' : 'Chào các em! Hôm nay chúng mình học bài: Phép cộng trong phạm vi 10. Năm cộng ba bằng tám.' );
	if ( ! hv_tts_enabled() ) {
		wp_send_json_error( 'Hãy chọn dịch vụ, nhập khoá và bấm Lưu trước.' );
	}
	$url = hv_tts_file( $text, $who, $lang, 'normal', false );
	if ( is_wp_error( $url ) ) {
		wp_send_json_error( $url->get_error_message() );
	}
	wp_send_json_success( $url );
}
add_action( 'wp_ajax_hv_tts_test', 'hv_tts_admin_test' );

/**
 * Số file và dung lượng đã lưu.
 *
 * @return array [ số file, MB ]
 */
function hv_tts_cache_stats() {
	list( $dir ) = hv_tts_dir();
	$files       = (array) glob( $dir . '/*/*.mp3' );
	$size        = 0;
	foreach ( $files as $f ) {
		$size += (int) filesize( $f );
	}
	return array( count( array_filter( $files ) ), round( $size / 1048576, 1 ) );
}

/**
 * Giao diện trang cài đặt.
 */
function hv_tts_page() {
	$s     = hv_tts_settings();
	$def   = hv_tts_default_voices( $s['provider'] ? $s['provider'] : 'azure' );
	$stats = hv_tts_cache_stats();
	$used  = (int) get_option( 'hv_tts_used_' . gmdate( 'Ymd' ), 0 );
	?>
	<div class="wrap">
		<h1>Giọng đọc AI cho Học Vui</h1>
		<?php if ( isset( $_GET['cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success"><p>Đã xoá các file âm thanh đã lưu.</p></div>
		<?php endif; ?>
		<p style="max-width:780px">Giọng đọc AI nghe tự nhiên, rõ ràng và <strong>giống nhau trên mọi máy</strong> (Chrome, Safari, điện thoại, máy tính bảng). Mỗi câu chỉ tạo âm thanh <strong>một lần</strong> rồi lưu vào <code>uploads/hocvui-tts</code>, lần sau phát ngay và không tốn phí. Nếu chưa cấu hình hoặc dịch vụ lỗi, trang tự dùng giọng có sẵn của trình duyệt.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'hv_tts' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="hv-tts-provider">Dịch vụ</label></th><td>
					<select id="hv-tts-provider" name="hv_tts[provider]">
						<option value="" <?php selected( $s['provider'], '' ); ?>>Tắt – dùng giọng của trình duyệt</option>
						<option value="azure" <?php selected( $s['provider'], 'azure' ); ?>>Microsoft Azure Speech (khuyên dùng: giọng HoaiMy, NamMinh)</option>
						<option value="google" <?php selected( $s['provider'], 'google' ); ?>>Google Cloud Text-to-Speech</option>
					</select>
				</td></tr>
				<tr><th scope="row"><label for="hv-tts-key">Khoá API</label></th><td>
					<input id="hv-tts-key" type="password" class="regular-text" name="hv_tts[key]" value="" autocomplete="off" placeholder="<?php echo $s['key'] ? esc_attr( 'Đã lưu (…' . substr( $s['key'], -4 ) . ') – để trống để giữ nguyên' ) : 'Dán khoá vào đây'; ?>">
					<?php if ( $s['key'] ) : ?>
						<label style="margin-left:8px"><input type="checkbox" name="hv_tts[forget_key]" value="1"> Xoá khoá</label>
					<?php endif; ?>
					<p class="description">Azure: Key 1 của tài nguyên <em>Speech service</em>. Google: API key đã bật <em>Cloud Text-to-Speech API</em> (nên giới hạn khoá chỉ dùng cho API này).</p>
				</td></tr>
				<tr><th scope="row"><label for="hv-tts-region">Vùng (chỉ Azure)</label></th><td>
					<input id="hv-tts-region" type="text" class="regular-text" name="hv_tts[region]" value="<?php echo esc_attr( $s['region'] ); ?>">
					<p class="description">Ví dụ: <code>southeastasia</code> (Singapore – nhanh nhất cho Việt Nam), <code>eastasia</code>.</p>
				</td></tr>
				<tr><th scope="row">Tên giọng (để trống = mặc định)</th><td>
					<?php
					$labels = array( 'vi_female' => 'Cô giáo (tiếng Việt)', 'vi_male' => 'Thầy giáo (tiếng Việt)', 'en_female' => 'Tiếng Anh – giọng nữ', 'en_male' => 'Tiếng Anh – giọng nam' );
					foreach ( $labels as $k => $l ) :
						?>
						<p><label><span style="display:inline-block;width:190px"><?php echo esc_html( $l ); ?></span>
						<input type="text" name="hv_tts[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $s[ $k ] ); ?>" placeholder="<?php echo esc_attr( $def[ $k ] ); ?>"></label></p>
					<?php endforeach; ?>
					<p class="description">Cú Bông dùng giọng cô giáo, tươi và nhanh hơn một chút. Google: giọng tự nhiên nhất là dòng <em>Chirp 3 HD</em>, ví dụ <code>vi-VN-Chirp3-HD-Aoede</code> (nữ), <code>vi-VN-Chirp3-HD-Charon</code> (nam). Đổi giọng xong bấm "Xoá âm thanh đã lưu".</p>
				</td></tr>
				<tr><th scope="row"><label for="hv-tts-budget">Giới hạn mỗi ngày</label></th><td>
					<input id="hv-tts-budget" type="number" min="1000" step="1000" name="hv_tts[budget]" value="<?php echo (int) $s['budget']; ?>"> ký tự
					<p class="description">Chỉ tính câu mới (câu đã lưu không tính). Hôm nay đã dùng: <strong><?php echo esc_html( number_format_i18n( $used ) ); ?></strong> ký tự. Hết hạn mức thì trang tạm dùng giọng trình duyệt.</p>
				</td></tr>
			</table>
			<?php submit_button( 'Lưu cài đặt' ); ?>
		</form>

		<h2>Nghe thử</h2>
		<p>
			<button type="button" class="button" data-hv-tts-test="female" data-l="vi">👩‍🏫 Cô giáo</button>
			<button type="button" class="button" data-hv-tts-test="male" data-l="vi">👨‍🏫 Thầy giáo</button>
			<button type="button" class="button" data-hv-tts-test="owl" data-l="vi">🦉 Cú Bông</button>
			<button type="button" class="button" data-hv-tts-test="female" data-l="en">🇬🇧 Tiếng Anh</button>
			<span id="hv-tts-msg" style="margin-left:8px"></span>
		</p>
		<audio id="hv-tts-audio" controls style="display:none"></audio>

		<h2>Âm thanh đã lưu</h2>
		<p><?php echo esc_html( number_format_i18n( $stats[0] ) . ' câu · ' . $stats[1] . ' MB' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Xoá toàn bộ file âm thanh đã lưu? Các câu sẽ được tạo lại khi bé nghe.');">
			<input type="hidden" name="action" value="hv_tts_clear"><?php wp_nonce_field( 'hv_tts_clear' ); ?>
			<?php submit_button( 'Xoá âm thanh đã lưu (sau khi đổi giọng)', 'secondary', 'submit', false ); ?>
		</form>

		<h2>Cách lấy khoá Azure (miễn phí 500 000 ký tự/tháng)</h2>
		<ol style="max-width:780px">
			<li>Vào <a href="https://portal.azure.com" target="_blank" rel="noopener">portal.azure.com</a>, đăng nhập tài khoản Microsoft.</li>
			<li>Tạo tài nguyên <strong>Speech service</strong>, vùng <strong>Southeast Asia</strong>, gói <strong>Free F0</strong>.</li>
			<li>Mở tài nguyên → <strong>Keys and Endpoint</strong> → chép <strong>KEY 1</strong> dán vào ô Khoá API, vùng ghi <code>southeastasia</code>, bấm Lưu rồi Nghe thử.</li>
		</ol>
	</div>
	<script>
	document.querySelectorAll('[data-hv-tts-test]').forEach(function (b) {
		b.addEventListener('click', function () {
			var msg = document.getElementById('hv-tts-msg'), au = document.getElementById('hv-tts-audio');
			msg.textContent = 'Đang tạo âm thanh…';
			var fd = new FormData();
			fd.append('action', 'hv_tts_test'); fd.append('nonce', '<?php echo esc_js( wp_create_nonce( 'hv_tts_test' ) ); ?>');
			fd.append('v', b.getAttribute('data-hv-tts-test')); fd.append('l', b.getAttribute('data-l'));
			fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
				if (!d.success) { msg.textContent = '⚠️ ' + d.data; return; }
				msg.textContent = '✅ Đang phát'; au.style.display = 'block'; au.src = d.data; au.play();
			}).catch(function () { msg.textContent = '⚠️ Không kết nối được.'; });
		});
	});
	</script>
	<?php
}
