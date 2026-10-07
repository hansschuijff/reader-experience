/**
 * Verbergt de balk bij omlaag scrollen, toont hem weer bij omhoog scrollen.
 * Stuurt een event zodat open paneeltjes van kindblokken (delen, waardering)
 * zichzelf sluiten, zonder dat dit blok hun specifieke classes hoeft te kennen.
 *
 * Belangrijk: zolang er een paneel open staat, doet deze logica niets. Op mobiel
 * verschuift de pagina vaak even wanneer het toetsenbord opent (om het actieve
 * veld zichtbaar te houden); zonder deze uitzondering werd dat ten onrechte als
 * "naar beneden scrollen" gelezen, waardoor de balk zichzelf verborg en daarbij
 * het paneel sloot waar iemand net in aan het typen was.
 */
import { anyPanelOpen } from '../shared/popover';

const isDesktop = window.matchMedia( '(min-width: 782px)' );

document.querySelectorAll( '.rx-bar' ).forEach( ( bar ) => {
	let lastY = window.scrollY;
	let queued = false;

	const update = () => {
		queued = false;

		if ( anyPanelOpen() ) {
			lastY = window.scrollY;
			return;
		}

		// Op desktop is de balk klein en gecentreerd: die blijft gewoon altijd in beeld.
		// Alleen op mobiel, waar hij de volle breedte onderaan beslaat, is wegscrollen
		// en teruggkomen bij omhoog scrollen prettig.
		if ( isDesktop.matches ) {
			bar.classList.remove( 'rx-bar--hidden' );
			lastY = window.scrollY;
			return;
		}

		const y = window.scrollY;
		const goingDown = y > lastY + 4;
		const goingUp = y < lastY - 4;
		const nearTop = y < 80;

		if ( nearTop || goingUp ) {
			bar.classList.remove( 'rx-bar--hidden' );
		} else if ( goingDown ) {
			bar.classList.add( 'rx-bar--hidden' );
			document.dispatchEvent( new CustomEvent( 'rx:bar-hidden' ) );
		}
		lastY = y;
	};

	window.addEventListener(
		'scroll',
		() => {
			if ( ! queued ) {
				queued = true;
				window.requestAnimationFrame( update );
			}
		},
		{ passive: true }
	);
} );
