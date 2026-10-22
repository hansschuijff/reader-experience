/**
 * Hides the bar when scrolling down and shows it again when scrolling up. Sends an event so open
 * panels of child blocks (share, rating) close themselves, without this block having to know
 * their specific classes.
 *
 * Important: while a panel is open, this logic does nothing. On mobile the page often shifts a
 * little when the keyboard opens (to keep the active field visible); without this exception that
 * was wrongly read as "scrolling down", so the bar hid itself and closed the panel someone was
 * typing in.
 */
import { anyPanelOpen } from '../shared/popover';

const isDesktop = window.matchMedia( '(min-width: 782px)' );

document.querySelectorAll( '.dwp-rx-bar' ).forEach( ( bar ) => {
	let lastY = window.scrollY;
	let queued = false;

	const update = () => {
		queued = false;

		if ( anyPanelOpen() ) {
			lastY = window.scrollY;
			return;
		}

		// On desktop the bar is small and centred: it simply stays in view. Only on mobile, where it
		// spans the full width at the bottom, is scrolling away and coming back on scroll up pleasant.
		if ( isDesktop.matches ) {
			bar.classList.remove( 'dwp-rx-bar--hidden' );
			lastY = window.scrollY;
			return;
		}

		const y = window.scrollY;
		const goingDown = y > lastY + 4;
		const goingUp = y < lastY - 4;
		const nearTop = y < 80;

		if ( nearTop || goingUp ) {
			bar.classList.remove( 'dwp-rx-bar--hidden' );
		} else if ( goingDown ) {
			bar.classList.add( 'dwp-rx-bar--hidden' );
			document.dispatchEvent( new CustomEvent( 'dwp-rx:bar-hidden' ) );
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
