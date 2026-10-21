<?php
/**
 * Unique anchor ids.
 *
 * @package ReaderExperience\Modules\Reading
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Reading;

use function is_string;
use function sanitize_title;

/**
 * Hands out unique anchors, in order. The same order and the same rules are used when rendering
 * headings and when building the table of contents, so both end up with the same ids.
 */
final class AnchorRegistry {

	/**
	 * Anchors that are taken.
	 *
	 * @var array<string,true>
	 */
	private array $used = array();

	/**
	 * Forgets all anchors, to start a new post.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function reset(): void {
		$this->used = array();
	}

	/**
	 * Claims an anchor for a heading.
	 *
	 * An id the heading already has is kept (and marked as taken). Otherwise one is made from
	 * the text, with a numeric suffix for duplicates: "intro", "intro-2", "intro-3".
	 *
	 * @since 0.1.0
	 * @param string|null $existing Id the heading already has, if any.
	 * @param string      $text     Plain text of the heading.
	 * @return string
	 */
	public function claim( ?string $existing, string $text ): string {
		if ( is_string( $existing ) && '' !== $existing ) {
			$this->used[ $existing ] = true;
			return $existing;
		}

		$base = sanitize_title( $text );
		if ( '' === $base ) {
			// Fallback id for headings without usable text.
			$base = 'section';
		}

		$slug = $base;
		$n    = 1;
		while ( isset( $this->used[ $slug ] ) ) {
			++$n;
			$slug = $base . '-' . $n;
		}

		$this->used[ $slug ] = true;
		return $slug;
	}
}
