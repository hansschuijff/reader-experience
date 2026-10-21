import { openPanel, closeAllPanels, wireGlobalPopoverBehaviour } from '../shared/popover';

wireGlobalPopoverBehaviour();

function storageKey( postId ) {
	return 'rx-rated-' + postId;
}

function showStep( panel, name ) {
	panel.querySelectorAll( '[data-rx-step]' ).forEach( ( el ) => {
		el.hidden = el.dataset.rxStep !== name;
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
	// Lichte controle, geen volledige RFC-validatie: alleen bedoeld om de
	// overduidelijke tikfout op te vangen, niet om iets te blokkeren.
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value );
}

document.querySelectorAll( '[data-rx-rating]' ).forEach( ( root ) => {
	const postId = root.dataset.rxPost;
	const isBar = root.classList.contains( 'rx-rating--bar' );
	const panel = root.querySelector( '[data-rx-panel]' );
	const thumbsWrap = root.querySelector( '[data-rx-step="thumbs"]' );
	const questionEl = panel.querySelector( '[data-rx-step-question]' );
	const textarea = panel.querySelector( '.rx-rating__text' );

	// In de balk krijgt het paneel een eigen id, zodat het als "vast, gecentreerd"
	// paneeltje via de gedeelde popover-module beheerd kan worden.
	if ( isBar ) {
		panel.id = panel.id || 'rx-rating-panel-' + postId + '-' + Math.random().toString( 36 ).slice( 2, 7 );
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
		thumbsWrap.querySelectorAll( '[data-rx-value]' ).forEach( ( btn ) => {
			btn.setAttribute( 'aria-pressed', String( btn.dataset.rxValue === votedValue ) );
		} );
	}

	let current = { id: null, token: null, value: null };

	// Geen "op slot" na een keuze: nogmaals klikken op dezelfde duim doet niets,
	// op de andere duim klikken wijzigt de stem gewoon (een nieuwe, losse waardering;
	// er is toch geen account om een eerdere mee te koppelen).
	thumbsWrap.querySelectorAll( '[data-rx-value]' ).forEach( ( button ) => {
		button.addEventListener( 'click', async () => {
			const value = button.dataset.rxValue;
			if ( value === votedValue ) {
				return;
			}
			votedValue = value;
			localStorage.setItem( storageKey( postId ), value );
			thumbsWrap.querySelectorAll( '[data-rx-value]' ).forEach( ( b ) => {
				b.setAttribute( 'aria-pressed', String( b === button ) );
			} );

			// Bij het wisselen van stem hoort een schone lei: anders blijft oude
			// tekst staan en kan die bij opnieuw versturen aan de nieuwe stem
			// vast komen te hangen, met een dubbel ogende reactie tot gevolg.
			if ( textarea ) {
				textarea.value = '';
			}
			if ( questionEl ) {
				questionEl.textContent = questionEl.dataset[ 'like' === value ? 'rxLabelLike' : 'rxLabelDislike' ] || '';
			}

			const honeypot = panel.querySelector( '.rx-rating__hp input' );
			const result = await postJson( root.dataset.rxRestCreate, {
				post_id: Number( postId ),
				value,
				website: honeypot ? honeypot.value : '',
			} );

			current = { id: result?.id ?? null, token: result?.token ?? null, value };

			showStep( panel, 'text' );
			openOrShow();
		} );
	} );

	const skipButton = panel.querySelector( '.rx-rating__skip' );
	const sendButton = panel.querySelector( '.rx-rating__send' );

	const finish = () => showStep( panel, 'klaar' );

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

		const honeypot = panel.querySelector( '.rx-rating__hp input' );
		await postJson( root.dataset.rxRestTextBase + current.id + '/text', {
			token: current.token,
			text,
			website: honeypot ? honeypot.value : '',
		} );

		showFollowUp();
	} );

	function showFollowUp() {
		const followUpStep = panel.querySelector( '[data-rx-step="follow-up"]' );
		const followUpQuestionEl = followUpStep.querySelector( '[data-rx-follow-up-question]' );
		const fieldsEl = followUpStep.querySelector( '[data-rx-follow-up-fields]' );
		const errorEl = followUpStep.querySelector( '[data-rx-follow-up-error]' );
		const yesButton = followUpStep.querySelector( '.rx-rating__follow-up-yes' );
		const noButton = followUpStep.querySelector( '.rx-rating__follow-up-no' );
		const nameInput = followUpStep.querySelector( '.rx-rating__name' );
		const emailInput = followUpStep.querySelector( '.rx-rating__email' );

		const action = 'like' === current.value ? 'publish' : 'contact';

		// Staan reacties op deze pagina dicht (of vereisen ze een account), dan is er niets te
		// plaatsen: de toelichting blijft privé en we ronden gewoon af. De server houdt dezelfde
		// controle aan, dit voorkomt alleen dat iemand een aanbod ziet dat niet kan.
		if ( 'publish' === action && '1' !== root.dataset.rxCanComment ) {
			postJson( root.dataset.rxRestFollowupBase + current.id + '/follow-up', {
				token: current.token,
				action: 'skip',
				website: panel.querySelector( '.rx-rating__hp input' )?.value || '',
			} );
			finish();
			if ( isBar ) {
				setTimeout( () => closeAllPanels(), 1200 );
			}
			return;
		}

		const key = 'publish' === action ? 'rxLabelPublish' : 'rxLabelContact';
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

			// Naam en e-mail zijn hier niet optioneel: zonder die gegevens kan de
			// reactie niet geplaatst, of kan er geen contact opgenomen worden.
			const name = ( nameInput?.value || '' ).trim();
			const email = ( emailInput?.value || '' ).trim();
			if ( '' === name || '' === email || ! isValidEmail( email ) ) {
				if ( errorEl ) {
					errorEl.textContent =
						errorEl.dataset.rxError || 'Enter your name and a valid email address.';
				}
				return;
			}

			const honeypot = panel.querySelector( '.rx-rating__hp input' );
			await postJson( root.dataset.rxRestFollowupBase + current.id + '/follow-up', {
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
			const honeypot = panel.querySelector( '.rx-rating__hp input' );
			await postJson( root.dataset.rxRestFollowupBase + current.id + '/follow-up', {
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
