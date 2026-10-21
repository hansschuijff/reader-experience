<?php

declare(strict_types=1);

namespace ReaderExperience\Blocks;

/**
 * Registreert blokken uit de buildmap op basis van hun block.json.
 * Alleen blokken van actieve modules worden doorgegeven.
 */
final class BlockRegistrar {

	/** Handle of the stylesheet shared by all blocks (assets/css/base.css). */
	public const BASE_STYLE_HANDLE = 'rx-base';

	public function __construct( private readonly string $build_dir ) {}

	/** @param string[] $slugs */
	public function register( array $slugs ): void {
		$this->register_base_style();

		foreach ( $slugs as $slug ) {
			$dir = rtrim( $this->build_dir, '/' ) . '/' . $slug;

			if ( ! is_readable( $dir . '/block.json' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'Reader Experience: block.json ontbreekt voor "%s". Is npm run build uitgevoerd?', $slug ) );
				}
				continue;
			}

			register_block_type( $dir );
		}
	}

	/**
	 * Registers the shared base stylesheet, so block.json files can list it in "style".
	 * Registered (not enqueued): WordPress loads it only when a block that needs it is rendered.
	 */
	private function register_base_style(): void {
		$root = dirname( $this->build_dir, 2 ); // build/blocks -> plugin root.
		$file = $root . '/assets/css/base.css';

		wp_register_style(
			self::BASE_STYLE_HANDLE,
			plugins_url( 'assets/css/base.css', $root . '/reader-experience.php' ),
			array(),
			is_readable( $file ) ? (string) filemtime( $file ) : null
		);
	}
}
