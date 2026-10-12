/**
 * Quản lý doanh nghiệp – tìm hàng, dòng hàng của đơn / phiếu kho, tính tiền, ô tiền có dấu chấm.
 */
( function () {
	'use strict';

	var cfg = window.QLDN || {};
	var seq = 0;

	function num( v ) {
		return parseInt( String( v || '' ).replace( /[^\d-]/g, '' ), 10 ) || 0;
	}
	function qty( v ) {
		v = String( v || '' ).trim();
		if ( v.indexOf( ',' ) > -1 ) {
			v = v.replace( /\./g, '' ).replace( ',', '.' );
		}
		return parseFloat( v ) || 0;
	}
	function money( n ) {
		n = Math.round( n || 0 );
		return ( n < 0 ? '-' : '' ) + String( Math.abs( n ) ).replace( /\B(?=(\d{3})+(?!\d))/g, '.' );
	}
	function fmtQty( n ) {
		return String( Math.round( n * 100 ) / 100 ).replace( '.', ',' );
	}
	function esc( s ) {
		var d = document.createElement( 'div' );
		d.textContent = s == null ? '' : String( s );
		return d.innerHTML;
	}
	function ajax( action, q, cb ) {
		var url = cfg.ajax + '?action=' + action + '&nonce=' + encodeURIComponent( cfg.nonce ) + '&q=' + encodeURIComponent( q );
		fetch( url, { credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( r ) { cb( r && r.success ? r.data : [] ); } )
			.catch( function () { cb( [] ); } );
	}

	// Ô tiền: tự thêm dấu chấm hàng nghìn khi gõ.
	document.addEventListener( 'input', function ( e ) {
		var el = e.target;
		if ( ! el.classList || ! el.classList.contains( 'qldn-money' ) ) {
			return;
		}
		var raw = el.value.replace( /[^\d]/g, '' );
		var neg = /^\s*-/.test( el.value );
		el.value = raw === '' ? '' : ( neg ? '-' : '' ) + money( parseInt( raw, 10 ) );
	} );

	/**
	 * Ô tìm kiếm có danh sách gợi ý.
	 */
	function picker( input, action, render, onPick ) {
		var box = input.parentNode.querySelector( '.qldn-results' );
		var timer, items = [], active = 0;

		function show( list ) {
			items = list;
			active = 0;
			if ( ! list.length ) {
				box.innerHTML = input.value ? '<div class="qldn-empty">Không tìm thấy</div>' : '';
				box.classList.toggle( 'open', !! input.value );
				return;
			}
			box.innerHTML = list.map( function ( it, i ) {
				return '<div class="qldn-result' + ( i === 0 ? ' active' : '' ) + '" data-i="' + i + '">' + render( it ) + '</div>';
			} ).join( '' );
			box.classList.add( 'open' );
		}
		function pick( i ) {
			if ( items[ i ] ) {
				onPick( items[ i ] );
				input.value = '';
				box.classList.remove( 'open' );
				box.innerHTML = '';
				items = [];
			}
		}
		function search( then ) {
			ajax( action, input.value.trim(), function ( list ) {
				show( list );
				if ( then ) {
					then( list );
				}
			} );
		}
		input.addEventListener( 'input', function () {
			clearTimeout( timer );
			timer = setTimeout( search, 200 );
		} );
		input.addEventListener( 'focus', function () {
			if ( ! items.length ) {
				search();
			}
		} );
		input.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowDown' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				active = Math.max( 0, Math.min( items.length - 1, active + ( e.key === 'ArrowDown' ? 1 : -1 ) ) );
				box.querySelectorAll( '.qldn-result' ).forEach( function ( el, i ) {
					el.classList.toggle( 'active', i === active );
				} );
			} else if ( e.key === 'Enter' ) {
				e.preventDefault();
				// Máy quét mã vạch gõ rất nhanh rồi Enter: tìm ngay, khớp đúng mã thì thêm luôn.
				clearTimeout( timer );
				var q = input.value.trim().toUpperCase();
				search( function ( list ) {
					var exact = -1;
					list.forEach( function ( it, i ) {
						if ( it.sku && it.sku.toUpperCase() === q ) {
							exact = i;
						}
					} );
					pick( exact > -1 ? exact : ( list.length ? active : -1 ) );
				} );
			} else if ( e.key === 'Escape' ) {
				box.classList.remove( 'open' );
			}
		} );
		box.addEventListener( 'mousedown', function ( e ) {
			var el = e.target.closest( '.qldn-result' );
			if ( el ) {
				e.preventDefault();
				pick( parseInt( el.getAttribute( 'data-i' ), 10 ) );
			}
		} );
		input.addEventListener( 'blur', function () {
			setTimeout( function () { box.classList.remove( 'open' ); }, 150 );
		} );
	}

	/**
	 * Bảng dòng hàng (đơn bán / phiếu nhập / phiếu xuất).
	 */
	function linesForm( form ) {
		var table = form.querySelector( '.qldn-lines' );
		if ( ! table ) {
			return;
		}
		var mode = table.getAttribute( 'data-mode' ); // sale | in | out
		var tbody = table.querySelector( 'tbody' );

		function recalc() {
			var sum = 0;
			tbody.querySelectorAll( 'tr' ).forEach( function ( tr ) {
				var q = qty( tr.querySelector( '.qldn-q' ).value );
				var p = tr.querySelector( '.qldn-p' );
				var stock = parseFloat( tr.getAttribute( 'data-stock' ) );
				var line = p ? q * num( p.value ) : 0;
				sum += line;
				var lt = tr.querySelector( '.qldn-lt' );
				if ( lt ) {
					lt.textContent = money( line );
				}
				tr.classList.toggle( 'qldn-over', mode !== 'in' && ! cfg.allowNegative && q > stock );
			} );
			var s = form.querySelector( '.qldn-sum' );
			if ( s ) {
				s.textContent = money( sum );
			}
			if ( mode === 'sale' ) {
				var discount = Math.min( num( form.querySelector( '.qldn-discount' ).value ), sum );
				var total = sum - discount;
				var paidEl = form.querySelector( '.qldn-paid' );
				var paid = paidEl.value.trim() === '' ? total : num( paidEl.value );
				form.querySelector( '.qldn-subtotal' ).textContent = money( sum );
				form.querySelector( '.qldn-total' ).textContent = money( total );
				form.querySelector( '.qldn-change-label' ).textContent = paid >= total ? 'Tiền thừa trả khách' : 'Khách còn nợ';
				var ch = form.querySelector( '.qldn-change' );
				ch.textContent = money( Math.abs( paid - total ) );
				ch.classList.toggle( 'qldn-neg', paid < total );
			}
		}

		function add( p ) {
			var exist = tbody.querySelector( 'tr[data-id="' + p.id + '"]' );
			if ( exist ) {
				var q = exist.querySelector( '.qldn-q' );
				q.value = fmtQty( qty( q.value ) + 1 );
				recalc();
				q.focus();
				q.select();
				return;
			}
			var i = seq++;
			var tr = document.createElement( 'tr' );
			tr.setAttribute( 'data-id', p.id );
			tr.setAttribute( 'data-stock', p.stock );
			var html = '<td><input type="hidden" name="items[' + i + '][product_id]" value="' + esc( p.id ) + '"><strong>' + esc( p.name ) + '</strong><br><small>' + esc( p.sku ) + ( p.unit ? ' · ' + esc( p.unit ) : '' ) + '</small></td>' +
				'<td class="num">' + esc( fmtQty( p.stock ) ) + '</td>' +
				'<td class="num"><input type="text" inputmode="decimal" class="small-text qldn-q" name="items[' + i + '][qty]" value="1"></td>';
			if ( mode === 'sale' ) {
				html += '<td class="num"><input type="text" inputmode="numeric" class="qldn-money qldn-p" name="items[' + i + '][price]" value="' + money( p.sale_price ) + '"></td><td class="num qldn-lt"></td>';
			} else if ( mode === 'in' ) {
				html += '<td class="num"><input type="text" inputmode="numeric" class="qldn-money qldn-p" name="items[' + i + '][cost]" value="' + money( p.cost_price ) + '"></td><td class="num qldn-lt"></td>';
			}
			html += '<td><button type="button" class="button-link qldn-del" aria-label="Xoá dòng">✕</button></td>';
			tr.innerHTML = html;
			tbody.appendChild( tr );
			recalc();
		}

		var search = form.querySelector( '.qldn-product-search' );
		if ( search ) {
			picker( search, 'qldn_products', function ( p ) {
				return '<strong>' + esc( p.name ) + '</strong> <small>' + esc( p.sku ) + '</small><span>' +
					( mode === 'in' ? 'Tồn ' + esc( fmtQty( p.stock ) ) : money( p.sale_price ) + ' · tồn ' + esc( fmtQty( p.stock ) ) ) + '</span>';
			}, add );
		}

		form.addEventListener( 'input', recalc );
		tbody.addEventListener( 'click', function ( e ) {
			if ( e.target.classList.contains( 'qldn-del' ) ) {
				e.target.closest( 'tr' ).remove();
				recalc();
			}
		} );
		form.addEventListener( 'keydown', function ( e ) {
			// Enter trong ô số lượng / giá không gửi form giữa chừng.
			if ( e.key === 'Enter' && e.target.tagName === 'INPUT' && ! e.target.classList.contains( 'qldn-product-search' ) && form.classList.contains( 'qldn-pos' ) ) {
				e.preventDefault();
			}
		} );
		form.addEventListener( 'submit', function ( e ) {
			if ( ! tbody.querySelector( 'tr' ) ) {
				e.preventDefault();
				window.alert( 'Chưa có hàng hoá nào.' );
				return;
			}
			if ( tbody.querySelector( 'tr.qldn-over' ) ) {
				e.preventDefault();
				window.alert( 'Có mặt hàng vượt số lượng tồn kho (dòng tô đỏ).' );
				return;
			}
			var btn = form.querySelector( '[type=submit]' );
			if ( btn ) {
				btn.disabled = true; // Chống bấm 2 lần tạo 2 đơn.
			}
		} );

		// Khách hàng (trang bán hàng).
		var cs = form.querySelector( '.qldn-customer-search' );
		if ( cs ) {
			var hid = form.querySelector( 'input[name=customer_id]' );
			var chosen = form.querySelector( '.qldn-customer-chosen' );
			var fresh = form.querySelector( '.qldn-customer-new' );
			picker( cs, 'qldn_customers', function ( c ) {
				return '<strong>' + esc( c.name ) + '</strong> <small>' + esc( c.phone ) + '</small><span>' + esc( c.grp ) + ( num( c.debt ) > 0 ? ' · nợ ' + money( c.debt ) : '' ) + '</span>';
			}, function ( c ) {
				hid.value = c.id;
				chosen.querySelector( 'strong' ).textContent = c.name;
				chosen.querySelector( 'span' ).textContent = c.phone + ( c.points > 0 ? ' · ' + c.points + ' điểm' : '' );
				chosen.style.display = '';
				fresh.style.display = 'none';
				cs.style.display = 'none';
			} );
			form.querySelector( '.qldn-customer-clear' ).addEventListener( 'click', function ( e ) {
				e.preventDefault();
				hid.value = 0;
				chosen.style.display = 'none';
				fresh.style.display = '';
				cs.style.display = '';
				cs.focus();
			} );
		}

		if ( form.classList.contains( 'qldn-pos' ) ) {
			document.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'F9' ) {
					e.preventDefault();
					form.requestSubmit ? form.requestSubmit() : form.submit();
				}
			} );
		}
		recalc();
	}

	document.querySelectorAll( '.qldn-lines-form' ).forEach( linesForm );

	// Bảng công: điền X cho các ngày trống (trừ Chủ nhật).
	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.classList.contains( 'qldn-fill' ) ) {
			return;
		}
		e.preventDefault();
		e.target.closest( 'tr' ).querySelectorAll( 'td:not(.qldn-sun) input' ).forEach( function ( el ) {
			if ( el.value.trim() === '' ) {
				el.value = 'X';
			}
		} );
	} );
}() );
