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
				questionEl.textContent = questionEl.dataset[ 'ja' === value ? 'rxLabelJa' : 'rxLabelNee' ] || '';
			}

			const honeypot = panel.querySelector( '.rx-rating__hp input' );
			const result = await postJson( root.dataset.rxRestCreate, {
				post_id: Number( postId ),
				value,
				website: honeypot ? honeypot.value : '',
			} );

			current = { id: result?.id ?? null, token: result?.token ?? null, value };

			showStep( panel, 'tekst' );
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
		await postJson( root.dataset.rxRestTextBase + current.id + '/tekst', {
			token: current.token,
			tekst: text,
			website: honeypot ? honeypot.value : '',
		} );

		showFollowUp();
	} );

	function showFollowUp() {
		const vervolgStep = panel.querySelector( '[data-rx-step="vervolg"]' );
		const vraagEl = vervolgStep.querySelector( '[data-rx-vervolg-vraag]' );
		const veldenEl = vervolgStep.querySelector( '[data-rx-vervolg-velden]' );
		const errorEl = vervolgStep.querySelector( '[data-rx-vervolg-error]' );
		const jaButton = vervolgStep.querySelector( '.rx-rating__vervolg-ja' );
		const neeButton = vervolgStep.querySelector( '.rx-rating__vervolg-nee' );
		const naamInput = vervolgStep.querySelector( '.rx-rating__naam' );
		const emailInput = vervolgStep.querySelector( '.rx-rating__email' );

		const actie = 'ja' === current.value ? 'publiceren' : 'contact';
		const key = 'publiceren' === actie ? 'rxLabelPubliceren' : 'rxLabelContact';
		vraagEl.textContent = vraagEl.dataset[ key ] || '';
		jaButton.textContent = jaButton.dataset[ key ] || '';
		veldenEl.hidden = true;
		if ( errorEl ) {
			errorEl.textContent = '';
		}

		showStep( panel, 'vervolg' );

		let revealed = false;
		jaButton.onclick = async () => {
			if ( ! revealed ) {
				veldenEl.hidden = false;
				revealed = true;
				naamInput?.focus();
				return;
			}

			// Naam en e-mail zijn hier niet optioneel: zonder die gegevens kan de
			// reactie niet geplaatst, of kan er geen contact opgenomen worden.
			const naam = ( naamInput?.value || '' ).trim();
			const email = ( emailInput?.value || '' ).trim();
			if ( '' === naam || '' === email || ! isValidEmail( email ) ) {
				if ( errorEl ) {
					errorEl.textContent =
						errorEl.dataset.rxError || 'Vul je naam en een geldig e-mailadres in.';
				}
				return;
			}

			const honeypot = panel.querySelector( '.rx-rating__hp input' );
			await postJson( root.dataset.rxRestFollowupBase + current.id + '/vervolg', {
				token: current.token,
				actie,
				naam,
				email,
				website: honeypot ? honeypot.value : '',
			} );
			finish();
			if ( isBar ) {
				setTimeout( () => closeAllPanels(), 1500 );
			}
		};

		neeButton.onclick = async () => {
			const honeypot = panel.querySelector( '.rx-rating__hp input' );
			await postJson( root.dataset.rxRestFollowupBase + current.id + '/vervolg', {
				token: current.token,
				actie: 'overslaan',
				website: honeypot ? honeypot.value : '',
			} );
			finish();
			if ( isBar ) {
				setTimeout( () => closeAllPanels(), 1200 );
			}
		};
	}
} );
