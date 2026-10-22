import { resolveTarget } from './article-progress';

/*
 * Shared by the inline table of contents block and the drawer behind the "Contents" button.
 *
 * The list is built in the browser from the headings that are on the page, so it matches what the
 * visitor sees (headings from patterns and other plugins included) and does not depend on heading
 * ids or on the anchor link feature.
 *
 * Every heading gets data-rx-heading="<n>", its position among all h2 to h4 in the content. The
 * list links to that, not to an id. When a text is edited the number stays, so the list still
 * leads to roughly the right place; only a heading that is added or removed shifts the numbers
 * after it. If the heading has an id of its own, the link points to it as well and the address
 * bar gets that hash, so the link can be copied.
 */

const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

/**
 * Text of a heading without what other scripts add to it (the copy-link button and its toast).
 *
 * @param {Element} heading Heading element.
 * @return {string} Plain text.
 */
export function headingText( heading ) {
	const copy = heading.cloneNode( true );
	copy.querySelectorAll( '.rx-heading-link, .rx-heading-link__toast, .screen-reader-text' ).forEach( ( el ) => el.remove() );
	return copy.textContent.replace( /\s+/g, ' ' ).trim();
}

/**
 * Finds the headings, tags them and returns the entries for the list.
 *
 * @param {Object} options          Options.
 * @param {string} options.target   Selector of the content element (with fallbacks).
 * @param {number} options.maxLevel Deepest level to list (2 to 4).
 * @param {number} options.minItems Minimum number of entries; fewer returns null.
 * @return {Object[]|null} Entries { heading, level, text } with relative levels, or null.
 */
export function collectEntries( { target = '.wp-block-post-content', maxLevel = 3, minItems = 3 } = {} ) {
	const container = resolveTarget( target );
	if ( ! container ) {
		return null;
	}

	const headings = Array.from( container.querySelectorAll( 'h2, h3, h4' ) ).filter(
		( heading ) => ! heading.closest( '[data-rx-toc], [data-rx-toc-drawer]' ) && '' !== headingText( heading )
	);
	headings.forEach( ( heading, index ) => heading.setAttribute( 'data-rx-heading', String( index ) ) );

	const entries = headings
		.map( ( heading ) => ( { heading, level: Number( heading.tagName.slice( 1 ) ), text: headingText( heading ) } ) )
		.filter( ( entry ) => entry.level <= maxLevel );

	if ( entries.length < minItems ) {
		return null;
	}

	// Relative levels: the shallowest heading in use is the top level, whatever its tag.
	const top = Math.min( ...entries.map( ( entry ) => entry.level ) );
	entries.forEach( ( entry ) => ( entry.level = entry.level - top + 2 ) );

	return entries;
}

function createLink( entry ) {
	const a = document.createElement( 'a' );
	a.href = entry.heading.id ? '#' + encodeURIComponent( entry.heading.id ) : '#';
	a.textContent = entry.text;
	a.dataset.rxTarget = entry.heading.dataset.rxHeading;
	entry.a = a;
	return a;
}

function createItem( entry ) {
	const li = document.createElement( 'li' );
	li.className = 'rx-toc__item rx-toc__item--l' + entry.level;
	li.appendChild( createLink( entry ) );
	return li;
}

/**
 * Fills the list: flat, or (collapsible) one group per top-level heading.
 *
 * @param {HTMLElement} list    The <ol>.
 * @param {Object[]}    entries Entries from collectEntries().
 * @param {boolean}     group   Whether sub-levels collapse under their top-level heading.
 */
export function buildList( list, entries, group ) {
	list.replaceChildren();

	if ( ! group ) {
		entries.forEach( ( entry ) => list.appendChild( createItem( entry ) ) );
		return;
	}

	list.classList.add( 'rx-toc__list--grouped' );

	const groups = [];
	entries.forEach( ( entry ) => {
		if ( 2 === entry.level || ! groups.length ) {
			groups.push( { entry, children: [] } );
		} else {
			groups[ groups.length - 1 ].children.push( entry );
		}
	} );

	groups.forEach( ( { entry, children } ) => {
		if ( ! children.length ) {
			list.appendChild( createItem( entry ) );
			return;
		}

		const li = document.createElement( 'li' );
		li.className = 'rx-toc__group';
		const details = document.createElement( 'details' );
		details.className = 'rx-toc__group-toggle';
		const summary = document.createElement( 'summary' );
		summary.className = 'rx-toc__item rx-toc__item--l' + entry.level;
		summary.appendChild( createLink( entry ) );
		const sub = document.createElement( 'ul' );
		sub.className = 'rx-toc__sublist';
		children.forEach( ( child ) => sub.appendChild( createItem( child ) ) );
		details.append( summary, sub );
		li.appendChild( details );
		list.appendChild( li );
	} );
}

/**
 * Scrolls to a heading, moves focus there and keeps its own hash in the address bar.
 *
 * @param {Object} entry Entry from collectEntries().
 */
export function goTo( entry ) {
	entry.heading.scrollIntoView( { behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' } );
	entry.heading.setAttribute( 'tabindex', '-1' );
	entry.heading.focus( { preventScroll: true } );
	if ( entry.heading.id ) {
		window.history.replaceState( window.history.state, '', '#' + encodeURIComponent( entry.heading.id ) );
	}
}

/**
 * Marks the entry the visitor is reading (aria-current) and keeps it visible in the list.
 *
 * @param {Object[]}    entries  Entries with their links.
 * @param {HTMLElement} scroller The element that scrolls the list.
 * @return {{update: Function}} Handle; update() recomputes (call it after the list became visible).
 */
export function trackActive( entries, scroller ) {
	let active = null;
	let queued = false;

	const keepVisible = ( link ) => {
		if ( scroller.scrollHeight <= scroller.clientHeight ) {
			return;
		}
		const top = link.offsetTop;
		const bottom = top + link.offsetHeight;
		if ( top < scroller.scrollTop || bottom > scroller.scrollTop + scroller.clientHeight ) {
			scroller.scrollTop = top - scroller.clientHeight / 3;
		}
	};

	const update = () => {
		queued = false;
		const line = window.innerHeight * 0.25;
		const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 5;
		let current = null;
		if ( atBottom ) {
			current = entries[ entries.length - 1 ];
		} else {
			for ( const entry of entries ) {
				if ( entry.heading.getBoundingClientRect().top <= line ) {
					current = entry;
				} else {
					break;
				}
			}
		}
		if ( current === active ) {
			if ( active ) {
				keepVisible( active.a );
			}
			return;
		}
		if ( active ) {
			active.a.removeAttribute( 'aria-current' );
		}
		active = current;
		if ( active ) {
			active.a.setAttribute( 'aria-current', 'location' );
			// If the link sits in a folded group, unfold that group.
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

	return { update };
}

/**
 * Runs a function when the DOM is ready.
 *
 * @param {Function} fn Function.
 */
export function ready( fn ) {
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', fn );
	} else {
		fn();
	}
}
