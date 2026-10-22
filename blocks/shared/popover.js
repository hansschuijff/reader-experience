/**
 * Shared open/close logic for "fixed, centred" panels (the share menu, the rating panel). Fixed
 * and centred instead of anchored above the button, so no part of the panel can ever fall off
 * screen.
 *
 * Every panel element gets the attribute [data-dwp-rx-panel]. Only one is open at a time.
 */

function allPanels() {
	return Array.from( document.querySelectorAll( '[data-dwp-rx-panel]' ) );
}

export function anyPanelOpen() {
	return allPanels().some( ( panel ) => ! panel.hidden );
}

export function closeAllPanels( except = null ) {
	allPanels().forEach( ( panel ) => {
		if ( panel === except || panel.hidden ) {
			return;
		}
		panel.hidden = true;
		const trigger = document.querySelector( `[aria-controls="${ panel.id }"]` );
		trigger?.setAttribute( 'aria-expanded', 'false' );
	} );
}

export function openPanel( panel ) {
	closeAllPanels( panel );
	panel.hidden = false;
	const trigger = document.querySelector( `[aria-controls="${ panel.id }"]` );
	trigger?.setAttribute( 'aria-expanded', 'true' );
}

export function togglePanel( panel ) {
	if ( panel.hidden ) {
		openPanel( panel );
	} else {
		closeAllPanels();
	}
}

let wired = false;

/** Once only: a click outside a panel and Escape close everything. */
export function wireGlobalPopoverBehaviour() {
	if ( wired ) {
		return;
	}
	wired = true;

	document.addEventListener( 'click', ( event ) => {
		// Best effort, not verified on a real device: with gesture navigation on Android a back swipe
		// from the bottom edge can arrive as a plain tap on the page before the OS handles it as a
		// system gesture. A tap right at the bottom edge is therefore ignored for closing a panel;
		// nothing clickable of ours sits there outside the bar and the panel itself.
		if ( event.clientY >= window.innerHeight - 24 ) {
			return;
		}

		allPanels().forEach( ( panel ) => {
			if ( panel.hidden ) {
				return;
			}
			const root = panel.closest( '[data-dwp-rx-share], [data-dwp-rx-rating]' ) || panel;
			if ( ! root.contains( event.target ) ) {
				closeAllPanels();
			}
		} );
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key ) {
			closeAllPanels();
		}
	} );
}
