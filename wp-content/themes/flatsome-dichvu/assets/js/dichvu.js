/**
 * Flatsome Dịch vụ – tiện ích nhỏ, không phụ thuộc jQuery.
 */
( function () {
	'use strict';

	// Nút "Nhận báo giá" / "#dang-ky": nếu trang không có form thì chuyển sang trang Liên hệ.
	if ( ! document.getElementById( 'dang-ky' ) ) {
		document.querySelectorAll( 'a[href="#dang-ky"]' ).forEach( function ( a ) {
			a.setAttribute( 'href', '/lien-he/#dang-ky' );
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
