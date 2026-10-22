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

use function apply_filters;
use function array_filter;
use function array_map;
use function array_values;
use function in_array;
use function is_array;
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
	 * Ids that WordPress, themes and plugins use for their own elements. A heading with one of
	 * these as its text gets the level in front ("h2-comments"), so a link to it never lands on, for
	 * example, the comments section.
	 *
	 * @var string[]
	 */
	public const DEFAULT_RESERVED = array(
		'comments',
		'commentform',
		'respond',
		'reply-title',
		'content',
		'main',
		'primary',
		'secondary',
		'header',
		'footer',
		'masthead',
		'colophon',
		'sidebar',
		'navigation',
		'nav',
		'menu',
		'search',
		'page',
		'top',
		'wpadminbar',
		'site-header',
		'site-footer',
		'site-content',
		'site-navigation',
	);

	/**
	 * The reserved ids, after filtering. Read once per registry.
	 *
	 * @var string[]|null
	 */
	private ?array $reserved = null;

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
	 * the text, with a numeric suffix for duplicates: "intro", "intro-2", "intro-3". A reserved id
	 * gets the level in front: "h2-comments".
	 *
	 * @since 0.1.0
	 * @param string|null $existing Id the heading already has, if any.
	 * @param string      $text     Plain text of the heading.
	 * @param int         $level    Heading level (2 to 4), used for reserved ids.
	 * @return string
	 */
	public function claim( ?string $existing, string $text, int $level = 2 ): string {
		if ( is_string( $existing ) && '' !== $existing ) {
			$this->used[ $existing ] = true;
			return $existing;
		}

		$base = sanitize_title( $text );
		if ( '' === $base ) {
			// Fallback id for headings without usable text.
			$base = 'section';
		}

		/**
		 * Filters the id a heading would get, before reserved ids and duplicates are dealt with.
		 *
		 * Return a plain id (letters, digits, hyphens). Anything else is cleaned up with sanitize_title(),
		 * and an empty result falls back to "section". Existing ids of a heading are never filtered.
		 *
		 * @since 0.3.0
		 *
		 * @param string $base  The id made from the heading text, for example "introduction".
		 * @param string $text  Plain text of the heading.
		 * @param int    $level Heading level, 2 to 4.
		 */
		$filtered = apply_filters( 'reader_experience/heading_anchor_id', $base, $text, $level );
		$filtered = is_string( $filtered ) ? sanitize_title( $filtered ) : '';
		$base     = '' !== $filtered ? $filtered : $base;

		if ( in_array( $base, $this->reserved(), true ) ) {
			$base = 'h' . $level . '-' . $base;
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

	/**
	 * The ids that are reserved for elements of the theme and WordPress.
	 *
	 * @return string[]
	 */
	private function reserved(): array {
		if ( null === $this->reserved ) {
			/**
			 * Filters the ids that generated heading anchors must not take.
			 *
			 * A heading whose id would be one of these gets its level in front instead ("h2-comments").
			 * Add the ids your theme uses for its own elements.
			 *
			 * @since 0.3.0
			 *
			 * @param string[] $ids Reserved ids.
			 */
			$ids            = apply_filters( 'reader_experience/reserved_ids', self::DEFAULT_RESERVED );
			$this->reserved = is_array( $ids )
				? array_values( array_filter( array_map( 'strval', array_filter( $ids, 'is_scalar' ) ), static fn ( string $id ): bool => '' !== $id ) )
				: self::DEFAULT_RESERVED;
		}

		return $this->reserved;
	}
}
