/**
 * Voegt bij het overvaren van een kop (h2–h4) met een id een klein "kopieer link"-
 * icoontje toe. Bewust losse, ongebundelde JS: dit draait op elk singular bericht,
 * ongeacht welke blokken er gebruikt worden, en heeft dus geen eigen block.json.
 */
( function () {
	// Zelfde "kopieer naar klembord"-icoon als elders in de plugin (twee overlappende
	// rechthoeken), in plaats van een generiek kettingschakel-icoon: dat maakt
	// meteen duidelijk wat de knop doet, in plaats van te suggereren dat het een
	// gewone link is.
	var ICON =
		'<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';

	var containers = document.querySelectorAll( '.wp-block-post-content, .entry-content, article' );
	var container = containers[ 0 ];
	if ( ! container ) {
		return;
	}

	container.querySelectorAll( 'h2[id], h3[id], h4[id]' ).forEach( function ( heading ) {
		if ( heading.querySelector( '.rx-heading-link' ) ) {
			return;
		}

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'rx-heading-link';
		button.innerHTML = ICON;
		button.setAttribute( 'aria-label', 'Kopieer link naar dit onderdeel' );

		var toast = document.createElement( 'span' );
		toast.className = 'rx-heading-link__toast';
		toast.textContent = 'Link gekopieerd';
		toast.setAttribute( 'aria-live', 'polite' );
		button.appendChild( toast );

		var hideTimer = null;

		button.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var url = window.location.href.split( '#' )[ 0 ] + '#' + heading.id;
			navigator.clipboard?.writeText( url ).then(
				function () {
					// Zichtbaar voor iedereen, niet alleen via een aria-label dat een
					// ziende gebruiker nooit te zien krijgt.
					toast.classList.add( 'is-visible' );
					clearTimeout( hideTimer );
					hideTimer = setTimeout( function () {
						toast.classList.remove( 'is-visible' );
					}, 1500 );
				},
				function () {
					// Klembord niet beschikbaar: geen verdere actie.
				}
			);
		} );

		heading.appendChild( button );
	} );
} )();
