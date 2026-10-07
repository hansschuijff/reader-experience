import { resolveTarget, watchArticle } from '../shared/article-progress';

document.querySelectorAll( '.rx-progress' ).forEach( ( el ) => {
	const target = resolveTarget( el.dataset.rxTarget );
	const bar = el.querySelector( '.rx-progress__bar' );
	if ( ! target || ! bar ) {
		return; // Geen passend element gevonden: liever niets tonen dan een kapotte balk.
	}
	watchArticle( target, ( progress ) => {
		bar.style.transform = 'scaleX(' + progress + ')';
	} );
} );
