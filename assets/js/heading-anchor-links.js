/**
 * Voegt bij koppen (h2–h4) met een id een klein "kopieer link"-icoontje toe.
 *
 * Bewust losse, ongebundelde JS: dit draait op elk bericht waarvoor het aan staat, ongeacht
 * welke blokken er gebruikt worden, en heeft dus geen eigen block.json.
 *
 * Opties komen uit window.rxHeadingAnchors (gezet door de plugin, zie HeadingAnchors::config()):
 *   position   'before' (standaard) of 'after': voor of achter de koptekst
 *   container  selector of lijst selectors; de eerste die bestaat is het artikel
 *   labels     { copy, copied }: al vertaald door PHP
 */
( function () {
	var config = window.rxHeadingAnchors || {};
	var labels = config.labels || {};
	var copyLabel = labels.copy || 'Copy link to this section';
	var copiedLabel = labels.copied || 'Link copied';
	var position = 'after' === config.position ? 'after' : 'before';

	var candidates =
		config.container && config.container.length
			? [].concat( config.container )
			: [ '.wp-block-post-content', '.entry-content', 'article' ];

	// Zelfde "kopieer naar klembord"-icoon als elders in de plugin (twee overlappende
	// rechthoeken): dat maakt meteen duidelijk wat de knop doet.
	var ICON =
		'<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';

	var container = null;
	for ( var i = 0; i < candidates.length && ! container; i++ ) {
		try {
			container = document.querySelector( candidates[ i ] );
		} catch ( e ) {
			// Ongeldige selector uit een filter: overslaan, de volgende proberen.
		}
	}
	if ( ! container ) {
		return;
	}

	container.querySelectorAll( 'h2[id], h3[id], h4[id]' ).forEach( function ( heading ) {
		if ( heading.querySelector( '.rx-heading-link' ) ) {
			return;
		}

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'rx-heading-link rx-heading-link--' + position;
		button.innerHTML = ICON;
		button.setAttribute( 'aria-label', copyLabel );
		button.setAttribute( 'title', copyLabel );

		// Pas gevuld op het moment van kopiëren: een permanent aanwezige tekst zou
		// ongezien in de kop meelezen voor schermlezers en bij het selecteren van de kop.
		var toast = document.createElement( 'span' );
		toast.className = 'rx-heading-link__toast';
		toast.setAttribute( 'role', 'status' );
		button.appendChild( toast );

		var hideTimer = null;

		button.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var url = window.location.href.split( '#' )[ 0 ] + '#' + heading.id;

			if ( ! navigator.clipboard ) {
				return; // Geen klembord (bijvoorbeeld geen HTTPS): geen verdere actie.
			}

			navigator.clipboard.writeText( url ).then(
				function () {
					toast.textContent = copiedLabel;
					toast.classList.add( 'is-visible' );
					clearTimeout( hideTimer );
					hideTimer = setTimeout( function () {
						toast.classList.remove( 'is-visible' );
						setTimeout( function () {
							if ( ! toast.classList.contains( 'is-visible' ) ) {
								toast.textContent = '';
							}
						}, 200 );
					}, 1500 );
				},
				function () {
					// Klembord geweigerd: geen verdere actie.
				}
			);
		} );

		if ( 'after' === position ) {
			heading.appendChild( button );
		} else {
			heading.insertBefore( button, heading.firstChild );
		}
	} );
} )();
