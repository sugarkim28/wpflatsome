<?php
/**
 * Lõi AI: cấu hình nhà cung cấp (đổi được), khoá API mã hoá / đặt trong wp-config,
 * giới hạn chi phí theo tháng, giới hạn số lần gọi theo ngày, lịch sử sử dụng.
 * AI chỉ trả văn bản; việc tạo bài luôn là BẢN NHÁP chờ duyệt (xem ai-admin.php).
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nhà cung cấp hỗ trợ: id => [tên, model mặc định, giá vào $/1M token, giá ra $/1M token].
 * Giá chỉ là mặc định để ước tính chi phí – sửa trong Cài đặt → AI theo bảng giá hiện hành.
 *
 * @return array
 */
function th119_ai_providers() {
	return array(
		'anthropic' => array( 'Anthropic (Claude)', 'claude-opus-5-5', 4.0, 20.0 ),
		'openai'    => array( 'OpenAI', 'gpt-4.1-mini', 0.4, 1.6 ),
		'gemini'    => array( 'Google Gemini', 'gemini-2.5-flash', 0.3, 2.5 ),
		'compat'    => array( 'Tương thích OpenAI (OpenRouter, DeepSeek, máy chủ riêng…)', '', 0, 0 ),
	);
}

/**
 * Cài đặt AI.
 *
 * @return array
 */
function th119_ai_settings() {
	$p = th119_ai_providers();
	$s = wp_parse_args(
		(array) get_option( 'th119_ai', array() ),
		array(
			'enabled'       => 0,
			'provider'      => 'anthropic',
			'model'         => '',
			'base_url'      => '',
			'api_key'       => '',
			'price_in'      => '',
			'price_out'     => '',
			'budget_month'  => 10,
			'daily_limit'   => 50,
			'max_tokens'    => 8000,
			'effort'        => 'medium',
			'brand_voice'   => 'Giọng văn: thân thiện, rõ ràng, chính xác kỹ thuật, xưng "Tin Học 119" hoặc "chúng tôi", gọi người đọc là "bạn". Đối tượng: người dùng phổ thông và nhân viên văn phòng tại TP.HCM.',
		)
	);
	$prov = isset( $p[ $s['provider'] ] ) ? $p[ $s['provider'] ] : $p['anthropic'];
	if ( '' === $s['model'] ) {
		$s['model'] = $prov[1];
	}
	if ( '' === $s['price_in'] ) {
		$s['price_in'] = $prov[2];
	}
	if ( '' === $s['price_out'] ) {
		$s['price_out'] = $prov[3];
	}
	return $s;
}

/**
 * Khoá API: ưu tiên hằng số TH119_AI_KEY trong wp-config.php (không nằm trong database / mã nguồn).
 *
 * @return string
 */
function th119_ai_key() {
	if ( defined( 'TH119_AI_KEY' ) && TH119_AI_KEY ) {
		return (string) TH119_AI_KEY;
	}
	return th119_decrypt( th119_ai_settings()['api_key'] );
}

/**
 * AI đã sẵn sàng chưa.
 *
 * @return bool
 */
function th119_ai_ready() {
	$s = th119_ai_settings();
	return $s['enabled'] && '' !== th119_ai_key() && '' !== $s['model'] && ( 'compat' !== $s['provider'] || $s['base_url'] );
}

/**
 * Chi phí đã dùng trong tháng (USD).
 *
 * @return float
 */
function th119_ai_month_cost() {
	global $wpdb;
	return (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(cost),0) FROM ' . th119_table( 'ai_log' ) . ' WHERE created_at >= %s', current_time( 'Y-m-01 00:00:00' ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
}

/**
 * Số lần gọi hôm nay.
 *
 * @return int
 */
