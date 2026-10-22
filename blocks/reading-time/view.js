import { resolveTarget, watchArticle } from '../shared/article-progress';

document.querySelectorAll( '[data-dwp-rx-remaining]' ).forEach( ( el ) => {
	const target = resolveTarget( el.dataset.dwpRxTarget );
	const label = el.querySelector( '.dwp-rx-rt__label' );
	if ( ! target || ! label ) {
		return;
	}

	const total = Number( el.dataset.dwpRxMinutes ) || 1;
	const remainingText = el.dataset.dwpRxLabelRemaining || 'nog ~%d min';
	const doneText = el.dataset.dwpRxLabelDone || '';

	watchArticle( target, ( progress ) => {
		const left = Math.ceil( total * ( 1 - progress ) );
		const text = left <= 0 ? doneText : remainingText.replace( '%d', String( left ) );
		if ( label.textContent !== text ) {
			label.textContent = text;
		}
	} );
} );
