import { buildList, collectEntries, goTo, ready, trackActive } from '../shared/toc';

const small = window.matchMedia( '(max-width: 781px)' );
// Wide enough to give up some width to the panel and still leave the article a comfortable column.
const wide = window.matchMedia( '(min-width: 1100px)' );
const root = document.documentElement;

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

	// The page moves aside on a wide screen: the panel's width becomes padding on <html>, so the
	// theme's own layout (header, content, footer) just gets a narrower viewport to fill.
	const side = dialog.classList.contains( 'rx-toc-drawer--right' ) ? 'end' : 'start';
	const pushes = () => 'never' !== dialog.dataset.rxPush && wide.matches && ! small.matches;
	const releasePage = () => {
		root.classList.remove( 'rx-toc-pushed' );
		root.style.removeProperty( '--rx-toc-push-start' );
		root.style.removeProperty( '--rx-toc-push-end' );
	};
	const pushPage = () => {
		if ( ! pushes() ) {
			return;
		}
		root.style.setProperty( '--rx-toc-push-' + side, dialog.getBoundingClientRect().width + 'px' );
		root.classList.add( 'rx-toc-pushed' );
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
		pushPage();
		tracker.update();
	};

	dialog.addEventListener( 'close', () => {
		button.setAttribute( 'aria-expanded', 'false' );
		releasePage();
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

	// Window resized while the panel is open: move aside or float, whichever fits now.
	wide.addEventListener( 'change', () => {
		if ( dialog.open && ! small.matches ) {
			releasePage();
			pushPage();
		}
	} );

	// A rotated phone or a resized window: close, since modal and non-modal are different modes.
	small.addEventListener( 'change', () => {
		if ( dialog.open ) {
			dialog.close();
		}
	} );
}

ready( () => document.querySelectorAll( '[data-rx-toc-open]' ).forEach( init ) );