function th119_ai_today_calls() {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . th119_table( 'ai_log' ) . ' WHERE created_at >= %s', current_time( 'Y-m-d 00:00:00' ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
}

/**
 * Gọi AI.
 *
 * @param string $task   Mã tác vụ (topics, outline, draft…).
 * @param string $system Chỉ dẫn hệ thống.
 * @param string $prompt Nội dung yêu cầu.
 * @param array  $opts   max_tokens, post_id.
 * @return array|WP_Error [ 'text' => string, 'log_id' => int ].
 */
function th119_ai_request( $task, $system, $prompt, $opts = array() ) {
	if ( ! current_user_can( 'th119_use_ai' ) ) {
		return new WP_Error( 'th119_ai_cap', 'Không có quyền dùng AI.' );
	}
	if ( ! th119_ai_ready() ) {
		return new WP_Error( 'th119_ai_off', 'Chưa cấu hình AI (Tin Học 119 → Cài đặt → AI).' );
	}
	$s = th119_ai_settings();
	if ( th119_ai_month_cost() >= (float) $s['budget_month'] ) {
		return new WP_Error( 'th119_ai_budget', sprintf( 'Đã dùng hết ngân sách AI tháng này (%s USD). Tăng ngân sách trong Cài đặt → AI nếu cần.', $s['budget_month'] ) );
	}
	if ( th119_ai_today_calls() >= (int) $s['daily_limit'] ) {
		return new WP_Error( 'th119_ai_daily', 'Đã đạt giới hạn số lần dùng AI hôm nay.' );
	}
	$max    = min( 32000, max( 500, (int) ( $opts['max_tokens'] ?? $s['max_tokens'] ) ) );
	$system = trim( $system . "\n\n" . $s['brand_voice'] );
	$res    = th119_ai_call_provider( $s, $system, $prompt, $max );

	$in   = is_wp_error( $res ) ? 0 : (int) $res['in'];
	$out  = is_wp_error( $res ) ? 0 : (int) $res['out'];
	$cost = ( $in * (float) $s['price_in'] + $out * (float) $s['price_out'] ) / 1000000;
	global $wpdb;
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		th119_table( 'ai_log' ),
		array(
			'user_id'    => get_current_user_id(),
			'provider'   => $s['provider'],
			'model'      => $s['model'],
			'task'       => $task,
			'prompt'     => mb_substr( $prompt, 0, 4000 ),
			'output'     => is_wp_error( $res ) ? $res->get_error_message() : $res['text'],
			'tokens_in'  => $in,
			'tokens_out' => $out,
			'cost'       => round( $cost, 5 ),
			'post_id'    => absint( $opts['post_id'] ?? 0 ),
			'status'     => is_wp_error( $res ) ? 'error' : 'ok',
			'created_at' => th119_now(),
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	return array(
		'text'   => $res['text'],
		'log_id' => (int) $wpdb->insert_id,
		'cost'   => $cost,
	);
}

/**
 * Gửi yêu cầu tới nhà cung cấp.
 *
 * @param array  $s      Cài đặt.
 * @param string $system Chỉ dẫn hệ thống.
 * @param string $prompt Yêu cầu.
 * @param int    $max    Token tối đa.
 * @return array|WP_Error [text, in, out].
 */
function th119_ai_call_provider( $s, $system, $prompt, $max ) {
	$key  = th119_ai_key();
	$args = array(
		'timeout' => 240,
		'headers' => array( 'Content-Type' => 'application/json' ),
	);
	switch ( $s['provider'] ) {
		case 'anthropic':
			$url                             = 'https://api.anthropic.com/v1/messages';
			$args['headers']['x-api-key']    = $key;
			$args['headers']['anthropic-version'] = '2023-06-01';
			$body                            = array(
				'model'      => $s['model'],
				'max_tokens' => $max,
				'system'     => $system,
				'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
			);
			if ( in_array( $s['effort'], array( 'low', 'medium', 'high' ), true ) && preg_match( '/^claude-(opus|sonnet|fable)-(5|4-[6-9])/', $s['model'] ) ) {
				$body['output_config'] = array( 'effort' => $s['effort'] );
			}
			if ( preg_match( '/^claude-(opus-5|fable-5-1|sonnet-5-5)/', $s['model'] ) ) {
				// Khi model từ chối do bộ lọc an toàn, máy chủ tự chuyển sang model dự phòng.
				$args['headers']['anthropic-beta'] = 'server-side-fallback-2026-07-01';
				$body['fallbacks']                 = 'default';
			}
			break;
		case 'gemini':
			$url                                 = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $s['model'] ) . ':generateContent';
			$args['headers']['x-goog-api-key']   = $key;
			$body                                = array(
				'systemInstruction' => array( 'parts' => array( array( 'text' => $system ) ) ),
				'contents'          => array( array( 'role' => 'user', 'parts' => array( array( 'text' => $prompt ) ) ) ),
				'generationConfig'  => array( 'maxOutputTokens' => $max ),
			);
			break;
		default: // openai, compat.
			$base                            = 'compat' === $s['provider'] ? untrailingslashit( $s['base_url'] ) : 'https://api.openai.com/v1';
			$url                             = $base . '/chat/completions';
			$args['headers']['Authorization'] = 'Bearer ' . $key;
			$body                            = array(
				'model'    => $s['model'],
				'messages' => array(
					array( 'role' => 'system', 'content' => $system ),
					array( 'role' => 'user', 'content' => $prompt ),
				),
			);
			$body[ 'openai' === $s['provider'] ? 'max_completion_tokens' : 'max_tokens' ] = $max;
	}
	$args['body'] = wp_json_encode( $body );
	$r            = wp_remote_post( $url, $args );
	if ( is_wp_error( $r ) ) {
		return new WP_Error( 'th119_ai_http', 'Không kết nối được nhà cung cấp AI: ' . $r->get_error_message() );
	}
	$code = (int) wp_remote_retrieve_response_code( $r );
	$data = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
		$msg = is_array( $data ) && isset( $data['error']['message'] ) ? $data['error']['message'] : 'HTTP ' . $code;
		return new WP_Error( 'th119_ai_api', 'Nhà cung cấp AI báo lỗi: ' . $msg );
	}
	$text = '';
	$in   = 0;
	$out  = 0;
	if ( 'anthropic' === $s['provider'] ) {
		if ( isset( $data['stop_reason'] ) && 'refusal' === $data['stop_reason'] ) {
			return new WP_Error( 'th119_ai_refusal', 'AI từ chối yêu cầu này. Hãy diễn đạt lại chủ đề.' );
		}
		foreach ( (array) ( $data['content'] ?? array() ) as $block ) {
			if ( isset( $block['type'] ) && 'text' === $block['type'] ) {
				$text .= $block['text'];
			}
		}
		$in  = (int) ( $data['usage']['input_tokens'] ?? 0 );
		$out = (int) ( $data['usage']['output_tokens'] ?? 0 );
	} elseif ( 'gemini' === $s['provider'] ) {
		foreach ( (array) ( $data['candidates'][0]['content']['parts'] ?? array() ) as $part ) {
			$text .= $part['text'] ?? '';
		}
		$in  = (int) ( $data['usageMetadata']['promptTokenCount'] ?? 0 );
		$out = (int) ( $data['usageMetadata']['candidatesTokenCount'] ?? 0 ) + (int) ( $data['usageMetadata']['thoughtsTokenCount'] ?? 0 );
	} else {
		$text = (string) ( $data['choices'][0]['message']['content'] ?? '' );
		$in   = (int) ( $data['usage']['prompt_tokens'] ?? 0 );
		$out  = (int) ( $data['usage']['completion_tokens'] ?? 0 );
	}
	if ( '' === trim( $text ) ) {
		return new WP_Error( 'th119_ai_empty', 'AI không trả về nội dung (có thể do giới hạn token). Thử lại hoặc tăng "Token tối đa".' );
	}
	return array(
		'text' => $text,
		'in'   => $in,
		'out'  => $out,
	);
}

/**
 * Lấy JSON từ câu trả lời AI (bỏ khối ```json nếu có).
 *
 * @param string $text Văn bản.
 * @return array|null
 */
function th119_ai_json( $text ) {
	$text = trim( (string) $text );
	if ( preg_match( '/```(?:json)?\s*(.+?)```/s', $text, $m ) ) {
		$text = trim( $m[1] );
	}
	$start = strcspn( $text, '[{' );
	$text  = substr( $text, $start );
	$data  = json_decode( $text, true );
	return is_array( $data ) ? $data : null;
}
