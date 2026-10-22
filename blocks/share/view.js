import { togglePanel, wireGlobalPopoverBehaviour } from '../shared/popover';

wireGlobalPopoverBehaviour();

function report( root, channel ) {
	const url = root.dataset.rxRest;
	if ( ! url || ! ( 'sendBeacon' in navigator ) ) {
		return;
	}
	const body = new Blob(
		[ JSON.stringify( { post_id: Number( root.dataset.rxPost ), channel } ) ],
		{ type: 'application/json' }
	);
	navigator.sendBeacon( url, body );
}

document.querySelectorAll( '[data-rx-share]' ).forEach( ( root ) => {
	const trigger = root.querySelector( '.rx-share__trigger' );
	const popover = root.querySelector( '[data-rx-panel]' );
	const url = root.dataset.rxUrl || '';
	const title = root.dataset.rxTitle || '';

	trigger?.addEventListener( 'click', async () => {
		if ( 'share' in navigator && matchMedia( '(max-width: 781px)' ).matches ) {
			try {
				await navigator.share( { title, url } );
			} catch ( error ) {
				// Geannuleerd door de bezoeker: geen verdere actie nodig.
			}
			return;
		}
		togglePanel( popover );
	} );

	root.querySelectorAll( '[data-rx-channel]' ).forEach( ( link ) => {
		link.addEventListener( 'click', () => report( root, link.dataset.rxChannel ) );
	} );

	// Selecting the whole link on focus makes a manual copy one keystroke.
	root.querySelector( '.rx-share__url' )?.addEventListener( 'focus', ( event ) => event.target.select() );

	const copyButton = root.querySelector( '[data-rx-copy]' );
	const copyLabel = copyButton?.querySelector( '[data-rx-copy-label]' );
	copyButton?.addEventListener( 'click', async () => {
		try {
			await navigator.clipboard.writeText( url );
			// Only the label changes, so the icon (and the start state) stay intact.
			const original = copyLabel.textContent;
			copyLabel.textContent = copyButton.dataset.rxCopiedLabel || 'Copied';
			report( root, 'copy' );
			setTimeout( () => {
				copyLabel.textContent = original;
			}, 1500 );
		} catch ( error ) {
			// Klembord niet beschikbaar (bijvoorbeeld geen HTTPS): geen verdere actie.
		}
	} );
} );
