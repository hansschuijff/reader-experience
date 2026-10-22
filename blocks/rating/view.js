import { openPanel, closeAllPanels, wireGlobalPopoverBehaviour } from '../shared/popover';

wireGlobalPopoverBehaviour();

function storageKey( postId ) {
	return 'dwp-rx-rated-' + postId;
}

function showStep( panel, name ) {
	panel.querySelectorAll( '[data-dwp-rx-step]' ).forEach( ( el ) => {
		el.hidden = el.dataset.dwpRxStep !== name;
	} );
}

async function postJson( url, body ) {
	try {
		const res = await fetch( url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( body ),
		} );
		if ( ! res.ok ) {
			return null;
		}
		const text = await res.text();
		return text ? JSON.parse( text ) : {};
	} catch ( error ) {
		return null;
	}
}

function isValidEmail( value ) {
	// A light check, not full RFC validation: only meant to catch the obvious typo, not to
	// block anything.
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value );
}

document.querySelectorAll( '[data-dwp-rx-rating]' ).forEach( ( root ) => {
	const postId = root.dataset.dwpRxPost;
	const isBar = root.classList.contains( 'dwp-rx-rating--bar' );
	const panel = root.querySelector( '[data-dwp-rx-panel]' );
	const thumbsWrap = root.querySelector( '[data-dwp-rx-step="thumbs"]' );
	const questionEl = panel.querySelector( '[data-dwp-rx-step-question]' );
	const textarea = panel.querySelector( '.dwp-rx-rating__text' );

	// In the bar the panel gets an id of its own, so the shared popover module can manage it as a
	// "fixed, centred" panel.
	if ( isBar ) {
		panel.id = panel.id || 'dwp-rx-rating-panel-' + postId + '-' + Math.random().toString( 36 ).slice( 2, 7 );
	}

	const openOrShow = () => {
		if ( isBar ) {
			openPanel( panel );
		} else {
			panel.hidden = false;
		}
	};

	let votedValue = localStorage.getItem( storageKey( postId ) );
	if ( votedValue ) {
		thumbsWrap.querySelectorAll( '[data-dwp-rx-value]' ).forEach( ( btn ) => {
			btn.setAttribute( 'aria-pressed', String( btn.dataset.dwpRxValue === votedValue ) );
		} );
	}

	let current = { id: null, token: null, value: null };

	// No "lock" after a choice: clicking the same thumb again does nothing, clicking the other
	// thumb simply changes the vote (a new, separate rating; there is no account to link an earlier
	// one to anyway).
	thumbsWrap.querySelectorAll( '[data-dwp-rx-value]' ).forEach( ( button ) => {
		button.addEventListener( 'click', async () => {
			const value = button.dataset.dwpRxValue;
			if ( value === votedValue ) {
				return;
			}
			const previousValue = votedValue;
			votedValue = value;
			localStorage.setItem( storageKey( postId ), value );
			thumbsWrap.querySelectorAll( '[data-dwp-rx-value]' ).forEach( ( b ) => {
				b.setAttribute( 'aria-pressed', String( b === button ) );
			} );

			// Switching the vote starts with a clean slate: otherwise old text stays and, when sent
			// again, could get attached to the new vote, giving an apparently duplicate reaction.
			if ( textarea ) {
				textarea.value = '';
			}
			if ( questionEl ) {
				questionEl.textContent = questionEl.dataset[ 'like' === value ? 'dwpRxLabelLike' : 'dwpRxLabelDislike' ] || '';
			}

			const honeypot = panel.querySelector( '.dwp-rx-rating__hp input' );
			const result = await postJson( root.dataset.dwpRxRestCreate, {
				post_id: Number( postId ),
				value,
				website: honeypot ? honeypot.value : '',
			} );

			if ( ! result ) {
				// The request failed (offline, blocked, wrong URL): say so, and do not remember
				// the vote, so the same thumb can be tried again.
				votedValue = previousValue;
				if ( previousValue ) {
					localStorage.setItem( storageKey( postId ), previousValue );
				} else {
					localStorage.removeItem( storageKey( postId ) );
				}
				thumbsWrap.querySelectorAll( '[data-dwp-rx-value]' ).forEach( ( b ) => {
					b.setAttribute( 'aria-pressed', String( b.dataset.dwpRxValue === previousValue ) );
				} );
				showStep( panel, 'error' );
				openOrShow();
				return;
			}

			current = { id: result.id ?? null, token: result.token ?? null, value };

			showStep( panel, 'text' );
			openOrShow();
		} );
	} );

	const skipButton = panel.querySelector( '.dwp-rx-rating__skip' );
	const sendButton = panel.querySelector( '.dwp-rx-rating__send' );

	const finish = () => showStep( panel, 'done' );

	skipButton?.addEventListener( 'click', () => {
		finish();
		if ( isBar ) {
			setTimeout( () => closeAllPanels(), 1200 );
		}
	} );

	sendButton?.addEventListener( 'click', async () => {
		const text = ( textarea?.value || '' ).trim();

		if ( '' === text || ! current.id || ! current.token ) {
			finish();
			if ( isBar ) {
				setTimeout( () => closeAllPanels(), 1200 );
			}
			return;
		}

		const honeypot = panel.querySelector( '.dwp-rx-rating__hp input' );
		await postJson( root.dataset.dwpRxRestTextBase + current.id + '/text', {
			token: current.token,
			text,
			website: honeypot ? honeypot.value : '',
		} );

		showFollowUp();
	} );

	function showFollowUp() {
		const followUpStep = panel.querySelector( '[data-dwp-rx-step="follow-up"]' );
		const followUpQuestionEl = followUpStep.querySelector( '[data-dwp-rx-follow-up-question]' );
		const fieldsEl = followUpStep.querySelector( '[data-dwp-rx-follow-up-fields]' );
		const errorEl = followUpStep.querySelector( '[data-dwp-rx-follow-up-error]' );
		const yesButton = followUpStep.querySelector( '.dwp-rx-rating__follow-up-yes' );
		const noButton = followUpStep.querySelector( '.dwp-rx-rating__follow-up-no' );
		const nameInput = followUpStep.querySelector( '.dwp-rx-rating__name' );
		const emailInput = followUpStep.querySelector( '.dwp-rx-rating__email' );

		const action = 'like' === current.value ? 'publish' : 'contact';

		// If comments are closed on this page (or need an account) there is nothing to publish: the
		// explanation stays private and we simply finish. The server applies the same check; this
		// only stops someone from seeing an offer that cannot be fulfilled.
		if ( 'publish' === action && '1' !== root.dataset.dwpRxCanComment ) {
			postJson( root.dataset.dwpRxRestFollowupBase + current.id + '/follow-up', {
				token: current.token,
				action: 'skip',
				website: panel.querySelector( '.dwp-rx-rating__hp input' )?.value || '',
			} );
			finish();
			if ( isBar ) {
				setTimeout( () => closeAllPanels(), 1200 );
			}
			return;
		}

		const key = 'publish' === action ? 'dwpRxLabelPublish' : 'dwpRxLabelContact';
		followUpQuestionEl.textContent = followUpQuestionEl.dataset[ key ] || '';
		yesButton.textContent = yesButton.dataset[ key ] || '';
		fieldsEl.hidden = true;
		if ( errorEl ) {
			errorEl.textContent = '';
		}

		showStep( panel, 'follow-up' );

		let revealed = false;
		yesButton.onclick = async () => {
			if ( ! revealed ) {
				fieldsEl.hidden = false;
				revealed = true;
				nameInput?.focus();
				return;
			}

			// Name and email are not optional here: without them the comment cannot be posted, or
			// no contact can be made.
			const name = ( nameInput?.value || '' ).trim();
			const email = ( emailInput?.value || '' ).trim();
			if ( '' === name || '' === email || ! isValidEmail( email ) ) {
				if ( errorEl ) {
					errorEl.textContent =
						errorEl.dataset.dwpRxError || 'Enter your name and a valid email address.';
				}
				return;
			}

			const honeypot = panel.querySelector( '.dwp-rx-rating__hp input' );
			await postJson( root.dataset.dwpRxRestFollowupBase + current.id + '/follow-up', {
				token: current.token,
				action,
				name,
				email,
				website: honeypot ? honeypot.value : '',
			} );
			finish();
			if ( isBar ) {
				setTimeout( () => closeAllPanels(), 1500 );
			}
		};

		noButton.onclick = async () => {
			const honeypot = panel.querySelector( '.dwp-rx-rating__hp input' );
			await postJson( root.dataset.dwpRxRestFollowupBase + current.id + '/follow-up', {
				token: current.token,
				action: 'skip',
				website: honeypot ? honeypot.value : '',
			} );
			finish();
			if ( isBar ) {
				setTimeout( () => closeAllPanels(), 1200 );
			}
		};
	}
} );
