/**
 * Adds a small "copy link" icon to headings (h2-h4) that have an id.
 *
 * Deliberately plain, unbundled JS: it runs on every post it is enabled for, whatever blocks are
 * used, and so has no block.json of its own.
 *
 * Options come from window.dwpRxHeadingAnchors (set by the plugin, see HeadingAnchors::config()):
 *   position   'before' (default) or 'after': before or after the heading text
 *   container  selector or list of selectors; the first one that exists is the article
 *   labels     { copy, copied }: already translated by PHP
 */
( function () {
	var config = window.dwpRxHeadingAnchors || {};
	var labels = config.labels || {};
	var copyLabel = labels.copy || 'Copy link to this section';
	var copiedLabel = labels.copied || 'Link copied';
	var position = 'after' === config.position ? 'after' : 'before';

	var candidates =
		config.container && config.container.length
			? [].concat( config.container )
			: [ '.wp-block-post-content', '.entry-content', 'article' ];

	// Same "copy to clipboard" icon as elsewhere in the plugin (two overlapping rectangles):
	// it makes clear at once what the button does.
	var ICON =
		'<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';

	var container = null;
	for ( var i = 0; i < candidates.length && ! container; i++ ) {
		try {
			container = document.querySelector( candidates[ i ] );
		} catch ( e ) {
			// Invalid selector from a filter: skip it, try the next one.
		}
	}
	if ( ! container ) {
		return;
	}

	container.querySelectorAll( 'h2[id], h3[id], h4[id]' ).forEach( function ( heading ) {
		if ( heading.querySelector( '.dwp-rx-heading-link' ) ) {
			return;
		}

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'dwp-rx-heading-link dwp-rx-heading-link--' + position;
		button.innerHTML = ICON;
		button.setAttribute( 'aria-label', copyLabel );
		button.setAttribute( 'title', copyLabel );

		// Only filled at the moment of copying: a permanently present text would be read along
		// unseen in the heading by screen readers and when the heading is selected.
		var toast = document.createElement( 'span' );
		toast.className = 'dwp-rx-heading-link__toast';
		toast.setAttribute( 'role', 'status' );
		button.appendChild( toast );

		var hideTimer = null;

		button.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var url = window.location.href.split( '#' )[ 0 ] + '#' + heading.id;

			if ( ! navigator.clipboard ) {
				return; // No clipboard (for example no HTTPS): nothing further to do.
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
					// Clipboard refused: nothing further to do.
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
