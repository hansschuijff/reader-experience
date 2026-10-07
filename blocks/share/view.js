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

	const copyButton = root.querySelector( '[data-rx-copy]' );
	copyButton?.addEventListener( 'click', async () => {
		try {
			await navigator.clipboard.writeText( url );
			const original = copyButton.textContent;
			copyButton.textContent = copyButton.dataset.rxCopiedLabel || 'Gekopieerd';
			report( root, 'copy' );
			setTimeout( () => {
				copyButton.textContent = original;
			}, 1500 );
		} catch ( error ) {
			// Klembord niet beschikbaar (bijvoorbeeld geen HTTPS): geen verdere actie.
		}
	} );
} );
