/**
 * Finds the element whose reading progress is to be tracked. Tries the given selector first, then
 * falls back on a few common content containers, so it also finds something outside a block theme
 * (where .wp-block-post-content does not exist). Returns null when nothing fits.
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
 * Tracks how far the visitor has scrolled through an element (0 to 1).
 * 0 = top of the element at the top of the screen,
 * 1 = bottom of the element at the bottom of the screen.
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
		// Images that load later change the height of the article.
		new ResizeObserver( schedule ).observe( target );
	}
	compute();
}
