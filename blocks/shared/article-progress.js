/**
 * Vindt het element waarvan de leesvoortgang gevolgd moet worden. Probeert eerst
 * de opgegeven selector; valt daarna terug op een paar veelgebruikte content-
 * containers, zodat dit ook buiten een FSE-thema (waar .wp-block-post-content
 * niet bestaat) iets vindt. Geeft null terug als niets past.
 */
export function resolveTarget( preferred ) {
	const candidates = [ preferred, '.wp-block-post-content', '.entry-content', 'article', 'main' ].filter(
		Boolean
	);
	for ( const selector of candidates ) {
		const el = document.querySelector( selector );
		if ( el ) {
			return el;
		}
	}
	return null;
}

/**
 * Volgt hoe ver de bezoeker in een element is gescrold (0 tot 1).
 * 0 = bovenkant van het element op de bovenkant van het scherm,
 * 1 = onderkant van het element op de onderkant van het scherm.
 */
export function watchArticle( target, callback ) {
	let queued = false;

	const compute = () => {
		queued = false;
		const rect = target.getBoundingClientRect();
		const total = rect.height - window.innerHeight;
		let progress;
		if ( total > 0 ) {
			progress = -rect.top / total;
		} else {
			progress = rect.top < 0 ? 1 : 0;
		}
		callback( Math.min( 1, Math.max( 0, progress ) ) );
	};

	const schedule = () => {
		if ( ! queued ) {
			queued = true;
			window.requestAnimationFrame( compute );
		}
	};

	window.addEventListener( 'scroll', schedule, { passive: true } );
	window.addEventListener( 'resize', schedule );
	if ( 'ResizeObserver' in window ) {
		// Afbeeldingen die later laden veranderen de hoogte van het artikel.
		new ResizeObserver( schedule ).observe( target );
	}
	compute();
}
