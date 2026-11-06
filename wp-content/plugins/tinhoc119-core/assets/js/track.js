/* Tin Học 119 – đếm lượt xem & lượt bấm Gọi/Zalo (ẩn danh, không cookie). */
(function () {
	'use strict';
	var cfg = window.TH119Track || {};
	if (!cfg.url) { return; }

	function send(m) {
		var data = JSON.stringify({ m: m, p: location.pathname, r: document.referrer || '' });
		try {
			if (navigator.sendBeacon && navigator.sendBeacon(cfg.url, new Blob([data], { type: 'application/json' }))) { return; }
		} catch (e) { /* bỏ qua */ }
		try {
			fetch(cfg.url, { method: 'POST', body: data, headers: { 'Content-Type': 'application/json' }, keepalive: true, credentials: 'omit' });
		} catch (e2) { /* bỏ qua */ }
	}

	function gaEvent(name) {
		if (cfg.ga && typeof window.gtag === 'function') {
			window.gtag('event', name, { link_url: location.pathname });
		}
	}

	if (cfg.views) {
		if (document.readyState === 'complete') { send('view'); } else { window.addEventListener('load', function () { send('view'); }); }
	}

	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('a') : null;
		if (!a) { return; }
		var type = a.getAttribute('data-track');
		var href = a.getAttribute('href') || '';
		if (!type) {
			if (href.indexOf('tel:') === 0) { type = 'call'; }
			else if (/zalo\.me|zalo:\/\//i.test(href)) { type = 'zalo'; }
			else if (/m\.me\/|messenger\.com/i.test(href)) { type = 'messenger'; }
			else if (/google\.[a-z.]+\/maps|maps\.app\.goo\.gl|goo\.gl\/maps/i.test(href)) { type = 'maps'; }
		}
		if (!type) { return; }
		if (cfg.views) { send(type); }
		gaEvent({ call: 'click_call', zalo: 'click_zalo', cta: 'click_request', maps: 'click_directions', messenger: 'click_messenger' }[type] || 'click_' + type);
	}, true);
})();
