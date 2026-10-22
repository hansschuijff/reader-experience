<?php
/**
 * Heading ids for the anchor links.
 *
 * @package ReaderExperience\Modules\Reading
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Reading;

use WP_HTML_Tag_Processor;
use WP_Post;

use function add_filter;
use function get_post;
use function get_queried_object_id;
use function html_entity_decode;
use function is_singular;
use function is_string;
use function max;
use function preg_match;
use function trim;
use function wp_strip_all_tags;

/**
 * Gives headings (h2 to h4) an id while the core/heading blocks of the post content render, so
 * they can be linked to. The table of contents does not depend on this: it builds its list in the
 * browser from the headings on the page and tags them with its own data attribute.
 *
 * Ids are only added when HeadingAnchors allows it for the post (off by default).
 */
final class HeadingIndexer {

	public const MIN_LEVEL = 2;
	public const MAX_LEVEL = 4;

	/**
	 * Nesting depth of the_content runs, so only the post content itself counts.
	 *
	 * @var int
	 */
	private int $depth = 0;

	/**
	 * Decision for the running the_content pass: do the headings get an id here?
	 *
	 * @var bool
	 */
	private bool $active = false;

	/**
	 * Hands out unique anchors.
	 *
	 * @var AnchorRegistry
	 */
	private AnchorRegistry $registry;

	/**
	 * Policy: where are ids wanted?
	 *
	 * @var HeadingAnchors
	 */
	private HeadingAnchors $anchors;

	/**
	 * Constructor.
	 *
	 * @param HeadingAnchors $anchors Policy.
	 */
	public function __construct( HeadingAnchors $anchors ) {
		$this->registry = new AnchorRegistry();
		$this->anchors  = $anchors;
	}

	/**
	 * Attaches the filters.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'start' ), 1 );
		add_filter( 'the_content', array( $this, 'end' ), PHP_INT_MAX );
		add_filter( 'render_block_core/heading', array( $this, 'add_anchor' ), 10, 2 );
	}

	/**
	 * Marks the start of a the_content pass (first filter).
	 *
	 * @since 0.1.0
	 * @param mixed $content Post content, passed through untouched.
	 * @return mixed
	 */
	public function start( $content ) {
		if ( 0 === $this->depth ) {
			$this->registry->reset();
			$post         = get_post();
			$this->active = $this->anchors->is_enabled_for( $post ) && self::is_main_post( $post );
		}
		++$this->depth;
		return $content;
	}

	/**
	 * Marks the end of a the_content pass (last filter).
	 *
	 * @since 0.1.0
	 * @param mixed $content Post content, passed through untouched.
	 * @return mixed
	 */
	public function end( $content ) {
		$this->depth = max( 0, $this->depth - 1 );
		if ( 0 === $this->depth ) {
			$this->active = false;
		}
		return $content;
	}

	/**
	 * Adds an id to a rendered heading block.
	 *
	 * @since 0.1.0
	 * @param mixed               $html  Rendered block HTML.
	 * @param array<string,mixed> $block Parsed block (unused).
	 * @return mixed The HTML, with an id on the heading when the policy allows it.
	 */
	public function add_anchor( $html, $block = array() ) {
		// Only headings inside the post content count, not those from templates,
		// and only when the policy allows it for this post.
		if ( 0 === $this->depth || ! $this->active || ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag() ) {
			return $html;
		}

		if ( ! preg_match( '/^H([1-6])$/', (string) $processor->get_tag(), $m ) ) {
			return $html;
		}

		$level = (int) $m[1];
		if ( $level < self::MIN_LEVEL || $level > self::MAX_LEVEL ) {
			return $html;
		}

		$existing = $processor->get_attribute( 'id' );
		$existing = is_string( $existing ) && '' !== $existing ? $existing : null;

		$id = $this->registry->claim( $existing, self::plain_text( $html ), $level );

		if ( null !== $existing ) {
			return $html;
		}

		$processor->set_attribute( 'id', $id );
		return $processor->get_updated_html();
	}

	/**
	 * Whether this is the post the visitor asked for: a single post or page, not one that is shown
	 * inside another page (related posts, a query loop on a landing page) or in an archive.
	 *
	 * In_the_loop() is deliberately not used: block themes render the post content outside the classic loop.
	 *
	 * @param WP_Post|null $post The post whose content is being filtered.
	 * @return bool
	 */
	private static function is_main_post( ?WP_Post $post ): bool {
		return $post instanceof WP_Post && is_singular() && get_queried_object_id() === (int) $post->ID;
	}

	/**
	 * Plain text of an HTML snippet, with entities decoded.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function plain_text( string $html ): string {
		return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}
}
