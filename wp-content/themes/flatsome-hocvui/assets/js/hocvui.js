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
	function speak(text, lang) {
		if (!soundOn() || !('speechSynthesis' in window) || !text) { return; }
		try {
			window.speechSynthesis.cancel();
			var u = new window.SpeechSynthesisUtterance(text);
			u.lang = lang === 'en' ? 'en-US' : 'vi-VN';
			u.rate = lang === 'en' ? 0.85 : 0.95;
			window.speechSynthesis.speak(u);
		} catch (e) { /* bỏ qua */ }
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
		'quiz': { title: 'Luyện tập', icon: '✏️', grades: [], total: 0, fromData: true }
	};

	/* ---------- Trình chơi ---------- */
	function Player(root) {
		this.root = root;
		this.id = root.getAttribute('data-hv-game');
		this.def = GAMES[this.id];
		if (!this.def) { return; }
		var g = parseInt(root.getAttribute('data-grade'), 10) || store.get('grade', 1);
		if (this.def.grades.length && this.def.grades.indexOf(g) < 0) { g = this.def.grades[0]; }
		this.grade = g;
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
			if (!soundOn() && 'speechSynthesis' in window) { window.speechSynthesis.cancel(); }
			document.querySelectorAll('.hv-sound').forEach(function (b) { b.textContent = soundOn() ? '🔊' : '🔇'; });
		});
	};
	Player.prototype.say = function (msg) { this.live.textContent = msg; };
	Player.prototype.intro = function () {
		var self = this;
		var name = store.get('name', '');
		var b = this.body;
		var sub = this.def.fromData ? 'Trả lời các câu hỏi để ôn lại bài nhé!' : (this.def.grades.length ? 'Lớp ' + this.grade + ' · ' : '') + (this.def.custom === 'memory' ? 'Lật 2 thẻ giống nhau để ghép đôi' : this.def.custom === 'order' ? 'Chạm vào các từ theo đúng thứ tự' : 'Chọn đáp án đúng');
		b.innerHTML = '<div class="hv-intro"><div class="hv-mascot" aria-hidden="true"></div>' +
			'<h3>' + (name ? 'Chào ' + esc(name) + '!' : 'Chào bạn nhỏ!') + '</h3><p>' + esc(sub) + '</p>' +
			'<button type="button" class="hv-btn hv-btn-big">Bắt đầu chơi ▶</button></div>';
		b.querySelector('button').addEventListener('click', function () { self.start(); });
	};
	Player.prototype.start = function () {
		if (this.def.custom === 'memory') { return this.memory(); }
		if (this.def.custom === 'order') { return this.order(); }
		this.state = {};
		this.marks = [];
		this.i = 0;
		this.right = 0;
		this.t0 = Date.now();
		if (this.def.fromData) {
			var data = [];
			try { data = JSON.parse(this.root.getAttribute('data-quiz') || '[]'); } catch (e) { data = []; }
			this.deck = shuffle(data).map(function (r) { return { q: r.q, say: r.q, o: { choices: shuffle([r.a].concat(r.w || [])), a: r.a } }; });
		} else if (this.def.deck) {
			this.deck = this.def.deck(this.grade).slice(0, this.def.total);
		} else {
			this.deck = [];
			var seen = {};
			var guard = 0;
			while (this.deck.length < this.def.total && guard++ < 100) {
				var q = this.def.make(this.grade, this.state);
				var k = q.q + (q.visual || '') + q.o.a;
				if (!seen[k]) { seen[k] = 1; this.deck.push(q); }
			}
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
		if (this.grade <= 1 && store.get('autoread', true)) { speak(q.say || q.q); }
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
		var ok = val === q.o.a;
		buttons.forEach(function (b, idx) {
			b.disabled = true;
			if (q.o.choices[idx] === q.o.a) { b.classList.add('is-right'); }
		});
		var fb = this.body.querySelector('.hv-feedback');
		if (ok) {
			this.right++;
			sfx.good();
			fb.innerHTML = pick(['Giỏi quá! 🎉', 'Chính xác! 🌟', 'Tuyệt vời! 👏', 'Đúng rồi! 💯']);
			this.say('Đúng rồi');
		} else {
			btn.classList.add('is-wrong');
			sfx.bad();
			fb.innerHTML = 'Chưa đúng rồi. Đáp án là <b>' + esc(q.o.a) + '</b>' + (q.hint ? ' (' + esc(q.hint) + ')' : '');
			this.say('Chưa đúng. Đáp án là ' + q.o.a);
		}
		fb.className = 'hv-feedback ' + (ok ? 'ok' : 'no');
		if (q.word && ok) { speak(q.word); }
		this.marks[this.i] = ok;
		setTimeout(function () {
			self.locked = false;
			self.i++;
			if (self.i < self.deck.length) { self.ask(); } else { self.finish(self.right, self.deck.length); }
		}, ok ? 900 : 2200);
	};
	Player.prototype.finish = function (right, total, extra) {
		var self = this;
		var ratio = right / total;
		var got = ratio >= 0.9 ? 3 : ratio >= 0.6 ? 2 : ratio > 0 ? 1 : 0;
		var secs = Math.round((Date.now() - (this.t0 || Date.now())) / 1000);
		var fast = this.def.timer && got === 3 && secs <= total * 6;
		var earned = got + (fast ? 1 : 0);
		var sticker = earned ? addStars(earned) : '';
		this.marks = [];
		var starsHtml = '';
		for (var i = 1; i <= 3; i++) { starsHtml += '<span class="' + (i <= got ? 'on' : '') + '">★</span>'; }
		var msg = got === 3 ? 'Xuất sắc!' : got === 2 ? 'Làm tốt lắm!' : got === 1 ? 'Cố lên nhé!' : 'Mình thử lại nào!';
		this.body.innerHTML = '<div class="hv-result"><div class="hv-big-stars" aria-label="' + got + ' sao">' + starsHtml + '</div>' +
			'<h3>' + msg + '</h3><p>' + (extra ? esc(extra) : 'Em làm đúng <b>' + right + '/' + total + '</b>' + (this.def.timer ? ' · ' + secs + ' giây' : '')) + '</p>' +
			(fast ? '<p class="hv-fast">⚡ Siêu tốc! Thêm 1 sao thưởng</p>' : '') +
			(earned ? '<p>Em nhận được <b>+' + earned + ' ⭐</b></p>' : '') +
			(sticker ? '<div class="hv-new-sticker"><span>' + sticker + '</span>Em mở được nhãn dán mới!</div>' : '') +
			'<div class="hv-actions"><button type="button" class="hv-btn hv-again">Chơi lại ↻</button>' +
			(CFG.gamesUrl && !this.def.fromData ? '<a class="hv-btn hv-btn-ghost" href="' + esc(CFG.gamesUrl) + '">Trò chơi khác</a>' : '') + '</div></div>';
		this.body.querySelector('.hv-again').addEventListener('click', function () { self.start(); });
		if (got >= 2) { sfx.win(); confetti(this.root); }
		this.say(msg + ' Em làm đúng ' + right + ' trên ' + total);
	};

	/* Lật thẻ */
	Player.prototype.memory = function () {
		var self = this;
		var topic = pick(EN_TOPICS[this.grade] || EN_TOPICS[3]);
		var pairs = shuffle(EN[topic]).slice(0, this.grade <= 2 ? 4 : 6);
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
					sfx.good();
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
						if (ok) { self.right++; sfx.good(); fb.innerHTML = 'Đúng rồi! 🎉'; } else { sfx.bad(); fb.innerHTML = 'Câu đúng là: <b>' + esc(sentence) + '.</b>'; }
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
				renderStarBadges();
				var ok = f.querySelector('.hv-saved');
				if (ok) { ok.hidden = false; setTimeout(function () { ok.hidden = true; }, 1800); }
			});
		});
		document.querySelectorAll('[data-hv-reset]').forEach(function (b) {
			b.addEventListener('click', function () {
				if (window.confirm('Xoá toàn bộ sao và nhãn dán trên máy này?')) {
					store.set('stars', 0); store.set('played', 0);
					renderStarBadges(); renderAlbums();
				}
			});
		});
	}

	/* ---------- Khởi động ---------- */
	function init() {
		document.querySelectorAll('[data-hv-game]').forEach(function (n) {
			if (!n.__hv) { n.__hv = new Player(n); }
		});
		renderStarBadges();
		renderAlbums();
		bindName();
		document.querySelectorAll('[data-hv-speak]').forEach(function (b) {
			b.addEventListener('click', function () { speak(b.getAttribute('data-hv-speak'), b.getAttribute('data-lang') || 'vi'); });
		});
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }

	window.HocVui = { games: GAMES, init: init, _test: { mathQ: mathQ, countQ: countQ, clockQ: clockQ, wordQ: wordQ, num: num, dec: dec } };
})();
