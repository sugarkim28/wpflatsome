/* Tin Học 119 – trang quản trị. */
(function ($) {
	'use strict';
	var A = window.TH119Admin || {};

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	/* Chọn ảnh ở trang Cài đặt */
	$(document).on('click', '.th119-pick-image', function (e) {
		e.preventDefault();
		var target = $('#' + $(this).data('target'));
		var preview = target.siblings('.th119-img-preview');
		var frame = wp.media({ title: 'Chọn ảnh', library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			target.val(att.id);
			preview.attr('src', (att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url)).show();
		});
		frame.open();
	});
	$(document).on('click', '.th119-clear-image', function (e) {
		e.preventDefault();
		var target = $('#' + $(this).data('target'));
		target.val('');
		target.siblings('.th119-img-preview').hide();
	});

	/* Kiểm tra chất lượng bài */
	$(document).on('click', '.th119-check-post', function () {
		var btn = $(this), out = btn.siblings('.th119-check-result');
		btn.prop('disabled', true);
		out.html('<p>Đang kiểm tra…</p>');
		$.post(A.ajax, { action: 'th119_check_post', nonce: A.nonce, post_id: btn.data('id') })
			.done(function (r) { out.html(r && r.success ? r.data.html : '<p>Lỗi: ' + esc(r && r.data ? r.data.message : '') + '</p>'); })
			.fail(function () { out.html('<p>Không kiểm tra được.</p>'); })
			.always(function () { btn.prop('disabled', false); });
	});

	/* ---------------- AI ---------------- */
	var box = $('.th119-ai');
	if (!box.length) { return; }
	var logIds = [];

	function field(name) { return box.find('[data-field="' + name + '"]'); }
	function catId() { return box.find('select[name$="_cat"]').val() || 0; }
	function collect() {
		var d = { cat: catId() };
		box.find('[data-field]').each(function () { d[$(this).data('field')] = $(this).val(); });
		return d;
	}
	function out(html) { box.find('.th119-ai-out').html(html); }

	function renderJson(task, data) {
		if (!data) { return ''; }
		if (task === 'topics' && Array.isArray(data)) {
			return '<table class="widefat striped"><thead><tr><th>Chủ đề</th><th>Từ khoá</th><th>Ý định</th><th>Lý do</th><th></th></tr></thead><tbody>' + data.map(function (t) {
				return '<tr><td>' + esc(t.title) + '</td><td>' + esc(t.keyword) + '</td><td>' + esc(t.intent) + '</td><td>' + esc(t.why) + '</td><td><a class="button button-small" href="admin.php?page=th119-ai&tab=write&topic=' + encodeURIComponent(t.title || '') + '">Soạn bài</a></td></tr>';
			}).join('') + '</tbody></table>';
		}
		if (task === 'titles' && Array.isArray(data)) {
			return '<ol>' + data.map(function (t) { return '<li>' + esc(t) + ' <span class="th119-count">(' + String(t).length + ' ký tự)</span> <a class="button-link" href="admin.php?page=th119-ai&tab=write&topic=' + encodeURIComponent(t) + '">Soạn bài</a></li>'; }).join('') + '</ol>';
		}
		if (task === 'cluster' && data.articles) {
			return '<p><strong>Bài trụ cột:</strong> ' + esc(data.pillar && data.pillar.title) + ' – <em>' + esc(data.pillar && data.pillar.keyword) + '</em></p><table class="widefat striped"><thead><tr><th>Bài vệ tinh</th><th>Từ khoá</th><th>Liên kết tới</th><th></th></tr></thead><tbody>' + data.articles.map(function (a) {
				return '<tr><td>' + esc(a.title) + (a.exists ? ' <em>(đã có)</em>' : '') + '</td><td>' + esc(a.keyword) + '</td><td>' + esc((a.links_to || []).join('; ')) + '</td><td><a class="button button-small" href="admin.php?page=th119-ai&tab=write&topic=' + encodeURIComponent(a.title || '') + '">Soạn bài</a></td></tr>';
			}).join('') + '</tbody></table>';
		}
		if (task === 'keywords') {
			return '<table class="widefat striped"><tbody>' +
				'<tr><th>Ý định tìm kiếm</th><td>' + esc(data.intent) + '</td></tr>' +
				'<tr><th>Người tìm</th><td>' + esc(data.audience) + '</td></tr>' +
				'<tr><th>Từ khoá liên quan</th><td>' + esc((data.related || []).join(', ')) + '</td></tr>' +
				'<tr><th>Câu hỏi thường gặp</th><td>' + (data.questions || []).map(esc).join('<br>') + '</td></tr>' +
				'<tr><th>Nên viết dạng</th><td>' + esc(data.content_type) + '</td></tr>' +
				'<tr><th>Yếu tố địa phương</th><td>' + esc(data.local) + '</td></tr></tbody></table>';
		}
		return '';
	}

	function faqRow(q, a) {
		return '<div class="th119-faq-row"><input type="text" class="th119-faq-q" value="' + esc(q) + '" placeholder="Câu hỏi"><input type="text" class="th119-faq-a" value="' + esc(a) + '" placeholder="Trả lời"><button type="button" class="button-link-delete th119-faq-del" aria-label="Xoá">✕</button></div>';
	}
	$(document).on('click', '.th119-faq-del', function () { $(this).closest('.th119-faq-row').remove(); });

	box.on('click', 'button[data-task]', function (e) {
		e.preventDefault();
		var btn = $(this), task = btn.data('task'), d = collect();
		if (!d.topic && task !== 'keywords') { alert('Nhập chủ đề / tiêu đề trước.'); return; }
		if (task === 'keywords' && !d.keyword) { alert('Nhập từ khoá.'); return; }
		if (task === 'draft' && !d.outline) { alert('Tạo hoặc nhập dàn ý trước.'); return; }
		if ((task === 'meta' || task === 'faq' || task === 'links') && !d.content) { alert('Cần có nội dung bài trước.'); return; }
		box.addClass('th119-busy');
		out('<p>AI đang xử lý… (bài dài có thể mất 1–3 phút)</p>');
		$.post(A.ajax, { action: 'th119_ai_run', nonce: A.nonce, task: task, data: d })
			.done(function (r) {
				if (!r || !r.success) { out('<div class="notice notice-error inline"><p>' + esc(r && r.data ? r.data.message : 'Lỗi') + '</p></div>'); return; }
				var res = r.data;
				if (res.log) { logIds.push(res.log); }
				var note = '<p class="description">Chi phí ước tính: ' + res.cost + ' USD</p>';
				if (task === 'outline') { field('outline').val(res.text.trim()); out(note); return; }
				if (task === 'draft') { field('content').val(res.text.trim()); out('<div class="notice notice-warning inline"><p>Đọc lại toàn bộ, kiểm tra từng lệnh/thông số, thay các chỗ [ẢNH: …] và [CẦN KIỂM TRA …] trước khi gửi duyệt.</p></div>' + note); return; }
				if (task === 'meta' && res.json) {
					field('meta_title').val(res.json.title || '').trigger('input');
					field('meta_desc').val(res.json.description || '').trigger('input');
					field('slug').val(res.json.slug || '');
					field('excerpt').val(res.json.excerpt || '');
					out(note); return;
				}
				if (task === 'faq' && Array.isArray(res.json)) {
					box.find('.th119-faq-list').html(res.json.map(function (f) { return faqRow(f.q, f.a); }).join(''));
					out(note); return;
				}
				if (task === 'links' && Array.isArray(res.json)) {
					box.find('.th119-links-list').html(res.json.length ? '<ul>' + res.json.map(function (l) {
						return '<li><code>&lt;a href="' + esc(l.url) + '"&gt;' + esc(l.anchor) + '&lt;/a&gt;</code> – ' + esc(l.where) + '</li>';
					}).join('') + '</ul>' : '<p>Không tìm thấy trang phù hợp.</p>');
					out(note); return;
				}
				var html = renderJson(task, res.json);
				out((html || '<pre class="th119-pre">' + esc(res.text) + '</pre>') + note);
			})
			.fail(function (x) { out('<div class="notice notice-error inline"><p>Lỗi kết nối (' + esc(x.status) + '). Thử lại hoặc giảm độ dài yêu cầu.</p></div>'); })
			.always(function () { box.removeClass('th119-busy'); });
	});

	box.on('click', '.th119-preview', function (e) {
		e.preventDefault();
		var p = box.find('.th119-preview-box');
		p.prop('hidden', !p.prop('hidden'));
		// Hiển thị bản xem trước an toàn: loại bỏ script/thuộc tính sự kiện.
		var tmp = $('<div>').html($.parseHTML(field('content').val() || '', document, false));
		tmp.find('*').each(function () {
			var el = this;
			$.each($.makeArray(el.attributes), function (i, at) { if (/^on/i.test(at.name)) { el.removeAttribute(at.name); } });
		});
		p.html(tmp.html());
	});

	box.on('input', '[data-field="meta_title"], [data-field="meta_desc"]', function () {
		var f = $(this).data('field'), len = $(this).val().length;
		var ok = f === 'meta_title' ? (len >= 30 && len <= 60) : (len >= 110 && len <= 160);
		box.find('.th119-count[data-for="' + f + '"]').text(len + ' ký tự').toggleClass('bad', !ok);
	});

	box.on('click', '.th119-create-draft', function (e) {
		e.preventDefault();
		var d = collect();
		d.title = d.topic;
		d.faq = box.find('.th119-faq-row').map(function () {
			return { q: $(this).find('.th119-faq-q').val(), a: $(this).find('.th119-faq-a').val() };
		}).get();
		d.log_ids = logIds;
		if (!d.title || !d.content) { alert('Cần tiêu đề và nội dung.'); return; }
		box.addClass('th119-busy');
		$.post(A.ajax, { action: 'th119_ai_create_draft', nonce: A.nonce, data: d })
			.done(function (r) {
				if (!r || !r.success) { out('<div class="notice notice-error inline"><p>' + esc(r && r.data ? r.data.message : 'Lỗi') + '</p></div>'); return; }
				var dups = (r.data.dups || []).map(function (x) { return '<li><a href="' + esc(x[2]) + '" target="_blank">' + esc(x[1]) + '</a> – trùng nội dung ' + x[3] + '%, tiêu đề giống ' + x[4] + '%</li>'; }).join('');
				out('<div class="notice notice-success inline"><p>Đã tạo bản nháp. <a class="button button-primary" href="' + esc(r.data.edit) + '">Mở bài để chèn ảnh & chỉnh sửa</a></p></div>' + (dups ? '<div class="notice notice-warning inline"><p>Có bài tương tự trên website – kiểm tra tránh trùng lặp:</p><ul>' + dups + '</ul></div>' : ''));
			})
			.always(function () { box.removeClass('th119-busy'); });
	});
})(jQuery);
