<?php
/**
 * CSS and JS for the "copy link" icon next to headings.
 *
 * @package ReaderExperience\Modules\Reading
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.2.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Reading;

use WP_Post;

use function add_action;
use function defined;
use function filemtime;
use function get_queried_object;
use function is_singular;
use function plugin_dir_path;
use function plugin_dir_url;
use function wp_add_inline_script;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_json_encode;

use const ReaderExperience\PLUGIN_FILE;

/**
 * Registers the separate (unbundled) "copy link" icon assets for headings.
 *
 * Loads only on a single view for which HeadingAnchors switched the functionality on. CSS and JS
 * can be switched off separately, so a site can put its own version in their place (see
 * HeadingAnchors and the README for the filters).
 */
final class HeadingLinkAssets {

	/**
	 * Handle for the style and the script.
	 *
	 * @var string
	 */
	private const HANDLE = 'dwp-rx-heading-anchor-links';

	/**
	 * Policy.
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
		$this->anchors = $anchors;
	}

	/**
	 * Hooks into the scripts action.
	 *
	 * @since 0.2.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Loads CSS and JS, as far as the policy allows.
	 *
	 * @since 0.2.0
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! is_singular() || ! defined( 'ReaderExperience\PLUGIN_FILE' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! $this->anchors->is_enabled_for( $post ) ) {
			return;
		}

		$uri = plugin_dir_url( PLUGIN_FILE );
		$dir = plugin_dir_path( PLUGIN_FILE );

		if ( $this->anchors->load_css( $post ) ) {
			wp_enqueue_style(
				self::HANDLE,
				$uri . 'assets/css/heading-anchor-links.css',
				array(),
				(string) filemtime( $dir . 'assets/css/heading-anchor-links.css' )
			);
		}

		if ( $this->anchors->load_js( $post ) ) {
			wp_enqueue_script(
				self::HANDLE,
				$uri . 'assets/js/heading-anchor-links.js',
				array(),
				(string) filemtime( $dir . 'assets/js/heading-anchor-links.js' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);

			// The options belong to our JS: if that is replaced, the site handles its own options.
			wp_add_inline_script(
				self::HANDLE,
				'window.dwpRxHeadingAnchors=' . wp_json_encode( $this->anchors->config( $post ) ) . ';',
				'before'
			);
		}
	}
}
