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
	 * @param string|null $existing_id   Id the heading already has, if any.
	 * @param string      $heading       The heading text without tags.
	 * @param int         $heading_level Heading level (2 to 4), used for reserved ids.
	 * @return string
	 */
	public function claim( ?string $existing_id, string $heading, int $heading_level = 2 ): string {
		if ( is_string( $existing_id ) && '' !== $existing_id ) {
			$this->used[ $existing_id ] = true;
			return $existing_id;
		}

		$id = sanitize_title( $heading );
		if ( '' === $id ) {
			// Fallback id for headings without usable text.
			$id = 'section';
		}

		/**
		 * Filters the id a heading would get, before reserved ids and duplicates are dealt with.
		 *
		 * Use it to shorten, translate or otherwise tidy generated ids. The result still goes through the
		 * reserved ids check (a reserved id gets "h2-" in front) and the duplicate check ("-2", "-3").
		 * Return a plain id (letters, digits, hyphens); anything else is cleaned up with sanitize_title(),
		 * and an empty or invalid result is ignored. A heading's own id is never filtered.
		 *
		 * @since 0.3.0
		 *
		 * @param string $id            The id made from the heading text, for example "introduction".
		 * @param string $heading       The heading text without tags.
		 * @param int    $heading_level Heading level, 2 to 4.
		 */
		$filtered = apply_filters( 'reader_experience/heading_anchor_id', $id, $heading, $heading_level );
		$filtered = is_string( $filtered ) ? sanitize_title( $filtered ) : '';
		$id       = '' !== $filtered ? $filtered : $id;

		if ( in_array( $id, $this->reserved(), true ) ) {
			$id = 'h' . $heading_level . '-' . $id;
		}

		$unique_id = $id;
		$n         = 1;
		while ( isset( $this->used[ $unique_id ] ) ) {
			++$n;
			$unique_id = $id . '-' . $n;
		}

		$this->used[ $unique_id ] = true;
		return $unique_id;
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
