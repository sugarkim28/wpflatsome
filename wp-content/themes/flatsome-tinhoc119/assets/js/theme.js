/* Flatsome Tin Học 119 – đánh dấu mục đang đọc trong mục lục bên phải. */
(function () {
	'use strict';
	var links = document.querySelectorAll('.th-side-toc a[href^="#"]');
	if (!links.length || !('IntersectionObserver' in window)) { return; }
	var map = {};
	links.forEach(function (a) {
		var id = decodeURIComponent(a.getAttribute('href').slice(1));
		var el = document.getElementById(id);
		if (el) { map[id] = a; }
	});
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (e) {
			if (e.isIntersecting && map[e.target.id]) {
				links.forEach(function (l) { l.classList.remove('is-active'); });
				map[e.target.id].classList.add('is-active');
			}
		});
	}, { rootMargin: '-90px 0px -70% 0px' });
	Object.keys(map).forEach(function (id) { io.observe(document.getElementById(id)); });
})();
