document.querySelectorAll( '[data-rx-toc-open]' ).forEach( ( button ) => {
	button.addEventListener( 'click', () => {
		const target = document.querySelector( button.dataset.rxTocTarget || '#rx-toc' );
		if ( ! target ) {
			return;
		}
		if ( 'open' in target ) {
			target.open = true;
		}
		// Popovers van andere balkonderdelen sluiten, zodat er niet twee dingen open staan.
		document.querySelectorAll( '.rx-share__popover:not([hidden])' ).forEach( ( p ) => ( p.hidden = true ) );
		target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	} );
} );
