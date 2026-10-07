/**
 * Dezelfde iconen als src/Support/Icons.php, maar dan voor editor-previews.
 * Twee plekken met dezelfde bron is niet ideaal, maar er is geen praktische
 * manier om PHP-render-time iconen en JS-build-time previews één bron te
 * laten delen zonder extra tooling. Wijzig je een icoon, doe het op beide plekken.
 */
export const ICONS = {
	comments:
		'<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
	share:
		'<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5 15.4 17.5M15.4 6.5 8.6 10.5"/></svg>',
	toc:
		'<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>',
	thumb_up:
		'<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 10v11M2 13v6a2 2 0 0 0 2 2h11.3a3 3 0 0 0 3-2.4l1.5-7A2 2 0 0 0 17.8 9H13V5a2 2 0 0 0-2-2l-1 1-3 7H2z"/></svg>',
	thumb_down:
		'<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 14V3M22 11v-6a2 2 0 0 0-2-2H8.7a3 3 0 0 0-3 2.4l-1.5 7A2 2 0 0 0 6.2 15H11v4a2 2 0 0 0 2 2l1-1 3-7h5z"/></svg>',
};

export function Icon( { name } ) {
	return <span className="rx-bar__icon" dangerouslySetInnerHTML={ { __html: ICONS[ name ] || '' } } />;
}
