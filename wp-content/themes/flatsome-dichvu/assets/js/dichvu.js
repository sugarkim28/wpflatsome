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

	// Bấm "Chọn gói này" trong bảng giá: ghi tên gói vào ô nội dung của form.
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-sgd-package]' ) : null;
		var box = document.getElementById( 'dang-ky' );
		if ( ! btn || ! box ) {
			return;
		}
		// Form nằm trong #dang-ky, hoặc form đầu tiên đứng sau mốc #dang-ky.
		var form = box.querySelector( '.sgd-form' );
		if ( ! form ) {
			Array.prototype.some.call( document.querySelectorAll( '.sgd-form' ), function ( f ) {
				if ( box.compareDocumentPosition( f ) & Node.DOCUMENT_POSITION_FOLLOWING ) {
					form = f;
					return true;
				}
				return false;
			} );
		}
		var note = form ? form.querySelector( 'textarea[name="sgd_note"]' ) : null;
		if ( note ) {
			note.value = 'Tôi chọn: ' + btn.getAttribute( 'data-sgd-package' ) + ( note.value ? '\n' + note.value.replace( /^Tôi chọn: .*\n?/, '' ) : '' );
			form.classList.add( 'is-picked' );
		}
		var name = form ? form.querySelector( 'input[name="sgd_name"]' ) : null;
		if ( name ) {
			setTimeout( function () {
				name.focus( { preventScroll: true } );
			}, 450 );
		}
	} );
} )();
