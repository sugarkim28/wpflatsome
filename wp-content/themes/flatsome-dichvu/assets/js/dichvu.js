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
} )();
