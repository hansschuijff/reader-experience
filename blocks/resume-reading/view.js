import { resolveTarget } from '../shared/article-progress';
import { headingText, ready } from '../shared/toc';

/*
 * Remembers, per article, how far the visitor got and which heading they were at, and offers to
 * continue there on a return visit.
 *
 * The position is kept in the visitor's own browser (localStorage) and never sent anywhere. It is
 * forgotten once the article is (almost) finished, after a while, or when the visitor chooses
 * "Start over".
 */

const PREFIX = 'dwp-rx-resume:';
// Stored positions older than this are dropped.
const MAX_AGE = 90 * 24 * 60 * 60 * 1000;
// Below this nothing is remembered (the visitor has hardly started), above MAX_PROGRESS the article counts as read.
const MIN_PROGRESS = 0.1;
const MAX_PROGRESS = 0.95;
// A heading counts as "where the visitor is" once it has passed this fraction of the screen height.
const READING_LINE = 0.3;

const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

/* localStorage can be missing or throw (private mode, blocked storage): then nothing is remembered. */
const storage = {
	read( key ) {
		try {
			return window.localStorage.getItem( key );
		} catch ( e ) {
			return null;
		}
	},
	write( key, value ) {
		try {
			window.localStorage.setItem( key, value );
		} catch ( e ) {
			// Ignore: remembering the position is a convenience.
		}
	},
	remove( key ) {
		try {
			window.localStorage.removeItem( key );
		} catch ( e ) {
			// Ignore.
		}
	},
	keys() {
		try {
			return Object.keys( window.localStorage );
		} catch ( e ) {
			return [];
		}
	},
};

function loadPosition( key ) {
	const raw = storage.read( key );
	if ( ! raw ) {
		return null;
	}
	try {
		const value = JSON.parse( raw );
		const progress = Number( value.p );
		const savedAt = Number( value.d );
		if ( ! Number.isFinite( progress ) || ! Number.isFinite( savedAt ) || Date.now() - savedAt > MAX_AGE ) {
			storage.remove( key );
			return null;
		}
		return {
			progress,
			index: Number.isInteger( value.i ) ? value.i : -1,
			text: 'string' === typeof value.t ? value.t : '',
		};
	} catch ( e ) {
		storage.remove( key );
		return null;
	}
}

/* Housekeeping: positions of other articles that were never finished do not pile up forever. */
function pruneOld() {
	storage.keys().forEach( ( key ) => {
		if ( 0 === key.indexOf( PREFIX ) ) {
			loadPosition( key ); // Removes the entry itself when it is too old or unreadable.
		}
	} );
}

function readingHeadings( container ) {
	return Array.from( container.querySelectorAll( 'h2, h3, h4' ) ).filter(
		( heading ) => ! heading.closest( '[data-dwp-rx-toc], [data-dwp-rx-toc-drawer]' ) && '' !== headingText( heading )
	);
}

/* 0 at the top of the article, 1 when its bottom reaches the bottom of the screen. */
function progressOf( container ) {
	const rect = container.getBoundingClientRect();
	const total = rect.height - window.innerHeight;
	const progress = total > 0 ? -rect.top / total : ( rect.top < 0 ? 1 : 0 );
	return Math.min( 1, Math.max( 0, progress ) );
}

/* The last heading that has passed the reading line, or null before the first one. */
function currentHeading( headings ) {
	let current = null;
	headings.forEach( ( heading, index ) => {
		if ( heading.getBoundingClientRect().top <= window.innerHeight * READING_LINE ) {
			current = { index, text: headingText( heading ) };
		}
	} );
	return current;
}

/* The heading to continue at: by index when its text still matches, otherwise by text. */
function findHeading( headings, position ) {
	if ( position.index < 0 || '' === position.text ) {
		return null;
	}
	if ( headings[ position.index ] && headingText( headings[ position.index ] ) === position.text ) {
		return headings[ position.index ];
	}
	return headings.find( ( heading ) => headingText( heading ) === position.text ) || null;
}

