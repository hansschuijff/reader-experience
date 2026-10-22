<?php
/**
 * Policy for heading anchors: where they are active and with which options.
 *
 * @package ReaderExperience\Modules\Reading
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.2.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Reading;

use WP_Post;

use function __;
use function apply_filters;
use function array_filter;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
use function is_string;
use function trim;

/**
 * Decides, through filters, where the anchor functionality is active.
 *
 * Everything is OFF by default: without a filter nothing happens (no ids on headings, no
 * button, no CSS or JS). A site switches it on per post type and can then deviate per post or
 * replace the bundled CSS/JS. See the README for the full list of filters.
 *
 * Exception: headings need ids as soon as the post contains a table of contents block, even
 * when the post type is not switched on; otherwise the table of contents would point at nothing.
 * Only the ids, though: the button and the assets stay off.
 */
final class HeadingAnchors {

	public const POSITION_BEFORE = 'before';
	public const POSITION_AFTER  = 'after';

	/**
	 * Post types for which it is switched on, fetched once per request.
	 *
	 * @var string[]|null
	 */
	private ?array $post_types = null;

	/**
	 * Result of is_enabled_for() per post, so the filters do not run again for every heading.
	 *
	 * @var array<int,bool>
	 */
	private array $enabled = array();

	/**
	 * Post types for which the functionality is switched on.
	 *
	 * @since 0.2.0
	 * @return string[]
	 */
	public function post_types(): array {
		if ( null === $this->post_types ) {
			/**
			 * Filters the post types for which heading anchors are switched on.
			 *
			 * @since 0.2.0
			 *
			 * @param string[] $post_types Post type names. Default: none.
			 */
			$types = apply_filters( 'reader_experience/heading_anchors_post_types', array() );

			$this->post_types = is_array( $types )
				? array_values( array_unique( array_filter( $types, 'is_string' ) ) )
				: array();
		}

		return $this->post_types;
	}

	/**
	 * Is the full functionality (ids, button, assets) switched on for this post?
	 *
	 * @since 0.2.0
	 * @param WP_Post|null $post The post.
	 * @return bool
	 */
	public function is_enabled_for( ?WP_Post $post ): bool {
		if ( null === $post ) {
			return false;
		}

		if ( ! isset( $this->enabled[ $post->ID ] ) ) {
			$default = in_array( $post->post_type, $this->post_types(), true );

			/**
			 * Filters whether heading anchors are enabled for a post. The final say, per post.
			 *
			 * @since 0.2.0
			 *
			 * @param bool    $enabled True when the post type is in the post types list.
			 * @param WP_Post $post    The post.
			 */
			$this->enabled[ $post->ID ] = (bool) apply_filters( 'reader_experience/heading_anchors_enabled', $default, $post );
		}

		return $this->enabled[ $post->ID ];
	}

	/**
	 * May the bundled CSS be loaded?
	 *
	 * @since 0.2.0
	 * @param WP_Post $post The post.
	 * @return bool
	 */
	public function load_css( WP_Post $post ): bool {
		/**
		 * Filters whether the bundled heading anchor CSS is loaded.
		 *
		 * @since 0.2.0
		 *
		 * @param bool    $load Whether to load the CSS. Default true.
		 * @param WP_Post $post The post.
		 */
		return (bool) apply_filters( 'reader_experience/heading_anchors_load_css', true, $post );
	}

	/**
	 * May the bundled JS be loaded?
	 *
	 * @since 0.2.0
	 * @param WP_Post $post The post.
	 * @return bool
	 */
	public function load_js( WP_Post $post ): bool {
		/**
		 * Filters whether the bundled heading anchor JavaScript is loaded.
		 *
		 * @since 0.2.0
		 *
		 * @param bool    $load Whether to load the JavaScript. Default true.
		 * @param WP_Post $post The post.
		 */
		return (bool) apply_filters( 'reader_experience/heading_anchors_load_js', true, $post );
	}

	/**
	 * Options for the JS, cleaned up to valid values after filtering.
	 *
	 * @since 0.2.0
	 * @param WP_Post $post The post.
	 * @return array{position:string,container:string[],labels:array{copy:string,copied:string}}
	 */
	public function config( WP_Post $post ): array {
		/**
		 * Filters the options of the heading anchor JavaScript.
		 *
		 * Always return the whole array. If a filter returns anything else, the options are emptied
		 * and the JavaScript falls back to its built-in English defaults.
		 *
		 * @since 0.2.0
		 *
		 * @param array   $config {
		 *     The options.
		 *
		 *     @type string   $position  Where the button goes: 'before' or 'after' the heading text.
		 *     @type string[] $container CSS selectors of the element(s) that hold the headings, tried in order.
		 *     @type array    $labels    {
		 *         @type string $copy   Label of the copy button.
		 *         @type string $copied Label shown after copying.
		 *     }
		 * }
		 * @param WP_Post $post   The post.
		 */
		$config = apply_filters(
			'reader_experience/heading_anchors_config',
			array(
				'position'  => self::POSITION_BEFORE,
				'container' => array( '.wp-block-post-content', '.entry-content', 'article' ),
				'labels'    => array(
					'copy'   => __( 'Copy link to this section', 'reader-experience' ),
					'copied' => __( 'Link copied', 'reader-experience' ),
				),
			),
			$post
		);

		$config    = is_array( $config ) ? $config : array();
		$labels    = is_array( $config['labels'] ?? null ) ? $config['labels'] : array();
		$container = $config['container'] ?? array();

		return array(
			'position'  => self::POSITION_AFTER === ( $config['position'] ?? '' ) ? self::POSITION_AFTER : self::POSITION_BEFORE,
			'container' => array_values( array_filter( (array) $container, static fn ( $selector ): bool => is_string( $selector ) && '' !== trim( $selector ) ) ),
			'labels'    => array(
				'copy'   => (string) ( $labels['copy'] ?? '' ),
				'copied' => (string) ( $labels['copied'] ?? '' ),
			),
		);
	}
}
