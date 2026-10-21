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
		$this->register_base_style();

		foreach ( $slugs as $slug ) {
			$dir = rtrim( $this->build_dir, '/' ) . '/' . $slug;

			if ( ! is_readable( $dir . '/block.json' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'Reader Experience: block.json is missing for "%s". Did you run npm run build?', $slug ) );
				}
				continue;
			}

			register_block_type( $dir );
		}
	}

	/**
	 * Registers the shared base stylesheet, so block.json files can list it in "style".
	 * It is registered, not enqueued: WordPress loads it only when a block that needs it renders.
	 *
	 * @return void
	 */
	private function register_base_style(): void {
		$root = dirname( $this->build_dir, 2 ); // From build/blocks to the plugin root.
		$file = $root . '/assets/css/base.css';

		wp_register_style(
			self::BASE_STYLE_HANDLE,
			plugins_url( 'assets/css/base.css', $root . '/reader-experience.php' ),
			array(),
			is_readable( $file ) ? (string) filemtime( $file ) : null
		);
	}
}
