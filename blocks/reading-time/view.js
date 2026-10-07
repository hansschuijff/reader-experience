import { resolveTarget, watchArticle } from '../shared/article-progress';

document.querySelectorAll( '[data-rx-remaining]' ).forEach( ( el ) => {
	const target = resolveTarget( el.dataset.rxTarget );
	const label = el.querySelector( '.rx-rt__label' );
	if ( ! target || ! label ) {
		return;
	}

	const total = Number( el.dataset.rxMinutes ) || 1;
	const remainingText = el.dataset.rxLabelRemaining || 'nog ~%d min';
	const doneText = el.dataset.rxLabelDone || '';

	watchArticle( target, ( progress ) => {
		const left = Math.ceil( total * ( 1 - progress ) );
		const text = left <= 0 ? doneText : remainingText.replace( '%d', String( left ) );
		if ( label.textContent !== text ) {
			label.textContent = text;
		}
	} );
} );
