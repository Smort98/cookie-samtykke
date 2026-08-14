( function () {
	'use strict';

	var data = window.cookieSamtykkeData || {};
	var cookieNavn = data.cookieNavn || 'cookie_samtykke';
	var cookieDage = data.cookieDage || 365;

	function hentCookie( navn ) {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + navn.replace( /[.$?*|{}()[\]\\/+^]/g, '\\$&' ) + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ) : null;
	}

	function saetCookie( navn, vaerdi, dage ) {
		var udloeb = new Date();
		udloeb.setTime( udloeb.getTime() + dage * 24 * 60 * 60 * 1000 );
		document.cookie = navn + '=' + encodeURIComponent( vaerdi ) + ';expires=' + udloeb.toUTCString() + ';path=/;SameSite=Lax';
	}

	function hentSamtykke() {
		var raa = hentCookie( cookieNavn );
		if ( ! raa ) {
			return null;
		}
		try {
			return JSON.parse( raa );
		} catch ( e ) {
			return null;
		}
	}

	function aktiverGatedeScripts( samtykke ) {
		var elementer = document.querySelectorAll( 'script[type="text/plain"][data-cs-kategori]' );
		elementer.forEach( function ( el ) {
			var kategori = el.getAttribute( 'data-cs-kategori' );
			if ( ! samtykke[ kategori ] ) {
				return;
			}
			var nyt = document.createElement( 'script' );
			Array.prototype.forEach.call( el.attributes, function ( attr ) {
				if ( 'type' === attr.name || 'data-cs-kategori' === attr.name ) {
					return;
				}
				if ( 'data-cs-src' === attr.name ) {
					nyt.setAttribute( 'src', attr.value );
					return;
				}
				nyt.setAttribute( attr.name, attr.value );
			} );
			if ( ! nyt.src ) {
				nyt.text = el.text;
			}
			el.parentNode.replaceChild( nyt, el );
		} );
	}

	function dispatchOpdateret( samtykke ) {
		document.dispatchEvent( new CustomEvent( 'cookieSamtykkeOpdateret', { detail: samtykke } ) );
	}

	window.cookieSamtykke = {
		harSamtykke: function ( kategori ) {
			if ( 'nodvendige' === kategori ) {
				return true;
			}
			var samtykke = hentSamtykke();
			return !! ( samtykke && samtykke[ kategori ] );
		},
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		var banner    = document.getElementById( 'cookie-samtykke' );
		if ( ! banner ) {
			return;
		}
		var detaljer  = banner.querySelector( '.cookie-samtykke__detaljer' );
		var knapTilpas = banner.querySelector( '[data-cs-action="tilpas"]' );
		var knapGem    = banner.querySelector( '[data-cs-action="gem"]' );
		var knapAfvis  = banner.querySelector( '[data-cs-action="afvis"]' );
		var knapAccept = banner.querySelector( '[data-cs-action="accepter"]' );
		var checkboxe  = banner.querySelectorAll( '[data-cs-kategori]' );

		function byggSamtykke( alleTrue ) {
			var samtykke = { nodvendige: true };
			checkboxe.forEach( function ( cb ) {
				var kategori = cb.getAttribute( 'data-cs-kategori' );
				samtykke[ kategori ] = alleTrue ? true : cb.checked;
			} );
			return samtykke;
		}

		function gem( samtykke ) {
			saetCookie( cookieNavn, JSON.stringify( samtykke ), cookieDage );
			aktiverGatedeScripts( samtykke );
			dispatchOpdateret( samtykke );
			banner.setAttribute( 'hidden', '' );
		}

		function visBanner() {
			var eksisterende = hentSamtykke();
			if ( eksisterende ) {
				checkboxe.forEach( function ( cb ) {
					var kategori = cb.getAttribute( 'data-cs-kategori' );
					cb.checked = !! eksisterende[ kategori ];
				} );
			}
			banner.removeAttribute( 'hidden' );
		}

		knapTilpas.addEventListener( 'click', function () {
			detaljer.removeAttribute( 'hidden' );
			knapTilpas.setAttribute( 'hidden', '' );
			knapGem.removeAttribute( 'hidden' );
		} );

		knapGem.addEventListener( 'click', function () {
			gem( byggSamtykke( false ) );
		} );

		knapAfvis.addEventListener( 'click', function () {
			gem( byggSamtykke( false ) );
		} );

		knapAccept.addEventListener( 'click', function () {
			gem( byggSamtykke( true ) );
		} );

		document.addEventListener( 'click', function ( e ) {
			var link = e.target.closest && e.target.closest( '[data-cs-genaabn]' );
			if ( ! link ) {
				return;
			}
			e.preventDefault();
			visBanner();
		} );

		var eksisterende = hentSamtykke();
		if ( eksisterende ) {
			aktiverGatedeScripts( eksisterende );
		} else {
			visBanner();
		}
	} );
} )();
