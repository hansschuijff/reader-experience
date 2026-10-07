/**
 * Gedeelde open/sluit-logica voor "vaste, gecentreerde" paneeltjes (het deelmenu,
 * het waarderingspaneel). Vast en gecentreerd in plaats van boven de knop verankerd,
 * zodat er nooit een deel van het paneel buiten het scherm kan vallen.
 *
 * Elk paneel-element krijgt het attribuut [data-rx-panel]. Er is er telkens maar
 * één tegelijk open.
 */

function allPanels() {
	return Array.from( document.querySelectorAll( '[data-rx-panel]' ) );
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

/** Eenmalig: klik buiten een paneel en Escape sluiten alles. */
export function wireGlobalPopoverBehaviour() {
	if ( wired ) {
		return;
	}
	wired = true;

	document.addEventListener( 'click', ( event ) => {
		// Beste poging, niet geverifieerd op een echt toestel: bij gebaarnavigatie
		// op Android kan een terugveeg vanaf de onderrand soms als een gewone tik
		// op de pagina binnenkomen vóórdat het OS 'm als systeemgebaar afhandelt.
		// Een tik vlak bij de onderrand negeren we daarom voor het sluiten van een
		// paneel; daar staat toch niets klikbaars van ons buiten de balk/het paneel zelf.
		if ( event.clientY >= window.innerHeight - 24 ) {
			return;
		}

		allPanels().forEach( ( panel ) => {
			if ( panel.hidden ) {
				return;
			}
			const root = panel.closest( '[data-rx-share], [data-rx-rating]' ) || panel;
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
