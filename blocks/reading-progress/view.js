import { resolveTarget, watchArticle } from '../shared/article-progress';

document.querySelectorAll( '.dwp-rx-progress' ).forEach( ( el ) => {
	const target = resolveTarget( el.dataset.dwpRxTarget );
	const bar = el.querySelector( '.dwp-rx-progress__bar' );
	if ( ! target || ! bar ) {
		return; // Geen passend element gevonden: liever niets tonen dan een kapotte balk.
	}
	watchArticle( target, ( progress ) => {
		bar.style.transform = 'scaleX(' + progress + ')';
	} );
} );
