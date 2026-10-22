import { buildList, collectEntries, goTo, ready, trackActive } from '../shared/toc';

const small = window.matchMedia( '(max-width: 781px)' );

function init( button ) {
	const dialog = document.getElementById( button.getAttribute( 'aria-controls' ) );
	const list = dialog?.querySelector( '.rx-toc__list' );
	const body = dialog?.querySelector( '.rx-toc-drawer__body' );
	const entries = list
		? collectEntries( {
			maxLevel: Number( dialog.dataset.rxMaxLevel ) || 3,
			minItems: Number( dialog.dataset.rxMinItems ) || 3,
		} )
		: null;

	// Too few headings: the button stays hidden and the panel never exists.
	if ( ! entries ) {
		return;
	}

	buildList( list, entries, '1' === dialog.dataset.rxCollapse );
	button.hidden = false;

	const tracker = trackActive( entries, body );

	// Closing by the back button: on a phone the panel is a layer of its own, so "back" closes the
	// panel and not the page. An entry is added to the history when the panel opens for that.
	let pushed = false;
	let waiting = false;
	let after = null;

	const flush = () => {
		const run = after;
		after = null;
		if ( run ) {
			window.setTimeout( run, 0 );
		}
	};

	const open = () => {
		if ( small.matches ) {
			dialog.showModal();
			window.history.pushState( { rxToc: true }, '' );
			pushed = true;
		} else {
			dialog.show();
		}
		button.setAttribute( 'aria-expanded', 'true' );
		tracker.update();
	};

	dialog.addEventListener( 'close', () => {
		button.setAttribute( 'aria-expanded', 'false' );
		if ( pushed ) {
			pushed = false;
			if ( window.history.state && window.history.state.rxToc ) {
				// Take our own history entry away again; the scroll happens after that, because going
				// back would otherwise restore the earlier scroll position.
				waiting = true;
				window.history.back();
				return;
			}
		}
		flush();
	} );

	window.addEventListener( 'popstate', () => {
		if ( dialog.open && pushed ) {
			pushed = false;
			dialog.close();
		} else if ( waiting ) {
			waiting = false;
			flush();
		}
	} );

	button.addEventListener( 'click', () => {
		if ( dialog.open ) {
			dialog.close();
			return;
		}
		// Panels of other bar items (share, rating) close, so only one thing is open at a time.
		document.querySelectorAll( '[data-rx-panel]:not([hidden])' ).forEach( ( panel ) => ( panel.hidden = true ) );
		open();
	} );

	dialog.querySelector( '[data-rx-toc-close]' )?.addEventListener( 'click', () => dialog.close() );

	// A click on the dimmed area outside the panel closes it (modal on a phone).
	dialog.addEventListener( 'click', ( event ) => {
		if ( event.target === dialog ) {
			dialog.close();
		}
	} );

	entries.forEach( ( entry ) => {
		entry.a.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			if ( small.matches && dialog.open ) {
				after = () => goTo( entry );
				dialog.close();
			} else {
				goTo( entry );
			}
		} );
	} );

	// A rotated phone or a resized window: close, since modal and non-modal are different modes.
	small.addEventListener( 'change', () => {
		if ( dialog.open ) {
			dialog.close();
		}
	} );
}

ready( () => document.querySelectorAll( '[data-rx-toc-open]' ).forEach( init ) );
