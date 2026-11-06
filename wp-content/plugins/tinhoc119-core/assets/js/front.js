/* Tin Học 119 – form yêu cầu hỗ trợ, tra cứu bảo hành, nút sao chép lệnh. */
(function () {
	'use strict';
	var ajaxUrl = (window.TH119Track && window.TH119Track.ajax) || '/wp-admin/admin-ajax.php';

	function msg(box, ok, html) {
		box.hidden = false;
		box.innerHTML = '<div class="th-alert ' + (ok ? 'th-alert--ok' : 'th-alert--err') + '"></div>';
		box.firstChild.textContent = html;
		box.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	function clearErrors(form) {
		form.querySelectorAll('.th-field.is-error').forEach(function (f) { f.classList.remove('is-error'); });
		form.querySelectorAll('.th-field__err').forEach(function (n) { n.remove(); });
	}

	function showErrors(form, errors) {
		Object.keys(errors || {}).forEach(function (name) {
			var input = form.querySelector('[name="' + name + '"]') || form.querySelector('[name="' + name + '[]"]');
			if (!input) { return; }
			var field = input.closest('.th-field');
			if (!field) { return; }
			field.classList.add('is-error');
			var s = document.createElement('span');
			s.className = 'th-field__err';
			s.textContent = errors[name];
			field.appendChild(s);
		});
	}

	// Form yêu cầu hỗ trợ / đặt lịch.
	document.querySelectorAll('.th-ticket-form').forEach(function (form) {
		var src = form.querySelector('[name="source_url"]');
		var ref = form.querySelector('[name="ref"]');
		if (src) { src.value = location.href.split('#')[0].replace(/[?&]th_(ok|err)=[^&]*/g, ''); }
		if (ref) { ref.value = document.referrer || ''; }
		var files = form.querySelector('input[type="file"]');
		if (files) {
			files.addEventListener('change', function () {
				if (files.files.length > 3) { alert('Chỉ gửi tối đa 3 ảnh.'); files.value = ''; return; }
				for (var i = 0; i < files.files.length; i++) {
					if (files.files[i].size > 5 * 1024 * 1024) { alert('Mỗi ảnh tối đa 5 MB.'); files.value = ''; return; }
				}
			});
		}
		form.addEventListener('submit', function (e) {
			if (!window.fetch || !window.FormData) { return; }
			e.preventDefault();
			var box = form.querySelector('.th-form__msg');
			var btn = form.querySelector('button[type="submit"]');
			clearErrors(form);
			var phone = form.querySelector('[name="phone"]');
			if (phone && !/^(\+?84|0)\d{9,10}$/.test(phone.value.replace(/[\s.\-]/g, ''))) {
				showErrors(form, { phone: 'Số điện thoại chưa đúng (10 số, bắt đầu bằng 0).' });
				phone.focus();
				return;
			}
			var fd = new FormData(form);
			fd.set('action', 'th119_ticket');
			btn.disabled = true;
			var label = btn.textContent;
			btn.textContent = 'Đang gửi…';
			fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res.ok) {
						msg(box, true, res.message);
						form.reset();
						if (src) { src.value = location.href.split('#')[0]; }
						if (window.gtag) { window.gtag('event', 'generate_lead', { form: 'th119_ticket' }); }
					} else {
						msg(box, false, res.message || 'Gửi không thành công.');
						showErrors(form, res.errors);
					}
				})
				.catch(function () { msg(box, false, 'Không gửi được. Vui lòng kiểm tra mạng hoặc gọi hotline.'); })
				.then(function () { btn.disabled = false; btn.textContent = label; });
		});
	});

	// Tra cứu bảo hành.
	document.querySelectorAll('.th-lookup-form').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			if (!window.fetch || !window.FormData) { return; }
			e.preventDefault();
			var out = form.querySelector('.th-lookup-result');
			var btn = form.querySelector('button[type="submit"]');
			var fd = new FormData(form);
			fd.set('action', 'th119_lookup');
			btn.disabled = true;
			out.innerHTML = '<p class="th-loading">Đang tra cứu…</p>';
			fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) { out.innerHTML = res.html || ''; })
				.catch(function () { out.innerHTML = '<div class="th-alert th-alert--err">Không tra cứu được, vui lòng thử lại.</div>'; })
				.then(function () { btn.disabled = false; });
		});
	});

	// Sao chép lệnh kỹ thuật.
	document.addEventListener('click', function (e) {
		var b = e.target.closest ? e.target.closest('.th-code__copy') : null;
		if (!b) { return; }
		var code = b.closest('.th-code').querySelector('code').textContent;
		var done = function () { b.textContent = 'Đã chép'; setTimeout(function () { b.textContent = 'Sao chép'; }, 1500); };
		if (navigator.clipboard) { navigator.clipboard.writeText(code).then(done); }
		else {
			var t = document.createElement('textarea');
			t.value = code; document.body.appendChild(t); t.select();
			try { document.execCommand('copy'); done(); } catch (err) { /* bỏ qua */ }
			t.remove();
		}
	});
})();
