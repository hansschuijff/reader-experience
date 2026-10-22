import { buildList, collectEntries, goTo, ready, trackActive } from '../shared/toc';

const small = window.matchMedia( '(max-width: 781px)' );

function init( toc ) {
	const list = toc.querySelector( '.rx-toc__list' );
	const entries = list
		? collectEntries( {
			target: toc.dataset.rxTarget || '.wp-block-post-content',
			maxLevel: Number( toc.dataset.rxMaxLevel ) || 3,
			minItems: Number( toc.dataset.rxMinItems ) || 3,
		} )
		: null;

	// The block stays hidden when the page has too few headings.
	if ( ! entries ) {
		return;
	}

	buildList( list, entries, '1' === toc.dataset.rxCollapse );
	toc.hidden = false;

	// On narrow screens the list starts folded and folds again after a tap.
	if ( small.matches ) {
		toc.open = false;
	}

	entries.forEach( ( entry ) => {
		entry.a.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			goTo( entry );
			if ( small.matches ) {
				toc.open = false;
			}
		} );
	} );

	trackActive( entries, list );
}

ready( () => document.querySelectorAll( '[data-rx-toc]' ).forEach( init ) );
