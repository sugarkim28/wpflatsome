/**
 * Flatsome Portal – tiện ích nhỏ, không phụ thuộc jQuery.
 */
( function () {
	'use strict';

	// Nút "Nhận báo giá" / "#dang-ky": nếu trang không có form thì chuyển sang trang Liên hệ.
	if ( ! document.getElementById( 'dang-ky' ) ) {
		document.querySelectorAll( 'a[href="#dang-ky"]' ).forEach( function ( a ) {
			a.setAttribute( 'href', '/lien-he/#dang-ky' );
		} );
	}

	// Bộ lọc: bỏ tham số trống để URL gọn (?kv=binh-duong thay vì ?kv=binh-duong&lh=&gia=).
	document.querySelectorAll( '.sgp-filter' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function () {
			form.querySelectorAll( 'select' ).forEach( function ( s ) {
				if ( ! s.value ) {
					s.disabled = true;
				}
			} );
		} );
	} );
} )();