function init( notice ) {
	const container = resolveTarget( notice.dataset.dwpRxTarget );
	const key = PREFIX + notice.dataset.dwpRxPost;
	if ( ! container || ! notice.dataset.dwpRxPost ) {
		return;
	}

	const label = notice.querySelector( '.dwp-rx-resume__label' );
	const title = notice.querySelector( '.dwp-rx-resume__heading' );
	const continueButton = notice.querySelector( '.dwp-rx-resume__continue' );
	const restartButton = notice.querySelector( '.dwp-rx-resume__restart' );
	let timer = null;
	let showing = false;

	/* Remembering: a moment after the visitor stops scrolling, and when they leave the page. */
	let saveTimer = null;
	const save = () => {
		const progress = progressOf( container );
		if ( progress >= MAX_PROGRESS ) {
			storage.remove( key ); // Read to the end.
			return;
		}
		if ( progress < MIN_PROGRESS ) {
			return; // Hardly started: keep what was remembered earlier.
		}
		const heading = currentHeading( readingHeadings( container ) );
		storage.write(
			key,
			JSON.stringify( { p: Math.round( progress * 1000 ) / 1000, i: heading ? heading.index : -1, t: heading ? heading.text : '', d: Date.now() } )
		);
	};
	const scheduleSave = () => {
		window.clearTimeout( saveTimer );
		saveTimer = window.setTimeout( save, 400 );
	};

	const hide = () => {
		window.clearTimeout( timer );
		showing = false;
		notice.classList.remove( 'is-visible' );
		window.setTimeout( () => {
			if ( ! showing ) {
				notice.hidden = true;
			}
		}, 250 );
	};

	const show = ( position, headings ) => {
		const heading = findHeading( headings, position );
		label.textContent = heading ? notice.dataset.dwpRxLabelHeading : notice.dataset.dwpRxLabelPlain;
		title.textContent = heading ? position.text : '';

		// Sit above the interaction bar when there is one.
		const bar = document.querySelector( '.dwp-rx-bar:not(.dwp-rx-bar--hidden)' );
		if ( bar && 'fixed' === window.getComputedStyle( bar ).position ) {
			notice.style.setProperty( '--dwp-rx-resume-bottom', bar.getBoundingClientRect().height + 12 + 'px' );
		}

		showing = true;
		notice.hidden = false;
		// Next frame, so the transition has a starting point.
		window.requestAnimationFrame( () => notice.classList.add( 'is-visible' ) );

		const seconds = Number( notice.dataset.dwpRxSeconds ) || 0;
		if ( seconds > 0 ) {
			timer = window.setTimeout( hide, seconds * 1000 );
			// Someone reading or pointing at the notice gets all the time they need.
			const keep = () => window.clearTimeout( timer );
			notice.addEventListener( 'pointerenter', keep, { once: true } );
			notice.addEventListener( 'focusin', keep, { once: true } );
		}

		continueButton.onclick = () => {
			hide();
			if ( heading ) {
				heading.scrollIntoView( { behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' } );
				heading.setAttribute( 'tabindex', '-1' );
				heading.focus( { preventScroll: true } );
			} else {
				const top = container.getBoundingClientRect().top + window.scrollY;
				const distance = Math.max( 0, container.offsetHeight - window.innerHeight );
				window.scrollTo( { top: top + position.progress * distance, behavior: reduceMotion.matches ? 'auto' : 'smooth' } );
			}
		};
		restartButton.onclick = () => {
			storage.remove( key );
			hide();
		};
	};

	const decide = () => {
		const position = loadPosition( key );
		const navigation = window.performance?.getEntriesByType?.( 'navigation' )?.[ 0 ];
		const restored = navigation && ( 'back_forward' === navigation.type || 'reload' === navigation.type );
		if ( ! position || position.progress < MIN_PROGRESS || position.progress >= MAX_PROGRESS ) {
			return;
		}
		// A link to a part of the article, or the browser putting the visitor back where they were: nothing to offer.
		if ( window.location.hash || restored || progressOf( container ) > position.progress - 0.05 ) {
			return;
		}
		show( position, readingHeadings( container ) );
	};

	// The notice is no longer relevant once the visitor starts reading on their own, or presses Escape.
	window.addEventListener(
		'scroll',
		() => {
			if ( showing && progressOf( container ) > MIN_PROGRESS ) {
				hide();
			}
		},
		{ passive: true }
	);
	notice.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key ) {
			hide();
		}
	} );

	window.addEventListener( 'scroll', scheduleSave, { passive: true } );
	document.addEventListener( 'visibilitychange', () => {
		if ( 'hidden' === document.visibilityState ) {
			save();
		}
	} );
	window.addEventListener( 'pagehide', save );

	// Give the browser a moment to restore its own scroll position (and images to load) first.
	const start = () => window.setTimeout( decide, 400 );
	if ( 'complete' === document.readyState ) {
		start();
	} else {
		window.addEventListener( 'load', start, { once: true } );
	}
}

ready( () => {
	pruneOld();
	document.querySelectorAll( '.dwp-rx-resume' ).forEach( init );
} );
