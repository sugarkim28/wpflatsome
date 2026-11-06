/**
 * Học Vui – trò chơi học tập cho học sinh tiểu học (lớp 1–5).
 * Không cần thư viện. Sao, nhãn dán, tên bé lưu trong trình duyệt (localStorage).
 *
 * Gắn trò chơi: <div class="hv-game" data-hv-game="tinh-nham" data-grade="2"></div>
 * Bài luyện tập của bài học: data-hv-game="quiz" data-quiz='[{"q":"…","a":"…","w":["…"]}]'
 */
(function () {
	'use strict';

	var CFG = window.hvConfig || {};
	var NBSP = ' ';

	/* ---------- Tiện ích ---------- */
	function rnd(a, b) { return a + Math.floor(Math.random() * (b - a + 1)); }
	function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
	function shuffle(arr) {
		var a = arr.slice();
		for (var i = a.length - 1; i > 0; i--) {
			var j = Math.floor(Math.random() * (i + 1));
			var t = a[i]; a[i] = a[j]; a[j] = t;
		}
		return a;
	}
	function esc(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function el(tag, cls, html) {
		var e = document.createElement(tag);
		if (cls) { e.className = cls; }
		if (html !== undefined) { e.innerHTML = html; }
		return e;
	}
	// Số viết theo SGK: tách lớp bằng khoảng trắng (25 000), thập phân dùng dấu phẩy (2,5).
	function num(n) {
		var s = String(n).split('.');
		var i = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
		if (s[0].length <= 4 && s[0].indexOf('-') < 0) { i = s[0]; } // 4 chữ số viết liền: 1000, 2025
		return s.length > 1 ? i + ',' + s[1] : i;
	}
	// Số thập phân từ số nguyên chia cho 10^d (tránh sai số dấu phẩy động).
	function dec(intVal, d) {
		var p = Math.pow(10, d);
		var neg = intVal < 0;
		var v = Math.abs(intVal);
		var ip = Math.floor(v / p);
		var fp = String(v % p);
		while (fp.length < d) { fp = '0' + fp; }
		fp = fp.replace(/0+$/, '');
		return (neg ? '-' : '') + num(ip) + (fp ? ',' + fp : '');
	}
	// 4 lựa chọn số: đáp án + 3 số gần đó (không âm, không trùng).
	function numChoices(ans, spread, fmt, step) {
		fmt = fmt || num;
		spread = spread || 3;
		step = step || 1;
		var set = {};
		var out = [ans];
		set[ans] = 1;
		var guard = 0;
		while (out.length < 4 && guard++ < 200) {
			var d = rnd(1, spread) * step * (Math.random() < 0.5 ? -1 : 1);
			var v = ans + d;
			if (v >= 0 && !set[v]) { set[v] = 1; out.push(v); }
		}
		return { choices: shuffle(out).map(fmt), a: fmt(ans) };
	}
	function gcd(a, b) { return b ? gcd(b, a % b) : a; }

	/* ---------- Lưu tiến độ ---------- */
	var STICKERS = ['🦄', '🐼', '🦁', '🐬', '🦋', '🚀', '🌈', '🏆', '🎨', '🦖', '🐙', '🌻', '🐳', '🦉', '🍭', '🎸', '🛸', '👑', '🐲', '💎'];
	var STAR_PER_STICKER = 10;
	var store = {
		get: function (k, d) {
			try { var v = window.localStorage.getItem('hv_' + k); return v === null ? d : JSON.parse(v); } catch (e) { return d; }
		},
		set: function (k, v) {
			try { window.localStorage.setItem('hv_' + k, JSON.stringify(v)); } catch (e) { /* trình duyệt chặn lưu: vẫn chơi bình thường */ }
		}
	};
	function stars() { return store.get('stars', 0); }
	var TITLES = ['Tân binh', 'Nhà thám hiểm', 'Chiến binh nhí', 'Hiệp sĩ tri thức', 'Siêu sao học tập', 'Huyền thoại phá đảo'];
	function lvOf(xp) { return 1 + Math.floor(Math.sqrt(xp / 40)); }
	function lvXp(lv) { return 40 * (lv - 1) * (lv - 1); }
	function titleOf(lv) { return TITLES[Math.min(lv - 1, TITLES.length - 1)]; }
	function todayKey(d) { d = d || new Date(); return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate(); }
	function today() {
		var t = store.get('today', {});
		if (t.date !== todayKey()) { t = { date: todayKey(), plays: 0, lessons: 0, perfect: 0 }; }
		return t;
	}
	// Ghi nhận 1 lượt chơi: XP, chuỗi ngày học, nhiệm vụ hôm nay. Trả về cấp mới nếu vừa lên cấp.
	function addXp(n, got, isLesson) {
		var before = store.get('xp', 0);
		var after = before + n;
		store.set('xp', after);
		var last = store.get('lastDay', '');
		var y = new Date(); y.setDate(y.getDate() - 1);
		if (last !== todayKey()) {
			store.set('streak', last === todayKey(y) ? store.get('streak', 0) + 1 : 1);
			store.set('lastDay', todayKey());
		}
		var t = today();
		t.plays++;
		if (isLesson && got > 0) { t.lessons++; }
		if (got === 3) { t.perfect++; }
		store.set('today', t);
		return lvOf(after) > lvOf(before) ? lvOf(after) : 0;
	}
	function addStars(n) {
		var before = stars();
		var after = before + n;
		store.set('stars', after);
		var games = store.get('played', 0);
		store.set('played', games + 1);
		renderStarBadges();
		renderAlbums();
		var newSticker = Math.floor(after / STAR_PER_STICKER) > Math.floor(before / STAR_PER_STICKER) && Math.floor(before / STAR_PER_STICKER) < STICKERS.length;
		return newSticker ? STICKERS[Math.floor(before / STAR_PER_STICKER)] : '';
	}
	function renderStarBadges() {
		var s = stars();
		document.querySelectorAll('[data-hv-stars]').forEach(function (n) { n.textContent = s; });
		var name = store.get('name', '');
		document.querySelectorAll('[data-hv-name]').forEach(function (n) { n.textContent = name || n.getAttribute('data-hv-name'); });
	}

	/* ---------- Âm thanh, đọc to, pháo giấy ---------- */
	var audioCtx = null;
	function soundOn() { return store.get('sound', true); }
	function tone(freqs, dur) {
		if (!soundOn()) { return; }
		try {
			audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
			var t = audioCtx.currentTime;
			freqs.forEach(function (f, i) {
				var o = audioCtx.createOscillator();
				var g = audioCtx.createGain();
				o.type = 'sine';
				o.frequency.value = f;
				g.gain.setValueAtTime(0.0001, t + i * dur);
				g.gain.exponentialRampToValueAtTime(0.18, t + i * dur + 0.02);
				g.gain.exponentialRampToValueAtTime(0.0001, t + (i + 1) * dur);
				o.connect(g); g.connect(audioCtx.destination);
				o.start(t + i * dur); o.stop(t + (i + 1) * dur + 0.02);
			});
		} catch (e) { /* không hỗ trợ âm thanh */ }
	}
	var sfx = {
		good: function () { tone([660, 880], 0.11); },
		bad: function () { tone([300, 220], 0.14); },
		flip: function () { tone([520], 0.06); },
		win: function () { tone([523, 659, 784, 1046], 0.13); }
	};
	/* Giọng đọc: Cô giáo (nữ) hoặc Thầy giáo (nam), đọc rõ ràng, tốc độ vừa phải.
	   Ưu tiên giọng tự nhiên: Edge có HoaiMy (nữ) và NamMinh (nam); Chrome có Google Tiếng Việt; iOS/macOS có Linh.
	   Máy không có giọng nam tiếng Việt thì Thầy giáo đọc bằng giọng trầm (hạ cao độ giọng nữ). */
	var voices = [];
	var MALE = /NamMinh|Nam\s?Minh|Microsoft An\b|^An\b|\bmale\b|\bGuy\b|Christopher|Eric|Davis|Andrew|Brian|Roger|Daniel|Alex\b|Fred|Google UK English Male|Aaron|Arthur|Tom\b/i;
	var PREF = {
		vi: { female: [/HoaiMy/i, /Hoài\s?My/i, /Google.*(Vi|Việt)/i, /Linh/i], male: [/NamMinh/i, /Nam\s?Minh/i, /\bAn\b/i] },
		en: { female: [/\bAna\b/i, /Jenny/i, /Aria/i, /Google US English/i, /Samantha/i], male: [/Guy/i, /Christopher/i, /Andrew/i, /Google UK English Male/i, /Daniel/i, /Alex\b/i] }
	};
	var RATES = { slow: [0.75, 0.68], normal: [0.9, 0.8], fast: [1.05, 0.92] };
	function loadVoices() {
		try { voices = window.speechSynthesis.getVoices() || []; } catch (e) { voices = []; }
		document.querySelectorAll('[data-set="voice"]').forEach(fillVoiceSelect);
	}
	if ('speechSynthesis' in window) {
		loadVoices();
		if (window.speechSynthesis.addEventListener) { window.speechSynthesis.addEventListener('voiceschanged', loadVoices); }
	}
	function isMale(v) { return MALE.test(v.name) || /male/i.test(v.voiceURI || '') && !/female/i.test(v.voiceURI || ''); }
	function langVoices(want) {
		return voices.filter(function (v) { return (v.lang || '').toLowerCase().replace('_', '-').indexOf(want) === 0; })
			.sort(function (a, b) { return (/Natural|Online|Neural/i.test(b.name) ? 1 : 0) - (/Natural|Online|Neural/i.test(a.name) ? 1 : 0); });
	}
	// Người đọc: Cô giáo (nữ), Thầy giáo (nam), Cú Bông (giọng cô tươi, cao hơn – kể chuyện cho bé lớp 1–2).
	function persona() { var p = store.get('vgender', 'female'); return p === 'male' || p === 'owl' ? p : 'female'; }
	function gender() { return persona() === 'male' ? 'male' : 'female'; }
	function rateKey() { var r = store.get('rate', 'normal'); return RATES[r] ? r : 'normal'; }
	// Chọn giọng theo ngôn ngữ và giới tính; trả về { v: giọng, fake: true nếu phải giả giọng nam }.
	function pickVoice(lang) {
		var want = lang === 'en' ? 'en' : 'vi';
		var list = langVoices(want);
		if (!list.length) { return { v: null, fake: gender() === 'male' }; }
		if (want === 'vi') {
			var chosen = store.get('voice', '');
			var c = list.filter(function (v) { return v.name === chosen; })[0];
			if (c) { return { v: c, fake: false }; }
		}
		var g = gender();
		var same = list.filter(function (v) { return isMale(v) === (g === 'male'); });
		var prefs = PREF[want][g];
		for (var i = 0; i < prefs.length; i++) {
			var hit = same.filter(function (v) { return prefs[i].test(v.name); })[0];
			if (hit) { return { v: hit, fake: false }; }
		}
		if (same.length) { return { v: same[0], fake: false }; }
		// Không có giọng đúng giới tính: dùng giọng còn lại (giọng nam giả bằng cách hạ cao độ).
		var other = PREF[want].female.map(function (re) { return list.filter(function (v) { return re.test(v.name); })[0]; }).filter(Boolean)[0] || list[0];
		return { v: other, fake: g === 'male' };
	}
	function voiceFor(lang) { return pickVoice(lang).v; }
	function hasRealMale() { return langVoices('vi').some(isMale); }
	/* Chuẩn hoá câu trước khi đọc để phát âm đúng kiểu cô giáo đọc bài: phép tính, phân số, ngày tháng,
	   giờ, đơn vị đo, số có dấu cách hàng nghìn, chỗ trống, chữ viết tắt; bỏ biểu tượng cảm xúc. */
	var UNITS = { 'km²': 'ki-lô-mét vuông', 'm²': 'mét vuông', 'dm²': 'đề-xi-mét vuông', 'cm²': 'xăng-ti-mét vuông', 'cm³': 'xăng-ti-mét khối', 'dm³': 'đề-xi-mét khối', 'm³': 'mét khối', 'km/h': 'ki-lô-mét một giờ', 'km': 'ki-lô-mét', 'hm': 'héc-tô-mét', 'dam': 'đề-ca-mét', 'dm': 'đề-xi-mét', 'cm': 'xăng-ti-mét', 'mm': 'mi-li-mét', 'kg': 'ki-lô-gam', 'ml': 'mi-li-lít', 'g': 'gam', 'l': 'lít', 'm': 'mét', 'ha': 'héc-ta', '°C': 'độ C', '%': 'phần trăm', 'đ': 'đồng' };
	var ABBR = [[/\bTP\.?\s?HCM\b/g, 'Thành phố Hồ Chí Minh'], [/\bTP\.\s?/g, 'thành phố '], [/\bVD:/g, 'Ví dụ:'], [/\bSGK\b/g, 'sách giáo khoa'], [/\bGDPT\b/g, 'giáo dục phổ thông'], [/\bXP\b/g, 'điểm kinh nghiệm'], [/\bSTT\b/g, 'số thứ tự']];
	function speechText(t) {
		var s = String(t);
		try { s = s.replace(/[\p{Extended_Pictographic}\u{FE0F}\u{200D}\u{20E3}]/gu, ' '); } catch (e) { s = s.replace(/[🔈🔊🎯⭐✏️🎮]/g, ''); }
		// Xuống dòng (đoạn, hàng bảng) → ngắt câu; ô trong cùng hàng (tab) → ngắt nhẹ.
		s = s.replace(/[ \t]*\t[ \t]*/g, ', ').replace(/([^.!?:;,\s])[ \t]*\n\s*/g, '$1.\n').replace(/\s*\n\s*/g, '\n');
		ABBR.forEach(function (r) { s = s.replace(r[0], r[1]); });
		s = s
			.replace(/(\d) (?=\d{3}(?!\d))/g, '$1')                           // 10 000 → 10000
			.replace(/(\d)\.(?=\d{3}(?!\d))/g, '$1')                          // 1.000 → 1000
			.replace(/(\d),(\d)/g, '$1 phẩy $2')                               // 2,5 → 2 phẩy 5
			.replace(/(ngày|Quốc khánh|dịp|lễ|Tết) (\d{1,2})\/(\d{1,2})(?:\/(\d{4}))?/gi, function (m, w, d, mo, y) { return w + ' ' + d + ' tháng ' + mo + (y ? ' năm ' + y : ''); })
			.replace(/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/g, 'ngày $1 tháng $2 năm $3')
			.replace(/\)\s*\/\s*\(/g, ') phần (').replace(/\b(\d{1,4})\/(\d{1,4})\b/g, '$1 phần $2')   // phân số
			.replace(/≠/g, ' khác ').replace(/≈/g, ' xấp xỉ ')
			.replace(/\b(\d{1,2}):(\d{2})\b/g, function (m, h, mi) { return h + ' giờ' + (+mi ? ' ' + (+mi) + ' phút' : ''); })
			.replace(/(\d)\s*(km²|m²|dm²|cm²|cm³|dm³|m³|km\/h|km|hm|dam|dm|cm|mm|kg|ml|ha|°C|%|đ|g|l|m)(?![A-Za-zÀ-ỹ0-9])/g, function (m, n, u) { return n + ' ' + UNITS[u]; })
			.replace(/(\d)\s*[−–-]\s+(?=\d)|(\d)\s+[−–-]\s*(?=\d)|(\d)\s*−\s*(?=\d)/g, function (m, a, b, c) { return (a || b || c) + ' trừ '; })
			.replace(/(\d)[–-](?=\d)/g, '$1 đến ')                               // lớp 1–5, 2026–2027
			.replace(/(\d|\?|…|_+)\s*[×x*]\s*(?=\d|\?|…|_)/g, '$1 nhân ')
			.replace(/(\d|\?|…|_+)\s*[:÷]\s+(?=\d|\?|…|_)/g, '$1 chia ')
			.replace(/(\d|\?|…|_+)\s*\+\s*(?=\d|\?|…|_)/g, '$1 cộng ')
			.replace(/=\s*\?/g, ' bằng bao nhiêu?')
			.replace(/(\d|\?|…|_|\))\s*=\s*/g, '$1 bằng ')
			.replace(/(\d|\?|…|_)\s*(?:>|&gt;)\s*(?=\d|\?|…|_)/g, '$1 lớn hơn ')
			.replace(/(\d|\?|…|_)\s*(?:<|&lt;)\s*(?=\d|\?|…|_)/g, '$1 bé hơn ')
			.replace(/(^|[\s(])(?:…|\.{3,}|_{2,})(?=[\s).,?!]|$)/g, '$1 chỗ trống ')    // ô trống cần điền
			.replace(/\?(?=\s*(?:cộng|trừ|nhân|chia|bằng))/g, ' mấy ')
			.replace(/(cộng|trừ|nhân|chia|bằng)\s*\?/g, '$1 mấy?')
			.replace(/…/g, '.').replace(/[→⇒➔›»]/g, ', ').replace(/[•·|]/g, ', ')
			.replace(/[ \t]+([,.!?])/g, '$1').replace(/[ \t]+/g, ' ').replace(/ ?\n ?/g, '\n').trim();
		return s;
	}
	// Tách thành từng câu ngắn: đọc rõ, có ngắt nghỉ, và tránh lỗi Chrome tự dừng khi câu đọc quá 15 giây.
	function chunks(text) {
		var out = [];
		String(text).replace(/([.!?;:])\s+/g, '$1\n').split(/\n+/).forEach(function (s) {
			s = s.trim();
			while (s.length > 180) {
				var cut = s.lastIndexOf(',', 180);
				cut = cut > 40 ? cut + 1 : s.lastIndexOf(' ', 180);
				if (cut < 1) { cut = 180; }
				out.push(s.slice(0, cut).trim());
				s = s.slice(cut).trim();
			}
			if (s && /[\p{L}\p{N}]/u.test(s)) { out.push(s); }
		});
		return out;
	}
	/* Giọng đọc AI của máy chủ (nếu quản trị đã bật ở Giao diện → Giọng đọc AI): mỗi câu là 1 file MP3
	   đã lưu sẵn, nghe giống nhau trên mọi máy. Một thẻ <audio> dùng lại cho mọi câu (iPhone chỉ cho phát
	   tiếp trên thẻ đã được bé chạm mở); câu sau được tải trước để đọc liền mạch. Lỗi → dùng giọng trình duyệt. */
	var TTS = CFG.tts && CFG.tts.url ? CFG.tts : null;
	var ttsAudio = null;
	var ttsDownUntil = 0;
	function ttsUrl(text, en) {
		return TTS.url + (TTS.url.indexOf('?') > -1 ? '&' : '?') + 't=' + encodeURIComponent(text) + '&v=' + persona() + '&l=' + (en ? 'en' : 'vi') + '&r=' + rateKey();
	}
	var speakId = 0;
	var talkers = [];
	function talk(on) {
		if (on) { talkers = document.querySelectorAll('.hv-mascot, .hv-owl, .hv-owl-img'); }
		talkers.forEach(function (m) { m.classList.toggle('is-talking', !!on); });
	}
	function stopSpeech() {
		speakId++;
		if (ttsAudio) { try { ttsAudio.pause(); } catch (e) { /* bỏ qua */ } }
		if ('speechSynthesis' in window) { try { window.speechSynthesis.cancel(); } catch (e) { /* bỏ qua */ } }
		talk(false);
	}
	function speakBrowser(parts, en, id) {
		if (!('speechSynthesis' in window) || !parts.length) { talk(false); return; }
		var pick = pickVoice(en ? 'en' : 'vi');
		var rate = RATES[rateKey()][en ? 1 : 0];
		var pitch = persona() === 'owl' ? 1.2 : (pick.fake ? 0.7 : 1);
		parts.forEach(function (part, i) {
			var u = new window.SpeechSynthesisUtterance(part);
			if (pick.v) { u.voice = pick.v; }
			u.lang = en ? 'en-US' : 'vi-VN';
			u.rate = rate;
			u.pitch = pitch;
			u.volume = 1;
			if (i === parts.length - 1) { u.onend = function () { if (id === speakId) { talk(false); } }; }
			u.onerror = function () { if (id === speakId) { talk(false); } };
			window.speechSynthesis.speak(u);
		});
	}
	// Giọng AI: gộp các câu ngắn liền nhau (tối đa ~260 ký tự) để ngữ điệu liền mạch, ít khoảng lặng giữa các file.
	function groupParts(parts) {
		var out = [];
		parts.forEach(function (p) {
			var last = out[out.length - 1];
			if (last && last.length + p.length < 260 && !/:$/.test(last)) { out[out.length - 1] = last + ' ' + p; } else { out.push(p); }
		});
		return out;
	}
	function speakServer(parts, en, id) {
		parts = groupParts(parts);
		if (!ttsAudio) { ttsAudio = new window.Audio(); ttsAudio.preload = 'auto'; }
		var a = ttsAudio;
		var i = 0;
		var fail = function () {
			if (id !== speakId) { return; }
			ttsDownUntil = Date.now() + 5 * 60 * 1000; // Máy chủ lỗi/hết hạn mức: dùng giọng trình duyệt 5 phút.
			speakBrowser(parts.slice(i), en, id);
		};
		var next = function () {
			if (id !== speakId) { return; }
			if (i >= parts.length) { talk(false); return; }
			var url = ttsUrl(parts[i], en);
			[1, 2].forEach(function (k) { if (parts[i + k] && window.fetch) { window.fetch(ttsUrl(parts[i + k], en)).catch(function () { /* tải trước */ }); } });
			a.onended = function () { i++; next(); };
			a.onerror = fail;
			a.src = url;
			var pr = a.play();
			if (pr && pr.catch) { pr.catch(function (err) { if (err && err.name === 'NotAllowedError') { talk(false); } else { fail(); } }); }
		};
		next();
	}
	function speak(text, lang) {
		if (!soundOn() || !text) { return; }
		try {
			stopSpeech();
			var en = lang === 'en';
			var parts = chunks(en ? String(text) : speechText(text));
			if (!parts.length) { return; }
			var id = speakId;
			talk(true);
			if (TTS && Date.now() > ttsDownUntil && window.Audio) { speakServer(parts, en, id); } else { speakBrowser(parts, en, id); }
		} catch (e) { talk(false); }
	}
	function autoRead(grade) {
		var v = store.get('autoread', null);
		return v === null ? (grade || 1) <= 2 : !!v;
	}
	function confetti(host) {
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
		var box = el('div', 'hv-confetti');
		var colors = ['#ff5a5f', '#ffb400', '#2ec4b6', '#5b8def', '#9b5de5', '#f15bb5'];
		for (var i = 0; i < 60; i++) {
			var p = el('i');
			p.style.left = rnd(0, 100) + '%';
			p.style.background = pick(colors);
			p.style.animationDelay = (Math.random() * 0.6) + 's';
			p.style.animationDuration = (1.6 + Math.random() * 1.4) + 's';
			p.style.transform = 'rotate(' + rnd(0, 360) + 'deg)';
			box.appendChild(p);
		}
		host.appendChild(box);
		setTimeout(function () { box.remove(); }, 3600);
	}

	/* ---------- Ngân hàng nội dung ---------- */

	// Ghép chữ: [hình, chữ, âm đầu, vần].
	var WORDS = [
		['🐟', 'cá', 'c', 'á'], ['🐔', 'gà', 'g', 'à'], ['🏠', 'nhà', 'nh', 'à'], ['🐄', 'bò', 'b', 'ò'],
		['🐱', 'mèo', 'm', 'èo'], ['🐶', 'chó', 'ch', 'ó'], ['🐰', 'thỏ', 'th', 'ỏ'], ['🐘', 'voi', 'v', 'oi'],
		['🐢', 'rùa', 'r', 'ùa'], ['🍎', 'táo', 't', 'áo'], ['🐻', 'gấu', 'g', 'ấu'], ['🦀', 'cua', 'c', 'ua'],
		['📚', 'sách', 's', 'ách'], ['🥭', 'xoài', 'x', 'oài'], ['🍇', 'nho', 'nh', 'o'], ['🐴', 'ngựa', 'ng', 'ựa'],
		['🐒', 'khỉ', 'kh', 'ỉ'], ['🌽', 'ngô', 'ng', 'ô'], ['🍋', 'chanh', 'ch', 'anh'], ['⛵', 'thuyền', 'th', 'uyền'],
		['🦆', 'vịt', 'v', 'ịt'], ['🌳', 'cây', 'c', 'ây'], ['🌙', 'trăng', 'tr', 'ăng'], ['🥚', 'trứng', 'tr', 'ứng'],
		['🐍', 'rắn', 'r', 'ắn'], ['🍌', 'chuối', 'ch', 'uối'], ['🌸', 'hoa', 'h', 'oa'], ['⭐', 'sao', 's', 'ao'],
		['🐷', 'lợn', 'l', 'ợn'], ['🍚', 'cơm', 'c', 'ơm'], ['🦷', 'răng', 'r', 'ăng'], ['🐜', 'kiến', 'k', 'iến'],
		['👟', 'giày', 'gi', 'ày'], ['✋', 'tay', 't', 'ay'], ['🌧️', 'mưa', 'm', 'ưa'], ['🔔', 'chuông', 'ch', 'uông'],
		['🐃', 'trâu', 'tr', 'âu'], ['🎁', 'quà', 'qu', 'à'], ['☁️', 'mây', 'm', 'ây'], ['🔥', 'lửa', 'l', 'ửa'],
		['🐛', 'sâu', 's', 'âu'], ['🦊', 'cáo', 'c', 'áo'], ['🐑', 'cừu', 'c', 'ừu'], ['🍑', 'đào', 'đ', 'ào'],
		['🪁', 'diều', 'd', 'iều'], ['🌊', 'sóng', 's', 'óng'], ['🧸', 'gấu bông', '', ''], ['🍬', 'kẹo', 'k', 'ẹo']
	].filter(function (w) { return w[2]; });

	// Cặp âm đầu dễ viết sai (chính tả lớp 2–3): ch/tr, s/x, r/d/gi, l/n.
	var CONFUSE = { ch: ['tr'], tr: ['ch'], s: ['x'], x: ['s'], r: ['d', 'gi'], d: ['r', 'gi'], gi: ['d', 'r'], l: ['n'], n: ['l'] };
	var TONES = ['̀', '́', '̃', '̉', '̣']; // huyền, sắc, ngã, hỏi, nặng
	function swapTone(word) {
		var d = word.normalize('NFD');
		for (var i = 0; i < TONES.length; i++) {
			if (d.indexOf(TONES[i]) >= 0) {
				var other = pick(TONES.filter(function (t) { return t !== TONES[i]; }));
				return d.replace(TONES[i], other).normalize('NFC');
			}
		}
		return '';
	}

	// Sắp xếp câu theo lớp.
	var SENTENCES = {
		1: ['Em đi học', 'Mẹ nấu cơm', 'Bé chơi bóng', 'Bà kể chuyện cổ tích', 'Mèo bắt chuột', 'Chim hót líu lo', 'Bố đọc báo', 'Em tưới cây'],
		2: ['Em yêu ngôi trường của em', 'Bạn Lan đang đọc sách', 'Ông em tưới cây mỗi sáng', 'Chú gà trống gáy vang', 'Cả nhà em đi chơi công viên', 'Cô giáo khen bạn Nam', 'Đàn vịt bơi trên hồ'],
		3: ['Những chú chim sẻ ríu rít trên cành cây', 'Em luôn giữ gìn sách vở sạch đẹp', 'Đàn trâu thong thả gặm cỏ ven đê', 'Mặt trăng tròn như chiếc đĩa bạc', 'Các bạn nhỏ vui vẻ nô đùa trên sân trường'],
		4: ['Học sinh chăm chỉ ôn bài để chuẩn bị cho kì thi', 'Bác nông dân cần mẫn làm việc trên cánh đồng', 'Chúng em trồng thêm nhiều cây xanh quanh trường', 'Ông mặt trời thức dậy sau rặng tre', 'Dòng sông quê em hiền hoà chảy qua làng'],
		5: ['Mỗi người cần có ý thức bảo vệ môi trường sống', 'Nhờ chăm chỉ luyện tập nên Hoa đã tiến bộ rõ rệt', 'Những cánh đồng lúa chín vàng trải dài tít tắp', 'Tiếng trống trường rộn ràng chào đón năm học mới', 'Đất nước ta có nhiều danh lam thắng cảnh nổi tiếng']
	};

	// Đố vui khoa học – xã hội: [câu hỏi, đúng, sai…, môn].
	var QUIZ = {
		1: [
			['Bộ phận nào giúp em nhìn thấy mọi vật?', 'Mắt', 'Tai', 'Mũi', 'TN&XH'],
			['Em cần rửa tay bằng xà phòng khi nào?', 'Trước khi ăn và sau khi đi vệ sinh', 'Một tuần một lần', 'Chỉ khi đi ngủ', 'TN&XH'],
			['Con vật nào sống dưới nước?', 'Con cá', 'Con gà', 'Con mèo', 'TN&XH'],
			['Khi đèn giao thông màu đỏ, em phải làm gì?', 'Dừng lại', 'Đi tiếp', 'Chạy thật nhanh', 'TN&XH'],
			['Cây xanh cần gì để sống và lớn lên?', 'Nước, ánh sáng và không khí', 'Chỉ cần bóng tối', 'Chỉ cần đất khô', 'TN&XH'],
			['Ban đêm trên bầu trời em thường thấy gì?', 'Mặt Trăng và các vì sao', 'Cầu vồng', 'Mặt Trời chói chang', 'TN&XH'],
			['Mỗi ngày em nên đánh răng mấy lần?', 'Ít nhất 2 lần: sáng và tối', 'Một tuần một lần', 'Không cần đánh răng', 'TN&XH'],
			['Khi bạn bị ngã, em nên làm gì?', 'Đỡ bạn dậy và hỏi thăm bạn', 'Cười bạn', 'Đi chỗ khác', 'Đạo đức'],
			['Khi được người khác giúp đỡ, em nói gì?', 'Em cảm ơn ạ!', 'Không nói gì', 'Em xin lỗi ạ!', 'Đạo đức']
		],
		2: [
			['Cơ quan nào giúp em thở?', 'Mũi, khí quản và phổi', 'Dạ dày', 'Xương chân', 'TN&XH'],
			['Xương và cơ thuộc cơ quan nào?', 'Cơ quan vận động', 'Cơ quan hô hấp', 'Cơ quan tiêu hoá', 'TN&XH'],
			['Con vật nào vừa sống được dưới nước vừa sống được trên cạn?', 'Con ếch', 'Con cá', 'Con chim sẻ', 'TN&XH'],
			['Khi ho hoặc hắt hơi, em nên làm gì?', 'Che miệng bằng khăn giấy hoặc khuỷu tay', 'Hắt hơi thẳng vào bạn', 'Không cần che', 'TN&XH'],
			['Nước uống an toàn là:', 'Nước đun sôi để nguội', 'Nước ao hồ', 'Nước mưa đọng trên lá', 'TN&XH'],
			['Miền Bắc nước ta có mấy mùa?', '4 mùa: xuân, hạ, thu, đông', '2 mùa', '1 mùa', 'TN&XH'],
			['Cây lúa thường được trồng ở đâu?', 'Ruộng nước', 'Sa mạc', 'Trên mái nhà', 'TN&XH'],
			['Khi có bão, em nên làm gì?', 'Ở trong nhà an toàn, nghe người lớn hướng dẫn', 'Ra sông bơi', 'Trèo lên cây cao', 'TN&XH'],
			['Em nhặt được của rơi, em nên làm gì?', 'Trả lại người đánh mất hoặc nhờ thầy cô trả', 'Giữ lấy dùng', 'Vứt đi', 'Đạo đức']
		],
		3: [
			['Bộ phận nào của cây hút nước và muối khoáng từ đất?', 'Rễ', 'Lá', 'Hoa', 'TN&XH'],
			['Lá cây thường có màu xanh vì có:', 'Chất diệp lục', 'Nước', 'Muối', 'TN&XH'],
			['Tim thuộc cơ quan nào của cơ thể?', 'Cơ quan tuần hoàn', 'Cơ quan hô hấp', 'Cơ quan vận động', 'TN&XH'],
			['Bộ phận nào điều khiển mọi hoạt động của cơ thể?', 'Não', 'Dạ dày', 'Phổi', 'TN&XH'],
			['Mặt Trời mọc ở phương nào?', 'Phương Đông', 'Phương Tây', 'Phương Nam', 'TN&XH'],
			['Trái Đất có dạng hình gì?', 'Gần giống hình cầu', 'Hình vuông', 'Hình tam giác', 'TN&XH'],
			['Con vật nào đẻ trứng?', 'Con gà', 'Con chó', 'Con bò', 'TN&XH'],
			['Con vật nào có xương sống?', 'Con cá', 'Con giun', 'Con ốc sên', 'TN&XH'],
			['Đọc sách đúng cách để bảo vệ mắt:', 'Ngồi ngay ngắn, đủ ánh sáng, giữ khoảng cách hợp lí', 'Đọc trong bóng tối', 'Nằm đọc sát mắt', 'TN&XH']
		],
		4: [
			['Ở điều kiện bình thường, nước sôi ở bao nhiêu độ C?', '100 °C', '50 °C', '0 °C', 'Khoa học'],
			['Nước đóng băng (thành đá) ở bao nhiêu độ C?', '0 °C', '100 °C', '37 °C', 'Khoa học'],
			['Khí nào trong không khí giúp duy trì sự cháy?', 'Khí ô-xi', 'Khí ni-tơ', 'Khí các-bô-níc', 'Khoa học'],
			['Vật nào cho ánh sáng truyền qua?', 'Tấm kính trong', 'Tấm gỗ', 'Tấm sắt', 'Khoa học'],
			['Âm thanh được tạo ra khi nào?', 'Khi vật rung động', 'Khi vật đứng yên', 'Khi vật bị chiếu sáng', 'Khoa học'],
			['Nấm men được dùng để làm gì?', 'Làm bánh mì', 'Làm thuốc nhuộm vải', 'Làm phân bón hoá học', 'Khoa học'],
			['Gạo, bánh mì, khoai thuộc nhóm chất nào?', 'Chất bột đường', 'Chất đạm', 'Chất béo', 'Khoa học'],
			['Dãy núi cao nhất nước ta là:', 'Hoàng Liên Sơn', 'Trường Sơn', 'Bạch Mã', 'LS&ĐL'],
			['Tây Nguyên nổi tiếng với cây công nghiệp nào?', 'Cà phê', 'Lúa nước', 'Cói', 'LS&ĐL'],
			['Đền Hùng nằm ở tỉnh nào?', 'Phú Thọ', 'Cà Mau', 'Lâm Đồng', 'LS&ĐL']
		],
		5: [
			['Vật liệu nào dẫn điện tốt?', 'Đồng', 'Nhựa', 'Gỗ khô', 'Khoa học'],
			['Nguồn năng lượng nào là năng lượng tái tạo?', 'Năng lượng mặt trời', 'Than đá', 'Dầu mỏ', 'Khoa học'],
			['Nước muối là:', 'Một dung dịch', 'Một chất rắn', 'Một chất khí', 'Khoa học'],
			['Cơ quan sinh sản của thực vật có hoa là:', 'Hoa', 'Rễ', 'Thân', 'Khoa học'],
			['Nòng nọc lớn lên sẽ thành con gì?', 'Con ếch', 'Con cá', 'Con rắn', 'Khoa học'],
			['Để tiết kiệm điện, em nên:', 'Tắt đèn, quạt khi ra khỏi phòng', 'Bật đèn cả ngày', 'Mở tủ lạnh liên tục', 'Khoa học'],
			['Ai đọc bản Tuyên ngôn Độc lập ngày 2/9/1945?', 'Chủ tịch Hồ Chí Minh', 'Ngô Quyền', 'Lê Lợi', 'LS&ĐL'],
			['Ngô Quyền đánh tan quân Nam Hán năm 938 trên sông nào?', 'Sông Bạch Đằng', 'Sông Hồng', 'Sông Mê Công', 'LS&ĐL'],
			['Phần đất liền nước ta có hình dạng thế nào?', 'Hẹp ngang, kéo dài theo chiều bắc – nam (hình chữ S)', 'Hình tròn', 'Hình vuông', 'LS&ĐL'],
			['Nước láng giềng phía bắc của Việt Nam là:', 'Trung Quốc', 'Lào', 'Cam-pu-chia', 'LS&ĐL']
		]
	};

	// Tiếng Anh: chủ đề theo lớp → [hình, từ].
	var EN = {
		animals: [['🐱', 'cat'], ['🐶', 'dog'], ['🐟', 'fish'], ['🐦', 'bird'], ['🦆', 'duck'], ['🐰', 'rabbit'], ['🐵', 'monkey'], ['🐘', 'elephant'], ['🐯', 'tiger']],
		colours: [['🔴', 'red'], ['🔵', 'blue'], ['🟢', 'green'], ['🟡', 'yellow'], ['🟠', 'orange'], ['🟣', 'purple'], ['⚫', 'black'], ['🟤', 'brown']],
		food: [['🍎', 'apple'], ['🍌', 'banana'], ['🥛', 'milk'], ['🍞', 'bread'], ['🥚', 'egg'], ['🍚', 'rice'], ['🍰', 'cake'], ['🍊', 'orange']],
		body: [['👁️', 'eye'], ['👂', 'ear'], ['👃', 'nose'], ['✋', 'hand'], ['🦶', 'foot'], ['👄', 'mouth']],
		school: [['📖', 'book'], ['🖊️', 'pen'], ['✏️', 'pencil'], ['📏', 'ruler'], ['🎒', 'school bag'], ['✂️', 'scissors']],
		family: [['👨', 'father'], ['👩', 'mother'], ['👶', 'baby'], ['👵', 'grandmother'], ['👴', 'grandfather'], ['👦', 'brother'], ['👧', 'sister']],
		weather: [['☀️', 'sunny'], ['🌧️', 'rainy'], ['🌬️', 'windy'], ['❄️', 'snowy'], ['☁️', 'cloudy'], ['⛈️', 'stormy']],
		jobs: [['🧑‍⚕️', 'doctor'], ['🧑‍🏫', 'teacher'], ['🧑‍🌾', 'farmer'], ['🧑‍🍳', 'cook'], ['👮', 'police officer'], ['🧑‍🚒', 'firefighter'], ['🧑‍✈️', 'pilot']],
		transport: [['🚌', 'bus'], ['🚗', 'car'], ['🚲', 'bike'], ['🚆', 'train'], ['✈️', 'plane'], ['⛵', 'boat']]
	};
	var EN_TOPICS = { 1: ['colours', 'animals', 'food', 'body'], 2: ['colours', 'animals', 'food', 'body'], 3: ['school', 'family', 'animals', 'colours'], 4: ['weather', 'jobs', 'food', 'family'], 5: ['jobs', 'transport', 'weather', 'school'] };
	var EN_NAMES = { animals: 'Animals – Con vật', colours: 'Colours – Màu sắc', food: 'Food – Đồ ăn', body: 'My body – Cơ thể', school: 'School things – Đồ dùng học tập', family: 'Family – Gia đình', weather: 'Weather – Thời tiết', jobs: 'Jobs – Nghề nghiệp', transport: 'Transport – Phương tiện' };

	/* ---------- Bộ sinh câu hỏi Toán ---------- */
	function mathQ(g) {
		var a, b, c, r, t;
		var types = {
			1: ['add10', 'sub10', 'add10', 'sub10', 'tens', 'noCarry'],
			2: ['carryAdd', 'carrySub', 'mul25', 'div25', 'carryAdd', 'carrySub'],
			3: ['mulTable', 'divTable', 'mul2d', 'hundreds', 'times', 'mulTable'],
			4: ['thousands', 'by10', 'fracSame', 'fracDiv', 'avg', 'roundMul'],
			5: ['decAdd', 'decSub', 'decBy10', 'percent', 'speed', 'decAdd']
		}[g] || ['add10'];
		switch (pick(types)) {
			case 'add10': a = rnd(0, 9); b = rnd(0, 10 - a); return mk(a + ' + ' + b, a + b, 2);
			case 'sub10': a = rnd(1, 10); b = rnd(0, a); return mk(a + ' − ' + b, a - b, 2);
			case 'tens': a = rnd(1, 8) * 10; b = rnd(1, (90 - a) / 10 + 1) * 10; return Math.random() < 0.5 ? mk(a + ' + ' + b, a + b, 2, 10) : mk((a + b) + ' − ' + b, a, 2, 10);
			case 'noCarry':
				a = rnd(1, 8) * 10 + rnd(0, 8); b = rnd(1, 9 - a % 10);
				return Math.random() < 0.5 ? mk(a + ' + ' + b, a + b, 3) : mk((a + b) + ' − ' + b, a, 3);
			case 'carryAdd':
				do { a = rnd(11, 89); b = rnd(2, 99 - a); } while ((a % 10) + (b % 10) < 10);
				return mk(a + ' + ' + b, a + b, 10);
			case 'carrySub':
				do { a = rnd(20, 99); b = rnd(5, a - 1); } while ((a % 10) >= (b % 10));
				return mk(a + ' − ' + b, a - b, 10);
			case 'mul25': a = pick([2, 5]); b = rnd(1, 10); return mk(a + ' × ' + b, a * b, a);
			case 'div25': a = pick([2, 5]); b = rnd(1, 10); return mk((a * b) + ' : ' + a, b, 2);
			case 'mulTable': a = rnd(2, 9); b = rnd(2, 10); return mk(a + ' × ' + b, a * b, a);
			case 'divTable': a = rnd(2, 9); b = rnd(2, 10); return mk((a * b) + ' : ' + a, b, 2);
			case 'mul2d': a = rnd(11, 32); b = rnd(2, 3); return mk(a + ' × ' + b, a * b, 10);
			case 'hundreds': a = rnd(1, 8) * 100 + rnd(0, 9) * 10; b = rnd(1, 9 - Math.floor(a / 100)) * 100; return Math.random() < 0.5 ? mk(a + ' + ' + b, a + b, 2, 100) : mk((a + b) + ' − ' + b, a, 2, 100);
			case 'times':
				a = rnd(2, 9); b = rnd(2, 5);
				return Math.random() < 0.5
					? { q: 'Gấp ' + a + ' lên ' + b + ' lần được bao nhiêu?', say: 'Gấp ' + a + ' lên ' + b + ' lần được bao nhiêu?', o: numChoices(a * b, 4) }
					: { q: 'Giảm ' + (a * b) + ' đi ' + b + ' lần được bao nhiêu?', say: 'Giảm ' + (a * b) + ' đi ' + b + ' lần được bao nhiêu?', o: numChoices(a, 3) };
			case 'thousands': a = rnd(41, 69) * 1000; b = rnd(10, 30) * 1000; return Math.random() < 0.5 ? mk(num(a) + ' + ' + num(b), a + b, 2, 1000) : mk(num(a + b) + ' − ' + num(b), a, 2, 1000);
			case 'by10':
				a = rnd(12, 99); b = pick([10, 100, 1000]);
				return Math.random() < 0.6 ? mk(a + ' × ' + num(b), a * b, 0, 0, function (ans) { return [ans, ans / 10, ans * 10, a + b]; }) : mk(num(a * b) + ' : ' + num(b), a, 0, 0, function (ans) { return [ans, ans * 10, a * b / 10, ans + 10]; });
			case 'fracSame':
				b = rnd(5, 12); a = rnd(1, b - 2); c = rnd(1, b - a - 1);
				return fracQ(a + '/' + b + ' + ' + c + '/' + b, a + c, b, [[a + c, b + b], [a * c, b], [a + c + 1, b]]);
			case 'fracDiv':
				b = pick([2, 3, 4, 5]); t = b * pick([2, 3]); a = 1; c = rnd(1, t - t / b - 1);
				// a/b + c/t, mẫu t chia hết cho b
				r = a * (t / b) + c;
				return fracQ(a + '/' + b + ' + ' + c + '/' + t, r, t, [[a + c, b + t], [a + c, t], [r + 1, t]]);
			case 'avg': a = rnd(10, 60); b = a + 2 * rnd(1, 15); return { q: 'Trung bình cộng của ' + a + ' và ' + b + ' là bao nhiêu?', say: 'Trung bình cộng của ' + a + ' và ' + b + ' là bao nhiêu?', o: numChoices((a + b) / 2, 4) };
			case 'roundMul': a = rnd(2, 9) * 100; b = rnd(2, 9); return mk(num(a) + ' × ' + b, a * b, 0, 0, function (ans) { return [ans, ans / 10, ans + 100, ans - 100]; });
			case 'decAdd': a = rnd(11, 99); b = rnd(11, 99); return decQ(dec(a, 1) + ' + ' + dec(b, 1), a + b, 1);
			case 'decSub': a = rnd(30, 99); b = rnd(11, a - 5); return decQ(dec(a, 1) + ' − ' + dec(b, 1), a - b, 1);
			case 'decBy10':
				a = rnd(101, 999); b = pick([10, 100]);
				// a/100 × b
				return decQ(dec(a, 2) + ' × ' + b, a * b, 2, [a, a * b * 10, a * 10 === a * b ? a * 1000 : a * 10]);
			case 'percent':
				a = pick([10, 20, 25, 50]); b = pick([20, 40, 60, 80, 100, 120, 200]);
				return { q: a + '% của ' + b + ' là bao nhiêu?', say: a + ' phần trăm của ' + b + ' là bao nhiêu?', o: numChoices(a * b / 100, 5) };
			case 'speed':
				t = rnd(2, 5); r = rnd(3, 12) * 5;
				return { q: 'Một xe đi ' + (r * t) + ' km trong ' + t + ' giờ. Vận tốc của xe là?', say: 'Một xe đi ' + (r * t) + ' ki lô mét trong ' + t + ' giờ. Vận tốc của xe là bao nhiêu?', o: numChoices(r, 10, function (v) { return v + ' km/giờ'; }) };
		}
		return mk('1 + 1', 2, 2);

		function mk(expr, ans, spread, step, custom) {
			var o;
			if (custom) {
				var seen = {};
				var list = custom(ans).filter(function (v) { if (seen[v] || v < 0 || v !== Math.floor(v)) { return false; } seen[v] = 1; return true; });
				while (list.length < 4) { var v = ans + rnd(1, 9) * (list.length); if (!seen[v]) { seen[v] = 1; list.push(v); } }
				o = { choices: shuffle(list.slice(0, 4)).map(num), a: num(ans) };
			} else {
				o = numChoices(ans, spread, num, step);
			}
			return { q: expr + ' = ?', say: expr.replace(/−/g, 'trừ').replace(/×/g, 'nhân').replace(/ : /g, ' chia ').replace(/\+/g, 'cộng') + ' bằng mấy?', o: o, big: true };
		}
		function fracQ(expr, n, d, wrong) {
			var fmt = function (p) { return p[0] + '/' + p[1]; };
			var seen = {};
			var list = [[n, d]].concat(wrong).filter(function (p) {
				var k = fmt(p);
				if (seen[k] || p[0] <= 0) { return false; }
				seen[k] = 1; return true;
			});
			return { q: expr + ' = ?', say: 'Tính ' + expr.replace(/\//g, ' phần ').replace(/\+/g, 'cộng'), o: { choices: shuffle(list).map(fmt), a: fmt([n, d]) }, big: true, frac: true, note: 'Không cần rút gọn' };
		}
		function decQ(expr, ansInt, d, wrongInts) {
			var seen = {};
			var list = [ansInt];
			seen[ansInt] = 1;
			(wrongInts || []).forEach(function (v) { if (!seen[v] && v > 0) { seen[v] = 1; list.push(v); } });
			while (list.length < 4) {
				var v = ansInt + rnd(1, 12) * (Math.random() < 0.5 ? -1 : 1) * (Math.random() < 0.5 ? 1 : Math.pow(10, d - 1) || 1);
				if (v > 0 && !seen[v]) { seen[v] = 1; list.push(v); }
			}
			var fmt = function (v) { return dec(v, d); };
			return { q: expr + ' = ?', say: expr.replace(/−/g, 'trừ').replace(/×/g, 'nhân').replace(/\+/g, 'cộng') + ' bằng bao nhiêu?', o: { choices: shuffle(list.slice(0, 4)).map(fmt), a: fmt(ansInt) }, big: true };
		}
	}

	/* ---------- Hình học & đếm ---------- */
	var SHAPES = {
		'Hình tròn': '<circle cx="50" cy="50" r="38"/>',
		'Hình vuông': '<rect x="14" y="14" width="72" height="72" rx="4"/>',
		'Hình tam giác': '<path d="M50 10 L90 86 L10 86 Z"/>',
		'Hình chữ nhật': '<rect x="6" y="26" width="88" height="48" rx="4"/>'
	};
	var COUNT_ITEMS = ['🍎', '🐟', '⭐', '🌸', '🐥', '🍓', '🎈', '🐞', '🍊', '🚗'];
	function countQ(g) {
		var it = pick(COUNT_ITEMS);
		if (g >= 2 && Math.random() < 0.7) {
			// Đếm theo nhóm → làm quen phép nhân (lớp 2).
			var per = pick([2, 5]);
			var groups = rnd(2, 5);
			var html = '';
			for (var i = 0; i < groups; i++) { html += '<span class="hv-group">' + new Array(per + 1).join(it) + '</span>'; }
			return {
				q: 'Mỗi nhóm có ' + per + ' ' + it + '. Có ' + groups + ' nhóm. Tất cả có bao nhiêu?',
				say: 'Mỗi nhóm có ' + per + '. Có ' + groups + ' nhóm. Tất cả có bao nhiêu?',
				visual: '<div class="hv-count">' + html + '</div>',
				o: numChoices(per * groups, per, function (v) { return num(v); }),
				hint: per + ' × ' + groups + ' = ' + (per * groups)
			};
		}
		var r = Math.random();
		if (r < 0.3) {
			var name = pick(Object.keys(SHAPES));
			var color = pick(['#ff6b6b', '#4dabf7', '#51cf66', '#fcc419', '#9775fa']);
			return {
				q: 'Đây là hình gì?', say: 'Đây là hình gì?',
				visual: '<svg class="hv-shape" viewBox="0 0 100 100" fill="' + color + '" aria-hidden="true">' + SHAPES[name] + '</svg>',
				o: { choices: shuffle(Object.keys(SHAPES)), a: name }
			};
		}
		if (r < 0.45) {
			var x = rnd(1, 10); var y;
			do { y = rnd(1, 10); } while (y === x);
			return { q: 'Số nào lớn hơn?', say: 'Số nào lớn hơn?', o: { choices: [String(x), String(y)], a: String(Math.max(x, y)) }, big: true };
		}
		var n = rnd(1, 10);
		return {
			q: 'Có bao nhiêu ' + it + '?', say: 'Có bao nhiêu?',
			visual: '<div class="hv-count"><span class="hv-group">' + new Array(n + 1).join(it) + '</span></div>',
			o: numChoices(n, 2)
		};
	}

	/* ---------- Xem đồng hồ ---------- */
	function clockSvg(h, m) {
		var ticks = '';
		for (var i = 1; i <= 12; i++) {
			var ang = i * 30 * Math.PI / 180;
			ticks += '<text x="' + (50 + 36 * Math.sin(ang)).toFixed(1) + '" y="' + (50 - 36 * Math.cos(ang) + 4).toFixed(1) + '">' + i + '</text>';
		}
		var ha = ((h % 12) + m / 60) * 30;
		var ma = m * 6;
		return '<svg class="hv-clock" viewBox="0 0 100 100" role="img" aria-label="Đồng hồ">' +
			'<circle cx="50" cy="50" r="46" class="hv-clock-face"/>' + ticks +
			'<line x1="50" y1="50" x2="50" y2="26" class="hv-clock-h" transform="rotate(' + ha + ' 50 50)"/>' +
			'<line x1="50" y1="50" x2="50" y2="14" class="hv-clock-m" transform="rotate(' + ma + ' 50 50)"/>' +
			'<circle cx="50" cy="50" r="3" class="hv-clock-dot"/></svg>';
	}
	function timeText(h, m, g) {
		if (m === 0) { return h + ' giờ'; }
		if (m === 30 && g <= 2) { return h + ' giờ rưỡi'; }
		return h + ' giờ ' + m + ' phút';
	}
	function clockQ(g) {
		var h = rnd(1, 12);
		var m = g === 1 ? 0 : g === 2 ? pick([0, 15, 30]) : rnd(0, 11) * 5;
		var key = function (hh, mm) { return hh + ':' + mm; };
		var seen = {};
		var opts = [[h, m]];
		seen[key(h, m)] = 1;
		var cands = [];
		if (m % 5 === 0 && m > 0) { cands.push([m / 5, h * 5 % 60]); } // nhầm kim giờ với kim phút
		cands.push([h % 12 + 1, m], [h === 1 ? 12 : h - 1, m], [h, (m + 15) % 60], [h, (m + 45) % 60], [rnd(1, 12), m]);
		shuffle(cands).forEach(function (p) {
			if (opts.length < (g === 1 ? 3 : 4) && p[0] >= 1 && p[0] <= 12 && !seen[key(p[0], p[1])] && (g > 1 || p[1] === 0) && (g !== 2 || p[1] % 15 === 0)) {
				seen[key(p[0], p[1])] = 1; opts.push(p);
			}
		});
		while (opts.length < (g === 1 ? 3 : 4)) {
			var p = [rnd(1, 12), m];
			if (!seen[key(p[0], p[1])]) { seen[key(p[0], p[1])] = 1; opts.push(p); }
		}
		return {
			q: 'Đồng hồ chỉ mấy giờ?', say: 'Đồng hồ chỉ mấy giờ?',
			visual: clockSvg(h, m),
			o: { choices: shuffle(opts).map(function (p) { return timeText(p[0], p[1], g); }), a: timeText(h, m, g) }
		};
	}

	/* ---------- Ghép chữ ---------- */
	function wordQ(g, used) {
		var w;
		var guard = 0;
		do { w = pick(WORDS); } while (used[w[1]] && guard++ < 50);
		used[w[1]] = 1;
		var pic = '<div class="hv-pic" aria-hidden="true">' + w[0] + '</div>';
		var opts, ans, shown;
		if (g === 1) {
			ans = w[2];
			opts = [ans];
			shuffle(WORDS).forEach(function (o) { if (opts.length < 3 && opts.indexOf(o[2]) < 0) { opts.push(o[2]); } });
			shown = '<span class="hv-blank">?</span>' + esc(w[3]);
			return { q: 'Chọn âm đầu còn thiếu', say: 'Chọn âm còn thiếu trong tiếng ' + w[1], visual: pic + '<div class="hv-word">' + shown + '</div>', o: { choices: shuffle(opts), a: ans }, word: w[1] };
		}
		if (g === 2) {
			ans = w[3];
			opts = [ans];
			shuffle(WORDS).forEach(function (o) { if (opts.length < 3 && opts.indexOf(o[3]) < 0 && o[2] + o[3] !== w[1]) { opts.push(o[3]); } });
			shown = esc(w[2]) + '<span class="hv-blank">?</span>';
			return { q: 'Chọn vần còn thiếu', say: 'Chọn vần còn thiếu trong tiếng ' + w[1], visual: pic + '<div class="hv-word">' + shown + '</div>', o: { choices: shuffle(opts), a: ans }, word: w[1] };
		}
		// Lớp 3: chọn từ viết đúng chính tả (chỉ lấy từ đủ 2 cách viết sai: có dấu thanh, hoặc âm đầu r/d/gi).
		while (!swapTone(w[1]) && (CONFUSE[w[2]] || []).length < 2) { w = pick(WORDS); }
		used[w[1]] = 1;
		pic = '<div class="hv-pic" aria-hidden="true">' + w[0] + '</div>';
		ans = w[1];
		opts = [ans];
		(CONFUSE[w[2]] || []).forEach(function (on) {
			// gi + iều → giều (không viết 2 chữ i).
			opts.push(on === 'gi' && w[3].normalize('NFD').charAt(0) === 'i' ? 'g' + w[3] : on + w[3]);
		});
		var t = swapTone(w[1]);
		if (t && opts.indexOf(t) < 0) { opts.push(t); }
		var guard2 = 0;
		while (opts.length < 3 && guard2++ < 30) {
			var t2 = swapTone(w[1]);
			if (t2 && opts.indexOf(t2) < 0) { opts.push(t2); }
		}
		return { q: 'Từ nào viết đúng chính tả?', say: 'Từ nào viết đúng chính tả?', visual: pic, o: { choices: shuffle(opts.slice(0, 3)), a: ans }, word: w[1] };
	}

	/* ---------- Định nghĩa trò chơi ---------- */
	var GAMES = {
		'tinh-nham': { title: 'Tính nhẩm siêu tốc', icon: '🧮', grades: [1, 2, 3, 4, 5], total: 10, timer: true, make: function (g) { return mathQ(g); } },
		'dem-hinh': { title: 'Đếm hình & nhận biết hình', icon: '🔢', grades: [1, 2], total: 8, make: function (g) { return countQ(g); } },
		'xem-gio': { title: 'Bé xem đồng hồ', icon: '⏰', grades: [1, 2, 3], total: 8, make: function (g) { return clockQ(g); } },
		'ghep-chu': {
			title: 'Ghép chữ – Đánh vần', icon: '🔤', grades: [1, 2, 3], total: 8,
			make: function (g, st) { st.used = st.used || {}; return wordQ(Math.min(g, 3), st.used); }
		},
		'dua-xe': { title: 'Đua xe tính nhẩm', icon: '🏎️', grades: [1, 2, 3, 4, 5], custom: 'race', hint: 'Trả lời đúng để xe chạy – về đích trước xe của Cú Bông!' },
		'dap-chuot': { title: 'Đập chuột chũi', icon: '🔨', grades: [1, 2, 3, 4, 5], custom: 'whack', hint: 'Đập thật nhanh chú chuột mang số đúng yêu cầu trong 45 giây' },
		'hai-tao': { title: 'Hái táo – Tập dùng chuột', icon: '🍎', grades: [1, 2, 3, 4, 5], custom: 'mouse', hint: '3 bước: nháy chuột, nháy đúp, kéo thả táo vào giỏ' },
		'go-phim': { title: 'Tập gõ phím', icon: '⌨️', grades: [1, 2, 3, 4, 5], custom: 'typing', hint: 'Gõ đúng phím để phá bong bóng chữ trước khi chạm đất' },
		'bo-phan': { title: 'Ghép bộ phận máy tính', icon: '🖥️', grades: [1, 2, 3, 4, 5], custom: 'parts', hint: 'Kéo tên (hoặc chạm tên rồi chạm dấu ?) vào đúng bộ phận' },
		'do-vui': {
			title: 'Đố vui khoa học – xã hội', icon: '🔬', grades: [1, 2, 3, 4, 5], total: 8,
			deck: function (g) {
				return shuffle(QUIZ[g] || QUIZ[1]).map(function (r) {
					return { q: r[0], say: r[0], tag: r[r.length - 1], o: { choices: shuffle(r.slice(1, r.length - 1)), a: r[1] } };
				});
			}
		},
		'sap-xep-cau': { title: 'Sắp xếp câu', icon: '🧩', grades: [1, 2, 3, 4, 5], total: 5, custom: 'order' },
		'lat-the': { title: 'Lật thẻ tiếng Anh', icon: '🃏', grades: [1, 2, 3, 4, 5], custom: 'memory' },
		'bong-bay': { title: 'Bắn bóng bay', icon: '🎈', grades: [1, 2, 3, 4, 5], custom: 'balloon', hint: 'Chạm quả bóng mang đáp án đúng trước khi bóng bay mất' },
		'cau-ca': { title: 'Câu cá – Nghe và chọn', icon: '🎣', grades: [1, 2, 3, 4, 5], custom: 'fishing', hint: 'Nghe Cú Bông đọc rồi câu đúng con cá' },
		'dung-sai': { title: 'Đúng hay Sai siêu tốc', icon: '✅', grades: [1, 2, 3, 4, 5], custom: 'truefalse', hint: 'Trả lời Đúng hay Sai thật nhanh trước khi hết giờ' },
		'leo-nui': { title: 'Leo núi tri thức', icon: '🏔️', grades: [1, 2, 3, 4, 5], custom: 'climb', hint: 'Trả lời đúng để leo 10 nấc lên đỉnh – có quyền trợ giúp' },
		'quiz': { title: 'Luyện tập', icon: '✏️', grades: [], total: 0, fromData: true }
	};

	/* ---------- Độ khó: thời gian đếm ngược, số lựa chọn, tim, hệ số điểm ---------- */
	var DIFFS = {
		1: { name: 'Dễ', icon: '🐣', sec: 20, time: 1.35, speed: 0.75, choices: 3, hearts: 0, extra: 0, mul: 1, desc: 'Thong thả, 3 lựa chọn' },
		2: { name: 'Vừa', icon: '🐯', sec: 12, time: 1, speed: 1, choices: 4, hearts: 0, extra: 0, mul: 1.5, desc: 'Nhanh tay hơn, điểm x1,5' },
		3: { name: 'Khó', icon: '🐲', sec: 7, time: 0.75, speed: 1.3, choices: 4, hearts: 3, extra: 2, mul: 2, desc: 'Siêu tốc, 3 tim, câu thử thách, điểm x2' }
	};
	// Trò chơi hỏi–đáp: thời gian mỗi câu nhân thêm khi câu dài (đọc chữ, xem hình).
	var READ_MUL = { 'do-vui': 1.5, 'ghep-chu': 1.3, 'xem-gio': 1.3, 'dem-hinh': 1.2 };
	function diffOf(p) { return DIFFS[p.d] || DIFFS[2]; }
	// Mô tả độ khó riêng cho từng kiểu trò chơi (mặc định: mô tả chung của DIFFS).
	var DIFF_TEXT = {
		balloon: ['Bóng bay chậm, 3 quả', 'Bóng bay vừa, điểm x1,5', 'Bóng bay nhanh, điểm x2'],
		truefalse: ['80 giây, thong thả', '60 giây, điểm x1,5', '45 giây, điểm x2'],
		race: ['Xe Cú Bông chạy chậm', 'Xe Cú Bông chạy vừa, điểm x1,5', 'Xe Cú Bông chạy nhanh, điểm x2'],
		whack: ['Chuột thò lên lâu', 'Chuột nhanh hơn, điểm x1,5', 'Chuột siêu nhanh, điểm x2'],
		typing: ['7 tim, chữ rơi chậm', '5 tim, điểm x1,5', '3 tim, chữ rơi nhanh, điểm x2'],
		climb: ['2 quyền trợ giúp', '2 quyền trợ giúp, điểm x1,5', '1 quyền trợ giúp, điểm x2'],
		memory: ['Ít thẻ, dễ nhớ', 'Nhiều thẻ hơn, điểm x1,5', 'Nhiều thẻ nhất, điểm x2'],
		other: ['Điểm x1', 'Điểm x1,5', 'Điểm x2']
	};
	function diffText(p, d) {
		if (!p.def.custom) { return DIFFS[d].desc; }
		return (DIFF_TEXT[p.def.custom] || DIFF_TEXT.other)[d - 1];
	}
	// Đổi màu, rung và kêu tích tắc khi thanh thời gian sắp hết.
	function timerAlert(wrap, left, limit) {
		if (!wrap) { return; }
		wrap.classList.toggle('is-warn', left <= Math.max(5, limit * 0.35) && left > Math.min(5, limit * 0.2));
		wrap.classList.toggle('is-danger', left <= Math.min(5, limit * 0.2) || left <= 3);
		if (left > 0 && left <= 5) { tone([left <= 2 ? 990 : 760], 0.05); }
	}

	/* ---------- Trình chơi ---------- */
	function Player(root) {
		this.root = root;
		this.id = root.getAttribute('data-hv-game');
		this.def = GAMES[this.id];
		if (!this.def) { return; }
		var g = parseInt(root.getAttribute('data-grade'), 10) || store.get('grade', 1);
		if (this.def.grades.length && this.def.grades.indexOf(g) < 0) { g = this.def.grades[0]; }
		this.grade = g;
		this.d = this.def.fromData ? 2 : (parseInt(store.get('diff_' + this.id, 2), 10) || 2);
		this.title = root.getAttribute('data-title') || this.def.title;
		root.classList.add('hv-player');
		root.innerHTML = '';
		this.head = el('div', 'hv-p-head');
		this.body = el('div', 'hv-p-body');
		this.live = el('div', 'hv-sr');
		this.live.setAttribute('aria-live', 'polite');
		root.appendChild(this.head);
		root.appendChild(this.body);
		root.appendChild(this.live);
		this.renderHead();
		this.intro();
	}
	Player.prototype.renderHead = function () {
		var self = this;
		var h = '<div class="hv-p-title"><span class="hv-p-ico" aria-hidden="true">' + this.def.icon + '</span>' + esc(this.title) + '</div>';
		h += '<div class="hv-p-tools">';
		if (this.def.grades.length > 1 && !this.root.hasAttribute('data-lock-grade')) {
			h += '<div class="hv-grades" role="group" aria-label="Chọn lớp">';
			this.def.grades.forEach(function (g) {
				h += '<button type="button" class="hv-chip' + (g === self.grade ? ' is-on' : '') + '" data-g="' + g + '" aria-pressed="' + (g === self.grade) + '">Lớp ' + g + '</button>';
			});
			h += '</div>';
		}
		h += '<button type="button" class="hv-icon-btn hv-sound" aria-label="Bật/tắt âm thanh" title="Âm thanh">' + (soundOn() ? '🔊' : '🔇') + '</button>';
		h += '<span class="hv-starbox" title="Sao của em">⭐ <b data-hv-stars>' + stars() + '</b></span></div>';
		this.head.innerHTML = h;
		this.head.querySelectorAll('[data-g]').forEach(function (b) {
			b.addEventListener('click', function () {
				self.grade = parseInt(b.getAttribute('data-g'), 10);
				store.set('grade', self.grade);
				self.renderHead();
				self.intro();
			});
		});
		this.head.querySelector('.hv-sound').addEventListener('click', function () {
			store.set('sound', !soundOn());
			if (!soundOn()) { stopSpeech(); }
			document.querySelectorAll('.hv-sound').forEach(function (b) { b.textContent = soundOn() ? '🔊' : '🔇'; });
		});
	};
	Player.prototype.say = function (msg) { this.live.textContent = msg; };
	Player.prototype.intro = function () {
		var self = this;
		if (this.stopTimer) { this.stopTimer(); this.stopTimer = null; }
		this.stopClock();
		clearTimeout(this.cdTimer);
		this.root.classList.remove('is-playing', 'is-hot');
		var name = store.get('name', '');
		var b = this.body;
		var sub = this.def.fromData ? 'Trả lời các câu hỏi để ôn lại bài nhé!' : (this.def.grades.length ? 'Lớp ' + this.grade + ' · ' : '') + (this.def.hint ? this.def.hint : this.def.custom === 'memory' ? 'Lật 2 thẻ giống nhau để ghép đôi' : this.def.custom === 'order' ? 'Chạm vào các từ theo đúng thứ tự' : 'Chọn đáp án đúng');
		var h = '<div class="hv-intro"><div class="hv-intro-art" aria-hidden="true"><span class="hv-mascot"></span><span class="hv-intro-ico">' + this.def.icon + '</span></div>' +
			'<h3>' + (name ? 'Chào ' + esc(name) + '!' : 'Chào bạn nhỏ!') + '</h3><p>' + esc(sub) + '</p>';
		if (!this.def.fromData) {
			var deckGame = !this.def.custom;
			var rm = READ_MUL[this.id] || 1;
			h += '<div class="hv-diff" role="radiogroup" aria-label="Chọn độ khó">';
			[1, 2, 3].forEach(function (d) {
				var D = DIFFS[d];
				var t = deckGame ? ' · ' + Math.round(D.sec * rm) + ' giây/câu' : '';
				h += '<button type="button" role="radio" class="hv-diff-btn d' + d + (d === self.d ? ' is-on' : '') + '" aria-checked="' + (d === self.d) + '" data-d="' + d + '"><span aria-hidden="true">' + D.icon + '</span><b>' + D.name + '</b><small>' + esc(diffText(self, d) + t) + '</small></button>';
			});
			h += '</div>';
			if (deckGame) {
				h += '<label class="hv-switch hv-timed"><input type="checkbox" data-timed' + (store.get('timed', true) ? ' checked' : '') + '><span></span>⏱️ Đếm ngược thời gian mỗi câu</label>';
			}
			var best = store.get(this.bestKey(), 0);
			h += '<p class="hv-best">' + (best ? (ico('trophy', 22) || '🏆') + ' Kỷ lục mức ' + DIFFS[this.d].name + ': <b>' + best + '</b> điểm' : 'Chưa có kỷ lục ở mức này – phá kỷ lục đầu tiên nào!') + '</p>';
		}
		b.innerHTML = h + '<button type="button" class="hv-btn hv-btn-big hv-go">Bắt đầu chơi ▶</button></div>';
		b.querySelectorAll('[data-d]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				self.d = parseInt(btn.getAttribute('data-d'), 10);
				store.set('diff_' + self.id, self.d);
				tone([520 + self.d * 120], 0.08);
				self.intro();
				var again = self.body.querySelector('[data-d="' + self.d + '"]');
				if (again) { again.focus(); }
			});
		});
		var tg = b.querySelector('[data-timed]');
		if (tg) { tg.addEventListener('change', function () { store.set('timed', tg.checked); }); }
		b.querySelector('.hv-go').addEventListener('click', function () { self.start(); });
	};
	Player.prototype.bestKey = function () {
		var lvl = this.root.getAttribute('data-level');
		return 'best_' + (lvl || this.id + '_' + this.grade + (this.d !== 2 ? '_d' + this.d : ''));
	};
	/* Đồng hồ đếm ngược từng câu: vòng tròn đổi màu xanh → cam → đỏ, tích tắc 5 giây cuối, hết giờ tính là sai. */
	Player.prototype.startClock = function (secs, onEnd) {
		var self = this;
		this.stopClock();
		if (!this.clockEl) { return; }
		var end = Date.now() + secs * 1000;
		var lastSec = -1;
		this.clockEl.hidden = false;
		this.clockSecs = secs;
		var draw = function () {
			if (!self.clockEl || !self.clockEl.isConnected) { self.stopClock(); return; }
			var left = Math.max(0, (end - Date.now()) / 1000);
			var whole = Math.ceil(left);
			self.clockLeft = left;
			self.clockEl.style.setProperty('--p', (left / secs).toFixed(3));
			if (whole !== lastSec) {
				lastSec = whole;
				self.clockEl.querySelector('b').textContent = whole;
				timerAlert(self.clockEl, whole, secs);
			}
			if (left <= 0) { self.stopClock(); onEnd(); }
		};
		draw();
		this.clockT = setInterval(draw, 100);
	};
	Player.prototype.stopClock = function () {
		clearInterval(this.clockT);
		this.clockT = null;
		if (this.clockEl) { this.clockEl.classList.remove('is-warn', 'is-danger'); }
	};
	/* ---------- Cảm giác game: đếm ngược, điểm, combo, Cú Bông cổ vũ, kỷ lục ---------- */
	Player.prototype.start = function () {
		var self = this;
		if (this.stopTimer) { this.stopTimer(); this.stopTimer = null; }
		this.points = 0;
		this.combo = 0;
		this.bestCombo = 0;
		this.root.classList.add('is-playing');
		this.updateScore();
		if (this.def.fromData) { return this.begin(); }
		var steps = ['3', '2', '1', 'Bắt đầu!'];
		var k = 0;
		var tick = function () {
			if (k >= steps.length) { return self.begin(); }
			self.body.innerHTML = '<div class="hv-countdown"><span>' + steps[k] + '</span></div>';
			tone(k < 3 ? [520] : [660, 880], k < 3 ? 0.12 : 0.14);
			k++;
			self.cdTimer = setTimeout(tick, k < 4 ? 650 : 750);
		};
		tick();
	};
	Player.prototype.updateScore = function () {
		if (!this.scoreEl) {
			this.scoreEl = el('div', 'hv-scorebar', '<span class="hv-score-in"></span><span class="hv-clock" hidden><svg viewBox="0 0 36 36" aria-hidden="true"><circle cx="18" cy="18" r="15.9"></circle><circle class="hv-clock-arc" cx="18" cy="18" r="15.9"></circle></svg><b>0</b></span>');
			this.scoreEl.setAttribute('aria-hidden', 'true');
			this.clockEl = this.scoreEl.querySelector('.hv-clock');
			this.root.insertBefore(this.scoreEl, this.body);
			this.coach = el('div', 'hv-coach', (CFG.img ? '<img src="' + esc(CFG.img + 'owl.webp') + '" width="64" height="64" alt="">' : '🦉') + '<span></span>');
			this.coach.setAttribute('aria-hidden', 'true');
			this.root.appendChild(this.coach);
		}
		var D = diffOf(this);
		this.scoreEl.querySelector('.hv-score-in').innerHTML = (this.def.fromData ? '' : '<span class="hv-dbadge d' + this.d + '">' + D.icon + ' ' + D.name + '</span>') +
			'<span class="hv-pts">' + (ico('star', 22) || '⭐') + ' <b>' + (this.points || 0) + '</b> điểm</span>' +
			(this.combo >= 2 ? '<span class="hv-combo">🔥 Combo x' + this.combo + '</span>' : '');
	};
	// Gọi mỗi lần trả lời: cộng điểm theo combo, hiện "+điểm" bay lên, Cú Bông nhảy mừng hoặc động viên.
	Player.prototype.hit = function (ok, anchor, bonus) {
		var self = this;
		if (ok) {
			this.combo++;
			this.bestCombo = Math.max(this.bestCombo, this.combo);
			var add = Math.round((10 * Math.min(this.combo, 5) + (bonus || 0)) * diffOf(this).mul);
			this.points += add;
			tone([660 + this.combo * 30, 880 + this.combo * 40], 0.1);
			if (this.combo >= 3 && this.combo % 3 === 0) { tone([784, 988, 1175], 0.08); }
			var r = this.root.getBoundingClientRect();
			var a = anchor && anchor.getBoundingClientRect ? anchor.getBoundingClientRect() : { left: r.left + r.width / 2, top: r.top + r.height / 2, width: 0, height: 0 };
			var f = el('span', 'hv-float', '+' + add + (bonus ? ' ⏱️' : '') + (this.combo >= 3 ? ' 🔥' : ''));
			f.style.left = (a.left - r.left + a.width / 2) + 'px';
			f.style.top = (a.top - r.top) + 'px';
			this.root.appendChild(f);
			setTimeout(function () { f.remove(); }, 900);
		} else {
			this.combo = 0;
			sfx.bad();
		}
		if (this.coach) {
			var c = this.coach;
			c.className = 'hv-coach ' + (ok ? 'is-cheer' : 'is-sad');
			var cs = c.querySelector('span');
			if (cs) { cs.textContent = ok ? pick(this.combo >= 3 ? ['Combo đỉnh quá!', 'Không cản nổi!', 'Siêu sao!'] : ['Giỏi quá!', 'Đúng rồi!', 'Tuyệt!', 'Hay lắm!']) : pick(['Cố lên!', 'Không sao đâu!', 'Thử lại nào!']); }
			clearTimeout(this.coachT);
			this.coachT = setTimeout(function () { c.className = 'hv-coach'; }, 1100);
		}
		this.updateScore();
		self.root.classList.toggle('is-hot', this.combo >= 3);
	};
	Player.prototype.begin = function () {
		if (this.def.custom === 'memory') { return this.memory(); }
		if (this.def.custom === 'order') { return this.order(); }
		if (this.def.custom === 'balloon') { return this.balloon(); }
		if (this.def.custom === 'fishing') { return this.fishing(); }
		if (this.def.custom === 'truefalse') { return this.truefalse(); }
		if (this.def.custom === 'climb') { return this.climb(); }
		if (this.def.custom === 'race') { return this.race(); }
		if (this.def.custom === 'whack') { return this.whack(); }
		if (this.def.custom === 'mouse') { return this.mouse(); }
		if (this.def.custom === 'typing') { return this.typing(); }
		if (this.def.custom === 'parts') { return this.parts(); }
		this.state = {};
		this.marks = [];
		var D = diffOf(this);
		this.maxHearts = parseInt(this.root.getAttribute('data-hearts'), 10) || (this.def.fromData ? 0 : D.hearts);
		this.timed = !this.def.fromData && store.get('timed', true);
		this.qSecs = Math.round(D.sec * (READ_MUL[this.id] || 1));
		this.hearts = this.maxHearts ? this.maxHearts : null;
		this.i = 0;
		this.right = 0;
		this.t0 = Date.now();
		if (this.def.fromData) {
			var data = [];
			try { data = JSON.parse(this.root.getAttribute('data-quiz') || '[]'); } catch (e) { data = []; }
			var limit = parseInt(this.root.getAttribute('data-limit'), 10) || data.length;
			this.deck = shuffle(data).slice(0, limit).map(function (r) { return { q: r.q, say: r.q, tag: r.t || '', why: r.e || '', o: { choices: shuffle([r.a].concat(r.w || [])), a: r.a } }; });
		} else if (this.def.deck) {
			this.deck = this.def.deck(this.grade).slice(0, this.def.total + D.extra);
		} else {
			// Dễ: xen câu của lớp dưới; Khó: thêm câu "thử thách" của lớp trên (nếu trò chơi có lớp đó).
			this.deck = [];
			var seen = {};
			var guard = 0;
			var gs = this.def.grades;
			while (this.deck.length < this.def.total + D.extra && guard++ < 150) {
				var gq = this.grade;
				var hard = false;
				if (this.d === 1 && gs.indexOf(gq - 1) > -1 && Math.random() < 0.5) { gq--; }
				if (this.d === 3 && gs.indexOf(gq + 1) > -1 && Math.random() < 0.25) { gq++; hard = true; }
				var q = this.def.make(gq, this.state);
				if (hard) { q.tag = '⚡ Thử thách'; }
				var k = q.q + (q.visual || '') + q.o.a;
				if (!seen[k]) { seen[k] = 1; this.deck.push(q); }
			}
		}
		if (D.choices < 4) {
			this.deck.forEach(function (q) {
				if (q.o.choices.length > D.choices) {
					var wrong = q.o.choices.filter(function (c) { return c !== q.o.a; }).slice(0, D.choices - 1);
					q.o.choices = shuffle([q.o.a].concat(wrong));
				}
			});
		}
		if (!this.deck.length) { this.body.innerHTML = '<p class="hv-empty">Chưa có câu hỏi.</p>'; return; }
		this.ask();
	};
	Player.prototype.progress = function () {
		var h = '<div class="hv-progress" aria-hidden="true">';
		for (var i = 0; i < this.deck.length; i++) {
			h += '<i class="' + (i < this.i ? (this.marks[i] ? 'ok' : 'no') : i === this.i ? 'cur' : '') + '"></i>';
		}
		return h + '</div>';
	};
	Player.prototype.ask = function () {
		var self = this;
		this.marks = this.marks || [];
		var q = this.deck[this.i];
		var h = this.progress();
		if (this.hearts !== null) {
			h += '<div class="hv-hearts" aria-label="Còn ' + this.hearts + ' tim">' + new Array(this.hearts + 1).join('❤️') + new Array(this.maxHearts - this.hearts + 1).join('🤍') + '</div>';
		}
		h += '<div class="hv-q">';
		if (q.tag) { h += '<span class="hv-tag">' + esc(q.tag) + '</span>'; }
		h += '<div class="hv-q-text' + (q.big ? ' is-big' : '') + '">' + esc(q.q) +
			' <button type="button" class="hv-icon-btn hv-read" aria-label="Đọc to câu hỏi" title="Đọc to">🔈</button></div>';
		if (q.note) { h += '<div class="hv-note">' + esc(q.note) + '</div>'; }
		if (q.visual) { h += '<div class="hv-visual">' + q.visual + '</div>'; }
		h += '<div class="hv-choices n' + q.o.choices.length + '">';
		q.o.choices.forEach(function (c) { h += '<button type="button" class="hv-choice' + (q.frac ? ' is-frac' : '') + '">' + fracHtml(c, q.frac) + '</button>'; });
		h += '</div><div class="hv-feedback" aria-hidden="true"></div></div>';
		this.body.innerHTML = h;
		this.body.querySelector('.hv-read').addEventListener('click', function () { speak(q.say || q.q); });
		var buttons = this.body.querySelectorAll('.hv-choice');
		buttons.forEach(function (btn, idx) {
			btn.addEventListener('click', function () { self.answer(q, q.o.choices[idx], btn, buttons); });
		});
		if (autoRead(this.grade)) { speak(q.say || q.q); }
		if (this.timed) {
			// Bé lớp 1–2 nghe đọc câu hỏi: cộng thêm vài giây.
			this.startClock(this.qSecs + (autoRead(this.grade) ? 3 : 0), function () {
				// Chỉ tính hết giờ nếu câu này vẫn đang hiện (bé chưa chuyển màn/chơi lại).
				if (self.deck && self.deck[self.i] === q && buttons[0] && self.body.contains(buttons[0])) { self.answer(q, null, null, buttons); }
			});
		}
	};
	function fracHtml(s, isFrac) {
		if (!isFrac || String(s).indexOf('/') < 0) { return esc(s); }
		var p = String(s).split('/');
		return '<span class="hv-frac"><span>' + esc(p[0]) + '</span><span>' + esc(p[1]) + '</span></span>';
	}
	Player.prototype.answer = function (q, val, btn, buttons) {
		var self = this;
		if (this.locked) { return; }
		this.locked = true;
		var timeout = val === null;
		var bonus = this.timed && this.clockT ? Math.round(Math.max(0, this.clockLeft || 0) / this.qSecs * 10) : 0;
		this.stopClock();
		var ok = !timeout && val === q.o.a;
		buttons.forEach(function (b, idx) {
			b.disabled = true;
			if (q.o.choices[idx] === q.o.a) { b.classList.add('is-right'); }
		});
		var fb = this.body.querySelector('.hv-feedback');
		if (ok) {
			this.right++;
			this.hit(true, btn, bonus);
			var nm = store.get('name', '');
			var praise = pick(['Giỏi quá đi!', 'Chính xác luôn!', 'Tuyệt vời!', 'Đúng rồi, em giỏi lắm!', 'Quá đỉnh!']) + (nm && Math.random() < 0.4 ? ' ' + nm + ' ơi!' : '');
			fb.innerHTML = esc(praise) + ' ' + pick(['🎉', '🌟', '👏', '💯', '🔥']);
			this.say(praise);
		} else {
			if (btn) { btn.classList.add('is-wrong'); }
			this.hit(false, btn);
			fb.innerHTML = (timeout ? '⏰ Hết giờ! ' : 'Gần đúng rồi! ') + 'Đáp án là <b>' + esc(q.o.a) + '</b>' + (q.hint ? ' (' + esc(q.hint) + ')' : '') + '. Lần sau em sẽ làm được! 💪';
			this.say('Chưa đúng. Đáp án là ' + q.o.a);
			if (this.hearts !== null) { this.hearts--; }
		}
		fb.className = 'hv-feedback ' + (ok ? 'ok' : 'no');
		this.marks[this.i] = ok;
		var go = function () {
			self.locked = false;
			self.i++;
			if (self.hearts === 0) { self.finish(self.right, self.deck.length, 'Ôi, hết tim mất rồi! Em ôn lại phần Bí kíp rồi đánh lại trùm nhé – chắc chắn sẽ thắng!'); return; }
			if (self.i < self.deck.length) { self.ask(); } else { self.finish(self.right, self.deck.length); }
		};
		if (q.why) {
			// Có lời giải thích: hiện "Vì sao?" và chờ bé bấm Tiếp tục để kịp đọc, hiểu.
			var box = el('div', 'hv-why-box', '<b>' + (ico('bulb', 28) || '💡') + ' Vì sao?</b><p>' + esc(q.why) + '</p><button type="button" class="hv-btn hv-next-q">' + (this.i + 1 < this.deck.length ? 'Câu tiếp theo ›' : 'Xem kết quả ›') + '</button>');
			fb.parentNode.insertBefore(box, fb.nextSibling);
			speak((ok ? 'Đúng rồi. ' : 'Đáp án là ' + q.o.a + '. ') + q.why);
			var nb = box.querySelector('.hv-next-q');
			nb.addEventListener('click', function () { stopSpeech(); go(); });
			setTimeout(function () { try { nb.focus({ preventScroll: true }); } catch (e) { nb.focus(); } }, 50);
			return;
		}
		if (q.word && ok) { speak(q.word); } else if (soundOn()) { speak(ok ? fb.textContent.replace(/[^\p{L}\p{N}\s,!.]/gu, '') : 'Đáp án là ' + q.o.a); }
		setTimeout(go, ok ? 1000 : 2400);
	};
	Player.prototype.finish = function (right, total, extra) {
		var self = this;
		var ratio = right / total;
		var got = ratio >= 0.9 ? 3 : ratio >= 0.6 ? 2 : ratio > 0 ? 1 : 0;
		if (this.hearts === 0) { got = Math.min(got, 1); }
		var secs = Math.round((Date.now() - (this.t0 || Date.now())) / 1000);
		var fast = this.def.timer && got === 3 && secs <= total * 6;
		var earned = got + (fast ? 1 : 0);
		var sticker = earned ? addStars(earned) : '';
		var lvl = this.root.getAttribute('data-level');
		var xp = earned * 10 + right * 2;
		var lvUp = addXp(xp, got, !!lvl);
		if (lvl && got > 0) {
			var done = store.get('done', {});
			done[lvl] = Math.max(done[lvl] || 0, got);
			store.set('done', done);
		}
		this.marks = [];
		this.root.classList.remove('is-playing', 'is-hot');
		if (this.stopTimer) { this.stopTimer(); this.stopTimer = null; }
		this.stopClock();
		if (this.clockEl) { this.clockEl.hidden = true; }
		var pts = (this.points || 0) + Math.round(got * 20 * diffOf(this).mul);
		var bestKey = this.bestKey();
		var best = store.get(bestKey, 0);
		var record = pts > best && pts > 0;
		if (record) { store.set(bestKey, pts); }
		var next = this.root.getAttribute('data-next-url');
		var home = this.root.getAttribute('data-home-url');
		var starsHtml = '';
		for (var i = 1; i <= 3; i++) { starsHtml += '<span class="' + (i <= got ? 'on' : '') + '">★</span>'; }
		var msg = lvl && got >= 2 ? (lvl.indexOf('boss-') === 0 ? 'Hạ gục trùm!' : 'Phá đảo màn này!') : got === 3 ? 'Xuất sắc!' : got === 2 ? 'Làm tốt lắm!' : got === 1 ? 'Cố lên, sắp được rồi!' : 'Mình thử lại nào!';
		this.body.innerHTML = '<div class="hv-result"><div class="hv-big-stars" aria-label="' + got + ' sao">' + starsHtml + '</div>' +
			(this.def.fromData ? '' : '<span class="hv-dbadge d' + this.d + '">' + diffOf(this).icon + ' Mức ' + diffOf(this).name + '</span>') +
			'<h3>' + msg + '</h3><p>' + (extra ? esc(extra) : 'Em làm đúng <b>' + right + '/' + total + '</b>' + (this.def.timer ? ' · ' + secs + ' giây' : '')) + '</p>' +
			(fast ? '<p class="hv-fast">⚡ Siêu tốc! Thêm 1 sao thưởng</p>' : '') +
			'<div class="hv-score-final"><b>' + pts + '</b> điểm' + (this.bestCombo >= 2 ? ' · combo dài nhất 🔥 x' + this.bestCombo : '') + '<small>' + (record ? (ico('trophy', 24) || '🏆') + ' KỶ LỤC MỚI!' : 'Kỷ lục của em: ' + Math.max(best, pts)) + '</small></div>' +
			'<p class="hv-reward"><span>' + (ico('star', 26) || '⭐') + ' +' + earned + '</span><span>' + (ico('gem', 26) || '💎') + ' +' + xp + ' XP</span></p>' +
			(lvUp ? '<div class="hv-levelup">' + (ico('party', 36) || '🎉') + ' Lên <b>cấp ' + lvUp + '</b> – ' + esc(titleOf(lvUp)) + '!</div>' : '') +
			(sticker ? '<div class="hv-new-sticker"><span>' + sticker + '</span>Em mở được nhãn dán mới!</div>' : '') +
			(!this.def.fromData && got === 3 && this.d < 3 ? '<p class="hv-up">' + DIFFS[this.d + 1].icon + ' Em giỏi quá! Thử sức ở mức <b>' + DIFFS[this.d + 1].name + '</b> nhé? <button type="button" class="hv-btn hv-btn-accent hv-harder">Lên mức ' + DIFFS[this.d + 1].name + ' ▶</button></p>' : '') +
			(!this.def.fromData && got <= 1 && this.d > 1 ? '<p class="hv-up">Thử mức <b>' + DIFFS[this.d - 1].name + '</b> ' + DIFFS[this.d - 1].icon + ' để luyện thêm nhé! <button type="button" class="hv-btn hv-btn-white hv-easier">Chơi mức ' + DIFFS[this.d - 1].name + '</button></p>' : '') +
			'<div class="hv-actions">' + (next && got >= 1 ? '<a class="hv-btn hv-btn-accent hv-btn-big" href="' + esc(next) + '">' + esc(this.root.getAttribute('data-next-label') || 'Màn tiếp theo') + ' ▶</a>' : '') +
			'<button type="button" class="hv-btn hv-again">Chơi lại ↻</button>' +
			(home ? '<a class="hv-btn hv-btn-ghost" href="' + esc(home) + '">🏝️ Về đảo</a>' : '') +
			(CFG.gamesUrl && !this.def.fromData ? '<a class="hv-btn hv-btn-ghost" href="' + esc(CFG.gamesUrl) + '">Trò chơi khác</a>' : '') + '</div></div>';
		renderProgress();
		try { this.root.dispatchEvent(new CustomEvent('hv:done', { bubbles: true, detail: { level: lvl, stars: got } })); } catch (e) { /* trình duyệt cũ */ }
		speak(msg + (got ? ' Em được ' + earned + ' sao.' : ''));
		this.body.querySelector('.hv-again').addEventListener('click', function () { self.start(); });
		var shift = function (delta) { self.d = Math.max(1, Math.min(3, self.d + delta)); store.set('diff_' + self.id, self.d); self.start(); };
		var hb = this.body.querySelector('.hv-harder');
		if (hb) { hb.addEventListener('click', function () { shift(1); }); }
		var eb = this.body.querySelector('.hv-easier');
		if (eb) { eb.addEventListener('click', function () { shift(-1); }); }
		if (got >= 2) { sfx.win(); confetti(this.root); }
		this.say(msg + ' Em làm đúng ' + right + ' trên ' + total);
	};

	/* Lật thẻ */
	Player.prototype.memory = function () {
		var self = this;
		var topic = pick(EN_TOPICS[this.grade] || EN_TOPICS[3]);
		var pairs = shuffle(EN[topic]).slice(0, [0, this.grade <= 2 ? 3 : 4, this.grade <= 2 ? 4 : 6, 6][this.d] || 4);
		var cards = [];
		pairs.forEach(function (p, i) {
			cards.push({ id: i, face: p[0], kind: 'pic', word: p[1] });
			cards.push({ id: i, face: p[1], kind: 'word', word: p[1] });
		});
		cards = shuffle(cards);
		var moves = 0;
		var found = 0;
		var open = [];
		this.t0 = Date.now();
		var h = '<div class="hv-topic">Chủ đề: <b>' + esc(EN_NAMES[topic]) + '</b></div><div class="hv-cards n' + cards.length + '">';
		cards.forEach(function (c, i) {
			h += '<button type="button" class="hv-card" data-i="' + i + '" aria-label="Thẻ ' + (i + 1) + '"><span class="hv-card-back" aria-hidden="true">?</span><span class="hv-card-face ' + c.kind + '">' + esc(c.face) + '</span></button>';
		});
		h += '</div><div class="hv-moves">Số lượt: <b>0</b></div>';
		this.body.innerHTML = h;
		var movesEl = this.body.querySelector('.hv-moves b');
		this.body.querySelectorAll('.hv-card').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var c = cards[+btn.getAttribute('data-i')];
				if (btn.classList.contains('is-open') || open.length === 2) { return; }
				btn.classList.add('is-open');
				btn.setAttribute('aria-label', c.face);
				sfx.flip();
				speak(c.word, 'en');
				open.push({ btn: btn, c: c });
				if (open.length < 2) { return; }
				moves++;
				movesEl.textContent = moves;
				if (open[0].c.id === open[1].c.id) {
					open.forEach(function (o) { o.btn.classList.add('is-done'); o.btn.disabled = true; });
					open = [];
					found++;
					self.hit(true, btn);
					self.say('Ghép đúng: ' + c.word);
					if (found === pairs.length) {
						// Lượt thừa (ngoài số cặp) càng ít càng nhiều sao.
						var extra = moves - pairs.length;
						var score = extra <= pairs.length ? 10 : extra <= pairs.length * 2 ? 7 : 4;
						setTimeout(function () { self.finish(score, 10, 'Em ghép xong ' + pairs.length + ' cặp sau ' + moves + ' lượt lật'); }, 700);
					}
				} else {
					setTimeout(function () {
						open.forEach(function (o) { o.btn.classList.remove('is-open'); o.btn.setAttribute('aria-label', 'Thẻ úp'); });
						open = [];
					}, 1000);
				}
			});
		});
	};

	/* ---------- Bắn bóng bay: bóng mang đáp án trôi lên, chạm đúng quả trước khi bay mất ---------- */
	Player.prototype.balloon = function () {
		var self = this;
		var total = 10;
		var dur = ([0, 11, 10, 9.5, 9, 8.5][this.grade] || 9.5) * diffOf(this).time;
		var colors = ['#ff5d73', '#2ea3f2', '#58b847', '#ffb81c', '#9775fa'];
		var deck = [];
		var seen = {};
		var guard = 0;
		while (deck.length < total && guard++ < 200) {
			var q0 = mathQ(this.grade);
			if (!seen[q0.q]) { seen[q0.q] = 1; deck.push(q0); }
		}
		if (this.d === 1) {
			deck.forEach(function (q) { var w = q.o.choices.filter(function (c) { return c !== q.o.a; }).slice(0, 2); q.o.choices = shuffle([q.o.a].concat(w)); });
		}
		this.deck = deck;
		this.i = 0;
		this.right = 0;
		this.marks = [];
		this.t0 = Date.now();
		var next = function () {
			if (self.i >= deck.length) { return self.finish(self.right, deck.length); }
			var q = deck[self.i];
			var done = false;
			var n = q.o.choices.length;
			var h = self.progress() + '<div class="hv-q"><div class="hv-q-text' + (q.big ? ' is-big' : '') + '">' + esc(q.q) +
				' <button type="button" class="hv-icon-btn hv-read" aria-label="Đọc to câu hỏi">🔈</button></div>' + (q.note ? '<div class="hv-note">' + esc(q.note) + '</div>' : '') +
				'<div class="hv-sky-arena" role="group" aria-label="Các quả bóng đáp án">';
			shuffle(q.o.choices.map(function (c, k) { return k; })).forEach(function (k, pos) {
				var x = n > 1 ? 4 + pos * (72 / (n - 1)) : 40;
				h += '<button type="button" class="hv-balloon" data-k="' + k + '" style="--x:' + x.toFixed(1) + '%;--d:' + (dur + Math.random() * 1.6).toFixed(2) + 's;--bc:' + colors[pos % colors.length] + ';animation-delay:' + (pos * 0.3).toFixed(2) + 's"><span>' + fracHtml(q.o.choices[k], q.frac) + '</span></button>';
			});
			h += '</div><div class="hv-feedback" aria-live="polite"></div></div>';
			self.body.innerHTML = h;
			var arena = self.body.querySelector('.hv-sky-arena');
			var fb = self.body.querySelector('.hv-feedback');
			self.body.querySelector('.hv-read').addEventListener('click', function () { speak(q.say || q.q); });
			if (autoRead(self.grade)) { speak(q.say || q.q); }
			var end = function (ok, msg) {
				if (done) { return; }
				done = true;
				self.marks[self.i] = ok;
				if (ok) { self.right++; }
				self.hit(ok, arena.querySelector('.is-pop') || arena);
				fb.className = 'hv-feedback ' + (ok ? 'ok' : 'no');
				fb.innerHTML = msg;
				arena.querySelectorAll('.hv-balloon').forEach(function (b) {
					b.disabled = true;
					b.style.animationPlayState = 'paused';
					if (q.o.choices[+b.getAttribute('data-k')] === q.o.a && !ok) { b.classList.add('is-answer'); }
				});
				setTimeout(function () { self.i++; next(); }, ok ? 900 : 2200);
			};
			arena.querySelectorAll('.hv-balloon').forEach(function (b) {
				var val = q.o.choices[+b.getAttribute('data-k')];
				b.addEventListener('click', function () {
					if (done) { return; }
					if (val === q.o.a) {
						b.classList.add('is-pop');
						end(true, esc(pick(['Bùm! Chính xác!', 'Trúng rồi! Giỏi quá!', 'Bắn trúng phóc!'])) + ' 🎈');
					} else {
						b.classList.add('is-wrong');
						end(false, 'Chưa đúng rồi. Quả bóng đúng là <b>' + esc(q.o.a) + '</b>');
					}
				});
				if (val === q.o.a) {
					b.addEventListener('animationend', function (e) {
						if (e.animationName === 'hv-rise') { end(false, 'Ôi, bóng bay mất rồi! Đáp án là <b>' + esc(q.o.a) + '</b>'); }
					});
				}
			});
		};
		next();
	};

	/* ---------- Câu cá: nghe Cú Bông đọc rồi câu đúng con cá ---------- */
	Player.prototype.fishing = function () {
		var self = this;
		var total = 8;
		var english = this.grade >= 3;
		var hasVoice = soundOn() && (!!TTS || ('speechSynthesis' in window && langVoices(english ? 'en' : 'vi').length > 0));
		var deck = [];
		if (english) {
			var pool = [];
			(EN_TOPICS[this.grade] || EN_TOPICS[3]).forEach(function (t) { pool = pool.concat(EN[t]); });
			shuffle(pool).slice(0, total).forEach(function (w) {
				var others = shuffle(pool.filter(function (o) { return o[1] !== w[1] && o[0] !== w[0]; })).slice(0, 2);
				deck.push({ say: w[1], lang: 'en', show: hasVoice ? '' : w[1], ans: w[0], items: shuffle([w[0]].concat(others.map(function (o) { return o[0]; }))), pic: true, word: w[1] });
			});
		} else {
			shuffle(WORDS).slice(0, total).forEach(function (w) {
				var near = shuffle(WORDS.filter(function (o) { return o[1] !== w[1] && (o[3] === w[3] || o[2] === w[2]); }));
				var far = shuffle(WORDS.filter(function (o) { return o[1] !== w[1]; }));
				var opts = [w[1]];
				near.concat(far).forEach(function (o) { if (opts.length < 3 && opts.indexOf(o[1]) < 0) { opts.push(o[1]); } });
				deck.push({ say: w[1], lang: 'vi', show: w[0], ans: w[1], items: shuffle(opts), pic: false, word: w[1] });
			});
		}
		this.deck = deck;
		this.i = 0;
		this.right = 0;
		this.marks = [];
		this.t0 = Date.now();
		var next = function () {
			if (self.i >= deck.length) { return self.finish(self.right, deck.length); }
			var q = deck[self.i];
			var done = false;
			var h = self.progress() + '<div class="hv-q"><div class="hv-q-text">' + (english ? 'Nghe và câu con cá mang hình đúng' : 'Câu con cá mang chữ đúng với hình') +
				' <button type="button" class="hv-icon-btn hv-read" aria-label="Nghe lại">🔈</button></div>' +
				(q.show ? '<div class="hv-fish-prompt">' + (english ? '<b>' + esc(q.show) + '</b>' : '<span class="hv-pic" aria-hidden="true">' + esc(q.show) + '</span>') + '</div>' : '<div class="hv-fish-prompt"><button type="button" class="hv-btn hv-btn-accent hv-replay">🔈 Nghe lại</button></div>') +
				'<div class="hv-pond" role="group" aria-label="Các con cá">';
			q.items.forEach(function (it, k) {
				h += '<button type="button" class="hv-fish' + (k % 2 ? ' rev' : '') + '" style="--y:' + (14 + k * 30) + '%;--d:' + (7 + Math.random() * 3).toFixed(2) + 's;animation-delay:-' + (Math.random() * 5).toFixed(2) + 's" data-v="' + esc(it) + '">' +
					(CFG.img ? '<img src="' + esc(CFG.img + (k % 2 ? 'fish2' : 'fish') + '.webp') + '" width="72" height="72" alt="">' : '🐟') + '<span class="' + (q.pic ? 'is-pic' : '') + '">' + esc(it) + '</span></button>';
			});
			h += '</div><div class="hv-feedback" aria-live="polite"></div></div>';
			self.body.innerHTML = h;
			var fb = self.body.querySelector('.hv-feedback');
			var say = function () { speak(english ? q.say : 'Câu chữ: ' + q.say, q.lang); };
			self.body.querySelectorAll('.hv-read, .hv-replay').forEach(function (b) { b.addEventListener('click', say); });
			say();
			self.body.querySelectorAll('.hv-fish').forEach(function (b) {
				b.addEventListener('click', function () {
					if (done) { return; }
					done = true;
					var ok = b.getAttribute('data-v') === q.ans;
					self.marks[self.i] = ok;
					self.body.querySelectorAll('.hv-fish').forEach(function (f) { f.disabled = true; f.style.animationPlayState = 'paused'; if (f.getAttribute('data-v') === q.ans) { f.classList.add('is-caught'); } });
					self.hit(ok, b);
					if (ok) { self.right++; fb.className = 'hv-feedback ok'; fb.innerHTML = 'Câu trúng rồi! 🎣 <b>' + esc(q.word) + '</b>'; } else { b.classList.add('is-wrong'); fb.className = 'hv-feedback no'; fb.innerHTML = 'Con cá đúng mang <b>' + esc(q.ans) + '</b>' + (english ? ' – <i>' + esc(q.word) + '</i>' : ''); }
					speak(q.word, q.lang);
					setTimeout(function () { self.i++; next(); }, ok ? 1200 : 2400);
				});
			});
		};
		next();
	};

	/* ---------- Đúng hay Sai: 60 giây trả lời nhanh ---------- */
	function tfStatement(g) {
		var truth = Math.random() < 0.5;
		if (Math.random() < 0.5) {
			var q = mathQ(g);
			var guard = 0;
			while ((!q.big || q.frac) && guard++ < 20) { q = mathQ(g); }
			var wrong = q.o.choices.filter(function (c) { return c !== q.o.a; });
			var shown = truth || !wrong.length ? q.o.a : pick(wrong);
			return { text: q.q.replace('= ?', '= ' + shown), truth: shown === q.o.a, math: true, ans: q.o.a };
		}
		var r = pick(QUIZ[g] || QUIZ[1]);
		var opts = r.slice(1, r.length - 1);
		var shownA = truth ? r[1] : pick(opts.slice(1));
		return { text: r[0].replace(/[:?]\s*$/, '') + ': ' + shownA, truth: shownA === r[1], ans: r[1] };
	}
	Player.prototype.truefalse = function () {
		var self = this;
		var limit = Math.round(60 * diffOf(this).time / 5) * 5;
		var left = limit;
		var total = 0;
		this.right = 0;
		this.marks = [];
		this.t0 = Date.now();
		var cur = tfStatement(this.grade);
		var busy = false;
		this.body.innerHTML = '<div class="hv-tf"><div class="hv-timer"><i></i><b>' + limit + ' giây</b></div><div class="hv-tf-score">Đúng <b>0</b> / 0</div><div class="hv-tf-card" aria-live="polite"></div>' +
			'<div class="hv-tf-btns"><button type="button" class="hv-tf-btn yes">' + (ico('check', 40) || '✔') + ' Đúng</button><button type="button" class="hv-tf-btn no">' + (ico('cross', 40) || '✘') + ' Sai</button></div><div class="hv-feedback"></div></div>';
		var card = this.body.querySelector('.hv-tf-card');
		var bar = this.body.querySelector('.hv-timer i');
		var tlabel = this.body.querySelector('.hv-timer b');
		var score = this.body.querySelector('.hv-tf-score');
		var fb = this.body.querySelector('.hv-feedback');
		var show = function () { card.textContent = cur.text; card.className = 'hv-tf-card'; if (autoRead(self.grade)) { speak(cur.text); } };
		show();
		var timer = setInterval(function () {
			left--;
			bar.style.width = (left / limit * 100) + '%';
			tlabel.textContent = left + ' giây';
			timerAlert(bar.parentNode, left, limit);
			if (left <= 0) {
				clearInterval(timer);
				self.finish(self.right, Math.max(total, 10), 'Em trả lời đúng ' + self.right + '/' + total + ' câu trong ' + limit + ' giây');
			}
		}, 1000);
		this.stopTimer = function () { clearInterval(timer); };
		var answer = function (say) {
			if (busy || left <= 0) { return; }
			busy = true;
			total++;
			var ok = say === cur.truth;
			if (ok) { self.right++; }
			self.hit(ok, card);
			card.className = 'hv-tf-card ' + (ok ? 'ok' : 'no');
			fb.className = 'hv-feedback ' + (ok ? 'ok' : 'no');
			fb.innerHTML = ok ? 'Chính xác!' : (cur.truth ? 'Câu này đúng đấy!' : 'Câu này sai. Đáp án đúng: <b>' + esc(cur.ans) + '</b>');
			score.innerHTML = 'Đúng <b>' + self.right + '</b> / ' + total;
			setTimeout(function () { busy = false; fb.innerHTML = ''; cur = tfStatement(self.grade); if (left > 0) { show(); } }, ok ? 500 : 1400);
		};
		this.body.querySelector('.hv-tf-btn.yes').addEventListener('click', function () { answer(true); });
		this.body.querySelector('.hv-tf-btn.no').addEventListener('click', function () { answer(false); });
	};

	/* ---------- Leo núi tri thức: 10 nấc khó dần, 2 quyền trợ giúp ---------- */
	Player.prototype.climb = function () {
		var self = this;
		var g = this.grade;
		var facts = shuffle(QUIZ[g] || QUIZ[1]).slice(0, 4).map(function (r) {
			return { q: r[0], say: r[0], tag: r[r.length - 1], o: { choices: shuffle(r.slice(1, r.length - 1)), a: r[1] } };
		});
		var deck = [mathQ(Math.max(1, g - 1)), mathQ(Math.max(1, g - 1)), facts[0], mathQ(g), facts[1], mathQ(g), facts[2], mathQ(g), facts[3] || mathQ(g), mathQ(Math.min(5, g + 1))];
		var level = 0;
		var help = { half: true, owl: this.d < 3 }; // Mức Khó chỉ có 1 quyền trợ giúp.
		this.deck = deck;
		this.t0 = Date.now();
		this.marks = [];
		var draw = function () {
			var q = deck[level];
			var h = '<div class="hv-climb"><ol class="hv-ladder" aria-label="Nấc núi">';
			for (var k = deck.length - 1; k >= 0; k--) {
				h += '<li class="' + (k < level ? 'ok' : k === level ? 'cur' : '') + '">' + (k === deck.length - 1 ? (ico('flag', 22) || '🚩') + ' Đỉnh' : 'Nấc ' + (k + 1)) + '</li>';
			}
			h += '</ol><div class="hv-q">' + (q.tag ? '<span class="hv-tag">' + esc(q.tag) + '</span>' : '') +
				'<div class="hv-q-text' + (q.big ? ' is-big' : '') + '">' + esc(q.q) + ' <button type="button" class="hv-icon-btn hv-read" aria-label="Đọc to câu hỏi">🔈</button></div>' +
				(q.visual ? '<div class="hv-visual">' + q.visual + '</div>' : '') +
				'<div class="hv-lifelines"><button type="button" class="hv-chip" data-help="half"' + (help.half ? '' : ' disabled') + '>½ Bỏ bớt đáp án sai</button><button type="button" class="hv-chip" data-help="owl"' + (help.owl ? '' : ' disabled') + '>🦉 Hỏi Cú Bông</button></div>' +
				'<div class="hv-choices n' + q.o.choices.length + '">';
			q.o.choices.forEach(function (c) { h += '<button type="button" class="hv-choice' + (q.frac ? ' is-frac' : '') + '">' + fracHtml(c, q.frac) + '</button>'; });
			h += '</div><div class="hv-feedback" aria-live="polite"></div></div></div>';
			self.body.innerHTML = h;
			var fb = self.body.querySelector('.hv-feedback');
			var buttons = self.body.querySelectorAll('.hv-choice');
			self.body.querySelector('.hv-read').addEventListener('click', function () { speak(q.say || q.q); });
			if (autoRead(g)) { speak(q.say || q.q); }
			self.body.querySelectorAll('[data-help]').forEach(function (hb) {
				hb.addEventListener('click', function () {
					var kind = hb.getAttribute('data-help');
					if (!help[kind]) { return; }
					help[kind] = false;
					hb.disabled = true;
					if (kind === 'half') {
						var wrongBtns = shuffle(Array.prototype.filter.call(buttons, function (b, idx) { return q.o.choices[idx] !== q.o.a; }));
						wrongBtns.slice(0, Math.max(1, wrongBtns.length - 1)).forEach(function (b) { b.disabled = true; b.classList.add('is-gone'); });
					} else {
						var msg = 'Cú Bông nghĩ đáp án là: ' + q.o.a;
						fb.className = 'hv-feedback ok';
						fb.textContent = '🦉 ' + msg;
						speak(msg);
					}
				});
			});
			buttons.forEach(function (btn, idx) {
				btn.addEventListener('click', function () {
					buttons.forEach(function (b, k) { b.disabled = true; if (q.o.choices[k] === q.o.a) { b.classList.add('is-right'); } });
					if (q.o.choices[idx] === q.o.a) {
						self.hit(true, btn);
						level++;
						self.marks.push(true);
						fb.className = 'hv-feedback ok';
						fb.textContent = level === deck.length ? 'Lên tới đỉnh rồi!' : 'Leo lên nấc ' + (level + 1) + '!';
						setTimeout(function () { if (level >= deck.length) { self.finish(deck.length, deck.length, 'Em đã chinh phục đỉnh núi tri thức! 🏔️'); } else { draw(); } }, 1000);
					} else {
						btn.classList.add('is-wrong');
						self.hit(false, btn);
						fb.className = 'hv-feedback no';
						fb.innerHTML = 'Đáp án đúng là <b>' + esc(q.o.a) + '</b>. Em đã leo được ' + level + '/' + deck.length + ' nấc!';
						setTimeout(function () { self.finish(level, deck.length, 'Em leo được ' + level + '/' + deck.length + ' nấc núi'); }, 2400);
					}
				});
			});
		};
		draw();
	};

	/* ---------- Đua xe tính nhẩm: trả lời đúng để xe chạy, về đích trước xe của Cú Bông ---------- */
	Player.prototype.race = function () {
		var self = this;
		var g = this.grade;
		var goal = 10;
		var me = 0;
		var over = false;
		var botTime = ([0, 80, 75, 70, 65, 60][g] || 70) * diffOf(this).time;
		var started = Date.now();
		var seen = {};
		this.right = 0;
		this.marks = [];
		this.deck = [];
		this.t0 = started;
		this.body.innerHTML = '<div class="hv-race"><div class="hv-track">' +
			'<div class="hv-lane"><span class="hv-car me" style="--p:0">' + (ico('racecar', 64, 'hv-car-img') || '🏎️') + '<em>Em</em></span></div>' +
			'<div class="hv-lane"><span class="hv-car bot" style="--p:0">' + (ico('car', 64, 'hv-car-img') || '🚙') + '<em>Cú Bông</em></span></div>' +
			'<span class="hv-finish">' + (ico('finish', 40) || '🏁') + '</span></div><div class="hv-race-q"></div></div>';
		var meCar = this.body.querySelector('.hv-car.me');
		var botCar = this.body.querySelector('.hv-car.bot');
		var qbox = this.body.querySelector('.hv-race-q');
		var end = function (win) {
			if (over) { return; }
			over = true;
			clearInterval(timer);
			setTimeout(function () { self.finish(win ? goal : me, goal, win ? 'Em về nhất! Vượt mặt xe của Cú Bông rồi! 🏆' : 'Cú Bông về đích trước mất rồi. Em chạy được ' + me + '/' + goal + ' chặng – đua lại nhé!'); }, 700);
		};
		var timer = setInterval(function () {
			var p = Math.min(1, (Date.now() - started) / (botTime * 1000));
			botCar.style.setProperty('--p', p);
			if (p >= 1) { end(false); }
		}, 120);
		this.stopTimer = function () { clearInterval(timer); over = true; };
		var ask = function () {
			if (over) { return; }
			var q;
			var guard = 0;
			do { q = mathQ(g); } while (seen[q.q] && guard++ < 30);
			seen[q.q] = 1;
			var h = '<div class="hv-q-text' + (q.big ? ' is-big' : '') + '">' + esc(q.q) + '</div><div class="hv-choices n' + q.o.choices.length + '">';
			q.o.choices.forEach(function (c) { h += '<button type="button" class="hv-choice' + (q.frac ? ' is-frac' : '') + '">' + fracHtml(c, q.frac) + '</button>'; });
			qbox.innerHTML = h + '</div>';
			if (autoRead(g)) { speak(q.say || q.q); }
			var buttons = qbox.querySelectorAll('.hv-choice');
			buttons.forEach(function (b, idx) {
				b.addEventListener('click', function () {
					if (over) { return; }
					buttons.forEach(function (x) { x.disabled = true; });
					var ok = q.o.choices[idx] === q.o.a;
					self.hit(ok, b);
					if (ok) {
						me++;
						self.right++;
						b.classList.add('is-right');
						meCar.style.setProperty('--p', me / goal);
						meCar.classList.remove('is-boost');
						void meCar.offsetWidth;
						meCar.classList.add('is-boost');
						if (me >= goal) { end(true); return; }
						setTimeout(ask, 350);
					} else {
						b.classList.add('is-wrong');
						buttons.forEach(function (x, k) { if (q.o.choices[k] === q.o.a) { x.classList.add('is-right'); } });
						meCar.classList.remove('is-spin');
						void meCar.offsetWidth;
						meCar.classList.add('is-spin');
						setTimeout(ask, 1300);
					}
				});
			});
		};
		ask();
	};

	/* ---------- Đập chuột chũi: đập đúng chú chuột theo yêu cầu trong 45 giây ---------- */
	Player.prototype.whack = function () {
		var self = this;
		var g = this.grade;
		var left = 45;
		var hits = 0;
		var wrongs = 0;
		var rule = null;
		var ruleHits = 0;
		var stay = Math.max(1100, 1900 - g * 140) * diffOf(this).time;
		this.right = 0;
		this.marks = [];
		this.t0 = Date.now();
		var newRule = function () {
			var kinds = g >= 2 ? ['math', 'even', 'odd', 'gt'] : ['math', 'gt', 'math'];
			var kind = pick(kinds);
			var max = [0, 10, 50, 100, 1000, 1000][g] || 100;
			if (kind === 'math') {
				var q;
				var guard = 0;
				do { q = mathQ(g); } while ((!q.big || q.frac) && guard++ < 30);
				var others = q.o.choices.filter(function (c) { return c !== q.o.a; });
				rule = { text: 'Đập chuột mang kết quả của ' + q.q.replace(' = ?', ''), say: q.say, ok: function (v) { return v === q.o.a; }, make: function (good) { return good ? q.o.a : pick(others.length ? others : ['?']); } };
			} else if (kind === 'even' || kind === 'odd') {
				var even = kind === 'even';
				rule = { text: 'Đập chuột mang SỐ ' + (even ? 'CHẴN' : 'LẺ'), ok: function (v) { return (+v.replace(/\D/g, '')) % 2 === (even ? 0 : 1); }, make: function (good) { var n; do { n = rnd(1, max); } while ((n % 2 === 0) !== (good === even)); return num(n); } };
			} else {
				var t = g === 1 ? rnd(3, 7) : rnd(Math.round(max / 5), Math.round(max * 0.7));
				rule = { text: 'Đập chuột mang số LỚN HƠN ' + num(t), ok: function (v) { return +v.replace(/\D/g, '') > t; }, make: function (good) { return num(good ? rnd(t + 1, Math.max(t + 1, max)) : rnd(Math.max(0, t - (g === 1 ? 5 : max / 4)), t)); } };
			}
			ruleHits = 0;
			var rb = self.body.querySelector('.hv-rule');
			rb.textContent = rule.text;
			rb.classList.remove('is-new');
			void rb.offsetWidth;
			rb.classList.add('is-new');
			speak(rule.say ? 'Đập chuột mang kết quả của ' + rule.say.replace(/ bằng (mấy|bao nhiêu)\?$/, '') : rule.text);
		};
		var h = '<div class="hv-whack"><div class="hv-timer"><i></i><b>45 giây</b></div><div class="hv-rule" aria-live="polite"></div><div class="hv-holes">';
		for (var i = 0; i < 9; i++) { h += '<div class="hv-hole"><button type="button" class="hv-mole" tabindex="-1">' + (ico('mole', 72, 'hv-mole-img') || '🐭') + '<b></b></button></div>'; }
		this.body.innerHTML = h + '</div><div class="hv-tf-score">Đập trúng <b>0</b></div></div>';
		var moles = Array.prototype.slice.call(this.body.querySelectorAll('.hv-mole'));
		var bar = this.body.querySelector('.hv-timer i');
		var tl = this.body.querySelector('.hv-timer b');
		var score = this.body.querySelector('.hv-tf-score b');
		newRule();
		var pop = function () {
			var free = moles.filter(function (m) { return !m.classList.contains('is-up'); });
			if (!free.length) { return; }
			var m = pick(free);
			var good = Math.random() < 0.45;
			var v = rule.make(good);
			m.querySelector('b').textContent = v;
			m.setAttribute('data-v', v);
			m.tabIndex = 0;
			m.classList.remove('is-hit', 'is-miss');
			m.classList.add('is-up');
			m._t = setTimeout(function () { m.classList.remove('is-up'); m.tabIndex = -1; }, stay);
		};
		moles.forEach(function (m) {
			m.addEventListener('click', function () {
				if (!m.classList.contains('is-up') || left <= 0) { return; }
				clearTimeout(m._t);
				var ok = rule.ok(m.getAttribute('data-v'));
				m.classList.add(ok ? 'is-hit' : 'is-miss');
				self.hit(ok, m);
				if (ok) { hits++; self.right++; ruleHits++; score.textContent = hits; } else { wrongs++; }
				setTimeout(function () { m.classList.remove('is-up'); m.tabIndex = -1; }, 350);
				if (ok && ruleHits >= 3) { setTimeout(newRule, 400); }
			});
		});
		var spawn = setInterval(pop, Math.max(450, 950 - g * 70) * diffOf(this).time);
		var clock = setInterval(function () {
			left--;
			bar.style.width = (left / 45 * 100) + '%';
			tl.textContent = left + ' giây';
			timerAlert(bar.parentNode, left, 45);
			if (left <= 0) {
				self.stopTimer();
				self.finish(hits, Math.max(hits + wrongs, 12), 'Em đập trúng ' + hits + ' chú chuột trong 45 giây' + (wrongs ? ' (nhầm ' + wrongs + ' lần)' : ''));
			}
		}, 1000);
		this.stopTimer = function () { clearInterval(spawn); clearInterval(clock); moles.forEach(function (m) { clearTimeout(m._t); }); };
	};

	/* ---------- Hái táo: tập nháy chuột, nháy đúp, kéo thả ---------- */
	Player.prototype.mouse = function () {
		var self = this;
		var small = this.grade <= 1;
		var stages = [
			{ type: 'click', n: small ? 6 : 8, text: 'Nháy chuột (chạm) vào từng quả táo đỏ để hái', key: 'apple' },
			{ type: 'dbl', n: small ? 4 : 5, text: 'Nháy đúp – nháy thật nhanh 2 lần – vào quả táo vàng', key: 'apple' },
			{ type: 'drag', n: small ? 4 : 5, text: 'Kéo quả táo thả vào giỏ', key: 'apple' }
		];
		var si = 0;
		var done = 0;
		var total = stages.reduce(function (a, s) { return a + s.n; }, 0);
		var mistakes = 0;
		this.right = 0;
		this.marks = [];
		this.t0 = Date.now();
		this.body.innerHTML = '<div class="hv-orchard-wrap"><div class="hv-stage-title" aria-live="polite"></div><div class="hv-orchard">' + (ico('tree', 192, 'hv-tree') || '') + '<div class="hv-basket">' + (ico('basket', 96) || '🧺') + '<b>0</b></div></div><div class="hv-stage-dots"></div></div>';
		var orchard = this.body.querySelector('.hv-orchard');
		var basket = this.body.querySelector('.hv-basket');
		var title = this.body.querySelector('.hv-stage-title');
		var dots = this.body.querySelector('.hv-stage-dots');
		var place = function (a) {
			a.style.left = rnd(18, 66) + '%';
			a.style.top = rnd(8, 46) + '%';
		};
		var collect = function (a) {
			done++;
			self.right++;
			self.hit(true, a);
			basket.querySelector('b').textContent = done;
			a.classList.add('is-picked');
			setTimeout(function () { a.remove(); }, 450);
			if (orchard.querySelectorAll('.hv-apple:not(.is-picked)').length === 0) { setTimeout(stage, 600); }
		};
		var stage = function () {
			if (si >= stages.length) {
				var secs = Math.round((Date.now() - self.t0) / 1000);
				var limit = total * (small ? 4 : 3);
				var score = secs <= limit ? 10 : secs <= limit * 1.6 ? 7 : 4;
				score = Math.max(1, score - Math.floor(mistakes / 4));
				return self.finish(score, 10, 'Em hái xong ' + total + ' quả táo trong ' + secs + ' giây – đôi tay dùng chuột thật khéo!');
			}
			var st = stages[si++];
			title.innerHTML = '<b>Bước ' + si + '/3:</b> ' + esc(st.text);
			dots.innerHTML = stages.map(function (x, k) { return '<i class="' + (k < si - 1 ? 'ok' : k === si - 1 ? 'cur' : '') + '"></i>'; }).join('');
			speak(st.text);
			for (var k = 0; k < st.n; k++) {
				var a = el('button', 'hv-apple' + (st.type === 'dbl' ? ' is-gold' : '') + (st.type === 'drag' ? ' is-drag' : ''), ico('apple', 56) || '🍎');
				a.type = 'button';
				a.setAttribute('aria-label', st.type === 'drag' ? 'Quả táo – kéo vào giỏ' : 'Quả táo');
				place(a);
				orchard.appendChild(a);
				bind(a, st.type);
			}
		};
		var bind = function (a, type) {
			if (type === 'click') {
				a.addEventListener('click', function () { if (!a.classList.contains('is-picked')) { collect(a); } });
			} else if (type === 'dbl') {
				var last = 0;
				a.addEventListener('click', function () {
					var now = Date.now();
					if (now - last < 450) { if (!a.classList.contains('is-picked')) { collect(a); } } else {
						a.classList.remove('is-wobble');
						void a.offsetWidth;
						a.classList.add('is-wobble');
					}
					last = now;
				});
			} else {
				var sx = 0, sy = 0, dx = 0, dy = 0, dragging = false;
				a.addEventListener('pointerdown', function (e) {
					dragging = true; sx = e.clientX; sy = e.clientY; dx = 0; dy = 0;
					a.setPointerCapture(e.pointerId);
					a.classList.add('is-dragging');
				});
				a.addEventListener('pointermove', function (e) {
					if (!dragging) { return; }
					dx = e.clientX - sx; dy = e.clientY - sy;
					a.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(1.1)';
				});
				var drop = function () {
					if (!dragging) { return; }
					dragging = false;
					a.classList.remove('is-dragging');
					var ar = a.getBoundingClientRect();
					var br = basket.getBoundingClientRect();
					var cx = ar.left + ar.width / 2;
					var cy = ar.top + ar.height / 2;
					if (cx > br.left - 10 && cx < br.right + 10 && cy > br.top - 20 && cy < br.bottom + 10) {
						a.style.transform = '';
						collect(a);
					} else {
						if (Math.abs(dx) + Math.abs(dy) > 8) { mistakes++; }
						a.style.transform = '';
					}
				};
				a.addEventListener('pointerup', drop);
				a.addEventListener('pointercancel', drop);
				// Bàn phím: Enter để "thả" vào giỏ (hỗ trợ trẻ không dùng chuột).
				a.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); collect(a); } });
			}
		};
		orchard.addEventListener('click', function (e) { if (e.target === orchard) { mistakes++; } });
		stage();
	};

	/* ---------- Tập gõ phím: chữ rơi trong bong bóng, gõ đúng phím để phá ---------- */
	var FINGERS = { Q: 'Ngón út trái', A: 'Ngón út trái', Z: 'Ngón út trái', 1: 'Ngón út trái', W: 'Ngón áp út trái', S: 'Ngón áp út trái', X: 'Ngón áp út trái', 2: 'Ngón áp út trái', E: 'Ngón giữa trái', D: 'Ngón giữa trái', C: 'Ngón giữa trái', 3: 'Ngón giữa trái', R: 'Ngón trỏ trái', F: 'Ngón trỏ trái', V: 'Ngón trỏ trái', T: 'Ngón trỏ trái', G: 'Ngón trỏ trái', B: 'Ngón trỏ trái', 4: 'Ngón trỏ trái', 5: 'Ngón trỏ trái', Y: 'Ngón trỏ phải', H: 'Ngón trỏ phải', N: 'Ngón trỏ phải', U: 'Ngón trỏ phải', J: 'Ngón trỏ phải', M: 'Ngón trỏ phải', 6: 'Ngón trỏ phải', 7: 'Ngón trỏ phải', I: 'Ngón giữa phải', K: 'Ngón giữa phải', 8: 'Ngón giữa phải', O: 'Ngón áp út phải', L: 'Ngón áp út phải', 9: 'Ngón áp út phải', P: 'Ngón út phải', 0: 'Ngón út phải' };
	Player.prototype.typing = function () {
		var self = this;
		var g = this.grade;
		var sets = { 1: 'FJDK', 2: 'ASDFJKL', 3: 'ASDFGHJKLQWERTYUIOP', 4: 'QWERTYUIOPASDFGHJKLZXCVBNM', 5: 'QWERTYUIOPASDFGHJKLZXCVBNM1234567890' };
		var letters = (sets[g] || sets[3]).split('');
		var total = 20;
		var lives = [0, 7, 5, 3][this.d] || 5;
		var maxLives = lives;
		var spawned = 0;
		var popped = 0;
		var bubbles = [];
		var running = true;
		var lastSpawn = 0;
		var lastT = performance.now();
		var speed = (26 + g * 7) * diffOf(this).speed;
		this.right = 0;
		this.marks = [];
		this.t0 = Date.now();
		var rows = ['1234567890', 'QWERTYUIOP', 'ASDFGHJKL', 'ZXCVBNM'];
		var kb = '<div class="hv-kb" aria-label="Bàn phím ảo">';
		rows.forEach(function (r, ri) {
			if (ri === 0 && g < 5) { return; }
			kb += '<div class="hv-kb-row">';
			r.split('').forEach(function (k) { kb += '<button type="button" class="hv-key' + (letters.indexOf(k) < 0 ? ' is-off' : '') + (k === 'F' || k === 'J' ? ' is-bump' : '') + '" data-k="' + k + '">' + k + '</button>'; });
			kb += '</div>';
		});
		kb += '</div>';
		this.body.innerHTML = '<div class="hv-type"><div class="hv-type-top"><span class="hv-lives"></span><span class="hv-finger" aria-live="polite"></span><span class="hv-tf-score">Gõ trúng <b>0</b>/' + total + '</span></div><div class="hv-type-sky"></div>' + kb + '<p class="hv-note">Đặt 2 ngón trỏ lên phím có gờ <b>F</b> và <b>J</b>. Gõ trên bàn phím thật hoặc chạm phím ảo.</p></div>';
		var sky = this.body.querySelector('.hv-type-sky');
		var livesEl = this.body.querySelector('.hv-lives');
		var finger = this.body.querySelector('.hv-finger');
		var score = this.body.querySelector('.hv-tf-score b');
		var drawLives = function () { livesEl.innerHTML = new Array(lives + 1).join('❤️') + new Array(maxLives - lives + 1).join('🤍'); };
		drawLives();
		var target = function () {
			var low = null;
			bubbles.forEach(function (b) { if (!b.dead && (!low || b.y > low.y)) { low = b; } });
			self.body.querySelectorAll('.hv-key.is-target').forEach(function (k) { k.classList.remove('is-target'); });
			if (low) {
				var key = self.body.querySelector('.hv-key[data-k="' + low.ch + '"]');
				if (key) { key.classList.add('is-target'); }
				finger.textContent = 'Gõ ' + low.ch + ' bằng ' + (FINGERS[low.ch] || 'ngón tay').toLowerCase();
			}
		};
		var stopAll = function () { running = false; document.removeEventListener('keydown', onKey); };
		var check = function () {
			if ((popped + (maxLives - lives) >= total && !bubbles.some(function (b) { return !b.dead; })) || lives <= 0 || (spawned >= total && !bubbles.some(function (b) { return !b.dead; }))) {
				stopAll();
				self.finish(popped, total, 'Em gõ trúng ' + popped + '/' + total + ' chữ' + (lives <= 0 ? ' – hết tim rồi, luyện thêm nhé!' : '!'));
				return true;
			}
			return false;
		};
		var press = function (ch, keyEl) {
			if (!running) { return; }
			var hitB = null;
			bubbles.forEach(function (b) { if (!b.dead && b.ch === ch && (!hitB || b.y > hitB.y)) { hitB = b; } });
			if (keyEl) { keyEl.classList.remove('is-press'); void keyEl.offsetWidth; keyEl.classList.add('is-press'); }
			if (hitB) {
				hitB.dead = true;
				hitB.el.classList.add('is-pop');
				popped++;
				self.right++;
				score.textContent = popped;
				self.hit(true, hitB.el);
				setTimeout(function () { hitB.el.remove(); }, 300);
				target();
				check();
			} else if (letters.indexOf(ch) >= 0) {
				self.hit(false, keyEl);
			}
		};
		var onKey = function (e) {
			if (e.ctrlKey || e.metaKey || e.altKey) { return; }
			var ch = (e.key || '').toUpperCase();
			if (ch.length !== 1 || !/[A-Z0-9]/.test(ch)) { return; }
			e.preventDefault();
			press(ch, self.body.querySelector('.hv-key[data-k="' + ch + '"]'));
		};
		document.addEventListener('keydown', onKey);
		this.body.querySelectorAll('.hv-key:not(.is-off)').forEach(function (k) {
			k.addEventListener('pointerdown', function (e) { e.preventDefault(); press(k.getAttribute('data-k'), k); });
		});
		var frame = function (now) {
			if (!running || !self.body.contains(sky)) { stopAll(); return; }
			var dt = Math.min(0.05, (now - lastT) / 1000);
			lastT = now;
			var alive = bubbles.filter(function (b) { return !b.dead; }).length;
			if (spawned < total && now - lastSpawn > Math.max(900, 2200 - g * 150 - popped * 25) && alive < 3) {
				lastSpawn = now;
				spawned++;
				var ch = pick(letters);
				var b = { ch: ch, y: -10, x: rnd(6, 84), el: el('span', 'hv-bubble-key', ch) };
				b.el.style.left = b.x + '%';
				sky.appendChild(b.el);
				bubbles.push(b);
				target();
			}
			var hgt = sky.clientHeight - 56;
			bubbles.forEach(function (b) {
				if (b.dead) { return; }
				b.y += (speed + popped * 1.5) * dt;
				b.el.style.transform = 'translateY(' + b.y + 'px)';
				if (b.y >= hgt) {
					b.dead = true;
					b.el.classList.add('is-miss');
					lives--;
					drawLives();
					self.hit(false, b.el);
					setTimeout(function () { b.el.remove(); }, 400);
					target();
				}
			});
			if (!check()) { requestAnimationFrame(frame); }
		};
		requestAnimationFrame(frame);
		this.stopTimer = stopAll;
	};

	/* ---------- Ghép bộ phận máy tính: kéo (hoặc chạm) tên thả vào đúng bộ phận ---------- */
	var PC_PARTS = [
		{ id: 'monitor', name: 'Màn hình', fn: 'Hiện chữ và hình ảnh', x: 48, y: 30 },
		{ id: 'keyboard', name: 'Bàn phím', fn: 'Gõ chữ, gõ số', x: 46, y: 80 },
		{ id: 'mouse', name: 'Chuột', fn: 'Di chuyển con trỏ, nháy chọn', x: 72, y: 82 },
		{ id: 'case', name: 'Thân máy', fn: 'Bộ não – xử lí và lưu thông tin', x: 87, y: 46 },
		{ id: 'speaker', name: 'Loa', fn: 'Phát ra âm thanh', x: 16, y: 44 }
	];
	function pcSvg() {
		return '<svg class="hv-pc" viewBox="0 0 600 340" aria-hidden="true">' +
			'<rect x="0" y="300" width="600" height="40" rx="8" fill="#e7d3b5"/>' +
			'<rect x="70" y="110" width="58" height="90" rx="12" fill="#44527c"/><circle cx="99" cy="140" r="14" fill="#2b3557"/><circle cx="99" cy="140" r="6" fill="#8fa0d0"/><circle cx="99" cy="178" r="9" fill="#2b3557"/>' +
			'<rect x="160" y="22" width="260" height="170" rx="14" fill="#2b3a67"/><rect x="174" y="36" width="232" height="142" rx="6" fill="#8fd6ff"/>' +
			'<path d="M196 160 L240 110 L270 140 L310 90 L384 160 Z" fill="#58b847" opacity=".85"/><circle cx="360" cy="70" r="16" fill="#ffd45e"/>' +
			'<rect x="270" y="192" width="40" height="40" fill="#2b3a67"/><rect x="225" y="230" width="130" height="12" rx="6" fill="#2b3a67"/>' +
			'<rect x="150" y="256" width="290" height="44" rx="9" fill="#dfe7f3" stroke="#aebbd3" stroke-width="2"/>' +
			'<g fill="#fff" stroke="#c3cee0">' + (function () { var k = ''; for (var r = 0; r < 3; r++) { for (var c = 0; c < 12; c++) { k += '<rect x="' + (160 + c * 23) + '" y="' + (261 + r * 12) + '" width="19" height="9" rx="2"/>'; } } return k; })() + '</g>' +
			'<ellipse cx="460" cy="282" rx="17" ry="24" fill="#dfe7f3" stroke="#aebbd3" stroke-width="2"/><path d="M460 258 V276" stroke="#aebbd3" stroke-width="2"/><path d="M460 258 C 470 230, 430 230, 420 252" stroke="#aebbd3" stroke-width="3" fill="none"/>' +
			'<rect x="490" y="74" width="84" height="226" rx="12" fill="#4a5a8a"/><rect x="504" y="94" width="56" height="10" rx="4" fill="#2b3557"/><rect x="504" y="112" width="56" height="10" rx="4" fill="#2b3557"/><circle cx="532" cy="160" r="9" fill="#58b847"/>' +
			'</svg>';
	}
	Player.prototype.parts = function () {
		var self = this;
		var rounds = [{ key: 'name', title: 'Kéo tên bộ phận vào đúng chỗ' }];
		if (this.grade >= 2) { rounds.push({ key: 'fn', title: 'Bộ phận nào làm việc này? Kéo vào đúng chỗ' }); }
		var parts = this.grade <= 1 ? PC_PARTS.slice(0, 4) : PC_PARTS;
		var ri = 0;
		var placed = 0;
		var tries = 0;
		var firstTry = 0;
		this.right = 0;
		this.marks = [];
		this.t0 = Date.now();
		var round = function () {
			if (ri >= rounds.length) { return self.finish(firstTry, parts.length * rounds.length, 'Em ghép đúng ngay lần đầu ' + firstTry + '/' + (parts.length * rounds.length) + ' lần'); }
			var rd = rounds[ri++];
			placed = 0;
			var h = '<div class="hv-parts"><div class="hv-stage-title"><b>Vòng ' + ri + '/' + rounds.length + ':</b> ' + esc(rd.title) + '</div><div class="hv-pc-wrap">' + pcSvg();
			parts.forEach(function (p) { h += '<button type="button" class="hv-zone-drop" data-id="' + p.id + '" style="left:' + p.x + '%;top:' + p.y + '%" aria-label="Chỗ trống">?</button>'; });
			h += '</div><div class="hv-labels">';
			shuffle(parts).forEach(function (p) { h += '<button type="button" class="hv-label" data-id="' + p.id + '">' + esc(rd.key === 'name' ? p.name : p.fn) + '</button>'; });
			self.body.innerHTML = h + '</div><div class="hv-feedback" aria-live="polite"></div></div>';
			speak(rd.title);
			var fb = self.body.querySelector('.hv-feedback');
			var selected = null;
			var attempts = {};
			var tryDrop = function (label, zone) {
				if (!label || !zone || zone.classList.contains('is-ok')) { return; }
				var id = label.getAttribute('data-id');
				attempts[id] = (attempts[id] || 0) + 1;
				tries++;
				if (zone.getAttribute('data-id') === id) {
					zone.classList.add('is-ok');
					zone.textContent = label.textContent;
					label.remove();
					placed++;
					self.right++;
					if (attempts[id] === 1) { firstTry++; }
					self.hit(true, zone);
					var part = PC_PARTS.filter(function (p) { return p.id === id; })[0];
					fb.className = 'hv-feedback ok';
					fb.textContent = part.name + ': ' + part.fn;
					speak(part.name + '. ' + part.fn);
					if (placed >= parts.length) { setTimeout(round, 1300); }
				} else {
					zone.classList.remove('is-no');
					void zone.offsetWidth;
					zone.classList.add('is-no');
					self.hit(false, zone);
					fb.className = 'hv-feedback no';
					fb.textContent = 'Chưa đúng chỗ, em thử lại nhé!';
				}
				if (selected) { selected.classList.remove('is-sel'); selected = null; }
			};
			self.body.querySelectorAll('.hv-label').forEach(function (lb) {
				lb.addEventListener('click', function () {
					if (selected) { selected.classList.remove('is-sel'); }
					selected = lb === selected ? null : lb;
					if (selected) { selected.classList.add('is-sel'); fb.className = 'hv-feedback'; fb.textContent = 'Giờ chạm vào dấu ? đúng chỗ'; }
				});
				var sx, sy, moved = false, dragging = false;
				lb.addEventListener('pointerdown', function (e) { dragging = true; moved = false; sx = e.clientX; sy = e.clientY; lb.setPointerCapture(e.pointerId); });
				lb.addEventListener('pointermove', function (e) {
					if (!dragging) { return; }
					var dx = e.clientX - sx;
					var dy = e.clientY - sy;
					if (Math.abs(dx) + Math.abs(dy) > 6) { moved = true; lb.classList.add('is-dragging'); }
					lb.style.transform = 'translate(' + dx + 'px,' + dy + 'px)';
				});
				var up = function (e) {
					if (!dragging) { return; }
					dragging = false;
					lb.classList.remove('is-dragging');
					lb.style.transform = '';
					if (!moved) { return; }
					lb.style.pointerEvents = 'none';
					var under = document.elementFromPoint(e.clientX, e.clientY);
					lb.style.pointerEvents = '';
					var zone = under && under.closest ? under.closest('.hv-zone-drop') : null;
					if (zone) { tryDrop(lb, zone); }
					setTimeout(function () { if (selected === lb) { lb.classList.remove('is-sel'); selected = null; } }, 0);
				};
				lb.addEventListener('pointerup', up);
				lb.addEventListener('pointercancel', up);
			});
			self.body.querySelectorAll('.hv-zone-drop').forEach(function (z) {
				z.addEventListener('click', function () { if (selected) { tryDrop(selected, z); } });
			});
		};
		round();
	};

	/* Sắp xếp câu */
	Player.prototype.order = function () {
		var self = this;
		var list = shuffle(SENTENCES[this.grade] || SENTENCES[1]).slice(0, this.def.total);
		this.deck = list;
		this.i = 0;
		this.right = 0;
		this.marks = [];
		this.t0 = Date.now();
		var next = function () {
			if (self.i >= list.length) { return self.finish(self.right, list.length); }
			var sentence = list[self.i];
			var words = sentence.split(' ');
			var tiles = shuffle(words.map(function (w, k) { return { w: w, k: k }; }));
			// Không để thứ tự xáo trùng câu gốc.
			if (tiles.map(function (t) { return t.w; }).join(' ') === sentence && tiles.length > 1) { tiles.push(tiles.shift()); }
			var built = [];
			var tries = 0;
			var draw = function () {
				var h = self.progress() + '<div class="hv-q"><div class="hv-q-text">Sắp xếp các từ thành câu đúng <button type="button" class="hv-icon-btn hv-read" aria-label="Đọc câu mẫu" title="Nghe câu">🔈</button></div>';
				h += '<div class="hv-line" aria-label="Câu em ghép">';
				built.forEach(function (t, idx) { h += '<button type="button" class="hv-tile is-used" data-b="' + idx + '">' + esc(t.w) + '</button>'; });
				if (!built.length) { h += '<span class="hv-line-hint">Chạm vào từ bên dưới…</span>'; } else { h += '<span class="hv-dot">.</span>'; }
				h += '</div><div class="hv-bank">';
				tiles.forEach(function (t, idx) { if (!t.used) { h += '<button type="button" class="hv-tile" data-t="' + idx + '">' + esc(t.w) + '</button>'; } });
				h += '</div><div class="hv-actions"><button type="button" class="hv-btn hv-check"' + (built.length === words.length ? '' : ' disabled') + '>Kiểm tra ✓</button></div><div class="hv-feedback"></div></div>';
				self.body.innerHTML = h;
				self.body.querySelector('.hv-read').addEventListener('click', function () { speak(sentence); });
				self.body.querySelectorAll('[data-t]').forEach(function (b) {
					b.addEventListener('click', function () { var t = tiles[+b.getAttribute('data-t')]; t.used = true; built.push(t); sfx.flip(); draw(); });
				});
				self.body.querySelectorAll('[data-b]').forEach(function (b) {
					b.addEventListener('click', function () { var t = built.splice(+b.getAttribute('data-b'), 1)[0]; t.used = false; draw(); });
				});
				self.body.querySelector('.hv-check').addEventListener('click', function () {
					var ok = built.map(function (t) { return t.w; }).join(' ') === sentence;
					var fb = self.body.querySelector('.hv-feedback');
					tries++;
					if (ok || tries >= 2) {
						self.hit(ok, fb);
						if (ok) { self.right++; fb.innerHTML = 'Đúng rồi! 🎉'; } else { fb.innerHTML = 'Câu đúng là: <b>' + esc(sentence) + '.</b>'; }
						fb.className = 'hv-feedback ' + (ok ? 'ok' : 'no');
						self.marks[self.i] = ok;
						self.say(ok ? 'Đúng rồi' : 'Câu đúng là ' + sentence);
						speak(sentence);
						self.body.querySelectorAll('.hv-tile, .hv-check').forEach(function (x) { x.disabled = true; });
						setTimeout(function () { self.i++; next(); }, ok ? 1400 : 2800);
					} else {
						sfx.bad();
						fb.className = 'hv-feedback no';
						fb.innerHTML = 'Chưa đúng, em thử lại nhé! (Gợi ý: chữ viết hoa đứng đầu câu)';
						self.say('Chưa đúng, em thử lại nhé');
					}
				});
			};
			draw();
		};
		next();
	};

	/* ---------- Album nhãn dán, tên bé ---------- */
	function renderAlbums() {
		var s = stars();
		var unlocked = Math.min(STICKERS.length, Math.floor(s / STAR_PER_STICKER));
		document.querySelectorAll('[data-hv-album]').forEach(function (box) {
			var h = '<div class="hv-album-grid">';
			STICKERS.forEach(function (st, i) {
				h += i < unlocked ? '<span class="hv-sticker on" title="Đã mở">' + st + '</span>' : '<span class="hv-sticker" title="Cần ' + ((i + 1) * STAR_PER_STICKER) + ' sao">🔒<small>' + ((i + 1) * STAR_PER_STICKER) + '⭐</small></span>';
			});
			h += '</div><p class="hv-album-note">Em có <b>' + s + ' ⭐</b> · đã mở <b>' + unlocked + '/' + STICKERS.length + '</b> nhãn dán · đã chơi <b>' + store.get('played', 0) + '</b> lượt.' +
				(unlocked < STICKERS.length ? ' Còn <b>' + ((unlocked + 1) * STAR_PER_STICKER - s) + ' ⭐</b> nữa để mở nhãn dán tiếp theo!' : ' Em đã sưu tập đủ bộ! 🏆') + '</p>';
			box.innerHTML = h;
		});
	}
	function bindName() {
		document.querySelectorAll('[data-hv-name-form]').forEach(function (f) {
			var input = f.querySelector('input');
			input.value = store.get('name', '');
			f.addEventListener('submit', function (e) {
				e.preventDefault();
				store.set('name', input.value.trim().slice(0, 24));
				renderProgress();
				var ok = f.querySelector('.hv-saved');
				if (ok) { ok.hidden = false; setTimeout(function () { ok.hidden = true; }, 1800); }
			});
		});
		document.querySelectorAll('[data-hv-reset]').forEach(function (b) {
			b.addEventListener('click', function () {
				if (curKid()) {
					if (window.confirm('Tiến độ của bé đang lưu trong tài khoản phụ huynh. Mở trang Tài khoản phụ huynh để chọn "Học lại từ đầu"?')) { window.location.href = USER.account; }
					return;
				}
				if (window.confirm('Xoá toàn bộ sao, XP, màn đã phá và nhãn dán trên máy này?')) {
					['stars', 'played', 'xp', 'done', 'streak', 'lastDay', 'today'].forEach(function (k) { try { window.localStorage.removeItem('hv_' + k); } catch (e) { /* bỏ qua */ } });
					renderProgress(); renderAlbums();
				}
			});
		});
	}

	/* ---------- Thanh nhân vật, bản đồ đảo, nhiệm vụ, huy hiệu ---------- */
	var AVATARS = ['🦉', '🐯', '🐼', '🦊', '🐸', '🐵', '🦄', '🐙', '🐶', '🐱', '🐰', '🐲'];
	var BADGES = [
		['medal', 'Màn đầu tiên', 'Phá đảo 1 màn bài học', function (p) { return p.cleared >= 1; }],
		['map', 'Nhà thám hiểm', 'Phá đảo 5 màn', function (p) { return p.cleared >= 5; }],
		['island', 'Chúa đảo', 'Phá đảo 20 màn', function (p) { return p.cleared >= 20; }],
		['swords', 'Diệt trùm', 'Hạ gục 1 trùm cuối', function (p) { return p.bosses >= 1; }],
		['fire', 'Lửa nhỏ', 'Học 3 ngày liên tiếp', function (p) { return p.streak >= 3; }],
		['sun', 'Mặt trời nhỏ', 'Học 7 ngày liên tiếp', function (p) { return p.streak >= 7; }],
		['hundred', 'Tia chớp', 'Đạt 200 XP', function (p) { return p.xp >= 200; }],
		['crown', 'Huyền thoại', 'Đạt 1000 XP', function (p) { return p.xp >= 1000; }]
	];
	// Ảnh biểu tượng 3D trong theme (Fluent Emoji, MIT).
	function ico(key, size, cls) {
		return CFG.img ? '<img class="' + (cls || 'hv-3d') + '" src="' + esc(CFG.img + key + '.webp') + '" width="' + size + '" height="' + size + '" alt="" aria-hidden="true">' : '';
	}
	function profile() {
		var done = store.get('done', {});
		var cleared = 0;
		var bosses = 0;
		Object.keys(done).forEach(function (k) { if (done[k] > 0) { if (k.indexOf('boss-') === 0) { bosses++; } else { cleared++; } } });
		var last = store.get('lastDay', '');
		var y = new Date(); y.setDate(y.getDate() - 1);
		var streak = last === todayKey() || last === todayKey(y) ? store.get('streak', 0) : 0;
		return { xp: store.get('xp', 0), done: done, cleared: cleared, bosses: bosses, streak: streak, name: store.get('name', ''), avatar: store.get('avatar', '🦉') };
	}
	function hudHtml(p) {
		var lv = lvOf(p.xp);
		var pct = Math.round((p.xp - lvXp(lv)) / (lvXp(lv + 1) - lvXp(lv)) * 100);
		return '<a class="hv-hud-ava" href="' + esc(CFG.profileUrl || '#') + '" aria-label="Hồ sơ của em">' + esc(p.avatar) + '<b>' + lv + '</b></a>' +
			'<div class="hv-hud-who"><strong>' + esc(p.name || 'Bạn nhỏ') + '</strong><span>Cấp ' + lv + ' · ' + esc(titleOf(lv)) + '</span>' +
			'<div class="hv-xpbar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' + pct + '" aria-label="Kinh nghiệm"><i style="width:' + pct + '%"></i><em>' + p.xp + ' XP</em></div></div>' +
			'<div class="hv-hud-stats"><span title="Sao">' + (ico('star', 22) || '⭐') + ' <b>' + stars() + '</b></span><span title="Chuỗi ngày học" class="' + (p.streak ? 'is-hot' : '') + '">' + (ico('fire', 22) || '🔥') + ' <b>' + p.streak + '</b></span><span title="Màn đã phá">' + (ico('trophy', 22) || '🏁') + ' <b>' + p.cleared + '</b></span></div>' +
			(USER.in ? '<button type="button" class="hv-hud-set hv-hud-kids" data-hv-kids aria-label="Đổi bé đang học" title="Đổi bé đang học">👪</button>' : '<a class="hv-hud-set hv-hud-kids" href="' + esc(USER.account || '#') + '" aria-label="Phụ huynh đăng nhập" title="Phụ huynh đăng nhập">👪</a>') +
			'<button type="button" class="hv-hud-set" data-hv-settings aria-label="Cài đặt âm thanh và giọng đọc">⚙️</button>';
	}
	function renderProgress() {
		var p = profile();
		document.querySelectorAll('[data-hv-hud]').forEach(function (n) { n.innerHTML = hudHtml(p); });
		renderStarBadges();
		// Màn chơi trên đường đi của đảo.
		document.querySelectorAll('[data-hv-path]').forEach(function (path) {
			var cur = false;
			path.querySelectorAll('[data-hv-node]').forEach(function (n) {
				var st = p.done[n.getAttribute('data-hv-node')] || 0;
				n.classList.toggle('is-done', st > 0);
				n.classList.remove('is-current');
				var s = n.querySelector('.hv-node-stars');
				if (s) { s.textContent = st ? new Array(st + 1).join('★') + new Array(4 - st).join('☆') : ''; }
				if (!st && !cur) { cur = true; n.classList.add('is-current'); }
			});
		});
		// Tiến độ từng đảo.
		document.querySelectorAll('[data-hv-island]').forEach(function (n) {
			var list = (n.getAttribute('data-hv-island') || '').split(',').filter(Boolean);
			var c = list.filter(function (k) { return p.done[k] > 0; }).length;
			var bar = n.querySelector('.hv-isl-bar i');
			var txt = n.querySelector('.hv-isl-count');
			if (bar) { bar.style.width = (list.length ? Math.round(c / list.length * 100) : 0) + '%'; }
			if (txt) { txt.textContent = c + '/' + list.length + ' màn'; }
			n.classList.toggle('is-cleared', list.length > 0 && c === list.length);
		});
		// Nhiệm vụ hôm nay.
		var t = today();
		var quests = [['book', 'Phá đảo 1 màn bài học', t.lessons, 1], ['game', 'Chơi 3 lượt trò chơi', t.plays, 3], ['star', 'Đạt 3 sao trong 1 lượt', t.perfect, 1]];
		document.querySelectorAll('[data-hv-quests]').forEach(function (box) {
			var h = '';
			quests.forEach(function (q) {
				var ok = q[2] >= q[3];
				h += '<li class="' + (ok ? 'is-ok' : '') + '">' + ico(q[0], 36) + '<span>' + esc(q[1]) + '<small><b style="width:' + Math.min(100, Math.round(q[2] / q[3] * 100)) + '%"></b></small></span><em>' + (ok ? '✔' : Math.min(q[2], q[3]) + '/' + q[3]) + '</em></li>';
			});
			box.innerHTML = h;
		});
		// Huy hiệu.
		document.querySelectorAll('[data-hv-badges]').forEach(function (box) {
			var h = '';
			BADGES.forEach(function (b) {
				var ok = b[3](p);
				h += '<div class="hv-badge' + (ok ? ' on' : '') + '" title="' + esc(b[2]) + '">' + ico(b[0], 56) + '<strong>' + esc(b[1]) + '</strong><small>' + esc(b[2]) + '</small></div>';
			});
			box.innerHTML = h;
		});
		document.querySelectorAll('[data-hv-avatars]').forEach(function (box) {
			var h = '';
			AVATARS.forEach(function (a) { h += '<button type="button" class="hv-ava' + (a === p.avatar ? ' is-on' : '') + '" data-ava="' + a + '" aria-pressed="' + (a === p.avatar) + '">' + a + '</button>'; });
			box.innerHTML = h;
		});
	}
	document.addEventListener('click', function (e) {
		var a = e.target.closest && e.target.closest('[data-ava]');
		if (a) { store.set('avatar', a.getAttribute('data-ava')); sfx.flip(); renderProgress(); }
		var s = e.target.closest && e.target.closest('[data-hv-settings]');
		if (s) { openSettings(); }
		var b = e.target.closest && e.target.closest('[data-hv-boss-link]');
		if (b) { openArena(b.getAttribute('href')); }
		// Tab "Vì sao chọn Học Vui".
		var tab = e.target.closest && e.target.closest('[data-hv-tabs] [role="tab"]');
		if (tab) {
			var box = tab.closest('[data-hv-tabs]');
			box.querySelectorAll('[role="tab"]').forEach(function (x) { var on = x === tab; x.setAttribute('aria-selected', on); x.tabIndex = on ? 0 : -1; });
			box.querySelectorAll('[role="tabpanel"]').forEach(function (pn) { pn.hidden = pn.id !== tab.getAttribute('aria-controls'); });
		}
		// Cú Bông trợ giúp.
		var hb = e.target.closest && e.target.closest('[data-hv-helper-toggle]');
		if (hb) {
			var pop = document.querySelector('[data-hv-helper] .hv-helper-pop');
			pop.hidden = !pop.hidden;
			if (!pop.hidden) {
				var pr = profile();
				var tips = [
					'Chào ' + (pr.name || 'em') + '! Hôm nay mình phá đảo thêm một màn nhé?',
					'Em đang có ' + stars() + ' sao. Đủ 10 sao là mở được nhãn dán mới đó!',
					pr.streak ? 'Ngọn lửa của em đã cháy ' + pr.streak + ' ngày rồi. Giỏi lắm, giữ lửa nhé!' : 'Học mỗi ngày một chút để thắp ngọn lửa chăm chỉ nha!',
					'Làm sai cũng không sao đâu. Mỗi lần sai là một lần mình học thêm được điều mới!'
				];
				var msg = pick(tips);
				pop.querySelector('[data-hv-helper-text]').textContent = msg;
				speak(msg);
			}
		}
		if (e.target.closest && e.target.closest('[data-hv-print]')) { window.print(); }
		if (e.target.closest && e.target.closest('[data-hv-helper-say]')) { speak(document.querySelector('[data-hv-helper-text]').textContent); }
	});

	function openArena(hash) {
		var d = hash && hash.indexOf('#trum-') === 0 ? document.getElementById(hash.slice(1)) : null;
		if (d && d.tagName === 'DETAILS') { d.open = true; }
	}
	window.addEventListener('hashchange', function () { openArena(window.location.hash); });

	/* ---------- Cài đặt âm thanh, giọng đọc ---------- */
	function fillVoiceSelect(sel) {
		var list = langVoices('vi');
		var chosen = store.get('voice', '');
		sel.innerHTML = list.length ? '<option value="">Tự động theo Cô giáo / Thầy giáo</option>' + list.map(function (v) {
			return '<option value="' + esc(v.name) + '"' + (chosen === v.name ? ' selected' : '') + '>' + esc(v.name.replace(/Microsoft |Online |\(Natural\)| - Vietnamese \(Vietnam\)/g, '').trim()) + (isMale(v) ? ' · nam' : ' · nữ') + '</option>';
		}).join('') : '<option value="">(Máy chưa có giọng tiếng Việt)</option>';
		var note = document.querySelector('.hv-voice-note');
		if (note) { note.hidden = !!TTS || !!list.length; }
		var mnote = document.querySelector('.hv-male-note');
		if (mnote) { mnote.hidden = !!TTS || !list.length || gender() !== 'male' || hasRealMale(); }
	}
	var settingsBox = null;
	function openSettings() {
		if (!settingsBox) {
			settingsBox = el('div', 'hv-settings');
			settingsBox.setAttribute('role', 'dialog');
			settingsBox.setAttribute('aria-modal', 'true');
			settingsBox.setAttribute('aria-label', 'Cài đặt âm thanh và giọng đọc');
			settingsBox.innerHTML = '<div class="hv-settings-card"><div class="hv-owl" aria-hidden="true"></div><h3>Cài đặt giọng đọc</h3>' +
				'<label class="hv-switch"><input type="checkbox" data-set="sound"><span></span>Âm thanh và giọng đọc</label>' +
				'<label class="hv-switch"><input type="checkbox" data-set="autoread"><span></span>Tự đọc to bài học và câu hỏi</label>' +
				'<div class="hv-field">Người đọc<div class="hv-seg hv-seg-voice"><button type="button" data-gender="female"><span aria-hidden="true">👩‍🏫</span> Cô giáo<small>giọng nữ</small></button><button type="button" data-gender="male"><span aria-hidden="true">👨‍🏫</span> Thầy giáo<small>giọng nam</small></button><button type="button" data-gender="owl"><span aria-hidden="true">🦉</span> Cú Bông<small>kể chuyện</small></button></div></div>' +
				'<div class="hv-field">Tốc độ đọc<div class="hv-seg hv-seg-3"><button type="button" data-rate="slow">🐢 Chậm</button><button type="button" data-rate="normal">🙂 Vừa phải</button><button type="button" data-rate="fast">🐇 Nhanh</button></div></div>' +
				'<p class="hv-ai-note" hidden>✨ Đang dùng <b>giọng đọc AI</b> của Học Vui – tự nhiên, rõ ràng và nghe giống nhau trên mọi máy.</p>' +
				'<p class="hv-male-note" hidden>Máy này chưa có giọng nam tiếng Việt nên Thầy giáo đọc bằng giọng trầm. Mở Học Vui bằng trình duyệt Microsoft Edge để nghe giọng thầy giáo tự nhiên (NamMinh).</p>' +
				'<details class="hv-voice-adv"><summary>Chọn giọng cụ thể</summary><label class="hv-field"><select data-set="voice" aria-label="Giọng tiếng Việt"></select></label></details>' +
				'<p class="hv-voice-note" hidden>Máy này chưa có giọng tiếng Việt. Gợi ý: dùng trình duyệt Microsoft Edge (giọng HoaiMy rất ấm), Google Chrome, hoặc vào Cài đặt điện thoại → Trợ năng → Nội dung được đọc → Giọng nói → thêm Tiếng Việt.</p>' +
				'<div class="hv-actions"><button type="button" class="hv-btn hv-btn-accent" data-test>🔈 Nghe thử</button><button type="button" class="hv-btn" data-close>Xong</button></div></div>';
			document.body.appendChild(settingsBox);
			var sync = function () {
				settingsBox.querySelector('[data-set="sound"]').checked = soundOn();
				settingsBox.querySelector('[data-set="autoread"]').checked = autoRead(store.get('grade', 1));
				settingsBox.querySelectorAll('[data-rate]').forEach(function (b) { b.classList.toggle('is-on', rateKey() === b.getAttribute('data-rate')); });
				settingsBox.querySelectorAll('[data-gender]').forEach(function (b) { b.classList.toggle('is-on', persona() === b.getAttribute('data-gender')); });
				settingsBox.querySelector('.hv-ai-note').hidden = !TTS;
				settingsBox.querySelector('.hv-voice-adv').hidden = !!TTS;
				fillVoiceSelect(settingsBox.querySelector('[data-set="voice"]'));
			};
			settingsBox._sync = sync;
			settingsBox.addEventListener('change', function (e) {
				var k = e.target.getAttribute('data-set');
				if (k === 'sound') { store.set('sound', e.target.checked); document.querySelectorAll('.hv-sound').forEach(function (b) { b.textContent = soundOn() ? '🔊' : '🔇'; }); }
				if (k === 'autoread') { store.set('autoread', e.target.checked); }
				if (k === 'voice') { store.set('voice', e.target.value); speak('Chào em! Đây là giọng đọc mới.'); }
			});
			settingsBox.addEventListener('click', function (e) {
				if (e.target === settingsBox || e.target.hasAttribute('data-close')) { settingsBox.hidden = true; }
				var gb = e.target.closest && e.target.closest('[data-gender]');
				if (gb) { store.set('vgender', gb.getAttribute('data-gender')); store.set('voice', ''); sync(); speak(persona() === 'male' ? 'Chào em! Thầy sẽ đọc bài cho em nghe nhé.' : (persona() === 'owl' ? 'Chào bạn nhỏ! Mình là Cú Bông. Mình sẽ kể bài cho bạn nghe nhé!' : 'Chào em! Cô sẽ đọc bài cho em nghe nhé.')); }
				if (e.target.hasAttribute('data-rate')) { store.set('rate', e.target.getAttribute('data-rate')); sync(); speak('Mình sẽ đọc với tốc độ này. Em nghe có rõ không?'); }
				if (e.target.hasAttribute('data-test')) {
					var nm = store.get('name', '');
					speak('Chào ' + (nm || 'em') + '! Hôm nay chúng mình học bài: Phép cộng trong phạm vi 10. 5 + 3 = 8. Em hãy đọc to cùng ' + (persona() === 'male' ? 'thầy' : (persona() === 'owl' ? 'Cú Bông' : 'cô')) + ' nhé!');
				}
			});
			document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && settingsBox) { settingsBox.hidden = true; } });
		}
		settingsBox._sync();
		settingsBox.hidden = false;
		var first = settingsBox.querySelector('input');
		if (first) { first.focus(); }
	}

	/* ---------- Bài học dạng màn chơi: từng bước một ---------- */
	function Stepper(root) {
		var self = this;
		this.root = root;
		this.grade = parseInt(root.getAttribute('data-grade'), 10) || 1;
		// Tách phần nội dung dài thành nhiều thẻ "Khám phá": mỗi tiêu đề H2, mỗi ví dụ/bảng/mẹo là một thẻ.
		root.querySelectorAll('.hv-step[data-split]').forEach(function (step) {
			var body = step.querySelector('.hv-content');
			var kids = Array.prototype.slice.call(body.children);
			var groups = [];
			var cur = [];
			kids.forEach(function (k) {
				var breaker = k.tagName === 'H2' || ((k.classList.contains('hv-example') || k.classList.contains('hv-tip') || k.tagName === 'TABLE') && cur.length >= 2);
				if (breaker && cur.length) { groups.push(cur); cur = []; }
				cur.push(k);
			});
			if (cur.length) { groups.push(cur); }
			groups.forEach(function (g, i) {
				var s = el('section', 'hv-step hv-step-explore');
				s.setAttribute('data-title', 'Khám phá ' + (i + 1) + '/' + groups.length);
				var card = el('div', 'hv-card-xl hv-content');
				g.forEach(function (k) { card.appendChild(k); });
				s.appendChild(card);
				step.parentNode.insertBefore(s, step);
			});
			step.remove();
		});
		this.steps = Array.prototype.slice.call(root.querySelectorAll('.hv-step'));
		this.i = 0;
		root.classList.add('is-stepped');
		var bar = el('div', 'hv-stepbar');
		var segs = '';
		this.steps.forEach(function () { segs += '<i></i>'; });
		bar.innerHTML = '<div class="hv-stepsegs">' + segs + '</div><span class="hv-steptitle"></span><button type="button" class="hv-icon-btn hv-read-step" aria-label="Nghe Cú Bông đọc" title="Nghe Cú Bông đọc">🔈</button>';
		root.insertBefore(bar, root.firstChild);
		var nav = el('div', 'hv-stepnav');
		nav.innerHTML = '<button type="button" class="hv-btn hv-btn-ghost hv-prev">← Quay lại</button><button type="button" class="hv-btn hv-btn-accent hv-btn-big hv-next">Tiếp tục →</button>';
		root.appendChild(nav);
		this.bar = bar;
		this.nav = nav;
		nav.querySelector('.hv-prev').addEventListener('click', function () { self.go(self.i - 1, true); });
		nav.querySelector('.hv-next').addEventListener('click', function () { sfx.flip(); self.go(self.i + 1, true); });
		bar.querySelector('.hv-read-step').addEventListener('click', function () { self.read(); });
		root.querySelectorAll('[data-hv-start]').forEach(function (b) { b.addEventListener('click', function () { sfx.good(); self.go(1, true); }); });
		root.querySelectorAll('[data-hv-hear]').forEach(function (b) { b.addEventListener('click', function () { self.read(); }); });
		this.go(0, false);
	}
	Stepper.prototype.read = function () {
		var s = this.steps[this.i];
		var t = s.querySelector('[data-read]') || s.querySelector('.hv-card-xl') || s;
		speak(t.innerText || t.textContent);
	};
	Stepper.prototype.go = function (i, user) {
		if (i < 0 || i >= this.steps.length) { return; }
		this.i = i;
		this.steps.forEach(function (s, k) { s.classList.toggle('is-on', k === i); });
		this.bar.querySelectorAll('.hv-stepsegs i').forEach(function (seg, k) { seg.className = k < i ? 'ok' : k === i ? 'cur' : ''; });
		this.bar.querySelector('.hv-steptitle').textContent = this.steps[i].getAttribute('data-title') || '';
		this.nav.querySelector('.hv-prev').style.visibility = i > 0 ? 'visible' : 'hidden';
		var last = i === this.steps.length - 1;
		this.nav.querySelector('.hv-next').hidden = last || i === 0;
		if (user) {
			var top = this.root.getBoundingClientRect().top + window.pageYOffset - 90;
			if (window.pageYOffset > top) { window.scrollTo({ top: top, behavior: 'smooth' }); }
			if (autoRead(this.grade) && !last) { this.read(); } else { stopSpeech(); }
		}
	};

	/* ---------- Tài khoản phụ huynh: một phụ huynh nhiều bé, tiến độ của bé lưu trên máy chủ ---------- */
	var USER = CFG.user || {};
	var PKEYS = ['stars', 'played', 'xp', 'done', 'streak', 'lastDay', 'today'];
	var rawSet = store.set;
	var pushT = null;
	// Mọi thay đổi tiến độ, tên, nhân vật của bé đang học sẽ được gửi lên tài khoản (gom lại 1,5 giây một lần).
	store.set = function (k, v) {
		rawSet(k, v);
		if (PKEYS.indexOf(k) > -1 || k === 'name' || k === 'avatar') { schedulePush(); }
	};
	function kidList() { return USER.in && USER.kids ? USER.kids : []; }
	function kidById(id) { return kidList().filter(function (k) { return k.id === id; })[0] || null; }
	function curKid() { return kidById(store.get('kid', '')); }
	function localProgress() {
		var done = store.get('done', {});
		return {
			stars: store.get('stars', 0), played: store.get('played', 0), xp: store.get('xp', 0), streak: store.get('streak', 0),
			lastDay: store.get('lastDay', ''), today: store.get('today', {}), done: done && !Array.isArray(done) ? done : {}
		};
	}
	function clearLocal() {
		PKEYS.concat(['name', 'avatar', 'kidR']).forEach(function (k) { try { window.localStorage.removeItem('hv_' + k); } catch (e) { /* bỏ qua */ } });
	}
	function dayNum(d) { var m = String(d || '').split('-'); return m.length === 3 ? new Date(+m[0], m[1] - 1, +m[2]).getTime() : 0; }
	// Gộp như máy chủ: không bao giờ mất sao, XP, bài đã học.
	function mergeP(a, b) {
		var o = { stars: Math.max(a.stars || 0, b.stars || 0), played: Math.max(a.played || 0, b.played || 0), xp: Math.max(a.xp || 0, b.xp || 0), done: {} };
		[a.done, b.done].forEach(function (d) { Object.keys(d && !Array.isArray(d) ? d : {}).forEach(function (k) { o.done[k] = Math.max(o.done[k] || 0, d[k] || 0); }); });
		var newer = dayNum(a.lastDay) >= dayNum(b.lastDay) ? a : b;
		o.lastDay = newer.lastDay || ''; o.streak = newer.streak || 0;
		var ta = a.today || {}, tb = b.today || {};
		o.today = ta.date === tb.date ? { date: ta.date, plays: Math.max(ta.plays || 0, tb.plays || 0), lessons: Math.max(ta.lessons || 0, tb.lessons || 0), perfect: Math.max(ta.perfect || 0, tb.perfect || 0) } : (dayNum(ta.date) > dayNum(tb.date) ? ta : tb);
		return o;
	}
	function applyKid(k, merge) {
		var p = merge ? mergeP(localProgress(), k.p || {}) : (k.p || {});
		PKEYS.forEach(function (key) {
			var v = p[key];
			if (key === 'done' && (!v || Array.isArray(v))) { v = {}; }
			if (v !== undefined && v !== null) { rawSet(key, v); }
		});
		rawSet('name', k.name); rawSet('avatar', k.avatar); rawSet('kid', k.id); rawSet('kidR', k.r || 0);
	}
	function switchKid(id) {
		var k = kidById(id);
		if (!k) { return false; }
		var cur = curKid();
		if (cur && cur.id === id) { applyKid(k, true); return true; }
		if (cur) { pushNow(true); }
		clearLocal();
		applyKid(k, false);
		rawSet('grade', k.grade);
		return true;
	}
	function schedulePush() {
		if (!curKid()) { return; }
		clearTimeout(pushT);
		pushT = setTimeout(function () { pushNow(false); }, 1500);
	}
	function pushNow(keep) {
		clearTimeout(pushT);
		pushT = null;
		var k = curKid();
		if (!k || !window.fetch) { return; }
		var body = { progress: localProgress(), avatar: store.get('avatar', k.avatar) };
		if (store.get('name', '')) { body.name = store.get('name', ''); }
		window.fetch(USER.rest + '/' + k.id, {
			method: 'POST', credentials: 'same-origin', keepalive: !!keep,
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': USER.nonce },
			body: JSON.stringify(body)
		}).then(function (r) { return r.ok ? r.json() : null; }).then(function (d) {
			if (d && d.id) { USER.kids = kidList().map(function (x) { return x.id === d.id ? d : x; }); }
		}).catch(function () { /* mất mạng: lần sau gửi lại */ });
	}
	function flushPush() { if (pushT) { pushNow(true); } }
	window.addEventListener('pagehide', flushPush);
	document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') { flushPush(); } });
	function initAccount() {
		var id = store.get('kid', '');
		if (!USER.in) {
			// Phụ huynh đã đăng xuất: máy này quay về chế độ khách (tiến độ của bé vẫn an toàn trên tài khoản).
			if (id) { clearLocal(); rawSet('kid', ''); }
			return;
		}
		var q = window.location.search.match(/[?&]hv_pick=([a-z0-9]+)/);
		if (q && kidById(q[1])) {
			if (curKid() && curKid().id === q[1]) { rawSet('kid', ''); }
			switchKid(q[1]);
			if (window.history.replaceState) { window.history.replaceState(null, '', window.location.href.replace(/([?&])hv_pick=[a-z0-9]+&?/, '$1').replace(/[?&]$/, '')); }
			return;
		}
		var k = curKid();
		if (id && !k) { clearLocal(); rawSet('kid', ''); return; }
		if (k) {
			// Bố mẹ vừa bấm "Học lại từ đầu" trên thiết bị khác: bỏ tiến độ cũ trên máy này.
			if ((k.r || 0) !== store.get('kidR', 0)) { clearLocal(); applyKid(k, false); return; }
			applyKid(k, true);
			if (JSON.stringify(mergeP(k.p || {}, localProgress())) !== JSON.stringify(mergeP(k.p || {}, k.p || {}))) { schedulePush(); }
		}
	}
	var kidsBox = null;
	function openKids(force) {
		if (!USER.in || (!force && document.querySelector('.hv-account'))) { return; }
		if (!kidsBox) {
			kidsBox = el('div', 'hv-settings hv-kids-pick');
			kidsBox.setAttribute('role', 'dialog');
			kidsBox.setAttribute('aria-modal', 'true');
			kidsBox.setAttribute('aria-label', 'Chọn bé');
			document.body.appendChild(kidsBox);
			kidsBox.addEventListener('click', function (e) {
				var b = e.target.closest && e.target.closest('[data-kid]');
				if (b) { switchKid(b.getAttribute('data-kid')); window.location.reload(); return; }
				if (e.target === kidsBox || e.target.hasAttribute('data-close')) { kidsBox.hidden = true; }
			});
		}
		var cur = curKid();
		var list = kidList();
		kidsBox.innerHTML = '<div class="hv-settings-card"><div class="hv-owl" aria-hidden="true"></div><h3>' + (list.length ? 'Hôm nay bé nào học?' : 'Thêm bé để bắt đầu') + '</h3>' +
			'<div class="hv-kid-list">' + list.map(function (k) {
				return '<button type="button" data-kid="' + esc(k.id) + '" class="' + (cur && cur.id === k.id ? 'is-on' : '') + '"><span>' + esc(k.avatar) + '</span><b>' + esc(k.name) + '</b><small>Lớp ' + (+k.grade) + ' · ' + ((k.p && k.p.xp) || 0) + ' XP</small></button>';
			}).join('') + '</div>' +
			'<div class="hv-actions"><a class="hv-btn hv-btn-white" href="' + esc(USER.account || '#') + '">👪 Quản lý các bé</a>' + (cur ? '<button type="button" class="hv-btn" data-close>Đóng</button>' : '') + '</div></div>';
		kidsBox.hidden = false;
		var f = kidsBox.querySelector('button');
		if (f) { f.focus(); }
	}
	function bindAccount() {
		document.addEventListener('click', function (e) {
			var p = e.target.closest && e.target.closest('[data-hv-pick]');
			if (p) {
				switchKid(p.getAttribute('data-hv-pick'));
				if (p.tagName === 'BUTTON') { window.location.href = p.getAttribute('data-go') || '/'; }
				return;
			}
			if (e.target.closest && e.target.closest('[data-hv-kids]')) { openKids(true); }
		});
		document.querySelectorAll('form[data-hv-confirm]').forEach(function (f) {
			f.addEventListener('submit', function (e) { if (!window.confirm(f.getAttribute('data-hv-confirm'))) { e.preventDefault(); } });
		});
		// Thêm bé đầu tiên: hỏi có chuyển tiến độ khách đang có trên máy này cho bé không.
		document.querySelectorAll('[data-hv-adopt]').forEach(function (lab) {
			var lp = localProgress();
			var n = Object.keys(lp.done).filter(function (k) { return lp.done[k] > 0; }).length;
			if (curKid() || (!lp.xp && !n)) { return; }
			lab.hidden = false;
			lab.querySelector('span').textContent = 'Chuyển tiến độ đang có trên máy này cho bé (' + lp.xp + ' XP, ' + n + ' bài, ' + lp.stars + ' sao).';
			lab.closest('form').querySelector('[name="adopt_data"]').value = JSON.stringify(lp);
		});
		// Đang đăng nhập mà máy này chưa chọn bé: hỏi "Hôm nay bé nào học?".
		if (USER.in && kidList().length && !curKid()) { openKids(false); }
	}

	/* ---------- Khởi động ---------- */
	function init() {
		initAccount();
		document.querySelectorAll('[data-hv-game]').forEach(function (n) {
			if (!n.__hv) { n.__hv = new Player(n); }
		});
		document.querySelectorAll('[data-hv-stepper]').forEach(function (n) {
			if (!n.__hv) { n.__hv = new Stepper(n); }
		});
		renderProgress();
		renderAlbums();
		bindName();
		bindAccount();
		openArena(window.location.hash);
		document.querySelectorAll('[data-hv-speak]').forEach(function (b) {
			b.addEventListener('click', function () { speak(b.getAttribute('data-hv-speak'), b.getAttribute('data-lang') || 'vi'); });
		});
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }

	window.HocVui = { games: GAMES, init: init, _test: { mathQ: mathQ, countQ: countQ, clockQ: clockQ, wordQ: wordQ, num: num, dec: dec } };
})();
