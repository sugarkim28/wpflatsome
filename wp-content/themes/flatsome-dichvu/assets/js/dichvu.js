/**
 * Flatsome Dịch vụ – tiện ích nhỏ, không phụ thuộc jQuery.
 */
( function () {
	'use strict';

	var popup = document.getElementById( 'sgd-popup' );
	var lastFocus = null;

	function openPopup() {
		if ( ! popup || ! popup.hidden ) {
			return;
		}
		lastFocus = document.activeElement;
		popup.hidden = false;
		document.documentElement.classList.add( 'sgd-noscroll' );
		var first = popup.querySelector( 'input[name="sgd_name"]' );
		if ( first ) {
			first.focus( { preventScroll: true } );
		}
		try {
			sessionStorage.setItem( 'sgdPopup', '1' );
		} catch ( e ) {}
	}

	function closePopup() {
		if ( ! popup || popup.hidden ) {
			return;
		}
		popup.hidden = true;
		popup.classList.remove( 'is-package' );
		document.documentElement.classList.remove( 'sgd-noscroll' );
		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	if ( popup ) {
		popup.addEventListener( 'click', function ( e ) {
			if ( e.target === popup || e.target.closest( '.sgd-popup__close' ) ) {
				closePopup();
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				closePopup();
			}
		} );
		// Có thông báo kết quả gửi form popup → mở lại để khách thấy.
		if ( /[?&]sgd_form=popup\b/.test( location.search ) ) {
			openPopup();
		} else {
			var delay = parseInt( popup.getAttribute( 'data-delay' ), 10 ) || 0;
			var seen = false;
			try {
				seen = !! sessionStorage.getItem( 'sgdPopup' );
			} catch ( e ) {}
			// Chỉ tự mở trên màn hình lớn: popup tự bật trên điện thoại bị Google coi là quảng cáo xen ngang.
			var desktop = window.matchMedia && window.matchMedia( '(min-width: 850px)' ).matches;
			if ( delay > 0 && ! seen && desktop ) {
				setTimeout( openPopup, delay * 1000 );
			}
		}
	}

	// Liên kết "#dang-ky": trang có form thì cuộn tới; không có thì mở popup (hoặc sang trang Liên hệ).
	if ( ! document.getElementById( 'dang-ky' ) ) {
		document.querySelectorAll( 'a[href="#dang-ky"], .sgd-menu-cta > a' ).forEach( function ( a ) {
			if ( popup ) {
				a.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					openPopup();
				} );
			} else {
				a.setAttribute( 'href', window.sgdContactUrl || '#dang-ky' );
			}
		} );
	}

	// Tab (danh mục dịch vụ, bảng giá): chuột, bàn phím ← → ↑ ↓.
	document.querySelectorAll( '.sgd-tabset' ).forEach( function ( set ) {
		var tabs = Array.prototype.slice.call( set.querySelectorAll( '[role="tab"]' ) );
		function select( tab, focus ) {
			tabs.forEach( function ( t ) {
				var on = t === tab;
				t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				t.tabIndex = on ? 0 : -1;
				var panel = document.getElementById( t.getAttribute( 'aria-controls' ) );
				if ( panel ) {
					panel.hidden = ! on;
				}
			} );
			if ( focus ) {
				tab.focus();
			}
		}
		tabs.forEach( function ( tab, i ) {
			tab.addEventListener( 'click', function () {
				select( tab, false );
			} );
			tab.addEventListener( 'keydown', function ( e ) {
				var d = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[ e.key ];
				if ( d ) {
					e.preventDefault();
					select( tabs[ ( i + d + tabs.length ) % tabs.length ], true );
				}
			} );
		} );
	} );

	// Bấm "Chọn gói này": mở ngay form đăng ký (popup) đã ghi sẵn gói + giá, con trỏ ở ô họ tên → khách điền và gửi luôn.
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-sgd-package]' ) : null;
		if ( ! btn ) {
			return;
		}
		var pkg   = btn.getAttribute( 'data-sgd-package' );
		var price = btn.getAttribute( 'data-sgd-price' ) || '';
		var form  = popup ? popup.querySelector( '.sgd-form' ) : null;
		if ( ! form ) {
			form = document.querySelector( '#dang-ky .sgd-form' ) || document.querySelector( '.sgd-form' );
			if ( ! form ) {
				return;
			}
		} else {
			e.preventDefault();
			var pick = popup.querySelector( '.sgd-popup__pick' );
			if ( pick ) {
				pick.querySelector( 'strong' ).textContent = pkg;
				pick.querySelector( 'b' ).textContent = price;
				pick.hidden = false;
			}
			popup.classList.add( 'is-package' );
		}
		var note = form.querySelector( 'textarea[name="sgd_note"]' );
		if ( note ) {
			var rest = note.value.replace( /^Tôi chọn: .*\n?/, '' ).trim();
			note.value = 'Tôi chọn: ' + pkg + ( price ? ' (' + price + ')' : '' ) + ( rest ? '\n' + rest : '' );
		}
		form.classList.add( 'is-picked' );
		if ( popup && popup.contains( form ) ) {
			openPopup();
		} else {
			var name = form.querySelector( 'input[name="sgd_name"]' );
			form.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			if ( name ) {
				setTimeout( function () {
					name.focus( { preventScroll: true } );
				}, 450 );
			}
		}
	} );
	// Nút chia sẻ: Facebook/X/LinkedIn/Telegram mở cửa sổ nhỏ; Zalo dùng bảng chia sẻ của điện thoại
	// (có Zalo), máy tính thì sao chép liên kết để dán vào Zalo; "Sao chép" lấy liên kết bài.
	document.querySelectorAll( '.sgd-share' ).forEach( function ( box ) {
		var url = box.getAttribute( 'data-url' ), title = box.getAttribute( 'data-title' ), msg = box.querySelector( '.sgd-share__msg' );
		function say( t ) { if ( msg ) { msg.textContent = t; setTimeout( function () { msg.textContent = ''; }, 3500 ); } }
		function copy( done ) {
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( url ).then( function () { say( done ); } );
			} else {
				var ta = document.createElement( 'textarea' ); ta.value = url; ta.style.position = 'fixed'; ta.style.opacity = '0';
				document.body.appendChild( ta ); ta.select();
				try { document.execCommand( 'copy' ); say( done ); } catch ( e ) { window.prompt( 'Sao chép liên kết:', url ); }
				document.body.removeChild( ta );
			}
		}
		box.addEventListener( 'click', function ( e ) {
			var a = e.target.closest( 'a.sgd-share__btn' ), b = e.target.closest( 'button[data-share]' );
			if ( a ) {
				e.preventDefault();
				window.open( a.href, 'sgd-share', 'width=640,height=560,noopener' );
			} else if ( b && 'zalo' === b.getAttribute( 'data-share' ) ) {
				if ( navigator.share ) {
					navigator.share( { title: title, url: url } ).catch( function () {} );
				} else {
					copy( 'Đã sao chép liên kết – dán vào Zalo để gửi.' );
				}
			} else if ( b ) {
				copy( 'Đã sao chép liên kết.' );
			}
		} );
	} );
	// Slider dịch vụ đầu trang chủ: tự chuyển, dừng khi rê chuột / focus, phím mũi tên, vuốt trên điện thoại.
	document.querySelectorAll( '.sgd-hslider' ).forEach( function ( box ) {
		var tabs = box.querySelectorAll( '.sgd-hslider__tab' ),
			slides = box.querySelectorAll( '.sgd-hslide' ),
			ms = parseInt( box.getAttribute( 'data-interval' ), 10 ) || 6000,
			reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches,
			cur = 0, timer = null, hold = false, x0 = null;
		if ( slides.length < 2 ) {
			return;
		}
		box.style.setProperty( '--sgd-hs', ms + 'ms' );
		function go( n, focus ) {
			cur = ( n + slides.length ) % slides.length;
			slides.forEach( function ( s, i ) {
				var on = i === cur;
				s.classList.toggle( 'is-active', on );
				if ( on ) {
					s.removeAttribute( 'aria-hidden' );
				} else {
					s.setAttribute( 'aria-hidden', 'true' );
				}
				s.querySelectorAll( 'a' ).forEach( function ( a ) {
					if ( on ) {
						a.removeAttribute( 'tabindex' );
					} else {
						a.setAttribute( 'tabindex', '-1' );
					}
				} );
			} );
			tabs.forEach( function ( t, i ) {
				var on = i === cur;
				t.classList.toggle( 'is-active', on );
				t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				t.setAttribute( 'tabindex', on ? '0' : '-1' );
				// Khởi động lại thanh tiến trình.
				var bar = t.querySelector( 'i' );
				if ( bar ) {
					bar.style.animation = 'none';
					void bar.offsetWidth;
					bar.style.animation = '';
				}
			} );
			if ( focus ) {
				tabs[ cur ].focus();
			}
			play();
		}
		function play() {
			clearTimeout( timer );
			if ( reduce || hold || document.hidden ) {
				box.classList.remove( 'is-playing' );
				return;
			}
			box.classList.add( 'is-playing' );
			timer = setTimeout( function () {
				go( cur + 1 );
			}, ms );
		}
		function pause( on ) {
			hold = on;
			box.classList.toggle( 'is-paused', on );
			if ( on ) {
				clearTimeout( timer );
			} else {
				go( cur );
			}
		}
		tabs.forEach( function ( t, i ) {
			t.addEventListener( 'click', function () {
				go( i );
			} );
			t.addEventListener( 'keydown', function ( e ) {
				if ( 'ArrowRight' === e.key || 'ArrowLeft' === e.key ) {
					e.preventDefault();
					go( cur + ( 'ArrowRight' === e.key ? 1 : -1 ), true );
				}
			} );
		} );
		box.addEventListener( 'mouseenter', function () {
			pause( true );
		} );
		box.addEventListener( 'mouseleave', function () {
			pause( false );
		} );
		box.addEventListener( 'focusin', function () {
			if ( ! hold ) {
				hold = true;
				clearTimeout( timer );
				box.classList.add( 'is-paused' );
			}
		} );
		box.addEventListener( 'focusout', function ( e ) {
			if ( ! box.contains( e.relatedTarget ) ) {
				pause( false );
			}
		} );
		box.addEventListener( 'touchstart', function ( e ) {
			x0 = e.touches[ 0 ].clientX;
		}, { passive: true } );
		box.addEventListener( 'touchend', function ( e ) {
			if ( null === x0 ) {
				return;
			}
			var dx = e.changedTouches[ 0 ].clientX - x0;
			x0 = null;
			if ( Math.abs( dx ) > 50 ) {
				go( cur + ( dx < 0 ? 1 : -1 ) );
			}
		}, { passive: true } );
		document.addEventListener( 'visibilitychange', play );
		play();
	} );
} )();
