<?php
/**
 * Heading anchors and the heading list for the table of contents.
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
use function array_column;
use function array_filter;
use function array_values;
use function count;
use function get_post;
use function html_entity_decode;
use function is_string;
use function max;
use function min;
use function parse_blocks;
use function preg_match;
use function trim;
use function wp_strip_all_tags;

/**
 * Gives headings (h2 to h4) an anchor and builds the list for the table of contents.
 *
 * - Anchors are added while the core/heading blocks of the post content render.
 * - The list comes from parse_blocks() on the post content, including synced patterns.
 *
 * Limitation: headings that only come into being through other dynamic blocks (a query loop
 * inside the post, say) and classic, non-block headings are not included.
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
	 * Heading lists per post id, built once per request.
	 *
	 * @var array<int,array<int,array{id:string,text:string,level:int}>>
	 */
	private static array $cache = array();

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
			$this->active = $this->anchors->needs_ids_for( get_post() );
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

		$id = $this->registry->claim( $existing, self::plain_text( $html ) );

		if ( null !== $existing ) {
			return $html;
		}

		$processor->set_attribute( 'id', $id );
		return $processor->get_updated_html();
	}

	/**
	 * The headings of a post, for the table of contents.
	 *
	 * @since 0.1.0
	 * @param int $post_id   Post id.
	 * @param int $max_level Deepest heading level to include (2 to 4).
	 * @return array<int,array{id:string,text:string,level:int}>
	 */
	public static function headings_for_post( int $post_id, int $max_level = 3 ): array {
		if ( ! isset( self::$cache[ $post_id ] ) ) {
			$post = get_post( $post_id );
			$out  = array();

			if ( $post instanceof WP_Post ) {
				self::walk( parse_blocks( $post->post_content ), new AnchorRegistry(), $out, 0 );
			}

			self::$cache[ $post_id ] = $out;
		}

		return array_values(
			array_filter(
				self::$cache[ $post_id ],
				static fn ( array $item ): bool => $item['level'] <= $max_level
			)
		);
	}

	/**
	 * Collects headings from a block tree, following synced patterns (reusable blocks).
	 *
	 * @param array<int,array<string,mixed>>                    $blocks   Parsed blocks.
	 * @param AnchorRegistry                                    $registry Anchor registry.
	 * @param array<int,array{id:string,text:string,level:int}> $out      Collected headings (by reference).
	 * @param int                                               $guard    Nesting depth of synced patterns, to stop loops.
	 * @return void
	 */
	private static function walk( array $blocks, AnchorRegistry $registry, array &$out, int $guard ): void {
		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? null;

			if ( 'core/heading' === $name ) {
				$level = (int) ( $block['attrs']['level'] ?? 2 );

				if ( $level >= self::MIN_LEVEL && $level <= self::MAX_LEVEL ) {
					$text     = self::plain_text( (string) ( $block['innerHTML'] ?? '' ) );
					$existing = $block['attrs']['anchor'] ?? null;
					$id       = $registry->claim( is_string( $existing ) ? $existing : null, $text );

					if ( '' !== $text ) {
						$out[] = array(
							'id'    => $id,
							'text'  => $text,
							'level' => $level,
						);
					}
				}
			} elseif ( 'core/block' === $name && $guard < 3 && ! empty( $block['attrs']['ref'] ) ) {
				$ref = get_post( (int) $block['attrs']['ref'] );
				if ( $ref instanceof WP_Post && 'wp_block' === $ref->post_type ) {
					self::walk( parse_blocks( $ref->post_content ), $registry, $out, $guard + 1 );
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::walk( $block['innerBlocks'], $registry, $out, $guard );
			}
		}
	}

	/**
	 * Groups a flat heading list by top level (the lowest level in the list, usually h2):
	 * everything below becomes a child of the most recent top-level heading. Headings that
	 * come before the first top-level heading (rare) get a group of their own, without children.
	 *
	 * @since 0.1.0
	 * @param array<int,array{id:string,text:string,level:int}> $items Flat heading list.
	 * @return array<int,array{item:array{id:string,text:string,level:int},children:array<int,array{id:string,text:string,level:int}>}>
	 */
	public static function group_by_top_level( array $items ): array {
		if ( ! $items ) {
			return array();
		}

		$top_level = min( array_column( $items, 'level' ) );
		$groups    = array();
		$current   = null;

		foreach ( $items as $item ) {
			if ( $item['level'] === $top_level ) {
				$current  = array(
					'item'     => $item,
					'children' => array(),
				);
				$groups[] = $current;
			} elseif ( null !== $current ) {
				$groups[ count( $groups ) - 1 ]['children'][] = $item;
			} else {
				$groups[] = array(
					'item'     => $item,
					'children' => array(),
				);
			}
		}

		return $groups;
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
