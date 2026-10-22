<?php
/**
 * Block registration.
 *
 * @package ReaderExperience\Blocks
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Blocks;

use WP_Block_Type;

use function array_merge;
use function defined;
use function dirname;
use function error_log;
use function filemtime;
use function in_array;
use function is_readable;
use function plugins_url;
use function register_block_type;
use function rtrim;
use function sprintf;
use function wp_register_style;
use function wp_scripts;
use function wp_set_script_translations;

/**
 * Registers blocks from the build folder, based on their block.json.
 * Only blocks of active modules are passed in.
 */
final class BlockRegistrar {

	/**
	 * Handle of the stylesheet shared by all blocks (assets/css/base.css).
	 *
	 * @var string
	 */
	public const BASE_STYLE_HANDLE = 'rx-base';

	/**
	 * Handle of the stylesheet of the table of contents list (assets/css/toc.css), shared by the
	 * inline block and the drawer.
	 *
	 * @var string
	 */
	public const TOC_STYLE_HANDLE = 'rx-toc';

	/**
	 * Constructor.
	 *
	 * @param string $build_dir Absolute path of the build folder with one folder per block.
	 */
	public function __construct( private readonly string $build_dir ) {}

	/**
	 * Registers the given blocks.
	 *
	 * @since 0.1.0
	 * @param string[] $slugs Block slugs, equal to the folder names in the build folder.
	 * @return void
	 */
	public function register( array $slugs ): void {
		$this->register_shared_styles();

		foreach ( $slugs as $slug ) {
			$dir = rtrim( $this->build_dir, '/' ) . '/' . $slug;

			if ( ! is_readable( $dir . '/block.json' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'Reader Experience: block.json is missing for "%s". Did you run npm run build?', $slug ) );
				}
				continue;
			}

			$type = register_block_type( $dir );

			if ( $type instanceof WP_Block_Type ) {
				$this->set_script_translations( $type );
			}
		}
	}

	/**
	 * Tells WordPress where the bundled JSON translations of a block's scripts live. Core only
	 * looks in the global languages folder (wp-content/languages/plugins) unless it is given a path.
	 *
	 * @param WP_Block_Type $type Registered block type.
	 * @return void
	 */
	private function set_script_translations( WP_Block_Type $type ): void {
		$languages = dirname( $this->build_dir, 2 ) . '/languages';

		foreach ( array_merge( $type->editor_script_handles, $type->view_script_handles ) as $handle ) {
			$script = wp_scripts()->query( (string) $handle );

			// Only scripts that use wp.i18n: do not add the dependency to scripts that do not.
			if ( $script && in_array( 'wp-i18n', $script->deps, true ) ) {
				wp_set_script_translations( (string) $handle, 'reader-experience', $languages );
			}
		}
	}

	/**
	 * Registers the shared stylesheets, so block.json files can list them in "style".
	 * They are registered, not enqueued: WordPress loads them only when a block that needs them renders.
	 *
	 * @return void
	 */
	private function register_shared_styles(): void {
		$root = dirname( $this->build_dir, 2 ); // From build/blocks to the plugin root.

		$styles = array(
			self::BASE_STYLE_HANDLE => 'assets/css/base.css',
			self::TOC_STYLE_HANDLE  => 'assets/css/toc.css',
		);

		foreach ( $styles as $handle => $relative ) {
			$file = $root . '/' . $relative;

			wp_register_style(
				$handle,
				plugins_url( $relative, $root . '/reader-experience.php' ),
				array(),
				is_readable( $file ) ? (string) filemtime( $file ) : null
			);
		}
	}
}
