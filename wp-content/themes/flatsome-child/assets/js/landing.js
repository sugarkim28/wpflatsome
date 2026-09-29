/**
 * Landing page Bất động sản – popup đăng ký, video lazy, thanh liên hệ mobile.
 */
( function () {
	'use strict';

	var cfg = window.bdsLanding || {};
	var STORAGE_KEY = 'bdsPopupShown';

	function storage( action, value ) {
		try {
			if ( action === 'get' ) {
				return window.sessionStorage.getItem( STORAGE_KEY );
			}
			window.sessionStorage.setItem( STORAGE_KEY, value );
		} catch ( e ) {}
		return null;
	}

	document.body.classList.add( 'bds-has-mbar' );

	/* ---------- Popup ---------- */
	var modal = document.getElementById( 'bds-modal' );
	var lastFocus = null;

	function openModal() {
		if ( ! modal ) {
			return false;
		}
		lastFocus = document.activeElement;
		modal.hidden = false;
		storage( 'set', '1' );
		var first = modal.querySelector( 'input[name="bds_name"]' );
		if ( first ) {
			first.focus();
		}
		return true;
	}

	function closeModal() {
		if ( ! modal ) {
			return;
		}
		modal.hidden = true;
		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	if ( modal ) {
		modal.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '[data-bds-close]' ) ) {
				closeModal();
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! modal.hidden ) {
				closeModal();
			}
		} );

		var params = new URLSearchParams( window.location.search );
		if ( params.get( 'bds_form' ) === 'popup' ) {
			// Hiện lại popup để khách thấy kết quả gửi form.
			openModal();
		} else if ( cfg.popupDelay > 0 && ! storage( 'get' ) ) {
			window.setTimeout( function () {
				if ( modal.hidden ) {
					openModal();
				}
			}, cfg.popupDelay * 1000 );
		}
	}

	/* ---------- Nút "Nhận báo giá" trên mobile ---------- */
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-bds-open-popup]' );
		if ( ! btn ) {
			return;
		}
		if ( document.getElementById( 'dang-ky' ) ) {
			return; // Có section đăng ký: để trình duyệt cuộn tới.
		}
		e.preventDefault();
		if ( ! openModal() ) {
			var form = document.querySelector( '.bds-form' );
			if ( form ) {
				form.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			}
		}
	} );

	/* ---------- Video: chỉ nạp YouTube khi bấm ---------- */
	document.querySelectorAll( '[data-bds-video]' ).forEach( function ( el ) {
		el.addEventListener( 'click', function () {
			var id = el.getAttribute( 'data-bds-video' );
			if ( ! /^[\w-]{11}$/.test( id ) ) {
				return;
			}
			var iframe = document.createElement( 'iframe' );
			iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
			iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
			iframe.allowFullscreen = true;
			iframe.title = el.getAttribute( 'aria-label' ) || 'Video';
			el.innerHTML = '';
			el.appendChild( iframe );
			el.style.cursor = 'default';
		}, { once: true } );
	} );
} )();
