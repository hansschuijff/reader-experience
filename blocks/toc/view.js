import { resolveTarget } from '../shared/article-progress';

/*
 * The table of contents is built here, from the headings that are on the page, and not on the
 * server from the post content. That keeps the list in step with what the visitor sees (headings
 * from patterns and other plugins included) and makes it independent of heading ids and of the
 * anchor link feature.
 *
 * Every heading gets data-rx-heading="<n>", its position among all h2 to h4 in the content. The
 * list links to that, not to an id. When a text is edited the number stays, so the list still
 * leads to roughly the right place; only a heading that is added or removed shifts the numbers
 * after it. If the heading has an id of its own, the link points to it as well and the address
 * bar gets that hash, so the link can be copied.
 */

const small = window.matchMedia( '(max-width: 781px)' );
const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

/**
 * Text of a heading without what other scripts add to it (the copy-link button and its toast).
 *
 * @param {Element} heading Heading element.
 * @return {string} Plain text.
 */
function headingText( heading ) {
	const copy = heading.cloneNode( true );
	copy.querySelectorAll( '.rx-heading-link, .rx-heading-link__toast, .screen-reader-text' ).forEach( ( el ) => el.remove() );
	return copy.textContent.replace( /\s+/g, ' ' ).trim();
}

/**
 * Creates the link of one entry.
 *
 * @param {Object} item Entry: { heading, text }.
 * @return {HTMLAnchorElement} Link.
 */
function createLink( item ) {
	const a = document.createElement( 'a' );
	a.href = item.heading.id ? '#' + encodeURIComponent( item.heading.id ) : '#';
	a.textContent = item.text;
	a.dataset.rxTarget = item.heading.dataset.rxHeading;
	item.a = a;
	return a;
}

function createItem( item ) {
	const el = document.createElement( 'li' );
	el.className = 'rx-toc__item rx-toc__item--l' + item.level;
	el.appendChild( createLink( item ) );
	return el;
}

/**
 * Fills the list: flat, or (collapsible) one group per top-level heading.
 *
 * @param {HTMLElement} list  The <ol>.
 * @param {Object[]}    items Entries with a relative level (2 = top level).
 * @param {boolean}     group Whether sub-levels collapse under their top-level heading.
 */
function build( list, items, group ) {
	if ( ! group ) {
		items.forEach( ( item ) => list.appendChild( createItem( item ) ) );
		return;
	}

	list.classList.add( 'rx-toc__list--grouped' );

	const groups = [];
	items.forEach( ( item ) => {
		if ( 2 === item.level || ! groups.length ) {
			groups.push( { item, children: [] } );
		} else {
			groups[ groups.length - 1 ].children.push( item );
		}
	} );

	groups.forEach( ( { item, children } ) => {
		if ( ! children.length ) {
			list.appendChild( createItem( item ) );
			return;
		}

		const li = document.createElement( 'li' );
		li.className = 'rx-toc__group';
		const details = document.createElement( 'details' );
		details.className = 'rx-toc__group-toggle';
		const summary = document.createElement( 'summary' );
		summary.className = 'rx-toc__item rx-toc__item--l' + item.level;
		summary.appendChild( createLink( item ) );
		const sub = document.createElement( 'ul' );
		sub.className = 'rx-toc__sublist';
		children.forEach( ( child ) => sub.appendChild( createItem( child ) ) );
		details.append( summary, sub );
		li.appendChild( details );
		list.appendChild( li );
	} );
}

function init( toc ) {
	const list = toc.querySelector( '.rx-toc__list' );
	const container = resolveTarget( toc.dataset.rxTarget || '.wp-block-post-content' );
	if ( ! list || ! container ) {
		return false;
	}

	const maxLevel = Number( toc.dataset.rxMaxLevel ) || 3;
	const minItems = Number( toc.dataset.rxMinItems ) || 3;

	const headings = Array.from( container.querySelectorAll( 'h2, h3, h4' ) ).filter(
		( heading ) => ! toc.contains( heading ) && '' !== headingText( heading )
	);
	headings.forEach( ( heading, index ) => heading.setAttribute( 'data-rx-heading', String( index ) ) );

	const entries = headings
		.map( ( heading ) => ( { heading, level: Number( heading.tagName.slice( 1 ) ), text: headingText( heading ) } ) )
		.filter( ( entry ) => entry.level <= maxLevel );

	if ( entries.length < minItems ) {
		return false;
	}

	// Relative levels: the shallowest heading in use is the top level, whatever its tag.
	const top = Math.min( ...entries.map( ( entry ) => entry.level ) );
	entries.forEach( ( entry ) => ( entry.level = entry.level - top + 2 ) );

	build( list, entries, '1' === toc.dataset.rxCollapse );
	toc.hidden = false;

	// On narrow screens the list starts folded and folds again after a tap.
	if ( small.matches ) {
		toc.open = false;
	}

	entries.forEach( ( entry ) => {
		entry.a.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			entry.heading.scrollIntoView( { behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' } );
			entry.heading.setAttribute( 'tabindex', '-1' );
			entry.heading.focus( { preventScroll: true } );
			if ( entry.heading.id ) {
				window.history.replaceState( null, '', '#' + encodeURIComponent( entry.heading.id ) );
			}
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
	return true;
}

function run() {
	let any = false;
	document.querySelectorAll( '[data-rx-toc]' ).forEach( ( toc ) => {
		any = init( toc ) || any;
	} );
	// Lets CSS hide the "Contents" button in the bar when there is no table of contents to open.
	document.documentElement.dataset.rxToc = any ? 'ready' : 'none';
}

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', run );
} else {
	run();
}
