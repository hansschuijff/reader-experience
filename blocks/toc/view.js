const small = window.matchMedia( '(max-width: 781px)' );

document.querySelectorAll( '.rx-toc' ).forEach( ( toc ) => {
	const list = toc.querySelector( '.rx-toc__list' );
	const pairs = Array.from( toc.querySelectorAll( '.rx-toc__item a' ) )
		.map( ( a ) => ( { a, heading: document.getElementById( decodeURIComponent( a.hash.slice( 1 ) ) ) } ) )
		.filter( ( pair ) => pair.heading );

	if ( ! list || ! pairs.length ) {
		return;
	}

	// Op smalle schermen start de lijst ingeklapt en klapt na een tik weer in.
	if ( small.matches ) {
		toc.open = false;
	}
	pairs.forEach( ( { a } ) => {
		a.addEventListener( 'click', () => {
			if ( small.matches ) {
				toc.open = false;
			}
		} );
	} );

	let active = null;
	let queued = false;

	const keepVisible = ( link ) => {
		if ( list.scrollHeight <= list.clientHeight ) {
			return;
		}
		const top = link.offsetTop;
		const bottom = top + link.offsetHeight;
		if ( top < list.scrollTop || bottom > list.scrollTop + list.clientHeight ) {
			list.scrollTop = top - list.clientHeight / 3;
		}
	};

	const update = () => {
		queued = false;
		const line = window.innerHeight * 0.25;
		let current = null;
		for ( const pair of pairs ) {
			if ( pair.heading.getBoundingClientRect().top <= line ) {
				current = pair;
			} else {
				break;
			}
		}
		if ( current === active ) {
			return;
		}
		if ( active ) {
			active.a.removeAttribute( 'aria-current' );
		}
		active = current;
		if ( active ) {
			active.a.setAttribute( 'aria-current', 'location' );
			// Als de link in een ingeklapte groep zit, die groep openklappen.
			const group = active.a.closest( '.rx-toc__group-toggle' );
			if ( group && ! group.open ) {
				group.open = true;
			}
			keepVisible( active.a );
		}
	};

	const schedule = () => {
		if ( ! queued ) {
			queued = true;
			window.requestAnimationFrame( update );
		}
	};

	window.addEventListener( 'scroll', schedule, { passive: true } );
	window.addEventListener( 'resize', schedule );
	update();
} );
